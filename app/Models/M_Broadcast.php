<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Broadcast extends Model
{
    protected $table            = 'trx_broadcast';
    protected $primaryKey       = 'trx_broadcast_id';
    protected $allowedFields    = [
    'title',
    'message',
    'attachment',
    'attachment2',
    'attachment3',
    'created_by',
    'md_employee_id',
    'md_branch_id',
    'md_division_id',
    'effective_date',
    'is_sent',
    'sentmethod',
    'lastupdate'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Broadcast';
    protected $request;
    protected $db;
    protected $builder;

    /** Broadcast Message */
    protected $Broadcast = 'BROADCAST';

    public function __construct(RequestInterface $request = null)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect()
    {
        return "
            trx_broadcast.trx_broadcast_id,
            trx_broadcast.title AS title,
            trx_broadcast.message AS message,
            trx_broadcast.attachment,
            trx_broadcast.attachment2,
            trx_broadcast.attachment3,
            sys_user.name AS name,
            trx_broadcast.md_employee_id AS md_employee_id,
            trx_broadcast.md_branch_id AS md_branch_id,
            trx_broadcast.md_division_id AS md_division_id,
            trx_broadcast.effective_date,
            trx_broadcast.created_at,
            trx_broadcast.is_sent,
            trx_broadcast.sentmethod,
            trx_broadcast.lastupdate
        ";
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin(
                'sys_user',
                'sys_user.sys_user_id = ' . $this->table . '.created_by',
                'left'
            )
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