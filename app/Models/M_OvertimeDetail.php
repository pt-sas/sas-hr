<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_OvertimeDetail extends Model
{
    protected $table                = 'trx_overtime_detail';
    protected $primaryKey           = 'trx_overtime_detail_id';
    protected $allowedFields        = [
        'trx_overtime_id',
        'md_employee_id',
        'startdate',
        'enddate',
        'description',
        'overtime_balance',
        'overtime_expense',
        'enddate_realization',
        'total',
        'isagree',
        'created_by',
        'updated_by',
        'realization_by',
        'approve_date',
        'realization_date_superior',
        'realization_by_superior',
        'realization_date_hrd',
        'realization_by_hrd'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\OvertimeDetail';
    protected $afterUpdate = ['doAfterUpdate'];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
        $this->request = $request;
    }

    /**
     * Change value of field data
     *
     * @param $data Data
     * @return array
     */
    public function doChangeValueField($data, $id, $dataHeader): array
    {
        $mOvertime = new M_Overtime($this->request);
        $result = [];

        if (!empty($id)) {
            $dataHeader = $mOvertime->find($id);
        }

        foreach ($data as $row) :
            if (isset($dataHeader->startdate)) {
                $row->startdate = date('Y-m-d', strtotime($dataHeader->startdate)) . " " . $row->starttime;
                $row->enddate = date('Y-m-d', strtotime($dataHeader->enddate)) . " " . $row->endtime;
            }
            $result[] = $row;
        endforeach;

        return $result;
    }

    public function getRealizationOvertime($where)
    {
        $builder = $this->db->table("v_realization_overtime");

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    public function doAfterUpdate(array $rows)
    {
        $mOvertime = new M_Overtime($this->request);

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $line = $this->find($ID);
        $header = $mOvertime->find($line->trx_overtime_id);

        if ($header->ispacket == 'Y' && !empty($header->trx_bundling_id) && $line->isagree == 'Y' && !empty($line->overtime_balance)) {
            $this->db->transBegin();
            try {
                $mBundling = new M_Bundling($this->request);
                $mBundlingParticipant = new M_BundlingParticipant($this->request);
                $mBundlingEvent = new M_BundlingEvent($this->request);

                $bundling = $mBundling->find($header->trx_bundling_id);
                $participantIDs = $mBundlingParticipant->select('trx_bundling_participant_id')
                    ->where('trx_bundling_id', $header->trx_bundling_id)
                    ->findColumn('trx_bundling_participant_id');

                $totalTime = 0;

                if (!empty($participantIDs)) {
                    $result = $mBundlingEvent
                        ->selectSum('time', 'total_time_sum')
                        ->whereIn('trx_bundling_participant_id', $participantIDs)
                        ->first();

                    $totalTime = $result->total_time_sum ?? 0;
                }

                $timeLeft = max($bundling->estimate_time - $totalTime, 0);

                $insertTime = min($line->overtime_balance, $timeLeft);
                if ($timeLeft > 0 && $insertTime > 0) {
                    $bundlingParticipant = $mBundlingParticipant->where(['trx_bundling_id' => $header->trx_bundling_id, 'md_employee_id' => $line->md_employee_id])->first();

                    $entity = new \App\Entities\BundlingEvent();
                    $entity->created_by = $line->updated_by;
                    $entity->updated_by = $line->updated_by;
                    $entity->trx_overtime_detail_id = $ID;
                    $entity->trx_bundling_participant_id = $bundlingParticipant->trx_bundling_participant_id;
                    $entity->date = date('Y-m-d', strtotime($line->startdate));
                    $entity->time = $insertTime;

                    $mBundlingEvent->save($entity);
                }

                $this->db->transCommit();
            } catch (\Exception $e) {
                $this->db->transRollback();
                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }
}
