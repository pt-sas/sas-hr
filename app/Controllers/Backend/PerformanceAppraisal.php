<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalCycle;
use App\Models\M_Division;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Exception;

class PerformanceAppraisal extends BaseController
{
    /** @var M_Appraisal */
    protected $model;
    
    /** @var M_AppraisalCycle */
    protected $cycleModel;
    
    /** @var M_Division */
    protected $divisionModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model         = new M_Appraisal();
        $this->cycleModel    = new M_AppraisalCycle();
        $this->divisionModel = new M_Division($this->request);
        $this->db            = \Config\Database::connect();
    }

    /**
     * Resolve current logged-in employee ID.
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
     * Check if the current user has superadmin/whitelist access for appraisals.
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
     * Scope appraisal query to evaluator/reviewer assignments for non-admin users.
     */
    protected function applyAppraisalScope($builder, string $alias, int $empId, bool $isAdmin): void
    {
        if ($isAdmin) {
            return;
        }

        if ($empId <= 0) {
            $builder->where('1 = 0', null, false);
            return;
        }

        $builder->groupStart()
            ->where("{$alias}.snapshot_evaluator_id", $empId)
            ->orWhere("{$alias}.snapshot_reviewer_id", $empId)
            ->groupEnd();
    }

    /**
     * Render Appraisal History & Monitoring Dashboard View
     */
    public function index()
    {
        $empId   = $this->getCurrentEmployeeId();
        $isAdmin = $this->isAppraisalAdmin($empId);

        // Fetch all cycles sorted by start date desc
        $cycles = $this->db->table('md_appraisal_cycle')
            ->select('md_appraisal_cycle_id, title, start_date, end_date, deadline_filling, deadline_review, deadline_reading, status')
            ->where('isactive', 'Y')
            ->orderBy('start_date', 'DESC')
            ->get()
            ->getResultArray();

        // Fetch active divisions (scoped to assigned appraisals for non-admins)
        if ($isAdmin) {
            $divisions = $this->db->table('md_division')
                ->select('md_division_id, name')
                ->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            $divBuilder = $this->db->table('md_division d')
                ->distinct()
                ->select('d.md_division_id, d.name')
                ->join('md_appraisal a', 'a.snapshot_division_id = d.md_division_id AND a.isactive = "Y"', 'inner')
                ->where('d.isactive', 'Y');
            $this->applyAppraisalScope($divBuilder, 'a', $empId, false);
            $divisions = $divBuilder->orderBy('d.name', 'ASC')
                ->get()
                ->getResultArray();
        }

        // Determine default cycle: preferably latest OPEN cycle, or most recent cycle
        $defaultCycleId = 0;
        foreach ($cycles as $c) {
            if ($c['status'] === M_AppraisalCycle::STATUS_OPEN) {
                $defaultCycleId = (int) $c['md_appraisal_cycle_id'];
                break;
            }
        }
        if ($defaultCycleId === 0 && !empty($cycles)) {
            $defaultCycleId = (int) $cycles[0]['md_appraisal_cycle_id'];
        }

        $data = [
            'cycles'           => $cycles,
            'divisions'        => $divisions,
            'default_cycle_id' => $defaultCycleId,
            'is_admin'         => $isAdmin,
            'status_labels'    => M_Appraisal::getStatusLabels(),
        ];

        return $this->template->render('masterdata/appraisal-dashboard/v_appraisal_dashboard', $data);
    }

    /**
     * Fetch KPI Summary, Completion Metrics, and Division Progress Analytics
     */
    public function getSummary()
    {
        try {
            $cycleId     = (int) ($this->request->getVar('cycle_id') ?? 0);
            $divisionId  = (int) ($this->request->getVar('division_id') ?? 0);
            $year        = $this->request->getVar('year') ?? null;
            $status      = $this->request->getVar('status') ?? null;

            $empId   = $this->getCurrentEmployeeId();
            $isAdmin = $this->isAppraisalAdmin($empId);

            $builder = $this->db->table('md_appraisal a')
                ->join('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = a.md_appraisal_cycle_id', 'left')
                ->join('md_appraisal_template t', 't.md_appraisal_template_id = a.md_appraisal_template_id', 'left')
                ->where('a.isactive', 'Y');

            $this->applyAppraisalScope($builder, 'a', $empId, $isAdmin);

            if ($cycleId > 0) {
                $builder->where('a.md_appraisal_cycle_id', $cycleId);
            }
            if ($divisionId > 0) {
                $builder->where('a.snapshot_division_id', $divisionId);
            }
            if (!empty($year)) {
                $builder->where('YEAR(c.start_date)', (int) $year);
            }
            if (!empty($status) && in_array($status, ['DRAFT', 'OPEN', 'CLOSED'], true)) {
                $builder->where('c.status', $status);
            }

            $appraisals = $builder->select('a.md_appraisal_id, a.md_appraisal_cycle_id, a.status, a.final_score, a.rating, a.snapshot_division_id, d.name as division_name, t.target_group')
                ->join('md_division d', 'd.md_division_id = a.snapshot_division_id', 'left')
                ->get()
                ->getResultArray();

            $total              = count($appraisals);
            $draftEvaluator     = 0;
            $submittedEvaluator = 0;
            $reviewReviewer     = 0;
            $pendingAck         = 0;
            $finalized          = 0;

            $totalFinalScore    = 0.0;
            $finalizedCountWithScore = 0;

            $ratingCounts = [
                'sangat_baik' => 0,
                'baik'        => 0,
                'cukup'       => 0,
                'kurang'      => 0,
            ];

            $ratingByGroup = [
                'PERMANENT' => [
                    'total_final' => 0,
                    'counts'      => ['sangat_baik' => 0, 'baik' => 0, 'cukup' => 0, 'kurang' => 0],
                ],
                'OPERATOR' => [
                    'total_final' => 0,
                    'counts'      => ['sangat_baik' => 0, 'baik' => 0, 'cukup' => 0, 'kurang' => 0],
                ],
            ];

            $divisionGroups = [];

            foreach ($appraisals as $app) {
                $st   = $app['status'];
                $div  = $app['division_name'] ?: 'Tanpa Divisi';
                $score = $app['final_score'] !== null ? (float) $app['final_score'] : null;

                if (!isset($divisionGroups[$div])) {
                    $divisionGroups[$div] = [
                        'division_id'      => (int) ($app['snapshot_division_id'] ?? 0),
                        'division_name'    => $div,
                        'total'            => 0,
                        'in_progress'      => 0,
                        'penilaian'        => 0,
                        'submit_penilaian' => 0,
                        'review_reviewer'  => 0,
                        'pending_ack'      => 0,
                        'finalized'        => 0,
                        'percent'          => 0.0,
                        'score_sum'        => 0.0,
                        'score_count'      => 0,
                        'avg_score'        => null,
                    ];
                }

                $divisionGroups[$div]['total']++;

                if ($st === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $draftEvaluator++;
                    $divisionGroups[$div]['in_progress']++;
                    $divisionGroups[$div]['penilaian']++;
                } elseif ($st === M_Appraisal::STATUS_SUBMITTED_EVALUATOR) {
                    $submittedEvaluator++;
                    $divisionGroups[$div]['in_progress']++;
                    $divisionGroups[$div]['submit_penilaian']++;
                } elseif ($st === M_Appraisal::STATUS_REVIEW) {
                    $reviewReviewer++;
                    $divisionGroups[$div]['in_progress']++;
                    $divisionGroups[$div]['review_reviewer']++;
                } elseif ($st === M_Appraisal::STATUS_PENDING_ACK) {
                    $pendingAck++;
                    $divisionGroups[$div]['in_progress']++;
                    $divisionGroups[$div]['pending_ack']++;
                } elseif ($st === M_Appraisal::STATUS_FINALIZED) {
                    $finalized++;
                    $divisionGroups[$div]['finalized']++;

                    $rowGroup = strtoupper((string) ($app['target_group'] ?? 'PERMANENT'));
                    if ($rowGroup !== 'OPERATOR') {
                        $rowGroup = 'PERMANENT';
                    }
                    $ratingByGroup[$rowGroup]['total_final']++;

                    if ($score !== null) {
                        $totalFinalScore += $score;
                        $finalizedCountWithScore++;
                        $divisionGroups[$div]['score_sum'] += $score;
                        $divisionGroups[$div]['score_count']++;

                        if ($rowGroup === 'OPERATOR') {
                            $scoreInt = (int) $score;
                            if ($scoreInt >= 13) {
                                $ratingByGroup['OPERATOR']['counts']['sangat_baik']++;
                            } elseif ($scoreInt >= 8) {
                                $ratingByGroup['OPERATOR']['counts']['baik']++;
                            } else {
                                $ratingByGroup['OPERATOR']['counts']['kurang']++;
                            }
                        } else {
                            if ($score >= 3.50) {
                                $ratingCounts['sangat_baik']++;
                                $ratingByGroup['PERMANENT']['counts']['sangat_baik']++;
                            } elseif ($score >= 2.75) {
                                $ratingCounts['baik']++;
                                $ratingByGroup['PERMANENT']['counts']['baik']++;
                            } elseif ($score >= 2.00) {
                                $ratingCounts['cukup']++;
                                $ratingByGroup['PERMANENT']['counts']['cukup']++;
                            } else {
                                $ratingCounts['kurang']++;
                                $ratingByGroup['PERMANENT']['counts']['kurang']++;
                            }
                        }
                    }
                }
            }

            // Calculate percentage and average score per division
            foreach ($divisionGroups as &$dg) {
                $dg['percent'] = $dg['total'] > 0
                    ? round(($dg['finalized'] / $dg['total']) * 100, 1)
                    : 0.0;
                $dg['avg_score'] = $dg['score_count'] > 0
                    ? round($dg['score_sum'] / $dg['score_count'], 2)
                    : null;
            }
            unset($dg);

            $divisionsList = array_values($divisionGroups);
            usort($divisionsList, static function (array $a, array $b): int {
                $cmp = $a['percent'] <=> $b['percent'];
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcasecmp($a['division_name'], $b['division_name']);
            });

            $completionPercent = $total > 0
                ? round(($finalized / $total) * 100, 1)
                : 0.0;

            $avgFinalScore = $finalizedCountWithScore > 0
                ? round($totalFinalScore / $finalizedCountWithScore, 2)
                : null;

            $inProgressTotal = $draftEvaluator + $submittedEvaluator + $reviewReviewer + $pendingAck;

            // Fetch cycle details if a specific cycle is selected
            $cycleInfo = null;
            if ($cycleId > 0) {
                $cRow = $this->db->table('md_appraisal_cycle')
                    ->where('md_appraisal_cycle_id', $cycleId)
                    ->get()
                    ->getRowArray();
                if ($cRow) {
                    $dlFilling = !empty($cRow['deadline_filling']) ? $cRow['deadline_filling'] : null;
                    $dlReview  = !empty($cRow['deadline_review']) ? $cRow['deadline_review'] : null;
                    $dlReading = !empty($cRow['deadline_reading']) ? $cRow['deadline_reading'] : null;

                    $cycleInfo = [
                        'id'               => $cRow['md_appraisal_cycle_id'],
                        'title'            => $cRow['title'],
                        'start_date'       => !empty($cRow['start_date']) ? date('d/m/Y', strtotime($cRow['start_date'])) : '-',
                        'end_date'         => !empty($cRow['end_date']) ? date('d/m/Y', strtotime($cRow['end_date'])) : '-',
                        'deadline_filling' => !empty($dlFilling) ? date('d/m/Y', strtotime($dlFilling)) : '-',
                        'deadline_review'  => !empty($dlReview) ? date('d/m/Y', strtotime($dlReview)) : '-',
                        'deadline_reading' => !empty($dlReading) ? date('d/m/Y', strtotime($dlReading)) : '-',
                        'status'           => $cRow['status'],
                    ];
                }
            }

            $result = [
                'cycle'        => $cycleInfo,
                'metrics'      => [
                    'total'               => $total,
                    'in_progress'         => $inProgressTotal,
                    'draft_evaluator'     => $draftEvaluator,
                    'submitted_evaluator' => $submittedEvaluator,
                    'review_reviewer'     => $reviewReviewer,
                    'pending_ack'         => $pendingAck,
                    'finalized'           => $finalized,
                    'completion_percent'  => $completionPercent,
                    'avg_final_score'     => $avgFinalScore,
                    'rating_counts'       => $ratingCounts,
                    'rating_by_group'     => $ratingByGroup,
                ],
                'divisions'    => $divisionsList,
            ];

            return $this->response->setJSON(message('success', true, $result));
        } catch (\Throwable $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Datatable fetch for Organization Appraisal Records (Form Explorer)
     */
    public function showAll()
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method Not Allowed']);
        }

        try {
            $empId   = $this->getCurrentEmployeeId();
            $isAdmin = $this->isAppraisalAdmin($empId);

            $table   = $this->model->table;
            $builder = $this->db->table($table)
                ->select($this->model->getSelect() . ', t.target_group as template_target_group');
            foreach ($this->model->getJoin() as $join) {
                $builder->join($join['tableJoin'], $join['columnJoin'], $join['typeJoin']);
            }
            $builder->join('md_appraisal_template t', "t.md_appraisal_template_id = {$table}.md_appraisal_template_id", 'left');
            $builder->where("{$table}.isactive", 'Y');

            $this->applyAppraisalScope($builder, $table, $empId, $isAdmin);

            // Form Filter Parsing
            $formFilter  = $this->request->getPost('form') ?? [];
            $cycleId     = (int) ($this->request->getPost('cycle_id') ?? 0);
            $divisionId  = (int) ($this->request->getPost('division_id') ?? 0);
            $status      = $this->request->getPost('status') ?? null;
            $year        = $this->request->getPost('year') ?? null;
            $cycleStatus = null;

            foreach ($formFilter as $row) {
                $name  = $row['name']  ?? null;
                $value = $row['value'] ?? null;
                if ($value === '' || $value === null) {
                    continue;
                }

                if ($name === 'cycle_id') {
                    $cycleId = (int) $value;
                }
                if ($name === 'division_id') {
                    $divisionId = (int) $value;
                }
                if ($name === 'year') {
                    $year = (int) $value;
                }
                if ($name === 'status') {
                    if (in_array($value, ['DRAFT', 'OPEN', 'CLOSED'], true)) {
                        $cycleStatus = $value;
                    } else {
                        $status = $value;
                    }
                }
            }

            if ($cycleId > 0) {
                $builder->where("{$table}.md_appraisal_cycle_id", $cycleId);
            }
            if ($divisionId > 0) {
                $builder->where("{$table}.snapshot_division_id", $divisionId);
            }
            if (!empty($year)) {
                $builder->where('YEAR(c.start_date)', (int) $year);
            }
            if (!empty($cycleStatus)) {
                $builder->where('c.status', $cycleStatus);
            }
            if (!empty($status) && $status !== 'ALL') {
                $builder->where("{$table}.status", $status);
            }

            // Global Search Filter
            $search = $this->request->getPost('search')['value'] ?? null;
            if (!empty(trim($search))) {
                $s = trim($search);
                $builder->groupStart()
                    ->like('e.fullname', $s)
                    ->orLike('e.nik', $s)
                    ->orLike('c.title', $s)
                    ->orLike('b.name', $s)
                    ->orLike('d.name', $s)
                    ->orLike('p.name', $s)
                    ->groupEnd();
            }

            $totalFiltered = (clone $builder)->countAllResults(false);

            // Sorting
            $orderColIdx = (int) ($this->request->getPost('order')[0]['column'] ?? 0);
            $orderDir    = $this->request->getPost('order')[0]['dir'] ?? 'desc';
            
            $columnMap = [
                0  => "{$table}.md_appraisal_id",
                1  => "{$table}.md_appraisal_id",
                2  => 'e.fullname',
                3  => 'p.name',
                4  => 'l.name',
                5  => 'b.name',
                6  => 'd.name',
                7  => 'c.title',
                8  => "{$table}.final_score",
                9  => "{$table}.status",
                10 => "{$table}.md_appraisal_id",
            ];

            if (isset($columnMap[$orderColIdx])) {
                $builder->orderBy($columnMap[$orderColIdx], $orderDir);
            } else {
                $builder->orderBy("{$table}.md_appraisal_id", 'DESC');
            }

            // Pagination
            $start  = (int) ($this->request->getPost('start') ?? 0);
            $length = (int) ($this->request->getPost('length') ?? 10);
            if ($length > 0) {
                $builder->limit($length, $start);
            }

            $list = $builder->get()->getResultArray();
            $data = [];
            $number = $start;

            foreach ($list as $val) {
                $number++;
                $id           = (int) $val['md_appraisal_id'];
                $appStatus    = $val['status'] ?? M_Appraisal::STATUS_PENILAIAN;
                $score        = $val['final_score'] !== null ? number_format((float) $val['final_score'], 2) : '-';
                $rating       = $val['rating'] ?? '-';
                $cycleTitle   = $val['cycle_title'] ?? '-';
                $cycleStatus  = $val['cycle_status'] ?? 'OPEN';
                $empName      = !empty($val['employee_name']) ? mb_strtoupper($val['employee_name'], 'UTF-8') : '-';
                $posName      = $val['position_name'] ?? '-';
                $levelName    = $val['level_name'] ?? '-';
                $divName      = $val['division_name'] ?? '-';
                $branchName   = $val['branch_name'] ?? '';
                $nik          = $val['nik'] ?? '-';

                // Cycle Column
                $cycleCol = '<span class="font-weight-500 text-dark">' . esc($cycleTitle) . '</span>';

                // Score & Grade Badge (Single Line with hyphen)
                $scoreCol = '<div class="text-center">';
                if ($score !== '-') {
                    $numScore = (float) $val['final_score'];
                    $rowGroup = strtoupper((string) ($val['template_target_group'] ?? 'PERMANENT'));
                    if ($rowGroup === 'OPERATOR') {
                        $scoreColor = ($numScore >= 13) ? 'text-success' : (($numScore >= 8) ? 'text-primary' : 'text-danger');
                    } else {
                        $scoreColor = ($numScore >= 3.50) ? 'text-success' : (($numScore >= 2.75) ? 'text-primary' : (($numScore >= 2.00) ? 'text-warning' : 'text-danger'));
                    }
                    $scoreCol .= '<span class="font-weight-bold ' . $scoreColor . '">' . $score . '</span>';
                    if (!empty($rating) && $rating !== '-') {
                        $shortRating = esc(explode('(', $rating)[0]);
                        $scoreCol .= ' <span class="text-muted font-weight-normal">&ndash; ' . trim($shortRating) . '</span>';
                    }
                } else {
                    $scoreCol .= '<span class="text-muted">-</span>';
                }
                $scoreCol .= '</div>';

                // Status Badge
                $statusBadge = $this->renderStatusBadge($appStatus);

                // Action Buttons
                $actions = $this->template->appraisalTableButton('dashboard', $id, [
                    'status'        => $appStatus,
                    'employee_name' => $val['employee_name'] ?? 'Karyawan',
                    'is_admin'      => $isAdmin,
                ]);

                $karyawanCol = '<span class="font-weight-bold text-dark" title="' . esc($nik) . '">' . esc($empName) . '</span>';
                $posisiCol   = '<span class="text-dark">' . esc($posName) . '</span>';
                $levelCol    = '<span class="text-dark">' . esc($levelName) . '</span>';
                $cabangCol   = '<span class="text-dark">' . esc($branchName ?: '-') . '</span>';
                $divisiCol   = '<span class="text-dark">' . esc($divName) . '</span>';

                $row = [];
                $row[] = $id;          // [0] ID (hidden)
                $row[] = $number;      // [1] No
                $row[] = $karyawanCol; // [2] Karyawan
                $row[] = $posisiCol;   // [3] Jabatan
                $row[] = $levelCol;    // [4] Level
                $row[] = $cabangCol;   // [5] Cabang
                $row[] = $divisiCol;   // [6] Divisi
                $row[] = $cycleCol;    // [7] Siklus Penilaian
                $row[] = $scoreCol;    // [8] Nilai
                $row[] = $statusBadge; // [9] Status
                $row[] = $actions;     // [10] Aksi

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

    /**
     * Helper to render standardized workflow status badge
     */
    protected function renderStatusBadge(string $status): string
    {
        $label = M_Appraisal::getStatusLabel($status);
        return '<center>' . esc($label) . '</center>';
    }

    /**
     * Manual Nudge endpoint triggered by HR from the monitoring dashboard
     */
    public function nudge()
    {
        try {
            $empId = $this->getCurrentEmployeeId();
            if (!$this->isAppraisalAdmin($empId)) {
                return $this->response->setStatusCode(403)
                    ->setJSON(message('error', false, 'Anda tidak memiliki hak akses untuk mengirim pengingat.'));
            }

            $id = (int) ($this->request->getPost('id') ?? $this->request->getVar('id') ?? 0);
            if ($id <= 0) {
                return $this->response->setJSON(message('error', false, 'ID Penilaian tidak valid.'));
            }

            $actorId = (int) ($this->session->get('sys_user_id') ?? 100000);
            $nudgeService = new \App\Services\AppraisalNudgeServices();
            $result = $nudgeService->manualNudge($id, $actorId);

            if ($result['success']) {
                return $this->response->setJSON(message('success', true, $result['message']));
            }

            return $this->response->setJSON(message('error', false, $result['message']));
        } catch (\Throwable $e) {
            return $this->response->setJSON(message('error', false, '[PerformanceAppraisal::nudge] ' . $e->getMessage()));
        }
    }

    /**
     * Daily Cron endpoint for H-3 and Overdue Appraisal Nudges
     */
    public function cronDailyNudge()
    {
        try {
            $nudgeService = new \App\Services\AppraisalNudgeServices();
            $result = $nudgeService->runDailyNudge();
            return $this->response->setJSON(message('success', true, 'Daily appraisal nudge selesai diproses.', $result));
        } catch (\Throwable $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }
}
