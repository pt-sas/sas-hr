<?php

namespace App\Entities;

use CodeIgniter\Entity;
use App\Models\M_User;
use Config\Services;

class User extends Entity
{
	protected $sys_user_id;
	protected $username;
	protected $name;
	protected $password;
	protected $description;
	protected $email;
	protected $isactive;
	protected $datelastlogin;
	protected $datepasswordchange;
	protected $created_by;
	protected $updated_by;
	protected $md_employee_id;
	protected $md_levelling_id;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getUserId()
	{
		return $this->attributes['sys_user_id'];
	}

	public function setUserId($sys_user_id)
	{
		$this->attributes['sys_user_id'] = $sys_user_id;
	}

	public function getUserName()
	{
		return $this->attributes['username'];
	}

	public function setUserName($username)
	{
		$this->attributes['username'] = $username;
	}

	public function getName()
	{
		return $this->attributes['name'];
	}

	public function setName($name)
	{
		$this->attributes['name'] = $name;
	}

	public function getPassword()
	{
		return $this->attributes['password'];
	}

	public function setPassword(string $password)
	{
		$request = Services::request();
		$user = new M_User($request);

		$row = $user->where('password', $password)->first();

		if ($row && $row->getPassword() === $password) {
			$this->attributes['password'] = $password;
		} else {
			$this->attributes['password'] = password_hash($password, PASSWORD_BCRYPT);
			$this->setDatePasswordChange(date('Y-m-d H:i:s'));
		}
	}

	public function getDescription()
	{
		return $this->attributes['description'];
	}

	public function setDescription($description)
	{
		$this->attributes['description'] = $description;
	}

	public function getEmail()
	{
		return $this->attributes['email'];
	}

	public function setEmail($email)
	{
		$this->attributes['email'] = $email;
	}

	public function getDateLastLogin()
	{
		return $this->attributes['datelastlogin'];
	}

	public function setDateLastLogin($datelastlogin)
	{
		return $this->attributes['datelastlogin'] = $datelastlogin;
	}

	public function getIsActive()
	{
		return $this->attributes['isactive'];
	}

	public function setIsActive($isactive)
	{
		return $this->attributes['isactive'] = $isactive;
	}

	public function getDatePasswordChange()
	{
		return $this->attributes['datepasswordchange'];
	}

	public function setDatePasswordChange($datepasswordchange)
	{
		return $this->attributes['datepasswordchange'] = $datepasswordchange;
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

	public function getEmployeeId()
	{
		return $this->attributes['md_employee_id'];
	}

	public function setEmployeeId($md_employee_id)
	{
		$this->attributes['md_employee_id'] = $md_employee_id;
	}

	public function getLevellingId()
	{
		return $this->attributes['md_levelling_id'];
	}

	public function setLevellingId($md_levelling_id)
	{
		$this->attributes['md_levelling_id'] = $md_levelling_id;
	}
}
