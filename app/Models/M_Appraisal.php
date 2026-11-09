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

    // Workflow Status Constants (Unified Option A)
    public const STATUS_PENILAIAN        = 'PENILAIAN';
    public const STATUS_SUBMIT_PENILAIAN = 'SUBMIT_PENILAIAN';
    public const STATUS_REVIEW           = 'REVIEW';
    public const STATUS_SUBMIT_REVIEW    = 'SUBMIT_REVIEW';
    public const STATUS_FINAL            = 'FINAL';

    // Backward-compatibility aliases
    public const STATUS_DRAFT_EVALUATOR     = self::STATUS_PENILAIAN;
    public const STATUS_SUBMITTED_EVALUATOR = self::STATUS_SUBMIT_PENILAIAN;
    public const STATUS_REVIEW_GM           = self::STATUS_REVIEW;
    public const STATUS_PENDING_ACK         = self::STATUS_SUBMIT_REVIEW;
    public const STATUS_FINALIZED           = self::STATUS_FINAL;

    /**
     * Standardized human-readable status labels across all appraisal views
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENILAIAN        => 'Penilaian',
            self::STATUS_SUBMIT_PENILAIAN => 'Submit Penilaian',
            self::STATUS_REVIEW           => 'Review',
            self::STATUS_SUBMIT_REVIEW    => 'Submit Review',
            self::STATUS_FINAL            => 'Final',
        ];
    }

    /**
     * Get label for a given status code
     */
    public static function getStatusLabel(?string $status): string
    {
        $labels = self::getStatusLabels();
        return $labels[$status] ?? ($status ?: '-');
    }

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}