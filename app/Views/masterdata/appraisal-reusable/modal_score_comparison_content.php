<?php
    $isOperator = ! empty($is_operator);
    $this->setVar('display_mode', 'compare');
    $this->setVar('section_number', 'IV');

    if (! $isOperator) {
        $scoreSections   = $scoreSections ?? [];
        $booleanSections = $booleanSections ?? [];
        if (empty($scoreSections) && empty($booleanSections) && ! empty($sections)) {
            foreach ($sections as $s) {
                if (($s['section_type'] ?? '') === 'BOOLEAN') {
                    $booleanSections[] = $s;
                } else {
                    $scoreSections[] = $s;
                }
            }
        }
        $this->setVar('scoreSections', $scoreSections);
        $this->setVar('booleanSections', $booleanSections);
    }
?>

<div class="ap-wrap" style="max-width: 100%;">
    <!-- MODAL TOP BAR -->
    <div class="modal-header ap-modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title ap-modal-title font-weight-bold d-flex align-items-center mb-0">
            <?php if ($isOperator): ?>
                <i class="far fa-file-alt text-primary mr-2" aria-hidden="true"></i>Riwayat &amp; Komparasi Skor Penilaian (Operator / Pelaksana)
            <?php else: ?>
                Riwayat &amp; Komparasi Skor Penilaian
            <?php endif; ?>
        </h5>
        <button type="button" class="btn btn-sm btn-close-preview" data-dismiss="modal" aria-label="Tutup">
            <i class="fas fa-times mr-1" aria-hidden="true"></i> Tutup
        </button>
    </div>

    <!-- MODAL BODY -->
    <div class="modal-body p-3 p-md-4 ap-modal-uniform-body">
        <?= $this->include('Views/masterdata/appraisal-reusable/_info_card') ?>

        <?php if ($isOperator): ?>
            <?= $this->include('Views/masterdata/appraisal-reusable/_section_operator_performance') ?>
            <?= $this->include('Views/masterdata/appraisal-reusable/_section_operator_additional') ?>
        <?php else: ?>
            <?= $this->include('Views/masterdata/appraisal-reusable/_section_performance') ?>
            <?= $this->include('Views/masterdata/appraisal-reusable/_section_additional') ?>
        <?php endif; ?>

        <?= $this->include('Views/masterdata/appraisal-reusable/_section_feedback') ?>
    </div>
</div>
