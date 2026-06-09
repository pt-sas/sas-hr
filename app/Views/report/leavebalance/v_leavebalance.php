<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<form id="saldo_cuti_detail">
    <div class="card-body">
        <!-- <div class="form-group row">
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
        </div> -->
        <div class="form-group row">
            <label for="md_employee_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Karyawan </label>
            <div class="col-lg-6 col-md-9 col-sm-8">
                <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                    data-url="employee/getList/$Access" required>
                    <option value="">Select Karyawan</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label for="year" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Tahun</label>
            <div class="col-lg-6 col-md-9 col-sm-8">
                <div class="input-icon">
                    <input type="text" class="form-control yearpicker" name="year" value=<?= $year ?>></input>
                    <span class="input-icon-addon">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>