<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<?= $this->include('process/closingperiod/modal_closing_period') ?>
<?= $this->include('process/closingperiod/form_closing_period'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Tahun</th>
                <th>Deskripsi</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>