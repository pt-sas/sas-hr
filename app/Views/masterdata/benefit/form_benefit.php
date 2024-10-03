<form class="form-horizontal" id="form_benefit">
    <?= csrf_field(); ?>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="name">Nama <span class="required">*</span></label>
                <input type="text" class="form-control" id="name" name="name">
                <small class="form-text text-danger" id="error_name"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_branch_id">Cabang <span class="required">*</span></label>
                <select class="form-control select-data" id="md_branch_id" name="md_branch_id" data-url="branch/getList">
                    <option value="">Select Cabang</option>
                </select>
                <small class="form-text text-danger" id="error_md_branch_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_division_id">Divisi <span class="required">*</span></label>
                <select class="form-control select-data" id="md_division_id" name="md_division_id" data-url="division/getList">
                    <option value="">Select Divisi</option>
                </select>
                <small class="form-text text-danger" id="error_md_division_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_levelling_id">Level <span class="required">*</span></label>
                <select class="form-control select-data" id="md_levelling_id" name="md_levelling_id" data-url="levelling/getList">
                    <option value="">Select Level</option>
                </select>
                <small class="form-text text-danger" id="error_md_levelling_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_position_id">Jabatan <span class="required">*</span></label>
                <select class="form-control select-data" id="md_position_id" name="md_position_id" data-url="position/getList">
                    <option value="">Select Jabatan</option>
                </select>
                <small class="form-text text-danger" id="error_md_position_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_status_id">Status Karyawan <span class="required">*</span></label>
                <select class="form-control select-data" id="md_status_id" name="md_status_id" data-url="status/getList">
                    <option value="">Select Status Karyawan</option>
                </select>
                <small class="form-text text-danger" id="error_md_status_id"></small>
            </div>
        </div>
        <div class="col-md-2 mt-4">
            <div class="form-check">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                    <span class="form-check-sign">Active</span>
                </label>
            </div>
        </div>
    </div>
</form>