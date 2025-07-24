<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table_unprocessed table-hover" style="width: 100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipe Pengajuan</th>
                            <th>DocumentNo</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>