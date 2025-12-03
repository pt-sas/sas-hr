<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_adjustment">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Karyawan <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                        data-url="employee/getList/$Access">
                        <option value="">Pilih Karyawan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_employee_id"></small>
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
                    <select class="form-control select2" id="md_branch_id" name="md_branch_id" disabled>
                        <option value="">Pilih Cabang</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_branch_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_division_id">Divisi <span class="required">*</span></label>
                    <select class="form-control select2" id="md_division_id" name="md_division_id" disabled>
                        <option value="">Pilih Divisi</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
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
                    <label for="date">Tanggal <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="date" name="date" autocomplete="off">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_date"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="begin_balance">Saldo Awal</label>
                    <input type="text" class="form-control" id="begin_balance" name="begin_balance" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="adjustment">Penyesuaian <span class="required">*</span></label>
                    <input type="text" class="form-control adjustment" id="adjustment" name="adjustment">
                    <small class="form-text text-danger" id="error_adjustment"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="ending_balance">Saldo Akhir</label>
                    <input type="text" class="form-control" id="ending_balance" name="ending_balance" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reason">Alasan <span class="required">*</span></label>
                    <textarea type="text" class="form-control" name="reason" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_reason"></small>
                </div>
            </div>
        </div>
    </form>
</div>