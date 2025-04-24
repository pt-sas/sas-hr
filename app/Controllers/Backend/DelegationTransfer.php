<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AccessMenu;
use App\Models\M_Branch;
use App\Models\M_DelegationTransfer;
use App\Models\M_DelegationTransferDetail;
use App\Models\M_Division;
use App\Models\M_EmpDelegation;
use App\Models\M_Employee;
use Config\Services;
use App\Models\M_Role;
use App\Models\M_ProxySwitching;
use App\Models\M_User;
use App\Models\M_UserRole;

class DelegationTransfer extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_DelegationTransfer($this->request);
        $this->modelDetail = new M_DelegationTransferDetail($this->request);
        $this->entity = new \App\Entities\DelegationTransfer();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/delegationtransfer/v_delegation_transfer', $data);
    }

    public function showAll()
    {
        $mEmployee = new M_Employee($this->request);
        $mAccess = new M_AccessMenu($this->request);

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

                    $where['ef.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['ef.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['ef.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['ef.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['ef.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['ef.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_delegation_transfer.submissiontype'] = $this->model->Pengajuan_Transfer_Duta;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_delegation_transfer_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->karyawan_from;
                $row[] = $value->karyawan_to;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->date, '-');
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
        $mUser = new M_User($this->request);
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $table = json_decode($post['table']);
            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = ['table' => arrTableLine($table)];

            try {
                if (!$this->validation->run($post, 'transfer_duta')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $post["submissiontype"] = $this->model->Pengajuan_Transfer_Duta;
                    $post["necessary"] = 'TD';
                    $startDate = $post['date'];
                    $today = date('Y-m-d');
                    // $user_from = $mUser->where('md_employee_id', $post['employee_from'])->first();
                    $user_to = $mUser->where('md_employee_id', $post['employee_to'])->first();

                    if ($post['employee_from'] == $post['employee_to']) {
                        $response = message('success', false, 'Duta Awal dengan Duta Tujuan tidak boleh sama');
                    } else if (!$user_to) {
                        $response = message('success', false, 'Duta Tujuan tidak memiliki akses pengguna');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Transfer_Duta, $post);
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
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmployeeFrom = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeFrom())->first();
                $rowEmployeeTo = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeTo())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, 'employee_from', $rowEmployeeFrom->getEmployeeId(), $rowEmployeeFrom->getValue());
                $list = $this->field->setDataSelect($mEmployee->table, $list, 'employee_to', $rowEmployeeTo->getEmployeeId(), $rowEmployeeTo->getValue());

                if (!empty($list[0]->getBranchId())) {
                    $rowBranch = $mBranch->find($list[0]->getBranchId());
                    $list = $this->field->setDataSelect($mBranch->table, $list, $mBranch->primaryKey, $rowBranch->getBranchId(), $rowBranch->getName());
                }

                if (!empty($list[0]->getDivisionId())) {
                    $rowDiv = $mDiv->find($list[0]->getDivisionId());
                    $list = $this->field->setDataSelect($mDiv->table, $list, $mDiv->primaryKey, $rowDiv->getDivisionId(), $rowDiv->getName());
                }

                $title = $list[0]->getDocumentNo() . "_" . $rowEmployeeFrom->getFullName();

                //* Need to set data into date field in form
                $list[0]->setDate(format_dmy($list[0]->date, "-"));

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

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $dataEmployee = $mEmployee->where('md_employee_id', $row->md_employee_id)->findAll();
                $fieldEmployee->setList($dataEmployee);
                $fieldEmployee->setValue($row->md_employee_id);

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldEmployee),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $dataEmployee = $mEmployee->where('md_employee_id', $row->md_employee_id)->findAll();
                $fieldEmployee->setList($dataEmployee);
                $fieldEmployee->setValue($row->md_employee_id);
                $fieldLine->setValue($row->lineno);
                $btnDelete->setValue($row->trx_delegation_transfer_detail_id);

                if (!empty($row->istransfered)) {
                    $status = active($row->istransfered);
                } else {
                    $status = '';
                }

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldEmployee),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }


    public function getEmployeeDelegation()
    {
        if ($this->request->isAJAX()) {
            $mUser = new M_User($this->request);
            $mEmpDelegation = new M_EmpDelegation($this->request);
            $post = $this->request->getVar();
            $ID = $post['md_employee_id'];
            $result = [];

            try {
                $user = $mUser->where('md_employee_id', $ID)->first();

                if ($user) {
                    //TODO : Get All User Role Contains W_App
                    $listEmp = $mEmpDelegation->where('sys_user_id', $user->sys_user_id)->findAll();

                    $result = [
                        'line' => $this->tableLine(null, $listEmp)
                    ];
                }

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}