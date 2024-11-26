<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AssignmentDate extends Model
{
    protected $table                = 'trx_assignment_date';
    protected $primaryKey           = 'trx_assignment_date_id';
    protected $allowedFields        = [
        'trx_assignment_detail_id',
        'date',
        'isagree',
        'table',
        'reference_id',
        'comment',
        'branch_in',
        'branch_out',
        'realization_in',
        'realization_out',
        'instruction_in',
        'instruction_out',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AssignmentDate';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert         = ['createAllowance'];
    protected $beforeUpdate         = [];
    protected $afterUpdate         = ['createAllowance'];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
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

    public function getDetail($field, $where)
    {
        $this->builder->select($this->table . '.*,
            trx_assignment.trx_assignment_id,
            trx_assignment.documentno');

        $this->builder->join('trx_assignment_detail', 'trx_assignment_detail.trx_assignment_detail_id = ' . $this->table . '.trx_assignment_detail_id', 'left');
        $this->builder->join('trx_assignment', 'trx_assignment.trx_assignment_id = trx_assignment_detail.trx_assignment_id', 'left');

        if (!empty($where)) {
            $this->builder->where($field, $where);
        }

        return $this->builder->get();
    }

    public function createAllowance(array $rows)
    {
        $mAssignment = new M_Assignment($this->request);
        $mAssignmentDetail = new M_AssignmentDetail($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mRuleValue = new M_RuleValue($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mHoliday = new M_Holiday($this->request);

        $amount = 0;

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $updated_by = $rows['data']['updated_by'] ?? session()->get('id');;
        $today = date('Y-m-d');
        $day = date('w');
        $entryTime = "08:00";

        $subLine = $this->find(($ID));
        $line = $mAssignmentDetail->find($subLine->{$mAssignmentDetail->primaryKey});
        $sql = $mAssignment->where($mAssignment->primaryKey, $line->{$mAssignment->primaryKey})->first();

        try {
            if ($sql->submissiontype == $mAssignment->Pengajuan_Tugas_Kantor) {
                $rule = $mRule->where([
                    'name'      => 'Tugas Kantor 1 Hari',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: $rule->value;
                }
            }

            if ($sql->submissiontype == $mAssignment->Pengajuan_Penugasan) {
                $rule = $mRule->where([
                    'name'      => 'Penugasan',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => 'Sanksi', 'isactive' => 'Y'])->first();

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $line->md_employee_id,
                        'validfrom <='      => $today
                    ])->orderBy('validfrom', 'ASC')->first();

                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $line->md_employee_id";
                    $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                    $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                    $daysOff = getDaysOff($workDetail);
                    $dateIndex = date('w', strtotime($subLine->date));

                    $holidays = $mHoliday->getHolidayDate();

                    if (in_array($dateIndex, $daysOff) || in_array(date('Y-m-d', strtotime($subLine->date)), $holidays)) {
                        $amount = $rule->condition ?: $rule->value;

                        if ($amount != 0 && $subLine->isagree === 'Y') {
                            $entity = new \App\Entities\AllowanceAtt();

                            $entity->record_id = $sql->{$mAssignment->primaryKey};
                            $entity->table = $mAssignment->table;
                            $entity->submissiontype = $sql->submissiontype;
                            $entity->submissiondate = $subLine->date;
                            $entity->md_employee_id = $line->md_employee_id;
                            $entity->amount = $amount;
                            $entity->created_by = $updated_by;
                            $entity->updated_by = $updated_by;

                            $mAllowance->save($entity);

                            $amount = 0; // Reset amount for the next iteration
                        }
                    }

                    $sanksi =  $mRuleValue->where(['md_rule_detail_id' => $ruleDetail->md_rule_detail_id])->findAll();

                    if ($sanksi && ($subLine->instruction_in === "N" && $subLine->instruction_out === "N")) {
                        $amount = $sanksi[2]->value;
                    } else if ($sanksi && $subLine->instruction_in === "N") {
                        $amount = $sanksi[0]->value;
                    } else if ($sanksi && $subLine->instruction_out === "N") {
                        $amount = $sanksi[1]->value;
                    }
                }
            }

            if ($amount != 0 && $subLine->isagree === 'Y') {
                $entity = new \App\Entities\AllowanceAtt();

                $entity->record_id = $sql->{$mAssignment->primaryKey};
                $entity->table = $mAssignment->table;
                $entity->submissiontype = $sql->submissiontype;
                $entity->submissiondate = $subLine->date;
                $entity->md_employee_id = $line->md_employee_id;
                $entity->amount = $amount;
                $entity->created_by = $updated_by;
                $entity->updated_by = $updated_by;

                $mAllowance->save($entity);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function doAfterUpdate(array $rows)
    {
        $mAssignment = new M_Assignment($this->request);
        $mAssignmentDetail = new M_AssignmentDetail($this->request);
        $entity = new \App\Entities\Assignment();

        try {
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $subLine = $this->find($ID);
            $list = $this->where(
                $mAssignmentDetail->primaryKey,
                $subLine->{$mAssignmentDetail->primaryKey}
            )->whereIn('isagree', ['Y', 'H'])->first();

            if (is_null($list)) {
                $line = $mAssignmentDetail->find($subLine->{$mAssignmentDetail->primaryKey});

                $todayTime = date('Y-m-d H:i:s');
                $updatedBy = $rows['data']['updated_by'];

                $entity->setDocStatus("CO");
                $entity->setReceivedDate($todayTime);
                $entity->setAssignmentId($line->{$mAssignment->primaryKey});
                $entity->setUpdatedBy($updatedBy);
                $mAssignment->save($entity);
            }

            if ($subLine->isagree === "Y")
                $this->createAllowance($rows);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
