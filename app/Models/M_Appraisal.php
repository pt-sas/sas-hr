<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

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
        'snapshot_reviewer_id',
        'template_snapshot',
        'final_score',
        'rating',
        'evaluator_comment',
        'reviewer_comment',
        'development_plan',
        'employee_comment',
        'status',
        'evaluator_submitted_at',
        'reviewer_reviewed_at',
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
    public const STATUS_PENDING_ACK         = self::STATUS_SUBMIT_REVIEW;
    public const STATUS_FINALIZED           = self::STATUS_FINAL;

    /**
     * Standardized human-readable status labels across all appraisal views.
     * Resolves from sys_reference ('AppraisalWorkflowStatus') via M_Reference,
     * falling back to the default map if not yet seeded.
     */
    public static function getStatusLabels(): array
    {
        $fallback = [
            self::STATUS_PENILAIAN        => 'Penilaian',
            self::STATUS_SUBMIT_PENILAIAN => 'Dinilai',
            self::STATUS_REVIEW           => 'Peninjauan',
            self::STATUS_SUBMIT_REVIEW    => 'Ditinjau',
            self::STATUS_FINAL            => 'Selesai',
        ];

        try {
            $refModel = new M_Reference(\Config\Services::request());
            $rows = $refModel->findBy([
                'sys_reference.name'      => 'AppraisalWorkflowStatus',
                'sys_reference.isactive'  => 'Y',
                'sys_ref_detail.isactive' => 'Y',
            ])->getResult();

            if (!empty($rows)) {
                $labels = [];
                foreach ($rows as $row) {
                    $labels[$row->value] = $row->name;
                }
                return $labels + $fallback;
            }
        } catch (\Throwable $e) {
            // Fallback to static labels if database/reference is unavailable
        }

        return $fallback;
    }

    /**
     * Get label for a given status code
     */
    public static function getStatusLabel(?string $status): string
    {
        $labels = self::getStatusLabels();
        return $labels[$status] ?? ($status ?: '-');
    }

    /**
     * Resolve cycle stage (2..6) from a joined appraisal+cycle row using date-only (Y-m-d) comparison,
     * matching AppraisalCycle::getCurrentStage() semantics.
     */
    public static function resolveCycleStageFromRow(array $row, ?string $currentDate = null): ?int
    {
        if (empty($row['start_date']) && empty($row['deadline_filling'])) {
            return null;
        }

        $today = strtotime($currentDate ?: date('Y-m-d'));
        $fill  = !empty($row['deadline_filling']) ? strtotime($row['deadline_filling']) : null;
        $rev   = !empty($row['deadline_review']) ? strtotime($row['deadline_review']) : null;
        $read  = !empty($row['deadline_reading']) ? strtotime($row['deadline_reading']) : null;
        $over  = !empty($row['deadline_override']) ? strtotime($row['deadline_override']) : null;
        $end   = !empty($row['end_date']) ? strtotime($row['end_date']) : null;

        if ($fill && $today <= $fill) {
            return 2;
        }
        if ($rev && $today <= $rev) {
            return 3;
        }
        if ($read && $today <= $read) {
            return 4;
        }
        if ($over && $today <= $over) {
            return 5;
        }
        if ($end && $today > $end) {
            return 6;
        }

        return null;
    }

    /**
     * Standardized workflow stage display label shared across Penilaian and Monitoring.
     */
    public static function getStageDisplayLabel(?string $status, ?int $cycleStage = null): string
    {
        return self::getStatusLabel($status);
    }

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $request;
    protected $db;
    protected $builder;

    public $column_order = [
        null,
        null,
        'e.fullname',
        'p.name',
        'l.name',
        'b.name',
        'd.name',
        'c.title',
        'md_appraisal.final_score',
        'md_appraisal.status',
        null,
    ];

    public $column_search = [
        'e.fullname',
        'e.nik',
        'c.title',
        'b.name',
        'd.name',
        'p.name',
        'l.name',
    ];

    /**
     * Apply global DataTable search across joined appraisal record columns,
     * localized workflow status labels, and optionally score/rating.
     */
    public static function applyDatatableSearch($builder, string $table, ?string $search, bool $includeScore = false): void
    {
        $s = trim((string) $search);
        if ($s === '') {
            return;
        }

        $matchedStatuses = [];
        foreach (self::getStatusLabels() as $code => $label) {
            if (mb_stripos((string) $label, $s, 0, 'UTF-8') !== false) {
                $matchedStatuses[] = $code;
            }
        }
        $matchedStatuses = array_values(array_unique($matchedStatuses));

        $builder->groupStart()
            ->like('e.fullname', $s)
            ->orLike('e.nik', $s)
            ->orLike('p.name', $s)
            ->orLike('l.name', $s)
            ->orLike('b.name', $s)
            ->orLike('d.name', $s)
            ->orLike('c.title', $s)
            ->orLike("{$table}.status", $s);

        if (! empty($matchedStatuses)) {
            $builder->orWhereIn("{$table}.status", $matchedStatuses);
        }

        if ($includeScore) {
            $builder->orLike("{$table}.rating", $s)
                ->orLike("CAST({$table}.final_score AS CHAR)", $s);
        }

        $builder->groupEnd();
    }

    public $order = ['md_appraisal.md_appraisal_id' => 'DESC'];

    public function __construct(?RequestInterface $request = null)
    {
        parent::__construct();
        $this->db      = db_connect();
        $this->request = $request ?? \Config\Services::request();
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect(): string
    {
        return $this->table . '.md_appraisal_id, '
            . $this->table . '.md_employee_id, '
            . $this->table . '.md_appraisal_cycle_id, '
            . $this->table . '.snapshot_evaluator_id, '
            . $this->table . '.snapshot_reviewer_id, '
            . $this->table . '.status, '
            . $this->table . '.final_score, '
            . $this->table . '.rating, '
            . $this->table . '.finalized_at, '
            . 'e.nik as nik, e.fullname as employee_name, '
            . 'd.name as division_name, p.name as position_name, l.name as level_name, b.name as branch_name, '
            . 'c.title as cycle_title, c.status as cycle_status, c.start_date, c.end_date, '
            . 'c.deadline_filling, c.deadline_review, c.deadline_reading, c.deadline_override, '
            . 'eval.fullname as evaluator_name, rev.fullname as reviewer_name';
    }

    public function getJoin(): array
    {
        return [
            $this->setDataJoin('md_employee e', 'e.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_division d', 'd.md_division_id = ' . $this->table . '.snapshot_division_id', 'left'),
            $this->setDataJoin('md_branch b', 'b.md_branch_id = ' . $this->table . '.snapshot_branch_id', 'left'),
            $this->setDataJoin('md_position p', 'p.md_position_id = ' . $this->table . '.snapshot_position_id', 'left'),
            $this->setDataJoin('md_levelling l', 'l.md_levelling_id = e.md_levelling_id', 'left'),
            $this->setDataJoin('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = ' . $this->table . '.md_appraisal_cycle_id', 'left'),
            $this->setDataJoin('md_employee eval', 'eval.md_employee_id = ' . $this->table . '.snapshot_evaluator_id', 'left'),
            $this->setDataJoin('md_employee rev', 'rev.md_employee_id = ' . $this->table . '.snapshot_reviewer_id', 'left'),
        ];
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = 'inner'): array
    {
        return [
            'tableJoin'  => $tableJoin,
            'columnJoin' => $columnJoin,
            'typeJoin'   => $typeJoin,
        ];
    }
}