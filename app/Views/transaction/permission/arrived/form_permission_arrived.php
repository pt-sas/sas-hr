<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_permission_arrived">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Nama Karyawan <span class="required">*</span></label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id" data-url="karyawan/getList">
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
                    <label for="documentno">No Form <span class="required">*</span></label>
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
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate" value=<?= $today ?> readonly>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="receiveddate">Tanggal Diterima <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="receiveddate" name="receiveddate" readonly>
                    <small class="form-text text-danger" id="error_receiveddate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="necessary">Jenis Form <span class="required">*</span></label>
                    <select class="form-control select2" id="necessary" name="necessary" disabled>
                        <option value="">Select Pengajuan</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <?php if ($ref_default === $row->value) : ?>
                                <option value="<?= $row->value ?>" selected><?= $row->name ?> </option>
                            <?php else : ?>
                                <option value="<?= $row->value ?>"><?= $row->name ?> </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_necessary"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class=" form-check mt-4">
                    <label class="form-radio-label">
                        <input class="form-radio-input" type="radio" id="submissiontype" name="submissiontype" value="datang terlambat" checked disabled>
                        <span class="form-radio-sign">Ijin Datang Terlambat</span>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date">Tanggal <span class="required">*</span></label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control datetimepicker-start" name="startdate" placeholder="Tanggal Mulai">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control datetimepicker-end" name="enddate" placeholder="Tanggal Selesai">
                        </div>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reason">Alasan </label>
                    <textarea type="text" class="form-control" name="reason" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_reason"></small>
                </div>
            </div>
        </div>
    </form>
</div>