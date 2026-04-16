<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_Levelling;
use App\Models\M_Position;
use App\Models\M_Status;

class EmployeeServices extends BaseServices
{

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Employee($this->request);
        $this->entity = new \App\Entities\Employee();
    }

    public function getEmployeeDetail(int $employeeId)
    {
        //* call Model
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv    = new M_EmpDivision($this->request);
        $mBranch    = new M_Branch($this->request);
        $mDiv       = new M_Division($this->request);
        $mLevelling = new M_Levelling($this->request);
        $mPosition = new M_Position($this->request);
        $mStatus = new M_Status($this->request);

        $fieldsAllowed = [
            'md_employee_id',
            'value',
            'nickname',
            'fullname',
            'nik',
            'isactive',
            'md_levelling_id',
            'md_position_id',
            'md_status_id',
            'superior_id'
        ];

        $employee = $this->model->select($fieldsAllowed)
            ->where($this->model->primaryKey, $employeeId)
            ->first();

        if (!$employee)
            throw new NotFoundException("Karyawan tidak ditemukan");

        //* Get employee branch & division IDs
        $branchIDs = array_column(
            $mEmpBranch
                ->select($mBranch->primaryKey)
                ->where('md_employee_id', $employeeId)
                ->findAll(),
            $mBranch->primaryKey
        );

        $divIDs = array_column(
            $mEmpDiv
                ->select($mDiv->primaryKey)
                ->where('md_employee_id', $employeeId)
                ->findAll(),
            $mDiv->primaryKey
        );

        //* Mapping detail data
        $branches = $branchIDs
            ? array_map(fn($row) => [
                'id'   => $row->getBranchId(),
                'name' => $row->getName()
            ], $mBranch->whereIn($mBranch->primaryKey, $branchIDs)->findAll())
            : [];

        $divisions = $divIDs
            ? array_map(fn($row) => [
                'id'   => $row->getDivisionId(),
                'name' => $row->getName()
            ], $mDiv->whereIn($mDiv->primaryKey, $divIDs)->findAll())
            : [];

        $leveling = array_map(fn($row) => [
            'id'   => $row->getLevellingId(),
            'name' => $row->getName()
        ], $mLevelling->where($mLevelling->primaryKey, $employee->md_levelling_id)->findAll());

        $position = array_map(fn($row) => [
            'id'   => $row->getPositionId(),
            'name' => $row->getName()
        ], $mPosition->where($mPosition->primaryKey, $employee->md_position_id)->findAll());

        $status = array_map(fn($row) => [
            'id'   => $row->getStatusId(),
            'name' => $row->getName()
        ], $mStatus->where($mStatus->primaryKey, $employee->md_status_id)->findAll());

        $superior = array_map(fn($row) => [
            'id'   => $row->getEmployeeId(),
            'name' => $row->getFullname()
        ], $this->model->where($this->model->primaryKey, $employee->superior_id)->findAll());

        $employee->md_branch_id  = $branches;
        $employee->md_division_id = $divisions;
        $employee->md_levelling_id = $leveling;
        $employee->md_position_id = $position;
        $employee->md_status_id = $status;
        $employee->superior_id = $superior;

        return [$employee];
    }
}
