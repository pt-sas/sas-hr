<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?php $is_standalone = true; ?>
<div class="card-body card-main ap-wrap ap-hide-page-toolbar pb-3">
    <?= $this->include('Views/masterdata/appraisal-filling/form_appraisal_filling_inner') ?>
</div>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_audit_log') ?>

<?= $this->endSection() ?>
