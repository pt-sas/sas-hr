<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('masterdata/help/form_help'); ?>

<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%;">
        <thead>
            <tr>
                <th>ID</th>
                <th style="width: 60px;">No</th>
                <th style="width: 17%;">Kategori</th>
                <th style="width: 22%;">Judul Artikel</th>
                <th>Text</th>
                <th style="width: 100px;">Status Aktif</th>
                <th style="width: 100px;">Aksi</th>
            </tr>
        </thead>
    </table>
</div>

<style>
    .tb_display {
        width: 100% !important;
    }

    .tb_display th,
    .tb_display td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }

    .tb_display th:nth-child(6),
    .tb_display td:nth-child(6),
    .tb_display th:nth-child(7),
    .tb_display td:nth-child(7) {
        white-space: nowrap !important;
    }
</style>

<?= $this->endSection() ?>