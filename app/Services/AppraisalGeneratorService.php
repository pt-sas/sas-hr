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

        // Query all active employees with current org structure (deduplicated by employee)
        $employees = $db->table('md_employee e')
            ->select('
                e.md_employee_id, 
                e.md_levelling_id, 
                e.md_position_id, 
                e.superior_id,
                GROUP_CONCAT(DISTINCT eb.md_branch_id) as branch_ids,
                GROUP_CONCAT(DISTINCT ed.md_division_id) as division_ids
            ')
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->where('e.isactive', 'Y')
            ->where('(e.resigndate IS NULL OR e.resigndate > CURDATE() OR CAST(e.resigndate AS CHAR) = "0000-00-00")')
            ->groupBy('e.md_employee_id, e.md_levelling_id, e.md_position_id, e.superior_id')
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
                $branchIds   = !empty($employee['branch_ids']) ? explode(',', $employee['branch_ids']) : [];
                $divisionIds = !empty($employee['division_ids']) ? explode(',', $employee['division_ids']) : [];
                $levellingId = (int) ($employee['md_levelling_id'] ?? 0);
                $positionId  = (int) ($employee['md_position_id'] ?? 0);

                // Primary branch/division for snapshot
                $branchId    = !empty($branchIds) ? (int) $branchIds[0] : 0;
                $divisionId  = !empty($divisionIds) ? (int) $divisionIds[0] : 0;

                // 2. Resolve target published template ID based on Branch, Division, Levelling
                $templateId = $this->resolverService->resolveTemplateId($branchIds, $divisionIds, $levellingId);

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
                            $itemData = $item->toRawArray();
                            if (isset($itemData['config']) && is_string($itemData['config'])) {
                                $decodedConfig = json_decode($itemData['config'], true);
                                $itemData['config'] = is_array($decodedConfig) ? $decodedConfig : [];
                            }
                            $allItems[] = $itemData;
                        }
                    }

                    $defaultRatingScale = [
                        ['min' => 3.50, 'max' => 4.00, 'label' => 'Sangat Baik'],
                        ['min' => 2.75, 'max' => 3.49, 'label' => 'Baik'],
                        ['min' => 2.00, 'max' => 2.74, 'label' => 'Cukup'],
                        ['min' => 0.00, 'max' => 1.99, 'label' => 'Kurang'],
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

    /**
     * Preview appraisal batch distribution for a cycle (or general simulation if cycleId is null).
     *
     * @param int|null $cycleId
     * @return array
     */
    public function previewBatchForCycle(?int $cycleId = null): array
    {
        $db    = \Config\Database::connect();
        $cycle = null;
        if (!empty($cycleId)) {
            $cycle = $this->cycleModel->find($cycleId);
        }

        $cycleStatus = $cycle ? (is_object($cycle) ? ($cycle->status ?? 'DRAFT') : ($cycle['status'] ?? 'DRAFT')) : 'DRAFT';
        $isHistoricalCycle = ($cycle && in_array($cycleStatus, [M_AppraisalCycle::STATUS_OPEN, M_AppraisalCycle::STATUS_CLOSED, 'OPEN', 'CLOSED'], true));

        // CASE 1: RUNNING (OPEN) OR COMPLETED (CLOSED) CYCLE
        if ($isHistoricalCycle) {
            $cycleIdVal = is_object($cycle) ? ($cycle->md_appraisal_cycle_id ?? 0) : ($cycle['md_appraisal_cycle_id'] ?? 0);

            $appraisals = $db->table('md_appraisal a')
                ->select('a.md_appraisal_id, a.md_employee_id, a.md_appraisal_template_id, a.status as appraisal_status, '
                    . 'e.nik as nik, e.fullname, '
                    . 'b.name as branch_name, d.name as division_name, l.name as level_name, p.name as position_name, '
                    . 't.title as template_title, t.template_code, t.status as template_status, '
                    . 'sup.fullname as evaluator_name, gm.fullname as reviewer_name, '
                    . 'a.snapshot_evaluator_id as evaluator_id, a.snapshot_gm_id as reviewer_id')
                ->join('md_employee e', 'e.md_employee_id = a.md_employee_id', 'left')
                ->join('md_branch b', 'b.md_branch_id = a.snapshot_branch_id', 'left')
                ->join('md_division d', 'd.md_division_id = a.snapshot_division_id', 'left')
                ->join('md_position p', 'p.md_position_id = a.snapshot_position_id', 'left')
                ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                ->join('md_appraisal_template t', 't.md_appraisal_template_id = a.md_appraisal_template_id', 'left')
                ->join('md_employee sup', 'sup.md_employee_id = a.snapshot_evaluator_id', 'left')
                ->join('md_employee gm', 'gm.md_employee_id = a.snapshot_gm_id', 'left')
                ->where('a.md_appraisal_cycle_id', (int) $cycleIdVal)
                ->where('a.isactive', 'Y')
                ->orderBy('e.fullname', 'ASC')
                ->get()
                ->getResultArray();

            $rows = [];
            foreach ($appraisals as $app) {
                $tplId    = (int) ($app['md_appraisal_template_id'] ?? 0);
                $tplCode  = $app['template_code'] ?? 'TPL';
                $tplTitle = $app['template_title'] ?? "Template #{$tplId}";
                $tplName  = "[{$tplCode}] {$tplTitle}";

                $rows[] = [
                    'md_employee_id'   => (int) $app['md_employee_id'],
                    'nik'              => $app['nik'] ?? '-',
                    'name'             => $app['fullname'] ?? '-',
                    'branch'           => $app['branch_name'] ?? 'Pusat',
                    'division'         => $app['division_name'] ?? 'Semua Divisi',
                    'level'            => $app['level_name'] ?? 'Staff',
                    'position'         => $app['position_name'] ?? '-',
                    'template_id'      => $tplId,
                    'template_name'    => $tplName,
                    'template_status'  => $app['template_status'] ?? 'PUBLISHED',
                    'evaluator_id'     => (int) ($app['evaluator_id'] ?? 0),
                    'evaluator_name'   => !empty($app['evaluator_name']) ? $app['evaluator_name'] : '-',
                    'reviewer_id'      => (int) ($app['reviewer_id'] ?? 0),
                    'reviewer_name'    => !empty($app['reviewer_name']) ? $app['reviewer_name'] : '-',
                    'readiness_status' => 'ALREADY_GENERATED',
                    'status_note'      => 'Form aktif (' . ($app['appraisal_status'] ?? M_Appraisal::STATUS_PENILAIAN) . ')',
                ];
            }

            $summary = [
                'total'             => count($rows),
                'ready'             => count($rows),
                'missing_template'  => 0,
                'missing_evaluator' => 0,
                'draft_template'    => 0,
                'already_generated' => count($rows),
                'can_blast'         => false,
            ];

            return [
                'cycle'   => [
                    'id'     => $cycleIdVal,
                    'title'  => is_object($cycle) ? ($cycle->title ?? null) : ($cycle['title'] ?? null),
                    'status' => $cycleStatus,
                ],
                'summary' => $summary,
                'rows'    => $rows,
            ];
        }

        // CASE 2: DRAFT CYCLE OR GENERAL SIMULATION
        $employees = $db->table('md_employee e')
            ->select("
                e.md_employee_id, 
                e.nik as nik, 
                e.fullname, 
                e.md_levelling_id, 
                l.name as level_name, 
                e.md_position_id, 
                p.name as position_name, 
                e.superior_id,
                GROUP_CONCAT(DISTINCT eb.md_branch_id) as branch_ids,
                GROUP_CONCAT(DISTINCT b.name ORDER BY b.name SEPARATOR ', ') as branch_name,
                GROUP_CONCAT(DISTINCT ed.md_division_id) as division_ids,
                GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') as division_name
            ")
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
            ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
            ->where('e.isactive', 'Y')
            ->where('(e.resigndate IS NULL OR e.resigndate > CURDATE() OR CAST(e.resigndate AS CHAR) = "0000-00-00")')
            ->groupBy('e.md_employee_id, e.nik, e.fullname, e.md_levelling_id, l.name, e.md_position_id, p.name, e.superior_id')
            ->orderBy('e.fullname', 'ASC')
            ->get()
            ->getResultArray();

        $empNameMap = [];
        foreach ($employees as $e) {
            $empNameMap[(int) $e['md_employee_id']] = $e['fullname'];
        }

        $templateCache = [];
        $templateStatusCache = [];
        $templateRows = $db->table('md_appraisal_template')
            ->select('md_appraisal_template_id, title, template_code, status')
            ->whereIn('status', [M_AppraisalTemplate::STATUS_PUBLISHED, 'PUBLISHED', 'CO', M_AppraisalTemplate::STATUS_DRAFT, 'DRAFT', 'DR'])
            ->where('isactive', 'Y')
            ->get()
            ->getResultArray();
        foreach ($templateRows as $tRow) {
            $templateCache[(int) $tRow['md_appraisal_template_id']] = '[' . $tRow['template_code'] . '] ' . $tRow['title'];
            $templateStatusCache[(int) $tRow['md_appraisal_template_id']] = $tRow['status'];
        }

        $existingGenerated = [];
        if ($cycle) {
            $cycleIdVal = is_object($cycle) ? ($cycle->md_appraisal_cycle_id ?? 0) : ($cycle['md_appraisal_cycle_id'] ?? 0);
            $existingAppraisals = $db->table('md_appraisal a')
                ->select('a.md_appraisal_id, a.md_employee_id, a.md_appraisal_template_id, a.snapshot_evaluator_id, a.snapshot_gm_id, a.status, t.title as template_title, t.template_code, t.status as template_status')
                ->join('md_appraisal_template t', 't.md_appraisal_template_id = a.md_appraisal_template_id', 'left')
                ->where('a.md_appraisal_cycle_id', (int) $cycleIdVal)
                ->where('a.isactive', 'Y')
                ->get()
                ->getResultArray();
            foreach ($existingAppraisals as $app) {
                $existingGenerated[(int) $app['md_employee_id']] = $app;
            }
        }

        $rows             = [];
        $totalCount       = count($employees);
        $readyCount       = 0;
        $missingTplCount  = 0;
        $missingEvalCount = 0;
        $draftTplCount    = 0;
        $generatedCount   = 0;

        foreach ($employees as $emp) {
            $empId       = (int) $emp['md_employee_id'];
            $branchIds   = !empty($emp['branch_ids']) ? explode(',', $emp['branch_ids']) : [];
            $divisionIds = !empty($emp['division_ids']) ? explode(',', $emp['division_ids']) : [];
            $levellingId = (int) ($emp['md_levelling_id'] ?? 0);

            $existingApp = $existingGenerated[$empId] ?? null;

            if ($existingApp) {
                $resolvedTplId     = (int) ($existingApp['md_appraisal_template_id'] ?? 0);
                $tplCode           = $existingApp['template_code'] ?? 'TPL';
                $tplTitle          = $existingApp['template_title'] ?? "Template #{$resolvedTplId}";
                $resolvedTplName   = "[{$tplCode}] {$tplTitle}";
                $resolvedTplStatus = $existingApp['template_status'] ?? 'PUBLISHED';

                $evaluatorId = (int) ($existingApp['snapshot_evaluator_id'] ?? 0);
                $evaluatorName = ($evaluatorId > 0 && isset($empNameMap[$evaluatorId]))
                    ? $empNameMap[$evaluatorId]
                    : ($evaluatorId > 0 ? "Karyawan #{$evaluatorId}" : '-');

                $reviewerId = (int) ($existingApp['snapshot_gm_id'] ?? 0);
                $reviewerName = ($reviewerId > 0 && isset($empNameMap[$reviewerId]))
                    ? $empNameMap[$reviewerId]
                    : ($reviewerId > 0 ? "Karyawan #{$reviewerId}" : '-');

                $readiness  = 'ALREADY_GENERATED';
                $appStatus  = $existingApp['status'] ?? M_Appraisal::STATUS_PENILAIAN;
                $statusNote = 'Form telah aktif (' . $appStatus . ')';
                $generatedCount++;
            } else {
                $resolvedTplId     = 0;
                $resolvedTplName   = '-';
                $resolvedTplStatus = 'NONE';
                try {
                    $resolvedTplId     = $this->resolverService->resolveTemplateId($branchIds, $divisionIds, $levellingId, true);
                    $resolvedTplName   = $templateCache[$resolvedTplId] ?? "Template #{$resolvedTplId}";
                    $resolvedTplStatus = $templateStatusCache[$resolvedTplId] ?? 'PUBLISHED';
                } catch (\Throwable $te) {
                    $resolvedTplId     = 0;
                    $resolvedTplName   = 'Belum ditugaskan';
                    $resolvedTplStatus = 'NONE';
                }

                $hierarchy   = $this->employeeModel->getAppraisalHierarchy($empId);
                $evaluatorId = (int) ($hierarchy['manager_id'] ?? 0);
                if ($evaluatorId <= 0 && !empty($emp['superior_id'])) {
                    $evaluatorId = (int) $emp['superior_id'];
                }
                $evaluatorName = ($evaluatorId > 0 && isset($empNameMap[$evaluatorId]))
                    ? $empNameMap[$evaluatorId]
                    : ($evaluatorId > 0 ? "Karyawan #{$evaluatorId}" : '-');

                $reviewerId = (int) ($hierarchy['reviewer_id'] ?? 0);
                $reviewerName = ($reviewerId > 0 && isset($empNameMap[$reviewerId]))
                    ? $empNameMap[$reviewerId]
                    : ($reviewerId > 0 ? "Karyawan #{$reviewerId}" : '-');

                $readiness  = 'READY';
                $statusNote = 'Siap didistribusikan';

                if ($resolvedTplId <= 0) {
                    $readiness  = 'MISSING_TEMPLATE';
                    $statusNote = 'Tidak ada master template yang cocok';
                    $missingTplCount++;
                } elseif ($evaluatorId <= 0) {
                    $readiness  = 'MISSING_EVALUATOR';
                    $statusNote = 'Atasan langsung (evaluator) belum terdata';
                    $missingEvalCount++;
                } else {
                    if ($resolvedTplStatus === 'DRAFT') {
                        $draftTplCount++;
                        $statusNote = 'Template masih DRAFT (perlu dipublish sebelum rilis)';
                    }
                    $readyCount++;
                }
            }

            $rows[] = [
                'md_employee_id'   => $empId,
                'nik'              => $emp['nik'] ?? '-',
                'name'             => $emp['fullname'] ?? '-',
                'branch'           => $emp['branch_name'] ?? 'Pusat',
                'division'         => $emp['division_name'] ?? 'Semua Divisi',
                'level'            => $emp['level_name'] ?? 'Staff',
                'position'         => $emp['position_name'] ?? '-',
                'template_id'      => $resolvedTplId,
                'template_name'    => $resolvedTplName,
                'template_status'  => $resolvedTplStatus,
                'evaluator_id'     => $evaluatorId,
                'evaluator_name'   => $evaluatorName,
                'reviewer_id'      => $reviewerId,
                'reviewer_name'    => $reviewerName,
                'readiness_status' => $readiness,
                'status_note'      => $statusNote,
            ];
        }

        $isCycleOpen = ($cycle && (is_object($cycle) ? ($cycle->status ?? '') : ($cycle['status'] ?? '')) === M_AppraisalCycle::STATUS_OPEN);
        $summary = [
            'total'             => $totalCount,
            'ready'             => $readyCount,
            'missing_template'  => $missingTplCount,
            'missing_evaluator' => $missingEvalCount,
            'draft_template'    => $draftTplCount,
            'already_generated' => $generatedCount,
            'can_blast'         => (!$isCycleOpen && $readyCount > 0 && $draftTplCount === 0),
        ];

        return [
            'cycle'   => $cycle ? [
                'id'     => is_object($cycle) ? ($cycle->md_appraisal_cycle_id ?? null) : ($cycle['md_appraisal_cycle_id'] ?? null),
                'title'  => is_object($cycle) ? ($cycle->title ?? null) : ($cycle['title'] ?? null),
                'status' => is_object($cycle) ? ($cycle->status ?? null) : ($cycle['status'] ?? null),
            ] : null,
            'summary' => $summary,
            'rows'    => $rows,
        ];
    }
}