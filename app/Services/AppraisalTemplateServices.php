<?php

namespace App\Services;

use App\Entities\AppraisalTemplate;
use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\M_AppraisalTemplate;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AppraisalTemplateServices extends BaseServices
{
    public const SCOPE_TABLES = [
        'branch_ids'    => ['table' => 'md_template_branch',    'fk' => 'md_branch_id',    'master' => 'md_branch',    'type' => 'BRANCH'],
        'division_ids'  => ['table' => 'md_template_division',  'fk' => 'md_division_id',  'master' => 'md_division',  'type' => 'DIVISION'],
        'levelling_ids' => ['table' => 'md_template_levelling', 'fk' => 'md_levelling_id', 'master' => 'md_levelling', 'type' => 'LEVELLING'],
        'position_ids'  => ['table' => 'md_template_position',  'fk' => 'md_position_id',  'master' => 'md_position',  'type' => 'POSITION'],
    ];

    protected M_AppraisalTemplate $templateModel;

    public function __construct()
    {
        parent::__construct();
        $this->templateModel = new M_AppraisalTemplate();
    }

    /**
     * Persist or update an appraisal template with its scoping, sections, and items.
     *
     * @param array $payload Validated template payload
     * @param int $userId Current user ID
     * @param int|null $templateId Optional template ID if updating existing draft/unused template
     * @return int Persisted template ID
     * @throws Exception
     */
    public function saveTemplate(array $payload, int $userId, ?int $templateId = null): int
    {
        $sections = array_values(array_filter(
            (array) ($payload['sections'] ?? []),
            static fn($s) => !empty($s['items']) && is_array($s['items'])
        ));
        $scoping = (array) ($payload['scoping'] ?? []);

        if (trim((string) ($payload['template_code'] ?? '')) === '' || trim((string) ($payload['title'] ?? '')) === '') {
            throw new ValidationException('Kode dan judul template wajib diisi.');
        }
        if (empty($sections)) {
            throw new ValidationException('Template harus memiliki minimal satu kelompok kompetensi.');
        }

        // Scoping & Section compatibility validation
        $rawLevellingIds     = $scoping['levelling_ids'] ?? $scoping['leveling_ids'] ?? $scoping['position_ids'] ?? [];
        $levellingIds        = array_unique(array_filter(array_map('intval', (array) $rawLevellingIds)));
        $operatorLevellingIds = $this->getOperatorLevellingIds();
        $hasOperator         = !empty(array_intersect($levellingIds, $operatorLevellingIds));
        $hasNonOperator      = !empty(array_diff($levellingIds, $operatorLevellingIds));

        if ($hasOperator && $hasNonOperator) {
            throw new ValidationException('Jenjang Operator tidak dapat digabungkan dengan jenjang jabatan lainnya.');
        }

        if ($hasOperator) {
            foreach ($sections as $sec) {
                $secType = $sec['section_type'] ?? '';
                if (in_array($secType, ['SCORE', 'BOOLEAN'], true)) {
                    throw new ValidationException('Template Operator hanya boleh menggunakan Single Checklist dan Weighted Checklist.');
                }
            }
        } elseif ($hasNonOperator) {
            foreach ($sections as $sec) {
                $secType = $sec['section_type'] ?? '';
                if (in_array($secType, ['SINGLE_CHECKLIST', 'WEIGHTED_CHECKLIST'], true)) {
                    throw new ValidationException('Template Staff/Supervisor hanya boleh menggunakan Checklist dan Skala.');
                }
            }
        }

        $totalScoreWeight = 0.0;
        $hasScore         = false;
        foreach ($sections as $sec) {
            foreach ($sec['items'] as $item) {
                if (($item['input_type'] ?? '') === 'SCORE') {
                    $hasScore          = true;
                    $totalScoreWeight += (float) ($item['weight'] ?? 0);
                }
            }
        }
        if ($hasScore && $totalScoreWeight > 0 && abs($totalScoreWeight - 100.0) > 0.05) {
            throw new ValidationException('Total bobot kelompok skala harus tepat 100%. Total saat ini: ' . $totalScoreWeight . '%');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1. Header persistence (with target_group & compact schema_json)
            $code        = trim((string) ($payload['template_code'] ?? ''));
            $title       = trim((string) ($payload['title'] ?? ''));
            $description = trim((string) ($payload['description'] ?? ''));
            $isactive    = (($payload['isactive'] ?? 'N') === 'Y') ? 'Y' : 'N';
            $targetGroup = $hasOperator ? 'OPERATOR' : 'PERMANENT';
            $schemaJson  = self::compactSchema($sections, $targetGroup);

            if (!empty($templateId)) {
                $existing = $this->templateModel->find($templateId);
                if (!$existing) {
                    throw new NotFoundException('Template tidak ditemukan.');
                }
                if (!$existing->isDraft() && $this->isTemplateInUse($templateId)) {
                    throw new BusinessException('Template ini sudah digunakan oleh data penilaian karyawan aktif sehingga tidak dapat diubah secara langsung. Silakan buat Versi Baru.');
                }

                $this->templateModel->update($templateId, [
                    'template_code' => $code,
                    'title'         => $title,
                    'description'   => $description,
                    'target_group'  => $targetGroup,
                    'schema_json'   => json_encode($schemaJson, JSON_UNESCAPED_UNICODE),
                    'isactive'      => $isactive,
                    'updated_by'    => $userId,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            } else {
                // Auto-increment version if template_code already exists
                $existingMax = $this->templateModel
                    ->selectMax('version')
                    ->where('template_code', $code)
                    ->first();
                $maxVer = $existingMax ? (int) $existingMax->version : 0;
                $version = ($maxVer > 0) ? ($maxVer + 1) : 1;

                $templateId = (int) $this->templateModel->insert([
                    'template_code' => $code,
                    'version'       => $version,
                    'title'         => $title,
                    'description'   => $description,
                    'target_group'  => $targetGroup,
                    'schema_json'   => json_encode($schemaJson, JSON_UNESCAPED_UNICODE),
                    'status'        => M_AppraisalTemplate::STATUS_DRAFT,
                    'isactive'      => $isactive,
                    'created_by'    => $userId,
                    'updated_by'    => $userId,
                ], true);

                if (empty($templateId)) {
                    throw new DatabaseException('Gagal membuat header template baru.');
                }
            }

            // 2. Sync scoping across the 4 mapping tables
            foreach (self::SCOPE_TABLES as $key => $cfg) {
                $db->table($cfg['table'])->where('md_appraisal_template_id', $templateId)->delete();
                $rawIds = $scoping[$key] ?? [];
                if ($key === 'levelling_ids' && empty($rawIds)) {
                    $rawIds = $scoping['leveling_ids'] ?? [];
                }
                $ids = array_unique(array_filter(array_map('intval', (array) $rawIds)));
                foreach ($ids as $scopeId) {
                    $db->table($cfg['table'])->insert([
                        'md_appraisal_template_id' => $templateId,
                        $cfg['fk']                 => $scopeId,
                        'isactive'                 => 'Y',
                        'created_by'               => $userId,
                        'updated_by'               => $userId,
                    ]);
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new DatabaseException('Gagal menyimpan template penilaian.');
            }

            $db->transCommit();

            return $templateId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Publish a draft appraisal template ("DR" -> "CO").
     */
    public function publishTemplate(int $templateId, int $userId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new NotFoundException('Appraisal template not found.');
        }

        if ($template->status !== M_AppraisalTemplate::STATUS_DRAFT) {
            throw new ValidationException('Only DRAFT templates can be published.');
        }

        // Verify template has at least one active item in schema_json
        $itemCount = 0;
        $schema    = is_string($template->schema_json)
            ? json_decode($template->schema_json, true)
            : (array) ($template->schema_json ?? []);
        foreach ($schema['sections'] ?? [] as $sec) {
            $itemCount += count($sec['items'] ?? []);
        }

        if ($itemCount === 0) {
            throw new ValidationException('Cannot publish an appraisal template without evaluation items.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $template->status       = M_AppraisalTemplate::STATUS_PUBLISHED;
            $template->published_at = date('Y-m-d H:i:s');
            $template->updated_by   = $userId;

            $this->templateModel->save($template);

            // Auto-archive previous published versions for this template_code
            $db->table('md_appraisal_template')
                ->where('template_code', $template->template_code)
                ->where('md_appraisal_template_id !=', $templateId)
                ->where('status', M_AppraisalTemplate::STATUS_PUBLISHED)
                ->update([
                    'status'     => M_AppraisalTemplate::STATUS_ARCHIVED,
                    'updated_by' => $userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if ($db->transStatus() === false) {
                $db->transRollback();
                return false;
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Check if an appraisal template has any generated appraisals.
     */
    public function isTemplateInUse(int $templateId): bool
    {
        $db = \Config\Database::connect();
        return $db->table('md_appraisal')
            ->where('md_appraisal_template_id', $templateId)
            ->countAllResults() > 0;
    }

    /**
     * Get the count of appraisals using this template.
     */
    public function getAppraisalUsageCount(int $templateId): int
    {
        $db = \Config\Database::connect();
        return $db->table('md_appraisal')
            ->where('md_appraisal_template_id', $templateId)
            ->countAllResults();
    }

    /**
     * Clone a PUBLISHED ("CO") template into a new DRAFT ("DR") version (version + 1).
     */
    public function createNewVersion(int $templateId, int $userId): AppraisalTemplate
    {
        $sourceTemplate = $this->templateModel->find($templateId);

        if (! $sourceTemplate) {
            throw new NotFoundException('Source appraisal template not found.');
        }

        if ($sourceTemplate->status !== M_AppraisalTemplate::STATUS_PUBLISHED) {
            throw new ValidationException('New versions can only be created from PUBLISHED templates.');
        }

        // Guard against multiple active drafts for the same template code
        $existingDraft = $this->templateModel
            ->where('template_code', $sourceTemplate->template_code)
            ->where('status', M_AppraisalTemplate::STATUS_DRAFT)
            ->where('isactive', 'Y')
            ->first();

        if ($existingDraft) {
            throw new BusinessException("Draft versi {$existingDraft->version} sudah ada untuk template {$existingDraft->template_code}. Selesaikan atau hapus draft tersebut terlebih dahulu.");
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Determine next version number for this template_code
            $maxVersionRow = $this->templateModel
                ->selectMax('version')
                ->where('template_code', $sourceTemplate->template_code)
                ->first();

            $nextVersion = ($maxVersionRow->version ?? $sourceTemplate->version) + 1;

            // 1. Duplicate Template Header (including target_group & compact schema_json)
            $newTemplate = new AppraisalTemplate();
            $newTemplate->template_code   = $sourceTemplate->template_code;
            $newTemplate->version         = $nextVersion;
            $newTemplate->title           = $sourceTemplate->title;
            $newTemplate->description     = $sourceTemplate->description;
            $newTemplate->target_group    = $sourceTemplate->target_group ?? 'PERMANENT';
            $newTemplate->schema_json     = $sourceTemplate->schema_json;
            $newTemplate->status          = M_AppraisalTemplate::STATUS_DRAFT;
            $newTemplate->isactive        = 'Y';
            $newTemplate->created_by      = $userId;
            $newTemplate->updated_by      = $userId;

            $newTemplateId = $this->templateModel->insert($newTemplate, true);

            // 2. Duplicate scoping mappings across the 4 tables
            foreach (self::SCOPE_TABLES as $cfg) {
                $rows = $db->table($cfg['table'])
                    ->where('md_appraisal_template_id', $templateId)
                    ->where('isactive', 'Y')
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $db->table($cfg['table'])->insert([
                        'md_appraisal_template_id' => $newTemplateId,
                        $cfg['fk']                 => (int) $row[$cfg['fk']],
                        'isactive'                 => 'Y',
                        'created_by'               => $userId,
                        'updated_by'               => $userId,
                    ]);
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new DatabaseException('Failed to clone appraisal template version.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return $this->templateModel->find($newTemplateId);
    }

    /**
     * Archive an appraisal template.
     *
     * @param int $templateId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function archiveTemplate(int $templateId, int $userId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new NotFoundException('Appraisal template not found.');
        }

        if ($template->status === M_AppraisalTemplate::STATUS_ARCHIVED) {
            throw new ValidationException('Template is already archived.');
        }

        $template->status     = M_AppraisalTemplate::STATUS_ARCHIVED;
        $template->updated_by = $userId;
        $template->updated_at = date('Y-m-d H:i:s');

        return $this->templateModel->save($template);
    }

    /**
     * Activate / Reactivate an archived template back to PUBLISHED.
     *
     * @param int $templateId
     * @param int $userId
     * @return bool
     */
    public function activateTemplate(int $templateId, int $userId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new NotFoundException('Template appraisal tidak ditemukan.');
        }

        if ($template->status === M_AppraisalTemplate::STATUS_PUBLISHED) {
            throw new ValidationException('Template sudah berstatus PUBLISHED (aktif).');
        }

        $template->status     = M_AppraisalTemplate::STATUS_PUBLISHED;
        $template->isactive   = 'Y';
        $template->updated_by = $userId;
        $template->updated_at = date('Y-m-d H:i:s');

        return $this->templateModel->save($template);
    }

    /**
     * Unpublish a PUBLISHED template back to DRAFT (Zero-Usage Undo).
     * Allowed only if no employee appraisal transactions have been generated from it.
     *
     * @param int $templateId
     * @param int $userId
     * @return bool
     */
    public function unpublishTemplate(int $templateId, int $userId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new NotFoundException('Appraisal template not found.');
        }

        if ($template->status !== M_AppraisalTemplate::STATUS_PUBLISHED) {
            throw new ValidationException('Hanya template berstatus PUBLISHED yang dapat dibatalkan publish-nya.');
        }

        $usageCount = $this->getAppraisalUsageCount($templateId);
        if ($usageCount > 0) {
            throw new BusinessException("Tidak dapat membatalkan publish: Template ini sudah digunakan oleh {$usageCount} penilaian karyawan aktif.");
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $template->status       = M_AppraisalTemplate::STATUS_DRAFT;
            $template->published_at = null;
            $template->updated_by   = $userId;

            $this->templateModel->save($template);

            // If publishing this template had auto-archived a previous version, restore the most recent one
            $lastArchived = $this->templateModel
                ->where('template_code', $template->template_code)
                ->where('md_appraisal_template_id !=', $templateId)
                ->where('status', M_AppraisalTemplate::STATUS_ARCHIVED)
                ->where('isactive', 'Y')
                ->orderBy('version', 'DESC')
                ->first();

            if ($lastArchived) {
                $lastArchived->status     = M_AppraisalTemplate::STATUS_PUBLISHED;
                $lastArchived->updated_by = $userId;
                $this->templateModel->save($lastArchived);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return false;
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Safely delete a template, scoping, sections, and items.
     * Allowed if template is DRAFT, or PUBLISHED/ARCHIVED with 0 appraisals linked.
     *
     * @param int $templateId
     * @return bool
     */
    public function deleteTemplate(int $templateId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new NotFoundException('Template appraisal tidak ditemukan.');
        }

        $usageCount = $this->getAppraisalUsageCount($templateId);
        if ($usageCount > 0) {
            throw new BusinessException("Template tidak dapat dihapus karena sudah memiliki {$usageCount} data penilaian karyawan. Silakan gunakan fungsi Arsip.");
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1. Delete scoping mappings across the 4 tables
            foreach (self::SCOPE_TABLES as $cfg) {
                $db->table($cfg['table'])->where('md_appraisal_template_id', $templateId)->delete();
            }

            // 2. Delete header
            $this->templateModel->delete($templateId, true); // true for permanent delete

            if ($db->transStatus() === false) {
                $db->transRollback();
                return false;
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Resolve Operator md_levelling_id(s) dynamically from md_levelling.
     *
     * @return int[]
     */
    protected function getOperatorLevellingIds(): array
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('md_levelling')
            ->select('md_levelling_id')
            ->where('UPPER(name)', 'OPERATOR')
            ->where('isactive', 'Y')
            ->get()
            ->getResultArray();

        $ids = array_values(array_filter(array_map('intval', array_column($rows, 'md_levelling_id'))));

        return ! empty($ids) ? $ids : [100006];
    }

    /**
     * Compact sections & items into the zero-surplus schema_json contract
     * aligned with pdf_staff.php and pdf_operator.php.
     */
    public static function compactSchema(array $sections, string $targetGroup = 'PERMANENT'): array
    {
        $compactSections = [];
        $nextSecId       = 1;
        $nextItemId      = 101;

        foreach ($sections as $sec) {
            $secType = strtoupper((string) ($sec['section_type'] ?? 'SCORE'));
            $secId   = (int) ($sec['md_appraisal_section_id'] ?? $sec['id'] ?? 0);
            if ($secId <= 0) {
                $secId = $nextSecId++;
            }

            // Auto-distribute weights if scored items sum to 0
            $rawItems = (array) ($sec['items'] ?? []);
            $scoredIndices = [];
            $sumWeights    = 0.0;
            foreach ($rawItems as $iIdx => $it) {
                $t = strtoupper((string) ($it['input_type'] ?? $secType));
                if ($t === 'SCORE' || $t === 'WEIGHTED_CHECKLIST') {
                    $scoredIndices[] = $iIdx;
                    $sumWeights     += (float) ($it['weight'] ?? $it['bobot'] ?? 0);
                }
            }
            $autoWeight = null;
            if (!empty($scoredIndices) && $sumWeights <= 0) {
                $secWeight  = (float) ($sec['weight'] ?? 0);
                $autoWeight = $secWeight > 0 ? round($secWeight / count($scoredIndices), 2) : 1.0;
            }

            $compactItems = [];
            $minScore     = null;
            $maxScore     = null;

            foreach ($rawItems as $iIdx => $it) {
                $cfg = $it['config'] ?? [];
                if (is_string($cfg)) {
                    $cfg = json_decode($cfg, true) ?: [];
                }
                $cfg = (array) $cfg;

                if ($minScore === null && isset($cfg['min_score'])) {
                    $minScore = (int) $cfg['min_score'];
                }
                if ($maxScore === null && isset($cfg['max_score'])) {
                    $maxScore = (int) $cfg['max_score'];
                }

                $itemId = (int) ($it['md_appraisal_template_item_id'] ?? $it['id'] ?? 0);
                if ($itemId <= 0) {
                    $itemId = $nextItemId++;
                }

                $w = (float) ($it['weight'] ?? $it['bobot'] ?? 0);
                if (($secType === 'SCORE' || $secType === 'WEIGHTED_CHECKLIST') && $w <= 0 && $autoWeight !== null) {
                    $w = $autoWeight;
                }
                $bobotVal = ($w == (int) $w) ? (int) $w : $w;

                $actions = $it['indikator'] ?? $cfg['key_actions'] ?? [];
                if (is_string($actions)) {
                    $actions = json_decode($actions, true) ?: array_values(array_filter(array_map('trim', explode("\n", $actions))));
                }
                $actions = is_array($actions) ? array_values(array_filter(array_map('trim', $actions))) : [];

                $desc = trim((string) ($it['desc'] ?? $it['description'] ?? $it['definition'] ?? $cfg['description'] ?? $cfg['definition'] ?? ''));

                if ($secType === 'SCORE') {
                    $entry = [
                        'id'         => $itemId,
                        'kompetensi' => trim((string) ($it['kompetensi'] ?? ($cfg['competency'] ?? ($it['title'] ?? '')))),
                        'bobot'      => $bobotVal,
                    ];
                    if ($desc !== '') {
                        $entry['desc'] = $desc;
                    }
                    if (!empty($actions)) {
                        $entry['indikator'] = $actions;
                    }
                    $compactItems[] = $entry;
                } elseif ($secType === 'BOOLEAN') {
                    $entry = [
                        'id'         => $itemId,
                        'kompetensi' => trim((string) ($it['kompetensi'] ?? ($cfg['competency'] ?? ($it['title'] ?? '')))),
                    ];
                    if ($desc !== '') {
                        $entry['desc'] = $desc;
                    }
                    if (!empty($actions)) {
                        $entry['indikator'] = $actions;
                    }
                    $compactItems[] = $entry;
                } elseif ($secType === 'WEIGHTED_CHECKLIST') {
                    $entry = [
                        'id'    => $itemId,
                        'aspek' => trim((string) ($it['aspek'] ?? ($it['title'] ?? ($cfg['aspect'] ?? '')))),
                        'bobot' => $bobotVal,
                    ];
                    if ($desc !== '') {
                        $entry['desc'] = $desc;
                    }
                    $compactItems[] = $entry;
                } else {
                    // SINGLE_CHECKLIST
                    $entry = [
                        'id'        => $itemId,
                        'parameter' => trim((string) ($it['parameter'] ?? ($it['title'] ?? ($cfg['competency'] ?? '')))),
                    ];
                    if (!empty($actions)) {
                        $entry['indikator'] = $actions;
                    }
                    $compactItems[] = $entry;
                }
            }

            $secNameMap = [
                'SCORE'              => 'PERFORMA',
                'BOOLEAN'            => 'KOMPETENSI TAMBAHAN',
                'WEIGHTED_CHECKLIST' => 'PENILAIAN KINERJA',
                'SINGLE_CHECKLIST'   => 'PENILAIAN TAMBAHAN',
            ];
            $secName = $secNameMap[$secType] ?? trim((string) ($sec['name'] ?? 'Section'));

            $secObj = [
                'id'   => $secId,
                'name' => $secName,
                'type' => $secType,
            ];
            if ($secType === 'SCORE') {
                $secObj['scale'] = [$minScore ?? 0, $maxScore ?? 4];
            } elseif ($secType === 'WEIGHTED_CHECKLIST') {
                $secObj['scale'] = [$minScore ?? 1, $maxScore ?? 3];
            }
            $secObj['items'] = $compactItems;

            $compactSections[] = $secObj;
        }

        return ['sections' => $compactSections];
    }

    /**
     * Expand compact schema_json into the legacy {template, sections, items} shape
     * consumed by AppraisalScoringServices, AppraisalEvaluation, and PDF/filling views.
     * Returns legacy snapshots unchanged for full backward compatibility.
     */
    public static function expandSchema(array $data, ?AppraisalTemplate $template = null): array
    {
        // Already in legacy flat format
        if (isset($data['items']) && is_array($data['items']) && isset($data['sections']) && !isset($data['sections'][0]['items'])) {
            return $data;
        }

        $sections = [];
        $items    = [];
        $tplId    = $template ? (int) $template->md_appraisal_template_id : (int) ($data['template']['md_appraisal_template_id'] ?? 0);

        foreach ($data['sections'] ?? [] as $sIdx => $sec) {
            $secId   = (int) ($sec['id'] ?? ($sIdx + 1));
            $secType = strtoupper((string) ($sec['type'] ?? 'SCORE'));
            $scale   = $sec['scale'] ?? ($secType === 'SCORE' ? [0, 4] : ($secType === 'WEIGHTED_CHECKLIST' ? [1, 3] : [0, 1]));
            $minS    = (int) ($scale[0] ?? 0);
            $maxS    = (int) ($scale[1] ?? ($secType === 'SCORE' ? 4 : ($secType === 'WEIGHTED_CHECKLIST' ? 3 : 1)));

            $secWeight = 0.0;
            $secItems  = (array) ($sec['items'] ?? []);

            foreach ($secItems as $iIdx => $it) {
                $itemId = (int) ($it['id'] ?? (($sIdx + 1) * 100 + $iIdx + 1));
                $bobot  = (float) ($it['bobot'] ?? 0);
                $secWeight += $bobot;

                $title = (string) ($it['kompetensi'] ?? $it['aspek'] ?? $it['parameter'] ?? '');
                $desc  = (string) ($it['desc'] ?? '');
                $actions = (array) ($it['indikator'] ?? []);

                $cfg = [
                    'min_score' => $minS,
                    'max_score' => $maxS,
                ];
                if ($secType === 'SCORE' || $secType === 'BOOLEAN') {
                    $cfg['competency'] = $title;
                    if (!empty($actions)) {
                        $cfg['key_actions'] = $actions;
                    }
                } elseif ($secType === 'WEIGHTED_CHECKLIST') {
                    $cfg['aspect'] = $title;
                    $cfg['bobot']  = $bobot;
                    if ($desc !== '') {
                        $cfg['description'] = $desc;
                    }
                } elseif ($secType === 'SINGLE_CHECKLIST' && !empty($actions)) {
                    $cfg['key_actions'] = $actions;
                }

                $items[] = [
                    'md_appraisal_template_item_id' => $itemId,
                    'md_appraisal_section_id'       => $secId,
                    'input_type'                    => $secType,
                    'title'                         => $title,
                    'description'                   => $desc,
                    'weight'                        => $bobot,
                    'sequence_order'                => $iIdx + 1,
                    'is_required'                   => 'Y',
                    'config'                        => $cfg,
                ];
            }

            $sections[] = [
                'md_appraisal_section_id'  => $secId,
                'md_appraisal_template_id' => $tplId,
                'name'                     => (string) ($sec['name'] ?? ''),
                'section_type'             => $secType,
                'weight'                   => $secWeight,
                'sequence_order'           => $sIdx + 1,
                'description'              => '',
            ];
        }

        $tplArr = $data['template'] ?? [];
        if ($template) {
            $tplArr = [
                'md_appraisal_template_id' => (int) $template->md_appraisal_template_id,
                'template_code'            => $template->template_code,
                'title'                    => $template->title,
                'version'                  => (int) $template->version,
                'status'                   => $template->status,
                'target_group'             => $template->target_group ?? 'PERMANENT',
            ];
        }

        $result = [
            'template' => $tplArr,
            'sections' => $sections,
            'items'    => $items,
        ];
        if (isset($data['config'])) {
            $result['config'] = $data['config'];
        }

        return $result;
    }
}