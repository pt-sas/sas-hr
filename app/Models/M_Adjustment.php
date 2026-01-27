<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Adjustment extends Model
{
    protected $table                = 'trx_adjustment';
    protected $primaryKey           = 'trx_adjustment_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'submissiontype',
        'submissiondate',
        'begin_balance',
        'adjustment',
        'ending_balance',
        'date',
        'md_year_id',
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
    protected $returnType           = 'App\Entities\Adjustment';
    protected $order                = ['submissiondate' => 'DESC', 'documentno' => 'DESC'];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_adjustment.documentno',
        'trx_adjustment.docstatus',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_adjustment.submissiondate',
        'trx_adjustment.date',
        'trx_adjustment.reason',
        'sys_user.name'
    ];
    protected $column_search        = [
        'trx_adjustment.documentno',
        'trx_adjustment.docstatus',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_adjustment.submissiondate',
        'trx_adjustment.date',
        'trx_adjustment.reason',
        'sys_user.name'
    ];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Adjustment Cuti */
    protected $Pengajuan_Adj_Cuti = 100029;

    /** Pengajuan Adjustment TKH */
    protected $Pengajuan_Adj_TKH = 100030;

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
                md_employee.nik as nik,
                md_branch.name as branch,
                sys_user.name as createdby,
                md_division.name as division';

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
        $first = $post["necessary"];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function doAfterUpdate(array $rows)
    {
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mTransaction = new M_Transaction($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mYear = new M_Year($this->request);

        $ID = $rows['id'][0] ?? $rows['id'];

        $sql = $this->find($ID);
        $updatedBy = $rows['data']['updated_by'] ?? session()->get('id');

        if (
            $sql->getIsApproved() === 'Y' && $sql->docstatus === "CO"
        ) {
            if ($sql->submissiontype == $this->Pengajuan_Adj_Cuti) {
                $year = $mYear->find($sql->md_year_id);
                $leaveBalance = $mLeaveBalance->where(['md_employee_id' => $sql->md_employee_id, 'year' => $year->year])->first();

                $entityBal = new \App\Entities\LeaveBalance();
                $entityBal->md_employee_id = $sql->md_employee_id;
                $entityBal->updated_by = $updatedBy;

                if ($leaveBalance) {
                    $entityBal->trx_leavebalance_id = $leaveBalance->trx_leavebalance_id;
                    $entityBal->balance_amount = $leaveBalance->balance_amount + $sql->adjustment;
                } else {
                    $entityBal->submissiondate = date('Y-m-d');
                    $entityBal->annual_allocation = 0;
                    $entityBal->balance_amount = $sql->adjustment;
                    $entityBal->year = $year->year;
                    $entityBal->startdate = $sql->date;
                    $entityBal->enddate = $year->year . '-12-31';
                    $entityBal->carried_over_amount = 0;
                    $entityBal->carry_over_expiry_date = null;
                    $entityBal->created_by = $updatedBy;
                }

                if ($mLeaveBalance->save($entityBal)) {
                    $dataLeaveUsage = [
                        "record_id"         => $sql->{$this->primaryKey},
                        "table"             => $this->table,
                        "transactiondate"   => $sql->date,
                        "transactiontype"   => $sql->adjustment < 0 ? 'P-' : 'P+',
                        "year"              => $year->year,
                        "amount"            => $sql->adjustment,
                        "reserved_amount"   => 0,
                        "md_employee_id"    => $sql->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updatedBy,
                        "updated_by"        => $updatedBy
                    ];

                    $mTransaction->builder->insert($dataLeaveUsage);
                }
            } else {
                $tkh = $mAllowance->getTotalAmount($sql->md_employee_id, date("Y-m-d", strtotime($sql->date)));
                $amount = $sql->ending_balance - $tkh;

                if ($amount != 0) {
                    $transactiontype = $amount < 0 ? 'A-' : 'A+';
                    $mAllowance->insertAllowance($sql->{$this->primaryKey}, $this->table, $transactiontype, $sql->date, $sql->submissiontype, $sql->md_employee_id, $amount, $updatedBy);
                }
            }
        }
    }
}
