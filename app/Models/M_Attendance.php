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
        'checktime',
        'status',
        'verify',
        'reserved',
        'reserved2',
        'serialnumber',
        'created_by',
        'updated_by',
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
        $sql = 'v_attendance.*,
                md_employee.md_employee_id,
                md_employee.fullname';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = v_attendance.md_employee_id', 'inner'),
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

    public function getAttendance($where, $order = null)
    {
        $builder = $this->db->table("v_attendance");

        $sql = 'v_attendance.*,
        md_employee.fullname,
        DATE_FORMAT(v_attendance.date, "%w") AS day';

        $builder->select($sql);

        if ($order === 'ASC') {
            $builder->orderBy('v_attendance.date', 'ASC');
        } else if ($order === 'DESC') {
            $builder->orderBy('v_attendance.date', 'DESC');
        }

        $builder->join('md_employee', 'md_employee.nik = v_attendance.nik', 'left');

        if ($where)
            $builder->where($where);

        return $builder->get();
    }
}
