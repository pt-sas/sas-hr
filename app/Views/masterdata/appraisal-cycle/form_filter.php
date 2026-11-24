<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_appraisal_cycle">
                    <div class="row align-items-end">
                        <!-- Filter Tahun -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_year" name="year" title="Pilih Tahun Penilaian" data-placeholder="Pilih opsi tahun">
                                        <option value=""></option>
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
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_semester" name="semester" title="Pilih Semester" data-placeholder="Pilih opsi semester">
                                        <option value=""></option>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Status -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_status" name="status" title="Pilih Status Siklus" data-placeholder="Pilih opsi status">
                                        <option value=""></option>
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
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>