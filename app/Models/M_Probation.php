<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Probation extends Model
{

    protected $table            = 'trx_probation';
    protected $primaryKey       = 'trx_probation_id';
    protected $allowedFields    = [
        'documentno',
        'category',
        'submissiondate',
        'submissiontype',
        'md_employee_id',
        'nik',
        'md_branch_id',
        'md_division_id',
        'md_position_id',
        'registerdate',
        'notes',
        'feedback',
        'passed',
        'docstatus',
        'probation_enddate',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Probation';
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert      = [];
    protected $beforeUpdate            = [];
    protected $afterUpdate            = [];
    protected $beforeDelete            = [];
    protected $afterDelete            = [];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Monitoring Probation */
    protected $Pengajuan_Monitoring_Probation = 100020;

    /** Pengajuan Penilaian Probation */
    protected $Pengajuan_Evaluasi_Probation = 100021;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect()
    {
        $sql = $this->table .
            '.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                md_position.name as position,
                ref_cat.name as kategori,
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('md_position', 'md_position.md_position_id = ' . $this->table . '.md_position_id', 'left'),
            $this->setDataJoin('sys_reference', 'sys_reference.name = "CategoryProbation"', 'left'),
            $this->setDataJoin('sys_ref_detail ref_cat', 'ref_cat.value = ' . $this->table . '.category AND sys_reference.sys_reference_id = ref_cat.sys_reference_id', 'left'),
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

    public function doAfterUpdate(array $rows)
    {
        $entity = new \App\Entities\Probation();
        $mEmployee = new M_Employee($this->request);
        $emEntity = new \App\Entities\Employee();

        try {

            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $list = $this->find($ID);

            if ($list->isapproved === "Y" && $list->docstatus === "CO" && $list->passed === "Y") {

                $employee = $mEmployee->where([
                    'md_employee_id' => $list->md_employee_id,
                    'isactive'       => 'Y',
                    'md_status_id'   => 100002
                ])->first();

                $todayTime = date('Y-m-d H:i:s');
                $updatedBy = $rows['data']['updated_by'];

                if ($employee) {
                    $emEntity->setEmployeeId($employee->md_employee_id);
                    $emEntity->setStatusId(100001);
                    $emEntity->setUpdatedBy($updatedBy);
                    $mEmployee->save($emEntity);
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}