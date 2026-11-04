<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * Service to dispatch Performance Appraisal nudges and notifications
 * leveraging SAS-HR's existing core broadcast queue (`trx_broadcast` & `trx_broadcast_queue`).
 */
class AppraisalNudgeService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Enqueue a message directly into SAS-HR's broadcast engine.
     *
     * @param int $employeeId Target employee ID (0 if targeting branch/division)
     * @param string $title Message subject
     * @param string $messageHtml HTML formatted message
     * @param int $createdBy Actor user ID
     * @param string $sentMethod Delivery channels: 'T,E,N' (Telegram, Email, In-app Notification)
     * @return int|false The created trx_broadcast_id or false on failure
     */
    public function enqueueBroadcast(
        int $employeeId,
        string $title,
        string $messageHtml,
        int $createdBy = 100000,
        string $sentMethod = 'T,E,N'
    ) {
        try {
            $encodedMessage = htmlentities($messageHtml, ENT_QUOTES, 'UTF-8');
            $now = date('Y-m-d H:i:s');

            $broadcastData = [
                'title'          => $title,
                'message'        => $encodedMessage,
                'md_employee_id' => $employeeId,
                'md_branch_id'   => 0,
                'md_division_id' => 0,
                'sentmethod'     => $sentMethod,
                'effective_date' => null, // Immediate send has NO effective date/time
                'isactive'       => 'Y',
                'is_sent'        => 'Y',  // Marked sent like immediate broadcast
                'created_by'     => $createdBy,
                'created_at'     => $now,
                'updated_by'     => $createdBy,
                'updated_at'     => $now,
            ];

            $this->db->table('trx_broadcast')->insert($broadcastData);
            $broadcastId = (int) $this->db->insertID();

            if ($broadcastId > 0) {
                $queueData = [
                    'trx_broadcast_id' => $broadcastId,
                    'status'           => 'PE', // Pending execution by cron
                    'starttime'        => $now, // Immediate: starttime is NOW so worker picks it up
                    'isactive'         => 'Y',
                    'created_by'       => $createdBy,
                    'created_at'       => $now,
                    'updated_by'       => $createdBy,
                    'updated_at'       => $now,
                ];
                $this->db->table('trx_broadcast_queue')->insert($queueData);
                return $broadcastId;
            }

            return false;
        } catch (Throwable $e) {
            log_message('error', '[AppraisalNudgeService::enqueueBroadcast] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify workflow transitions between stages:
     * - SUBMIT_EVALUATOR -> Alert GM for review
     * - APPROVE_GM      -> Alert Employee for reading/acknowledgment
     * - RETURN_GM       -> Alert Evaluator that form was returned
     *
     * @param int $appraisalId
     * @param string $event 'SUBMIT_EVALUATOR'|'APPROVE_GM'|'RETURN_GM'
     * @param int $actorUserId
     * @return bool
     */
    public function notifyWorkflowTransition(int $appraisalId, string $event, int $actorUserId = 100000): bool
    {
        try {
            $row = $this->db->table('md_appraisal a')
                ->select('a.*, e.fullname as employee_name, e.nik as employee_nik, c.title as cycle_title, c.deadline_filling, c.deadline_review, c.deadline_reading')
                ->join('md_employee e', 'e.md_employee_id = a.md_employee_id', 'left')
                ->join('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = a.md_appraisal_cycle_id', 'left')
                ->where('a.md_appraisal_id', $appraisalId)
                ->get()
                ->getRowArray();

            if (! $row) {
                return false;
            }

            $empName    = $row['employee_name'] ?? 'Karyawan';
            $cycleTitle = $row['cycle_title'] ?? 'Siklus Penilaian';

            switch ($event) {
                case 'SUBMIT_EVALUATOR':
                    // Target: GM / Reviewer
                    $gmId = (int) ($row['snapshot_gm_id'] ?? 0);
                    if ($gmId <= 0) {
                        return false;
                    }

                    $dlStr = ! empty($row['deadline_review']) ? date('d-M-Y', strtotime($row['deadline_review'])) : '-';
                    $title = "Menunggu Review GM: Evaluasi Kinerja {$empName}";
                    $html  = "<p>Halo,</p>"
                        . "<p>Penilaian kinerja untuk <b>{$empName}</b> pada siklus <b>{$cycleTitle}</b> telah diselesaikan oleh atasan langsung dan saat ini <b>menunggu peninjauan (review) Anda</b>.</p>"
                        . "<p><b>Batas Waktu Review:</b> {$dlStr}</p>"
                        . "<p>Silakan periksa dan berikan tanggapan melalui menu Task List Penilaian pada sistem SAS-HR.</p>"
                        . "<p><small>Pesan otomatis sistem SAS-HR.</small></p>";

                    return (bool) $this->enqueueBroadcast($gmId, $title, $html, $actorUserId);

                case 'APPROVE_GM':
                    // Target: Employee for reading & signing
                    $empId = (int) ($row['md_employee_id'] ?? 0);
                    if ($empId <= 0) {
                        return false;
                    }

                    $dlStr = ! empty($row['deadline_reading']) ? date('d-M-Y', strtotime($row['deadline_reading'])) : '-';

                    // Check if employee has an active user account
                    $hasUser = (bool) $this->db->table('sys_user')
                        ->where('md_employee_id', $empId)
                        ->where('isactive', 'Y')
                        ->countAllResults();

                    if (! $hasUser) {
                        // Employee has no user account: route notification to assigned Duta
                        $dutaRow = $this->db->table('sys_emp_delegation ed')
                            ->select('u.md_employee_id as duta_emp_id, u.name as duta_name')
                            ->join('sys_user u', 'u.sys_user_id = ed.sys_user_id', 'left')
                            ->where('ed.md_employee_id', $empId)
                            ->where('ed.isactive', 'Y')
                            ->where('u.isactive', 'Y')
                            ->get()
                            ->getRowArray();

                        $dutaEmpId = (int) ($dutaRow['duta_emp_id'] ?? 0);
                        if ($dutaEmpId > 0) {
                            $dutaName = $dutaRow['duta_name'] ?? 'Duta';
                            $title    = "Menunggu Konfirmasi Duta: Evaluasi Kinerja {$empName}";
                            $html     = "<p>Halo <b>{$dutaName}</b>,</p>"
                                . "<p>Hasil evaluasi kinerja untuk karyawan perwakilan Anda, <b>{$empName}</b> (Karyawan tanpa akun sistem), pada siklus <b>{$cycleTitle}</b> telah selesai direview oleh Manajemen dan saat ini <b>menunggu konfirmasi & tanda tangan Anda selaku Duta</b>.</p>"
                                . "<p><b>Batas Waktu Konfirmasi:</b> {$dlStr}</p>"
                                . "<p>Silakan tinjau hasil penilaian dan bubuhkan tanda tangan digital melalui tab <i>Penilaian Saya & Delegasi Duta</i> pada sistem SAS-HR.</p>"
                                . "<p><small>Pesan otomatis sistem SAS-HR.</small></p>";

                            return (bool) $this->enqueueBroadcast($dutaEmpId, $title, $html, $actorUserId);
                        }
                    }

                    $title = "Evaluasi Kinerja Selesai Direview: {$cycleTitle}";
                    $html  = "<p>Halo <b>{$empName}</b>,</p>"
                        . "<p>Hasil evaluasi kinerja Anda untuk siklus <b>{$cycleTitle}</b> telah selesai direview oleh Manajemen dan saat ini siap untuk Anda baca serta bubuhkan tanda tangan.</p>"
                        . "<p><b>Batas Waktu Konfirmasi:</b> {$dlStr}</p>"
                        . "<p>Silakan baca hasil penilaian dan berikan konfirmasi Anda melalui menu Penilaian Kinerja pada sistem SAS-HR.</p>"
                        . "<p><small>Pesan otomatis sistem SAS-HR.</small></p>";

                    return (bool) $this->enqueueBroadcast($empId, $title, $html, $actorUserId);

                case 'RETURN_GM':
                    // Target: Evaluator (Returned for revision)
                    $evalId = (int) ($row['snapshot_evaluator_id'] ?? 0);
                    if ($evalId <= 0) {
                        return false;
                    }

                    $title = "Evaluasi Dikembalikan untuk Revisi: {$empName}";
                    $html  = "<p>Halo,</p>"
                        . "<p>Form evaluasi kinerja karyawan <b>{$empName}</b> pada siklus <b>{$cycleTitle}</b> telah <b>dikembalikan oleh General Manager</b> untuk direvisi/dilengkapi.</p>"
                        . "<p>Silakan buka form dan periksa catatan revisi melalui menu Task List Penilaian pada sistem SAS-HR.</p>"
                        . "<p><small>Pesan otomatis sistem SAS-HR.</small></p>";

                    return (bool) $this->enqueueBroadcast($evalId, $title, $html, $actorUserId);

                default:
                    return false;
            }
        } catch (Throwable $e) {
            log_message('error', '[AppraisalNudgeService::notifyWorkflowTransition] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify cycle kickoff to all evaluators with pending forms.
     *
     * @param int $cycleId
     * @param int $actorUserId
     * @return int Number of notifications enqueued
     */
    public function notifyCycleKickoff(int $cycleId, int $actorUserId = 100000): int
    {
        try {
            $cycle = $this->db->table('md_appraisal_cycle')
                ->where('md_appraisal_cycle_id', $cycleId)
                ->get()
                ->getRowArray();

            if (! $cycle) {
                return 0;
            }

            // Find all unique evaluators in this cycle
            $evaluators = $this->db->table('md_appraisal')
                ->select('snapshot_evaluator_id, COUNT(*) as form_count')
                ->where('md_appraisal_cycle_id', $cycleId)
                ->where('isactive', 'Y')
                ->where('snapshot_evaluator_id >', 0)
                ->groupBy('snapshot_evaluator_id')
                ->get()
                ->getResultArray();

            $dlStr    = ! empty($cycle['deadline_filling']) ? date('d-M-Y', strtotime($cycle['deadline_filling'])) : '-';
            $title    = "Siklus Penilaian Dibuka: {$cycle['title']}";
            $sentCount = 0;

            foreach ($evaluators as $ev) {
                $evaluatorId = (int) $ev['snapshot_evaluator_id'];
                $count       = (int) $ev['form_count'];

                $html = "<p>Halo,</p>"
                    . "<p>Siklus penilaian kinerja <b>{$cycle['title']}</b> telah resmi <b>DIBUKA</b>.</p>"
                    . "<p>Anda ditugaskan untuk melakukan penilaian terhadap <b>{$count} anggota tim / bawahan</b>.</p>"
                    . "<p><b>Batas Waktu Pengisian:</b> {$dlStr}</p>"
                    . "<p>Mohon segera melengkapi penilaian melalui menu Task List Penilaian pada sistem SAS-HR.</p>"
                    . "<p><small>Pesan otomatis sistem SAS-HR.</small></p>";

                if ($this->enqueueBroadcast($evaluatorId, $title, $html, $actorUserId)) {
                    $sentCount++;
                }
            }

            return $sentCount;
        } catch (Throwable $e) {
            log_message('error', '[AppraisalNudgeService::notifyCycleKickoff] ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Manual Nudge for a specific appraisal initiated by HR from the dashboard.
     *
     * @param int $appraisalId
     * @param int $actorUserId
     * @return array [success => bool, message => string]
     */
    public function manualNudge(int $appraisalId, int $actorUserId = 100000): array
    {
        try {
            $row = $this->db->table('md_appraisal a')
                ->select('a.*, e.fullname as employee_name, e.nik as employee_nik, c.title as cycle_title, c.deadline_filling, c.deadline_review, c.deadline_reading')
                ->join('md_employee e', 'e.md_employee_id = a.md_employee_id', 'left')
                ->join('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = a.md_appraisal_cycle_id', 'left')
                ->where('a.md_appraisal_id', $appraisalId)
                ->get()
                ->getRowArray();

            if (! $row) {
                return ['success' => false, 'message' => 'Data penilaian tidak ditemukan.'];
            }

            $status   = $row['status'];
            $empName  = $row['employee_name'] ?? 'Karyawan';
            $cycle    = $row['cycle_title'] ?? 'Siklus Penilaian';

            $targetId = 0;
            $roleLabel = '';
            $dlStr     = '-';

            if ($status === 'DRAFT_EVALUATOR' || $status === 'SUBMITTED_EVALUATOR') {
                $targetId  = (int) ($row['snapshot_evaluator_id'] ?? 0);
                $roleLabel = 'Atasan Langsung (Penilai)';
                $dlStr     = ! empty($row['deadline_filling']) ? date('d-M-Y', strtotime($row['deadline_filling'])) : '-';
            } elseif ($status === 'REVIEW_GM') {
                $targetId  = (int) ($row['snapshot_gm_id'] ?? 0);
                $roleLabel = 'General Manager (Reviewer)';
                $dlStr     = ! empty($row['deadline_review']) ? date('d-M-Y', strtotime($row['deadline_review'])) : '-';
            } elseif ($status === 'PENDING_ACK') {
                $empId = (int) ($row['md_employee_id'] ?? 0);
                $hasUser = (bool) $this->db->table('sys_user')
                    ->where('md_employee_id', $empId)
                    ->where('isactive', 'Y')
                    ->countAllResults();

                if (! $hasUser) {
                    $dutaRow = $this->db->table('sys_emp_delegation ed')
                        ->select('u.md_employee_id as duta_emp_id, u.name as duta_name')
                        ->join('sys_user u', 'u.sys_user_id = ed.sys_user_id', 'left')
                        ->where('ed.md_employee_id', $empId)
                        ->where('ed.isactive', 'Y')
                        ->where('u.isactive', 'Y')
                        ->get()
                        ->getRowArray();

                    $dutaEmpId = (int) ($dutaRow['duta_emp_id'] ?? 0);
                    if ($dutaEmpId > 0) {
                        $targetId  = $dutaEmpId;
                        $roleLabel = 'Duta Karyawan (Konfirmasi / TTD atas nama ' . $empName . ')';
                    } else {
                        $targetId  = $empId;
                        $roleLabel = 'Karyawan (Konfirmasi / TTD)';
                    }
                } else {
                    $targetId  = $empId;
                    $roleLabel = 'Karyawan (Konfirmasi / TTD)';
                }
                $dlStr     = ! empty($row['deadline_reading']) ? date('d-M-Y', strtotime($row['deadline_reading'])) : '-';
            } else {
                return ['success' => false, 'message' => "Form dengan status '{$status}' sudah selesai atau tidak memerlukan pengingat."];
            }

            if ($targetId <= 0) {
                return ['success' => false, 'message' => "Penerima tugas untuk peran {$roleLabel} tidak terdata pada form ini."];
            }

            $title = "Pengingat: Form Evaluasi Kinerja ({$empName})";
            $html  = "<p>Halo,</p>"
                . "<p>Ini adalah pengingat dari HR untuk menyelesaikan form evaluasi kinerja <b>{$empName}</b> pada siklus <b>{$cycle}</b>.</p>"
                . "<p><b>Peran Anda:</b> {$roleLabel}<br>"
                . "<b>Batas Waktu:</b> {$dlStr}</p>"
                . "<p>Mohon segera memproses form tersebut melalui menu Penilaian Kinerja pada sistem SAS-HR.</p>"
                . "<p><small>Pesan otomatis dikirim oleh HR melalui SAS-HR.</small></p>";

            $ok = $this->enqueueBroadcast($targetId, $title, $html, $actorUserId);

            if ($ok) {
                // Record in audit log if available
                $this->db->table('md_appraisal_audit_log')->insert([
                    'md_appraisal_id' => $appraisalId,
                    'actor_id'        => $actorUserId,
                    'action'          => 'NUDGED',
                    'from_status'     => $status,
                    'to_status'       => $status,
                    'notes'           => "Pengingat manual dikirim oleh HR ke ID Karyawan #{$targetId} ({$roleLabel}).",
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);

                return ['success' => true, 'message' => "Pengingat berhasil dimasukkan ke antrean broadcast untuk {$roleLabel}."];
            }

            return ['success' => false, 'message' => 'Gagal memasukkan pesan ke antrean broadcast.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Daily automated check for approaching deadlines (H-3) or overdue appraisals.
     * Can be invoked via cron URL: /cron-appraisal-nudge
     *
     * @return array Summary of processed and sent reminders
     */
    public function runDailyNudge(): array
    {
        $todayStr          = date('Y-m-d');
        $remindedCount     = 0;
        $closedCyclesCount = 0;

        try {
            // 1. Auto-close expired cycles whose end_date has passed
            try {
                $cycleService      = new AppraisalCycleService();
                $closedCyclesCount = $cycleService->closeExpiredCycles(1);
            } catch (Throwable $ce) {
                log_message('error', '[AppraisalNudgeService::runDailyNudge] Auto-close cycles error: ' . $ce->getMessage());
            }

            // 2. Check active appraisals in open cycles
            $appraisals = $this->db->table('md_appraisal a')
                ->select('a.md_appraisal_id, a.status, a.snapshot_evaluator_id, a.snapshot_gm_id, a.md_employee_id, e.fullname as emp_name, c.title as cycle_title, c.deadline_filling, c.deadline_review, c.deadline_reading')
                ->join('md_appraisal_cycle c', 'c.md_appraisal_cycle_id = a.md_appraisal_cycle_id', 'inner')
                ->join('md_employee e', 'e.md_employee_id = a.md_employee_id', 'left')
                ->where('c.status', 'OPEN')
                ->where('a.isactive', 'Y')
                ->where('a.status !=', 'FINALIZED')
                ->get()
                ->getResultArray();

            $pendingByTarget = [];

            foreach ($appraisals as $app) {
                $st = $app['status'];
                $targetId = 0;
                $deadline = null;
                $stageName = '';

                if ($st === 'DRAFT_EVALUATOR' || $st === 'SUBMITTED_EVALUATOR') {
                    $targetId  = (int) $app['snapshot_evaluator_id'];
                    $deadline  = $app['deadline_filling'];
                    $stageName = 'Pengisian Atasan';
                } elseif ($st === 'REVIEW_GM') {
                    $targetId  = (int) $app['snapshot_gm_id'];
                    $deadline  = $app['deadline_review'];
                    $stageName = 'Review GM';
                } elseif ($st === 'PENDING_ACK') {
                    $targetId  = (int) $app['md_employee_id'];
                    $deadline  = $app['deadline_reading'];
                    $stageName = 'Pembacaan / TTD';
                }

                if ($targetId <= 0 || empty($deadline)) {
                    continue;
                }

                $diffDays = (int) floor((strtotime($deadline) - strtotime($todayStr)) / 86400);

                // Nudge on H-3, H-1, or Overdue (diffDays <= 0)
                if ($diffDays === 3 || $diffDays === 1 || $diffDays <= 0) {
                    if (! isset($pendingByTarget[$targetId])) {
                        $pendingByTarget[$targetId] = [
                            'target_id'   => $targetId,
                            'cycle_title' => $app['cycle_title'],
                            'deadline'    => $deadline,
                            'days_left'   => $diffDays,
                            'stage'       => $stageName,
                            'count'       => 0,
                        ];
                    }
                    $pendingByTarget[$targetId]['count']++;
                }
            }

            foreach ($pendingByTarget as $targetId => $info) {
                $statusDesc = ($info['days_left'] < 0) 
                    ? "Terlambat " . abs($info['days_left']) . " hari" 
                    : (($info['days_left'] === 0) ? "Jatuh tempo HARI INI" : "Tersisa {$info['days_left']} hari");

                $dlStr = date('d-M-Y', strtotime($info['deadline']));
                $title = "Pengingat Evaluasi Kinerja ({$statusDesc})";
                $html  = "<p>Halo,</p>"
                    . "<p>Anda memiliki <b>{$info['count']} form evaluasi</b> yang menunggu penyelesaian pada siklus <b>{$info['cycle_title']}</b>.</p>"
                    . "<p><b>Tahap:</b> {$info['stage']}<br>"
                    . "<b>Batas Waktu:</b> {$dlStr} (<b>{$statusDesc}</b>)</p>"
                    . "<p>Mohon segera memproses form terkait melalui menu Task List Penilaian pada sistem SAS-HR.</p>"
                    . "<p><small>Pesan otomatis terjadwal sistem SAS-HR.</small></p>";

                if ($this->enqueueBroadcast($targetId, $title, $html, 100000)) {
                    $remindedCount++;
                }
            }

            return [
                'status'        => 'success',
                'closed_cycles' => $closedCyclesCount,
                'reminded'      => $remindedCount,
                'total_groups'  => count($pendingByTarget),
            ];
        } catch (Throwable $e) {
            log_message('error', '[AppraisalNudgeService::runDailyNudge] ' . $e->getMessage());
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
