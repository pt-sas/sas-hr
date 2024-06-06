<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_half_day_office_duties">
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
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate" value=<?= $today ?>>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="receiveddate">Tanggal Diterima</label>
                    <input type="text" class="form-control datepicker" id="receiveddate" name="receiveddate" readonly>
                    <small class="form-text text-danger" id="error_receiveddate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="datestart">Tanggal Mulai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepick-start" name="datestart" placeholder="Tanggal Mulai">
                        <div class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_datestart"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="starttime">Jam Mulai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control timepicker-start" name="starttime" placeholder="Jam Mulai">
                        <div class="input-icon-addon">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_starttime"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="dateend">Tanggal Selesai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepick-end" name="dateend" placeholder="Tanggal Selesai" readonly>
                        <div class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="endtime">Jam Selesai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control timepicker-end" name="endtime" placeholder="Jam Selesai">
                        <div class="input-icon-addon">
                            <i class="fa fa-clock"></i>
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_endtime"></small>
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
        <div class="row">
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Tanggal Tidak Masuk</th>
                                <th>Doc No</th>
                                <th>Doc Referensi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>