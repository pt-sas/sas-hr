<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Models\M_WorkDetail;
use DateTime;

class OfficeDutiesServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();

        $this->baseSubType = $this->model->Pengajuan_Tugas_Kantor;
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
        $eWorkDayServices = new EmpWorkDayServices($this->userID, $this->employeeID);
        $periodServices = new PeriodServices($this->userID, $this->employeeID);
        $uploadServices = new UploadServices($this->userID, $this->employeeID);

        //* Call model
        $mHoliday = new M_Holiday($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        $_ID = !empty($data[$this->model->primaryKey]) ? $data[$this->model->primaryKey] : null;
        $holidays = $mHoliday->getHolidayDate();
        $startDate = date("Y-m-d", strtotime($data['startdate']));
        $endDate = date('Y-m-d', strtotime($data['enddate']));
        $subDate = date('Y-m-d', strtotime($data['submissiondate']));
        $employeeId = $data['md_employee_id'];
        $reopen = false;

        $data["submissiontype"] = $this->baseSubType;
        $data["necessary"] = 'TK';

        //* Add submission & necessary to variable data when update data
        $sql = null;

        if ($_ID) {
            //* Validation for check docstatus when update
            $sql = $this->model->where([$this->model->primaryKey => $_ID, 'submissiontype' => $this->baseSubType])->first();

            if ($sql->docstatus != $this->DOCSTATUS_Drafted)
                throw new ValidationException("Tidak bisa edit, dokumen sudah diproses");

            //* Check reopen status
            if ($sql->isreopen == "Y")
                $reopen = true;
        }

        //* Get Rule
        $rule = $mRule->where([
            'name'      => 'Tugas Kantor 1 Hari',
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

        //* Validate Minimum Dates for Submission Office Duties
        $nextDate = lastWorkingDays($startDate, $holidays, $minDays, false, $daysOff);
        $lastDate = end($nextDate);

        if ($lastDate < $subDate && !$reopen)
            throw new ValidationException("Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan");

        //* Validate submission one day
        $this->validateDuplicateSubmission($employeeId, $startDate, $endDate);

        //* Validate Max Days for Submission Future
        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
        $addDays = end($addDays);

        if ($endDate > $addDays)
            throw new ValidationException("Tanggal selesai melewati tanggal ketentuan");

        //* Validate Max Time when submission Same Day
        $ruleDetail = $rule ? $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'isactive' => 'Y'])->first() : null;
        $todayMinutes = convertToMinutes(date('H:i'));
        $maxMinutes = $ruleDetail ? convertToMinutes(date("H:i", strtotime($ruleDetail->condition))) : null;

        if ($startDate == $subDate && ($maxMinutes && ($todayMinutes > $maxMinutes)))
            throw new ValidationException('Maksimal jam pengajuan ' . $ruleDetail->condition);

        //* Validate Period
        $periodServices->validatePeriod($this->baseSubType, $startDate, $endDate);

        //* Upload Images
        $file = $this->request->getFile('image');
        $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

        if ($sql && empty($data['image']) && !empty($sql->getImage()) && file_exists($path . $sql->getImage())) {
            unlink($path . $sql->getImage());
        }

        $data['image'] = $uploadServices->saveImage($file, $employeeId, $this->baseSubType);

        //* Do Insert or update Data
        $this->entity->fill($data);

        if (!$_ID) {
            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            $docNo = $this->model->getInvNumber("submissiontype", $this->baseSubType, $data, $this->userID);
            $this->entity->setDocumentNo($docNo);
        } else {
            $this->entity->setAbsentId($_ID);
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
            'isreopen',
            'image'
        ];

        $list = $this->model->select($fieldsAllowed)->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->findAll();

        //* Validate if transaction exists
        if (empty($list))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        //* Image Select
        $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

        if (file_exists($path . $list[0]->getImage())) {
            $path = 'uploads/' . $this->PATH_Pengajuan . '/';
            $list[0]->setImage($path . $list[0]->getImage());
        } else {
            $list[0]->setImage(null);
        }

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

    public function proccessTransaction(int $id, String $docaction, int $subTypeTarget = null)
    {
        //* Call Services
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices = new PeriodServices($this->userID, $this->employeeID);

        //* Call Models
        $mDocType = new M_DocumentType($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        //* Get Data Transaction
        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        //* Get menu URL
        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        $today = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime($row->startdate));
        $endDate = date('Y-m-d', strtotime($row->enddate));
        $holidays = $mHoliday->getHolidayDate();

        //* Validate Period
        $periodServices->validatePeriod($row->submissiontype, $startDate, $endDate);

        //* Checking docaction condition
        if ($docaction === $this->DOCSTATUS_Completed) {
            //* Validate submission one day
            $this->validateDuplicateSubmission($row->md_employee_id, $startDate, $endDate);

            //* Check line, if not exists then create line
            $line = $this->modelDetail->where($this->model->primaryKey, $id)->first();

            if (empty($line)) {
                $data = [
                    'id'        => $id,
                    'created_by' => $this->userID,
                    'updated_by' => $this->userID
                ];

                $this->model->createAbsentDetail($data, $row, true, true);
            }

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url, null, true);

            return 'Pengajuan berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            return 'Pengajuan berhasil Divoid';
        } else if ($docaction === $this->DOCSTATUS_Reopen) {
            $config = $mConfig->where('name', "MAX_DATE_REOPEN")->first();

            $rule = $mRule->where([
                'name'      => 'Tugas Kantor 1 Hari',
                'isactive'  => 'Y'
            ])->first();

            $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => 'Batas Reopen'])->first();

            $maxDateReopen = DateTime::createFromFormat('d-m', $config->value);
            $dateRange = getDatesFromRange($row->submissiondate, $today, $holidays, 'Y-m-d');

            //* Validate Reopen
            if (empty($subTypeTarget))
                throw new ValidationException("Silahkan pilih tipe form dahulu.");

            if ($row->md_employee_id == $this->employeeID)
                throw new ValidationException("Tidak bisa reopen untuk pengajuan diri sendiri");

            if ($startDate > date('Y-m-d', strtotime($row->submissiondate)))
                throw new ValidationException("Tidak bisa reopen untuk pengajuan future");

            if ($today > $maxDateReopen->format('Y-m-d'))
                throw new ValidationException("Batas reopen tanggal 24 Desember");

            if (count($dateRange) > ($ruleDetail ? $ruleDetail->condition : 1))
                throw new ValidationException("Sudah melewati batas waktu reopen");

            if ($row->isreopen == "Y")
                throw new ValidationException("Dokumen ini sudah tidak bisa direopen");

            if ($subTypeTarget != $this->baseSubType)
                throw new BusinessException("Tipe pengajuan ini tidak bisa direopen ke tipe pengajuan lain");

            //* Do Save
            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            $this->entity->setIsReopen('Y');
            $this->entity->setIsApproved('');

            $this->save();

            return "Dokumen berhasil direopen";
        } else {
            throw new BusinessException("Dokumen aksi ini tidak tersedia pada tipe pengajuan ini");
        }
    }

    //** This Section is Validate Function */
    private function validateDuplicateSubmission(int $md_employee_id, $startDate, $endDate)
    {
        $whereClause = "v_all_submission.md_employee_id = {$md_employee_id}";
        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
        $whereClause .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Inprogress}','{$this->DOCSTATUS_Completed}')";
        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
        $trx = $this->model->getAllSubmission($whereClause)->getRow();

        if ($trx)
            throw new BusinessException("Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain");
    }
}
