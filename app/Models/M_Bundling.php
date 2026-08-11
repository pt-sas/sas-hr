<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Bundling extends Model
{
    protected $table                = 'trx_bundling';
    protected $primaryKey           = 'trx_bundling_id';
    protected $allowedFields        = [
        'documentno',
        'name',
        'bundling_type',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'submissiontype',
        'submissiondate',
        'startdate',
        'enddate',
        'estimate_time',
        'description',
        'docstatus',
        'isapproved',
        'approved_by',
        'receiveddate',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Bundling';
    protected $order                = ['submissiondate' => 'DESC', 'documentno' => 'DESC'];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_adjustment.documentno',
        'trx_adjustment.docstatus',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_adjustment.submissiondate',
        'trx_adjustment.date',
        'trx_adjustment.reason',
        'sys_user.name'
    ];
    protected $column_search        = [
        'trx_adjustment.documentno',
        'trx_adjustment.docstatus',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_adjustment.submissiondate',
        'trx_adjustment.date',
        'trx_adjustment.reason',
        'sys_user.name'
    ];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Paket */
    protected $Pengajuan_Paket = 100031;

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
                md_employee.nik as nik,
                md_branch.name as branch,
                sys_user.name as createdby,
                md_division.name as division';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left')
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

        $first = $post["necessary"];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function doAfterUpdate(array $rows)
    {
        $mBundlingParticipant = new M_BundlingParticipant($this->request);
        $mBundlingEvent = new M_BundlingEvent($this->request);
        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $trx = $this->find($ID);

        if ($trx->docstatus == 'CO' && $trx->isapproved == 'Y') {
            $line = $mBundlingParticipant->where($this->primaryKey, $ID)->findAll();
            $lineID = array_column($line, $mBundlingParticipant->primaryKey);
            $subLine = $mBundlingEvent->whereIn($mBundlingParticipant->primaryKey, $lineID)->first();

            if (!$subLine) {
                $entity = new \App\Entities\Bundling();

                $entity->{$this->primaryKey} = $ID;
                $entity->docstatus = 'IP';

                $this->save($entity);
            }
        }
    }
}
