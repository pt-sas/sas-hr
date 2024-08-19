<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<div class="card-main">
    <form id="form_absent_manual" enctype="multipart/form-data">
        <div class="card-body">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date">Tanggal <span class="required">*</span></label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" name="date" placeholder="Tanggal"
                                value=<?= $today ?> disabled>
                            <span class="input-icon-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                        </div>
                        <small class="form-text text-danger" id="error_date"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="time">Jam <span class="required">*</span></label>
                        <div class="input-icon">
                            <input type="text" class="form-control timepicker" name="time" placeholder="Jam">
                            <div class="input-icon-addon">
                                <i class="fa fa-clock"></i>
                            </div>
                        </div>
                        <small class="form-text text-danger" id="error_time"></small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>