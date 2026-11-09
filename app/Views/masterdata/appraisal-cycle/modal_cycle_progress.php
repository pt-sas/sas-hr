<!-- Modal Appraisal Cycle Progress Monitoring -->
<div class="modal fade" id="modal_cycle_progress" modal-type="not-main" tabindex="-1" role="dialog" aria-labelledby="modalCycleProgressLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 90%;">
        <div class="modal-content border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            
            <!-- Modal Header: Clean & Structured like Blast -->
            <div class="modal-header bg-white px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center">
                        <h5 class="modal-title font-weight-bold text-dark mb-0" id="modalCycleProgressLabel" style="font-size: 16px; letter-spacing: -0.2px;">
                            Monitoring Progres Evaluasi Karyawan
                        </h5>
                        <span id="prog_cycle_info" class="text-muted ml-2 font-weight-normal" style="font-size: 13px;">
                            &middot; <span id="prog_cycle_title" class="font-weight-bold text-dark">-</span>
                        </span>
                    </div>
                </div>
                <button type="button" class="close p-2 m-0 text-muted" data-dismiss="modal" aria-label="Close" style="outline: none; opacity: 0.6;">
                    <span aria-hidden="true" style="font-size: 22px;">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light-soft" style="background-color: #fafbfc;">

                <!-- Unified Metric Ribbon (Exact same styling & layout as Blast) -->
                <div class="card border mb-4 shadow-none" style="border-radius: 10px; border-color: #e2e8f0 !important; background: #ffffff;">
                    <div class="card-body p-0">
                        <div class="row no-gutters divide-cols">
                            
                            <!-- Total Form -->
                            <div class="col-6 col-md-3 p-3 px-md-4">
                                <span class="d-block text-muted font-weight-bold text-uppercase mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                    Total Form
                                </span>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-dark mb-0" id="prog_kpi_total">0</span>
                                    <span class="text-muted ml-1" style="font-size: 11px;">form</span>
                                </div>
                            </div>

                            <!-- Draft / Penilaian -->
                            <div class="col-6 col-md-3 p-3 px-md-4 border-left">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="status-dot bg-warning mr-1"></span>
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        Penilaian
                                    </span>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-warning mb-0" id="prog_kpi_draft">0</span>
                                    <small class="text-muted ml-1 font-weight-bold" style="font-size: 11px;">draft</small>
                                </div>
                            </div>

                            <!-- Menunggu Review -->
                            <div class="col-6 col-md-3 p-3 px-md-4 border-left">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="status-dot bg-info mr-1"></span>
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        Review
                                    </span>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-info mb-0" id="prog_kpi_review">0</span>
                                    <small class="text-muted ml-1" style="font-size: 11px;">atasan / TTD</small>
                                </div>
                            </div>

                            <!-- Final / Selesai -->
                            <div class="col-6 col-md-3 p-3 px-md-4 border-left">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="status-dot bg-success mr-1"></span>
                                    <span class="text-muted font-weight-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                        Final
                                    </span>
                                </div>
                                <div class="d-flex align-items-baseline">
                                    <span class="h3 font-weight-bold text-success mb-0" id="prog_kpi_finalized">0</span>
                                    <small class="text-muted ml-1" style="font-size: 11px;">selesai</small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Division Breakdown Datatable -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered" id="tb_progress_divisions" style="width: 100%; font-size: 12px;">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>Nama Divisi</th>
                                <th style="width: 90px;" class="text-center">Total Form</th>
                                <th style="width: 110px;" class="text-center">Sedang Berjalan</th>
                                <th style="width: 90px;" class="text-center">Selesai</th>
                                <th style="width: 150px;">Progres (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated dynamically via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top d-flex justify-content-end">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    Tutup
                </button>
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
