<?php
    $displayMode = $display_mode ?? 'edit';
    $userMode    = $user_mode ?? '';
    $sectionNum  = $section_number ?? 'IV';
    $evalComment = $evaluator_comment ?? ($appraisal->evaluator_comment ?? '');
    $devPlan     = $development_plan ?? ($appraisal->development_plan ?? '');
    $empComment  = $appraisal->employee_comment ?? '';
    $isEditable  = ($displayMode === 'edit') && in_array($userMode, ['EVALUATOR', 'REVIEWER', 'HR'], true);
?>

<div class="ap-card<?= $displayMode === 'compare' ? ' mb-0' : '' ?>" id="card_section_feedback">
    <div class="ap-card-head">
        <h5 class="ap-card-title"><?= esc($sectionNum) ?>. Catatan</h5>
    </div>
    <div class="ap-card-body<?= $displayMode !== 'edit' ? ' p-0' : '' ?>">
        <?php if ($isEditable): ?>
            <div class="form-group mb-3">
                <label class="font-weight-bold" for="evaluator_comment">
                    1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                    <?php if ($userMode === 'EVALUATOR'): ?>
                        <span class="text-danger">*</span>
                    <?php endif; ?>
                </label>
                <textarea class="form-control ap-textarea"
                          name="evaluator_comment"
                          id="evaluator_comment"
                          rows="3"
                          placeholder="Tuliskan apresiasi, kelebihan, atau kompetensi yang paling menonjol..."><?= esc($evalComment) ?></textarea>
            </div>

            <div class="form-group mb-0">
                <label class="font-weight-bold" for="development_plan">
                    2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                </label>
                <textarea class="form-control ap-textarea"
                          name="development_plan"
                          id="development_plan"
                          rows="3"
                          placeholder="Tuliskan aspek yang perlu ditingkatkan, arahan, atau rencana pengembangan..."><?= esc($devPlan) ?></textarea>
            </div>
        <?php else: ?>
            <div class="ap-feedback-section">
                <div class="ap-feedback-card">
                    <div class="ap-feedback-item">
                        <div class="ap-feedback-q">
                            1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                        </div>
                        <div class="ap-feedback-block"><?= ! empty($evalComment) ? esc(trim($evalComment)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                    </div>

                    <div class="ap-feedback-item">
                        <div class="ap-feedback-q">
                            2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                        </div>
                        <div class="ap-feedback-block"><?= ! empty($devPlan) ? esc(trim($devPlan)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                    </div>
                </div>

                <?php if ($displayMode !== 'edit' && ! empty($empComment)): ?>
                    <div class="ap-feedback-card">
                        <div class="ap-feedback-header">
                            <i class="fas fa-signature text-success mr-1" aria-hidden="true"></i>
                            <span>Tanggapan / Catatan Konfirmasi Karyawan (<?= esc(mb_strtoupper($employee['name'] ?? 'Karyawan', 'UTF-8')) ?>)</span>
                        </div>
                        <div class="ap-feedback-item">
                            <div class="ap-feedback-block"><?= esc(trim($empComment)) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
