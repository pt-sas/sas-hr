<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('transaction/masterbundling/form_master_bundling'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Nama Paket</th>
                <th>Divisi</th>
                <th>Jenis Paket</th>
                <th>Periode Paket</th>
                <th>Deskripsi</th>
                <th>Createdby</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>