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
        $sql = "v_attendance.*,
                md_employee.md_employee_id,
                md_employee.fullname,
                CASE
                WHEN v_attendance.clock_in >= COALESCE(ADDTIME(mwd.startwork, '00:01:00'), '08:01:00')
                THEN 'Y'
                ELSE 'N' END AS is_late,
                CASE
                WHEN v_attendance.clock_out < COALESCE(mwd.endwork, '17:00:00') THEN 'Y' 
                ELSE 'N'
                END AS is_leave_early";

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = v_attendance.md_employee_id', 'inner'),
            $this->setDataJoin('md_employee_branch', 'md_employee_branch.md_employee_id = md_employee.md_employee_id', 'left'),
            $this->setDataJoin('md_employee_division', 'md_employee_division.md_employee_id = md_employee.md_employee_id', 'left'),
            $this->setDataJoin('md_employee_work mew', 'mew.md_employee_id = v_attendance.md_employee_id AND (mew.validfrom <= v_attendance.date and mew.validto >= v_attendance.date)', 'left'),
            $this->setDataJoin('md_work_detail mwd', 'mew.md_work_id = mwd.md_work_id AND (WEEKDAY(v_attendance.date) + 1) = mwd.md_day_id', 'left'),
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
        $builder->join('md_employee_work mew', 'mew.md_employee_id = v_attendance.md_employee_id AND (mew.validfrom <= v_attendance.date and mew.validto >= v_attendance.date)', 'left');
        $builder->join('md_work_detail mwd', 'mew.md_work_id = mwd.md_work_id AND (WEEKDAY(v_attendance.date) + 1) = mwd.md_day_id', 'left');

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    public function getSelectDetail()
    {
        $sql = $this->table . '.*,
                md_employee.md_employee_id,
                md_employee.fullname';

        return $sql;
    }

    public function getJoinDetail()
    {
        $sql = [
            $this->setDataJoin('md_employee', "md_employee.nik = {$this->table}.nik", 'inner')
        ];

        return $sql;
    }

    public function getAttendanceBranch($where, $order = null)
    {
        $builder = $this->db->table("v_attendance_serialnumber");

        $sql = 'v_attendance_serialnumber.*,
        md_attendance_machines.md_branch_id';

        $builder->select($sql);

        if ($order === 'ASC') {
            $builder->orderBy('v_attendance_serialnumber.date', 'ASC');
        } else if ($order === 'DESC') {
            $builder->orderBy('v_attendance_serialnumber.date', 'DESC');
        }

        // $builder->join('md_employee', 'md_employee.nik = v_attendance.nik', 'left');
        $builder->join('md_attendance_machines', 'md_attendance_machines.serialnumber = v_attendance_serialnumber.serialnumber', 'left');

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    public function getAttBranch($where, $order = null, $sort = false)
    {
        $builder = $this->db->table("v_attendance_branch");

        $sql = 'v_attendance_branch.*';

        $builder->select($sql);

        if ($order === 'ASC') {
            $builder->orderBy('v_attendance_branch.date', 'ASC');
        } else if ($order === 'DESC') {
            $builder->orderBy('v_attendance_branch.date', 'DESC');
        }

        if ($sort) {
            $builder->orderBy('v_attendance_branch.clock_in', 'ASC');
            $builder->orderBy('v_attendance_branch.clock_out', 'DESC');
        }

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    public function getEmpCheckInEmpty($where = null)
    {
        $builder = $this->db->table("v_employee_checkin_empty");

        $sql = 'v_employee_checkin_empty.*';

        $builder->select($sql);

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    public function getEmpCheckOutEmpty($where = null)
    {
        $builder = $this->db->table("v_employee_checkout_empty");

        $sql = 'v_employee_checkout_empty.*';

        $builder->select($sql);

        if ($where)
            $builder->where($where);

        return $builder->get();
    }
}
