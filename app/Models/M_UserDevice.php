<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\M_UserRole;

class M_UserDevice extends Model
{
	protected $table                = 'sys_user_device';
	protected $primaryKey           = 'sys_user_device_id';
	protected $allowedFields        = [
		'sys_user_id',
		'device_token',
		'fcm_token',
		'platform',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\UserDevice';
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
