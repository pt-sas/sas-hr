<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_SubmissionCancelDetail extends Model
{
    protected $table                = 'trx_submission_cancel_detail';
    protected $primaryKey           = 'trx_submission_cancel_detail_id';
    protected $allowedFields        = [
        'trx_submission_cancel_id',
        'md_employee_id',
        "lineno",
        'date',
        'isagree',
        'reference_id',
        'ref_table',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\SubmissionCancelDetail';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
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

    function getDetail($field, $where)
    {
        $this->builder->table = $this->table;

        $this->builder->select($this->table . ".*, trx_submission_cancel.documentno,
        trx_submission_cancel.docstatus,
        trx_submission_cancel.submissiondate,
        CASE trx_submission_cancel.ref_table
        WHEN 'trx_absent' THEN trx_absent.documentno
        ELSE trx_assignment.documentno END AS ref_documentno");

        $this->builder->join('trx_submission_cancel', $this->table . '.trx_submission_cancel_id = trx_submission_cancel.trx_submission_cancel_id', 'left');
        $this->builder->join('trx_absent', 'trx_submission_cancel.reference_id = trx_absent.trx_absent_id', 'left');
        $this->builder->join('trx_assignment', 'trx_submission_cancel.reference_id = trx_assignment.trx_assignment_id', 'left');

        if ($field && !empty($where)) {
            $this->builder->where($field, $where);
        } else {
            $this->builder->where($where);
        }


        return $this->builder->get();
    }

    /**
     * Change value of field data
     *
     * @param $data Data
     * @return array
     */
    public function doChangeValueField($data, $id, $dataHeader): array
    {
        $mSubmissionCancel = new M_SubmissionCancel($this->request);
        $result = [];

        $number = 1;

        foreach ($data as $row) :
            if (property_exists($row, "lineno"))
                $row->lineno = $number;

            if (!property_exists($row, "reference_id")) {
                // if (isset($dataHeader->trx_submission_cancel_id)) {
                //     $header = $mSubmissionCancel->find($dataHeader->trx_submission_cancel_id);

                //     $where = "header_id = {$header->reference_id}";
                //     $where .= " AND submissiontype = {$header->ref_submissiontype}";
                //     $where .= " AND date = '{$row->date}'";
                //     $where .= " AND md_employee_id = {$row->md_employee_id}";
                // } else {
                $where = "header_id = {$dataHeader->reference_id}";
                $where .= " AND submissiontype = {$dataHeader->ref_submissiontype}";
                $where .= " AND date = '{$row->date}'";
                $where .= " AND md_employee_id = {$row->md_employee_id}";
                // }

                $ref = $mSubmissionCancel->getAllSubmission($where)->getRow();
                log_message('debug', json_encode($ref));

                $row->reference_id = $ref->id;
                $row->ref_table = $ref->table_detail;
            }

            $result[] = $row;
            $number++;
        endforeach;

        return $result;
    }

    public function createAllowance(array $rows)
    {
        $mSubmissionCancel = new M_SubmissionCancel($this->request);
        $mTransaction = new M_Transaction($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mWActivity = new M_WActivity($this->request);
        $mWEvent = new M_WEvent($this->request);

        $docSubmission = [
            'Sakit' => 100001, // Sakit
            'Cuti' => 100003, // Cuti
            'Ijin' => 100004, // Ijin
            'Ijin Resmi' => 100005, // Ijin Resmi
            'Tugas Kantor' => 100007, // Tugas Kantor
            'Penugasan' => 100008  // Penugasan
        ];

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $updated_by = $rows['data']['updated_by'] ?? session()->get('id');

        $line = $this->find($ID);
        $sql = $mSubmissionCancel->where($mSubmissionCancel->primaryKey, $line->{$mSubmissionCancel->primaryKey})->first();

        try {
            $year = date('Y', strtotime($line->date));

            $model = $line->ref_table === "trx_absent_detail" ? new M_AbsentDetail($this->request) : new M_AssignmentDate($this->request);

            $where = "header_id = {$sql->reference_id}";
            $where .= " AND table = '{$sql->ref_table}'";
            $where .= " AND md_employee_id = {$line->md_employee_id}";
            $where .= " AND date = '{$line->date}'";
            $where .= " AND isagree != 'C'";
            $where .= " AND (ref_id IS NULL OR ref_id = 0)";

            $refData = $mSubmissionCancel->getAllSubmission($where)->getRow();

            if ($sql->getRefSubmissionType() == $docSubmission['Cuti'] && $line->isagree === "Y") {
                $balance = $mLeaveBalance->where([
                    'year'              => date("Y", strtotime($line->date)),
                    'md_employee_id'    => $line->md_employee_id
                ])->first();

                $where = "transactiondate = '" . date('Y-m-d', strtotime($line->date)) . "'";
                $where .= " AND record_id = {$line->reference_id}";
                $where .= " AND table = '{$line->ref_table}'";
                $where .= " AND transactiontype = 'C-'";
                $leaveTransaction = $mTransaction->where($where)->first();

                $saldo = $balance->balance_amount;
                $carryOverValid = ($balance->carry_over_expiry_date && $line->date <= date('Y-m-d', strtotime($balance->carry_over_expiry_date)));
                $mainLeaveValid = ($balance->enddate && $line->date <= date('Y-m-d', strtotime($balance->enddate)));

                $dataLeaveUsage = [];
                if ($leaveTransaction && $carryOverValid) {
                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $line->md_employee_id;
                    $entityBal->carried_over_amount = $carriedOverAmt + 1;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $mLeaveBalance->save($entityBal);

                    $dataLeaveUsage[] = [
                        "record_id"         => $ID,
                        "table"             => $this->table,
                        "transactiondate"   => $line->date,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => 1,
                        "md_employee_id"    => $line->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updated_by,
                        "updated_by"        => $updated_by
                    ];
                } else if ($leaveTransaction && $mainLeaveValid) {
                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $line->md_employee_id;
                    $entityBal->balance_amount = $saldo + 1;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $mLeaveBalance->save($entityBal);

                    $dataLeaveUsage[] = [
                        "record_id"         => $ID,
                        "table"             => $this->table,
                        "transactiondate"   => $line->date,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => 1,
                        "md_employee_id"    => $line->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updated_by,
                        "updated_by"        => $updated_by
                    ];
                }

                if ($dataLeaveUsage)
                    $mTransaction->builder->insertBatch($dataLeaveUsage);
            }

            //TODO : Update Reference Data
            if ($refData) {
                $entity = $line->ref_table === "trx_absent_detail" ? new \App\Entities\AbsentDetail() : new \App\Entities\AssignmentDate();
                $entity->updated_by = $updated_by;
                $entity->{$model->primaryKey} = $refData->id;
                $entity->isagree = "C";
                $line->ref_table === "trx_absent_detail" ? $entity->ref_absent_detail_id = $ID : $entity->reference_id = $ID;
                $entity->table = $this->table;
                $model->save($entity);

                //TODO : Update wf Event
                $wfActivity = $mWActivity->where(['table' => $refData->table, 'record_id' => $refData->header_id, 'state' => 'OS'])->first();

                if ($wfActivity) {
                    //TODO : Checking if tableline is not null then update data based on record line
                    if ($wfActivity->tableline) {
                        $wfActivity = $mWActivity->where(['tableline' => $refData->table_detail, 'recordline_id' => $refData->id, 'state' => 'OS'])->first();
                    }

                    $waEntity = new \App\Entities\WEvent();
                    $waEntity->{$mWActivity->primaryKey} = $wfActivity->sys_wfactivity_id;
                    $waEntity->updated_by = $updated_by;
                    $waEntity->created_by = $wfActivity->created_by;
                    $waEntity->sys_wfscenario_id = $wfActivity->sys_wfscenario_id;
                    $waEntity->sys_wfresponsible_id = $wfActivity->sys_wfresponsible_id;
                    $waEntity->state = 'AB';

                    $mWActivity->save($waEntity);

                    // $mWEvent->setEventAudit($wfActivity->sys_wfactivity_id, $wfActivity->sys_wfresponsible_id, $updated_by, 'AB', false, $refData->table, $refData->header_id, $updated_by, false, $wfActivity->tableline, $refData->id);
                }

                //TODO : Update Reference Header Doc Status if There no Another Line To Process Approve or Realization
                $where = "header_id = {$refData->header_id}";
                $where .= " AND table = '{$refData->table}'";
                $where .= " AND isagree NOT IN ('C', 'Y')";
                $anotherLine = $mSubmissionCancel->getAllSubmission($where)->getRow();

                if (!$anotherLine) {
                    $hModel = $refData->table === "trx_absent" ? new M_Absent($this->request) : new M_Assignment($this->request);
                    $hEntity = $refData->table === "trx_absent" ? new \App\Entities\Absent() : new \App\Entities\Assignment();

                    $hEntity->updated_by = $updated_by;
                    $hEntity->docstatus = 'CO';
                    $hEntity->{$hModel->primaryKey} = $refData->header_id;

                    $hModel->save($hEntity);
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function doAfterUpdate(array $rows)
    {
        $mSubmissionCancel = new M_SubmissionCancel($this->request);
        $entity = new \App\Entities\SubmissionCancel();

        try {
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $line = $this->find($ID);
            $list = $this->where([
                $mSubmissionCancel->primaryKey => $line->{$mSubmissionCancel->primaryKey}
            ])->whereIn('isagree', ['S', 'M', 'H'])->first();

            if (is_null($list)) {
                $todayTime = date('Y-m-d H:i:s');
                $updatedBy = $rows['data']['updated_by'];

                $entity->setDocStatus("CO");
                $entity->setReceivedDate($todayTime);
                $entity->setSubmissionCancelId($line->{$mSubmissionCancel->primaryKey});
                $entity->setUpdatedBy($updatedBy);
                $mSubmissionCancel->save($entity);
            }

            if ($line->isagree === "Y")
                $this->createAllowance($rows);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}