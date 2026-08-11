<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BundlingEvent extends Model
{
    protected $table                = 'trx_bundling_event';
    protected $primaryKey           = 'trx_bundling_event_id';
    protected $allowedFields        = [
        'trx_bundling_participant_id',
        'trx_overtime_detail_id',
        'date',
        'time',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\BundlingEvent';
    protected $afterInsert          = ['doAfterInsert'];
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

    public function doAfterInsert(array $rows)
    {
        $mBundling = new M_Bundling($this->request);
        $mBundlingParticipant = new M_BundlingParticipant($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        try {
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
            $subLine = $this->find($ID);
            $line = $mBundlingParticipant->find($subLine->trx_bundling_participant_id);
            $bundling = $mBundling->find($line->trx_bundling_id);

            $sessionUser = session()->get('sys_user_id');
            $todayTime = date('Y-m-d H:i:s');
            $updatedBy = !empty($sessionUser) ? $sessionUser : 100000;

            $data = [
                'updated_by' => $updatedBy,
                'updated_at' => $todayTime,
                'total_time' => $line->total_time + $subLine->time
            ];

            $mBundlingParticipant->builder->update($data, [$mBundlingParticipant->primaryKey => $line->trx_bundling_participant_id]);

            $participantIDs = $mBundlingParticipant->select('trx_bundling_participant_id')
                ->where('trx_bundling_id', $bundling->trx_bundling_id)
                ->findColumn('trx_bundling_participant_id');

            $totalTime = 0;
            if (!empty($participantIDs)) {
                $result = $this
                    ->selectSum('time', 'total_time_sum')
                    ->whereIn('trx_bundling_participant_id', $participantIDs)
                    ->first();

                $totalTime = $result->total_time_sum ?? 0;
            }

            $timeLeft = $bundling->estimate_time - $totalTime;

            if ($timeLeft <= 0) {
                $rule = $mRule->where(['name' => 'Paket', 'isactive' => 'Y'])->first();
                $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => $bundling->bundling_type])->first();

                $bundlingAmount = $ruleDetail->value;

                if ($ruleDetail->name == 'Paket Closing') {
                } else {
                    $amountRate = $bundlingAmount / $bundling->estimate_time;

                    foreach ($participantIDs as $participantID) {
                        $totalAmount = 0;

                        $bundlingEvent = $this->where('trx_bundling_participant_id', $participantID)->findAll();

                        foreach ($bundlingEvent as $event) {
                            $amount = $event->time * $amountRate;
                            $data = [
                                'updated_by' => $updatedBy,
                                'updated_at' => $todayTime,
                                // 'amount' => $amount
                            ];

                            $this->builder->update($data, [$this->primaryKey => $event->{$this->primaryKey}]);

                            $totalAmount += $amount;
                        }

                        $data = [
                            'updated_by' => $updatedBy,
                            'updated_at' => $todayTime,
                            'total_amount' => $totalAmount
                        ];

                        $mBundlingParticipant->builder->update($data, [$mBundlingParticipant->primaryKey => $participantID]);
                    }

                    $data = [
                        'updated_by' => $updatedBy,
                        'updated_at' => $todayTime,
                        'docstatus' => 'CO'
                    ];

                    $mBundling->builder->update($data, [$mBundling->primaryKey => $bundling->trx_bundling_id]);
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
