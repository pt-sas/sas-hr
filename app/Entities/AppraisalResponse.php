<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AppraisalResponse extends Entity
{
    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'md_appraisal_response_id'      => 'integer',
        'md_appraisal_id'               => 'integer',
        'md_appraisal_template_item_id' => 'integer',
        'score'                         => '?float',
        'evaluator_response'            => 'json-array',
        'gm_response'                   => 'json-array',
        'created_by'                    => 'integer',
        'updated_by'                    => 'integer',
    ];

    /**
     * Helper to retrieve value from evaluator_response payload
     */
    public function getResponseData(?string $key = null, $default = null)
    {
        $payload = is_string($this->attributes['evaluator_response'] ?? null)
            ? json_decode($this->attributes['evaluator_response'], true)
            : ($this->attributes['evaluator_response'] ?? []);

        if ($key === null) {
            return $payload;
        }

        return $payload[$key] ?? $default;
    }
}
