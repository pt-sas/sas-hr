<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<form id="parameter_report">
    <div class="card-body">
        <div class="form-group row">
            <label for="md_division_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Cabang </label>
            <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                <select class="form-control multiple-select-branch" name="md_branch_id"></select>
            </div>
        </div>
        <div class="form-group row">
            <label for="md_division_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Divisi </label>
            <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                <select class="form-control multiple-select-division" name="md_division_id"></select>
            </div>
        </div>
        <div class="form-group row">
            <label for="md_employee_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Karyawan </label>
            <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                <select class="form-control multiple-select-employee" name="md_employee_id"></select>
            </div>
        </div>
        <div class="form-group row">
            <label for="submissiondate" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Tanggal</label>
            <div class="col-lg-6 col-md-9 col-sm-8 ">
                <div class="input-icon">
                    <input type="text" class="form-control daterange" name="submissiondate" value="<?= $date_range ?>"
                        placeholder="Tanggal mulai dan selesai">
                    <span class="input-icon-addon">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>