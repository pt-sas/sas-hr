<div class="card card-filter mb-3 shadow-none border" style="border-radius: 10px; border-color: #e2e8f0 !important; background: #ffffff;">
    <div class="card-body p-3">
        <form class="form-horizontal" id="filter_preview_blast">
            <div class="row align-items-end">
                <!-- Status Kesiapan -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <label for="blast_filter_readiness" class="font-weight-bold text-muted small mb-1">Status Kesiapan</label>
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_readiness" name="readiness_status" style="width: 100%;">
                                <option value="">Semua Status Kesiapan</option>
                                <option value="READY">Siap (Lengkap)</option>
                                <option value="ALREADY_GENERATED">Sudah Digenerate</option>
                                <option value="MISSING_TEMPLATE">Tanpa Template</option>
                                <option value="MISSING_EVALUATOR">Tanpa Atasan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cabang -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <label for="blast_filter_branch" class="font-weight-bold text-muted small mb-1">Cabang</label>
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_branch" name="branch" style="width: 100%;">
                                <option value="">Semua Cabang</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Divisi -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <label for="blast_filter_division" class="font-weight-bold text-muted small mb-1">Divisi</label>
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_division" name="division" style="width: 100%;">
                                <option value="">Semua Divisi</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Level Jabatan -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <label for="blast_filter_level" class="font-weight-bold text-muted small mb-1">Level Jabatan</label>
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_level" name="level" style="width: 100%;">
                                <option value="">Semua Level</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Status Template -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <label for="blast_filter_template_status" class="font-weight-bold text-muted small mb-1">Status Template</label>
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_template_status" name="template_status" style="width: 100%;">
                                <option value="">Semua Status Template</option>
                                <option value="PUBLISHED">PUBLISHED</option>
                                <option value="DRAFT">DRAFT</option>
                                <option value="NONE">Belum Ditugaskan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons in One Row -->
                <div class="col-md-2 col-sm-12">
                    <div class="form-group mb-0 d-flex align-items-center">
                        <button type="button" class="btn btn-primary btn-sm btn-round btn_filter_blast" title="Terapkan Filter">
                            <i class="fas fa-search fa-fw"></i> Filter
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-round btn_reset_blast ml-2" id="btn_reset_filter_blast" title="Reset Semua Filter">
                            <i class="fas fa-undo fa-fw"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
