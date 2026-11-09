<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalTemplateMapping extends Model
{
    public $table            = 'template_mappings';
    public $primaryKey       = 'template_mapping_id';
    public $useAutoIncrement = true;
    public $returnType       = \App\Entities\AppraisalTemplateMapping::class;
    public $allowedFields    = [
        'appraisal_template_id',
        'md_division_id',
        'md_position_id',
        'isactive',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}