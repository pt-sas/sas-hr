<?php

namespace App\Models;

use CodeIgniter\Model;

class M_AppraisalCycle extends Model
{
    public $table            = 'md_appraisal_cycle'; // Or 'tr_appraisal_cycle' depending on your DB
    public $primaryKey       = 'md_appraisal_cycle_id';
    public $useAutoIncrement = true;
    protected $returnType    = \App\Entities\AppraisalCycle::class;
    public $allowedFields    = [
        'title',
        'semester',
        'start_date',
        'end_date',
        'deadline_filling',
        'deadline_review',
        'deadline_reading',
        'deadline_override',
        'evaluator_due_date',
        'gm_due_date',
        'status',
        'isactive',
        'created_by',
        'updated_by',
    ];

    // Status Constants matching database schema VARCHAR(50)
    public const STATUS_DRAFT  = 'DRAFT';
    public const STATUS_OPEN   = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // =========================================================================
    // DATATABLES REQUIREMENTS
    // =========================================================================
    
    public $column_order  = [null, null, 'title', 'semester', 'start_date', 'deadline_filling', 'deadline_review', 'deadline_reading', 'deadline_override', 'status', null]; 
    public $column_search = ['title', 'semester', 'status'];
    public $order         = ['md_appraisal_cycle_id' => 'DESC'];

    public function getSelect()
    {
        // Select all fields from this table. 
        // If you need 'cycle_code', 'year', and 'template_title' (as referenced in your controller), 
        // you will need to add those columns to your table OR join them here.
        $sql = $this->table . '.*'; 
        
        return $sql;
    }

    public function getJoin()
    {
        // Return array of joins if needed, otherwise return empty array
        $sql = [];
        
        return $sql;
    }
}