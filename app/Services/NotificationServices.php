<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_Message;

class NotificationServices extends BaseServices
{
    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Message($this->request);
        $this->entity = new \App\Entities\Message();
    }

    public function getPaginated(array $params)
    {
        $page      = $params['page'];
        $limit     = $params['limit'];
        $search    = $params['search'];

        $offset = ($page - 1) * $limit;

        $builder = $this->model->builder;

        $builder->select("trx_message_id, author.name as author, subject, body, messagedate, isread");
        $builder->join('sys_user author', 'author.sys_user_id = trx_message.author_id', 'left');
        $builder->where('recipient_id', $this->userID);

        if (!empty($search)) {
            $searchFields = ['author.name', 'subject', 'messagedate'];

            $builder->groupStart();
            foreach ($searchFields as $i => $field) {
                if ($i == 0)
                    $builder->like($field, $search);
                else
                    $builder->orLike($field, $search);
            }
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $builder->orderBy('messagedate', 'DESC');

        $data = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'data' => $data,
            'meta' => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => $total,
                'total_page' => ceil($total / $limit),
                'sort_by'    => 'messagedate'
            ]
        ];
    }

    public function updateRead(int $trx_message_id)
    {
        $sql = $this->model->where([$this->model->primaryKey => $trx_message_id, 'recipient_id' => $this->userID])->first();

        //* Throw error if message not found
        if (!$sql) throw new NotFoundException("Message not found");

        //* If Message already read then return
        if ($sql->isread == 'Y') return;

        //* Do Update isRead
        $this->entity->trx_message_id = $trx_message_id;
        $this->entity->isread = 'Y';
        $this->save();
    }

    public function destroy(int $trx_message_id)
    {
        $sql = $this->model->where([$this->model->primaryKey => $trx_message_id, 'recipient_id' => $this->userID])->first();

        //* Throw error if message not found
        if (!$sql) throw new NotFoundException("Message not found");

        //* Do Delete 
        $this->delete($trx_message_id);
    }
}
