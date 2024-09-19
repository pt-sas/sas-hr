<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_AbsentDetail;
use App\Models\M_Attendance;
use App\Models\M_Configuration;
use App\Models\M_Holiday;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_LeaveBalance;
use App\Models\M_MassLeave;
use App\Models\M_Transaction;
use Config\Services;

class LeaveCancel extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y'),
        ];

        return $this->template->render('transaction/leavecancel/v_leave_cancel', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'ref.documentno',
                'trx_absent.submissiondate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'ref.documentno',
                'trx_absent.submissiondate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_absent.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_absent.submissiontype'] = $this->model->Pengajuan_Pembatalan_Cuti;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->reference_doc;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = !is_null($value->receiveddate) ? format_dmy($value->receiveddate, '-') : "";
                $row[] = $value->reason;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus);
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
        $mRule = new M_Rule($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAttendance = new M_Attendance($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post["submissiontype"] = $this->model->Pengajuan_Pembatalan_Cuti;
            $post["necessary"] = 'CB';
            $today = date('Y-m-d');
            $employeeId = $post['md_employee_id'];

            $table = json_decode($post['table']);

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            $post['startdate'] = date('Y-m-d H:i', strtotime($post['submissiondate']));
            $post['enddate'] = date('Y-m-d H:i', strtotime($post['submissiondate']));

            try {
                if (!$this->validation->run($post, 'pembatalan_cuti')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $nik = $post['nik'];
                    $submissionDate = $post['submissiondate'];
                    $subDate = date('Y-m-d', strtotime($submissionDate));
                    $newTable;

                    $rule = $mRule->where([
                        'name'      => 'Pembatalan Cuti',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $post['md_employee_id'],
                        'validfrom <='      => $today
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

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($submissionDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        // Property For Loop
                        $insert = false;
                        $lastLoop = end($table);

                        foreach ($table as $key => $value) {
                            //TODO : Get next day attendance from enddate
                            $presentNextDate = null;

                            $dateClause = date('Y-m-d', strtotime($value->date));

                            if ($dateClause <= $subDate) {
                                $whereClause = "trx_absent.nik = $nik";
                                $whereClause .= " AND DATE_FORMAT(trx_absent.enddate, '%Y-%m-%d') > '$dateClause'";
                                $whereClause .= " AND trx_absent.docstatus = '{$this->DOCSTATUS_Completed}'";
                                $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                                $whereClause .= " AND trx_absent.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor}, {$this->model->Pengajuan_Sakit})";
                                $trxPresentNextDay = $this->modelDetail->getAbsentDetail($whereClause)->getRow();

                                if (is_null($trxPresentNextDay)) {
                                    $whereClause = "v_attendance.nik = '{$nik}'";
                                    $whereClause .= " AND v_attendance.date > '{$dateClause}'";
                                    $attPresentNextDay = $mAttendance->getAttendance($whereClause, 'ASC')->getRow();

                                    $presentNextDate = $attPresentNextDay ? $attPresentNextDay->date : $dateClause;
                                } else {
                                    $presentNextDate = $trxPresentNextDay->date;
                                }

                                $nextDate = lastWorkingDays($presentNextDate, $holidays, $minDays, false, $daysOff);

                                //* last index of array from variable nextDate
                                $lastDate = end($nextDate);
                            }

                            // TODO : Get Office Duties & SickLeave Submission
                            $whereClause = "trx_absent.nik = '{$nik}'";
                            $whereClause .= " AND trx_absent_detail.date = '{$dateClause}'";
                            $whereClause .= " AND trx_absent.docstatus = '{$this->DOCSTATUS_Completed}'";
                            $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                            $whereClause .= " AND trx_absent.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor}, {$this->model->Pengajuan_Sakit})";
                            $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                            // TODO : Get Leave Cancel Submission
                            $whereClause = "trx_absent.nik = '{$nik}'";
                            $whereClause .= " AND trx_absent_detail.date = '{$dateClause}'";
                            $whereClause .= " AND trx_absent.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                            $whereClause .= " AND trx_absent.submissiontype = {$this->model->Pengajuan_Pembatalan_Cuti}";
                            $trxLeaveCancel = $this->modelDetail->getAbsentDetail($whereClause)->getResult();


                            //TODO : Get attendance employee
                            $whereClause = "v_attendance.nik = '{$nik}'";
                            $whereClause .= " AND v_attendance.date = '{$dateClause}'";
                            $attPresent = $mAttendance->getAttendance($whereClause)->getRow();

                            $dateNow = format_dmy($value->date, '-');

                            if ($dateClause > $addDays) {
                                $response = message('success', false, "Tanggal {$dateNow} melewati tanggal ketentuan");
                                break;
                            } else if ($presentNextDate && !($lastDate >= $subDate)) {
                                $lastDate = format_dmy($lastDate, '-');

                                $response = message('success', false, "Maksimal pembatalan cuti untuk tanggal {$dateNow} adalah tanggal : {$lastDate}");
                                break;
                            } else if (($dateClause <= $subDate) && !$attPresent && !$trx) {
                                $response = message('success', false, "Tidak ada kehadiran, tidak bisa mengajukan pembatalan cuti pada tanggal : {$dateNow}");
                                break;
                            } else if ($trxLeaveCancel) {
                                $response = message('success', false, "Tidak bisa mengajukan pembatalan untuk tanggal {$dateNow}, karena sudah ada pengajuan lain");
                                break;
                            }

                            if ($value === $lastLoop)
                                $insert = true;
                        }


                        if ($insert) {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Pembatalan_Cuti, $post);
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
                $refLeave = $this->model->where([$this->model->primaryKey => $list[0]->reference_id])->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());
                $list = $this->field->setDataSelect($this->model->table, $list, 'reference_id', $refLeave->trx_absent_id, $refLeave->documentno);

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //Need to set data into date field in form
                $list[0]->startdate = format_dmy($list[0]->startdate, "-");
                $list[0]->enddate = format_dmy($list[0]->enddate, "-");

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine('update', $detail)
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
        $mAttendance = new M_Attendance($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $rowDetail = $this->modelDetail->where($this->model->primaryKey, $row->trx_absent_id)->findAll();
            $menu = $this->request->uri->getSegment(2);
            $today = date("Y-m-d");

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {

                        $keys = array_keys($rowDetail);
                        $lastLoop = end($keys);
                        $nik = $row->nik;

                        $process = false;
                        foreach ($rowDetail as $key => $value) {
                            $dateClause = date('Y-m-d', strtotime($value->date));

                            // TODO : Get Office Duties & SickLeave Submission
                            $whereClause = "trx_absent.nik = '{$nik}'";
                            $whereClause .= " AND trx_absent_detail.date = '{$dateClause}'";
                            $whereClause .= " AND trx_absent.docstatus = '{$this->DOCSTATUS_Completed}'";
                            $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                            $whereClause .= " AND trx_absent.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor}, {$this->model->Pengajuan_Sakit})";
                            $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                            //TODO : Get attendance employee
                            $whereClause = "v_attendance.nik = '{$nik}'";
                            $whereClause .= " AND v_attendance.date = '{$dateClause}'";
                            $attPresent = $mAttendance->getAttendance($whereClause)->getRow();

                            $dateNow = format_dmy($value->date, '-');


                            if (($dateClause <= $today) && !$attPresent && !$trx) {
                                $response = message('error', true, "Saldo cuti tanggal {$dateNow} sudah terpakai");
                                break;
                            }

                            if ($key === $lastLoop)
                                $process = true;
                        }

                        if ($process) {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
                        }
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

    public function tableLine($set = null, $detail = [])
    {

        $post = $this->request->getPost();

        $table = [];

        $fieldLine = new \App\Entities\Table();
        $fieldLine->setName("lineno");
        $fieldLine->setId("lineno");
        $fieldLine->setType("text");
        $fieldLine->setLength(50);
        $fieldLine->setIsReadonly(true);

        $fieldDate = new \App\Entities\Table();
        $fieldDate->setName("date");
        $fieldDate->setId("date");
        $fieldDate->setType("text");
        $fieldDate->setClass("datepicker");
        $fieldDate->setLength(200);
        $fieldDate->setIsReadonly(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set)) {
            foreach ($detail as $row) :
                $fieldDate->setValue(format_dmy($row->date, '-'));

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldDate),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldLine->setValue($row->lineno);
                $fieldDate->setValue(format_dmy($row->date, '-'));
                $btnDelete->setValue($row->trx_absent_detail_id);

                if ($row->isagree) {
                    $status = statusRealize($row->isagree);
                } else {
                    $status = '';
                }

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldDate),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getLeaveDetail()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            try {

                $list = $this->modelDetail->getAbsentDetail(["trx_absent.trx_absent_id" => $post['id'], "trx_absent.docstatus" => $this->DOCSTATUS_Completed, 'trx_absent_detail.isagree' => 'Y'])
                    ->getResult();

                $result = [
                    'line'      => $this->tableLine(null, $list)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}