<?php

namespace App\Entities;

use App\Models\M_AppraisalTemplate;
use CodeIgniter\Entity\Entity;

class AppraisalTemplate extends Entity
{
    protected $dates = [
        'created_at',
        'updated_at',
        'published_at',
    ];

    protected $casts = [
        'md_appraisal_template_id' => 'integer',
        'version'                  => 'integer',
        'config'                   => 'json',
        'created_by'               => '?integer',
        'updated_by'               => '?integer',
    ];

    /**
     * Check if the template is currently in Draft status.
     */
    public function isDraft(): bool
    {
        return ($this->attributes['status'] ?? null) === M_AppraisalTemplate::STATUS_DRAFT;
    }

    /**
     * Check if the template has been Published.
     */
    public function isPublished(): bool
    {
        return ($this->attributes['status'] ?? null) === M_AppraisalTemplate::STATUS_PUBLISHED;
    }
}