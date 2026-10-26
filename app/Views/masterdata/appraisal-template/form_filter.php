<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_appraisal_template">
                    <div class="row">
                        <!-- Filter Posisi -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="select2-input select2-primary">
                                    <select class="form-control multiple-select-branch access" name="md_branch_id"></select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="select2-input select2-primary">
                                    <select class="form-control multiple-select-division access"
                                        name="md_division_id"></select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="select2-input select2-primary">
                                    <select class="form-control multiple-select-levelling"
                                        name="md_levelling_id"></select>
                                </div>
                            </div>
                        </div>
                        <!-- Filter Status -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="DR">Draft (DR)</option>
                                        <option value="CO">Published / Completed (CO)</option>
                                        <option value="VO">Archived / Voided (VO)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-sm btn-round btn_filter" title="Filter">
                                    <i class="fas fa-search fa-fw"></i> Filter
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-round btn_reset ml-1" id="btn_reset_filter" title="Reset">
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
