<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Employee extends Model
{
	protected $table                = 'md_employee';
	protected $primaryKey           = 'md_employee_id';
	protected $allowedFields        = [
		'value',
		'nik',
		'nickname',
		'fullname',
		'email',
		'pob',
		'birthday',
		'officephone',
		'phone',
		'phone2',
		'gender',
		'homestatus',
		'nationality',
		'nationality',
		'md_religion_id',
		'md_bloodtype_id',
		'rhesus',
		'md_levelling_id',
		'md_position_id',
		'md_status_id',
		'issameaddress',
		'address',
		'md_country_id',
		'md_province_id',
		'md_city_id',
		'md_district_id',
		'md_subdistrict_id',
		'postalcode',
		'address_dom',
		'md_country_dom_id',
		'md_province_dom_id',
		'md_city_dom_id',
		'md_district_dom_id',
		'md_subdistrict_dom_id',
		'postalcode_dom',
		'superior_id',
		'marital_status',
		'registerdate',
		'childnumber',
		'nos',
		'card_id',
		'npwp_id',
		'ptkp_status',
		'bank',
		'bank_branch',
		'bank_account',
		'bpjs_kes_no',
		'bpjs_kes_period',
		'bpjs_tenaga_no',
		'bpjs_tenaga_period',
		'image',
		'description',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\Employee';
	protected $allowCallbacks		= true;
	protected $beforeInsert			= [];
	protected $afterInsert			= ['createMultipleData'];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= ['createMultipleData'];
	protected $beforeDelete			= [];
	protected $afterDelete			= ['deleteMultipleData'];
	protected $column_order = [
		'', // Hide column
		'', // Number column
		'', // Image
		'md_employee.value',
		'md_employee.fullname',
		'md_employee.pob',
		'md_employee.birthday',
		'sys_ref_detail.name',
		'md_religion.name',
		'md_employee.isactive'
	];
	protected $column_search = [
		'md_employee.value',
		'md_employee.fullname',
		'md_employee.pob',
		'md_employee.birthday',
		'sys_ref_detail.name',
		'md_religion.name',
		'md_employee.isactive'
	];
	protected $order = ['fullname' => 'ASC'];
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
		$sql = $this->table . '.*,
				md_religion.name as religion_name,
				sys_ref_detail.name as gender_name';

		return $sql;
	}

	public function getJoin()
	{
		$sql = [
			$this->setDataJoin('md_religion', 'md_religion.md_religion_id = ' . $this->table . '.md_religion_id', 'left'),
			$this->setDataJoin('sys_reference', 'sys_reference.name = "Gender"', 'left'),
			$this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.gender AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left'),
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

	public function createMultipleData(array $rows)
	{
		$post = $this->request->getVar();

		if (isset($post['md_branch_id'])) {
			$empBranch = new M_EmpBranch($this->request);

			$post['md_branch_id'] = explode(',', $post['md_branch_id']);
			$post['md_employee_id'] = $rows['id'];

			$empBranch->create($post);
		}

		if (isset($post['md_division_id'])) {
			$empDiv = new M_EmpDivision($this->request);

			$post['md_division_id'] = explode(',', $post['md_division_id']);
			$post['md_employee_id'] = $rows['id'];

			$empDiv->create($post);
		}
	}

	public function deleteMultipleData(array $rows)
	{
		$empBranch = new M_EmpBranch($this->request);
		$empDiv = new M_EmpDivision($this->request);

		$empBranch->where($this->primaryKey, $rows['id'])->delete();
		$empDiv->where($this->primaryKey, $rows['id'])->delete();
	}

	public function getDetail($employee_id)
	{
		$this->builder->select($this->table . '.*,' .
			'md_employee_branch.md_branch_id,
			md_employee_division.md_division_id');

		$this->builder->join('md_employee_branch', 'md_employee_branch.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
		$this->builder->join('md_employee_division', 'md_employee_division.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
		$this->builder->where($this->table . '.' . $this->primaryKey, $employee_id);

		$query = $this->builder->get();
		return $query;
	}
}
