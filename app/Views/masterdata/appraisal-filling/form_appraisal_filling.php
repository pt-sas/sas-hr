<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<div class="card-body card-main ap-wrap ap-hide-page-toolbar pb-3">
    <?= $this->include('Views/masterdata/appraisal-filling/form_appraisal_filling_inner') ?>
</div>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_audit_log') ?>

<script src="<?= base_url('custom/js/appraisal-filling.js') ?>"></script>
<script>
(function () {
    var formEl = document.querySelector('#form_appraisal_evaluation');
    if (formEl && window.AppraisalForm) {
        window.AppraisalForm.mount(formEl);
        <?php if (empty($is_preview)): ?>
        window.AppraisalForm.bindHostActions({
            formSelector: '#form_appraisal_evaluation',
            isStandalone: true,
            appraisalId: <?= (int) ($appraisal_id ?? 0) ?>,
            saveUrl: '<?= site_url("sas/penilaian/create") ?>',
            redirectUrl: '<?= site_url("sas/penilaian") ?>',
            auditLogsUrl: '<?= base_url("sas/penilaian/auditLogs") ?>'
        });
        <?php endif; ?>
    }
})();
</script>

<?= $this->endSection() ?>
