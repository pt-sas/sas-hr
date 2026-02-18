<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Adjustment;
use App\Models\M_AllowanceAtt;
use App\Models\M_Assignment;
use App\Models\M_Attendance;
use App\Models\M_Configuration;
use App\Models\M_DocumentType;
use App\Models\M_EmpBranch;
use App\Models\M_Employee;
use App\Models\M_EmpWorkDay;
use App\Models\M_LeaveBalance;
use App\Models\M_Year;
use Config\Services;

class Adjustment extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Adjustment($this->request);
        $this->entity = new \App\Entities\Adjustment();
    }

    public function index()
    {
        $mDocType = new M_DocumentType($this->request);
        $data = [
            'today'     => date('d-M-Y'),
            'type'      => $mDocType->whereIn('md_doctype_id', [$this->model->Pengajuan_Adj_Cuti, $this->model->Pengajuan_Adj_TKH])->findAll()
        ];

        return $this->template->render('transaction/adjustment/v_adjustment', $data);
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

            $where['md_employee.md_employee_id'] = [
                'value'     => $this->access->getEmployeeData()
            ];

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->{$this->model->primaryKey};

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->employee_fullname;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->date, '-');
                $row[] = $value->reason;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus, $this->BTN_Print);
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
        if ($this->request->getMethod(true) === 'POST') {
            $mYear = new M_Year($this->request);
            $post = $this->request->getVar();
            $post["necessary"] = $post['submissiontype'] == $this->model->Pengajuan_Adj_Cuti ? 'AC' : 'AT';

            try {
                if (!$this->validation->run($post, 'penyesuaian')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $date = date('Y-m-d', strtotime($post['date']));

                    // TODO : Checking Period
                    $period = $mYear->getPeriodStatus($date, $post['submissiontype'])->getRow();

                    // TODO : In Progress Document
                    $whereClause = "DATE(date) = '{$date}'
                    AND md_employee_id = {$post['md_employee_id']}
                    AND submissiontype = {$post['submissiontype']}
                    AND docstatus = '{$this->DOCSTATUS_Inprogress}'";
                    $trx = $this->model->where($whereClause)->first();

                    if (empty($period)) {
                        $response = message('success', false, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('success', false, "Periode {$period->name} ditutup");
                    } else if ($trx) {
                        $response = message('success', false, "Ada document berjalan dengan nomor {$trx->documentno}");
                    } else if ($post['submissiontype'] == $this->model->Pengajuan_Adj_Cuti && $post['ending_balance'] < 0) {
                        $response = message('success', false, 'Saldo akhir cuti tidak bisa dibawah 0');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $post['submissiontype'], $post);
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
        $mYear = new M_Year($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
                $rowYear = $mYear->where('md_year_id', $list[0]->getYear())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                if (!empty($rowYear))
                    $list = $this->field->setDataSelect($mYear->table, $list, $mYear->primaryKey, $rowYear->md_year_id, $rowYear->year);

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();
                $list[0]->setDate(format_dmy($list[0]->date, "-"));

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
        if ($this->request->isAJAX()) {
            $cWfs = new WScenario();
            $mYear = new M_Year($this->request);

            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];
            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    $date = date('Y-m-d', strtotime($row->date));
                    // TODO : Checking Period
                    $period = $mYear->getPeriodStatus($date, $row->submissiontype)->getRow();


                    if (empty($period)) {
                        $response = message('error', true, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('error', true, "Periode {$period->name} ditutup");
                    } else if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        // TODO : In Progress Document
                        $whereClause = "DATE(date) = '{$date}'
                        AND md_employee_id = {$row->md_employee_id}
                        AND submissiontype = {$row->submissiontype}
                        AND docstatus = '{$this->DOCSTATUS_Inprogress}'";
                        $trx = $this->model->where($whereClause)->first();

                        if ($trx) {
                            $response = message('error', true, "Ada document berjalan dengan nomor {$trx->documentno}");
                        } else {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Reopen) {
                        $response = message('error', true, 'Dokumen ini tidak bisa direopen.');
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

    public function getBeginBalance()
    {
        $mLeavebalance = new M_LeaveBalance($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mAbsent = new M_Absent($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mConfig = new M_Configuration($this->request);
        $mEmployee = new M_Employee($this->request);
        $mAssignment = new M_Assignment($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mYear = new M_Year($this->request);

        if ($this->request->isAjax()) {
            $post = $this->request->getPost();

            try {
                $submissionType = $post['submissiontype'];
                $md_employee_id = $post['md_employee_id'];

                if ($submissionType == $this->model->Pengajuan_Adj_Cuti) {
                    $year = $mYear->find($post['date']);
                    $where = "md_employee_id = {$md_employee_id}";
                    $where .= " AND year = '{$year->year}'";

                    $begin_balance = (int) $mLeavebalance->getBalance($where)->balance_amount;
                } else if ($submissionType == $this->model->Pengajuan_Adj_TKH) {
                    $date = date('Y-m-d', strtotime($post['date']));

                    // TODO : Getting Configuration Manager not need Special Office Duties
                    $configMNSOD = $mConfig->where('name', 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES')->first();

                    $configMNSOD = $configMNSOD->value == 'Y' ? true : false;
                    $lvlManager = 100003;

                    // TODO : Get Employee Data
                    $employee = $mEmployee->find($md_employee_id);

                    $empBranch = $mEmpBranch->where('md_employee_id', $md_employee_id)->findAll();
                    $branchID = null;

                    if (count($empBranch) > 1) {
                        $branchID = array_column($empBranch, 'md_branch_id');

                        $branchID = implode(" ,", $branchID);
                    }

                    // TODO : Get Amount Allowance
                    $begin_balance = $mAllowance->getTotalAmount($md_employee_id, $date);

                    //TODO : Get work day employee
                    $day = strtoupper(formatDay_idn(date('w', strtotime($date))));

                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $md_employee_id";
                    $whereClause .= " AND (md_employee_work.validfrom <= '{$date}' and md_employee_work.validto >= '{$date}')";
                    $whereClause .= " AND md_day.name = '$day'";
                    $work = $mEmpWork->getEmpWorkDetail($whereClause)->getRow();

                    // TODO : Get Assignment
                    $whereClause = "DATE(v_all_submission.date) = '{$date}'";
                    $whereClause .= " AND v_all_submission.md_employee_id = {$md_employee_id}";
                    $whereClause .= " AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'";
                    $whereClause .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                    $whereClause .= " AND v_all_submission.submissiontype IN (100007, 100008, 100009)";
                    $assignment = $mAbsent->getAllSubmission($whereClause)->getRow();

                    // TODO : Get Attendance
                    $whereClause = "v_attendance.date = '{$date}'";
                    $whereClause .= " AND v_attendance.md_employee_id = {$md_employee_id}";
                    $attendance = $mAttendance->getAttendance($whereClause)->getRow();

                    // TODO : Get Adjustment
                    $whereClause = "DATE(date) = '{$date}'
                                    AND md_employee_id = {$md_employee_id}
                                    AND submissiontype = {$this->model->Pengajuan_Adj_TKH}
                                    AND docstatus = '{$this->DOCSTATUS_Completed}'";

                    $adjustment = $this->model->where($whereClause)->first();

                    if ($work || (($configMNSOD && $employee->md_levelling_id <= $lvlManager) ? !empty($attendance) : $assignment)) {
                        // TODO : Getting Submission Leave, Sick, Permission, Official Permission
                        $whereClause = "DATE(trx_absent_detail.date) = '{$date}'
                                        AND trx_absent.md_employee_id = {$md_employee_id}
                                        AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Sakit}, {$mAbsent->Pengajuan_Cuti}, {$mAbsent->Pengajuan_Ijin}, {$mAbsent->Pengajuan_Ijin_Resmi}, {$mAbsent->Pengajuan_Tugas_Kantor})
                                        AND trx_absent.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                                        AND trx_absent_detail.isagree = '{$this->LINESTATUS_Disetujui}'";
                        $trxAbsent = $mAbsentDetail->getAbsentDetail($whereClause)->getRow();

                        if (!$trxAbsent && !$adjustment) {
                            // TODO : get Tugas Kunjungan
                            $whereClause = "DATE(trx_assignment_date.date) = '{$date}'
                                            AND trx_assignment_detail.md_employee_id = {$md_employee_id}
                                            AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                                            AND trx_assignment_date.isagree = '{$this->LINESTATUS_Disetujui}'
                                            AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";
                            $tugasKunjungan = $mAssignment->getDetailData($whereClause)->getRow();

                            //TODO : Get Attendance if level under Manager and config is nonaktif
                            if ($configMNSOD && $employee->md_levelling_id <= $lvlManager) {
                                $clock_in = !empty($attendance->clock_in) ? $attendance->clock_in : null;
                                $clock_out = !empty($attendance->clock_out) ? $attendance->clock_out : null;
                            } else {
                                $whereIn = "v_attendance_branch.md_employee_id = {$md_employee_id}";
                                $whereIn .= " AND v_attendance_branch.date = '{$date}'";
                                $whereIn .= " AND v_attendance_branch.clock_in != ''";

                                $whereOut = "v_attendance_branch.md_employee_id = {$md_employee_id}";
                                $whereOut .= " AND v_attendance_branch.date = '{$date}'";
                                $whereOut .= " AND v_attendance_branch.clock_out != ''";

                                if ($tugasKunjungan) {
                                    $whereIn .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_in_line}";
                                    $whereOut .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_out_line}";
                                } else {
                                    if (!empty($branchID)) {
                                        $whereIn .= " AND v_attendance_branch.md_branch_id IN ($branchID)";
                                        $whereOut .= " AND v_attendance_branch.md_branch_id IN ($branchID)";
                                    } else {
                                        $whereIn .= " AND v_attendance_branch.md_branch_id = {$empBranch[0]->md_branch_id}";
                                        $whereOut .= " AND v_attendance_branch.md_branch_id = {$empBranch[0]->md_branch_id}";
                                    }
                                }

                                $attIn = $mAttendance->getAttBranch($whereIn, null, true)->getRow();
                                $attOut = $mAttendance->getAttBranch($whereOut, null, true)->getRow();

                                $clock_in = !empty($attIn) ? $attIn->clock_in : null;
                                $clock_out = !empty($attOut) ? $attOut->clock_out : null;
                            }

                            // TODO : Get Submission Forgot Absent In
                            $whereClause = "DATE(v_all_submission.date) = '{$date}'
                                            AND v_all_submission.md_employee_id = {$md_employee_id}
                                            AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                                            AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                                            AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Lupa_Absen_Masuk}";
                            $forgetAbsentIn = $mAbsent->getAllSubmission($whereClause)->getRow();

                            // TODO : Get Submission Forgot Absent Out
                            $whereClause = "DATE(v_all_submission.date) = '{$date}'
                                            AND v_all_submission.md_employee_id = {$md_employee_id}
                                            AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                                            AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                                            AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Lupa_Absen_Pulang}";
                            $forgetAbsentOut = $mAbsent->getAllSubmission($whereClause)->getRow();

                            // TODO : Get Submission Permission Submission Leave Early
                            $whereClause = "DATE(v_all_submission.date) = '{$date}'
                                            AND v_all_submission.md_employee_id = {$md_employee_id}
                                            AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                                            AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                                            AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Pulang_Cepat}";
                            $leaveEarly = $mAbsent->getAllSubmission($whereClause)->getRow();

                            // TODO : Get Submission Half Day Office Duties
                            $whereClause = "DATE(trx_absent_detail.date) = '{$date}'
                                            AND trx_absent.md_employee_id = {$md_employee_id}
                                            AND trx_absent.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                                            AND trx_absent_detail.isagree = '{$this->LINESTATUS_Disetujui}'
                                            AND trx_absent.submissiontype = {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}";
                            $officeHalfDay = $mAbsentDetail->getAbsentDetail($whereClause)->getRow();

                            if ($officeHalfDay) {
                                $startHour = convertToMinutes(date('H:i', strtotime($officeHalfDay->startdate_realization)));
                                $endHour = convertToMinutes(date('H:i', strtotime($officeHalfDay->enddate_realization)));
                            }

                            // This Variable for calculating if employee absent clock out less than minAbsentOut then meaning employee is late and will be punished for half TKH
                            $breakStart = $work ? convertToMinutes($work->breakstart) : convertToMinutes('12:00');
                            $minAbsentIn = $work ? convertToMinutes($work->startwork) : convertToMinutes('08:30');
                            $minAbsentOut = $work ? convertToMinutes($work->endwork) : convertToMinutes('15:30');

                            $empClockIn = !empty($clock_in) ? convertToMinutes($clock_in) : null;
                            $empClockOut = !empty($clock_out) ? convertToMinutes($clock_out) : null;

                            if ($work && is_null($empClockIn) && !$forgetAbsentIn && (!$officeHalfDay || ($officeHalfDay && $startHour > $breakStart))) {
                                $begin_balance = 0;
                            }

                            if ($work && is_null($empClockOut) && (!$forgetAbsentOut && !$leaveEarly) && (!$officeHalfDay || ($officeHalfDay && $endHour < $minAbsentOut))) {
                                $begin_balance = 0;
                            }

                            if ($work && !is_null($empClockOut) && $empClockOut < $minAbsentOut && !$leaveEarly && (!$officeHalfDay || ($officeHalfDay && $endHour < $minAbsentOut))) {
                                $begin_balance = 0;
                            }

                            if (!$work && ($tugasKunjungan || ($configMNSOD && $row->md_levelling_id <= $lvlManager)) && $empClockOut < $minAbsentOut) {
                                $begin_balance += -0.5;
                            }

                            if (!$work && $tugasKunjungan && $empClockIn > ($minAbsentIn + 30)) {
                                $begin_balance += -0.5;
                            }

                            if (!$work && ($tugasKunjungan || ($configMNSOD && $row->md_levelling_id <= $lvlManager)) && (is_null($empClockIn) || is_null($empClockOut))) {
                                $begin_balance = 0;
                            }
                        }
                    }
                }

                $response = ['data' => $begin_balance];
            } catch (\Throwable $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
