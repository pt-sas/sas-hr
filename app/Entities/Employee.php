<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Employee extends Entity
{
	protected $md_employee_id;
	protected $value;
	protected $nik;
	protected $nickname;
	protected $fullname;
	protected $email;
	protected $pob;
	protected $birthday;
	protected $officephone;
	protected $phone;
	protected $phone2;
	protected $gender;
	protected $homestatus;
	protected $nationality;
	protected $md_religion_id;
	protected $md_bloodtype_id;
	protected $rhesus;
	protected $md_levelling_id;
	protected $md_position_id;
	protected $md_status_id;
	protected $issameaddress;
	protected $address;
	protected $md_country_id;
	protected $md_province_id;
	protected $md_city_id;
	protected $md_district_id;
	protected $md_subdistrict_id;
	protected $postalcode;
	protected $address_dom;
	protected $md_country_dom_id;
	protected $md_province_dom_id;
	protected $md_city_dom_id;
	protected $md_district_dom_id;
	protected $md_subdistrict_dom_id;
	protected $postalcode_dom;
	protected $superior_id;
	protected $marital_status;
	protected $registerdate;
	protected $childnumber;
	protected $nos;
	protected $card_id;
	protected $npwp_id;
	protected $ptkp_status;
	protected $bank;
	protected $bank_branch;
	protected $bank_account;
	protected $bpjs_kes_no;
	protected $bpjs_kes_period;
	protected $bpjs_tenaga_no;
	protected $bpjs_tenaga_period;
	protected $image;
	protected $description;
	protected $isactive;
	protected $isovertime;
	protected $created_by;
	protected $updated_by;
	protected $md_supplier_id;
	protected $resigndate;
	protected $telegram_username;
	protected $telegram_id;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getEmployeeId()
	{
		return $this->attributes['md_employee_id'];
	}

	public function setEmployeeId($md_employee_id)
	{
		$this->attributes['md_employee_id'] = $md_employee_id;
	}

	public function getValue()
	{
		return $this->attributes['value'];
	}

	public function setValue($value)
	{
		$this->attributes['value'] = $value;
	}

	public function getNik()
	{
		return $this->attributes['nik'];
	}

	public function setNik($nik)
	{
		$this->attributes['nik'] = $nik;
	}

	public function getNickName()
	{
		return $this->attributes['nickname'];
	}

	public function setNickName($nickname)
	{
		$this->attributes['nickname'] = $nickname;
	}

	public function getFullName()
	{
		return $this->attributes['fullname'];
	}

	public function setFullName($fullname)
	{
		$this->attributes['fullname'] = $fullname;
	}

	public function getEmail()
	{
		return $this->attributes['email'];
	}

	public function setEmail($email)
	{
		$this->attributes['email'] = $email;
	}

	public function getPlaceOfBirthday()
	{
		return $this->attributes['pob'];
	}

	public function setPlaceOfBirthday($pob)
	{
		$this->attributes['pob'] = $pob;
	}

	public function getBirthday()
	{
		return $this->attributes['birthday'];
	}

	public function setBirthday($birthday)
	{
		if (empty($birthday))
			$this->attributes['birthday'] = null;
		else
			$this->attributes['birthday'] = $birthday;
	}

	public function getOfficePhone()
	{
		return $this->attributes['officephone'];
	}

	public function setOfficePhone($officephone)
	{
		$this->attributes['officephone'] = $officephone;
	}

	public function getPhone()
	{
		return $this->attributes['phone'];
	}

	public function setPhone($phone)
	{
		$this->attributes['phone'] = $phone;
	}

	public function getPhoneSecond()
	{
		return $this->attributes['phone2'];
	}

	public function setPhoneSecond($phone2)
	{
		$this->attributes['phone2'] = $phone2;
	}

	public function getGender()
	{
		return $this->attributes['gender'];
	}

	public function setGender($gender)
	{
		$this->attributes['gender'] = $gender;
	}

	public function getHomeStatus()
	{
		return $this->attributes['homestatus'];
	}

	public function setHomeStatus($homestatus)
	{
		$this->attributes['homestatus'] = $homestatus;
	}

	public function getNationality()
	{
		return $this->attributes['nationality'];
	}

	public function setNationality($nationality)
	{
		$this->attributes['nationality'] = $nationality;
	}

	public function getReligionId()
	{
		return $this->attributes['md_religion_id'];
	}

	public function setReligionId($md_religion_id)
	{
		$this->attributes['md_religion_id'] = $md_religion_id;
	}

	public function getBloodTypeId()
	{
		return $this->attributes['md_bloodtype_id'];
	}

	public function setBloodTypeId($md_bloodtype_id)
	{
		$this->attributes['md_bloodtype_id'] = $md_bloodtype_id;
	}

	public function getRhesus()
	{
		return $this->attributes['rhesus'];
	}

	public function setRhesus($rhesus)
	{
		$this->attributes['rhesus'] = $rhesus;
	}

	public function getLevellingId()
	{
		return $this->attributes['md_levelling_id'];
	}

	public function setLevellingId($md_levelling_id)
	{
		$this->attributes['md_levelling_id'] = $md_levelling_id;
	}

	public function getPositionId()
	{
		return $this->attributes['md_position_id'];
	}

	public function setPositionId($md_position_id)
	{
		$this->attributes['md_position_id'] = $md_position_id;
	}

	public function getStatusId()
	{
		return $this->attributes['md_status_id'];
	}

	public function setStatusId($md_status_id)
	{
		$this->attributes['md_status_id'] = $md_status_id;
	}

	public function getIsSameAddress()
	{
		return $this->attributes['issameaddress'];
	}

	public function setIsSameAddress($issameaddress)
	{
		$this->attributes['issameaddress'] = $issameaddress;
	}

	public function getAddress()
	{
		return $this->attributes['address'];
	}

	public function setAddress($address)
	{
		$this->attributes['address'] = strtoupper($address);
	}

	public function getCountryId()
	{
		return $this->attributes['md_country_id'];
	}

	public function setCountryId($md_country_id)
	{
		$this->attributes['md_country_id'] = $md_country_id;
	}

	public function getProvinceId()
	{
		return $this->attributes['md_province_id'];
	}

	public function setProvinceId($md_province_id)
	{
		$this->attributes['md_province_id'] = $md_province_id;
	}

	public function getCityId()
	{
		return $this->attributes['md_city_id'];
	}

	public function setCityId($md_city_id)
	{
		$this->attributes['md_city_id'] = $md_city_id;
	}

	public function getDistrictId()
	{
		return $this->attributes['md_district_id'];
	}

	public function setDistrictId($md_district_id)
	{
		$this->attributes['md_district_id'] = $md_district_id;
	}

	public function getSubDistrictId()
	{
		return $this->attributes['md_subdistrict_id'];
	}

	public function setSubDistrictId($md_subdistrict_id)
	{
		$this->attributes['md_subdistrict_id'] = $md_subdistrict_id;
	}

	public function getPostalCode()
	{
		return $this->attributes['postalcode'];
	}

	public function setPostalCode($postalcode)
	{
		$this->attributes['postalcode'] = $postalcode;
	}

	public function getAddressDom()
	{
		return $this->attributes['address_dom'];
	}

	public function setAddressDom($address_dom)
	{
		$this->attributes['address_dom'] = strtoupper($address_dom);
	}

	public function getCountryDomId()
	{
		return $this->attributes['md_country_dom_id'];
	}

	public function setCountryDomId($md_country_dom_id)
	{
		$this->attributes['md_country_dom_id'] = $md_country_dom_id;
	}

	public function getProvinceDomId()
	{
		return $this->attributes['md_province_dom_id'];
	}

	public function setProvinceDomId($md_province_dom_id)
	{
		$this->attributes['md_province_dom_id'] = $md_province_dom_id;
	}

	public function getCityDomId()
	{
		return $this->attributes['md_city_dom_id'];
	}

	public function setCityDomId($md_city_dom_id)
	{
		$this->attributes['md_city_dom_id'] = $md_city_dom_id;
	}

	public function getDistrictDomId()
	{
		return $this->attributes['md_district_dom_id'];
	}

	public function setDistrictDomId($md_district_dom_id)
	{
		$this->attributes['md_district_dom_id'] = $md_district_dom_id;
	}

	public function getSubDistrictDomId()
	{
		return $this->attributes['md_subdistrict_dom_id'];
	}

	public function setSubDistrictDomId($md_subdistrict_dom_id)
	{
		$this->attributes['md_subdistrict_dom_id'] = $md_subdistrict_dom_id;
	}

	public function getPostalCodeDom()
	{
		return $this->attributes['postalcode_dom'];
	}

	public function setPostalCodeDom($postalcode_dom)
	{
		$this->attributes['postalcode_dom'] = $postalcode_dom;
	}

	public function getSuperiorId()
	{
		return $this->attributes['superior_id'];
	}

	public function setSuperiorId($superior_id)
	{
		$this->attributes['superior_id'] = $superior_id;
	}

	public function getMaritalStatus()
	{
		return $this->attributes['marital_status'];
	}

	public function setMaritalStatus($marital_status)
	{
		$this->attributes['marital_status'] = $marital_status;
	}

	public function getRegisterDate()
	{
		return $this->attributes['registerdate'];
	}

	public function setRegisterDate($registerdate)
	{
		if (empty($registerdate))
			$this->attributes['registerdate'] = null;
		else
			$this->attributes['registerdate'] = $registerdate;
	}

	public function getChildNumber()
	{
		return $this->attributes['childnumber'];
	}

	public function setChildNumber($childnumber)
	{
		$this->attributes['childnumber'] = $childnumber;
	}

	public function getNoOfSiblings()
	{
		return $this->attributes['nos'];
	}

	public function setNoOfSiblings($nos)
	{
		$this->attributes['nos'] = $nos;
	}

	public function getCardId()
	{
		return $this->attributes['card_id'];
	}

	public function setCardId($card_id)
	{
		$this->attributes['card_id'] = $card_id;
	}

	public function getNpwpId()
	{
		return $this->attributes['npwp_id'];
	}

	public function setNpwpId($npwp_id)
	{
		$this->attributes['npwp_id'] = $npwp_id;
	}

	public function getPtkpStatus()
	{
		return $this->attributes['ptkp_status'];
	}

	public function setPtkpStatus($ptkp_status)
	{
		$this->attributes['ptkp_status'] = $ptkp_status;
	}

	public function getBank()
	{
		return $this->attributes['bank'];
	}

	public function setBank($bank)
	{
		$this->attributes['bank'] = $bank;
	}

	public function getBankBranch()
	{
		return $this->attributes['bank_branch'];
	}

	public function setBankBranch($bank_branch)
	{
		$this->attributes['bank_branch'] = $bank_branch;
	}

	public function getBankAccount()
	{
		return $this->attributes['bank_account'];
	}

	public function setBankAccount($bank_account)
	{
		$this->attributes['bank_account'] = $bank_account;
	}

	public function getBpjsKesNo()
	{
		return $this->attributes['bpjs_kes_no'];
	}

	public function setBpjsKesNo($bpjs_kes_no)
	{
		$this->attributes['bpjs_kes_no'] = $bpjs_kes_no;
	}

	public function getBpjsKesPeriod()
	{
		return $this->attributes['bpjs_kes_period'];
	}

	public function setBpjsKesPeriod($bpjs_kes_period)
	{
		if (empty($bpjs_kes_period))
			$this->attributes['bpjs_kes_period'] = null;
		else
			$this->attributes['bpjs_kes_period'] = $bpjs_kes_period;
	}

	public function getBpjsTenagaNo()
	{
		return $this->attributes['bpjs_tenaga_no'];
	}

	public function setBpjsTenagaNo($bpjs_tenaga_no)
	{
		$this->attributes['bpjs_tenaga_no'] = $bpjs_tenaga_no;
	}

	public function getBpjsTenagaPeriod()
	{
		return $this->attributes['bpjs_tenaga_period'];
	}

	public function setBpjsTenagaPeriod($bpjs_tenaga_period)
	{
		if (empty($bpjs_tenaga_period))
			$this->attributes['bpjs_tenaga_period'] = null;
		else
			$this->attributes['bpjs_tenaga_period'] = $bpjs_tenaga_period;
	}

	public function getImage()
	{
		return $this->attributes['image'];
	}

	public function setImage($image)
	{
		$this->attributes['image'] = $image;
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

	public function getIsOvertime()
	{
		return $this->attributes['isovertime'];
	}

	public function setIsOvertime($isovertime)
	{
		return $this->attributes['isovertime'] = $isovertime;
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

	public function getSupplierId()
	{
		return $this->attributes['md_supplier_id'];
	}

	public function setSupplierId($md_supplier_id)
	{
		$this->attributes['md_supplier_id'] = $md_supplier_id;
	}

	public function getResignDate()
	{
		return $this->attributes['resigndate'];
	}

	public function setResignDate($resigndate)
	{
		if (empty($resigndate))
			$this->attributes['resigndate'] = null;
		else
			$this->attributes['resigndate'] = $resigndate;
	}

	public function getTelegramUsername()
	{
		return $this->attributes['telegram_username'];
	}

	public function setTelegramUsername($telegram_username)
	{
		$this->attributes['telegram_username'] = $telegram_username;
	}

	public function getTelegramID()
	{
		return $this->attributes['telegram_id'];
	}

	public function setTelegramID($telegram_id)
	{
		$this->attributes['telegram_id'] = $telegram_id;
	}
}