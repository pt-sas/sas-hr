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
        return $this->attributes['status'] === M_Appraisal::STATUS_PENILAIAN;
    }

    /**
     * Get ID of Penilai (Evaluator / direct supervisor)
     */
    public function getPenilaiId(): int
    {
        return (int) ($this->attributes['snapshot_evaluator_id'] ?? 0);
    }

    /**
     * Get ID of Peninjau (Reviewer / second-line manager)
     */
    public function getPeninjauId(): ?int
    {
        return !empty($this->attributes['snapshot_gm_id']) ? (int) $this->attributes['snapshot_gm_id'] : null;
    }

    public function isFinalized(): bool
    {
        return $this->attributes['status'] === M_Appraisal::STATUS_FINAL;
    }

    public function getTitle(): string
    {
        return 'Penilaian #' . ($this->attributes['md_appraisal_id'] ?? '');
    }
}