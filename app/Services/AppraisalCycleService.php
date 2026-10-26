<?php

namespace App\Services;

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
}
