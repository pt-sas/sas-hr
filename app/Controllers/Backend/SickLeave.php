<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_AbsentDetail;
use App\Models\M_AssignmentDate;
use App\Models\M_Holiday;
use App\Models\M_Attendance;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_Division;
use App\Models\M_MedicalCertificate;
use App\Models\M_SubmissionCancelDetail;
use TCPDF;
use Config\Services;

class SickLeave extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/sickleave/v_sickleave', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $roleEmpRepren = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Representative');
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if ($roleEmpRepren && empty($this->session->get('md_employee_id'))) {
                    $whereClause = 'md_employee.md_levelling_id IN (100005, 100006)';
                    $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv, $whereClause);
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_absent.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_absent.submissiontype'] = $this->model->Pengajuan_Sakit;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = !is_null($value->receiveddate) ? format_dmytime($value->receiveddate, '-') : "";
                $row[] = $value->reason;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus, $this->BTN_Print);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        $mEmployee = new M_Employee($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mRule = new M_Rule($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');
            $file2 = $this->request->getFile('image2');
            $file3 = $this->request->getFile('image3');

            $post["submissiontype"] = $this->model->Pengajuan_Sakit;
            $post["necessary"] = 'SA';
            $today = date('Y-m-d');
            $employeeId = $post['md_employee_id'];
            $day = date('w');

            try {
                $img_name = "";
                $img2_name = "";
                $img3_name = "";
                $value = "";

                if (!empty($post['md_employee_id'])) {
                    $row = $mEmployee->find($post['md_employee_id']);
                    $lenPos = strpos($row->getValue(), '-');
                    $value = substr_replace($row->getValue(), "", $lenPos);
                    $ymd = date('YmdHis');
                }

                if ($file && $file->isValid()) {
                    $ext = $file->getClientExtension();
                    $img_name = $this->model->Pengajuan_Sakit . '_' . $value . '_' . $ymd . '.' . $ext;
                    $post['image'] = $img_name;
                }

                if ($file2 && $file2->isValid()) {
                    $ext2 = $file2->getClientExtension();
                    $img2_name = $this->model->Pengajuan_Sakit . '_' . $value . '2_' . $ymd . '.' . $ext2;
                    $post['image2'] = $img2_name;
                }

                if ($file3 && $file3->isValid()) {
                    $ext3 = $file3->getClientExtension();
                    $img3_name = $this->model->Pengajuan_Sakit . '_' . $value . '3_' . $ymd . '.' . $ext3;
                    $post['image3'] = $img3_name;
                }

                if (!$this->validation->run($post, 'sakit')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = date('Y-m-d', strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $nik = $post['nik'];
                    $submissionDate = $post['submissiondate'];
                    $subDate = date('Y-m-d', strtotime($submissionDate));

                    $rule = $mRule->where([
                        'name'      => 'Sakit',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $post['md_employee_id'],
                        'validfrom <='      => $today
                    ])->orderBy('validfrom', 'ASC')->first();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else {
                        //TODO : Get Work Detail
                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);
                        $dateWorkRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

                        $dayClause = [];
                        $workClause = [];
                        foreach ($dateWorkRange as $value) {
                            $date = date('Y-m-d', strtotime($value));
                            $day = strtoupper(formatDay_idn(date('w', strtotime($value))));

                            $dayClause[] = "'{$day}'";
                            $workClause[] = "'{$date}'";
                        }

                        $dayClause = implode(", ", $dayClause);
                        $workClause = implode(", ", $workClause);

                        //TODO: Get Work Detail by day
                        $whereClause .= " AND md_day.name IN ({$dayClause})";
                        $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                        //TODO : Get attendance present employee
                        $whereClause = "v_attendance.nik = '{$nik}'";
                        $whereClause .= " AND v_attendance.date IN ({$workClause})";
                        $attPresent = $mAttendance->getAttendance($whereClause)->getResult();

                        //TODO : Get attendance not present employee
                        $whereClause = "v_attendance.nik = '{$nik}'";
                        $whereClause .= " AND v_attendance.date = '{$startDate}'";
                        $attNotPresent = $mAttendance->getAttendance($whereClause)->getRow();

                        //TODO : Get next day attendance from enddate
                        $presentNextDate = null;

                        if ($startDate <= $subDate) {
                            $whereClause = "trx_absent.nik = $nik";
                            $whereClause .= " AND DATE_FORMAT(trx_absent.enddate, '%Y-%m-%d') > '$endDate'";
                            $whereClause .= " AND trx_absent.docstatus = '{$this->DOCSTATUS_Completed}'";
                            $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                            $trxPresentNextDay = $this->modelDetail->getAbsentDetail($whereClause)->getRow();

                            if (is_null($trxPresentNextDay)) {
                                $whereClause = "v_attendance.nik = '{$nik}'";
                                $whereClause .= " AND v_attendance.date > '{$endDate}'";
                                $attPresentNextDay = $mAttendance->getAttendance($whereClause)->getRow();

                                $presentNextDate = $attPresentNextDay ? $attPresentNextDay->date : $endDate;
                            } else {
                                $presentNextDate = $trxPresentNextDay->date;
                            }

                            $nextDate = lastWorkingDays($presentNextDate, $holidays, $minDays, false, $daysOff);

                            //* last index of array from variable nextDate
                            $lastDate = end($nextDate);
                        }

                        //TODO : Get submission
                        $dateStartClause = date('Y-m-d', strtotime($startDate));

                        $whereClause = "trx_absent.nik = '{$nik}'";
                        $whereClause .= " AND DATE_FORMAT(trx_absent.startdate, '%Y-%m-%d') >= '{$dateStartClause}' AND DATE_FORMAT(trx_absent.enddate, '%Y-%m-%d') <= '{$endDate}'";
                        $whereClause .= " AND trx_absent.docstatus = '{$this->DOCSTATUS_Completed}'";
                        $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                        $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($submissionDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($presentNextDate && !($lastDate >= $subDate) && $work && is_null($attNotPresent)) {
                            $lastDate = format_dmy($lastDate, '-');

                            $response = message('success', false, "Maksimal tanggal pengajuan pada tanggal : {$lastDate}");
                        } else if ($attPresent) {
                            $date = implode(", ", array_map(function ($value) {
                                return format_dmy($value->date, '-');
                            }, $attPresent));

                            $response = message('success', false, "Ada kehadiran, tidak bisa mengajukan pada tanggal : [{$date}]");
                        } else if ($trx) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                        } else {
                            $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                            if ($this->isNew()) {
                                // uploadFile($file, $path, $img_name);

                                if ($file && $file->isValid())
                                    uploadFile($file, $path, $img_name);

                                if ($file2 && $file2->isValid())
                                    uploadFile($file2, $path, $img2_name);

                                if ($file3 && $file3->isValid())
                                    uploadFile($file3, $path, $img3_name);
                            } else {
                                $row = $this->model->find($this->getID());

                                // if (!empty($post['image']) && !empty($row->getImage()) && $post['image'] !== $row->getImage()) {
                                //     if (file_exists($path . $row->getImage()))
                                //         unlink($path . $row->getImage());

                                //     uploadFile($file, $path, $img_name);
                                // }

                                if (empty($post['image']) && !empty($row->getImage()) && file_exists($path . $row->getImage())) {
                                    unlink($path . $row->getImage());
                                } else {
                                    uploadFile($file, $path, $img_name);
                                }

                                if (empty($post['image2']) && !empty($row->getImage2()) && file_exists($path . $row->getImage2())) {
                                    unlink($path . $row->getImage2());
                                } else {
                                    uploadFile($file2, $path, $img2_name);
                                }

                                if (empty($post['image3']) && !empty($row->getImage3()) && file_exists($path . $row->getImage3())) {
                                    unlink($path . $row->getImage3());
                                } else {
                                    uploadFile($file3, $path, $img3_name);
                                }
                            }

                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Sakit, $post);
                                $this->entity->setDocumentNo($docNo);
                            }

                            $response = $this->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                if (file_exists($path . $list[0]->getImage())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage($path . $list[0]->getImage());
                } else {
                    $list[0]->setImage(null);
                }

                if (!empty($list[0]->getImage2()) && file_exists($path . $list[0]->getImage2())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage2($path . $list[0]->getImage2());
                } else {
                    $list[0]->setImage2(null);
                }

                if (!empty($list[0]->getImage3()) && file_exists($path . $list[0]->getImage3())) {
                    $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                    $list[0]->setImage3($path . $list[0]->getImage3());
                } else {
                    $list[0]->setImage3(null);
                }

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                //* Need to set data into date field in form
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));
                $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine('edit', $detail)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function destroy($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $result = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function processIt()
    {
        $cWfs = new WScenario();
        $mMedical = new M_MedicalCertificate($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        // TODO : Get Document Medical Status In Progress And Draft
                        $docMedical = $mMedical->where('trx_absent_id', $row->trx_absent_id)->whereIn('docstatus', ['DR', 'IP'])->first();

                        // TODO : Check if this submission have Sick Letter
                        if (empty($row->image) && empty($row->image2) && empty($row->image3) && empty($row->img_medical)) {
                            $response = message('error', true, 'Pengajuan ini belum ada Foto Surat Sakit maupun Keterangan Surat Sakit');
                        } else if ($docMedical) {
                            $response = message('error', true, "Pengajuan ini ada surat keterangan sakit yang masih pending dengan nomor : {$docMedical->documentno}");
                        } else {
                            $data = [
                                'id'        => $_ID,
                                'created_by' => $this->access->getSessionUser(),
                                'updated_by' => $this->access->getSessionUser()
                            ];

                            $this->model->createAbsentDetail($data, $row);

                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if (($_DocAction === $this->DOCSTATUS_Unlock || $_DocAction === $this->DOCSTATUS_Voided)) {
                        $response = message('error', true, 'Tidak bisa diproses');
                    } else {
                        $this->entity->setDocStatus($_DocAction);
                        $response = $this->save();
                    }
                } else {
                    $response = message('error', true, 'Silahkan pilih tindakan terlebih dahulu.');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function tableLine($set = null, $detail = [])
    {
        $table = [];

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $docNoRef = "";
                $line = $this->model->where('trx_absent_id', $row->trx_absent_id)->first();

                if (!empty($row->ref_absent_detail_id)) {
                    if ($row->table === 'trx_submission_cancel_detail') {
                        $refModel = new M_SubmissionCancelDetail($this->request);
                    } else if ($row->table === 'trx_assignment') {
                        $refModel = new M_AssignmentDate($this->request);
                    } else {
                        $refModel = new M_AbsentDetail($this->request);
                    }
                    $lineRef = $refModel->getDetail($refModel->primaryKey, $row->ref_absent_detail_id)->getRow();
                    $docNoRef = $lineRef->documentno;
                }

                $table[] = [
                    $row->lineno,
                    format_dmy($row->date, '-'),
                    $line->getDocumentNo(),
                    $docNoRef,
                    statusRealize($row->isagree)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function exportPDF($id)
    {
        $mEmployee = new M_Employee($this->request);
        $mDivision = new M_Division($this->request);
        $list = $this->model->find($id);
        $employee = $mEmployee->where($mEmployee->primaryKey, $list->md_employee_id)->first();
        $division = $mDivision->where($mDivision->primaryKey, $list->md_division_id)->first();
        $tglpenerimaan = '';

        if ($list->receiveddate !== null) {
            $tglpenerimaan = format_dmy($list->receiveddate, '-');
        };

        //bagian PF
        $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->Cell(140, 0, 'pt. sahabat abadi sejahtera', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(50, 0, 'No Form : ' . $list->documentno, 0, 1, 'L', false, '', 0, false);
        $pdf->setFont('helvetica', 'B', 20);
        $pdf->Cell(0, 25, 'FORM SAKIT', 0, 1, 'C');
        $pdf->setFont('helvetica', '', 12);
        //Ini untuk bagian field nama dan tanggal pengajuan
        $pdf->Cell(30, 0, 'Nama ', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . $employee->fullname, 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, 'Tanggal Pengajuan', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(30, 0, ': ' . format_dmy($list->submissiondate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini untuk bagian field divisi dan Tanggal diterima
        $pdf->Cell(30, 0, 'Divisi ', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(90, 0, ': ' . $division->name, 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, 'Tanggal Diterima', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(30, 0, ': ' . $tglpenerimaan, 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(10);
        //Ini bagian tanggal ijin dan jam
        $pdf->Cell(30, 0, 'Tanggal', 0, 0, 'L', false, '', 0, false);
        $pdf->Cell(40, 0, ': ' . format_dmy($list->startdate, '-') . ' s/d ' . format_dmy($list->enddate, '-'), 0, 1, 'L', false, '', 0, false);
        $pdf->Ln(2);
        //Ini bagian Alasan
        $pdf->Cell(30, 0, 'Alasan', 0, 0, 'L');
        $pdf->Cell(3, 0, ':', 0, 0, 'L');
        $pdf->MultiCell(0, 20, $list->reason, 0, '', false, 1, null, null, false, 0, false, false, 20);
        $pdf->Ln(2);
        //Bagian ttd
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(63, 0, 'Dibuat oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Disetujui oleh,', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Diketahui oleh,', 0, 0, 'C');
        $pdf->Ln(25);
        $pdf->Cell(63, 0, $employee->fullname, 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 0, 'C');
        $pdf->Cell(63, 0, '(                          )', 0, 1, 'C');
        $pdf->Cell(63, 0, 'Karyawan Ybs', 0, 0, 'C');
        $pdf->Cell(63, 0, 'Mgr. Dept. Ybs', 0, 0, 'C');
        $pdf->Cell(63, 0, 'HRD', 0, 0, 'C');

        $this->response->setContentType('application/pdf');
        $pdf->Output('detail-laporan.pdf', 'I');
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();
            $response = [];

            try {
                $mAccess = new M_AccessMenu($this->request);
                $mEmployee = new M_Employee($this->request);

                /**
                 * Hak akses
                 */
                $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
                $roleEmpRepren = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Representative');
                $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
                $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

                if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                    $arrBranch = $arrAccess["branch"];
                    $arrDiv = $arrAccess["division"];

                    $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                    if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                        $arrMerge = implode(',', array_unique(array_merge($arrEmpBased, $arrEmployee)));

                        $where = "trx_absent.md_employee_id IN ({$arrMerge})";
                    } else if ($roleEmpRepren && !empty($this->session->get('md_employee_id'))) {
                        $whereClause = 'md_employee.md_levelling_id IN (100005, 100006)';
                        $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv, $whereClause);
                        $arrMerge = implode(',', array_unique(array_merge($arrEmpBased, $arrEmployee)));

                        $where = "trx_absent.md_employee_id IN ({$arrMerge})";
                    } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                        $arrEmp = implode(',', $arrEmployee);
                        $where = "trx_absent.md_employee_id IN ({$arrEmp})";
                    } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                        $arrEmp = implode(',', $arrEmpBased);
                        $where = "trx_absent.md_employee_id IN ({$arrEmp})";
                    }
                } else if (!empty($this->session->get('md_employee_id'))) {
                    $arrEmp = implode(',', $arrEmployee);
                    $where = "trx_absent.md_employee_id IN ({$arrEmp})";
                }

                $where .= " AND trx_absent.md_employee_id != {$this->session->get('md_employee_id')}";
                $where .= " AND trx_absent.submissiontype = {$this->model->Pengajuan_Sakit}";
                $where .= " AND trx_absent.docstatus = 'DR'";
                $where .= " AND trx_medical_certificate.trx_medical_certificate_id IS NULL";
                $where .= " AND trx_absent.startdate = trx_absent.enddate";

                if (isset($post['search'])) {
                    $search = $post['search'];
                    $where .= " AND trx_absent.documentno LIKE '%{$search}%'";
                }

                $list = $this->model->getSickLeaveSubmission($where)->getResult();

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->trx_absent_id;
                    $response[$key]['text'] = $row->documentno . ' - ' . $row->employee_fullname;
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getDetail()
    {
        if ($this->request->isAjax()) {
            $mEmpWork = new M_EmpWorkDay($this->request);
            $mWorkDetail = new M_WorkDetail($this->request);
            $mHoliday = new M_Holiday($this->request);
            $post = $this->request->getVar();
            $response = [];

            try {
                $holiday = $mHoliday->getHolidayDate();
                $today = date('Y-m-d');
                $list = $this->model->where('trx_absent_id', $post['trx_absent_id'])->first();

                /**
                 *  This Section for getting employee days off
                 */
                $workDay = $mEmpWork->where([
                    'md_employee_id'    => $list->md_employee_id,
                    'validfrom <='      => $today
                ])->orderBy('validfrom', 'ASC')->first();

                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = {$list->md_employee_id}";
                $whereClause .= " AND md_work.md_work_id = {$workDay->md_work_id}";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                $daysOff = getDaysOff($workDetail);

                $dateRange = getDatesFromRange($list->startdate, $list->enddate, $holiday, 'Y-m-d', 'all', $daysOff);
                $listDate = [];
                foreach ($dateRange as $date) {
                    $nextDateIsDaysOff = in_array(date('w', strtotime("$date +1 day")), $daysOff);
                    $lastDateIsDaysOff = in_array(date('w', strtotime("$date -1 day")), $daysOff);
                    $nextDateIsHoliday = in_array(date('Y-m-d', strtotime("$date +1 day")), $holiday);
                    $lastDateIsHoliday = in_array(date('Y-m-d', strtotime("$date -1 day")), $holiday);

                    if (!$nextDateIsDaysOff && !$lastDateIsDaysOff && !$nextDateIsHoliday && !$lastDateIsHoliday) {
                        $listDate[] = ['id' => $date, 'text' => format_dmy($date, '-')];
                    }
                }

                $list->date = $listDate;

                $response = $list;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
