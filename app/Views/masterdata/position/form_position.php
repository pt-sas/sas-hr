<div class="card-body card-form">
    <form class="form-horizontal" id="form_position">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="value">Kode Posisi <span class="required">*</span></label>
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
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_division_id">Divisi </label>
                    <select class="form-control select-data" id="md_division_id" name="md_division_id"
                        data-url="division/getList">
                        <option value="">Pilih Divisi</option>
                    </select>
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
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="ismandatoryduta" name="ismandatoryduta">
                        <span class="form-check-sign">Wajib Duta</span>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>