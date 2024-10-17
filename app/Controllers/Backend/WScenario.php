<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_WScenario;
use App\Models\M_WScenarioDetail;
use App\Models\M_Menu;
use App\Models\M_NotificationText;
use App\Models\M_Responsible;
use App\Models\M_Status;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_Levelling;
use Config\Services;

class WScenario extends BaseController
{
    protected $sys_wfscenario_id = 0;

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_WScenario($this->request);
        $this->modelDetail = new M_WScenarioDetail($this->request);
        $this->entity = new \App\Entities\WScenario();
    }

    public function index()
    {
        $mMenu = new M_Menu($this->request);

        $data = [
            'menu'      => $mMenu->getMenuUrl()
        ];

        return $this->template->render('backend/configuration/wscenario/v_wscenario', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->sys_wfscenario_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->lineno;
                $row[] = $value->grandtotal;
                $row[] = $value->menu;
                $row[] = $value->status;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->level;
                $row[] = $value->description;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'wscenario')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
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
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);
        $mLevel = new M_Levelling($this->request);
        $mStatus = new M_Status($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getBranchId())) {
                    $rowBranch = $mBranch->find($list[0]->getBranchId());
                    $list = $this->field->setDataSelect($mBranch->table, $list, $mBranch->primaryKey, $rowBranch->getBranchId(), $rowBranch->getName());
                }

                if (!empty($list[0]->getDivisionId())) {
                    $rowDivision = $mDiv->find($list[0]->getDivisionId());
                    $list = $this->field->setDataSelect($mDiv->table, $list, $mDiv->primaryKey, $rowDivision->getDivisionId(), $rowDivision->getName());
                }

                if (!empty($list[0]->getLevellingId())) {
                    $rowLevel = $mLevel->find($list[0]->getLevellingId());
                    $list = $this->field->setDataSelect($mLevel->table, $list, $mLevel->primaryKey, $rowLevel->getLevellingId(), $rowLevel->getName());
                }

                if (!empty($list[0]->getStatusId())) {
                    $rowStatus = $mStatus->find($list[0]->getStatusId());
                    $list = $this->field->setDataSelect($mStatus->table, $list, $mStatus->primaryKey, $rowStatus->getStatusId(), $rowStatus->getName());
                }

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->getName());
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

    public function tableLine($set = null, $detail = [])
    {
        $mResponsible = new M_Responsible($this->request);
        $mNotif = new M_NotificationText($this->request);

        $table = [];

        $fieldLineNo = new \App\Entities\Table();
        $fieldLineNo->setName("lineno");
        $fieldLineNo->setType("text");
        $fieldLineNo->setClass("number");
        $fieldLineNo->setLength(70);

        $fieldGrandTotal = new \App\Entities\Table();
        $fieldGrandTotal->setName("grandtotal");
        $fieldGrandTotal->setType("text");
        $fieldGrandTotal->setClass("number");
        $fieldGrandTotal->setLength(160);

        $fieldResponsible = new \App\Entities\Table();
        $fieldResponsible->setName("sys_wfresponsible_id");
        $fieldResponsible->setType("select");
        $fieldResponsible->setClass("select2");
        $fieldResponsible->setIsRequired(true);
        $fieldResponsible->setField([
            'id'    => 'sys_wfresponsible_id',
            'text'  => 'name'
        ]);
        $listRespon = $mResponsible->where('isactive', 'Y')
            ->orderBy('name', 'ASC')
            ->findAll();
        $fieldResponsible->setList($listRespon);
        $fieldResponsible->setLength(200);

        $fieldNotification = new \App\Entities\Table();
        $fieldNotification->setName("sys_notiftext_id");
        $fieldNotification->setType("select");
        $fieldNotification->setClass("select2");
        $fieldNotification->setIsRequired(true);
        $fieldNotification->setField([
            'id'    => 'sys_notiftext_id',
            'text'  => 'name'
        ]);
        $listNotif = $mNotif->where('isactive', 'Y')
            ->orderBy('name', 'ASC')
            ->findAll();
        $fieldNotification->setList($listNotif);
        $fieldNotification->setLength(200);

        $fieldActive = new \App\Entities\Table();
        $fieldActive->setName("isactive");
        $fieldActive->setType("checkbox");
        $fieldActive->setClass("active");
        $fieldActive->setIsChecked(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $this->field->fieldTable($fieldLineNo),
                $this->field->fieldTable($fieldGrandTotal),
                $this->field->fieldTable($fieldResponsible),
                $this->field->fieldTable($fieldNotification),
                $this->field->fieldTable($fieldActive),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldLineNo->setValue($row->getLineNo());
                $fieldGrandTotal->setValue($row->getGrandTotal());
                $fieldResponsible->setValue($row->getWfResponsibleId());
                $fieldNotification->setValue($row->getNotifTextId());
                $fieldActive->setValue($row->getIsActive());
                $btnDelete->setValue($row->getWfScenarioDetailId());

                if ($row->getIsActive() === "N") {
                    $fieldLineNo->setIsReadonly(true);
                    $fieldGrandTotal->setIsReadonly(true);
                    $fieldResponsible->setIsReadonly(true);
                    $fieldNotification->setIsReadonly(true);
                    $fieldActive->setIsChecked(false);
                } else {
                    $fieldLineNo->setIsReadonly(false);
                    $fieldGrandTotal->setIsReadonly(false);
                    $fieldResponsible->setIsReadonly(false);
                    $fieldNotification->setIsReadonly(false);
                    $fieldActive->setIsChecked(true);
                }

                $table[] = [
                    $this->field->fieldTable($fieldLineNo),
                    $this->field->fieldTable($fieldGrandTotal),
                    $this->field->fieldTable($fieldResponsible),
                    $this->field->fieldTable($fieldNotification),
                    $this->field->fieldTable($fieldActive),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function setScenario($entity, $model, $modelDetail = null, $trxID, $docStatus, $menu, $session)
    {
        $mWfs = new M_WScenario($this->request);
        $cWfa = new WActivity();
        $mDocType = new M_DocumentType($this->request);
        $mEmployee = new M_Employee($this->request);

        $this->model = $model;
        $this->entity = $entity;

        $table = $this->model->table;
        $primaryKey = $this->model->primaryKey;
        $sessionUserId = $session->get('sys_user_id');
        $isWfscenario = false;

        $trx = $this->model->find($trxID);

        if (!is_null($modelDetail)) {
            $this->modelDetail = $modelDetail;
            $trxLine = $this->modelDetail->where($primaryKey, $trxID)->findAll();
        }

        $docType = $mDocType->find($trx->submissiontype);

        if (!$trx && $docStatus === $this->DOCSTATUS_Completed) {
            $this->entity->setDocStatus($this->DOCSTATUS_Invalid);
            $this->entity->setWfScenarioId(0);
        } else if ($docStatus === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
        } else if ($trx && $docStatus === $this->DOCSTATUS_Completed) {
            $employee = $mEmployee->find($trx->md_employee_id);

            if ($table === 'trx_absent') {
                $totalDays = 0;

                if ($trx->submissiontype == $this->model->Pengajuan_Cuti) {
                    $totalDays = count($trxLine);

                    if ($totalDays <= 3)
                        $totalDays = 3; //Set GT sesuai scenario
                    else if ($totalDays > 3 && $totalDays <= 5)
                        $totalDays = 5; //Set GT sesuai scenario
                    else if ($totalDays > 5)
                        $totalDays = 6; //Set GT sesuai scenario
                }

                if ($trx->submissiontype == $this->model->Pengajuan_Pembatalan_Cuti) {
                    $trxRefLine = $this->modelDetail->where($primaryKey, $trx->reference_id)->findAll();
                    $totalDays = count($trxRefLine);

                    if ($totalDays <= 3)
                        $totalDays = 3; //Set GT sesuai scenario
                    else if ($totalDays > 3 && $totalDays <= 5)
                        $totalDays = 5; //Set GT sesuai scenario
                    else if ($totalDays > 5)
                        $totalDays = 6; //Set GT sesuai scenario
                }

                $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, $employee->md_levelling_id, null, $totalDays);
            } else {
                $this->sys_wfscenario_id = $mWfs->getScenario($menu, null, null, $trx->md_branch_id, $trx->md_division_id, null);
            }

            if ($this->sys_wfscenario_id) {
                $this->entity->setDocStatus($this->DOCSTATUS_Inprogress);
                $this->entity->setWfScenarioId($this->sys_wfscenario_id);
                $isWfscenario = true;
            } else if ($docType->getIsRealization() === "Y" && !is_null($modelDetail) && $trxLine) {
                $this->entity->setDocStatus($this->DOCSTATUS_Inprogress);
                $this->entity->setIsApproved("Y");
            } else {
                $this->entity->setDocStatus($this->DOCSTATUS_Completed);
            }
        } else if ($trx && $docStatus === $this->DOCSTATUS_Requested) {
            if ($table === 'trx_absent') {
                $this->sys_wfscenario_id = $mWfs->getScenario('request-anulir');

                if ($this->sys_wfscenario_id) {
                    $this->entity->setDocStatus($this->DOCSTATUS_Requested);
                    $this->entity->setWfScenarioId($this->sys_wfscenario_id);
                    $isWfscenario = true;
                } else {
                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                }
            }
        }

        $this->entity->setUpdatedBy($session->get('sys_user_id'));
        $this->entity->{$primaryKey} = $trxID;
        $result = $this->save();

        if ($result && $isWfscenario) {
            if ($docType->getIsApprovedLine() === "Y" && !is_null($modelDetail) && $trxLine) {
                $this->modelDetail = $modelDetail;

                $tableLine = $this->modelDetail->table;
                $primaryKey = $this->modelDetail->primaryKey;

                foreach ($trxLine as $line) {
                    $cWfa->setActivity(null, $this->sys_wfscenario_id, $this->getScenarioResponsible($this->sys_wfscenario_id), $sessionUserId, $this->DOCSTATUS_Suspended, false, null, $table, $trxID, $menu, null, $tableLine, $line->{$primaryKey});
                }
            } else {
                $cWfa->setActivity(null, $this->sys_wfscenario_id, $this->getScenarioResponsible($this->sys_wfscenario_id), $sessionUserId, $this->DOCSTATUS_Suspended, false, null, $table, $trxID, $menu);
            }
        }

        return $result;
    }

    private function getScenarioResponsible($sys_wfscenario_id)
    {
        $this->modelDetail = new M_WScenarioDetail($this->request);

        $row = $this->modelDetail->where([
            'sys_wfscenario_id'       => $sys_wfscenario_id,
            'isactive'                => 'Y'
        ])->orderBy('lineno', 'ASC')->first();

        return $row->getWfResponsibleId();
    }
}
