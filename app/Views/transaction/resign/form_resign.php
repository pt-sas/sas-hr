<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_resign">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Nama Karyawan <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                        data-url="employee/getList/$Access">
                        <option value="">Select Karyawan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_employee_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="nik">NIK <span class="required">*</span></label>
                    <input type="text" class="form-control" id="nik" name="nik" readonly>
                    <small class="form-text text-danger" id="error_nik"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="fullname">Nama Lengkap</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" readonly>
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
                    <label for="md_branch_id">Cabang <span class="required">*</span></label>
                    <select class="form-control select2" id="md_branch_id" name="md_branch_id" disabled>
                        <option value="">Select Cabang</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
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
                    <label for="submissiondate">Tanggal Diterima HRD <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                        value=<?= $today ?>>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="approveddate">Tanggal Disetujui</label>
                    <input type="text" class="form-control datepicker" id="approveddate" name="approveddate" readonly>
                    <small class="form-text text-danger" id="error_approveddate"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_position_id">Jabatan <span class="required">*</span></label>
                    <select class="form-control select2" id="md_position_id" name="md_position_id" disabled>
                        <option value="">Pilih Jabatan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_position_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <label>Tipe Berhenti <span class="required">*</span></label>
                    <select class="form-control select2" id="departuretype" name="departuretype">
                        <option value="">Pilih Tipe Berhenti</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?> </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_departuretype"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <label>Kategori Berhenti </label>
                    <select class="form-control select2" id="departurerule" name="departurerule">
                        <option value="">Pilih Kategori Berhenti</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Alasan Berhenti <span class="required">*</span></label>
                    <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="letterdate">Tanggal Surat <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="letterdate" name="letterdate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_letterdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Berhenti <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" name="date">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_date"></small>
                </div>
            </div>
        </div>
    </form>
</div>