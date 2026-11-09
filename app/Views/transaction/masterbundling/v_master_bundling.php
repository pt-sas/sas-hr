<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<link rel="stylesheet" href="<?= base_url('custom/css/bundling-form.css') ?>">
<link rel="stylesheet" href="<?= base_url('custom/css/masterdata/masterbundling/master-bundling.css') ?>">

<?= $this->include('transaction/masterbundling/form_master_bundling'); ?>

<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Nama Paket</th>
                <th>Jenis Paket</th>
                <th>Tanggal Pembuatan</th>
                <th>Deskripsi</th>
                <th>Createdby</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>