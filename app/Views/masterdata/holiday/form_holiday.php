<div class="card-body card-form">
    <form class="form-horizontal" id="form_holiday">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="startdate">Tanggal <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="startdate" name="startdate">
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_religion_id">Agama</label>
                    <select class="form-control select-data" name="md_religion_id" id="md_religion_id" data-url="religion/getList">
                        <option value="">Pilih Agama</option>
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
                </div>
            </div>
        </div>
    </form>
</div>