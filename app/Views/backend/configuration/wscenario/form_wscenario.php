<div class="card-body card-form">
    <form class="form-horizontal" id="form_wscenario">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="lineno">Line No </label>
                    <input type="text" class="form-control number" id="lineno" name="lineno" value="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="menu">Menu <span class="required">*</span></label>
                    <select class="form-control select2" id="menu" name="menu">
                        <option value="">Select Menu</option>
                        <?php foreach ($menu as $row) : ?>
                            <option value="<?= $row['url']; ?>"><?= $row['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_menu"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_status_id">Status Karyawan</label>
                    <select class="form-control select-data" id="md_status_id" name="md_status_id"
                        data-url="status/getList">
                        <option value="">Select Status</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_branch_id">Cabang </label>
                    <select class="form-control select-data" id="md_branch_id" name="md_branch_id"
                        data-url="branch/getList">
                        <option value="">Select Cabang</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_division_id">Divisi </label>
                    <select class="form-control select-data" id="md_division_id" name="md_division_id"
                        data-url="division/getList">
                        <option value="">Select Divisi</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="grandtotal">Grand Total </label>
                    <input type="text" class="form-control number" id="grandtotal" name="grandtotal" value="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_levelling_id">Jabatan </label>
                    <select class="form-control select-data" id="md_levelling_id" name="md_levelling_id"
                        data-url="levelling/getList">
                        <option value="">Select Jabatan</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="submissiontype">Tipe Form </label>
                    <select class="form-control select-data" id="submissiontype" name="submissiontype"
                        data-url="document-type/getList">
                        <option value="">Select Tipe Form</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Keterangan </label>
                    <textarea type="text" class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Aktif</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="text-right">
                        <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto add_row"
                            title="Tambah Baru"><i class="fa fa-plus fa-fw"></i> Tambah Baru</button>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" id="table_scenario" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Line No</th>
                                <th>Grand Total</th>
                                <th>Workflow Responsible</th>
                                <th>Notification Template</th>
                                <th>Aktif</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>