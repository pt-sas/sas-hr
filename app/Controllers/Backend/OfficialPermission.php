<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Employee;
use App\Models\M_LeaveType;
use App\Models\M_AccessMenu;
use App\Models\M_Holiday;

class OfficialPermission extends BaseController
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
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/officialpermission/v_official_permission', $data);
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

            $where['trx_absent.submissiontype'] = $this->model->Pengajuan_Ijin_Resmi;

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

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');

            $post["submissiontype"] = $this->model->Pengajuan_Ijin_Resmi;
            $post["necessary"] = 'IR';

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

                if (!$this->validation->run($post, 'absent')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                    $this->entity->fill($post);

                    if ($this->isNew()) {
                        uploadFile($file, $path, $img_name);

                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Ijin_Resmi, $post, $this->session->get('sys_user_id'));
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
                        $line = $this->modelDetail->where($this->model->primaryKey, $_ID)->find();

                        if (is_null($line)) {
                            // TODO : Create Line if not exist
                            $data = [
                                'id'        => $_ID,
                                'created_by' => $this->access->getSessionUser(),
                                'updated_by' => $this->access->getSessionUser()
                            ];

                            $this->model->createAbsentDetail($data, $row);
                        } else {
                            //TODO : Update line if line exist
                            foreach ($line as $row) :
                                $entity = new \App\Entities\AbsentDetail();

                                $entity->trx_absent_detail_id = $row->trx_absent_detail_id;
                                $entity->isagree = 'H';

                                $this->modelDetail->save($entity);
                            endforeach;
                        }

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
}