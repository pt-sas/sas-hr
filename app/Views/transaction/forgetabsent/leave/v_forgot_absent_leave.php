<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('transaction/forgetabsent/leave/form_forgot_absent_leave'); ?>
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
                <th>Tanggal Pengajuan</th>
                <th>Tanggal Lupa Absen Pulang</th>
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