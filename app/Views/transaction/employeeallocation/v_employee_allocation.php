<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('transaction/employeeallocation/form_employee_allocation'); ?>
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
                <th>Cabang Tujuan</th>
                <th>Divisi Tujuan</th>
                <th>Tanggal Pengajuan</th>
                <th>Tanggal Perbantuan</th>
                <th>Alasan</th>
                <th>Doc Status</th>
                <th>Dibuat Oleh</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>