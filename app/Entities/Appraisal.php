<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Models\M_Appraisal;

class Appraisal extends Entity
{
    protected $dates = [
        'created_at',
        'updated_at',
        'evaluator_submitted_at',
        'gm_reviewed_at',
        'employee_acknowledged_at',
        'finalized_at',
    ];

    protected $casts = [
        'md_appraisal_id'          => 'integer',
        'md_appraisal_cycle_id'    => 'integer',
        'md_appraisal_template_id' => 'integer',
        'md_employee_id'           => 'integer',
        'snapshot_division_id'     => 'integer',
        'snapshot_position_id'     => 'integer',
        'snapshot_branch_id'       => '?integer',
        'snapshot_evaluator_id'    => 'integer',
        'snapshot_gm_id'           => '?integer',
        'template_snapshot'        => 'json-array', // Automatically cast JSON snapshot to array
        'performance_score'        => '?float',
        'final_score'              => '?float',
        'created_by'               => 'integer',
        'updated_by'               => 'integer',
    ];

    public function canBeEditedByEvaluator(): bool
    {
        return $this->attributes['status'] === M_Appraisal::STATUS_DRAFT_EVALUATOR;
    }

    public function isFinalized(): bool
    {
        return $this->attributes['status'] === M_Appraisal::STATUS_FINALIZED;
    }

    public function getTitle(): string
    {
        return 'Penilaian #' . ($this->attributes['md_appraisal_id'] ?? '');
    }
}