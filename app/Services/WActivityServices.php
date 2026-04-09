<?php

namespace App\Services;

use App\Controllers\Backend\Message as BackendMessage;
use App\Controllers\Backend\Telegram;
use App\Models\M_Branch;
use App\Models\M_Datatable;
use App\Models\M_Division;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_NotificationText;
use App\Models\M_Responsible;
use App\Models\M_User;
use App\Models\M_WActivity;
use App\Models\M_WEvent;
use App\Models\M_WScenarioDetail;
use Pusher\Pusher;

class WActivityServices extends BaseServices
{
    protected $wfScenarioId = 0;
    protected $wfResponsibleId = [];

    public function __construct(int $userID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->model = new M_WActivity($this->request);
        $this->entity = new \App\Entities\WActivity();
    }

    public function processActivity(int $sys_wfactivity_id, $data)
    {
        $_ID = $sys_wfactivity_id;

        if (empty($_ID)) {
            $result = $this->setActivity(null, $data['sys_wfscenario_id'], $data['sys_wfresponsible'], $data['sys_user_id'], $data['state'], $data['processed'], $data['textmsg'], $data['table'], $data['record_id'], $data['menu'], $data['isanswer'], $data['tableLine'], $data['recordLine_id']);
            $output = $this->respondService(true, $result);
        } else {
            $isAnswer = $data['isanswer'];
            $txtMsg = $data['textmsg'];

            $activity = $this->model->find($_ID);

            if ($activity->getState() != $this->DOCSTATUS_Suspended) {
                $output = $this->respondService(false, "Data tidak bisa diproses, harap lakukan refresh");
            } else if ($isAnswer != $this->LINESTATUS_Ditolak) {
                $mWEvent = new M_WEvent($this->request);

                $eList = $mWEvent->where($this->model->primaryKey, $_ID)->findAll();

                foreach ($eList as $event) :
                    $this->wfResponsibleId[] = $event->getWfResponsibleId();
                endforeach;

                $this->wfScenarioId = $activity->getWfScenarioId();

                $result = $this->setActivity($activity->getWfActivityId(), $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->userID, $this->DOCSTATUS_Completed, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu(), $isAnswer, $activity->getTableLine(), $activity->getRecordLineId());
                $output = $this->respondService(true, $result);
            } else {
                $result = $this->setActivity($activity->getWfActivityId(), $activity->getWfScenarioId(), $activity->getWfResponsibleId(), $this->userID, $this->DOCSTATUS_Aborted, true, $txtMsg, $activity->getTable(), $activity->getRecordId(), $activity->getMenu(), $isAnswer, $activity->getTableLine(), $activity->getRecordLineId());
                $output = $this->respondService(true, $result);
            }
        }

        return $output;
    }

    protected function setActivity($sys_wfactivity_id, $sys_wfscenario_id, $sys_wfresponsible_id, $user_by, $state, $processed, $textmsg, $table, $record_id, $menu, $isanswer = null, $tableLine = null, $recordLine_id = null)
    {
        $mWResponsible = new M_Responsible($this->request);
        $mWEvent = new M_WEvent($this->request);
        $mUser = new M_User($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $mDocType = new M_DocumentType($this->request);
        $mEmployee = new M_Employee($this->request);
        $mDivision = new M_Division($this->request);
        $mBranch = new M_Branch($this->request);
        $cTelegram = new Telegram();
        $cMessage = new BackendMessage();

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

            //TODO : Get data responsible 
            $dataResp = $mWResponsible->find($sys_wfresponsible_id);

            //TODO : Get data user based on role from responsible 
            $dataUser = $mUser->detail(['sr.sys_role_id' => $dataResp->getRoleId()])->getResult();

            $employee = null;

            if ($tableLine) {
                $model = new M_Datatable($this->request);

                //TODO : Initialize modeling data table 
                $trxModel = $model->initDataTable($table);

                //TODO : Call data transaction
                $trx = $trxModel->where($trxModel->primaryKey, $record_id)->first();

                $docType = $mDocType->find($trx->submissiontype);

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

            //TODO : Get Approval Notification Text Template
            if ($docType->isapprovedline == 'Y' && $table == "trx_absent" && $tableLine) {
                $emp = $mEmployee->find($trx->md_employee_id);
                $dataNotif = $mNotifText->where('name', 'Email Approval Line')->first();
                $subject = $dataNotif->getSubject();
                $message = str_replace(['(Var1)', '(Var2)', '(Var3)'], [$trx->documentno, date('Y-m-d', strtotime($trxLine->date)), $emp->fullname], $dataNotif->getText());
            } else if ($table == "trx_assignment" || $table == "trx_overtime") {
                $dataNotif = $mNotifText->where('name', 'Email Approval Bundling')->first();
                $subject = $dataNotif->getSubject();
                $branch = $mBranch->find($trx->md_branch_id);
                $division = $mDivision->find($trx->md_division_id);
                $message = str_replace(['(Var1)', '(Var2)', '(Var3)'], [$trx->documentno, ucwords(strtolower($branch->name)), ucwords(strtolower($division->description))], $dataNotif->getText());
            } else {
                $dataNotif = $mNotifText->where('name', 'Email Approval')->first();
                $subject = $dataNotif->getSubject();
                $message = str_replace(['(Var1)'], [$trx->documentno], $dataNotif->getText());
            }

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
                $newWfResponsibleId = $this->getNextResponsible();

                if (!empty($newWfResponsibleId)) {
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

                    //TODO : For getting user new reponsible
                    $dataUser = $mUser->detail(['sr.sys_role_id' => $dataResp->getRoleId()])->getResult();

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

                        $this->entity->isagree = $this->LINESTATUS_Ditolak;
                        $this->entity->isapproved = $this->LINESTATUS_Ditolak;

                        //TODO : Get data Notification Not Approved Text Template
                        if ($docType->isapprovedline == 'Y' && $table == "trx_absent" && $tableLine) {
                            $employee = $mEmployee->find($trx->md_employee_id);
                            $dataNotif = $mNotifText->where('name', 'Email Not Approved Line')->first();
                            $subject = $dataNotif->getSubject();
                            $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, date('Y-m-d', strtotime($trxLine->date))], $dataNotif->getText());
                        } else if ($table == "trx_assignment" || $table == "trx_overtime") {
                            $dataNotif = $mNotifText->where('name', 'Email Not Approved Bundling')->first();
                            $subject = $dataNotif->getSubject();
                            $branch = $mBranch->find($trx->md_branch_id);
                            $division = $mDivision->find($trx->md_division_id);
                            $message = str_replace(['(Var1)', '(Var2)', '(Var3)'], [$trx->documentno, ucwords(strtolower($branch->name)), ucwords(strtolower($division->description))], $dataNotif->getText());
                        } else {
                            $dataNotif = $mNotifText->where('name', 'Email Not Approved')->first();
                            $subject = $dataNotif->getSubject();
                            $message = str_replace(['(Var1)'], [$trx->documentno], $dataNotif->getText());
                        }
                    } else {
                        $state = $this->DOCSTATUS_Completed;
                        $processed = true;

                        $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, false, $tableLine, $recordLine_id);

                        //! SAS Form Realisasi 
                        if ($docType->getIsRealization() === "Y") {
                            $this->entity->isapproved = $this->LINESTATUS_Disetujui;
                            $this->entity->approved_by = $user_by;

                            $subType = [
                                100001 => $this->LINESTATUS_Realisasi_HRD, // Sakit
                                100003 => $this->LINESTATUS_Realisasi_HRD, // Cuti
                                100004 => $this->LINESTATUS_Realisasi_HRD, // Ijin
                                100005 => $this->LINESTATUS_Realisasi_HRD, // Ijin Resmi
                                100007 => $this->LINESTATUS_Realisasi_Atasan, // Tugas Kantor
                                100008 => $this->LINESTATUS_Realisasi_Atasan, // Penugasan
                                100014 => $this->LINESTATUS_Realisasi_Atasan, // Lembur
                                100010 => $this->LINESTATUS_Realisasi_Atasan, // Lupa Absen Masuk
                                100011 => $this->LINESTATUS_Realisasi_Atasan, // Lupa Absen Pulang
                                100018 => $this->LINESTATUS_Realisasi_HRD, // Pembatalan
                                100013 => $this->LINESTATUS_Realisasi_Atasan, // Pulang Cepat
                                100009 => $this->LINESTATUS_Realisasi_Atasan  // Tugas Kantor 1/2 Hari
                            ];

                            $this->entity->isagree = $subType[$docType->getDocTypeId()];

                            if ($isanswer === "W")
                                $this->entity->comment = $textmsg;
                        } else {
                            $this->entity->docstatus = $state;
                            $this->entity->receiveddate = date("Y-m-d H:i:s");
                            $this->entity->isagree = $this->LINESTATUS_Disetujui;
                            $this->entity->approved_by = $user_by;
                            $this->entity->isapproved = $this->LINESTATUS_Disetujui;
                        }

                        if ($trx->docstatus === $this->DOCSTATUS_Requested) {
                            $this->entity->docstatus = $this->DOCSTATUS_Voided;
                        }

                        //TODO : Get data Notification Approved Text Template
                        if ($docType->isapprovedline == 'Y' && $table == "trx_absent" && $tableLine) {
                            $employee = $mEmployee->find($trx->md_employee_id);
                            $dataNotif = $mNotifText->where('name', 'Email Approved Line')->first();
                            $subject = $dataNotif->getSubject();
                            $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, date('Y-m-d', strtotime($trxLine->date))], $dataNotif->getText());
                        } else if ($table == "trx_assignment" || $table == "trx_overtime") {
                            $dataNotif = $mNotifText->where('name', 'Email Approved Bundling')->first();
                            $subject = $dataNotif->getSubject();
                            $branch = $mBranch->find($trx->md_branch_id);
                            $division = $mDivision->find($trx->md_division_id);
                            $message = str_replace(['(Var1)', '(Var2)', '(Var3)'], [$trx->documentno, ucwords(strtolower($branch->name)), ucwords(strtolower($division->description))], $dataNotif->getText());
                        } else {
                            $dataNotif = $mNotifText->where('name', 'Email Approved')->first();
                            $subject = $dataNotif->getSubject();
                            $message = str_replace(['(Var1)'], [$trx->documentno], $dataNotif->getText());
                        }
                    }

                    //TODO: Save data update 
                    $this->entity->updated_by = $user_by;
                    $this->entity->approve_date = date("Y-m-d H:i:s");
                    $result = $this->save();

                    //TODO : Get data user based on createdby
                    $dataUser = $mUser->where($mUser->primaryKey, $trx->created_by)->findAll();
                }

                $entityAct->setWfResponsibleId($sys_wfresponsible_id);
                $entityAct->setSysUserId($user_id);
                $entityAct->setState($state);
                $entityAct->setProcessed($processed);
                $entityAct->setUpdatedBy($user_by);
                $entityAct->setWfActivityId($sys_wfactivity_id);
                $result = $modelAct->save($entityAct);
            }

            /**
             * TODO: Send information
             */

            foreach ($dataUser as $users) {
                $sendTelegram = true;

                if (!empty($employee) && $users->md_employee_id == $employee->md_employee_id)
                    $sendTelegram = false;

                $cMessage->sendInformation($users, $subject, $message, 'HARMONY SAS', null, null, true, $sendTelegram, true);
            }

            if (!empty($employee) && !empty($employee->telegram_id)) {
                $cTelegram->sendMessage($employee->telegram_id, (new Html2Text($message))->getText());
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

            $this->model->db->transCommit();
        } catch (\Exception $e) {
            $this->model->db->transRollback();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
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
}
