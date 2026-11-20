<div class="card card-filter mb-3 shadow-none border" style="border-radius: 10px; border-color: #e2e8f0 !important; background: #ffffff;">
    <div class="card-body p-3">
        <form class="form-horizontal" id="filter_preview_blast">
            <div class="row align-items-end">
                <!-- Status Kesiapan -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_readiness" name="readiness_status" style="width: 100%;">
                                <option value="">Semua Status Kesiapan</option>
                                <option value="READY">Siap Rilis</option>
                                <option value="TEMPLATE_DRAFT">Template Draft</option>
                                <option value="ALREADY_GENERATED">Sudah Digenerate</option>
                                <option value="MISSING_TEMPLATE">Tanpa Template</option>
                                <option value="MISSING_EVALUATOR">Tanpa Atasan</option>
                                <option value="INCOMPLETE">Belum Lengkap</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cabang -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
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
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_level" name="level" style="width: 100%;">
                                <option value="">Semua Level</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Posisi -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group mb-0">
                        <div class="select2-input select2-primary">
                            <select class="form-control select2-blast" id="blast_filter_position" name="position" style="width: 100%;">
                                <option value="">Semua Posisi</option>
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
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
