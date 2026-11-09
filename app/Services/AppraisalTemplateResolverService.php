<?php

namespace App\Services;

use App\Models\M_AppraisalTemplate;
use CodeIgniter\Database\BaseConnection;
use Exception;

class AppraisalTemplateResolverService
{
    protected BaseConnection $db;
    protected M_AppraisalTemplate $templateModel;

    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->templateModel = new M_AppraisalTemplate();
    }

    /**
     * Resolve the appropriate template ID for a given branch, division, and levelling.
     *
     * Specificity Hierarchy:
     * - Levelling: 4 points
     * - Division:  2 points
     * - Branch:    1 point
     * - Global:    0 points
     *
     * @param int|array $branchId
     * @param int|array $divisionId
     * @param int       $levellingId
     * @param bool      $includeDraft Whether to consider DRAFT templates (e.g. for simulations)
     * @return int
     */
    public function resolveTemplateId($branchId, $divisionId, int $levellingId, bool $includeDraft = false): int
    {
        $branchIds = is_array($branchId) ? array_filter(array_map('intval', $branchId)) : ($branchId > 0 ? [(int) $branchId] : []);
        $divisionIds = is_array($divisionId) ? array_filter(array_map('intval', $divisionId)) : ($divisionId > 0 ? [(int) $divisionId] : []);

        $allowedStatuses = [M_AppraisalTemplate::STATUS_PUBLISHED, 'PUBLISHED', 'CO'];
        if ($includeDraft) {
            $allowedStatuses[] = M_AppraisalTemplate::STATUS_DRAFT;
            $allowedStatuses[] = 'DRAFT';
            $allowedStatuses[] = 'DR';
        }

        // 1. Fetch active templates, prioritizing higher version numbers
        $candidates = $this->db->table('md_appraisal_template')
            ->select('md_appraisal_template_id, template_code, title, version, status')
            ->whereIn('status', $allowedStatuses)
            ->where('isactive', 'Y')
            ->orderBy('version', 'DESC')
            ->orderBy('md_appraisal_template_id', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($candidates)) {
            $statusLabel = $includeDraft ? 'aktif' : 'PUBLISHED yang aktif';
            throw new Exception("Tidak ada template appraisal {$statusLabel} di sistem.");
        }

        $bestMatch = null;
        $bestScore = -1;

        foreach ($candidates as $tpl) {
            $tplId = (int) $tpl['md_appraisal_template_id'];

            // Load scoping mappings for this candidate
            $tplBranches = array_column(
                $this->db->table('md_template_branch')
                    ->select('md_branch_id')
                    ->where('md_appraisal_template_id', $tplId)
                    ->where('isactive', 'Y')
                    ->get()
                    ->getResultArray(),
                'md_branch_id'
            );

            $tplDivisions = array_column(
                $this->db->table('md_template_division')
                    ->select('md_division_id')
                    ->where('md_appraisal_template_id', $tplId)
                    ->where('isactive', 'Y')
                    ->get()
                    ->getResultArray(),
                'md_division_id'
            );

            $tplLevellings = array_column(
                $this->db->table('md_template_levelling')
                    ->select('md_levelling_id')
                    ->where('md_appraisal_template_id', $tplId)
                    ->where('isactive', 'Y')
                    ->get()
                    ->getResultArray(),
                'md_levelling_id'
            );

            $tplBranches   = array_map('intval', $tplBranches);
            $tplDivisions  = array_map('intval', $tplDivisions);
            $tplLevellings = array_map('intval', $tplLevellings);

            // Filtering: If template has specific scoping in a dimension, the employee must match
            if (! empty($tplLevellings) && ($levellingId <= 0 || ! in_array($levellingId, $tplLevellings, true))) {
                continue;
            }
            if (! empty($tplDivisions)) {
                if (empty($divisionIds) || empty(array_intersect($divisionIds, $tplDivisions))) {
                    continue;
                }
            }
            if (! empty($tplBranches)) {
                if (empty($branchIds) || empty(array_intersect($branchIds, $tplBranches))) {
                    continue;
                }
            }

            // Specificity score: Levelling (4) > Division (2) > Branch (1) > Global (0)
            $score = (! empty($tplLevellings) ? 4 : 0)
                + (! empty($tplDivisions) ? 2 : 0)
                + (! empty($tplBranches) ? 1 : 0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $tpl;
            }
        }

        if (! $bestMatch) {
            $branchStr = implode(',', $branchIds);
            $divisionStr = implode(',', $divisionIds);
            throw new Exception("No published appraisal template found matching Branch: [{$branchStr}], Division: [{$divisionStr}], Levelling: {$levellingId}.");
        }

        return (int) $bestMatch['md_appraisal_template_id'];
    }
}
