<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_appraisal_cycle">
                    <div class="row align-items-end">
                        <!-- Filter Tahun -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label for="filter_year" class="font-weight-bold text-muted small mb-1">Tahun Penilaian</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_year" name="year" title="Pilih Tahun Penilaian" data-placeholder="Pilih Tahun...">
                                        <option value="">Pilih Tahun...</option>
                                        <?php for ($y = (int) date('Y') + 1; $y >= 2020; $y--) : ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Semester -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label for="filter_semester" class="font-weight-bold text-muted small mb-1">Semester</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_semester" name="semester" title="Pilih Semester" data-placeholder="Pilih Semester...">
                                        <option value="">Pilih Semester...</option>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Status -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label for="filter_status" class="font-weight-bold text-muted small mb-1">Status Siklus</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_status" name="status" title="Pilih Status Siklus" data-placeholder="Pilih Status...">
                                        <option value="">Pilih Status...</option>
                                        <option value="DRAFT">DRAFT (Belum Dibuka)</option>
                                        <option value="OPEN">OPEN (Sedang Aktif)</option>
                                        <option value="CLOSED">CLOSED (Selesai / Ditutup)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-3">
                            <div class="form-group mb-0 d-flex align-items-center">
                                <button type="button" class="btn btn-primary btn-sm btn-round btn_filter" title="Terapkan Filter">
                                    <i class="fas fa-search fa-fw"></i> Filter
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-round btn_reset ml-2" id="btn_reset_filter" title="Reset Semua Filter">
                                    <i class="fas fa-undo fa-fw"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>