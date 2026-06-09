<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form-import">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="documentno">No Form</label>
                    <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="submissiondate">Tanggal Pembuatan <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" name="submissiondate" id="submissiondate"
                        value=<?= $today ?> disabled>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="approveddate">Tanggal Disetujui</label>
                    <input type="text" class="form-control datepicker" name="approveddate" id="approveddate" disabled>
                    <small class="form-text text-danger" id="error_approveddate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Nama Pembuat <span class="required">*</span></label>
                    <select name="md_employee_id" id="md_employee_id" class="form-control select-data">
                        <option value="">Pilih Karyawan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_employee_id"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="startdate" name="startdate">
                    <span class="inout-icon-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                </div>
                <small class="form-text text-danger" id="error_startdate"></small>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reason">Keterangan</label>
                    <textarea type="text" name="reason" id="reason" class="form-control" rows="4"></textarea>
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
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>NIK</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>