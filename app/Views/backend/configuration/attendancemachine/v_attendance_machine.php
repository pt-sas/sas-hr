<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('backend/configuration/attendancemachine/form_attendance_machine'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Nama</th>
                <th>Nomor Seri</th>
                <th>Cabang</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>