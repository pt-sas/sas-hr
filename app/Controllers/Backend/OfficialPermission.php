<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Employee;
use App\Models\M_LeaveType;
use App\Models\M_Holiday;
use App\Models\M_Attendance;
use App\Models\M_Rule;
use App\Models\M_EmpWorkDay;
use App\Models\M_WorkDetail;
use App\Models\M_Configuration;
use App\Models\M_DocumentType;
use App\Models\M_RuleDetail;
use App\Models\M_Year;
use DateTime;

class OfficialPermission extends BaseController
{
    protected $baseSubType;

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
        $this->baseSubType = $this->model->Pengajuan_Ijin_Resmi;
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/officialpermission/v_official_permission', $data);
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

            $where['md_employee.md_employee_id'] = ['value' => $this->access->getEmployeeData(false, true)];

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
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
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
        $mEmployee = new M_Employee($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mRule = new M_Rule($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mYear = new M_Year($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');

            $ID = isset($post['id']) ? $post['id'] : null;
            $post["submissiontype"] = $this->baseSubType;
            $post["necessary"] = 'IR';
            $employeeId = $post['md_employee_id'];
            $day = date('w');

            try {
                $img_name = "";
                $value = "";

                if (!empty($post['md_employee_id'])) {
                    $row = $mEmployee->find($post['md_employee_id']);
                    $lenPos = strpos($row->getValue(), '-');
                    $value = substr_replace($row->getValue(), "", $lenPos);
                    $ymd = date('YmdHis');
                }

                if ($file && $file->isValid()) {
                    $ext = $file->getClientExtension();
                    $img_name = $this->model->Pengajuan_Ijin_Resmi . '_' . $value . '_' . $ymd . '.' . $ext;
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'ijin_resmi')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = date('Y-m-d', strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $subDate = date('Y-m-d', strtotime($post['submissiondate']));

                    $rule = $mRule->where([
                        'name'      => 'Ijin Resmi',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $employeeId,
                        'validfrom <='      => $startDate,
                        'validto >='        => $endDate
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
                        $dateWorkRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

                        $dayClause = [];
                        $workClause = [];
                        foreach ($dateWorkRange as $value) {
                            $date = date('Y-m-d', strtotime($value));
                            $day = strtoupper(formatDay_idn(date('w', strtotime($value))));

                            $dayClause[] = "'{$day}'";
                            $workClause[] = "'{$date}'";
                        }

                        $dayClause = implode(", ", $dayClause);
                        $workClause = implode(", ", $workClause);

                        //TODO: Get Work Detail by day
                        $whereClause .= " AND md_day.name IN ({$dayClause})";
                        $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                        //TODO : Get attendance present employee
                        $whereClause = "v_attendance.md_employee_id = '{$employeeId}'";
                        $whereClause .= " AND v_attendance.date IN ({$workClause})";
                        $attPresent = $mAttendance->getAttendance($whereClause)->getResult();

                        //TODO : Get Max Last Date for Submission Past
                        if ($startDate <= $subDate) {
                            $attDate = [];
                            $lastDate = [];
                            $daysOffStr = implode(', ', $daysOff);

                            $date_range = getDatesFromRange($startDate, $subDate, [], 'Y-m-d', 'all', []);

                            foreach ($date_range as $date) {
                                $whereClause = "v_attendance.md_employee_id = {$employeeId}";
                                $whereClause .= " AND v_attendance.date = '{$date}'";
                                $whereClause .= " AND DATE_FORMAT(v_attendance.date, '%w') NOT IN ({$daysOffStr})";
                                $attPresentNextDay = $mAttendance->getAttendance($whereClause)->getRow();

                                $whereClause = "trx_absent.md_employee_id = {$employeeId}";
                                $whereClause .= " AND DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '$date'";
                                $whereClause .= " AND trx_absent.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor}, {$this->model->Pengajuan_Tugas_Kantor_setengah_Hari})";
                                $whereClause .= " AND trx_absent_detail.isagree IN ('Y','M','S')";
                                $whereClause .= " AND DATE_FORMAT(trx_absent_detail.date, '%w') NOT IN  ({$daysOffStr})";
                                $trxPresentNextDay =  $this->modelDetail->getAbsentDetail($whereClause)->getRow();

                                if ($attPresentNextDay || $trxPresentNextDay) {
                                    $attDate[] = $date;
                                }

                                $lastDate[] = $date;

                                if (count($attDate) == $minDays) {
                                    break;
                                }
                            }
                            $lastDate = end($lastDate);
                        }

                        //TODO : Get submission one day
                        $whereClause = "v_all_submission.md_employee_id = {$employeeId}";
                        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
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

                        // TODO : Checking Period
                        foreach ($dateWorkRange as $date) {
                            $period = $mYear->getPeriodStatus($date, $post['submissiontype'])->getRow();

                            if (empty($period) || $period->period_status == $this->PERIOD_CLOSED) {
                                break;
                            }
                        }

                        if (empty($period)) {
                            $response = message('success', false, "Periode belum dibuat");
                        } else if ($period->period_status == $this->PERIOD_CLOSED) {
                            $response = message('success', false, "Periode {$period->name} ditutup");
                        } else if ($trx) {
                            $date = format_dmy($trx->date, '-');
                            $response = message('success', false, "Tidak bisa mengajukan pada tanggal : {$date}, karena sudah ada pengajuan lain dengan no : {$trx->documentno}");
                        } else if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if (!empty($lastDate) && ($lastDate < $subDate) && $work && !$reopen) {
                            $lastDate = format_dmy($lastDate, '-');

                            $response = message('success', false, "Maksimal tanggal pengajuan pada tanggal : {$lastDate}");
                        } else if ($attPresent) {
                            $date = implode(", ", array_map(function ($value) {
                                return format_dmy($value->date, '-');
                            }, $attPresent));

                            $response = message('success', false, "Ada kehadiran, tidak bisa mengajukan pada tanggal : [{$date}]");
                        } else {
                            $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                uploadFile($file, $path, $img_name);

                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->baseSubType, $post, $this->session->get('sys_user_id'));
                                $this->entity->setDocumentNo($docNo);
                            } else {
                                $row = $this->model->find($this->getID());

                                if (!empty($post['image']) && !empty($row->getImage()) && $post['image'] !== $row->getImage()) {
                                    if (file_exists($path . $row->getImage()))
                                        unlink($path . $row->getImage());

                                    uploadFile($file, $path, $img_name);
                                }
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

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                if (file_exists($path . $list[0]->getImage())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage($path . $list[0]->getImage());
                } else {
                    $list[0]->setImage(null);
                }

                //* Need to set data into date field in form
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));
                $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
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
        $mYear = new M_Year($this->request);

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
                    // TODO : Checking Period
                    $dateRange = getDatesFromRange($row->startdate, $row->enddate, [], 'Y-m-d', "all");
                    foreach ($dateRange as $date) {
                        $period = $mYear->getPeriodStatus($date, $row->submissiontype)->getRow();

                        if (empty($period) || $period->period_status == $this->PERIOD_CLOSED) {
                            break;
                        }
                    }

                    if (empty($period)) {
                        $response = message('error', true, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('error', true, "Periode {$period->name} ditutup");
                    } else if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        //TODO : Get submission one day
                        $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
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

                            $this->model->createAbsentDetail($data, $row);

                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session, null, true);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Reopen) {
                        $holiday = $mHoliday->getHolidayDate();
                        $config = $mConfig->where('name', "MAX_DATE_REOPEN")->first();

                        $rule = $mRule->where([
                            'name'      => 'Ijin Resmi',
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

    public function getEndDate()
    {
        $mHoliday = new M_Holiday($this->request);

        if ($this->request->isAJAX()) {

            $leave = new M_LeaveType($this->request);
            $post = $this->request->getVar();

            try {
                $holidays = $mHoliday->getHolidayDate();
                $today = date('Y-m-d');

                if (!empty($post["md_leavetype_id"])) {
                    $leavetype = $leave->find($post["md_leavetype_id"]);

                    if ($leavetype->duration_type === "D") {
                        $post['startdate'] = !empty($post['startdate']) ? $post['startdate'] : $today;

                        // //TODO : Get work day employee
                        // $workDay = $mEmpWork->where([
                        //     'md_employee_id'    => $post['md_employee_id'],
                        //     'validfrom <='      => $today
                        // ])->orderBy('validfrom', 'ASC')->first();

                        // //TODO : Get Work Detail
                        // $whereClause = "md_work_detail.isactive = 'Y'";
                        // $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                        // $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        // $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $nextDate = lastWorkingDays($post['startdate'], $holidays, $leavetype->getDuration(), false);

                        $response = end($nextDate);
                    } else if ($leavetype->duration_type === "M") {
                        $response = date('Y-m-d', strtotime($post["startdate"] . "+" . $leavetype->duration . "month - 1 days"));
                    }
                } else {
                    $response = $today;
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return json_encode($response);
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
                    statusRealize($row->isagree)
                ];
            endforeach;
        }

        return json_encode($table);
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
