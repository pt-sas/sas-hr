<div class="card-body card-form">
    <form class="form-horizontal" id="form_responsible">
        <?= csrf_field(); ?>
        <div class="row">
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
                    <textarea type="text" class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="responsibletype">Responsible Type <span class="required">*</span></label>
                    <select class="form-control select2" id="responsibletype" name="responsibletype" hide-field="sys_role_id, sys_user_id">
                        <option value="">Select Responsible Type</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_responsibletype"></small>
                </div>
                <div class="form-group">
                    <label>Alert Recipient <span class="required">*</span></label>
                    <div class="select2-input select2-primary">
                        <select class="form-control multiple-select" name="alert" multiple="multiple" style="width: 100%;">
                            <?php foreach ($user as $row) : ?>
                                <option value="<?= $row->sys_user_id; ?>"><?= $row->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-danger" id="error_alert"></small>
                    </div>
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Active</span>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sys_role_id">Role <span class="required">*</span></label>
                    <select class="form-control select-data" id="sys_role_id" name="sys_role_id" data-url="role/getList">
                        <option value="">Select Role</option>
                    </select>
                    <small class="form-text text-danger" id="error_sys_role_id"></small>
                </div>
                <div class="form-group">
                    <label for="sys_user_id">User <span class="required">*</span></label>
                    <select class="form-control select-data" id="sys_user_id" name="sys_user_id" data-url="user/getList">
                        <option value="">Select Role</option>
                    </select>
                    <small class="form-text text-danger" id="error_sys_user_id"></small>
                </div>
            </div>
        </div>
    </form>
</div>