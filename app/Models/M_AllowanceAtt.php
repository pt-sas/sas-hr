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

    public function createAllowance(array $data)
    {
        $mAbsent = new M_Absent($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);

        try {
            $amount = 0;
            $ruleDetail = null;

            $array = [];
            foreach ($data as $item) {
                $row = [];

                if ($item['submissiontype'] == $mAbsent->Pengajuan_Sakit) {
                    $rule = $mRule->where([
                        'name'      => 'Sakit',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                if ($amount != 0 && $item['isagree'] === 'Y') {
                    $row['record_id'] = $item[$mAbsent->primaryKey];
                    $row['table'] = $mAbsent->table;
                    $row['submissiontype'] = $item['submissiontype'];
                    $row['submissiondate'] = $item['date'];
                    $row['md_employee_id'] = $item['md_employee_id'];
                    $row['amount'] = $amount;
                    $row['created_by'] = $item['updated_by'];
                    $row['updated_by'] = $item['updated_by'];
                    $array[] = $row;
                }
            }

            if ($array)
                $this->builder->insertBatch($array);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
