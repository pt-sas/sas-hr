<?php
    $cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
    $startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
    $endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

    $periodeText = '-';
    if (!empty($startDate) && !empty($endDate)) {
        $periodeText = date('d M Y', strtotime($startDate)) . ' s/d ' . date('d M Y', strtotime($endDate));
    } elseif (!empty($cycleTitle)) {
        $periodeText = $cycleTitle;
    }

    $modeClass = $user_mode === 'EVALUATOR' ? '' : ($user_mode === 'GM' ? 'is-gm' : ($user_mode === 'EMPLOYEE' ? 'is-emp' : 'is-none'));
?>

<!-- 1. INFORMASI KARYAWAN & SIKLUS EVALUASI -->
<div class="ap-card">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div>
            <h5 class="ap-card-title d-inline-block mb-0 mr-2">Informasi Karyawan &amp; Siklus Evaluasi</h5>
            <?php if (!empty($is_preview)): ?>
                <span class="ap-mode is-none"><i class="fas fa-eye mr-1"></i> Mode: SIMULASI (VIEW)</span>
            <?php else: ?>
                <span class="ap-mode <?= $modeClass ?>">Mode: <?= esc($user_mode) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($is_preview)): ?>
            <button type="button" data-dismiss="modal" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times mr-1"></i> Tutup Pratinjau
            </button>
        <?php else: ?>
            <button type="button" onclick="$('.close_form').click();" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Tugas
            </button>
        <?php endif; ?>
    </div>
    <?php if (!empty($is_duta_acknowledgment)): ?>
        <div class="alert alert-warning py-2 px-3 mx-3 mt-3 mb-0 border-warning d-flex align-items-center" style="font-size: 13px;">
            <i class="fas fa-user-shield fa-lg mr-2 text-warning"></i>
            <div>
                <strong>Konfirmasi oleh Duta:</strong> Anda menandatangani evaluasi kinerja ini mewakili <strong><?= esc($employee['name'] ?? 'karyawan') ?></strong> karena karyawan yang bersangkutan tidak memiliki akun pengguna sistem.
            </div>
        </div>
    <?php endif; ?>
    <div class="ap-card-body">
        <div class="ap-info-grid">
            <div class="ap-info-item">
                <div class="ap-info-label">NIK / Nama</div>
                <div class="ap-info-value"><?= esc($employee['nik'] ?? '-') ?> &middot; <?= esc($employee['name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Siklus Penilaian</div>
                <div class="ap-info-value"><?= esc($cycleTitle) ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Divisi / Posisi</div>
                <div class="ap-info-value"><?= esc($employee['division_name'] ?? '-') ?> / <?= esc($employee['position_name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Periode Evaluasi</div>
                <div class="ap-info-value"><?= esc($periodeText) ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Cabang / Lokasi</div>
                <div class="ap-info-value"><?= esc($employee['branch_name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Penilai (Evaluator)</div>
                <div class="ap-info-value"><?= esc($evaluator['name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Peninjau (Reviewer)</div>
                <div class="ap-info-value"><?= esc($gm['name'] ?? '-') ?></div>
            </div>
            <div class="ap-info-item">
                <div class="ap-info-label">Status Penilaian</div>
                <div class="ap-info-value font-weight-bold text-dark">
                    <?php
                        $curSt = $appraisal->status ?? 'DRAFT_EVALUATOR';
                        echo esc(\App\Models\M_Appraisal::getStatusLabel($curSt));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($appraisal->gm_comment) && $appraisal->status === \App\Models\M_Appraisal::STATUS_DRAFT_EVALUATOR): ?>
    <div class="ap-kickback">
        <div class="ap-kickback-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="ap-kickback-title">Form dikembalikan oleh Peninjau (Reviewer) untuk revisi</div>
            <p class="ap-kickback-text"><?= nl2br(esc($appraisal->gm_comment)) ?></p>
        </div>
    </div>
<?php endif; ?>