<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_overtime">
        <?= csrf_field(); ?>
        <div class="row">
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
            <div class="col-md-3"></div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="documentno">No Form</label>
                    <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_branch_id">Cabang <span class="required">*</span></label>
                    <select class="form-control select2" id="md_branch_id" name="md_branch_id">
                        <option value="">Select Cabang</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_division_id">Divisi <span class="required">*</span></label>
                    <select class="form-control select2" id="md_division_id" name="md_division_id">
                        <option value="">Select Divisi</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="submissiondate">Tanggal Pembuatan <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                            value=<?= $today ?>>
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
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Deskripsi <span class="required">*</span></label>
                    <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Mulai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-lembur" name="startdate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" name="isemployee" checked
                            hide-field="md_supplier_id">
                        <span class=" form-check-sign">Karyawan</span>
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Selesai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" name="enddate" readonly>
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_supplier_id">Asal Outsourcing <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_supplier_id" name="md_supplier_id"
                        data-url="supplier/getList">
                        <option value="">Select Supplier</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_supplier_id"></small>
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
                    <table class="table table-light table-hover tb_displayline" id="table_overtime" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Waktu Realisasi</th>
                                <th>Saldo Lembur</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>