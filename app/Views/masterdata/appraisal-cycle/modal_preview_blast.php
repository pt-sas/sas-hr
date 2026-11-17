<div class="modal fade" id="modal_preview_blast" modal-type="not-main" tabindex="-1" role="dialog" aria-labelledby="modalPreviewBlastLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; position: relative;">
            
            <button type="button" class="close text-muted" data-dismiss="modal" aria-label="Close" style="position: absolute; top: 12px; right: 16px; z-index: 1050; outline: none; opacity: 0.7; font-size: 26px; line-height: 1;">
                <span aria-hidden="true">&times;</span>
            </button>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light-soft" style="background-color: #fafbfc;">

                <!-- Unified Metric Ribbon (Replaces individual bulky cards) -->
                <div class="card border mb-3 shadow-none" style="border-radius: 10px; border-color: #e2e8f0 !important; background: #ffffff;">
                    <div class="card-body p-0">
                        <div class="row no-gutters divide-cols">
                            
                            <!-- Total Karyawan -->
                            <div class="col-6 col-md-3 p-3 px-md-4 kpi-card-clickable" data-kpi-filter="" title="Tampilkan semua karyawan" style="cursor: pointer;">
                                <span class="d-block text-muted font-weight-bold text-uppercase mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                    Total Target
                                </span>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-dark mb-0" id="blast_kpi_total">0</span>
                                    <span class="text-muted ml-1" style="font-size: 11px;">karyawan</span>
                                </div>
                            </div>

                            <!-- Siap Distribusi -->
                            <div class="col-6 col-md-3 p-3 px-md-4 border-left kpi-card-clickable" data-kpi-filter="READY" title="Filter karyawan siap rilis" style="cursor: pointer;">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="status-dot bg-success mr-1"></span>
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        Siap Rilis
                                    </span>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-success mb-0" id="blast_kpi_ready">0</span>
                                    <small class="text-muted ml-1 font-weight-bold" style="font-size: 11px;">lengkap</small>
                                </div>
                            </div>

                            <!-- Missing Template -->
                            <div class="col-6 col-md-3 p-3 px-md-4 border-left kpi-card-clickable" data-kpi-filter="MISSING_TEMPLATE" title="Filter karyawan tanpa template" style="cursor: pointer;">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="status-dot bg-warning mr-1"></span>
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        Tanpa Template
                                    </span>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-warning mb-0" id="blast_kpi_missing_tpl">0</span>
                                    <small class="text-muted ml-1" style="font-size: 11px;">butuh mapping</small>
                                </div>
                            </div>

                            <!-- Missing Evaluator -->
                            <div class="col-6 col-md-3 p-3 px-md-4 border-left kpi-card-clickable" data-kpi-filter="MISSING_EVALUATOR" title="Filter karyawan tanpa atasan langsung" style="cursor: pointer;">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="status-dot bg-danger mr-1"></span>
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        Tanpa Atasan
                                    </span>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-danger mb-0" id="blast_kpi_missing_eval">0</span>
                                    <small class="text-muted ml-1" style="font-size: 11px;">hierarki kosong</small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <?= $this->include('masterdata/appraisal-cycle/form_filter_blast'); ?>

                <!-- Datatable Container -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tb_preview_blast" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">No</th>
                                <th style="width: 90px;">NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Posisi</th>
                                <th>Cabang</th>
                                <th>Divisi</th>
                                <th>Level Jabatan</th>
                                <th>Template Terpilih</th>
                                <th>Penilai</th>
                                <th>Peninjau</th>
                                <th style="width: 110px;" class="text-center">Kesiapan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated dynamically via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top d-flex justify-content-between">
                <div id="blast_action_container">
                    <button type="button" class="btn btn-primary btn-sm" id="btn_blast_open_cycle" style="display: none;">
                        Buka Siklus &amp; Generate Form Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Lightweight utility for modern status indicators */
    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    @media (max-width: 767.98px) {
        .divide-cols > div:nth-child(2) {
            border-left: 1px solid #e2e8f0 !important;
        }
        .divide-cols > div:nth-child(3),
        .divide-cols > div:nth-child(4) {
            border-top: 1px solid #e2e8f0;
        }
        .divide-cols > div:nth-child(4) {
            border-left: 1px solid #e2e8f0 !important;
        }
    }
</style>