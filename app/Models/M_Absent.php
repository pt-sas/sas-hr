<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Absent extends Model
{
    protected $table                = 'trx_absent';
    protected $primaryKey           = 'trx_absent_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'nik',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'receiveddate',
        'necessary',
        'submissiontype',
        'startdate',
        'enddate',
        'reason',
        'docstatus',
        'image',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
        'md_leavetype_id',
        'image2',
        'image3',
        'comment',
        'enddate_realization',
        'isbranch',
        'branch_to',
        'reference_id',
        'availableleavedays',
        'totaldays',
        'img_medical'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Absent';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = ['doAfterUpdate'];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $column_search        = [
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $order                = ['documentno' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;
    /** Pengajuan Sakit */
    protected $Pengajuan_Sakit      = 100001;
    /** Pengajuan Alpa */
    protected $Pengajuan_Alpa       = 100002;
    /** Pengajuan Cuti */
    protected $Pengajuan_Cuti       = 100003;
    /** Pengajuan Ijin */
    protected $Pengajuan_Ijin       = 100004;
    /** Pengajuan Ijin Resmi */
    protected $Pengajuan_Ijin_Resmi = 100005;
    /** Pengajuan Tugas Kantor Setengah Hari */
    protected $Pengajuan_Ijin_Keluar_Kantor = 100006;
    /** Pengajuan Tugas Kantor */
    protected $Pengajuan_Tugas_Kantor = 100007;
    /** Pengajuan Tugas Khusus */
    protected $Pengajuan_Penugasan = 100008;
    /** Pengajuan Tugas Kantor Setengah Hari */
    protected $Pengajuan_Tugas_Kantor_setengah_Hari = 100009;
    /** Pengajuan Lupa Absen Masuk */
    protected $Pengajuan_Lupa_Absen_Masuk = 100010;
    /** Pengajuan Lupa Absen Pulang */
    protected $Pengajuan_Lupa_Absen_Pulang = 100011;
    /** Pengajuan Ijin Datang Terlambat */
    protected $Pengajuan_Datang_Terlambat = 100012;
    /** Pengajuan Ijin Pulang Cepat */
    protected $Pengajuan_Pulang_Cepat = 100013;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect()
    {
        $sql = $this->table . '.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                sys_ref_detail.name as necessarytype,
                sys_user.name as createdby,
                md_leavetype.name as leavetype,
                ref.documentno as reference_doc';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_reference', 'sys_reference.name = "NecessaryType"', 'left'),
            $this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.necessary AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left'),
            $this->setDataJoin('md_leavetype', 'md_leavetype.md_leavetype_id = ' . $this->table . '.md_leavetype_id', 'left'),
            $this->setDataJoin('trx_absent ref', 'ref.trx_absent_id = ' . $this->table . '.reference_id', 'left')
        ];

        return $sql;
    }

    public function getSelectDetail()
    {
        $sql = $this->table . '.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                trx_absent_detail.trx_absent_detail_id,
                trx_absent_detail.isagree,
                trx_absent_detail.date,
                md_leavetype.name as leavetype,
                md_doctype.name as doctype,
                trx_absent.md_employee_id as employee_id';

        return $sql;

        // $post = $this->request->getPost();

        // $startDate = date('Y-m-d');
        // $endDate = date('Y-m-d');

        // foreach ($post['form'] as $value) :
        //     if (!empty($value['value'])) {
        //         $datetime = urldecode($value['value']);
        //         $date = explode(" - ", $datetime);

        //         $startDate = $date[0];
        //         $endDate = $date[1];
        //     }
        // endforeach;

        // $sql = "(SELECT
        //             ta.*,
        //             me.value as employee,
        //             me.fullname as employee_fullname,
        //             mb.name as branch,
        //             md.name as division,
        //             td.trx_absent_detail_id,
        //             td.isagree,
        //             td.date,
        //             ml.name as leavetype
        //             from trx_absent ta 
        //             left join trx_absent_detail td on ta.trx_absent_id = td.trx_absent_id 
        //             left join md_employee me on me.md_employee_id = ta.md_employee_id 
        //             left join md_branch mb on mb.md_branch_id = ta.md_branch_id 
        //             left join md_division md on md.md_division_id = ta.md_division_id 
        //             left join md_leavetype ml on ml.md_leavetype_id = ta.md_leavetype_id 
        //             where ta.docstatus = 'IP'
        //             and td.isagree = 'H'
        //             order by td.date asc)";
        // union all 
        // (SELECT
        // ta.*,
        // me.value as employee,
        // me.fullname as employee_fullname,
        // mb.name as branch,
        // md.name as division,
        // (select max(td.trx_absent_detail_id)
        //     from trx_absent_detail td
        //     where td.trx_absent_id = ta.trx_absent_id)
        // as trx_absent_detail_id,
        // 'H' as isagree,
        // ta.startdate as date,
        // ml.name as leavetype
        // from trx_absent ta 
        // left join md_employee me on me.md_employee_id = ta.md_employee_id 
        // left join md_branch mb on mb.md_branch_id = ta.md_branch_id 
        // left join md_division md on md.md_division_id = ta.md_division_id 
        // left join md_leavetype ml on ml.md_leavetype_id = ta.md_leavetype_id 
        // where ta.docstatus = 'IP'
        // and ta.isapproved = 'Y'
        // and ta.md_leavetype_id is not null
        // order by ta.startdate asc)";

        // return $this->db->query($sql)->getResult();
    }

    public function getJoinDetail()
    {
        $sql = [
            $this->setDataJoin('trx_absent_detail', 'trx_absent_detail.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left'),
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('md_leavetype', 'md_leavetype.md_leavetype_id = ' . $this->table . '.md_leavetype_id', 'left'),
            $this->setDataJoin('md_doctype', 'md_doctype.md_doctype_id = ' . $this->table . '.submissiontype', 'left'),
        ];

        return $sql;
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }

    public function getInvNumber($field, $where, $post, $created_by)
    {
        $mDelegTransfer = new M_DelegationTransfer($this->request);
        $submissionDate = new \DateTime($post['submissiondate']);
        $year  = $submissionDate->format('Y');
        $month = $submissionDate->format('m');

        $this->builder->select("MAX(CAST(REPLACE(SUBSTRING_INDEX(documentno, '/', -1), '*', '') AS UNSIGNED)) AS documentno");
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get()->getRow();

        $lastNumber = isset($sql->documentno) ? (int) $sql->documentno : 0;
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        $first = $post['necessary'];

        $inTransition = $mDelegTransfer->getInTransitionDelegation("user_to = {$created_by} AND md_employee_id = {$post['md_employee_id']}")->getRow();

        $prefix = "{$first}/{$year}/{$month}/{$nextNumber}";

        // TODO : Add prefix * to documentno when created user is in transition
        if ($inTransition) {
            $prefix .= "*";
        }

        return $prefix;
    }

    public function createAbsentDetail($rows, $header)
    {
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);

        $result = 0;

        try {
            $holiday = $mHoliday->getHolidayDate();
            $today = date('Y-m-d');

            $submissionType = $header->submissiontype;
            $md_employee_id = $header->md_employee_id;
            $startDate = $header->startdate;
            $endDate = $header->enddate;

            //TODO : Get work day employee
            $workDay = $mEmpWork->where([
                'md_employee_id'    => $md_employee_id,
                'validfrom <='      => $today,
                'validto >='        => $today
            ])->orderBy('validfrom', 'ASC')->first();

            if (is_null($workDay)) {
                if ($submissionType == $this->Pengajuan_Tugas_Kantor_setengah_Hari || $submissionType == $this->Pengajuan_Ijin_Keluar_Kantor) {
                    $date_range = getDatesFromRange($endDate, $endDate, $holiday);
                } else {
                    $date_range = getDatesFromRange($startDate, $endDate, $holiday);
                }
            } else {
                //TODO : Get Work Detail
                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = $md_employee_id";
                $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                $daysOff = getDaysOff($workDetail);

                if ($submissionType == $this->Pengajuan_Tugas_Kantor_setengah_Hari || $submissionType == $this->Pengajuan_Ijin_Keluar_Kantor) {
                    $date_range = getDatesFromRange($endDate, $endDate, $holiday, 'Y-m-d H:i:s', 'all', $daysOff);
                } else {
                    $date_range = getDatesFromRange($startDate, $endDate, $holiday, 'Y-m-d H:i:s', 'all', $daysOff);
                }
            }

            $data = [];
            $number = 0;
            foreach ($date_range as $date) :
                $row = [];

                $number++;

                $row[$this->primaryKey] = $rows['id'];
                $row['date'] = $date;
                $row['lineno'] = $number;
                $row['isagree'] = $rows['isagree'] ?? "H";
                $row['created_by'] = $rows['created_by'];
                $row['updated_by'] = $rows['updated_by'];
                $data[] = $row;
            endforeach;

            $result = $mAbsentDetail->builder->insertBatch($data);

            $data = array_map(function ($item)
            use ($submissionType, $md_employee_id) {
                $item['submissiontype'] = $submissionType;
                $item['md_employee_id'] = $md_employee_id;

                return $item;
            }, $data);

            $mAllowance->createAllowance($data);

            //TODO : Insert Total Days
            $entity = new \App\Entities\Absent();
            $entity->trx_absent_id = $rows['id'];
            $entity->totaldays = $number;
            $this->save($entity);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    public function doAfterUpdate(array $rows)
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $sql = $this->find($ID);
        $line = $mAbsentDetail->where($this->primaryKey, $ID)->first();

        $agree = 'Y';
        $notAgree = 'N';
        $holdAgree = 'H';

        // 'M' Is Realization Manager, 'S' is Realization HRD 
        $subType = [
            100001 => 'S', // Sakit
            100003 => 'S', // Cuti
            100004 => 'S', // Ijin
            100005 => 'S', // Ijin Resmi
            100007 => 'M', // Tugas Kantor
            100010 => 'M', // Lupa Absen Masuk
            100011 => 'M'  // Lupa Absen Pulang
        ];

        $formAttendance = [$this->Pengajuan_Lupa_Absen_Masuk, $this->Pengajuan_Lupa_Absen_Pulang, $this->Pengajuan_Datang_Terlambat, $this->Pengajuan_Pulang_Cepat];
        $isSubAttendance = in_array($sql->submissiontype, $formAttendance);

        $updatedBy = $rows['data']['updated_by'] ?? session()->get('id');

        if (($sql->getIsApproved() === 'Y' || $isSubAttendance) && ($sql->docstatus === "IP" || $sql->docstatus === "CO") && is_null($line)) {
            if ($sql->docstatus === "CO")
                $isAgree = $agree;

            if ($sql->docstatus === "IP") {
                $isagree = $subType[$sql->getSubmissionType()];
            }

            $data = [
                'id'         => $ID,
                'created_by' => $updatedBy,
                'updated_by' => $updatedBy,
                'isagree'    => $isAgree
            ];

            $this->createAbsentDetail($data, $sql);
        }

        // TODO : If line is not null then update isagree on line
        if (!empty($sql->getIsApproved()) && ($sql->docstatus === "NA" || $sql->docstatus === "IP") && !is_null($line)) {
            $line = $mAbsentDetail->where($this->primaryKey, $ID)->findAll();

            if ($sql->getIsApproved() === 'Y' && $sql->docstatus === "IP") {
                $isagree = $subType[$sql->getSubmissionType()];
            } else {
                // TODO : If is not approved then update isagree to Not Approved
                $isagree = 'N';
            }

            foreach ($line as $row) :
                $entity = new \App\Entities\AbsentDetail();

                $entity->{$mAbsentDetail->primaryKey} = $row->{$mAbsentDetail->primaryKey};
                $entity->isagree = $isagree;

                $mAbsentDetail->save($entity);
            endforeach;
        }

        if ($sql->getIsApproved() === 'Y' && $sql->docstatus === "VO" && !is_null($line)) {
            $line = $mAbsentDetail->where($this->primaryKey, $ID)->findAll();

            $data = [];
            foreach ($line as $val) :
                $row = [];
                $row[$mAbsentDetail->primaryKey] = $val->{$mAbsentDetail->primaryKey};
                $row['isagree'] = $notAgree;
                $row['updated_by'] = $updatedBy;
                $data[] = $row;

                $refDetail = $mAbsentDetail->where('trx_absent_detail_id', $val->ref_absent_detail_id)->first();
                $whereClause = "trx_absent.trx_absent_id = " . $refDetail->trx_absent_id;
                $lineNo = $mAbsentDetail->getLineNo($whereClause);

                /**
                 * Inserting New Absent Detail
                 */
                $this->entity = new \App\Entities\AbsentDetail();
                $this->entity->trx_absent_id = $refDetail->trx_absent_id;
                $this->entity->isagree = $holdAgree;
                $this->entity->lineno = $lineNo;
                $this->entity->date = $refDetail->date;
                $this->entity->created_by = $updatedBy;
                $this->entity->updated_by = $updatedBy;
                $mAbsentDetail->save($this->entity);

                $this->entity = new \App\Entities\Absent();
                $this->entity->setDocStatus("IP");
                $this->entity->setAbsentId($refDetail->trx_absent_id);
                $this->entity->setUpdatedBy($updatedBy);
                $this->save($this->entity);
            endforeach;

            $mAbsentDetail->builder->updateBatch($data, $mAbsentDetail->primaryKey);

            $whereParam = [
                'table'             => $this->table,
                'md_employee_id'    => $sql->md_employee_id,
                'record_id'         => $ID
            ];

            $tkh = $mAllowance->where($whereParam)->findAll();

            $saldo_cuti = $mLeaveBalance->where($whereParam)->findAll();

            if ($tkh) {
                $arr = [];

                foreach ($tkh as $row) {
                    $arr[] = [
                        "record_id"         => $ID,
                        "table"             => $this->table,
                        "submissiontype"    => $row->submissiontype,
                        "submissiondate"    => $row->submissiondate,
                        "md_employee_id"    => $row->md_employee_id,
                        "amount"            => - ($row->amount),
                        "created_by"        => $updatedBy,
                        "updated_by"        => $updatedBy
                    ];
                }

                $mAllowance->builder->insertBatch($arr);
            }

            if ($saldo_cuti) {
                $saldo = [];

                foreach ($saldo_cuti as $row) {
                    $saldo[] = [
                        "record_id"         => $ID,
                        "table"             => $this->table,
                        "submissiondate"    => $row->submissiondate,
                        "md_employee_id"    => $row->md_employee_id,
                        "amount"            => abs($row->balance_amount),
                        "created_by"        => $updatedBy,
                        "updated_by"        => $updatedBy
                    ];
                }

                $mLeaveBalance->builder->insertBatch($saldo);
            }
        }
    }

    public function getAllSubmission($where)
    {
        $builder = $this->db->table("v_all_submission");

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    public function getSickLeaveSubmission($where)
    {
        $this->builder->select("{$this->table}.*,
        md_employee.fullname as employee_fullname");
        $this->builder->join('trx_medical_certificate', 'trx_medical_certificate.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');
        $this->builder->join('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left');
        $this->builder->where($where);

        return $this->builder->get();
    }
}