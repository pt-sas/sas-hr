<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Absent;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Attendance;
use App\Models\M_AbsentDetail;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_AllowanceAtt;
use App\Models\M_Rule;

class Alpha extends BaseController
{
    /** Pengajuan Alpa */
    protected $Pengajuan_Alpa = 'alpa';

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
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/alpha/v_alpha', $data);
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
                'trx_absent.submissiondate',
                'trx_absent.startdate',
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
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
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

            $where = ['trx_absent.necessary' => 'AL'];

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
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
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
        $mEmployee = new M_Employee($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mRule = new M_Rule($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post["submissiontype"] = $this->Pengajuan_Alpa;
            $post["necessary"] = 'AL';

            try {
                if (!$this->validation->run($post, 'alpa')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = $post['startdate'];
                    $endDate = $post['enddate'];
                    $nik = $post['nik'];
                    $date = $post['submissiondate'];

                    $rule = $mRule->where([
                        'name'      => 'Alpa',
                        'isactive'  => 'Y'
                    ])->first();

                    $countDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $prevDate = lastWorkingDays($date, $holidays, $countDays);
                    $lastDate = end($prevDate);

                    $att = $mAttendance->where([
                        'nik'       => $nik,
                        'date'      => $startDate,
                        'absent'    => 'Y'
                    ])->first();

                    $whereClause = "trx_absent.nik = $nik";
                    $whereClause .= " AND trx_absent.startdate >= '$startDate' AND trx_absent.enddate <= '$endDate'";
                    $whereClause .= " AND trx_absent.docstatus = '$this->DOCSTATUS_Completed'";
                    $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                    $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                    if ($startDate < $lastDate && ($att || is_null($att))) {
                        $response = message('success', false, 'Tanggal mulai sudah melewati ketentuan, maksimal tanggal mulai : ' . format_dmy($lastDate, '-'));
                    } else if ($startDate = $lastDate && $trx) {
                        $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $post["submissiontype"], $post);
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
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //Need to set data into date field in form
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
                $result = $this->model->delete($id);
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
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                        $response = message('success', true, true);
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $mRule = new M_Rule($this->request);
                        $mAllowance = new M_AllowanceAtt($this->request);

                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $this->save();


                        $rule = $mRule->where([
                            'name'      => 'Alpa',
                            'isactive'  => 'Y'
                        ])->first();

                        $arr[] = [
                            "record_id"         => $_ID,
                            "table"             => $this->model->table,
                            "submissiontype"    => $row->getSubmissionType(),
                            "submissiondate"    => $row->getStartDate(),
                            "md_employee_id"    => $row->getEmployeeId(),
                            "amount"            => $rule->value,
                            "created_by"        => $this->access->getSessionUser(),
                            "updated_by"        => $this->access->getSessionUser()
                        ];

                        $mAllowance->builder->insertBatch($arr);

                        $response = message('success', true, 'Alpa dibatalkan');
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
                    $lineRef = $this->modelDetail->getDetail('trx_absent_detail_id', $row->ref_absent_detail_id)->getRow();
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

    public function generateAlpa()
    {
        $mAttendance = new M_Attendance($this->request);
        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $todayTime = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $agree = 'Y';

        try {
            $post = $this->request->getVar();
            $post['necessary'] = 'AL';
            $post['submissiondate'] = $today;

            $attendance = $mAttendance->where('trx_attendance_id', $post['trx_attendance_id'])->find();
            $employee = $mEmployee->where('nik', $attendance[0]->nik)->find();
            $branch = $mEmpBranch->where('md_employee_id', $employee[0]->md_employee_id)->find();
            $division = $mEmpDivision->where('md_employee_id', $employee[0]->md_employee_id)->find();

            $this->entity->setNecessary($post['necessary']);
            $this->entity->setSubmissionType('alpa');
            $this->entity->setEmployeeId($employee[0]->md_employee_id);
            $this->entity->setNik($employee[0]->nik);
            $this->entity->setBranchId($branch[0]->md_branch_id);
            $this->entity->setDivisionId($division[0]->md_division_id);
            $this->entity->setReceivedDate($todayTime);
            $this->entity->setReason('');
            $this->entity->setSubmissionDate($today);
            $this->entity->setStartDate($attendance[0]->date);
            $this->entity->setEndDate($attendance[0]->date);
            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            $docNo = $this->model->getInvNumber("submissiontype", 'alpa', $post);
            $this->entity->setDocumentNo($docNo);
            $this->save();

            //* Foreignkey id 
            $ID =  $this->insertID;

            $this->model = new M_AbsentDetail($this->request);
            $this->entity = new \App\Entities\AbsentDetail();
            $this->entity->isagree = $agree;
            $this->entity->trx_absent_id = $ID;
            $this->entity->lineno = 1;
            $this->entity->date = $attendance[0]->date;
            $this->save();

            $this->model = new M_Absent($this->request);
            $this->entity = new \App\Entities\Absent();
            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
            $this->entity->setAbsentId($ID);
            $this->save();

            $response = message('success', true, 'Alpa telah digenerate dengan nomor ' . $docNo);
        } catch (\Exception $e) {
            $response = message('error', false, $e->getMessage());
        }

        return $this->response->setJSON($response);
    }
}
