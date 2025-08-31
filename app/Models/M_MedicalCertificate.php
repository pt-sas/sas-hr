<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;
use TCPDF;

class M_MedicalCertificate extends Model
{
    protected $table                = 'trx_medical_certificate';
    protected $primaryKey           = 'trx_medical_certificate_id';
    protected $allowedFields        = [
        'trx_absent_id',
        'documentno',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'submissiontype',
        'submissiondate',
        'date',
        'reason',
        'docstatus',
        'isapproved',
        'receiveddate',
        'approveddate',
        'sys_wfscenario_id',
        'pdf',
        'approved_by',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\MedicalCertificate';
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
        'trx_medical_certificate.documentno',
        'ref.documentno',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_medical_certificate.submissiondate',
        'trx_medical_certificate.date',
        'trx_medical_certificate.approveddate',
        'trx_medical_certificate.reason',
        'trx_medical_certificate.docstatus',
        'sys_user.name'
    ];
    protected $column_search        = [
        'trx_medical_certificate.documentno',
        'ref.documentno',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_medical_certificate.submissiondate',
        'trx_medical_certificate.date',
        'trx_medical_certificate.approveddate',
        'trx_medical_certificate.reason',
        'trx_medical_certificate.docstatus',
        'sys_user.name'
    ];

    protected $order                = ['trx_medical_certificate.documentno' => 'ASC'];
    protected $request;
    protected $db;
    protected $builder;

    /** Pengajuan Keterangan Sakit */
    protected $Pengajuan_Surat_Keterangan_Sakit = 100026;

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
                sys_user.name as createdby,
                ref.documentno as reference_doc';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left'),
            $this->setDataJoin('trx_absent ref', 'ref.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left')
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

    public function doAfterUpdate(array $rows)
    {
        $mEmployee = new M_Employee($this->request);
        $mDivision = new M_Division($this->request);
        $mUser = new M_User($this->request);
        $mAbsent = new M_Absent($this->request);

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

        $list = $this->where($this->primaryKey, $ID)->first();

        // TODO : If is Approved and Document is Completed, Then generating PDF File
        if ($list->getIsApproved() === "Y" && $list->docstatus === "CO") {
            $employee = $mEmployee->where($mEmployee->primaryKey, $list->md_employee_id)->first();
            $createdBy = $mUser->where('sys_user_id', $list->created_by)->first();
            $approvedBy = $mUser->where('sys_user_id', $list->approved_by)->first();
            $division = $mDivision->where($mDivision->primaryKey, $list->md_division_id)->first();

            // TODO : Set File Name
            $lenPos = strpos($employee->getValue(), '-');
            $value = substr_replace($employee->getValue(), "", $lenPos);
            $ymd = date('YmdHis');
            $fileName = $this->Pengajuan_Surat_Keterangan_Sakit . '_' . $value . '_' . $ymd . '.pdf';

            //TODO : Set Path
            $path = FCPATH . '/uploads/keterangan/';

            // TODO : Set PDF Structure And Data
            $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);

            $pdf->setPrintHeader(false);
            $pdf->AddPage();
            $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
            $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
            $pdf->setFont('helvetica', 'B', 20);
            $pdf->Cell(0, 25, 'SURAT KETERANGAN SAKIT', 0, 1, 'C');
            $pdf->setFont('helvetica', '', 12);
            //Ini untuk bagian field nama dan tanggal pengajuan
            $pdf->Cell(30, 0, 'Nama ', 0, 0, 'L', false, '', 0, false);
            $pdf->Cell(90, 0, ': ' . $employee->fullname, 0, 0, 'L', false, '', 0, false);
            $pdf->Cell(40, 0, 'Tanggal Pembuatan', 0, 0, 'L', false, '', 0, false);
            $pdf->Cell(30, 0, ': ' . format_dmy($list->submissiondate, '-'), 0, 1, 'L', false, '', 0, false);
            $pdf->Ln(2);
            //Ini untuk bagian field divisi dan Tanggal diterima
            $pdf->Cell(30, 0, 'Divisi ', 0, 0, 'L', false, '', 0, false);
            $pdf->Cell(90, 0, ': ' . $division->name, 0, 0, 'L', false, '', 0, false);
            // $pdf->Cell(40, 0, 'Tanggal Diterima', 0, 0, 'L', false, '', 0, false);
            // $pdf->Cell(30, 0, ': ' . $tglpenerimaan, 0, 1, 'L', false, '', 0, false);
            $pdf->Ln(10);
            //Ini bagian tanggal ijin dan jam
            $pdf->Cell(30, 0, 'Tanggal', 0, 0, 'L', false, '', 0, false);
            $pdf->Cell(40, 0, ': ' . format_dmy($list->date, '-'), 0, 1, 'L', false, '', 0, false);
            $pdf->Ln(2);
            //Ini bagian Alasan
            $pdf->Cell(30, 0, 'Alasan', 0, 0, 'L');
            $pdf->Cell(3, 0, ':', 0, 0, 'L');
            $pdf->MultiCell(0, 20, $list->reason, 0, '', false, 1, null, null, false, 0, false, false, 20);
            $pdf->Ln(5);
            //Bagian ttd
            $pdf->setFont('helvetica', '', 10);
            $pdf->Cell(63, 0, 'Dibuat oleh,', 0, 0, 'C');
            $pdf->Cell(63, 0, '', 0, 0, 'C');
            $pdf->Cell(63, 0, 'Disetujui oleh,', 0, 0, 'C');
            $pdf->Ln(25);
            $pdf->Cell(63, 0, $createdBy->name, 0, 0, 'C');
            $pdf->Cell(63, 0, '', 0, 0, 'C');
            $pdf->Cell(63, 0, $approvedBy->name, 0, 1, 'C');
            $pdf->Cell(63, 0, '', 0, 0, 'C');
            $pdf->Cell(63, 0, '', 0, 0, 'C');
            $pdf->Cell(63, 0, '', 0, 0, 'C');


            // TODO : Create Directory if Not Exists 
            if (!is_dir($path)) mkdir($path, 0777, true);

            // TODO : Create PDF File
            $pdf->Output($path . $fileName, 'F');

            // TODO : Inserting image name on SickLeave Submission
            $entity = new \App\Entities\Absent();
            $entity->setAbsentId($list->trx_absent_id);
            $entity->setImageMedical($fileName);
            $mAbsent->save($entity);
        }
    }
}
