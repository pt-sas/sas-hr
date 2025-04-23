<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ProxySwitching extends Model
{
	protected $table                = 'trx_proxy_switching';
	protected $primaryKey           = 'trx_proxy_switching_id';
	protected $allowedFields        = [
		'proxytype',
		'trx_proxy_special_detail_id',
		'sys_role_id',
		'sys_user_from',
		'sys_user_to',
		'startdate',
		'enddate',
		'state',
		'isactive',
		'updated_at',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\ProxySwitching';
	protected $beforeInsert			= [];
	protected $afterInsert			= [];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= [];
	protected $beforeDelete			= [];
	protected $afterDelete			= [];
	protected $order 				= ['startdate' => 'ASC'];
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

	public function getProxyDetail($where)
	{
		$this->builder->select("{$this->table}.*,
		ps.documentno,
		ps.startdate as proxy_startdate,
		ps.enddate as proxy_enddate");

		$this->builder->join('trx_proxy_special_detail psd', "psd.trx_proxy_special_detail_id = {$this->table}.trx_proxy_special_detail_id", 'left');
		$this->builder->join('trx_proxy_special ps', "ps.trx_proxy_special_id = psd.trx_proxy_special_id", 'left');

		$this->builder->where($where);

		return $this->builder->get();
	}
}