<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_AppraisalTemplate;
use CodeIgniter\Database\BaseConnection;

class AppraisalTemplateResolverServices extends BaseServices
{
    protected BaseConnection $db;
    protected M_AppraisalTemplate $templateModel;

    public function __construct()
    {
        parent::__construct();
        $this->db            = \Config\Database::connect();
        $this->templateModel = new M_AppraisalTemplate();
    }

    /**
     * Resolve the appropriate template ID for a given branch, division, levelling, and position.
     *
     * Specificity Hierarchy:
     * - Position:  8 points
     * - Levelling: 4 points
     * - Division:  2 points
     * - Branch:    1 point
     * - Global:    0 points
     *
     * @param int|array $branchId
     * @param int|array $divisionId
     * @param int       $levellingId
     * @param int       $positionId
     * @param bool      $includeDraft Whether to consider DRAFT templates (e.g. for simulations)
     * @return int
     */
    public function resolveTemplateId($branchId, $divisionId, int $levellingId, int $positionId = 0, bool $includeDraft = false): int
    {
        $branchIds = is_array($branchId) ? array_filter(array_map('intval', $branchId)) : ($branchId > 0 ? [(int) $branchId] : []);
        $divisionIds = is_array($divisionId) ? array_filter(array_map('intval', $divisionId)) : ($divisionId > 0 ? [(int) $divisionId] : []);

        $allowedStatuses = [M_AppraisalTemplate::STATUS_PUBLISHED];
        if ($includeDraft) {
            $allowedStatuses[] = M_AppraisalTemplate::STATUS_DRAFT;
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
            throw new NotFoundException("Tidak ada template appraisal {$statusLabel} di sistem.");
        }

        // 2. Load all active scopes for candidate templates across the 4 mapping tables
        $candidateIds = array_column($candidates, 'md_appraisal_template_id');
        $scopesByTpl  = [];
        foreach (AppraisalTemplateServices::SCOPE_TABLES as $cfg) {
            $rows = $this->db->table($cfg['table'])
                ->select("md_appraisal_template_id, {$cfg['fk']} AS scope_id")
                ->whereIn('md_appraisal_template_id', $candidateIds)
                ->where('isactive', 'Y')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $tId = (int) $row['md_appraisal_template_id'];
                $scopesByTpl[$tId][$cfg['type']][] = (int) $row['scope_id'];
            }
        }

        $bestMatch = null;
        $bestScore = -1;

        foreach ($candidates as $tpl) {
            $tplId         = (int) $tpl['md_appraisal_template_id'];
            $tplScopes     = $scopesByTpl[$tplId] ?? [];
            $tplBranches   = $tplScopes['BRANCH'] ?? [];
            $tplDivisions  = $tplScopes['DIVISION'] ?? [];
            $tplLevellings = $tplScopes['LEVELLING'] ?? [];
            $tplPositions  = $tplScopes['POSITION'] ?? [];

            // Filtering: If template has specific scoping in a dimension, the employee must match
            if (! empty($tplPositions) && ($positionId <= 0 || ! in_array($positionId, $tplPositions, true))) {
                continue;
            }
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

            // Specificity score: Position (8) > Levelling (4) > Division (2) > Branch (1) > Global (0)
            $score = (! empty($tplPositions) ? 8 : 0)
                + (! empty($tplLevellings) ? 4 : 0)
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
            throw new NotFoundException("No published appraisal template found matching Branch: [{$branchStr}], Division: [{$divisionStr}], Levelling: {$levellingId}, Position: {$positionId}.");
        }

        return (int) $bestMatch['md_appraisal_template_id'];
    }
}
