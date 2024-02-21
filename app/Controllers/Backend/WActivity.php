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
use App\Models\M_Datatable;
use App\Models\M_NotificationText;
use Config\Services;
use Pusher\Pusher;
use Html2Text\Html2Text;

class WActivity extends BaseController
{
    protected $wfScenarioId = 0;
    protected $wfResponsibleId = [];

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_WActivity($this->request);
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
        $mWResponsible = new M_Responsible($this->request);
        $mWEvent = new M_WEvent($this->request);
        $mUser = new M_User($this->request);
        $mMenu = new M_Menu($this->request);
        $mWfsD = new M_WScenarioDetail($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $cMail = new Mail();

        $modelAct = new M_WActivity($this->request);
        $entityAct = new \App\Entities\WActivity();

        $this->model = new M_Datatable($this->request);
        $this->entity = new \App\Entities\DataTable();

        try {
            $modelAct->db->transBegin();

            $entityAct->setWfScenarioId($sys_wfscenario_id);
            $entityAct->setTable($table);
            $entityAct->setRecordId($record_id);
            $entityAct->setMenu($menu);

            $user_id = $mWResponsible->getUserByResponsible($sys_wfresponsible_id);
            $menuName = $mMenu->getMenuBy($menu);

            //TODO : Get data responsible 
            $dataResp = $mWResponsible->find($sys_wfresponsible_id);

            //TODO : Get data user based on role from responsible 
            $dataUser = $mUser->detail(['sr.sys_role_id' => $dataResp->getRoleId()])->getResult();

            //TODO : Get data scenario line 
            $dataScenLine = $mWfsD->where([
                'sys_wfscenario_id'       => $sys_wfscenario_id,
                'sys_wfresponsible_id'    => $sys_wfresponsible_id,
                'isactive'                => 'Y'
            ])->orderBy('lineno', 'DESC')->first();

            //TODO : Get data Notification Text Template
            $dataNotif = $mNotifText->find($dataScenLine->getNotifTextId());

            //TODO : Initialize modeling data table 
            $trxModel = $this->model->initDataTable($table);

            //TODO : Call data transaction
            $trx = $trxModel->where($trxModel->primaryKey, $record_id)->first();

            //* Get data text from notification text template 
            $message = $dataNotif->getText();

            if (empty($sys_wfactivity_id)) {
                $entityAct->setWfResponsibleId($sys_wfresponsible_id);
                $entityAct->setSysUserId($user_id);
                $entityAct->setState($state);
                $entityAct->setTextMsg($textmsg);
                $entityAct->setProcessed($processed);
                $entityAct->setCreatedBy($user_by);
                $entityAct->setUpdatedBy($user_by);
                $result = $modelAct->save($entityAct);

                $sys_wfactivity_id = $modelAct->getInsertID();
                $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);
            } else {
                if (!empty($this->getNextResponsible())) {
                    $newWfResponsibleId = $this->getNextResponsible();
                    $user_id = $mWResponsible->getUserByResponsible($newWfResponsibleId);

                    //TODO : Update event audit the previous data
                    $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, true);

                    //TODO : Set data sys_wfresponsible_id the next responsible
                    $sys_wfresponsible_id = $newWfResponsibleId;

                    //TODO : Get data user based on session user
                    $dataUser = $mUser->where($mUser->primaryKey, $user_by)->findAll();

                    //TODO : Get data responsible 
                    $dataResp = $mWResponsible->find($sys_wfresponsible_id);

                    $msg = 'Approved By : ' . $dataUser[0]->getUserName() . ' </br> ';
                    $msg .= 'Next Approver : ' . $dataResp->getName() . ' </br> ';
                    $msg .= $textmsg;

                    $entityAct->setTextMsg($msg);

                    if ($state === $this->DOCSTATUS_Completed && $processed) {
                        $state = $this->DOCSTATUS_Suspended;
                        $processed = false;

                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);
                    }
                } else {
                    if ($state === $this->DOCSTATUS_Aborted && $processed) {
                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);

                        $this->entity->docstatus = $this->DOCSTATUS_NotApproved;
                        $this->entity->isapproved = "N";
                        $this->entity->{$this->model->primaryKey} = $record_id;
                        $this->save();

                        //TODO : Get data Notification Not Approved Text Template
                        $dataNotif = $mNotifText->find($this->Notif_NotApproved);
                    } else {
                        $state = $this->DOCSTATUS_Completed;
                        $processed = true;

                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by);

                        $this->entity->docstatus = $state;
                        $this->entity->isapproved = "Y";
                        $this->entity->receiveddate = date("Y-m-d H:i:s");
                        $this->entity->approveddate = date("Y-m-d H:i:s");
                        $this->entity->updated_by = $user_by;
                        $this->entity->{$this->model->primaryKey} = $record_id;
                        $this->save();

                        //TODO : Get data Notification Approved Text Template
                        $dataNotif = $mNotifText->find($this->Notif_Approved);
                    }

                    //TODO : Get data user based on createdby
                    $dataUser = $mUser->where($mUser->primaryKey, $trx->created_by)->findAll();

                    //* Get data text from notification text template
                    $message = $dataNotif->getText();
                }

                $entityAct->setWfResponsibleId($sys_wfresponsible_id);
                $entityAct->setSysUserId($user_id);
                $entityAct->setState($state);
                $entityAct->setProcessed($processed);
                $entityAct->setUpdatedBy($user_by);
                $entityAct->setWfActivityId($sys_wfactivity_id);
                $result = $modelAct->save($entityAct);
            }

            //TODO : Get data from field email 
            $email = array_column($dataUser, "email");

            //TODO : Filter the data use array_unique remove duplicate value then array_filter and exclude null value 
            $filtered_email = array_unique(array_filter($email));

            //TODO : Get data by value only
            $arr_email = array_values($filtered_email);

            /**
             * TODO: Send Email information
             */
            if (isset($trx->documentno))
                $subject = "[" . ucwords($menuName) . "_" . $trx->documentno . "]";
            else
                $subject = "[" . ucwords($menuName) . "]";

            $subject .= " - " . $dataScenLine->notif_subject;
            $message .= "-----" . " " . ucwords($menuName) . " " . $trx->documentno;

            $message = new Html2Text($message);
            $message = $message->getText();

            foreach ($arr_email as $email) {
                $cMail->sendEmail($email, $subject, $message, null, "SAS HR");
            }

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

            $this->model->db->transCommit();
        } catch (\Exception $e) {
            $this->model->db->transRollback();
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    public function create()
    {
        $mWEvent = new M_WEvent($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $isAnswer = $post['isanswer'];
            $_ID = $post['record_id'];
            $txtMsg = $post['textmsg'];

            try {
                $activity = $this->model->find($_ID);

                if ($isAnswer === 'Y') {
                    $eList = $mWEvent->where($this->model->primaryKey, $_ID)->orderBy('created_at', 'ASC')->findAll();

                    foreach ($eList as $event) :
                        $this->wfResponsibleId[] = $event->getWfResponsibleId();
                    endforeach;

                    $this->wfScenarioId = $activity->getWfScenarioId();

                    $response = $this->setActivity($_ID, $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->access->getSessionUser(), $this->DOCSTATUS_Completed, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu());
                } else {
                    $response = $this->setActivity($_ID, $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->access->getSessionUser(), $this->DOCSTATUS_Aborted, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu());
                }
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

    private function toForwardAlert($table, $record_id, $subject, $message)
    {
        $mAlert = new M_AlertRecipient($this->request);
        $cMail = new Mail();

        $result = false;

        $alert = $mAlert->getAlertRecipient($table, $record_id);

        if ($alert) {
            //TODO : Get data from field email 
            $email = array_column($alert, "email");

            //TODO : Filter the data use array_filter and exclude null value 
            $filtered_email = array_filter($email);

            //TODO : Get data by value only
            $arr_email = array_values($filtered_email);


            $result = $cMail->sendEmail($arr_email, $subject, $message, null, "SAS HR");
        }

        return $result;
    }
}
