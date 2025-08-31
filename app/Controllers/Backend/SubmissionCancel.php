<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AccessMenu;
use App\Models\M_Assignment;
use App\Models\M_Employee;
use App\Models\M_Attendance;
use App\Models\M_DocumentType;
use App\Models\M_EmpBranch;
use App\Models\M_Rule;
use App\Models\M_SubmissionCancel;
use App\Models\M_SubmissionCancelDetail;
use Config\Services;

class SubmissionCancel extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_SubmissionCancel($this->request);
        $this->modelDetail = new M_SubmissionCancelDetail($this->request);
        $this->entity = new \App\Entities\SubmissionCancel();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y'),
        ];

        return $this->template->render('transaction/submissioncancel/v_submission_cancel', $data);
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
            $sort = ['trx_submission_cancel.submissiondate' => 'DESC'];

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

            $where['trx_submission_cancel.submissiontype'] = $this->model->Pengajuan_Pembatalan;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_submission_cancel_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->ref_docno;
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
        $mAttendance = new M_Attendance($this->request);
        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mAbsent = new M_Absent($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');

            $post["submissiontype"] = $this->model->Pengajuan_Pembatalan;
            $post["necessary"] = 'PB';

            $post['ref_table'] = $post['ref_submissiontype'] == 100008 ? "trx_assignment" : "trx_absent";

            $table = json_decode($post['table']);

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                $img_name = "";

                if (!empty($post['md_employee_id'])) {
                    $row = $mEmployee->find($post['md_employee_id']);
                    $lenPos = strpos($row->getValue(), '-');
                    $value = substr_replace($row->getValue(), "", $lenPos);
                    $ymd = date('YmdHis');
                }

                if ($file && $file->isValid()) {
                    $ext = $file->getClientExtension();
                    $img_name = $this->model->Pengajuan_Pembatalan . '_' . $value . '_' . $ymd . '.' . $ext;
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'pembatalan_cuti')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $subDate = date('Y-m-d', strtotime($post['submissiondate']));

                    $rule = $mRule->where([
                        'name'      => 'Pembatalan',
                        'isactive'  => 'Y'
                    ])->first();

                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //* last index of array from variable addDays
                    $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
                    $addDays = end($addDays);

                    // Property For Loop
                    $insert = false;
                    $lastLoop = end($table);

                    foreach ($table as $key => $value) {
                        $dateClause = date('Y-m-d', strtotime($value->date));

                        // TODO : Get Cancel Submission
                        $whereClause = "trx_submission_cancel_detail.md_employee_id = '{$value->md_employee_id}'";
                        $whereClause .= " AND trx_submission_cancel_detail.date = '{$dateClause}'";
                        $whereClause .= " AND trx_submission_cancel.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                        $whereClause .= " AND trx_submission_cancel.ref_submissiontype = {$post['ref_submissiontype']}";
                        $whereClause .= " AND trx_submission_cancel.reference_id = {$post['reference_id']}";
                        $trxSubmissionCancel = $this->modelDetail->getDetail(null, $whereClause)->getRow();

                        if ($dateClause == $subDate) {
                            $empBranch = $mEmpBranch->where('md_employee_id', $value->md_employee_id)->first();
                            //TODO : Get attendance employee
                            $whereClause = "v_attendance_serialnumber.md_employee_id = '{$value->md_employee_id}'";
                            $whereClause .= " AND v_attendance_serialnumber.date = '{$dateClause}'";
                            $whereClause .= " AND md_attendance_machines.md_branch_id = {$empBranch->md_branch_id}";
                            $attPresent = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                            //TODO : Get submission Office Duties
                            $whereClause = "v_all_submission.md_employee_id = {$employeeId}";
                            $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') = '{$dateClause}'";
                            $whereClause .= " AND v_all_submission.submissiontype IN ($mAbsent->Pengajuan_Tugas_Kantor)";
                            $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                            $trx = $this->model->getAllSubmission($whereClause)->getResult();
                        }

                        $dateNow = format_dmy($value->date, '-');

                        if ($dateClause < $subDate) {
                            $response = message('success', false, "Tanggal {$dateNow} tidak bisa dibatalkan karena sudah melebihi batas tanggal pembatalan");
                            break;
                        } else if (($dateClause == $subDate) && is_null($attPresent) && is_null($trx)) {
                            $response = message('success', false, "Tidak ada kehadiran, tidak bisa mengajukan pembatalan pada tanggal : {$dateNow}");
                            break;
                        } else if ($trxSubmissionCancel) {
                            $response = message('success', false, "Tidak bisa mengajukan pembatalan untuk tanggal {$dateNow}, karena sudah ada pengajuan lain dengan nomor {$trxSubmissionCancel->documentno}");
                            break;
                        }

                        if ($value === $lastLoop)
                            $insert = true;
                    }

                    if ($insert) {
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

                            $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Pembatalan, $post, $this->session->get('sys_user_id'));
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
        $mDocType = new M_DocumentType($this->request);

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

                $model = $list[0]->ref_table === "trx_absent" ? new M_Absent($this->request) : new M_Assignment($this->request);

                $refData = $model->find($list[0]->reference_id);
                $list = $this->field->setDataSelect($model->table, $list, 'reference_id', $refData->{$model->primaryKey}, $refData->documentno);

                $refDocType = $mDocType->find($list[0]->ref_submissiontype);
                $list = $this->field->setDataSelect($mDocType->table, $list, 'ref_submissiontype', $refDocType->getDocTypeId(), $refDocType->getName());

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
        $mAbsent = new M_Absent($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $rowDetail = $this->modelDetail->where($this->model->primaryKey, $row->trx_submission_cancel_id)->findAll();
            $menu = $this->request->uri->getSegment(2);
            $today = date("Y-m-d");

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $keys = array_keys($rowDetail);
                        $lastLoop = end($keys);

                        $process = false;
                        foreach ($rowDetail as $key => $value) {
                            $dateClause = date('Y-m-d', strtotime($value->date));

                            // TODO : Get Cancel Submission
                            $whereClause = "trx_submission_cancel_detail.md_employee_id = '{$value->md_employee_id}'";
                            $whereClause .= " AND trx_submission_cancel_detail.date = '{$dateClause}'";
                            $whereClause .= " AND trx_submission_cancel.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                            $whereClause .= " AND trx_submission_cancel.ref_submissiontype = {$row->getRefSubmissionType()}";
                            $whereClause .= " AND trx_submission_cancel.reference_id = {$row->getReferenceId()}";

                            $trxSubmissionCancel = $this->modelDetail->getDetail(null, $whereClause)->getRow();

                            if ($dateClause == $today) {
                                $empBranch = $mEmpBranch->where('md_employee_id', $value->md_employee_id)->first();
                                //TODO : Get attendance employee
                                $whereClause = "v_attendance_serialnumber.md_employee_id = '{$value->md_employee_id}'";
                                $whereClause .= " AND v_attendance_serialnumber.date = '{$dateClause}'";
                                $whereClause .= " AND md_attendance_machines.md_branch_id = {$empBranch->md_branch_id}";
                                $attPresent = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                                //TODO : Get submission Office Duties
                                $whereClause = "v_all_submission.md_employee_id = {$employeeId}";
                                $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') = '{$dateClause}'";
                                $whereClause .= " AND v_all_submission.submissiontype IN ($mAbsent->Pengajuan_Tugas_Kantor)";
                                $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                                $trxOfficeDuties = $this->model->getAllSubmission($whereClause)->getResult();
                            }

                            $dateNow = format_dmy($value->date, '-');

                            if ($dateClause < $today) {
                                $response = message('error', true, "Tidak bisa proses dokumen, tanggal {$dateNow} sudah melewati batas pembatalan");
                                break;
                            } else if (($dateClause == $today) && is_null($attPresent) && is_null($trxOfficeDuties)) {
                                $response = message('error', true, "Tidak bisa proses dokumen, tanggal {$dateNow} sudah tidak ada kehadiran");
                                break;
                            } else if ($trxSubmissionCancel) {
                                $response = message('error', true, "Tidak bisa proses dokumen, sudah ada pengajuan lain dengan nomor dokumen : {$trxSubmissionCancel->documentno}");
                                break;
                            }

                            if ($key === $lastLoop)
                                $process = true;
                        }

                        if ($process) {
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
        $mEmployee = new M_Employee($this->request);

        $table = [];

        $fieldLine = new \App\Entities\Table();
        $fieldLine->setName("lineno");
        $fieldLine->setId("lineno");
        $fieldLine->setType("text");
        $fieldLine->setLength(50);
        $fieldLine->setIsReadonly(true);

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
        $fieldEmployee->setIsReadonly(true);

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
                $dataEmployee = $mEmployee->where($mEmployee->primaryKey, $row->md_employee_id)->findAll();
                $fieldEmployee->setList($dataEmployee);
                $fieldEmployee->setValue($row->md_employee_id);
                $fieldDate->setValue(format_dmy($row->date, '-'));

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDate),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $dataEmployee = $mEmployee->where($mEmployee->primaryKey, $row->md_employee_id)->findAll();
                $fieldEmployee->setList($dataEmployee);
                $fieldEmployee->setValue($row->md_employee_id);


                $fieldLine->setValue($row->lineno);
                $fieldDate->setValue(format_dmy($row->date, '-'));
                $btnDelete->setValue($row->trx_submission_cancel_detail_id);

                if ($row->isagree) {
                    $status = statusRealize($row->isagree);
                } else {
                    $status = '';
                }

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDate),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getSubmissionDetail()
    {

        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            try {
                $today = date('Y-m-d');
                $where = "v_all_submission.documentno = '{$post['document_no']}'";
                $where .= " AND v_all_submission.docstatus IN ('CO','IP')";
                $where .= " AND v_all_submission.isagree NOT IN ('{$this->LINESTATUS_Dibatalkan}', '{$this->LINESTATUS_Ditolak}', '{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Approval}')";
                $where .= " AND (v_all_submission.ref_id IS NULL OR v_all_submission.ref_id = 0)";
                $where .= " AND trx_submission_cancel_detail.trx_submission_cancel_id IS NULL";
                $where .= " AND v_all_submission.date >= '{$today}'";

                $detail = $this->model->getAllSubmission($where, true)->getResult();

                $result = [
                    'line'      => $this->tableLine(null, $detail)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getAllSubmission()
    {
        $post = $this->request->getVar();

        if ($this->request->isAJAX()) {

            try {
                $today = date('Y-m-d');
                $where = "v_all_submission.employee_id = {$post['md_employee_id']}";
                $where .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                $where .= " AND v_all_submission.submissiontype = {$post['ref_submissiontype']}";
                $where .= " AND v_all_submission.isagree NOT IN ('{$this->LINESTATUS_Dibatalkan}', '{$this->LINESTATUS_Ditolak}', '{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Approval}')";
                $where .= " AND (v_all_submission.ref_id IS NULL OR v_all_submission.ref_id = 0)";
                $where .= " AND trx_submission_cancel_detail.trx_submission_cancel_id IS NULL";
                $where .= " AND v_all_submission.date >= '{$today}'";

                $list_data = array_unique(array_map(fn($val) => [
                    'id' => $val->header_id,
                    'text' => $val->documentno
                ], $this->model->getAllSubmission($where, true)->getResult()), SORT_REGULAR);

                $response = array_values($list_data);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}