<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalResponse extends Model
{
    public $table            = 'md_appraisal_response';
    public $primaryKey       = 'md_appraisal_response_id';
    public $useAutoIncrement = true;
    public $returnType       = \App\Entities\AppraisalResponse::class;
    public $allowedFields    = [
        'md_appraisal_id',
        'md_appraisal_template_item_id',
        'score',
        'evaluator_response',
        'gm_response',
        'isactive',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
