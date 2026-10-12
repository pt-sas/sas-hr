<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_LeaveBalance;
use App\Controllers\Backend\DashboardHelper;

class Dashboard extends BaseController
{
    protected $db;

    // TODO : Define this
    protected array $submissionTypes = [
        100001 => 'Sakit',
        100003 => 'Cuti',
        100004 => 'Ijin',
        100005 => 'Ijin Resmi',
        100026 => 'Surat Keterangan Sakit',
    ];

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index()
    {
        // ---------------------------------------------------------
        // Date & Mode Parameter Processing
        // ---------------------------------------------------------
        $mode = $this->request->getGet('mode');
        if (!in_array($mode, ['day', 'week', 'month'], true)) {
            $mode = 'month';
        }

        $rawDate = $this->request->getGet('date') ?? $this->request->getGet('period') ?? date('Y-m-d');
        if (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
            $rawDate .= '-01';
        }

        try {
            $dt = new \DateTime($rawDate);
        } catch (\Exception $e) {
            $dt = new \DateTime();
        }
        $dt->setTime(0, 0);

        $today        = $dt->format('Y-m-d');
        $currentMonth = $dt->format('m');
        $currentYear  = $dt->format('Y');

        $months = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
        $days   = ['Sun'=>'Ming','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];

        if ($mode === 'day') {
            $prevDate   = (clone $dt)->modify('-1 day')->format('Y-m-d');
            $nextDate   = (clone $dt)->modify('+1 day')->format('Y-m-d');
            $rangeStart = $rangeEnd = $dt->format('Y-m-d');
            $dateLabel  = ($days[$dt->format('D')] ?? $dt->format('D')) . ', ' . $dt->format('j') . ' ' . ($months[$dt->format('m')] ?? $dt->format('M')) . ' ' . $dt->format('Y');

        } elseif ($mode === 'week') {
            $prevDate   = (clone $dt)->modify('-1 week')->format('Y-m-d');
            $nextDate   = (clone $dt)->modify('+1 week')->format('Y-m-d');

            $wStart     = (clone $dt)->modify('-' . ((int) $dt->format('N') - 1) . ' days');
            $wEnd       = (clone $wStart)->modify('+6 days');
            $rangeStart = $wStart->format('Y-m-d');
            $rangeEnd   = $wEnd->format('Y-m-d');

            if ($wStart->format('Y') !== $wEnd->format('Y')) {
                $dateLabel = $wStart->format('j') . ' ' . ($months[$wStart->format('m')] ?? $wStart->format('M')) . ' ' . $wStart->format('Y')
                           . ' – ' . $wEnd->format('j') . ' ' . ($months[$wEnd->format('m')] ?? $wEnd->format('M')) . ' ' . $wEnd->format('Y');
            } elseif ($wStart->format('m') !== $wEnd->format('m')) {
                $dateLabel = $wStart->format('j') . ' ' . ($months[$wStart->format('m')] ?? $wStart->format('M'))
                           . ' – ' . $wEnd->format('j') . ' ' . ($months[$wEnd->format('m')] ?? $wEnd->format('M')) . ' ' . $wEnd->format('Y');
            } else {
                $dateLabel = $wStart->format('j') . ' – ' . $wEnd->format('j') . ' ' . ($months[$wEnd->format('m')] ?? $wEnd->format('M')) . ' ' . $wEnd->format('Y');
            }

        } else {
            $firstOfMonth = (clone $dt)->modify('first day of this month');
            $prevDate     = (clone $firstOfMonth)->modify('first day of previous month')->format('Y-m-d');
            $nextDate     = (clone $firstOfMonth)->modify('first day of next month')->format('Y-m-d');
            $rangeStart   = $firstOfMonth->format('Y-m-d');
            $rangeEnd     = (clone $firstOfMonth)->modify('last day of this month')->format('Y-m-d');
            $dateLabel    = ($months[$dt->format('m')] ?? $dt->format('M')) . ' ' . $dt->format('Y');
        }
        
        // ---------------------------------------------------------
        // 1. Employee Name & IDs
        // ---------------------------------------------------------
        $employeeName = $this->access->getUser('name') ?? $this->access->getUser('username');
        $employeeId   = session()->get('md_employee_id');
        $nik          = session()->get('nik');

        if (empty($nik) && !empty($employeeId)) {
            $empRow = $this->db->table('md_employee')
                ->select('nik')
                ->where('md_employee_id', $employeeId)
                ->get()->getRow();
            $nik = $empRow->nik ?? null;
        }

        // Attendance stats initializers
        $attendanceDays   = 0;
        $lateCount        = 0;
        $teamTotal        = 0;
        $teamPresent      = 0;
        $teamOnLeave      = 0;
        $haveClockIn      = false;
        $haveClockOut     = false;
        $clockInTime      = 'Belum absen';
        $clockOutTime     = 'Belum absen';
        $scheduleRange    = '-';
        $branchName       = '-';
        $attendanceStatus = 'ABSENT';
        $tkhAllowance     = 1.0;

        $balance          = 0;
        $availableLeave   = 0;
        $pendingApprovals = 0;
        $pendingFull      = 'Tidak ada pengajuan berjalan';
        $pendingSummary   = 'Tidak ada pengajuan berjalan';
        $calendarEvents   = [];
        $announcements    = [];
        $divisions        = [];
        $branches         = [];

        // TODO : Define this [x]
        // $superRoleIds    = [1, 5, 6, 7, 21, 79, 80, 90];
        // 1. System / IT Super Admins (Full platform access)
        $masterAdminRoleIds = [1, 4];
        // 2. HR Super Users (Full HR, Salary, & Employee Master Data access)
        $hrSuperRoleIds = [5, 6, 79, 90]; 
        // 3. Executive / Management Super Users (Company-wide approval & view access)
        $executiveRoleIds = [21, 70, 71, 74];
        // Combined array if your codebase specifically requires a single array:
        $superRoleIds = array_unique(array_merge(
            $masterAdminRoleIds, 
            $hrSuperRoleIds, 
            $executiveRoleIds
        ));

        // TODO : Define this [x]
        $allStatuses = [
            $this->DOCSTATUS_Drafted,
            $this->DOCSTATUS_Requested,
            $this->DOCSTATUS_Inprogress,
            $this->DOCSTATUS_Reopen,
            $this->DOCSTATUS_Approved,
            $this->DOCSTATUS_Completed,
            $this->DOCSTATUS_NotApproved,
            $this->DOCSTATUS_Voided,
            $this->DOCSTATUS_Invalid,
            $this->DOCSTATUS_Suspended,
            $this->DOCSTATUS_Aborted,
            $this->DOCSTATUS_Unlock,
        ];
        // 1. Pending / Active Work-in-Progress (Awaiting Action or Processing)
        $pendingStatuses = [
            $this->DOCSTATUS_Drafted,    // 'DR'
            $this->DOCSTATUS_Requested,  // 'RQ'
            $this->DOCSTATUS_Inprogress, // 'IP'
            $this->DOCSTATUS_Reopen,     // 'RE'
            $this->DOCSTATUS_Unlock,     // 'UL' (Re-opened for editing/action)
        ];

        // 2. Final Approved / Success Statuses
        $completedStatuses = [
            $this->DOCSTATUS_Approved,   // 'AP'
            $this->DOCSTATUS_Completed,  // 'CO'
        ];

        // 3. Terminal / Rejected / Cancelled Statuses
        $closedStatuses = [
            $this->DOCSTATUS_NotApproved,
            $this->DOCSTATUS_Voided,
            $this->DOCSTATUS_Invalid,
            $this->DOCSTATUS_Suspended,
            $this->DOCSTATUS_Aborted,
        ];

        if (!empty($employeeId) && !empty($nik)) {

            // ---------------------------------------------------------
            // 2. Run Instant Engine Recalculation (In-Memory) & Query Card
            // ---------------------------------------------------------
            $attProcessor = new DashboardHelper();
            $attProcessor->initController($this->request, $this->response, \Config\Services::logger());

            // 1. Calculate today's status in memory (Bypasses DB writes entirely)
            $todayCalc = $attProcessor->processEmployeeDay($nik, $today);

            $dayNo = (int) date('N', strtotime($today)); // 1 (Mon) - 7 (Sun)

            // 2. Fetch supplementary schedule & branch details
            $empSchedule = $this->db->table('md_employee me')
                ->select("
                    mb.name AS branch_name,
                    mwd.startwork,
                    mwd.endwork,
                    COALESCE(CONCAT(DATE_FORMAT(mwd.startwork, '%H:%i'), ' - ', DATE_FORMAT(mwd.endwork, '%H:%i')), 'Libur / Tidak Ada Jadwal') AS schedule_range
                ")
                ->join('md_employee_branch meb', 'me.md_employee_id = meb.md_employee_id AND meb.isactive = "Y"', 'left')
                ->join('md_branch mb', 'meb.md_branch_id = mb.md_branch_id', 'left')
                ->join('md_employee_work mew', 'me.md_employee_id = mew.md_employee_id 
                    AND mew.validfrom <= ' . $this->db->escape($today . ' 23:59:59') . '
                    AND (mew.validto IS NULL OR mew.validto >= ' . $this->db->escape($today . ' 00:00:00') . ' OR CAST(mew.validto AS CHAR) LIKE "0000%")
                    AND (mew.isactive = "Y" OR mew.isactive IS NULL)', 'left')
                ->join('md_work mw', 'mw.md_work_id = mew.md_work_id', 'left')
                ->join('md_work_detail mwd', 'mw.md_work_id = mwd.md_work_id AND mwd.md_day_id = ' . $dayNo, 'left')
                ->where('me.nik', $nik)
                ->get()
                ->getRow();

            // Default fallbacks for $data array & box_attendance.php
            $myClockInRaw      = $todayCalc['clock_in'] ?? null;
            $myClockOutRaw     = $todayCalc['clock_out'] ?? null;
            $haveClockIn       = !empty($myClockInRaw);
            $haveClockOut      = !empty($myClockOutRaw);

            $clockInTime       = $haveClockIn ? date('H:i', strtotime($myClockInRaw)) : 'Belum absen';
            $clockOutTime      = $haveClockOut ? date('H:i', strtotime($myClockOutRaw)) : 'Belum absen';

            $scheduleRange     = $empSchedule->schedule_range ?? 'Libur / Tidak Ada Jadwal';
            $branchName        = $empSchedule->branch_name ?? '-';
            $attendanceStatus  = $todayCalc['attendance_status'] ?? 'ABSENT';
            $tkhAllowance      = (float) ($todayCalc['tkh_allowance'] ?? 1.0);

            $statusMap = [
                'NORMAL'                    => ['text' => 'Hadir Tepat Waktu',   'tone' => 'success',   'punctual' => 'Tepat Waktu'],
                'IN_OFFICE'                 => ['text' => 'Sedang Bekerja',     'tone' => 'primary',   'punctual' => 'Dalam Jam Kerja'],
                'IN_OFFICE_LATE'            => ['text' => 'Bekerja (Terlambat)','tone' => 'warning',   'punctual' => 'Terlambat Datang'],
                'LATE_ARRIVAL'              => ['text' => 'Terlambat Datang',   'tone' => 'warning',   'punctual' => 'Terlambat'],
                'UNEXCUSED_EARLY_DEPARTURE' => ['text' => 'Pulang Cepat',       'tone' => 'warning',   'punctual' => 'Pulang Lebih Awal'],
                'LATE_AND_EARLY_LEAVE'      => ['text' => 'Terlambat & Pulang Cepat', 'tone' => 'danger', 'punctual' => 'Jam Inkomplit'],
                'MISSING_CHECK_IN'          => ['text' => 'Tidak Absen Masuk',  'tone' => 'danger',    'punctual' => 'Tanpa Absen Masuk'],
                'MISSING_CHECK_OUT'         => ['text' => 'Tidak Absen Pulang', 'tone' => 'danger',    'punctual' => 'Tanpa Absen Pulang'],
                'LATE_AND_MISSING_OUT'      => ['text' => 'Terlambat & Tanpa Pulang', 'tone' => 'danger', 'punctual' => 'Tidak Lengkap'],
                'ABSENT'                    => ['text' => 'Tidak Hadir',        'tone' => 'danger',    'punctual' => 'Mangkir'],
                'LEAVE_APPROVED'            => ['text' => 'Izin / Cuti Disetujui', 'tone' => 'info',   'punctual' => 'Izin Disetujui'],
                'HALF_DAY_APPROVED'         => ['text' => 'Izin Setengah Hari', 'tone' => 'info',     'punctual' => 'Setengah Hari'],
                'LATE_EXCUSED'              => ['text' => 'Terlambat (Disetujui)', 'tone' => 'info',  'punctual' => 'Izin Terlambat'],
                'EARLY_LEAVE_EXCUSED'       => ['text' => 'Pulang Cepat (Disetujui)', 'tone' => 'info', 'punctual' => 'Izin Pulang Cepat'],
                'APPROVED_SHORT_HOURS'      => ['text' => 'Izin Jam Kerja',     'tone' => 'info',      'punctual' => 'Izin Jam Kerja'],
                'MANUAL_HRD_OVERRIDE'       => ['text' => 'Koreksi HRD',        'tone' => 'info',      'punctual' => 'Disesuaikan HRD'],
                'OFF_DAY'                   => ['text' => 'Hari Libur Kerja',   'tone' => 'secondary', 'punctual' => 'Libur Kerja'],
                'PUBLIC_HOLIDAY'            => ['text' => 'Libur Nasional',     'tone' => 'secondary', 'punctual' => 'Libur Nasional'],
                'OVERTIME_OFF_DAY'          => ['text' => 'Lembur Hari Libur',  'tone' => 'info',      'punctual' => 'Kerja Hari Libur'],
                'HOLIDAY_WORKED'            => ['text' => 'Lembur Libur Nasional', 'tone' => 'info',   'punctual' => 'Kerja Libur Nasional'],
            ];

            $meta               = $statusMap[$attendanceStatus] ?? ['text' => $attendanceStatus, 'tone' => 'secondary', 'punctual' => 'Catatan Khusus'];
            $myStatusText      = $meta['text'];
            $myTone            = $meta['tone'];
            $myPunctualityText = $meta['punctual'];

            $myClockInText     = $clockInTime;
            $myClockOutText    = $clockOutTime;

            $myShiftStart = !empty($empSchedule->startwork) ? date('H:i', strtotime($empSchedule->startwork)) : '08:00';
            $myShiftEnd   = !empty($empSchedule->endwork)   ? date('H:i', strtotime($empSchedule->endwork))   : '17:00';

            // ---------------------------------------------------------
            // 3. Aggregate Daily Engine Results for Period (In-Memory)
            // ---------------------------------------------------------
            $todayStr     = date('Y-m-d');
            $processUntil = ($rangeEnd > $todayStr) ? $todayStr : $rangeEnd;

            // 1. Process attendance engine directly in-memory (Returns array, 0 DB writes)
            $rowsByDate = [];
            if ($rangeStart <= $processUntil) {
                $rowsByDate = $attProcessor->processEmployeeRange($nik, $rangeStart, $processUntil);
            }

            // 2. Iterate through EVERY calendar day in the full period ($rangeStart to $rangeEnd)
            $periodAll = new \DatePeriod(
                new \DateTime($rangeStart),
                new \DateInterval('P1D'),
                (new \DateTime($rangeEnd))->modify('+1 day')
            );

            // --- OPTIMIZATION: Pre-fetch schedule & holidays ONCE outside the loop ---
            $futureHolidays = $this->db->table('md_holiday')
                ->select("DATE(startdate) as start_date, DATE(CASE WHEN enddate IS NULL OR CAST(enddate AS CHAR) LIKE '0000%' THEN startdate ELSE enddate END) as end_date")
                ->where('isactive', 'Y')
                ->where('startdate <=', $rangeEnd . ' 23:59:59')
                ->get()->getResultArray();

            $futureMassLeaves = $this->db->table('md_massleave')
                ->select("DATE(startdate) as start_date, DATE(CASE WHEN enddate IS NULL OR CAST(enddate AS CHAR) LIKE '0000%' THEN startdate ELSE enddate END) as end_date")
                ->where('isactive', 'Y')
                ->where('startdate <=', $rangeEnd . ' 23:59:59')
                ->get()->getResultArray();

            $schedDays = $this->db->table('md_employee_work mew')
                ->select('mwd.md_day_id')
                ->join('md_work mw', 'mw.md_work_id = mew.md_work_id')
                ->join('md_work_detail mwd', 'mw.md_work_id = mwd.md_work_id')
                ->where('mew.md_employee_id', $employeeId)
                ->where('mew.isactive', 'Y')
                ->get()->getResultArray();

            $scheduledDayIds = array_column($schedDays, 'md_day_id');

            $isOffDate = function($wDate) use ($futureHolidays, $futureMassLeaves) {
                foreach ($futureHolidays as $h) {
                    if ($wDate >= $h['start_date'] && $wDate <= $h['end_date']) return true;
                }
                foreach ($futureMassLeaves as $m) {
                    if ($wDate >= $m['start_date'] && $wDate <= $m['end_date']) return true;
                }
                return false;
            };

            $ontimeCount     = 0;
            $lateCount       = 0;
            $incompleteCount = 0;
            $myLeaveCount    = 0;
            $myAbsentCount   = 0;
            $overtimeCount   = 0;
            $targetWorkDays  = 0;
            $elapsedWorkDays = 0;

            foreach ($periodAll as $dtItem) {
                $wDate         = $dtItem->format('Y-m-d');
                $isPastOrToday = ($wDate <= $todayStr);

                // Determine if this date is a scheduled work day
                if (isset($rowsByDate[$wDate])) {
                    $status   = $rowsByDate[$wDate]['attendance_status'];
                    $isTarget = !in_array($status, ['OFF_DAY', 'PUBLIC_HOLIDAY', 'MASS_LEAVE'], true);
                } else {
                    $dayNo    = (int) $dtItem->format('N');
                    $hasSched = in_array($dayNo, $scheduledDayIds, true);
                    $isOff    = $isOffDate($wDate);

                    $isTarget = ($hasSched && !$isOff);
                }

                if ($isTarget) {
                    $targetWorkDays++; // Accumulates FULL month/week target (y)
                    if ($isPastOrToday) {
                        $elapsedWorkDays++;
                    }
                }

                // Process past and today attendance metrics directly from memory
                if ($isPastOrToday && isset($rowsByDate[$wDate])) {
                    $status = $rowsByDate[$wDate]['attendance_status'];
                    switch ($status) {
                        // ON TIME / NORMAL PRESENCE
                        case 'NORMAL':
                        case 'IN_OFFICE':
                        case 'MANUAL_HRD_OVERRIDE':
                        case 'OFFICIAL_DUTY':
                            $ontimeCount++;
                            break;

                        // OVERTIME ON OFF-DAYS / HOLIDAYS
                        case 'OVERTIME_OFF_DAY':
                        case 'HOLIDAY_WORKED':
                        case 'MASS_LEAVE_WORKED':
                            $ontimeCount++;
                            $overtimeCount++;
                            break;

                        // LATE ARRIVALS
                        case 'IN_OFFICE_LATE':
                        case 'LATE_ARRIVAL':
                        case 'LATE_AND_EARLY_LEAVE':
                            $lateCount++;
                            break;

                        // INCOMPLETE TAPS
                        case 'MISSING_CHECK_IN':
                        case 'MISSING_CHECK_OUT':
                        case 'LATE_AND_MISSING_OUT':
                            $incompleteCount++;
                            break;

                        // GENERALIZED IZIN / CUTI
                        case 'LEAVE_APPROVED':
                        case 'HALF_DAY_APPROVED':
                        case 'LATE_EXCUSED':
                        case 'EARLY_LEAVE_EXCUSED':
                        case 'APPROVED_SHORT_HOURS':
                        case 'LATE_WITH_APPROVED_LEAVE':
                            $myLeaveCount++;
                            break;

                        // PURE ABSENT (MANGKIR)
                        case 'ABSENT':
                        case 'UNEXCUSED_EARLY_DEPARTURE':
                            $myAbsentCount++;
                            break;
                    }
                }
            }

            // x = Physical Presence (Hadir)
            $attendanceDays  = $ontimeCount + $lateCount + $incompleteCount;
            $totalLate       = $lateCount + $incompleteCount;
            $fulfilledDays   = $attendanceDays + $myLeaveCount;

            // Safeguards against division by zero
            $targetWorkDays  = max(1, $targetWorkDays);
            $denomElapsed    = max(1, $elapsedWorkDays);

            // Rate Calculation: (Fulfilled Obligations / Elapsed Target Workdays) * 100
            $myRate          = min(100, (int) round(($fulfilledDays / $denomElapsed) * 100));
            // ---------------------------------------------------------
            // 4. Team Attendance Stats (D, W, M)
            // ---------------------------------------------------------
            // $mEmployee  = new M_Employee($this->request);
            // $allTeamIds = $mEmployee->getChartEmployee($employeeId);

            // $candidateIds = array_values(array_diff($allTeamIds, [$employeeId]));

            // Whitelist: Super Roles + Managers + Supervisors (SPVs)
            // Get session credentials
            $roleId      = (int) (session()->get('sys_role_id') ?? 0);
            $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

            // Whitelist: Super Roles + Managers (100001, 100002) + Supervisors / SPVs (100003, 100004)
            $allowedTeamLevellings = [100001, 100002, 100003, 100004];

            $canViewTeamCard = in_array($roleId, $superRoleIds ?? [], true) 
                || in_array($levellingId, $allowedTeamLevellings, true);

            $candidateIds = [];

            if ($canViewTeamCard) {
                $mEmployee    = new M_Employee($this->request);
                $allTeamIds   = $mEmployee->getChartEmployee($employeeId) ?? [];
                $candidateIds = array_values(array_diff($allTeamIds, [$employeeId]));
            }

            // Exclude resigned / pensioned / inactive — check both md_status and resigndate
            $excludedStatusNames = ['RESIGN', 'PENSIUN'];

            $subordinateIds = [];
            if (!empty($candidateIds)) {
                $activeRows = $this->db->table('md_employee e')
                    ->select('e.md_employee_id')
                    ->join('md_status s', 's.md_status_id = e.md_status_id', 'left')
                    ->whereIn('e.md_employee_id', $candidateIds)
                    ->where('e.isactive', 'Y')
                    ->groupStart()
                        ->where('e.resigndate IS NULL')
                        ->orWhere("CAST(e.resigndate AS CHAR) = '0000-00-00'")
                        ->orWhere('e.resigndate >', $today)
                    ->groupEnd()
                    ->groupStart()
                        ->whereNotIn('s.name', $excludedStatusNames)
                        ->orWhere('s.name IS NULL')
                    ->groupEnd()
                    ->get()->getResultArray();

                $subordinateIds = array_column($activeRows, 'md_employee_id');
            }

            $teamTotal   = count($subordinateIds);
            $teamPresent = 0;
            $teamOnLeave = 0;
            $teamRate    = 0;
            $teamTrend   = [];

            if (!empty($subordinateIds)) {
                // 1. Determine overall Date Range based on Mode
                if ($mode === 'day') {
                    $startDate = $today;
                    $endDate   = $today;
                } elseif ($mode === 'week') {
                    $startDate = $wStart->format('Y-m-d');
                    $endDate   = (clone $wStart)->modify('+6 days')->format('Y-m-d');
                } else {
                    $firstDay  = new \DateTime("{$currentYear}-{$currentMonth}-01");
                    $startDate = $firstDay->format('Y-m-d');
                    $endDate   = (clone $firstDay)->modify('last day of this month')->format('Y-m-d');
                }

                // 2. Pre-fetch Off-days (Holidays & Mass Leave with date ranges)
                $holidayRows = $this->db->table('md_holiday')
                    ->select('name, startdate AS start_date, enddate AS end_date')
                    ->where('startdate <=', $endDate)
                    ->groupStart()
                        ->where('enddate >=', $startDate)
                        ->orWhere('enddate IS NULL')
                        ->orWhere("CAST(enddate AS CHAR) = '0000-00-00'")
                        ->orWhere("CAST(enddate AS CHAR) = ''")
                    ->groupEnd()
                    ->get()->getResultArray();

                $massLeaveRows = $this->db->table('md_massleave')
                    ->select('name, startdate AS start_date, enddate AS end_date')
                    ->where('startdate <=', $endDate)
                    ->groupStart()
                        ->where('enddate >=', $startDate)
                        ->orWhere('enddate IS NULL')
                        ->orWhere("CAST(enddate AS CHAR) = '0000-00-00'")
                        ->orWhere("CAST(enddate AS CHAR) = ''")
                    ->groupEnd()
                    ->get()->getResultArray();

                $offDaysMap = [];

                // Helper Closure: Expand date ranges into individual date keys
                $processOffDays = function ($rows) use (&$offDaysMap) {
                    foreach ($rows as $row) {
                        if (empty($row['start_date']) || $row['start_date'] === '0000-00-00') {
                            continue;
                        }

                        $startDateObj = new \DateTime($row['start_date']);
                        $endDateObj   = (!empty($row['end_date']) && !in_array($row['end_date'], ['0000-00-00', '']))
                            ? new \DateTime($row['end_date'])
                            : clone $startDateObj; // Fallback to single-day if enddate is NULL or empty

                        $curr = clone $startDateObj;
                        while ($curr <= $endDateObj) {
                            $offDaysMap[$curr->format('Y-m-d')] = $row['name'];
                            $curr->modify('+1 day');
                        }
                    }
                };

                $processOffDays($holidayRows);
                $processOffDays($massLeaveRows);

                // 3. Pre-fetch Approved Penugasan (Assignments)
                $penugasanRows = $this->db->table('v_all_submission')
                    ->select('md_employee_id, DATE(date) as date')
                    ->whereIn('md_employee_id', $subordinateIds)
                    ->where('DATE(date) >=', $startDate)
                    ->where('DATE(date) <=', $endDate)
                    ->where('doctype', 'Penugasan')
                    ->where('isagree', 'Y')
                    ->get()->getResultArray();

                $penugasanMap = [];
                foreach ($penugasanRows as $p) {
                    $penugasanMap[$p['date']][$p['md_employee_id']] = true;
                }

                // 4. Pre-fetch Regular Employee Work Schedules
                // md_day_id: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun
                $schedules = $this->db->table('md_employee_work mew')
                    ->select('mew.md_employee_id, mwd.md_day_id')
                    ->join('md_work_detail mwd', 'mwd.md_work_id = mew.md_work_id')
                    ->whereIn('mew.md_employee_id', $subordinateIds)
                    ->where('mew.isactive', 'Y')
                    ->get()->getResultArray();

                $employeeWorkDays = [];
                foreach ($schedules as $s) {
                    $employeeWorkDays[$s['md_employee_id']][] = (int) $s['md_day_id'];
                }

                // 5. Pre-fetch Attendance Logs (clock_in OR clock_out presence)
                $attendanceRows = $this->db->table('v_attendance')
                    ->select('md_employee_id, DATE(date) as date, clock_in, clock_out')
                    ->whereIn('md_employee_id', $subordinateIds)
                    ->where('date >=', $startDate)
                    ->where('date <=', $endDate)
                    ->groupStart()
                        ->groupStart()
                            ->where('clock_in IS NOT NULL')
                            ->where("clock_in != ''")
                            ->where("clock_in != '00:00:00'")
                        ->groupEnd()
                        ->orGroupStart()
                            ->where('clock_out IS NOT NULL')
                            ->where("clock_out != ''")
                            ->where("clock_out != '00:00:00'")
                        ->groupEnd()
                    ->groupEnd()
                    ->get()->getResultArray();

                $attendanceMap = [];
                foreach ($attendanceRows as $att) {
                    $attendanceMap[$att['date']][$att['md_employee_id']] = true;
                }

                // Helper Closure: Compute Capacity & Attendance for a Single Date
                $evaluateDailyStats = function ($dateStr) use ($subordinateIds, $offDaysMap, $penugasanMap, $employeeWorkDays, $attendanceMap) {
                    $dt        = new \DateTime($dateStr);
                    $dayOfWeek = (int) $dt->format('N'); // 1 (Mon) to 7 (Sun)
                    $isWeekend = ($dayOfWeek >= 6);
                    $isHoliday = isset($offDaysMap[$dateStr]);

                    $dayCapacity = 0;
                    $dayPresent  = 0;

                    foreach ($subordinateIds as $empId) {
                        $hasPenugasan      = isset($penugasanMap[$dateStr][$empId]);
                        $scheduledDays     = $employeeWorkDays[$empId] ?? [1, 2, 3, 4, 5]; // Default Mon-Fri
                        $isScheduledToWork = in_array($dayOfWeek, $scheduledDays);

                        // Employee is expected to work if:
                        // 1. Has explicit Penugasan (overrides holiday/weekend off) OR
                        // 2. Is scheduled on this day AND it is not a holiday/mass leave
                        $isExpected = $hasPenugasan || ($isScheduledToWork && !$isHoliday);

                        if ($isExpected) {
                            $dayCapacity++;
                            if (isset($attendanceMap[$dateStr][$empId])) {
                                $dayPresent++;
                            }
                        }
                    }

                    $rate = $dayCapacity > 0 ? (int) round(($dayPresent / $dayCapacity) * 100) : ($isHoliday || $isWeekend ? 100 : 0);

                    return [
                        'capacity'   => $dayCapacity,
                        'present'    => $dayPresent,
                        'rate'       => $rate,
                        'is_holiday' => $isHoliday || ($isWeekend && $dayCapacity === 0)
                    ];
                };

                // 6. Execute Stats Aggregation by Mode
                if ($mode === 'day') {
                    $stats             = $evaluateDailyStats($today);
                    $teamPresent       = $stats['present'];
                    $totalTeamCapacity = $stats['capacity'];
                    $teamRate          = $stats['rate'];

                    $teamOnLeave = $this->db->table('v_all_submission')
                        ->whereIn('md_employee_id', $subordinateIds)
                        ->where('DATE(date)', $today)
                        ->where('isagree', 'Y')
                        ->countAllResults();

                } elseif ($mode === 'week') {
                    $dayLabels         = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
                    $monday            = clone $wStart;
                    $totalTeamCapacity = 0;

                    foreach ($dayLabels as $i => $label) {
                        $dayDate = (clone $monday)->modify("+{$i} days")->format('Y-m-d');
                        $stats   = $evaluateDailyStats($dayDate);

                        $teamPresent       += $stats['present'];
                        $totalTeamCapacity += $stats['capacity'];

                        $teamTrend[] = [
                            'label'      => $label,
                            'rate'       => min(100, $stats['rate']),
                            'is_holiday' => $stats['is_holiday']
                        ];
                    }

                    $teamRate = $totalTeamCapacity > 0 ? (int) round(($teamPresent / $totalTeamCapacity) * 100) : 0;

                } else { // Month Mode
                    $firstDayOfMonth   = new \DateTime("{$currentYear}-{$currentMonth}-01");
                    $totalTeamCapacity = 0;

                    for ($w = 1; $w <= 4; $w++) {
                        $wStartBracket = (clone $firstDayOfMonth)->modify('+' . (($w - 1) * 7) . ' days');
                        $wEndBracket   = $w === 4
                            ? (clone $firstDayOfMonth)->modify('last day of this month')
                            : (clone $firstDayOfMonth)->modify('+' . ($w * 7 - 1) . ' days');

                        $weekCapacity = 0;
                        $weekPresent  = 0;

                        $curr = clone $wStartBracket;
                        while ($curr <= $wEndBracket) {
                            $stats        = $evaluateDailyStats($curr->format('Y-m-d'));
                            $weekCapacity += $stats['capacity'];
                            $weekPresent  += $stats['present'];
                            $curr->modify('+1 day');
                        }

                        $wRate       = $weekCapacity > 0 ? (int) round(($weekPresent / $weekCapacity) * 100) : 100;
                        $teamTrend[] = [
                            'label' => 'W' . $w,
                            'rate'  => min(100, $wRate)
                        ];

                        $teamPresent       += $weekPresent;
                        $totalTeamCapacity += $weekCapacity;
                    }

                    $teamRate = $totalTeamCapacity > 0 ? (int) round(($teamPresent / $totalTeamCapacity) * 100) : 0;
                }
            }

            // ---------------------------------------------------------
            // 5. Leave Balance Calculation
            // ---------------------------------------------------------
            $mLeaveBalance  = new M_LeaveBalance($this->request);
            $leaveBalance   = $mLeaveBalance->getTotalBalance($employeeId, $currentYear);
            $carryOverValid = (!empty($leaveBalance) && !empty($leaveBalance->carry_over_expiry_date) && $today <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

            $balance        = (float) ($leaveBalance->balance_amount ?? 0);
            $carriedOver    = (float) ($leaveBalance->carried_over_amount ?? 0);
            $reserved       = (float) ($leaveBalance->reserved ?? 0);

            $availableLeave = $carryOverValid ? ($carriedOver + $balance) : $balance;
            $availableLeave = max(0, $availableLeave - $reserved);

            // ---------------------------------------------------------
            // 6. Pending Approvals
            // ---------------------------------------------------------
            $pendingGroups = $this->db->table('v_all_submission')
                ->select('doctype, COUNT(DISTINCT documentno) as total')
                ->where('md_employee_id', $employeeId)
                ->where('date <=', $rangeEnd . ' 23:59:59') // Removes DATE() function to keep index active
                ->whereIn('docstatus', $pendingStatuses)
                ->groupBy('doctype')
                ->get()->getResult();

            $pendingApprovals = 0;
            $summaryParts     = [];
            $pendingBreakdown = [];

            foreach ($pendingGroups as $group) {
                $count = (int) $group->total;
                $pendingApprovals += $count;

                $cleanLabel = ucwords(strtolower(str_replace('_', ' ', $group->doctype ?? 'Submission')));
                $pendingBreakdown[$cleanLabel] = $count;
                $summaryParts[]                = "{$count} " . strtolower($cleanLabel);
            }

            $pendingSummary = !empty($summaryParts) ? implode(', ', $summaryParts) : 'Tidak ada yang tertunda';
            $pendingFull    = $pendingSummary;

            // ---------------------------------------------------------
            // 7. Calendar Subordinates & Birthday Events (Approved Only)
            // ---------------------------------------------------------
            if (!empty($subordinateIds)) {

                // Pull session data needed for permission check
                $roleId      = (int) (session()->get('sys_role_id') ?? 0);
                $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

                // Executives/GM (70,71,74, etc.), Super Admins, HR, and Managers (100001, 100002)
                $canViewSubmissions = in_array($roleId, $superRoleIds, true) 
                    || in_array($levellingId, [100001, 100002], true);
                
                if ($canViewSubmissions) {
                    // 1. Pengajuan Bawahan yang SUDAH DISETUJUI
                    $submissions = $this->db->table('v_all_submission s')
                        ->select('s.date, s.reason, dt.md_doctype_id, dt.name as doctype_name, e.fullname as employee_name')
                        ->join('md_doctype dt', 'dt.md_doctype_id = s.submissiontype', 'inner')
                        ->join('md_employee e', 'e.md_employee_id = s.md_employee_id', 'left')
                        ->whereIn('s.md_employee_id', $subordinateIds)
                        ->where('s.docstatus', $this->DOCSTATUS_Approved)
                        ->get()->getResult();

                    foreach ($submissions as $sub) {
                        $isLeaveType = isset($this->submissionTypes[$sub->md_doctype_id]);
                        $eventType   = $isLeaveType ? 'leave' : 'team';
                        $cleanLabel  = ucwords(strtolower($sub->doctype_name ?? 'Pengajuan'));
                        $empName     = $sub->employee_name ?? 'Karyawan';

                        $calendarEvents[] = [
                            'start' => date('Y-m-d', strtotime($sub->date)),
                            'title' => "{$cleanLabel} - {$empName}",
                            'type'  => $eventType,
                            'place' => $sub->reason ?? ''
                        ];
                    }
                }

                // 2. Ulang Tahun Bawahan
                $birthdays = $this->db->table('md_employee')
                    ->select('fullname, birthday')
                    ->whereIn('md_employee_id', $subordinateIds)
                    ->where('birthday IS NOT NULL')
                    ->where('birthday >', '1900-01-01')
                    ->get()->getResult();

                foreach ($birthdays as $bday) {
                    $timestamp = strtotime($bday->birthday);
                    if (!empty($bday->birthday) && $timestamp > 0) {
                        $calendarEvents[] = [
                            'start' => $currentYear . '-' . date('m-d', $timestamp),
                            'title' => "Ulang Tahun - {$bday->fullname}",
                            'type'  => 'bday',
                            'place' => 'Ulang Tahun Karyawan'
                        ];
                    }
                }
            }

            // ---------------------------------------------------------
            // 8. Announcements
            // ---------------------------------------------------------
            $announcements = $this->db->table('trx_message')
                ->select('trx_message.trx_message_id, trx_message.subject, trx_message.body, trx_message.messagedate, trx_message.isread, sys_user.name as author')
                ->join('sys_user', 'sys_user.sys_user_id = trx_message.author_id', 'left')
                ->where('trx_message.recipient_id', $employeeId)
                ->where('trx_message.isread', 'N')
                ->orderBy('trx_message.messagedate', 'DESC')
                ->limit(25)
                ->get()->getResultArray();

            // ---------------------------------------------------------
            // 9. Greetings and Personalization
            // ---------------------------------------------------------
            $positionTable = 'md_position';
            $positionLabel = 'name';
            $photoDir      = 'uploads/employee/';
            $useSalutation = true;

            $validDate = static function (?string $d): ?\DateTime {
                if (!$d || str_starts_with($d, '0000')) {
                    return null;
                }
                try {
                    return new \DateTime($d);
                } catch (\Throwable $e) {
                    return null;
                }
            };

            $greetingRow = $this->db->table('md_employee e')
                ->select('e.nickname, e.fullname, e.image, e.birthday, e.registerdate, e.gender,
                          p.' . $positionLabel . ' AS position_name,
                          sup.nickname AS sup_nick, sup.fullname AS sup_full')
                ->join($positionTable . ' p', 'p.md_position_id = e.md_position_id', 'left')
                ->join('md_employee sup', 'sup.md_employee_id = e.superior_id', 'left')
                ->where('e.md_employee_id', $employeeId)
                ->where('e.isactive', 'Y')
                ->get()->getRowArray();

            $employeePhoto    = null;
            $employeeNickname = null;
            $employeePosition = null;
            $salutation       = null;
            $isBirthday       = false;
            $workAnniversary  = null;
            $tenureText       = null;
            $supervisorName   = null;

            if ($greetingRow) {
                $nowDateObj = new \DateTime('today');
                $md         = $nowDateObj->format('m-d');

                $bday       = $validDate($greetingRow['birthday']);
                $isBirthday = $bday && $bday->format('m-d') === $md;

                $joined          = $validDate($greetingRow['registerdate']);
                $years           = $joined ? $joined->diff($nowDateObj)->y : 0;
                $months          = $joined ? $joined->diff($nowDateObj)->m : 0;
                $workAnniversary = ($joined && $years >= 1 && $joined->format('m-d') === $md) ? $years : null;

                $tenureText = $joined ? trim(($years ? $years . ' th ' : '') . ($months ? $months . ' bln' : '')) : '';
                if ($joined && $tenureText === '') {
                    $tenureText = 'baru bergabung';
                }

                $img = trim((string) ($greetingRow['image'] ?? ''));
                if ($img !== '' && is_file(FCPATH . $photoDir . $img)) {
                    $employeePhoto = base_url($photoDir . $img);
                }

                if ($useSalutation) {
                    $g          = strtoupper(trim((string) ($greetingRow['gender'] ?? '')));
                    $salutation = in_array($g, ['L', 'M'], true) ? 'Pak' : (in_array($g, ['P', 'F'], true) ? 'Bu' : null);
                }

                $employeeNickname = $greetingRow['nickname'] ?: null;
                $employeePosition = $greetingRow['position_name'] ?: null;
                $supervisorName   = ($greetingRow['sup_nick'] ?: $greetingRow['sup_full']) ?: null;
            }

            // ---------------------------------------------------------
            // 10. Scope Levels & Access Lookup
            // ---------------------------------------------------------
            $roleId      = (int) (session()->get('sys_role_id') ?? 0);
            $roleName    = strtoupper((string) (session()->get('role_name') ?? session()->get('name') ?? ''));
            $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

            $levels = ['me' => 'Saya'];
            $managerialLevellings = [100001, 100002, 100003, 100004];
            $isManagerialRole     = (bool) preg_match('/(MGR|SPV|GM|KACAB|DIREKTUR|OM)/i', $roleName);

            if (!empty($subordinateIds) || in_array($levellingId, $managerialLevellings, true) || $isManagerialRole) {
                $levels['team'] = 'Tim Saya';
            }

            if (in_array($roleId, $superRoleIds, true) || $levellingId === 100001 || str_contains($roleName, 'HRD')) {
                $levels['all'] = 'Semua';
            }

            $currentLevel = $this->request->getGet('level') ?? 'me';
            if (!array_key_exists($currentLevel, $levels)) {
                $currentLevel = array_key_first($levels);
            }

            $fetchAccess = function (string $table, string $accessTable, string $pkCol, string $targetEmpId) {
                return $this->db->table("{$table} m")
                    ->select("m.{$pkCol} as id, m.name")
                    ->join("{$accessTable} acc", "acc.{$pkCol} = m.{$pkCol}", 'inner')
                    ->join('sys_user su', 'su.sys_user_id = acc.sys_user_id', 'inner')
                    ->join('md_employee me', 'me.md_employee_id = su.md_employee_id', 'inner')
                    ->where('me.md_employee_id', $targetEmpId)
                    ->where('m.isactive', 'Y')
                    ->orderBy('m.name', 'ASC')
                    ->get()->getResultArray();
            };

            $resolveAccess = function (string $empId, array &$visited = []) use (&$resolveAccess, $fetchAccess) {
                if (empty($empId) || isset($visited[$empId])) {
                    return ['branches' => [], 'divisions' => []];
                }
                $visited[$empId] = true;

                $branches  = $fetchAccess('md_branch', 'sys_user_branchaccess', 'md_branch_id', $empId);
                $divisions = $fetchAccess('md_division', 'sys_user_divaccess', 'md_division_id', $empId);

                if (!empty($branches) && !empty($divisions)) {
                    return ['branches' => $branches, 'divisions' => $divisions];
                }

                $superiorRow = $this->db->table('md_employee')
                    ->select('superior_id')
                    ->where('md_employee_id', $empId)
                    ->get()->getRow();

                $superiorId = $superiorRow->superior_id ?? null;

                if (!empty($superiorId)) {
                    $parentAccess = $resolveAccess($superiorId, $visited);
                    if (empty($branches))  $branches  = $parentAccess['branches'];
                    if (empty($divisions)) $divisions = $parentAccess['divisions'];
                }

                return ['branches' => $branches, 'divisions' => $divisions];
            };

            $access    = $resolveAccess($employeeId);
            $branches  = $access['branches'];
            $divisions = $access['divisions'];
        }

        // TODO : Need to define all the default value
        // If not this will cause error 
        // -----------------------------------------------------------------
        // $data Array Payload (Hardened Default Values)
        // -----------------------------------------------------------------
        $todayDate = $today ?? date('Y-m-d');

        $data = [
            // Employee Profile & Greeting Header
            'employee_name'     => $employeeName ?? ($greetingRow['fullname'] ?? 'Karyawan'),
            'employee_nickname' => $employeeNickname ?? ($employeeName ?? 'Karyawan'),
            'employee_photo'    => $employeePhoto ?? null,
            'employee_position' => $employeePosition ?? '-',
            'salutation'        => $salutation ?? null,
            'is_birthday'       => $isBirthday ?? false,
            'work_anniversary'  => $workAnniversary ?? null,
            'tenure_text'       => $tenureText ?? null,
            'supervisor_name'   => $supervisorName ?? '-',
            'checkin_url'       => site_url('attendance/checkin'),
            'leave_remaining'   => $availableLeave ?? 0,
            'leave_total'       => $balance ?? 0,
            'employee_moments'  => $employeeMoments ?? [],
            'calendar_events'   => $calendarEvents ?? [],
            'announcements'     => $announcements ?? [],

            // Attendance Raw Data & Summary
            'have_clock_in'     => $haveClockIn ?? false,
            'have_clock_out'    => $haveClockOut ?? false,
            'clock_in_time'     => $clockInTime ?? null,
            'clock_out_time'    => $clockOutTime ?? null,
            'today_checkin'     => (!empty($haveClockIn) && !empty($clockInTime)) ? $clockInTime : null,
            'today_checkout'    => (!empty($haveClockOut) && !empty($clockOutTime)) ? $clockOutTime : null,
            'schedule_range'    => $scheduleRange ?? '-',
            'branch_name'       => $branchName ?? '-',
            'attendance_status' => $attendanceStatus ?? '-',
            'tkh_allowance'     => $tkhAllowance ?? 0,

            // Personal Attendance Card (box_attendance.php)
            'mode'              => $mode ?? 'month', // FIXED: 'month' instead of 'me'
            'label'             => $dateLabel ?? ('Hari ini, ' . date('d M Y', strtotime($todayDate))),
            'attendance_days'   => $attendanceDays ?? 0,     // x in x/y (Hadir)
            'work_days'         => $targetWorkDays ?? 0,     // y in x/y (Total target kerja)
            'work_days_elapsed' => $elapsedWorkDays ?? 0,    // Hari kerja berlalu
            'my_rate'           => $myRate ?? 0,              // Attendance %
            'my_leave_count'    => $myLeaveCount ?? 0,        // Total Izin / Cuti
            'my_absent_count'   => $myAbsentCount ?? 0,       // Total Alpa / Mangkir
            'late_count'        => $totalLate ?? 0,          // Total Terlambat + Tidak Lengkap
            'incomplete_count'  => $incompleteCount ?? 0,    // Tanpa Absen Masuk / Pulang
            'overtime_count'    => $overtimeCount ?? 0,      // Total Hari Lembur
            'my_streak'         => $streakCount ?? 0,        // Streak beruntun

            // Text & UI Theme Keys for box_attendance.php
            'my_status_text'      => $myStatusText ?? '-',
            'my_clock_in_text'    => $myClockInText ?? '--:--',
            'my_clock_out_text'   => $myClockOutText ?? '--:--',
            'my_punctuality_text' => $myPunctualityText ?? '-',
            'my_tone'             => $myTone ?? 'neutral',
            'my_shift_start'      => $myShiftStart ?? null,
            'my_shift_end'        => $myShiftEnd ?? null,
            'my_clock_in_raw'     => $myClockInRaw ?? null,
            'my_clock_out_raw'    => $myClockOutRaw ?? null,
            'my_days'             => $myDays ?? [],
            'my_ontime_count'     => $ontimeCount ?? 0,

            // Team Attendance Stats
            'team_total'        => $teamTotal ?? 0,
            'team_present'      => $teamPresent ?? 0,
            'team_on_leave'     => $teamOnLeave ?? 0,
            'team_rate'         => min(100, (float)($teamRate ?? 0)),
            'team_trend'        => is_array($teamTrend ?? null) ? $teamTrend : [], // FIXED: Array fallback instead of string 'flat'
            'show_team_card'    => $canViewTeamCard ?? false,

            // Filters & Scope
            'levels'            => $levels ?? ['me' => 'Saya'],
            'level'             => $currentLevel ?? 'me',
            'divisions'         => $divisions ?? [],
            'branches'          => $branches ?? [],

            // FIXED: Re-added missing dropdown filter selections
            'selected_branch'   => $selectedBranchId ?? null,
            'selected_division' => $selectedDivisionId ?? null,

            // Date Picker (date_picker.php)
            'date'              => (isset($dt) && $dt instanceof DateTimeInterface) ? $dt->format('Y-m-d') : $todayDate,
            'today'             => $todayDate,
            'todayDate'         => $todayDate,
            'dateLabel'         => $dateLabel ?? date('F Y', strtotime($todayDate)),
            'prevDate'          => $prevDate ?? date('Y-m-d', strtotime('-1 day', strtotime($todayDate))),
            'nextDate'          => $nextDate ?? date('Y-m-d', strtotime('+1 day', strtotime($todayDate))),
            'rangeStart'        => $rangeStart ?? date('Y-m-01', strtotime($todayDate)),
            'rangeEnd'          => $rangeEnd ?? date('Y-m-t', strtotime($todayDate)),

            // Pending Approvals Summary
            'pending_approvals' => $pendingApprovals ?? 0,
            'pending_summary'   => $pendingSummary ?? 'Tidak ada pengajuan berjalan',
            'pending_full'      => $pendingFull ?? 'Tidak ada pengajuan berjalan',
            'pending_breakdown' => $pendingBreakdown ?? [],
        ];

        // Debug Output
        if ($this->request->getGet('debug') === '1') {
            return $this->response->setJSON($data);
        }

        // Ajax Refresh
        if ($this->request->isAJAX() || $this->request->getGet('ajax') === '1') {
            return $this->response->setJSON($data);
        }

        return $this->template->render('dashboard/v_dashboard', $data);
    }

    public function getCalendarEvents()
    {
        try {
            $startDate  = $this->request->getGet('start');
            $endDate    = $this->request->getGet('end');
            $scope      = $this->request->getGet('level') ?? $this->request->getGet('scope') ?? 'team';
            $divisionId = $this->request->getGet('division');
            $branchId   = $this->request->getGet('branch');
            
            $rawCats    = $this->request->getGet('categories');
            $categories = is_array($rawCats) ? $rawCats : explode(',', (string) $rawCats);
            $categories = array_filter(array_map('trim', $categories));

            if (empty($categories)) {
                $categories = ['leave', 'holiday', 'bday', 'team'];
            }

            $employeeId  = session()->get('md_employee_id');
            $roleId      = (int) (session()->get('sys_role_id') ?? 0);
            $roleName    = strtoupper((string) (session()->get('role_name') ?? session()->get('name') ?? ''));
            $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

            $isHrd          = (strpos($roleName, 'HRD') !== false);
            $superRoleIds   = [1, 5, 6, 7, 21, 79, 80, 90];
            $hasSuperAccess = in_array($roleId, $superRoleIds, true) || $levellingId === 100001 || $isHrd;

            $fetchAccess = function (string $table, string $accessTable, string $pkCol, string $targetEmpId) {
                return $this->db->table("{$table} m")
                    ->select("m.{$pkCol} as id, m.name")
                    ->join("{$accessTable} acc", "acc.{$pkCol} = m.{$pkCol}", 'inner')
                    ->join('sys_user su', 'su.sys_user_id = acc.sys_user_id', 'inner')
                    ->join('md_employee me', 'me.md_employee_id = su.md_employee_id', 'inner')
                    ->where('me.md_employee_id', $targetEmpId)
                    ->where('m.isactive', 'Y')
                    ->orderBy('m.name', 'ASC')
                    ->get()->getResultArray();
            };

            $resolveAccess = function (string $empId, array &$visited = []) use (&$resolveAccess, $fetchAccess) {
                if (empty($empId) || isset($visited[$empId])) {
                    return ['branches' => [], 'divisions' => []];
                }
                $visited[$empId] = true;

                $branches  = $fetchAccess('md_branch', 'sys_user_branchaccess', 'md_branch_id', $empId);
                $divisions = $fetchAccess('md_division', 'sys_user_divaccess', 'md_division_id', $empId);

                if (!empty($branches) && !empty($divisions)) {
                    return ['branches' => $branches, 'divisions' => $divisions];
                }

                $superiorRow = $this->db->table('md_employee')
                    ->select('superior_id')
                    ->where('md_employee_id', $empId)
                    ->get()->getRow();

                $superiorId = $superiorRow->superior_id ?? null;

                if (!empty($superiorId) && $superiorId != $empId) {
                    $parentAccess = $resolveAccess((string) $superiorId, $visited);

                    if (empty($branches))  $branches  = $parentAccess['branches'];
                    if (empty($divisions)) $divisions = $parentAccess['divisions'];
                }

                return ['branches' => $branches, 'divisions' => $divisions];
            };

            $myAccess    = $resolveAccess((string) $employeeId);
            $myBranchIds = array_map('strval', array_column($myAccess['branches'], 'id'));
            $myDivIds    = array_map('strval', array_column($myAccess['divisions'], 'id'));

            $allTeamIds = [];
            if (class_exists('\App\Models\M_Employee')) {
                $mEmployee  = new \App\Models\M_Employee($this->request);
                $allTeamIds = $mEmployee->getChartEmployee($employeeId) ?? [];
            }
            if (empty($allTeamIds)) {
                $allTeamIds = [$employeeId];
            }

            $selectedBranch = (!empty($branchId) && $branchId !== 'all') ? (string) $branchId : null;
            $selectedDiv    = (!empty($divisionId) && $divisionId !== 'all') ? (string) $divisionId : null;
            $isFiltering    = ($selectedBranch !== null) || ($selectedDiv !== null);

            if ($hasSuperAccess && !$isFiltering && ($scope === 'all' || empty($scope))) {
                $targetEmployeeIds = null;
            } else {
                $empBuilder = $this->db->table('md_employee e')
                    ->select('e.md_employee_id')
                    ->where('e.isactive', 'Y');

                if ($isFiltering) {
                    if ($selectedBranch !== null) {
                        $empBuilder->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id AND eb.isactive = "Y"', 'inner')
                                  ->where('eb.md_branch_id', $selectedBranch);
                    }
                    if ($selectedDiv !== null) {
                        $empBuilder->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id AND ed.isactive = "Y"', 'inner')
                                  ->where('ed.md_division_id', $selectedDiv);
                    }
                } else {
                    $empBuilder->groupStart();
                    if (!empty($myBranchIds)) {
                        $empBuilder->orWhereIn('e.md_employee_id', function($sub) use ($myBranchIds) {
                            return $sub->select('md_employee_id')->from('md_employee_branch')->whereIn('md_branch_id', $myBranchIds)->where('isactive', 'Y');
                        });
                    }
                    if (!empty($myDivIds)) {
                        $empBuilder->orWhereIn('e.md_employee_id', function($sub) use ($myDivIds) {
                            return $sub->select('md_employee_id')->from('md_employee_division')->whereIn('md_division_id', $myDivIds)->where('isactive', 'Y');
                        });
                    }
                    if (!empty($allTeamIds)) {
                        $empBuilder->orWhereIn('e.md_employee_id', $allTeamIds);
                    }
                    $empBuilder->groupEnd();
                }

                $rows = $empBuilder->get()->getResultArray();
                $targetEmployeeIds = array_column($rows, 'md_employee_id');

                if (empty($targetEmployeeIds)) {
                    $targetEmployeeIds = [0];
                }
            }

            $events   = [];
            $colorMap = [
                'leave'   => '#f59e0b',
                'holiday' => '#dc2626',
                'bday'    => '#8b5cf6',
                'team'    => '#10b981',
            ];

            $superRoleIds = [1, 4, 5, 6, 21, 70, 71, 74, 79, 80, 90];
            $subCats = array_intersect($categories, ['leave', 'team']);
            $canViewSubmissions = in_array($roleId, $superRoleIds, true) 
                || in_array($levellingId, [100001, 100002], true);

            if (!$canViewSubmissions) {
                $subCats = [];
            }
            if (!empty($subCats)) {
                $builder = $this->db->table('v_all_submission s')
                    ->select('
                        s.documentno,
                        MAX(s.date) as date, 
                        MAX(s.reason) as reason, 
                        MAX(s.docstatus) as docstatus, 
                        MAX(s.ref_id) as ref_id, 
                        MAX(s.ref_table) as ref_table, 
                        MAX(dt.md_doctype_id) as md_doctype_id, 
                        MAX(dt.name) as doctype_name, 
                        MAX(e.fullname) as employee_name, 
                        MAX(e.nik) as employee_nik,
                        MAX(b.name) as branch_name, 
                        MAX(d.name) as division_name
                    ')
                    ->join('md_doctype dt', 'dt.md_doctype_id = s.submissiontype', 'inner')
                    ->join('md_employee e', 'e.md_employee_id = s.md_employee_id', 'inner')
                    ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id AND eb.isactive = "Y"', 'left')
                    ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
                    ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id AND ed.isactive = "Y"', 'left')
                    ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
                    ->whereIn('s.docstatus', ['CO', 'AP', 'IP']);

                if ($targetEmployeeIds !== null) {
                    $builder->whereIn('s.md_employee_id', $targetEmployeeIds);
                }
                if ($startDate && $endDate) {
                    $builder->where('s.date >=', $startDate)->where('s.date <=', $endDate);
                }

                $submissions = $builder->groupBy('s.documentno')->get()->getResult();

                foreach ($submissions as $sub) {
                    $catKey = (isset($sub->md_doctype_id) && $sub->md_doctype_id == 100003) ? 'leave' : 'team';
                    if (!in_array($catKey, $categories, true)) continue;

                    $events[] = [
                        'id'              => $sub->documentno,
                        'title'           => ucwords(strtolower($sub->doctype_name ?? 'Pengajuan')) . " - " . ($sub->employee_name ?? 'Karyawan'),
                        'start'           => date('Y-m-d', strtotime($sub->date)),
                        'className'       => 't-' . $catKey,
                        'backgroundColor' => $colorMap[$catKey],
                        'type'            => $catKey,
                        'extendedProps'   => [
                            'documentno'   => $sub->documentno,
                            'doctype_name' => ucwords(strtolower($sub->doctype_name ?? 'Pengajuan')),
                            'employee'     => $sub->employee_name ?? 'Karyawan',
                            'nik'          => $sub->employee_nik ?? '',
                            'branch'       => $sub->branch_name ?? '-',
                            'division'     => $sub->division_name ?? '-',
                            'status'       => $sub->docstatus ?? 'CO',
                            'reason'       => $sub->reason ?? '-',
                            'ref_id'       => $sub->ref_id ?? '',
                            'ref_table'    => $sub->ref_table ?? ''
                        ]
                    ];
                }
            }

            if (in_array('bday', $categories, true)) {
                $bdayBuilder = $this->db->table('md_employee e')
                    ->select('
                        e.md_employee_id,
                        MAX(e.fullname) as employee_name,
                        MAX(e.nik) as employee_nik,
                        MAX(e.birthday) as birthday,
                        MAX(b.name) as branch_name,
                        MAX(d.name) as division_name
                    ')
                    ->join('md_employee_branch eb', 'eb.md_employee_id = e.md_employee_id AND eb.isactive = "Y"', 'left')
                    ->join('md_branch b', 'b.md_branch_id = eb.md_branch_id', 'left')
                    ->join('md_employee_division ed', 'ed.md_employee_id = e.md_employee_id AND ed.isactive = "Y"', 'left')
                    ->join('md_division d', 'd.md_division_id = ed.md_division_id', 'left')
                    ->where('e.isactive', 'Y')
                    ->where('e.birthday IS NOT NULL')
                    ->groupBy('e.md_employee_id');

                if ($targetEmployeeIds !== null) {
                    $bdayBuilder->whereIn('e.md_employee_id', $targetEmployeeIds);
                }

                $bdayEmps  = $bdayBuilder->get()->getResult();
                $startYear = (int) date('Y', strtotime($startDate));
                $endYear   = (int) date('Y', strtotime($endDate));

                foreach ($bdayEmps as $emp) {
                    if (empty($emp->birthday)) continue;
                    $dobMonthDay = date('m-d', strtotime($emp->birthday));

                    for ($yr = $startYear; $yr <= $endYear; $yr++) {
                        $bdayDate = $yr . '-' . $dobMonthDay;
                        if ($bdayDate >= $startDate && $bdayDate <= $endDate) {
                            $events[] = [
                                'id'              => 'bday_' . $emp->md_employee_id . '_' . $yr,
                                'title'           => 'Ulang Tahun - ' . ($emp->employee_name ?? 'Karyawan'),
                                'start'           => $bdayDate,
                                'className'       => 't-bday',
                                'backgroundColor' => $colorMap['bday'],
                                'type'            => 'bday',
                                'extendedProps'   => [
                                    'employee' => $emp->employee_name ?? 'Karyawan',
                                    'nik'      => $emp->employee_nik ?? '',
                                    'branch'   => $emp->branch_name ?? '-',
                                    'division' => $emp->division_name ?? '-'
                                ]
                            ];
                        }
                    }
                }
            }

            if (in_array('holiday', $categories, true)) {
                $year     = $startDate ? date('Y', strtotime($startDate)) : date('Y');
                $holidays = cache("google_id_holidays_{$year}") ?? [];

                foreach ($holidays as $index => $h) {
                    $events[] = [
                        'id'              => 'holiday_' . $index,
                        'title'           => "Libur - {$h['title']}",
                        'start'           => $h['date'],
                        'allDay'          => true,
                        'className'       => 't-holiday',
                        'backgroundColor' => $colorMap['holiday'],
                        'type'            => 'holiday'
                    ];
                }
            }

            return $this->response->setJSON($events);

        } catch (\Throwable $e) {
            log_message('error', 'Calendar Events Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $this->response->setStatusCode(500)->setJSON(['error' => $e->getMessage()]);
        }
    }
}