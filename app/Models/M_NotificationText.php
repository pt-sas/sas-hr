<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_NotificationText extends Model
{
    protected $table      = 'sys_notiftext';
    protected $primaryKey = 'sys_notiftext_id';
    protected $allowedFields = [
        'name',
        'subject',
        'text',
        'text2',
        'text3',
        'notiftype',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'App\Entities\NotificationText';
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'sys_notiftext.name',
        'sys_notiftext.subject',
        'sys_notiftext.text',
        'sys_notiftext.notiftype',
        'sys_notiftext.isactive'
    ];
    protected $column_search = [
        'sys_notiftext.name',
        'sys_notiftext.subject',
        'sys_notiftext.text',
        'sys_notiftext.notiftype',
        'sys_notiftext.isactive'
    ];
    protected $order = ['name' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect()
    {
        $sql = $this->table . '.*,' .
            'sys_ref_detail.name as notif_type';

        return $sql;
    }

    public function getJoin()
    {
        //* SYS_Notification Type
        $defaultID = 5;

        $sql = [
            $this->setDataJoin('sys_ref_detail', 'sys_ref_detail.sys_reference_id = ' . $defaultID . ' AND sys_ref_detail.value = ' . $this->table . '.notiftype', 'left')
        ];

        return $sql;
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }
}
