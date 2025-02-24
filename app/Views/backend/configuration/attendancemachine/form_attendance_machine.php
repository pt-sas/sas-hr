<div class="card-body card-form">
    <form class="form-horizontal" id="form_attendance_machine">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="serialnumber">Nomor Seri <span class="required">*</span></label>
                    <input type="text" class="form-control" id="serialnumber" name="serialnumber">
                    <small class="form-text text-danger" id="error_serialnumber"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_branch_id">Cabang <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_branch_id" name="md_branch_id"
                        data-url="branch/getList">
                        <option value="">Select Cabang</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_branch_id"></small>
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