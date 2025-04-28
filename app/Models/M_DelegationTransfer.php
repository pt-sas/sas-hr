<?php

namespace App\Models;

use App\Controllers\Backend\EmpEducation;
use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_DelegationTransfer extends Model
{
    protected $table                = 'trx_delegation_transfer';
    protected $primaryKey           = 'trx_delegation_transfer_id';
    protected $allowedFields        = [
        'documentno',
        'employee_from',
        'employee_to',
        'md_branch_id',
        'md_division_id',
        'submissiontype',
        'submissiondate',
        'date',
        'reason',
        'docstatus',
        'isapproved',
        'approved_by',
        'receiveddate',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\DelegationTransfer';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = ['doAfterUpdate'];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_delegation_transfer.documentno',
        'ef.value',
        'et.value',
        'mb.name',
        'dv.name',
        'trx_delegation_transfer.submissiondate',
        'trx_delegation_transfer.date',
        'trx_delegation_transfer.approveddate',
        'trx_delegation_transfer.reason',
        'trx_delegation_transfer.docstatus',
        'uc.name'
    ];
    protected $column_search        = [
        'trx_delegation_transfer.documentno',
        'ef.value',
        'et.value',
        'mb.name',
        'dv.name',
        'trx_delegation_transfer.submissiondate',
        'trx_delegation_transfer.date',
        'trx_delegation_transfer.approveddate',
        'trx_delegation_transfer.reason',
        'trx_delegation_transfer.docstatus',
        'uc.name'
    ];
    protected $order                = ['trx_delegation_transfer.documentno' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Tugas Kantor */
    protected $Pengajuan_Transfer_Duta = 100027;

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
                ef.value as karyawan_from,
                et.value as karyawan_to,
                mb.name as branch,
                dv.name as division,
                uc.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee ef', 'ef.md_employee_id = ' . $this->table . '.employee_from', 'left'),
            $this->setDataJoin('md_employee et', 'et.md_employee_id = ' . $this->table . '.employee_to', 'left'),
            $this->setDataJoin('md_branch mb', 'mb.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division dv', 'dv.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user uc', 'uc.sys_user_id = ' . $this->table . '.created_by', 'left'),
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
        $mEmpDelegation = new M_EmpDelegation($this->request);
        $mTransferDetail = new M_DelegationTransferDetail($this->request);
        $mEmployee = new M_Employee($this->request);
        $changeLog = new M_ChangeLog($this->request);
        $mUser = new M_User($this->request);

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $sql = $this->find($ID);
        $line = $mTransferDetail->where($this->primaryKey, $ID)->findAll();

        if ($sql->docstatus === "CO" && !empty($line)) {
            $user_from = $mUser->where('md_employee_id', $sql->employee_from)->first();
            $user_to = $mUser->where('md_employee_id', $sql->employee_to)->first();

            foreach ($line as $value) {
                $result = false;

                $oldDelegation = $mEmpDelegation->where(['sys_user_id' => $user_from->sys_user_id, 'md_employee_id' => $value->md_employee_id])->first();
                $employee = $mEmployee->where('md_employee_id', $value->md_employee_id)->first();

                if ($oldDelegation) {
                    $mEmpDelegation->delete($oldDelegation->sys_emp_delegation_id);
                    $changeLog->insertLog($mEmpDelegation->table, 'md_employee_id', $oldDelegation->sys_emp_delegation_id, $employee->value, null, 'D', $user_from->name);
                }

                $anotherDelegation = $mEmpDelegation->where(['md_employee_id' => $value->md_employee_id])->first();
                if (!$anotherDelegation) {
                    $entity = new \App\Entities\EmpDelegation();
                    $entity->sys_user_id = $user_to->sys_user_id;
                    $entity->md_employee_id = $value->md_employee_id;
                    $result = $mEmpDelegation->save($entity);

                    $changeLog->insertLog($mEmpDelegation->table, 'md_employee_id', $mEmpDelegation->getInsertID(), null, $employee->value, 'I', $user_to->name);
                }

                $entity = new \App\Entities\DelegationTransferDetail();
                $entity->trx_delegation_transfer_detail_id = $value->trx_delegation_transfer_detail_id;
                $entity->istransfered = $result ? 'Y' : 'N';
                $mTransferDetail->save($entity);
            }
        }
    }
}