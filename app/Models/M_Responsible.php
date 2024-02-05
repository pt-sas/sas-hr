<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Responsible extends Model
{
    protected $table      = 'sys_wfresponsible';
    protected $primaryKey = 'sys_wfresponsible_id';
    protected $allowedFields = [
        'name',
        'description',
        'responsibletype',
        'sys_role_id',
        'sys_user_id',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Responsible';
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert      = [];
    protected $beforeUpdate     = [];
    protected $afterUpdate      = [];
    protected $beforeDelete     = [];
    protected $afterDelete      = [];
    protected $column_order     = [
        '', // Hide column
        '', // Number column
        'sys_wfresponsible.name',
        'sys_wfresponsible.description',
        'sys_ref_detail.name',
        'sys_role.name',
        'sys_user.name',
        'sys_wfresponsible.isactive'
    ];
    protected $column_search    = [
        'sys_wfresponsible.name',
        'sys_wfresponsible.description',
        'sys_ref_detail.name',
        'sys_role.name',
        'sys_user.name',
        'sys_wfresponsible.isactive'
    ];
    protected $order            = ['name' => 'ASC'];
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
            'sys_role.name as role,
        	sys_user.name as user,
            sys_ref_detail.name as res_type';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('sys_role', 'sys_role.sys_role_id = ' . $this->table . '.sys_role_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.sys_user_id', 'left'),
            $this->setDataJoin('sys_reference', 'sys_reference.name = "WF_ParticipantType"', 'left'),
            $this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.responsibletype AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left')
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

    public function getUserByResponsible($sys_wfresponsible_id)
    {
        $mUr = new M_UserRole($this->request);
        $resp = $this->find($sys_wfresponsible_id);

        if ($resp->getResponsibleType() === 'U') {
            $user_id = $resp->getUserId();
        } else if ($resp->getResponsibleType() === 'R') {
            $list = $mUr->where('sys_role_id', $resp->getRoleId())->orderBy('created_at', 'ASC')->findAll();

            foreach ($list as $key => $user) :
                if ($key == 0)
                    $user_id = $user->getUserId();
            endforeach;
        }

        return $user_id;
    }
}
