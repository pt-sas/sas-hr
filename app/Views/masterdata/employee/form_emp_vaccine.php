<form class="form-horizontal" id="form_emp_vaccine">
    <?= csrf_field(); ?>
    <h4 class="card-title ml-2">Riwayat Vaksin</h4>
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
                <table class="table-rounded table-head-bg-primary table-hover tb_displaytab" id="table-emp-vaccine"
                    style="width: 100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="text-center">Jenis Vaksin</th>
                            <th class="text-center">Tanggal Vaksin</th>
                            <th class="text-center">Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</form>