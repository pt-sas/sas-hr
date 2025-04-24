<div class="card-body card-form">
    <form class="form-horizontal" id="form_menu">
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
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Description </label>
                    <textarea type="text" class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email">Email </label>
                    <input type="text" class="form-control" id="email" name="email">
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
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_employee_id">Nama Karyawan </label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                        data-url="employee/getList">
                        <option value="">Select Karyawan</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sys_role_id">Hak Akses </label>
                    <div class="select2-input select2-primary">
                        <select class="form-control multiple-select" id="sys_role_id" name="sys_role_id"
                            multiple="multiple" style="width: 100%;">
                            <?php foreach ($role as $row) : ?>
                            <option value="<?= $row->sys_role_id; ?>"><?= $row->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_branch_id">Hak Akses Cabang <span class="required">*</span></label>
                    <div class="select2-input select2-primary">
                        <select class="form-control multiple-select" id="md_branch_id" name="md_branch_id"
                            multiple="multiple" style="width: 100%;">
                            <?php foreach ($branch as $row) : ?>
                            <option value="<?= $row->getBranchId(); ?>"><?= $row->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-danger" id="error_md_branch_id"></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_division_id">Hak Akses Divisi <span class="required">*</span></label>
                    <div class="select2-input select2-primary">
                        <select class="form-control multiple-select" id="md_division_id" name="md_division_id"
                            multiple="multiple" style="width: 100%;">
                            <?php foreach ($division as $row) : ?>
                            <option value="<?= $row->getDivisionId(); ?>"><?= $row->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-danger" id="error_md_division_id"></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sys_emp_delegation_id">Mewakili Karyawan <span class="required">*</span></label>
                    <div class="select2-input select2-primary">
                        <select class="form-control multiple-select" id="sys_emp_delegation_id"
                            name="sys_emp_delegation_id" multiple="multiple" style="width: 100%;">
                            <?php foreach ($employee as $row) : ?>
                            <option value="<?= $row['md_employee_id']; ?>"><?= $row['value']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-danger" id="error_sys_emp_delegation_id"></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_levelling_id">Level <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_levelling_id" name="md_levelling_id"
                        data-url="levelling/getList">
                        <option value="">Select Level</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_levelling_id"></small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Active</span>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>