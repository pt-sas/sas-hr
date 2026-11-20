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
    font-size: 11px !important;
    font-weight: 700 !important;
    color: #575962 !important;
    background: #f8f9fa !important;
    border-top: 0 !important;
    border-bottom: 1px solid #ebedf2 !important;
    padding: 0.5rem 0.45rem !important;
    vertical-align: middle !important;
    white-space: nowrap;
}
.apd-table td {
    font-size: 11.5px;
    color: #2a2f5b;
    padding: 0.45rem 0.45rem !important;
    vertical-align: middle !important;
    border-top: 1px solid #f1f3f7 !important;
}
.apd-table tfoot td {
    font-size: 11.5px;
    color: #2a2f5b;
    padding: 0.5rem 0.45rem !important;
    vertical-align: middle !important;
}
.apd-table tbody tr:hover {
    background-color: #fbfcfd;
}
.apd-table tfoot {
    position: sticky;
    bottom: 0;
    z-index: 2;
    background: #fbfcfd;
    box-shadow: 0 -1px 0 #ebedf2;
}
.apd-div-row {
    cursor: pointer;
}
.apd-div-cell-filter:hover {
    text-decoration: underline;
}

/* Form Type Segmented Switcher (inside Distribusi Predikat header) */
.apd-type-switch {
    display: inline-flex;
    background: #f1f3f7;
    padding: 2px;
    border-radius: 5px;
    gap: 2px;
}
.apd-type-btn {
    border: 0;
    background: transparent;
    color: #575962;
    font-size: 11px;
    font-weight: 600;
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s ease;
    line-height: 1.2;
}
.apd-type-btn:hover {
    color: #2a2f5b;
}
.apd-type-btn.is-active {
    background: #1572e8;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(21, 114, 232, 0.25);
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

/* ==========================================================================
   READABILITY PASS (older users) -- typography & color ONLY.
   No layout properties (grid, width, padding, margin, position) below.
   ========================================================================== */

.apd-wrap .apd-panel-title { font-size: 17px; color: #1c2040; }
.apd-wrap .apd-panel-sub,
.apd-wrap .apd-cycle-meta { font-size: 14px; color: #44475a; }
.apd-wrap .apd-field label { font-size: 14px; color: #1c2040; }
.apd-wrap .apd-stat-label { font-size: 14.5px; font-weight: 700; color: #33364d; }
.apd-wrap .apd-stat-num { font-size: 2.25rem; color: #1c2040; }
.apd-wrap .apd-stat-unit { font-size: 15px; color: #44475a; }
.apd-wrap .apd-stat-note { font-size: 14px; color: #44475a; }

.apd-wrap .apd-grade-row { font-size: 14.5px; }
.apd-wrap .apd-grade-name { color: #1c2040; }
.apd-wrap .apd-grade-range { font-size: 13.5px; color: #44475a; }
.apd-wrap .apd-grade-count { color: #1c2040; }
.apd-wrap .apd-grade-count small { font-size: 13.5px; font-weight: 600; }
.apd-wrap .apd-grade-footer { font-size: 14px; color: #44475a; }
.apd-wrap .apd-type-btn { font-size: 13.5px; color: #33364d; }
.apd-wrap .apd-type-btn.is-active { color: #ffffff; background: #0b57c0; }

/* NOTE: the data grid (.tb_display) below is intentionally untouched. */

/* Progres per Divisi table (.apd-table) */
.apd-wrap .apd-table th { font-size: 13.5px !important; color: #1c2040 !important; }
.apd-wrap .apd-table td,
.apd-wrap .apd-table tfoot td { font-size: 14.5px; color: #1c2040; }
.apd-wrap .apd-table .apd-tone-ok,   .apd-wrap .apd-table .text-success { color: #166534 !important; }
.apd-wrap .apd-table .text-warning { color: #8a4506 !important; }
.apd-wrap .apd-table .apd-tone-pri,  .apd-wrap .apd-table .text-primary { color: #0b57c0 !important; }
.apd-wrap .apd-table .text-info { color: #0a5a78 !important; }
.apd-wrap .apd-table td.text-muted,
.apd-wrap .apd-table th.text-muted { color: #5a5d70 !important; }
.apd-wrap .apd-div-row:focus-visible { outline: 3px solid #0b57c0; outline-offset: 2px; }

/* Darker semantic text colors for non-table elements only */
.apd-wrap .apd-stat-num.apd-tone-ok   { color: #166534 !important; }
.apd-wrap .apd-stat-unit.apd-tone-ok  { color: #166534 !important; }
.apd-wrap .apd-stat-num.apd-tone-warn { color: #8a4506 !important; }
.apd-wrap .apd-stat-num.apd-tone-pri  { color: #0b57c0 !important; }
.apd-wrap .apd-panel .text-muted:not(td):not(th) { color: #4a4d5e !important; }

/* Keyboard focus */
.apd-wrap .apd-type-btn:focus-visible {
    outline: 3px solid #0b57c0;
    outline-offset: 2px;
}

/* Filter bar (outside .apd-wrap) */
#filter_appraisal_dashboard label { font-size: 14px; color: #1c2040 !important; }
#filter_appraisal_dashboard .select2-selection__rendered,
#filter_appraisal_dashboard .btn { font-size: 14px; }

/* Audit trail modal (outside .apd-wrap) */
#modal_audit_log .modal-title { font-size: 18px !important; color: #1c2040; }
#modal_audit_log small,
#modal_audit_log .small { font-size: 14px; }
#modal_audit_log .text-muted { color: #44475a !important; }
#modal_audit_log .text-dark { color: #1c2040 !important; }
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
                    <span>Evaluasi Selesai</span>
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
                <?php $kpiStLabels = $status_labels ?? \App\Models\M_Appraisal::getStatusLabels(); ?>
                <p class="apd-stat-note" id="kpi_in_progress_detail" title="Rincian tahapan berjalan">0 <?= esc($kpiStLabels[\App\Models\M_Appraisal::STATUS_PENILAIAN] ?? 'Penilaian') ?> · 0 <?= esc($kpiStLabels[\App\Models\M_Appraisal::STATUS_REVIEW] ?? 'Peninjauan') ?> · 0 <?= esc($kpiStLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_REVIEW] ?? 'Ditinjau') ?></p>
            </div>

            <!-- Rata-Rata Skor Akhir -->
            <div class="apd-ledger-cell">
                <div class="apd-stat-label">
                    <span>Rata-Rata Skor Akhir</span>
                    <span class="apd-stat-dot" style="background: #1572e8;"></span>
                </div>
                <div class="apd-stat-main">
                    <span class="apd-stat-num apd-tone-pri" id="kpi_avg_score">-</span>
                    <span class="apd-stat-unit" id="kpi_avg_scale">/ 4.00</span>
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
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <span class="badge badge-light border font-weight-bold text-dark" id="badge_final_total">0 Form Final</span>
                    </div>
                </div>

                <div class="p-3 d-flex flex-column justify-content-between flex-grow-1" id="apd_grade_container">
                    <!-- Active State Content -->
                    <div id="apd_grade_content">
                        <!-- Stacked Proportion Ribbon -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted font-weight-bold">Proporsi Keseluruhan</span>
                                <span class="text-muted" style="font-size: 13.5px;">Form Selesai (Final)</span>
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
                                    <span class="apd-grade-range" id="range_rating_sb">&ge; 3.50</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_sb" style="background:#15803d;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_sb">0</b> <small class="text-muted" id="pct_rating_sb">(0%)</small></span>
                                </li>
                                <li class="apd-grade-row">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#1572e8;"></span>Baik</span>
                                    <span class="apd-grade-range" id="range_rating_b">2.75 – 3.49</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_b" style="background:#1572e8;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_b">0</b> <small class="text-muted" id="pct_rating_b">(0%)</small></span>
                                </li>
                                <li class="apd-grade-row" id="row_rating_c">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#d97706;"></span>Cukup</span>
                                    <span class="apd-grade-range" id="range_rating_c">2.00 – 2.74</span>
                                    <div class="apd-grade-bar"><span id="bar_rating_c" style="background:#d97706;"></span></div>
                                    <span class="apd-grade-count"><b id="cnt_rating_c">0</b> <small class="text-muted" id="pct_rating_c">(0%)</small></span>
                                </li>
                                <li class="apd-grade-row">
                                    <span class="apd-grade-name"><span class="apd-stat-dot" style="background:#b91c1c;"></span>Kurang</span>
                                    <span class="apd-grade-range" id="range_rating_k">&lt; 2.00</span>
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
                        <p class="text-muted mb-0" style="max-width: 280px; margin: 0 auto; line-height: 1.45; font-size: 14px;">
                            Distribusi predikat akan terbentuk otomatis setelah form evaluasi selesai ditandatangani.
                        </p>
                    </div>

                    <!-- Footer Insight -->
                    <div class="apd-grade-footer mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                        <span id="txt_dominant_grade"><i class="fas fa-info-circle text-primary mr-1"></i> Predikat Dominan: <strong>-</strong></span>
                        <span class="text-muted" id="txt_grade_scale">Skala 1.00 – 4.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Division Performance Breakdown -->
        <div class="col-lg-7 d-flex">
            <div class="apd-panel w-100 d-flex flex-column" style="min-width: 0;">

                <div class="table-responsive flex-grow-1" style="overflow-y: auto; overflow-x: auto; max-height: 420px;">
                    <table class="table apd-table mb-0 table-hover">
                        <thead style="position: sticky; top: 0; z-index: 2;">
                            <?php $stLabels = $status_labels ?? \App\Models\M_Appraisal::getStatusLabels(); ?>
                            <tr>
                                <th rowspan="2" class="align-middle px-2 py-1">Divisi</th>
                                <th rowspan="2" class="text-center align-middle px-1 py-1" style="width: 38px;">Total</th>
                                <th colspan="4" class="text-center py-1 border-bottom-0" style="background: #f1f3f7 !important;">Berjalan</th>
                                <th rowspan="2" class="text-center align-middle px-1 py-1" style="width: 44px;"><?= esc($stLabels[\App\Models\M_Appraisal::STATUS_FINAL] ?? 'Selesai') ?></th>
                                <th rowspan="2" class="align-middle px-2 py-1" style="min-width: 105px; width: 115px;">Progres</th>
                                <th rowspan="2" class="text-center align-middle px-1 py-1" style="width: 55px;">Rata-Rata</th>
                            </tr>
                            <tr>
                                <th class="text-center p-1 text-muted" style="width: 56px; background: #f8f9fa !important;" title="Tahap <?= esc($stLabels[\App\Models\M_Appraisal::STATUS_PENILAIAN] ?? 'Penilaian') ?>"><?= esc($stLabels[\App\Models\M_Appraisal::STATUS_PENILAIAN] ?? 'Penilaian') ?></th>
                                <th class="text-center p-1 text-muted" style="width: 68px; background: #f8f9fa !important;" title="Tahap <?= esc($stLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_PENILAIAN] ?? 'Dinilai') ?>"><?= esc($stLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_PENILAIAN] ?? 'Dinilai') ?></th>
                                <th class="text-center p-1 text-muted" style="width: 48px; background: #f8f9fa !important;" title="Tahap <?= esc($stLabels[\App\Models\M_Appraisal::STATUS_REVIEW] ?? 'Peninjauan') ?>"><?= esc($stLabels[\App\Models\M_Appraisal::STATUS_REVIEW] ?? 'Peninjauan') ?></th>
                                <th class="text-center p-1 text-muted" style="width: 68px; background: #f8f9fa !important;" title="Tahap <?= esc($stLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_REVIEW] ?? 'Ditinjau') ?>"><?= esc($stLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_REVIEW] ?? 'Ditinjau') ?></th>
                            </tr>
                        </thead>
                        <tbody id="tbody_division_progress">
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted" style="font-size: 14px;">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data divisi...
                                </td>
                            </tr>
                        </tbody>
                        <tfoot id="tfoot_division_progress" style="background: #fbfcfd; border-top: 2px solid #ebedf2;">
                        </tfoot>
                    </table>
                </div>
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
                        <th>ID</th>
                        <th class="text-center" style="width: 40px;">No</th>
                        <th>Karyawan</th>
                        <th>Jabatan</th>
                        <th>Level</th>
                        <th>Cabang</th>
                        <th>Divisi</th>
                        <th>Siklus Penilaian</th>
                        <th class="text-center">Nilai</th>
                        <th class="text-center" style="width: 140px;">Status</th>
                        <th class="text-center" style="width: 110px;">Aksi</th>
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

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // =========================================================================
    // 1. FETCH SUMMARY METRICS & DIVISION PROGRESS
    // =========================================================================
    var defaultTargetGroup = '<?= esc($default_target_group ?? 'PERMANENT') ?>';
    var activeGradeGroup = ($('#filter_target_group').val() || defaultTargetGroup) === 'OPERATOR' ? 'OPERATOR' : 'PERMANENT';
    var latestRatingByGroup = null;
    var latestFallbackCounts = null;
    var latestFallbackFinal = 0;

    function reloadDataTable() {
        if (typeof reloadTable === 'function') {
            reloadTable();
        } else if (typeof _table !== 'undefined' && _table) {
            _table.ajax.reload();
        }
    }

    function syncGradeSwitcherUI(group) {
        activeGradeGroup = (group === 'OPERATOR') ? 'OPERATOR' : 'PERMANENT';
        if (activeGradeGroup === 'OPERATOR') {
            $('#kpi_avg_scale').text('/ 15');
            $('#txt_grade_scale').text('Skala 5 – 15');
            $('#range_rating_sb').text('13 – 15');
            $('#range_rating_b').text('8 – 12');
            $('#range_rating_k').text('5 – 7');
            $('#row_rating_c, #ribbon_c').hide();
        } else {
            $('#kpi_avg_scale').text('/ 4.00');
            $('#txt_grade_scale').text('Skala 1.00 – 4.00');
            $('#range_rating_sb').html('&ge; 3.50');
            $('#range_rating_b').text('2.75 – 3.49');
            $('#range_rating_c').text('2.00 – 2.74');
            $('#range_rating_k').html('&lt; 2.00');
            $('#row_rating_c, #ribbon_c').show();
        }
    }

    function renderGradeDistribution() {
        var isOp = activeGradeGroup === 'OPERATOR';
        var groupData = latestRatingByGroup && latestRatingByGroup[activeGradeGroup] ? latestRatingByGroup[activeGradeGroup] : null;
        var rc = groupData ? (groupData.counts || {}) : (latestFallbackCounts || {});
        var groupFinalCount = groupData ? (parseInt(groupData.total_final, 10) || 0) : latestFallbackFinal;

        $('#badge_final_total').text(groupFinalCount + ' Form Final');

        var sb = parseInt(rc.sangat_baik, 10) || 0;
        var b  = parseInt(rc.baik, 10) || 0;
        var c  = isOp ? 0 : (parseInt(rc.cukup, 10) || 0);
        var k  = parseInt(rc.kurang, 10) || 0;
        var ratedTotal = sb + b + c + k;

        if (groupFinalCount > 0 && ratedTotal > 0) {
            $('#apd_grade_content').show();
            $('#apd_grade_empty').hide();

            var pctSb = (sb / ratedTotal) * 100;
            var pctB  = (b / ratedTotal) * 100;
            var pctC  = (c / ratedTotal) * 100;
            var pctK  = (k / ratedTotal) * 100;

            $('#cnt_rating_sb').text(sb);
            $('#pct_rating_sb').text('(' + pctSb.toFixed(0) + '%)');
            $('#cnt_rating_b').text(b);
            $('#pct_rating_b').text('(' + pctB.toFixed(0) + '%)');
            $('#cnt_rating_c').text(c);
            $('#pct_rating_c').text('(' + pctC.toFixed(0) + '%)');
            $('#cnt_rating_k').text(k);
            $('#pct_rating_k').text('(' + pctK.toFixed(0) + '%)');

            $('#ribbon_sb').css('width', pctSb + '%').attr('title', 'Sangat Baik: ' + sb + ' (' + pctSb.toFixed(1) + '%)');
            $('#ribbon_b').css('width', pctB + '%').attr('title', 'Baik: ' + b + ' (' + pctB.toFixed(1) + '%)');
            $('#ribbon_c').css('width', pctC + '%').attr('title', 'Cukup: ' + c + ' (' + pctC.toFixed(1) + '%)');
            $('#ribbon_k').css('width', pctK + '%').attr('title', 'Kurang: ' + k + ' (' + pctK.toFixed(1) + '%)');

            var maxCount = Math.max(sb, b, c, k, 1);
            $('#bar_rating_sb').css('width', ((sb / maxCount) * 100) + '%');
            $('#bar_rating_b').css('width', ((b / maxCount) * 100) + '%');
            $('#bar_rating_c').css('width', ((c / maxCount) * 100) + '%');
            $('#bar_rating_k').css('width', ((k / maxCount) * 100) + '%');

            var dominantName = 'Baik';
            var dominantPct = pctB;
            if (sb >= b && sb >= c && sb >= k) {
                dominantName = 'Sangat Baik';
                dominantPct = pctSb;
            } else if (b >= sb && b >= c && b >= k) {
                dominantName = 'Baik';
                dominantPct = pctB;
            } else if (!isOp && c >= sb && c >= b && c >= k) {
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
    }

    function loadSummaryMetrics() {
        var $filterForm = $('.filter_page form, .card-filter form, #filter_appraisal_dashboard, #filter_appraisal_cycle');
        var cycleId     = $('#filter_cycle').val() || $filterForm.find('[name="cycle_id"]').val() || '';
        var divisionId  = $('#filter_division').val() || $filterForm.find('[name="division_id"]').val() || '';
        var year        = $('#filter_year').val() || $filterForm.find('[name="year"]').val() || '';
        var status      = $('#filter_status').val() || $filterForm.find('[name="status"]').val() || '';
        var targetGroup = $('#filter_target_group').val() || activeGradeGroup || 'PERMANENT';

        syncGradeSwitcherUI(targetGroup);

        $.ajax({
            url: '<?= base_url('sas/monitoring-penilaian/getSummary') ?>',
            type: 'GET',
            data: {
                cycle_id: cycleId,
                division_id: divisionId,
                year: year,
                status: status,
                target_group: targetGroup
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
                    $('#banner_eval_due').text(cycle.deadline_filling || '-');
                    $('#banner_reviewer_due').text(cycle.deadline_review || '-');
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
                var penilaianCount    = parseInt(metrics.draft_evaluator, 10) || 0;
                var dinilaiCount      = parseInt(metrics.submitted_evaluator, 10) || 0;
                var peninjauanCount   = parseInt(metrics.review_reviewer, 10) || 0;
                var ditinjauCount     = parseInt(metrics.pending_ack, 10) || 0;

                $('#kpi_total').text(totalCount);
                $('#kpi_finalized').text(finalCount);

                var compPct = parseFloat(metrics.completion_percent) || 0.0;
                var wipPct  = totalCount > 0 ? Math.min(100 - compPct, (inProgCount / totalCount) * 100) : 0.0;
                $('#kpi_completion_badge').text('(' + compPct.toFixed(1) + '%)');

                $('#kpi_in_progress').text(inProgCount);
                var inProgParts = [
                    penilaianCount + ' ' + <?= json_encode($kpiStLabels[\App\Models\M_Appraisal::STATUS_PENILAIAN] ?? 'Penilaian') ?>,
                    peninjauanCount + ' ' + <?= json_encode($kpiStLabels[\App\Models\M_Appraisal::STATUS_REVIEW] ?? 'Peninjauan') ?>,
                    ditinjauCount + ' ' + <?= json_encode($kpiStLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_REVIEW] ?? 'Ditinjau') ?>
                ];
                if (dinilaiCount > 0) {
                    inProgParts.splice(1, 0, dinilaiCount + ' ' + <?= json_encode($kpiStLabels[\App\Models\M_Appraisal::STATUS_SUBMIT_PENILAIAN] ?? 'Dinilai') ?>);
                }
                var inProgDetail = inProgParts.join(' · ');
                $('#kpi_in_progress_detail').text(inProgDetail).attr('title', inProgDetail);

                var avgScore = metrics.avg_final_score !== null && metrics.avg_final_score !== undefined
                    ? Number(metrics.avg_final_score).toFixed(2)
                    : '-';
                $('#kpi_avg_score').text(avgScore);

                if (avgScore !== '-') {
                    var sVal = parseFloat(avgScore);
                    var gTxt;
                    if (activeGradeGroup === 'OPERATOR') {
                        gTxt = sVal >= 13 ? 'Predikat: Sangat Baik' : (sVal >= 8 ? 'Predikat: Baik' : 'Predikat: Kurang');
                    } else {
                        gTxt = sVal >= 3.50 ? 'Predikat: Sangat Baik' : (sVal >= 2.75 ? 'Predikat: Baik' : (sVal >= 2.00 ? 'Predikat: Cukup' : 'Predikat: Kurang'));
                    }
                    $('#kpi_avg_grade').text(gTxt);
                } else {
                    $('#kpi_avg_grade').text('Belum ada form selesai');
                }

                // 2.3 Predicate Distribution & Proportion Ribbon (Scoped to activeGradeGroup)
                latestRatingByGroup = metrics.rating_by_group || null;
                latestFallbackCounts = metrics.rating_counts || {};
                latestFallbackFinal = finalCount;
                renderGradeDistribution();

                // 2.5 Division Progress Breakdown Table
                $('#txt_div_count').text(divs.length + ' Divisi');
                var $tbody = $('#tbody_division_progress');
                var $tfoot = $('#tfoot_division_progress');
                $tbody.empty();
                $tfoot.empty();

                if (divs.length === 0) {
                    $tbody.html('<tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data evaluasi divisi pada siklus ini.</td></tr>');
                } else {
                    var sumTotal = 0;
                    var sumPenilaian = 0;
                    var sumSubmitPenilaian = 0;
                    var sumReviewReviewer = 0;
                    var sumPendingAck = 0;
                    var sumFinalized = 0;
                    var sumScoreTotal = 0.0;
                    var sumScoreCount = 0;

                    $.each(divs, function (i, d) {
                        var divId               = parseInt(d.division_id, 10) || 0;
                        var totalVal            = parseInt(d.total, 10) || 0;
                        var penilaianVal        = parseInt(d.penilaian, 10) || 0;
                        var submitPenilaianVal  = parseInt(d.submit_penilaian, 10) || 0;
                        var reviewReviewerVal   = parseInt(d.review_reviewer, 10) || 0;
                        var pendingAckVal       = parseInt(d.pending_ack, 10) || 0;
                        var finalVal            = parseInt(d.finalized, 10) || 0;
                        var inProgVal           = penilaianVal + submitPenilaianVal + reviewReviewerVal + pendingAckVal;
                        var scoreSumVal         = parseFloat(d.score_sum || 0.0);
                        var scoreCountVal       = parseInt(d.score_count, 10) || 0;

                        sumTotal           += totalVal;
                        sumPenilaian       += penilaianVal;
                        sumSubmitPenilaian += submitPenilaianVal;
                        sumReviewReviewer  += reviewReviewerVal;
                        sumPendingAck      += pendingAckVal;
                        sumFinalized       += finalVal;
                        sumScoreTotal      += scoreSumVal;
                        sumScoreCount      += scoreCountVal;

                        var pVal = parseFloat(d.percent || 0.0);
                        var wipBarPct = totalVal > 0 ? Math.min(100 - pVal, (inProgVal / totalVal) * 100) : 0.0;
                        var avgDivScore = d.avg_score !== null && d.avg_score !== undefined ? Number(d.avg_score).toFixed(2) : '-';
                        var avgColor = avgDivScore !== '-' ? 'apd-tone-pri font-weight-bold' : 'text-muted';

                        var rowHtml = '<tr class="apd-div-row" data-division-id="' + divId + '" title="Klik untuk memfilter tabel ke divisi ' + esc(d.division_name) + '">' +
                            '<td class="font-weight-bold text-dark px-2 py-1 text-truncate" style="max-width: 180px;" title="' + esc(d.division_name) + '">' + esc(d.division_name) + '</td>' +
                            '<td class="text-center text-dark font-weight-bold px-1 py-1">' + totalVal + '</td>' +
                            '<td class="text-center px-1 py-1 apd-div-cell-filter ' + (penilaianVal > 0 ? 'text-warning font-weight-bold' : 'text-muted') + '" data-status="PENILAIAN">' + penilaianVal + '</td>' +
                            '<td class="text-center px-1 py-1 apd-div-cell-filter ' + (submitPenilaianVal > 0 ? 'text-warning font-weight-bold' : 'text-muted') + '" data-status="SUBMIT_PENILAIAN">' + submitPenilaianVal + '</td>' +
                            '<td class="text-center px-1 py-1 apd-div-cell-filter ' + (reviewReviewerVal > 0 ? 'text-info font-weight-bold' : 'text-muted') + '" data-status="REVIEW">' + reviewReviewerVal + '</td>' +
                            '<td class="text-center px-1 py-1 apd-div-cell-filter ' + (pendingAckVal > 0 ? 'text-primary font-weight-bold' : 'text-muted') + '" data-status="SUBMIT_REVIEW">' + pendingAckVal + '</td>' +
                            '<td class="text-center apd-tone-ok font-weight-bold px-1 py-1 apd-div-cell-filter" data-status="FINAL">' + finalVal + '</td>' +
                            '<td class="px-2 py-1">' +
                                '<div class="d-flex align-items-center" style="gap: 6px;">' +
                                    '<div class="apd-prog-track flex-grow-1" style="height: 6px; min-width: 36px;" title="Final: ' + finalVal + ' · Berjalan: ' + inProgVal + '">' +
                                        '<div class="apd-prog-fill-ok" style="width:' + pVal + '%;"></div>' +
                                        '<div class="apd-prog-fill-wip" style="width:' + wipBarPct.toFixed(1) + '%;"></div>' +
                                    '</div>' +
                                    '<span class="font-weight-bold text-dark" style="font-size: 13.5px; width: 32px; text-align: right; white-space: nowrap;">' + pVal.toFixed(0) + '%</span>' +
                                '</div>' +
                            '</td>' +
                            '<td class="text-center px-1 py-1 ' + avgColor + '">' + avgDivScore + '</td>' +
                        '</tr>';
                        $tbody.append(rowHtml);
                    });

                    // Summary Aggregate Row in TFOOT
                    var sumInProg = sumPenilaian + sumSubmitPenilaian + sumReviewReviewer + sumPendingAck;
                    var overallPct = sumTotal > 0 ? ((sumFinalized / sumTotal) * 100) : 0.0;
                    var overallWipPct = sumTotal > 0 ? Math.min(100 - overallPct, (sumInProg / sumTotal) * 100) : 0.0;
                    var overallAvg = metrics.avg_final_score !== null && metrics.avg_final_score !== undefined
                        ? Number(metrics.avg_final_score).toFixed(2)
                        : (sumScoreCount > 0 ? (sumScoreTotal / sumScoreCount).toFixed(2) : '-');
                    var overallAvgColor = overallAvg !== '-' ? 'apd-tone-pri font-weight-bold' : 'text-muted';

                    var tfootHtml = '<tr>' +
                        '<td class="font-weight-bold text-dark text-uppercase px-2 py-1">Total</td>' +
                        '<td class="text-center font-weight-bold text-dark px-1 py-1">' + sumTotal + '</td>' +
                        '<td class="text-center font-weight-bold px-1 py-1 ' + (sumPenilaian > 0 ? 'text-warning' : 'text-muted') + '">' + sumPenilaian + '</td>' +
                        '<td class="text-center font-weight-bold px-1 py-1 ' + (sumSubmitPenilaian > 0 ? 'text-warning' : 'text-muted') + '">' + sumSubmitPenilaian + '</td>' +
                        '<td class="text-center font-weight-bold px-1 py-1 ' + (sumReviewReviewer > 0 ? 'text-info' : 'text-muted') + '">' + sumReviewReviewer + '</td>' +
                        '<td class="text-center font-weight-bold px-1 py-1 ' + (sumPendingAck > 0 ? 'text-primary' : 'text-muted') + '">' + sumPendingAck + '</td>' +
                        '<td class="text-center apd-tone-ok font-weight-bold px-1 py-1">' + sumFinalized + '</td>' +
                        '<td class="px-2 py-1">' +
                            '<div class="d-flex align-items-center" style="gap: 6px;">' +
                                '<div class="apd-prog-track flex-grow-1" style="height: 6px; min-width: 36px;">' +
                                    '<div class="apd-prog-fill-ok" style="width: ' + overallPct + '%;"></div>' +
                                    '<div class="apd-prog-fill-wip" style="width: ' + overallWipPct.toFixed(1) + '%;"></div>' +
                                '</div>' +
                                '<span class="font-weight-bold text-dark" style="font-size: 13.5px; width: 32px; text-align: right; white-space: nowrap;">' + overallPct.toFixed(0) + '%</span>' +
                            '</div>' +
                        '</td>' +
                        '<td class="text-center font-weight-bold px-1 py-1 ' + overallAvgColor + '">' + overallAvg + '</td>' +
                    '</tr>';
                    $tfoot.html(tfootHtml);
                }
            },
            error: function () {
                $('#tbody_division_progress').html('<tr><td colspan="9" class="text-center py-3 text-danger"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memuat ringkasan divisi.</td></tr>');
                $('#tfoot_division_progress').empty();
            }
        });
    }

    // =========================================================================
    // 2. EVENT HANDLERS & FILTERS
    // =========================================================================
    loadSummaryMetrics();

    $('#filter_cycle, #filter_division, #filter_status, #filter_year, #filter_target_group').on('change', function () {
        reloadDataTable();
        loadSummaryMetrics();
    });

    // Listen to global filter buttons
    $(document).on('click', '.btn_filter, .btn_reset_filter, .btn_requery', function () {
        setTimeout(function () {
            loadSummaryMetrics();
        }, 300);
    });

    // Refresh Button
    $('#btn_refresh_all').on('click', function () {
        var $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        reloadDataTable();
        loadSummaryMetrics();
        setTimeout(function () {
            $icon.removeClass('fa-spin');
        }, 500);
    });

    // Click-to-drill-down on Progres per Divisi rows & status cells
    $(document).on('click', '#tbody_division_progress tr.apd-div-row', function (e) {
        var divId = $(this).data('division-id');
        if (!divId) return;

        var $cell = $(e.target).closest('[data-status]');
        var st = $cell.length ? $cell.data('status') : 'ALL';

        $('#filter_division').val(String(divId)).trigger('change.select2');
        $('#filter_status').val(st).trigger('change.select2');
        reloadDataTable();
        loadSummaryMetrics();

        var $grid = $('.card-main .tb_display').closest('.card-main');
        if ($grid.length) {
            $('html, body').animate({ scrollTop: $grid.offset().top - 70 }, 250);
        }
    });

    // Reset Filter Button
    $('#btn_reset_filter, .btn_reset').on('click', function () {
        $('#filter_cycle').val('<?= (int) $default_cycle_id ?>').trigger('change.select2');
        $('#filter_division').val('0').trigger('change.select2');
        $('#filter_status').val('ALL').trigger('change.select2');
        $('#filter_target_group').val(defaultTargetGroup).trigger('change.select2');
        syncGradeSwitcherUI(defaultTargetGroup);
        reloadDataTable();
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
            url: '<?= base_url('sas/penilaian/auditLogs') ?>/' + appraisalId,
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

        var targetTitle = empName ? 'evaluasi <b>' + esc(String(empName).toUpperCase()) + '</b>' : 'form evaluasi ini';

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
                    url: '<?= base_url('sas/monitoring-penilaian/nudge') ?>',
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
