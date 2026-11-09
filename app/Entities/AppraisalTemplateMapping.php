<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TemplateMapping extends Entity
{
    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'template_mapping_id'   => 'integer',
        'appraisal_template_id' => 'integer',
        'md_division_id'        => 'integer',
        'md_position_id'        => 'integer',
        'created_by'            => 'integer',
        'updated_by'            => 'integer',
    ];
}