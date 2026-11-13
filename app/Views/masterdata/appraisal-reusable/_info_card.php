<?php
    helper('appraisal_view');

    $displayMode  = $display_mode ?? 'edit';
    $userMode     = $user_mode ?? '';
    $periodeText  = appraisal_format_period($cycle ?? null);
    $curSt        = $appraisal->status ?? \App\Models\M_Appraisal::STATUS_PENILAIAN;
    $statusLabel  = \App\Models\M_Appraisal::getStatusLabel($curSt);
    $isDraftSt    = ($curSt === \App\Models\M_Appraisal::STATUS_PENILAIAN || strpos($curSt, 'DRAFT') !== false);
?>

<!-- I. INFORMASI KARYAWAN & SIKLUS EVALUASI -->
<div class="ap-card<?= $displayMode === 'compare' ? ' mb-4' : '' ?>">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="ap-card-title-group d-flex align-items-center">
            <h5 class="ap-card-title d-inline-block mb-0 mr-2">
                I. Informasi Karyawan &amp; Siklus Evaluasi PA
            </h5>
            <?php if ($displayMode === 'compare'): ?>
                <span class="ap-mode is-gm">STATUS: KOMPARASI SKOR</span>
            <?php elseif (! empty($is_preview)): ?>
                <span class="ap-mode is-none">STATUS: MODE SIMULASI</span>
            <?php elseif ($displayMode === 'readonly' || $userMode === 'VIEW'): ?>
                <span class="ap-mode is-emp">STATUS: <?= esc(mb_strtoupper($statusLabel, 'UTF-8')) ?></span>
            <?php elseif ($userMode === 'GM'): ?>
                <span class="ap-mode is-gm">STATUS: PENINJAU</span>
            <?php elseif ($userMode === 'HR'): ?>
                <span class="ap-mode is-gm" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">STATUS: INTERVENSI HR</span>
            <?php elseif ($userMode === 'EVALUATOR'): ?>
                <span class="ap-mode is-evaluator">STATUS: PENILAI</span>
            <?php elseif ($userMode === 'EMPLOYEE'): ?>
                <span class="ap-mode is-emp">STATUS: KARYAWAN</span>
            <?php else: ?>
                <span class="ap-mode is-none">STATUS: <?= esc(mb_strtoupper($statusLabel, 'UTF-8')) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($displayMode === 'edit'): ?>
            <?php if (! empty($is_preview)): ?>
                <button type="button" data-dismiss="modal" class="btn btn-sm btn-close-preview">
                    <i class="fas fa-times mr-1" aria-hidden="true"></i> Tutup Pratinjau
                </button>
            <?php else: ?>
                <button type="button" onclick="$('.close_form').click();" class="btn btn-sm btn-close-preview">
                    <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Kembali ke Daftar Tugas
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (! empty($is_duta_acknowledgment)): ?>
        <div class="alert alert-warning py-2 px-3 mx-3 mt-3 mb-0 border-warning d-flex align-items-center" style="font-size: 13px;">
            <i class="fas fa-user-shield fa-lg mr-2 text-warning" aria-hidden="true"></i>
            <div>
                <strong>Konfirmasi oleh Duta:</strong> Anda menandatangani evaluasi kinerja ini mewakili <strong><?= esc(mb_strtoupper($employee['name'] ?? 'karyawan', 'UTF-8')) ?></strong> karena karyawan yang bersangkutan tidak memiliki akun pengguna sistem.
            </div>
        </div>
    <?php endif; ?>

    <div class="ap-card-body">
        <div class="ap-info-grid">
            <div class="ap-info-item">
                <div class="ap-info-label">NIK / Nama</div>
                <div class="ap-info-value highlight"><?= esc($employee['nik'] ?? '-') ?> &middot; <?= esc(mb_strtoupper($employee['name'] ?? '-', 'UTF-8')) ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Siklus Penilaian</div>
                <div class="ap-info-value"><?= esc(is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-')) ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Divisi / Posisi</div>
                <div class="ap-info-value"><?= esc($employee['division_name'] ?? '-') ?> / <?= esc($employee['position_name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label"><i class="far fa-calendar-alt mr-1" aria-hidden="true"></i> Periode Evaluasi</div>
                <div class="ap-info-value"><?= esc($periodeText) ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Cabang</div>
                <div class="ap-info-value"><?= esc($employee['branch_name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Penilai</div>
                <div class="ap-info-value"><?= esc(mb_strtoupper($evaluator['name'] ?? '-', 'UTF-8')) ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Status Penilaian</div>
                <div class="ap-info-value">
                    <span class="<?= $isDraftSt ? 'text-danger font-weight-bold' : 'font-weight-bold' ?>">
                        <?= esc($statusLabel) ?>
                    </span>
                </div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Peninjau</div>
                <div class="ap-info-value"><?= esc(mb_strtoupper($gm['name'] ?? '-', 'UTF-8')) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($displayMode === 'edit' && ! empty($appraisal->gm_comment) && $curSt === \App\Models\M_Appraisal::STATUS_DRAFT_EVALUATOR): ?>
    <div class="ap-kickback">
        <div class="ap-kickback-icon"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></div>
        <div>
            <div class="ap-kickback-title">Form dikembalikan oleh Peninjau untuk revisi</div>
            <p class="ap-kickback-text"><?= nl2br(esc($appraisal->gm_comment)) ?></p>
        </div>
    </div>
<?php endif; ?>
