<div class="card-body card-form">
    <form class="form-horizontal" id="form_day">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="value">Kode Tipe Cuti <span class="required">*</span></label>
                    <input type="text" class="form-control code" id="value" name="value" readonly>
                    <small class="form-text text-danger" id="error_value"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="duration">Durasi</label>
                    <input type="text" class="form-control" id="duration" name="duration">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
                <div class="form-check">
                    <label>Tipe Durasi</label>
                    <br>
                    <label for="form-radio-label">
                        <input class="form-radio-input" type="radio" name="duration_type" value="D" id="duration_type" checked>
                        <span class="form-radio-sign">Hari</span>
                    </label>
                    <label for="form-radio-label" style="margin-left: 10px;">
                        <input class="form-radio-input" type="radio" name="duration_type" value="M" id="duration_type">
                        <span class="form-radio-sign">Bulan</span>
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select class="form-control select2" id="gender" name="gender">
                        <option value="">Select Status</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_gender"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Deskripsi </label>
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