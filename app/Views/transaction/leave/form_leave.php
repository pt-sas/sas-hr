<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_leave">
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
                    <label for="submissiondate">Tanggal Pengajuan <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate" value=<?= $today ?>>
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
                        <input type="text" class="form-control datepicker" id="receiveddate" name="receiveddate" readonly>
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
                    <textarea type="text" class="form-control" name="reason" rows="6"></textarea>
                    <small class="form-text text-danger" id="error_reason"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Mulai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-start" id="startdate" name="startdate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
                <div class="form-group">
                    <label for="availableleavedays">Sisa Cuti </label>
                    <input type="text" class="form-control" id="availableleavedays" name="availableleavedays" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Selesai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-end" id="enddate" name="enddate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_enddate"></small>
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
                                <th>Tanggal Tidak Masuk</th>
                                <th>Doc No</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>