<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Message;
use App\Models\M_User;
use Pusher\Pusher;
use CodeIgniter\Config\Services;
use Html2Text\Html2Text;

class Message extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Message($this->request);
        $this->entity = new \App\Entities\Message();
    }

    public function index()
    {
        return $this->template->render('message/v_message');
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
            $today = date('Y-m-d');

            $data = [];

            $number = $this->request->getPost('start');

            $where['trx_message.recipient_id'] = $this->session->get('sys_user_id');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            $fieldChk = new \App\Entities\Table();
            $fieldChk->setName("ischecked");
            $fieldChk->setType("checkbox");
            $fieldChk->setClass("check-message");

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_message_id;
                $fieldChk->setValue($ID);
                $fieldChk->setAttribute(['data-isread' => $value->isread]);
                $date = date('Y-m-d', strtotime($value->messagedate));

                if ($today == $date) {
                    $time = date('H:i', strtotime($value->messagedate));
                } else {
                    $time = date('d M y', strtotime($value->messagedate));
                }

                $number++;

                $row[] = $ID;
                $row[] = $this->field->fieldTable($fieldChk);
                $row[] = $value->author;
                $row[] = $value->subject;
                $row[] = $time;
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

    public function show($id)
    {

        if ($this->request->isAjax()) {
            try {
                $list = $this->model->getNotifDetail('trx_message.trx_message_id =' . $id)->getResult();

                if (isset($list[0]->image)) {
                    $list[0]->image = '<img src="' . base_url("uploads/karyawan/" . $list[0]->image) . '">';
                } else {
                    $list[0]->image = '<img src="https://via.placeholder.com/200/808080/ffffff?text=No+Image">';
                }

                $result = [
                    'notification' => $list
                ];

                if ($list[0]->isread === "N") {
                    $this->updateRead($list[0]->trx_message_id);
                }

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function updateRead($messageId = null)
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getPost();

            try {
                if ($messageId) {
                    $id = $messageId;
                } else {
                    $id = $post['trx_message_id'];
                }

                $this->entity->setMessageId($id);
                $this->entity->setIsRead("Y");

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

                $response = $this->save();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function destroy()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getPost();
            try {
                $ID = explode(",", $post['id']);

                $result = $this->deleteBatch($ID);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getNotifMessage()
    {
        $mUser = new M_User($this->request);
        $post = $this->request->getPost();

        if ($post && $post['type'] === "count") {
            $list = $this->model->getNotification("count");
        } else {
            $listNotif = $this->model->getNotification();
            $data = [];
            $number = 1;

            foreach ($listNotif as $value) {
                $user = $mUser->find($value->author_id);
                $time = calculateTime($value->messagedate);
                $row = '<a class="action-notif" href="javascript:void(0)" data-url = ' . $value->trx_message_id . '>
                                            <div class="notif-img"> <img src="https://via.placeholder.com/200/808080/ffffff?text=No+Image">
                                            </div>
                                            <div class="notif-content">
                                                <span class="block"> ' . $user->name . ' - ' . $value->subject . ' </span>
                                                <span class="time"> ' .  $time . ' </span>
                                            </div>
                                        </a>';

                $data[] = $row;
                $number++;
            }

            $totalNotif = $this->model->getNotification("count");

            if ($totalNotif > 0) {
                $countNotif = "Ada $totalNotif notifikasi baru";
            } else {
                $countNotif = "Tidak ada notifikasi baru";
            }

            $list = ['data' => $data, 'total' => $countNotif];
        }

        return json_encode($list);
    }

    public function sendNotification($to, $subject, $message, $from = null)
    {
        if (is_null($from))
            $from = 100001;

        $this->entity->setCreatedBy(100001);
        $this->entity->setUpdatedBy(100001);
        $this->entity->setAuthorId($from);
        $this->entity->setRecipientId($to);
        $this->entity->setSubject($subject);
        $this->entity->setMessageDate(date('Y-m-d H:i:s'));
        $this->entity->setBody($message);

        return $this->model->save($this->entity);
    }

    public function sendInformation($user_to, $subject, $message, $yourname = null, $attachment = null, $user_from = null, $sendEmail = false, $sendTelegram = false, $sendNotif = false)
    {
        $cMail = new Mail();
        $cTelegram = new Telegram();

        $plainMessage = (new Html2Text($message))->getText();

        if ($sendNotif) {
            $this->sendNotification($user_to->sys_user_id, $subject, $message, $user_from ? $user_from->sys_user_id : null);

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
        }

        if ($sendEmail && !empty($user_to->email)) {
            $cMail->sendEmail($user_to->email, $subject, $plainMessage, $user_from ? $user_from->email : null, $yourname, $attachment);
        }

        if ($sendTelegram && !empty($user_to->telegram_id)) {
            $cTelegram->sendMessage($user_to->telegram_id, $plainMessage);
        }
    }
}
