<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AttendanceNew extends Model
{
    protected $table                = 'v_attendance';
    protected $allowedFields        = [
        'nik',
        'date',
        'clock_in',
        'clock_out'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AttendanceNew';
    protected $order                = ['date' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table('v_attendance');
    }

    public function getSelect()
    {
        $sql = '*,
                md_employee.md_employee_id,
                md_employee.fullname as fullname';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.nik = ' . $this->table . '.nik', 'left'),
            $this->setDataJoin('md_employee_branch', 'md_employee_branch.md_employee_id = md_employee.md_employee_id', 'left'),
            $this->setDataJoin('md_employee_division', 'md_employee_division.md_employee_id = md_employee.md_employee_id', 'left')
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
