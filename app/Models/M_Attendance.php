<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Attendance extends Model
{
    protected $table                = 'trx_attendance';
    protected $primaryKey           = 'trx_attendance_id';
    protected $allowedFields        = [
        'nik',
        'date',
        'clock_in',
        'clock_out',
        'absent',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Attendance';
    protected $order                = ['date' => 'ASC'];
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
                md_employee.fullname as fullname';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.nik = ' . $this->table . '.nik', 'left'),
            $this->setDataJoin('md_employee_branch', 'md_employee_branch.md_employee_id = md_employee.md_employee_id', 'inner'),
            $this->setDataJoin('md_employee_division', 'md_employee_division.md_employee_id = md_employee.md_employee_id', 'inner')
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

    public function getAttendance($where)
    {
        $sql = $this->table . '.*,
        md_employee.fullname';

        $this->builder->select($sql);

        $this->builder->join('md_employee', 'md_employee.nik = ' . $this->table . '.nik', 'left');

        if ($where)
            $this->builder->where($where);

        return $this->builder->get();
    }
}
