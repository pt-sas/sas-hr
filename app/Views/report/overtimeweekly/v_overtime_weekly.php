<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="<?= site_url('sas/laporan-lembur-mingguan/showAll') ?>" method="POST" id="parameter_report">
                    <div class="form-group row">
                        <label for="md_division_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Cabang
                        </label>
                        <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                            <select class="form-control multiple-select-branch" name="md_branch_id[]"></select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="md_division_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Divisi
                        </label>
                        <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                            <select class="form-control multiple-select-division" name="md_division_id[]"></select>
                        </div>
                    </div>
                    <!-- <div class="form-group row">
                        <label for="md_employee_id" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Karyawan
                        </label>
                        <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                            <select class="form-control multiple-select-employee" name="md_employee_id[]"></select>
                        </div>
                    </div> -->
                    <div class="form-group row">
                        <label for="periode" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Tanggal </label>
                        <div class="col-lg-6 col-md-9 col-sm-8 select2-input select2-primary">
                            <input type="text" class="form-control week-picker" name="date" value=<?= $week ?> id="week-picker" required></input>
                        </div>
                    </div>
                    <div class="card-action d-flex justify-content-center">
                        <div>
                            <button type="reset" class="btn btn-danger btn-sm btn-round ml-auto"><i class="fas fa-undo-alt fa-fw"></i> Reset</button>
                            <button type="submit" class="btn btn-success btn-sm btn-round ml-auto"><i class="fas fa-check fa-fw"></i> OK</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>