<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('masterdata/benefit/tab_benefit'); ?>
<?= $this->include('masterdata/benefit/modal_benefit_detail'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Nama Benefit</th>
                <th>Cabang</th>
                <th>Divisi</th>
                <th>Level</th>
                <th>Jabatan</th>
                <th>Status Karyawan</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>