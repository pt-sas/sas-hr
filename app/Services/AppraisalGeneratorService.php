<?php

namespace App\Services;

use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalCycle;
use App\Models\M_AppraisalTemplate;
use App\Models\M_AppraisalTemplateItem;
use App\Models\M_AppraisalResponse;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class AppraisalGeneratorService
{
    protected M_AppraisalCycle $cycleModel;
    protected M_Appraisal $appraisalModel;
    protected M_AppraisalTemplate $templateModel;
    protected M_AppraisalTemplateItem $templateItemModel;
    protected M_AppraisalResponse $responseModel;
    protected AppraisalTemplateResolverService $resolverService;
    protected \App\Models\M_Employee $employeeModel;

    public function __construct()
    {
        $this->cycleModel        = new M_AppraisalCycle();
        $this->appraisalModel    = new M_Appraisal();
        $this->templateModel     = new M_AppraisalTemplate();
        $this->templateItemModel = new M_AppraisalTemplateItem();
        $this->responseModel     = new M_AppraisalResponse();
        $this->resolverService   = new AppraisalTemplateResolverService();
        $this->employeeModel     = new \App\Models\M_Employee();
    }

    /**
     * Batch generate appraisals for all active employees in an OPEN cycle.
     */
    public function generateBatchForCycle(int $cycleId, int $userId): array
    {
        $cycle = $this->cycleModel->find($cycleId);

        if (! $cycle || ! $cycle->isOpen()) {
            throw new Exception('Appraisals can only be generated for OPEN cycles.');
        }

        $db = \Config\Database::connect();

        // Query all active employees with current org structure
        $employees = $db->table('md_employee e')
            ->select('e.md_employee_id, eb.md_branch_id, ed.md_division_id, e.md_levelling_id, e.md_position_id, e.superior_id')
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->where('e.isactive', 'Y')
            ->where('(e.resigndate IS NULL OR e.resigndate > CURDATE() OR CAST(e.resigndate AS CHAR) = "0000-00-00")')
            ->get()
            ->getResultArray();

        if (empty($employees)) {
            throw new Exception('No active employees found to generate appraisals.');
        }

        $generatedCount = 0;
        $skippedCount   = 0;
        $errors         = [];

        // Cache resolved template snapshots in memory to prevent redundant queries
        $templateCache = [];

        foreach ($employees as $employee) {
            $employeeId = (int) $employee['md_employee_id'];

            // 1. Check duplicate safeguard (uq_appraisals_cycle_employee)
            $existing = $this->appraisalModel
                ->where('md_appraisal_cycle_id', $cycleId)
                ->where('md_employee_id', $employeeId)
                ->first();

            if ($existing) {
                $skippedCount++;
                continue;
            }

            $db->transStart();

            try {
                $branchId    = (int) ($employee['md_branch_id'] ?? 0);
                $divisionId  = (int) ($employee['md_division_id'] ?? 0);
                $levellingId = (int) ($employee['md_levelling_id'] ?? 0);
                $positionId  = (int) ($employee['md_position_id'] ?? 0);

                // 2. Resolve target published template ID based on Branch, Division, Levelling
                $templateId = $this->resolverService->resolveTemplateId($branchId, $divisionId, $levellingId);

                // 3. Load or build template JSON snapshot
                if (! isset($templateCache[$templateId])) {
                    $template = $this->templateModel->find($templateId);
                    $sections = $db->table('md_appraisal_section')
                        ->where('md_appraisal_template_id', $templateId)
                        ->where('isactive', 'Y')
                        ->orderBy('sequence_order', 'ASC')
                        ->get()
                        ->getResultArray();

                    $allItems = [];
                    foreach ($sections as $sec) {
                        $items = $this->templateItemModel
                            ->where('md_appraisal_section_id', $sec['md_appraisal_section_id'])
                            ->where('isactive', 'Y')
                            ->orderBy('sequence_order', 'ASC')
                            ->findAll();
                        foreach ($items as $item) {
                            $allItems[] = $item->toRawArray();
                        }
                    }

                    $defaultRatingScale = [
                        ['min' => 3.50, 'max' => 4.00, 'label' => 'Sangat Baik (Exceeds Expectations)'],
                        ['min' => 2.75, 'max' => 3.49, 'label' => 'Baik (Meets Expectations)'],
                        ['min' => 2.00, 'max' => 2.74, 'label' => 'Cukup (Needs Improvement)'],
                        ['min' => 0.00, 'max' => 1.99, 'label' => 'Kurang (Unsatisfactory)'],
                    ];
                    $tplConfig = [];
                    if ($template && ! empty($template->config)) {
                        $tplConfig = is_string($template->config) ? json_decode($template->config, true) : (array) $template->config;
                    }
                    if (empty($tplConfig['rating_scale'])) {
                        $tplConfig['rating_scale'] = $defaultRatingScale;
                    }

                    $templateCache[$templateId] = [
                        'template' => $template ? $template->toRawArray() : [],
                        'config'   => $tplConfig,
                        'sections' => $sections,
                        'items'    => $allItems,
                    ];
                }

                $cachedData = $templateCache[$templateId];

                // 4. Create Appraisal Header with Frozen Snapshots
                $appraisal = new Appraisal();
                $appraisal->md_appraisal_cycle_id    = $cycleId;
                $appraisal->md_appraisal_template_id = $templateId;
                $appraisal->md_employee_id           = $employeeId;

                // Deduce hierarchy (Evaluator/Manager & Reviewer/GM)
                $hierarchy   = $this->employeeModel->getAppraisalHierarchy($employeeId);
                $evaluatorId = (int) ($hierarchy['manager_id'] ?? 0);
                if ($evaluatorId <= 0 && ! empty($employee['superior_id'])) {
                    $evaluatorId = (int) $employee['superior_id'];
                }
                $gmId = (int) ($hierarchy['reviewer_id'] ?? 0);

                // Freeze organizational structure
                $appraisal->snapshot_branch_id    = $branchId ?: null;
                $appraisal->snapshot_division_id  = $divisionId;
                $appraisal->snapshot_position_id  = $positionId;
                $appraisal->snapshot_evaluator_id = $evaluatorId;
                $appraisal->snapshot_gm_id        = $gmId > 0 ? $gmId : null;

                // Freeze template JSON structure
                $appraisal->template_snapshot     = $cachedData;
                $appraisal->status                = M_Appraisal::STATUS_DRAFT_EVALUATOR;
                $appraisal->created_by            = $userId;
                $appraisal->updated_by            = $userId;

                $appraisalId = $this->appraisalModel->insert($appraisal, true);

                // 5. Pre-seed Appraisal Detail Responses
                $responseBatch = [];
                foreach ($cachedData['items'] as $item) {
                    $itemId = $item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? null;
                    if ($itemId) {
                        $responseBatch[] = [
                            'md_appraisal_id'               => $appraisalId,
                            'md_appraisal_template_item_id' => $itemId,
                            'isactive'                      => 'Y',
                            'created_by'                    => $userId,
                            'updated_by'                    => $userId,
                        ];
                    }
                }

                if (! empty($responseBatch)) {
                    $db->table('md_appraisal_response')->insertBatch($responseBatch);
                }

                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new DatabaseException("Failed transaction for employee ID {$employeeId}");
                }

                $generatedCount++;
            } catch (Exception $e) {
                $db->transRollback();
                $errors[] = "Employee ID {$employeeId}: " . $e->getMessage();
            }
        }

        return [
            'total_processed' => count($employees),
            'generated'       => $generatedCount,
            'skipped'         => $skippedCount,
            'errors'          => $errors,
        ];
    }
}