<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AlertRecipient;
use App\Models\M_Configuration;
use App\Models\M_Responsible;
use App\Models\M_User;
use App\Models\M_WActivity;
use App\Models\M_WEvent;
use App\Models\M_WScenarioDetail;
use App\Models\M_Menu;
use App\Models\M_Datatable;
use App\Models\M_NotificationText;
use App\Models\M_DocumentType;
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
                    $tableLine = $value->tableline;
                    $recordLine_id = $value->recordline_id;

                    $menuName = ucwords($mMenu->getMenuBy($menu));

                    if ($tableLine)
                        $trx = $this->model->getDataTrx($table, $recordLine_id, $tableLine);
                    else
                        $trx = $this->model->getDataTrx($table, $record_id);

                    $node = "Approval {$menuName}";
                    $created_at = "";

                    if ($trx && is_null($tableLine)) {
                        $created_at = format_dmytime($trx->created_at, "-");
                        $summary = "{$menuName} {$trx->documentno} : {$trx->usercreated_by}";

                        if ($trx->docstatus === $this->DOCSTATUS_Requested) {
                            $node = "Request Anulir {$menuName}";
                            $created_at = format_dmytime($trx->created_at, "-");
                            $summary = "{$menuName} {$trx->documentno} : {$trx->userupdated_by}";
                        }
                    } else if ($trx && $tableLine) {
                        $created_at = format_dmytime($trx->created_at, "-");
                        $date = format_dmy($trx->date, "-");
                        $summary = "{$menuName} {$trx->documentno} [$trx->value / {$date}] : {$trx->usercreated_by}";
                    } else {
                        $summary = "{$menuName} {$record_id}";
                    }

                    $responsible = $value->wfresponsible;
                    $scenario = $value->scenario;
                    $scenario = "{$scenario} [{$responsible}]";

                    $row[] = $ID;
                    $row[] = $record_id;
                    $row[] = $table;
                    $row[] = $menu;
                    $row[] = $node;
                    $row[] = $created_at;
                    $row[] = $scenario;
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

    public function setActivity($sys_wfactivity_id, $sys_wfscenario_id, $sys_wfresponsible_id, $user_by, $state, $processed, $textmsg, $table, $record_id, $menu, $isanswer = null, $tableLine = null, $recordLine_id = null)
    {
        $mWResponsible = new M_Responsible($this->request);
        $mWEvent = new M_WEvent($this->request);
        $mUser = new M_User($this->request);
        $mMenu = new M_Menu($this->request);
        $mWfsD = new M_WScenarioDetail($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $mDocType = new M_DocumentType($this->request);
        $cMail = new Mail();
        $cMessage = new Message();

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
            $entityAct->setTableLine($tableLine);
            $entityAct->setRecordLineId($recordLine_id);

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

            if ($tableLine) {
                $model = new M_Datatable($this->request);

                //TODO : Initialize modeling data table 
                $trxModel = $model->initDataTable($table);

                //TODO : Call data transaction
                $trx = $trxModel->where($trxModel->primaryKey, $record_id)->first();

                $docType = $mDocType->find($trx->submissiontype);

                // if ($docType->getIsRealization() === "Y") {
                //     //TODO : Initialize modeling data table 
                //     $trxModel = $this->model->initDataTable($table);

                //     //TODO : Call data transaction
                //     $trx = $trxModel->where($trxModel->primaryKey, $record_id)->first();

                //     $this->entity->{$this->model->primaryKey} = $record_id;
                //     $this->entity->approveddate = date("Y-m-d H:i:s");
                // } else {
                $trxModelLine = $this->model->initDataTable($tableLine);

                $trxLine = $trxModelLine->where($trxModelLine->primaryKey, $recordLine_id)->first();

                $this->entity->{$this->model->primaryKey} = $recordLine_id;
                $this->entity->{$trxModel->primaryKey} = $record_id;
                // }
            } else {
                //TODO : Initialize modeling data table 
                $trxModel = $this->model->initDataTable($table);

                //TODO : Call data transaction
                $trx = $trxModel->where($trxModel->primaryKey, $record_id)->first();

                $docType = $mDocType->find($trx->submissiontype);

                $this->entity->{$this->model->primaryKey} = $record_id;
                $this->entity->approveddate = date("Y-m-d H:i:s");
            }

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
                $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, false, $tableLine, $recordLine_id);
            } else {
                if (!empty($this->getNextResponsible())) {
                    $newWfResponsibleId = $this->getNextResponsible();
                    $user_id = $mWResponsible->getUserByResponsible($newWfResponsibleId);

                    //TODO : Update event audit the previous data
                    $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, true, $tableLine, $recordLine_id);

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

                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, false, $tableLine, $recordLine_id);
                    }
                } else {
                    if ($state === $this->DOCSTATUS_Aborted && $processed) {
                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, false, $tableLine, $recordLine_id);

                        if ($trx->docstatus === $this->DOCSTATUS_Requested) {
                            $this->entity->docstatus = $this->DOCSTATUS_Completed;
                        } else {
                            $this->entity->docstatus = $this->DOCSTATUS_NotApproved;
                        }

                        $this->entity->isagree = "N";
                        $this->entity->isapproved = "N";

                        //TODO : Get data Notification Not Approved Text Template
                        $dataNotif = $mNotifText->find($this->Notif_NotApproved);
                    } else {
                        $state = $this->DOCSTATUS_Completed;
                        $processed = true;

                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, false, $tableLine, $recordLine_id);

                        //! SAS Form Realisasi 
                        if ($docType->getIsRealization() === "Y") {
                            $this->entity->isapproved = "Y";
                            $this->entity->approved_by = $user_by;

                            $subType = [
                                100001 => 'S', // Sakit
                                100003 => 'S', // Cuti
                                100004 => 'S', // Ijin
                                100005 => 'S', // Ijin Resmi
                                100007 => 'M', // Tugas Kantor
                                100008 => 'M', // Penugasan
                                100014 => 'M', // Lembur
                                100010 => 'M', // Lupa Absen Masuk
                                100011 => 'M'  // Lupa Absen Pulang
                            ];

                            $this->entity->isagree = $subType[$docType->getDocTypeId()];

                            if ($isanswer === "W")
                                $this->entity->comment = $textmsg;
                        } else {
                            $this->entity->docstatus = $state;
                            $this->entity->receiveddate = date("Y-m-d H:i:s");
                            $this->entity->isagree = "Y";
                            $this->entity->approved_by = $user_by;
                            $this->entity->isapproved = "Y";
                        }

                        if ($trx->docstatus === $this->DOCSTATUS_Requested) {
                            $this->entity->docstatus = $this->DOCSTATUS_Voided;
                        }

                        //TODO : Get data Notification Approved Text Template
                        $dataNotif = $mNotifText->find($this->Notif_Approved);
                    }

                    //TODO: Save data update 
                    $this->entity->updated_by = $user_by;
                    $result = $this->save();

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

            $subject .= " - " . $dataNotif->getSubject();
            $message .= "-----" . " " . ucwords($menuName) . " " . $trx->documentno;

            // $message = new Html2Text($message);
            // $message = $message->getText();

            // foreach ($arr_email as $email) {
            //     $cMail->sendEmail($email, $subject, $message, null, "SAS HR");
            // }

            foreach ($dataUser as $users) {
                $cMessage->sendInformation($users, $subject, $message, 'SAS HRD', null, null, true, true, true);
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
                if (!$this->validation->run($post, 'wactivity')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $activity = $this->model->find($_ID);

                    if ($isAnswer !== 'N') {
                        $eList = $mWEvent->where($this->model->primaryKey, $_ID)->orderBy('created_at', 'ASC')->findAll();

                        foreach ($eList as $event) :
                            $this->wfResponsibleId[] = $event->getWfResponsibleId();
                        endforeach;

                        $this->wfScenarioId = $activity->getWfScenarioId();

                        $result = $this->setActivity($_ID, $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->access->getSessionUser(), $this->DOCSTATUS_Completed, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu(), $isAnswer, $activity->getTableLine(), $activity->getRecordLineId());
                    } else {
                        $result = $this->setActivity($_ID, $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->access->getSessionUser(), $this->DOCSTATUS_Aborted, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu(), $isAnswer, $activity->getTableLine(), $activity->getRecordLineId());
                    }

                    $response = message('success', true, $result);;
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return json_encode($response);
        }
    }

    public function doNotApproved()
    {
        $mConfig = new M_Configuration($this->request);

        $this->session->set([
            'sys_user_id'       => 100000,
        ]);

        //TODO : Get Rule for Not Approve Approval
        $rule = $mConfig->where(['name' => 'AUTO_REJECT_APPROVAL', 'isactive' => 'Y'])->first();

        //TODO : If configuration is equal or under 0 then Cron will not run
        if ($rule->value > 0) {
            $where = "ADDDATE(sys_wfactivity.created_at, INTERVAL {$rule->value} DAY) <= NOW()";

            $list = $this->model->getActivity(null, $where);

            if ($list) {
                foreach ($list as $row) {
                    $this->setActivity($row->sys_wfactivity_id, $row->sys_wfscenario_id, $row->sys_wfresponsible_id, session()->get('sys_user_id'), $this->DOCSTATUS_Aborted, true, "Not Approved By System", $row->table, $row->record_id, $row->menu);
                }
            }
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
