<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AbsentDetail;
use App\Models\M_Memo;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_EmpWorkDay;
use App\Models\M_Position;
use App\Models\M_ReferenceDetail;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Models\M_RuleValue;
use App\Models\M_Year;
use Config\Services;
use DateTime;
use TCPDF;

class Memo extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Memo($this->request);
        $this->entity = new \App\Entities\Memo();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/memo/v_memo', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

            $where['md_employee.md_employee_id'] = ['value' => $this->access->getEmployeeData(false)];

            $where['trx_hr_memo.memotype'] = $this->model->Pengajuan_Memo_SDM;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_hr_memo_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->memodate, '-');
                $row[] = $value->memocriteria;
                $row[] = $value->memocontent;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus, "Print");
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        $mEmpWork = new M_EmpWorkDay($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                if (!$this->validation->run($post, 'memo')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $today = date('Y-m-d');
                    $day = date('w');
                    $post["necessary"] = 'MS';
                    $employeeId = $post['md_employee_id'];
                    $nik = $post['nik'];
                    $memoDate = $post['memodate'];
                    $post["memotype"] = $this->model->Pengajuan_Memo_SDM;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'                            => $post['md_employee_id'],
                        'date_format(validto, "%Y-%m-%d") >='       => $today
                    ])->orderBy('validto', 'ASC')->first();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $docNo = $this->model->getInvNumber($post);
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                            $this->entity->setDocumentNo($docNo);
                        }

                        $response = $this->save();
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();


                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function destroy($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $result = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function processIt()
    {
        $cWfs = new WScenario();
        $mYear = new M_Year($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    // TODO : Checking Period
                    $period = $mYear->getPeriodStatus(date('Y-m-d', strtotime($row->memodate)), $row->memotype)->getRow();

                    if (empty($period)) {
                        $response = message('error', true, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('error', true, "Periode {$period->name} ditutup");
                    } else if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                        $response = message('success', true, true);
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if (($_DocAction === $this->DOCSTATUS_Unlock || $_DocAction === $this->DOCSTATUS_Voided)) {
                        $response = message('error', true, 'Tidak bisa diproses');
                    } else {
                        $this->entity->setDocStatus($_DocAction);
                        $response = $this->save();
                    }
                } else {
                    $response = message('error', true, 'Silahkan pilih tindakan terlebih dahulu.');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function indexGenerate()
    {
        $data = [
            'month' => date('M-Y', strtotime('-1 month'))
        ];

        return $this->template->render('generate/listmemo/v_list_memo', $data);
    }

    public function showAllGenerate()
    {
        $post = $this->request->getVar();

        if ($this->request->getMethod(true) === 'POST') {
            $mRule = new M_Rule($this->request);
            $mRuleDetail = new M_RuleDetail($this->request);

            $table = "v_attendance_submission";
            $select = $this->model->getSelectList();
            $join = $this->model->getJoinList();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['nik' => 'ASC', 'fullname' => 'ASC'];
            $number = $this->request->getPost('start');

            $period = date('m-Y');
            $year = date('Y');

            foreach ($post['form'] as $value) :
                if ($value['name'] === "periode") {
                    if (!empty($value['value'])) {
                        $period = date('m-Y', strtotime($value['value']));
                        $year = date('Y', strtotime($value['value']));
                    }
                }

            endforeach;

            $rule = $mRule->where('name', 'Memo')->first();
            $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->{$mRule->primaryKey})->findAll();

            $where = ["(v_attendance_submission.period = '{$period}' OR v_attendance_submission.period = '{$year}')
                    AND md_employee.md_levelling_id " . getOperation($ruleDetail[0]->operation) . " {$ruleDetail[0]->condition}
                    AND ((v_attendance_submission.type = 'alpa' AND v_attendance_submission.total " . getOperation($ruleDetail[5]->operation) . " {$ruleDetail[5]->condition}) 
                    OR (v_attendance_submission.type = 'kehadiran_masuk' AND v_attendance_submission.total " . getOperation($ruleDetail[1]->operation) . " {$ruleDetail[1]->condition})
                    OR (v_attendance_submission.type = 'kehadiran_pulang' AND v_attendance_submission.total " . getOperation($ruleDetail[3]->operation) . " {$ruleDetail[3]->condition})
                    OR (v_attendance_submission.type = 'ijin' AND v_attendance_submission.total " . getOperation($ruleDetail[6]->operation) . " {$ruleDetail[6]->condition}))"];

            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);
            $data = [];

            foreach ($list as $val) :
                $row = [];
                $ID = $val->md_employee_id;
                $number++;

                $row[] = $val->nik;
                $row[] = $val->fullname;
                $row[] = $val->branch;
                $row[] = $val->division;
                $row[] = $val->type;
                $row[] = $period;
                $row[] = $val->total;
                $row[] = $this->template->buttonGenerateMemo($ID);
                $data[] = $row;
            endforeach;

            $recordTotal = count($data);
            $recordsFiltered = count($data);

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $recordTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function generateMemo()
    {
        if ($this->request->isAJAX()) {
            $mEmployee = new M_Employee($this->request);
            $mBranch = new M_Branch($this->request);
            $mDiv = new M_Division($this->request);
            $mPosition = new M_Position($this->request);
            $mRule = new M_Rule($this->request);
            $mRuleDetail = new M_RuleDetail($this->request);
            $mRuleValue = new M_RuleValue($this->request);
            $mAbsentDetail = new M_AbsentDetail($this->request);
            $mYear = new M_Year($this->request);

            try {
                $post = $this->request->getVar();
                $post = json_decode($post['memos'])[0];
                $post = (array)$post;

                $post['necessary'] = 'MS';
                $today = date('Y-m-d');

                $name = $post['name'];
                $nik = $post['nik'];
                $employeeId = $post['md_employee_id'];
                $branch =  $post['branch'];
                $division =  $post['division'];
                $criteria =  [];
                $period = $post['period'];
                $totalDays = $post['total'];

                $rule = $mRule->where(['name' => 'Memo', 'isactive' => 'Y'])->first();
                $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->{$mRule->primaryKey})->findAll();

                // TODO : Get Memo Last Month
                $lastMonth = DateTime::createFromFormat('m-Y', $period);

                $memoLastMonthAgo = $this->model->where([
                    'docstatus'                             => 'CO',
                    'date_format(memodate, "%Y-%m")'        => $lastMonth->modify('-1 month')->format('Y-m'),
                    'md_employee_id'                        => $employeeId
                ])->first();

                // TODO : Get All Memo List
                $date = DateTime::createFromFormat('m-Y', $period);

                $where = "(v_attendance_submission.period = '{$period}' OR v_attendance_submission.period = '{$date->format('Y')}')
                    AND md_employee.md_employee_id = {$employeeId}
                    AND md_employee.md_levelling_id " . getOperation($ruleDetail[0]->operation) . " {$ruleDetail[0]->condition}
                    AND ((v_attendance_submission.type = 'alpa' AND v_attendance_submission.total " . getOperation($ruleDetail[5]->operation) . " {$ruleDetail[5]->condition}) 
                    OR (v_attendance_submission.type = 'kehadiran_masuk' AND v_attendance_submission.total " . getOperation($ruleDetail[1]->operation) . " {$ruleDetail[1]->condition})
                    OR (v_attendance_submission.type = 'kehadiran_pulang' AND v_attendance_submission.total " . getOperation($ruleDetail[3]->operation) . " {$ruleDetail[3]->condition})
                    OR (v_attendance_submission.type = 'ijin' AND v_attendance_submission.total " . getOperation($ruleDetail[6]->operation) . " {$ruleDetail[6]->condition}))";

                $memoList = $this->model->getMemoList($where)->getResult();

                // TODO : Do Looping to get memo Level and Set memo Content
                $memoLevel = $memoLastMonthAgo ? $memoLastMonthAgo->getMemoLevel() : 0;
                $memoContent = "";
                $number = 1;

                $post['memodate'] = $date->format('Y-m-d');

                // TODO : Checking Period
                $period = $mYear->getPeriodStatus($post['memodate'], $this->model->Pengajuan_Memo_SDM)->getRow();

                if (empty($period)) {
                    return $this->response->setJSON(message('success', false, "Periode belum dibuat"));
                } else if ($period->period_status == $this->PERIOD_CLOSED) {
                    return $this->response->setJSON(message('success', false, "Periode {$period->name} ditutup"));
                }

                foreach ($memoList as $memo) {
                    $memoLevel += 1;
                    $criteria[] = $memo->type;
                    $str = ucfirst(str_replace('_', ' ', $memo->type));

                    if ($memo->type == "kehadiran_masuk" && $memo->total > $ruleDetail[2]->condition) {
                        $memoLevel += 1;
                        $memoContent .= " {$number}. {$str} lebih dari {$ruleDetail[2]->condition} Kali Total {$str} dalam bulan {$period} = {$memo->total} Kali.";
                    } else if ($memo->type == "kehadiran_masuk") {
                        $memoContent .= " {$number}. {$str} lebih dari {$ruleDetail[1]->condition} Kali Total {$str} dalam bulan {$period} = {$memo->total} Kali.";
                    }

                    if ($memo->type == "kehadiran_pulang" && $memo->total > $ruleDetail[4]->condition) {
                        $memoLevel += 1;
                        $memoContent .= " {$number}. {$str} lebih dari {$ruleDetail[4]->condition} Kali Total {$str} dalam bulan {$period} = {$memo->total} Kali.";
                    } else if ($memo->type == "kehadiran_pulang") {
                        $memoContent .= " {$number}. {$str} lebih dari {$ruleDetail[3]->condition} Kali Total {$str} dalam bulan {$period} = {$memo->total} Kali.";
                    }

                    if ($memo->type == "ijin") {
                        $memoContent .= " {$number}. {$str} lebih dari {$ruleDetail[6]->condition} Kali Total {$str} dalam bulan {$period} = {$memo->total} Kali.";
                    }

                    if ($memo->type == "alpa") {
                        $memoContent .= " {$number}. {$str} lebih dari {$ruleDetail[5]->condition} Kali Total {$str} dalam tahun {$date->format('Y')} = {$memo->total} Kali.";
                    }

                    $number++;
                }

                if ($memoLevel > 3) {
                    return $this->response->setJSON(message('error', false, "Maaf, Memo tidak bisa digenerate karena melebihi Memo SDM 3"));
                }

                $date->modify('last day of this month');
                $post['memodate'] = $date->format('Y-m-d');
                $post['submissiondate'] = $today;

                $post['md_branch_id'] = $mBranch->where('name', $branch)->first()->getBranchId();
                $post['md_division_id'] = $mDiv->where('name', $division)->first()->getDivisionId();
                $rowEmp = $mEmployee->find($employeeId);

                $rowPoisition = $mPosition->find($rowEmp->md_position_id);
                $position = $rowPoisition ? $rowPoisition->getName() : "";
                $isAlpa = in_array('alpa', $criteria) ? true : false;
                $criteria = ucwords(str_replace('_', ' ', implode(", ", $criteria)));

                $this->entity->setEmployeeId($employeeId);
                $this->entity->setNik($nik);
                $this->entity->setBranchId($post['md_branch_id']);
                $this->entity->setDivisionId($post['md_division_id']);
                $this->entity->setMemoType($this->model->Pengajuan_Memo_SDM);
                $this->entity->setSubmissionDate($post['submissiondate']);
                $this->entity->setMemoDate($post['memodate']);
                $this->entity->setMemoCriteria($criteria);
                $this->entity->setTotalDays($totalDays);
                $this->entity->setMemoLevel($memoLevel);
                $this->entity->setDescription("Tanggal pemotongan akan berlaku selama karyawan masuk kerja (tanggal tidak mengikat).");

                $ruleValue = $mRuleValue->where(['name' => "Memo SDM {$memoLevel}"])->first();
                $this->entity->setMemoContent("Memo SDM {$memoLevel}. Pemotongan Tunjangan kehadiran atas nama : {$name} - {$nik} ({$position} - {$branch}) Sebanyak " . abs($ruleValue->value) . " hari kerja, dikarenakan :" . $memoContent);

                if ($this->isNew()) {
                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                    $docNo = $this->model->getInvNumber($post);
                    $this->entity->setDocumentNo($docNo);
                }

                $this->save();

                if ($isAlpa) {
                    $whereClause = "trx_absent.md_employee_id = {$employeeId}";
                    $whereClause .= " AND trx_absent.docstatus = 'CO'";
                    $whereClause .= " AND trx_absent_detail.is_generated_memo = 'N'";
                    $whereClause .= " AND DATE_FORMAT(trx_absent_detail.date, '%Y') = '{$date->format('Y')}'";
                    $listAlpa = $mAbsentDetail->getAbsentDetail($whereClause)->getResult();

                    foreach ($listAlpa as $alpa) {
                        $data = [
                            'is_generated_memo' => 'Y'
                        ];

                        $mAbsentDetail->builder->update($data, [$mAbsentDetail->primaryKey => $alpa->trx_absent_detail_id]);
                    }
                }

                $response = message('success', true, "Memo telah digenerate dengan nomor {$docNo}");
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function exportPDF($id)
    {
        $mEmployee = new M_Employee($this->request);
        $list = $this->model->find($id);
        $employee = $mEmployee->where($mEmployee->primaryKey, $list->created_by)->first();

        $memosplit = preg_split('/(?=Pemotongan|Sebanyak|Total)/', $list->memocontent);


        //bagian PF
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
        $pdf->setFont('helvetica', 'B', 20);
        $pdf->Cell(0, 20, 'MEMO SDM', 0, 1, 'C');
        $pdf->setFont('helvetica', '', 12);
        //Ini untuk bagian field nama dan tanggal pengajuan
        $pdf->Cell(40, 0, 'Tanggal Pembuatan', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . format_dmy($list->memodate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(5);
        //Ini untuk bagian field divisi dan Tanggal diterima
        $pdf->Cell(40, 0, 'Isi Memo :', 0, 1, 'L', false, '', 0, false);
        // $pdf->Cell(3, 0, ':', 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->MultiCell(0, 0, $memosplit[1], 0, '', false, 1, null, null, false, 0, false, false, 20);
        // $pdf->Ln(1);
        $pdf->Cell(0, 0, $memosplit[2], 0, 1, 'L', false, '', 0, false);
        // $pdf->Ln(1);
        $pdf->Cell(0, 0, $memosplit[3], 0, 1, 'L', false, '', 0, false);
        // $pdf->MultiCell(0, 20, $memosplit[1], 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(5);
        //Ini bagian tanggal ijin dan jam
        $pdf->Cell(10, 0, 'Ket :', 0, 0, 'L', false, '', 0, false);
        $pdf->MultiCell(0, 10, $list->description, 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(10);
        //Bagian ttd
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(48, 0, 'Dibuat oleh,', 0, 0, 'C');
        $pdf->Cell(48, 0, 'Disetujui oleh,', 0, 0, 'C');
        $pdf->Cell(48, 0, 'Mgr. Ybs,', 0, 0, 'C');
        $pdf->Cell(48, 0, 'Kary. Ybs,', 0, 0, 'C');
        $pdf->Ln(25);
        $pdf->Cell(48, 0, $employee->fullname, 0, 0, 'C');
        $pdf->Cell(48, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(48, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(48, 0, '(                          )', 0, 1, 'C');

        $this->response->setContentType('application/pdf');
        $pdf->Output('detail-laporan,pdf', 'I');
    }
}
