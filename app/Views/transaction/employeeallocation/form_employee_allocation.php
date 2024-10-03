<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_employee_allocation">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="documentno">No Form</label>
                    <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="submissiontype">Tipe Form <span class="required">*</span></label>
                    <select class="form-control select2" id="submissiontype" name="submissiontype">
                        <option value="">Select Tipe Form</option>
                        <?php foreach ($type as $row) : ?>
                            <option value="<?= $row->md_doctype_id ?>"><?= $row->name ?> </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_submissiontype"></small>
                </div>
            </div>
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
                    <label for="submissiondate">Tanggal Pengajuan <span class="required">*</span></label>
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
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Mutasi <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" name="date">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_date"></small>
                </div>
            </div>
            <div class="col-md-3"></div>
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
                    <label for="branch_to">Cabang Tujuan <span class="required">*</span></label>
                    <select class="form-control select-data" id="branch_to" name="branch_to" data-url="branch/getList">
                        <option value="">Pilih Cabang Tujuan</option>
                    </select>
                    <small class="form-text text-danger" id="error_branch_to"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="division_to">Divisi Tujuan <span class="required">*</span></label>
                    <select class="form-control select-data" id="division_to" name="division_to"
                        data-url="division/getList">
                        <option value="">Pilih Divisi Tujuan</option>
                    </select>
                    <small class="form-text text-danger" id="error_division_to"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_levelling_id">Level <span class="required">*</span></label>
                    <select class="form-control select2" id="md_levelling_id" name="md_levelling_id" disabled>
                        <option value="">Pilih Level</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_levelling_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_position_id">Jabatan <span class="required">*</span></label>
                    <select class="form-control select2" id="md_position_id" name="md_position_id" disabled>
                        <option value="">Pilih Jabatan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_position_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="levelling_to">Level Tujuan <span class="required">*</span></label>
                    <select class="form-control select-data" id="levelling_to" name="levelling_to" data-url="levelling/getList">
                        <option value="">Pilih Level Tujuan</option>
                    </select>
                    <small class="form-text text-danger" id="error_levelling_to"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="position_to">Jabatan Tujuan <span class="required">*</span></label>
                    <select class="form-control select-data" id="position_to" name="position_to"
                        data-url="position/getList">
                        <option value="">Pilih Jabatan Tujuan</option>
                    </select>
                    <small class="form-text text-danger" id="error_position_to"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Alasan <span class="required">*</span></label>
                    <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
            </div>
        </div>
    </form>
</div>