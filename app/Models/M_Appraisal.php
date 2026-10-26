<?php

namespace App\Models;

use CodeIgniter\Model;

class M_Appraisal extends Model
{
    public $table            = 'md_appraisal';
    public $primaryKey       = 'md_appraisal_id';
    public $useAutoIncrement = true;
    public $returnType       = \App\Entities\Appraisal::class;
    public $allowedFields    = [
        'md_appraisal_cycle_id',
        'md_appraisal_template_id',
        'md_employee_id',
        'snapshot_division_id',
        'snapshot_position_id',
        'snapshot_branch_id',
        'snapshot_evaluator_id',
        'snapshot_gm_id',
        'template_snapshot',
        'performance_score',
        'final_score',
        'rating',
        'evaluator_comment',
        'gm_comment',
        'development_plan',
        'employee_comment',
        'status',
        'evaluator_submitted_at',
        'gm_reviewed_at',
        'employee_acknowledged_at',
        'finalized_at',
        'isactive',
        'created_by',
        'updated_by',
    ];

    // Workflow Status Constants
    public const STATUS_DRAFT_EVALUATOR     = 'DRAFT_EVALUATOR';
    public const STATUS_SUBMITTED_EVALUATOR = 'SUBMITTED_EVALUATOR';
    public const STATUS_REVIEW_GM           = 'REVIEW_GM';
    public const STATUS_PENDING_ACK         = 'PENDING_ACK';
    public const STATUS_FINALIZED           = 'FINALIZED';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}