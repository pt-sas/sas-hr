<?php

namespace App\Entities;

use CodeIgniter\Entity;

class OvertimeDetail extends Entity
{
	protected $trx_overtime_detail_id;
	protected $trx_overtime_id;
	protected $md_employee_id;
	protected $startdate;
	protected $enddate;
	protected $description;
	protected $overtime_balance;
	protected $overtime_expense;
	protected $enddate_realization;
	protected $total;
	protected $created_by;
	protected $updated_by;
	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getOvertimeDetailId()
	{
		return $this->attributes['trx_overtime_detail_id'];
	}

	public function setOvertimeDetailId($trx_overtime_detail_id)
	{
		$this->attributes['trx_overtime_detail_id'] = $trx_overtime_detail_id;
	}

	public function getOvertimeId()
	{
		return $this->attributes['trx_overtime_id'];
	}

	public function setOvertimeId($trx_overtime_id)
	{
		$this->attributes['trx_overtime_id'] = $trx_overtime_id;
	}

	public function getEmployeeId()
	{
		return $this->attributes['md_employee_id'];
	}

	public function setEmployeeId($md_employee_id)
	{
		$this->attributes['md_employee_id'] = $md_employee_id;
	}

	public function getStartDate()
	{
		return $this->attributes['startdate'];
	}

	public function setStartDate($startdate)
	{
		$this->attributes['startdate'] = $startdate;
	}

	public function getEndDate()
	{
		return $this->attributes['enddate'];
	}

	public function setEndDate($enddate)
	{

		$this->attributes['enddate'] = $enddate;
	}

	public function getEndDateRealization()
	{
		return $this->attributes['enddate_realization'];
	}

	public function setEndDateRealization($enddate_realization)
	{

		$this->attributes['enddate_realization'] = $enddate_realization;
	}

	public function getDescription()
	{
		return $this->attributes['description'];
	}

	public function setDescription($description)
	{
		$this->attributes['description'] = $description;
	}

	public function getIsActive()
	{
		return $this->attributes['isactive'];
	}

	public function setIsActive($isactive)
	{
		return $this->attributes['isactive'] = $isactive;
	}

	public function getOvertimeBalance()
	{
		return $this->attributes['overtime_balance'];
	}

	public function setOvertimeBalance($overtime_balance)
	{
		return $this->attributes['overtime_balance'] = $overtime_balance;
	}

	public function getOvertimeExpense()
	{
		return $this->attributes['overtime_expense'];
	}

	public function setOvertimeExpense($overtime_expense)
	{
		return $this->attributes['overtime_expense'] = $overtime_expense;
	}

	public function getTotal()
	{
		return $this->attributes['total'];
	}

	public function setTotal($total)
	{
		return $this->attributes['total'] = $total;
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