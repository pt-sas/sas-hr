<!-- Modal Appraisal Cycle Progress Monitoring -->
<div class="modal fade" id="modal_cycle_progress" modal-type="not-main" tabindex="-1" role="dialog" aria-labelledby="modalCycleProgressLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 900px;">
        <div class="modal-content shadow-lg border-0">
            <!-- Modal Header -->
            <div class="modal-header bg-light border-bottom">
                <div>
                    <h5 class="modal-title font-weight-bold text-primary mb-1" id="modalCycleProgressLabel">
                        <i class="fas fa-chart-line mr-2"></i>Monitoring Progres Evaluasi Karyawan
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 12.5px;">
                        <span id="prog_cycle_title" class="font-weight-bold text-dark">-</span> &middot; 
                        <span id="prog_cycle_deadlines" class="text-muted">Deadline: -</span>
                    </p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Overall Progress Bar Container -->
                <div class="card p-3 border mb-3" style="background: #fdfefe;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="font-weight-bold text-dark" style="font-size: 13px;">Persentase Penyelesaian Seluruh Karyawan</span>
                        <span class="font-weight-bold text-primary" id="prog_overall_percent_text" style="font-size: 14px;">0.0%</span>
                    </div>
                    <div class="progress" style="height: 18px; border-radius: 9px; background-color: #e9ecef;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="prog_overall_percent_bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            0%
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="row mb-3">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="card card-stats card-round border mb-0 text-center p-2" style="background: #f8fbfd;">
                            <small class="text-muted text-uppercase font-weight-bold" style="font-size: 10.5px;">Total Form</small>
                            <h4 class="font-weight-bold text-primary mb-0 mt-1" id="prog_kpi_total">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="card card-stats card-round border mb-0 text-center p-2" style="background: #fffdf5;">
                            <small class="text-muted text-uppercase font-weight-bold" style="font-size: 10.5px;">Draft Atasan</small>
                            <h4 class="font-weight-bold text-warning mb-0 mt-1" id="prog_kpi_draft">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="card card-stats card-round border mb-0 text-center p-2" style="background: #f0f7ff;">
                            <small class="text-muted text-uppercase font-weight-bold" style="font-size: 10.5px;">Menunggu Review GM</small>
                            <h4 class="font-weight-bold text-info mb-0 mt-1" id="prog_kpi_review">0</h4>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="card card-stats card-round border mb-0 text-center p-2" style="background: #f6fcf8;">
                            <small class="text-muted text-uppercase font-weight-bold" style="font-size: 10.5px;">Final / Selesai</small>
                            <h4 class="font-weight-bold text-success mb-0 mt-1" id="prog_kpi_finalized">0</h4>
                        </div>
                    </div>
                </div>

                <!-- Division Breakdown Table -->
                <div class="mt-4">
                    <h6 class="font-weight-bold text-dark mb-2" style="font-size: 13px;">
                        <i class="fas fa-sitemap mr-1 text-primary"></i> Rincian Progres Per Divisi
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered" id="tb_progress_divisions" style="width: 100%; font-size: 12px;">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">No</th>
                                    <th>Nama Divisi</th>
                                    <th style="width: 90px;" class="text-center">Total Form</th>
                                    <th style="width: 110px;" class="text-center">Sedang Berjalan</th>
                                    <th style="width: 90px;" class="text-center">Selesai</th>
                                    <th style="width: 140px;">Progres (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated dynamically via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm btn-round" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
