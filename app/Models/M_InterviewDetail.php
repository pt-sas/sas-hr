<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_InterviewDetail extends Model
{

    protected $table            = 'trx_interview_detail';
    protected $primaryKey       = 'trx_interview_detail_id';
    protected $allowedFields    = [
        'trx_interview_id',
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
    protected $returnType       = 'App\Entities\InterviewDetail';
    protected $allowCallbacks   = true;
    protected $beforeInsert     = [];
    protected $afterInsert     = [];
    protected $beforeUpdate            = [];
    protected $afterUpdate            = [];
    protected $beforeDelete            = [];
    protected $afterDelete            = [];
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
