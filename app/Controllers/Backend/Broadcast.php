<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Controllers\Backend\Telegram;
use App\Controllers\Backend\Message;

use App\Models\M_Configuration;
use App\Models\M_Employee;
use App\Models\M_Division;
use App\Models\M_Branch;
use App\Models\M_Broadcast;
use App\Models\M_User;
use App\Models\M_BroadcastLog;

use Config\Services;
use Html2Text\Html2Text;

class Broadcast extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Broadcast($this->request);
        $this->entity = new \App\Entities\Broadcast();
        $this->modelDetail = new M_BroadcastLog();
    }

    public function index()
    {
        $data = [
            'today' => date('d-M-Y')
        ];

        return $this->template->render('broadcast/v_broadcast', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {

            $table  = $this->model->table;
            $select = $this->model->getSelect();
            $join   = $this->model->getJoin();

            $order = [
                '', // Hidden ID
                '', // Number
                'trx_broadcast.title',
                'trx_broadcast.message',
                'trx_broadcast.attachment',
                'trx_broadcast.attachment2',
                'trx_broadcast.attachment3',
                'sys_user.name',                 // created_by
                'trx_broadcast.md_employee_id',
                'trx_broadcast.md_branch_id',
                'trx_broadcast.md_division_id',
                'trx_broadcast.effective_date',
                'trx_broadcast.created_at',
                ''
            ];

            $search = [
                'trx_broadcast.title',
                'trx_broadcast.message',
                'sys_user.name',
                'trx_broadcast.effective_date',
                'trx_broadcast.md_employee_id',
                'trx_broadcast.md_branch_id',
                'trx_broadcast.md_division_id',
            ];

            $sort  = ['trx_broadcast.effective_date' => 'DESC'];
            $where = [];

            $data   = [];
            $number = $this->request->getPost('start');

            $list = $this->datatable->getDatatables(
                $table,
                $select,
                $order,
                $sort,
                $search,
                $join,
                $where
            );

            foreach ($list as $value) {

                $row = [];
                $ID = $value->trx_broadcast_id;
                $number++;

                $plainTextMessage = (new Html2Text($value->message))->getText();
                if (strlen($plainTextMessage) > 50) {
                    $plainTextMessage = substr($plainTextMessage, 0, 50) . '...';
                }

                $row[] = $ID;                     // Hidden ID
                $row[] = $number;                 // Number
                $row[] = $value->title;         // Title
                $row[] = $plainTextMessage; // Message

                $row[] = $value->name ?? '-';   // Created By (sys_user.name)

                $row[] = !empty($value->effective_date) && $value->effective_date != '0000-00-00 00:00:00' ? format_dmy($value->effective_date, '-') : '';

                $docStatus = $value->is_sent == 'Y' ? 'IP' : null;

                $row[] = $this->template->tableButton($ID, $docStatus); // Action

                $data[] = $row;
            }

            $result = [
                'draw'            => $this->request->getPost('draw'),
                'recordsTotal'    => $this->datatable->countAll(
                    $table,
                    $select,
                    $order,
                    $sort,
                    $search,
                    $join,
                    $where
                ),
                'recordsFiltered' => $this->datatable->countFiltered(
                    $table,
                    $select,
                    $order,
                    $sort,
                    $search,
                    $join,
                    $where
                ),
                'data'            => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                $isSend = isset($post['issend']) ? $post['issend'] : 'N';

                $sendMethods = [];

                $sendMethods = array_filter([
                    isset($post['send_email']) ? 'E' : null,
                    isset($post['send_notification']) ? 'N' : null,
                    isset($post['send_telegram']) ? 'T' : null,
                ]); 

                if (!$this->validation->run($post, 'broadcast_bb')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {

                    if (!empty($sendMethods)) {
                        
                        $post['sentmethod'] = implode(',', $sendMethods);

                        $title    = $post['title'] ?? '';
                        $imgTitle = url_title($title, '-', true);
                        $imgTitle = substr($imgTitle, 0, 50);
                        $ymd = date('YmdHis');
                        $PATH_Broadcast = "broadcast";
                        $path = $this->PATH_UPLOAD . $PATH_Broadcast . '/';

                        $existingRecord = null;
                        if (!empty($post['id'])) {
                            $existingRecord = $this->model->find($post['id']);
                        }

                        $attachments = ['attachment', 'attachment2', 'attachment3'];
                        foreach ($attachments as $attachmentKey) {
                            $file = $this->request->getFile($attachmentKey); 
                            $getterMethod = 'get' . $attachmentKey; 
                            $oldFileName = $existingRecord 
                                ? $existingRecord->$getterMethod() 
                                : null;

                            $suffix = '';
                            if ($attachmentKey !== 'attachment') {
                                $number = str_replace('attachment', '', $attachmentKey);
                                $suffix = '_' . $number;
                            }

                            $maxFileSize = 2097152;

                            if ($file && $file->isValid() && !$file->hasMoved()) {

                                if ($file->getSize() > $maxFileSize) {

                                    $fileSizeMB = round($file->getSize() / 1048576, 2);
                                    $maxSizeMB  = round($maxFileSize / 1048576, 2);

                                    $response = message(
                                        'error',
                                        false,
                                        "Ukuran file {$attachmentKey} terlalu besar ({$fileSizeMB}MB). Maksimal {$maxSizeMB}MB"
                                    );

                                    return $this->response->setJSON($response);
                                }

                                if ($oldFileName) {
                                    $oldPath = $path . $oldFileName;
                                    if (file_exists($oldPath)) {
                                        unlink($oldPath);
                                    }
                                }

                                $ext = $file->getClientExtension();
                                $img_name = $this->model->Broadcast . $suffix . '_' . $imgTitle . '_' . $ymd . '.' . $ext;

                                uploadFile($file, $path, $img_name);

                                $post[$attachmentKey] = $img_name;
                            }

                            elseif (empty($post[$attachmentKey])) {

                                if ($oldFileName) {
                                    $oldPath = $path . $oldFileName;
                                    if (file_exists($oldPath)) {
                                        unlink($oldPath);
                                    }
                                }

                                $post[$attachmentKey] = null;
                            }
                        }

                        $this->entity->fill($post);

                        $response = $this->save();

                        if ($response && $isSend === 'Y') {
                            $ID = $this->isNew() ? $this->model->getInsertID() : $post['id'];
                            $this->sendBroadcastNow($ID); 
                            $response = message('success', true, 'Pesan sudah dikirim');
                        }
                    } else {
                        $response = message('error', false, 'Mohon pilih minimal satu metode pengiriman (Email, Notification, atau Telegram)');
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
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where('trx_broadcast_id', $id)->findAll();

                $mEmployee = new M_Employee($this->request);
                $mBranch = new M_Branch($this->request);
                $mDivision = new M_Division($this->request);

                if ($list[0]->getMdEmployeeId()) {
                    $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getMdEmployeeId())->first();
                    if ($rowEmp) {
                        $list = $this->field->setDataSelect(
                            $mEmployee->table,
                            $list,
                            $mEmployee->primaryKey,
                            $rowEmp->getEmployeeId(),
                            $rowEmp->getValue()
                        );
                    }
                }

                // Set data for branch if exists
                if ($list[0]->getMdBranchId()) {
                    $rowBranch = $mBranch->where($mBranch->primaryKey, $list[0]->getMdBranchId())->first();
                    if ($rowBranch) {
                        $list = $this->field->setDataSelect(
                            $mBranch->table,
                            $list,
                            $mBranch->primaryKey,
                            $rowBranch->getBranchId(),
                            $rowBranch->getName()
                        );
                    }
                }

                // Set data for division if exists
                if ($list[0]->getMdDivisionId()) {
                    $rowDivision = $mDivision->where($mDivision->primaryKey, $list[0]->getMdDivisionId())->first();
                    if ($rowDivision) {
                        $list = $this->field->setDataSelect(
                            $mDivision->table,
                            $list,
                            $mDivision->primaryKey,
                            $rowDivision->getDivisionId(),
                            $rowDivision->getName()
                        );
                    }
                }

                $PATH_Broadcast = "broadcast";
                $path = $this->PATH_UPLOAD . $PATH_Broadcast . '/';

                // Handle first attachment
                $this->handleAttachmentForShow($list[0], 'attachment', $path, $PATH_Broadcast);

                // Handle second attachment
                $this->handleAttachmentForShow($list[0], 'attachment2', $path, $PATH_Broadcast);

                // Handle third attachment
                $this->handleAttachmentForShow($list[0], 'attachment3', $path, $PATH_Broadcast);

                $title = $list[0]->getTitle();
                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $headerData = $this->field->store($fieldHeader);
                $sentMethod = $list[0]->getSentMethod();
                $methods = !empty($sentMethod) ? explode(',', $sentMethod) : []; 

                $headerData[] = [
                    'field' => 'send_email',
                    'label' => in_array('E', $methods) ? 'E' : '',
                    'primarykey' => false
                ];

                $headerData[] = [
                    'field' => 'send_notification',
                    'label' => in_array('N', $methods) ? 'N' : '',
                    'primarykey' => false
                ];

                $headerData[] = [
                    'field' => 'send_telegram',
                    'label' => in_array('T', $methods) ? 'T' : '',
                    'primarykey' => false
                ];

                $result = [
                    'header' => $headerData,  
                    'line'   => $this->tableLine('edit', $detail)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    private function handleAttachmentForShow($listItem, $fieldName, $fullPath, $relativePath)
    {
        $getterMethod = 'get' . $fieldName;
        $setterMethod = 'set' . $fieldName;

        $filename = $listItem->$getterMethod(); 

        if (!empty($filename) && file_exists($fullPath . $filename)) {
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $attachmentExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

            if (in_array($fileExt, $attachmentExtensions)) {
                $listItem->$setterMethod('uploads/' . $relativePath . '/' . $filename);
            } else {
                $listItem->$setterMethod($filename);
            }
        } else {
            $listItem->$setterMethod(null);
        }
    }

    public function tableLine($set = null, $detail = [])
    {
        $table = [];

        if (!empty($set) && count($detail) > 0) {
            $mEmployee = new M_Employee($this->request);

            $number = 1;

            foreach ($detail as $row) :
                $employee = $mEmployee->find($row->md_employee_id);

                $table[] = [
                    $number++,
                    $employee->value,
                    $row->sentmethod,
                    $row->error_message,
                    format_dmytime($row->created_at, '-')
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function cronUpdateBroadcast()
    {
        $broadcasts = $this->model
            ->where('is_sent', 'N')
            ->where('effective_date <=', date('Y-m-d'))
            ->findAll();
        foreach ($broadcasts as $broadcast) {
            $this->sendBroadcastNow($broadcast->getBroadcastId());
        }

        return;
    }

    public function sendBroadcastNow($broadcastId)
    {
        $broadcast = $this->model->find($broadcastId);

        if (!$broadcast) {
            throw new \Exception("Broadcast {$broadcastId} is not found");
        }

        $mEmployee      = new M_Employee($this->request);
        $mUser          = new M_User($this->request);
        $mBroadcastLog  = new M_BroadcastLog();
        $telegram       = new Telegram($this->request);
        $mail           = new Mail($this->request);
        $messageCon     = new Message();

        $methods = explode(',', (string) $broadcast->getSentMethod());

        $sendEmail    = in_array('E', $methods);
        $sendTelegram = in_array('T', $methods);
        $sendNotif    = in_array('N', $methods);

        $employeeIds = [];

        if ($broadcast->getMdEmployeeId()) {
            $employeeIds[] = $broadcast->getMdEmployeeId();
        } else {
            $arrB = $broadcast->getMdBranchId()   ? [$broadcast->getMdBranchId()]   : [];
            $arrD = $broadcast->getMdDivisionId() ? [$broadcast->getMdDivisionId()] : [];

            if ($arrB || $arrD) {
                $employeeIds = $mEmployee->getEmployeeBased($arrB, $arrD);
            } 
        }

        $mEmployee->whereIn('md_status_id', [100001, 100002, 100008]);

        if (!empty($employeeIds)) {
            $mEmployee->whereIn('md_employee_id', $employeeIds);
        } 

        $employees = $mEmployee->findAll();

        if (empty($employees)) {
            throw new \Exception("No employees found for broadcast {$broadcastId}");
        }

        $subject     = $broadcast->getTitle();
        $messageHtml = $broadcast->getMessage();

        // Attachments
        $attachments = [];
        $basePath = $this->PATH_UPLOAD . "broadcast/";

        foreach (
            [$broadcast->getattachment(), $broadcast->getattachment2(),$broadcast->getattachment3()] 
            as $file
        ) {
            if ($file && file_exists($basePath . $file)) {
                $attachments[] = $basePath . $file;
            }
        }

        // Employee
        foreach ($employees as $employee) {

            $employeeId = $employee->md_employee_id;

            $user = $mUser
                ->where('md_employee_id', $employeeId)
                ->first();

        // Email
        if ($sendEmail) {
            if (!$user || empty($user->email)) {
                $mBroadcastLog->logBroadcast(
                    $broadcastId,
                    $employeeId,
                    'Email',
                    "No sys_user account or email"
                );
            } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                $mBroadcastLog->logBroadcast(
                    $broadcastId,
                    $employeeId,
                    'Email',
                    "Invalid email format ({$user->email})"
                );
            } else {
                try {
                    $sent = $mail->sendEmail(
                        $user->email,
                        $subject,
                        $messageHtml,
                        null,
                        null,
                        $attachments ?: null
                    );

                    if (!$sent) {
                        $mBroadcastLog->logBroadcast(
                            $broadcastId,
                            $employeeId,
                            'Email',
                            "Email failed to send ({$user->email})"
                        );
                    }
                } catch (\Exception $e) {
                    $mBroadcastLog->logBroadcast(
                        $broadcastId,
                        $employeeId,
                        'Email',
                        $e->getMessage()
                    );
                }
            }
        }

            // Telegram
            if ($sendTelegram) {
                if (empty($employee->telegram_id)) {
                    $mBroadcastLog->logBroadcast(
                        $broadcastId,
                        $employeeId,
                        'Telegram',
                        "No telegram_id"
                    );
                } else {
                    try {
                        $telegramMessage = $telegram->prepareHtmlForTelegram($messageHtml);
                        $response = json_decode(
                            $telegram->sendMessage($employee->telegram_id, $telegramMessage, 'HTML'),
                            true
                        );

                        if (empty($response['ok'])) {
                            $mBroadcastLog->logBroadcast(
                                $broadcastId,
                                $employeeId,
                                'Telegram',
                                $response['description'] ?? 'Unknown telegram error'
                            );
                        } else {
                            foreach ($attachments as $file) {
                                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                $attachmentExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                                try {
                                    in_array($ext, $attachmentExts)
                                        ? $telegram->sendPhoto($employee->telegram_id, $file)
                                        : $telegram->sendDocument($employee->telegram_id, $file);
                                } catch (\Exception $e) {
                                    $mBroadcastLog->logBroadcast(
                                        $broadcastId,
                                        $employeeId,
                                        'Telegram',
                                        $e->getMessage()
                                    );
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $mBroadcastLog->logBroadcast(
                            $broadcastId,
                            $employeeId,
                            'Telegram',
                            $e->getMessage()
                        );
                    }
                }
            }


            // Notification
            if ($sendNotif) {
                if (!$user) {
                    $mBroadcastLog->logBroadcast(
                        $broadcastId,
                        $employeeId,
                        'Notification',
                        "No sys_user account"
                    );
                } else {
                    try {
                        $sent = $messageCon->sendNotification(
                            $user->sys_user_id,
                            $subject,
                            $messageHtml
                        );

                        if (!$sent) {
                            $mBroadcastLog->logBroadcast(
                                $broadcastId,
                                $employeeId,
                                'Notification',
                                "Notification failed"
                            );
                        }
                    } catch (\Exception $e) {
                        $mBroadcastLog->logBroadcast(
                            $broadcastId,
                            $employeeId,
                            'Notification',
                            $e->getMessage()
                        );
                    }
                }
            }
        }

        $this->model->update($broadcastId, [
            'is_sent'    => 'Y',
            'lastupdate' => date('Y-m-d H:i:s')
        ]);
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

}
