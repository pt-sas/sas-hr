<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_ProxySpecial;
use App\Models\M_ProxySpecialDetail;
use App\Models\M_Role;
use App\Models\M_ProxySwitching;
use App\Models\M_User;
use App\Models\M_UserRole;

class ProxySpecial extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_ProxySpecial($this->request);
        $this->modelDetail = new M_ProxySpecialDetail($this->request);
        $this->entity = new \App\Entities\ProxySpecial();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/proxyspecial/v_proxy_special', $data);
    }

    public function showAll()
    {

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = ['trx_proxy_special.submissiondate' => 'DESC'];

            $where['trx_proxy_special.submissiontype'] = $this->model->Pengajuan_Proxy_Khusus;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_proxy_special_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->user_from;
                $row[] = $value->user_to;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = $value->ispermanent === "Y" ? format_dmy($value->startdate, '-') : format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
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
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $table = json_decode($post['table']);
            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = ['table' => arrTableLine($table)];

            try {
                if (!$this->validation->run($post, 'proxy_special')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $post["submissiontype"] = $this->model->Pengajuan_Proxy_Khusus;
                    $post["necessary"] = 'PK';
                    $startDate = $post['startdate'];
                    $today = date('Y-m-d');

                    if ($today > $startDate) {
                        $response = message('success', false, 'Tanggal Mulai kurang dari tanggal hari ini');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Proxy_Khusus, $post);
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
        $mUser = new M_User($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowUserFrom = $mUser->where($mUser->primaryKey, $list[0]->getUserFrom())->first();
                $rowUserTo = $mUser->where($mUser->primaryKey, $list[0]->getUserTo())->first();

                $list = $this->field->setDataSelect($mUser->table, $list, 'sys_user_from', $rowUserFrom->getUserId(), $rowUserFrom->getName());
                $list = $this->field->setDataSelect($mUser->table, $list, 'sys_user_to', $rowUserTo->getUserId(), $rowUserTo->getName());

                $title = $list[0]->getDocumentNo() . "_" . $rowUserFrom->getName();

                //* Need to set data into date field in form
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));

                if (!empty($list[0]->getEndDate()) && $list[0]->getEndDate() != "0000-00-00 00:00:00") {
                    $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));
                }

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
        $mRole = new M_Role($this->request);
        $mProxySwitch = new M_ProxySwitching($this->request);
        $table = [];

        $fieldLine = new \App\Entities\Table();
        $fieldLine->setName("lineno");
        $fieldLine->setId("lineno");
        $fieldLine->setType("text");
        $fieldLine->setLength(50);
        $fieldLine->setIsReadonly(true);

        $fieldRole = new \App\Entities\Table();
        $fieldRole->setName("sys_role_id");
        $fieldRole->setIsRequired(true);
        $fieldRole->setType("select");
        $fieldRole->setClass("select2");
        $fieldRole->setField([
            'id'    => 'sys_role_id',
            'text'  => 'name'
        ]);
        $fieldRole->setLength(200);
        $fieldRole->setIsReadonly(true);

        $dataRole = $mRole->like('name', 'W_App%')->findAll();
        $fieldRole->setList($dataRole);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldRole->setValue($row->role);

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldRole),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldRole->setValue($row->sys_role_id);
                $fieldLine->setValue($row->lineno);
                $btnDelete->setValue($row->trx_proxy_special_detail_id);

                $proxySwitch = $mProxySwitch->where($this->modelDetail->primaryKey, $row->trx_proxy_special_detail_id)->first();

                if ($proxySwitch) {
                    $status = statusTransfered($proxySwitch->state);
                } else {
                    $status = '';
                }

                $table[] = [
                    $this->field->fieldTable($fieldLine),
                    $this->field->fieldTable($fieldRole),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }


    public function getUserRole()
    {
        if ($this->request->isAJAX()) {
            $mUser = new M_User($this->request);
            $post = $this->request->getVar();
            $ID = $post['sys_user_id'];
            $result = [];

            try {
                //TODO : Get All User Role Contains W_App
                $where = "sys_user.sys_user_id = {$ID}";
                $where .= " AND sr.name LIKE 'W_App%'";
                $userRole = $mUser->detail([], null, $where)->getResult();

                $result = [
                    'line' => $this->tableLine(null, $userRole)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function proxySwitching()
    {
        $mUserRole = new M_UserRole($this->request);
        $mProxySwitch = new M_ProxySwitching($this->request);
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime("+1 day"));

        $this->session->set([
            'sys_user_id' => 100000
        ]);
        $user_by = session()->get('sys_user_id');

        //TODO : Running Proxy Switching from Special Proxy
        $where = "DATE_FORMAT(startdate, '%Y-%m-%d') = '{$tomorrow}' AND docstatus = '{$this->DOCSTATUS_Completed}'";
        $listDocNo = $this->model->where($where)->findAll();

        if ($listDocNo) {
            foreach ($listDocNo as $value) {
                $line = $this->modelDetail->where('trx_proxy_special_id', $value->trx_proxy_special_id)->findAll();
                foreach ($line as $val) {
                    $mProxySwitch->insertProxy($value->sys_user_from, $value->sys_user_to, $val->sys_role_id, true, $val->trx_proxy_special_detail_id, $value->ispermanent);
                }
            }
        }

        //TODO : Get All In Progress Reguler Proxy dan Return it back to original user
        $where = "(trx_proxy_switching.proxytype = 'reguler' OR (trx_proxy_switching.proxytype = 'special' AND ps.ispermanent = 'N' AND DATE_FORMAT(ps.enddate, '%Y-%m-%d') = '{$today}')) AND trx_proxy_switching.state = 'IP'";
        $listProxy = $mProxySwitch->getProxyDetail($where)->getResult();

        if ($listProxy) {
            foreach ($listProxy as $value) {
                $userRole = $mUserRole->where(['sys_user_id' => $value->sys_user_to, 'sys_role_id' => $value->sys_role_id])->first();
                if ($userRole) {
                    //TODO : Update Proxy Switching to Complete
                    $entity = new \App\Entities\ProxySwitching();
                    $entity->setProxySwitchingId($value->trx_proxy_switching_id);
                    $entity->setEndDate(date('Y-m-d H:i:s'));
                    $entity->setState($this->DOCSTATUS_Completed);
                    $entity->setUpdatedBy($user_by);

                    //TODO : Delete User Role
                    if ($mProxySwitch->save($entity)) {
                        $mUserRole->delete($userRole->sys_user_role_id);
                    }
                }
            }
        }
    }
}