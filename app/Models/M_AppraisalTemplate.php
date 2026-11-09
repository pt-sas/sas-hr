<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalTemplate extends Model
{
    // Status constants referenced by services and controllers
    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_ARCHIVED  = 'ARCHIVED';

    protected $table            = 'md_appraisal_template';
    protected $primaryKey       = 'md_appraisal_template_id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\AppraisalTemplate::class;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'template_code',
        'version',
        'title',
        'description',
        'config',
        'status',
        'published_at',
        'isactive',
        'created_by',
        'updated_by',
    ];

    public array $column_order = [
        null,
        null,
        null,
        null,
        null,
        'md_appraisal_template.template_code',
        'md_appraisal_template.version',
        'md_appraisal_template.created_at',
        'md_appraisal_template.status',
        null,
    ];

    public array $column_search = [
        'md_appraisal_template.template_code',
        'md_appraisal_template.title',
    ];

    public array $order = [
        'md_appraisal_template.md_appraisal_template_id' => 'DESC',
    ];

    /**
     * Scoping lives in md_template_branch / md_template_division / md_template_levelling.
     * Names are aggregated by correlated subqueries (no commas, so CI select() keeps them intact).
     */
    public function getSelect(): string
    {
        $agg = static fn(string $map, string $master, string $fk): string =>
            "(SELECT GROUP_CONCAT(m.name SEPARATOR ' / ') FROM {$map} x " .
            "JOIN {$master} m ON m.{$fk} = x.{$fk} " .
            "WHERE x.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id AND x.isactive = 'Y')";

        return 'md_appraisal_template.*, '
            . $agg('md_template_branch', 'md_branch', 'md_branch_id') . ' AS branch_name, '
            . $agg('md_template_division', 'md_division', 'md_division_id') . ' AS division_name, '
            . $agg('md_template_levelling', 'md_levelling', 'md_levelling_id') . ' AS level_name, '
            . '(SELECT COUNT(1) FROM md_appraisal a WHERE a.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id) AS appraisal_usage_count, '
            . "(SELECT MAX(t2.version) FROM md_appraisal_template t2 WHERE t2.template_code = md_appraisal_template.template_code AND t2.status IN ('PUBLISHED', 'CO') AND t2.isactive = 'Y') AS latest_published_version";
    }

    public function getJoin(): array
    {
        return [];
    }
}
