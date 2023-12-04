<div class="card-body card-form">
    <form class="form-horizontal" id="form_menu">
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
                    <label for="sequence">Sequence <span class="required">*</span></label>
                    <input type="text" class="form-control number" id="sequence" name="sequence">
                    <small class="form-text text-danger" id="error_sequence"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="url">URL <span class="required">*</span></label>
                    <input type="text" class="form-control" id="url" name="url">
                    <small class="form-text text-danger" id="error_url"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="action">Action </label>
                    <select class="form-control select2" id="action" name="action">
                        <option value="">Select Action</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="icon">Icon <span class="required">*</span></label>
                    <input type="text" class="form-control" id="icon" name="icon" placeholder="fas fa-tachometer-alt">
                    <small class="form-text text-danger" id="error_icon"></small>
                </div>
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