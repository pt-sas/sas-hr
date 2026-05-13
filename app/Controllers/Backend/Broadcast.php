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
use App\Models\M_BroadcastQueue;
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
                $plainTextMessage = (new Html2Text(
                    html_entity_decode($value->message, ENT_QUOTES, 'UTF-8')
                ))->getText();
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
            $mBroadcastQueue = new M_BroadcastQueue($this->request);

            $post = $this->request->getVar();
            $file = $this->request->getFile('attachment');
            $file2 = $this->request->getFile('attachment2');
            $file3 = $this->request->getFile('attachment3');

            try {
                $isSend = isset($post['issend']) ? $post['issend'] : 'N';
                $post['message'] = htmlentities(base64_decode($post['message']), ENT_QUOTES, 'UTF-8');
                $ymd = date('YmdHis');

                if ($file && $file->isValid()) {
                    $ext = $file->getClientExtension();
                    $img_name = 'Broadcast1' . '_' . $ymd . '.' . $ext;
                    $post['attachment'] = $img_name;
                }

                if ($file2 && $file2->isValid()) {
                    $ext2 = $file2->getClientExtension();
                    $img2_name = "Broadcast2" . '_' . $ymd . '.' . $ext2;
                    $post['attachment2'] = $img2_name;
                }

                if ($file3 && $file3->isValid()) {
                    $ext3 = $file3->getClientExtension();
                    $img3_name = "Broadcast3" . '_' . $ymd . '.' . $ext3;
                    $post['attachment3'] = $img3_name;
                }

                if (!$this->validation->run($post, 'broadcast_bb')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $sendMethods = array_filter([
                        isset($post['send_email']) ? 'E' : null,
                        isset($post['send_notification']) ? 'N' : null,
                        isset($post['send_telegram']) ? 'T' : null,
                    ]);

                    $hasEffectiveDate = !empty($post['effective_date']);
                    $hasEffectiveTime = !empty($post['effective_time']);
                    $currentDateTime = date('Y-m-d H:i:s');

                    if ($hasEffectiveDate && $hasEffectiveTime) {
                        $effectiveDateTime = date('Y-m-d', strtotime($post["effective_date"])) . " " . $post['effective_time'];
                        $post["effective_date"] = date('Y-m-d', strtotime($post["effective_date"])) . " " . $post['effective_time'];
                    }

                    if (($hasEffectiveDate || $hasEffectiveTime) && $isSend == "Y") {
                        $response = message('error', false, 'Tidak bisa mengirim sekarang jika anda set Tanggal/Jam Efektif');
                    } else if (($hasEffectiveDate && !$hasEffectiveTime) || (!$hasEffectiveDate && $hasEffectiveTime)) {
                        $response = message('error', false, 'Jam Efektif dan Tanggal Efektif harus diisi');
                    } else if (!empty($effectiveDateTime) && $effectiveDateTime <= $currentDateTime) {
                        $response = message('error', false, 'Tanggal/Jam Efektif tidak boleh kurang atau sama persis dengan waktu sekarang');
                    } else if (empty($sendMethods)) {
                        $response = message('error', false, 'Mohon pilih minimal satu metode pengiriman (Email, Notification, atau Telegram)');
                    } else {
                        $post['sentmethod'] = implode(',', $sendMethods);

                        $path = $this->PATH_UPLOAD . "broadcast" . '/';

                        if ($this->isNew()) {
                            if ($file && $file->isValid())
                                uploadFile($file, $path, $img_name);

                            if ($file2 && $file2->isValid())
                                uploadFile($file2, $path, $img2_name);

                            if ($file3 && $file3->isValid())
                                uploadFile($file3, $path, $img3_name);
                        } else {
                            $row = $this->model->find($this->getID());

                            if (empty($post['attachment']) && !empty($row->getAttachment()) && file_exists($path . $row->getAttachment())) {
                                unlink($path . $row->getAttachment());
                            } else  if ($file && $file->isValid()) {
                                uploadFile($file, $path, $img_name);
                            }

                            if (empty($post['attachment2']) && !empty($row->getAttachment2()) && file_exists($path . $row->getAttachment2())) {
                                unlink($path . $row->getAttachment2());
                            } else if ($file2 && $file2->isValid()) {
                                uploadFile($file2, $path, $img2_name);
                            }

                            if (empty($post['attachment3']) && !empty($row->getAttachment3()) && file_exists($path . $row->getAttachment3())) {
                                unlink($path . $row->getAttachment3());
                            } else if ($file3 && $file3->isValid()) {
                                uploadFile($file3, $path, $img3_name);
                            }
                        }

                        $this->entity->fill($post);

                        $response = $this->save();

                        if ($isSend === 'Y') {
                            $ID = $this->isNew() ? $this->model->getInsertID() : $post['id'];

                            //* Insert to queue
                            $queueEntity = new \App\Entities\BroadcastQueue();
                            $queueEntity->trx_broadcast_id = $ID;
                            $queueEntity->status = 'PE';
                            $queueEntity->starttime = date('Y-m-d H:i:s');
                            $queueEntity->created_by = session()->get('sys_user_id');
                            $queueEntity->updated_by = session()->get('sys_user_id');
                            $mBroadcastQueue->save($queueEntity);

                            //* Update is Sent to Yes
                            $this->entity->setBroadcastId($ID);
                            $this->entity->setIsSent('Y');
                            $this->save();

                            $response = message('success', true, 'Pesan sudah dikirim');
                        }
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

                if ($list[0]->getEffectiveDate() && $list[0]->getEffectiveDate() != '0000-00-00 00:00:00') {
                    $list[0]->effective_time = format_time($list[0]->getEffectiveDate());
                    $list[0]->effective_date = format_dmy($list[0]->getEffectiveDate(), "-");
                } else {
                    $list[0]->effective_time = '';
                    $list[0]->effective_date = '';
                }

                $title = $list[0]->getTitle();
                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(["effective_time"]);
                $fieldHeader->setList($list);
                $list[0]->message = html_entity_decode($list[0]->getMessage(), ENT_QUOTES, 'UTF-8');

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

    private function extractOriginalFilename($storedFilename)
    {

        if (preg_match('/\[(.+?)\]/', $storedFilename, $matches)) {
            $originalName = $matches[1];
            $extension = pathinfo($storedFilename, PATHINFO_EXTENSION);
            return $originalName . '.' . $extension;
        }

        return $storedFilename;
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
        //* Set Process time limit to zero or no limit
        set_time_limit(0);

        $mBroadcastQueue = new M_BroadcastQueue($this->request);
        $now = date('Y-m-d H:i:s');

        // * Insert to queue first
        $broadcasts = $this->model->select('trx_broadcast_id')
            ->where('is_sent', 'N')
            ->where('effective_date IS NOT NULL')
            ->where('YEAR(effective_date) > 1970')
            ->where('effective_date <=', $now)
            ->findAll();

        foreach ($broadcasts as $val) {
            $queue = $mBroadcastQueue->where('trx_broadcast_id', $val->trx_broadcast_id)->first();

            if (!$queue) {
                //* Insert to queue
                $queueEntity = new \App\Entities\BroadcastQueue();
                $queueEntity->trx_broadcast_id = $val->trx_broadcast_id;
                $queueEntity->status = 'PE';
                $queueEntity->starttime = $now;
                $queueEntity->created_by = 100000;
                $queueEntity->updated_by = 100000;
                $mBroadcastQueue->save($queueEntity);
            }
        }

        //* Running broadcast base on queue
        $queueList = $mBroadcastQueue->where(['status' => 'PE', 'starttime <=' => date('Y-m-d H:i:s')])->findAll();

        if (!empty($queueList)) {
            //* Update to inprogress 
            $allQueueID = array_column($queueList, 'trx_broadcast_queue_id');
            $mBroadcastQueue->whereIn('trx_broadcast_queue_id', $allQueueID)->set(['status' => 'IP'])->update();

            //* Do Send Broadcast
            foreach ($queueList as $val) {
                $this->sendBroadcastNow($val->trx_broadcast_id);
            }
        }
    }

    public function sendBroadcastNow(int $broadcastId)
    {
        $mBroadcastQueue = new M_BroadcastQueue($this->request);
        $mEmployee      = new M_Employee($this->request);
        $mUser          = new M_User($this->request);
        $mBroadcastLog  = new M_BroadcastLog();
        $telegram       = new Telegram();
        $mail           = new Mail();
        $messageCon     = new Message();

        $trx = $this->model->find($broadcastId);

        if (!$trx) {
            throw new \Exception("Broadcast {$broadcastId} is not found");
        }

        //* Set isSent to yes
        $this->model->update($broadcastId, [
            'is_sent'    => 'Y',
            'lastupdate' => date('Y-m-d H:i:s')
        ]);

        //* Get Send Method
        $methods = explode(',', (string) $trx->getSentMethod());

        $sendEmail    = in_array('E', $methods);
        $sendTelegram = in_array('T', $methods);
        $sendNotif    = in_array('N', $methods);

        //* Get Employee List
        $employeeIds = null;

        if ($trx->getMdEmployeeId()) {
            $employeeIds = [$trx->getMdEmployeeId()];
        } else {
            $arrB = $trx->getMdBranchId()   ? [$trx->getMdBranchId()]   : [];
            $arrD = $trx->getMdDivisionId() ? [$trx->getMdDivisionId()] : [];

            if ($arrB || $arrD) {
                $employeeIds = $mEmployee->getEmployeeBased($arrB, $arrD);
            }
        }

        $mEmployee->whereIn('md_status_id', [$this->Status_PERMANENT, $this->Status_PROBATION, $this->Status_KONTRAK]);

        if (!empty($employeeIds)) {
            $mEmployee->whereIn('md_employee_id', $employeeIds);
        }

        $employees = $mEmployee->findAll();

        if (empty($employees)) {
            throw new \Exception("No employees found for broadcast {$broadcastId}");
        }

        //* Preparing Broadcast Data
        $subject     = $trx->getTitle();
        $messageEncoded = $trx->getMessage(); //Encoded
        $messageHtml = html_entity_decode($messageEncoded, ENT_QUOTES, 'UTF-8');

        // Attachments
        $attachments = [];
        $path = $this->PATH_UPLOAD . "broadcast" . '/';

        foreach (
            [$trx->getattachment(), $trx->getattachment2(), $trx->getattachment3()]
            as $file
        ) {
            if ($file && file_exists($path . $file)) {
                $attachments[] = [
                    'path' => $path . $file,
                    'name' => $this->extractOriginalFilename($file)
                ];
            }
        }

        //* Populate Users
        $whereClause = "isactive = 'Y' AND (md_employee_id IS NOT NULL OR md_employee_id != 0)";
        $allUsers = $mUser->where($whereClause)->findAll();
        $userMap = array_column($allUsers, null, 'md_employee_id');

        $logging_data = [];

        // Employee
        foreach ($employees as $employee) {
            $employeeId = $employee->md_employee_id;
            $user = isset($userMap[$employeeId]) ? $userMap[$employeeId] : null;

            // Email
            if ($sendEmail && $user) {
                if (empty($user->email)) {
                    $logging_data[] = [
                        'trx_broadcast_id' => $broadcastId,
                        'md_employee_id' => $employeeId,
                        'sentmethod' => 'Email',
                        'error_message' => 'No email account'
                    ];
                } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    $logging_data[] = [
                        'trx_broadcast_id' => $broadcastId,
                        'md_employee_id' => $employeeId,
                        'sentmethod' => 'Email',
                        'error_message' => "Invalid email format ({$user->email})"
                    ];
                } else {
                    try {
                        $sent = $mail->sendEmail(
                            $user->email,
                            $subject,
                            $messageHtml,
                            null,
                            null,
                            $attachments,
                            true
                        );

                        if (!$sent) {
                            $logging_data[] = [
                                'trx_broadcast_id' => $broadcastId,
                                'md_employee_id' => $employeeId,
                                'sentmethod' => 'Email',
                                'error_message' => "Email failed to send ({$user->email})"
                            ];
                        }
                    } catch (\Exception $e) {
                        $logging_data[] = [
                            'trx_broadcast_id' => $broadcastId,
                            'md_employee_id' => $employeeId,
                            'sentmethod' => 'Email',
                            'error_message' => $e->getMessage()
                        ];
                    }
                }
            }

            // Telegram
            if ($sendTelegram) {
                if (empty($employee->telegram_id)) {
                    $logging_data[] = [
                        'trx_broadcast_id' => $broadcastId,
                        'md_employee_id' => $employeeId,
                        'sentmethod' => 'Telegram',
                        'error_message' => "No telegram_id"
                    ];
                } else {
                    try {
                        $telegramMessage = $telegram->prepareHtmlForTelegram($messageHtml);
                        $response = json_decode(
                            $telegram->sendMessage($employee->telegram_id, $telegramMessage, 'HTML'),
                            true
                        );

                        if (empty($response['ok'])) {
                            $logging_data[] = [
                                'trx_broadcast_id' => $broadcastId,
                                'md_employee_id' => $employeeId,
                                'sentmethod' => 'Telegram',
                                'error_message' => $response['description'] ?? 'Unknown telegram error'
                            ];
                        } else {
                            foreach ($attachments as $att) {
                                $ext = strtolower(pathinfo($att['path'], PATHINFO_EXTENSION));
                                $attachmentExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                                try {
                                    if (in_array($ext, $attachmentExts)) {
                                        $telegram->sendPhoto($employee->telegram_id, $att['path']);
                                    } else {
                                        $telegram->sendDocument(
                                            $employee->telegram_id,
                                            $att,
                                        );
                                    }
                                } catch (\Exception $e) {
                                    $logging_data[] = [
                                        'trx_broadcast_id' => $broadcastId,
                                        'md_employee_id' => $employeeId,
                                        'sentmethod' => 'Telegram',
                                        'error_message' => $e->getMessage()
                                    ];
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $logging_data[] = [
                            'trx_broadcast_id' => $broadcastId,
                            'md_employee_id' => $employeeId,
                            'sentmethod' => 'Telegram',
                            'error_message' => $e->getMessage()
                        ];
                    }
                }
            }

            // Notification
            if ($sendNotif && $user) {
                try {
                    $sent = $messageCon->sendNotification(
                        $user->sys_user_id,
                        $subject,
                        $messageHtml
                    );

                    if (!$sent) {
                        $logging_data[] = [
                            'trx_broadcast_id' => $broadcastId,
                            'md_employee_id' => $employeeId,
                            'sentmethod' => 'Notification',
                            'error_message' => "Notification failed"
                        ];
                    }
                } catch (\Exception $e) {
                    $logging_data[] = [
                        'trx_broadcast_id' => $broadcastId,
                        'md_employee_id' => $employeeId,
                        'sentmethod' => 'Notification',
                        'error_message' => $e->getMessage()
                    ];
                }
            }
        }

        if (!empty($logging_data))
            $mBroadcastLog->builder->insertBatch($logging_data);

        //* Update Broadcast Queue
        $queue = $mBroadcastQueue->where('trx_broadcast_id', $broadcastId)->first();

        $queueEntity = new \App\Entities\BroadcastQueue();
        $queueEntity->trx_broadcast_queue_id = $queue->trx_broadcast_queue_id;
        $queueEntity->status = 'CO';
        $queueEntity->endtime = date('Y-m-d H:i:s');
        $queueEntity->updated_by = 100000;
        $mBroadcastQueue->save($queueEntity);
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
