<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Models\AppraisalTemplateItemModel;

class AppraisalTemplateItem extends Entity
{
    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'template_item_id'      => 'integer',
        'appraisal_template_id' => 'integer',
        'min_value'             => '?float',
        'max_value'             => '?float',
        'weight'                => 'float',
        'sequence_order'        => 'integer',
        'created_by'            => 'integer',
        'updated_by'            => 'integer',
    ];

    public function getKeyActionsList(): array
    {
        if (empty($this->attributes['key_actions'])) {
            return [];
        }

        return array_values(
            array_filter(
                array_map('trim', explode("\n", $this->attributes['key_actions']))
            )
        );
    }

    public function isPerforma(): bool
    {
        return $this->attributes['category'] === AppraisalTemplateItemModel::CAT_PERFORMA;
    }

    public function isAdditionalCompetency(): bool
    {
        return $this->attributes['category'] === AppraisalTemplateItemModel::CAT_KOMPETENSI_TAMBAHAN;
    }
}