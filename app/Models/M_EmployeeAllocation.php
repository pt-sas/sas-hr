<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmployeeAllocation extends Model
{

    protected $table            = 'trx_employee_allocation';
    protected $primaryKey       = 'trx_employee_allocation_id';
    protected $allowedFields    = [
        'md_employee_id',
        'nik',
        'documentno',
        'submissiondate',
        'submissiontype',
        'md_branch_id',
        'md_division_id',
        'branch_to',
        'division_to',
        'startdate',
        'enddate',
        'description',
        'docstatus',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\EmployeeAllocation';
    protected $allowCallbacks   = true;
    protected $beforeInsert            = [];
    protected $afterInsert            = [];
    protected $beforeUpdate            = [];
    protected $afterUpdate            = [];
    protected $beforeDelete            = [];
    protected $afterDelete            = [];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Perbantuan */
    protected $Pengajuan_Perbantuan = 100016;

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
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                bto.name as branch_to,
                dto.name as division_to,
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_branch bto', 'bto.md_branch_id = ' . $this->table . '.branch_to', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('md_division dto', 'dto.md_division_id = ' . $this->table . '.division_to', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left')
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

    public function getInvNumber($field, $where, $post)
    {
        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get();

        $code = "";
        if ($sql->getNumRows() > 0) {
            foreach ($sql->getResult() as $row) {
                $doc = ((int)$row->documentno + 1);
                $code = sprintf("%04s", $doc);
            }
        } else {
            $code = "0001";
        }
        $first = $post['necessary'];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }
}