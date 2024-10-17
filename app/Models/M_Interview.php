<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Interview extends Model
{

    protected $table            = 'trx_interview';
    protected $primaryKey       = 'trx_interview_id';
    protected $allowedFields    = [
        'reference_id',
        'md_employee_id',
        'nik',
        'documentno',
        'submissiondate',
        'submissiontype',
        'md_branch_id',
        'md_division_id',
        'md_position_id',
        'terminatedate',
        'description',
        'docstatus',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Interview';
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
    protected $Pengajuan_Exit_Interview = 100019;

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
                ed.documentno as ref_docno,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                md_position.name as position,
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
            $this->setDataJoin('trx_employee_departure ed', 'ed.trx_employee_departure_id = ' . $this->table . '.reference_id', 'left'),
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
        $mResign = new M_EmployeeDeparture($this->request);
        $entity = new \App\Entities\Interview();
        $refEntity = new \App\Entities\EmployeeDeparture();
        $mEmployee = new M_Employee($this->request);
        $emEntity = new \App\Entities\Employee();
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mTransaction = new M_Transaction($this->request);

        try {

            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $list = $this->find($ID);

            if ($list->docstatus === "CO") {

                $todayTime = date('Y-m-d H:i:s');
                $updatedBy = $rows['data']['updated_by'];

                $refDoc = $mResign->find($list->reference_id);

                $leaveBalance = $mLeaveBalance->getSumBalanceAmount($list->md_employee_id, date("Y", strtotime($list->terminatedate)));


                $employee = $mEmployee->where([
                    'md_employee_id' => $list->md_employee_id,
                    'isactive'       => 'Y',
                    'md_status_id <>' => 100004
                ])->first();


                if ($employee) {
                    $emEntity->setEmployeeId($employee->md_employee_id);
                    $emEntity->setIsActive("N");
                    $emEntity->setStatusId(100004);
                    $emEntity->setResignDate($todayTime);
                    $emEntity->setUpdatedBy($updatedBy);
                    $mEmployee->save($emEntity);
                }

                // Process 0 Leave Balance
                if ($leaveBalance) {
                    $carryOverValid = ($leaveBalance->carry_over_expiry_date && date('Y-m-d', strtotime($list->terminatedate)) <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

                    $mainLeaveValid = ($leaveBalance->enddate && date('Y-m-d', strtotime($list->terminatedate)) <= date('Y-m-d', strtotime($leaveBalance->enddate)));

                    if ($carryOverValid && $leaveBalance->balance_carried > 0) {

                        $dataLeaveCarryProcessZero = [
                            "transactiondate"   => $todayTime,
                            "transactiontype"   => 'O-',
                            "year"              => $leaveBalance->year,
                            "record_id"         => $refDoc->trx_employee_departure_id,
                            "table"             => $mResign->table,
                            "amount"            => - ($leaveBalance->balance_carried),
                            "md_employee_id"    => $list->md_employee_id,
                            "isprocessed"       => "N",
                            "created_by"        => $updatedBy,
                            "updated_by"        => $updatedBy
                        ];

                        $dataCarryUpdate = [
                            "md_employee_id"        => $list->md_employee_id,
                            "year"                  => $leaveBalance->year,
                            "carried_over_amount"   => 0,
                            "updated_by"            => $updatedBy,
                            "trx_leavebalance_id"   => $leaveBalance->trx_leavebalance_id,
                        ];

                        $mTransaction->builder->insert($dataLeaveCarryProcessZero);
                        $mLeaveBalance->builder->update($dataCarryUpdate, [$mLeaveBalance->primaryKey => $leaveBalance->trx_leavebalance_id]);
                    }

                    if ($mainLeaveValid && $leaveBalance->balance > 0) {

                        $dataLeaveProcessZero = [
                            "transactiondate"   => $todayTime,
                            "transactiontype"   => 'C-',
                            "year"              => $leaveBalance->year,
                            "record_id"         => $refDoc->trx_employee_departure_id,
                            "table"             => $mResign->table,
                            "amount"            => - ($leaveBalance->balance),
                            "md_employee_id"    => $list->md_employee_id,
                            "isprocessed"       => "N",
                            "created_by"        => $updatedBy,
                            "updated_by"        => $updatedBy
                        ];

                        $dataUpdate = [
                            "md_employee_id"        => $list->md_employee_id,
                            "year"                  => $leaveBalance->year,
                            "balance_amount"        => 0,
                            "updated_by"            => $updatedBy,
                            "trx_leavebalance_id"   => $leaveBalance->trx_leavebalance_id,
                        ];

                        $mTransaction->builder->insert($dataLeaveProcessZero);
                        $mLeaveBalance->builder->update($dataUpdate, [$mLeaveBalance->primaryKey => $leaveBalance->trx_leavebalance_id]);
                    }
                }

                $refEntity->setEmployeeDepartureId($refDoc->trx_employee_departure_id);
                $refEntity->setDocStatus("CO");
                $refEntity->setApprovedDate($todayTime);
                $refEntity->setUpdatedBy($updatedBy);
                $mResign->save($refEntity);

                $entity->setInterviewId($list->trx_interview_id);
                $entity->setDocStatus("CO");
                $entity->setApprovedDate($todayTime);
                $entity->setUpdatedBy($updatedBy);
                $this->save($entity);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
