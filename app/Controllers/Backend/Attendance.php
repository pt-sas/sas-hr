<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Attendance;
use App\Models\M_Configuration;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_EmployeeDeparture;
use App\Models\M_EmpWorkDay;
use App\Models\M_Holiday;
use App\Models\M_Message;
use App\Models\M_WorkDetail;
use App\Models\M_NotificationText;
use App\Models\M_User;
use Config\Services;
use Html2Text\Html2Text;
use DateTime;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Fill;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
use PHPExcel;
use PHPExcel_Worksheet_PageSetup;

class Attendance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function reportIndex()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/attendance/v_attendance', $data);
    }

    public function reportShowAll()
    {
        $post = $this->request->getVar();
        $mEmployee = new M_Employee($this->request);

        $recordTotal = 0;
        $recordsFiltered = 0;
        $data = [];

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                // TODO : Get Employee Access
                $empList = $this->access->getEmployeeData();

                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "date") {
                            $datetime = urldecode($value['value']);
                            $date = explode(" - ", $datetime);
                        }

                        if ($value['name'] === "md_employee_id") {
                            $empList = $value['value'];
                        }
                    }
                }

                $table = "v_attendance";
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $search = $this->request->getPost('search');
                $sort = ['v_attendance.date' => 'ASC', 'v_attendance.nik' => 'ASC'];

                $where['v_attendance.md_employee_id'] = ['value' => $empList];
                $where['md_employee.md_status_id'] = ['value' => [$this->Status_PERMANENT, $this->Status_PROBATION, $this->Status_KONTRAK, $this->Status_MAGANG, $this->Status_FREELANCE]];

                $number = $this->request->getPost('start');
                $list = array_unique($this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where), SORT_REGULAR);

                $dataAttendance = [];
                foreach ($list as $val) {
                    $dataAttendance[$val->md_employee_id][$val->date] = $val;
                }

                //* Get Date Range
                $dateStart = isset($date) ? date('Y-m-d', strtotime($date[0])) : date('Y-m-d');
                $dateEnd = isset($date) ? date('Y-m-d', strtotime($date[1])) : date('Y-m-d');
                $dateRange = getDatesFromRange($dateStart, $dateEnd, [], 'Y-m-d', 'all');

                $empList = $mEmployee->whereIn('md_status_id', [$this->Status_PERMANENT, $this->Status_PROBATION, $this->Status_KONTRAK, $this->Status_MAGANG, $this->Status_FREELANCE])
                    ->whereIn('md_employee_id', $empList)->findAll();

                foreach ($empList as $employee) :
                    foreach ($dateRange as $date) {
                        $row = [];
                        $number++;

                        $clock_in = '';
                        $clock_out = '';

                        if (isset($dataAttendance[$employee->md_employee_id][$date])) {
                            $att = $dataAttendance[$employee->md_employee_id][$date];

                            if (!empty($att->clock_in)) {
                                if ($att->is_late == 'Y') {
                                    $clock_in = "<small class='text-danger'>$att->clock_in</small>";
                                } else {
                                    $clock_in = $att->clock_in;
                                }
                            }

                            if (!empty($att->clock_out)) {
                                if ($att->is_leave_early == 'Y') {
                                    $clock_out = "<small class='text-danger'>$att->clock_out</small>";
                                } else {
                                    $clock_out = $att->clock_out;
                                }
                            }
                        }

                        $row[] = $number;
                        $row[] = $employee->nik;
                        $row[] = $employee->fullname;
                        $row[] = format_dmy($date, "-");
                        $row[] = $clock_in;
                        $row[] = $clock_out;
                        $data[] = $row;
                    }
                endforeach;

                $recordTotal = count($data);
                $recordsFiltered = count($data);
            }

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $recordTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function indexSummary()
    {
        $data = [
            'month' => date('M-Y'),
        ];

        return $this->template->render('report/attendancesummary/v_report_attendance_summary', $data);
    }

    public function showAllSummary()
    {
        $post = $this->request->getPost();

        $mEmployee = new M_Employee($this->request);
        $mAbsent = new M_Absent($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAttendance = new M_Attendance($this->request);

        $periode = date('m-Y', strtotime($post['periode']));
        $md_branch_id = isset($post['md_branch_id']) ? $post['md_branch_id'] : null;
        $md_division_id = isset($post['md_division_id']) ? $post['md_division_id'] : null;
        $md_employee_id = isset($post['md_employee_id']) ? $post['md_employee_id'] : $this->access->getEmployeeData();

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Rekap Kehadiran')
            ->setTitle("Laporan Rekap Kehadiran");

        // Style untuk header kolom (teks bold dan rata tengah)
        $style_col = $this->createBorderStyle(true, true);

        // Style untuk baris normal
        $style_row = $this->createBorderStyle();

        // Style untuk baris ada transaksi
        $style_trx = $this->createBorderStyle(false, true, "FFFF00");

        // Style untuk baris tidak ada transaksi
        $style_no_trx = $this->createBorderStyle(false, true);

        $sheet = $excel->setActiveSheetIndex(0);

        //** This set header report */
        $sheet->setCellValue('A2', "LAPORAN REKAP KEHADIRAN");
        $sheet->mergeCells('A2:P2');
        $sheet->getStyle('A2:P2')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A2:P2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //** This set Date report */
        $sheet->setCellValue('P1', date('M-Y', strtotime($post['periode'])));
        $sheet->getStyle('P1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        //TODO : Header kolom dan merge cell
        $headers = [
            'A' => ['text' => 'No'],
            'B' => ['text' => 'NIK'],
            'C' => ['text' => 'Nama'],
            'D' => ['text' => 'Sakit', 'submissiontype' => $mAbsent->Pengajuan_Sakit],
            'E' => ['text' => 'Cuti', 'submissiontype' => $mAbsent->Pengajuan_Cuti],
            'F' => ['text' => 'Ijin', 'submissiontype' => $mAbsent->Pengajuan_Ijin],
            'G' => ['text' => 'I. Resmi', 'submissiontype' => $mAbsent->Pengajuan_Ijin_Resmi],
            'H' => ['text' => 'Alpa', 'submissiontype' => $mAbsent->Pengajuan_Alpa],
            'I' => ['text' => 'TK 1 Hari', 'submissiontype' => $mAbsent->Pengajuan_Tugas_Kantor],
            'J' => ['text' => 'Penugasan', 'submissiontype' => $mAbsent->Pengajuan_Penugasan],
            'K' => ['text' => 'TK 1/2 Hari', 'submissiontype' => $mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari],
            'L' => ['text' => 'Telat < 30 MNT'],
            'M' => ['text' => 'Telat > 30 MNT'],
            'N' => ['text' => 'Pulang Cepat', 'submissiontype' => $mAbsent->Pengajuan_Pulang_Cepat],
            'O' => ['text' => 'Lupa Absen Masuk', 'submissiontype' => $mAbsent->Pengajuan_Lupa_Absen_Masuk],
            'P' => ['text' => 'Lupa Absen Pulang', 'submissiontype' => $mAbsent->Pengajuan_Lupa_Absen_Pulang],
        ];

        foreach ($headers as $cell => $info) {
            $sheet->setCellValue($cell . 4, $info['text']);
            $sheet->getStyle($cell . 4)->applyFromArray($style_col);
        }

        //** This getting to body report */
        $builder = $mEmployee
            ->distinct()
            ->select('md_employee.*')
            ->where([
                'md_employee.isactive'        => 'Y'
            ]);

        if (!empty($md_employee_id)) {
            $builder->whereIn('md_employee.md_employee_id', (array) $md_employee_id);
            $builder->whereIn('md_employee.md_status_id', [$this->Status_PERMANENT, $this->Status_PROBATION, $this->Status_KONTRAK]);
        }

        if (!empty($md_branch_id)) {
            $builder->join(
                'md_employee_branch eb',
                'eb.md_employee_id = md_employee.md_employee_id',
                'left'
            );
            $builder->whereIn('eb.md_branch_id', (array) $md_branch_id);
        }

        if (!empty($md_division_id)) {
            $builder->join(
                'md_employee_division ed',
                'ed.md_employee_id = md_employee.md_employee_id',
                'left'
            );
            $builder->whereIn('ed.md_division_id', (array) $md_division_id);
        }

        $sql = $builder->orderBy('md_employee.nik', 'ASC')->findAll();

        $holiday = $mHoliday->getHolidayDate();
        $holidays = implode(", ", $holiday);

        $number = 1;
        $numrow = 5;

        foreach ($sql as $row) {
            // TODO : Set Data Karyawan
            $sheet->setCellValue('A' . $numrow, $number);
            $sheet->getStyle('A' . $numrow)->applyFromArray($style_row);

            $sheet->setCellValue('B' . $numrow, $row->nik);
            $sheet->getStyle('B' . $numrow)->applyFromArray($style_row);

            $sheet->setCellValue('C' . $numrow, $row->fullname);
            $sheet->getStyle('C' . $numrow)->applyFromArray($style_row);

            foreach ($headers as $cell => $info) {
                if (($info['text'] != "Telat < 30 MNT" && $info['text'] != "Telat > 30 MNT") && !isset($info['submissiontype'])) continue;

                if (isset($info['submissiontype'])) {
                    $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}
                                    AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                                    AND MONTH(v_all_submission.date) = '{$periode}'
                                    AND v_all_submission.submissiontype = {$info['submissiontype']}";
                    $trx = count($mAbsent->getAllSubmission($whereClause)->getResult());
                } else {
                    if ($info['text'] == "Telat < 30 MNT") {
                        $whereClause = "(v_attendance.clock_in >= COALESCE(ADDTIME(mwd.startwork, '00:01:00'), '08:01:00') AND v_attendance.clock_in < COALESCE(ADDTIME(mwd.startwork, '00:31:00'), '08:31:00'))";
                    } else {
                        $whereClause = "v_attendance.clock_in >= COALESCE(ADDTIME(mwd.startwork, '00:31:00'), '08:31:00')";
                    }

                    $whereClause .= " AND v_attendance.md_employee_id = {$row->md_employee_id}
                                      AND MONTH(v_attendance.date) = '{$periode}'
                                      AND DATE(v_attendance.date) NOT IN ({$holidays})
                                      AND mwd.md_work_detail_id IS NOT NULL
                                      AND NOT EXISTS (SELECT 1
                                                      FROM trx_absent a
                                                      LEFT JOIN trx_absent_detail ad ON a.trx_absent_id = ad.trx_absent_id
                                                      WHERE a.md_employee_id = {$row->md_employee_id}
                                                      AND a.submissiontype = {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}
                                                      AND ad.isagree = '{$this->LINESTATUS_Disetujui}'
                                                      AND DATE(ad.date) = DATE(v_attendance.date)
                                                      AND TIME_FORMAT(a.startdate, '%H:%i') <= '12:00')";

                    $trx = count($mAttendance->getAttendance($whereClause)->getResult());
                }

                $sheet->setCellValue($cell . $numrow, '-');
                $sheet->getStyle($cell . $numrow)->applyFromArray($style_no_trx);

                if ($trx > 0) {
                    $sheet->setCellValue($cell . $numrow, $trx);
                    $sheet->getStyle($cell . $numrow)->applyFromArray($style_trx);
                }

                $sheet->getColumnDimension($cell)->setWidth(17);
            }

            $number++;
            $numrow++;
        }

        $sheet->getColumnDimension('A')->setWidth(4); // Set width kolom A
        $sheet->getColumnDimension('B')->setWidth(10); // Set width kolom B
        $sheet->getColumnDimension('C')->setWidth(25); // Set width kolom C
        // Set orientasi kertas jadi LANDSCAPE
        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $sheet->setTitle("Laporan Rekap Kehadiran");
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Rekap Kehadiran.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
        exit();
    }

    public function getClockInOut()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            try {
                $data = '';

                if ($post['typeform'] == 100008) {
                    $mAssignmentDate = new M_AssignmentDate($this->request);
                    $mAssignmentDetail = new M_AssignmentDetail($this->request);

                    $subDetail = $mAssignmentDate->find($post['id']);
                    $detail = $mAssignmentDetail->find($subDetail->{$mAssignmentDetail->primaryKey});

                    $att = $this->model->getAttBranch([
                        'v_attendance_branch.md_employee_id' => $detail->md_employee_id,
                        'v_attendance_branch.date' => date("Y-m-d", strtotime($subDetail->date)),
                        'v_attendance_branch.md_branch_id' => $post['md_branch_id']
                    ])->getRow();

                    $data = [
                        'clock_in' => $att && $att->clock_in ? format_time($att->clock_in) : '',
                        'clock_out' => $att && $att->clock_out ? format_time($att->clock_out) : ''
                    ];
                } else {
                    $att = $this->model->getAttendance([
                        'v_attendance.nik'        => $post['nik'],
                        'v_attendance.date'       => date("Y-m-d", strtotime($post['startdate']))
                    ])->getRow();

                    if ($post['typeform'] == 100012 && $att) {
                        $data = format_time($att->clock_in);
                    } else if ($post['typeform'] == 100013 && $att) {
                        $data = format_time($att->clock_out);
                    }
                }

                $response['clock'] = $data;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    // public function toDoCheckAbsent()
    // {
    //     $mEmployee = new M_Employee($this->request);
    //     $mNotifText = new M_NotificationText($this->request);
    //     $mUser = new M_User($this->request);
    //     $mWorkDetail = new M_WorkDetail($this->request);
    //     $mAbsent = new M_Absent($this->request);
    //     $mAbsentDetail = new M_AbsentDetail($this->request);
    //     $mHoliday = new M_Holiday($this->request);
    //     $mConfig = new M_Configuration($this->request);
    //     $mAssignment = new M_Assignment($this->request);
    //     $mEmpBranch = new M_EmpBranch($this->request);
    //     $mMessage = new M_Message($this->request);
    //     $cMail = new Mail();
    //     $cTelegram = new Telegram();

    //     set_time_limit(0);

    //     $holiday = $mHoliday->getHolidayDate();
    //     $today = date("Y-m-d");
    //     $formatIDNToday = strtoupper(formatDay_idn(date('w')));

    //     $yesterday = addBusinessDays($today, 1, $holiday, true);
    //     $formatIDNYesterday = strtoupper(formatDay_idn(date('w', strtotime($yesterday))));

    //     // TODO : Set Master Data Notification
    //     $dataNotifIn = $mNotifText->where('name', 'Belum Absen Masuk')->first();
    //     $subjectIn = $dataNotifIn->getSubject();
    //     $messageIn = str_replace(['(Var1)'], [$today], $dataNotifIn->getText());
    //     $dataNotifOut = $mNotifText->where('name', 'Belum Absen Pulang')->first();
    //     $subjectOut = $dataNotifOut->getSubject();
    //     $messageOut = str_replace(['(Var1)'], [$yesterday], $dataNotifOut->getText());
    //     $employee = $mEmployee->where('isactive', 'Y')->whereIn('md_status_id', [100001, 100002])->whereNotIn('md_levelling_id', [100001])->findAll();

    //     $configMNSOD = $mConfig->where('name', 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES')->first();

    //     $configMNSOD = $configMNSOD->value == 'Y' ? true : false;
    //     $lvlManager = 100003;

    //     //* Get All User
    //     $allUser = [];
    //     foreach ($mUser->where("isactive", 'Y')->findAll() as $value) {
    //         $allUser[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Workday Today
    //     $allWorkdayToday = [];
    //     $whereClause = "md_work_detail.isactive = 'Y'";
    //     $whereClause .= " AND md_employee_work.validfrom <= '{$today}'";
    //     $whereClause .= " AND md_employee_work.validto >= '{$today}'";
    //     $whereClause .= " AND md_day.name = '{$formatIDNToday}'";

    //     foreach ($mWorkDetail->getWorkDetail($whereClause)->getResult() as $value) {
    //         $allWorkdayToday[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Kunjungan Today
    //     $allTugasKunjunganToday = [];
    //     $whereClause = "DATE(trx_assignment_date.date) = '{$today}'
    //                 AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
    //                 AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
    //                 AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

    //     foreach ($mAssignment->getDetailData($whereClause)->getResult() as $value) {
    //         $allTugasKunjunganToday[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Submission Today
    //     $allSubmissionToday = [];
    //     $whereClause = "v_realization.date = '{$today}'";
    //     $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
    //     $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}', '{$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}')";
    //     $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";

    //     foreach ($mAbsentDetail->getAllSubmission($whereClause)->getResult() as $value) {
    //         $allSubmissionToday[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Workday Yesterday
    //     $allWorkdayYesterday = [];
    //     $whereClause = "md_work_detail.isactive = 'Y'";
    //     $whereClause .= " AND md_employee_work.validfrom <= '{$yesterday}'";
    //     $whereClause .= " AND md_employee_work.validto >= '{$yesterday}'";
    //     $whereClause .= " AND md_day.name = '{$formatIDNYesterday}'";

    //     foreach ($mWorkDetail->getWorkDetail($whereClause)->getResult() as $value) {
    //         $allWorkdayYesterday[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Kunjungan Yesterday
    //     $allTugasKunjunganYesterday = [];
    //     $whereClause = "DATE(trx_assignment_date.date) = '{$yesterday}'
    //                 AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
    //                 AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
    //                 AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

    //     foreach ($mAssignment->getDetailData($whereClause)->getResult() as $value) {
    //         $allTugasKunjunganYesterday[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Submission Yesterday
    //     $allSubmissionYesterday = [];
    //     $whereClause = "v_realization.date = '{$yesterday}'";
    //     $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
    //     $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}', '{$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}')";
    //     $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";

    //     foreach ($mAbsentDetail->getAllSubmission($whereClause)->getResult() as $value) {
    //         $allSubmissionYesterday[$value->md_employee_id] = $value;
    //     }

    //     //* Get All Submission Forget Absent Out & Leave Early Yesterday
    //     $allForgotAbsentLeave = [];
    //     $whereClause = "v_realization.date = '{$yesterday}'";
    //     $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
    //     $whereClause .= " AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang}, {$mAbsent->Pengajuan_Pulang_Cepat})";
    //     $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";

    //     foreach ($mAbsentDetail->getAllSubmission($whereClause)->getResult() as $value) {
    //         $allForgotAbsentLeave[$value->md_employee_id] = $value;
    //     }

    //     $dataNotif = [];
    //     $dataEmailNotifIn = [];
    //     $dataEmailNotifOut = [];
    //     foreach ($employee as $value) {
    //         $employeeID = $value->md_employee_id;

    //         $empBranch = $mEmpBranch->where('md_employee_id', $employeeID)->findAll();
    //         $user = isset($allUser[$employeeID]) ? $allUser[$employeeID] : null;

    //         if (empty($empBranch)) {
    //             continue;
    //         }

    //         $empBranch = implode(", ", array_column($empBranch, 'md_branch_id'));

    //         //** This Section for checking Today Absent In */

    //         // TODO : Get Workday Employee
    //         $workDetail = isset($allWorkdayToday[$employeeID]) ? $allWorkdayToday[$employeeID] : null;

    //         // TODO : Skip when runtime cronjob not same as starwork hour
    //         if (!empty($workDetail) && date('H') != date('H', strtotime($workDetail->startwork))) {
    //             continue;
    //         }

    //         // TODO : Get Submission Assignment
    //         $tugasKunjungan = isset($allTugasKunjunganToday[$employeeID]) ? $allTugasKunjunganToday[$employeeID] : null;

    //         // TODO : Get Attendance In Today
    //         if ($configMNSOD && $value->md_levelling_id <= $lvlManager) {
    //             $whereClause = "v_attendance.md_employee_id = {$employeeID}";
    //             $whereClause .= " AND v_attendance.date = '{$today}'";
    //             $whereClause .= " AND v_attendance.clock_in != ''";
    //             $absentIn = $this->model->getAttendance($whereClause)->getRow();
    //         } else {
    //             $whereClause = "v_attendance_branch.md_employee_id = {$employeeID}";
    //             $whereClause .= " AND v_attendance_branch.date = '{$today}'";
    //             $whereClause .= " AND v_attendance_branch.clock_in != ''";

    //             if ($tugasKunjungan) {
    //                 $whereClause .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_in_line}";
    //             } else {
    //                 $whereClause .= " AND v_attendance_branch.md_branch_id IN ({$empBranch})";
    //             }

    //             $absentIn = $this->model->getAttBranch($whereClause)->getRow();
    //         }

    //         // TODO : Get Submission Today
    //         $submission = isset($allSubmissionToday[$employeeID]) ? $allSubmissionToday[$employeeID] : null;

    //         if (!$absentIn && ($workDetail || $tugasKunjungan) && !$submission && !in_array($today, $holiday) && $dataNotifIn) {
    //             if (!empty($value->telegram_id)) {
    //                 $cTelegram->sendMessage($value->telegram_id, (new Html2Text($messageIn))->getText());
    //             }

    //             if ($user) {
    //                 $dataNotif[] = [
    //                     'created_by'    => 100001,
    //                     'updated_by'    => 100001,
    //                     'author_id'     => 100001,
    //                     'recipient_id'  => $user->sys_user_id,
    //                     'subject'       => $subjectIn,
    //                     'messagedate'   => date('Y-m-d H:i:s'),
    //                     'body'          => $messageIn
    //                 ];

    //                 if (!empty($user->email))
    //                     $dataEmailNotifIn[] = $user->email;
    //             }
    //         }

    //         //** This Section for checking Yesterday Absent Out*/

    //         // TODO : Get Workday Employee
    //         $workDetail = isset($allWorkdayYesterday[$employeeID]) ? $allWorkdayYesterday[$employeeID] : null;

    //         // TODO : Get Submission Assignment Yesterday
    //         $tugasKunjungan = isset($allTugasKunjunganYesterday[$employeeID]) ? $allTugasKunjunganYesterday[$employeeID] : null;

    //         // TODO : Get Submission Yesterday
    //         $submission = isset($allSubmissionYesterday[$employeeID]) ? $allSubmissionYesterday[$employeeID] : null;

    //         // TODO : Get Attendance Out Yesterday
    //         if ($configMNSOD && $value->md_levelling_id <= $lvlManager) {
    //             $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
    //             $whereClause .= " AND v_attendance.date = '{$yesterday}'";
    //             $whereClause .= " AND v_attendance.clock_out != ''";
    //             $absentOut = $this->model->getAttendance($whereClause)->getRow();
    //         } else {
    //             $whereClause = "v_attendance_branch.md_employee_id = {$value->md_employee_id}";
    //             $whereClause .= " AND v_attendance_branch.date = '{$yesterday}'";
    //             $whereClause .= " AND v_attendance_branch.clock_out != ''";

    //             if ($tugasKunjungan) {
    //                 $whereClause .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_out_line}";
    //             } else {
    //                 $whereClause .= " AND v_attendance_branch.md_branch_id IN ({$empBranch})";
    //             }

    //             $absentOut = $this->model->getAttBranch($whereClause)->getRow();
    //         }

    //         // TODO : Get Submission Forget Absent Leave Yesterday
    //         $forgotAbsentLeave = isset($allForgotAbsentLeave[$employeeID]) ? $allForgotAbsentLeave[$employeeID] : null;

    //         if ($workDetail && !$absentOut && !$forgotAbsentLeave && !$submission && $dataNotifOut) {
    //             if (!empty($value->telegram_id)) {
    //                 $cTelegram->sendMessage($value->telegram_id, (new Html2Text($messageOut))->getText());
    //             }

    //             if ($user) {
    //                 $dataNotif[] = [
    //                     'created_by'    => 100001,
    //                     'updated_by'    => 100001,
    //                     'author_id'     => 100001,
    //                     'recipient_id'  => $user->sys_user_id,
    //                     'subject'       => $subjectOut,
    //                     'messagedate'   => date('Y-m-d H:i:s'),
    //                     'body'          => $messageOut
    //                 ];

    //                 if (!empty($user->email))
    //                     $dataEmailNotifOut[] = $user->email;
    //             }
    //         }
    //     }

    //     //* Send Notif Apps
    //     if ($dataNotif) {
    //         $mMessage->insertBatch($dataNotif);
    //     }

    //     //* Send Email
    //     if ($dataEmailNotifIn) {
    //         $cMail->sendEmail('alert.harmony@sahabatabadi.com', $subjectIn, (new Html2Text($messageIn))->getText(), null, 'HARMONY SAS', null, null, $dataEmailNotifIn);
    //     }

    //     if ($dataEmailNotifOut) {
    //         $cMail->sendEmail('alert.harmony@sahabatabadi.com', $subjectOut, (new Html2Text($messageOut))->getText(), null, 'HARMONY SAS', null, null, $dataEmailNotifOut);
    //     }
    // }

    public function toDoCheckAbsent()
    {
        $mNotifText = new M_NotificationText($this->request);
        $mUser = new M_User($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mMessage = new M_Message($this->request);
        $cMail = new Mail();
        $cTelegram = new Telegram();

        set_time_limit(0);

        $holiday = $mHoliday->getHolidayDate();
        $today = date("Y-m-d");

        $yesterday = addBusinessDays($today, 1, $holiday, true);

        // TODO : Set Master Data Notification
        $dataNotifIn = $mNotifText->where('name', 'Belum Absen Masuk')->first();
        $subjectIn = $dataNotifIn->getSubject();
        $messageIn = str_replace(['(Var1)'], [$today], $dataNotifIn->getText());
        $dataNotifOut = $mNotifText->where('name', 'Belum Absen Pulang')->first();
        $subjectOut = $dataNotifOut->getSubject();
        $messageOut = str_replace(['(Var1)'], [$yesterday], $dataNotifOut->getText());

        $listEmpCheckInEmpty = $this->model->getEmpCheckInEmpty()->getResult();
        $listEmpCheckOutEmpty = $this->model->getEmpCheckOutEmpty()->getResult();

        //* Get All User
        $allUser = [];
        foreach ($mUser->where("isactive", 'Y')->findAll() as $value) {
            $allUser[$value->md_employee_id] = $value;
        }

        $dataNotif = [];
        $dataEmailNotifIn = [];
        $dataEmailNotifOut = [];

        //* Send Telegram And Pooling Employee Not Absent In
        foreach ($listEmpCheckInEmpty as $value) {
            $employeeID = $value->md_employee_id;
            $user = isset($allUser[$employeeID]) ? $allUser[$employeeID] : null;

            if (!empty($value->telegram_id)) {
                $cTelegram->sendMessage($value->telegram_id, (new Html2Text($messageIn))->getText());
            }

            if ($user) {
                $dataNotif[] = [
                    'created_by'    => 100001,
                    'updated_by'    => 100001,
                    'author_id'     => 100001,
                    'recipient_id'  => $user->sys_user_id,
                    'subject'       => $subjectIn,
                    'messagedate'   => date('Y-m-d H:i:s'),
                    'body'          => $messageIn
                ];

                if (!empty($user->email))
                    $dataEmailNotifIn[] = $user->email;
            }
        }

        //* Send Telegram And Pooling Employee Not Absent Out
        foreach ($listEmpCheckOutEmpty as $value) {
            $employeeID = $value->md_employee_id;
            $user = isset($allUser[$employeeID]) ? $allUser[$employeeID] : null;

            if (!empty($value->telegram_id)) {
                $cTelegram->sendMessage($value->telegram_id, (new Html2Text($messageOut))->getText());
            }

            if ($user) {
                $dataNotif[] = [
                    'created_by'    => 100001,
                    'updated_by'    => 100001,
                    'author_id'     => 100001,
                    'recipient_id'  => $user->sys_user_id,
                    'subject'       => $subjectOut,
                    'messagedate'   => date('Y-m-d H:i:s'),
                    'body'          => $messageOut
                ];

                if (!empty($user->email))
                    $dataEmailNotifOut[] = $user->email;
            }
        }

        //* Send Notif Apps
        if ($dataNotif) {
            $mMessage->insertBatch($dataNotif);
        }

        //* Send Email
        if ($dataEmailNotifIn) {
            $cMail->sendEmail('alert.harmony@sahabatabadi.com', $subjectIn, (new Html2Text($messageIn))->getText(), null, 'HARMONY SAS', null, null, $dataEmailNotifIn);
        }

        if ($dataEmailNotifOut) {
            $cMail->sendEmail('alert.harmony@sahabatabadi.com', $subjectOut, (new Html2Text($messageOut))->getText(), null, 'HARMONY SAS', null, null, $dataEmailNotifOut);
        }
    }

    public function toDoSendAbsentSummary()
    {
        $mAssignment = new M_Assignment($this->request);
        $mEmployee = new M_Employee($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $mUser = new M_User($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAbsent = new M_Absent($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $mHoliday = new M_Holiday($this->request);
        $cMessage = new Message();

        $today = date("Y-m-d");
        $storagePath = FCPATH . "/uploads/attsummary/";
        $holiday = $mHoliday->getHolidayDate();

        if (in_array($today, $holiday))
            return;

        $manager = $mEmployee->where(['isactive' => 'Y', 'md_levelling_id' => 100003])->whereIn('md_status_id', [100001, 100002])->findAll();

        $dataNotif = $mNotifText->where('name', 'Summary Absent')->first();
        $message = str_replace(['(Var1)'], [$today], $dataNotif->getText());
        $subject = $dataNotif->getSubject();

        $seq = 1;
        foreach ($manager as $value) {
            $user = $mUser->where('md_employee_id', $value->md_employee_id)->first();
            if ($user && $user->email) {
                $isData = false;
                $filename = 'Laporan Karyawan Tidak Absen Masuk ' . date('d-m-Y') . ' 00' . $seq . '.xlsx';

                $excel = new PHPExcel();
                $excel->getProperties()->setCreator('sas')
                    ->setLastModifiedBy('sas')
                    ->setTitle("Laporan Absen Karyawan Summary")
                    ->setSubject("Laporan Absen Karyawan Summary")
                    ->setDescription("Laporan Absen Karyawan Summary")
                    ->setKeywords("Laporan Absen Karyawan Summary");

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

                $whereClause = "md_employee.isactive = 'Y'";
                $whereClause .= " AND (md_employee.superior_id IN (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $value->md_employee_id))";
                $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $value->md_employee_id)";
                $whereClause .= " OR md_employee.superior_id = $value->md_employee_id)";
                $whereClause .= " AND md_employee.md_status_id IN ({$this->Status_PERMANENT}, {$this->Status_PROBATION})";
                $employee = $mEmployee->getEmployee($whereClause);


                $excel->setActiveSheetIndex(0)->setCellValue('A1', date('d M Y'));
                $excel->setActiveSheetIndex(0)->setCellValue('A2', 'Alert karyawan Tidak Absen');
                $excel->getActiveSheet()->getStyle('A2')->getFont()->setBold(TRUE); // Set bold kolom A1
                $excel->getActiveSheet()->getStyle('A2')->getFont()->setSize(15); // Set font size 15 untuk kolom A1
                $excel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1
                $excel->getActiveSheet()->mergeCells('A2:E2');

                $excel->setActiveSheetIndex(0)->setCellValue('A4', "No");
                $excel->getActiveSheet()->getStyle('A4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('B4', "Nik");
                $excel->getActiveSheet()->getStyle('B4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('C4', "Nama");
                $excel->getActiveSheet()->getStyle('C4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('D4', "Cabang");
                $excel->getActiveSheet()->getStyle('D4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('E4', "Divisi");
                $excel->getActiveSheet()->getStyle('E4')->applyFromArray($style_col);

                $row = 5;
                $number = 1;
                foreach ($employee as $val) {
                    $day = strtoupper(formatDay_idn(date('w')));
                    $branch = $mEmpBranch->getBranchDetail("md_employee_branch.md_employee_id = {$val->md_employee_id}")->getRow();
                    $division = $mEmpDivision->getDivisionDetail("md_employee_division.md_employee_id = {$val->md_employee_id}")->getRow();
                    if (empty($branch) || empty($division)) {
                        continue;
                    }

                    // TODO : Get Workday Employee
                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND md_employee_work.validfrom <= '{$today}'";
                    $whereClause .= " AND md_employee_work.validto >= '{$today}'";
                    $whereClause .= " AND md_day.name = '{$day}'";
                    $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                    // TODO : Get Submission Assignment
                    $whereClause = "DATE(trx_assignment_date.date) = '{$today}'
                    AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_assignment_detail.md_employee_id = {$val->md_employee_id}
                    AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
                    AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

                    $tugasKunjungan = $mAssignment->getDetailData($whereClause)->getRow();

                    // TODO : Get Absent Clock In Today
                    $whereClause = " v_attendance_branch.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND v_attendance_branch.date = '{$today}'";
                    $whereClause .= " AND v_attendance_branch.clock_in != ''";

                    if ($tugasKunjungan) {
                        $whereClause .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_in_line}";
                    } else {
                        $whereClause .= " AND v_attendance_branch.md_branch_id = {$branch->md_branch_id}";
                    }

                    $absentIn = $this->model->getAttBranch($whereClause)->getRow();

                    // TODO : Get Submission Today
                    $whereClause = "v_realization.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND v_realization.date = '{$today}'";
                    $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
                    $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}', '{$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}')";
                    $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                    $submission = $mAbsentDetail->getAllSubmission($whereClause)->getRow();


                    // TODO : If There no Clock In, Then Insert to Report
                    if ((!$absentIn && !$submission && $workDetail)) {
                        $excel->setActiveSheetIndex(0)->setCellValue('A' . $row, $number);
                        $excel->getActiveSheet()->getStyle('A' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('B' . $row, $val->nik);
                        $excel->getActiveSheet()->getStyle('B' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('C' . $row, $val->fullname);
                        $excel->getActiveSheet()->getStyle('C' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('D' . $row, $branch->branch_name);
                        $excel->getActiveSheet()->getStyle('D' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('E' . $row, $division->division_name);
                        $excel->getActiveSheet()->getStyle('E' . $row)->applyFromArray($style_row);
                        $row++;
                        $number++;

                        $isData = true;
                    }
                }

                $excel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(10); // Set width kolom A
                $excel->getActiveSheet()->getColumnDimension('C')->setWidth(30); // Set width kolom B
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(15); // Set width kolom C
                $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);

                $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
                $excel->getActiveSheet(0)->setTitle("Laporan Absen Karyawan Summary");
                $excel->setActiveSheetIndex(0);

                if ($isData) {
                    $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                    $filePath = $storagePath . $filename;

                    if (!is_dir($storagePath))
                        mkdir($storagePath, 0777, true);

                    // TODO : Save File and Send Email
                    $write->save($filePath);
                    $cMessage->sendInformation($user, $subject, $message, null, $filePath, null, true, false, false);

                    $seq++;
                }
            }
        }
    }

    public function toDoDeleteAttSummary()
    {
        $storagePath = FCPATH . "/uploads/attsummary";

        if (is_dir($storagePath))
            exec("rm -rf " . escapeshellarg($storagePath));
    }
    private function createBorderStyle($bold = false, $center = false, $fillColor = null)
    {
        $style = [
            'font' => ['bold' => $bold],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => array_fill_keys(['top', 'right', 'bottom', 'left'], ['style' => PHPExcel_Style_Border::BORDER_THIN])
        ];

        if ($center) {
            $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        }

        if ($fillColor) {
            $style['fill'] = [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => $fillColor]
            ];
        }

        return $style;
    }
}
