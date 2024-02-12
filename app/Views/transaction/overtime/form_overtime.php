<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_overtime">
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
                    <label for="md_employee_id">Nama Pengaju <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                        data-url="employee/getList">
                        <option value="">Select Karyawan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_employee_id"></small>
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
                    <label for="submissiondate">Tanggal Pengajuan <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                        value=<?= $today ?> readonly>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="receiveddate">Tanggal Disetujui</label>
                    <input type="text" class="form-control datepicker" id="receiveddate" name="receiveddate">
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
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" id="table_overtime" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Tanggal Mulai</th>
                                <th>Jam Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Jam Selesai</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>