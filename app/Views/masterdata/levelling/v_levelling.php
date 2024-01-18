<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('masterdata/levelling/form_levelling'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Kode Jabatan</th>
                <th>Nama</th>
                <th>Deskripsi</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>