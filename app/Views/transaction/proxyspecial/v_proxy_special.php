<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('transaction/proxyspecial/form_proxy_special'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>No Form</th>
                <th>Doc Status</th>
                <th>Pengguna</th>
                <th>Di Wakilkan Oleh</th>
                <th>Tanggal Pembuatan</th>
                <th>Tanggal Peralihan</th>
                <th>Tanggal Diterima</th>
                <th>Alasan</th>
                <th>Createdby</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>