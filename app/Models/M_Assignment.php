<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Assignment extends Model
{
    protected $table                = 'trx_assignment';
    protected $primaryKey           = 'trx_assignment_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'branch_to',
        'submissiontype',
        'submissiondate',
        'startdate',
        'enddate',
        'reason',
        'docstatus',
        'isapproved',
        'receiveddate',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Assignment';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
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

    /** Pengajuan Tugas Kantor */
    protected $Pengajuan_Tugas_Kantor = 100007;
    /** Pengajuan Tugas Khusus */
    protected $Pengajuan_Penugasan = 100008;
    /** Pengajuan Tugas Kantor Setengah Hari */
    protected $Pengajuan_Tugas_Kantor_setengah_Hari = 100009;

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
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left'),
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
                trx_assignment_detail.trx_assignment_detail_id,
                trx_assignment_date.trx_assignment_date_id,
                trx_assignment_date.isagree,
                trx_assignment_date.date,
                md_doctype.name as doctype,
                trx_assignment.reason,
                trx_assignment_detail.md_employee_id as employee_id';

        return $sql;
    }

    public function getJoinDetail()
    {
        $sql = [
            $this->setDataJoin('trx_assignment_detail', 'trx_assignment_detail.trx_assignment_id = ' . $this->table . '.trx_assignment_id', 'left'),
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = trx_assignment_detail.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('md_doctype', 'md_doctype.md_doctype_id = ' . $this->table . '.submissiontype', 'left'),
            $this->setDataJoin('trx_assignment_date', 'trx_assignment_date.trx_assignment_detail_id = trx_assignment_detail.trx_assignment_detail_id', 'left'),
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

    public function getInvNumber($field, $where, $post)
    {
        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get();

        $code = "";
        if ($sql->getNumRows() > 0) {
            foreach ($sql->getResult() as $row) {
                $doc = ((int)$row->documentno + 1);
                $code = sprintf("%04s", $doc);
            }
        } else {
            $code = "0001";
        }
        $first = $post['necessary'];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function checkStatusDate($where)
    {
        $this->builder->select('*');
        $this->builder->join('trx_assignment_detail', 'trx_assignment_detail.trx_assignment_id = trx_assignment.trx_assignment_id', 'left');
        $this->builder->join('trx_assignment_date', 'trx_assignment_date.trx_assignment_detail_id = trx_assignment_detail.trx_assignment_detail_id', 'left');

        $this->builder->where($where);

        return $this->builder->get();
    }

    public function createAssignmentDate($rows, $line)
    {
        $mAssignmentDate = new M_AssignmentDate($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        try {
            $header = $this->where($this->primaryKey, $line->trx_assignment_id)->first();
            $today = date('Y-m-d');
            $holiday = $mHoliday->getHolidayDate();

            $startDate = $header->startdate;
            $endDate = $header->enddate;
            $isSingleDay = in_array($header->submissiontype, [$this->Pengajuan_Tugas_Kantor_setengah_Hari, $this->Pengajuan_Ijin_Keluar_Kantor]);

            $workDay = $mEmpWork->where([
                'md_employee_id' => $line->md_employee_id,
                'validfrom <=' => $today
            ])->orderBy('validfrom', 'ASC')->first();

            $daysOff = [];

            if ($workDay) {
                $whereClause = "md_work_detail.isactive = 'Y' AND md_employee_work.md_employee_id = {$line->md_employee_id} AND md_work.md_work_id = $workDay->md_work_id";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();
                $daysOff = getDaysOff($workDetail);
            }

            $date_range = getDatesFromRange(
                $isSingleDay ? $endDate : $startDate,
                $endDate,
                $holiday,
                'Y-m-d H:i:s',
                'all',
                $daysOff
            );

            $data = array_map(function ($date) use ($rows) {
                return [
                    'trx_assignment_detail_id' => $rows['id'],
                    'date' => $date,
                    'isagree' => $rows['isagree'] ?? "H",
                    'created_by' => $rows['created_by'],
                    'updated_by' => $rows['updated_by'],
                ];
            }, $date_range);

            $result = $mAssignmentDate->builder->insertBatch($data);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }


    public function doAfterUpdate(array $rows)
    {
        $mAssignmentDetail = new M_AssignmentDetail($this->request);
        $mAssignmentDate = new M_AssignmentDate($this->request);

        $ID = $rows['id'][0] ?? $rows['id'];

        $sql = $this->find($ID);
        $line = $mAssignmentDetail->where($this->primaryKey, $ID)->findAll();

        $agree = 'Y';
        $notAgree = 'N';
        $holdAgree = 'H';
        $rlzManager = 'M';

        $updatedBy = $rows['data']['updated_by'] ?? session()->get('id');

        if (
            $sql->getIsApproved() === 'Y' && ($sql->docstatus === "IP" || $sql->docstatus === "CO") && !is_null($line)
        ) {

            foreach ($line as $detail) {
                $listDate = $mAssignmentDate->where($mAssignmentDetail->primaryKey, $detail->trx_assignment_detail_id)->findAll();

                if (!$listDate) {
                    if ($sql->docstatus === "CO")
                        $isAgree = $agree;

                    if ($sql->docstatus === "IP")
                        $isAgree = $rlzManager;

                    $data = [
                        'id'         => $detail->trx_assignment_detail_id,
                        'created_by' => $updatedBy,
                        'updated_by' => $updatedBy,
                        'isagree'    => $isAgree
                    ];
                    $this->createAssignmentDate($data, $detail);
                }
            }
        }
    }
}
