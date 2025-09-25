<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_EmpContact;
use App\Models\M_EmpCourse;
use App\Models\M_EmpEducation;
use App\Models\M_EmpFamily;
use App\Models\M_EmpFamilyCore;
use App\Models\M_EmpJob;
use App\Models\M_EmpLicense;
use App\Models\M_Employee;
use App\Models\M_EmpSkill;
use App\Models\M_EmpVaccine;
use App\Models\M_AccessMenu;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Role;
use App\Models\M_Status;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Fill;
use PHPExcel_Worksheet_PageSetup;
use PHPExcel_Cell;
use Config\Services;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\StartEndTokenAwareAnalysis;

class Rpt_Employee extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
    }

    public function index()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $mBranch = new M_Branch($this->request);
        $mDivision = new M_Division($this->request);

        $employee = $this->model->find($this->session->get('md_employee_id'));

        $roleKACAB = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_KACAB');
        $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
        $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));

        //** This for getting Akses Branch & Division from Employee ID */
        $arrEmployee = $this->model->getChartEmployee($this->session->get('md_employee_id'));
        $empSession = $this->session->get('md_employee_id');
        $arrEmpStr = implode(" ,", $arrEmployee);

        $BrchEmp = $mEmpBranch->select('md_branch_id')->where($this->model->primaryKey, $empSession)->findAll();
        $arrEmpBranch = array_column($BrchEmp, 'md_branch_id');
        $arrEmpBranchStr = implode(" ,", $arrEmpBranch);

        $DivEmp = $mEmpDivision->select('md_division_id')->where($this->model->primaryKey, $empSession)->findAll();
        $arrEmpDiv = array_column($DivEmp, 'md_division_id');

        $arrEmpDivStr = implode(" ,", $arrEmpDiv);

        /** This for set WhereClause */
        $whereEmp = "";
        $whereBranch = "";
        $whereDiv = "";

        if ($roleKACAB) {
            $empBranch = $this->model->getEmployeeBased($arrEmpBranch);

            $whereEmp = "md_employee_id IN (" . implode(" ,", $empBranch) . ")";
            $whereBranch = "md_branch_id IN ($arrEmpBranchStr)";

            $allDiv = $mEmpDivision->select('md_division_id')->where('isactive', 'Y')->findAll();
            $allDivEmp = array_column($allDiv, 'md_division_id');

            $whereDiv = "md_division_id IN (" . implode(" ,", $allDivEmp) . ")";
        } else if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
            $arrBranch = $arrAccess["branch"];
            $arrDiv = $arrAccess["division"];

            $arrEmpBased = $this->model->getEmployeeBased($arrBranch, $arrDiv);

            if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                $arrMerge = implode(" ,", array_unique(array_merge($arrEmpBased, $arrEmployee)));
                $arrBrchMerge = implode(" ,", array_unique(array_merge($arrBranch, $arrEmpBranch)));
                $arrDivMerge = implode(" ,", array_unique(array_merge($arrDiv, $arrEmpDiv)));

                $whereEmp = "md_employee_id IN ($arrMerge)";
                $whereBranch = "md_branch_id IN ($arrBrchMerge)";
                $whereDiv = "md_division_id IN ($arrDivMerge)";
            } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                $whereBranch = "md_branch_id IN (" . implode(" ,", $arrBranch) . ")";
                $whereDiv = "md_division_id IN (" . implode(" ,", $arrDiv) . ")";
                $whereEmp = "md_employee_id IN (" . implode(" ,", $arrEmpBased) . ")";
            } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                $whereEmp = " md_employee_id IN ($arrEmpStr)";
                $whereBranch = "md_branch_id IN ($arrEmpBranchStr)";
                $whereDiv = "md_division_id IN ($arrEmpDivStr)";
            } else {
                $whereEmp = "md_employee_id IN ($empSession)";
            }
        } else if (!empty($this->session->get('md_employee_id'))) {
            $whereEmp = "md_employee_id IN ($arrEmpStr)";
            $whereBranch = "md_branch_id IN ($arrEmpBranchStr)";
            $whereDiv = "md_division_id IN ($arrEmpDivStr)";
        } else {
            $whereEmp = "md_employee_id IN ($empSession)";
        }

        if ($employee->md_levelling_id > 100003 && !$roleEmp) {
            $whereEmp = "md_employee_id = $employee->md_employee_id";
        }

        $whereEmp .= " AND md_status_id IN (100001, 100002)";

        $data = [
            'ref_employee' => $this->model->getEmployeeValue($whereEmp)->getResult(),
            'ref_branch' => $mBranch->select('md_branch_id, name')->where($whereBranch)->findAll(),
            'ref_division' => $mDivision->select('md_division_id, name')->where($whereDiv)->findAll()
        ];

        return $this->template->render('report/employee/v_employee', $data);
    }

    public function showAll()
    {
        $post = $this->request->getPost();

        $mAccess = new M_AccessMenu($this->request);
        $mEmpFamily = new M_EmpFamily($this->request);
        $mEmpFamilyCore = new M_EmpFamilyCore($this->request);
        $mEmpEducation = new M_EmpEducation($this->request);
        $mEmpJob = new M_EmpJob($this->request);
        $mEmpVaccine = new M_EmpVaccine($this->request);
        $mEmpCourses = new M_EmpCourse($this->request);
        $mEmpSkills = new M_EmpSkill($this->request);
        $mEmpCourses = new M_EmpCourse($this->request);
        $mEmpContact = new M_EmpContact($this->request);
        $mEmpLicense = new M_EmpLicense($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);

        if (isset($post['md_branch_id']))
            $md_branch_id = implode(", ", $post['md_branch_id']);

        if (isset($post['md_division_id']))
            $md_division_id = implode(", ", $post['md_division_id']);

        if (isset($post['md_employee_id']))
            $md_employee_id = implode(", ", $post['md_employee_id']);

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();

        // Settingan awal file excel
        $excel->getProperties()->setCreator('Karyawan')
            ->setLastModifiedBy('Karyawan')
            ->setTitle("Karyawan")
            ->setSubject("Karyawan")
            ->setDescription("Karyawan")
            ->setKeywords("Laporan Karyawan");

        // Buat sebuah variabel untuk menampung pengaturan style dari header tabel
        $style_col = array(
            'font' => array('bold' => true), // Set font nya jadi bold
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, // Set text jadi ditengah secara horizontal (center)
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border top dengan garis tipis
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),  // Set border right dengan garis tipis
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border bottom dengan garis tipis
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN) // Set border left dengan garis tipis
            )
        );

        // Buat sebuah variabel untuk menampung pengaturan style dari isi tabel
        $style_row = array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border top dengan garis tipis
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),  // Set border right dengan garis tipis
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border bottom dengan garis tipis
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN) // Set border left dengan garis tipis
            )
        );

        $title = [
            ['Data Diri', $this->model],
            ['Keluarga Inti', $mEmpFamilyCore],
            ['Keluarga Setelah Menikah', $mEmpFamily],
            ['Riwayat Pendidikan', $mEmpEducation],
            ['Riwayat Pekerjaan', $mEmpJob],
            ['Riwayat Vaksin', $mEmpVaccine],
            ['Keterampilan', $mEmpSkills],
            ['Kursus', $mEmpCourses],
            ['Penguasaan Bahasa', $mEmpSkills],
            ['Kontak Darurat', $mEmpContact],
            ['SIM', $mEmpLicense]
        ];

        $index = 0;
        $test;

        /** Where clause for checking access*/
        $roleKACAB = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_KACAB');
        $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
        $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
        $arrEmployee = $this->model->getChartEmployee($this->session->get('md_employee_id'));
        $arrEmpStr = implode(" ,", $arrEmployee);
        $employee = $this->model->find($this->session->get('md_employee_id'));
        $whereClause = "isactive = 'Y'";

        if (isset($md_branch_id)) {
            $whereClause .= " AND md_branch_id IN ($md_branch_id)";
        }

        if (isset($md_division_id)) {
            $whereClause .= " AND md_division_id IN ($md_division_id)";
        }

        if (isset($md_employee_id)) {
            $whereClause .= " AND md_employee_id IN ($md_employee_id)";
        } else {
            // This if for set employee_id when user dont choose any of one employee
            if ($roleKACAB) {
                $BrchEmp = $mEmpBranch->select('md_branch_id')->where($this->model->primaryKey, $this->session->get('md_employee_id'))->findAll();
                $arrEmpBranch = [];

                if ($BrchEmp)
                    foreach ($BrchEmp as $row) :
                        $arrEmpBranch[] = $row->md_branch_id;
                    endforeach;

                $empBranch = $this->model->getEmployeeBased($arrEmpBranch);
                $whereClause .= " AND md_employee_id IN (" . implode(" ,", $empBranch) . ")";
            } else if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $this->model->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = implode(" ,", array_unique(array_merge($arrEmpBased, $arrEmployee)));

                    $whereClause .= " AND md_employee_id IN ($arrMerge)";
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $whereClause .= " AND md_employee_id IN (" . implode(" ,", $arrEmpBased) . ")";
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $whereClause .= " AND md_employee_id IN ($arrEmpStr)";
                } else {
                    $whereClause .= " AND md_employee_id IN (" . $this->session->get('md_employee_id') . ")";
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $whereClause .= " AND md_employee_id IN ($arrEmpStr)";
            } else {
                $whereClause .= " AND md_employee_id IN (" . $this->session->get('md_employee_id') . ")";
            }

            if ($employee->md_levelling_id > 100003 && !$roleEmp) {
                $whereClause .= " AND md_employee_id = $employee->md_employee_id";
            }
        }

        $dataEmpNotMarried = [];

        // For creating sheet based on title variable
        foreach ($title as $value) {
            if ($index > 0) {
                $excel->createSheet();
            }
            $sheet = $excel->setActiveSheetIndex($index);
            $sheet->setTitle("$value[0]");

            if ($value[0] === "Penguasaan Bahasa") {
                $data = $value[1]->getDataReportEmpLang($whereClause)->getResult();
            } else {
                $data = $value[1]->getDataReport($whereClause)->getResult();
            }

            $col = 'A';
            $row = 1;

            // For set header and remove col md_employee_id, md_branch_id and md_division_id
            if ($data) {
                // For getting data employee not married
                if ($value[0] === "Data Diri") {
                    foreach ($data as $val) {
                        if ($val->{"Status Menikah"} == 'Belum Kawin') {
                            $dataEmpNotMarried[] = $val->md_employee_id;
                        }
                    }
                }

                $header = array_keys((array)$data[0]);
                $header = array_slice($header, 0, count($header) - 5);

                foreach ($header as $column) {
                    $sheet->setCellValue($col . $row, $column);
                    $sheet->getStyle($col . $row)->applyFromArray($style_col);

                    $col++;
                }

                $row = 2;

                // For insert cell data
                foreach ($data as $val) {
                    if ($value[0] == "Keluarga Setelah Menikah" && in_array($val->md_employee_id, $dataEmpNotMarried))
                        continue;

                    $colData = array_slice((array) $val, 0, count((array)$val) - 5);
                    $col = 'A';

                    foreach ($colData as $item) {
                        // $sheet->setCellValue($col . $row, $item);
                        $sheet->setCellValueExplicit($col . $row, $item, PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->getStyle($col . $row)->applyFromArray($style_row);
                        $col++;
                    }

                    $row++;
                }

                // Set Auto Width
                foreach (range(0, 50) as $columnID) {
                    $columnLetter = PHPExcel_Cell::stringFromColumnIndex($columnID);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }
            } else {
                $sheet->setCellValue('A1', "TIDAK ADA DATA");
                $sheet->getStyle('A1')->getFont()->setBold(TRUE);
                $sheet->getStyle('A1')->getFont()->setSize(15);
            }

            $index++;
        }

        $sheet = $excel->setActiveSheetIndex(0);
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Karyawan.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $write->save('php://output');
        exit();
    }
}