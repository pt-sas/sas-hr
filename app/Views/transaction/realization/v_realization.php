<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('transaction/realization/modal_realization') ?>
<?= $this->include($filter) ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="float-right d-none">
                    <?= $toolbarRealization ?>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover table_report" style="width: 100%">
                    <thead>
                        <tr>
                            <!-- <th>
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input ischeckall-realize" id="ischeckall" name="ischeckall">
                                        <span class="form-check-sign"></span>
                                    </label>
                                </div>
                            </th> -->
                            <th>#</th>
                            <th>Tanggal Tidak Masuk</th>
                            <th>Tipe Form</th>
                            <th>Cabang</th>
                            <th>Divisi</th>
                            <th>Karyawan</th>
                            <th>Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>