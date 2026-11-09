<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalCycle;
use App\Models\M_AppraisalResponse;
use App\Models\M_Employee;
use App\Services\AppraisalCycleService;
use App\Services\AppraisalScoringService;
use App\Services\AppraisalWorkflowService;
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

class AppraisalEvaluationController extends BaseController
{
    protected AppraisalScoringService $scoringService;
    protected AppraisalWorkflowService $workflowService;
    protected AppraisalCycleService $cycleService;
    protected M_AppraisalCycle $cycleModel;
    protected M_Employee $mEmployee;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model           = new M_Appraisal();
        $this->modelDetail     = new M_AppraisalResponse();
        $this->entity          = new Appraisal();
        $this->scoringService  = new AppraisalScoringService();
        $this->workflowService = new AppraisalWorkflowService();
        $this->cycleService    = new AppraisalCycleService();
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

        // 2. Count tasks per role
        $countEvaluator = $this->model->where('snapshot_evaluator_id', $empId)
                                      ->where('isactive', 'Y')
                                      ->where('status', M_Appraisal::STATUS_DRAFT_EVALUATOR)
                                      ->countAllResults();

        $countGm = $this->model->where('snapshot_gm_id', $empId)
                               ->where('isactive', 'Y')
                               ->where('status', M_Appraisal::STATUS_REVIEW_GM)
                               ->countAllResults();

        $userId = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $delegatedEmpIdsWithoutUser = $this->getDelegatedEmployeesWithoutUser($userId);
        $empIdsForAck = array_values(array_unique(array_filter(array_merge([$empId], $delegatedEmpIdsWithoutUser))));

        // Count employee tasks: reviewed employees can only see appraisals once READING phase (Stage >= 4) commences
        if (! $isAdmin && $currentStage < 4) {
            $countEmp = 0;
        } else {
            if (! empty($empIdsForAck)) {
                $countEmpQuery = $this->model->whereIn('md_employee_id', $empIdsForAck)
                                             ->where('isactive', 'Y');
                if (! $isAdmin) {
                    $countEmpQuery->whereIn('status', [M_Appraisal::STATUS_PENDING_ACK, M_Appraisal::STATUS_FINALIZED]);
                }
                $countEmp = $countEmpQuery->countAllResults();
            } else {
                $countEmp = 0;
            }
        }

        // Count personal history (Finalized past appraisals)
        $countHistory = 0;
        if (! empty($empIdsForAck)) {
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

        $totalActiveTasks = $countEvaluator + $countGm;

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
            $canGm        = false;
            $canEmployee  = false;
            $canHistory   = true;
            $defaultTab   = 'ALL';
        } else {
            $canAll       = false;
            $canEvaluator = in_array($empLevel, ['MANAGER', 'GM', 'DIREKSI']) || $countEvaluator > 0;
            $canGm        = in_array($empLevel, ['GM', 'DIREKSI']) || $countGm > 0;
            $canEmployee  = ($empId > 0 || ! empty($delegatedEmpIdsWithoutUser));
            $canHistory   = ($empId > 0 || ! empty($delegatedEmpIdsWithoutUser));

            $defaultTab   = 'EVALUATOR';
            if ($countEvaluator === 0 && $countGm > 0) {
                $defaultTab = 'GM';
            } elseif ($countEvaluator === 0 && $countGm === 0 && $countEmp > 0) {
                $defaultTab = 'EMPLOYEE';
            } elseif (! $canEvaluator && $canGm) {
                $defaultTab = 'GM';
            } elseif (! $canEvaluator && $canEmployee) {
                $defaultTab = ($countEmp > 0 || $currentStage >= 4) ? 'EMPLOYEE' : ($canGm ? 'GM' : 'EMPLOYEE');
            } elseif (! $canEvaluator && ! $canGm && ! $canEmployee && $countHistory > 0) {
                $defaultTab = 'HISTORY';
            } elseif (! $activeCycle && $countHistory > 0) {
                $defaultTab = 'HISTORY';
            }
        }

        $data = [
            'active_cycle'      => $activeCycle,
            'count_all'         => $countAll,
            'count_evaluator'   => $countEvaluator,
            'count_gm'          => $countGm,
            'count_employee'    => $countEmp,
            'count_history'     => $countHistory,
            'current_emp_id'    => $empId,
            'can_all'           => $canAll,
            'can_evaluator'     => $canEvaluator,
            'can_gm'            => $canGm,
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

                $builder = $this->db->table('md_appraisal a')
                    ->select('a.md_appraisal_id, a.md_employee_id, a.md_appraisal_cycle_id, a.status, a.performance_score, a.final_score, a.rating, a.evaluator_submitted_at, a.gm_reviewed_at, a.finalized_at,
                              a.snapshot_evaluator_id, a.snapshot_gm_id,
                              e.nik as nik, e.fullname as employee_name,
                              ev.nik as evaluator_nik, ev.fullname as evaluator_name,
                              gm.nik as gm_nik, gm.fullname as gm_name,
                              d.name as division_name, p.name as position_name, l.name as level_name,
                              c.title as cycle_title, c.deadline_filling, c.deadline_review, c.deadline_reading, c.deadline_override, c.start_date, c.end_date, c.evaluator_due_date, c.gm_due_date')
                    ->join('md_employee e', 'e.md_employee_id = a.md_employee_id', 'left')
                    ->join('md_employee ev', 'ev.md_employee_id = a.snapshot_evaluator_id', 'left')
                    ->join('md_employee gm', 'gm.md_employee_id = a.snapshot_gm_id', 'left')
                    ->join('md_division d', 'd.md_division_id = a.snapshot_division_id', 'left')
                    ->join('md_position p', 'p.md_position_id = a.snapshot_position_id', 'left')
                    ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                    ->join('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = a.md_appraisal_cycle_id', 'left')
                    ->where('a.isactive', 'Y');

                // Role Tab Scope (If Admin/System $empId <= 0 or roleTab is ALL, don't filter to single user)
                $isAdmin = $this->isAppraisalAdmin($empId);

                if ($empId > 0 && (! $isAdmin || $roleTab !== 'ALL')) {
                    if (! $isAdmin) {
                        $empRow = $this->db->table('md_employee e')
                            ->select('l.name as level_name')
                            ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                            ->where('e.md_employee_id', $empId)
                            ->get()->getRow();
                            
                        $empLevel = strtoupper($empRow->level_name ?? '');

                        $canEvaluator = in_array($empLevel, ['MANAGER', 'GM', 'DIREKSI']);
                        $canGm        = in_array($empLevel, ['GM', 'DIREKSI']);

                        if ($roleTab === 'EVALUATOR' && ! $canEvaluator) {
                            $hasEvalTasks = $this->model->where('snapshot_evaluator_id', $empId)->countAllResults() > 0;
                            if (! $hasEvalTasks) {
                                $roleTab = 'EMPLOYEE';
                            }
                        } elseif ($roleTab === 'GM' && ! $canGm) {
                            $hasGmTasks = $this->model->where('snapshot_gm_id', $empId)->countAllResults() > 0;
                            if (! $hasGmTasks) {
                                $roleTab = 'EMPLOYEE';
                            }
                        }
                    }

                    if ($roleTab === 'GM') {
                        $builder->where('a.snapshot_gm_id', $empId);
                    } elseif ($roleTab === 'HISTORY') {
                        $userId       = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
                        $delegatedIds = $this->getDelegatedEmployeesWithoutUser($userId);
                        $ackEmpIds    = array_values(array_unique(array_filter(array_merge([$empId], $delegatedIds))));

                        if (! empty($ackEmpIds)) {
                            $builder->whereIn('a.md_employee_id', $ackEmpIds);
                        } else {
                            $builder->where('a.md_employee_id', $empId);
                        }
                        $builder->where('a.status', M_Appraisal::STATUS_FINALIZED);
                    } elseif ($roleTab === 'EMPLOYEE') {
                        $userId       = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
                        $delegatedIds = $this->getDelegatedEmployeesWithoutUser($userId);
                        $ackEmpIds    = array_values(array_unique(array_filter(array_merge([$empId], $delegatedIds))));

                        if (! empty($ackEmpIds)) {
                            $builder->whereIn('a.md_employee_id', $ackEmpIds);
                        } else {
                            $builder->where('a.md_employee_id', $empId);
                        }

                        if (! $isAdmin) {
                            $activeCycleStage = $this->resolveCycleStage();
                            if ($activeCycleStage < 4) {
                                // READING phase not yet reached: reviewed employee cannot see appraisals
                                $builder->where('1 = 0');
                            } else {
                                // In READING phase: employee can only see appraisals that are ready for reading
                                $builder->whereIn('a.status', [M_Appraisal::STATUS_PENDING_ACK, M_Appraisal::STATUS_FINALIZED]);
                            }
                        }
                    } elseif ($roleTab === 'EVALUATOR') {
                        $builder->where('a.snapshot_evaluator_id', $empId);
                    }
                }

                // Filter search
                $search = $this->request->getPost('search')['value'] ?? null;
                if (!empty(trim($search))) {
                    $builder->groupStart()
                        ->like('e.fullname', trim($search))
                        ->orLike('e.value', trim($search))
                        ->orLike('ev.fullname', trim($search))
                        ->orLike('c.title', trim($search))
                        ->groupEnd();
                }

                // Form Filters (Status, Cycle ID, etc.)
                if (isset($formValues['status'])) {
                    $builder->where('a.status', $formValues['status']);
                }
                if (isset($formValues['cycle_id'])) {
                    $builder->where('a.md_appraisal_cycle_id', (int) $formValues['cycle_id']);
                }

                $totalFiltered = (clone $builder)->countAllResults(false);

                // Sorting
                $builder->orderBy('a.md_appraisal_id', 'DESC');

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
                    $evalName = esc(mb_strtoupper($value['evaluator_name'] ?? '-', 'UTF-8'));
                    $evalNik  = esc($value['evaluator_nik'] ?? '');
                    $gmName   = esc(mb_strtoupper($value['gm_name'] ?? '-', 'UTF-8'));
                    $gmNik    = esc($value['gm_nik'] ?? '');

                    $isDelegatedRow = (! empty($delegatedIds) && in_array((int) $value['md_employee_id'], $delegatedIds, true) && (int) $value['md_employee_id'] !== $empId);
                    $delegatedBadge = $isDelegatedRow ? ' <span class="badge badge-info" style="font-size: 10px; font-weight: normal;"><i class="fas fa-user-friends mr-1"></i>Delegasi Duta</span>' : '';

                    $karyawanCol = '<div>' .
                        '<p class="mb-0 font-weight-bold">' . $empName . $delegatedBadge . '</p>' .
                        '<small class="text-muted">' . $nik . ($level ? ' &middot; ' . $level : '') . '</small>' .
                    '</div>';

                    $divisiCol = '<div><p class="mb-0 font-weight-bold">' . $divName . '</p><small class="text-muted">' . $posName . '</small></div>';

                    $evaluatorCol = '<div><p class="mb-0 font-weight-bold">' . $evalName . '</p>' . ($evalNik ? '<small class="text-muted">' . $evalNik . '</small>' : '') . '</div>';

                    $reviewerCol  = '<div><p class="mb-0 font-weight-bold">' . $gmName . '</p>' . ($gmNik ? '<small class="text-muted">' . $gmNik . '</small>' : '') . '</div>';

                    $row = [];
                    $row[] = $id;                                    // [0] ID (hidden)
                    $row[] = $number;                                // [1] No
                    $row[] = $karyawanCol;                           // [2] Karyawan
                    $row[] = $divisiCol;                             // [3] Divisi & Posisi
                    $row[] = $evaluatorCol;                          // [4] Penilai (Evaluator)
                    $row[] = $reviewerCol;                           // [5] Reviewer (GM / Atasan)
                    $row[] = '<strong>' . $cycTitle . '</strong>';   // [6] Siklus Penilaian
                    $row[] = $stageProgress;                         // [7] Tahapan (Plain Text)
                    $row[] = $actionBtn;                             // [8] Aksi Buttons

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
        $gm        = $appraisal->snapshot_gm_id ? $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_gm_id)->get()->getRowArray() : null;
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
            } elseif ($empId === (int) $appraisal->snapshot_gm_id && $appraisal->status === M_Appraisal::STATUS_REVIEW_GM) {
                $mode = 'GM';
            } elseif ($isAdmin) {
                // If HR is acting as the direct reviewed employee or has selected EMPLOYEE mode
                if ($empId === (int) $appraisal->md_employee_id && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                    $mode = 'EMPLOYEE';
                } elseif ($appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $mode = 'EVALUATOR';
                } elseif ($appraisal->status === M_Appraisal::STATUS_REVIEW_GM) {
                    $mode = 'GM';
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
            'gm'                     => $gm ?: ['name' => '-'],
            'has_reviewer'           => ! empty($appraisal->snapshot_gm_id),
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
        $gm        = $appraisal->snapshot_gm_id ? $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_gm_id)->get()->getRowArray() : null;
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
                } elseif ($empId === (int) $appraisal->snapshot_gm_id && $appraisal->status === M_Appraisal::STATUS_REVIEW_GM) {
                    $mode = 'GM';
                } elseif ($isAdmin) {
                    if ($empId === (int) $appraisal->md_employee_id && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK) {
                        $mode = 'EMPLOYEE';
                    } elseif ($appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                        $mode = 'EVALUATOR';
                    } elseif ($appraisal->status === M_Appraisal::STATUS_REVIEW_GM) {
                        $mode = 'GM';
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
            'gm'                     => $gm ?: ['name' => '-'],
            'has_reviewer'           => ! empty($appraisal->snapshot_gm_id),
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
        $gm        = $appraisal->snapshot_gm_id ? $this->db->table('md_employee')->select('fullname as name')->where('md_employee_id', $appraisal->snapshot_gm_id)->get()->getRowArray() : null;
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
            'gm'                   => $gm ?: ['name' => '-'],
            'cycle'                => $cycle,
            'template_snapshot'    => $snapshot,
            'responses_by_item_id' => $responsesByItemId,
            'duta_ack_name'        => $dutaAckName,
        ];

        // Detect if template is Operator mode (WEIGHTED_CHECKLIST / SINGLE_CHECKLIST)
        $isOperator = false;
        foreach ($snapshot['sections'] ?? [] as $s) {
            $st = strtoupper($s['section_type'] ?? '');
            if ($st === 'WEIGHTED_CHECKLIST' || $st === 'SINGLE_CHECKLIST') {
                $isOperator = true;
                break;
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

        if ($isOperator) {
            return $this->template->render('masterdata/appraisal-filling/v_appraisal_complete_operator', $data);
        }

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
        $gm                = $formData['gm'];
        $cycle             = $formData['cycle'];
        $snapshot          = $formData['template_snapshot'];
        $responsesByItemId = $formData['responses_by_item_id'];

        $parseActions = function ($raw): array {
            if (empty($raw)) return [];
            if (is_array($raw)) return array_values(array_filter(array_map('trim', $raw)));
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('trim', $decoded)));
                }
                $lines = explode("\n", $raw);
                return array_values(array_filter(array_map('trim', $lines)));
            }
            return [];
        };

        $parseChecked = function (int $idx, array $checkedList): bool {
            if (empty($checkedList)) return false;
            $hasYorN = false;
            foreach ($checkedList as $v) {
                if (in_array(strtoupper((string) $v), ['Y', 'N'], true)) {
                    $hasYorN = true;
                    break;
                }
            }
            if ($hasYorN) {
                $val = $checkedList[$idx] ?? $checkedList[(string) $idx] ?? null;
                return in_array(strtoupper((string) $val), ['Y', '1', 'TRUE'], true);
            }
            return in_array((string) $idx, array_map('strval', $checkedList), true);
        };

        $sections = $snapshot['sections'] ?? [];
        $items    = $snapshot['items'] ?? [];

        $itemsBySection = [];
        foreach ($items as $item) {
            $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
            $itemsBySection[$secId][] = $item;
        }

        $isGmReviewed = ! empty($appraisal->gm_reviewed_at) || ! empty($appraisal->gm_comment);
        foreach ($responsesByItemId as $r) {
            if (! empty($r->gm_response)) {
                $isGmReviewed = true;
                break;
            }
        }

        // Detect if template is Operator mode (WEIGHTED_CHECKLIST / SINGLE_CHECKLIST)
        $isOperator = false;
        foreach ($sections as $s) {
            $st = strtoupper($s['section_type'] ?? '');
            if ($st === 'WEIGHTED_CHECKLIST' || $st === 'SINGLE_CHECKLIST') {
                $isOperator = true;
                break;
            }
        }
        if (! $isOperator) {
            foreach ($items as $it) {
                $itType = strtoupper($it['input_type'] ?? '');
                if ($itType === 'WEIGHTED_CHECKLIST' || $itType === 'SINGLE_CHECKLIST') {
                    $isOperator = true;
                    break;
                }
            }
        }

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
                $gmPayload = ($resp && ! empty($resp->gm_response))
                    ? (is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response)
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

                    $gmScore = null;
                    if ($isGmReviewed) {
                        if (isset($gmPayload['score']) && $gmPayload['score'] !== '') {
                            $gmScore = (float) $gmPayload['score'];
                        } elseif ($evalScore !== null) {
                            $gmScore = $evalScore;
                        }
                    }

                    $weightedItems[] = [
                        'item_id'    => $itemId,
                        'title'      => $title,
                        'bobot'      => $bobot,
                        'eval_score' => $evalScore,
                        'eval_nilai' => $evalScore !== null ? ($bobot * $evalScore) : null,
                        'gm_score'   => $gmScore,
                        'gm_nilai'   => $gmScore !== null ? ($bobot * $gmScore) : null,
                    ];
                } elseif ($inputType === 'SINGLE_CHECKLIST') {
                    $evalChecked = false;
                    if (isset($evalPayload['boolean_value'])) {
                        $evalChecked = in_array(strtoupper((string) $evalPayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                    } elseif (! empty($evalPayload['checked_actions'])) {
                        $evalChecked = true;
                    }

                    $gmChecked = false;
                    if ($isGmReviewed) {
                        if (isset($gmPayload['boolean_value'])) {
                            $gmChecked = in_array(strtoupper((string) $gmPayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                        } elseif (! empty($gmPayload['checked_actions'])) {
                            $gmChecked = true;
                        } else {
                            $gmChecked = $evalChecked;
                        }
                    }

                    $isOther = (stripos(trim($title), 'lainnya') === 0 || stripos(trim($title), 'lain-lain') === 0);

                    $singleItems[] = [
                        'item_id'         => $itemId,
                        'title'           => $title,
                        'is_other'        => $isOther,
                        'eval_checked'    => $evalChecked,
                        'eval_other_text' => $evalPayload['other_text'] ?? '',
                        'gm_checked'      => $gmChecked,
                        'gm_other_text'   => $gmPayload['other_text'] ?? ($evalPayload['other_text'] ?? ''),
                    ];
                }
            }

            $opViewData = [
                'appraisal_id'      => $id,
                'appraisal'         => $appraisal,
                'employee'          => $employee,
                'evaluator'         => $evaluator,
                'gm'                => $gm,
                'cycle'             => $cycle,
                'weighted_items'    => $weightedItems,
                'single_items'      => $singleItems,
                'is_gm_reviewed'    => $isGmReviewed,
                'evaluator_comment' => $appraisal->evaluator_comment ?? '',
                'development_plan'  => $appraisal->development_plan ?? '',
                'gm_comment'        => $appraisal->gm_comment ?? '',
            ];

            return view('masterdata/appraisal-reusable/modal_score_comparison_operator', $opViewData);
        }

        $comparisonSections     = [];
        $evalTotalWeightedScore = 0.0;
        $evalTotalWeight        = 0.0;
        $gmTotalWeightedScore   = 0.0;
        $gmTotalWeight          = 0.0;
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
                $actions    = $parseActions($cfg['key_actions'] ?? null);

                $resp = $responsesByItemId[$itemId] ?? null;

                $evalPayload = ($resp && !empty($resp->evaluator_response))
                    ? (is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response)
                    : [];
                $evalActionScores   = $evalPayload['action_scores'] ?? [];
                $evalCheckedActions = $evalPayload['checked_actions'] ?? [];

                $gmPayload = ($resp && !empty($resp->gm_response))
                    ? (is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response)
                    : [];
                $gmActionScores   = $gmPayload['action_scores'] ?? [];
                $gmCheckedActions = $gmPayload['checked_actions'] ?? [];

                if ($inputType === 'SCORE') {
                    $hasKeyActions = !empty($actions);
                    $totalActions  = $hasKeyActions ? count($actions) : 1;

                    $evalActionSum = 0.0;
                    $gmActionSum   = 0.0;
                    $actionRows    = [];

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

                            $gmActScore   = $evalActScore;
                            $isOverridden = false;
                            if ($isGmReviewed) {
                                if (isset($gmActionScores[$aIdx]) && $gmActionScores[$aIdx] !== '') {
                                    $gmActScore = (float) $gmActionScores[$aIdx];
                                    if (round($gmActScore, 2) !== round($evalActScore, 2)) {
                                        $isOverridden = true;
                                        $hasOverride  = true;
                                    }
                                } elseif (isset($gmActionScores[(string) $aIdx]) && $gmActionScores[(string) $aIdx] !== '') {
                                    $gmActScore = (float) $gmActionScores[(string) $aIdx];
                                    if (round($gmActScore, 2) !== round($evalActScore, 2)) {
                                        $isOverridden = true;
                                        $hasOverride  = true;
                                    }
                                }
                            }
                            $gmActionSum += $gmActScore;

                            $actionRows[] = [
                                'index'         => $aIdx,
                                'letter'        => chr(97 + $aIdx),
                                'text'          => $actionText,
                                'eval_score'    => $evalActScore,
                                'gm_score'      => $isGmReviewed ? $gmActScore : null,
                                'is_overridden' => $isOverridden,
                                'delta'         => $isGmReviewed ? round($gmActScore - $evalActScore, 2) : 0,
                            ];
                        }

                        $evalAvg = $totalActions > 0 ? ($evalActionSum / $totalActions) : 3.00;
                        $gmAvg   = $totalActions > 0 ? ($gmActionSum / $totalActions) : 3.00;
                    } else {
                        $evalAvg = ($resp && $resp->score !== null) ? (float) $resp->score : 3.00;
                        $gmAvg   = $evalAvg;
                        $isOverridden = false;
                        if ($isGmReviewed && isset($gmPayload['score']) && $gmPayload['score'] !== '') {
                            $gmAvg = (float) $gmPayload['score'];
                            if (round($gmAvg, 2) !== round($evalAvg, 2)) {
                                $isOverridden = true;
                                $hasOverride  = true;
                            }
                        }

                        $actionRows[] = [
                            'index'         => 0,
                            'letter'        => 'a',
                            'text'          => 'Indikator umum',
                            'eval_score'    => $evalAvg,
                            'gm_score'      => $isGmReviewed ? $gmAvg : null,
                            'is_overridden' => $isOverridden,
                            'delta'         => $isGmReviewed ? round($gmAvg - $evalAvg, 2) : 0,
                        ];
                    }

                    $evalAvg = round($evalAvg, 2);
                    $gmAvg   = round($gmAvg, 2);

                    $effectiveWeight = $weight > 0 ? $weight : 1.0;
                    $evalTotalWeightedScore += ($evalAvg * $effectiveWeight);
                    $evalTotalWeight        += $effectiveWeight;
                    $gmTotalWeightedScore   += ($gmAvg * $effectiveWeight);
                    $gmTotalWeight          += $effectiveWeight;

                    $processedItems[] = [
                        'item_id'       => $itemId,
                        'title'         => $compTitle,
                        'definition'    => $definition,
                        'weight'        => $weight,
                        'input_type'    => 'SCORE',
                        'actions'       => $actionRows,
                        'eval_avg'      => $evalAvg,
                        'gm_avg'        => $isGmReviewed ? $gmAvg : null,
                        'is_overridden' => $isGmReviewed && (round($gmAvg, 2) !== round($evalAvg, 2)),
                    ];
                } else {
                    // BOOLEAN / CHECKLIST
                    $actionRows = [];
                    if (!empty($actions)) {
                        foreach ($actions as $aIdx => $actionText) {
                            $evalChecked = $parseChecked($aIdx, $evalCheckedActions);

                            $gmChecked    = null;
                            $isOverridden = false;

                            if ($isGmReviewed) {
                                if (isset($gmPayload['checked_actions'])) {
                                    $gmChecked = $parseChecked($aIdx, $gmCheckedActions);
                                    if ($gmChecked !== $evalChecked) {
                                        $isOverridden = true;
                                        $hasOverride  = true;
                                    }
                                } else {
                                    $gmChecked = $evalChecked;
                                }
                            }

                            $actionRows[] = [
                                'index'         => $aIdx,
                                'letter'        => chr(97 + $aIdx),
                                'text'          => $actionText,
                                'eval_checked'  => $evalChecked,
                                'gm_checked'    => $isGmReviewed ? $gmChecked : null,
                                'is_overridden' => $isOverridden,
                            ];
                        }
                    } else {
                        $evalChecked  = in_array(strtoupper((string) ($evalPayload['boolean_value'] ?? '')), ['Y', '1', 'TRUE'], true);
                        $gmChecked    = null;
                        $isOverridden = false;

                        if ($isGmReviewed) {
                            if (isset($gmPayload['boolean_value'])) {
                                $gmChecked = in_array(strtoupper((string) $gmPayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                                if ($gmChecked !== $evalChecked) {
                                    $isOverridden = true;
                                    $hasOverride  = true;
                                }
                            } else {
                                $gmChecked = $evalChecked;
                            }
                        }

                        $actionRows[] = [
                            'index'         => 0,
                            'letter'        => 'a',
                            'text'          => $compTitle,
                            'eval_checked'  => $evalChecked,
                            'gm_checked'    => $isGmReviewed ? $gmChecked : null,
                            'is_overridden' => $isOverridden,
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

        if ($isGmReviewed) {
            $gmFinalOverall = $gmTotalWeight > 0 ? ($gmTotalWeightedScore / $gmTotalWeight) : 3.00;
            if ($appraisal->final_score !== null && !empty($appraisal->gm_reviewed_at) && (float) $appraisal->final_score > 0) {
                $gmFinalOverall = (float) $appraisal->final_score;
            }
            $gmFinalOverall = max(0.00, min(4.00, round($gmFinalOverall, 2)));
            $delta          = round($gmFinalOverall - $evalFinalOverall, 2);
        } else {
            $gmFinalOverall = null;
            $delta          = null;
        }

        $getRating = function ($score): array {
            if ($score === null) return ['label' => 'Belum Dinilai', 'class' => 'r-muted'];
            if ($score >= 3.50) return ['label' => 'Sangat Baik', 'class' => 'r-great badge-success'];
            if ($score >= 2.75) return ['label' => 'Baik', 'class' => 'r-good badge-primary'];
            if ($score >= 2.00) return ['label' => 'Cukup', 'class' => 'r-fair badge-warning'];
            return ['label' => 'Kurang', 'class' => 'r-low badge-danger'];
        };

        $evalRating = $getRating($evalFinalOverall);
        $gmRating   = $getRating($gmFinalOverall);

        $viewData = [
            'appraisal_id'         => $id,
            'appraisal'            => $appraisal,
            'employee'             => $employee,
            'evaluator'            => $evaluator,
            'gm'                   => $gm,
            'cycle'                => $cycle,
            'sections'             => $comparisonSections,
            'eval_overall_score'   => $evalFinalOverall,
            'eval_rating'          => $evalRating,
            'gm_overall_score'     => $gmFinalOverall,
            'gm_rating'            => $gmRating,
            'delta'                => $delta,
            'is_gm_reviewed'       => $isGmReviewed,
            'has_override'         => $hasOverride,
            'evaluator_comment'    => $appraisal->evaluator_comment ?? '',
            'development_plan'     => $appraisal->development_plan ?? '',
            'gm_comment'           => $appraisal->gm_comment ?? '',
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
        if (!empty($appraisal->snapshot_gm_id)) {
            $reviewer = $this->db->table('md_employee e')
                ->select('e.fullname as name, p.name as position_name')
                ->join('md_position p', 'p.md_position_id = e.md_position_id', 'left')
                ->where('e.md_employee_id', $appraisal->snapshot_gm_id)
                ->get()
                ->getRowArray();
        }

        $cycle = $this->cycleModel->find($appraisal->md_appraisal_cycle_id);

        $snapshot = is_string($appraisal->template_snapshot)
            ? json_decode($appraisal->template_snapshot, true)
            : ($appraisal->template_snapshot ?: []);

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

        $empAckDate  = !empty($appraisal->employee_acknowledged_at) ? date('d/m/Y H:i', strtotime($appraisal->employee_acknowledged_at)) . ' WIB' : null;
        $evalSubDate = !empty($appraisal->evaluator_submitted_at) ? date('d/m/Y H:i', strtotime($appraisal->evaluator_submitted_at)) . ' WIB' : null;
        $gmRevDate   = !empty($appraisal->gm_reviewed_at) ? date('d/m/Y H:i', strtotime($appraisal->gm_reviewed_at)) . ' WIB' : null;

        $empDateStr  = !empty($appraisal->employee_acknowledged_at) ? date('d M Y', strtotime($appraisal->employee_acknowledged_at)) : '-';
        $evalDateStr = !empty($appraisal->evaluator_submitted_at) ? date('d M Y', strtotime($appraisal->evaluator_submitted_at)) : (!empty($appraisal->created_at) ? date('d M Y', strtotime($appraisal->created_at)) : '-');
        $reviewerDateStr = !empty($appraisal->gm_reviewed_at) ? date('d M Y', strtotime($appraisal->gm_reviewed_at)) : (!empty($appraisal->snapshot_gm_id) ? '-' : 'N/A');

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
            'gmRevDate'            => $gmRevDate,
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
            } elseif ($empId === (int) $appraisal->snapshot_gm_id && $appraisal->status === M_Appraisal::STATUS_REVIEW_GM) {
                $userMode = 'GM';
            } elseif ($isAdmin) {
                if ($empId === (int) $appraisal->md_employee_id && $appraisal->status === M_Appraisal::STATUS_PENDING_ACK && ($post['user_mode'] ?? '') === 'EMPLOYEE') {
                    $userMode = 'EMPLOYEE';
                } elseif ($appraisal->status === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $userMode = 'EVALUATOR';
                } elseif ($appraisal->status === M_Appraisal::STATUS_REVIEW_GM) {
                    $userMode = 'GM';
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
        $db->transStart();

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

            } elseif ($userMode === 'GM') {
                $evaluatorComment = isset($post['evaluator_comment']) ? trim($post['evaluator_comment']) : $appraisal->evaluator_comment;
                $developmentPlan  = isset($post['development_plan']) ? trim($post['development_plan']) : $appraisal->development_plan;
                $gmComment        = isset($post['gm_comment']) ? trim($post['gm_comment']) : $appraisal->gm_comment;

                $appraisal->evaluator_comment = $evaluatorComment;
                $appraisal->development_plan  = $developmentPlan;
                $appraisal->gm_comment        = $gmComment;
                $appraisal->updated_by        = $userId;
                $this->model->save($appraisal);

                if (! empty($responseList)) {
                    $this->scoringService->saveResponses($appraisalId, $responseList, $userId, 'GM');
                }

                if ($actionType === 'kickback') {
                    $reason = trim($post['kickback_reason'] ?? $gmComment ?? '');
                    if (empty($reason)) {
                        throw new \RuntimeException('Alasan revisi wajib diisi jika mengembalikan form.');
                    }
                    $this->workflowService->kickbackByGm($appraisalId, $userId, $reason);
                } elseif ($actionType === 'submit') {
                    $this->workflowService->reviewByGm($appraisalId, $userId, $gmComment);
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
                $gmComment        = isset($post['gm_comment']) ? trim($post['gm_comment']) : $appraisal->gm_comment;
                $employeeComment  = isset($post['employee_comment']) ? trim($post['employee_comment']) : $appraisal->employee_comment;

                $appraisal->evaluator_comment = $evaluatorComment;
                $appraisal->development_plan  = $developmentPlan;
                $appraisal->gm_comment        = $gmComment;
                if ($employeeComment !== null) {
                    $appraisal->employee_comment = $employeeComment;
                }
                $appraisal->updated_by = $userId;
                $this->model->save($appraisal);

                if (! empty($responseList)) {
                    $this->scoringService->saveResponses($appraisalId, $responseList, $userId, 'GM');
                } else {
                    $this->scoringService->calculateAndUpdateScores($appraisalId, $userId);
                }

                if ($actionType === 'submit') {
                    // HR finalizes / acknowledges on behalf of reading completion
                    $this->workflowService->acknowledgeByEmployee($appraisalId, $userId, $employeeComment);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Gagal memproses transaksi database penyimpanan penilaian.');
            }

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
            if (isset($post['gm_comment'])) {
                $appraisal->gm_comment = $post['gm_comment'];
            }

            $appraisal->updated_by = $userId;
            $this->model->save($appraisal);

            // Save individual item responses
            $rawList                 = $post['responses_json'] ?? $post['responses'] ?? (! empty($post['table']) ? $post['table'] : null);
            $actionScoresFallback    = $post['action_scores'] ?? [];
            $checkedActionsFallback  = $post['checked_actions'] ?? [];
            $booleanValuesFallback   = $post['boolean_value'] ?? [];
            $responseList            = $this->normalizeResponseList($rawList, $actionScoresFallback, $checkedActionsFallback, $booleanValuesFallback);

            $actorRole = ($empId === (int) $appraisal->snapshot_gm_id && $appraisal->status === M_Appraisal::STATUS_REVIEW_GM) ? 'GM' : 'EVALUATOR';

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
     * Evaluator submits form to GM or Employee sign-off
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
            case M_Appraisal::STATUS_REVIEW_GM:
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
        $id     = $row['md_appraisal_id'];
        $status = $row['status'];
        $actions = '';

        $empId   = $this->getCurrentEmployeeId();
        $isAdmin = $this->isAppraisalAdmin($empId);
        $userId  = (int) ($this->access->getSessionUser() ?? $this->session->get('sys_user_id') ?? 0);
        $isDuta  = (! empty($userId) && $this->isDutaForEmployeeWithoutUser($userId, (int) ($row['md_employee_id'] ?? 0)));

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

        if ($status === M_Appraisal::STATUS_DRAFT_EVALUATOR && ($roleTab === 'EVALUATOR' || (int) ($row['snapshot_evaluator_id'] ?? 0) === $empId || ($isAdmin && $roleTab !== 'EMPLOYEE'))) {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="Isi Penilaian"><i class="fas fa-edit text-primary"></i></a>';
        } elseif ($status === M_Appraisal::STATUS_REVIEW_GM && ($roleTab === 'GM' || (int) ($row['snapshot_gm_id'] ?? 0) === $empId || ($isAdmin && $roleTab !== 'EMPLOYEE'))) {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="Review GM"><i class="fas fa-user-check text-info"></i></a>';
        } elseif ($status === M_Appraisal::STATUS_PENDING_ACK && ((int) $row['md_employee_id'] === $empId || $isDuta || $isAdmin)) {
            $isDirectEmployee = ((int) $row['md_employee_id'] === $empId);
            // Intervensi HR is strictly AFTER pembacaan (Stage 5)
            if ($isAdmin && ! $isDirectEmployee && ! $isDuta && $cycleStage === 5) {
                $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="Edit Penilaian"><i class="fas fa-edit text-primary"></i></a>';
            } else {
                $ttdTitle = 'Konfirmasi TTD';
                if ($isDuta && ! $isDirectEmployee) {
                    $ttdTitle .= ' (Duta)';
                }
                $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" title="' . $ttdTitle . '"><i class="fas fa-signature text-warning"></i></a>';
            }
        } elseif ($status === M_Appraisal::STATUS_FINALIZED) {
            $actions .= '<a class="btn btn-sm" href="' . base_url('sas/penilaian/complete/' . $id) . '" title="Rapor Kinerja"><i class="fas fa-file-alt text-success"></i></a>';
        } else {
            $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'VIEW\')" title="Lihat Detail"><i class="fas fa-eye text-info"></i></a>';
        }

        // Score Comparison & History Action Button
        $actions .= '<button type="button" class="btn btn-sm" onclick="showScoreComparisonModal(\'' . $id . '\')" title="Riwayat & Komparasi Skor"><i class="fas fa-history text-primary"></i></button>';

        return $actions;
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
            } elseif (! empty($row['action_scores']) && is_array($row['action_scores'])) {
                $numericScores = array_values(array_map(function ($s) {
                    return round((float) $s, 2);
                }, $row['action_scores']));
                $row['action_scores'] = $numericScores;
                $row['score']         = count($numericScores) > 0 ? round(array_sum($numericScores) / count($numericScores), 2) : 3.00;
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
