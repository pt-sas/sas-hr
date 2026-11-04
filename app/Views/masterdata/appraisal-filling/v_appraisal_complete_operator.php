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
.ap-report-header {
    background: #ffffff;
    border: 1px solid var(--ap-line);
    border-radius: var(--ap-radius);
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.ap-report-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--ap-ink);
    letter-spacing: 0.04em;
    margin: 0;
    text-transform: uppercase;
}
.ap-feedback-block {
    background: var(--ap-surface-2);
    border: 1px solid var(--ap-line);
    border-radius: 8px;
    padding: 14px 16px;
    min-height: 60px;
    font-size: 13.5px;
    color: var(--ap-ink-2);
    line-height: 1.5;
    white-space: pre-wrap;
}
.ap-feedback-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ap-muted);
    margin-bottom: 6px;
}
.operator-check-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
}
.operator-check-badge.is-yes {
    background: #eff6ff;
    border: 1.5px solid #3b82f6;
    color: #2563eb;
}
.operator-check-badge.is-no {
    color: #94a3b8;
}
</style>

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

    // Totals for Rekap
    $totBobot = 0.0;
    $totSkor  = 0.0;
    $totNilai = 0.0;
?>

<div class="card-body card-main ap-wrap pb-5">

    <!-- TOP HEADER / ACTION BAR -->
    <div class="ap-report-header action-top-bar">
        <div>
            <a href="<?= $backUrl ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Tugas
            </a>
        </div>
        <div class="text-center flex-grow-1 mx-3">
            <h5 class="ap-report-title">
                </i>Laporan Akhir Penilaian Kinerja (Operator / Pelaksana)
            </h5>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showScoreComparisonModal('<?= (int) $appraisal_id ?>')">
                <i class="fas fa-history mr-1"></i> Riwayat Komparasi Skor
            </button>
            <a href="<?= base_url('sas/penilaian/exportPDF/' . (int) $appraisal_id) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-pdf mr-1"></i> Unduh PDF
            </a>
        </div>
    </div>

    <!-- 1. INFORMASI KARYAWAN & SIKLUS EVALUASI -->
    <div class="ap-card mb-4">
        <div class="ap-card-head">
            <h5 class="ap-card-title">Informasi Karyawan &amp; Siklus Evaluasi</h5>
        </div>
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
                            $curSt = $appraisal->status ?? 'FINALIZED';
                            $statusLabel = \App\Models\M_Appraisal::getStatusLabel($curSt);
                        ?>
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i><?= esc($statusLabel) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. REKAP PENILAIAN KINERJA -->
    <div class="ap-card mb-4">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <h5 class="ap-card-title mb-0">Rekap Penilaian Kinerja</h5>
            <span class="badge badge-light border text-dark font-weight-bold" style="font-size: 11px;">Skala 1 - 3</span>
        </div>
        <div class="table-responsive">
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
            <h5 class="ap-card-title mb-0">Kriteria Tambahan</h5>
            <small class="text-muted">Kriteria inisiatif dan kontribusi kerja tambahan</small>
        </div>
        <div class="table-responsive">
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

    <!-- 4. CATATAN & FEEDBACK -->
    <div class="ap-card mb-4">
        <div class="ap-card-head">
            <h5 class="ap-card-title">4. Catatan</h5>
        </div>
        <div class="ap-card-body p-4">
            <div class="form-group mb-3">
                <label class="font-weight-bold text-dark small mb-2 d-block">
                    1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                </label>
                <div class="p-3 rounded bg-light border text-dark" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;">
                    <?= !empty($appraisal->evaluator_comment) ? esc($appraisal->evaluator_comment) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?>
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="font-weight-bold text-dark small mb-2 d-block">
                    2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                </label>
                <div class="p-3 rounded bg-light border text-dark" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;">
                    <?= !empty($appraisal->development_plan) ? esc($appraisal->development_plan) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?>
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
