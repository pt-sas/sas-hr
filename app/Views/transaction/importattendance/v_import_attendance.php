<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('transaction/importattendance/form_import_attendance'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>No Form</th>
                <th>Pemohon</th>
                <th>Tanggal Pembuatan</th>
                <th>Tanggal Absensi</th>
                <th>Tanggal Approve</th>
                <th>Alasan</th>
                <th>Doc Status</th>
                <th>Createdby</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<?= $this->endSection() ?>