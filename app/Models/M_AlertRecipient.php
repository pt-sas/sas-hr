<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AlertRecipient extends Model
{
	protected $table      = 'md_alertrecipient';
	protected $primaryKey = 'md_alertrecipient_id';
	protected $allowedFields = [
		'table',
		'record_id',
		'sys_user_id',
		'sys_role_id',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps = true;
	protected $returnType = 'App\Entities\AlertRecipient';
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

	public function create($post, $table, $record_id)
	{
		$entity = new \App\Entities\AlertRecipient();

		$post['alert'] = explode(',', $post['alert']);

		if (!isset($post['id'])) {
			foreach ($post['alert'] as $key => $val) :
				$entity->setTable($table);
				$entity->setRecordId($record_id);
				$entity->setUserId($val);
				$entity->setCreatedBy(session()->get('sys_user_id'));
				$entity->setUpdatedBy(session()->get('sys_user_id'));

				$this->save($entity);
			endforeach;
		} else {
			$arrAlert = $this->where('record_id', $post['id'])->findAll();

			$arrUser = [];

			foreach ($arrAlert as $key => $row) :
				if (!in_array($row->getUserId(), $post['alert'])) {
					$this->where([
						'table'			=> $table,
						'record_id'		=> $record_id,
						'sys_user_id'	=> $row->getUserId()
					])->delete();
				}

				// Get list user in this employee before update
				$arrUser[] = $row->getUserId();
			endforeach;

			// Add new user when update employee
			foreach ($post['alert'] as $key => $val) :
				if (!in_array($val, $arrUser)) {
					$entity->setTable($table);
					$entity->setRecordId($record_id);
					$entity->setUserId($val);
					$entity->setCreatedBy(session()->get('sys_user_id'));
					$entity->setUpdatedBy(session()->get('sys_user_id'));

					$this->save($entity);
				}
			endforeach;
		}
	}

	public function getAlertRecipient($table, $record_id)
	{
		$this->builder->join('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.sys_user_id');

		$this->builder->where([
			'table' 	=> $table,
			'record_id' => $record_id
		]);

		return $this->builder->get()->getResult();
	}
}
