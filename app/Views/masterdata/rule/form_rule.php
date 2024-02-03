<form class="form-horizontal" id="form_rule">
    <?= csrf_field(); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Menu <span class="required">*</span></label>
                <div class="select2-input select2-primary">
                    <select class="form-control multiple-select" name="menu_url" multiple="multiple" style="width: 100%;">
                        <?php foreach ($menu as $row) : ?>
                            <option value="<?= $row['url']; ?>"><?= $row['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_menu_url"></small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="name">Nama Rule <span class="required">*</span></label>
                <input type="text" class="form-control" id="name" name="name">
                <small class="form-text text-danger" id="error_name"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="condition">Kondisi </label>
                <input type="text" class="form-control" id="condition" name="condition">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="value">Value </label>
                <input type="text" class="form-control" id="value" name="value">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="priority">Prioritas <span class="required">*</span></label>
                <input type="text" class="form-control number" id="priority" name="priority">
                <small class="form-text text-danger" id="error_priority"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="isdetail">Detail </label>
                <select class="form-control select-data" id="isdetail" name="isdetail" data-url="reference/getList/$StatusActive">
                    <option value="">Select Detail</option>
                </select>
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