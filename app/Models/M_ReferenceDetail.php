<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ReferenceDetail extends Model
{
	protected $table                = 'sys_ref_detail';
	protected $primaryKey           = 'sys_ref_detail_id';
	protected $allowedFields        = [
		'value',
		'name',
		'description',
		'sys_reference_id',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\ReferenceDetail';
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
