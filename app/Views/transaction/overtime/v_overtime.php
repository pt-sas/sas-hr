<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('transaction/overtime/form_overtime'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>No Form</th>
                <th>Karyawan</th>
                <th>Cabang</th>
                <th>Divisi</th>
                <th>Tanggal Pembuatan</th>
                <th>Tanggal Lembur</th>
                <th>Alasan</th>
                <th>Doc Status</th>
                <th>Createdby</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>