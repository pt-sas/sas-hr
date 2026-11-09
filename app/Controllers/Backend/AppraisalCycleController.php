<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Entities\AppraisalCycle;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalCycle;
use App\Models\M_Employee;
use App\Services\AppraisalCycleService;
use App\Services\AppraisalGeneratorService;
use App\Services\AppraisalTemplateResolverService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class AppraisalCycleController extends BaseController
{
    protected AppraisalCycleService $cycleService;
    protected AppraisalTemplateResolverService $resolverService;
    protected M_Employee $mEmployee;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->model           = new M_AppraisalCycle();
        $this->entity          = new AppraisalCycle();
        $this->cycleService    = new AppraisalCycleService();
        $this->resolverService = new AppraisalTemplateResolverService();
        $this->mEmployee       = new M_Employee($this->request);
        $this->primaryKey      = $this->model->primaryKey;
        $this->db              = \Config\Database::connect();
    }

    /**
     * Render Main Page View
     */
    public function index()
    {
        $data = [
            'today' => date('d-M-Y'),
        ];

        return $this->template->render('/masterdata/appraisal-cycle/v_appraisal_cycle', $data);
    }

    /**
     * Datatable fetch with form filters
     */
    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            try {
                $table  = $this->model->table;
                $select = $this->model->getSelect();
                $join   = $this->model->getJoin();
                $order  = $this->model->column_order;
                $sort   = $this->model->order;
                $search = $this->model->column_search;

                $where      = [];
                $formFilter = $this->request->getPost('form') ?? [];

                foreach ($formFilter as $row) {
                    $name  = $row['name']  ?? null;
                    $value = $row['value'] ?? null;

                    if ($value === '' || $value === null) {
                        continue;
                    }

                    if ($name === 'year') {
                        $where['YEAR(md_appraisal_cycle.start_date)'] = (int) $value;
                    }
                    if ($name === 'semester') {
                        $where['md_appraisal_cycle.semester'] = (int) $value;
                    }
                    if ($name === 'status') {
                        $where['md_appraisal_cycle.status'] = $value;
                    }
                }

                $data   = [];
                $number = (int) ($this->request->getPost('start') ?? 0);
                $list   = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

                foreach ($list as $value) {
                    $row = [];

                    $id        = is_object($value) ? ($value->md_appraisal_cycle_id ?? null) : ($value['md_appraisal_cycle_id'] ?? null);
                    $title     = is_object($value) ? ($value->title ?? '-') : ($value['title'] ?? '-');
                    $sem       = is_object($value) ? ($value->semester ?? '1') : ($value['semester'] ?? '1');
                    $startDate = is_object($value) ? ($value->start_date ?? null) : ($value['start_date'] ?? null);
                    $endDate   = is_object($value) ? ($value->end_date ?? null) : ($value['end_date'] ?? null);
                    $dlFilling = is_object($value) ? ($value->deadline_filling ?? $value->evaluator_due_date ?? null) : ($value['deadline_filling'] ?? $value['evaluator_due_date'] ?? null);
                    $dlReview  = is_object($value) ? ($value->deadline_review ?? $value->gm_due_date ?? null) : ($value['deadline_review'] ?? $value['gm_due_date'] ?? null);
                    $dlReading = is_object($value) ? ($value->deadline_reading ?? null) : ($value['deadline_reading'] ?? null);
                    $dlOverride= is_object($value) ? ($value->deadline_override ?? null) : ($value['deadline_override'] ?? null);
                    $status    = is_object($value) ? ($value->status ?? M_AppraisalCycle::STATUS_DRAFT) : ($value['status'] ?? M_AppraisalCycle::STATUS_DRAFT);

                    $number++;

                    $periodFormatted = (!empty($startDate) && !empty($endDate))
                        ? date('d/m/Y', strtotime($startDate)) . ' s/d ' . date('d/m/Y', strtotime($endDate))
                        : '-';
                    $dlFillingFormatted  = !empty($dlFilling) ? date('d/m/Y', strtotime($dlFilling)) : '-';
                    $dlReviewFormatted   = !empty($dlReview) ? date('d/m/Y', strtotime($dlReview)) : '-';
                    $dlReadingFormatted  = !empty($dlReading) ? date('d/m/Y', strtotime($dlReading)) : '-';
                    $dlOverrideFormatted = !empty($dlOverride) ? date('d/m/Y', strtotime($dlOverride)) : '-';

                    if ($status === M_AppraisalCycle::STATUS_OPEN) {
                        $statusBadge = '<center><span class="badge badge-success px-2 py-1"><i class="fas fa-play-circle mr-1"></i>OPEN</span></center>';
                    } elseif ($status === M_AppraisalCycle::STATUS_CLOSED) {
                        $statusBadge = '<center><span class="badge badge-secondary px-2 py-1"><i class="fas fa-lock mr-1"></i>CLOSED</span></center>';
                    } else {
                        $statusBadge = '<center><span class="badge badge-warning px-2 py-1"><i class="fas fa-edit mr-1"></i>DRAFT</span></center>';
                    }

                    $actions = '';
                    if ($status === M_AppraisalCycle::STATUS_DRAFT) {
                        $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\')" id="' . $id . '" title="Edit"><i class="fas fa-edit text-info"></i></a>';
                        $actions .= '<a class="btn btn-sm" onclick="window.cycPreviewBlast(\'' . $id . '\')" title="Simulasi Distribusi"><i class="fas fa-paper-plane text-warning"></i></a>';
                        $actions .= '<a class="btn btn-sm" onclick="window.cycOpen(\'' . $id . '\')" title="Buka Siklus"><i class="fas fa-play text-success"></i></a>';
                        $actions .= '<a class="btn btn-sm" onclick="Destroy(\'' . $id . '\')" title="Hapus"><i class="fas fa-trash-alt text-danger"></i></a>';
                    } elseif ($status === M_AppraisalCycle::STATUS_OPEN) {
                        $actions .= '<a class="btn btn-sm" onclick="window.cycProgress(\'' . $id . '\')" title="Monitoring Progres"><i class="fas fa-chart-line text-primary"></i></a>';
                        $actions .= '<a class="btn btn-sm" onclick="window.cycPreviewBlast(\'' . $id . '\')" title="Daftar Karyawan"><i class="fas fa-users text-info"></i></a>';
                        $actions .= '<a class="btn btn-sm" onclick="window.cycClose(\'' . $id . '\')" title="Tutup Siklus"><i class="fas fa-lock text-danger"></i></a>';
                    } else {
                        $actions .= '<a class="btn btn-sm" onclick="Edit(\'' . $id . '\', \'CLOSED\')" id="' . $id . '" data-status="CLOSED" title="Detail"><i class="fas fa-eye text-info"></i></a>';
                        $actions .= '<a class="btn btn-sm" onclick="window.cycProgress(\'' . $id . '\')" title="Laporan Akhir"><i class="fas fa-chart-line text-primary"></i></a>';
                    }

                    $titleCol = '<strong>' . esc($title) . '</strong>';
                    $semCol   = '<center>Semester ' . esc($sem) . '</center>';

                    $row[] = $id;
                    $row[] = $number;
                    $row[] = $titleCol;
                    $row[] = $semCol;
                    $row[] = $periodFormatted;
                    $row[] = $dlFillingFormatted;
                    $row[] = $dlReviewFormatted;
                    $row[] = $dlReadingFormatted;
                    $row[] = $dlOverrideFormatted;
                    $row[] = $statusBadge;
                    $row[] = $actions;

                    $data[] = $row;
                }

                $result = [
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                    'recordsFiltered' => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                    'data'            => $data,
                ];

                return $this->response->setJSON($result);

            } catch (Throwable $e) {
                return $this->response->setJSON([
                    'draw'            => (int) ($this->request->getPost('draw') ?? 1),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => '[AppraisalCycleController::showAll] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                ]);
            }
        }
    }

    /**
     * Fetch Single Cycle Details for Modal Auto-fill
     */
    public function show($id = null)
    {
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list)) {
                    $title = 'Detail Siklus Penilaian';
                    if (is_object($list[0])) {
                        $title = method_exists($list[0], 'getTitle') ? $list[0]->getTitle() : ($list[0]->title ?? $title);
                    } elseif (is_array($list[0])) {
                        $title = $list[0]['title'] ?? $title;
                    }

                    $fieldHeader = new \App\Entities\Table();
                    $fieldHeader->setTitle($title);
                    $fieldHeader->setTable($this->model->table);
                    $fieldHeader->setList($list);

                    $result = [
                        'header' => $this->field->store($fieldHeader),
                    ];

                    $response = message('success', true, $result);
                } else {
                    $response = message('error', false, '[AppraisalCycleController::show] Siklus penilaian tidak ditemukan.');
                }
            } catch (Throwable $e) {
                $response = message('error', false, '[AppraisalCycleController::show] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }

            return $this->response->setJSON($response);
        }
    }

    /**
     * Create or Update Appraisal Cycle
     */
    public function create()
    {
        $post = (array) $this->request->getVar();
        $id   = $this->getID();

        // 1. Validation via Config\Validation
        $this->validation->reset();
        if (!$this->validation->run($post, 'appraisal_cycle')) {
            $response = $this->field->errorValidation($this->model->table, $post);
            return $this->response->setJSON($response);
        }

        // 2. Normalize datepicker text inputs
        if (!empty($post['start_date'])) {
            $post['start_date'] = date('Y-m-d', strtotime($post['start_date']));
        }
        if (!empty($post['end_date'])) {
            $post['end_date'] = date('Y-m-d', strtotime($post['end_date']));
        }

        $dlFilling  = !empty($post['deadline_filling']) ? $post['deadline_filling'] : (!empty($post['evaluator_due_date']) ? $post['evaluator_due_date'] : null);
        $dlReview   = !empty($post['deadline_review']) ? $post['deadline_review'] : (!empty($post['gm_due_date']) ? $post['gm_due_date'] : null);
        $dlReading  = !empty($post['deadline_reading']) ? $post['deadline_reading'] : null;
        $dlOverride = !empty($post['deadline_override']) ? $post['deadline_override'] : null;

        $post['deadline_filling']   = !empty($dlFilling) ? date('Y-m-d', strtotime($dlFilling)) : null;
        $post['deadline_review']    = !empty($dlReview) ? date('Y-m-d', strtotime($dlReview)) : null;
        $post['deadline_reading']   = !empty($dlReading) ? date('Y-m-d', strtotime($dlReading)) : null;
        $post['deadline_override']  = !empty($dlOverride) ? date('Y-m-d', strtotime($dlOverride)) : null;
        $post['evaluator_due_date'] = $post['deadline_filling'];
        $post['gm_due_date']        = $post['deadline_review'];
        $post['semester']           = $post['semester'] ?? '1';

        // 3. Chronological date validation
        $dateError = $this->validateCycleDates($post);
        if ($dateError !== null) {
            return $this->response->setJSON($dateError);
        }

        // 4. Guard on closed cycle
        if (!empty($id)) {
            $existing = $this->model->find($id);
            if ($existing && method_exists($existing, 'isClosed') && $existing->isClosed()) {
                return $this->response->setJSON(message('error', false, 'Siklus penilaian yang sudah CLOSED tidak dapat diubah.'));
            }
        }

        $userId = (int) ($this->access->getSessionUser() ?? 1);

        $this->entity->fill($post);

        // Force "new" semantics for insert path in BaseController::save()
        $pk = $this->model->primaryKey;
        if (empty($id)) {
            $this->entity->{$pk}      = null;
            $this->entity->status     = M_AppraisalCycle::STATUS_DRAFT;
            $this->entity->created_by = $userId;
        } else {
            $this->entity->{$pk}      = $id;
        }
        $this->entity->updated_by = $userId;

        try {
            $saveResult = $this->save();
            return $this->response->setJSON($saveResult);
        } catch (\Throwable $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Validate cycle date ranges and chronological sequence.
     */
    protected function validateCycleDates(array $post): ?array
    {
        $startTs    = !empty($post['start_date']) ? strtotime($post['start_date']) : 0;
        $endTs      = !empty($post['end_date']) ? strtotime($post['end_date']) : 0;
        $fillingTs  = !empty($post['deadline_filling']) ? strtotime($post['deadline_filling']) : null;
        $reviewTs   = !empty($post['deadline_review']) ? strtotime($post['deadline_review']) : null;
        $readingTs  = !empty($post['deadline_reading']) ? strtotime($post['deadline_reading']) : null;
        $overrideTs = !empty($post['deadline_override']) ? strtotime($post['deadline_override']) : null;

        if ($endTs < $startTs) {
            return [
                ['error' => true, 'field' => $this->model->table],
                ['error' => 'error_end_date', 'field' => 'end_date', 'label' => 'Tanggal selesai harus lebih besar atau sama dengan tanggal mulai.'],
            ];
        }

        if ($fillingTs !== null) {
            if ($fillingTs < $startTs || $fillingTs > $endTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_filling', 'field' => 'deadline_filling', 'label' => 'Deadline pengisian harus berada di dalam rentang periode siklus.'],
                ];
            }
        }

        if ($reviewTs !== null) {
            if ($reviewTs < $startTs || $reviewTs > $endTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_review', 'field' => 'deadline_review', 'label' => 'Deadline review harus berada di dalam rentang periode siklus.'],
                ];
            }
            if ($fillingTs !== null && $reviewTs < $fillingTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_review', 'field' => 'deadline_review', 'label' => 'Deadline review tidak boleh mendahului deadline pengisian.'],
                ];
            }
        }

        if ($readingTs !== null) {
            if ($readingTs < $startTs || $readingTs > $endTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_reading', 'field' => 'deadline_reading', 'label' => 'Deadline pembacaan harus berada di dalam rentang periode siklus.'],
                ];
            }
            $prevTs = $reviewTs ?? $fillingTs;
            if ($prevTs !== null && $readingTs < $prevTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_reading', 'field' => 'deadline_reading', 'label' => 'Deadline pembacaan tidak boleh mendahului deadline sebelumnya.'],
                ];
            }
        }

        if ($overrideTs !== null) {
            if ($overrideTs < $startTs || $overrideTs > $endTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_override', 'field' => 'deadline_override', 'label' => 'Deadline masa tenggang HR harus berada di dalam rentang periode siklus.'],
                ];
            }
            $prevTs = $readingTs ?? ($reviewTs ?? $fillingTs);
            if ($prevTs !== null && $overrideTs < $prevTs) {
                return [
                    ['error' => true, 'field' => $this->model->table],
                    ['error' => 'error_deadline_override', 'field' => 'deadline_override', 'label' => 'Deadline masa tenggang HR tidak boleh mendahului deadline sebelumnya.'],
                ];
            }
        }

        return null;
    }

    /**
     * Open an appraisal cycle ('DRAFT' -> 'OPEN') and generate appraisal forms batch
     */
    public function open($id = null)
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(message('error', false, '[AppraisalCycleController::open] Metode HTTP tidak diizinkan. Gunakan POST.'));
        }

        try {
            $userId = (int) ($this->access->getSessionUser() ?? 1);
            $result = $this->cycleService->openCycle((int) $id, $userId);

            $msg = sprintf(
                'Siklus penilaian berhasil dibuka. Total diproses: %d karyawan, Berhasil digenerate: %d, Dilewati: %d.',
                $result['total_processed'] ?? 0,
                $result['generated'] ?? 0,
                $result['skipped'] ?? 0
            );

            return $this->response->setJSON(message('success', true, $msg, $result));
        } catch (Throwable $e) {
            return $this->response->setJSON(message('error', false, '[AppraisalCycleController::open] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()));
        }
    }

    /**
     * Close an appraisal cycle ('OPEN' -> 'CLOSED')
     */
    public function close($id = null)
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(message('error', false, '[AppraisalCycleController::close] Metode HTTP tidak diizinkan. Gunakan POST.'));
        }

        try {
            $userId = (int) ($this->access->getSessionUser() ?? 1);
            $this->cycleService->closeCycle((int) $id, $userId);

            return $this->response->setJSON(message('success', true, 'Siklus penilaian berhasil ditutup.'));
        } catch (Throwable $e) {
            return $this->response->setJSON(message('error', false, '[AppraisalCycleController::close] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()));
        }
    }

    /**
     * Destroy appraisal cycle (Strict POST)
     */
    public function destroy($id = null)
    {
        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(message('error', false, '[AppraisalCycleController::destroy] Metode HTTP tidak diizinkan. Gunakan POST.'));
        }

        try {
            $hasAppraisals = $this->db->table('md_appraisal')
                ->where('md_appraisal_cycle_id', (int) $id)
                ->where('isactive', 'Y')
                ->countAllResults();

            if ($hasAppraisals > 0) {
                return $this->response->setJSON(message('error', false, "[AppraisalCycleController::destroy] Siklus tidak dapat dihapus karena sudah memiliki {$hasAppraisals} data form penilaian yang terdistribusi."));
            }

            $result = $this->delete((int) $id);
            return $this->response->setJSON(message('success', $result, notification('updated')));
        } catch (Throwable $e) {
            return $this->response->setJSON(message('error', false, '[AppraisalCycleController::destroy] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()));
        }
    }

    /**
     * Preview Blast: Simulate employee form distribution and readiness status
     */
    public function previewBlast($id = null)
    {
        try {
            $cycleId   = !empty($id) ? (int) $id : (int) ($this->request->getVar('id') ?? 0);
            $generator = new AppraisalGeneratorService();
            $preview   = $generator->previewBatchForCycle($cycleId > 0 ? $cycleId : null);

            return $this->response->setJSON(message('success', true, $preview));
        } catch (Throwable $e) {
            return $this->response->setJSON(message('error', false, $e->getMessage()));
        }
    }

    /**
     * Progress Monitoring: Aggregate evaluation progress and division breakdown
     */
    public function progress($id = null)
    {
        try {
            $cycle = $this->model->find((int) $id);
            if (! $cycle) {
                return $this->response->setJSON(message('error', false, '[AppraisalCycleController::progress] Siklus penilaian tidak ditemukan.'));
            }

            $appraisals = $this->db->table('md_appraisal a')
                ->select('a.md_appraisal_id, a.md_employee_id, a.status, a.performance_score, a.final_score, a.snapshot_division_id, d.name as division_name')
                ->join('md_division d', 'd.md_division_id = a.snapshot_division_id', 'left')
                ->where('a.md_appraisal_cycle_id', (int) $id)
                ->where('a.isactive', 'Y')
                ->get()
                ->getResultArray();

            $total              = count($appraisals);
            $draftEvaluator     = 0;
            $submittedEvaluator = 0;
            $reviewGm           = 0;
            $pendingAck         = 0;
            $finalized          = 0;

            $divisionGroups = [];

            foreach ($appraisals as $app) {
                $st  = $app['status'];
                $div = $app['division_name'] ?: 'Tanpa Divisi';

                if (! isset($divisionGroups[$div])) {
                    $divisionGroups[$div] = [
                        'division_name' => $div,
                        'total'         => 0,
                        'in_progress'   => 0,
                        'finalized'     => 0,
                        'percent'       => 0.0,
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
                }
            }

            foreach ($divisionGroups as &$dGroup) {
                $dGroup['percent'] = $dGroup['total'] > 0
                    ? round(($dGroup['finalized'] / $dGroup['total']) * 100, 1)
                    : 0.0;
            }
            unset($dGroup);

            $completionPercent = $total > 0
                ? round(($finalized / $total) * 100, 1)
                : 0.0;

            $summary = [
                'total'               => $total,
                'draft_evaluator'     => $draftEvaluator,
                'submitted_evaluator' => $submittedEvaluator,
                'review_gm'           => $reviewGm,
                'pending_ack'         => $pendingAck,
                'finalized'           => $finalized,
                'completion_percent'  => $completionPercent,
            ];

            $cycleId    = is_object($cycle) ? ($cycle->md_appraisal_cycle_id ?? null) : ($cycle['md_appraisal_cycle_id'] ?? null);
            $cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
            $startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
            $endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);
            $dlFilling  = is_object($cycle) ? ($cycle->deadline_filling ?? $cycle->evaluator_due_date ?? null) : ($cycle['deadline_filling'] ?? $cycle['evaluator_due_date'] ?? null);
            $dlReview   = is_object($cycle) ? ($cycle->deadline_review ?? $cycle->gm_due_date ?? null) : ($cycle['deadline_review'] ?? $cycle['gm_due_date'] ?? null);
            $dlReading  = is_object($cycle) ? ($cycle->deadline_reading ?? null) : ($cycle['deadline_reading'] ?? null);
            $dlOverride = is_object($cycle) ? ($cycle->deadline_override ?? null) : ($cycle['deadline_override'] ?? null);
            $cycleStatus= is_object($cycle) ? ($cycle->status ?? '-') : ($cycle['status'] ?? '-');

            return $this->response->setJSON(message('success', true, [
                'cycle' => [
                    'id'                 => $cycleId,
                    'title'              => $cycleTitle,
                    'start_date'         => $startDate ? date('d/m/Y', strtotime($startDate)) : '-',
                    'end_date'           => $endDate ? date('d/m/Y', strtotime($endDate)) : '-',
                    'deadline_filling'   => $dlFilling ? date('d/m/Y', strtotime($dlFilling)) : '-',
                    'deadline_review'    => $dlReview ? date('d/m/Y', strtotime($dlReview)) : '-',
                    'deadline_reading'   => $dlReading ? date('d/m/Y', strtotime($dlReading)) : '-',
                    'deadline_override'  => $dlOverride ? date('d/m/Y', strtotime($dlOverride)) : '-',
                    'evaluator_due_date' => $dlFilling ? date('d/m/Y', strtotime($dlFilling)) : '-',
                    'gm_due_date'        => $dlReview ? date('d/m/Y', strtotime($dlReview)) : '-',
                    'status'             => $cycleStatus,
                ],
                'summary'   => $summary,
                'divisions' => array_values($divisionGroups),
            ]));
        } catch (Throwable $e) {
            return $this->response->setJSON(message('error', false, '[AppraisalCycleController::progress] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()));
        }
    }

    /**
     * Select2 Dropdown: Levelling Master
     */
    public function getListLevelling()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_levelling')
                ->select('md_levelling_id as id, name as text')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('name', trim($search));
            }

            $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();

            $response[] = ['id' => '0', 'text' => 'Semua Level (Default)'];
            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $row['text'],
                ];
            }
        } catch (Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }

    /**
     * Select2 Dropdown: Master Template List
     */
    public function getListTemplate()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_appraisal_template')
                ->select('md_appraisal_template_id as id, title as text, template_code')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('title', trim($search));
            }

            $list = $builder->orderBy('title', 'ASC')->get()->getResultArray();

            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => '[' . $row['template_code'] . '] ' . $row['text'],
                ];
            }
        } catch (Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }

    /**
     * Select2 Dropdown: Branch List
     */
    public function getListBranch()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_branch')
                ->select('md_branch_id as id, name as text')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('name', trim($search));
            }

            $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();

            $response[] = ['id' => '0', 'text' => 'Semua Cabang (Default)'];
            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $row['text'],
                ];
            }
        } catch (Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }

    /**
     * Select2 Dropdown: Division List
     */
    public function getListDivision()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_division')
                ->select('md_division_id as id, name as text')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('name', trim($search));
            }

            $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();

            $response[] = ['id' => '0', 'text' => 'Semua Divisi (Default)'];
            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $row['text'],
                ];
            }
        } catch (Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }

    /**
     * Select2 Dropdown: Position List
     */
    public function getListPosition()
    {
        $response = [];
        try {
            $search  = $this->request->getVar('search') ?? $this->request->getVar('term');
            $builder = $this->db->table('md_position')
                ->select('md_position_id as id, name as text')
                ->where('isactive', 'Y');

            if (!empty(trim($search)) && $search !== 'undefined' && $search !== 'null') {
                $builder->like('name', trim($search));
            }

            $list = $builder->orderBy('name', 'ASC')->get()->getResultArray();

            $response[] = ['id' => '0', 'text' => 'Semua Posisi (Default)'];
            foreach ($list as $row) {
                $response[] = [
                    'id'   => (string) $row['id'],
                    'text' => $row['text'],
                ];
            }
        } catch (Throwable $e) {
            $response = [];
        }

        return $this->response->setJSON($response);
    }
}