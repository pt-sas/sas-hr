<form class="form-horizontal" id="form_emp_user">
    <?= csrf_field(); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" class="form-control" id="username" name="username">
                <small class="form-text text-danger" id="error_username"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="name">Nama <span class="required">*</span></label>
                <input type="text" class="form-control" id="name" name="name">
                <small class="form-text text-danger" id="error_name"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="email">Email </label>
                <input type="text" class="form-control" name="email">
                <small class="form-text text-danger" id="error_email"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" class="form-control" id="password" name="password">
                <small class="form-text text-danger" id="error_password"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_employee_id">Karyawan <span class="required">*</span></label>
                <input type="text" class="form-control foreignkey" id="md_employee_id" name="md_employee_id" data-url="karyawan/getDataBy" readonly>
                <small class="form-text text-danger" id="error_md_employee_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Hak Akses </label>
                <div class="select2-input select2-primary">
                    <select class="form-control multiple-select" name="role" multiple="multiple" style="width: 100%;">
                        <?php foreach ($role as $row) : ?>
                            <option value="<?= $row->sys_role_id; ?>"><?= $row->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="description">Deskripsi </label>
                <textarea type="text" class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
        </div>
        <div class="col-md-2 mt-4">
            <div class="form-check">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input active" name="isactive">
                    <span class="form-check-sign">Aktif</span>
                </label>
            </div>
        </div>
    </div>
</form>