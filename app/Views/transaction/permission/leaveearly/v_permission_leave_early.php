<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('transaction/permission/leaveearly/form_permission_leave_early'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>No Form</th>
                <th>Karyawan</th>
                <th>NIK</th>
                <th>Cabang</th>
                <th>Divisi</th>
                <th>Tanggal Pembuatan</th>
                <th>Tanggal Pulang Cepat</th>
                <th>Tanggal Diterima</th>
                <th>Alasan</th>
                <th>Doc Status</th>
                <th>Createdby</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>