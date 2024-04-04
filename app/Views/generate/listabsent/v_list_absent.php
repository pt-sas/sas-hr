<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<?= $this->include($filter) ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-hover table_report">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIK</th>
                            <th>Karyawan</th>
                            <th>Tanggal Tidak Absen</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>



<?= $this->endSection() ?>