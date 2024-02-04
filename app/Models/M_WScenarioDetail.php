<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_WScenarioDetail extends Model
{
	protected $table                = 'sys_wfscenario_detail';
	protected $primaryKey           = 'sys_wfscenario_detail_id';
	protected $allowedFields        = [
		'grandtotal',
		'lineno',
		'sys_wfscenario_id',
		'sys_wfresponsible_id',
		'sys_notiftext_id',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\WScenarioDetail';
	protected $db;
	protected $builder;
	protected $request;

	public function __construct(RequestInterface $request)
	{
		parent::__construct();
		$this->db = db_connect();
		$this->builder = $this->db->table($this->table);
		$this->request = $request;
	}
}
