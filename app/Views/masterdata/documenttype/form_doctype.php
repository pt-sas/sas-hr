<div class="card-body card-form">
    <form class="form-horizontal" id="form_doctype">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-2 mt-4">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="isrealization" name="isrealization">
                        <span class="form-check-sign">Realisasi</span>
                    </label>
                    <small class="form-text text-danger" id="error_isrealization"></small>
                </div>
            </div>
            <div class="col-md-2 mt-4">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="isapprovedline" name="isapprovedline">
                        <span class="form-check-sign">Approved Line</span>
                    </label>
                    <small class="form-text text-danger" id="error_isapprovedline"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="days_realization_mgr">Hari bisa direalisasi Manager </label>
                    <input type="text" class="form-control number" id="days_realization_mgr"
                        name="days_realization_mgr">
                    <small class="form-text text-danger" id="error_days_realization_mgr"></small>
                </div>
                <!-- <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="is_realization_mgr"
                            name="is_realization_mgr">
                        <span class="form-check-sign">Realisasi Manager</span>
                    </label>
                </div> -->
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="days_realization_hrd">Hari bisa direalisasi HRD </label>
                    <input type="text" class="form-control number" id="days_realization_hrd"
                        name="days_realization_hrd">
                    <small class="form-text text-danger" id="error_days_realization_hrd"></small>
                </div>
                <!-- <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="is_realization_hrd"
                            name="is_realization_hrd">
                        <span class="form-check-sign">Realisasi HRD</span>
                    </label>
                </div> -->
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Auto Reject Approval</label>
                    <input type="text" class="form-control number" id="auto_not_approve_days"
                        name="auto_not_approve_days">
                    <small class="form-text text-danger" id="error_auto_not_approve_days"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Keterangan </label>
                    <textarea type="text" class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Aktif</span>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>