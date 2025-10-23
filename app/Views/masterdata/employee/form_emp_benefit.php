<form class="form-horizontal" id="form_emp_benefit">
    <?= csrf_field(); ?>
    <h4 class="card-title ml-2">Benefit</h4>
    <div class="form-group row">
        <label for="name" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Karyawan <span
                class="required-label">*</span></label>
        <div class="col-lg-4 col-md-4 col-sm-4">
            <input type="text" class="form-control foreignkey" name="md_employee_id" data-url="karyawan/getDataBy"
                readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="text-right">
                    <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto add_row"
                        title="Create Line" <?= $disabled ?>><i class="fa fa-plus fa-fw"></i> Tambah Data</button>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group table-responsive">
                <table class="table-rounded table-head-bg-primary table-hover tb_displaytab" id="table-emp-benefit"
                    style="width: 100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="text-center">Tipe Benefit</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Keterangan</th>
                            <th class="text-center">Detail</th>
                            <th class="text-center">Tombol Detail</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- <div class="form-group row">
        <div class="col-md-12">
            <label for="username">Manager</span></label>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="cop">
                    <span class="form-check-sign">COP</span>
                </label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="cuti">
                    <span class="form-check-sign">Cuti</span>
                </label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="tunjangan">
                    <span class="form-check-sign">Tunjangan</span>
                </label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" name="lembur">
                    <span class="form-check-sign">Lembur</span>
                </label>
            </div>
        </div> -->
</form>