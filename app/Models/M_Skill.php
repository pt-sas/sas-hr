<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Skill extends Model
{
    protected $table            = 'md_skill';
    protected $primaryKey       = 'md_skill_id';
    protected $returnType       = 'App\Entities\Skill';
    protected $allowedFields    =
    [
        'value',
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_skill.value',
        'md_skill.name',
        'md_skill.description',
        'md_skill.isactive'
    ];
    protected $column_search = [
        'md_skill.value',
        'md_skill.name',
        'md_skill.description',
        'md_skill.isactive'
    ];
    protected $order = ['value' => 'ASC'];
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