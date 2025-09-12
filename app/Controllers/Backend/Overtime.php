<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AbsentDetail;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use App\Models\M_Division;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_RuleDetail;
use TCPDF;
use Config\Services;

class Overtime extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Overtime($this->request);
        $this->modelDetail = new M_OvertimeDetail($this->request);
        $this->entity = new \App\Entities\Overtime();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/overtime/v_overtime', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

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

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_overtime_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-');
                $row[] = $value->description;
                $row[] = docStatus($value->docstatus);
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
        $mHoliday = new M_Holiday($this->request);
        $mRule = new M_Rule($this->request);
        $mEmployee = new M_Employee($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);

            $post['submissiontype'] = $this->model->Pengajuan_Lembur;
            $post["necessary"] = 'LB';

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                $today = date('Y-m-d');
                $time = date('H:i');
                $employeeId = $post['md_employee_id'];
                $day = date('w', strtotime($post['startdate']));

                if (!$this->validation->run($post, 'lembur')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    // $holidays = $mHoliday->getHolidayDate();
                    $startDate = date('Y-m-d', strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $subDate = date('Y-m-d', strtotime($post['submissiondate']));

                    $rule = $mRule->where([
                        'name'      => 'Lembur',
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
                        // $whereClause = "md_work_detail.isactive = 'Y'";
                        // $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                        // $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        // $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        // $daysOff = getDaysOff($workDetail);

                        //* last index of array from variable nextDate
                        $nextDate = lastWorkingDays($startDate, [], $minDays, false, [], true);
                        $lastDate = end($nextDate);

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        $operation = null;
                        $submissionMaxTime = null;

                        if ($rule) {
                            $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                            if ($ruleDetail) {
                                foreach ($ruleDetail as $detail) {
                                    if ($detail->name === "Batas Waktu Pengajuan") {
                                        $operation = $detail->operation;
                                        $submissionMaxTime = $detail->condition;
                                    }
                                }
                            }
                        }

                        $arrEmpId = array_map(function ($value) {
                            return $value->md_employee_id;
                        }, $table);

                        // $empWork = $mEmployee
                        //     ->whereIn("md_employee_id", $arrEmpId)
                        //     ->where("NOT EXISTS (SELECT 1 
                        //                         FROM md_employee_work mew
                        //                         WHERE mew.md_employee_id = {$mEmployee->table}.md_employee_id
                        //                         AND (date_format(validto, '%Y-%m-%d') >= '{$startDate}' AND date_format(validfrom, '%Y-%m-%d') <= '{$endDate}')
                        //                         AND (SELECT mwd.md_day_id
                        //                             FROM md_work_detail mwd
                        //                             WHERE mwd.md_work_id = mew.md_work_id
                        //                             AND mwd.md_day_id = {$day}))")
                        //     ->findAll();

                        //TODO : Get submission one day
                        $whereClause = "md_employee_id IN (" . implode(" ,", $arrEmpId) . ")";
                        $whereClause .= " AND DATE_FORMAT(startdate, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                        $trx = $this->modelDetail->where($whereClause)->first();

                        if ($trx) {
                            $response = message('success', false, "Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain");
                        } else if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($lastDate < $subDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan');
                        } else if (!is_null($operation) && !is_null($submissionMaxTime) && $today == $startDate && !getOperationResult($time, $submissionMaxTime, $operation)) {
                            $response = message('success', false, 'Sudah melewati batas waktu pengajuan');
                            // } else if ($empWork) {
                            //     $value = implode(", ", array_map(function ($row) {
                            //         return $row->value;
                            //     }, $empWork));

                            //     $response = message('success', false, "Karyawan tidak terdaftar dalam hari kerja : [{$value}]");
                        } else {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Lembur, $post);
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
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(["datestart", "dateend"]);
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
            $line = $this->modelDetail->where($this->model->primaryKey, $_ID)->find();
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $startDate = date('Y-m-d', strtotime($row->startdate));
                        $endDate = date('Y-m-d', strtotime($row->enddate));

                        $arrEmpId = array_map(function ($value) {
                            return $value->md_employee_id;
                        }, $line);

                        //TODO : Get submission one day
                        $whereClause = "md_employee_id IN (" . implode(" ,", $arrEmpId) . ")";
                        $whereClause .= " AND DATE_FORMAT(startdate, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                        $trx = $this->modelDetail->where($whereClause)->first();

                        if ($trx) {
                            $response = message('error', true, 'Tidak bisa proses pengajuan, karena sudah ada pengajuan lain');
                        } else if (empty($line)) {
                            $response = message('error', true, 'Line Kosong');
                        } else {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session, null, true);

                            $response = message('success', true, true);
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
        $employee = new M_Employee($this->request);

        $post = $this->request->getPost();

        $table = [];

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

        $fieldDateEndRealization = new \App\Entities\Table();
        $fieldDateEndRealization->setName("dateend_realization");
        $fieldDateEndRealization->setType("text");
        $fieldDateEndRealization->setLength(200);
        $fieldDateEndRealization->setIsReadonly(true);

        $fieldStartTime = new \App\Entities\Table();
        $fieldStartTime->setName("starttime");
        $fieldStartTime->setId("starttime");
        $fieldStartTime->setIsRequired(true);
        $fieldStartTime->setType("text");
        $fieldStartTime->setClass("timepicker");
        $fieldStartTime->setLength(100);

        $fieldEndTime = new \App\Entities\Table();
        $fieldEndTime->setName("endtime");
        $fieldEndTime->setId("endtime");
        $fieldEndTime->setIsRequired(true);
        $fieldEndTime->setType("text");
        $fieldEndTime->setClass("timepicker");
        $fieldEndTime->setLength(100);

        $fieldBalance = new \App\Entities\Table();
        $fieldBalance->setName("overtime_balance");
        $fieldBalance->setType("text");
        $fieldBalance->setLength(100);
        $fieldBalance->setIsReadonly(true);

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
            if (!$this->validation->run($post, 'lemburAddRow')) {
                $table = $this->field->errorValidation($this->model->table, $post);
            } else {

                if ($post['md_branch_id'] !== null || $post['md_division_id'] !== null) {
                    $empId = $post['md_employee_id'];

                    $whereClause = "md_employee.isactive = 'Y'";
                    $whereClause .= " AND md_benefit_detail.benefit = 'LEMBUR'";
                    $whereClause .= " AND md_benefit_detail.status = 'Y'";
                    $whereClause .= " AND md_employee_branch.md_branch_id = {$post['md_branch_id']}";
                    $whereClause .= " AND (superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId)";
                    $whereClause .= " AND md_employee.md_status_id <> {$this->Status_RESIGN}";

                    if (!empty($post['md_supplier_id']))
                        $whereClause .= " AND md_employee.md_supplier_id = {$post['md_supplier_id']}";
                    else
                        $whereClause .= " AND md_employee.md_status_id <> {$this->Status_OUTSOURCING}";

                    $dataEmployee = $employee->getEmployee($whereClause);

                    $fieldEmployee->setList($dataEmployee);
                }

                $table = [
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldEndTime),
                    $this->field->fieldTable($fieldDateEndRealization),
                    $this->field->fieldTable($fieldBalance),
                    $this->field->fieldTable($fieldDesctiprion),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            }
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getOvertimeId();
                $header = $this->model->where('trx_overtime_id', $id)->first();
                $empId = $header->md_employee_id;

                $whereClause = "md_employee.isactive = 'Y'";
                $whereClause .= " AND md_benefit_detail.benefit = 'LEMBUR'";
                $whereClause .= " AND md_benefit_detail.status = 'Y'";
                $whereClause .= " AND md_employee_branch.md_branch_id = {$header->md_branch_id}";
                $whereClause .= " AND (superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                $whereClause .= " OR md_employee.superior_id = $empId)";
                $whereClause .= " AND md_employee.md_status_id <> {$this->Status_RESIGN}";

                if (!empty($header->md_supplier_id))
                    $whereClause .= " AND md_employee.md_supplier_id = {$header->md_supplier_id}";
                else
                    $whereClause .= " AND md_employee.md_status_id <> {$this->Status_OUTSOURCING}";

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $fieldEmployee->setValue($row->getEmployeeId());
                $fieldStartTime->setValue(format_time($row->getStartDate()));
                $fieldEndTime->setValue(format_time($row->getEndDate()));

                if ($row->getEndDateRealization() != "0000-00-00 00:00:00")
                    $fieldDateEndRealization->setValue(format_dmy($row->getEndDateRealization(), '-') . " " . format_time($row->getEndDateRealization()));

                $fieldDesctiprion->setValue($row->getDescription());
                $fieldBalance->setValue($row->getOvertimeBalance());
                $btnDelete->setValue($row->getOvertimeDetailId());

                if (
                    $header->docstatus === $this->DOCSTATUS_Inprogress ||
                    $header->docstatus === $this->DOCSTATUS_Completed
                )
                    $status = statusRealize($row->isagree);
                else
                    $status = '';

                $table[] = [
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldEndTime),
                    $this->field->fieldTable($fieldDateEndRealization),
                    $this->field->fieldTable($fieldBalance),
                    $this->field->fieldTable($fieldDesctiprion),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function exportPDF($id)
    {
        $mEmployee = new M_Employee($this->request);
        $mDivision = new M_Division($this->request);

        $list = $this->model->find($id);
        $mOvertimeDetail = new M_OvertimeDetail($this->request);
        $employee = $mEmployee->where($mEmployee->primaryKey, $list->md_employee_id)->first();
        $division = $mDivision->where($mDivision->primaryKey, $list->md_division_id)->first();
        $tglpenerimaan = '';

        if ($list->receiveddate !== null) {
            $tglpenerimaan = format_dmy($list->receiveddate, '-');
        };

        //bagian PF
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
        $pdf->setFont('helvetica', 'B', 20);
        $pdf->Cell(0, 25, 'SURAT PERINTAH LEMBUR', 0, 1, 'C');
        $pdf->setFont('helvetica', '', 12);
        //Ini untuk bagian field nama dan tanggal pengajuan
        $pdf->Ln(2);
        //Ini untuk bagian field divisi dan Tanggal diterima
        $pdf->Cell(30, 0, 'Divisi ', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . $division->name, 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, 'Tanggal Diterima', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(30, 0, ': ' . $tglpenerimaan, 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini bagian tanggal ijin dan jam
        $pdf->Cell(30, 0, 'Tanggal', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, ': ' . format_dmy($list->startdate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini bagian Alasan
        $pdf->Cell(30, 0, 'Alasan', 0, 0, 'L');
        $pdf->Cell(3, 0, ':', 0, 0, 'L');
        $pdf->MultiCell(0, 20, $list->description, 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(2);

        $header = ['No', 'Karyawan', 'Keterangan', 'Jam Mulai', 'Jam Selesai'];

        $detail = $mOvertimeDetail->where('trx_overtime_id', $list->trx_overtime_id)->find();

        // Colors, line width and bold font
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('', 'B');
        // Header
        $w = array(10, 50, 70, 30, 30);
        $num_headers = count($header);
        for ($i = 0; $i < $num_headers; ++$i) {
            $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        // Color and font restoration
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');


        // Data table line
        $number = 1;
        foreach ($detail as $row) {
            $employeeDetail = $mEmployee->where('md_employee_id', $row->md_employee_id)->first();

            $pdf->Cell($w[0], 6, $number, 1, 0, 'C');
            $pdf->Cell($w[1], 6, $employeeDetail->value, 1, 0, 'L');
            $pdf->Cell($w[2], 6, $row->description, 1, 0, 'L', false, '', 1);
            $pdf->Cell($w[3], 6, format_time($row->startdate), 1, 0, 'C');
            $pdf->Cell($w[4], 6, format_time($row->enddate), 1, 0, 'C');
            $pdf->Ln();
            $number++;
        }
        // $pdf->Cell(array_sum($w), 0, '', 'T');

        //Bagian ttd
        $pdf->Ln(10);
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(63, 0, 'Dibuat oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Disetujui oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Diketahui oleh,', 0, 0, 'C');
        $pdf->Ln(25);
        $pdf->Cell(63, 0, $employee->fullname, 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 1, 'C');
        // $pdf->Cell(63, 0, 'Karyawan Ybs', 0, 0, 'C');
        // $pdf->Cell(63, 0, 'Mgr. Dept. Ybs', 0, 0, 'C');
        // $pdf->Cell(63, 0, 'HRD', 0, 0, 'C');

        $this->response->setContentType('application/pdf');
        $pdf->Output('detail-laporan,pdf', 'I');
    }
}