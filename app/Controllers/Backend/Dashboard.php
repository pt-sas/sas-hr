<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_LeaveBalance;

class Dashboard extends BaseController
{
    protected $db;

    private array $debugLog = [];
    public array $rangeQueryStats = [];

    // Configurable engine thresholds
    private int $lateToleranceMinutes = 5;  // 5-minute late grace period
    private int $tapDeduplicationSec  = 60; // Ignore rapid biometric taps within 60 seconds

    // Super user role IDs (System Admins, HR Super Users, Management Executives)
    protected array $superRoleIds = [1, 4, 5, 6, 21, 70, 71, 74, 79, 80, 90];

    // Managerial levellings (Managers & Supervisors)
    protected array $managerialLevellings = [100001, 100002, 100003, 100004];

    // Submission document statuses
    protected array $pendingDocStatuses   = ['DR', 'RQ', 'IP', 'RE', 'RO', 'XL'];
    protected array $completedDocStatuses = ['AP', 'CO'];
    protected array $closedDocStatuses    = ['NA', 'VO', 'IN', 'OS', 'AB'];

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
        // Query Tracking via CI4 native DBQuery event
        // ---------------------------------------------------------
        $totalQueries = 0;
        \CodeIgniter\Events\Events::on('DBQuery', static function () use (&$totalQueries) {
            $totalQueries++;
        });

        $reqStartTime  = microtime(true);
        $sectionTimers = [];
        $lastTime      = $reqStartTime;
        $lastQ         = 0;

        $checkpoint = function (string $sectionName) use (&$sectionTimers, &$lastTime, &$lastQ, &$totalQueries) {
            $nowTime = microtime(true);
            $nowQ    = $totalQueries;

            $sectionTimers[$sectionName] = [
                'time_ms' => round(($nowTime - $lastTime) * 1000, 2),
                'queries' => $nowQ - $lastQ,
            ];

            $lastTime = $nowTime;
            $lastQ    = $nowQ;
        };

        // 1. Date & Mode Parameter Processing
        $dateParams   = $this->resolveDateParameters();
        $mode         = $dateParams['mode'];
        $dt           = $dateParams['dt'];
        $today        = $dateParams['today'];
        $currentMonth = $dateParams['currentMonth'];
        $currentYear  = $dateParams['currentYear'];
        $prevDate     = $dateParams['prevDate'];
        $nextDate     = $dateParams['nextDate'];
        $rangeStart   = $dateParams['rangeStart'];
        $rangeEnd     = $dateParams['rangeEnd'];
        $dateLabel    = $dateParams['dateLabel'];
        $wStart       = $dateParams['wStart'];

        // 2. Employee Identity
        $employeeName = $this->access->getUser('name') ?? $this->access->getUser('username');
        $employeeId   = session()->get('md_employee_id');
        $nik          = $this->resolveEmployeeNik($employeeId ? (int) $employeeId : null);

        // Pre-fetch shared holidays & mass leaves
        $holidayRows   = $this->fetchHolidayRows($rangeEnd);
        $massLeaveRows = $this->fetchMassLeaveRows($rangeEnd);

        // Component Data Containers (Hardened Defaults)
        $personalAtt   = [];
        $teamStats     = [];
        $leaveData     = ['balance' => 0, 'available_leave' => 0];
        $pendingData   = [
            'pending_approvals' => 0,
            'pending_summary'   => 'Tidak ada pengajuan berjalan',
            'pending_full'      => 'Tidak ada pengajuan berjalan',
            'pending_breakdown' => []
        ];
        $calEvents     = [];
        $announcements = [];
        $greetingData  = [];
        $scopeData     = [
            'levels'        => ['me' => 'Saya'],
            'current_level' => 'me',
            'branches'      => [],
            'divisions'     => []
        ];

        if (!empty($employeeId) && !empty($nik)) {
            // 3. Personal Attendance Calculation
            $personalAtt = $this->calculatePersonalAttendance(
                (int) $employeeId,
                (string) $nik,
                $today,
                $rangeStart,
                $rangeEnd,
                $holidayRows,
                $massLeaveRows
            );
            $checkpoint('1_attendance_engine');

            // 4. Team Attendance Stats
            $teamStats = $this->calculateTeamAttendanceStats(
                (int) $employeeId,
                $mode,
                $today,
                $currentYear,
                $currentMonth,
                $wStart,
                $holidayRows,
                $massLeaveRows
            );
            $checkpoint('2_team_stats');

            // 5. Leave Balance Calculation
            $leaveData = $this->getLeaveBalanceData((int) $employeeId, $today, $currentYear);
            $checkpoint('3_leave_balance');

            // 6. Pending Approvals
            $pendingData = $this->getPendingApprovalsData((int) $employeeId, $rangeEnd);
            $checkpoint('4_pending_approvals');

            // 7. Calendar Subordinates & Birthday Events
            $calEvents = $this->getSubordinateCalendarEvents($teamStats['subordinate_ids'] ?? [], $currentYear);
            $checkpoint('5_calendar_birthdays');

            // 8. Announcements
            $announcements = $this->getUnreadAnnouncements((int) $employeeId, 25);
            $checkpoint('6_announcements');

            // 9. Greetings & Personalization
            $greetingData = $this->getEmployeeGreetingData((int) $employeeId);
            $checkpoint('7_greetings');

            // 10. Scope Levels & Access Lookup
            $scopeData = $this->resolveDashboardScope((int) $employeeId, $teamStats['subordinate_ids'] ?? []);
            $checkpoint('8_access_resolution');
        }

        $todayDate = $today ?? date('Y-m-d');

        $data = [
            // Employee Profile & Greeting Header
            'employee_name'     => $employeeName ?? ($greetingData['greeting_row']['fullname'] ?? 'Karyawan'),
            'employee_nickname' => $greetingData['employee_nickname'] ?? ($employeeName ?? 'Karyawan'),
            'employee_photo'    => $greetingData['employee_photo'] ?? null,
            'employee_position' => $greetingData['employee_position'] ?? '-',
            'salutation'        => $greetingData['salutation'] ?? null,
            'is_birthday'       => $greetingData['is_birthday'] ?? false,
            'work_anniversary'  => $greetingData['work_anniversary'] ?? null,
            'tenure_text'       => $greetingData['tenure_text'] ?? null,
            'supervisor_name'   => $greetingData['supervisor_name'] ?? '-',
            'checkin_url'       => site_url('attendance/checkin'),
            'leave_remaining'   => $leaveData['available_leave'] ?? 0,
            'leave_total'       => $leaveData['balance'] ?? 0,
            'calendar_events'   => $calEvents,
            'announcements'     => $announcements,

            // Attendance Raw Data & Summary
            'have_clock_in'     => $personalAtt['have_clock_in'] ?? false,
            'have_clock_out'    => $personalAtt['have_clock_out'] ?? false,
            'clock_in_time'     => $personalAtt['clock_in_time'] ?? null,
            'clock_out_time'    => $personalAtt['clock_out_time'] ?? null,
            'today_checkin'     => (!empty($personalAtt['have_clock_in']) && !empty($personalAtt['clock_in_time'])) ? $personalAtt['clock_in_time'] : null,
            'today_checkout'    => (!empty($personalAtt['have_clock_out']) && !empty($personalAtt['clock_out_time'])) ? $personalAtt['clock_out_time'] : null,
            'schedule_range'    => $personalAtt['schedule_range'] ?? '-',
            'branch_name'       => $personalAtt['branch_name'] ?? '-',
            'attendance_status' => $personalAtt['attendance_status'] ?? '-',
            'tkh_allowance'     => $personalAtt['tkh_allowance'] ?? 0,

            // Personal Attendance Card (box_attendance.php)
            'mode'              => $mode ?? 'month',
            'label'             => $dateLabel ?? ('Hari ini, ' . date('d M Y', strtotime($todayDate))),
            'attendance_days'   => $personalAtt['attendance_days'] ?? 0,     // x in x/y (Hadir)
            'work_days'         => $personalAtt['target_work_days'] ?? 0,    // y in x/y (Total target kerja)
            'work_days_elapsed' => $personalAtt['elapsed_work_days'] ?? 0,   // Hari kerja berlalu
            'my_rate'           => $personalAtt['my_rate'] ?? 0,             // Attendance %
            'my_leave_count'    => $personalAtt['my_leave_count'] ?? 0,       // Total Izin / Cuti
            'my_absent_count'   => $personalAtt['my_absent_count'] ?? 0,      // Total Alpa / Mangkir
            'late_count'        => $personalAtt['total_late'] ?? 0,          // Total Terlambat + Tidak Lengkap
            'incomplete_count'  => $personalAtt['incomplete_count'] ?? 0,    // Tanpa Absen Masuk / Pulang
            'overtime_count'    => $personalAtt['overtime_count'] ?? 0,      // Total Hari Lembur
            'my_streak'         => 0,                                        // Streak beruntun

            // Text & UI Theme Keys for box_attendance.php
            'my_status_text'      => $personalAtt['my_status_text'] ?? '-',
            'my_clock_in_text'    => $personalAtt['my_clock_in_text'] ?? '--:--',
            'my_clock_out_text'   => $personalAtt['my_clock_out_text'] ?? '--:--',
            'my_punctuality_text' => $personalAtt['my_punctuality_text'] ?? '-',
            'my_tone'             => $personalAtt['my_tone'] ?? 'neutral',
            'my_shift_start'      => $personalAtt['my_shift_start'] ?? null,
            'my_shift_end'        => $personalAtt['my_shift_end'] ?? null,
            'my_clock_in_raw'     => $personalAtt['my_clock_in_raw'] ?? null,
            'my_clock_out_raw'    => $personalAtt['my_clock_out_raw'] ?? null,
            'my_days'             => [],
            'my_ontime_count'     => $personalAtt['ontime_count'] ?? 0,

            // Team Attendance Stats
            'team_total'        => $teamStats['team_total'] ?? 0,
            'team_present'      => $teamStats['team_present'] ?? 0,
            'team_on_leave'     => $teamStats['team_on_leave'] ?? 0,
            'team_rate'         => min(100, (float)($teamStats['team_rate'] ?? 0)),
            'team_trend'        => is_array($teamStats['team_trend'] ?? null) ? $teamStats['team_trend'] : [],
            'show_team_card'    => $teamStats['can_view_team_card'] ?? false,

            // Filters & Scope
            'levels'            => $scopeData['levels'] ?? ['me' => 'Saya'],
            'level'             => $scopeData['current_level'] ?? 'me',
            'divisions'         => $scopeData['divisions'] ?? [],
            'branches'          => $scopeData['branches'] ?? [],

            'selected_branch'   => null,
            'selected_division' => null,

            // Date Picker (date_picker.php)
            'date'              => (isset($dt) && $dt instanceof \DateTimeInterface) ? $dt->format('Y-m-d') : $todayDate,
            'today'             => $todayDate,
            'todayDate'         => $todayDate,
            'dateLabel'         => $dateLabel ?? date('F Y', strtotime($todayDate)),
            'prevDate'          => $prevDate ?? date('Y-m-d', strtotime('-1 day', strtotime($todayDate))),
            'nextDate'          => $nextDate ?? date('Y-m-d', strtotime('+1 day', strtotime($todayDate))),
            'rangeStart'        => $rangeStart ?? date('Y-m-01', strtotime($todayDate)),
            'rangeEnd'          => $rangeEnd ?? date('Y-m-t', strtotime($todayDate)),

            // Pending Approvals Summary
            'pending_approvals' => $pendingData['pending_approvals'] ?? 0,
            'pending_summary'   => $pendingData['pending_summary'] ?? 'Tidak ada pengajuan berjalan',
            'pending_full'      => $pendingData['pending_full'] ?? 'Tidak ada pengajuan berjalan',
            'pending_breakdown' => $pendingData['pending_breakdown'] ?? [],
        ];

        // Debug Output
        if ($this->request->getGet('debug') === '1') {
            $checkpoint('9_data_assembly');

            $data['baseline_metrics'] = [
                'mode'               => $mode,
                'total_time_ms'      => round((microtime(true) - $reqStartTime) * 1000, 2),
                'total_queries'      => $totalQueries,
                'sections'           => $sectionTimers,
                'engine_loop_daily'  => $this->rangeQueryStats,
            ];

            return $this->response->setJSON($data);
        }

        // Ajax Refresh
        if ($this->request->isAJAX() || $this->request->getGet('ajax') === '1') {
            return $this->response->setJSON($data);
        }

        return $this->template->render('dashboard/v_dashboard', $data);
    }

    // -------------------------------------------------------------------------
    // Index Decomposed Helper Functions
    // -------------------------------------------------------------------------

    /**
     * Resolves date & period parameters for day, week, and month modes.
     */
    private function resolveDateParameters(): array
    {
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

        $wStart = null;
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
                $dateLabel = $wStart->format('j') . ' – ' . $wEnd->format('j') . ' ' . ($months[$wEnd->format('m')] ?? $wStart->format('M')) . ' ' . $wEnd->format('Y');
            }

        } else {
            $firstOfMonth = (clone $dt)->modify('first day of this month');
            $prevDate     = (clone $firstOfMonth)->modify('first day of previous month')->format('Y-m-d');
            $nextDate     = (clone $firstOfMonth)->modify('first day of next month')->format('Y-m-d');
            $rangeStart   = $firstOfMonth->format('Y-m-d');
            $rangeEnd     = (clone $firstOfMonth)->modify('last day of this month')->format('Y-m-d');
            $dateLabel    = ($months[$dt->format('m')] ?? $dt->format('M')) . ' ' . $dt->format('Y');
        }

        return [
            'mode'         => $mode,
            'dt'           => $dt,
            'today'        => $today,
            'currentMonth' => $currentMonth,
            'currentYear'  => $currentYear,
            'prevDate'     => $prevDate,
            'nextDate'     => $nextDate,
            'rangeStart'   => $rangeStart,
            'rangeEnd'     => $rangeEnd,
            'dateLabel'    => $dateLabel,
            'wStart'       => $wStart,
        ];
    }

    /**
     * Resolves employee NIK from session or employee master table.
     */
    private function resolveEmployeeNik(?int $employeeId): ?string
    {
        $nik = session()->get('nik');
        if (empty($nik) && !empty($employeeId)) {
            $empRow = $this->db->table('md_employee')
                ->select('nik')
                ->where('md_employee_id', $employeeId)
                ->get()->getRow();
            $nik = $empRow->nik ?? null;
        }
        return $nik;
    }

    /**
     * Fetches active national holidays within the specified range.
     */
    private function fetchHolidayRows(string $rangeEnd): array
    {
        return $this->db->table('md_holiday')
            ->select('name, DATE(startdate) AS start_date, DATE(CASE WHEN enddate IS NULL OR CAST(enddate AS CHAR) LIKE "0000%" OR CAST(enddate AS CHAR) = "" THEN startdate ELSE enddate END) AS end_date')
            ->where('isactive', 'Y')
            ->where('startdate <=', $rangeEnd . ' 23:59:59')
            ->get()->getResultArray();
    }

    /**
     * Fetches active mass leaves within the specified range.
     */
    private function fetchMassLeaveRows(string $rangeEnd): array
    {
        return $this->db->table('md_massleave')
            ->select('name, DATE(startdate) AS start_date, DATE(CASE WHEN enddate IS NULL OR CAST(enddate AS CHAR) LIKE "0000%" OR CAST(enddate AS CHAR) = "" THEN startdate ELSE enddate END) AS end_date')
            ->where('isactive', 'Y')
            ->where('startdate <=', $rangeEnd . ' 23:59:59')
            ->get()->getResultArray();
    }

    /**
     * Calculates personal attendance summary, schedule, and KPI metrics.
     */
    private function calculatePersonalAttendance(
        int $employeeId,
        string $nik,
        string $today,
        string $rangeStart,
        string $rangeEnd,
        array $holidayRows,
        array $massLeaveRows
    ): array {
        $todayStr     = date('Y-m-d');
        $processUntil = ($rangeEnd > $todayStr) ? $todayStr : $rangeEnd;

        $rowsByDate = [];
        if ($rangeStart <= $processUntil) {
            $rowsByDate = $this->processEmployeeRange($nik, $rangeStart, $processUntil);
        }

        if (isset($rowsByDate[$today])) {
            $todayCalc = $rowsByDate[$today];
        } else {
            $todayCalc = $this->processEmployeeDay($nik, $today);
        }

        $dayNo = (int) date('N', strtotime($today)); // 1 (Mon) - 7 (Sun)

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

        $myClockInRaw     = $todayCalc['clock_in'] ?? null;
        $myClockOutRaw    = $todayCalc['clock_out'] ?? null;
        $haveClockIn      = !empty($myClockInRaw);
        $haveClockOut     = !empty($myClockOutRaw);

        $clockInTime      = $haveClockIn ? date('H:i', strtotime($myClockInRaw)) : 'Belum absen';
        $clockOutTime     = $haveClockOut ? date('H:i', strtotime($myClockOutRaw)) : 'Belum absen';

        $scheduleRange    = $empSchedule->schedule_range ?? 'Libur / Tidak Ada Jadwal';
        $branchName       = $empSchedule->branch_name ?? '-';
        $attendanceStatus = $todayCalc['attendance_status'] ?? 'ABSENT';
        $tkhAllowance     = (float) ($todayCalc['tkh_allowance'] ?? 1.0);

        $statusMap = [
            'NORMAL'                    => ['text' => 'Hadir Tepat Waktu',        'tone' => 'success',   'punctual' => 'Tepat Waktu'],
            'IN_OFFICE'                 => ['text' => 'Sedang Bekerja',          'tone' => 'primary',   'punctual' => 'Dalam Jam Kerja'],
            'IN_OFFICE_LATE'            => ['text' => 'Bekerja (Terlambat)',     'tone' => 'warning',   'punctual' => 'Terlambat Datang'],
            'LATE_ARRIVAL'              => ['text' => 'Terlambat Datang',        'tone' => 'warning',   'punctual' => 'Terlambat'],
            'UNEXCUSED_EARLY_DEPARTURE' => ['text' => 'Pulang Cepat',            'tone' => 'warning',   'punctual' => 'Pulang Lebih Awal'],
            'LATE_AND_EARLY_LEAVE'      => ['text' => 'Terlambat & Pulang Cepat', 'tone' => 'danger',    'punctual' => 'Jam Inkomplit'],
            'MISSING_CHECK_IN'          => ['text' => 'Tidak Absen Masuk',       'tone' => 'danger',    'punctual' => 'Tanpa Absen Masuk'],
            'MISSING_CHECK_OUT'         => ['text' => 'Tidak Absen Pulang',      'tone' => 'danger',    'punctual' => 'Tanpa Absen Pulang'],
            'LATE_AND_MISSING_OUT'      => ['text' => 'Terlambat & Tanpa Pulang', 'tone' => 'danger',    'punctual' => 'Tidak Lengkap'],
            'ABSENT'                    => ['text' => 'Tidak Hadir',             'tone' => 'danger',    'punctual' => 'Mangkir'],
            'LEAVE_APPROVED'            => ['text' => 'Izin / Cuti Disetujui',    'tone' => 'info',      'punctual' => 'Izin Disetujui'],
            'HALF_DAY_APPROVED'         => ['text' => 'Izin Setengah Hari',      'tone' => 'info',      'punctual' => 'Setengah Hari'],
            'LATE_EXCUSED'              => ['text' => 'Terlambat (Disetujui)',    'tone' => 'info',      'punctual' => 'Izin Terlambat'],
            'EARLY_LEAVE_EXCUSED'       => ['text' => 'Pulang Cepat (Disetujui)', 'tone' => 'info',      'punctual' => 'Izin Pulang Cepat'],
            'APPROVED_SHORT_HOURS'      => ['text' => 'Izin Jam Kerja',          'tone' => 'info',      'punctual' => 'Izin Jam Kerja'],
            'MANUAL_HRD_OVERRIDE'       => ['text' => 'Koreksi HRD',             'tone' => 'info',      'punctual' => 'Disesuaikan HRD'],
            'OFF_DAY'                   => ['text' => 'Hari Libur Kerja',        'tone' => 'secondary', 'punctual' => 'Libur Kerja'],
            'PUBLIC_HOLIDAY'            => ['text' => 'Libur Nasional',          'tone' => 'secondary', 'punctual' => 'Libur Nasional'],
            'OVERTIME_OFF_DAY'          => ['text' => 'Lembur Hari Libur',       'tone' => 'info',      'punctual' => 'Kerja Hari Libur'],
            'HOLIDAY_WORKED'            => ['text' => 'Lembur Libur Nasional',   'tone' => 'info',      'punctual' => 'Kerja Libur Nasional'],
        ];

        $meta               = $statusMap[$attendanceStatus] ?? ['text' => $attendanceStatus, 'tone' => 'secondary', 'punctual' => 'Catatan Khusus'];
        $myStatusText       = $meta['text'];
        $myTone             = $meta['tone'];
        $myPunctualityText  = $meta['punctual'];

        $myClockInText      = $clockInTime;
        $myClockOutText     = $clockOutTime;

        $myShiftStart = !empty($empSchedule->startwork) ? date('H:i', strtotime($empSchedule->startwork)) : '08:00';
        $myShiftEnd   = !empty($empSchedule->endwork)   ? date('H:i', strtotime($empSchedule->endwork))   : '17:00';

        $periodAll = new \DatePeriod(
            new \DateTime($rangeStart),
            new \DateInterval('P1D'),
            (new \DateTime($rangeEnd))->modify('+1 day')
        );

        $schedDays = $this->db->table('md_employee_work mew')
            ->select('mwd.md_day_id')
            ->join('md_work mw', 'mw.md_work_id = mew.md_work_id')
            ->join('md_work_detail mwd', 'mw.md_work_id = mwd.md_work_id')
            ->where('mew.md_employee_id', $employeeId)
            ->where('mew.isactive', 'Y')
            ->get()->getResultArray();

        $scheduledDayIds = array_column($schedDays, 'md_day_id');

        $isOffDate = function($wDate) use ($holidayRows, $massLeaveRows) {
            foreach ($holidayRows as $h) {
                if ($wDate >= $h['start_date'] && $wDate <= $h['end_date']) return true;
            }
            foreach ($massLeaveRows as $m) {
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
                $targetWorkDays++;
                if ($isPastOrToday) {
                    $elapsedWorkDays++;
                }
            }

            if ($isPastOrToday && isset($rowsByDate[$wDate])) {
                $status = $rowsByDate[$wDate]['attendance_status'];
                switch ($status) {
                    case 'NORMAL':
                    case 'IN_OFFICE':
                    case 'MANUAL_HRD_OVERRIDE':
                    case 'OFFICIAL_DUTY':
                        $ontimeCount++;
                        break;

                    case 'OVERTIME_OFF_DAY':
                    case 'HOLIDAY_WORKED':
                    case 'MASS_LEAVE_WORKED':
                        $ontimeCount++;
                        $overtimeCount++;
                        break;

                    case 'IN_OFFICE_LATE':
                    case 'LATE_ARRIVAL':
                    case 'LATE_AND_EARLY_LEAVE':
                        $lateCount++;
                        break;

                    case 'MISSING_CHECK_IN':
                    case 'MISSING_CHECK_OUT':
                    case 'LATE_AND_MISSING_OUT':
                        $incompleteCount++;
                        break;

                    case 'LEAVE_APPROVED':
                    case 'HALF_DAY_APPROVED':
                    case 'LATE_EXCUSED':
                    case 'EARLY_LEAVE_EXCUSED':
                    case 'APPROVED_SHORT_HOURS':
                    case 'LATE_WITH_APPROVED_LEAVE':
                        $myLeaveCount++;
                        break;

                    case 'ABSENT':
                    case 'UNEXCUSED_EARLY_DEPARTURE':
                        $myAbsentCount++;
                        break;
                }
            }
        }

        $attendanceDays = $ontimeCount + $lateCount + $incompleteCount;
        $totalLate      = $lateCount + $incompleteCount;
        $fulfilledDays  = $attendanceDays + $myLeaveCount;

        $targetWorkDays = max(1, $targetWorkDays);
        $denomElapsed   = max(1, $elapsedWorkDays);
        $myRate         = min(100, (int) round(($fulfilledDays / $denomElapsed) * 100));

        return [
            'have_clock_in'       => $haveClockIn,
            'have_clock_out'      => $haveClockOut,
            'clock_in_time'       => $clockInTime,
            'clock_out_time'      => $clockOutTime,
            'schedule_range'      => $scheduleRange,
            'branch_name'         => $branchName,
            'attendance_status'   => $attendanceStatus,
            'tkh_allowance'       => $tkhAllowance,
            'my_status_text'      => $myStatusText,
            'my_clock_in_text'    => $myClockInText,
            'my_clock_out_text'   => $myClockOutText,
            'my_punctuality_text' => $myPunctualityText,
            'my_tone'             => $myTone,
            'my_shift_start'      => $myShiftStart,
            'my_shift_end'        => $myShiftEnd,
            'my_clock_in_raw'     => $myClockInRaw,
            'my_clock_out_raw'    => $myClockOutRaw,
            'attendance_days'     => $attendanceDays,
            'target_work_days'    => $targetWorkDays,
            'elapsed_work_days'   => $elapsedWorkDays,
            'my_rate'             => $myRate,
            'my_leave_count'      => $myLeaveCount,
            'my_absent_count'     => $myAbsentCount,
            'total_late'          => $totalLate,
            'incomplete_count'    => $incompleteCount,
            'overtime_count'      => $overtimeCount,
            'ontime_count'        => $ontimeCount,
        ];
    }

    /**
     * Calculates team daily attendance metrics and weekly/monthly trends for managerial levels.
     */
    private function calculateTeamAttendanceStats(
        int $employeeId,
        string $mode,
        string $today,
        string $currentYear,
        string $currentMonth,
        ?\DateTime $wStart,
        array $holidayRows,
        array $massLeaveRows
    ): array {
        $roleId      = (int) (session()->get('sys_role_id') ?? 0);
        $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

        $canViewTeamCard = in_array($roleId, $this->superRoleIds, true) 
            || in_array($levellingId, $this->managerialLevellings, true);

        $candidateIds = [];

        if ($canViewTeamCard) {
            $mEmployee    = new M_Employee($this->request);
            $allTeamIds   = $mEmployee->getChartEmployee($employeeId) ?? [];
            $candidateIds = array_values(array_diff($allTeamIds, [$employeeId]));
        }

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
            if ($mode === 'day') {
                $startDate = $today;
                $endDate   = $today;
            } elseif ($mode === 'week' && $wStart) {
                $startDate = $wStart->format('Y-m-d');
                $endDate   = (clone $wStart)->modify('+6 days')->format('Y-m-d');
            } else {
                $firstDay  = new \DateTime("{$currentYear}-{$currentMonth}-01");
                $startDate = $firstDay->format('Y-m-d');
                $endDate   = (clone $firstDay)->modify('last day of this month')->format('Y-m-d');
            }

            $offDaysMap = [];
            $processOffDays = function ($rows) use (&$offDaysMap) {
                foreach ($rows as $row) {
                    if (empty($row['start_date']) || $row['start_date'] === '0000-00-00') {
                        continue;
                    }

                    $startDateObj = new \DateTime($row['start_date']);
                    $endDateObj   = (!empty($row['end_date']) && !in_array($row['end_date'], ['0000-00-00', '']))
                        ? new \DateTime($row['end_date'])
                        : clone $startDateObj;

                    $curr = clone $startDateObj;
                    while ($curr <= $endDateObj) {
                        $offDaysMap[$curr->format('Y-m-d')] = $row['name'];
                        $curr->modify('+1 day');
                    }
                }
            };

            $processOffDays($holidayRows);
            $processOffDays($massLeaveRows);

            $penugasanRows = $this->db->table('trx_assignment_date adate')
                ->select('adetail.md_employee_id, DATE(adate.date) as date')
                ->join('trx_assignment_detail adetail', 'adetail.trx_assignment_detail_id = adate.trx_assignment_detail_id', 'inner')
                ->whereIn('adetail.md_employee_id', $subordinateIds)
                ->where('adate.date >=', $startDate . ' 00:00:00')
                ->where('adate.date <=', $endDate . ' 23:59:59')
                ->where('adate.isagree', 'Y')
                ->get()->getResultArray();

            $penugasanMap = [];
            foreach ($penugasanRows as $p) {
                $penugasanMap[$p['date']][$p['md_employee_id']] = true;
            }

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

            $attendanceRows = $this->db->table('trx_attendance t')
                ->select('e.md_employee_id, t.work_date as date')
                ->join('md_employee e', 'e.nik = t.nik', 'inner')
                ->whereIn('e.md_employee_id', $subordinateIds)
                ->where('t.work_date >=', $startDate)
                ->where('t.work_date <=', $endDate)
                ->groupBy('e.md_employee_id, t.work_date')
                ->get()->getResultArray();

            $attendanceMap = [];
            foreach ($attendanceRows as $att) {
                $attendanceMap[$att['date']][$att['md_employee_id']] = true;
            }

            $evaluateDailyStats = function ($dateStr) use ($subordinateIds, $offDaysMap, $penugasanMap, $employeeWorkDays, $attendanceMap) {
                $dt        = new \DateTime($dateStr);
                $dayOfWeek = (int) $dt->format('N'); // 1 (Mon) to 7 (Sun)
                $isWeekend = ($dayOfWeek >= 6);
                $isHoliday = isset($offDaysMap[$dateStr]);

                $dayCapacity = 0;
                $dayPresent  = 0;

                foreach ($subordinateIds as $empId) {
                    $hasPenugasan      = isset($penugasanMap[$dateStr][$empId]);
                    $scheduledDays     = $employeeWorkDays[$empId] ?? [1, 2, 3, 4, 5];
                    $isScheduledToWork = in_array($dayOfWeek, $scheduledDays);

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

            } elseif ($mode === 'week' && $wStart) {
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

            } else {
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

        return [
            'can_view_team_card' => $canViewTeamCard,
            'subordinate_ids'    => $subordinateIds,
            'team_total'         => $teamTotal,
            'team_present'       => $teamPresent,
            'team_on_leave'      => $teamOnLeave,
            'team_rate'          => $teamRate,
            'team_trend'         => $teamTrend,
        ];
    }

    /**
     * Calculates leave remaining balance and carried over amounts.
     */
    private function getLeaveBalanceData(int $employeeId, string $today, string $currentYear): array
    {
        $mLeaveBalance  = new M_LeaveBalance($this->request);
        $leaveBalance   = $mLeaveBalance->getTotalBalance($employeeId, $currentYear);
        $carryOverValid = (!empty($leaveBalance) && !empty($leaveBalance->carry_over_expiry_date) && $today <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

        $balance        = (float) ($leaveBalance->balance_amount ?? 0);
        $carriedOver    = (float) ($leaveBalance->carried_over_amount ?? 0);
        $reserved       = (float) ($leaveBalance->reserved ?? 0);

        $availableLeave = $carryOverValid ? ($carriedOver + $balance) : $balance;
        $availableLeave = max(0, $availableLeave - $reserved);

        return [
            'balance'         => $balance,
            'available_leave' => $availableLeave,
        ];
    }

    /**
     * Aggregates pending submissions and formats summary breakdown.
     */
    private function getPendingApprovalsData(int $employeeId, string $rangeEnd): array
    {
        $pendingGroups = $this->db->table('v_all_submission')
            ->select('doctype, COUNT(DISTINCT documentno) as total')
            ->where('md_employee_id', $employeeId)
            ->where('date <=', $rangeEnd . ' 23:59:59')
            ->whereIn('docstatus', $this->pendingDocStatuses)
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

        return [
            'pending_approvals' => $pendingApprovals,
            'pending_summary'   => $pendingSummary,
            'pending_full'      => $pendingSummary,
            'pending_breakdown' => $pendingBreakdown,
        ];
    }

    /**
     * Fetches approved subordinate submissions and upcoming birthdays for calendar feed.
     */
    private function getSubordinateCalendarEvents(array $subordinateIds, string $currentYear): array
    {
        $calendarEvents = [];
        if (empty($subordinateIds)) {
            return $calendarEvents;
        }

        $roleId      = (int) (session()->get('sys_role_id') ?? 0);
        $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

        // Executives/GM, Super Admins, HR, and Managers (100001, 100002)
        $canViewSubmissions = in_array($roleId, $this->superRoleIds, true) 
            || in_array($levellingId, [100001, 100002], true);

        if ($canViewSubmissions) {
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

        // Subordinate birthdays
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

        return $calendarEvents;
    }

    /**
     * Fetches unread announcements for the employee.
     */
    private function getUnreadAnnouncements(int $employeeId, int $limit = 25): array
    {
        return $this->db->table('trx_message')
            ->select('trx_message.trx_message_id, trx_message.subject, trx_message.body, trx_message.messagedate, trx_message.isread, sys_user.name as author')
            ->join('sys_user', 'sys_user.sys_user_id = trx_message.author_id', 'left')
            ->where('trx_message.recipient_id', $employeeId)
            ->where('trx_message.isread', 'N')
            ->orderBy('trx_message.messagedate', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Fetches employee greeting, tenure, birthday, and avatar info.
     */
    private function getEmployeeGreetingData(int $employeeId): array
    {
        $positionTable = 'md_position';
        $positionLabel = 'name';
        $photoDir      = 'uploads/employee/';
        $useSalutation = true;

        $validDate = static function (?string $d): ?\DateTime {
            if (!$d || strpos($d, '0000') === 0) {
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

        return [
            'greeting_row'      => $greetingRow,
            'employee_photo'    => $employeePhoto,
            'employee_nickname' => $employeeNickname,
            'employee_position' => $employeePosition,
            'salutation'        => $salutation,
            'is_birthday'       => $isBirthday,
            'work_anniversary'  => $workAnniversary,
            'tenure_text'       => $tenureText,
            'supervisor_name'   => $supervisorName,
        ];
    }

    /**
     * Resolves scope levels (me, team, all) and branch/division access hierarchy.
     */
    private function resolveDashboardScope(int $employeeId, array $subordinateIds): array
    {
        $roleId      = (int) (session()->get('sys_role_id') ?? 0);
        $roleName    = strtoupper((string) (session()->get('role_name') ?? session()->get('name') ?? ''));
        $levellingId = (int) (session()->get('md_levelling_id') ?? 0);

        $levels = ['me' => 'Saya'];
        $isManagerialRole = (bool) preg_match('/(MGR|SPV|GM|KACAB|DIREKTUR|OM)/i', $roleName);

        if (!empty($subordinateIds) || in_array($levellingId, $this->managerialLevellings, true) || $isManagerialRole) {
            $levels['team'] = 'Tim Saya';
        }

        if (in_array($roleId, $this->superRoleIds, true) || $levellingId === 100001 || strpos($roleName, 'HRD') !== false) {
            $levels['all'] = 'Semua';
        }

        $currentLevel = $this->request->getGet('level') ?? 'me';
        if (!array_key_exists($currentLevel, $levels)) {
            $currentLevel = array_key_first($levels);
        }

        $access    = $this->resolveEmployeeAccess((string) $employeeId);
        $branches  = $access['branches'];
        $divisions = $access['divisions'];

        return [
            'levels'        => $levels,
            'current_level' => $currentLevel,
            'branches'      => $branches,
            'divisions'     => $divisions,
        ];
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
            $hasSuperAccess = in_array($roleId, $this->superRoleIds, true) || $levellingId === 100001 || $isHrd;

            $myAccess    = $this->resolveEmployeeAccess((string) $employeeId);
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

            $subCats = array_intersect($categories, ['leave', 'team']);
            $canViewSubmissions = in_array($roleId, $this->superRoleIds, true) 
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

    /**
     * Dashboard Endpoint: Fetches attendance card data for today or a target date directly in-memory.
     * Route: GET /sas/get-today-card?date=2026-10-07&debug=1
     */
    public function getTodayCard()
    {
        $sessionNik = (string) ($this->session->get('nik') ?? '');
        $targetNik  = (string) ($this->request->getGet('nik') ?: $sessionNik);

        if (empty($targetNik)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Session expired or missing NIK.'
            ]);
        }

        // IDOR Check: Non-privileged users can only view their own attendance card
        if ($sessionNik !== '' && $targetNik !== $sessionNik) {
            $roleId            = (int) (session()->get('sys_role_id') ?? 0);
            $levellingId       = (int) (session()->get('md_levelling_id') ?? 0);
            $allowedLevellings = [100001, 100002, 100003, 100004];

            $canInspect = in_array($roleId, $this->superRoleIds, true) || in_array($levellingId, $allowedLevellings, true);

            if (!$canInspect) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized to view other employee attendance.'
                ]);
            }
        }

        $nik      = $targetNik;
        $workDate = $this->request->getGet('date') ?? date('Y-m-d');
        $isDebug  = ($this->request->getGet('debug') === '1');

        // 1. Process attendance engine calculation in-memory
        $calc = $this->processEmployeeDay($nik, $workDate);

        // 2. Fetch supplementary branch & employee information
        $empInfo = $this->db->table('md_employee me')
            ->select('me.value AS employee_name, mb.name AS branch_name')
            ->join('md_employee_branch meb', 'me.md_employee_id = meb.md_employee_id AND meb.isactive = "Y"', 'left')
            ->join('md_branch mb', 'meb.md_branch_id = mb.md_branch_id', 'left')
            ->where('me.nik', $nik)
            ->get()->getRowArray();

        $schedule   = $this->getEmployeeSchedule($calc['md_employee_id'], $workDate);
        $startWork  = $schedule['startwork'] ?? null;
        $endWork    = $schedule['endwork'] ?? null;
        $schedRange = ($startWork && $endWork) ? "{$startWork} - {$endWork}" : 'Libur / Tidak Ada Jadwal';

        $attendance = array_merge($calc, [
            'employee_name'  => $empInfo['employee_name'] ?? '-',
            'branch_name'    => $empInfo['branch_name'] ?? '-',
            'startwork'      => $startWork,
            'endwork'        => $endWork,
            'schedule_range' => $schedRange,
        ]);

        $response = [
            'status' => 'success',
            'data'   => $attendance
        ];

        if ($isDebug) {
            $response['debug'] = [
                'request_params'    => ['nik' => $nik, 'work_date' => $workDate],
                'calculation_trace' => $this->debugLog,
                'result'            => $attendance
            ];
        }

        return $this->response->setJSON($response);
    }

    /**
     * Batch process employee attendance for a date range in-memory.
     * OPTIMIZATION: Replaces the 180+ query N+1 loop with 5 batch pre-fetches.
     */
    private function processEmployeeRange(string $nik, string $startDate, string $endDate): array
    {
        $this->rangeQueryStats = [];

        // 1. Resolve Employee ID once
        $empId = $this->getEmployeeId($nik);
        if ($empId === 0) {
            return [];
        }

        // 2. Batch Pre-fetch Holidays overlapping the period
        $holidayRows = $this->db->table('md_holiday')
            ->select("DATE(startdate) AS start_date, DATE(CASE WHEN enddate IS NULL OR CAST(enddate AS CHAR) LIKE '0000%' THEN startdate ELSE enddate END) AS end_date")
            ->where('isactive', 'Y')
            ->where('startdate <=', $endDate . ' 23:59:59')
            ->groupStart()
                ->where('enddate >=', $startDate . ' 00:00:00')
                ->orWhere('enddate IS NULL')
                ->orWhere("CAST(enddate AS CHAR) = '0000-00-00'")
                ->orWhere("CAST(enddate AS CHAR) = ''")
            ->groupEnd()
            ->get()->getResultArray();

        $holidaySet = [];
        foreach ($holidayRows as $h) {
            $curr = new \DateTime($h['start_date']);
            $end  = new \DateTime($h['end_date']);
            while ($curr <= $end) {
                $holidaySet[$curr->format('Y-m-d')] = true;
                $curr->modify('+1 day');
            }
        }

        // 3. Batch Pre-fetch Mass Leaves overlapping the period
        $massLeaveRows = $this->db->table('md_massleave')
            ->select("DATE(startdate) AS start_date, DATE(CASE WHEN enddate IS NULL OR CAST(enddate AS CHAR) LIKE '0000%' THEN startdate ELSE enddate END) AS end_date")
            ->where('isactive', 'Y')
            ->where('startdate <=', $endDate . ' 23:59:59')
            ->groupStart()
                ->where('enddate >=', $startDate . ' 00:00:00')
                ->orWhere('enddate IS NULL')
                ->orWhere("CAST(enddate AS CHAR) = '0000-00-00'")
                ->orWhere("CAST(enddate AS CHAR) = ''")
            ->groupEnd()
            ->get()->getResultArray();

        $massLeaveSet = [];
        foreach ($massLeaveRows as $m) {
            $curr = new \DateTime($m['start_date']);
            $end  = new \DateTime($m['end_date']);
            while ($curr <= $end) {
                $massLeaveSet[$curr->format('Y-m-d')] = true;
                $curr->modify('+1 day');
            }
        }

        // 4. Batch Pre-fetch Employee Schedules (7 days max)
        $scheduleRows = $this->db->table('md_employee_work mew')
            ->select("
                mwd.md_day_id,
                DATE_FORMAT(mwd.startwork, '%H:%i:%s') AS startwork,
                DATE_FORMAT(mwd.endwork, '%H:%i:%s') AS endwork,
                mew.validfrom,
                mew.validto
            ")
            ->join('md_work mw', 'mw.md_work_id = mew.md_work_id')
            ->join('md_work_detail mwd', 'mw.md_work_id = mwd.md_work_id')
            ->where('mew.md_employee_id', $empId)
            ->where('(mew.isactive = "Y" OR mew.isactive IS NULL)')
            ->where('mew.validfrom <=', $endDate . ' 23:59:59')
            ->groupStart()
                ->where('mew.validto IS NULL')
                ->orWhere("CAST(mew.validto AS CHAR) LIKE '0000%'")
                ->orWhere('mew.validto >=', $startDate . ' 00:00:00')
            ->groupEnd()
            ->get()->getResultArray();

        // 5. Batch Pre-fetch Approved Submissions
        $subRows = $this->db->table('v_all_submission')
            ->select('DATE(date) AS sub_date, submissiontype')
            ->where('md_employee_id', $empId)
            ->where('date >=', $startDate . ' 00:00:00')
            ->where('date <=', $endDate . ' 23:59:59')
            ->whereIn('docstatus', ['CO', 'AP'])
            ->get()->getResultArray();

        $submissionsByDate = [];
        foreach ($subRows as $sub) {
            if (!isset($submissionsByDate[$sub['sub_date']])) {
                $submissionsByDate[$sub['sub_date']] = $sub['submissiontype'];
            }
        }

        // 6. Batch Pre-fetch All Raw Biometric Taps for Range
        $startBound = $startDate . ' 05:00:00';
        $endBound   = date('Y-m-d', strtotime($endDate . ' +1 day')) . ' 04:59:59';

        $allTaps = $this->db->table('trx_attendance')
            ->select("checktime, updated_by, DATE_FORMAT(checktime, '%H:%i:%s') AS time_val")
            ->where('nik', $nik)
            ->where('checktime >=', $startBound)
            ->where('checktime <=', $endBound)
            ->orderBy('checktime', 'ASC')
            ->get()->getResultArray();

        // Bucket taps by logical workDate (taps before 05:00 belong to previous day)
        $rawTapsByDate = [];
        foreach ($allTaps as $tap) {
            $timePart = substr($tap['checktime'], 11, 8);
            $datePart = substr($tap['checktime'], 0, 10);
            $workDate = ($timePart < '05:00:00') ? date('Y-m-d', strtotime($datePart . ' -1 day')) : $datePart;
            $rawTapsByDate[$workDate][] = $tap;
        }

        // Deduplicate taps per work date within 60s
        $cleanedTapsByDate = [];
        foreach ($rawTapsByDate as $wDate => $taps) {
            $cleaned = [];
            $lastStamp = 0;
            foreach ($taps as $tap) {
                $currentStamp = strtotime($tap['checktime']);
                if ($lastStamp === 0 || ($currentStamp - $lastStamp) >= $this->tapDeduplicationSec) {
                    $cleaned[] = $tap;
                    $lastStamp = $currentStamp;
                }
            }
            $cleanedTapsByDate[$wDate] = $cleaned;
        }

        // 7. In-Memory Evaluation Loop (0 DB Queries inside loop)
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        $results = [];
        foreach ($period as $dt) {
            $workDate = $dt->format('Y-m-d');
            $dayNo    = (int) $dt->format('N');

            // Find matching schedule for this specific date
            $matchedSched = null;
            $wStartDt = $workDate . ' 00:00:00';
            $wEndDt   = $workDate . ' 23:59:59';
            foreach ($scheduleRows as $sRow) {
                if ((int)$sRow['md_day_id'] === $dayNo) {
                    $vFrom = $sRow['validfrom'];
                    $vTo   = $sRow['validto'];
                    if ($vFrom <= $wEndDt && ($vTo === null || strpos((string)$vTo, '0000') === 0 || $vTo >= $wStartDt)) {
                        $matchedSched = [
                            'startwork' => $sRow['startwork'],
                            'endwork'   => $sRow['endwork'],
                        ];
                        break;
                    }
                }
            }

            $dayTaps     = $cleanedTapsByDate[$workDate] ?? [];
            $isHoliday   = isset($holidaySet[$workDate]);
            $isMassLeave = isset($massLeaveSet[$workDate]);
            $subType     = $submissionsByDate[$workDate] ?? null;

            $tStart = microtime(true);

            $results[$workDate] = $this->evaluateDayLogic(
                $empId,
                $nik,
                $workDate,
                $dayTaps,
                $matchedSched,
                $isHoliday,
                $isMassLeave,
                $subType
            );

            // Record micro-timing for profiler (queries = 0 inside loop!)
            $this->rangeQueryStats[] = [
                'date'    => $workDate,
                'queries' => 0,
                'time_ms' => round((microtime(true) - $tStart) * 1000, 3),
            ];
        }

        return $results;
    }

    /**
     * Core Engine Processor (In-Memory Evaluation for Single Day)
     */
    private function processEmployeeDay(string $nik, string $workDate): array
    {
        $empId       = $this->getEmployeeId($nik);
        $rawTaps     = $this->getCleanedRawTaps($nik, $workDate);
        $schedule    = $this->getEmployeeSchedule($empId, $workDate);
        $isHoliday   = $this->isNationalHoliday($workDate);
        $isMassLeave = $this->isMassLeave($workDate);
        $subType     = $this->getApprovedSubmission($nik, $workDate);

        return $this->evaluateDayLogic(
            $empId,
            $nik,
            $workDate,
            $rawTaps,
            $schedule,
            $isHoliday,
            $isMassLeave,
            $subType
        );
    }

    /**
     * Pure In-Memory Attendance Rules Engine
     */
    private function evaluateDayLogic(
        int $empId,
        string $nik,
        string $workDate,
        array $rawTaps,
        ?array $schedule,
        bool $isHoliday,
        bool $isMassLeave,
        ?string $subType
    ): array {
        $totalPunches = count($rawTaps);
        $hasManual    = $this->hasManualOverride($rawTaps);
        $isDebug      = ($this->request->getGet('debug') === '1');
        $isToday      = ($workDate === date('Y-m-d'));
        $currentTime  = date('H:i:s');

        // -----------------------------------------------------------------
        // RULE 1: Manual HRD Override Bypass
        // -----------------------------------------------------------------
        if ($hasManual) {
            $in  = date('H:i:s', strtotime($rawTaps[0]['checktime']));
            $out = $totalPunches > 1 ? date('H:i:s', strtotime(end($rawTaps)['checktime'])) : null;

            if ($isDebug) {
                $this->debugLog[] = [
                    'nik' => $nik, 'work_date' => $workDate, 'final_status' => 'MANUAL_HRD_OVERRIDE'
                ];
            }

            return $this->formatOutput($empId, $nik, $workDate, $in, $out, $totalPunches, 'MANUAL_HRD_OVERRIDE', 1.0, 1);
        }

        // -----------------------------------------------------------------
        // RULE 2: Non-Working Days / Public Holidays / Mass Leaves
        // -----------------------------------------------------------------
        $isOffDay = (!$schedule || $isHoliday || $isMassLeave);
        if ($isOffDay) {
            if ($totalPunches > 0) {
                $in     = $rawTaps[0]['time_val'];
                $out    = $totalPunches > 1 ? end($rawTaps)['time_val'] : null;
                $status = $isHoliday ? 'HOLIDAY_WORKED' : ($isMassLeave ? 'MASS_LEAVE_WORKED' : 'OVERTIME_OFF_DAY');
                return $this->formatOutput($empId, $nik, $workDate, $in, $out, $totalPunches, $status, 1.0, 0);
            } else {
                $status = $isHoliday ? 'PUBLIC_HOLIDAY' : ($isMassLeave ? 'MASS_LEAVE' : 'OFF_DAY');
                return $this->formatOutput($empId, $nik, $workDate, null, null, 0, $status, 1.0, 0);
            }
        }

        // -----------------------------------------------------------------
        // RULE 3: Regular Workday Calculations
        // -----------------------------------------------------------------
        $startWork     = $schedule['startwork'];
        $endWork       = $schedule['endwork'];
        $cutoff        = date('H:i:s', strtotime($startWork . ' +4 hours'));
        $lateThreshold = date('H:i:s', strtotime($startWork . " +{$this->lateToleranceMinutes} minutes"));

        // Split Taps by Midday Cutoff
        $morning   = [];
        $afternoon = [];
        foreach ($rawTaps as $tap) {
            if ($tap['time_val'] < $cutoff && $tap['time_val'] >= '05:00:00') {
                $morning[] = $tap;
            } else {
                $afternoon[] = $tap;
            }
        }

        // Resolve Clock-In and Clock-Out
        $clockIn  = !empty($morning) ? $morning[0]['time_val'] : null;
        $clockOut = !empty($afternoon) ? end($afternoon)['time_val'] : null;

        // Disambiguate single taps
        if ($totalPunches === 1) {
            $singleTap = $rawTaps[0]['time_val'];
            if ($singleTap < $cutoff) {
                $clockIn  = ($subType === 'TUGAS_HALF_AFTERNOON') ? null : $singleTap;
                $clockOut = ($subType === 'TUGAS_HALF_AFTERNOON') ? $singleTap : null;
            } else {
                $clockIn  = ($subType === 'TUGAS_HALF_MORNING') ? $singleTap : null;
                $clockOut = ($subType === 'TUGAS_HALF_MORNING') ? null : $singleTap;
            }
        }

        // -----------------------------------------------------------------
        // RULE 4: Status & Allowance Evaluation Engine
        // -----------------------------------------------------------------
        $status = 'NORMAL';
        $tkh    = 1.0;

        // --- A. No Taps At All ---
        if (!$clockIn && !$clockOut) {
            if ($subType) {
                $status = 'LEAVE_APPROVED';
                $tkh    = 1.0;
            } else {
                $status = 'ABSENT';
                $tkh    = 0.0;
            }
        } 
        // --- B. Clock-In Present, Clock-Out Missing ---
        elseif ($clockIn && !$clockOut) {
            $isLate = ($clockIn > $lateThreshold);

            if ($isToday && $currentTime < $endWork) {
                $status = $isLate ? 'IN_OFFICE_LATE' : 'IN_OFFICE';
                $tkh    = $isLate ? 0.5 : 1.0;
            } elseif (in_array($subType, ['100011', 'LUPA_ABSEN_PULANG'], true)) {
                $status = $isLate ? 'LATE_EXCUSED' : 'NORMAL';
                $tkh    = $isLate ? 0.5 : 1.0;
            } elseif ($subType) {
                $status = $isLate ? 'LATE_WITH_APPROVED_LEAVE' : 'HALF_DAY_APPROVED';
                $tkh    = $isLate ? 0.5 : 1.0;
            } else {
                $status = $isLate ? 'LATE_AND_MISSING_OUT' : 'MISSING_CHECK_OUT';
                $tkh    = 0.0;
            }
        } 
        // --- C. Clock-In Missing, Clock-Out Present ---
        elseif (!$clockIn && $clockOut) {
            if (in_array($subType, ['100010', 'LUPA_ABSEN_MASUK'], true)) {
                $status = 'NORMAL';
                $tkh    = 1.0;
            } elseif ($subType) {
                $status = 'HALF_DAY_APPROVED';
                $tkh    = 1.0;
            } else {
                $status = 'MISSING_CHECK_IN';
                $tkh    = 0.0;
            }
        } 
        // --- D. Both Clock-In and Clock-Out Present ---
        else {
            $isLate     = ($clockIn > $lateThreshold);
            $isEarlyOut = ($clockOut < $endWork);

            if ($isLate && $isEarlyOut) {
                $status = $subType ? 'APPROVED_SHORT_HOURS' : 'LATE_AND_EARLY_LEAVE';
                $tkh    = $subType ? 1.0 : 0.0;
            } elseif ($isLate) {
                $status = (in_array($subType, ['100012', 'DATANG_TERLAMBAT'], true) || !empty($subType)) ? 'LATE_EXCUSED' : 'LATE_ARRIVAL';
                $tkh    = (in_array($subType, ['100012', 'DATANG_TERLAMBAT'], true) || !empty($subType)) ? 1.0 : 0.5;
            } elseif ($isEarlyOut) {
                $status = (in_array($subType, ['100013', 'PULANG_CEPAT', 'TUGAS_HALF_AFTERNOON'], true) || !empty($subType)) ? 'EARLY_LEAVE_EXCUSED' : 'UNEXCUSED_EARLY_DEPARTURE';
                $tkh    = (in_array($subType, ['100013', 'PULANG_CEPAT', 'TUGAS_HALF_AFTERNOON'], true) || !empty($subType)) ? 1.0 : 0.5;
            }
        }

        if ($isDebug) {
            $this->debugLog[] = [
                'nik'           => $nik,
                'work_date'     => $workDate,
                'shift'         => "{$startWork} - {$endWork}",
                'late_limit'    => $lateThreshold,
                'resolved_in'   => $clockIn,
                'resolved_out'  => $clockOut,
                'submission'    => $subType,
                'final_status'  => $status,
                'tkh_allowance' => $tkh
            ];
        }

        return $this->formatOutput($empId, $nik, $workDate, $clockIn, $clockOut, $totalPunches, $status, $tkh, 0);
    }

    private function getCleanedRawTaps(string $nik, string $workDate): array
    {
        $startBound = $workDate . ' 05:00:00';
        $nextDay    = date('Y-m-d', strtotime($workDate . ' +1 day'));
        $endBound   = $nextDay . ' 04:59:59';

        $sql = "
            SELECT checktime, updated_by, DATE_FORMAT(checktime, '%H:%i:%s') AS time_val
            FROM trx_attendance
            WHERE nik = :nik:
              AND checktime >= :startBound:
              AND checktime <= :endBound:
            ORDER BY checktime ASC
        ";

        $raw = $this->db->query($sql, [
            'nik'        => $nik,
            'startBound' => $startBound,
            'endBound'   => $endBound
        ])->getResultArray();

        $cleaned   = [];
        $lastStamp = 0;

        foreach ($raw as $tap) {
            $currentStamp = strtotime($tap['checktime']);
            if ($lastStamp === 0 || ($currentStamp - $lastStamp) >= $this->tapDeduplicationSec) {
                $cleaned[] = $tap;
                $lastStamp = $currentStamp;
            }
        }

        return $cleaned;
    }

    private function isNationalHoliday(string $workDate): bool
    {
        $sql = "
            SELECT COUNT(*) as cnt 
            FROM md_holiday 
            WHERE isactive = 'Y'
              AND (
                  ( (enddate IS NULL OR CAST(enddate AS CHAR) LIKE '0000%') AND startdate >= :wStart: AND startdate <= :wEnd: )
                  OR 
                  ( enddate IS NOT NULL AND CAST(enddate AS CHAR) NOT LIKE '0000%' AND startdate <= :wEnd: AND enddate >= :wStart: )
              )
        ";
        $row = $this->db->query($sql, [
            'wStart' => $workDate . ' 00:00:00',
            'wEnd'   => $workDate . ' 23:59:59'
        ])->getRowArray();

        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    private function isMassLeave(string $workDate): bool
    {
        $sql = "
            SELECT COUNT(*) as cnt 
            FROM md_massleave 
            WHERE isactive = 'Y'
              AND (
                  ( (enddate IS NULL OR CAST(enddate AS CHAR) LIKE '0000%') AND startdate >= :wStart: AND startdate <= :wEnd: )
                  OR 
                  ( enddate IS NOT NULL AND CAST(enddate AS CHAR) NOT LIKE '0000%' AND startdate <= :wEnd: AND enddate >= :wStart: )
              )
        ";
        $row = $this->db->query($sql, [
            'wStart' => $workDate . ' 00:00:00',
            'wEnd'   => $workDate . ' 23:59:59'
        ])->getRowArray();

        return ((int)($row['cnt'] ?? 0)) > 0;
    }

    private function getApprovedSubmission(string $nik, string $workDate): ?string
    {
        $sql = "
            SELECT vas.submissiontype
            FROM v_all_submission vas
            JOIN md_employee me ON me.md_employee_id = vas.md_employee_id
            WHERE me.nik = :nik:
              AND vas.date >= :wStart:
              AND vas.date <= :wEnd:
              AND vas.docstatus IN ('CO', 'AP')
            LIMIT 1
        ";
        $row = $this->db->query($sql, [
            'nik'    => $nik,
            'wStart' => $workDate . ' 00:00:00',
            'wEnd'   => $workDate . ' 23:59:59'
        ])->getRowArray();

        return $row['submissiontype'] ?? null;
    }

    private function getEmployeeSchedule(int $empId, string $workDate): ?array
    {
        $dayNo = (int) date('N', strtotime($workDate));

        $sql = "
            SELECT 
                DATE_FORMAT(mwd.startwork, '%H:%i:%s') AS startwork, 
                DATE_FORMAT(mwd.endwork, '%H:%i:%s') AS endwork
            FROM md_employee_work mew
            JOIN md_work_detail mwd ON mew.md_work_id = mwd.md_work_id
            WHERE mew.md_employee_id = :empId:
              AND mew.validfrom <= :wEnd: 
              AND (mew.validto IS NULL OR CAST(mew.validto AS CHAR) LIKE '0000%' OR mew.validto >= :wStart:)
              AND (mew.isactive = 'Y' OR mew.isactive IS NULL)
              AND mwd.md_day_id = :dayNo:
            LIMIT 1
        ";

        return $this->db->query($sql, [
            'empId'  => $empId,
            'wStart' => $workDate . ' 00:00:00',
            'wEnd'   => $workDate . ' 23:59:59',
            'dayNo'  => $dayNo
        ])->getRowArray();
    }

    private function getEmployeeId(string $nik): int
    {
        $sql = "SELECT md_employee_id FROM md_employee WHERE nik = :nik: LIMIT 1";
        $row = $this->db->query($sql, ['nik' => $nik])->getRowArray();
        return (int)($row['md_employee_id'] ?? 0);
    }

    private function hasManualOverride(array $rawTaps): bool
    {
        foreach ($rawTaps as $tap) {
            if (
                $tap['updated_by'] !== null && 
                !in_array((string)$tap['updated_by'], ['100000', '0', 'SYSTEM'], true)
            ) {
                return true;
            }
        }
        return false;
    }

    private function formatOutput(
        int $empId,
        string $nik,
        string $wDate,
        ?string $in,
        ?string $out,
        int $punches,
        string $status,
        float $tkh,
        int $manual
    ): array {
        return [
            'md_employee_id'     => $empId,
            'nik'                => $nik,
            'work_date'          => $wDate,
            'clock_in'           => $in,
            'clock_out'          => $out,
            'total_punches'      => $punches,
            'attendance_status'  => $status,
            'tkh_allowance'      => $tkh,
            'is_manual_override' => $manual
        ];
    }

    /**
     * Recursive employee branch & division access resolver
     */
    private function resolveEmployeeAccess(string $empId, array &$visited = []): array
    {
        if (empty($empId) || isset($visited[$empId])) {
            return ['branches' => [], 'divisions' => []];
        }
        $visited[$empId] = true;

        $branches  = $this->fetchAccessList('md_branch', 'sys_user_branchaccess', 'md_branch_id', $empId);
        $divisions = $this->fetchAccessList('md_division', 'sys_user_divaccess', 'md_division_id', $empId);

        if (!empty($branches) && !empty($divisions)) {
            return ['branches' => $branches, 'divisions' => $divisions];
        }

        $superiorRow = $this->db->table('md_employee')
            ->select('superior_id')
            ->where('md_employee_id', $empId)
            ->get()->getRow();

        $superiorId = $superiorRow->superior_id ?? null;

        if (!empty($superiorId) && $superiorId != $empId) {
            $parentAccess = $this->resolveEmployeeAccess((string) $superiorId, $visited);

            if (empty($branches))  $branches  = $parentAccess['branches'];
            if (empty($divisions)) $divisions = $parentAccess['divisions'];
        }

        return ['branches' => $branches, 'divisions' => $divisions];
    }

    private function fetchAccessList(string $table, string $accessTable, string $pkCol, string $targetEmpId): array
    {
        return $this->db->table("{$table} m")
            ->select("m.{$pkCol} as id, m.name")
            ->join("{$accessTable} acc", "acc.{$pkCol} = m.{$pkCol}", 'inner')
            ->join('sys_user su', 'su.sys_user_id = acc.sys_user_id', 'inner')
            ->join('md_employee me', 'me.md_employee_id = su.md_employee_id', 'inner')
            ->where('me.md_employee_id', $targetEmpId)
            ->where('m.isactive', 'Y')
            ->orderBy('m.name', 'ASC')
            ->get()->getResultArray();
    }
}
