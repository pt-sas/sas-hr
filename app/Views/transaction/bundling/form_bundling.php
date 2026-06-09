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
                    <label for="bundling_type">Tipe Paket <span class="required">*</span></label>
                    <select class="form-control select2" id="bundling_type" name="bundling_type">
                        <option value="">Pilih Paket</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <option value="<?= $row->name ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_bundling_type"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="documentno">No Form</label>
                    <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Pemohon <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                        data-url="employee/getList/$Spesific">
                        <option value="">Select Karyawan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_employee_id"></small>
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
                            readonly>
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_approveddate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_branch_id">Cabang <span class="required">*</span></label>
                    <select class="form-control select2" id="md_branch_id" name="md_branch_id" disabled>
                        <option value="">Select Cabang</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_branch_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_division_id">Divisi <span class="required">*</span></label>
                    <select class="form-control select2" id="md_division_id" name="md_division_id" disabled>
                        <option value="">Select Divisi</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
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
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="text-right">
                        <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto add_row"
                            title="Tambah Baru"><i class="fa fa-plus fa-fw"></i> Tambah</button>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <table class="table table-light table-hover tb_displayline tb_childrow" id="table_overtime" style="width: 100%">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Karyawan</th>
                                <th>Total Jam</th>
                                <th>Total Rupiah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>