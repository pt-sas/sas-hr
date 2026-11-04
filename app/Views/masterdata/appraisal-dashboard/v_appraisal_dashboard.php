<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<?= $this->include('Views/masterdata/appraisal-reusable/modal_score_comparison') ?>


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
/* Surface containers */
.apd-panel {
    background: #ffffff;
    border: 1px solid #ebedf2;
    border-radius: 6px;
    margin-bottom: 1.25rem;

    /* PAKSA MATIKAN STICKY DI SINI: */
    position: static !important;
    top: auto !important;
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

/* Stacked Distribution Ribbon */
.apd-ribbon-track {
    height: 8px;
    border-radius: 4px;
    background: #ebedf2;
    overflow: hidden;
    display: flex;
    width: 100%;
}
.apd-ribbon-segment {
    height: 100%;
    transition: width 0.3s ease;
}
.apd-ribbon-segment:hover {
    filter: brightness(0.92);
}

/* Predicate Rows (Clean tabular list with proportion) */
.apd-grade-list {
    margin: 0;
    padding: 0;
    list-style: none;
}
.apd-grade-row {
    display: grid;
    grid-template-columns: 105px 75px 1fr 68px;
    align-items: center;
    gap: 0.6rem;
    padding: 0.45rem 0;
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
    white-space: nowrap;
}
.apd-grade-count small {
    font-weight: 500;
    font-size: 11px;
}

/* Empty State & Footer Insight */
.apd-grade-footer {
    font-size: 11.5px;
    color: #6b6d7e;
}
.apd-grade-empty {
    padding: 2.2rem 1rem;
    text-align: center;
}
.apd-grade-empty-icon {
    width: 44px;
    height: 44px;
    line-height: 44px;
    border-radius: 50%;
    background: #f4f5f8;
    color: #8d9498;
    margin: 0 auto 0.75rem auto;
    font-size: 1.15rem;
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

.card-header .new_form {
        display: none !important;
    }
</style>

<div class="<?= $inCard ? 'card-body card-main' : 'page-inner pt-0' ?> apd-wrap">

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
        <!-- Left Column: Predicate Distribution -->
        <div class="col-lg-5 d-flex">
            <div class="apd-panel w-100 d-flex flex-column">
                <div class="apd-panel-head">
                    <h2 class="apd-panel-title">Distribusi Predikat</h2>
                    <span class="badge badge-light border font-weight-bold text-dark" id="badge_final_total">0 Form Final</span>
                </div>

                <div class="p-3 d-flex flex-column justify-content-between flex-grow-1" id="apd_grade_container">
                    <!-- Active State Content -->
                    <div id="apd_grade_content">
                        <!-- Stacked Proportion Ribbon -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted font-weight-bold">Proporsi Keseluruhan</span>
                                <span class="text-muted" style="font-size: 11px;">Form Selesai (Final)</span>
                            </div>
                            <div class="apd-ribbon-track" id="ribbon_track">
                                <div id="ribbon_sb" class="apd-ribbon-segment" style="background:#15803d; width: 0%;" data-toggle="tooltip" title="Sangat Baik"></div>
                                <div id="ribbon_b" class="apd-ribbon-segment" style="background:#1572e8; width: 0%;" data-toggle="tooltip" title="Baik"></div>
                                <div id="ribbon_c" class="apd-ribbon-segment" style="background:#d97706; width: 0%;" data-toggle="tooltip" title="Cukup"></div>
                                <div id="ribbon_k" class="apd-ribbon-segment" style="background:#b91c1c; width: 0%;" data-toggle="tooltip" title="Kurang"></div>
                            </div>
                        </div>

                        <!-- Predicate Breakdown List -->
                        <div class="pt-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small font-weight-bold text-muted">Rincian Predikat</span>
                                <span class="small text-muted">Jumlah (%)</span>
                            </div>
                            <ul class="apd-grade-list">
                                <li class="apd-grade-row">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#15803d;"></span>Sangat Baik</span>
                                    <span class="apd-grade-range">&ge; 3.50</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_sb" style="background:#15803d;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_sb">0</b> <small class="text-muted" id="pct_rating_sb">(0%)</small></span>
                                </li>
                                <li class="apd-grade-row">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#1572e8;"></span>Baik</span>
                                    <span class="apd-grade-range">2.75 – 3.49</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_b" style="background:#1572e8;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_b">0</b> <small class="text-muted" id="pct_rating_b">(0%)</small></span>
                                </li>
                                <li class="apd-grade-row">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#d97706;"></span>Cukup</span>
                                    <span class="apd-grade-range">2.00 – 2.74</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_c" style="background:#d97706;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_c">0</b> <small class="text-muted" id="pct_rating_c">(0%)</small></span>
                                </li>
                                <li class="apd-grade-row">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#b91c1c;"></span>Kurang</span>
                                    <span class="apd-grade-range">&lt; 2.00</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_k" style="background:#b91c1c;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_k">0</b> <small class="text-muted" id="pct_rating_k">(0%)</small></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Empty State (when 0 finalized) -->
                    <div id="apd_grade_empty" class="apd-grade-empty my-auto" style="display: none;">
                        <div class="apd-grade-empty-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h6 class="font-weight-bold text-dark mb-1">Belum Ada Rapor Final</h6>
                        <p class="text-muted small mb-0" style="max-width: 280px; margin: 0 auto; line-height: 1.45;">
                            Distribusi predikat akan terbentuk otomatis setelah form evaluasi selesai ditandatangani.
                        </p>
                    </div>

                    <!-- Footer Insight -->
                    <div class="apd-grade-footer mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span id="txt_dominant_grade"><i class="fas fa-info-circle text-primary mr-1"></i> Predikat Dominan: <strong>-</strong></span>
                        <span class="text-muted">Skala 1.00 – 4.00</span>
                    </div>
                </div>
            </div>
        </div>

    <!-- Right Column: Division Performance Breakdown -->
    <div class="col-lg-7 d-flex">
        <div class="apd-panel w-100 d-flex flex-column" style="min-width: 0;">
            <div class="apd-panel-head py-2">
                <h2 class="apd-panel-title" style="font-size: 13px;">Progres per Divisi</h2>
                <span class="small text-muted font-weight-bold" style="font-size: 11px;">Live Status</span>
            </div>

            <!-- Table Container with Ultra-Compact Fit -->
            <div class="table-responsive flex-grow-1" style="overflow-y: auto; overflow-x: auto; max-height: 380px;">
                <table class="table apd-table mb-0" style="min-width: 390px; font-size: 10px;">
                    <thead style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th rowspan="2" class="align-middle px-1 py-1 text-truncate" style="width: 75px; max-width: 75px;">Div.</th>
                            <th rowspan="2" class="text-center align-middle px-1 py-1" style="width: 32px;">Tot</th>
                            <th colspan="3" class="text-center py-1 border-bottom-0" style="background: #f1f3f7 !important; font-size: 9.5px;">Berjalan</th>
                            <th rowspan="2" class="text-center align-middle px-1 py-1" style="width: 38px;">Done</th>
                            <th rowspan="2" class="align-middle px-1 py-1" style="width: 65px;">Progres</th>
                            <th rowspan="2" class="text-center align-middle px-1 py-1" style="width: 42px;">Rata2</th>
                        </tr>
                        <tr>
                            <th class="text-center p-0 text-muted" style="width: 30px; font-size: 9px !important; background: #f8f9fa !important;" title="Draft Atasan">Ev</th>
                            <th class="text-center p-0 text-muted" style="width: 30px; font-size: 9px !important; background: #f8f9fa !important;" title="Review GM">Re</th>
                            <th class="text-center p-0 text-muted" style="width: 30px; font-size: 9px !important; background: #f8f9fa !important;" title="Menunggu TTD Karyawan">Bc</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_division_progress">
                        <tr>
                            <td colspan="8" class="text-center py-3 text-muted" style="font-size: 11px;">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data divisi...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 4. FORM EXPLORER / AUDIT GRID                                         -->
    <!-- ===================================================================== -->
    <div class="card-body card-main">
        <div class="table-responsive">
            <table class="table table-striped table-hover tb_display" style="width: 100%;">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <!-- TODO : Delete this remnants of code -->
                        <!-- <th>User</th> -->
                        <th>Karyawan</th>
                        <th>Divisi/Cabang</th>
                        <th>Nama/Status Siklus</th>
                        <th>Evaluator/Reviewer</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated via Server-Side DataTables -->
                </tbody>
            </table>
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
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function initAppraisalDashboard() {
    if (typeof window.jQuery === 'undefined') {
        setTimeout(initAppraisalDashboard, 30);
        return;
    }
    var $ = window.jQuery;
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
        if ($('#table_appraisal_explorer').length) {
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
    }

    // =========================================================================
    // 2. FETCH SUMMARY METRICS & DIVISION PROGRESS
    // =========================================================================
    function loadSummaryMetrics() {
        var $filterForm = $('.filter_page form, .card-filter form, #filter_appraisal_dashboard, #filter_appraisal_cycle');
        var cycleId    = $('#filter_cycle').val() || $filterForm.find('[name="cycle_id"]').val() || '';
        var divisionId = $('#filter_division').val() || $filterForm.find('[name="division_id"]').val() || '';
        var year       = $('#filter_year').val() || $filterForm.find('[name="year"]').val() || '';
        var status     = $('#filter_status').val() || $filterForm.find('[name="status"]').val() || '';

        $.ajax({
            url: '<?= base_url('monitoring-penilaian/getSummary') ?>',
            type: 'GET',
            data: {
                cycle_id: cycleId,
                division_id: divisionId,
                year: year,
                status: status
            },
            dataType: 'JSON',
            success: function (res) {
                var payload = {};
                if (res && res[0] && res[0].message) {
                    payload = res[0].message;
                } else if (res && res[0] && res[0].data) {
                    payload = res[0].data;
                } else if (res && res.message) {
                    payload = res.message;
                } else if (res && res.data) {
                    payload = res.data;
                } else if (res && !Array.isArray(res)) {
                    payload = res;
                } else if (Array.isArray(res) && res[0]) {
                    payload = res[0];
                }

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
                var inProgDetail = draftCount + ' Evaluasi · ' + gmCount + ' Review · ' + ackCount + ' Pembacaan';
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

                // 2.3 Predicate Distribution & Proportion Ribbon
                $('#badge_final_total').text(finalCount + ' Form Final');

                var rc = metrics.rating_counts || {};
                var sb = parseInt(rc.sangat_baik, 10) || 0;
                var b  = parseInt(rc.baik, 10) || 0;
                var c  = parseInt(rc.cukup, 10) || 0;
                var k  = parseInt(rc.kurang, 10) || 0;
                var ratedTotal = sb + b + c + k;

                if (finalCount > 0 && ratedTotal > 0) {
                    $('#apd_grade_content').show();
                    $('#apd_grade_empty').hide();

                    var pctSb = (sb / ratedTotal) * 100;
                    var pctB  = (b / ratedTotal) * 100;
                    var pctC  = (c / ratedTotal) * 100;
                    var pctK  = (k / ratedTotal) * 100;

                    // Update Counts & Percentages
                    $('#cnt_rating_sb').text(sb);
                    $('#pct_rating_sb').text('(' + pctSb.toFixed(0) + '%)');
                    $('#cnt_rating_b').text(b);
                    $('#pct_rating_b').text('(' + pctB.toFixed(0) + '%)');
                    $('#cnt_rating_c').text(c);
                    $('#pct_rating_c').text('(' + pctC.toFixed(0) + '%)');
                    $('#cnt_rating_k').text(k);
                    $('#pct_rating_k').text('(' + pctK.toFixed(0) + '%)');

                    // Stacked Proportion Ribbon Segments
                    $('#ribbon_sb').css('width', pctSb + '%').attr('title', 'Sangat Baik: ' + sb + ' (' + pctSb.toFixed(1) + '%)');
                    $('#ribbon_b').css('width', pctB + '%').attr('title', 'Baik: ' + b + ' (' + pctB.toFixed(1) + '%)');
                    $('#ribbon_c').css('width', pctC + '%').attr('title', 'Cukup: ' + c + ' (' + pctC.toFixed(1) + '%)');
                    $('#ribbon_k').css('width', pctK + '%').attr('title', 'Kurang: ' + k + ' (' + pctK.toFixed(1) + '%)');

                    // Individual Relative Bars (relative to highest category count for visual distinction)
                    var maxCount = Math.max(sb, b, c, k, 1);
                    $('#bar_rating_sb').css('width', ((sb / maxCount) * 100) + '%');
                    $('#bar_rating_b').css('width', ((b / maxCount) * 100) + '%');
                    $('#bar_rating_c').css('width', ((c / maxCount) * 100) + '%');
                    $('#bar_rating_k').css('width', ((k / maxCount) * 100) + '%');

                    // Dominant Predicate Insight
                    var dominantName = 'Baik';
                    var dominantPct = pctB;
                    if (sb >= b && sb >= c && sb >= k) {
                        dominantName = 'Sangat Baik';
                        dominantPct = pctSb;
                    } else if (b >= sb && b >= c && b >= k) {
                        dominantName = 'Baik';
                        dominantPct = pctB;
                    } else if (c >= sb && c >= b && c >= k) {
                        dominantName = 'Cukup';
                        dominantPct = pctC;
                    } else {
                        dominantName = 'Kurang';
                        dominantPct = pctK;
                    }
                    $('#txt_dominant_grade').html('<i class="fas fa-info-circle text-primary mr-1"></i> Predikat Dominan: <strong>' + dominantName + '</strong> (' + dominantPct.toFixed(0) + '%)');
                } else {
                    $('#apd_grade_content').hide();
                    $('#apd_grade_empty').show();
                    $('#txt_dominant_grade').html('<i class="fas fa-info-circle text-muted mr-1"></i> Predikat Dominan: <strong>-</strong>');
                }

                // 2.5 Division Progress Breakdown Table
                $('#txt_div_count').text(divs.length + ' Divisi');
                var $tbody = $('#tbody_division_progress');
                $tbody.empty();

                if (divs.length === 0) {
                    $tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data evaluasi divisi pada siklus ini.</td></tr>');
                } else {
                    $.each(divs, function (i, d) {
                        var pVal = parseFloat(d.percent || 0.0);
                        var avgDivScore = d.avg_score !== null && d.avg_score !== undefined ? Number(d.avg_score).toFixed(2) : '-';
                        var avgColor = avgDivScore !== '-' ? 'apd-tone-pri font-weight-bold' : 'text-muted';

                        var rowHtml = '<tr>' +
                            '<td class="font-weight-bold">' + esc(d.division_name) + '</td>' +
                            '<td class="text-center text-muted">' + (d.total || 0) + '</td>' +
                            '<td class="text-center ' + ((parseInt(d.draft, 10) || 0) > 0 ? 'text-warning font-weight-bold' : 'text-muted') + '">' + (parseInt(d.draft, 10) || 0) + '</td>' +
                            '<td class="text-center ' + ((parseInt(d.review_gm, 10) || 0) > 0 ? 'text-info font-weight-bold' : 'text-muted') + '">' + (parseInt(d.review_gm, 10) || 0) + '</td>' +
                            '<td class="text-center ' + ((parseInt(d.pending_ack, 10) || 0) > 0 ? 'text-primary font-weight-bold' : 'text-muted') + '">' + (parseInt(d.pending_ack, 10) || 0) + '</td>' +
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
                $('#tbody_division_progress').html('<tr><td colspan="8" class="text-center py-3 text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat ringkasan divisi.</td></tr>');
            }
        });
    }

    // =========================================================================
    // 3. EVENT HANDLERS & FILTERS
    // =========================================================================
    initExplorerTable();
    loadSummaryMetrics();

    $('#filter_cycle, #filter_division, #filter_status, #filter_year').on('change', function () {
        if (dtExplorer) {
            dtExplorer.ajax.reload();
        }
        loadSummaryMetrics();
    });

    // Listen to global filter buttons and inputs
    $(document).on('click', '.btn_filter, .btn_reset_filter, .btn_requery', function () {
        setTimeout(function () {
            loadSummaryMetrics();
        }, 300);
    });

    $(document).on('change', '.filter_page select, .filter_page input, .card-filter select, .card-filter input', function () {
        setTimeout(function () {
            loadSummaryMetrics();
        }, 150);
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
    $('#btn_reset_filter, .btn_reset').on('click', function () {
        $('#filter_cycle').val('<?= (int) $default_cycle_id ?>').trigger('change.select2');
        $('#filter_division').val('0').trigger('change.select2');
        $('#filter_status').val('ALL').trigger('change');
        if (dtExplorer) {
            dtExplorer.ajax.reload();
        }
        loadSummaryMetrics();
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
                if (res && res[0] && (res[0].message || res[0].data)) {
                    logs = res[0].message || res[0].data;
                } else if (res && (res.message || res.data)) {
                    logs = res.message || res.data;
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

    // =========================================================================
    // 5. MANUAL NUDGE ACTION HANDLER
    // =========================================================================
    window.nudgeAppraisal = function (appraisalId, empName) {
        if (!appraisalId) return;

        var targetTitle = empName ? 'evaluasi <b>' + esc(empName) + '</b>' : 'form evaluasi ini';

        Swal.fire({
            title: 'Kirim Pengingat?',
            html: 'Kirim notifikasi pengingat broadcast untuk ' + targetTitle + ' ke pemegang tugas saat ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffad46',
            cancelButtonColor: '#adb5bd',
            confirmButtonText: '<i class="fas fa-bell mr-1"></i> Ya, Kirim Pengingat',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Memasukkan pesan pengingat ke antrean broadcast...',
                    allowOutsideClick: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });

                var postData = { id: appraisalId };
                postData['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

                $.ajax({
                    url: '<?= base_url('monitoring-penilaian/nudge') ?>',
                    type: 'POST',
                    data: postData,
                    dataType: 'JSON',
                    success: function (res) {
                        var isOk = (res && res[0] && res[0].success) || (res && res.success);
                        var msg  = (res && res[0] && res[0].message) || (res && res.message) || 'Pengingat berhasil dikirim.';

                        if (isOk) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: msg,
                                timer: 2500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Perhatian',
                                text: msg
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan sistem saat mengirim pengingat.'
                        });
                    }
                });
            }
        });
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAppraisalDashboard);
} else {
    initAppraisalDashboard();
}
</script>

<?= $this->endSection() ?>
