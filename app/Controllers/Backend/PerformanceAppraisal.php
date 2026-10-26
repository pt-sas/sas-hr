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
     * Render Appraisal History & Monitoring Dashboard View
     */
    public function index()
    {
        // Fetch all cycles sorted by start date desc
        $cycles = $this->db->table('md_appraisal_cycle')
            ->select('md_appraisal_cycle_id, title, start_date, end_date, deadline_filling, deadline_review, deadline_reading, evaluator_due_date, gm_due_date, status')
            ->where('isactive', 'Y')
            ->orderBy('start_date', 'DESC')
            ->get()
            ->getResultArray();

        // Fetch active divisions
        $divisions = $this->db->table('md_division')
            ->select('md_division_id, name')
            ->where('isactive', 'Y')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

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
        ];

        return $this->template->render('masterdata/appraisal-dashboard/v_appraisal_dashboard', $data);
    }

    /**
     * Fetch KPI Summary, Completion Metrics, and Division Progress Analytics
     */
    public function getSummary()
    {
        try {
            $cycleId    = (int) ($this->request->getVar('cycle_id') ?? 0);
            $divisionId = (int) ($this->request->getVar('division_id') ?? 0);

            $builder = $this->db->table('md_appraisal a')
                ->where('a.isactive', 'Y');

            if ($cycleId > 0) {
                $builder->where('a.md_appraisal_cycle_id', $cycleId);
            }
            if ($divisionId > 0) {
                $builder->where('a.snapshot_division_id', $divisionId);
            }

            $appraisals = $builder->select('a.md_appraisal_id, a.md_appraisal_cycle_id, a.status, a.final_score, a.rating, a.snapshot_division_id, d.name as division_name')
                ->join('md_division d', 'd.md_division_id = a.snapshot_division_id', 'left')
                ->get()
                ->getResultArray();

            $total              = count($appraisals);
            $draftEvaluator     = 0;
            $submittedEvaluator = 0;
            $reviewGm           = 0;
            $pendingAck         = 0;
            $finalized          = 0;

            $totalFinalScore    = 0.0;
            $finalizedCountWithScore = 0;

            $ratingCounts = [
                'sangat_baik' => 0, // >= 3.50
                'baik'        => 0, // 2.75 - 3.49
                'cukup'       => 0, // 2.00 - 2.74
                'kurang'      => 0, // < 2.00
            ];

            $divisionGroups = [];

            foreach ($appraisals as $app) {
                $st   = $app['status'];
                $div  = $app['division_name'] ?: 'Tanpa Divisi';
                $score = $app['final_score'] !== null ? (float) $app['final_score'] : null;

                if (!isset($divisionGroups[$div])) {
                    $divisionGroups[$div] = [
                        'division_name' => $div,
                        'total'         => 0,
                        'in_progress'   => 0,
                        'finalized'     => 0,
                        'percent'       => 0.0,
                        'score_sum'     => 0.0,
                        'score_count'   => 0,
                        'avg_score'     => null,
                    ];
                }

                $divisionGroups[$div]['total']++;

                if ($st === M_Appraisal::STATUS_DRAFT_EVALUATOR) {
                    $draftEvaluator++;
                    $divisionGroups[$div]['in_progress']++;
                } elseif ($st === M_Appraisal::STATUS_SUBMITTED_EVALUATOR) {
                    $submittedEvaluator++;
                    $divisionGroups[$div]['in_progress']++;
                } elseif ($st === M_Appraisal::STATUS_REVIEW_GM) {
                    $reviewGm++;
                    $divisionGroups[$div]['in_progress']++;
                } elseif ($st === M_Appraisal::STATUS_PENDING_ACK) {
                    $pendingAck++;
                    $divisionGroups[$div]['in_progress']++;
                } elseif ($st === M_Appraisal::STATUS_FINALIZED) {
                    $finalized++;
                    $divisionGroups[$div]['finalized']++;

                    if ($score !== null) {
                        $totalFinalScore += $score;
                        $finalizedCountWithScore++;
                        $divisionGroups[$div]['score_sum'] += $score;
                        $divisionGroups[$div]['score_count']++;

                        if ($score >= 3.50) {
                            $ratingCounts['sangat_baik']++;
                        } elseif ($score >= 2.75) {
                            $ratingCounts['baik']++;
                        } elseif ($score >= 2.00) {
                            $ratingCounts['cukup']++;
                        } else {
                            $ratingCounts['kurang']++;
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

            $completionPercent = $total > 0
                ? round(($finalized / $total) * 100, 1)
                : 0.0;

            $avgFinalScore = $finalizedCountWithScore > 0
                ? round($totalFinalScore / $finalizedCountWithScore, 2)
                : null;

            $inProgressTotal = $draftEvaluator + $submittedEvaluator + $reviewGm + $pendingAck;

            // Fetch cycle details if a specific cycle is selected
            $cycleInfo = null;
            if ($cycleId > 0) {
                $cRow = $this->db->table('md_appraisal_cycle')
                    ->where('md_appraisal_cycle_id', $cycleId)
                    ->get()
                    ->getRowArray();
                if ($cRow) {
                    $dlFilling = !empty($cRow['deadline_filling']) ? $cRow['deadline_filling'] : (!empty($cRow['evaluator_due_date']) ? $cRow['evaluator_due_date'] : null);
                    $dlReview  = !empty($cRow['deadline_review']) ? $cRow['deadline_review'] : (!empty($cRow['gm_due_date']) ? $cRow['gm_due_date'] : null);
                    $dlReading = !empty($cRow['deadline_reading']) ? $cRow['deadline_reading'] : null;

                    $cycleInfo = [
                        'id'                 => $cRow['md_appraisal_cycle_id'],
                        'title'              => $cRow['title'],
                        'start_date'         => !empty($cRow['start_date']) ? date('d/m/Y', strtotime($cRow['start_date'])) : '-',
                        'end_date'           => !empty($cRow['end_date']) ? date('d/m/Y', strtotime($cRow['end_date'])) : '-',
                        'deadline_filling'   => !empty($dlFilling) ? date('d/m/Y', strtotime($dlFilling)) : '-',
                        'deadline_review'    => !empty($dlReview) ? date('d/m/Y', strtotime($dlReview)) : '-',
                        'deadline_reading'   => !empty($dlReading) ? date('d/m/Y', strtotime($dlReading)) : '-',
                        'evaluator_due_date' => !empty($dlFilling) ? date('d/m/Y', strtotime($dlFilling)) : '-',
                        'gm_due_date'        => !empty($dlReview) ? date('d/m/Y', strtotime($dlReview)) : '-',
                        'status'             => $cRow['status'],
                    ];
                }
            }

            $result = [
                'cycle'     => $cycleInfo,
                'metrics'   => [
                    'total'               => $total,
                    'in_progress'         => $inProgressTotal,
                    'draft_evaluator'     => $draftEvaluator,
                    'submitted_evaluator' => $submittedEvaluator,
                    'review_gm'           => $reviewGm,
                    'pending_ack'         => $pendingAck,
                    'finalized'           => $finalized,
                    'completion_percent'  => $completionPercent,
                    'avg_final_score'     => $avgFinalScore,
                    'rating_counts'       => $ratingCounts,
                ],
                'divisions' => array_values($divisionGroups),
            ];

            return $this->response->setJSON(message('success', true, $result));
        } catch (Exception $e) {
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
            $builder = $this->db->table('md_appraisal a')
                ->select('a.md_appraisal_id, a.md_employee_id, a.md_appraisal_cycle_id, a.status, a.performance_score, a.final_score, a.rating, a.finalized_at,
                          e.value as nik, e.fullname as employee_name,
                          d.name as division_name, p.name as position_name, l.name as level_name, b.name as branch_name,
                          c.title as cycle_title, c.status as cycle_status,
                          eval.fullname as evaluator_name, gm.fullname as gm_name')
                ->join('md_employee e', 'e.md_employee_id = a.md_employee_id', 'left')
                ->join('md_division d', 'd.md_division_id = a.snapshot_division_id', 'left')
                ->join('md_branch b', 'b.md_branch_id = a.snapshot_branch_id', 'left')
                ->join('md_position p', 'p.md_position_id = a.snapshot_position_id', 'left')
                ->join('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left')
                ->join('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = a.md_appraisal_cycle_id', 'left')
                ->join('md_employee eval', 'eval.md_employee_id = a.snapshot_evaluator_id', 'left')
                ->join('md_employee gm', 'gm.md_employee_id = a.snapshot_gm_id', 'left')
                ->where('a.isactive', 'Y');

            // Form Filter Parsing
            $formFilter = $this->request->getPost('form') ?? [];
            $cycleId    = (int) ($this->request->getPost('cycle_id') ?? 0);
            $divisionId = (int) ($this->request->getPost('division_id') ?? 0);
            $status     = $this->request->getPost('status') ?? null;

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
                if ($name === 'status') {
                    $status = $value;
                }
            }

            if ($cycleId > 0) {
                $builder->where('a.md_appraisal_cycle_id', $cycleId);
            }
            if ($divisionId > 0) {
                $builder->where('a.snapshot_division_id', $divisionId);
            }
            if (!empty($status) && $status !== 'ALL') {
                $builder->where('a.status', $status);
            }

            // Global Search Filter
            $search = $this->request->getPost('search')['value'] ?? null;
            if (!empty(trim($search))) {
                $s = trim($search);
                $builder->groupStart()
                    ->like('e.fullname', $s)
                    ->orLike('e.value', $s)
                    ->orLike('c.title', $s)
                    ->orLike('d.name', $s)
                    ->orLike('p.name', $s)
                    ->orLike('eval.fullname', $s)
                    ->orLike('gm.fullname', $s)
                    ->groupEnd();
            }

            $totalFiltered = (clone $builder)->countAllResults(false);

            // Sorting
            $orderColIdx = (int) ($this->request->getPost('order')[0]['column'] ?? 0);
            $orderDir    = $this->request->getPost('order')[0]['dir'] ?? 'desc';
            
            $columnMap = [
                0 => 'a.md_appraisal_id',
                1 => 'a.md_appraisal_id',
                2 => 'e.value',
                3 => 'e.fullname',
                4 => 'd.name',
                5 => 'c.title',
                6 => 'eval.fullname',
                7 => 'a.final_score',
                8 => 'a.status',
            ];

            if (isset($columnMap[$orderColIdx])) {
                $builder->orderBy($columnMap[$orderColIdx], $orderDir);
            } else {
                $builder->orderBy('a.md_appraisal_id', 'DESC');
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
                $appStatus    = $val['status'] ?? 'DRAFT_EVALUATOR';
                $score        = $val['final_score'] !== null ? number_format((float) $val['final_score'], 2) : '-';
                $rating       = $val['rating'] ?? '-';
                $cycleTitle   = $val['cycle_title'] ?? '-';
                $cycleStatus  = $val['cycle_status'] ?? 'OPEN';
                $evalName     = $val['evaluator_name'] ?: '-';
                $gmName       = $val['gm_name'] ?: '-';

                // Cycle Badge
                $cycleBadgeClass = ($cycleStatus === M_AppraisalCycle::STATUS_OPEN) ? 'badge-success' : 'badge-secondary';
                $cycleCol = '<strong>' . esc($cycleTitle) . '</strong><br><span class="badge ' . $cycleBadgeClass . ' px-2 py-0 mt-1" style="font-size:10px;">' . esc($cycleStatus) . '</span>';

                // Score & Grade Badge
                $scoreCol = '<div class="text-center">';
                if ($score !== '-') {
                    $numScore = (float) $val['final_score'];
                    $scoreColor = ($numScore >= 3.50) ? 'text-success' : (($numScore >= 2.75) ? 'text-primary' : (($numScore >= 2.00) ? 'text-warning' : 'text-danger'));
                    $scoreCol .= '<span class="font-weight-bold ' . $scoreColor . '" style="font-size:14px;">' . $score . '</span>';
                    if (!empty($rating) && $rating !== '-') {
                        $scoreCol .= '<br><small class="text-muted" style="font-size:11px;" title="' . esc($rating) . '">' . esc(explode('(', $rating)[0]) . '</small>';
                    }
                } else {
                    $scoreCol .= '<span class="text-muted">-</span>';
                }
                $scoreCol .= '</div>';

                // Status Badge
                $statusBadge = $this->renderStatusBadge($appStatus);

                // Action Buttons
                $actions = '<div class="d-flex align-items-center justify-content-center" style="gap: 4px;">';
                
                // View Scorecard / Rapor Button
                $actions .= '<a href="' . base_url('penilaian/complete/' . $id) . '" class="btn btn-sm btn-icon btn-round btn-info" data-toggle="tooltip" title="Lihat Rapor Lengkap"><i class="fas fa-file-alt"></i></a>';
                
                // Audit Trail Button
                $actions .= '<button type="button" class="btn btn-sm btn-icon btn-round btn-secondary" onclick="window.showAuditLogModal(' . $id . ')" data-toggle="tooltip" title="Riwayat Audit Trail"><i class="fas fa-history"></i></button>';
                
                $actions .= '</div>';

                $row = [];
                $row[] = $id;
                $row[] = $number;
                $row[] = esc($val['nik'] ?? '-');
                $row[] = '<strong>' . esc($val['employee_name'] ?? '-') . '</strong><br><small class="text-muted">' . esc($val['position_name'] ?? '') . ' &middot; ' . esc($val['level_name'] ?? '') . '</small>';
                $row[] = esc($val['division_name'] ?? '-') . '<br><small class="text-muted">' . esc($val['branch_name'] ?? 'Pusat') . '</small>';
                $row[] = $cycleCol;
                $row[] = '<small class="text-dark"><strong>Atasan:</strong> ' . esc($evalName) . '<br><strong>GM:</strong> ' . esc($gmName) . '</small>';
                $row[] = $scoreCol;
                $row[] = $statusBadge;
                $row[] = $actions;

                $data[] = $row;
            }

            return $this->response->setJSON([
                'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                'recordsTotal'    => $totalFiltered,
                'recordsFiltered' => $totalFiltered,
                'data'            => $data,
            ]);

        } catch (Exception $e) {
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
        switch ($status) {
            case M_Appraisal::STATUS_DRAFT_EVALUATOR:
                return '<center><span class="badge badge-warning px-2 py-1"><i class="fas fa-edit mr-1"></i>Draft Atasan</span></center>';
            case M_Appraisal::STATUS_SUBMITTED_EVALUATOR:
                return '<center><span class="badge badge-info px-2 py-1"><i class="fas fa-paper-plane mr-1"></i>Submit Atasan</span></center>';
            case M_Appraisal::STATUS_REVIEW_GM:
                return '<center><span class="badge badge-primary px-2 py-1"><i class="fas fa-user-tie mr-1"></i>Review GM</span></center>';
            case M_Appraisal::STATUS_PENDING_ACK:
                return '<center><span class="badge badge-secondary px-2 py-1" style="background:#6c757d;color:#fff;"><i class="fas fa-signature mr-1"></i>Menunggu TTD</span></center>';
            case M_Appraisal::STATUS_FINALIZED:
                return '<center><span class="badge badge-success px-2 py-1"><i class="fas fa-check-double mr-1"></i>Final</span></center>';
            default:
                return '<center><span class="badge badge-light border px-2 py-1">' . esc($status) . '</span></center>';
        }
    }
}
