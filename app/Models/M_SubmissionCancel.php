<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_SubmissionCancel extends Model
{
    protected $table                = 'trx_submission_cancel';
    protected $primaryKey           = 'trx_submission_cancel_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'receiveddate',
        'submissiontype',
        'ref_submissiontype',
        'reason',
        'docstatus',
        'image',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
        'reference_id',
        'ref_table'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\SubmissionCancel';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_submission_cancel.documentno',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'ref_docno',
        'trx_submission_cancel.submissiondate',
        'trx_submission_cancel.receiveddate',
        'trx_submission_cancel.reason',
        'trx_submission_cancel.docstatus',
        'sys_user.name'
    ];
    protected $column_search        = [
        'trx_submission_cancel.documentno',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'ref_docno',
        'trx_submission_cancel.submissiondate',
        'trx_submission_cancel.receiveddate',
        'trx_submission_cancel.reason',
        'trx_submission_cancel.docstatus',
        'sys_user.name'
    ];
    protected $order                = ['documentno' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;
    protected $Pengajuan_Pembatalan = 100018;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect()
    {
        $sql = $this->table . ".*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                CASE WHEN trx_submission_cancel.ref_table = 'trx_absent' THEN trx_absent.documentno
                WHEN trx_submission_cancel.ref_table = 'trx_assignment' THEN trx_assignment.documentno
                END as ref_docno,
                sys_user.name as createdby,";

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left'),
            $this->setDataJoin('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.reference_id', 'left'),
            $this->setDataJoin('trx_assignment', 'trx_assignment.trx_assignment_id = ' . $this->table . '.reference_id', 'left'),
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

    // public function doAfterUpdate(array $rows)
    // {
    //     $mRule = new M_Rule($this->request);
    //     $mRuleDetail = new M_RuleDetail($this->request);
    //     $mAllowance = new M_AllowanceAtt($this->request);
    //     $mAbsentDetail = new M_AbsentDetail($this->request);
    //     $mLeaveBalance = new M_LeaveBalance($this->request);

    //     $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
    //     $sql = $this->find($ID);
    //     $line = $mAbsentDetail->where($this->primaryKey, $ID)->first();

    //     $agree = 'Y';
    //     $notAgree = 'N';
    //     $holdAgree = 'H';
    //     $rlzMgr = 'M';

    //     $formAttendance = [$this->Pengajuan_Lupa_Absen_Masuk, $this->Pengajuan_Lupa_Absen_Pulang, $this->Pengajuan_Datang_Terlambat, $this->Pengajuan_Pulang_Cepat];
    //     $isSubAttendance = in_array($sql->submissiontype, $formAttendance);

    //     $updatedBy = $rows['data']['updated_by'] ?? session()->get('id');

    //     if (($sql->getIsApproved() === 'Y' || $isSubAttendance) && ($sql->docstatus === "IP" || $sql->docstatus === "CO") && is_null($line)) {
    //         if ($sql->docstatus === "CO")
    //             $isAgree = $agree;

    //         if ($sql->docstatus === "IP") {
    //             if ($isSubAttendance) {
    //                 $isAgree = $rlzMgr;
    //             } else {
    //                 $isAgree = $holdAgree;
    //             }
    //         }

    //         $data = [
    //             'id'         => $ID,
    //             'created_by' => $updatedBy,
    //             'updated_by' => $updatedBy,
    //             'isagree'    => $isAgree
    //         ];

    //         $this->createAbsentDetail($data, $sql);
    //     }

    //     if ($sql->getIsApproved() === 'Y' && $sql->docstatus === "VO" && !is_null($line)) {
    //         $line = $mAbsentDetail->where($this->primaryKey, $ID)->findAll();

    //         $data = [];
    //         foreach ($line as $val) :
    //             $row = [];
    //             $row[$mAbsentDetail->primaryKey] = $val->{$mAbsentDetail->primaryKey};
    //             $row['isagree'] = $notAgree;
    //             $row['updated_by'] = $updatedBy;
    //             $data[] = $row;

    //             $refDetail = $mAbsentDetail->where('trx_absent_detail_id', $val->ref_absent_detail_id)->first();
    //             $whereClause = "trx_absent.trx_absent_id = " . $refDetail->trx_absent_id;
    //             $lineNo = $mAbsentDetail->getLineNo($whereClause);

    //             /**
    //              * Inserting New Absent Detail
    //              */
    //             $this->entity = new \App\Entities\AbsentDetail();
    //             $this->entity->trx_absent_id = $refDetail->trx_absent_id;
    //             $this->entity->isagree = $holdAgree;
    //             $this->entity->lineno = $lineNo;
    //             $this->entity->date = $refDetail->date;
    //             $this->entity->created_by = $updatedBy;
    //             $this->entity->updated_by = $updatedBy;
    //             $mAbsentDetail->save($this->entity);

    //             $this->entity = new \App\Entities\Absent();
    //             $this->entity->setDocStatus("IP");
    //             $this->entity->setAbsentId($refDetail->trx_absent_id);
    //             $this->entity->setUpdatedBy($updatedBy);
    //             $this->save($this->entity);
    //         endforeach;

    //         $mAbsentDetail->builder->updateBatch($data, $mAbsentDetail->primaryKey);

    //         $whereParam = [
    //             'table'             => $this->table,
    //             'md_employee_id'    => $sql->md_employee_id,
    //             'record_id'         => $ID
    //         ];

    //         $tkh = $mAllowance->where($whereParam)->findAll();

    //         $saldo_cuti = $mLeaveBalance->where($whereParam)->findAll();

    //         if ($tkh) {
    //             $arr = [];

    //             foreach ($tkh as $row) {
    //                 $arr[] = [
    //                     "record_id"         => $ID,
    //                     "table"             => $this->table,
    //                     "submissiontype"    => $row->submissiontype,
    //                     "submissiondate"    => $row->submissiondate,
    //                     "md_employee_id"    => $row->md_employee_id,
    //                     "amount"            => - ($row->amount),
    //                     "created_by"        => $updatedBy,
    //                     "updated_by"        => $updatedBy
    //                 ];
    //             }

    //             $mAllowance->builder->insertBatch($arr);
    //         }

    //         if ($saldo_cuti) {
    //             $saldo = [];

    //             foreach ($saldo_cuti as $row) {
    //                 $saldo[] = [
    //                     "record_id"         => $ID,
    //                     "table"             => $this->table,
    //                     "submissiondate"    => $row->submissiondate,
    //                     "md_employee_id"    => $row->md_employee_id,
    //                     "amount"            => abs($row->balance_amount),
    //                     "created_by"        => $updatedBy,
    //                     "updated_by"        => $updatedBy
    //                 ];
    //             }

    //             $mLeaveBalance->builder->insertBatch($saldo);
    //         }
    //     }
    // }

    public function getAllSubmission($where, $refSubCancel = null)
    {
        $builder = $this->db->table("v_all_submission");

        if ($refSubCancel) {
            $builder->select("v_all_submission.*,
            trx_submission_cancel_detail.trx_submission_cancel_detail_id,
            trx_submission_cancel_detail.isagree as ref_isagree");

            $builder->join("trx_submission_cancel_detail", "trx_submission_cancel_detail.reference_id = v_all_submission.id AND trx_submission_cancel_detail.ref_table = v_all_submission.table_detail AND trx_submission_cancel_detail.isagree != 'N'", 'left');
        }

        if ($where)
            $builder->where($where);

        return $builder->get();
    }
}