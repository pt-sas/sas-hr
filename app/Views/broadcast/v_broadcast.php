<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('broadcast/form_broadcast'); ?>

<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>No</th>
            <th>Title</th>
            <th>Message</th>
            <th>Created By</th>
            <th>Effective Date</th>
            <th>Aksi</th>
        </tr>
        </thead>
    </table>
</div>

<?= $this->endSection() ?>