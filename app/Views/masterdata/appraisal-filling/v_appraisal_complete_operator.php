<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_score_comparison') ?>
<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">

<style>
.main-panel.is-loading:after {
    display: none !important;
}
.card-header .float-right,
.card-header .new_form,
.card-header .btn_requery,
.new_form {
    display: none !important;
}

@media print {
    .main-header, .sidebar, .breadcrumbs, .page-header, .action-top-bar, .modal, .btn, .badge-action, .ap-report-header button, .ap-report-header a {
        display: none !important;
    }
    .main-panel {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card-body, .card-main, .ap-wrap {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    .ap-card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 16px !important;
    }
    .ap-card-head {
        background-color: #f1f5f9 !important;
        border-bottom: 1px solid #cbd5e1 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ap-table thead th {
        background: #f1f5f9 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?php
    $cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
    $startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
    $endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

    $periodeText = '-';
    if (!empty($startDate) && !empty($endDate)) {
        $periodeText = strtr(date('d M Y', strtotime($startDate)), ['May'=>'Mei','Aug'=>'Agu','Oct'=>'Okt','Dec'=>'Des']) . ' s/d ' . strtr(date('d M Y', strtotime($endDate)), ['May'=>'Mei','Aug'=>'Agu','Oct'=>'Okt','Dec'=>'Des']);
    } elseif (!empty($cycleTitle)) {
        $periodeText = $cycleTitle;
    }

    $snapshot = is_string($template_snapshot) ? json_decode($template_snapshot, true) : ($template_snapshot ?: []);
    $sections = $snapshot['sections'] ?? [];
    $items    = $snapshot['items'] ?? [];

    $itemsBySection = [];
    foreach ($items as $item) {
        $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
        $itemsBySection[$secId][] = $item;
    }

    $weightedItems = [];
    $singleItems   = [];

    foreach ($items as $it) {
        $itType = strtoupper($it['input_type'] ?? '');
        if ($itType === 'WEIGHTED_CHECKLIST') {
            $weightedItems[] = $it;
        } elseif ($itType === 'SINGLE_CHECKLIST') {
            $singleItems[] = $it;
        }
    }

    // Fallback if input_type wasn't tagged
    if (empty($weightedItems) || empty($singleItems)) {
        foreach ($sections as $sec) {
            $sType = strtoupper($sec['section_type'] ?? '');
            $sId   = (int) ($sec['md_appraisal_section_id'] ?? 0);
            $sList = $itemsBySection[$sId] ?? [];
            if ($sType === 'WEIGHTED_CHECKLIST' && empty($weightedItems)) {
                $weightedItems = $sList;
            } elseif ($sType === 'SINGLE_CHECKLIST' && empty($singleItems)) {
                $singleItems = $sList;
            }
        }
    }

    $backUrl = base_url('sas/penilaian');
    if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'monitoring-penilaian') !== false || strpos($_SERVER['HTTP_REFERER'], 'performance-appraisal') !== false)) {
        $backUrl = base_url('sas/monitoring-penilaian');
    }

    // Helper to clean action text from leading numbering/lettering (e.g., 'a. ', '1. ')
    $cleanActionText = function (string $text): string {
        return preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($text));
    };

    // Track indicators for Detail Kinerja & Pencapaian Kompetensi
    $itemsNilai3             = [];
    $itemsNilai1             = [];
    $itemsKompetensiTambahan = [];

    // Totals for Rekap
    $totBobot = 0.0;
    $totSkor  = 0.0;
    $totNilai = 0.0;
?>

<div class="card-body card-main ap-wrap pb-5">

    <!-- TOP HEADER / ACTION BAR -->
    <div class="ap-report-header action-top-bar" style="background: #e2eaf4; border: 2px solid #94a3b8; border-radius: 4px; padding: 12px 20px;">
        <div>
            <a href="<?= $backUrl ?>" class="btn btn-sm btn-close-preview">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Tugas
            </a>
        </div>
        <div class="text-center flex-grow-1 mx-3">
            <h5 class="ap-report-title" style="font-size: 16px; font-weight: 800; color: #0f172a; letter-spacing: 0.3px;">
                <i class="far fa-file-alt mr-2 text-primary"></i>Laporan Akhir Penilaian Kinerja (Operator / Pelaksana)
            </h5>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-close-preview" onclick="showScoreComparisonModal('<?= (int) $appraisal_id ?>')">
                <i class="fas fa-history mr-1"></i> Riwayat Komparasi Skor
            </button>
            <a href="<?= base_url('sas/penilaian/exportPDF/' . (int) $appraisal_id) ?>" target="_blank" class="btn btn-sm ap-btn-primary" style="height: 34px; padding: 4px 14px; border-radius: 4px; font-size: 13px; font-weight: 700;">
                <i class="fas fa-file-pdf mr-1"></i> Unduh PDF
            </a>
        </div>
    </div>

    <!-- 1. INFORMASI KARYAWAN & SIKLUS EVALUASI -->
    <div class="ap-card mb-4">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <div class="ap-card-title-group d-flex align-items-center">
                <h5 class="ap-card-title d-inline-block mb-0 mr-2">
                    <i class="far fa-file-alt text-primary mr-2" aria-hidden="true"></i>I. Informasi Karyawan &amp; Siklus Evaluasi PA
                </h5>
                <span class="ap-mode is-emp">STATUS: FINALIZED</span>
            </div>
        </div>
        <div class="ap-card-body">
            <div class="ap-info-grid">
                <div class="ap-info-item">
                    <div class="ap-info-label">NIK / Nama</div>
                    <div class="ap-info-value highlight"><?= esc($employee['nik'] ?? '-') ?> &middot; <?= esc(mb_strtoupper($employee['name'] ?? '-', 'UTF-8')) ?></div>
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
                    <div class="ap-info-label"><i class="far fa-calendar-alt mr-1"></i> Periode Evaluasi</div>
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
                    <div class="ap-info-label">Peninjau</div>
                    <div class="ap-info-value"><?= esc(mb_strtoupper($gm['name'] ?? '-', 'UTF-8')) ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Status Penilaian</div>
                    <div class="ap-info-value font-weight-bold">
                        <?php
                            $curSt = $appraisal->status ?? 'FINALIZED';
                            $statusLabel = \App\Models\M_Appraisal::getStatusLabel($curSt);
                        ?>
                        <?= esc($statusLabel) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. REKAP PENILAIAN KINERJA -->
    <div class="ap-card mb-4">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center" style="gap: 12px;">
                <h5 class="ap-card-title mb-0">II. Penilaian Kinerja</h5>
                <span class="ap-category-badge">Sangat Baik / Baik / Kurang</span>
            </div>
            <span class="text-muted font-weight-bold" style="font-size: 13px;">*Beri tanda centang pada kolom yang ingin diisi</span>
        </div>
        <div class="table-responsive ap-table-wrapper">
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 50%;">Aspek Penilaian</th>
                        <th style="width: 15%; text-align: center;">Bobot</th>
                        <th style="width: 15%; text-align: center;">Skor</th>
                        <th style="width: 15%; text-align: center;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($weightedItems)): ?>
                        <?php foreach ($weightedItems as $idx => $item): ?>
                            <?php
                                $itemId = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                                $cfg    = (array) ($item['config'] ?? []);
                                $title  = !empty($item['title']) ? $item['title'] : ($cfg['aspect'] ?? ('Aspek ' . ($idx + 1)));
                                $bobot  = (float) ($item['weight'] ?? $cfg['bobot'] ?? 1.0);
                                if ($bobot <= 0) $bobot = 1.0;

                                $resp = $responses_by_item_id[$itemId] ?? null;
                                $effectiveScore = null;

                                if ($resp) {
                                    if (!empty($resp->gm_response)) {
                                        $gmPayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                                        if (isset($gmPayload['score']) && $gmPayload['score'] !== '') {
                                            $effectiveScore = (float) $gmPayload['score'];
                                        }
                                    }
                                    if ($effectiveScore === null && !empty($resp->evaluator_response)) {
                                        $evalPayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                        if (isset($evalPayload['score']) && $evalPayload['score'] !== '') {
                                            $effectiveScore = (float) $evalPayload['score'];
                                        }
                                    }
                                    if ($effectiveScore === null && $resp->score !== null) {
                                        $effectiveScore = (float) $resp->score;
                                    }
                                }

                                $nilai = ($effectiveScore !== null) ? ($bobot * $effectiveScore) : null;

                                $totBobot += $bobot;
                                if ($effectiveScore !== null) {
                                    $totSkor  += $effectiveScore;
                                    $totNilai += $nilai;

                                    $cleaned = $cleanActionText($title);
                                    if ($effectiveScore >= 3.0) {
                                        $itemsNilai3[] = $cleaned;
                                    } elseif ($effectiveScore <= 1.0) {
                                        $itemsNilai1[] = $cleaned;
                                    }
                                }
                            ?>
                            <tr>
                                <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                    <?= $idx + 1 ?>
                                </td>
                                <td class="align-middle font-weight-600 text-dark">
                                    <?= esc($title) ?>
                                    <?php if (!empty($item['description'])): ?>
                                        <small class="text-muted d-block"><?= esc($item['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-dark">
                                    <?= round($bobot) == $bobot ? (int)$bobot : number_format($bobot, 1) ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-dark">
                                    <?= $effectiveScore !== null ? (round($effectiveScore) == $effectiveScore ? (int)$effectiveScore : number_format($effectiveScore, 1)) : '-' ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-primary">
                                    <?= $nilai !== null ? (round($nilai) == $nilai ? (int)$nilai : number_format($nilai, 1)) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- TOTAL ROW -->
                        <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                            <td colspan="2" class="text-center align-middle text-uppercase" style="letter-spacing: 0.05em; font-size: 13px;">
                                TOTAL
                            </td>
                            <td class="text-center align-middle">
                                <?= round($totBobot) == $totBobot ? (int)$totBobot : number_format($totBobot, 1) ?>
                            </td>
                            <td class="text-center align-middle">
                                <?= round($totSkor) == $totSkor ? (int)$totSkor : number_format($totSkor, 1) ?>
                            </td>
                            <td class="text-center align-middle text-primary">
                                <?= round($totNilai) == $totNilai ? (int)$totNilai : number_format($totNilai, 1) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Tidak ada data aspek penilaian.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. KRITERIA TAMBAHAN -->
    <div class="ap-card mb-4">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center" style="gap: 12px;">
                <h5 class="ap-card-title mb-0">III. Penilaian Tambahan</h5>
                <span class="ap-category-badge">Ya / Tidak</span>
            </div>
            <span class="text-muted font-weight-bold" style="font-size: 13px;">*Beri tanda centang pada perilaku yang ditunjukkan</span>
        </div>
        <div class="table-responsive ap-table-wrapper">
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 80%;">Kriteria</th>
                        <th style="width: 15%; text-align: center;">Ya</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($singleItems)): ?>
                        <?php foreach ($singleItems as $idx => $item): ?>
                            <?php
                                $itemId = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                                $title  = !empty($item['title']) ? $item['title'] : ('Kriteria ' . ($idx + 1));

                                $resp = $responses_by_item_id[$itemId] ?? null;
                                $isChecked = false;
                                $otherText = '';

                                if ($resp) {
                                    $activePayload = [];
                                    if (!empty($resp->gm_response)) {
                                        $activePayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                                    } elseif (!empty($resp->evaluator_response)) {
                                        $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                    }

                                    if (isset($activePayload['boolean_value'])) {
                                        $isChecked = in_array(strtoupper((string) $activePayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                                    } elseif (!empty($activePayload['checked_actions'])) {
                                        $isChecked = true;
                                    }

                                    if (!empty($activePayload['other_text'])) {
                                        $otherText = (string) $activePayload['other_text'];
                                    }
                                }

                                if ($isChecked) {
                                    $itemsKompetensiTambahan[] = $cleanActionText($title);
                                }

                                $isOtherRow = (stripos(trim($title), 'lainnya') === 0 || stripos(trim($title), 'lain-lain') === 0);
                            ?>
                            <tr>
                                <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                    <?= $idx + 1 ?>
                                </td>
                                <td class="align-middle">
                                    <?php if ($isOtherRow): ?>
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                            <span class="font-weight-600 text-dark">Lainnya :</span>
                                            <span class="font-italic text-dark"><?= !empty($otherText) ? esc($otherText) : '-' ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="font-weight-600 text-dark"><?= esc($title) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle py-3">
                                    <span class="operator-check-badge <?= $isChecked ? 'is-yes' : 'is-no' ?>">
                                        <?= $isChecked ? 'v' : '-' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                Tidak ada data kriteria tambahan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- IV. REKAP PENILAIAN -->
    <div class="ap-card mb-4">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <h5 class="ap-card-title mb-0">IV. Rekap Penilaian</h5>
        </div>
        <div class="table-responsive ap-table-wrapper">
            <table class="ap-table mb-0">
                <tbody>
                    <tr>
                        <td style="width: 25%; font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">
                            Sangat Baik
                        </td>
                        <td style="width: 75%; vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                            <?php if (!empty($itemsNilai3)): ?>
                                <ul class="mb-0 pl-3">
                                    <?php foreach ($itemsNilai3 as $idx => $n3Text): ?>
                                        <li class="mb-1 text-dark"><?= esc($n3Text) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada indikator dengan nilai 3</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">
                            Kurang
                        </td>
                        <td style="vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                            <?php if (!empty($itemsNilai1)): ?>
                                <ul class="mb-0 pl-3">
                                    <?php foreach ($itemsNilai1 as $idx => $n1Text): ?>
                                        <li class="mb-1 text-dark"><?= esc($n1Text) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada indikator dengan nilai 1</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top;">
                            KOMPETENSI TAMBAHAN
                        </td>
                        <td style="vertical-align: top; line-height: 1.6;">
                            <?php if (!empty($itemsKompetensiTambahan)): ?>
                                <ul class="mb-0 pl-3">
                                    <?php foreach ($itemsKompetensiTambahan as $idx => $ktText): ?>
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

    <!-- V. CATATAN & FEEDBACK -->
    <div class="ap-card mb-4">
        <div class="ap-card-head">
            <h5 class="ap-card-title">V. Catatan</h5>
        </div>
        <div class="ap-card-body p-0">
            <div class="ap-feedback-section">
                <!-- Catatan Evaluator -->
                <div class="ap-feedback-card">
                    <div class="ap-feedback-item">
                        <div class="ap-feedback-q">
                            1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                        </div>
                        <div class="ap-feedback-block"><?= !empty($appraisal->evaluator_comment) ? esc(trim($appraisal->evaluator_comment)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                    </div>

                    <div class="ap-feedback-item">
                        <div class="ap-feedback-q">
                            2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                        </div>
                        <div class="ap-feedback-block"><?= !empty($appraisal->development_plan) ? esc(trim($appraisal->development_plan)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                    </div>
                </div>

                <?php if (!empty($appraisal->employee_comment)): ?>
                <!-- Catatan Karyawan / Duta -->
                <div class="ap-feedback-card">
                    <div class="ap-feedback-header">
                        <i class="fas fa-signature text-success mr-1"></i>
                        <span>Tanggapan / Catatan Konfirmasi Karyawan (<?= esc(mb_strtoupper($employee['name'] ?? 'Karyawan', 'UTF-8')) ?>)</span>
                    </div>
                    <div class="ap-feedback-item">
                        <div class="ap-feedback-block"><?= esc(trim($appraisal->employee_comment)) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- VI. RINGKASAN SKOR AKHIR -->
    <div class="ap-card mb-4">
        <div class="ap-card-head">
            <h5 class="ap-card-title">VI. Ringkasan Skor Akhir</h5>
        </div>
        <div class="ap-card-body p-0">
            <div class="ap-score-summary-grid">
                <div class="ap-score-col">
                    <div class="ap-summary-label">Skor Akhir Performa</div>
                    <div class="ap-summary-score">
                        <?= number_format((float) ($totNilai), 0) ?>
                    </div>
                </div>
                <div class="ap-score-col">
                    <div class="ap-summary-label">Predikat Penilaian</div>
                    <div>
                        <span class="ap-rating ap-summary-rating r-good">
                            <?= esc($appraisal->rating ?? '-') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- AUDIT LOG MODAL -->
<div class="modal fade" id="modal_audit_log" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 14px; border: 0;">
            <div class="modal-header" style="border-bottom: 1px solid var(--ap-line);">
                <h5 class="modal-title font-weight-bold" style="color: var(--ap-ink); font-size: 16px;">
                    <i class="fas fa-history mr-2 text-primary"></i>Riwayat Alur Penilaian (Audit Trail)
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div id="audit_timeline_container">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2 mb-0">Memuat riwayat log...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ap-line);">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var mp = document.querySelector('.main-panel');
    if (mp) mp.classList.remove('is-loading');
})();

$(document).ready(function () {
    $('.main-panel').removeClass('is-loading');
    $('.new_form, .btn_requery, .card-header .float-right').hide();
    var appraisalId = <?= (int) $appraisal_id ?>;

    window.showAuditLogModal = function (id) {
        var targetId = id || appraisalId;
        $('#modal_audit_log').modal('show');
        $('#audit_timeline_container').html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2 mb-0">Memuat riwayat log...</p>
            </div>
        `);

        $.ajax({
            url: '<?= base_url('penilaian/auditLogs') ?>/' + targetId,
            type: 'GET',
            dataType: 'JSON',
            success: function (res) {
                var logs = [];
                if (res && res[0] && res[0].data) {
                    logs = res[0].data;
                } else if (res && res.data) {
                    logs = res.data;
                }

                if (!logs || logs.length === 0) {
                    $('#audit_timeline_container').html(`
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <p class="mb-0">Belum ada riwayat aktivitas untuk form penilaian ini.</p>
                        </div>
                    `);
                    return;
                }

                var html = '<ul class="timeline" style="list-style: none; padding-left: 0;">';
                $.each(logs, function (i, log) {
                    var actionTitle = log.action || '-';
                    var timeStr = log.created_at ? log.created_at : '-';
                    var actor = log.actor_name ? (log.actor_name + (log.actor_username ? ' (' + log.actor_username + ')' : '')) : ('User ID: ' + (log.actor_id || '-'));
                    var notes = log.notes || '-';
                    var statusChange = (log.from_status || '-') + ' &rarr; ' + (log.to_status || '-');

                    html += `
                        <li class="timeline-item mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-primary font-weight-bold text-uppercase px-2 py-1">${actionTitle}</span>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i>${timeStr}</small>
                            </div>
                            <div class="small text-muted mb-1"><strong>Aktor:</strong> ${actor}</div>
                            <div class="small text-muted mb-1"><strong>Transisi Status:</strong> <span class="text-dark font-weight-bold">${statusChange}</span></div>
                            <div class="small p-2 bg-light rounded border text-dark mt-1">${notes}</div>
                        </li>
                    `;
                });
                html += '</ul>';

                $('#audit_timeline_container').html(html);
            },
            error: function () {
                $('#audit_timeline_container').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0">Gagal memuat log riwayat aktivitas.</p>
                    </div>
                `);
            }
        });
    };
});
</script>

<?= $this->endSection() ?>
