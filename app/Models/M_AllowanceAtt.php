<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AllowanceAtt extends Model
{
    protected $table                = 'trx_allow_attendance';
    protected $primaryKey           = 'trx_allow_attendance_id';
    protected $allowedFields        = [
        'record_id',
        'transactiontype',
        'table',
        'submissiondate',
        'submissiontype',
        'md_employee_id',
        'amount',
        'description',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AllowanceAtt';
    protected $order                = ['submissiondate' => 'ASC'];
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

    public function getAllowance($where, $order = null)
    {
        $this->builder->selectSum($this->table . '.amount');
        $this->builder->select('DATE_FORMAT(' . $this->table . '.submissiondate, "%Y-%m-%d") as date,' .
            $this->table . '.md_employee_id');

        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.record_id AND table = "trx_absent"', 'left');

        if ($where)
            $this->builder->where($where);

        $this->builder->groupBy([
            'date',
            $this->table . '.md_employee_id'
        ]);

        if ($order)
            $this->builder->orderBy($order);
        else
            $this->builder->orderBy('date', 'ASC');

        return $this->builder->get();
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }

    public function insertAllowance($record_id, $table, $type, $submissiondate, $submissiontype, $md_employee_id, $amount, $updatedBy = null)
    {
        $date = date('Y-m-d', strtotime($submissiondate));

        //TODO : Don't insert Saldo if there's exists one
        if ($type === "S+" || $type === "A+") {
            $isExistsAllowance = $this->where('md_employee_id', $md_employee_id)
                ->where("DATE(submissiondate) =", $date)
                ->whereIn('transactiontype', ["S+", "A+"])
                ->first();

            if ($isExistsAllowance)
                return;
        }

        $entity = new \App\Entities\AllowanceAtt();

        if ($record_id)
            $entity->record_id = $record_id;

        $entity->table = $table;
        $entity->transactiontype = $type;
        $entity->submissiondate = $submissiondate;

        if ($submissiontype)
            $entity->submissiontype = $submissiontype;

        $entity->md_employee_id = $md_employee_id;
        $entity->amount = $amount;
        $entity->updated_by = $updatedBy ?? 100000;
        $entity->created_by = $updatedBy ?? 100000;

        $this->save($entity);
    }

    public function getTotalAmount($md_employee_id, $date)
    {
        $this->builder->select('SUM(amount) as tkh');
        $this->builder->where('md_employee_id', $md_employee_id);
        $this->builder->where('date(submissiondate) =', $date);

        $result = $this->builder->get()->getRow();

        return !is_null($result->tkh) ? $result->tkh : 0;
    }
}