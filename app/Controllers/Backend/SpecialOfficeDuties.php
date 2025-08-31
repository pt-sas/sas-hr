<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use Config\Services;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDetail;
use App\Models\M_AssignmentDate;
use App\Models\M_AbsentDetail;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_AccessMenu;
use App\Models\M_Attendance;
use App\Models\M_Branch;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
use App\Models\M_Division;
use App\Models\M_RuleDetail;
use App\Models\M_SubmissionCancelDetail;
use TCPDF;

class SpecialOfficeDuties extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Assignment($this->request);
        $this->modelDetail = new M_AssignmentDetail($this->request);
        $this->modelSubDetail = new M_AssignmentDate($this->request);
        $this->entity = new \App\Entities\Assignment();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/specialofficeduties/v_special_office_duties', $data);
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
                'trx_assignment.documentno',
                'md_employee.fullname',
                'md_branch.name',
                'md_division.name',
                'trx_assignment.submissiondate',
                'trx_assignment.startdate',
                'trx_assignment.approveddate',
                'trx_assignment.reason',
                'trx_assignment.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_assignment.documentno',
                'md_employee.fullname',
                'md_branch.name',
                'md_division.name',
                'trx_assignment.submissiondate',
                'trx_assignment.startdate',
                'trx_assignment.enddate',
                'trx_assignment.approveddate',
                'trx_assignment.reason',
                'trx_assignment.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_assignment.submissiondate' => 'DESC'];

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $empDelegation = $mEmployee->getEmpDelegation($this->session->get('sys_user_id'));
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if (!empty($empDelegation)) {
                $arrEmployee = array_unique(array_merge($arrEmployee, $empDelegation));
            }

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if (!empty($empDelegation)) {
                    $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
                }

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['md_employee.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_assignment.submissiontype'] = $this->model->Pengajuan_Penugasan;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_assignment_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = !is_null($value->approveddate) ? format_dmy($value->approveddate, '-') : "";
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
        $mHoliday = new M_Holiday($this->request);
        $mEmployee = new M_Employee($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAbsent = new M_Absent($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);
            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                if (!$this->validation->run($post, 'penugasan')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $post["submissiontype"] = $this->model->Pengajuan_Penugasan;
                    $post["necessary"] = 'PG';
                    $employeeId = $post['md_employee_id'];
                    $startDate = date('Y-m-d', strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $today = date('H:i');
                    $dateReq = date('Y-m-d', strtotime($startDate));
                    $subDate = date('Y-m-d', strtotime($post['submissiondate']));
                    $holidays = $mHoliday->getHolidayDate();

                    $rule = $mRule->where([
                        'name'      => 'Penugasan',
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
                        $whereClause .= " AND md_employee_work.md_employee_id = {$employeeId}";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);

                        //* last index of array from variable nextDate
                        $nextDate = lastWorkingDays($startDate, $holidays, $minDays, false, $daysOff);
                        $lastDate = end($nextDate);

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        //* For Validation Same Day but Checking Max Time
                        $ruleDetail = $rule ? $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'isactive' => 'Y', 'name' => 'Batas waktu Pengajuan'])->first() : null;
                        $todayMinutes = convertToMinutes($today);
                        $maxMinutes = $ruleDetail ? convertToMinutes(date("H:i", strtotime($ruleDetail->condition))) : null;

                        $arrEmpId = array_map(function ($value) {
                            return $value->md_employee_id;
                        }, $table);

                        $empWork = $mEmployee
                            ->whereIn("md_employee_id", $arrEmpId)
                            ->where("NOT EXISTS (SELECT 1 
                                                FROM md_employee_work mew
                                                WHERE mew.md_employee_id = {$mEmployee->table}.md_employee_id
                                                AND date_format(validto, '%Y-%m-%d') >= '{$startDate}'
                                                AND date_format(validfrom, '%Y-%m-%d') <= '{$endDate}')")
                            ->findAll();

                        if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($lastDate < $subDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan');
                        } else if ($dateReq === $subDate && ($maxMinutes && ($todayMinutes > $maxMinutes))) {
                            $response = message('success', false, 'Maksimal jam pengajuan ' . $ruleDetail->condition);
                        } else if ($empWork) {
                            $value = implode(", ", array_map(function ($row) {
                                return $row->value;
                            }, $empWork));

                            $response = message('success', false, "Karyawan tidak terdaftar dalam hari kerja : [{$value}]");
                        } else {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Penugasan, $post);
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
        $mBranch = new M_Branch($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $branch_in = $mBranch->find($list[0]->branch_in);
                $branch_out = $mBranch->find($list[0]->branch_out);
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());
                $list = $this->field->setDataSelect($mBranch->table, $list, 'branch_in', $branch_in->getBranchId(), $branch_in->getName());
                $list = $this->field->setDataSelect($mBranch->table, $list, 'branch_out', $branch_out->getBranchId(), $branch_out->getName());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

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
                        $line = $this->modelDetail->where('trx_assignment_id', $_ID)->findAll();
                        $assignmentDate = $this->modelSubDetail->where("trx_assignment_detail_id", $line[0]->trx_assignment_detail_id)->first();

                        // TODO : Create Assignment Date if There's no one
                        if (empty($assignmentDate)) {
                            foreach ($line as $row) {
                                $data = [
                                    'id'         => $row->trx_assignment_detail_id,
                                    'created_by' => $this->access->getSessionUser(),
                                    'updated_by' => $this->access->getSessionUser()
                                ];
                                $this->model->createAssignmentDate($data, $row);
                            }
                        }

                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session, $this->modelSubDetail, true);
                        $response = message('success', true, true);
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
        $employee = new M_Employee($this->request);
        $mAssignmentDate = new M_AssignmentDate($this->request);

        $post = $this->request->getPost();

        $table = [];

        $btnChildRow = new \App\Entities\Table();
        $btnChildRow->setClass("details-control");

        $fieldEmployee = new \App\Entities\Table();
        $fieldEmployee->setName("md_employee_id");
        $fieldEmployee->setIsRequired(true);
        $fieldEmployee->setType("select");
        $fieldEmployee->setClass("select2");
        $fieldEmployee->setField([
            'id'    => 'md_employee_id',
            'text'  => 'value'
        ]);
        $fieldEmployee->setLength(200);

        $fieldDesctiprion = new \App\Entities\Table();
        $fieldDesctiprion->setName("description");
        $fieldDesctiprion->setId("description");
        $fieldDesctiprion->setType("text");
        $fieldDesctiprion->setLength(250);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set)) {
            if (!$this->validation->run($post, 'TugasKantorAddRow')) {
                $table = $this->field->errorValidation($this->model->table, $post);
            } else {
                $emp = $employee->find($this->session->get('md_employee_id'));
                $empId = $emp->getEmployeeId();

                $whereClause = "md_employee.isactive = 'Y'";

                if ($emp->getLevellingId() == 100002) {
                    $whereClause .= " AND (md_employee.superior_id = $empId OR md_employee.md_employee_id = $empId)";
                } else {
                    $whereClause .= " AND superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId";
                    $whereClause .= " AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                }

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $table = [
                    '',
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            }
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getAssignmentId();
                $header = $this->model->where('trx_assignment_id', $id)->first();
                $subDetail = $mAssignmentDate->where('trx_assignment_detail_id', $row->getAssignmentDetailId())->first();

                $emp = $employee->find($header->md_employee_id);
                $empId = $emp->getEmployeeId();

                $whereClause = "md_employee.isactive = 'Y'";

                if (
                    $emp->getLevellingId() == 100002
                ) {
                    $whereClause .= " AND (md_employee.superior_id = $empId OR md_employee.md_employee_id = $empId)";
                } else {
                    $whereClause .= " AND superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId";
                    $whereClause .= " AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                }

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $fieldEmployee->setValue($row->getEmployeeId());
                $fieldEmployee->setAttribute(['data-line-id' => $row->getAssignmentDetailId(), 'data-subdetail' => $subDetail ? 'Y' : 'N']);
                $fieldDesctiprion->setValue($row->getDescription());
                $btnDelete->setValue($row->getAssignmentDetailId());

                $table[] = [
                    '',
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getAssignmentDate()
    {
        if ($this->request->isAJAX()) {
            $mBranch = new M_Branch($this->request);
            $post = $this->request->getVar();
            $result = [];

            try {
                $line = $this->modelSubDetail->where('trx_assignment_detail_id', $post['id'])->orderBy('date', 'ASC')->findAll();

                foreach ($line as $row) {
                    $branch_in = $mBranch->find($row->branch_in);
                    $branch_out = $mBranch->find($row->branch_out);

                    $docNoRef = "";

                    if (!empty($row->reference_id)) {
                        $refModel = $row->table == 'trx_submission_cancel_detail' ? new M_SubmissionCancelDetail($this->request) : new M_AbsentDetail($this->request);

                        $lineRef = $refModel->getDetail($refModel->primaryKey, $row->reference_id)->getRow();
                        $docNoRef = $lineRef->documentno;
                    }
                    $time_in = '';
                    $time_out = '';

                    if ($row->realization_in) {
                        $time_in = format_time($row->realization_in);
                    }

                    if ($row->realization_out) {
                        $time_out = format_time($row->realization_out);
                    }

                    $result[] = [
                        'date' => format_dmy($row->date, '-'),
                        'branch_in' => $branch_in ? $branch_in->name : '',
                        'branch_out' => $branch_out ? $branch_out->name : '',
                        'clock_in' => $time_in != '00:00' ? $time_in : '',
                        'clock_out' => $time_out != '00:00' ? $time_out : '',
                        'description' => $row->description ?? '',
                        'isagree' => statusRealize($row->isagree),
                        'reference_id' => $docNoRef
                    ];
                }

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getRealizationData()
    {
        if ($this->request->isAJAX()) {
            $mBranch = new M_Branch($this->request);
            $mAttendance = new M_Attendance($this->request);
            $mAssignmentDetail = new M_AssignmentDetail($this->request);
            $post = $this->request->getVar();

            try {
                $subDetail = $this->modelSubDetail->find($post['id']);
                $line = $mAssignmentDetail->find($subDetail->{$mAssignmentDetail->primaryKey});

                $date = date('Y-m-d', strtotime($subDetail->date));
                $time_in = null;
                $time_out = null;

                $branch_in = $mBranch->find($subDetail->branch_in);
                $branch_out = $mBranch->find($subDetail->branch_out);

                $whereIn = " v_attendance_serialnumber.md_employee_id = {$line->md_employee_id}";
                $whereIn .= " AND v_attendance_serialnumber.date = '{$date}'";
                $whereIn .= " AND md_attendance_machines.md_branch_id = {$subDetail->branch_in}";
                $clock_in = $mAttendance->getAttendanceBranch($whereIn)->getRow();

                if ($clock_in && $clock_in->clock_in) {
                    $time_in = format_time($clock_in->clock_in);
                }

                $whereOut = " v_attendance_serialnumber.md_employee_id = {$line->md_employee_id}";
                $whereOut .= " AND v_attendance_serialnumber.date = '{$date}'";
                $whereOut .= " AND md_attendance_machines.md_branch_id = {$subDetail->branch_out}";
                $clock_out = $mAttendance->getAttendanceBranch($whereOut)->getRow();

                if ($clock_out && $clock_out->clock_out) {
                    $time_out = format_time($clock_out->clock_out);
                }

                $response = [
                    'branch_in' => ['id' => $branch_in->getBranchId(), 'text' => $branch_in->getName()],
                    'branch_out' => ['id' => $branch_out->getBranchId(), 'text' => $branch_out->getName()],
                    // 'branch_in' => $subDetail->branch_in,
                    'clock_in' => $time_in  ?? '',
                    'clock_out' => $time_out ?? ''
                ];
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function exportPDF($id)
    {
        $mEmployee = new M_Employee($this->request);
        $mDivision = new M_Division($this->request);
        $list = $this->model->find($id);
        $employee = $mEmployee->where($mEmployee->primaryKey, $list->md_employee_id)->first();
        $division = $mDivision->where($mDivision->primaryKey, $list->md_division_id)->first();
        $tglpenerimaan = '';

        if ($list->receiveddate !== null) {
            $tglpenerimaan = format_dmy($list->receiveddate, '-');
        };

        //bagian PF
        $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
        $pdf->setFont('helvetica', 'B', 20);
        $pdf->Cell(0, 25, 'FORM TUGAS KANTOR KHUSUS', 0, 1, 'C');
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
        $pdf->Cell(30, 0, 'Tanggal', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, ': ' . format_dmy($list->startdate, '-') . ' s/d ' . format_dmy($list->enddate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini bagian Alasan
        $pdf->Cell(30, 0, 'Alasan', 0, 0, 'L');
        $pdf->Cell(3, 0, ':', 0, 0, 'L');
        $pdf->MultiCell(0, 20, $list->reason, 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(2);
        //Bagian ttd
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(63, 0, 'Dibuat oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Disetujui oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Diketahui oleh,', 0, 0, 'C');
        $pdf->Ln(25);
        $pdf->Cell(63, 0, $employee->fullname, 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 1, 'C');
        $pdf->Cell(63, 0, 'Karyawan Ybs', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Mgr. Dept. Ybs', 0, 0, 'C');
        $pdf->Cell(63, 0, 'HRD', 0, 0, 'C');

        $this->response->setContentType('application/pdf');
        $pdf->IncludeJS("print();");
        $pdf->Output('pengajuan.pdf', 'I');
    }
}