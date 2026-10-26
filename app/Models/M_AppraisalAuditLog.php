<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalAuditLog extends Model
{
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
        'md_appraisal_template_item_id',
        'previous_value',
        'new_value',
        'notes',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = false;
}
