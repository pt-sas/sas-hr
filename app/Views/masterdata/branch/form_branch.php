<div class="card-body card-form">
    <form class="form-horizontal" id="form_branch">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="value">Branch Code <span class="required">*</span></label>
                    <input type="text" class="form-control code" id="value" name="value" readonly>
                    <small class="form-text text-danger" id="error_value"></small>
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
                    <label for="leader_id">PIC </label>
                    <select class="form-control select-data" id="leader_id" name="leader_id" data-url="employee/getList">
                        <option value="">Select PIC</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="phone">Telephone </label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="address">Address <span class="required">*</span></label>
                    <textarea type="text" class="form-control" id="address" name="address" rows="2"></textarea>
                    <small class="form-text text-danger" id="error_address"></small>
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