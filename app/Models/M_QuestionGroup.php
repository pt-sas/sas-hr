<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_QuestionGroup extends Model
{
    protected $table                = 'md_question_group';
    protected $primaryKey           = 'md_question_group_id';
    protected $allowedFields        = [
        'value',
        'name',
        'description',
        'isactive',
        'sequence',
        'menu_url',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType = 'App\Entities\QuestionGroup';
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_question_group.value',
        'md_question_group.name',
        'md_question_group.isactive'
    ];
    protected $column_search = [
        'md_question_group.value',
        'md_question_group.name',
        'md_question_group.isactive'
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

    public function getSelect()
    {
        $sql = $this->table . '.*';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_question', 'md_question.md_question_group_id = ' . $this->table . '.md_question_group_id', 'left')
        ];

        return $sql;
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }

    public function getQuestionDetail($where)
    {
        $this->builder->select($this->table . '.*,
            md_question.md_question_id,
            md_question.no,
            md_question.question,
            md_question.answertype');

        $this->builder->join('md_question', 'md_question.md_question_group_id = ' . $this->table . '.md_question_group_id', 'left');
        $this->builder->where($where);
        $this->builder->orderBy("md_question_group.sequence", "ASC");
        $this->builder->orderBy("md_question.no", "ASC");
        return $this->builder->get();
    }
}