<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<?= $this->include($filter) ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header"></div>
            <div class="card-body">
                <table class="table table-bordered table-hover table_report">
                    <thead>
                        <tr>
                            <th>NIK</th>
                            <th>Karyawan</th>
                            <th>Cabang</th>
                            <th>Divisi</th>
                            <th>Kriteria</th>
                            <th>Periode</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>