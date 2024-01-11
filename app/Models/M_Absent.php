<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Absent extends Model
{
    protected $table                = 'trx_absent';
    protected $primaryKey           = 'trx_absent_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'nik',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'receiveddate',
        'necessary',
        'submissiontype',
        'startdate',
        'enddate',
        'reason',
        'docstatus',
        'image',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Absent';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $column_search        = [
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $order                = ['documentno' => 'ASC'];
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
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                sys_ref_detail.name as necessarytype,
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_reference', 'sys_reference.name = "NecessaryType"', 'left'),
            $this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.necessary AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left'),
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

    public function getInvNumber($field, $where)
    {
        $post = $this->request->getPost();

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

        $first = $post["necessary"];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }
}
