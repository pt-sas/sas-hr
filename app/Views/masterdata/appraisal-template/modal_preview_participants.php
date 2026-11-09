<div class="modal fade" id="modal_preview_participants" modal-type="not-main" tabindex="-1" role="dialog" aria-labelledby="modal_preview_participants_title" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow">
            
            <!-- Modal Header -->
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center">
                    <span class="preview-avatar-icon mr-3">
                        <i class="fas fa-users text-primary"></i>
                    </span>
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0" id="modal_preview_participants_title">
                            Simulasi Peserta Target Evaluasi
                        </h5>
                        <small class="text-muted" id="preview_tpl_summary_text">Berdasarkan cakupan Cabang, Divisi, dan Level yang dipilih saat ini</small>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3">
                <!-- Ringkasan Filter Target Scope -->
                <div class="preview-scope-summary mb-3 p-2 px-3 bg-light rounded border">
                    <div class="row align-items-center" style="font-size: 12.5px;">
                        <div class="col-md-4 mb-1 mb-md-0">
                            <span class="text-muted font-weight-bold"><i class="fas fa-building mr-1"></i> Cabang:</span>
                            <span id="preview_scope_branch" class="text-dark ml-1 font-weight-600">Semua Cabang</span>
                        </div>
                        <div class="col-md-4 mb-1 mb-md-0">
                            <span class="text-muted font-weight-bold"><i class="fas fa-sitemap mr-1"></i> Divisi:</span>
                            <span id="preview_scope_division" class="text-dark ml-1 font-weight-600">Semua Divisi</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted font-weight-bold"><i class="fas fa-layer-group mr-1"></i> Level:</span>
                            <span id="preview_scope_levelling" class="text-dark ml-1 font-weight-600">Semua Level</span>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover tb_preview_participants w-100 mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th style="width: 90px;">NIK</th>
                                <th>Nama Karyawan</th>
                                <th>Cabang</th>
                                <th>Divisi</th>
                                <th>Level / Jabatan</th>
                                <th>Penilai (Evaluator)</th>
                                <th>Peninjau (Reviewer)</th>
                            </tr>
                        </thead>
                        <tbody id="tb_preview_participants_body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="badge badge-primary px-3 py-2 font-weight-bold" id="badge_preview_total_peserta" style="font-size: 12px;">
                        Total: 0 Karyawan
                    </span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-4 btn-round" data-dismiss="modal">
                    Tutup
                </button>
            </div>
            
        </div>
    </div>
</div>

<style>
.preview-avatar-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(21, 114, 232, 0.1);
    font-size: 1.15rem;
}
.tb_preview_participants {
    font-size: 12.5px !important;
}
.tb_preview_participants th {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .3px;
    color: #495057;
    border-top: none !important;
}
.tb_preview_participants td {
    vertical-align: middle !important;
}
</style>
