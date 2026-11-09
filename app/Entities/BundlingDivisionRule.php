<?php

namespace App\Entities;

use CodeIgniter\Entity;

class BundlingDivisionRule extends Entity
{
	protected $trx_bundling_division_rule_id;
	protected $trx_master_bundling_id;
	protected $md_division_id;
	protected $maximum_submission;
	protected $created_by;
	protected $updated_by;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getBundlingDivisionRuleId()
	{
		return $this->attributes['trx_bundling_division_rule_id'];
	}

	public function setBundlingDivisionRuleId($trx_bundling_division_rule_id)
	{
		$this->attributes['trx_bundling_division_rule_id'] = $trx_bundling_division_rule_id;
	}

	public function getMasterBundlingId()
	{
		return $this->attributes['trx_master_bundling_id'];
	}

	public function setMasterBundlingId($trx_master_bundling_id)
	{
		$this->attributes['trx_master_bundling_id'] = $trx_master_bundling_id;
	}

	public function getDivisionId()
	{
		return $this->attributes['md_division_id'];
	}

	public function setDivisionId($md_division_id)
	{
		$this->attributes['md_division_id'] = $md_division_id;
	}

	public function getMaximumSubmission()
	{
		return $this->attributes['maximum_submission'];
	}

	public function setMaximumSubmission($maximum_submission)
	{
		$this->attributes['maximum_submission'] = $maximum_submission;
	}

	public function getCreatedAt()
	{
		return $this->attributes['created_at'];
	}

	public function getCreatedBy()
	{
		return $this->attributes['created_by'];
	}

	public function setCreatedBy($created_by)
	{
		$this->attributes['created_by'] = $created_by;
	}

	public function getUpdatedAt()
	{
		return $this->attributes['updated_at'];
	}

	public function getUpdatedBy()
	{
		return $this->attributes['updated_by'];
	}

	public function setUpdatedBy($updated_by)
	{
		$this->attributes['updated_by'] = $updated_by;
	}
}
