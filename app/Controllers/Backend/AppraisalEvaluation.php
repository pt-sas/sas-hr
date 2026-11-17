<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalCycle;
use App\Models\M_AppraisalResponse;
use App\Models\M_Employee;
use App\Services\AppraisalCycleServices;
use App\Services\AppraisalScoringServices;
use App\Services\AppraisalTemplateServices;
use App\Services\AppraisalWorkflowServices;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;
use TCPDF;

/**
 * Custom TCPDF class for Performance Appraisal Report
 * Automatically renders a clean 1-line running header on Page 2 and later
 */
class AppraisalPdfReport extends TCPDF
{
    public string $docNo        = '';
    public string $employeeName = '';
    public string $employeeNik  = '';

    public function Header()
    {
        // Only render running header on page 2 and later
        if ($this->getPage() > 1) {
            $this->SetY(6);
            $this->SetFont('helvetica', '', 7.5);
            $this->SetTextColor(15, 23, 42); // #0f172a (dark ink, not gray)

            $html = '
            <table width="100%" cellpadding="3" cellspacing="0" border="0" style="border-bottom: 1px solid #0f172a; font-family: helvetica, sans-serif; font-size: 7.5pt; color: #0f172a;">
                <tr>
                    <td width="30%" align="left" style="vertical-align: middle;">
                        <b>PT. SAHABAT ABADI SEJAHTERA</b>
                    </td>
                    <td width="40%" align="center" style="vertical-align: middle;">
                        <b>' . esc($this->employeeName) . '</b> (' . esc($this->employeeNik) . ')
                    </td>
                    <td width="30%" align="right" style="vertical-align: middle;">
                        ' . esc($this->docNo) . ' | Hal ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages() . '
                    </td>
                </tr>
            </table>';

            $this->writeHTML($html, true, false, true, false, '');
        }
    }
}

class AppraisalEvaluation extends BaseController
{
    protected AppraisalScoringServices $scoringService;
    protected AppraisalWorkflowServices $workflowService;
    protected AppraisalCycleServices $cycleService;
    protected M_AppraisalCycle $cycleModel;
    protected M_Employee $mEmployee;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model           = new M_Appraisal();
        $this->modelDetail     = new M_AppraisalResponse();
        $this->entity          = new Appraisal();
        $this->scoringService  = new AppraisalScoringServices();
        $this->workflowService = new AppraisalWorkflowServices();
        $this->cycleService    = new AppraisalCycleServices();
        $this->cycleModel      = new M_AppraisalCycle();
        $this->mEmployee       = new M_Employee();
        $this->primaryKey      = $this->model->primaryKey ?? 'md_appraisal_id';
        $this->db              = \Config\Database::connect();
    }

    /**
     * Helper to resolve current logged-in employee ID.
     */
    protected function getCurrentEmployeeId(): int
    {
        $empId = (int) ($this->session->get('md_employee_id') ?? 0);
        if ($empId <= 0) {
            $userId = (int) ($this->access->getSessionUser() ?? 0);
            if ($userId > 0) {
                $user = $this->db->table('sys_user')
                    ->select('md_employee_id')
                    ->where('sys_user_id', $userId)
                    ->get()
                    ->getRowArray();
                $empId = (int) ($user['md_employee_id'] ?? 0);
            }
        }
        return $empId;
    }

    /**
     * Retrieve delegated employee IDs for a user where the employees have no user account.
     */
    protected function getDelegatedEmployeesWithoutUser(int $userId): array
    {
        return $this->workflowService->getDelegatedEmployeesWithoutUser($userId);
    }

    /**
     * Check if a user is the authorized Duta for an employee without a user account.
     */
    protected function isDutaForEmployeeWithoutUser(int $userId, int $targetEmployeeId): bool
    {
        return $this->workflowService->isDutaForEmployeeWithoutUser($userId, $targetEmployeeId);
    }

    /**
     * Check if the current user has superadmin/whitelist access for appraisals.
     * Whitelisted:
     * - Accounts without employee linkage (system/developer)
     * - 'awn Admin' (sys_role_id = 1)
     * - 'Role_Super_HRD' (sys_role_id = 90) e.g., Super HRD 1 & Super HRD 2
     */
    protected function isAppraisalAdmin(int $empId = 0): bool
    {
        $userId = (int) ($this->session->get('sys_user_id') ?? $this->access->getSessionUser() ?? 0);
        $roleId = (int) ($this->session->get('sys_role_id') ?? 0);

        if ($empId <= 0 && $userId > 0) {
            return true;
        }

        if (in_array($roleId, [1, 90], true)) {
            return true;
        }

        return (bool) $this->access->getUserRoleName($userId, 'awn Admin')
            || (bool) $this->access->getUserRoleName($userId, 'Role_Super_HRD');
    }

    /**
     * Resolve workflow stage (1 to 5) for a given cycle, factoring in session debug date if set.
     */
    protected function resolveCycleStage($cycle = null): int
    {
        $debugDate   = $this->session->get('appraisal_debug_date');
        $currentDate = $debugDate ?: date('Y-m-d');

        if (! $cycle) {
            $cycle = $this->cycleService->getActiveCycle($currentDate);
        }
        if (! $cycle || ! method_exists($cycle, 'getCurrentStage')) {
            return 2;
        }
        return $cycle->getCurrentStage($currentDate);
    }

    /**
     * GET /penilaian
     * Entry point: Renders Task List Hub or Empty State
     */
    public function index()
    {
        $empId = $this->getCurrentEmployeeId();

        // Resolve simulated debug date & active workflow stage early
        $debugDate    = $this->session->get('appraisal_debug_date');
        $currentDate  = $debugDate ?: date('Y-m-d');

        // 1. Fetch active OPEN cycle (lazily auto-closes if expired)
        $activeCycle = $this->cycleService->getActiveCycle($currentDate);

        $currentStage = ($activeCycle && method_exists($activeCycle, 'getCurrentStage')) 
            ? $activeCycle->getCurrentStage($currentDate) 
            : 2;

        $userId = (int) ($this->session->get('sys_user_id') ?? 0);
        $roleId = (int) ($this->session->get('sys_role_id') ?? 0);
        $isAdmin = $this->isAppraisalAdmin($empId);
        $activeCycleId = $activeCycle ? (int) $activeCycle->md_appraisal_cycle_id : 0;

        // 2. Count tasks per role (scoped strictly to current active cycle)
        if ($activeCycleId > 0) {
            $countEvaluator = $this->model->where('md_appraisal_cycle_id', $activeCycleId)
                                          ->where('snapshot_evaluator_id', $empId)
                                          ->where('isactive', 'Y')
                                          ->where('status', M_Appraisal::STATUS_DRAFT_EVALUATOR)
                                          ->countAllResults();

            $countReviewer = $this->model->where('md_appraisal_cycle_id', $activeCycleId)
                                         ->where('snapshot_reviewer_id', $empId)
                                         ->where('isactive', 'Y')
                                         ->where('status', M_Appraisal::STATUS_REVIEW)
                                         ->countAllResults();
        } else {
            $countEvaluator = 0;
            $countReviewer  = 0;
        }

        $userId = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $delegatedEmpIdsWithoutUser = $this->getDelegatedEmployeesWithoutUser($userId);
        $empIdsForAck = array_values(array_unique(array_filter(array_merge([$empId], $delegatedEmpIdsWithoutUser))));

        // Count employee tasks: finalized history across all cycles + active-cycle pending ack when Stage >= 4
        $countEmp = 0;
        if (! empty($empIdsForAck)) {
            $countEmpHistory = $this->model->whereIn('md_employee_id', $empIdsForAck)
                                           ->where('isactive', 'Y')
                                           ->where('status', M_Appraisal::STATUS_FINALIZED)
                                           ->countAllResults();

            $countEmpActive = 0;
            if ($activeCycleId > 0 && ($isAdmin || $currentStage >= 4)) {
                $countEmpActive = $this->model->where('md_appraisal_cycle_id', $activeCycleId)
                                              ->whereIn('md_employee_id', $empIdsForAck)
                                              ->where('isactive', 'Y')
                                              ->where('status', M_Appraisal::STATUS_PENDING_ACK)
                                              ->countAllResults();
            }

            $countEmp = $countEmpHistory + $countEmpActive;
        }

        // Count personal history (Finalized past appraisals)
        $countHistory = 0;
        if ($isAdmin) {
            $countHistory = $this->model->where('isactive', 'Y')
                                        ->where('status', M_Appraisal::STATUS_FINALIZED)
                                        ->countAllResults();
        } elseif (! empty($empIdsForAck)) {
            $countHistory = $this->model->whereIn('md_employee_id', $empIdsForAck)
                                        ->where('isactive', 'Y')
                                        ->where('status', M_Appraisal::STATUS_FINALIZED)
                                        ->countAllResults();
        }

        // Count all appraisals (for Admin / Super HRD overview)
        $countAll = 0;
        if ($isAdmin) {
            $countAllQuery = $this->model->where('isactive', 'Y');
            if ($activeCycle) {
                $countAllQuery->where('md_appraisal_cycle_id', (int) $activeCycle->md_appraisal_cycle_id);
            }
            $countAll = $countAllQuery->countAllResults();
        }

        $totalActiveTasks = $countEvaluator + $countReviewer;

        // If no cycle and no active tasks and no employee appraisals and no history, render empty state
        if (! $activeCycle && $totalActiveTasks === 0 && $countEmp === 0 && $countHistory === 0 && ! $isAdmin) {
            return $this->template->render('masterdata/appraisal-filling/v_appraisal_empty');
        }

        // 3. Resolve user levelling and tab permissions
        $empLevel = null;
        if ($empId > 0) {
            $empRow = $this->db->table('md_employee e')
                ->select('l.name as level_name')
                ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                ->where('e.md_employee_id', $empId)
                ->get()->getRow();
            $empLevel = strtoupper($empRow->level_name ?? '');
        }

        // Tab permissions
        // For Admin / Super HRD roles, show ONLY the "Semua Penilaian" (ALL) tab
        if ($isAdmin) {
            $canAll       = true;
            $canEvaluator = false;
            $canReviewer  = false;
            $canEmployee  = false;
            $canHistory   = false;
            $defaultTab   = 'ALL';
        } else {
            $canAll       = false;
            $canEvaluator = in_array($empLevel, ['MANAGER', 'GM', 'DIREKSI']) || $countEvaluator > 0;
            $canReviewer  = in_array($empLevel, ['GM', 'DIREKSI']) || $countReviewer > 0;
            $canEmployee  = ($empId > 0 || ! empty($delegatedEmpIdsWithoutUser));
            $canHistory   = false;

            $defaultTab   = 'EVALUATOR';
            if ($countEvaluator === 0 && $countReviewer > 0) {
                $defaultTab = 'REVIEWER';
            } elseif ($countEvaluator === 0 && $countReviewer === 0 && $countEmp > 0) {
                $defaultTab = 'EMPLOYEE';
            } elseif (! $canEvaluator && $canReviewer) {
                $defaultTab = 'REVIEWER';
            } elseif (! $canEvaluator && $canEmployee) {
                $defaultTab = ($countEmp > 0 || $currentStage >= 4) ? 'EMPLOYEE' : ($canReviewer ? 'REVIEWER' : 'EMPLOYEE');
            }
        }

        $data = [
            'active_cycle'      => $activeCycle,
            'count_all'         => $countAll,
            'count_evaluator'   => $countEvaluator,
            'count_reviewer'    => $countReviewer,
            'count_employee'    => $countEmp,
            'count_history'     => $countHistory,
            'current_emp_id'    => $empId,
            'can_all'           => $canAll,
            'can_evaluator'     => $canEvaluator,
            'can_reviewer'      => $canReviewer,
            'can_employee'      => $canEmployee,
            'can_history'       => $canHistory,
            'has_delegated_emp' => ! empty($delegatedEmpIdsWithoutUser),
            'delegated_emp_ids' => $delegatedEmpIdsWithoutUser,
            'default_tab'       => $defaultTab,
            'current_date'      => $currentDate,
            'is_debug_date'     => ! empty($debugDate),
            'current_stage'     => $currentStage,
        ];

        return $this->template->render('masterdata/appraisal-filling/v_appraisal_task_list', $data);
    }

    /**
     * GET/POST /penilaian/showAll
     * Supports both Action F GET header queries and Datatable POST fetches
     */
    public function showAll()
    {
        if ($this->request->getMethod(true) === 'GET') {
            return $this->response->setJSON(message('success', true, ['header' => []]));
        }

        if ($this->request->getMethod(true) === 'POST') {
            try {
                $empId = $this->getCurrentEmployeeId();

                // 1. Early parse of DataTable form filters (Process.js sends form: [ {name, value}, ... ])
                $formFilter = $this->request->getPost('form') ?? [];
                $formValues = [];
                if (is_array($formFilter)) {
                    foreach ($formFilter as $row) {
                        $name  = $row['name']  ?? null;
                        $value = $row['value'] ?? null;
                        if ($name && $value !== '' && $value !== null) {
                            $formValues[$name] = $value;
                        }
                    }
                }

                $roleTab = $formValues['role_tab'] ?? $this->request->getPost('role_tab') ?? 'EVALUATOR';

                $table   = $this->model->table;
                $builder = $this->db->table($table)
                    ->select($this->model->getSelect() . ", {$table}.evaluator_submitted_at, {$table}.reviewer_reviewed_at, eval.nik as evaluator_nik, rev.nik as reviewer_nik");
                foreach ($this->model->getJoin() as $join) {
                    $builder->join($join['tableJoin'], $join['columnJoin'], $join['typeJoin']);
                }
                $builder->where("{$table}.isactive", 'Y');

                // Role Tab Scope (If Admin/System $empId <= 0 or roleTab is ALL, don't filter to single user)
                $isAdmin = $this->isAppraisalAdmin($empId);

                $debugDate   = $this->session->get('appraisal_debug_date');
                $currentDate = $debugDate ?: date('Y-m-d');
                $activeCycle = $this->cycleService->getActiveCycle($currentDate);

                if ($roleTab !== 'HISTORY' && $roleTab !== 'EMPLOYEE') {
                    if ($activeCycle) {
                        $builder->where("{$table}.md_appraisal_cycle_id", (int) $activeCycle->md_appraisal_cycle_id);
                    } else {
                        $builder->where('1 = 0', null, false);
                    }
                }

                if ($roleTab === 'HISTORY') {
                    if (! $isAdmin) {
                        $userId       = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
                        $delegatedIds = $this->getDelegatedEmployeesWithoutUser($userId);
                        $ackEmpIds    = array_values(array_unique(array_filter(array_merge([$empId], $delegatedIds))));

                        if (! empty($ackEmpIds)) {
                            $builder->whereIn("{$table}.md_employee_id", $ackEmpIds);
                        } elseif ($empId > 0) {
                            $builder->where("{$table}.md_employee_id", $empId);
                        } else {
                            $builder->where('1 = 0');
                        }
                    }
                    $builder->where("{$table}.status", M_Appraisal::STATUS_FINALIZED);
                } elseif ($empId > 0 && (! $isAdmin || $roleTab !== 'ALL')) {
                    if (! $isAdmin) {
                        $empRow = $this->db->table('md_employee e')
                            ->select('l.name as level_name')
                            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                            ->where('e.md_employee_id', $empId)
                            ->get()->getRow();
                            
                        $empLevel = strtoupper($empRow->level_name ?? '');

                        $canEvaluator = in_array($empLevel, ['MANAGER', 'GM', 'DIREKSI']);
                        $canReviewer  = in_array($empLevel, ['GM', 'DIREKSI']);

                        if ($roleTab === 'EVALUATOR' && ! $canEvaluator) {
                            $hasEvalTasks = $this->model->where('snapshot_evaluator_id', $empId)->countAllResults() > 0;
                            if (! $hasEvalTasks) {
                                $roleTab = 'EMPLOYEE';
                            }
                        } elseif ($roleTab === 'REVIEWER' && ! $canReviewer) {
                            $hasReviewerTasks = $this->model->where('snapshot_reviewer_id', $empId)->countAllResults() > 0;
                            if (! $hasReviewerTasks) {
                                $roleTab = 'EMPLOYEE';
                            }
                        }
                    }

                    if ($roleTab === 'REVIEWER') {
                        $builder->where("{$table}.snapshot_reviewer_id", $empId);
                    } elseif ($roleTab === 'EMPLOYEE') {
                        $userId       = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
                        $delegatedIds = $this->getDelegatedEmployeesWithoutUser($userId);
                        $ackEmpIds    = array_values(array_unique(array_filter(array_merge([$empId], $delegatedIds))));

                        if (! empty($ackEmpIds)) {
                            $builder->whereIn("{$table}.md_employee_id", $ackEmpIds);
                        } else {
                            $builder->where("{$table}.md_employee_id", $empId);
                        }

                        $activeCycleStage = $this->resolveCycleStage();
                        $activeCycleId    = $activeCycle ? (int) $activeCycle->md_appraisal_cycle_id : 0;

                        if ($activeCycleId > 0 && ($isAdmin || $activeCycleStage >= 4)) {
                            $builder->groupStart()
                                ->where("{$table}.status", M_Appraisal::STATUS_FINALIZED)
                                ->orGroupStart()
                                    ->where("{$table}.md_appraisal_cycle_id", $activeCycleId)
                                    ->where("{$table}.status", M_Appraisal::STATUS_PENDING_ACK)
                                ->groupEnd()
                            ->groupEnd();
                        } else {
                            // Before READING phase (Stage < 4) or no active cycle: show only finalized history
                            $builder->where("{$table}.status", M_Appraisal::STATUS_FINALIZED);
                        }
                    } elseif ($roleTab === 'EVALUATOR') {
                        $builder->where("{$table}.snapshot_evaluator_id", $empId);
                    }
                }

                // Filter search
                $search = $this->request->getPost('search')['value'] ?? null;
                if (!empty(trim($search))) {
                    $builder->groupStart()
                        ->like('e.fullname', trim($search))
                        ->orLike('e.nik', trim($search))
                        ->orLike('b.name', trim($search))
                        ->orLike('d.name', trim($search))
                        ->orLike('p.name', trim($search))
                        ->orLike('c.title', trim($search))
                        ->groupEnd();
                }

                // Form Filters (Status, Cycle ID, etc.)
                if (isset($formValues['status'])) {
                    $builder->where("{$table}.status", $formValues['status']);
                }
                if (isset($formValues['cycle_id'])) {
                    $builder->where("{$table}.md_appraisal_cycle_id", (int) $formValues['cycle_id']);
                }

                $totalFiltered = (clone $builder)->countAllResults(false);

                // Sorting: prioritize actionable tasks by role urgency, then newest first
                $this->applyUrgencyOrder($builder, $table, $roleTab);

                // Pagination
                $start  = (int) ($this->request->getPost('start') ?? 0);
                $length = (int) ($this->request->getPost('length') ?? 10);
                if ($length > 0) {
                    $builder->limit($length, $start);
                }

                $list = $builder->get()->getResultArray();
                $data = [];
                $number = $start;

                $userId       = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
                $delegatedIds = $this->getDelegatedEmployeesWithoutUser($userId);

                foreach ($list as $value) {
                    $number++;
                    $id     = $value['md_appraisal_id'];
                    $status = $value['status'] ?? M_Appraisal::STATUS_PENILAIAN;
                    $score  = $value['final_score'] !== null ? number_format((float) $value['final_score'], 2) : '-';

                    // Status Badge
                    $statusBadge = $this->renderStatusBadge($status);

                    // Workflow Stage progress dots / Stage label
                    $stageProgress = $this->renderStageProgress($status, $value);

                    // Contextual Action Buttons based on Role & Status
                    $actionBtn = $this->renderActionButton($value, $roleTab, $empId);

                    // Avatar Initials
                    $empName  = esc(mb_strtoupper($value['employee_name'] ?? '-', 'UTF-8'));
                    $nik      = esc($value['nik'] ?? '-');
                    $level    = esc($value['level_name'] ?? '');
                    $divName  = esc($value['division_name'] ?? '-');
                    $posName  = esc($value['position_name'] ?? '-');
                    $cycTitle = esc($value['cycle_title'] ?? '-');

                    $isDelegatedRow = (! empty($delegatedIds) && in_array((int) $value['md_employee_id'], $delegatedIds, true) && (int) $value['md_employee_id'] !== $empId);
                    $delegatedBadge = $isDelegatedRow ? ' <span class="badge badge-info ml-1" style="font-size: 10px; font-weight: normal;" title="Delegasi Duta"><i class="fas fa-user-friends mr-1"></i>Duta</span>' : '';

                    $karyawanCol = '<span class="font-weight-bold text-dark" title="' . $nik . '">' . $empName . '</span>' . $delegatedBadge;
                    $posisiCol   = '<span class="text-dark">' . $posName . '</span>';
                    $levelCol    = '<span class="text-dark">' . ($level ?: '-') . '</span>';
                    $cabangCol   = '<span class="text-dark">' . esc($value['branch_name'] ?: '-') . '</span>';
                    $divisiCol   = '<span class="text-dark">' . $divName . '</span>';

                    $row = [];
                    $row[] = $id;                                    // [0] ID (hidden)
                    $row[] = $number;                                // [1] No
                    $row[] = $karyawanCol;                           // [2] Karyawan
                    $row[] = $posisiCol;                             // [3] Jabatan
                    $row[] = $levelCol;                              // [4] Level
                    $row[] = $cabangCol;                             // [5] Cabang
                    $row[] = $divisiCol;                             // [6] Divisi
                    $row[] = '<strong>' . $cycTitle . '</strong>';   // [7] Siklus Penilaian
                    $row[] = $stageProgress;                         // [8] Tahapan (Plain Text)
                    $row[] = $actionBtn;                             // [9] Aksi Buttons

                    $data[] = $row;
                }

                return $this->response->setJSON([
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => $totalFiltered,
                    'recordsFiltered' => $totalFiltered,
                    'data'            => $data,
                ]);

            } catch (\Throwable $e) {
                return $this->response->setJSON([
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Order appraisal tasks by role-specific urgency so actionable items appear first.
     */
    protected function applyUrgencyOrder($builder, string $table, string $roleTab): void
    {
        $col = "{$table}.status";

        if ($roleTab === 'EVALUATOR') {
            $case = "CASE {$col} "
                . "WHEN '" . M_Appraisal::STATUS_DRAFT_EVALUATOR . "' THEN 1 "
                . "WHEN '" . M_Appraisal::STATUS_REVIEW . "' THEN 2 "
                . "WHEN '" . M_Appraisal::STATUS_SUBMITTED_EVALUATOR . "' THEN 2 "
                . "WHEN '" . M_Appraisal::STATUS_PENDING_ACK . "' THEN 3 "
                . "WHEN '" . M_Appraisal::STATUS_FINALIZED . "' THEN 4 "
                . "ELSE 5 END";
            $builder->orderBy($case, 'ASC', false);
        } elseif ($roleTab === 'REVIEWER') {
            $case = "CASE {$col} "
                . "WHEN '" . M_Appraisal::STATUS_REVIEW . "' THEN 1 "
                . "WHEN '" . M_Appraisal::STATUS_DRAFT_EVALUATOR . "' THEN 2 "
                . "WHEN '" . M_Appraisal::STATUS_SUBMITTED_EVALUATOR . "' THEN 2 "
                . "WHEN '" . M_Appraisal::STATUS_PENDING_ACK . "' THEN 3 "
                . "WHEN '" . M_Appraisal::STATUS_FINALIZED . "' THEN 4 "
                . "ELSE 5 END";
            $builder->orderBy($case, 'ASC', false);
        } elseif ($roleTab === 'EMPLOYEE') {
            $case = "CASE {$col} "
                . "WHEN '" . M_Appraisal::STATUS_PENDING_ACK . "' THEN 1 "
                . "WHEN '" . M_Appraisal::STATUS_FINALIZED . "' THEN 2 "
                . "ELSE 3 END";
            $builder->orderBy($case, 'ASC', false);
        } elseif ($roleTab === 'ALL') {
            $case = "CASE {$col} "
                . "WHEN '" . M_Appraisal::STATUS_DRAFT_EVALUATOR . "' THEN 1 "
                . "WHEN '" . M_Appraisal::STATUS_REVIEW . "' THEN 2 "
                . "WHEN '" . M_Appraisal::STATUS_PENDING_ACK . "' THEN 3 "
                . "WHEN '" . M_Appraisal::STATUS_SUBMITTED_EVALUATOR . "' THEN 4 "
                . "WHEN '" . M_Appraisal::STATUS_FINALIZED . "' THEN 5 "
                . "ELSE 6 END";
            $builder->orderBy($case, 'ASC', false);
        }

        $builder->orderBy("{$table}.md_appraisal_id", 'DESC');
    }

    /**
     * GET /penilaian/fill/(:num)
     * Screen: Dynamic Form Filling Page
     */
    public function fill($id = null)
    {
        $appraisal = $this->model->find((int) $id);

        if (! $appraisal) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Form penilaian tidak ditemukan.');
        }

        if ($appraisal->status === M_Appraisal::STATUS_FINALIZED) {
            return redirect()->to(base_url('penilaian/complete/' . $id));
        }

        $empId = $this->getCurrentEmployeeId();

        // 1. Fetch Employee, Hierarchy & Cycle info
        $employee = $this->db->table('md_employee e')
            ->select('e.md_employee_id, e.nik as nik, e.fullname as name, eb.md_branch_id, b.name as branch_name, ed.md_division_id, d.name as division_name, e.md_levelling_id, l.name as level_name, e.md_position_id, p.name as position_name')
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
            ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
            ->where('e.md_employee_id', $appraisal->md_employee_id)
            ->get()
            ->getRowArray();

        $evaluator = $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_evaluator_id)->get()->getRowArray();
        $reviewer  = $appraisal->snapshot_reviewer_id ? $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_reviewer_id)->get()->getRowArray() : null;
        $cycle     = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);

        $userId  = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $isDuta  = (! empty($userId) && $this->isDutaForEmployeeWithoutUser($userId, (int) $appraisal->md_employee_id));

        // Authorization & Reading Phase Guard for reviewed employees and their Duta
        $isAdmin = $this->isAppraisalAdmin($empId);

        if (($empId === (int) $appraisal->md_employee_id || $isDuta) && ! $isAdmin) {
            $stage = $this->resolveCycleStage($cycle);
            if ($stage < 4) {
                return redirect()->to(base_url('sas/penilaian'))->with('error', 'Form penilaian belum dapat diakses sebelum tahap pembacaan karyawan dimulai.');
            }
            if (! in_array($appraisal->status, [M_Appraisal::STATUS_PENDING_ACK, M_Appraisal::STATUS_FINALIZED], true)) {
                return redirect()->to(base_url('sas/penilaian'))->with('error', 'Penilaian kinerja belum siap untuk dibaca.');
            }
        }

        // 2. Decode template snapshot JSON
        $snapshot = is_string($appraisal->template_snapshot)
            ? json_decode($appraisal->template_snapshot, true)
            : ($appraisal->template_snapshot ?: []);
        $snapshot = AppraisalTemplateServices::expandSchema(is_array($snapshot) ? $snapshot : []);

        // 3. Load pre-seeded or existing responses
        $responses = $this->modelDetail->where('md_appraisal_id', (int) $id)
                                       ->where('isactive', 'Y')
                                       ->findAll();

        $responsesByItemId = [];
        foreach ($responses as $resp) {
            $responsesByItemId[(int) $resp->md_appraisal_template_item_id] = $resp;
        }

        // 4. Determine user viewing mode
        $mode = 'VIEW';
        $cycleStage = $this->resolveCycleStage($cycle);

        if ($cycleStage < 6) {
            if ($empId === (int) $appraisal->snapshot_evaluator_id && $appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                $mode = 'EVALUATOR';
            } elseif ($empId === (int) $appraisal->snapshot_reviewer_id && $appraisal->status === M_Appraisal::STATUS_REVIEW) {
                $mode = 'REVIEWER';
            } elseif ($isAdmin) {
                // If HR is acting as the direct reviewed employee or has selected EMPLOYEE mode
                if ($empId === (int) $appraisal->md_employee_id && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                    $mode = 'EMPLOYEE';
                } elseif ($appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $mode = 'EVALUATOR';
                } elseif ($appraisal->status === M_Appraisal::STATUS_REVIEW) {
                    $mode = 'REVIEWER';
                } elseif ($appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                    // Intervensi HR is after pembacaan (Stage 5)
                    $mode = ($cycleStage === 5) ? 'HR' : 'EMPLOYEE';
                }
            } elseif (($empId === (int) $appraisal->md_employee_id || $isDuta) && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                $mode = 'EMPLOYEE';
            }
        }

        $dutaName = null;
        if ($isDuta) {
            $userRow  = $this->db->table('sys_user')->select('name')->where('sys_user_id', $userId)->get()->getRow();
            $dutaName = $userRow->name ?? null;
        }

        $data = [
            'appraisal_id'           => (int) $id,
            'appraisal'              => $appraisal,
            'employee'               => $employee ?: [],
            'evaluator'              => $evaluator ?: ['name' => '-'],
            'reviewer'               => $reviewer ?: ['name' => '-'],
            'has_reviewer'           => ! empty($appraisal->snapshot_reviewer_id),
            'cycle'                  => $cycle,
            'template_snapshot'      => $snapshot,
            'responses_by_item_id'   => $responsesByItemId,
            'user_mode'              => $mode,
            'is_duta_acknowledgment' => $isDuta,
            'duta_name'              => $dutaName,
        ];

        return $this->template->render('masterdata/appraisal-filling/form_appraisal_filling', $data);
    }

    /**
     * Helper to prepare appraisal form data
     */
    protected function getAppraisalFormData($id, $mode = null): ?array
    {
        $appraisal = $this->model->find((int) $id);
        if (! $appraisal) {
            return null;
        }

        $empId = $this->getCurrentEmployeeId();

        $employee = $this->db->table('md_employee e')
            ->select('e.md_employee_id, e.nik as nik, e.fullname as name, eb.md_branch_id, b.name as branch_name, ed.md_division_id, d.name as division_name, e.md_levelling_id, l.name as level_name, e.md_position_id, p.name as position_name')
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
            ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
            ->where('e.md_employee_id', $appraisal->md_employee_id)
            ->get()
            ->getRowArray();

        $evaluator = $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_evaluator_id)->get()->getRowArray();
        $reviewer  = $appraisal->snapshot_reviewer_id ? $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_reviewer_id)->get()->getRowArray() : null;
        $cycle     = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);

        $isAdmin = $this->isAppraisalAdmin($empId);
        $userId  = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $isDuta  = (! empty($userId) && $this->isDutaForEmployeeWithoutUser($userId, (int) $appraisal->md_employee_id));

        // Guard: reviewed employee or their Duta cannot view form data before READING phase
        if (($empId === (int) $appraisal->md_employee_id || $isDuta) && ! $isAdmin) {
            $stage = $this->resolveCycleStage($cycle);
            if ($stage < 4 || ! in_array($appraisal->status, [M_Appraisal::STATUS_PENDING_ACK, M_Appraisal::STATUS_FINALIZED], true)) {
                return null;
            }
        }

        $snapshot = is_string($appraisal->template_snapshot)
            ? json_decode($appraisal->template_snapshot, true)
            : ($appraisal->template_snapshot ?: []);
        $snapshot = AppraisalTemplateServices::expandSchema(is_array($snapshot) ? $snapshot : []);

        $responses = $this->modelDetail->where('md_appraisal_id', (int) $id)
                                       ->where('isactive', 'Y')
                                       ->findAll();

        $responsesByItemId = [];
        foreach ($responses as $resp) {
            $responsesByItemId[(int) $resp->md_appraisal_template_item_id] = $resp;
        }

        $cycleStage = $this->resolveCycleStage($cycle);

        if (! $mode) {
            $mode = 'VIEW';

            // Once the cycle reaches Stage 6 (Finalisasi), all evaluations are locked (read-only for everyone)
            if ($cycleStage < 6) {
                if ($empId === (int) $appraisal->snapshot_evaluator_id && $appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $mode = 'EVALUATOR';
                } elseif ($empId === (int) $appraisal->snapshot_reviewer_id && $appraisal->status === M_Appraisal::STATUS_REVIEW) {
                    $mode = 'REVIEWER';
                } elseif ($isAdmin) {
                    if ($empId === (int) $appraisal->md_employee_id && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                        $mode = 'EMPLOYEE';
                    } elseif ($appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                        $mode = 'EVALUATOR';
                    } elseif ($appraisal->status === M_Appraisal::STATUS_REVIEW) {
                        $mode = 'REVIEWER';
                    } elseif ($appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                        // Intervensi HR is after pembacaan (Stage 5)
                        $mode = ($cycleStage === 5) ? 'HR' : 'EMPLOYEE';
                    }
                } elseif (($empId === (int) $appraisal->md_employee_id || $isDuta) && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                    $mode = 'EMPLOYEE';
                }
            }
        } else {
            $mode = ($cycleStage >= 6) ? 'VIEW' : strtoupper($mode);
        }

        $dutaName = null;
        if ($isDuta) {
            $userRow  = $this->db->table('sys_user')->select('name')->where('sys_user_id', $userId)->get()->getRow();
            $dutaName = $userRow->name ?? null;
        }

        return [
            'appraisal_id'           => (int) $id,
            'appraisal'              => $appraisal,
            'employee'               => $employee ?: [],
            'evaluator'              => $evaluator ?: ['name' => '-'],
            'reviewer'               => $reviewer ?: ['name' => '-'],
            'has_reviewer'           => ! empty($appraisal->snapshot_reviewer_id),
            'cycle'                  => $cycle,
            'template_snapshot'      => $snapshot,
            'responses_by_item_id'   => $responsesByItemId,
            'user_mode'              => $mode,
            'is_duta_acknowledgment' => $isDuta,
            'duta_name'              => $dutaName,
        ];
    }

    /**
     * GET /penilaian/form/(:num)
     * AJAX: Form Filling Inner HTML for Action T Single Page Mode
     */
    public function form($id = null)
    {
        $data = $this->getAppraisalFormData($id, $this->request->getGet('mode'));
        if (! $data) {
            return $this->response->setStatusCode(403)->setBody('<div class="alert alert-danger m-3"><i class="fas fa-lock mr-2"></i>Form penilaian belum dapat diakses sebelum tahap pembacaan karyawan dimulai.</div>');
        }

        return view('masterdata/appraisal-filling/form_appraisal_filling_inner', $data);
    }


    /**
     * GET /penilaian/complete/(:num)
     * Screen: Completed Read-Only Rapor View
     */
    public function complete($id = null)
    {
        $appraisal = $this->model->find((int) $id);

        if (! $appraisal) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Form penilaian tidak ditemukan.');
        }

        $employee = $this->db->table('md_employee e')
            ->select('e.md_employee_id, e.nik as nik, e.fullname as name, b.name as branch_name, d.name as division_name, l.name as level_name, p.name as position_name')
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
            ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
            ->where('e.md_employee_id', $appraisal->md_employee_id)
            ->get()
            ->getRowArray();

        $evaluator = $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_evaluator_id)->get()->getRowArray();
        $reviewer  = $appraisal->snapshot_reviewer_id ? $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_reviewer_id)->get()->getRowArray() : null;
        $cycle     = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);

        $empId   = $this->getCurrentEmployeeId();
        $isAdmin = $this->isAppraisalAdmin($empId);
        $userId  = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $isDuta  = (! empty($userId) && $this->isDutaForEmployeeWithoutUser($userId, (int) $appraisal->md_employee_id));

        // Guard: reviewed employee or their Duta cannot view complete screen before READING phase
        if (($empId === (int) $appraisal->md_employee_id || $isDuta) && ! $isAdmin) {
            $stage = $this->resolveCycleStage($cycle);
            if ($stage < 4) {
                return redirect()->to(base_url('sas/penilaian'))->with('error', 'Rapor kinerja belum dapat diakses sebelum tahap pembacaan karyawan dimulai.');
            }
        }

        $snapshot = is_string($appraisal->template_snapshot)
            ? json_decode($appraisal->template_snapshot, true)
            : ($appraisal->template_snapshot ?: []);
        $snapshot = AppraisalTemplateServices::expandSchema(is_array($snapshot) ? $snapshot : []);

        $responses = $this->modelDetail->where('md_appraisal_id', (int) $id)
                                       ->where('isactive', 'Y')
                                       ->findAll();
        $responsesByItemId = [];
        foreach ($responses as $resp) {
            $responsesByItemId[(int) $resp->md_appraisal_template_item_id] = $resp;
        }

        // Check if employee acknowledgment was signed by Duta
        $ackLog = $this->db->table('md_appraisal_audit_log l')
            ->select('l.*, u.name as actor_name')
            ->join('sys_user u', 'u.sys_user_id = l.actor_id', 'left')
            ->where('l.md_appraisal_id', $appraisal->md_appraisal_id)
            ->where('l.action', 'ACKNOWLEDGE_EMPLOYEE')
            ->orderBy('l.created_at', 'DESC')
            ->get()
            ->getRow();

        $dutaAckName = null;
        if ($ackLog && strpos($ackLog->notes ?? '', 'Duta') !== false) {
            $dutaAckName = $ackLog->actor_name ?? 'Duta';
        }

        $data = [
            'appraisal_id'         => (int) $id,
            'appraisal'            => $appraisal,
            'employee'             => $employee ?: [],
            'evaluator'            => $evaluator ?: ['name' => '-'],
            'reviewer'             => $reviewer ?: ['name' => '-'],
            'cycle'                => $cycle,
            'template_snapshot'    => $snapshot,
            'responses_by_item_id' => $responsesByItemId,
            'duta_ack_name'        => $dutaAckName,
        ];

        helper('appraisal_view');
        $isOperator = appraisal_is_operator($snapshot);
        $data['is_operator'] = $isOperator;

        return $this->template->render('masterdata/appraisal-filling/v_appraisal_complete', $data);
    }

    /**
     * GET /penilaian/scoreComparison/(:num)
     * AJAX modal view: Riwayat & Komparasi Skor Penilaian (Evaluator vs Reviewer)
     */
    public function scoreComparison($id = null)
    {
        $id = (int) $id;
        $formData = $this->getAppraisalFormData($id);
        if (! $formData) {
            return $this->response->setStatusCode(403)->setBody(
                '<div class="p-4 text-center text-muted"><i class="fas fa-lock fa-2x mb-2 d-block text-warning"></i><p class="mb-0 font-weight-bold">Komparasi skor belum dapat diakses sebelum tahap pembacaan karyawan dimulai.</p></div>'
            );
        }

        $appraisal         = $formData['appraisal'];
        $employee          = $formData['employee'];
        $evaluator         = $formData['evaluator'];
        $reviewer          = $formData['reviewer'];
        $cycle             = $formData['cycle'];
        $snapshot          = $formData['template_snapshot'];
        $responsesByItemId = $formData['responses_by_item_id'];

        helper('appraisal_view');

        $sections = $snapshot['sections'] ?? [];
        $items    = $snapshot['items'] ?? [];

        $itemsBySection = [];
        foreach ($items as $item) {
            $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
            $itemsBySection[$secId][] = $item;
        }

        $isReviewerReviewed = ! empty($appraisal->reviewer_reviewed_at) || ! empty($appraisal->reviewer_comment);
        foreach ($responsesByItemId as $r) {
            if (! empty($r->reviewer_response)) {
                $isReviewerReviewed = true;
                break;
            }
        }

        $isOperator = appraisal_is_operator($snapshot);

        if ($isOperator) {
            $weightedItems = [];
            $singleItems   = [];

            foreach ($items as $idx => $item) {
                $itemId    = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? 0);
                $weight    = (float) ($item['weight'] ?? 1.0);
                $cfg       = (array) ($item['config'] ?? []);
                $inputType = strtoupper($item['input_type'] ?? 'SCORE');
                $title     = ! empty($item['title']) ? $item['title'] : ($cfg['aspect'] ?? ('Butir ' . ($idx + 1)));

                $resp = $responsesByItemId[$itemId] ?? null;

                $evalPayload = ($resp && ! empty($resp->evaluator_response))
                    ? (is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response)
                    : [];
                $reviewerPayload = ($resp && ! empty($resp->reviewer_response))
                    ? (is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response)
                    : [];

                if ($inputType === 'WEIGHTED_CHECKLIST') {
                    $bobot = (float) ($item['weight'] ?? $cfg['bobot'] ?? 1.0);
                    if ($bobot <= 0) $bobot = 1.0;

                    $evalScore = null;
                    if (isset($evalPayload['score']) && $evalPayload['score'] !== '') {
                        $evalScore = (float) $evalPayload['score'];
                    } elseif ($resp && $resp->score !== null) {
                        $evalScore = (float) $resp->score;
                    }

                    $reviewerScore = null;
                    if ($isReviewerReviewed) {
                        if (isset($reviewerPayload['score']) && $reviewerPayload['score'] !== '') {
                            $reviewerScore = (float) $reviewerPayload['score'];
                        } elseif ($evalScore !== null) {
                            $reviewerScore = $evalScore;
                        }
                    }

                    $weightedItems[] = [
                        'item_id'        => $itemId,
                        'title'          => $title,
                        'bobot'          => $bobot,
                        'eval_score'     => $evalScore,
                        'eval_nilai'     => $evalScore !== null ? ($bobot * $evalScore) : null,
                        'reviewer_score' => $reviewerScore,
                        'reviewer_nilai' => $reviewerScore !== null ? ($bobot * $reviewerScore) : null,
                    ];
                } elseif ($inputType === 'SINGLE_CHECKLIST') {
                    $evalChecked = false;
                    if (isset($evalPayload['boolean_value'])) {
                        $evalChecked = in_array(strtoupper((string) $evalPayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                    } elseif (! empty($evalPayload['checked_actions'])) {
                        $evalChecked = true;
                    }

                    $reviewerChecked = false;
                    if ($isReviewerReviewed) {
                        if (isset($reviewerPayload['boolean_value'])) {
                            $reviewerChecked = in_array(strtoupper((string) $reviewerPayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                        } elseif (! empty($reviewerPayload['checked_actions'])) {
                            $reviewerChecked = true;
                        } else {
                            $reviewerChecked = $evalChecked;
                        }
                    }

                    $isOther = (stripos(trim($title), 'lainnya') === 0 || stripos(trim($title), 'lain-lain') === 0);

                    $singleItems[] = [
                        'item_id'             => $itemId,
                        'title'               => $title,
                        'is_other'            => $isOther,
                        'eval_checked'        => $evalChecked,
                        'eval_other_text'     => $evalPayload['other_text'] ?? '',
                        'reviewer_checked'    => $reviewerChecked,
                        'reviewer_other_text' => $reviewerPayload['other_text'] ?? ($evalPayload['other_text'] ?? ''),
                    ];
                }
            }

            $opViewData = [
                'display_mode'         => 'compare',
                'is_operator'          => true,
                'appraisal_id'         => $id,
                'appraisal'            => $appraisal,
                'employee'             => $employee,
                'evaluator'            => $evaluator,
                'reviewer'             => $reviewer,
                'cycle'                => $cycle,
                'weighted_items'       => $weightedItems,
                'single_items'         => $singleItems,
                'is_reviewer_reviewed' => $isReviewerReviewed,
                'evaluator_comment'    => $appraisal->evaluator_comment ?? '',
                'development_plan'     => $appraisal->development_plan ?? '',
                'reviewer_comment'     => $appraisal->reviewer_comment ?? '',
            ];

            return view('masterdata/appraisal-reusable/modal_score_comparison_content', $opViewData);
        }

        $comparisonSections     = [];
        $evalTotalWeightedScore     = 0.0;
        $evalTotalWeight            = 0.0;
        $reviewerTotalWeightedScore = 0.0;
        $reviewerTotalWeight        = 0.0;
        $hasOverride            = false;

        foreach ($sections as $sec) {
            $secId    = (int) $sec['md_appraisal_section_id'];
            $secType  = strtoupper($sec['section_type'] ?? 'SCORE');
            $secItems = $itemsBySection[$secId] ?? [];
            if (empty($secItems)) continue;

            $processedItems = [];

            foreach ($secItems as $itIdx => $item) {
                $itemId    = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? 0);
                $weight    = (float) ($item['weight'] ?? 1.0);
                $cfg       = (array) ($item['config'] ?? []);
                $inputType = strtoupper($item['input_type'] ?? 'SCORE');

                $compTitle  = !empty($cfg['competency']) ? $cfg['competency'] : (!empty($item['title']) ? $item['title'] : ('Kompetensi ' . ($itIdx + 1)));
                $definition = !empty($item['description']) ? $item['description'] : (!empty($cfg['definition']) ? $cfg['definition'] : '-');
                $actions    = appraisal_parse_actions($cfg['key_actions'] ?? null);

                $resp = $responsesByItemId[$itemId] ?? null;

                $evalPayload = ($resp && !empty($resp->evaluator_response))
                    ? (is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response)
                    : [];
                $evalActionScores   = $evalPayload['action_scores'] ?? [];
                $evalCheckedActions = $evalPayload['checked_actions'] ?? [];

                $reviewerPayload = ($resp && !empty($resp->reviewer_response))
                    ? (is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response)
                    : [];
                $reviewerActionScores   = $reviewerPayload['action_scores'] ?? [];
                $reviewerCheckedActions = $reviewerPayload['checked_actions'] ?? [];

                if ($inputType === 'SCORE') {
                    $hasKeyActions = !empty($actions);
                    $totalActions  = $hasKeyActions ? count($actions) : 1;

                    $evalActionSum     = 0.0;
                    $reviewerActionSum = 0.0;
                    $actionRows        = [];

                    if ($hasKeyActions) {
                        foreach ($actions as $aIdx => $actionText) {
                            $evalActScore = 3.00;
                            if (isset($evalActionScores[$aIdx]) && $evalActionScores[$aIdx] !== '') {
                                $evalActScore = (float) $evalActionScores[$aIdx];
                            } elseif (isset($evalActionScores[(string) $aIdx]) && $evalActionScores[(string) $aIdx] !== '') {
                                $evalActScore = (float) $evalActionScores[(string) $aIdx];
                            } elseif ($resp && $resp->score !== null) {
                                $evalActScore = (float) $resp->score;
                            }
                            $evalActionSum += $evalActScore;

                            $reviewerActScore = $evalActScore;
                            $isOverridden     = false;
                            if ($isReviewerReviewed) {
                                if (isset($reviewerActionScores[$aIdx]) && $reviewerActionScores[$aIdx] !== '') {
                                    $reviewerActScore = (float) $reviewerActionScores[$aIdx];
                                    if (round($reviewerActScore, 2) !== round($evalActScore, 2)) {
                                        $isOverridden = true;
                                        $hasOverride  = true;
                                    }
                                } elseif (isset($reviewerActionScores[(string) $aIdx]) && $reviewerActionScores[(string) $aIdx] !== '') {
                                    $reviewerActScore = (float) $reviewerActionScores[(string) $aIdx];
                                    if (round($reviewerActScore, 2) !== round($evalActScore, 2)) {
                                        $isOverridden = true;
                                        $hasOverride  = true;
                                    }
                                }
                            }
                            $reviewerActionSum += $reviewerActScore;

                            $actionRows[] = [
                                'index'          => $aIdx,
                                'letter'         => chr(97 + $aIdx),
                                'text'           => $actionText,
                                'eval_score'     => $evalActScore,
                                'reviewer_score' => $isReviewerReviewed ? $reviewerActScore : null,
                                'is_overridden'  => $isOverridden,
                                'delta'          => $isReviewerReviewed ? round($reviewerActScore - $evalActScore, 2) : 0,
                            ];
                        }

                        $evalAvg     = $totalActions > 0 ? ($evalActionSum / $totalActions) : 3.00;
                        $reviewerAvg = $totalActions > 0 ? ($reviewerActionSum / $totalActions) : 3.00;
                    } else {
                        $evalAvg      = ($resp && $resp->score !== null) ? (float) $resp->score : 3.00;
                        $reviewerAvg  = $evalAvg;
                        $isOverridden = false;
                        if ($isReviewerReviewed && isset($reviewerPayload['score']) && $reviewerPayload['score'] !== '') {
                            $reviewerAvg = (float) $reviewerPayload['score'];
                            if (round($reviewerAvg, 2) !== round($evalAvg, 2)) {
                                $isOverridden = true;
                                $hasOverride  = true;
                            }
                        }

                        $actionRows[] = [
                            'index'          => 0,
                            'letter'         => 'a',
                            'text'           => 'Indikator umum',
                            'eval_score'     => $evalAvg,
                            'reviewer_score' => $isReviewerReviewed ? $reviewerAvg : null,
                            'is_overridden'  => $isOverridden,
                            'delta'          => $isReviewerReviewed ? round($reviewerAvg - $evalAvg, 2) : 0,
                        ];
                    }

                    $evalAvg     = round($evalAvg, 2);
                    $reviewerAvg = round($reviewerAvg, 2);

                    $effectiveWeight = $weight > 0 ? $weight : 1.0;
                    $evalTotalWeightedScore     += ($evalAvg * $effectiveWeight);
                    $evalTotalWeight            += $effectiveWeight;
                    $reviewerTotalWeightedScore += ($reviewerAvg * $effectiveWeight);
                    $reviewerTotalWeight        += $effectiveWeight;

                    $processedItems[] = [
                        'item_id'       => $itemId,
                        'title'         => $compTitle,
                        'definition'    => $definition,
                        'weight'        => $weight,
                        'input_type'    => 'SCORE',
                        'actions'       => $actionRows,
                        'eval_avg'      => $evalAvg,
                        'reviewer_avg'  => $isReviewerReviewed ? $reviewerAvg : null,
                        'is_overridden' => $isReviewerReviewed && (round($reviewerAvg, 2) !== round($evalAvg, 2)),
                    ];
                } else {
                    // BOOLEAN / CHECKLIST
                    $actionRows = [];
                    if (!empty($actions)) {
                        foreach ($actions as $aIdx => $actionText) {
                            $evalChecked = is_array($evalCheckedActions) && appraisal_parse_checked((int) $aIdx, $evalCheckedActions);

                            $reviewerChecked = null;
                            $isOverridden    = false;

                            if ($isReviewerReviewed) {
                                if (isset($reviewerPayload['checked_actions'])) {
                                    $reviewerChecked = is_array($reviewerCheckedActions) && appraisal_parse_checked((int) $aIdx, $reviewerCheckedActions);
                                    if ($reviewerChecked !== $evalChecked) {
                                        $isOverridden = true;
                                        $hasOverride  = true;
                                    }
                                } else {
                                    $reviewerChecked = $evalChecked;
                                }
                            }

                            $actionRows[] = [
                                'index'            => $aIdx,
                                'letter'           => chr(97 + $aIdx),
                                'text'             => $actionText,
                                'eval_checked'     => $evalChecked,
                                'reviewer_checked' => $isReviewerReviewed ? $reviewerChecked : null,
                                'is_overridden'    => $isOverridden,
                            ];
                        }
                    } else {
                        $evalChecked     = in_array(strtoupper((string) ($evalPayload['boolean_value'] ?? '')), ['Y', '1', 'TRUE'], true);
                        $reviewerChecked = null;
                        $isOverridden    = false;

                        if ($isReviewerReviewed) {
                            if (isset($reviewerPayload['boolean_value'])) {
                                $reviewerChecked = in_array(strtoupper((string) $reviewerPayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                                if ($reviewerChecked !== $evalChecked) {
                                    $isOverridden = true;
                                    $hasOverride  = true;
                                }
                            } else {
                                $reviewerChecked = $evalChecked;
                            }
                        }

                        $actionRows[] = [
                            'index'            => 0,
                            'letter'           => 'a',
                            'text'             => $compTitle,
                            'eval_checked'     => $evalChecked,
                            'reviewer_checked' => $isReviewerReviewed ? $reviewerChecked : null,
                            'is_overridden'    => $isOverridden,
                        ];
                    }

                    $processedItems[] = [
                        'item_id'    => $itemId,
                        'title'      => $compTitle,
                        'definition' => $definition,
                        'weight'     => $weight,
                        'input_type' => 'BOOLEAN',
                        'actions'    => $actionRows,
                    ];
                }
            }

            $comparisonSections[] = [
                'section_id'   => $secId,
                'title'        => $sec['title'] ?? 'Section',
                'section_type' => $secType,
                'items'        => $processedItems,
            ];
        }

        $evalFinalOverall = $evalTotalWeight > 0 ? ($evalTotalWeightedScore / $evalTotalWeight) : 3.00;
        $evalFinalOverall = max(0.00, min(4.00, round($evalFinalOverall, 2)));

        if ($isReviewerReviewed) {
            $reviewerFinalOverall = $reviewerTotalWeight > 0 ? ($reviewerTotalWeightedScore / $reviewerTotalWeight) : 3.00;
            if ($appraisal->final_score !== null && !empty($appraisal->reviewer_reviewed_at) && (float) $appraisal->final_score > 0) {
                $reviewerFinalOverall = (float) $appraisal->final_score;
            }
            $reviewerFinalOverall = max(0.00, min(4.00, round($reviewerFinalOverall, 2)));
            $delta                = round($reviewerFinalOverall - $evalFinalOverall, 2);
        } else {
            $reviewerFinalOverall = null;
            $delta                = null;
        }

        $getRating = function ($score): array {
            if ($score === null) return ['label' => 'Belum Dinilai', 'class' => 'r-muted'];
            if ($score >= 3.50) return ['label' => 'Sangat Baik', 'class' => 'r-great'];
            if ($score >= 2.75) return ['label' => 'Baik', 'class' => 'r-good'];
            if ($score >= 2.00) return ['label' => 'Cukup', 'class' => 'r-fair'];
            return ['label' => 'Kurang', 'class' => 'r-low'];
        };

        $evalRating     = $getRating($evalFinalOverall);
        $reviewerRating = $getRating($reviewerFinalOverall);

        $scoreSections   = [];
        $booleanSections = [];
        foreach ($comparisonSections as $cs) {
            if (($cs['section_type'] ?? '') === 'BOOLEAN') {
                $booleanSections[] = $cs;
            } else {
                $scoreSections[] = $cs;
            }
        }

        $viewData = [
            'display_mode'           => 'compare',
            'is_operator'            => false,
            'appraisal_id'           => $id,
            'appraisal'              => $appraisal,
            'employee'               => $employee,
            'evaluator'              => $evaluator,
            'reviewer'               => $reviewer,
            'cycle'                  => $cycle,
            'sections'               => $comparisonSections,
            'scoreSections'          => $scoreSections,
            'booleanSections'        => $booleanSections,
            'eval_overall_score'     => $evalFinalOverall,
            'eval_rating'            => $evalRating,
            'reviewer_overall_score' => $reviewerFinalOverall,
            'reviewer_rating'        => $reviewerRating,
            'delta'                  => $delta,
            'is_reviewer_reviewed'   => $isReviewerReviewed,
            'has_override'           => $hasOverride,
            'evaluator_comment'      => $appraisal->evaluator_comment ?? '',
            'development_plan'       => $appraisal->development_plan ?? '',
            'reviewer_comment'       => $appraisal->reviewer_comment ?? '',
        ];

        return view('masterdata/appraisal-reusable/modal_score_comparison_content', $viewData);
    }

    /**
     * GET /penilaian/exportPDF/(:num)
     * Generate Performance Appraisal (PA) PDF Report using TCPDF (A4 Portrait)
     */
    public function exportPDF($id = null)
    {
        $id = (int) $id;
        $appraisal = $this->model->find($id);

        if (! $appraisal) {
            return redirect()->to(base_url('penilaian'))->with('error', 'Form penilaian tidak ditemukan.');
        }

        $empId   = $this->getCurrentEmployeeId();
        $isAdmin = $this->isAppraisalAdmin($empId);

        if ($empId === (int) $appraisal->md_employee_id && ! $isAdmin) {
            $cycle = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);
            $stage = $this->resolveCycleStage($cycle);
            if ($stage < 4 || ! in_array($appraisal->status, [M_Appraisal::STATUS_PENDING_ACK, M_Appraisal::STATUS_FINALIZED], true)) {
                return redirect()->to(base_url('sas/penilaian'))->with('error', 'Laporan penilaian belum dapat diunduh sebelum tahap pembacaan karyawan dimulai.');
            }
        }

        // Fetch Employee, Evaluator, and GM Details with Position Names
        $employee = $this->db->table('md_employee e')
            ->select('e.md_employee_id, e.nik as nik, e.fullname as name, b.name as branch_name, d.name as division_name, l.name as level_name, p.name as position_name')
            ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id', 'left')
            ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
            ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id', 'left')
            ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
            ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
            ->where('e.md_employee_id', $appraisal->md_employee_id)
            ->get()
            ->getRowArray();

        $evaluator = $this->db->table('md_employee e')
            ->select('e.fullname as name, p.name as position_name')
            ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
            ->where('e.md_employee_id', $appraisal->snapshot_evaluator_id)
            ->get()
            ->getRowArray();

        $reviewer = null;
        if (!empty($appraisal->snapshot_reviewer_id)) {
            $reviewer = $this->db->table('md_employee e')
                ->select('e.fullname as name, p.name as position_name')
                ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
                ->where('e.md_employee_id', $appraisal->snapshot_reviewer_id)
                ->get()
                ->getRowArray();
        }

        $cycle = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);

        $snapshot = is_string($appraisal->template_snapshot)
            ? json_decode($appraisal->template_snapshot, true)
            : ($appraisal->template_snapshot ?: []);
        $snapshot = AppraisalTemplateServices::expandSchema(is_array($snapshot) ? $snapshot : []);

        $responses = $this->modelDetail->where('md_appraisal_id', $id)
            ->where('isactive', 'Y')
            ->findAll();

        $responsesByItemId = [];
        foreach ($responses as $resp) {
            $responsesByItemId[(int) $resp->md_appraisal_template_item_id] = $resp;
        }

        // Detect if report is for Operator level or Checklist-based template
        $levelName  = strtoupper(trim($employee['level_name'] ?? ''));
        $isOperator = ($levelName === 'OPERATOR');
        if (! $isOperator) {
            foreach ($snapshot['sections'] ?? [] as $s) {
                $st = strtoupper($s['section_type'] ?? '');
                if ($st === 'WEIGHTED_CHECKLIST' || $st === 'SINGLE_CHECKLIST') {
                    $isOperator = true;
                    break;
                }
            }
        }
        if (! $isOperator) {
            foreach ($snapshot['items'] ?? [] as $it) {
                $itType = strtoupper($it['input_type'] ?? '');
                if ($itType === 'WEIGHTED_CHECKLIST' || $itType === 'SINGLE_CHECKLIST') {
                    $isOperator = true;
                    break;
                }
            }
        }

        $finalScoreVal = (float) ($appraisal->final_score ?? 0);
        $rawRating     = !empty($appraisal->rating) ? $appraisal->rating : 'Baik';
        $ratingText    = trim(preg_replace('/\s*\([^)]*\)/', '', $rawRating));

        $createdYear   = !empty($appraisal->created_at) ? date('Y', strtotime($appraisal->created_at)) : date('Y');
        $semesterVal   = is_object($cycle) ? ($cycle->semester ?? '1') : ($cycle['semester'] ?? '1');
        if (empty($semesterVal)) {
            $semesterVal = '1';
        }

        // Hitung no urut form di dalam siklus/semester ini
        $urutan = $this->model->where('md_appraisal_cycle_id', $appraisal->md_appraisal_cycle_id)
            ->where('isactive', 'Y')
            ->where('md_appraisal_id <=', $appraisal->md_appraisal_id)
            ->countAllResults();
        $noUrut = sprintf("%04d", max(1, (int) $urutan));

        // Format: PA/Tahun/Semester/No Urut (Contoh: PA/2026/1/0001)
        $docNo = 'PA/' . $createdYear . '/' . $semesterVal . '/' . $noUrut;

        $empAckDate      = !empty($appraisal->employee_acknowledged_at) ? date('d/m/Y H:i', strtotime($appraisal->employee_acknowledged_at)) . ' WIB' : null;
        $evalSubDate     = !empty($appraisal->evaluator_submitted_at) ? date('d/m/Y H:i', strtotime($appraisal->evaluator_submitted_at)) . ' WIB' : null;
        $reviewerRevDate = !empty($appraisal->reviewer_reviewed_at) ? date('d/m/Y H:i', strtotime($appraisal->reviewer_reviewed_at)) . ' WIB' : null;

        $empDateStr      = !empty($appraisal->employee_acknowledged_at) ? date('d M Y', strtotime($appraisal->employee_acknowledged_at)) : '-';
        $evalDateStr     = !empty($appraisal->evaluator_submitted_at) ? date('d M Y', strtotime($appraisal->evaluator_submitted_at)) : (!empty($appraisal->created_at) ? date('d M Y', strtotime($appraisal->created_at)) : '-');
        $reviewerDateStr = !empty($appraisal->reviewer_reviewed_at) ? date('d M Y', strtotime($appraisal->reviewer_reviewed_at)) : (!empty($appraisal->snapshot_reviewer_id) ? '-' : 'N/A');

        $pdf = new AppraisalPdfReport('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->docNo        = (string) $docNo;
        $pdf->employeeName = (string) ($employee['name'] ?? '-');
        $pdf->employeeNik  = (string) ($employee['nik'] ?? '-');
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 16, 10);
        $pdf->SetHeaderMargin(6);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();

        $viewData = [
            'appraisal'            => $appraisal,
            'employee'             => $employee ?: [],
            'evaluator'            => $evaluator ?: ['name' => '-'],
            'reviewer'             => $reviewer,
            'cycle'                => $cycle,
            'snapshot'             => $snapshot,
            'responses_by_item_id' => $responsesByItemId,
            'pdf'                  => $pdf,
            'docNo'                => $docNo,
            'finalScoreVal'        => $finalScoreVal,
            'ratingText'           => $ratingText,
            'empAckDate'           => $empAckDate,
            'evalSubDate'          => $evalSubDate,
            'reviewerRevDate'      => $reviewerRevDate,
            'empDateStr'           => $empDateStr,
            'evalDateStr'          => $evalDateStr,
            'reviewerDateStr'      => $reviewerDateStr,
        ];

        if ($isOperator) {
            $html = view('masterdata/appraisal-pdf/pdf_operator', $viewData);
            $pdf->writeHTML($html, true, false, true, false, '');

            $this->response->setContentType('application/pdf');
            return $pdf->Output('laporan-penilaian-operator-' . $id . '.pdf', 'I');
        }

        $html = view('masterdata/appraisal-pdf/pdf_staff', $viewData);
        $pdf->writeHTML($html, true, false, true, false, '');

        $this->response->setContentType('application/pdf');
        return $pdf->Output('laporan-penilaian-' . $id . '.pdf', 'I');
    }

    /**
     * GET /penilaian/show/(:num)
     * JSON API returning appraisal metadata, snapshot items, and responses
     */
    public function show($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list)) {
                    $row = $list[0];
                    $title = 'Detail Penilaian';
                    if (is_object($row)) {
                        $title = method_exists($row, 'getTitle') ? $row->getTitle() : ($row->title ?? ('Penilaian #' . ($row->md_appraisal_id ?? $id)));
                    } elseif (is_array($row)) {
                        $title = $row['title'] ?? ('Penilaian #' . ($row['md_appraisal_id'] ?? $id));
                    }

                    $fieldHeader = new \App\Entities\Table();
                    $fieldHeader->setTitle($title);
                    $fieldHeader->setTable($this->model->table);
                    $fieldHeader->setList($list);

                    $formData = $this->getAppraisalFormData($id, $this->request->getGet('mode'));

                    $result = [
                        'header' => $this->field->store($fieldHeader),
                        'html'   => $formData ? view('masterdata/appraisal-filling/form_appraisal_filling_inner', $formData) : '',
                    ];

                    $response = message('success', true, $result);
                } else {
                    $response = message('error', false, 'Data penilaian tidak ditemukan.');
                }
            } catch (\Throwable $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * POST /penilaian/create
     * Action F Standard save endpoint triggered by template .save_form
     */
    public function create()
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(message('error', false, 'Method not allowed.'));
        }

        $post        = $this->request->getVar();
        $appraisalId = (int) ($post['md_appraisal_id'] ?? $this->request->getPost('id') ?? 0);

        if ($appraisalId <= 0) {
            return $this->response->setJSON(message('error', false, 'ID Penilaian tidak valid.'));
        }

        $appraisal = $this->model->find($appraisalId);
        if (! $appraisal) {
            return $this->response->setJSON(message('error', false, 'Form penilaian tidak ditemukan.'));
        }

        if ($appraisal->status === M_Appraisal::STATUS_FINALIZED) {
            return $this->response->setJSON(message('error', false, 'Form penilaian sudah berstatus FINAL dan tidak dapat diubah.'));
        }

        try {
            $debugDate = $this->session->get('appraisal_debug_date');
            $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id, $debugDate);
        } catch (Exception $ce) {
            return $this->response->setJSON(message('error', false, $ce->getMessage()));
        }

        $userId  = (int) ($this->access->getSessionUser() ?? 1);
        $empId   = (int) ($this->getCurrentEmployeeId() ?? 0);
        $isAdmin = $this->isAppraisalAdmin($empId);
        $isDuta  = (! empty($userId) && $this->isDutaForEmployeeWithoutUser($userId, (int) $appraisal->md_employee_id));

        // Determine userMode strictly
        $userMode = 'VIEW';
        $cycle = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);
        $cycleStage = $this->resolveCycleStage($cycle);

        if ($cycleStage < 6) {
            if ($empId === (int) $appraisal->snapshot_evaluator_id && $appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                $userMode = 'EVALUATOR';
            } elseif ($empId === (int) $appraisal->snapshot_reviewer_id && $appraisal->status === M_Appraisal::STATUS_REVIEW) {
                $userMode = 'REVIEWER';
            } elseif ($isAdmin) {
                if ($empId === (int) $appraisal->md_employee_id && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK && ($post['user_mode'] ?? '') === 'EMPLOYEE') {
                    $userMode = 'EMPLOYEE';
                } elseif ($appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $userMode = 'EVALUATOR';
                } elseif ($appraisal->status === M_Appraisal::STATUS_REVIEW) {
                    $userMode = 'REVIEWER';
                } elseif ($appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                    // Intervensi HR is after pembacaan (Stage 5)
                    $userMode = ($cycleStage === 5) ? 'HR' : 'EMPLOYEE';
                }
            } elseif (($empId === (int) $appraisal->md_employee_id || $isDuta) && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                $userMode = 'EMPLOYEE';
            }
        }

        // Guard against unauthorized saves
        if ($userMode === 'VIEW') {
            return $this->response->setJSON(message('error', false, 'Anda tidak memiliki hak akses untuk mengubah penilaian ini pada status saat ini.'));
        }

        // Normalize action type (supports submit, Y, 1, or is_finalize flag from Process.js checkboxes)
        $rawAction  = strtolower(trim((string) ($post['action_type'] ?? 'save_draft')));
        $isFinalize = in_array(strtolower(trim((string) ($post['is_finalize'] ?? ''))), ['y', '1', 'true', 'submit'], true);

        if ($isFinalize || in_array($rawAction, ['submit', 'y', '1', 'true'], true)) {
            $actionType = 'submit';
        } elseif ($rawAction === 'kickback') {
            $actionType = 'kickback';
        } else {
            $actionType = 'save_draft';
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Extract and normalize responses
            $rawResponses            = $post['responses_json'] ?? $post['responses'] ?? null;
            $actionScoresFallback    = $post['action_scores'] ?? [];
            $checkedActionsFallback  = $post['checked_actions'] ?? [];
            $booleanValuesFallback   = $post['boolean_value'] ?? [];
            $responseList            = $this->normalizeResponseList($rawResponses, $actionScoresFallback, $checkedActionsFallback, $booleanValuesFallback);

            if ($userMode === 'EVALUATOR') {
                $evaluatorComment = isset($post['evaluator_comment']) ? trim($post['evaluator_comment']) : $appraisal->evaluator_comment;
                $developmentPlan  = isset($post['development_plan']) ? trim($post['development_plan']) : $appraisal->development_plan;

                $appraisal->evaluator_comment = $evaluatorComment;
                $appraisal->development_plan  = $developmentPlan;
                $appraisal->updated_by        = $userId;
                $this->model->save($appraisal);

                if (! empty($responseList)) {
                    $this->scoringService->saveResponses($appraisalId, $responseList, $userId, 'EVALUATOR');
                } else {
                    $this->scoringService->calculateAndUpdateScores($appraisalId, $userId);
                }

                if ($actionType === 'submit') {
                    if (empty($evaluatorComment)) {
                        throw new \RuntimeException('Catatan & Feedback Evaluator wajib diisi sebelum melakukan submit.');
                    }
                    $this->workflowService->submitByEvaluator($appraisalId, $userId, $evaluatorComment, $developmentPlan);
                }

            } elseif ($userMode === 'REVIEWER') {
                $evaluatorComment = isset($post['evaluator_comment']) ? trim($post['evaluator_comment']) : $appraisal->evaluator_comment;
                $developmentPlan  = isset($post['development_plan']) ? trim($post['development_plan']) : $appraisal->development_plan;
                $reviewerComment  = isset($post['reviewer_comment']) ? trim($post['reviewer_comment']) : $appraisal->reviewer_comment;

                $appraisal->evaluator_comment = $evaluatorComment;
                $appraisal->development_plan  = $developmentPlan;
                $appraisal->reviewer_comment  = $reviewerComment;
                $appraisal->updated_by        = $userId;
                $this->model->save($appraisal);

                if (! empty($responseList)) {
                    $this->scoringService->saveResponses($appraisalId, $responseList, $userId, 'REVIEWER');
                }

                if ($actionType === 'kickback') {
                    $reason = trim($post['kickback_reason'] ?? $reviewerComment ?? '');
                    if (empty($reason)) {
                        throw new \RuntimeException('Alasan revisi wajib diisi jika mengembalikan form.');
                    }
                    $this->workflowService->kickbackByReviewer($appraisalId, $userId, $reason);
                } elseif ($actionType === 'submit') {
                    $this->workflowService->reviewByReviewer($appraisalId, $userId, $reviewerComment);
                }

            } elseif ($userMode === 'EMPLOYEE') {
                $employeeComment = isset($post['employee_comment']) ? trim($post['employee_comment']) : $appraisal->employee_comment;
                if ($actionType === 'submit') {
                    $this->workflowService->acknowledgeByEmployee($appraisalId, $userId, $employeeComment);
                } else {
                    if ($employeeComment !== null) {
                        $appraisal->employee_comment = $employeeComment;
                    }
                    $appraisal->updated_by = $userId;
                    $this->model->save($appraisal);
                }
            } elseif ($userMode === 'HR') {
                // Intervensi HR mode: HR can edit responses, evaluator comment, development plan, or finalize
                $evaluatorComment = isset($post['evaluator_comment']) ? trim($post['evaluator_comment']) : $appraisal->evaluator_comment;
                $developmentPlan  = isset($post['development_plan']) ? trim($post['development_plan']) : $appraisal->development_plan;
                $reviewerComment  = isset($post['reviewer_comment']) ? trim($post['reviewer_comment']) : $appraisal->reviewer_comment;
                $employeeComment  = isset($post['employee_comment']) ? trim($post['employee_comment']) : $appraisal->employee_comment;

                $appraisal->evaluator_comment = $evaluatorComment;
                $appraisal->development_plan  = $developmentPlan;
                $appraisal->reviewer_comment  = $reviewerComment;
                if ($employeeComment !== null) {
                    $appraisal->employee_comment = $employeeComment;
                }
                $appraisal->updated_by = $userId;
                $this->model->save($appraisal);

                if (! empty($responseList)) {
                    $this->scoringService->saveResponses($appraisalId, $responseList, $userId, 'REVIEWER');
                } else {
                    $this->scoringService->calculateAndUpdateScores($appraisalId, $userId);
                }

                if ($actionType === 'submit') {
                    // HR finalizes / acknowledges on behalf of reading completion
                    $this->workflowService->acknowledgeByEmployee($appraisalId, $userId, $employeeComment);
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new \RuntimeException('Gagal memproses transaksi database penyimpanan penilaian.');
            }

            $db->transCommit();

            if ($actionType === 'submit') {
                $msg = ($userMode === 'EMPLOYEE') ? 'Penilaian berhasil dikonfirmasi dan difinalisasi.' : 'Penilaian berhasil disubmit.';
            } elseif ($actionType === 'kickback') {
                $msg = 'Form berhasil dikembalikan untuk revisi.';
            } else {
                $msg = ($userMode === 'EMPLOYEE') ? 'Form tersimpan. Centang konfirmasi untuk menyelesaikan tahap pembacaan.' : 'Data penilaian berhasil disimpan.';
            }
            return $this->response->setJSON(message('success', true, $msg));

        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * POST /penilaian/saveDraft/(:num)
     * Auto-save or draft save of item responses without changing workflow status
     */
    public function saveDraft($id = null)
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                if (isset($json['responses']) && is_array($json['responses'])) {
                    $json['responses'] = json_encode($json['responses']);
                }
                $_POST = $json;
            }
        }

        $post        = $this->request->getPost();
        $appraisalId = (int) $id;

        $appraisal = $this->model->find($appraisalId);

        if (! $appraisal) {
            return $this->response->setJSON(message('error', false, 'Form penilaian tidak ditemukan.'));
        }

        if ($appraisal->status === M_Appraisal::STATUS_FINALIZED) {
            return $this->response->setJSON(message('error', false, 'Form penilaian terkunci karena sudah berstatus FINALIZED.'));
        }

        try {
            $debugDate = $this->session->get('appraisal_debug_date');
            $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id, $debugDate);

            $userId = (int) ($this->access->getSessionUser() ?? 1);
            $empId  = $this->getCurrentEmployeeId();

            if (isset($post['evaluator_comment'])) {
                $appraisal->evaluator_comment = $post['evaluator_comment'];
            }
            if (isset($post['development_plan'])) {
                $appraisal->development_plan = $post['development_plan'];
            }
            if (isset($post['reviewer_comment'])) {
                $appraisal->reviewer_comment = $post['reviewer_comment'];
            }

            $appraisal->updated_by = $userId;
            $this->model->save($appraisal);

            // Save individual item responses
            $rawList                 = $post['responses_json'] ?? $post['responses'] ?? (! empty($post['table']) ? $post['table'] : null);
            $actionScoresFallback    = $post['action_scores'] ?? [];
            $checkedActionsFallback  = $post['checked_actions'] ?? [];
            $booleanValuesFallback   = $post['boolean_value'] ?? [];
            $responseList            = $this->normalizeResponseList($rawList, $actionScoresFallback, $checkedActionsFallback, $booleanValuesFallback);

            $actorRole = ($empId === (int) $appraisal->snapshot_reviewer_id && $appraisal->status === M_Appraisal::STATUS_REVIEW) ? 'REVIEWER' : 'EVALUATOR';

            if (is_array($responseList) && ! empty($responseList)) {
                $this->scoringService->saveResponses($appraisalId, $responseList, $userId, $actorRole);
            } else {
                $this->scoringService->calculateAndUpdateScores($appraisalId, $userId);
            }

            $updatedAppraisal = $this->model->find($appraisalId);

            return $this->response->setJSON(message('success', true, 'Draft penilaian berhasil disimpan.', $updatedAppraisal));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * POST /penilaian/submit/(:num)
     * Evaluator submits form to Reviewer or Employee sign-off
     */
    public function submit($id = null)
    {
        if (strpos($this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                if (isset($json['responses']) && is_array($json['responses'])) {
                    $json['responses'] = json_encode($json['responses']);
                }
                $_POST = $json;
            }
        }

        $post        = $this->request->getPost();
        $appraisalId = (int) $id;

        $appraisal = $this->model->find($appraisalId);

        if (! $appraisal) {
            return $this->response->setJSON(message('error', false, 'Form penilaian tidak ditemukan.'));
        }

        try {
            $debugDate = $this->session->get('appraisal_debug_date');
            $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id, $debugDate);

            $userId = (int) ($this->access->getSessionUser() ?? 1);
            $empId  = $this->getCurrentEmployeeId();

            // Validate authorization
            if ($empId > 0 && (int) $appraisal->snapshot_evaluator_id !== $empId) {
                return $this->response->setJSON(message('error', false, 'Anda bukan atasan penilai yang ditugaskan untuk form ini.'));
            }

            // Save item responses first
            $rawList = ! empty($post['responses_json']) ? $post['responses_json'] : (! empty($post['responses']) ? $post['responses'] : (! empty($post['table']) ? $post['table'] : null));
            $responseList = $this->normalizeResponseList($rawList);

            if (is_array($responseList) && ! empty($responseList)) {
                $this->scoringService->saveResponses($appraisalId, $responseList, $userId, 'EVALUATOR');
            } else {
                $this->scoringService->calculateAndUpdateScores($appraisalId, $userId);
            }

            $evaluatorComment = $post['evaluator_comment'] ?? $appraisal->evaluator_comment;
            $developmentPlan  = $post['development_plan'] ?? $appraisal->development_plan;

            if (empty(trim($evaluatorComment ?? ''))) {
                return $this->response->setJSON(message('error', false, 'Catatan & feedback evaluasi wajib diisi sebelum melakukan submit!'));
            }

            // Transition status via workflow service
            $this->workflowService->submitByEvaluator($appraisalId, $userId, $evaluatorComment, $developmentPlan);

            return $this->response->setJSON(message('success', true, 'Evaluasi kinerja berhasil disubmit!'));
        } catch (Exception $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    protected function renderStageProgress(string $status, array $row = []): string
    {
        // Resolve cycle stage if row provides cycle info
        $cycleStage = null;
        if (! empty($row['start_date']) || ! empty($row['deadline_filling'])) {
            $today = ! empty($this->session->get('appraisal_debug_date')) ? strtotime($this->session->get('appraisal_debug_date')) : time();
            $fill  = ! empty($row['deadline_filling']) ? strtotime($row['deadline_filling']) : null;
            $rev   = ! empty($row['deadline_review']) ? strtotime($row['deadline_review']) : null;
            $read  = ! empty($row['deadline_reading']) ? strtotime($row['deadline_reading']) : null;
            $over  = ! empty($row['deadline_override']) ? strtotime($row['deadline_override']) : null;
            $end   = ! empty($row['end_date']) ? strtotime($row['end_date']) : null;

            if ($fill && $today <= $fill) {
                $cycleStage = 2;
            } elseif ($rev && $today <= $rev) {
                $cycleStage = 3;
            } elseif ($read && $today <= $read) {
                $cycleStage = 4;
            } elseif ($over && $today <= $over) {
                $cycleStage = 5;
            } elseif ($end && $today > $end) {
                $cycleStage = 6;
            }
        }

        switch ($status) {
            case M_Appraisal::STATUS_DRAFT_EVALUATOR:
                $label = 'Pengisian';
                break;
            case M_Appraisal::STATUS_SUBMITTED_EVALUATOR:
            case M_Appraisal::STATUS_REVIEW:
                $label = 'Peninjauan';
                break;
            case M_Appraisal::STATUS_PENDING_ACK:
                $label = ($cycleStage === 5) ? 'Intervensi HR' : 'Pembacaan';
                break;
            case M_Appraisal::STATUS_FINALIZED:
                $label = 'Final';
                break;
            default:
                $label = $status;
                break;
        }

        return esc($label);
    }

    protected function renderStatusBadge(string $status): string
    {
        return esc(M_Appraisal::getStatusLabel($status));
    }

    protected function renderActionButton(array $row, string $roleTab, int $empId): string
    {
        $id      = $row['md_appraisal_id'];
        $empId   = $this->getCurrentEmployeeId();
        $isAdmin = $this->isAppraisalAdmin($empId);
        $userId  = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $isDuta  = (! empty($userId) && $this->isDutaForEmployeeWithoutUser($userId, (int) ($row['md_employee_id'] ?? 0)));

        return $this->template->appraisalTableButton('evaluation', $id, [
            'row'      => $row,
            'role_tab' => $roleTab,
            'emp_id'   => $empId,
            'is_admin' => $isAdmin,
            'is_duta'  => $isDuta,
        ]);
    }

    /**
     * Normalize responses payload whether submitted as JSON string or nested array
     */
    protected function normalizeResponseList($raw, array $actionScoresFallback = [], array $checkedActionsFallback = [], array $booleanValuesFallback = []): array
    {
        $list = [];
        if (! empty($raw)) {
            $list = is_string($raw) ? json_decode($raw, true) : $raw;
        }
        if (! is_array($list)) {
            $list = [];
        }

        $itemsById = [];
        foreach ($list as $k => $row) {
            if (! is_array($row)) {
                continue;
            }
            $itemId = (int) ($row['md_appraisal_template_item_id'] ?? (is_numeric($k) ? $k : 0));
            if ($itemId <= 0) {
                continue;
            }
            $row['md_appraisal_template_item_id'] = $itemId;
            $itemsById[$itemId] = $row;
        }

        // Merge fallback action_scores if provided
        if (! empty($actionScoresFallback) && is_array($actionScoresFallback)) {
            foreach ($actionScoresFallback as $fbItemId => $scores) {
                $fbItemId = (int) $fbItemId;
                if ($fbItemId <= 0 || ! is_array($scores)) {
                    continue;
                }
                if (! isset($itemsById[$fbItemId])) {
                    $itemsById[$fbItemId] = [
                        'md_appraisal_template_item_id' => $fbItemId,
                    ];
                }
                if (empty($itemsById[$fbItemId]['action_scores'])) {
                    $itemsById[$fbItemId]['action_scores'] = array_values($scores);
                }
            }
        }

        // Merge fallback checked_actions if provided
        if (! empty($checkedActionsFallback) && is_array($checkedActionsFallback)) {
            foreach ($checkedActionsFallback as $fbItemId => $actions) {
                $fbItemId = (int) $fbItemId;
                if ($fbItemId <= 0) {
                    continue;
                }
                if (! isset($itemsById[$fbItemId])) {
                    $itemsById[$fbItemId] = [
                        'md_appraisal_template_item_id' => $fbItemId,
                    ];
                }
                if (! array_key_exists('checked_actions', $itemsById[$fbItemId])) {
                    $itemsById[$fbItemId]['checked_actions'] = is_array($actions) ? array_values($actions) : [$actions];
                    $itemsById[$fbItemId]['boolean_value']   = 'Y';
                }
            }
        }

        // Merge fallback boolean_value if provided
        if (! empty($booleanValuesFallback) && is_array($booleanValuesFallback)) {
            foreach ($booleanValuesFallback as $fbItemId => $bVal) {
                $fbItemId = (int) $fbItemId;
                if ($fbItemId <= 0) {
                    continue;
                }
                if (! isset($itemsById[$fbItemId])) {
                    $itemsById[$fbItemId] = [
                        'md_appraisal_template_item_id' => $fbItemId,
                    ];
                }
                if (! isset($itemsById[$fbItemId]['boolean_value'])) {
                    $itemsById[$fbItemId]['boolean_value'] = in_array(strtoupper((string) $bVal), ['Y', '1', 'TRUE'], true) ? 'Y' : 'N';
                }
            }
        }

        $normalized = [];
        foreach ($itemsById as $itemId => $row) {
            $isBooleanItem = isset($row['checked_actions']) || isset($row['boolean_value']);
            if ($isBooleanItem) {
                unset($row['score']);
                if (isset($row['checked_actions']) && is_array($row['checked_actions'])) {
                    $hasYorN = false;
                    foreach ($row['checked_actions'] as $v) {
                        if (in_array(strtoupper((string) $v), ['Y', 'N'], true)) {
                            $hasYorN = true;
                            break;
                        }
                    }
                    if ($hasYorN) {
                        $indices = [];
                        foreach ($row['checked_actions'] as $k => $v) {
                            if (in_array(strtoupper((string) $v), ['Y', '1', 'TRUE'], true)) {
                                $indices[] = (int) $k;
                            }
                        }
                        $row['checked_actions'] = array_values($indices);
                    } else {
                        $row['checked_actions'] = array_values(array_map('intval', array_filter($row['checked_actions'], 'is_numeric')));
                    }
                }
            } elseif (! empty($row['action_scores']) && is_array($row['action_scores'])) {
                $row['action_scores'] = array_values(array_map(function ($s) {
                    return round((float) $s, 2);
                }, $row['action_scores']));
                unset($row['score']);
            } elseif (isset($row['score']) && $row['score'] !== '') {
                $row['score'] = round((float) $row['score'], 2);
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * POST/GET /penilaian/setDebugDate
     * Set, jump stage, or reset simulated debug date in session
     */
    public function setDebugDate()
    {
        $post   = $this->request->getVar();
        $action = $post['action'] ?? $this->request->getGet('action') ?? null;
        $date   = trim((string) ($post['debug_date'] ?? $this->request->getGet('debug_date') ?? ''));
        $stage  = (int) ($post['stage'] ?? $this->request->getGet('stage') ?? 0);

        if ($action === 'reset' || $date === 'reset' || $date === 'today') {
            $this->session->remove('appraisal_debug_date');
            return $this->response->setJSON(message('success', true, [
                'message'       => 'Debug date direset ke tanggal sistem hari ini (' . date('d M Y') . ').',
                'debug_date'    => date('Y-m-d'),
                'is_debug'      => false,
                'current_stage' => 2,
            ]));
        }

        $activeCycle = $this->cycleModel->where('status', M_AppraisalCycle::STATUS_OPEN)
                                        ->where('isactive', 'Y')
                                        ->first();

        // If jump to specific stage requested
        if ($stage >= 1 && $stage <= 6 && $activeCycle && method_exists($activeCycle, 'getStageTargetDate')) {
            $date = $activeCycle->getStageTargetDate($stage);
        }

        if (empty($date) || ! strtotime($date)) {
            return $this->response->setJSON(message('error', false, 'Format tanggal tidak valid.'));
        }

        $formattedDate = date('Y-m-d', strtotime($date));
        $this->session->set('appraisal_debug_date', $formattedDate);

        $stageNum = ($activeCycle && method_exists($activeCycle, 'getCurrentStage')) 
            ? $activeCycle->getCurrentStage($formattedDate) 
            : 2;

        return $this->response->setJSON(message('success', true, [
            'message'       => 'Debug date aktif: ' . date('d M Y', strtotime($formattedDate)) . ' (Tahap ' . $stageNum . ').',
            'debug_date'    => $formattedDate,
            'is_debug'      => true,
            'current_stage' => $stageNum,
        ]));
    }
}
