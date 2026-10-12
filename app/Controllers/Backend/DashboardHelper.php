<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;

class DashboardHelper extends BaseController
{
    private BaseConnection $db;
    private array $debugLog = [];

    // Configurable engine thresholds
    private int $lateToleranceMinutes = 5;  // 5-minute late grace period
    private int $tapDeduplicationSec  = 60; // Ignore rapid biometric taps within 60 seconds

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        // Native CI4 database connection
        $this->db = \Config\Database::connect();
    }

    /**
     * Dashboard Endpoint: Fetches attendance card data for today or a target date directly in-memory.
     * Route: GET /sas/get-today-card?date=2026-10-07&debug=1
     */
    public function getTodayCard()
    {
        $nik      = $this->session->get('nik') ?? $this->request->getGet('nik');
        $workDate = $this->request->getGet('date') ?? date('Y-m-d');
        $isDebug  = ($this->request->getGet('debug') === '1');

        if (!$nik) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Session expired or missing NIK.'
            ]);
        }

        // 1. Process attendance engine calculation in-memory
        $calc = $this->processEmployeeDay($nik, $workDate);

        // 2. Fetch supplementary branch & employee information
        $empInfo = $this->db->table('md_employee me')
            ->select('me.value AS employee_name, mb.name AS branch_name')
            ->join('md_employee_branch meb', 'me.md_employee_id = meb.md_employee_id AND meb.isactive = "Y"', 'left')
            ->join('md_branch mb', 'meb.md_branch_id = mb.md_branch_id', 'left')
            ->where('me.nik', $nik)
            ->get()->getRowArray();

        $schedule  = $this->getEmployeeSchedule($calc['md_employee_id'], $workDate);
        $startWork = $schedule['startwork'] ?? null;
        $endWork   = $schedule['endwork'] ?? null;
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
     */
    public function processEmployeeRange(string $nik, string $startDate, string $endDate): array
    {
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        $results = [];
        foreach ($period as $dt) {
            $workDate = $dt->format('Y-m-d');
            $results[$workDate] = $this->processEmployeeDay($nik, $workDate);
        }

        return $results;
    }

    /**
     * Core Engine Processor (In-Memory Evaluation)
     */
    public function processEmployeeDay(string $nik, string $workDate): array
    {
        $empId       = $this->getEmployeeId($nik);
        $rawTaps     = $this->getCleanedRawTaps($nik, $workDate);
        $schedule    = $this->getEmployeeSchedule($empId, $workDate);
        $isHoliday   = $this->isNationalHoliday($workDate);
        $isMassLeave = $this->isMassLeave($workDate);
        $subType     = $this->getApprovedSubmission($nik, $workDate);

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

    // -----------------------------------------------------------------
    // PRIVATE INTERNAL HELPERS (STRICT-MODE SAFE)
    // -----------------------------------------------------------------

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
}