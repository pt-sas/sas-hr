<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpSkill extends Model
{
	protected $table                = 'md_employee_skills';
	protected $primaryKey           = 'md_employee_skills_id';
	protected $allowedFields        = [
		'md_employee_id',
		'name',
		'skilltype',
		'ability',
		'written_ability',
		'verbal_ability',
		'description',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\EmpSkill';
	protected $allowCallbacks		= true;
	protected $beforeInsert			= [];
	protected $afterInsert			= [];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= [];
	protected $beforeDelete			= [];
	protected $afterDelete			= [];
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

	/**
	 * Check if data exists for a specific employee and skill type
	 *
	 * This function retrieves a list of employee records based on the provided 
	 * foreign key (employee ID) and skill type. If an array of IDs is provided, 
	 * those IDs will be excluded from the result using a "NOT IN" clause.
	 *
	 * @param array $data Array containing data with at least a 'skilltype' field.
	 * @param mixed $foreignKey Employee ID or foreign key to filter by.
	 * @param array|int|null $id Optional. Array of IDs or a single ID to exclude from the result.
	 * @return array List of records that match the criteria.
	 */
	public function doCheckExistData($data, $primaryKey, $foreignKey): array
	{
		$list = $this->where('md_employee_id', $foreignKey)
			->where('skilltype', $data[0]['skilltype']);

		if ($primaryKey) {
			$list->whereNotIn($this->primaryKey, $primaryKey);
		}

		$list = $list->findAll();

		return $list;
	}

	public function getDataReport($where = null)
	{
		$builder = $this->db->table('v_rpt_employee_skills');

		if ($where)
			$builder->where($where);

		return $builder->get();
	}

	public function getDataReportEmpLang($where = null)
	{
		$builder = $this->db->table('v_rpt_employee_language');

		if ($where)
			$builder->where($where);

		return $builder->get();
	}
}