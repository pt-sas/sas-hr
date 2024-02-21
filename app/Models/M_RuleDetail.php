<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_RuleDetail extends Model
{
	protected $table                = 'md_rule_detail';
	protected $primaryKey           = 'md_rule_detail_id';
	protected $allowedFields        = [
		'md_rule_id',
		'name',
		'detail',
		'operation',
		'format_condition',
		'condition',
		'format_value',
		'value',
		'isdetail',
		'description',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\RuleDetail';
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
	 * Change value of field data
	 *
	 * @param $data Data From request post data
	 * @return array
	 */
	public function doChangeValueField($data): array
	{
		$mRuleValue = new M_RuleValue($this->request);

		$result = [];

		foreach ($data as $row) :
			if (!empty($row->isdetail)) {
				$ruleValue = $mRuleValue->where($this->primaryKey, $row->isdetail)->first();
				$row->isdetail = $ruleValue ? "Y" : "N";
			} else {
				$row->isdetail = "N";
			}

			$result[] = $row;
		endforeach;

		return $result;
	}
}
