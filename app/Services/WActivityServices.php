<?php

namespace App\Services;

use App\Controllers\Backend\Message as BackendMessage;
use App\Controllers\Backend\Telegram;
use App\Models\M_AssignmentDetail;
use App\Models\M_Branch;
use App\Models\M_Datatable;
use App\Models\M_Division;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_NotificationText;
use App\Models\M_OvertimeDetail;
use App\Models\M_Responsible;
use App\Models\M_User;
use App\Models\M_WActivity;
use App\Models\M_WEvent;
use App\Models\M_WScenarioDetail;
use Html2Text\Html2Text;
use Pusher\Pusher;

class WActivityServices extends BaseServices
{
    protected $wfScenarioId = 0;
    protected $wfResponsibleId = [];

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

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

    public function setActivity($sys_wfactivity_id, $sys_wfscenario_id, $sys_wfresponsible_id, $user_by, $state, $processed, $textmsg, $table, $record_id, $menu, $isanswer = null, $tableLine = null, $recordLine_id = null)
    {
        $mWResponsible = new M_Responsible($this->request);
        $mWEvent = new M_WEvent($this->request);
        $mUser = new M_User($this->request);
        $mDocType = new M_DocumentType($this->request);

        $modelAct = new M_WActivity($this->request);
        $entityAct = new \App\Entities\WActivity();

        $this->model = new M_Datatable($this->request);
        $this->entity = new \App\Entities\DataTable();

        try {
            $modelAct->db->transBegin();

            $today = date('Y-m-d H:i:s');

            //* Get sys_user_id that responsible for this activity
            $user_id = $mWResponsible->getUserByResponsible($sys_wfresponsible_id);

            //* Get data responsible
            $dataResp = $mWResponsible->find($sys_wfresponsible_id);

            //* Get data user based on role from responsible 
            $dataUser = $mUser->detail(['sr.sys_role_id' => $dataResp->getRoleId()])->getResult();

            //* Get Line Transaction
            $trxLine = null;

            if ($tableLine) {
                $model = new M_Datatable($this->request);
                $trxModel = $model->initDataTable($table);

                //* Call data transaction
                $trx = $trxModel->where($trxModel->primaryKey, $record_id)->first();

                $this->model->initDataTable($tableLine);
                $trxLine = $this->model->where($this->model->primaryKey, $recordLine_id)->first();

                $this->entity->{$this->model->primaryKey} = $recordLine_id;
                $this->entity->{$trxModel->primaryKey} = $record_id;
            } else {
                //* Get Header Transaction
                $this->model->initDataTable($table);
                $trx = $this->model->where($this->model->primaryKey, $record_id)->first();

                $this->entity->{$this->model->primaryKey} = $record_id;
            }

            //* Get Submisiontype data and url menu
            $docType = $mDocType->getDocTypeMenu($trx->submissiontype);

            //* WActivity Condition to checking if next responsible or approve/not approve activity
            if (!empty($sys_wfactivity_id)) {
                $newWfResponsibleId = $this->getNextResponsible();

                //* If Scenario have next responsible
                if (!empty($newWfResponsibleId)) {
                    $user_id = $mWResponsible->getUserByResponsible($newWfResponsibleId);

                    //* Update event audit the previous data
                    $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, true, $tableLine, $recordLine_id);

                    //* Set data sys_wfresponsible_id the next responsible
                    $sys_wfresponsible_id = $newWfResponsibleId;

                    //* Get data user based on session user
                    $dataUser = $mUser->where($mUser->primaryKey, $user_by)->findAll();

                    //* Get data responsible 
                    $dataResp = $mWResponsible->find($sys_wfresponsible_id);
                    $msg = 'Approved By : ' . $dataUser[0]->getUserName() . ' </br> ';
                    $msg .= 'Next Approver : ' . $dataResp->getName() . ' </br> ';
                    $msg .= $textmsg;

                    $entityAct->setTextMsg($msg);

                    //* For getting user new reponsible
                    $dataUser = $mUser->detail(['sr.sys_role_id' => $dataResp->getRoleId()])->getResult();

                    //* For set event audit below
                    if ($state == $this->DOCSTATUS_Completed && $processed) {
                        $state = $this->DOCSTATUS_Suspended;
                        $processed = false;
                    }
                } else {
                    //* if Not Approved
                    if ($state === $this->DOCSTATUS_Aborted && $processed) {
                        if ($trx->docstatus === $this->DOCSTATUS_Requested) {
                            $this->entity->docstatus = $this->DOCSTATUS_Completed;
                        } else {
                            $this->entity->docstatus = $this->DOCSTATUS_NotApproved;
                        }

                        $this->entity->isagree = $this->LINESTATUS_Ditolak;
                        $this->entity->isapproved = $this->LINESTATUS_Ditolak;
                    } else {
                        //* Checking if doctype flow needed to realization
                        if ($docType->isrealization === "Y") {
                            $this->entity->isapproved = $this->LINESTATUS_Disetujui;
                            $this->entity->approved_by = $user_by;

                            //* Set flow line realization
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

                            $this->entity->isagree = $subType[$docType->md_doctype_id];

                            if ($isanswer === "W")
                                $this->entity->comment = $textmsg;
                        } else {
                            $this->entity->docstatus = $state;
                            $this->entity->receiveddate = $today;
                            $this->entity->isagree = $this->LINESTATUS_Disetujui;
                            $this->entity->approved_by = $user_by;
                            $this->entity->isapproved = $this->LINESTATUS_Disetujui;
                        }

                        if ($trx->docstatus === $this->DOCSTATUS_Requested) {
                            $this->entity->docstatus = $this->DOCSTATUS_Voided;
                        }
                    }

                    //* Save data update 
                    $this->entity->updated_by = $user_by;
                    $this->entity->approve_date = $today;
                    $this->entity->approveddate = $today;

                    $result = $this->save();

                    //* Get data user based on createdby
                    $dataUser = $mUser->where($mUser->primaryKey, $trx->created_by)->findAll();
                }
            }

            //* Fill data to entity and update WActivity
            if (empty($sys_wfactivity_id)) {
                $entityAct->setTextMsg($textmsg);
                $entityAct->setCreatedBy($user_by);
            }

            $entityAct->setWfScenarioId($sys_wfscenario_id);
            $entityAct->setTable($table);
            $entityAct->setRecordId($record_id);
            $entityAct->setMenu($menu);
            $entityAct->setTableLine($tableLine);
            $entityAct->setRecordLineId($recordLine_id);
            $entityAct->setWfResponsibleId($sys_wfresponsible_id);
            $entityAct->setSysUserId($user_id);
            $entityAct->setState($state);
            $entityAct->setProcessed($processed);
            $entityAct->setUpdatedBy($user_by);
            $entityAct->setWfActivityId($sys_wfactivity_id);
            $result = $modelAct->save($entityAct);

            //* Set event audit
            if (empty($sys_wfactivity_id))
                $sys_wfactivity_id = $modelAct->getInsertID();

            $mWEvent->setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $processed, $table, $record_id, $user_by, false, $tableLine, $recordLine_id);

            $this->sendNotificationActivity($trx, $trxLine, $docType, $table, $tableLine, $state, $dataUser);

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

    private function sendNotificationActivity(object $trx, $trxLine = null, object $docType, $table, $tableLine, $state, $recipient)
    {
        $mEmployee = new M_Employee($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $cTelegram = new Telegram();
        $cMessage = new BackendMessage();

        if ($state == $this->DOCSTATUS_Completed) {
            $notifType = "Approved";
        } else if ($state == $this->DOCSTATUS_Aborted) {
            $notifType = "Not Approved";
        } else {
            $notifType = "Approval";
        }

        if ($table == "trx_absent") {
            $employee = $mEmployee->find($trx->md_employee_id);

            if ($docType->isapprovedline == 'Y' && $tableLine) {
                $dataNotif = $mNotifText->where('name', "Email {$notifType} Line")->first();
                $message = str_replace(['(Var1)', '(Var2)', '(Var3)'], [$trx->documentno, date('Y-m-d', strtotime($trxLine->date)), $employee->fullname], $dataNotif->getText());
            } else {
                $dataNotif = $mNotifText->where('name', "Email {$dataNotif}")->first();
                $message = str_replace(['(Var1)'], [$trx->documentno], $dataNotif->getText());
            }
        } else if ($table == "trx_assignment" || $table == "trx_overtime") {
            $mBranch = new M_Branch($this->request);
            $mDivision = new M_Division($this->request);
            $mClass = $table == "trx_assignment" ? new M_AssignmentDetail($this->request) : new M_OvertimeDetail($this->request);
            $idField = $table == "trx_assignment" ? 'trx_assignment_id' : 'trx_overtime_id';

            $employeeIds = array_column($mClass->select("md_employee_id")->where($idField, $trx->{$idField})->findAll(), 'md_employee_id');

            $listEmployees = $mEmployee->select('md_employee_id, value, telegram_id')->whereIn($mEmployee->primaryKey, $employeeIds)->findAll();

            $dataNotif = $mNotifText->where('name', "Email {$notifType} Bundling")->first();
            $branch = $mBranch->find($trx->md_branch_id);
            $division = $mDivision->find($trx->md_division_id);

            if ($notifType == "Approved") {
                $listNames = implode(", ", array_column($listEmployees, 'value'));

                $message = str_replace(['(Var1)', '(Var2)', '(Var3)', '(Var4)', '(Var5)'], [$trx->documentno, date('Y-m-d', strtotime($trx->startdate)) . ' s/d ' . date('Y-m-d', strtotime($trx->enddate)), ucwords(strtolower($branch->name)), $division->name, $listNames], $dataNotif->getText());
            } else {
                $message = str_replace(['(Var1)', '(Var2)', '(Var3)'], [$trx->documentno, ucwords(strtolower($branch->name)), ucwords(strtolower($division->name))], $dataNotif->getText());
            }
        } else {
            $dataNotif = $mNotifText->where('name', "Email {$dataNotif}")->first();
            $message = str_replace(['(Var1)'], [$trx->documentno], $dataNotif->getText());
        }

        $subject = $dataNotif->getSubject();

        $msg = (new Html2Text($message))->getText();

        //* Send Information
        foreach ($recipient as $users) {
            $sendTelegram = true;

            if (!empty($employee) && $users->md_employee_id == $employee->md_employee_id)
                $sendTelegram = false;

            $cMessage->sendInformation($users, $subject, $message, 'HARMONY SAS', null, null, true, $sendTelegram, true);
        }

        //* Send telegram to employee on submission
        if ($notifType != "Approval" && !empty($employee) && !empty($employee->telegram_id)) {
            $cTelegram->sendMessage($employee->telegram_id, $msg);
        }

        //* Send telegram to list employee on Assignment or Overtime
        if ($notifType != "Approval" && !empty($listEmployees)) {
            foreach ($listEmployees as $emp) {
                if (!empty($emp->telegram_id))
                    $cTelegram->sendMessage($emp->telegram_id, $msg);
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
}
