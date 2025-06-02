<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\M_DelegationTransfer;

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

    public function getInvNumber($field, $where, $post, $created_by)
    {
        $mDelegTransfer = new M_DelegationTransfer($this->request);
        $submissionDate = new \DateTime($post['submissiondate']);
        $year  = $submissionDate->format('Y');
        $month = $submissionDate->format('m');

        $this->builder->select("MAX(CAST(REPLACE(SUBSTRING_INDEX(documentno, '/', -1), '*', '') AS UNSIGNED)) AS documentno");
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get()->getRow();

        $lastNumber = isset($sql->documentno) ? (int) $sql->documentno : 0;
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        $first = $post['necessary'];

        $inTransition = $mDelegTransfer->getInTransitionDelegation("user_to = {$created_by} AND md_employee_id = {$post['md_employee_id']}")->getRow();

        $prefix = "{$first}/{$year}/{$month}/{$nextNumber}";

        // TODO : Add prefix * to documentno when created user is in transition
        if ($inTransition) {
            $prefix .= "*";
        }

        return $prefix;
    }

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