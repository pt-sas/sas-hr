<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ProbationDetail extends Model
{

    protected $table            = 'trx_probation_detail';
    protected $primaryKey       = 'trx_probation_detail_id';
    protected $allowedFields    = [
        'trx_probation_id',
        'md_question_group_id',
        'no',
        'md_question_id',
        'answertype',
        'answer',
        'description',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\ProbationDetail';
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert      = [];
    protected $beforeUpdate     = [];
    protected $afterUpdate      = [];
    protected $beforeDelete     = [];
    protected $afterDelete      = [];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }
}