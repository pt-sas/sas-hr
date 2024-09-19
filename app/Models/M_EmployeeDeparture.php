<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmployeeDeparture extends Model
{

    protected $table            = 'trx_employee_departure';
    protected $primaryKey       = 'trx_employee_departure_id';
    protected $allowedFields    = [
        'md_employee_id',
        'nik',
        'documentno',
        'submissiondate',
        'submissiontype',
        'md_branch_id',
        'md_division_id',
        'md_position_id',
        'date',
        'departuretype',
        'departurerule',
        'description',
        'docstatus',
        'isapproved',
        'approveddate',
        'letterdate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\EmployeeDeparture';
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert     = [];
    protected $beforeUpdate            = [];
    protected $afterUpdate            = [];
    protected $beforeDelete            = [];
    protected $afterDelete            = [];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Resign */
    protected $Pengajuan_Resign = 100017;

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
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
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
        $mEmployee = new M_Employee($this->request);
        $entity = new \App\Entities\EmployeeDeparture();
        $emEntity = new \App\Entities\Employee();

        try {
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $list = $this->find($ID);
            $employee = $mEmployee->where([
                'md_employee_id' => $list->md_employee_id,
                'isactive'       => 'Y',
                'md_status_id <>' => 100004
            ])->first();

            if ($list->isapproved === "Y" && $employee) {
                $todayTime = date('Y-m-d H:i:s');
                $updatedBy = $rows['data']['updated_by'];

                $emEntity->setEmployeeId($employee->md_employee_id);
                $emEntity->setIsActive("N");
                $emEntity->setStatusId(100004);
                $emEntity->setResignDate($todayTime);
                $mEmployee->save($emEntity);

                $entity->setEmployeeDepartureId($list->trx_employee_departure_id);
                $entity->setDocStatus("CO");
                $entity->setApprovedDate($todayTime);
                $entity->setUpdatedBy($updatedBy);
                $this->save($entity);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function findBy($where = null, $field = null, $orderBy = [])
    {
        //* Check arg where if not null value
        if (!empty($where))
            $this->builder->where($where);

        if (!is_array($field) && !is_array($where) && !empty($field) && !empty($where))
            $this->builder->where($field, $where);

        // $this->builder->join('sys_ref_detail', 'sys_ref_detail.sys_reference_id = ' . $this->table . '.' . $this->primaryKey);

        if (is_array($orderBy) && !empty($orderBy))
            $this->builder->orderBy($orderBy['field'], $orderBy['option']);

        return $this->builder->get();
    }
}