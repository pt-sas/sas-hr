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
        'target_group',
        'schema_json',
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
     * Scoping lives in md_template_scope (BRANCH, DIVISION, LEVELLING, POSITION).
     * Names are aggregated by correlated subqueries (no commas, so CI select() keeps them intact).
     */
    public function getSelect(): string
    {
        $agg = static fn(string $scopeType, string $master, string $fk): string =>
            "(SELECT GROUP_CONCAT(m.name SEPARATOR ' / ') FROM md_template_scope x " .
            "JOIN {$master} m ON m.{$fk} = x.scope_id " .
            "WHERE x.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id " .
            "AND x.scope_type = '{$scopeType}' AND x.isactive = 'Y')";

        return 'md_appraisal_template.*, '
            . $agg('BRANCH', 'md_branch', 'md_branch_id') . ' AS branch_name, '
            . $agg('DIVISION', 'md_division', 'md_division_id') . ' AS division_name, '
            . $agg('LEVELLING', 'md_levelling', 'md_levelling_id') . ' AS level_name, '
            . $agg('POSITION', 'md_position', 'md_position_id') . ' AS position_name, '
            . '(SELECT COUNT(1) FROM md_appraisal a WHERE a.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id) AS appraisal_usage_count, '
            . "(SELECT MAX(t2.version) FROM md_appraisal_template t2 WHERE t2.template_code = md_appraisal_template.template_code AND t2.status IN ('PUBLISHED', 'CO') AND t2.isactive = 'Y') AS latest_published_version";
    }

    public function getJoin(): array
    {
        return [];
    }
}
