<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalSection extends Model
{
    protected $table            = 'md_appraisal_section';
    protected $primaryKey       = 'md_appraisal_section_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'md_appraisal_template_id',
        'name',
        'section_type',
        'weight',
        'sequence_order',
        'description',
        'isactive',
        'created_by',
        'updated_by',
    ];
}
