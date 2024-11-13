<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AttendanceMachine extends Model
{
    protected $table                = 'md_attendance_machines';
    protected $primaryKey           = 'md_attendance_machines_id';
    protected $allowedFields        = [
        'serialnumber',
        'name',
        'additional_info',
        'attlog_stamp',
        'operlog_stamp',
        'attphotolog_stamp',
        'delay',
        'error_delay',
        'trans_times',
        'trans_interval',
        'trans_flag',
        'timezone',
        'realtime',
        'encrypt',
        'server_version',
        'md_branch_id',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AttendanceMachine';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_attendance_machines.serialnumber',
        'md_attendance_machines.name',
        'md_branch.name',
        'md_attendance_machines.timezone',
        'md_attendance_machines.realtime',
        'md_attendance_machines.server_version',
        'md_attendance_machines.isactive'
    ];
    protected $column_search        = [
        'md_attendance_machines.serialnumber',
        'md_attendance_machines.name',
        'md_branch.name',
        'md_attendance_machines.timezone',
        'md_attendance_machines.realtime',
        'md_attendance_machines.server_version',
        'md_attendance_machines.isactive'
    ];
    protected $order                = ['name' => 'ASC'];
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
        $sql = $this->table . '.*,
                md_employee.fullname as leader';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.leader_id', 'left')
        ];

        return $sql;
    }

    public function firstOrCreate(array $data)
    {
        $record = $this->where($data)->first();

        if ($record) {
            return $record;
        }

        if (!isset($data['created_by']))
            $data['created_by'] = 100000;

        if (!isset($data['updated_by']))
            $data['updated_by'] = 100000;

        $this->insert($data);

        return $this->where($data)->first();
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
