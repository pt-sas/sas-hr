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
     * Helper to retrieve value from Penilai (evaluator_response) payload
     */
    public function getPenilaiResponse(?string $key = null, $default = null)
    {
        $payload = is_string($this->attributes['evaluator_response'] ?? null)
            ? json_decode($this->attributes['evaluator_response'], true)
            : ($this->attributes['evaluator_response'] ?? []);

        if ($key === null) {
            return $payload;
        }

        return $payload[$key] ?? $default;
    }

    /**
     * Helper to retrieve value from Peninjau (gm_response) payload
     */
    public function getPeninjauResponse(?string $key = null, $default = null)
    {
        $payload = is_string($this->attributes['gm_response'] ?? null)
            ? json_decode($this->attributes['gm_response'], true)
            : ($this->attributes['gm_response'] ?? []);

        if ($key === null) {
            return $payload;
        }

        return $payload[$key] ?? $default;
    }

    /**
     * Alias for getPenilaiResponse
     */
    public function getResponseData(?string $key = null, $default = null)
    {
        return $this->getPenilaiResponse($key, $default);
    }
}
