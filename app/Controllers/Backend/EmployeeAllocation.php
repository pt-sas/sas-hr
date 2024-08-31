<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Holiday;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_Division;
use App\Models\M_EmployeeAllocation;
use App\Models\M_RuleDetail;
use App\Models\M_Branch;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use TCPDF;

class EmployeeAllocation extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_EmployeeAllocation($this->request);
        $this->entity = new \App\Entities\EmployeeAllocation();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/employeeallocation/v_employee_allocation', $data);
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
                'trx_employee_allocation.documentno',
                'md_employee.fullname',
                'trx_employee_allocation.nik',
                'md_branch.name',
                'md_division.name',
                'bto.name',
                'dto.name',
                'trx_employee_allocation.submissiondate',
                'trx_employee_allocation.startdate',
                'trx_employee_allocation.description',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_employee_allocation.documentno',
                'md_employee.fullname',
                'trx_employee_allocation.nik',
                'md_branch.name',
                'md_division.name',
                'bto.name',
                'dto.name',
                'trx_employee_allocation.submissiondate',
                'trx_employee_allocation.startdate',
                'trx_employee_allocation.enddate',
                'trx_employee_allocation.description',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_employee_allocation.documentno' => 'ASC'];

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

                    $where['trx_employee_allocation.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_employee_allocation.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_employee_allocation.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_employee_allocation.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_employee_allocation.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_employee_allocation.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_employee_allocation.submissiontype'] = $this->model->Pengajuan_Tugas_Kantor;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_employee_allocation_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->branch_to;
                $row[] = $value->division_to;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = $value->description;
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
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post["submissiontype"] = $this->model->Pengajuan_Perbantuan;
            $post["necessary"] = 'PB';
            $today = date('Y-m-d');
            $employeeId = $post['md_employee_id'];

            try {
                if (!$this->validation->run($post, 'employee_allo')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = date('Y-m-d', strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $nik = $post['nik'];
                    $submissionDate = $post['submissiondate'];
                    $subDate = date('Y-m-d', strtotime($submissionDate));

                    $rule = $mRule->where([
                        'name'      => 'Perbantuan',
                        'isactive'  => 'Y'
                    ])->first();

                    // This for getting minimum date Employee Allocation
                    $ruleDetail = $mRuleDetail->where([
                        'md_rule_id' =>
                        $rule->md_rule_id,
                        'isactive' => 'Y'
                    ])->find();

                    $minDate = 0;

                    if ($ruleDetail)
                        $minDate = $ruleDetail[0]->condition;

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
                        $nextDate = lastWorkingDays($startDate, $holidays, $minDays, false, $daysOff);

                        //* last index of array from variable nextDate
                        $lastDate = end($nextDate);

                        //TODO : Get submission
                        $dateStartClause = date('Y-m-d', strtotime($startDate));

                        $whereClause = "trx_employee_allocation.nik = '{$nik}'";
                        $whereClause .= " AND DATE_FORMAT(trx_employee_allocation.startdate, '%Y-%m-%d') >= '{$dateStartClause}' AND DATE_FORMAT(trx_employee_allocation.enddate, '%Y-%m-%d') <= '{$endDate}'";
                        $whereClause .= " AND trx_employee_allocation.docstatus IN ('{$this->DOCSTATUS_Completed}','{$this->DOCSTATUS_Inprogress}')";
                        $trx = $this->model->where($whereClause)->first();

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($submissionDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        // Get DateRange From Start to End
                        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

                        if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($lastDate < $subDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan');
                        } else if ($trx) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                        } else if (count($dateRange) < $minDate) {
                            $response = message('success', false, "Tanggal perbantuan kurang dari {$minDate} hari");
                        } else {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Perbantuan, $post);
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
        $mDivision = new M_Division($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
                $rowBranchTo = $mBranch->where($mBranch->primaryKey, $list[0]->getBranchTo())->first();
                $rowDivisionTo = $mDivision->where($mDivision->primaryKey, $list[0]->getDivisionTo())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());
                $list = $this->field->setDataSelect($mBranch->table, $list, 'branch_to', $rowBranchTo->getBranchId(), $rowBranchTo->getName());
                $list = $this->field->setDataSelect($mDivision->table, $list, 'division_to', $rowDivisionTo->getDivisionId(), $rowDivisionTo->getName());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //* Need to set data into date field in form
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));
                $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));

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
        $pdf->Cell(0, 25, 'FORM TUGAS KANTOR', 0, 1, 'C');
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

    public function updateMasterEmployee()
    {
        $this->session->set([
            'sys_user_id'       => 1,
        ]);

        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));

        $where = '(startdate = CURDATE() + INTERVAL 1 DAY OR enddate = CURDATE())';
        $where .= " AND trx_employee_allocation.docstatus = 'IP'";
        $where .= " AND trx_employee_allocation.isapproved = 'Y'";
        $list = $this->model->where($where)->findAll();

        if ($list) {
            foreach ($list as $value) {
                $startdate = date('Y-m-d', strtotime($value->startdate));
                $enddate = date('Y-m-d', strtotime($value->enddate));

                if ($startdate === $tomorrow) {
                    // For Current Branch
                    $branch = $mEmpBranch->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_branch_id' => $value->md_branch_id])->first();
                    $division = $mEmpDivision->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_division_id' => $value->md_division_id])->first();

                    // This is if Branch To Exists, doesn't need insert data
                    $branchTo = $mEmpBranch->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_branch_id' => $value->branch_to])->first();
                    $divisionTo = $mEmpDivision->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_division_id' => $value->division_to])->first();

                    if ($value->md_branch_id !== $value->branch_to) {
                        if ($branch)
                            $mEmpBranch->delete($branch->md_employee_branch_id);

                        $data = [];

                        if (!$branchTo) {
                            $data = [
                                'created_by' => session()->get('sys_user_id'),
                                'updated_by' => session()->get('sys_user_id'),
                                'md_employee_id' => $value->md_employee_id,
                                'md_branch_id'   => $value->branch_to
                            ];
                            $mEmpBranch->insert($data);
                        }
                    }

                    if ($division && ($value->md_division_id !== $value->division_to)) {
                        if ($division)
                            $mEmpDivision->delete($division->md_employee_division_id);
                        $data = [];

                        if (!$divisionTo) {
                            $data = [
                                'created_by' => session()->get('sys_user_id'),
                                'updated_by' => session()->get('sys_user_id'),
                                'md_employee_id' => $value->md_employee_id,
                                'md_division_id' => $value->division_to
                            ];
                            $mEmpDivision->insert($data);
                        }
                    }
                } else if ($enddate === $today) {
                    $this->entity = new \App\Entities\EmployeeAllocation();

                    $branch = $mEmpBranch->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_branch_id' => $value->branch_to])->first();
                    $division = $mEmpDivision->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_division_id' => $value->division_to])->first();

                    $branchBef = $mEmpBranch->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_branch_id' => $value->md_branch_id])->first();
                    $divisionBef = $mEmpDivision->where([$mEmployee->primaryKey => $value->md_employee_id, 'md_division_id' => $value->md_division_id])->first();

                    if ($value->md_branch_id !== $value->branch_to) {
                        if ($branch)
                            $mEmpBranch->delete($branch->md_employee_branch_id);
                        $data = [];

                        if (!$branchBef) {
                            $data = [
                                'created_by' => session()->get('sys_user_id'),
                                'updated_by' => session()->get('sys_user_id'),
                                'md_employee_id' => $value->md_employee_id,
                                'md_branch_id'   => $value->md_branch_id
                            ];

                            $mEmpBranch->insert($data);
                        }
                    }

                    if ($value->md_division_id !== $value->division_to) {
                        if ($division)
                            $mEmpDivision->delete($division->md_employee_division_id);
                        $data = [];

                        if (!$divisionBef) {
                            $data = [
                                'created_by' => session()->get('sys_user_id'),
                                'updated_by' => session()->get('sys_user_id'),
                                'md_employee_id' => $value->md_employee_id,
                                'md_division_id' => $value->md_division_id
                            ];

                            $mEmpDivision->insert($data);
                        }
                    }

                    $this->entity->setEmployeeAllocationId($value->trx_employee_allocation_id);
                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);

                    $this->save();
                }
            }
        }
    }
}