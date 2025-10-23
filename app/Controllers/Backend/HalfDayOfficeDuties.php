<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_AbsentDetail;
use App\Models\M_AssignmentDate;
use App\Models\M_Holiday;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_Division;
use App\Models\M_Configuration;
use App\Models\M_DocumentType;
use App\Models\M_RuleDetail;
use App\Models\M_SubmissionCancelDetail;
use TCPDF;
use Config\Services;
use DateTime;

class HalfDayOfficeDuties extends BaseController
{
    protected $baseSubType;

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
        $this->baseSubType = $this->model->Pengajuan_Tugas_Kantor_setengah_Hari;
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/halfdayofficeduties/v_half_day_office_duties', $data);
    }

    public function showAll()
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $employee = $mEmployee->find($this->session->get('md_employee_id'));

            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_absent.documentno',
                'trx_absent.docstatus',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'trx_absent.docstatus',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            // TODO : Get Employee List
            $empList = $this->access->getEmployeeData();
            $where['md_employee.md_employee_id'] = ['value' => $empList];

            $where['trx_absent.submissiontype'] = $this->baseSubType;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_id;

                $editable = true;
                if ($employee && ($employee->md_levelling_id > 100003 || $value->md_employee_id == $employee->md_employee_id) && $value->isreopen == "Y") $editable = false;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmytime($value->startdate, '-') . " s/d " . format_dmytime($value->enddate, '-');
                $row[] = !is_null($value->receiveddate) ? format_dmy($value->receiveddate, '-') : "";
                $row[] = $value->reason;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus, $this->BTN_Print, $editable);
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
        $mHoliday = new M_Holiday($this->request);
        $mRule = new M_Rule($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');

            $ID = isset($post['id']) ? $post['id'] : null;
            $post["submissiontype"] = $this->baseSubType;
            $post["necessary"] = 'TH';
            $post["startdate"] = date('Y-m-d', strtotime($post["datestart"])) . " " . $post['starttime'];
            $post["enddate"] = date('Y-m-d', strtotime($post["dateend"])) . " " . $post['endtime'];
            $today = date('Y-m-d');
            $employeeId = $post['md_employee_id'];

            try {
                $img_name = "";

                if (!empty($employeeId)) {
                    $row = $mEmployee->find($employeeId);
                    $lenPos = strpos($row->getValue(), '-');
                    $value = substr_replace($row->getValue(), "", $lenPos);
                    $ymd = date('YmdHis');
                }

                if ($file && $file->isValid()) {
                    $ext = $file->getClientExtension();
                    $img_name = $this->model->Pengajuan_Tugas_Kantor_setengah_Hari . '_' . $value . '_' . $ymd . '.' . $ext;
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'pengajuantugas')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = date('Y-m-d', strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $subDate = date('Y-m-d', strtotime($post['submissiondate']));

                    $rule = $mRule->where([
                        'name'      => 'Tugas Kantor Setengah Hari',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $employeeId,
                        'validfrom <='      => $startDate,
                        'validto >='        => $startDate
                    ])->orderBy('validfrom', 'ASC')->first();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else {
                        //TODO : Get Work Detail
                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);
                        $nextDate = lastWorkingDays($startDate, $holidays, $minDays, false, $daysOff);

                        //* last index of array from variable nextDate
                        $lastDate = end($nextDate);

                        //TODO : Get submission Half Day Office
                        $whereClause = "v_all_submission.md_employee_id = {$employeeId}";
                        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND v_all_submission.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor_setengah_Hari})";
                        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                        $trx = $this->model->getAllSubmission($whereClause)->getRow();

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        // TODO : Get Reopen Status
                        $reopen = false;
                        if ($ID) {
                            $trxReopen = $this->model->where(['trx_absent_id' => $ID])->first();

                            if ($trxReopen->isreopen == "Y")
                                $reopen = true;
                        }

                        if ($trx) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                        } else if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if (!empty($lastDate) && ($lastDate < $subDate) && !$reopen) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan');
                        } else {
                            $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                            if ($this->isNew()) {
                                if ($file && $file->isValid())
                                    uploadFile($file, $path, $img_name);
                            } else {
                                $row = $this->model->find($this->getID());

                                if (empty($post['image']) && !empty($row->getImage()) && file_exists($path . $row->getImage())) {
                                    unlink($path . $row->getImage());
                                } else {
                                    uploadFile($file, $path, $img_name);
                                }
                            }

                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->baseSubType, $post, $this->session->get('sys_user_id'));
                                $this->entity->setDocumentNo($docNo);
                            }

                            $response = $this->save();
                        }
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
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                if (file_exists($path . $list[0]->getImage())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage($path . $list[0]->getImage());
                } else {
                    $list[0]->setImage(null);
                }

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //* Need to set data into date field in form
                $list[0]->starttime = format_time($list[0]->startdate);
                $list[0]->endtime = format_time($list[0]->enddate);
                $list[0]->datestart = format_dmy($list[0]->startdate, "-");
                $list[0]->dateend = format_dmy($list[0]->enddate, "-");

                $list[0]->starttime_realization = $list[0]->startdate_realization != "0000-00-00 00:00:00" ? format_time($list[0]->startdate_realization) : '';
                $list[0]->endtime_realization = $list[0]->enddate_realization != "0000-00-00 00:00:00" ? format_time($list[0]->enddate_realization) : '';

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(["starttime", "endtime", "datestart", "dateend", 'starttime_realization', 'endtime_realization']);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine('edit', $detail)
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
        $mConfig = new M_Configuration($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mHoliday = new M_Holiday($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $employeeId = $this->session->get('md_employee_id');
            $_ID = $post['id'];
            $_DocAction = $post['docaction'];
            $_SubType = $post['subtype'];
            $today = date('Y-m-d');
            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);
            $startDate = date('Y-m-d', strtotime($row->startdate));
            $endDate = date('Y-m-d', strtotime($row->enddate));

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        //TODO : Get submission Half Day Office
                        $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND v_all_submission.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor_setengah_Hari})";
                        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                        $trx = $this->model->getAllSubmission($whereClause)->getRow();

                        if ($trx) {
                            $response = message('error', true, "Sudah ada pengajuan lain dengan nomor : {$trx->documentno}");
                        } else {
                            // TODO : Create Line if not exist
                            $data = [
                                'id'        => $_ID,
                                'created_by' => $this->access->getSessionUser(),
                                'updated_by' => $this->access->getSessionUser()
                            ];

                            $this->model->createAbsentDetail($data, $row, true, true);

                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session, null, true);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Reopen) {
                        $holiday = $mHoliday->getHolidayDate();
                        $config = $mConfig->where('name', "MAX_DATE_REOPEN")->first();

                        $rule = $mRule->where([
                            'name'      => 'Tugas Kantor Setengah Hari',
                            'isactive'  => 'Y'
                        ])->first();
                        $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => 'Batas Reopen'])->first();

                        $maxDateReopen = DateTime::createFromFormat('d-m', $config->value);
                        $dateRange = getDatesFromRange($row->submissiondate, $today, $holiday, 'Y-m-d');

                        if (empty($_SubType)) {
                            $response = message('error', true, 'Silahkan pilih tipe form dahulu.');
                        } else if ($employeeId == $row->md_employee_id) {
                            $response = message('error', true, 'Tidak bisa reopen untuk pengajuan diri sendiri');
                        } else if (date('Y-m-d', strtotime($row->startdate)) > date('Y-m-d', strtotime($row->submissiondate))) {
                            $response = message('error', true, 'Tidak bisa reopen untuk pengajuan future');
                        } else if ($today > $maxDateReopen->format('Y-m-d')) {
                            $response = message('error', true, 'Batas reopen tanggal 24 Desember');
                        } else if (count($dateRange) > ($ruleDetail ? $ruleDetail->condition : 1)) {
                            $response = message('error', true, "Sudah melewati batas waktu reopen");
                        } else if ($row->isreopen == "Y") {
                            $response = message('error', true, "Dokumen ini sudah tidak bisa direopen");
                        } else {
                            if ($_SubType == $this->baseSubType) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                                $this->entity->setIsReopen('Y');
                                $this->entity->setIsApproved('');

                                $response = $this->save();
                            } else {
                                $response = message('error', true, "Tipe pengajuan ini tidak bisa direopen ke tipe pengajuan lain");
                            }
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
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

    public function tableLine($set = null, $detail = [])
    {
        $table = [];

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $docNoRef = "";
                $line = $this->model->where('trx_absent_id', $row->trx_absent_id)->first();

                if (!empty($row->ref_absent_detail_id)) {
                    if ($row->table === 'trx_submission_cancel_detail') {
                        $refModel = new M_SubmissionCancelDetail($this->request);
                    } else if ($row->table === 'trx_assignment') {
                        $refModel = new M_AssignmentDate($this->request);
                    } else {
                        $refModel = new M_AbsentDetail($this->request);
                    }
                    $lineRef = $refModel->getDetail($refModel->primaryKey, $row->ref_absent_detail_id)->getRow();
                    $docNoRef = $lineRef->documentno;
                }

                $table[] = [
                    $row->lineno,
                    format_dmy($row->date, '-'),
                    $line->getDocumentNo(),
                    $docNoRef,
                    statusRealize($row->isagree),
                    viewImage($row->trx_absent_detail_id, $row->image, true)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getRealizationData()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            try {
                $line = $this->modelDetail->find($post['id']);
                $row = $this->model->find($line->trx_absent_id);

                $response = [
                    'starttime' => format_time($row->startdate),
                    'endtime' => format_time($row->enddate)
                ];
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getImage($id)
    {
        $response = [];

        try {
            $row = $this->modelDetail->find($id);

            $response = [];

            if (!empty($row->image))
                array_push($response, base_url('uploads/pengajuan/' . $row->image));
        } catch (\Exception $e) {
            $response = message('error', false, $e->getMessage());
        }

        return $this->response->setJSON($response);
    }

    public function exportPDF($id)
    {
        $mEmployee = new M_Employee($this->request);
        $mDivision = new M_Division($this->request);
        $list = $this->model->find($id);
        $employee = $mEmployee->where($mEmployee->primaryKey, $list->md_employee_id)->first();
        $division = $mDivision->where($mDivision->primaryKey, $list->md_division_id)->first();
        $tglpenerimaan = '';
        $jamselesai = '';

        if ($list->receiveddate !== null) {
            $tglpenerimaan = format_dmy($list->receiveddate, '-');
        };

        if ($list->enddate !== null) {
            $jamselesai = format_time($list->enddate);
        };

        //bagian PF
        $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
        $pdf->setFont('helvetica', 'B', 20);
        $pdf->Cell(0, 25, 'FORM TUGAS KANTOR SETENGAH HARI', 0, 1, 'C');
        $pdf->setFont('helvetica', '', 12);
        //Ini untuk bagian field nama dan tanggal pengajuan
        $pdf->Cell(30, 0, 'Nama ', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . $employee->fullname, 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, 'Tanggal Pengajuan', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(30, 0, ': ' . format_dmy($list->submissiondate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini untuk bagian field divisi dan Tanggal diterima
        $pdf->Cell(30, 0, 'Divisi ', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . $division->name, 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, 'Tanggal Diterima', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(30, 0, ': ' . $tglpenerimaan, 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(10);
        //Ini bagian tanggal ijin dan jam
        $pdf->Cell(30, 0, 'Tanggal Ijin', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, ': ' . format_dmy($list->startdate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Cell(30, 0, 'Jam', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(15, 0, ': ' . format_time($list->startdate), 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(8, 0, 's/d ' . $jamselesai, 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini bagian Alasan
        $pdf->Cell(30, 0, 'Alasan', 0, 0, 'L');
        $pdf->Cell(3, 0, ':', 0, 0, 'L');
        $pdf->MultiCell(0, 20, $list->reason, 0, '', false, 1, null, null, false, 0, false, false, 20);
        //Bagian ttd
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(63, 0, 'Dibuat oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Disetujui oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Diketahui oleh,', 0, 0, 'C');
        $pdf->Ln(23);
        $pdf->Cell(63, 0, $employee->fullname, 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 1, 'C');
        $pdf->Cell(63, 0, 'Karyawan Ybs', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Mgr. Dept. Ybs', 0, 0, 'C');
        $pdf->Cell(63, 0, 'HRD', 0, 0, 'C');

        $this->response->setContentType('application/pdf');
        $pdf->Output('detail-laporan,pdf', 'I');
    }

    public function getRefSubType()
    {
        $mDocType = new M_DocumentType($this->request);

        if ($this->request->isAjax()) {
            try {
                $post = $this->request->getVar();

                $builder = $mDocType->whereIn('md_doctype_id', [$this->baseSubType])->where('isactive', 'Y');

                if (isset($post['search'])) {
                    $builder->like('name', $post['search']);
                }

                $list = $mDocType->findAll();

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->md_doctype_id;
                    $response[$key]['text'] = $row->name;
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
