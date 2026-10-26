<div class="modal fade" id="modal_preview_form" tabindex="-1" role="dialog" aria-labelledby="modal_preview_form_title" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 94vw;">
        <div class="modal-content border-0 shadow">
            
            <!-- Modal Header -->
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center">
                    <span class="preview-avatar-icon mr-3" style="width: 38px; height: 38px; border-radius: 8px; background: rgba(30, 41, 59, 0.08); display: inline-flex; align-items: center; justify-content: center;">
                        <i class="far fa-file-alt text-primary" style="font-size: 1.15rem;"></i>
                    </span>
                    <div>
                        <h5 class="modal-title font-weight-bold text-dark mb-0 d-flex align-items-center" id="modal_preview_form_title">
                            Pratinjau Lembar Form Evaluasi
                            <span class="badge badge-warning ml-2 font-weight-bold" style="font-size: 10px; letter-spacing: 0.5px;">SIMULASI MODE VIEW</span>
                        </h5>
                        <small class="text-muted" id="preview_form_tpl_name">Template: -</small>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-2 p-md-4" style="background-color: #f4f6f9; min-height: 400px;">
                
                <!-- Loading State -->
                <div id="preview_form_loader" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <h6 class="text-dark font-weight-bold mb-1">Menyiapkan Pratinjau Form...</h6>
                    <small class="text-muted">Memuat komponen visual evaluasi kinerja secara riil.</small>
                </div>

                <!-- Dynamic Rendered Container -->
                <div id="preview_form_content" style="display: none;"></div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light border-top py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1 text-primary"></i> Pratinjau ini menggunakan komponen riil form penilaian (mode <strong>VIEW</strong> hanya-baca, input dinonaktifkan).
                </small>
                <button type="button" class="btn btn-secondary btn-sm px-4 btn-round" data-dismiss="modal">
                    Tutup
                </button>
            </div>
            
        </div>
    </div>
</div>
