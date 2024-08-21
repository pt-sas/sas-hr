<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_sickleave">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Nama Karyawan <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id" data-url="employee/getList/$Access">
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
            <div class="col-md-6">
                <div class="form-group">
                    <label for="documentno">No Form</label>
                    <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]" readonly>
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
                    <label for="submissiondate">Tanggal Pengajuan<span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate" value=<?= $today ?>>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="memodate">Tanggal Memo <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="memodate" name="memodate">
                    <small class="form-text text-danger" id="error_memodate"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="memocontent">Isi Memo <span class="required">*</span></label>
                    <textarea type="text" class="form-control" name="memocontent" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_memocontent"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="memocriteria">Kriteria <span class="required">*</span></label>
                    <select class="form-control select-data" id="memocriteria" name="memocriteria" data-url="reference/getList/$MemoCriteria">
                        <option value="">Select Kriteria</option>
                    </select>
                    <small class="form-text text-danger" id="error_memocriteria"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="totaldays">Total <span class="required">*</span></label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="totaldays" name="totaldays">
                        <div class="input-group-append">
                            <span class="input-group-text">Hari</span>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_totaldays"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Keterangan </label>
                    <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                </div>
            </div>
        </div>
    </form>
</div>