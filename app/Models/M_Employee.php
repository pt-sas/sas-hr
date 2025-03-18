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
		'isovertime',
		'created_by',
		'updated_by',
		'md_supplier_id',
		'resigndate'
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
		'md_status.name',
		'md_employee.isactive'
	];
	protected $column_search = [
		'md_employee.value',
		'md_employee.fullname',
		'md_employee.pob',
		'md_employee.birthday',
		'sys_ref_detail.name',
		'md_religion.name',
		'md_status.name',
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
				sys_ref_detail.name as gender_name,
				md_status.name as status_karyawan';

		return $sql;
	}

	public function getJoin()
	{
		$sql = [
			$this->setDataJoin('md_religion', 'md_religion.md_religion_id = ' . $this->table . '.md_religion_id', 'left'),
			$this->setDataJoin('sys_reference', 'sys_reference.name = "Gender"', 'left'),
			$this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.gender AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left'),
			$this->setDataJoin('md_status', 'md_status.md_status_id = ' . $this->table . '.md_status_id', 'left'),
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

	public function getLastNik()
	{
		$year = date("y");
		$month = date("m");

		$this->builder->select('MAX(RIGHT(nik,2)) AS nik');
		$this->builder->where("DATE_FORMAT(created_at, '%Y-%m')", date("Y") . '-' . $month);
		$sql = $this->builder->get();

		$code = "";
		if ($sql->getNumRows() > 0) {
			foreach ($sql->getResult() as $row) {
				$doc = ((int)$row->nik + 1);
				$code = sprintf("%02s", $doc);
			}
		} else {
			$code = "01";
		}

		$prefix = $year . $month . $code;

		return $prefix;
	}

	public function getChartEmployee($id)
	{
		$employee = [];

		if (!empty($id)) {
			$row = $this->find($id);

			$sql = "SELECT e.md_employee_id
					FROM md_employee e
					LEFT JOIN md_employee_division ed ON ed.md_employee_id = e.md_employee_id
					LEFT JOIN md_employee_branch eb ON eb.md_employee_id = e.md_employee_id
					WHERE e.isactive = 'Y'";

			if ($row->getLevellingId() == 100004) {
				$sql .= "AND (e.superior_id = " . $id . "
					OR e.md_employee_id = " . $id . ")";
			} else {
				$sql .= "AND eb.md_branch_id IN (SELECT x.md_branch_id 
								FROM md_employee em
								LEFT JOIN md_employee_branch x ON x.md_employee_id = em.md_employee_id
								WHERE em.md_employee_id = $id)";
				$sql .= "AND (ed.md_division_id IN (SELECT v.md_division_id 
								FROM md_employee em
								LEFT JOIN md_employee_division v ON v.md_employee_id = em.md_employee_id
								WHERE em.superior_id = $id)
						OR e.md_employee_id = $id)";
			}

			$result = $this->db->query($sql)->getResult();

			foreach ($result as $row) {
				$employee[] = $row->md_employee_id;
			}
		}

		return $employee;
	}

	public function getEmployeeBased($arrB, $arrD, $where = null)
	{
		$this->builder->select($this->table . '.md_employee_id');
		$this->builder->join('md_employee_branch', 'md_employee_branch.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
		$this->builder->join('md_employee_division', 'md_employee_division.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
		$this->builder->whereIn('md_employee_branch.md_branch_id', $arrB);
		$this->builder->whereIn('md_employee_division.md_division_id', $arrD);

		if ($where) {
			$this->builder->where($where);
		}

		$query = $this->builder->get()->getResult();

		$arr = [];
		foreach ($query as $row) :
			$arr[] = $row->md_employee_id;
		endforeach;

		$arr = array_unique($arr);
		sort($arr);

		return $arr;
	}

	public function getEmployee($where)
	{
		$this->builder->join('md_employee_branch', 'md_employee_branch.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
		$this->builder->join('md_employee_division', 'md_employee_division.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
		$this->builder->join('md_benefit', 'md_employee_branch.md_branch_id = md_benefit.md_branch_id and md_employee_division.md_division_id = md_benefit.md_division_id and md_employee.md_levelling_id = md_benefit.md_levelling_id and md_employee.md_position_id = md_benefit.md_position_id and md_employee.md_status_id = md_benefit.md_status_id', 'left');
		$this->builder->join('md_benefit_detail', 'md_benefit_detail.md_benefit_id = md_benefit.md_benefit_id', 'left');
		$this->builder->distinct();
		$this->builder->select($this->table . '.*');

		if ($where)
			$this->builder->where($where);

		$result = $this->find();

		return $result;
	}

	public function getDataReport($where = null)
	{
		$builder = $this->db->table('v_rpt_employee');

		if ($where)
			$builder->where($where);

		return $builder->get();
	}

	public function getEmployeeValue($where)
	{
		$this->builder->select('md_employee_id, value');

		if ($where)
			$this->builder->where($where);

		return $this->builder->get();
	}
}