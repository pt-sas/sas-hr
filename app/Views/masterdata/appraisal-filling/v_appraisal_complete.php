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

    $backUrl = base_url('sas/penilaian');
    if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'monitoring-penilaian') !== false || strpos($_SERVER['HTTP_REFERER'], 'performance-appraisal') !== false)) {
        $backUrl = base_url('sas/monitoring-penilaian');
    }
?>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_score_comparison') ?>
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

        <!-- IV. REKAP PENILAIAN (Operator) -->
        <?php
            $itemsNilai3             = $summary['itemsNilai3'] ?? [];
            $itemsNilai1             = $summary['itemsNilai1'] ?? [];
            $itemsKompetensiTambahan = $summary['itemsKompetensiTambahan'] ?? [];
        ?>
        <div class="ap-card">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <h5 class="ap-card-title mb-0">IV. Rekap Penilaian</h5>
            </div>
            <div class="table-responsive ap-table-wrapper">
                <table class="ap-table mb-0">
                    <tbody>
                        <tr>
                            <td style="width: 25%; font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">Sangat Baik</td>
                            <td style="width: 75%; vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                                <?php if (! empty($itemsNilai3)): ?>
                                    <ol type="a" class="mb-0 pl-3">
                                        <?php foreach ($itemsNilai3 as $n3Text): ?>
                                            <li class="mb-1 text-dark"><?= esc($n3Text) ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <span class="text-muted font-italic">Tidak ada indikator dengan nilai 3</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">Kurang</td>
                            <td style="vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                                <?php if (! empty($itemsNilai1)): ?>
                                    <ol type="a" class="mb-0 pl-3">
                                        <?php foreach ($itemsNilai1 as $n1Text): ?>
                                            <li class="mb-1 text-dark"><?= esc($n1Text) ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <span class="text-muted font-italic">Tidak ada indikator dengan nilai 1</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top;">KOMPETENSI TAMBAHAN</td>
                            <td style="vertical-align: top; line-height: 1.6;">
                                <?php if (! empty($itemsKompetensiTambahan)): ?>
                                    <ol type="a" class="mb-0 pl-3">
                                        <?php foreach ($itemsKompetensiTambahan as $ktText): ?>
                                            <li class="mb-1 text-dark"><?= esc($ktText) ?></li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <span class="text-muted font-italic">Tidak ada kompetensi tambahan yang terpilih</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- 2 & 3. STAFF SECTIONS -->
        <?= $this->include('Views/masterdata/appraisal-reusable/_section_performance') ?>
        <?= $this->include('Views/masterdata/appraisal-reusable/_section_additional') ?>

        <!-- IV. REKAP PENILAIAN (Staff) -->
        <?php
            $itemsNilai4             = $summary['itemsNilai4'] ?? [];
            $itemsNilai1             = $summary['itemsNilai1'] ?? [];
            $itemsKompetensiTambahan = $summary['itemsKompetensiTambahan'] ?? [];
        ?>
        <div class="ap-card">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <h5 class="ap-card-title mb-0">IV. Rekap Penilaian</h5>
            </div>
            <div class="table-responsive ap-table-wrapper">
                <table class="ap-table mb-0">
                    <tbody>
                        <tr>
                            <td style="width: 25%; font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">NILAI 4</td>
                            <td style="width: 75%; vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                                <?php if (! empty($itemsNilai4)): ?>
                                    <ul class="mb-0 pl-3">
                                        <?php foreach ($itemsNilai4 as $n4Text): ?>
                                            <li class="mb-1 text-dark"><?= esc($n4Text) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted font-italic">Tidak ada indikator dengan nilai 4</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">NILAI 1</td>
                            <td style="vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                                <?php if (! empty($itemsNilai1)): ?>
                                    <ul class="mb-0 pl-3">
                                        <?php foreach ($itemsNilai1 as $n1Text): ?>
                                            <li class="mb-1 text-dark"><?= esc($n1Text) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted font-italic">Tidak ada indikator dengan nilai 1</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top;">KOMPETENSI TAMBAHAN</td>
                            <td style="vertical-align: top; line-height: 1.6;">
                                <?php if (! empty($itemsKompetensiTambahan)): ?>
                                    <ul class="mb-0 pl-3">
                                        <?php foreach ($itemsKompetensiTambahan as $ktText): ?>
                                            <li class="mb-1 text-dark"><?= esc($ktText) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted font-italic">Tidak ada kompetensi tambahan yang terpilih</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- CATATAN & FEEDBACK -->
    <?= $this->include('Views/masterdata/appraisal-reusable/_section_feedback') ?>

    <!-- RINGKASAN SKOR AKHIR -->
    <div class="ap-card mb-4">
        <div class="ap-card-head">
            <h5 class="ap-card-title">VI. Ringkasan Skor Akhir</h5>
        </div>
        <div class="ap-card-body p-0">
            <div class="ap-score-summary-grid">
                <div class="ap-score-col">
                    <div class="ap-summary-label">Skor Akhir Performa</div>
                    <div class="ap-summary-score">
                        <?php if ($is_operator): ?>
                            <?= esc($summary['displaySkorAkhir'] ?? '0') ?>
                        <?php else: ?>
                            <?= number_format($finalScoreVal, 2) ?> <small>/ 4.00</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ap-score-col">
                    <div class="ap-summary-label">Predikat Penilaian</div>
                    <div>
                        <span class="ap-rating ap-summary-rating <?= esc($ratingMeta['class']) ?>">
                            <?= esc($is_operator ? $ratingMeta['label'] : ($appraisal->rating ?? $ratingMeta['label'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
