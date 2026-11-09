<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalTemplateItem extends Model
{
    protected $table            = 'md_appraisal_template_item';
    protected $primaryKey       = 'md_appraisal_template_item_id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\AppraisalTemplateItem::class;

    protected $allowedFields = [
        'md_appraisal_section_id',
        'input_type',
        'title',
        'description',
        'weight',
        'sequence_order',
        'is_required',
        'config',
        'isactive',
        'created_by',
        'updated_by',
    ];
}
