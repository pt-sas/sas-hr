<?php

namespace App\Services;

use App\Entities\Appraisal;
use App\Models\M_Appraisal;
use App\Models\M_AppraisalAuditLog;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Exception;

class AppraisalWorkflowService
{
    protected M_Appraisal $appraisalModel;
    protected M_AppraisalAuditLog $auditLogModel;
    protected AppraisalCycleService $cycleService;

    public function __construct()
    {
        $this->appraisalModel = new M_Appraisal();
        $this->auditLogModel  = new M_AppraisalAuditLog();
        $this->cycleService   = new AppraisalCycleService();
    }

    /**
     * Evaluator submits the evaluation.
     * Transitions: DRAFT_EVALUATOR -> REVIEW_GM (if GM assigned) OR PENDING_ACK.
     *
     * @param int $appraisalId
     * @param int $actorId
     * @param string|null $evaluatorComment
     * @param string|null $developmentPlan
     * @return Appraisal
     * @throws Exception
     */
    public function submitByEvaluator(
        int $appraisalId,
        int $actorId,
        ?string $evaluatorComment = null,
        ?string $developmentPlan = null
    ): Appraisal {
        $appraisal = $this->getAppraisalOrFail($appraisalId);

        // 1. Cycle & Stage 6 Guard
        $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id);

        // 2. Status Guard
        if ($appraisal->status !== M_Appraisal::STATUS_DRAFT_EVALUATOR) {
            throw new Exception("Form penilaian tidak dapat disubmit dari status '{$appraisal->status}'.");
        }

        $previousStatus = $appraisal->status;

        // 2. Next status: REVIEW_GM if snapshot_gm_id is set, otherwise PENDING_ACK
        $nextStatus = ! empty($appraisal->snapshot_gm_id) 
            ? M_Appraisal::STATUS_REVIEW_GM 
            : M_Appraisal::STATUS_PENDING_ACK;

        $db = \Config\Database::connect();
        $db->transStart();

        if ($evaluatorComment !== null) {
            $appraisal->evaluator_comment = $evaluatorComment;
        }
        if ($developmentPlan !== null) {
            $appraisal->development_plan = $developmentPlan;
        }

        $appraisal->status                 = $nextStatus;
        $appraisal->evaluator_submitted_at = date('Y-m-d H:i:s');
        $appraisal->updated_by             = $actorId;
        $this->appraisalModel->save($appraisal);

        $auditNote = ! empty($appraisal->snapshot_gm_id)
            ? 'Penilai (atasan) menyelesaikan pengisian dan mengirim form penilaian.'
            : 'Penilai menyelesaikan evaluasi. Tanpa persetujuan lanjutan lapis kedua, form langsung dikirim ke karyawan untuk konfirmasi.';

        // 3. Record Audit Log
        $this->recordAuditLog(
            $appraisalId,
            $actorId,
            'SUBMIT_EVALUATOR',
            $previousStatus,
            $nextStatus,
            $auditNote
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('Gagal memproses submit penilaian oleh atasan.');
        }

        // Trigger Nudge / Handover Notification
        try {
            $nudgeService = new AppraisalNudgeService();
            $nudgeEvent = ! empty($appraisal->snapshot_gm_id) ? 'SUBMIT_EVALUATOR' : 'APPROVE_GM';
            $nudgeService->notifyWorkflowTransition($appraisalId, $nudgeEvent, $actorId);
        } catch (\Throwable $ne) {
            log_message('error', '[AppraisalWorkflowService] Nudge submit error: ' . $ne->getMessage());
        }

        return $this->appraisalModel->find($appraisalId);
    }

    /**
     * GM reviews and approves evaluation.
     * Transitions: REVIEW_GM -> PENDING_ACK.
     *
     * @param int $appraisalId
     * @param int $actorId
     * @param string|null $gmComment
     * @return Appraisal
     * @throws Exception
     */
    public function reviewByGm(int $appraisalId, int $actorId, ?string $gmComment = null): Appraisal
    {
        $appraisal = $this->getAppraisalOrFail($appraisalId);

        // 1. Cycle & Stage 6 Guard
        $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id);

        // 2. Status Guard
        if ($appraisal->status !== M_Appraisal::STATUS_REVIEW_GM) {
            throw new Exception("Form penilaian tidak dapat direview dari status '{$appraisal->status}'.");
        }

        $previousStatus = $appraisal->status;
        $nextStatus     = M_Appraisal::STATUS_PENDING_ACK;

        $db = \Config\Database::connect();
        $db->transStart();

        if ($gmComment !== null) {
            $appraisal->gm_comment = $gmComment;
        }

        $appraisal->status         = $nextStatus;
        $appraisal->gm_reviewed_at = date('Y-m-d H:i:s');
        $appraisal->updated_by     = $actorId;
        $this->appraisalModel->save($appraisal);

        // 2. Record Audit Log
        $this->recordAuditLog(
            $appraisalId,
            $actorId,
            'REVIEW_GM',
            $previousStatus,
            $nextStatus,
            'Peninjau menyelesaikan review evaluasi kinerja.'
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('Gagal memproses review oleh Peninjau.');
        }

        // Trigger Nudge: Notify employee that review is completed & ready for ack/sign
        try {
            $nudgeService = new AppraisalNudgeService();
            $nudgeService->notifyWorkflowTransition($appraisalId, 'APPROVE_GM', $actorId);
        } catch (\Throwable $ne) {
            log_message('error', '[AppraisalWorkflowService] Nudge approve GM error: ' . $ne->getMessage());
        }

        return $this->appraisalModel->find($appraisalId);
    }

    /**
     * GM kicks back appraisal form to Evaluator for revision.
     * Transitions: REVIEW_GM -> DRAFT_EVALUATOR.
     *
     * @param int $appraisalId
     * @param int $actorId
     * @param string $kickbackReason
     * @return Appraisal
     * @throws Exception
     */
    public function kickbackByGm(int $appraisalId, int $actorId, string $kickbackReason): Appraisal
    {
        $appraisal = $this->getAppraisalOrFail($appraisalId);

        // 1. Cycle & Stage 6 Guard
        $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id);

        // 2. Status Guard
        if ($appraisal->status !== M_Appraisal::STATUS_REVIEW_GM) {
            throw new Exception("Form penilaian tidak dapat dikembalikan dari status '{$appraisal->status}'.");
        }

        $kickbackReason = trim($kickbackReason);
        if (empty($kickbackReason)) {
            throw new Exception('Alasan revisi pengembalian form harus diisi oleh Peninjau.');
        }

        $previousStatus = $appraisal->status;
        $nextStatus     = M_Appraisal::STATUS_DRAFT_EVALUATOR;

        $db = \Config\Database::connect();
        $db->transStart();

        $appraisal->status     = $nextStatus;
        $appraisal->gm_comment = $kickbackReason;
        $appraisal->updated_by = $actorId;
        $this->appraisalModel->save($appraisal);

        // 2. Record Audit Log
        $this->recordAuditLog(
            $appraisalId,
            $actorId,
            'KICKBACK_GM',
            $previousStatus,
            $nextStatus,
            'Peninjau mengembalikan form evaluasi ke Penilai untuk direvisi: ' . $kickbackReason
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('Gagal memproses pengembalian form oleh Peninjau.');
        }

        // Trigger Nudge: Notify Evaluator that form was kicked back for revision
        try {
            $nudgeService = new AppraisalNudgeService();
            $nudgeService->notifyWorkflowTransition($appraisalId, 'RETURN_GM', $actorId);
        } catch (\Throwable $ne) {
            log_message('error', '[AppraisalWorkflowService] Nudge return GM error: ' . $ne->getMessage());
        }

        return $this->appraisalModel->find($appraisalId);
    }

    /**
     * Employee acknowledges and signs off the appraisal form.
     * Transitions: PENDING_ACK -> FINALIZED.
     *
     * @param int $appraisalId
     * @param int $actorId
     * @param string|null $employeeComment
     * @return Appraisal
     * @throws Exception
     */
    public function acknowledgeByEmployee(int $appraisalId, int $actorId, ?string $employeeComment = null): Appraisal
    {
        $appraisal = $this->getAppraisalOrFail($appraisalId);

        // 1. Cycle & Stage 6 Guard
        $this->cycleService->assertCycleModifiable((int) $appraisal->md_appraisal_cycle_id);

        // 2. Status Guard
        if ($appraisal->status !== M_Appraisal::STATUS_PENDING_ACK) {
            throw new Exception("Form penilaian tidak dapat dikonfirmasi dari status '{$appraisal->status}'.");
        }

        $previousStatus = $appraisal->status;
        $nextStatus     = M_Appraisal::STATUS_FINALIZED;

        $db = \Config\Database::connect();
        $db->transStart();

        if ($employeeComment !== null) {
            $appraisal->employee_comment = $employeeComment;
        }

        $appraisal->status                   = $nextStatus;
        $appraisal->employee_acknowledged_at = date('Y-m-d H:i:s');
        $appraisal->finalized_at             = date('Y-m-d H:i:s');
        $appraisal->updated_by               = $actorId;
        $this->appraisalModel->save($appraisal);

        // 2. Record Audit Log
        $userRow = $db->table('sys_user')
            ->select('sys_user_id, name, username, md_employee_id')
            ->where('sys_user_id', $actorId)
            ->get()
            ->getRow();

        $actorEmpId   = $userRow ? (int) $userRow->md_employee_id : 0;
        $targetEmpId  = (int) $appraisal->md_employee_id;
        $isDirectEmp  = ($actorEmpId > 0 && $actorEmpId === $targetEmpId);
        $isDuta       = (! $isDirectEmp && $this->isDutaForEmployeeWithoutUser($actorId, $targetEmpId));

        if ($isDirectEmp) {
            $logDescription = 'Karyawan mengonfirmasi dan menandatangani hasil evaluasi kinerja.';
        } elseif ($isDuta) {
            $targetEmpRow = $db->table('md_employee')->select('fullname')->where('md_employee_id', $targetEmpId)->get()->getRow();
            $targetName   = $targetEmpRow->fullname ?? "ID #{$targetEmpId}";
            $dutaName     = $userRow->name ?? "User #{$actorId}";
            $logDescription = "Duta ({$dutaName}) mengonfirmasi dan menandatangani evaluasi kinerja mewakili karyawan ({$targetName}).";
        } else {
            $logDescription = 'HR/Admin mengonfirmasi dan menandatangani evaluasi kinerja atas nama karyawan.';
        }

        $this->recordAuditLog(
            $appraisalId,
            $actorId,
            'ACKNOWLEDGE_EMPLOYEE',
            $previousStatus,
            $nextStatus,
            $logDescription
        );

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('Gagal memproses konfirmasi penilaian oleh karyawan.');
        }

        return $this->appraisalModel->find($appraisalId);
    }

    /**
     * Retrieve audit history logs for an appraisal with actor user details.
     *
     * @param int $appraisalId
     * @return array
     */
    public function getAuditLogs(int $appraisalId): array
    {
        $db = \Config\Database::connect();
        return $db->table('md_appraisal_audit_log l')
            ->select('l.*, u.name as actor_name, u.username as actor_username')
            ->join('sys_user u', 'u.sys_user_id = l.actor_id', 'left')
            ->where('l.md_appraisal_id', $appraisalId)
            ->orderBy('l.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Retrieve delegated employee IDs for a user where the employees do NOT have an active user account.
     * Only employees without a system user account pass their acknowledgment privilege to their Duta.
     *
     * @param int $userId
     * @return int[]
     */
    public function getDelegatedEmployeesWithoutUser(int $userId): array
    {
        $db = \Config\Database::connect();

        // 1. Fetch direct delegations from sys_emp_delegation
        $delegatedRows = $db->table('sys_emp_delegation')
            ->select('md_employee_id')
            ->where('sys_user_id', $userId)
            ->where('isactive', 'Y')
            ->get()
            ->getResultArray();
        $delegatedEmpIds = array_column($delegatedRows, 'md_employee_id');

        // 2. Also check M_Employee::getEmpDelegation
        try {
            $request = \Config\Services::request();
            $mEmployee = new \App\Models\M_Employee($request);
            $delegationFromModel = $mEmployee->getEmpDelegation($userId);
            if (! empty($delegationFromModel)) {
                $delegatedEmpIds = array_merge($delegatedEmpIds, $delegationFromModel);
            }
        } catch (\Throwable $e) {
            // Fallback gracefully if request context is not standard
        }

        $delegatedEmpIds = array_values(array_unique(array_filter(array_map('intval', $delegatedEmpIds))));

        if (empty($delegatedEmpIds)) {
            return [];
        }

        // 3. Exclude any employees that possess an active sys_user account
        $employeesWithUser = array_column(
            $db->table('sys_user')
                ->select('md_employee_id')
                ->whereIn('md_employee_id', $delegatedEmpIds)
                ->where('isactive', 'Y')
                ->where('md_employee_id IS NOT NULL')
                ->get()
                ->getResultArray(),
            'md_employee_id'
        );
        $employeesWithUser = array_map('intval', $employeesWithUser);

        return array_values(array_diff($delegatedEmpIds, $employeesWithUser));
    }

    /**
     * Check if a given user is the authorized Duta for an employee without a user account.
     *
     * @param int $userId
     * @param int $targetEmployeeId
     * @return bool
     */
    public function isDutaForEmployeeWithoutUser(int $userId, int $targetEmployeeId): bool
    {
        $delegated = $this->getDelegatedEmployeesWithoutUser($userId);
        return in_array($targetEmployeeId, $delegated, true);
    }

    protected function getAppraisalOrFail(int $appraisalId): Appraisal
    {
        $appraisal = $this->appraisalModel->find($appraisalId);

        if (! $appraisal) {
            throw new Exception('Form penilaian tidak ditemukan.');
        }

        return $appraisal;
    }

    protected function recordAuditLog(
        int $appraisalId,
        int $actorId,
        string $action,
        string $prevStatus,
        string $newStatus,
        ?string $notes = null
    ): void {
        $this->auditLogModel->insert([
            'md_appraisal_id' => $appraisalId,
            'actor_id'        => $actorId,
            'action'          => $action,
            'from_status'     => $prevStatus,
            'to_status'       => $newStatus,
            'notes'           => $notes,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
