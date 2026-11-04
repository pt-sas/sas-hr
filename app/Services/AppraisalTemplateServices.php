<?php

namespace App\Services;

use App\Entities\AppraisalTemplate;
use App\Entities\AppraisalTemplateItem;
use App\Models\M_AppraisalSection;
use App\Models\M_AppraisalTemplate;
use App\Models\M_AppraisalTemplateItem;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class AppraisalTemplateServices
{
    protected M_AppraisalTemplate $templateModel;
    protected M_AppraisalTemplateItem $itemModel;
    protected M_AppraisalSection $sectionModel;

    public function __construct()
    {
        $this->templateModel = new M_AppraisalTemplate();
        $this->itemModel     = new M_AppraisalTemplateItem();
        $this->sectionModel   = new M_AppraisalSection();
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
            throw new Exception('Kode dan judul template wajib diisi.');
        }
        if (empty($sections)) {
            throw new Exception('Template harus memiliki minimal satu kelompok kompetensi.');
        }

        // Scoping & Section compatibility validation
        $rawLevellingIds = $scoping['levelling_ids'] ?? $scoping['leveling_ids'] ?? $scoping['position_ids'] ?? [];
        $levellingIds    = array_unique(array_filter(array_map('intval', (array) $rawLevellingIds)));
        $hasOperator     = in_array(100006, $levellingIds, true);
        $hasNonOperator  = !empty(array_diff($levellingIds, [100006]));

        if ($hasOperator && $hasNonOperator) {
            throw new Exception('Jenjang Operator tidak dapat digabungkan dengan jenjang jabatan lainnya.');
        }

        if ($hasOperator) {
            foreach ($sections as $sec) {
                $secType = $sec['section_type'] ?? '';
                if (in_array($secType, ['SCORE', 'BOOLEAN'], true)) {
                    throw new Exception('Template Operator hanya boleh menggunakan Single Checklist dan Weighted Checklist.');
                }
            }
        } elseif ($hasNonOperator) {
            foreach ($sections as $sec) {
                $secType = $sec['section_type'] ?? '';
                if (in_array($secType, ['SINGLE_CHECKLIST', 'WEIGHTED_CHECKLIST'], true)) {
                    throw new Exception('Template Staff/Supervisor hanya boleh menggunakan Checklist dan Skala.');
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
            throw new Exception('Total bobot kelompok skala harus tepat 100%. Total saat ini: ' . $totalScoreWeight . '%');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Header persistence
        $code = trim((string) ($payload['template_code'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $isactive = (($payload['isactive'] ?? 'N') === 'Y') ? 'Y' : 'N';

        if (!empty($templateId)) {
            $existing = $this->templateModel->find($templateId);
            if (!$existing) {
                throw new Exception('Template tidak ditemukan.');
            }
            if (!$existing->isDraft() && $this->isTemplateInUse($templateId)) {
                throw new Exception('Template ini sudah digunakan oleh data penilaian karyawan aktif sehingga tidak dapat diubah secara langsung. Silakan buat Versi Baru.');
            }

            $this->templateModel->update($templateId, [
                'template_code' => $code,
                'title'         => $title,
                'description'   => $description,
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
                'status'        => M_AppraisalTemplate::STATUS_DRAFT,
                'isactive'      => $isactive,
                'created_by'    => $userId,
                'updated_by'    => $userId,
            ], true);

            if (empty($templateId)) {
                $db->transRollback();
                throw new Exception('Gagal membuat header template baru.');
            }
        }

        // 2. Scoping sync (replace-all)
        $maps = [
            'md_template_branch'    => ['md_branch_id',    'branch_ids'],
            'md_template_division'  => ['md_division_id',  'division_ids'],
            'md_template_levelling' => ['md_levelling_id', 'levelling_ids'],
        ];
        foreach ($maps as $table => [$col, $key]) {
            $db->table($table)->where('md_appraisal_template_id', $templateId)->delete();
            $rawIds = $scoping[$key] ?? [];
            if ($key === 'levelling_ids' && empty($rawIds)) {
                $rawIds = $scoping['leveling_ids'] ?? $scoping['position_ids'] ?? [];
            }
            $ids = array_unique(array_filter(array_map('intval', (array) $rawIds)));
            foreach ($ids as $scopeId) {
                $db->table($table)->insert([
                    'md_appraisal_template_id' => $templateId,
                    $col                       => $scopeId,
                    'created_by'               => $userId,
                    'updated_by'               => $userId,
                ]);
            }
        }

        // 3. Sections & items sync
        $keptSections = [];
        $keptItems    = [];

        foreach ($sections as $sIdx => $sec) {
            $sectionData = [
                'md_appraisal_template_id' => $templateId,
                'name'                     => trim((string) ($sec['name'] ?? 'Section')),
                'section_type'             => $sec['section_type'] ?? 'SCORE',
                'weight'                   => (float) ($sec['weight'] ?? 0),
                'sequence_order'           => (int) ($sec['sequence_order'] ?? ($sIdx + 1)),
                'description'              => $sec['description'] ?? null,
                'isactive'                 => 'Y',
                'updated_by'               => $userId,
            ];

            $sectionId = (int) ($sec['md_appraisal_section_id'] ?? 0);
            $owned     = $sectionId > 0 && $this->sectionModel->where('md_appraisal_template_id', $templateId)->find($sectionId);
            if ($owned) {
                $this->sectionModel->update($sectionId, $sectionData);
            } else {
                $sectionData['created_by'] = $userId;
                $sectionId = (int) $this->sectionModel->insert($sectionData, true);
            }
            if (!$sectionId) {
                $db->transRollback();
                throw new Exception('Gagal menyimpan section template.');
            }
            $keptSections[] = $sectionId;

            foreach ($sec['items'] as $iIdx => $item) {
                $type     = $item['input_type'] ?? ($sec['section_type'] ?? 'SCORE');
                $rawConfig  = $item['config'] ?? [];
                $itemConfig = is_string($rawConfig) ? json_decode($rawConfig, true) : (array) $rawConfig;
                $configVal  = !empty($itemConfig) ? $itemConfig : new \stdClass();

                $lineData = [
                    'md_appraisal_section_id' => $sectionId,
                    'input_type'              => $type,
                    'title'                   => trim((string) ($item['title'] ?? '')),
                    'description'             => $item['description'] ?? null,
                    'weight'                  => $type === 'BOOLEAN' ? 0 : (float) ($item['weight'] ?? 0),
                    'sequence_order'          => (int) ($item['sequence_order'] ?? ($iIdx + 1)),
                    'is_required'             => (($item['is_required'] ?? 'Y') === 'N') ? 'N' : 'Y',
                    'config'                  => json_encode($configVal, JSON_UNESCAPED_UNICODE),
                    'isactive'                => 'Y',
                    'updated_by'              => $userId,
                ];

                $itemId = (int) ($item['md_appraisal_template_item_id'] ?? 0);
                $ok     = $itemId > 0 && $db->table('md_appraisal_template_item')
                    ->where('md_appraisal_template_item_id', $itemId)
                    ->whereIn('md_appraisal_section_id', $keptSections)
                    ->countAllResults() > 0;

                if ($ok) {
                    $this->itemModel->update($itemId, $lineData);
                } else {
                    $lineData['created_by'] = $userId;
                    $itemId = (int) $this->itemModel->insert($lineData, true);
                }
                if (!$itemId) {
                    $db->transRollback();
                    throw new Exception('Gagal menyimpan item penilaian template.');
                }
                $keptItems[] = $itemId;
            }
        }

        // 4. Prune removed items & sections
        $sectionIds = array_column(
            $db->table('md_appraisal_section')->select('md_appraisal_section_id')->where('md_appraisal_template_id', $templateId)->get()->getResultArray(),
            'md_appraisal_section_id'
        );
        if (!empty($sectionIds)) {
            $b = $db->table('md_appraisal_template_item')->whereIn('md_appraisal_section_id', $sectionIds);
            if (!empty($keptItems)) {
                $b->whereNotIn('md_appraisal_template_item_id', $keptItems);
            }
            $b->delete();
        }
        $b = $db->table('md_appraisal_section')->where('md_appraisal_template_id', $templateId);
        if (!empty($keptSections)) {
            $b->whereNotIn('md_appraisal_section_id', $keptSections);
        }
        $b->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new Exception('Gagal menyimpan template penilaian.');
        }

        return $templateId;
    }

    /**
     * Publish a draft appraisal template ("DR" -> "CO").
     */
    public function publishTemplate(int $templateId, int $userId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new Exception('Appraisal template not found.');
        }

        if ($template->status !== M_AppraisalTemplate::STATUS_DRAFT) {
            throw new Exception('Only DRAFT templates can be published.');
        }

        // Verify template has at least one active item (items belong to sections)
        $itemCount = \Config\Database::connect()
            ->table('md_appraisal_template_item i')
            ->join('md_appraisal_section s', 's.md_appraisal_section_id = i.md_appraisal_section_id')
            ->where('s.md_appraisal_template_id', $templateId)
            ->where('i.isactive', 'Y')
            ->where('s.isactive', 'Y')
            ->countAllResults();

        if ($itemCount === 0) {
            throw new Exception('Cannot publish an appraisal template without evaluation items.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

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

        $db->transComplete();

        return $db->transStatus() !== false;
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
            throw new Exception('Source appraisal template not found.');
        }

        if ($sourceTemplate->status !== M_AppraisalTemplate::STATUS_PUBLISHED) {
            throw new Exception('New versions can only be created from PUBLISHED templates.');
        }

        // Guard against multiple active drafts for the same template code
        $existingDraft = $this->templateModel
            ->where('template_code', $sourceTemplate->template_code)
            ->where('status', M_AppraisalTemplate::STATUS_DRAFT)
            ->where('isactive', 'Y')
            ->first();

        if ($existingDraft) {
            throw new Exception("Draft versi {$existingDraft->version} sudah ada untuk template {$existingDraft->template_code}. Selesaikan atau hapus draft tersebut terlebih dahulu.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Determine next version number for this template_code
        $maxVersionRow = $this->templateModel
            ->selectMax('version')
            ->where('template_code', $sourceTemplate->template_code)
            ->first();

        $nextVersion = ($maxVersionRow->version ?? $sourceTemplate->version) + 1;

        // 1. Duplicate Template Header (including org structure links)
        $newTemplate = new AppraisalTemplate();
        $newTemplate->template_code   = $sourceTemplate->template_code;
        $newTemplate->version         = $nextVersion;
        $newTemplate->title           = $sourceTemplate->title;
        $newTemplate->description     = $sourceTemplate->description;
        $newTemplate->config          = $sourceTemplate->config;
        $newTemplate->status          = M_AppraisalTemplate::STATUS_DRAFT;
        $newTemplate->isactive        = 'Y';
        $newTemplate->created_by      = $userId;
        $newTemplate->updated_by      = $userId;

        $newTemplateId = $this->templateModel->insert($newTemplate, true);

        // 2. Duplicate scoping mappings
        foreach ([
            'md_template_branch'    => 'md_branch_id',
            'md_template_division'  => 'md_division_id',
            'md_template_levelling' => 'md_levelling_id',
        ] as $table => $col) {
            $rows = $db->table($table)->where('md_appraisal_template_id', $templateId)->where('isactive', 'Y')->get()->getResultArray();
            foreach ($rows as $row) {
                $db->table($table)->insert([
                    'md_appraisal_template_id' => $newTemplateId,
                    $col                       => $row[$col],
                    'created_by'               => $userId,
                    'updated_by'               => $userId,
                ]);
            }
        }

        // 3. Duplicate sections and their active items
        $sections = $db->table('md_appraisal_section')
            ->where('md_appraisal_template_id', $templateId)
            ->where('isactive', 'Y')
            ->get()->getResultArray();

        foreach ($sections as $section) {
            $oldSectionId = $section['md_appraisal_section_id'];
            unset($section['md_appraisal_section_id'], $section['created_at'], $section['updated_at']);
            $section['md_appraisal_template_id'] = $newTemplateId;
            $section['created_by']               = $userId;
            $section['updated_by']               = $userId;
            $db->table('md_appraisal_section')->insert($section);
            $newSectionId = $db->insertID();

            $items = $db->table('md_appraisal_template_item')
                ->where('md_appraisal_section_id', $oldSectionId)
                ->where('isactive', 'Y')
                ->get()->getResultArray();

            foreach ($items as $item) {
                unset($item['md_appraisal_template_item_id'], $item['created_at'], $item['updated_at']);
                $item['md_appraisal_section_id'] = $newSectionId;
                $item['created_by']              = $userId;
                $item['updated_by']              = $userId;
                $db->table('md_appraisal_template_item')->insert($item);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('Failed to clone appraisal template version.');
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
            throw new Exception('Appraisal template not found.');
        }

        if ($template->status === M_AppraisalTemplate::STATUS_ARCHIVED || $template->status === 'VO') {
            throw new Exception('Template is already archived.');
        }

        $template->status     = M_AppraisalTemplate::STATUS_ARCHIVED;
        $template->updated_by = $userId;

        return $this->templateModel->save($template);
    }

    /**
     * Unpublish a PUBLISHED template back to DRAFT (Zero-Usage Undo).
     * Allowed only if no employee appraisal transactions have been generated from it.
     *
     * @param int $templateId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function unpublishTemplate(int $templateId, int $userId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new Exception('Appraisal template not found.');
        }

        if ($template->status !== M_AppraisalTemplate::STATUS_PUBLISHED) {
            throw new Exception('Hanya template berstatus PUBLISHED yang dapat dibatalkan publish-nya.');
        }

        $usageCount = $this->getAppraisalUsageCount($templateId);
        if ($usageCount > 0) {
            throw new Exception("Tidak dapat membatalkan publish: Template ini sudah digunakan oleh {$usageCount} penilaian karyawan aktif.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $template->status       = M_AppraisalTemplate::STATUS_DRAFT;
        $template->published_at = null;
        $template->updated_by   = $userId;

        $this->templateModel->save($template);

        // If publishing this template had auto-archived a previous version, restore the most recent one
        $lastArchived = $this->templateModel
            ->where('template_code', $template->template_code)
            ->where('md_appraisal_template_id !=', $templateId)
            ->whereIn('status', [M_AppraisalTemplate::STATUS_ARCHIVED, 'VO'])
            ->where('isactive', 'Y')
            ->orderBy('version', 'DESC')
            ->first();

        if ($lastArchived) {
            $lastArchived->status     = M_AppraisalTemplate::STATUS_PUBLISHED;
            $lastArchived->updated_by = $userId;
            $this->templateModel->save($lastArchived);
        }

        $db->transComplete();

        return $db->transStatus() !== false;
    }

    /**
     * Safely delete a template, scoping, sections, and items.
     * Allowed if template is DRAFT, or PUBLISHED/ARCHIVED with 0 appraisals linked.
     *
     * @param int $templateId
     * @return bool
     * @throws Exception
     */
    public function deleteTemplate(int $templateId): bool
    {
        $template = $this->templateModel->find($templateId);

        if (! $template) {
            throw new Exception('Template appraisal tidak ditemukan.');
        }

        $usageCount = $this->getAppraisalUsageCount($templateId);
        if ($usageCount > 0) {
            throw new Exception("Template tidak dapat dihapus karena sudah memiliki {$usageCount} data penilaian karyawan. Silakan gunakan fungsi Arsip.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Delete scoping mappings
        $db->table('md_template_branch')->where('md_appraisal_template_id', $templateId)->delete();
        $db->table('md_template_division')->where('md_appraisal_template_id', $templateId)->delete();
        $db->table('md_template_levelling')->where('md_appraisal_template_id', $templateId)->delete();

        // 2. Delete items under sections
        $sections = $db->table('md_appraisal_section')
            ->select('md_appraisal_section_id')
            ->where('md_appraisal_template_id', $templateId)
            ->get()
            ->getResultArray();

        $sectionIds = array_column($sections, 'md_appraisal_section_id');
        if (! empty($sectionIds)) {
            $db->table('md_appraisal_template_item')->whereIn('md_appraisal_section_id', $sectionIds)->delete();
        }

        // 3. Delete sections
        $db->table('md_appraisal_section')->where('md_appraisal_template_id', $templateId)->delete();

        // 4. Delete header
        $this->templateModel->delete($templateId, true); // true for permanent delete

        $db->transComplete();

        return $db->transStatus() !== false;
    }
}