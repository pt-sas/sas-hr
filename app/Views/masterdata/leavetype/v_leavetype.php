<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('masterdata/leavetype/form_leavetype'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Kode Tipe Cuti</th>
                <th>Nama</th>
                <th>Gender</th>
                <th>Durasi</th>
                <th>Tipe Durasi</th>
                <th>Deskripsi</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>