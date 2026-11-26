<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AppraisalAuditLog extends Model
{
    public const ACTION_SUBMIT_EVALUATOR     = 'SUBMIT_EVALUATOR';
    public const ACTION_REVIEW               = 'REVIEW';
    public const ACTION_HR_INTERVENTION      = 'HR_INTERVENTION';
    public const ACTION_ACKNOWLEDGE_EMPLOYEE = 'ACKNOWLEDGE_EMPLOYEE';
    public const ACTION_ACKNOWLEDGE_DUTA     = 'ACKNOWLEDGE_DUTA';
    public const ACTION_NUDGED               = 'NUDGED';

    public $table            = 'md_appraisal_audit_log';
    public $primaryKey       = 'md_appraisal_audit_log_id';
    public $useAutoIncrement = true;
    public $returnType       = \App\Entities\AppraisalAuditLog::class;
    public $allowedFields    = [
        'md_appraisal_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = false;

    protected $request;
    protected $db;
    protected $builder;

    public function __construct(?RequestInterface $request = null)
    {
        parent::__construct();
        $this->db      = db_connect();
        $this->request = $request ?? \Config\Services::request();
        $this->builder = $this->db->table($this->table);
    }

    /**
     * Record a milestone audit event for an appraisal.
     */
    public function logEvent(
        int $appraisalId,
        int $actorId,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $notes = null
    ): bool {
        return (bool) $this->insert([
            'md_appraisal_id' => $appraisalId,
            'actor_id'        => $actorId,
            'action'          => $action,
            'from_status'     => $fromStatus,
            'to_status'       => $toStatus,
            'notes'           => $notes,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
