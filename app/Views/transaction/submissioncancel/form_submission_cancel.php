<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_submission_cancel">
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
            <div class="col-md-3"></div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="documentno">No Form </label>
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
                    <label for="receiveddate">Tanggal Diterima</label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="receiveddate" name="receiveddate"
                            readonly>
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_receiveddate"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reason">Alasan <span class="required">*</span></label>
                    <textarea type="text" class="form-control" name="reason" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_reason"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="ref_submissiontype">Tipe Pengajuan <span class="required">*</span></label>
                    <select class="form-control select-data" id="ref_submissiontype" name="ref_submissiontype"
                        data-url="document-type/getList/$Submission">
                        <option value="">Pilih Tipe Pengajuan</option>
                    </select>
                    <small class="form-text text-danger" id="error_ref_submissiontype"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="reference_id">No Pengajuan <span class="required">*</span></label>
                    <select class="form-control select2" id="reference_id" name="reference_id">
                        <option value="">Pilih Pengajuan</option>
                    </select>
                    <small class="form-text text-danger" id="error_reference_id"></small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Foto <span class="required">*</span></label>
                    <div class="form-upload-result">
                        <label class="col-md-4 form-result">
                            <button type="button" class="close-img" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <img class="img-result">
                        </label>
                    </div>
                    <div class="form-upload">
                        <label class="col-md-4 form-upload-foto" id="image-upload">
                            <input type="file" class="control-upload-image" id="image" name="image"
                                onchange="previewImage(this)" accept="image/jpeg, image/png"></input>
                            <img class="img-upload" src="<?= base_url('custom/image/cameraroll.png') ?>" />
                        </label>
                        <small class="form-text text-danger" id="error_image"></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Employee</th>
                                <th>Tanggal Batal</th>
                                <th>status</th>
                                <th>aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>