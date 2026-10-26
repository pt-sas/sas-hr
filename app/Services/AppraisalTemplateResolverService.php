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
     * Resolve the appropriate PUBLISHED template ID for a given branch, division, and levelling.
     *
     * Specificity Hierarchy:
     * - Levelling: 4 points
     * - Division:  2 points
     * - Branch:    1 point
     * - Global:    0 points
     */
    public function resolveTemplateId(int $branchId, int $divisionId, int $levellingId): int
    {
        // 1. Fetch all published active templates, prioritizing higher version numbers
        $candidates = $this->db->table('md_appraisal_template')
            ->select('md_appraisal_template_id, template_code, title, version')
            ->whereIn('status', [M_AppraisalTemplate::STATUS_PUBLISHED, 'PUBLISHED', 'CO'])
            ->where('isactive', 'Y')
            ->orderBy('version', 'DESC')
            ->orderBy('md_appraisal_template_id', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($candidates)) {
            throw new Exception("Tidak ada template appraisal PUBLISHED yang aktif di sistem.");
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
            if (! empty($tplDivisions) && ($divisionId <= 0 || ! in_array($divisionId, $tplDivisions, true))) {
                continue;
            }
            if (! empty($tplBranches) && ($branchId <= 0 || ! in_array($branchId, $tplBranches, true))) {
                continue;
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
            throw new Exception("No published appraisal template found matching Branch: {$branchId}, Division: {$divisionId}, Levelling: {$levellingId}.");
        }

        return (int) $bestMatch['md_appraisal_template_id'];
    }
}
