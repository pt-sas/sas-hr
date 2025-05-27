<div class="card-body card-form">
    <form class="form-horizontal" id="form_delegation_transfer">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="employee_from">Duta Awal <span class="required">*</span></label>
                    <select class="form-control select-data" id="employee_from" name="employee_from"
                        data-url="employee/getList/$AccessAll">
                        <option value="">Pilih Pengguna</option>
                    </select>
                    <small class="form-text text-danger" id="error_employee_from"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="employee_to">Duta Tujuan <span class="required">*</span></label>
                    <select class="form-control select2" id="employee_to" name="employee_to">
                        <option value="">Pilih Pengguna</option>
                    </select>
                    <small class=" form-text text-danger" id="error_employee_to"></small>
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
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                        value=<?= $today ?>>
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
                    <label for="startdate">Tanggal Mulai Pengalihan <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-start" name="startdate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="enddate">Tanggal Pengalihan Berakhir <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-end" name="enddate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_enddate"></small>
                </div>
            </div>
            <!-- <div class="col-md-3">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="ispermanent" name="ispermanent"
                            hide-field="enddate">
                        <span class="form-check-sign">Permanent</span>
                    </label>
                </div>
            </div> -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reason">Alasan</label>
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
                                <th>Karyawan</th>
                                <th>Status Transfer</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>