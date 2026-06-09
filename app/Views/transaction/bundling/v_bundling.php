<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('transaction/bundling/form_bundling'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>No Form</th>
                <th>Pemohon</th>
                <th>Doc Status</th>
                <th>Nama Paket</th>
                <th>Cabang</th>
                <th>Divisi</th>
                <th>Tanggal Pembuatan</th>
                <th>Periode Paket</th>
                <th>Deskripsi</th>
                <th>Createdby</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>