<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?php $inCard = in_array($action_menu ?? '', ['F', 'T', 'R'], true); ?>

<style>
/* ==========================================================================
   APPRAISAL MONITORING DASHBOARD
   Uses native Atlantis Pro tokens (#2a2f5b, #1572e8, #ebedf2) & tabular numerals
   ========================================================================== */

.apd-wrap {
    color: #2a2f5b;
    font-variant-numeric: tabular-nums;
}
.apd-wrap *, .apd-wrap *::before, .apd-wrap *::after {
    box-sizing: border-box;
}
.apd-wrap [hidden] {
    display: none !important;
}

/* Surface containers */
.apd-panel {
    background: #ffffff;
    border: 1px solid #ebedf2;
    border-radius: 6px;
    margin-bottom: 1.25rem;
}
.apd-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.85rem 1.25rem;
    background: #fbfcfd;
    border-bottom: 1px solid #ebedf2;
}
.apd-panel-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #2a2f5b;
    margin: 0;
}
.apd-panel-sub {
    font-size: 12px;
    color: #6b6d7e;
    margin: 2px 0 0 0;
}

/* 1. Filter & Scope Control Bar */
.apd-toolbar {
    padding: 1rem 1.25rem;
}
.apd-field label {
    display: block;
    font-size: 11.5px;
    font-weight: 600;
    color: #575962;
    margin-bottom: 4px;
}
.apd-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.apd-cycle-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 0.65rem 1.25rem;
    background: #f8f9fa;
    border-top: 1px solid #ebedf2;
    font-size: 12px;
    color: #575962;
}
.apd-meta-items {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem 1.5rem;
}
.apd-meta-item strong {
    color: #2a2f5b;
    font-weight: 700;
    margin-left: 4px;
}

/* 2. Unified KPI Ledger (No floating SaaS cards) */
.apd-ledger {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}
.apd-ledger-cell {
    padding: 1.1rem 1.25rem;
    border-right: 1px solid #ebedf2;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}
.apd-ledger-cell:last-child {
    border-right: 0;
}
@media (max-width: 991.98px) {
    .apd-ledger {
        grid-template-columns: repeat(2, 1fr);
    }
    .apd-ledger-cell:nth-child(2) {
        border-right: 0;
    }
    .apd-ledger-cell:nth-child(1),
    .apd-ledger-cell:nth-child(2) {
        border-bottom: 1px solid #ebedf2;
    }
}
@media (max-width: 575.98px) {
    .apd-ledger {
        grid-template-columns: 1fr;
    }
    .apd-ledger-cell {
        border-right: 0;
        border-bottom: 1px solid #ebedf2;
    }
    .apd-ledger-cell:last-child {
        border-bottom: 0;
    }
}
.apd-stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #575962;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.apd-stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex: none;
}
.apd-stat-main {
    display: flex;
    align-items: baseline;
    gap: 0.4rem;
    margin-bottom: 0.25rem;
}
.apd-stat-num {
    font-size: 1.75rem;
    font-weight: 800;
    color: #2a2f5b;
    line-height: 1.1;
    letter-spacing: -0.02em;
}
.apd-stat-unit {
    font-size: 12px;
    font-weight: 600;
    color: #6b6d7e;
}
.apd-stat-note {
    font-size: 11.5px;
    color: #6b6d7e;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Semantic high-contrast text tokens (WCAG AA >= 4.5:1 on white) */
.apd-tone-ok   { color: #15803d !important; }
.apd-tone-warn { color: #b45309 !important; }
.apd-tone-pri  { color: #1572e8 !important; }
.apd-tone-err  { color: #b91c1c !important; }

/* 3. Completion & Predicate Breakdown */
.apd-prog-track {
    height: 8px;
    border-radius: 4px;
    background: #ebedf2;
    overflow: hidden;
    display: flex;
}
.apd-prog-fill-ok {
    background: #15803d;
    height: 100%;
    transition: width 0.2s ease-out;
}
.apd-prog-fill-wip {
    background: #d97706;
    height: 100%;
    transition: width 0.2s ease-out;
}

/* Interactive Stage Breakdown Pills */
.apd-stage-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.65rem;
}
.apd-stage-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 3px 8px;
    border: 1px solid #ebedf2;
    border-radius: 4px;
    background: #fbfcfd;
    font-size: 11.5px;
    color: #575962;
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}
.apd-stage-chip:hover {
    border-color: #1572e8;
    background: #f0f6ff;
    color: #2a2f5b;
}
.apd-stage-chip b {
    color: #2a2f5b;
    font-weight: 700;
}

/* Predicate Rows (Clean tabular list instead of rainbow boxes) */
.apd-grade-list {
    margin: 0;
    padding: 0;
    list-style: none;
}
.apd-grade-row {
    display: grid;
    grid-template-columns: 110px 68px 1fr 42px;
    align-items: center;
    gap: 0.6rem;
    padding: 0.42rem 0;
    border-bottom: 1px solid #f1f3f7;
    font-size: 12px;
}
.apd-grade-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}
.apd-grade-name {
    font-weight: 600;
    color: #2a2f5b;
    display: flex;
    align-items: center;
    gap: 6px;
}
.apd-grade-range {
    font-size: 11px;
    color: #6b6d7e;
}
.apd-grade-bar {
    height: 6px;
    border-radius: 3px;
    background: #ebedf2;
    overflow: hidden;
}
.apd-grade-bar > span {
    display: block;
    height: 100%;
    width: 0%;
    transition: width 0.2s ease-out;
}
.apd-grade-count {
    text-align: right;
    font-weight: 700;
    color: #2a2f5b;
}

/* 4. Division Table */
.apd-div-scroll {
    max-height: 255px;
    overflow-y: auto;
}
.apd-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
}
.apd-table th {
    font-size: 11.5px !important;
    font-weight: 700 !important;
    color: #575962 !important;
    background: #f8f9fa !important;
    border-top: 0 !important;
    border-bottom: 1px solid #ebedf2 !important;
    padding: 0.6rem 0.9rem !important;
    vertical-align: middle !important;
    white-space: nowrap;
}
.apd-table td {
    font-size: 12.5px;
    color: #2a2f5b;
    padding: 0.65rem 0.9rem !important;
    vertical-align: middle !important;
    border-top: 1px solid #f1f3f7 !important;
}
.apd-table tbody tr:hover {
    background-color: #fbfcfd;
}

/* 5. Explorer DataTables Wrapper Spacing */
.apd-dt-body {
    padding: 1rem 1.25rem;
}
.apd-dt-body .dataTables_wrapper {
    padding: 0;
}
.apd-dt-body table.dataTable {
    border-collapse: collapse !important;
    margin-top: 0.75rem !important;
    margin-bottom: 0.75rem !important;
}

/* 6. Audit Trail Timeline */
.apd-timeline {
    position: relative;
    padding-left: 1.25rem;
    margin: 0;
    list-style: none;
}
.apd-timeline::before {
    content: '';
    position: absolute;
    top: 6px;
    bottom: 6px;
    left: 4px;
    width: 2px;
    background: #ebedf2;
}
.apd-tl-item {
    position: relative;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #f1f3f7;
}
.apd-tl-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: 0;
}
.apd-tl-item::before {
    content: '';
    position: absolute;
    left: -1.25rem;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #1572e8;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 1px #1572e8;
}
</style>

<div class="<?= $inCard ? 'card-body card-main' : 'page-inner pt-0' ?> apd-wrap">

    <!-- ===================================================================== -->
    <!-- 1. FILTER & CYCLE CONTEXT BAR                                         -->
    <!-- ===================================================================== -->
    <div class="apd-panel">
        <div class="apd-toolbar">
            <div class="row align-items-end">
                <!-- Cycle Selector -->
                <div class="col-lg-4 col-md-6 mb-2 mb-lg-0">
                    <div class="apd-field">
                        <label for="filter_cycle">Siklus Penilaian</label>
                        <select class="form-control form-control-sm select2" id="filter_cycle" style="width: 100%;">
                            <option value="0">Semua Siklus (Historis Keseluruhan)</option>
                            <?php foreach ($cycles as $cyc): ?>
                                <?php
                                    $cId      = $cyc['md_appraisal_cycle_id'];
                                    $cTitle   = $cyc['title'];
                                    $cStatus  = $cyc['status'];
                                    $cStart   = !empty($cyc['start_date']) ? date('d/m/Y', strtotime($cyc['start_date'])) : '-';
                                    $cEnd     = !empty($cyc['end_date']) ? date('d/m/Y', strtotime($cyc['end_date'])) : '-';
                                    $selected = ((int) $cId === (int) $default_cycle_id) ? 'selected' : '';
                                ?>
                                <option value="<?= $cId ?>" <?= $selected ?>>
                                    [<?= esc($cStatus) ?>] <?= esc($cTitle) ?> (<?= $cStart ?> - <?= $cEnd ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Division Selector -->
                <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                    <div class="apd-field">
                        <label for="filter_division">Divisi</label>
                        <select class="form-control form-control-sm select2" id="filter_division" style="width: 100%;">
                            <option value="0">Semua Divisi</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?= $div['md_division_id'] ?>"><?= esc($div['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Status Selector (Default ALL so active cycles don't appear empty) -->
                <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                    <div class="apd-field">
                        <label for="filter_status">Tahap Status Form</label>
                        <select class="form-control form-control-sm" id="filter_status">
                            <option value="ALL" selected>Semua Status</option>
                            <option value="DRAFT_EVALUATOR">Draft Atasan</option>
                            <option value="SUBMITTED_EVALUATOR">Diserahkan Atasan</option>
                            <option value="REVIEW_GM">Review GM</option>
                            <option value="PENDING_ACK">Menunggu TTD Karyawan</option>
                            <option value="FINALIZED">Final / Selesai</option>
                        </select>
                    </div>
                </div>

                <!-- Reset & Refresh Actions -->
                <div class="col-lg-2 col-md-6 mt-2 mt-lg-0">
                    <div class="apd-actions justify-content-lg-end">
                        <button type="button" class="btn btn-light btn-sm border text-secondary flex-fill" id="btn_reset_filter" title="Kembalikan filter ke pengaturan awal">
                            <i class="fas fa-undo mr-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-light btn-sm border text-secondary" id="btn_refresh_all" title="Muat ulang data" aria-label="Muat ulang data">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cycle Context Footer (Shown when a cycle is selected) -->
        <div id="cycle_banner_info" class="apd-cycle-meta" hidden>
            <div class="apd-meta-items">
                <span class="apd-meta-item">
                    <i class="far fa-calendar-alt text-muted mr-1"></i>Periode:<strong id="banner_period">-</strong>
                </span>
                <span class="apd-meta-item">
                    <i class="far fa-edit text-muted mr-1"></i>Batas Pengisian:<strong id="banner_eval_due">-</strong>
                </span>
                <span class="apd-meta-item">
                    <i class="fas fa-user-check text-muted mr-1"></i>Batas Review:<strong id="banner_gm_due">-</strong>
                </span>
                <span class="apd-meta-item">
                    <i class="fas fa-book-reader text-muted mr-1"></i>Batas Pembacaan:<strong id="banner_reading_due">-</strong>
                </span>
            </div>
            <div>
                <span class="badge badge-light border font-weight-bold px-2 py-1" id="banner_status_badge">-</span>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 2. UNIFIED EXECUTIVE SUMMARY LEDGER                                   -->
    <!-- ===================================================================== -->
    <div class="apd-panel">
        <div class="apd-ledger">
            <!-- Total Partisipan -->
            <div class="apd-ledger-cell">
                <div class="apd-stat-label">
                    <span>Total Partisipan</span>
                    <span class="apd-stat-dot" style="background: #94a3b8;"></span>
                </div>
                <div class="apd-stat-main">
                    <span class="apd-stat-num" id="kpi_total">0</span>
                    <span class="apd-stat-unit">form</span>
                </div>
                <p class="apd-stat-note">Karyawan aktif terdistribusi</p>
            </div>

            <!-- Final / Selesai -->
            <div class="apd-ledger-cell">
                <div class="apd-stat-label">
                    <span>Evaluasi Selesai (Final)</span>
                    <span class="apd-stat-dot" style="background: #15803d;"></span>
                </div>
                <div class="apd-stat-main">
                    <span class="apd-stat-num apd-tone-ok" id="kpi_finalized">0</span>
                    <span class="apd-stat-unit apd-tone-ok" id="kpi_completion_badge">(0%)</span>
                </div>
                <p class="apd-stat-note">Rapor lengkap &amp; ditandatangani</p>
            </div>

            <!-- Sedang Berjalan -->
            <div class="apd-ledger-cell">
                <div class="apd-stat-label">
                    <span>Sedang Berjalan</span>
                    <span class="apd-stat-dot" style="background: #d97706;"></span>
                </div>
                <div class="apd-stat-main">
                    <span class="apd-stat-num apd-tone-warn" id="kpi_in_progress">0</span>
                    <span class="apd-stat-unit">form</span>
                </div>
                <p class="apd-stat-note" id="kpi_in_progress_detail" title="Rincian tahapan berjalan">0 Draft · 0 GM · 0 TTD</p>
            </div>

            <!-- Rata-Rata Skor Akhir -->
            <div class="apd-ledger-cell">
                <div class="apd-stat-label">
                    <span>Rata-Rata Skor Akhir</span>
                    <span class="apd-stat-dot" style="background: #1572e8;"></span>
                </div>
                <div class="apd-stat-main">
                    <span class="apd-stat-num apd-tone-pri" id="kpi_avg_score">-</span>
                    <span class="apd-stat-unit">/ 4.00</span>
                </div>
                <p class="apd-stat-note" id="kpi_avg_grade">Skala penilaian 1.00 – 4.00</p>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 3. COMPLETION / PREDICATES & DIVISION SCOREBOARD                      -->
    <!-- ===================================================================== -->
    <div class="row">
        <!-- Left Column: Completion Progress & Predicate Distribution -->
        <div class="col-lg-5 d-flex">
            <div class="apd-panel w-100 d-flex flex-column">
                <div class="apd-panel-head">
                    <h2 class="apd-panel-title">Progres &amp; Distribusi Predikat</h2>
                    <span class="badge badge-light border font-weight-bold text-dark" id="badge_overall_percent">0.0%</span>
                </div>

                <div class="p-3 d-flex flex-column justify-content-between flex-grow-1">
                    <!-- Cumulative Progress Bar + Stage Filter Chips -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-muted">Penyelesaian Kumulatif</span>
                            <strong class="text-dark" id="txt_progress_fraction">0 / 0 Form Selesai</strong>
                        </div>
                        <div class="apd-prog-track" title="Hijau: Final, Kuning: Sedang Berjalan">
                            <div class="apd-prog-fill-ok" id="bar_overall_percent" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            <div class="apd-prog-fill-wip" id="bar_inprog_percent" style="width: 0%;"></div>
                        </div>

                        <!-- Quick Stage Filter Chips -->
                        <div class="apd-stage-bar">
                            <span class="apd-stage-chip" data-stage-filter="DRAFT_EVALUATOR" title="Klik untuk memfilter tabel ke Draft Atasan">
                                Draft Atasan: <b id="chip_cnt_draft">0</b>
                            </span>
                            <span class="apd-stage-chip" data-stage-filter="REVIEW_GM" title="Klik untuk memfilter tabel ke Review GM">
                                Review GM: <b id="chip_cnt_gm">0</b>
                            </span>
                            <span class="apd-stage-chip" data-stage-filter="PENDING_ACK" title="Klik untuk memfilter tabel ke Menunggu TTD">
                                Menunggu TTD: <b id="chip_cnt_ack">0</b>
                            </span>
                            <span class="apd-stage-chip" data-stage-filter="FINALIZED" title="Klik untuk memfilter tabel ke Final">
                                Final: <b id="chip_cnt_final">0</b>
                            </span>
                        </div>
                    </div>

                    <!-- Predicate Breakdown Table -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small font-weight-bold text-muted">Distribusi Predikat (Form Final)</span>
                            <span class="small text-muted">Jumlah</span>
                        </div>
                        <ul class="apd-grade-list">
                            <li class="apd-grade-row">
                                <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#15803d;"></span>Sangat Baik</span>
                                <span class="apd-grade-range">&ge; 3.50</span>
                                <div class="apd-grade-bar"><span id="bar_rating_sb" style="background:#15803d;"></span></div>
                                <span class="apd-grade-count" id="cnt_rating_sb">0</span>
                            </li>
                            <li class="apd-grade-row">
                                <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#1572e8;"></span>Baik</span>
                                <span class="apd-grade-range">2.75 – 3.49</span>
                                <div class="apd-grade-bar"><span id="bar_rating_b" style="background:#1572e8;"></span></div>
                                <span class="apd-grade-count" id="cnt_rating_b">0</span>
                            </li>
                            <li class="apd-grade-row">
                                <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#d97706;"></span>Cukup</span>
                                <span class="apd-grade-range">2.00 – 2.74</span>
                                <div class="apd-grade-bar"><span id="bar_rating_c" style="background:#d97706;"></span></div>
                                <span class="apd-grade-count" id="cnt_rating_c">0</span>
                            </li>
                            <li class="apd-grade-row">
                                <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#b91c1c;"></span>Kurang</span>
                                <span class="apd-grade-range">&lt; 2.00</span>
                                <div class="apd-grade-bar"><span id="bar_rating_k" style="background:#b91c1c;"></span></div>
                                <span class="apd-grade-count" id="cnt_rating_k">0</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Division Performance Breakdown -->
        <div class="col-lg-7 d-flex">
            <div class="apd-panel w-100 d-flex flex-column overflow-hidden">
                <div class="apd-panel-head">
                    <h2 class="apd-panel-title">Ringkasan Performa Per Divisi</h2>
                    <span class="badge badge-light border text-muted font-weight-normal" id="txt_div_count">0 Divisi</span>
                </div>
                <div class="table-responsive apd-div-scroll flex-grow-1">
                    <table class="table apd-table">
                        <thead class="sticky-top">
                            <tr>
                                <th>Nama Divisi</th>
                                <th class="text-center" style="width: 75px;">Total</th>
                                <th class="text-center" style="width: 80px;">Selesai</th>
                                <th style="width: 155px;">Penyelesaian</th>
                                <th class="text-center" style="width: 90px;">Rata-Rata</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_division_progress">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data divisi...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 4. FORM EXPLORER / AUDIT GRID                                         -->
    <!-- ===================================================================== -->
    <div class="apd-panel overflow-hidden mb-0">
        <div class="apd-panel-head">
            <div>
                <h2 class="apd-panel-title">Daftar Form &amp; Rapor Penilaian Karyawan</h2>
                <p class="apd-panel-sub">Telusuri status evaluasi per karyawan, nilai akhir, serta riwayat persetujuan.</p>
            </div>
        </div>

        <div class="apd-dt-body">
            <div class="table-responsive">
                <table class="table table-hover apd-table" id="table_appraisal_explorer" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 25px;">ID</th>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="width: 95px;">NIK</th>
                            <th>Nama Karyawan &amp; Jabatan</th>
                            <th>Divisi &amp; Cabang</th>
                            <th style="width: 165px;">Siklus Penilaian</th>
                            <th style="width: 165px;">Penilai / GM</th>
                            <th style="width: 105px;" class="text-center">Nilai Akhir</th>
                            <th style="width: 125px;" class="text-center">Status</th>
                            <th style="width: 90px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated by DataTables Server-Side -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- 5. AUDIT TRAIL TIMELINE MODAL                                             -->
<!-- ========================================================================= -->
<div class="modal fade" id="modal_audit_log" tabindex="-1" role="dialog" aria-labelledby="modalAuditLogTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark mb-0" id="modalAuditLogTitle" style="font-size: 15px;">
                        Riwayat Alur Penilaian (Audit Trail)
                    </h5>
                    <small class="text-muted">Catatan waktu dan transisi status evaluasi karyawan.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div id="audit_timeline_container">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat riwayat log aktivitas...
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm btn-round" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    var dtExplorer = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // =========================================================================
    // 1. INITIALIZE SERVER-SIDE DATATABLE
    // =========================================================================
    function initExplorerTable() {
        dtExplorer = $('#table_appraisal_explorer').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '<?= base_url('monitoring-penilaian/showAll') ?>',
                type: 'POST',
                data: function (d) {
                    d.cycle_id    = $('#filter_cycle').val();
                    d.division_id = $('#filter_division').val();
                    d.status      = $('#filter_status').val();
                    d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
                    return d;
                }
            },
            columns: [
                { data: 0, visible: false },
                { data: 1, className: 'text-center align-middle text-muted', orderable: false },
                { data: 2, className: 'align-middle font-weight-bold text-dark' },
                { data: 3, className: 'align-middle' },
                { data: 4, className: 'align-middle' },
                { data: 5, className: 'align-middle' },
                { data: 6, className: 'align-middle' },
                { data: 7, className: 'align-middle text-center' },
                { data: 8, className: 'align-middle text-center' },
                { data: 9, className: 'align-middle text-center', orderable: false }
            ],
            order: [[0, 'desc']],
            language: {
                emptyTable: 'Tidak ada form penilaian yang ditemukan untuk kriteria filter ini.',
                zeroRecords: 'Tidak ada data yang cocok dengan pencarian.',
                processing: '<div class="py-3 text-center text-muted"><i class="fas fa-spinner fa-spin mr-1 text-primary"></i> Memuat data form...</div>',
                search: '_INPUT_',
                searchPlaceholder: 'Cari NIK, nama, atau penilai...',
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ form',
                infoEmpty: '0 form penilaian',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });
    }

    // =========================================================================
    // 2. FETCH SUMMARY METRICS & DIVISION PROGRESS
    // =========================================================================
    function loadSummaryMetrics() {
        var cycleId    = $('#filter_cycle').val();
        var divisionId = $('#filter_division').val();

        $.ajax({
            url: '<?= base_url('monitoring-penilaian/getSummary') ?>',
            type: 'GET',
            data: {
                cycle_id: cycleId,
                division_id: divisionId
            },
            dataType: 'JSON',
            success: function (res) {
                var payload = (res && res[0] && res[0].data) ? res[0].data : (res ? (res.data || res) : {});
                var metrics = payload.metrics || {};
                var cycle   = payload.cycle || null;
                var divs    = payload.divisions || [];

                // 2.1 Cycle Context Strip
                var $banner = $('#cycle_banner_info');
                if (cycle && cycle.title) {
                    $('#banner_period').text((cycle.start_date || '-') + ' s/d ' + (cycle.end_date || '-'));
                    $('#banner_eval_due').text(cycle.deadline_filling || cycle.evaluator_due_date || '-');
                    $('#banner_gm_due').text(cycle.deadline_review || cycle.gm_due_date || '-');
                    $('#banner_reading_due').text(cycle.deadline_reading || '-');

                    var isOpen = cycle.status === 'OPEN';
                    $('#banner_status_badge')
                        .removeClass('badge-success badge-secondary badge-light')
                        .addClass(isOpen ? 'badge-success' : 'badge-secondary')
                        .text('STATUS SIKLUS: ' + cycle.status);

                    $banner.prop('hidden', false);
                } else {
                    $banner.prop('hidden', true);
                }

                // 2.2 KPI Ledger
                var totalCount = parseInt(metrics.total, 10) || 0;
                var finalCount = parseInt(metrics.finalized, 10) || 0;
                var inProgCount = parseInt(metrics.in_progress, 10) || 0;
                var draftCount = (parseInt(metrics.draft_evaluator, 10) || 0) + (parseInt(metrics.submitted_evaluator, 10) || 0);
                var gmCount    = parseInt(metrics.review_gm, 10) || 0;
                var ackCount   = parseInt(metrics.pending_ack, 10) || 0;

                $('#kpi_total').text(totalCount);
                $('#kpi_finalized').text(finalCount);

                var compPct = parseFloat(metrics.completion_percent) || 0.0;
                var wipPct  = totalCount > 0 ? Math.min(100 - compPct, (inProgCount / totalCount) * 100) : 0.0;
                $('#kpi_completion_badge').text('(' + compPct.toFixed(1) + '%)');

                $('#kpi_in_progress').text(inProgCount);
                var inProgDetail = draftCount + ' Draft Atasan · ' + gmCount + ' Review GM · ' + ackCount + ' Menunggu TTD';
                $('#kpi_in_progress_detail').text(inProgDetail).attr('title', inProgDetail);

                var avgScore = metrics.avg_final_score !== null && metrics.avg_final_score !== undefined
                    ? Number(metrics.avg_final_score).toFixed(2)
                    : '-';
                $('#kpi_avg_score').text(avgScore);

                if (avgScore !== '-') {
                    var sVal = parseFloat(avgScore);
                    var gTxt = sVal >= 3.50 ? 'Predikat: Sangat Baik' : (sVal >= 2.75 ? 'Predikat: Baik' : (sVal >= 2.00 ? 'Predikat: Cukup' : 'Predikat: Kurang'));
                    $('#kpi_avg_grade').text(gTxt);
                } else {
                    $('#kpi_avg_grade').text('Belum ada form selesai');
                }

                // 2.3 Overall Progress & Stage Chips
                $('#badge_overall_percent').text(compPct.toFixed(1) + '%');
                $('#txt_progress_fraction').text(finalCount + ' / ' + totalCount + ' Form Selesai');
                $('#bar_overall_percent').css('width', compPct + '%').attr('aria-valuenow', compPct);
                $('#bar_inprog_percent').css('width', wipPct + '%');

                $('#chip_cnt_draft').text(draftCount);
                $('#chip_cnt_gm').text(gmCount);
                $('#chip_cnt_ack').text(ackCount);
                $('#chip_cnt_final').text(finalCount);

                // 2.4 Predicate Distribution
                var rc = metrics.rating_counts || {};
                var sb = parseInt(rc.sangat_baik, 10) || 0;
                var b  = parseInt(rc.baik, 10) || 0;
                var c  = parseInt(rc.cukup, 10) || 0;
                var k  = parseInt(rc.kurang, 10) || 0;
                var ratedTotal = (sb + b + c + k) || 1;

                $('#cnt_rating_sb').text(sb);
                $('#cnt_rating_b').text(b);
                $('#cnt_rating_c').text(c);
                $('#cnt_rating_k').text(k);

                $('#bar_rating_sb').css('width', ((sb / ratedTotal) * 100) + '%');
                $('#bar_rating_b').css('width', ((b / ratedTotal) * 100) + '%');
                $('#bar_rating_c').css('width', ((c / ratedTotal) * 100) + '%');
                $('#bar_rating_k').css('width', ((k / ratedTotal) * 100) + '%');

                // 2.5 Division Progress Breakdown Table
                $('#txt_div_count').text(divs.length + ' Divisi');
                var $tbody = $('#tbody_division_progress');
                $tbody.empty();

                if (divs.length === 0) {
                    $tbody.html('<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data evaluasi divisi pada siklus ini.</td></tr>');
                } else {
                    $.each(divs, function (i, d) {
                        var pVal = parseFloat(d.percent || 0.0);
                        var avgDivScore = d.avg_score !== null && d.avg_score !== undefined ? Number(d.avg_score).toFixed(2) : '-';
                        var avgColor = avgDivScore !== '-' ? 'apd-tone-pri font-weight-bold' : 'text-muted';

                        var rowHtml = '<tr>' +
                            '<td class="font-weight-bold">' + esc(d.division_name) + '</td>' +
                            '<td class="text-center text-muted">' + (d.total || 0) + '</td>' +
                            '<td class="text-center apd-tone-ok font-weight-bold">' + (d.finalized || 0) + '</td>' +
                            '<td>' +
                                '<div class="d-flex align-items-center" style="gap: 8px;">' +
                                    '<div class="apd-prog-track flex-grow-1" style="height: 6px;">' +
                                        '<div class="apd-prog-fill-ok" style="width:' + pVal + '%;"></div>' +
                                    '</div>' +
                                    '<span class="font-weight-bold text-muted" style="font-size:11px; width: 34px; text-align: right;">' + pVal.toFixed(0) + '%</span>' +
                                '</div>' +
                            '</td>' +
                            '<td class="text-center ' + avgColor + '">' + avgDivScore + '</td>' +
                        '</tr>';
                        $tbody.append(rowHtml);
                    });
                }
            },
            error: function () {
                $('#tbody_division_progress').html('<tr><td colspan="5" class="text-center py-3 text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat ringkasan divisi.</td></tr>');
            }
        });
    }

    // =========================================================================
    // 3. EVENT HANDLERS & FILTERS
    // =========================================================================
    initExplorerTable();
    loadSummaryMetrics();

    $('#filter_cycle, #filter_division, #filter_status').on('change', function () {
        if (dtExplorer) {
            dtExplorer.ajax.reload();
        }
        loadSummaryMetrics();
    });

    // Click stage chip to filter status quickly
    $(document).on('click', '[data-stage-filter]', function () {
        var targetStatus = $(this).attr('data-stage-filter');
        if (!targetStatus) return;
        var currentVal = $('#filter_status').val();
        $('#filter_status').val(currentVal === targetStatus ? 'ALL' : targetStatus).trigger('change');
    });

    // Refresh Button
    $('#btn_refresh_all').on('click', function () {
        var $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        if (dtExplorer) {
            dtExplorer.ajax.reload(null, false);
        }
        loadSummaryMetrics();
        setTimeout(function () {
            $icon.removeClass('fa-spin');
        }, 500);
    });

    // Reset Filter Button
    $('#btn_reset_filter').on('click', function () {
        $('#filter_cycle').val('<?= (int) $default_cycle_id ?>').trigger('change.select2');
        $('#filter_division').val('0').trigger('change.select2');
        $('#filter_status').val('ALL').trigger('change');
    });

    // =========================================================================
    // 4. AUDIT TRAIL TIMELINE MODAL HANDLER
    // =========================================================================
    window.showAuditLogModal = function (appraisalId) {
        if (!appraisalId) return;

        $('#modal_audit_log').modal('show');
        $('#audit_timeline_container').html(
            '<div class="text-center py-4 text-muted">' +
                '<i class="fas fa-spinner fa-spin mr-1"></i> Memuat riwayat log aktivitas...' +
            '</div>'
        );

        $.ajax({
            url: '<?= base_url('penilaian/auditLogs') ?>/' + appraisalId,
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
                    $('#audit_timeline_container').html(
                        '<div class="text-center py-4 text-muted">' +
                            '<p class="mb-0 font-weight-bold text-dark">Belum ada riwayat aktivitas tercatat</p>' +
                            '<small>Form penilaian ini belum memiliki catatan perubahan status.</small>' +
                        '</div>'
                    );
                    return;
                }

                var html = '<ul class="apd-timeline">';
                $.each(logs, function (i, log) {
                    var actionTitle  = esc(log.action || '-');
                    var timeStr      = esc(log.created_at || '-');
                    var actor        = log.actor_name ? (esc(log.actor_name) + (log.actor_username ? ' (' + esc(log.actor_username) + ')' : '')) : ('User ID: ' + esc(log.actor_id || '-'));
                    var notes        = esc(log.notes || '-');
                    var statusChange = esc(log.from_status || '-') + ' &rarr; ' + esc(log.to_status || '-');

                    html += '<li class="apd-tl-item">' +
                        '<div class="d-flex justify-content-between align-items-center mb-1">' +
                            '<span class="badge badge-light border text-primary font-weight-bold px-2 py-1">' + actionTitle + '</span>' +
                            '<small class="text-muted"><i class="far fa-clock mr-1"></i>' + timeStr + '</small>' +
                        '</div>' +
                        '<div class="small text-muted mb-1">Aktor: <strong class="text-dark">' + actor + '</strong> &middot; Transisi: <strong class="text-dark">' + statusChange + '</strong></div>' +
                        '<div class="small p-2 bg-light rounded border text-dark mt-1">' + notes + '</div>' +
                    '</li>';
                });
                html += '</ul>';

                $('#audit_timeline_container').html(html);
            },
            error: function () {
                $('#audit_timeline_container').html(
                    '<div class="text-center py-4 text-danger">' +
                        '<i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat log riwayat aktivitas.' +
                    '</div>'
                );
            }
        });
    };
});
</script>

<?= $this->endSection() ?>
