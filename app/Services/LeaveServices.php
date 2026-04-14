<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_LeaveBalance;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Services\EmpWorkDayServices;
use App\Services\PeriodServices;

class LeaveServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
        $this->baseSubType = $this->model->Pengajuan_Cuti;
    }

    //* Function for paginated for API Mobile
    public function getPaginated(array $params, int $md_employee_id)
    {
        $page       = $params['page'];
        $limit      = $params['limit'];
        $docstatus  = $params['docstatus'];
        $search     = $params['search'];

        $offset = ($page - 1) * $limit;

        $builder = $this->model->builder;

        $builder->select("trx_absent_id,documentno, startdate, enddate, docstatus,e.md_employee_id, e.value as karyawan");

        $builder->join('md_employee e', 'e.md_employee_id = trx_absent.md_employee_id', 'left');

        $builder->where('e.md_employee_id', $md_employee_id);
        $builder->where('submissiontype', $this->baseSubType);

        if (!empty($docstatus))
            $builder->where('docstatus', $docstatus);

        if (!empty($search)) {
            $searchFields = ['documentno', 'docstatus', 'startdate', 'enddate', 'e.value'];

            $builder->groupStart();
            foreach ($searchFields as $i => $field) {
                if ($i == 0)
                    $builder->like($field, $search);
                else
                    $builder->orLike($field, $search);
            }
            $builder->groupEnd();
        }


        $total = $builder->countAllResults(false);

        $builder->orderBy('documentno', 'DESC');

        $data = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_page' => ceil($total / $limit),
                'sort_by' => 'documentno'
            ]
        ];
    }

    public function create(array $data)
    {
        //* Call services
        $eWorkDayServices = new EmpWorkDayServices($this->userID);
        $periodServices = new PeriodServices($this->userID);

        //* Call model
        $mHoliday = new M_Holiday($this->request);
        $mRule = new M_Rule($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        $holidays = $mHoliday->getHolidayDate();
        $startDate = date("Y-m-d", strtotime($data['startdate']));
        $endDate = date('Y-m-d', strtotime($data['enddate']));
        $subDate = date('Y-m-d', strtotime($data['submissiondate']));
        $employeeId = $data['md_employee_id'];

        //* Add submission & necessary to variable data when update data
        if (!empty($data[$this->model->primaryKey])) {
            //* Validation for check docstatus when update
            $sql = $this->model->find($data[$this->model->primaryKey]);

            if ($sql->docstatus != $this->DOCSTATUS_Drafted)
                throw new ValidationException("Tidak bisa edit, dokumen sudah diproses");
        }

        $data["submissiontype"] = $this->baseSubType;
        $data["necessary"] = 'CT';

        //* Get Rule
        $rule = $mRule->where([
            'name'      => 'Cuti',
            'isactive'  => 'Y'
        ])->first();

        $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
        $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

        //* Get work day employee
        $workDay = $eWorkDayServices->getEmpWorkDay($employeeId, $startDate, $endDate);

        //* Get Work Detail
        $whereClause = "md_work_detail.isactive = 'Y'";
        $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

        $daysOff = getDaysOff($workDetail);

        //* Validate Minimum Dates for Submission Leave
        $nextDate = lastWorkingDays($subDate, $holidays, $minDays, false, $daysOff);
        $lastDate = end($nextDate);

        if ($startDate <= $lastDate)
            throw new ValidationException("Tidak bisa mengajukan pada tanggal ' . format_dmy($startDate, " - ") . ', karena tidak sesuai dengan batas pengajuan");

        //* Validate submission one day
        $whereClause = "v_all_submission.md_employee_id = {$employeeId}";
        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
        $trx = $this->model->getAllSubmission($whereClause)->getRow();

        if ($trx)
            throw new BusinessException("Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain");

        //* Validate Max Days for Submission Future
        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
        $addDays = end($addDays);

        if ($endDate > $addDays)
            throw new ValidationException("Tanggal selesai melewati tanggal ketentuan");

        //* Validate Leave Balance
        $this->validateLeaveBalance($employeeId, $startDate, $endDate, $holidays, $daysOff);

        //* Validate Period
        $periodServices->validatePeriod($this->baseSubType, $startDate, $endDate, $holidays, $daysOff);

        //* Do Insert or update Data
        $this->entity->fill($data);

        if ($this->isNew()) {
            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            $docNo = $this->model->getInvNumber("submissiontype", $this->baseSubType, $data, $this->userID);
            $this->entity->setDocumentNo($docNo);
        } else {
            $this->entity->setAbsentId($data[$this->model->primaryKey]);
        }

        return $this->save();
    }

    public function show(int $id)
    {
        //* Call Models
        $mEmployee = new M_Employee($this->request);
        $mBranch = new M_Branch($this->request);
        $mDivision = new M_Division($this->request);

        $fieldsAllowed = [
            'trx_absent_id',
            'documentno',
            'md_employee_id',
            'nik',
            'md_branch_id',
            'md_division_id',
            'submissiondate',
            'receiveddate',
            'submissiontype',
            'startdate',
            'enddate',
            'reason',
            'docstatus',
            'approveddate',
            'created_by',
            'updated_by',
            'leavebalance',
            'availableleavedays',
            'totaldays',
            'isreopen'
        ];

        $list = $this->model->select($fieldsAllowed)->where($this->model->primaryKey, $id)->findAll();

        //* Validate if transaction exists
        if (empty($list))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        //* Data select
        $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
        $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

        $rowBranch = $mBranch->where($mBranch->primaryKey, $list[0]->getBranchId())->first();
        $list = $this->field->setDataSelect($mBranch->table, $list, $mBranch->primaryKey, $rowBranch->getBranchId(), $rowBranch->getName());

        $rowDiv = $mDivision->where($mDivision->primaryKey, $list[0]->getDivisionId())->first();
        $list = $this->field->setDataSelect($mDivision->table, $list, $mDivision->primaryKey, $rowDiv->getDivisionId(), $rowDiv->getName());

        //* Get Detail
        $fieldsAllowed = [
            'trx_absent_detail_id',
            'trx_absent_id',
            'lineno',
            'date',
            'isagree',
            'ref_absent_detail_id',
            'table'
        ];
        $detail = $this->modelDetail->select($fieldsAllowed)->where($this->model->primaryKey, $id)->findAll();

        $data = [
            'header' => $list,
            'line' => $detail
        ];

        return $data;
    }

    public function proccessTransaction(int $id, String $docaction)
    {
        //* Call Services
        $WScenarioServices = new WScenarioServices($this->userID);
        $periodServices = new PeriodServices($this->userID);
        $eWorkDayServices = new EmpWorkDayServices($this->userID);

        //* Call Models
        $mDocType = new M_DocumentType($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        //* Get Data Transaction
        $row = $this->model->find($id);

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        $startDate = date('Y-m-d', strtotime($row->startdate));
        $endDate = date('Y-m-d', strtotime($row->enddate));
        $holidays = $mHoliday->getHolidayDate();

        //* Get work day employee
        $workDay = $eWorkDayServices->getEmpWorkDay($row->md_employee_id, $startDate, $endDate);

        //* Get Work Detail
        $whereClause = "md_work_detail.isactive = 'Y'";
        $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

        $daysOff = getDaysOff($workDetail);

        $periodServices->validatePeriod($row->submissiontype, $startDate, $endDate, $holidays, $daysOff);

        if ($docaction === $this->DOCSTATUS_Completed) {
            //* Validate Leave Balance
            $this->validateLeaveBalance($row->md_employee_id, $startDate, $endDate, $holidays, $daysOff);

            //* Validate submission one day
            $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
            $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
            $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
            $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
            $trx = $this->model->getAllSubmission($whereClause)->getRow();

            if ($trx)
                throw new BusinessException("Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain");

            //* Check line, if not exists then create line
            $line = $this->modelDetail->where($this->model->primaryKey, $id)->first();

            if (empty($line)) {
                $data = [
                    'id'        => $id,
                    'created_by' => $this->userID,
                    'updated_by' => $this->userID
                ];

                $this->model->createAbsentDetail($data, $row);
            }

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url, null, true);

            return 'Pengajuan berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            return 'Pengajuan berhasil Divoid';
        } else {
            $this->entity->setDocStatus($docaction);
            return "Process Succeded";
        }
    }

    public function getAvailableDays(int $md_employee_id, $startDate)
    {
        $mLeaveBalance = new M_LeaveBalance($this->request);

        $startOfYear = date('Y', strtotime($startDate));
        $nextYear = date('Y', strtotime('+1 year'));

        $balance = 0;
        $availableleave = 0;
        if ($startOfYear == $nextYear) {
            $leaveBalance = $mLeaveBalance->getNextYearBalance($md_employee_id);

            $balance = $leaveBalance->saldo_cuti;
            $availableleave = $leaveBalance->balance;
        } else {
            $leaveBalance = $mLeaveBalance->getTotalBalance($md_employee_id, $startOfYear);

            if ($leaveBalance) {
                $carryOverValid = ($leaveBalance->carry_over_expiry_date && $startDate <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

                $balance = $leaveBalance->balance_amount;
                $availableleave = $carryOverValid ? $leaveBalance->carried_over_amount + $leaveBalance->balance_amount : $leaveBalance->balance_amount;
                $availableleave = $availableleave - $leaveBalance->reserved;
            }
        }

        return [
            "balance" => intval($balance),
            "availableleave" => intval($availableleave)
        ];
    }

    private function validateLeaveBalance(int $md_employee_id, $startDate, $endDate, $holidays, $daysOff)
    {
        $mLeaveBalance = new M_LeaveBalance($this->request);

        $year = date('Y', strtotime($startDate));
        $nextYear = date('Y', strtotime('+1 year'));

        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

        $amountThisYear = [];
        $amountNextYear = [];

        foreach ($dateRange as $date) {
            if (date('Y', strtotime($date)) == $nextYear) {
                $amountNextYear[] = $date;
            } else {
                $amountThisYear[] = $date;
            }
        }

        $leaveBalance = $mLeaveBalance->getTotalBalance($md_employee_id, $year);
        $leaveBalanceNextYear = !empty($amountNextYear) ? $mLeaveBalance->getNextYearBalance($md_employee_id) : null;

        if (empty($leaveBalance) && empty($leaveBalanceNextYear))
            throw new NotFoundException("Saldo cuti tidak tersedia");

        $balance = 0;

        if (!empty($leaveBalance)) {
            $carryOverValid = ($leaveBalance->carry_over_expiry_date && $endDate <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

            $balance = $carryOverValid ? $leaveBalance->carried_over_amount + $leaveBalance->balance_amount : $leaveBalance->balance_amount;
            $balance = $balance - $leaveBalance->reserved;
        }

        $balanceNextYear = !empty($leaveBalanceNextYear) ? $leaveBalanceNextYear->balance : 0;

        $amountThisYear = count($amountThisYear);
        $amountNextYear = count($amountNextYear);

        if (!empty($amountNextYear) && $amountNextYear > $balanceNextYear)
            throw new BusinessException("Saldo tahun depan tidak cukup");
        else if (!empty($amountThisYear) && $amountThisYear > $balance)
            throw new BusinessException('Saldo cuti tidak cukup atau sudah expired');
    }
}
