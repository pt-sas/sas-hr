<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AppraisalAuditLog extends Entity
{
    protected $dates = ['created_at'];

    protected $casts = [
        'md_appraisal_audit_log_id' => 'integer',
        'md_appraisal_id'           => 'integer',
        'actor_id'                  => 'integer',
    ];
}
