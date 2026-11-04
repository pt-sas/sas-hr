<?php

namespace App\Services;

use App\Entities\AppraisalCycle;
use App\Models\M_AppraisalCycle;
use Exception;

class AppraisalCycleService
{
    protected M_AppraisalCycle $cycleModel;

    public function __construct()
    {
        $this->cycleModel = new M_AppraisalCycle();
    }

    /**
     * Transition a cycle from Draft to Open and execute batch generation for active employees.
     *
     * @param int $cycleId
     * @param int $userId
     * @return array Summary of generated, skipped, and errors
     * @throws Exception
     */
    public function openCycle(int $cycleId, int $userId): array
    {
        $cycle = $this->cycleModel->find($cycleId);

        if (! $cycle) {
            throw new Exception('Siklus penilaian tidak ditemukan.');
        }

        if ($cycle->status !== M_AppraisalCycle::STATUS_DRAFT) {
            throw new Exception('Hanya siklus berstatus DRAFT yang dapat dibuka.');
        }

        // Ensure there is only one active OPEN cycle at a time
        $activeOpenCycle = $this->cycleModel->where('status', M_AppraisalCycle::STATUS_OPEN)
                                            ->where('isactive', 'Y')
                                            ->first();

        $activeOpenId = $activeOpenCycle ? (is_object($activeOpenCycle) ? (int) $activeOpenCycle->md_appraisal_cycle_id : (int) ($activeOpenCycle['md_appraisal_cycle_id'] ?? 0)) : 0;
        $activeTitle  = $activeOpenCycle ? (is_object($activeOpenCycle) ? ($activeOpenCycle->title ?? '') : ($activeOpenCycle['title'] ?? '')) : '';

        if ($activeOpenCycle && $activeOpenId !== $cycleId) {
            throw new Exception("Tidak dapat membuka siklus. Siklus '{$activeTitle}' saat ini sedang AKTIF (OPEN).");
        }

        // 1. Update cycle status to OPEN
        $cycle->status     = M_AppraisalCycle::STATUS_OPEN;
        $cycle->updated_by = $userId;
        $this->cycleModel->save($cycle);

        // 2. Trigger batch generation
        try {
            $generator = new AppraisalGeneratorService();
            $genResult = $generator->generateBatchForCycle($cycleId, $userId);

            if (($genResult['generated'] ?? 0) === 0 && ($genResult['skipped'] ?? 0) === 0) {
                $errDetail = !empty($genResult['errors']) ? ' Detail: ' . implode('; ', array_slice($genResult['errors'], 0, 3)) : '';
                throw new Exception('Tidak ada form penilaian yang berhasil di-generate. Pastikan terdapat template PUBLISHED dan karyawan aktif.' . $errDetail);
            }

            // Trigger Nudge: Notify cycle kickoff to all evaluators
            try {
                $nudgeService = new AppraisalNudgeService();
                $nudgeService->notifyCycleKickoff($cycleId, $userId);
            } catch (\Throwable $ne) {
                log_message('error', '[AppraisalCycleService] Kickoff nudge error: ' . $ne->getMessage());
            }

            return $genResult;
        } catch (Exception $e) {
            // Revert cycle status back to DRAFT if generation completely failed
            $cycle->status     = M_AppraisalCycle::STATUS_DRAFT;
            $cycle->updated_by = $userId;
            $this->cycleModel->save($cycle);

            throw $e;
        }
    }

    /**
     * Transition a cycle from Open to Closed.
     *
     * @param int $cycleId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function closeCycle(int $cycleId, int $userId): bool
    {
        $cycle = $this->cycleModel->find($cycleId);

        if (! $cycle) {
            throw new Exception('Siklus penilaian tidak ditemukan.');
        }

        if (! $cycle->isOpen()) {
            throw new Exception('Hanya siklus berstatus OPEN yang dapat ditutup.');
        }

        $cycle->status     = M_AppraisalCycle::STATUS_CLOSED;
        $cycle->updated_by = $userId;

        return (bool) $this->cycleModel->save($cycle);
    }

    /**
     * Get the currently active OPEN cycle.
     * Lazy Auto-Close: If the active cycle's end_date has passed, automatically
     * close it and return null (no active open cycle).
     *
     * @param string|null $currentDate Reference date (Y-m-d), defaults to today
     * @return AppraisalCycle|null
     */
    public function getActiveCycle(?string $currentDate = null): ?AppraisalCycle
    {
        $cycle = $this->cycleModel->where('status', M_AppraisalCycle::STATUS_OPEN)
                                  ->where('isactive', 'Y')
                                  ->first();

        if (! $cycle) {
            return null;
        }

        $nowStr  = $currentDate ?: date('Y-m-d');
        $endDate = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

        if (! empty($endDate) && $nowStr > $endDate) {
            $cycleId = is_object($cycle) ? (int) $cycle->md_appraisal_cycle_id : (int) ($cycle['md_appraisal_cycle_id'] ?? 0);
            try {
                $this->closeCycle($cycleId, 1);
                log_message('info', "[AppraisalCycleService::getActiveCycle] Auto-closed expired cycle #{$cycleId} (end_date: {$endDate})");
            } catch (Exception $e) {
                log_message('error', "[AppraisalCycleService::getActiveCycle] Failed to auto-close cycle #{$cycleId}: " . $e->getMessage());
            }
            return null;
        }

        return $cycle;
    }

    /**
     * Close all OPEN cycles whose end_date has passed.
     * Designed for scheduled cron maintenance.
     *
     * @param int $actorId User ID attributing the closure (1 = System)
     * @return int Number of cycles closed
     */
    public function closeExpiredCycles(int $actorId = 1): int
    {
        $today = date('Y-m-d');
        $expiredCycles = $this->cycleModel
            ->where('status', M_AppraisalCycle::STATUS_OPEN)
            ->where('isactive', 'Y')
            ->where('end_date <', $today)
            ->findAll();

        $closedCount = 0;
        foreach ($expiredCycles as $cycle) {
            $cycleId = is_object($cycle) ? (int) $cycle->md_appraisal_cycle_id : (int) ($cycle['md_appraisal_cycle_id'] ?? 0);
            try {
                $this->closeCycle($cycleId, $actorId);
                log_message('info', "[AppraisalCycleService::closeExpiredCycles] Auto-closed expired cycle #{$cycleId}");
                $closedCount++;
            } catch (Exception $e) {
                log_message('error', "[AppraisalCycleService::closeExpiredCycles] Failed to close cycle #{$cycleId}: " . $e->getMessage());
            }
        }

        return $closedCount;
    }

    /**
     * Validate that an appraisal cycle is still open and receptive to modifications.
     * Rejects modifications if the cycle is CLOSED or at/past Stage 6 (Finalisasi / end_date).
     *
     * @param int $cycleId
     * @param string|null $currentDate Reference date (Y-m-d)
     * @throws Exception
     */
    public function assertCycleModifiable(int $cycleId, ?string $currentDate = null): void
    {
        $cycle = $this->cycleModel->find($cycleId);

        if (! $cycle) {
            throw new Exception('Siklus penilaian tidak ditemukan.');
        }

        if (is_object($cycle) && method_exists($cycle, 'isClosed') && $cycle->isClosed()) {
            throw new Exception('Siklus penilaian telah ditutup (CLOSED). Form penilaian tidak dapat diubah.');
        }

        $status = is_object($cycle) ? ($cycle->status ?? '') : ($cycle['status'] ?? '');
        if ($status !== M_AppraisalCycle::STATUS_OPEN) {
            throw new Exception("Siklus penilaian sedang tidak aktif (Status: {$status}). Form penilaian tidak dapat diubah.");
        }

        $nowStr  = $currentDate ?: date('Y-m-d');
        $endDate = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

        // Stage 6 / Finalisasi guard
        if (is_object($cycle) && method_exists($cycle, 'getCurrentStage')) {
            if ($cycle->getCurrentStage($nowStr) >= 6) {
                throw new Exception('Siklus penilaian sudah memasuki tahap Finalisasi. Pengisian dan perubahan form telah dikunci.');
            }
        } elseif (! empty($endDate) && $nowStr > $endDate) {
            throw new Exception('Siklus penilaian telah melewati batas tanggal akhir. Pengisian dan perubahan form telah dikunci.');
        }
    }
}
