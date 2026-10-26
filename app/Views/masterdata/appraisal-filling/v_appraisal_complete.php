<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<style>
@media print {
    .main-header, .sidebar, .breadcrumbs, .page-header, .action-buttons-bar, .modal, .badge-action, .btn {
        display: none !important;
    }
    .main-panel {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
    }
    .badge {
        border: 1px solid #999 !important;
        color: #000 !important;
        background: transparent !important;
    }
}
</style>

<div class="card-body card-main pb-5">

    <!-- ALL STAGES COMPLETED INDICATOR -->
    <div class="card border mb-4 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center text-center">
                <div class="flex-fill">
                    <span class="badge badge-success rounded-circle p-2 mb-1"><i class="fas fa-check"></i></span>
                    <small class="d-block font-weight-bold text-success">1. Pengisian Penilai</small>
                    <small class="text-muted"><?= esc($evaluator['name'] ?? 'Atasan') ?></small>
                </div>
                <div class="border-top border-success flex-fill mx-2"></div>
                <div class="flex-fill">
                    <span class="badge badge-success rounded-circle p-2 mb-1"><i class="fas fa-check"></i></span>
                    <small class="d-block font-weight-bold text-success">2. Review GM</small>
                    <small class="text-muted"><?= esc($gm['name'] ?? 'General Manager') ?></small>
                </div>
                <div class="border-top border-success flex-fill mx-2"></div>
                <div class="flex-fill">
                    <span class="badge badge-success rounded-circle p-2 mb-1"><i class="fas fa-check"></i></span>
                    <small class="d-block font-weight-bold text-success">3. TTD Karyawan</small>
                    <small class="text-muted"><?= esc($employee['name'] ?? 'Karyawan') ?></small>
                </div>
                <div class="border-top border-success flex-fill mx-2"></div>
                <div class="flex-fill">
                    <span class="badge badge-success rounded-circle p-2 mb-1"><i class="fas fa-check-circle"></i></span>
                    <small class="d-block font-weight-bold text-success">4. Final</small>
                    <small class="text-muted">Tersimpan</small>
                </div>
            </div>
        </div>
    </div>

    <!-- OFFICIAL RAPOR HEADER -->
    <div class="card border mb-4" id="printable_scorecard">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h5 class="font-weight-bold text-dark mb-0">
                    <i class="fas fa-file-invoice mr-2 text-primary"></i>RAPOR HASIL EVALUASI KINERJA (SCORECARD)
                </h5>
                <small class="text-muted">
                    Siklus: <strong><?= esc(is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-')) ?></strong>
                </small>
            </div>
            <span class="badge badge-success px-3 py-2 font-weight-bold text-uppercase" style="font-size: 13px;">
                <i class="fas fa-check-double mr-1"></i>STATUS: FINALIZED
            </span>
        </div>

        <div class="card-body">
            <!-- EMPLOYEE & STRUCTURAL DETAILS -->
            <div class="row mb-4 pb-3 border-bottom">
                <div class="col-md-6 border-right">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 130px;" class="text-muted">NIK / Nama</td>
                            <td class="font-weight-bold">: <?= esc($employee['nik'] ?? '-') ?> - <?= esc($employee['name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Divisi / Posisi</td>
                            <td>: <?= esc($employee['division_name'] ?? '-') ?> / <?= esc($employee['position_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Level / Cabang</td>
                            <td>: <?= esc($employee['level_name'] ?? '-') ?> / <?= esc($employee['branch_name'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 150px;" class="text-muted">Atasan Penilai</td>
                            <td class="font-weight-bold">: <?= esc($evaluator['name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Reviewer (GM)</td>
                            <td>: <?= !empty($appraisal->snapshot_gm_id) ? esc($gm['name'] ?? '-') : '<span class="text-muted font-italic">Tidak diperlukan (Penilaian Direksi Langsung)</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tgl Finalisasi Resmi</td>
                            <td>: <?= !empty($appraisal->finalized_at) ? date('d M Y H:i', strtotime($appraisal->finalized_at)) : '-' ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- SCORE & RATING SUMMARY BANNER -->
            <div class="row align-items-center mb-4 p-3 bg-light rounded border mx-0">
                <div class="col-md-6 text-center border-right">
                    <small class="text-muted text-uppercase font-weight-bold d-block mb-1">SKOR AKHIR PERFORMA</small>
                    <h1 class="font-weight-bold text-primary mb-0" style="font-size: 2.8rem;">
                        <?= number_format((float) ($appraisal->final_score ?? 0), 2) ?>
                        <small class="text-muted" style="font-size: 1.2rem;">/ 4.00</small>
                    </h1>
                </div>
                <div class="col-md-6 text-center mt-3 mt-md-0">
                    <small class="text-muted text-uppercase font-weight-bold d-block mb-1">PREDIKAT EVALUASI RESMI</small>
                    <h3 class="font-weight-bold text-success mb-1">
                        <?= esc($appraisal->rating ?? 'Baik (Meets Expectations)') ?>
                    </h3>
                    <small class="text-muted">Kategori evaluasi berdasarkan akumulasi pembobotan standar</small>
                </div>
            </div>

            <!-- SECTIONS & CRITERIA BREAKDOWN -->
            <?php
                $sections = $template_snapshot['sections'] ?? [];
                $items    = $template_snapshot['items'] ?? [];

                $itemsBySection = [];
                foreach ($items as $item) {
                    $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
                    $itemsBySection[$secId][] = $item;
                }
            ?>

            <h6 class="font-weight-bold text-dark mb-3">
                <i class="fas fa-list-check mr-2 text-primary"></i>Rincian Penilaian Per Kriteria
            </h6>

            <?php if (!empty($sections)): ?>
                <?php foreach ($sections as $sIdx => $sec): ?>
                    <?php
                        $secId    = (int) $sec['md_appraisal_section_id'];
                        $secItems = $itemsBySection[$secId] ?? [];
                    ?>
                    <div class="card border mb-3">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold text-dark">
                                Section <?= $sIdx + 1 ?>: <?= esc($sec['name']) ?>
                            </span>
                            <span class="badge badge-light border">Bobot Section: <strong><?= $sec['weight'] ?>%</strong></span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th>Kriteria / Kompetensi</th>
                                        <th style="width: 100px;" class="text-center">Tipe</th>
                                        <th style="width: 80px;" class="text-center">Bobot</th>
                                        <th style="width: 120px;" class="text-center">Skor / Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($secItems)): ?>
                                        <?php foreach ($secItems as $iIdx => $item): ?>
                                            <?php
                                                $itemId = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id']);
                                                $resp   = $responses_by_item_id[$itemId] ?? null;
                                                $score  = $resp ? ($resp->score !== null ? number_format((float) $resp->score, 2) : '-') : '-';
                                                $isBooleanType = strtoupper($item['input_type'] ?? '') === 'BOOLEAN';
                                                $isProven = false;
                                                if ($isBooleanType) {
                                                    $evalP = ($resp && !empty($resp->evaluator_response)) ? (is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response) : [];
                                                    $gmP   = ($resp && !empty($resp->gm_response)) ? (is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response) : [];
                                                    $bVal  = $gmP['boolean_value'] ?? $evalP['boolean_value'] ?? null;
                                                    $isProven = in_array(strtoupper((string) $bVal), ['Y', '1', 'TRUE'], true);
                                                }
                                            ?>
                                            <tr>
                                                <td class="text-center align-middle"><?= $iIdx + 1 ?></td>
                                                <td class="align-middle">
                                                    <strong><?= esc($item['title']) ?></strong>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <br><small class="text-muted"><?= esc($item['description']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <span class="badge badge-light border"><?= esc($item['input_type']) ?></span>
                                                </td>
                                                <td class="text-center align-middle"><?= $item['weight'] ?>%</td>
                                                <td class="text-center align-middle font-weight-bold">
                                                    <?php if ($isBooleanType): ?>
                                                        <?php if ($isProven): ?>
                                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Terbukti</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-light border text-muted px-2 py-1">Tidak Terbukti</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-primary"><?= $score ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- OFFICIAL SIGN-OFF TRAIL & FEEDBACK -->
            <div class="card border mt-4">
                <div class="card-header bg-white">
                    <h6 class="font-weight-bold text-dark mb-0">
                        <i class="fas fa-signature mr-2 text-primary"></i>Catatan & Lembar Pengesahan Digital
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 1. ATASAN PENILAI -->
                        <div class="col-md-4 mb-3 mb-md-0 border-right">
                            <h6 class="font-weight-bold text-primary mb-2">
                                <i class="fas fa-user-edit mr-1"></i>Atasan Penilai
                            </h6>
                            <p class="font-weight-bold mb-1"><?= esc($evaluator['name'] ?? '-') ?></p>
                            <small class="text-muted d-block mb-2">
                                <i class="far fa-clock mr-1"></i>Submit: <?= !empty($appraisal->evaluator_submitted_at) ? date('d M Y H:i', strtotime($appraisal->evaluator_submitted_at)) : '-' ?>
                            </small>
                            <label class="small text-muted font-weight-bold">Feedback / Catatan:</label>
                            <div class="p-2 bg-light rounded border small text-dark mb-2" style="min-height: 60px;">
                                <?= esc($appraisal->evaluator_comment ?? 'Tidak ada catatan.') ?>
                            </div>
                            <?php if (!empty($appraisal->development_plan)): ?>
                                <label class="small text-muted font-weight-bold">Development Plan:</label>
                                <div class="p-2 bg-light rounded border small text-dark" style="min-height: 40px;">
                                    <?= esc($appraisal->development_plan) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 2. REVIEWER GM -->
                        <div class="col-md-4 mb-3 mb-md-0 border-right">
                            <h6 class="font-weight-bold text-info mb-2">
                                <i class="fas fa-user-tie mr-1"></i>General Manager / Reviewer
                            </h6>
                            <?php if (!empty($appraisal->snapshot_gm_id)): ?>
                                <p class="font-weight-bold mb-1"><?= esc($gm['name'] ?? '-') ?></p>
                                <small class="text-muted d-block mb-2">
                                    <i class="far fa-clock mr-1"></i>Review: <?= !empty($appraisal->gm_reviewed_at) ? date('d M Y H:i', strtotime($appraisal->gm_reviewed_at)) : '-' ?>
                                </small>
                                <label class="small text-muted font-weight-bold">Catatan Reviewer:</label>
                                <div class="p-2 bg-light rounded border small text-dark" style="min-height: 60px;">
                                    <?= esc($appraisal->gm_comment ?? 'Tidak ada catatan arahan GM.') ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted font-italic mb-1">Tidak Diperlukan</p>
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>Penilaian langsung oleh Direksi
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- 3. KARYAWAN -->
                        <div class="col-md-4">
                            <h6 class="font-weight-bold text-success mb-2">
                                <i class="fas fa-user-check mr-1"></i>Karyawan Yang Dinilai
                            </h6>
                            <p class="font-weight-bold mb-1"><?= esc($employee['name'] ?? '-') ?></p>
                            <small class="text-muted d-block mb-2">
                                <i class="far fa-clock mr-1"></i>TTD: <?= !empty($appraisal->employee_acknowledged_at) ? date('d M Y H:i', strtotime($appraisal->employee_acknowledged_at)) : '-' ?>
                            </small>
                            <label class="small text-muted font-weight-bold">Catatan Karyawan:</label>
                            <div class="p-2 bg-light rounded border small text-dark" style="min-height: 60px;">
                                <?= esc($appraisal->employee_comment ?? 'Telah membaca dan menyetujui hasil evaluasi.') ?>
                            </div>
                            <div class="text-center mt-3">
                                <span class="badge badge-success px-3 py-2">
                                    <i class="fas fa-check-circle mr-1"></i>DITANDATANGANI SECARA DIGITAL
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ACTION BUTTONS BAR (HIDDEN IN PRINT) -->
    <div class="d-flex justify-content-between align-items-center mb-5 action-buttons-bar">
        <div>
            <?php 
                $backUrl = base_url('penilaian');
                if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'monitoring-penilaian') !== false || strpos($_SERVER['HTTP_REFERER'], 'performance-appraisal') !== false)) {
                    $backUrl = base_url('monitoring-penilaian');
                }
            ?>
            <a href="<?= $backUrl ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
        </div>

        <div>
            <button type="button" class="btn btn-outline-secondary mr-2" onclick="showAuditLogModal(<?= (int) $appraisal_id ?>)">
                <i class="fas fa-history mr-1"></i>Riwayat Audit
            </button>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print mr-1"></i>Cetak Rapor / Print
            </button>
        </div>
    </div>

</div>

<!-- AUDIT LOG MODAL -->
<div class="modal fade" id="modal_audit_log" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold text-dark">
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
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
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

                var html = '<ul class="timeline">';
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
