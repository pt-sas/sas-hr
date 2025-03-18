<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Message extends Model
{
	protected $table                = 'trx_message';
	protected $primaryKey           = 'trx_message_id';
	protected $allowedFields        = [
		'author_id',
		'subject',
		'body',
		'messagedate',
		'recipient_id',
		'isread',
		'isfavorite',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\Message';
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
	protected $column_order 		= [
		'', // Hide column
		'', // Number column
		'trx_message.author_id',
		'',
		'trx_message.messagedate'
	];
	protected $column_search 		= [
		'trx_message.subject',
		'sys_user.name'
	];
	protected $order 				= ['trx_message.messagedate' => 'DESC'];

	public function __construct(RequestInterface $request)
	{
		parent::__construct();
		$this->db = db_connect();
		$this->request = $request;
		$this->builder = $this->db->table($this->table);
	}

	public function getSelect()
	{
		$sql = $this->table . ".*,
		sys_user.name as author";

		return $sql;
	}

	public function getJoin()
	{
		$sql = [
			$this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . ".author_id", 'left')
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

	public function getNotifDetail($where)
	{
		$this->builder->select($this->table . ".*,
		sender.name as author,
		receiver.name as recipient,
		DATE_FORMAT(trx_message.messagedate, '%d %M %y, %H:%i') AS date,
		emp.image");

		$this->builder->join('sys_user sender', 'sender.sys_user_id = ' . $this->table . '.author_id', 'join');
		$this->builder->join('sys_user receiver', 'receiver.sys_user_id = ' . $this->table . '.recipient_id', 'join');
		$this->builder->join('md_employee emp', 'emp.md_employee_id = sender.md_employee_id', 'left');

		$this->builder->where($where);

		return $this->builder->get();
	}

	public function getNotification(string $type = null)
	{
		$user = session()->get('sys_user_id');

		$this->builder->select($this->table . '.*');
		$this->builder->where([
			$this->table . '.recipient_id'  => $user,
			$this->table . '.isread'		=> 'N'
		]);

		$this->builder->orderBy($this->table . '.created_at', 'ASC');

		if (!is_null($type) && strtolower($type) === "count") {
			$sql = $this->builder->countAllResults();
		} else {
			$this->builder->limit(4);
			$sql = $this->builder->get()->getResult();
		}

		return $sql;
	}
}
