<?php
    helper('appraisal_view');

    $snapshot    = appraisal_normalize_snapshot($template_snapshot ?? []);
    $is_operator = isset($is_operator) ? (bool) $is_operator : appraisal_is_operator($snapshot);
    $formMode    = ! empty($is_preview) ? 'preview' : strtolower((string) ($user_mode ?? 'view'));

    $this->setVar('template_snapshot', $snapshot);
    $this->setVar('is_operator', $is_operator);
    $this->setVar('display_mode', 'edit');
    $this->setVar('section_number', 'IV');
?>

<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">

<div class="ap-wrap pb-3">
    <form id="form_appraisal_evaluation"
          data-appraisal-form
          data-mode="<?= esc($formMode) ?>"
          data-is-operator="<?= $is_operator ? '1' : '0' ?>"
          data-appraisal-id="<?= (int) $appraisal_id ?>">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group d-none"></div>
                <input type="hidden" name="md_appraisal_id" id="md_appraisal_id" value="<?= (int) $appraisal_id ?>">
                <input type="hidden" name="final_score" id="final_score" value="<?= $appraisal->final_score !== null ? number_format((float) $appraisal->final_score, 2) : '' ?>">
                <input type="hidden" name="responses_json" id="hidden_responses_payload">

                <!-- 1. INFO CARD -->
                <?= $this->include('Views/masterdata/appraisal-reusable/_info_card'); ?>

                <?php if ($is_operator): ?>
                    <!-- 2. OPERATOR PENILAIAN KINERJA -->
                    <?= $this->include('Views/masterdata/appraisal-reusable/_section_operator_performance'); ?>

                    <!-- 3. OPERATOR KRITERIA TAMBAHAN -->
                    <?= $this->include('Views/masterdata/appraisal-reusable/_section_operator_additional'); ?>
                <?php else: ?>
                    <!-- 2. PERFORMA -->
                    <?= $this->include('Views/masterdata/appraisal-reusable/_section_performance'); ?>

                    <!-- 3. KOMPETENSI TAMBAHAN -->
                    <?= $this->include('Views/masterdata/appraisal-reusable/_section_additional'); ?>
                <?php endif; ?>

                <!-- 4. CATATAN & FEEDBACK EVALUATOR -->
                <?= $this->include('Views/masterdata/appraisal-reusable/_section_feedback'); ?>

                <!-- 5. KONFIRMASI & PENGAJUAN -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_confirmation'); ?>
            </div>
        </div>
    </form>
</div>
