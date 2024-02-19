<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Overtime extends Model
{
    protected $table                = 'trx_overtime';
    protected $primaryKey           = 'trx_overtime_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'description',
        'docstatus',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Overtime';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        
    ];
    protected $column_search        = [
    ];
    protected $order                = ['documentno' => 'ASC'];
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
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_employee.nik as nik,
                md_branch.name as branch,
                md_division.name as division';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
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

    public function getInvNumber($field, $where)
    {
        $post = $this->request->getPost();

        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get();

        $code = "";
        if ($sql->getNumRows() > 0) {
            foreach ($sql->getResult() as $row) {
                $doc = ((int)$row->documentno + 1);
                $code = sprintf("%04s", $doc);
            }
        } else {
            $code = "0001";
        }
        $first = 'LB';

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function getDetail($field = null, $where = null)
	{
		$this->builder->join('trx_overtime_detail', 'trx_overtime_detail.trx_overtime_id = ' . $this->table . '.trx_overtime_id', 'left');

		if (!empty($field) && !empty($where)) {
			$this->builder->where($field, $where);
		}

		$this->builder->orderBy($this->table . '.created_at', 'DESC');

		return $this->builder->get();
	}
}