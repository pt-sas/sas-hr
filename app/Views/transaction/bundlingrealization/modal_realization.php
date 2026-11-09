<div class="modal fade" id="modal_bundling_realization_agree">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_bundling_realization_agree">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="br_documentno">No. Dokumen</label>
                                <input type="text" class="form-control" id="br_documentno" disabled>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="br_applicant">Pemohon</label>
                                <input type="text" class="form-control" id="br_applicant" disabled>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="br_tipe">Tipe Penilaian</label>
                                <input type="text" class="form-control" id="br_tipe" disabled>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="br_employee">Karyawan <span class="required">*</span></label>
                                <select class="form-control" id="br_employee" multiple></select>
                                <small class="form-text text-danger" id="error_employees"></small>
                            </div>
                        </div>

                        <!-- Tipe Penilaian: Jam -->
                        <div class="col-md-12 d-none" id="br_jam">
                            <div class="alert alert-light border" id="br_info"></div>
                            <div id="br_cards"></div>
                            <small class="form-text text-danger" id="error_lines"></small>
                        </div>

                        <!-- Tipe Penilaian: Hasil -->
                        <div class="col-md-12 d-none" id="br_hasil">
                            <div class="alert alert-light border" id="br_task"></div>
                            <div class="form-group">
                                <label for="br_photo_pick">Foto Hasil <span class="required">*</span></label>
                                <input type="file" class="form-control" id="br_photo_pick" accept="image/*" multiple>
                                <small class="form-text text-muted">Maksimal 20 foto (JPG, PNG, WEBP, GIF), 10 MB per foto.</small>
                                <ul class="list-group mt-2" id="br_photo_list"></ul>
                                <div class="d-none" id="br_photo_inputs"></div>
                                <small class="form-text text-danger" id="error_photos"></small>
                            </div>
                            <div class="form-group">
                                <label for="description">Deskripsi Pengerjaan <span class="required">*</span></label>
                                <textarea class="form-control" name="description" id="description" rows="4" maxlength="255"></textarea>
                                <small class="form-text text-danger" id="error_description"></small>
                            </div>
                        </div>

                        <input type="hidden" name="employees">
                        <input type="hidden" name="lines">
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="text-muted" id="br_sum"></div>
                <div>
                    <button type="button" class="btn btn-icon btn-round btn-danger btn_close_realization"
                        data-toggle="tooltip" data-placement="top" title="Cancel" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                    <button type="button" class="btn btn-icon btn-round btn-primary btn_ok_realization"
                        data-toggle="tooltip" data-placement="top" title="OK">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>