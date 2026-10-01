<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_bundling">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama Paket <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_division_id">Divisi <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_division_id" name="md_division_id" data-url="division/getList">
                        <option value="">Select Divisi</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="bundling_type">Tipe Paket <span class="required">*</span></label>
                    <select class="form-control select2" id="bundling_type" name="bundling_type">
                        <option value="">Pilih Paket</option>
                        <option value="rutin">Rutin</option>
                        <option value="non-rutin">Non Rutin</option>
                    </select>
                    <small class="form-text text-danger" id="error_bundling_type"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="minimal_time">Minimal Jam <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control number" id="minimal_time" name="minimal_time" autocomplete="off">
                        <div class="input-icon-addon">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_minimal_time"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="estimate_time">Estimasi Jam <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control number" id="estimate_time" name="estimate_time" autocomplete="off">
                        <div class="input-icon-addon">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_estimate_time"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="submissiondate">Tanggal Pembuatan</label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                            value=<?= $today ?> disabled>
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="approveddate">Tanggal Disetujui </label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="approveddate" name="approveddate"
                            disabled>
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_approveddate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Mulai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control date-start" name="startdate" autocomplete="off">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Selesai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control date-end" name="enddate" autocomplete="off">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="nominal_type">Kompensansi <span class="required">*</span></label>
                    <select class="form-control select2" id="nominal_type" name="nominal_type">
                        <option value="">Pilih Kompensansi</option>
                        <option value="orang">Per Orang</option>
                        <option value="fix pernilai">Fix Per Orang</option>
                        <option value="fix perdivisi">Fix Per Divisi</option>
                    </select>
                    <small class="form-text text-danger" id="error_nominal_type"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="recurring_type">Rekuren</label>
                    <select class="form-control select2" id="recurring_type" name="recurring_type">
                        <option value="">Pilih Rekuren</option>
                        <option value="hari">Harian</option>
                        <option value="minggu">Mingguan</option>
                        <option value="bulan">Bulanan</option>
                    </select>
                    <small class="form-text text-danger" id="recurring_type"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
            </div>
        </div>
    </form>
</div>