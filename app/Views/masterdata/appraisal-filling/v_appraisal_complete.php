<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?php
    helper('appraisal_view');

    $snapshot    = appraisal_normalize_snapshot($template_snapshot ?? []);
    $is_operator = isset($is_operator) ? (bool) $is_operator : appraisal_is_operator($snapshot);
    $summary     = appraisal_extract_complete_summary($snapshot, $responses_by_item_id ?? [], $is_operator);

    $this->setVar('template_snapshot', $snapshot);
    $this->setVar('is_operator', $is_operator);
    $this->setVar('display_mode', 'readonly');
    $this->setVar('section_number', 'V');

    $finalScoreVal = (float) ($appraisal->final_score ?? 0);
    $ratingMeta    = $is_operator
        ? appraisal_rating_meta((float) ($summary['totNilai'] ?? 0), true)
        : appraisal_rating_meta($appraisal->final_score !== null ? $finalScoreVal : null, false);

    $this->setVar('summary', $summary);
    $this->setVar('finalScoreVal', $finalScoreVal);
    $this->setVar('ratingMeta', $ratingMeta);

    $backUrl = base_url('sas/penilaian');
    if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'monitoring-penilaian') !== false || strpos($_SERVER['HTTP_REFERER'], 'performance-appraisal') !== false)) {
        $backUrl = base_url('sas/monitoring-penilaian');
    }
?>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_score_comparison') ?>
<?= $this->include('Views/masterdata/appraisal-reusable/modal_activity_feed') ?>
<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">

<div class="card-body card-main ap-wrap ap-hide-page-toolbar pb-5">

    <!-- TOP HEADER / ACTION BAR -->
    <div class="ap-report-header action-top-bar" style="background: #e2eaf4; border: 2px solid #94a3b8; border-radius: 4px; padding: 12px 20px;">
        <div>
            <a href="<?= $backUrl ?>" class="btn btn-sm btn-close-preview">
                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Kembali ke Daftar Tugas
            </a>
        </div>
        <div class="text-center flex-grow-1 mx-3">
            <h5 class="ap-report-title" style="font-size: 16px; font-weight: 800; color: #0f172a; letter-spacing: 0.3px;">
                Laporan Akhir Penilaian Evaluasi PA<?= $is_operator ? ' (Operator / Pelaksana)' : '' ?>
            </h5>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-close-preview" onclick="showScoreComparisonModal('<?= (int) $appraisal_id ?>')">
                <i class="fas fa-history mr-1" aria-hidden="true"></i> Riwayat Komparasi Skor
            </button>
            <button type="button" class="btn btn-sm btn-close-preview" onclick="showActivityFeedModal('<?= (int) $appraisal_id ?>')">
                <i class="fas fa-stream mr-1" aria-hidden="true"></i> Riwayat Aktivitas
            </button>
            <a href="<?= base_url('sas/penilaian/exportPDF/' . (int) $appraisal_id) ?>" target="_blank" class="btn btn-sm ap-btn-primary" style="height: 34px; padding: 4px 14px; border-radius: 4px; font-size: 13px; font-weight: 700;">
                <i class="fas fa-file-pdf mr-1" aria-hidden="true"></i> Unduh PDF
            </a>
        </div>
    </div>

    <!-- 1. INFO CARD -->
    <?= $this->include('Views/masterdata/appraisal-reusable/_info_card') ?>

    <?php if ($is_operator): ?>
        <!-- 2 & 3. OPERATOR SECTIONS -->
        <?= $this->include('Views/masterdata/appraisal-reusable/_section_operator_performance') ?>
        <?= $this->include('Views/masterdata/appraisal-reusable/_section_operator_additional') ?>
    <?php else: ?>
        <!-- 2 & 3. STAFF SECTIONS -->
        <?= $this->include('Views/masterdata/appraisal-reusable/_section_performance') ?>
        <?= $this->include('Views/masterdata/appraisal-reusable/_section_additional') ?>
    <?php endif; ?>

    <!-- IV. REKAP PENILAIAN -->
    <?= $this->include('Views/masterdata/appraisal-reusable/_section_rekap') ?>

    <!-- CATATAN & FEEDBACK -->
    <?= $this->include('Views/masterdata/appraisal-reusable/_section_feedback') ?>

    <!-- RINGKASAN SKOR AKHIR -->
    <?= $this->include('Views/masterdata/appraisal-reusable/_section_final_score') ?>

</div>

<div class="card-action">
    <a href="<?= $backUrl ?>" class="btn btn-outline-danger btn-round">Close</a>
</div>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_audit_log') ?>

<script>
(function () {
    var mp = document.querySelector('.main-panel');
    if (mp) mp.classList.remove('is-loading');
})();

document.addEventListener('DOMContentLoaded', function () {
    var mp = document.querySelector('.main-panel');
    if (mp) mp.classList.remove('is-loading');
    document.querySelectorAll('.card-header .new_form, .card-header .btn_requery, .card-header .float-right').forEach(function (el) {
        el.style.display = 'none';
    });
});
</script>

<?= $this->endSection() ?>
