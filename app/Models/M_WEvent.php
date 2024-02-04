<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_WEvent extends Model
{
	protected $table                = 'sys_wfevent_audit';
	protected $primaryKey           = 'sys_wfevent_audit_id';
	protected $allowedFields        = [
		'sys_wfactivity_id',
		'sys_wfresponsible_id',
		'sys_user_id',
		'state',
		'oldvalue',
		'newvalue',
		'isapproved',
		'table',
		'record_id',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\WEvent';
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

	public function setEventAudit($sys_wfactivity_id, $sys_wfresponsible_id, $user_id, $state, $approved, $table, $record_id, $created_by, $nextResp = false)
	{
		$entity = new \App\Entities\WEvent();
		$mUser = new M_User($this->request);

		$row = $this->where([
			'sys_wfactivity_id'		=> $sys_wfactivity_id,
			'sys_wfresponsible_id'	=> $sys_wfresponsible_id,
			'state'             	=> 'OS',
			'isapproved'         	=> 'N'
		])->first();

		$entity->setWfActivityId($sys_wfactivity_id);
		$entity->setWfResponsibleId($sys_wfresponsible_id);
		$entity->setState($state);
		$entity->setTable($table);
		$entity->setRecordId($record_id);

		if ($row) {
			$event = $this->find($row->getWfEventAuditId());

			if (!$nextResp && $event->getSysUserId() == $created_by) {
				$entity->setOldValue(true);
				$entity->setNewValue('Y');
			} else {
				$oldUser = $mUser->find($created_by);
				$entity->setOldValue($oldUser->getUserName());

				$newUser = $mUser->find($user_id);
				$entity->setNewValue($newUser->getUserName());
			}

			$entity->setSysUserId($created_by);
			$entity->setIsApproved($approved);
			$entity->setUpdatedBy($created_by);
			$entity->setWfEventAuditId($row->getWfEventAuditId());
		} else {
			$entity->setSysUserId($created_by);
			$entity->setIsApproved(false);
			$entity->setOldValue('false');
			$entity->setCreatedBy($created_by);
			$entity->setUpdatedBy($created_by);
		}

		$this->save($entity);

		return $user_id;
	}
}
