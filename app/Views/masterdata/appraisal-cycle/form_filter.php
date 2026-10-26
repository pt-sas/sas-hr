<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_appraisal_cycle">
                    <div class="row">
                        <!-- Year Filter -->
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="filter_year">Tahun Periode</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_year" name="year">
                                        <option value="">Semua Tahun</option>
                                        <?php for ($y = (int) date('Y') + 1; $y >= 2022; $y--) : ?>
                                            <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-6 col-lg-4">
                            <div class="form-group">
                                <label for="filter_status">Status Siklus</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="DRAFT">DRAFT (Belum Dibuka)</option>
                                        <option value="OPEN">OPEN (Sedang Aktif)</option>
                                        <option value="CLOSED">CLOSED (Selesai / Ditutup)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 col-lg-4 d-flex align-items-end mb-3">
                            <button type="button" class="btn btn-primary btn-sm btn-round btn_filter mr-2" title="Terapkan Filter">
                                <i class="fas fa-search fa-fw"></i> Filter
                            </button>
                            <button type="button" class="btn btn-black btn-border btn-sm btn-round btn_reset_filter" title="Reset Filter" onclick="$('#filter_appraisal_cycle')[0].reset(); $('#filter_year, #filter_status').val('').trigger('change'); $('.btn_filter').click();">
                                <i class="fas fa-undo fa-fw"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
