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
        'md_levelling_id',
        'md_position_id',
        'branch_to',
        'division_to',
        'levelling_to',
        'position_to',
        'date',
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

    /** Pengajuan Mutasi */
    protected $Pengajuan_Mutasi = 100016;
    /** Pengajuan Rotasi */
    protected $Pengajuan_Rotasi = 100022;
    /** Pengajuan Promosi */
    protected $Pengajuan_Promosi = 100023;
    /** Pengajuan Demosi */
    protected $Pengajuan_Demosi = 100024;

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
                md_levelling.name as level,
                md_position.name as position,
                bto.name as branch_to,
                dto.name as division_to,
                lto.name as level_to,
                pto.name as position_to,
                sys_user.name as createdby,
                md_doctype.name as formtype';

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
            $this->setDataJoin('md_levelling', 'md_levelling.md_levelling_id = ' . $this->table . '.md_levelling_id', 'left'),
            $this->setDataJoin('md_levelling lto', 'lto.md_levelling_id = ' . $this->table . '.levelling_to', 'left'),
            $this->setDataJoin('md_position', 'md_position.md_position_id = ' . $this->table . '.md_position_id', 'left'),
            $this->setDataJoin('md_position pto', 'pto.md_position_id = ' . $this->table . '.position_to', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left'),
            $this->setDataJoin('md_doctype', 'md_doctype.md_doctype_id = ' . $this->table . '.submissiontype', 'left')
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

    public function doAfterUpdate(array $rows)
    {
        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $emEntity = new \App\Entities\Employee();
        $emBranch = new \App\Entities\EmpBranch();
        $emDivision = new \App\Entities\EmpDivision();

        try {
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $row = $this->find($ID);

            if ($row->docstatus === "CO") {
                $emEntity->md_levelling_id = $row->levelling_to;
                $emEntity->md_position_id = $row->position_to;
                $emEntity->md_employee_id = $row->md_employee_id;
                $mEmployee->save($emEntity);

                //TODO: Update branch to 
                $rowBranch = $mEmpBranch->where([
                    'md_employee_id'    => $row->md_employee_id,
                    'md_branch_id'      => $row->md_branch_id
                ])->first();

                $emBranch->md_branch_id = $row->branch_to;
                $emBranch->{$mEmpBranch->primaryKey} = $rowBranch->{$mEmpBranch->primaryKey};
                $mEmpBranch->save($emBranch);

                //TODO: Update division to
                $rowDivision = $mEmpDivision->where([
                    'md_employee_id'    => $row->md_employee_id,
                    'md_division_id'    => $row->md_division_id
                ])->first();

                $emDivision->md_division_id = $row->division_to;
                $emDivision->{$mEmpDivision->primaryKey} = $rowDivision->{$mEmpDivision->primaryKey};
                $mEmpDivision->save($emDivision);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
