<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_AccessMenu;
use App\Models\M_Branch;
use App\Models\M_Country;
use App\Models\M_Division;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_Levelling;
use App\Models\M_Position;
use App\Models\M_Province;
use App\Models\M_Role;
use App\Models\M_Status;
use App\Models\M_SubDistrict;
use App\Models\M_User;

class EmployeeServices extends BaseServices
{
    protected $PATH_Karyawan = "karyawan";

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Employee($this->request);
        $this->entity = new \App\Entities\Employee();
    }

    public function getProfile()
    {
        //* call Model
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv    = new M_EmpDivision($this->request);
        $mBranch    = new M_Branch($this->request);
        $mDiv       = new M_Division($this->request);
        $mLevelling = new M_Levelling($this->request);
        $mPosition = new M_Position($this->request);
        $mStatus = new M_Status($this->request);
        $mUser = new M_User($this->request);
        $mRole = new M_Role($this->request);

        //* Get Employee Data
        $this->model->select("{$this->model->table}.*,
        rd.name as gender_detail,
        CONCAT_WS(', ',
        {$this->model->table}.address_dom,
        sd.name,
        d.name,
        city.name,
        p.name,
        c.name) AS full_address");

        $this->model->join('md_country c', "c.md_country_id = {$this->model->table}.md_country_dom_id", 'left');
        $this->model->join('md_province p', "p.md_province_id = {$this->model->table}.md_province_dom_id", 'left');
        $this->model->join('md_city city', "city.md_city_id = {$this->model->table}.md_city_dom_id", 'left');
        $this->model->join('md_district d', "d.md_district_id = {$this->model->table}.md_district_dom_id", 'left');
        $this->model->join('md_subdistrict sd', "sd.md_subdistrict_id = {$this->model->table}.md_subdistrict_dom_id", 'left');
        $this->model->join('sys_ref_detail rd', "rd.value = {$this->model->table}.gender AND rd.sys_reference_id = 5", 'left');

        $employee = $this->model->where($this->model->primaryKey, $this->employeeID)
            ->first();

        if (!$employee)
            throw new NotFoundException("Karyawan tidak ditemukan");

        //* Get employee branch & division IDs
        $branchIDs = array_column(
            $mEmpBranch
                ->select($mBranch->primaryKey)
                ->where('md_employee_id', $this->employeeID)
                ->findAll(),
            $mBranch->primaryKey
        );

        $divIDs = array_column(
            $mEmpDiv
                ->select($mDiv->primaryKey)
                ->where('md_employee_id', $this->employeeID)
                ->findAll(),
            $mDiv->primaryKey
        );

        //* Mapping detail data
        $branches = $branchIDs
            ? array_map(fn($row) => [
                'id'   => (int) $row->getBranchId(),
                'name' => ucwords(strtolower($row->getName()))
            ], $mBranch->whereIn($mBranch->primaryKey, $branchIDs)->findAll())
            : [];

        $divisions = $divIDs
            ? array_map(fn($row) => [
                'id'   => (int) $row->getDivisionId(),
                'name' => $row->getName()
            ], $mDiv->whereIn($mDiv->primaryKey, $divIDs)->findAll())
            : [];

        $leveling = array_map(fn($row) => [
            'id'   => (int) $row->getLevellingId(),
            'name' => $row->getName()
        ], $mLevelling->where($mLevelling->primaryKey, $employee->md_levelling_id)->findAll());

        $position = array_map(fn($row) => [
            'id'   => (int) $row->getPositionId(),
            'name' => $row->getName()
        ], $mPosition->where($mPosition->primaryKey, $employee->md_position_id)->findAll());

        $status = array_map(fn($row) => [
            'id'   => (int) $row->getStatusId(),
            'name' => ucwords(strtolower($row->getName()))
        ], $mStatus->where($mStatus->primaryKey, $employee->md_status_id)->findAll());

        $superior = array_map(fn($row) => [
            'id'   => (int) $row->getEmployeeId(),
            'name' => ucwords(strtolower($row->getFullname()))
        ], $this->model->where($this->model->primaryKey, $employee->superior_id)->findAll());

        $userRole = $mUser->detail(['sys_user.md_employee_id' => $this->employeeID])->getRow();

        $role = array_map(fn($row) => [
            'id'   => (int) $row->getRoleId(),
            'name' => $row->getName()
        ], $mRole->where($mRole->primaryKey, $userRole->role)->findAll());

        $dataEntity = new \App\Entities\Employee();

        $dataEntity->md_employee_id = $this->employeeID;
        $dataEntity->fullname = $employee->fullname;
        $dataEntity->nickname = $employee->nickname;
        $dataEntity->value = $employee->value;
        $dataEntity->nik = $employee->nik;
        $dataEntity->gender = $employee->gender_detail;
        $dataEntity->pob = $employee->pob;
        $dataEntity->birthday = format_idn(date('Y-m-d', strtotime($employee->birthday)));
        $dataEntity->email = $employee->email;
        $dataEntity->phone = $employee->phone;
        $dataEntity->telegram_username = $employee->telegram_username;
        $dataEntity->registerdate = format_idn(date('Y-m-d', strtotime($employee->registerdate)));;
        $dataEntity->officephone = $employee->officephone;
        $dataEntity->officeemail = !empty($userRole) ? $userRole->email : "";
        $dataEntity->address = $employee->full_address;
        $dataEntity->md_branch_id  = $branches;
        $dataEntity->md_division_id = $divisions;
        $dataEntity->md_levelling_id = $leveling;
        $dataEntity->md_position_id = $position;
        $dataEntity->md_status_id = $status;
        $dataEntity->superior_id = $superior;
        $dataEntity->sys_role_id = $role;
        $dataEntity->image = base_url('/uploads/' . $this->PATH_Karyawan . '/' . $employee->image);

        return [$dataEntity];
    }

    public function getEmpListDetail()
    {
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);

        $empList = $this->getEmployeeListAccess();
        if (empty($empList)) return [];

        $empListSql = implode(', ', $empList);

        $dataEmpList = [];
        foreach ($this->model->whereIn('md_status_id', [$this->Status_PERMANENT, $this->Status_PROBATION])->whereIn('md_employee_id', $empList)->findAll() as $value) {
            $dataEmpList[$value->md_employee_id] = $value;
        }

        $dataBranchList = [];
        foreach ($mEmpBranch->getBranchDetail("md_employee_id IN ({$empListSql})")->getResult() as $value) {
            $dataBranchList[$value->md_employee_id][] = [
                'id'   => (int) $value->md_branch_id,
                'name' => $value->branch_name,
            ];
        }

        $dataDivList = [];
        foreach ($mEmpDivision->getDivisionDetail("md_employee_id IN ({$empListSql})")->getResult() as $value) {
            $dataDivList[$value->md_employee_id][] = [
                'id'   => (int) $value->md_division_id,
                'name' => $value->division_name,
            ];
        }

        $data = [];
        foreach ($dataEmpList as $value) {
            $data[] = [
                'md_employee_id' => (int) $value->md_employee_id,
                'nik' => $value->nik,
                'fullname' => $value->fullname,
                'md_branch_id' => $dataBranchList[$value->md_employee_id] ?? [],
                'md_division_id' => $dataDivList[$value->md_employee_id] ?? []
            ];
        }

        return $data;
    }

    public function getEmployeeListAccess(bool $isAffectDelegation = true, bool $excludeSelf = false)
    {
        $mAccess = new M_AccessMenu($this->request);
        $mUser = new M_User($this->request);

        $roleEmp = $mUser->detail([
            'sr.isactive'           => 'Y',
            'sys_user.sys_user_id'  => $this->userID,
            'sr.name'               => 'W_Emp_All_Data'
        ])->getRow();

        if ($isAffectDelegation)
            $empDelegation = $this->model->getEmpDelegation($this->userID);

        $arrAccess = $mAccess->getAccess($this->userID);
        $arrEmployee = !empty($this->employeeID) ? $this->model->getChartEmployee($this->employeeID) : [];

        if (!empty($empDelegation)) {
            $arrEmployee = array_unique(array_merge($arrEmployee, $empDelegation));
        }

        $empList = [];

        if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
            $arrBranch = $arrAccess["branch"];
            $arrDiv = $arrAccess["division"];

            $arrEmpBased = $this->model->getEmployeeBased($arrBranch, $arrDiv);

            if (!empty($empDelegation)) {
                $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
            }

            if ($roleEmp && !empty($this->employeeID)) {
                $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                $empList = $arrMerge;
            } else if (!$roleEmp && !empty($this->employeeID)) {
                $empList = $arrEmployee;
            } else if ($roleEmp && empty($this->employeeID)) {
                $empList = $arrEmpBased;
            } else {
                $empList = [$this->employeeID];
            }
        } else if (!empty($this->employeeID)) {
            $empList = $arrEmployee;
        } else {
            $empList = [$this->employeeID];
        }

        if ($excludeSelf)
            $empList = array_diff($empList, [$this->employeeID]);

        return $empList;
    }
}
