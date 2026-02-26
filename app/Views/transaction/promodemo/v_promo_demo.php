<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('transaction/promodemo/form_promo_demo'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>No Form</th>
                <th>Doc Status</th>
                <th>Karyawan</th>
                <th>NIK</th>
                <th>Cabang</th>
                <th>Divisi</th>
                <th>Level</th>
                <th>Jabatan</th>
                <th>Level Tujuan</th>
                <th>Jabatan Tujuan</th>
                <th>Tipe Form</th>
                <th>Tanggal Pembuatan</th>
                <th>Tanggal Mutasi</th>
                <th>Alasan</th>
                <th>Dibuat Oleh</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>

<?= $this->endSection() ?>