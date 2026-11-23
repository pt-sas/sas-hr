<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalTemplate extends Model
{
    // Status constants referenced by services and controllers
    public const STATUS_DRAFT     = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_ARCHIVED  = 'ARCHIVED';

    // Target group constants matching sys_reference ('AppraisalTargetGroup')
    public const TARGET_GROUP_PERMANENT = 'PERMANENT';
    public const TARGET_GROUP_OPERATOR  = 'OPERATOR';

    /**
     * Standardized human-readable target group labels.
     * Resolves from sys_reference ('AppraisalTargetGroup') via M_Reference,
     * falling back to the default map if not yet seeded.
     */
    public static function getTargetGroupLabels(): array
    {
        $fallback = [
            self::TARGET_GROUP_PERMANENT => 'Permanent',
            self::TARGET_GROUP_OPERATOR  => 'Operator',
        ];

        try {
            $refModel = new M_Reference(\Config\Services::request());
            $rows = $refModel->findBy([
                'sys_reference.name'      => 'AppraisalTargetGroup',
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
        null,
        'md_appraisal_template.template_code',
        'md_appraisal_template.version',
        'md_appraisal_template.status',
        null,
    ];

    public array $column_search = [
        'md_appraisal_template.template_code',
        'md_appraisal_template.title',
        'md_appraisal_template.version',
        'md_appraisal_template.status',
    ];

    /**
     * Apply global DataTable search across template metadata, version, localized status labels,
     * and the 4 target-scope mapping tables (Cabang, Divisi, Level, Jabatan).
     */
    public static function applyDatatableSearch($builder, $db, string $table, ?string $search): void
    {
        $s = trim((string) $search);
        if ($s === '') {
            return;
        }

        $escapedLike = $db->escapeLikeString($s);
        $versionTerm = preg_replace('/^v(?=\d)/i', '', $s);

        $statusKeywords = [
            self::STATUS_DRAFT     => ['draft'],
            self::STATUS_PUBLISHED => ['published'],
            self::STATUS_ARCHIVED  => ['archived'],
        ];
        $matchedStatuses = [];
        foreach ($statusKeywords as $code => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_stripos($kw, $s, 0, 'UTF-8') !== false || mb_stripos($s, $kw, 0, 'UTF-8') !== false) {
                    $matchedStatuses[] = $code;
                    break;
                }
            }
        }
        $matchedStatuses = array_values(array_unique($matchedStatuses));

        $scopeConfigs = [
            ['md_template_branch',    'md_branch',    'md_branch_id',    'semua cabang'],
            ['md_template_division',  'md_division',  'md_division_id',  'semua divisi'],
            ['md_template_levelling', 'md_levelling', 'md_levelling_id', 'semua level'],
            ['md_template_position',  'md_position',  'md_position_id',  'semua jabatan'],
        ];

        $builder->groupStart()
            ->like("{$table}.template_code", $s)
            ->orLike("{$table}.title", $s)
            ->orLike("{$table}.status", $s)
            ->orLike("CAST({$table}.version AS CHAR)", $versionTerm);

        if (! empty($matchedStatuses)) {
            $builder->orWhereIn("{$table}.status", $matchedStatuses);
        }

        foreach ($scopeConfigs as [$scopeTable, $masterTable, $fk, $allLabel]) {
            $builder->orWhere(
                "EXISTS (SELECT 1 FROM {$scopeTable} sx JOIN {$masterTable} sm ON sm.{$fk} = sx.{$fk} " .
                "WHERE sx.md_appraisal_template_id = {$table}.md_appraisal_template_id AND sx.isactive = 'Y' " .
                "AND sm.name LIKE '%{$escapedLike}%' ESCAPE '!')",
                null,
                false
            );

            if (mb_stripos($allLabel, $s, 0, 'UTF-8') !== false) {
                $builder->orWhere(
                    "NOT EXISTS (SELECT 1 FROM {$scopeTable} sx WHERE sx.md_appraisal_template_id = {$table}.md_appraisal_template_id AND sx.isactive = 'Y')",
                    null,
                    false
                );
            }
        }

        $builder->groupEnd();
    }

    public array $order = [
        'md_appraisal_template.md_appraisal_template_id' => 'DESC',
    ];

    /**
     * Scoping lives in 4 dedicated mapping tables (md_template_branch, md_template_division,
     * md_template_levelling, md_template_position).
     * Names are aggregated by correlated subqueries (no commas, so CI select() keeps them intact).
     */
    public function getSelect(): string
    {
        $agg = static fn(string $scopeTable, string $master, string $fk): string =>
            "(SELECT GROUP_CONCAT(m.name SEPARATOR ' / ') FROM {$scopeTable} x " .
            "JOIN {$master} m ON m.{$fk} = x.{$fk} " .
            "WHERE x.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id " .
            "AND x.isactive = 'Y')";

        return 'md_appraisal_template.*, '
            . $agg('md_template_branch', 'md_branch', 'md_branch_id') . ' AS branch_name, '
            . $agg('md_template_division', 'md_division', 'md_division_id') . ' AS division_name, '
            . $agg('md_template_levelling', 'md_levelling', 'md_levelling_id') . ' AS level_name, '
            . $agg('md_template_position', 'md_position', 'md_position_id') . ' AS position_name, '
            . '(SELECT COUNT(1) FROM md_appraisal a WHERE a.md_appraisal_template_id = md_appraisal_template.md_appraisal_template_id) AS appraisal_usage_count, '
            . "(SELECT MAX(t2.version) FROM md_appraisal_template t2 WHERE t2.template_code = md_appraisal_template.template_code AND t2.status = 'PUBLISHED' AND t2.isactive = 'Y') AS latest_published_version";
    }

    public function getJoin(): array
    {
        return [];
    }
}
