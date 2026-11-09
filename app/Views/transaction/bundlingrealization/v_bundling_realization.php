<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('transaction/bundlingrealization/modal_realization') ?>
<?= $this->include($filter) ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-hover table_realization table_bundling">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th></th>
                            <th>Tanggal Realisasi</th>
                            <th>No Pengajuan Paket</th>
                            <th>Pemohon</th>
                            <th>Cabang</th>
                            <th>Divisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>