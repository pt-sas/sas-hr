<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('transaction/overtimerealization/modal_realization') ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-hover table_report">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Form</th>
                            <th>Karyawan</th>
                            <th>Cabang</th>
                            <th>Divisi</th>
                            <th>Tanggal Lembur</th>
                            <th>Tanggal Selesai</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>