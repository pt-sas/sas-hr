<?php

namespace App\Services;

use App\Entities\AppraisalTemplate;
use App\Entities\AppraisalTemplateItem;
use App\Models\M_AppraisalTemplate;
use App\Models\M_AppraisalTemplateItem;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class AppraisalTemplateServices
{
    protected M_AppraisalTemplate $templateModel;
    protected M_AppraisalTemplateItem $itemModel;

    public function __construct()
    {
        $this->templateModel = new M_AppraisalTemplate();
        $this->itemModel     = new M_AppraisalTemplateItem();
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
     * Archive an appraisal template ("CO" -> "VO").
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

        if ($template->status === 'VO') {
            throw new Exception('Template is already archived.');
        }

        $template->status     = 'VO';
        $template->updated_by = $userId;

        return $this->templateModel->save($template);
    }
}