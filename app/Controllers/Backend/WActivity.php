<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AlertRecipient;
use App\Models\M_Responsible;
use App\Models\M_User;
use App\Models\M_WActivity;
use App\Models\M_WEvent;
use App\Models\M_WScenarioDetail;
use App\Models\M_Menu;
use App\Models\M_Rule;
use App\Models\M_AllowanceAtt;
use App\Models\M_RuleDetail;
use Config\Services;
use Pusher\Pusher;
use Html2Text\Html2Text;
use stdClass;

class WActivity extends BaseController
{
    protected $wfScenarioId = 0;
    protected $wfResponsibleId = [];

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_WActivity($this->request);
        $this->entity = new \App\Entities\WActivity();
    }

    private function toForwardAlert($table, $record_id, $subject, $message)
    {
        $mAlert = new M_AlertRecipient($this->request);
        $cMail = new Mail();
        $alert = $mAlert->getAlertRecipient($table, $record_id);

        $result = false;

        if ($alert)
            foreach ($alert as $val) :
                if (!empty($val->email))
                    $result = $cMail->sendEmail($val->email, $subject, $message, null, "SAS HR");
            endforeach;

        return $result;
    }

    public function showActivityInfo()
    {
        $mMenu = new M_Menu($this->request);

        if ($this->request->isAjax()) {
            $data = [];
            $list = $this->model->getActivity();

            $result = [];

            if ($list) {
                foreach ($list as $value) :
                    $row = [];
                    $ID = $value->sys_wfactivity_id;
                    $record_id = $value->record_id;
                    $table = $value->table;
                    $menu = $value->menu;

                    $menuName = $mMenu->getMenuBy($menu);
                    $node = 'Approval ' . ucwords($menuName);

                    $trx = $this->model->getDataTrx($table, $record_id);

                    if ($trx)
                        $summary = ucwords($menuName) . ' ' . $trx->documentno . ': ' . $trx->usercreated_by;
                    else
                        $summary = ucwords($menuName) . ' ' . $record_id;

                    $row[] = $ID;
                    $row[] = $record_id;
                    $row[] = $table;
                    $row[] = $menu;
                    $row[] = $node;
                    $row[] = $summary;
                    $data[] = $row;
                endforeach;
            }

            $result = [
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function setActivity($sys_wfactivity_id, $sys_wfscenario_id, $sys_wfresponsible_id, $user_by, $state, $processed, $textmsg, $table, $record_id, $menu)
    {
        $mWr = new M_Responsible($this->request);
        $mWe = new M_WEvent($this->request);
        $mUser = new M_User($this->request);
        $cMail = new Mail();
        $mMenu = new M_Menu($this->request);

        $this->entity->setWfScenarioId($sys_wfscenario_id);
        $this->entity->setTable($table);
        $this->entity->setRecordId($record_id);
        $this->entity->setMenu($menu);

        $user_id = $mWr->getUserByResponsible($sys_wfresponsible_id);
        $menuName = $mMenu->getMenuBy($menu);

        if (empty($sys_wfactivity_id)) {
            $this->entity->setWfResponsibleId($sys_wfresponsible_id);
            $this->entity->setSysUserId($user_id);
            $this->entity->setState($state);
            $this->entity->setTextMsg($textmsg);
            $this->entity->setProcessed($processed);
            $this->entity->setCreatedBy($user_by);
            $this->entity->setUpdatedBy($user_by);
            $result = $this->model->save($this->entity);

            $sys_wfactivity_id = $this->model->getInsertID();
            $mWe->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);

            $resp = $mWr->find($sys_wfresponsible_id);
            $list = $mUser->detail(['sr.sys_role_id' => $resp->getRoleId()])->getResult();

            $builder = $this->getBuilder($table);
            $builder->where($this->getPrimaryKey($table), $record_id);
            $sql = $builder->get()->getRow();
            $subject = ucwords($menuName) . "_" . $sql->documentno;
            $message =  '<p>Dear Mr/Ms,</p><p><span style="letter-spacing: 0.05em;">Please approve document below.</span></p><div><br></div>';
            $message .= "-----" . " " . ucwords($menuName) . " ";

            if (isset($sql->grandtotal))
                $message .= $sql->documentno . ": Approval Amount =" . formatRupiah($sql->grandtotal);
            else
                $message .= $sql->documentno;

            $message = new Html2Text($message);
            $message = $message->getText();

            foreach ($list as $key => $user) :
                $cMail->sendEmail($user->email, $subject, $message, null, "SAS HR");
            endforeach;

            $this->toForwardAlert('sys_wfresponsible', $sys_wfresponsible_id, $subject, $message);

            $options = array(
                'cluster' => 'ap1',
                'useTLS' => true
            );
            $pusher = new Pusher(
                '8ae4540d78a7d493226a',
                '808c4eb78d03842672ca',
                '1490113',
                $options
            );

            $data['message'] = 'hello world';
            $pusher->trigger('my-channel', 'my-event', $data);
        } else {
            if (!empty($this->getNextResponsible())) {
                $newWfResponsibleId = $this->getNextResponsible();
                $user_id = $mWr->getUserByResponsible($newWfResponsibleId);

                $mWe->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, true);

                $sys_wfresponsible_id = $newWfResponsibleId;
                $user = $mUser->find($user_by);
                $resp = $mWr->find($sys_wfresponsible_id);
                $msg = 'Approved By : ' . $user->getUserName() . ' </br> ';

                $msg .= 'Next Approver : ' . $resp->getName() . ' </br> ';

                $msg .= $textmsg;
                $this->entity->setTextMsg($msg);

                if ($state === $this->DOCSTATUS_Completed && $processed) {
                    $state = $this->DOCSTATUS_Suspended;
                    $processed = false;
                    $mWe->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);
                }

                $resp = $mWr->find($sys_wfresponsible_id);
                $list = $mUser->detail(['sr.sys_role_id' => $resp->getRoleId()])->getResult();

                $builder = $this->getBuilder($table);
                $builder->where($this->getPrimaryKey($table), $record_id);
                $sql = $builder->get()->getRow();
                $subject = ucwords($menuName) . "_" . $sql->documentno;
                $message =  '<p>Dear Mr/Ms,</p><p><span style="letter-spacing: 0.05em;">Please approve document below.</span></p><div><br></div>';
                $message .= "-----" . " " . ucwords($menuName) . " ";
                $message .= $sql->documentno;
                $message = new Html2Text($message);
                $message = $message->getText();

                foreach ($list as $key => $user) :
                    $cMail->sendEmail($user->email, $subject, $message, null, "SAS HR");
                endforeach;

                $this->toForwardAlert('sys_wfresponsible', $sys_wfresponsible_id, $subject, $message);
            } else {
                $builder = $this->model->db->table($table);

                if ($state === $this->DOCSTATUS_Aborted && $processed) {
                    $mWe->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);

                    $data = [
                        'docstatus' => $this->DOCSTATUS_NotApproved
                    ];

                    $builder->where($this->getPrimaryKey($table), $record_id)->update($data);
                } else {
                    $state = $this->DOCSTATUS_Completed;
                    $processed = true;
                    $mWe->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);

                    $data = [
                        'docstatus' => $state
                    ];

                    $builder->where($this->getPrimaryKey($table), $record_id)->update($data);

                    $builder = $this->getBuilder($table);
                    $builder->where($this->getPrimaryKey($table), $record_id);
                    $sql = $builder->get()->getRow();
                    $subject = ucwords($menuName) . "_" . $sql->documentno;
                    $message =  'Sudah Di Approve' . "<br>";
                    $message .= "---" . "<br>";
                    $message .= ucwords($menuName) . " " . $sql->documentno . "<br>";
                    $message = new Html2Text($message);
                    $message = $message->getText();

                    $user = $mUser->find($sql->created_by);
                    $cMail->sendEmail($user->email, $subject, $message, null, "SAS HR");

                    $this->toForwardAlert('sys_wfresponsible', $sys_wfresponsible_id, $subject, $message);
                }
            }

            $this->entity->setWfResponsibleId($sys_wfresponsible_id);
            $this->entity->setSysUserId($user_id);
            $this->entity->setState($state);
            $this->entity->setProcessed($processed);
            $this->entity->setUpdatedBy($user_by);
            $this->entity->setWfActivityId($sys_wfactivity_id);
            $result = $this->model->save($this->entity);

            if ($table === "trx_absent") {
                $mRule = new M_Rule($this->request);
                $mRuleDetail = new M_RuleDetail($this->request);
                $mAllowance = new M_AllowanceAtt($this->request);

                $amount = 0;

                if ($sql->docstatus === $this->DOCSTATUS_Completed) {
                    $data = [
                        'receiveddate' => date('Y-m-d')
                    ];

                    $builder->where($this->getPrimaryKey($table), $record_id)->update($data);

                    $builder = $this->getBuilder($table);
                    $builder->where($this->getPrimaryKey($table), $record_id);
                    $sql = $builder->get()->getRow();

                    if ($sql->submissiontype === "sakit") {
                        $_Rule = $mRule->where(['name' => 'Sakit', 'isactive' => 'Y'])->first();

                        if ($_Rule->condition === "")
                            $amount = abs($_Rule->value);

                        $range = getDatesFromRange($sql->startdate, $sql->enddate);

                        $arr = [];

                        if ($amount != 0) {
                            foreach ($range as $date) {
                                $arr[] = [
                                    "record_id"         => $record_id,
                                    "table"             => $table,
                                    "submissiontype"    => $sql->submissiontype,
                                    "submissiondate"    => $date,
                                    "md_employee_id"    => $sql->md_employee_id,
                                    "amount"            => $amount,
                                    "created_by"        => $user_by,
                                    "updated_by"        => $user_by,
                                ];
                            }

                            $mAllowance->builder->insertBatch($arr);
                        }
                    }

                    if ($sql->submissiontype === "lupa absen masuk") {
                        $_Rule = $mRule->where('name', 'Lupa Absen')->first();
                        $_RuleDetail = $mRuleDetail->where(['md_rule_id' => $_Rule->md_rule_id, 'name' => 'Lupa Absen Masuk'])->first();
                        $amount = abs($_RuleDetail->value);

                        if ($amount != 0) {
                            $arr[] = [
                                "record_id"         => $record_id,
                                "table"             => $table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $sql->startdate,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $user_by,
                                "updated_by"        => $user_by,
                            ];

                            $mAllowance->builder->insertBatch($arr);
                        }
                    }

                    if ($sql->submissiontype === "lupa absen pulang") {
                        $_Rule = $mRule->where('name', 'Lupa Absen')->find();
                        $_RuleDetail = $mRuleDetail->where(['md_rule_id' => $_Rule[0]->md_rule_id, 'name' => 'Lupa Absen Pulang'])->find();
                        $amount = abs($_RuleDetail->value);

                        if ($amount != 0) {
                            $arr[] = [
                                "record_id"         => $record_id,
                                "table"             => $table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $sql->startdate,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $user_by,
                                "updated_by"        => $user_by,
                            ];

                            $mAllowance->builder->insertBatch($arr);
                        }
                    }

                    if ($sql->submissiontype === "datang terlambat") {
                        $_Rule = $mRule->where('name', 'Terlambat')->first();
                        $_RuleDetail = $mRuleDetail->where('md_rule_id', $_Rule->md_rule_id)->findAll();

                        $jamMasuk = convertToMinutes(format_time('08:00'));
                        $pagi = ($jamMasuk + $_RuleDetail[0]->condition);
                        $siang = ($jamMasuk + $_RuleDetail[1]->condition);
                        $jam = convertToMinutes(format_time($sql->startdate));

                        if ($_Rule->isdetail === 'Y') {
                            if (getOperationResult($jam, $siang, $_RuleDetail[1]->operation) === true) {
                                $amount = abs($_RuleDetail[1]->value);
                            } else if (getOperationResult($jam, $pagi, $_RuleDetail[0]->operation) === true) {
                                $amount = abs($_RuleDetail[0]->value);
                            }
                        }

                        if ($amount != 0) {
                            $arr[] = [
                                "record_id"         => $record_id,
                                "table"             => $table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $sql->startdate,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $user_by,
                                "updated_by"        => $user_by,
                            ];

                            $mAllowance->builder->insertBatch($arr);
                        }
                    }

                    if ($sql->submissiontype === "pulang cepat") {
                        $_Rule = $mRule->where('name', 'Pulang Cepat')->find();
                        $_RuleDetail = $mRuleDetail->where('md_rule_id = ' . $_Rule[0]->md_rule_id)->find();
                        $jamMasuk = convertToMinutes(format_time('08:00'));
                        $sore = ($jamMasuk + $_RuleDetail[0]->condition);
                        $siang = ($jamMasuk + $_RuleDetail[1]->condition);
                        $jam = convertToMinutes(format_time($sql->startdate));

                        if ($_Rule[0]->isdetail === 'Y') {
                            if (getOperationResult($jam, $jamMasuk, $_RuleDetail[0]->operation) === true) {
                                $amount = 0;
                            } else if (getOperationResult($jam, $siang, $_RuleDetail[1]->operation) === true) {
                                $amount = abs($_RuleDetail[1]->value);
                            } else if (getOperationResult($jam, $sore, $_RuleDetail[0]->operation) === true) {
                                $amount = abs($_RuleDetail[0]->value);
                            }
                        }

                        if ($amount != 0) {
                            $arr[] = [
                                "record_id"         => $record_id,
                                "table"             => $table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $sql->startdate,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $user_by,
                                "updated_by"        => $user_by,
                            ];

                            $mAllowance->builder->insertBatch($arr);
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function create()
    {
        $mWe = new M_WEvent($this->request);
        $mUser = new M_User($this->request);
        $cMail = new Mail();
        $mMenu = new M_Menu($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $isAnswer = $post['isanswer'];
            $_ID = $post['record_id'];
            $txtMsg = $post['textmsg'];

            try {
                $activity = $this->model->find($_ID);
                $menuName = $mMenu->getMenuBy($activity->getMenu());

                if ($isAnswer === 'Y') {
                    $eList = $mWe->where($this->model->primaryKey, $_ID)->orderBy('created_at', 'ASC')->findAll();

                    foreach ($eList as $event) :
                        $this->wfResponsibleId[] = $event->getWfResponsibleId();
                    endforeach;

                    $this->wfScenarioId = $activity->getWfScenarioId();

                    $response = $this->setActivity($_ID, $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->access->getSessionUser(), $this->DOCSTATUS_Completed, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu());
                } else {
                    $response = $this->setActivity($_ID, $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->access->getSessionUser(), $this->DOCSTATUS_Aborted, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu());

                    $builder = $this->getBuilder($activity->getTable());
                    $builder->where($this->getPrimaryKey($activity->getTable()), $activity->getRecordId());
                    $sql = $builder->get()->getRow();
                    $subject = ucwords($menuName) . "_" . $sql->documentno;
                    $message =  'Tidak Di Approve' . "<br>";
                    $message .= "---" . "<br>";
                    $message .= ucwords($menuName) . " " . $sql->documentno . "<br>";
                    $message = new Html2Text($message);
                    $message = $message->getText();

                    $user = $mUser->find($sql->created_by);
                    $cMail->sendEmail($user->email, $subject, $message, null, "SAS HR");
                    $this->toForwardAlert('sys_wfresponsible', $activity->getWfResponsibleId(), $subject, $message);
                }

                $options = array(
                    'cluster' => 'ap1',
                    'useTLS' => true
                );
                $pusher = new Pusher(
                    '8ae4540d78a7d493226a',
                    '808c4eb78d03842672ca',
                    '1490113',
                    $options
                );

                $data['message'] = 'hello world';
                $pusher->trigger('my-channel', 'my-event', $data);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return json_encode($response);
        }
    }

    private function getNextResponsible()
    {
        $mWfsD = new M_WScenarioDetail($this->request);
        $nextResp = 0;
        $responsible = [];

        $list = $mWfsD->where([
            'sys_wfscenario_id'       => $this->wfScenarioId,
            'isactive'                => 'Y'
        ])->orderBy('lineno', 'DESC')->findAll();

        foreach ($list as $resp) :
            if (!in_array($resp->getWfResponsibleId(), $this->wfResponsibleId))
                $responsible[] = $resp->getWfResponsibleId();
        endforeach;

        if (!empty($responsible))
            $nextResp = end($responsible);

        return $nextResp;
    }

    public function showNotif()
    {
        $list = $this->model->getActivity("count");
        return json_encode($list);
    }

    public function getBuilder($table)
    {
        return $this->model->db->table($table);
    }

    public function getPrimaryKey($table)
    {
        $fields = $this->model->db->getFieldData($table);

        $field = "";

        foreach ($fields as $row) :
            if ($row->primary_key == 1)
                $field = $row->name;
        endforeach;

        return $field;
    }
}
