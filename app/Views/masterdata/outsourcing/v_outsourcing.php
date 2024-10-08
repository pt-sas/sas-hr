<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('backend/modal/image_info'); ?>
<?= $this->include('masterdata/outsourcing/tab_outsourcing'); ?>
<?= $this->include('masterdata/employee/modal/form_benefit_detail'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Value</th>
                <th>Nama Karyawan</th>
                <th>Tempat Lahir</th>
                <th>Tanggal Lahir</th>
                <th>Jenis Kelamin</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>