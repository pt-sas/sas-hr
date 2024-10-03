<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Question extends Model
{
	protected $table                = 'md_question';
	protected $primaryKey           = 'md_question_id';
	protected $allowedFields        = [
		'md_question_group_id',
		'no',
		'question',
		'answertype',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\Question';
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

	public function getQuestion($where)
	{
		$this->builder->select($this->table . '.*,
            md_question_group.md_question_group_id,
			md_question_group.menu_url,
			md_question_group.sequence,
			md_question_group.name');

		$this->builder->join('md_question_group', 'md_question_group.md_question_group_id = ' . $this->table . '.md_question_group_id', 'left');
		$this->builder->where($where);
		$this->builder->orderBy("md_question_group.sequence", "ASC");
		$this->builder->orderBy("md_question.no", "ASC");
		return $this->builder->get();
	}
}
