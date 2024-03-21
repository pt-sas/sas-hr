<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('masterdata/rule/tab_rule'); ?>
<?= $this->include('masterdata/rule/form_rule_value'); ?>
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Nama Rule</th>
                <th>Kondisi</th>
                <th>Value</th>
                <th>Min</th>
                <th>Max</th>
                <th>Menu</th>
                <th>Priority</th>
                <th>Detail</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>