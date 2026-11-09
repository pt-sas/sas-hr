<!-- IV. CATATAN -->
<div class="ap-card" id="card_section_feedback">
    <div class="ap-card-head">
        <h5 class="ap-card-title">
            IV. Catatan
        </h5>
    </div>
    <div class="ap-card-body">
        <?php if ($user_mode === 'EVALUATOR'): ?>
            <div class="form-group mb-3">
                <label class="font-weight-bold" for="evaluator_comment">
                    1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan? <span class="text-danger">*</span>
                </label>
                <textarea class="form-control ap-textarea"
                          name="evaluator_comment"
                          id="evaluator_comment"
                          rows="3"
                          placeholder="Tuliskan apresiasi, kelebihan, atau kompetensi yang paling menonjol..."><?= esc($appraisal->evaluator_comment ?? '') ?></textarea>
            </div>

            <div class="form-group mb-0">
                <label class="font-weight-bold" for="development_plan">
                    2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                </label>
                <textarea class="form-control ap-textarea"
                          name="development_plan"
                          id="development_plan"
                          rows="3"
                          placeholder="Tuliskan aspek yang perlu ditingkatkan, arahan, atau rencana pengembangan..."><?= esc($appraisal->development_plan ?? '') ?></textarea>
            </div>

        <?php elseif ($user_mode === 'GM' || $user_mode === 'HR'): ?>
            <div class="form-group mb-3">
                <label class="font-weight-bold" for="evaluator_comment">
                    1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                </label>
                <textarea class="form-control ap-textarea"
                          name="evaluator_comment"
                          id="evaluator_comment"
                          rows="3"
                          placeholder="Tuliskan apresiasi, kelebihan, atau kompetensi yang paling menonjol..."><?= esc($appraisal->evaluator_comment ?? '') ?></textarea>
            </div>

            <div class="form-group mb-0">
                <label class="font-weight-bold" for="development_plan">
                    2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                </label>
                <textarea class="form-control ap-textarea"
                          name="development_plan"
                          id="development_plan"
                          rows="3"
                          placeholder="Tuliskan aspek yang perlu ditingkatkan, arahan, atau rencana pengembangan..."><?= esc($appraisal->development_plan ?? '') ?></textarea>
            </div>

        <?php elseif ($user_mode === 'EMPLOYEE'): ?>
            <div class="form-group mb-3">
                <label class="font-weight-bold text-muted small text-uppercase">
                    Catatan &amp; Feedback Evaluator:
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-2" style="white-space: pre-wrap; font-size: 13.5px;"><div class="font-weight-bold text-dark small mb-1">Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                </div><?= esc(!empty($appraisal->evaluator_comment) ? trim($appraisal->evaluator_comment) : '(Tidak ada catatan)') ?></div>
                <div class="p-3 bg-light rounded border text-dark mb-3" style="white-space: pre-wrap; font-size: 13.5px;"><div class="font-weight-bold text-dark small mb-1">2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?</div><?= esc(!empty($appraisal->development_plan) ? trim($appraisal->development_plan) : '(Tidak ada catatan)') ?></div>
            </div>

        <?php else: ?>
            <!-- VIEW ONLY MODE / PREVIEW -->
            <div class="form-group mb-3">
                <label class="font-weight-bold text-dark small mb-2 d-block">
                    1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-3" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;"><?= !empty($appraisal->evaluator_comment) ? esc($appraisal->evaluator_comment) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                <label class="font-weight-bold text-dark small mb-2 d-block">
                    2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                </label>
                <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;"><?= !empty($appraisal->development_plan) ? esc($appraisal->development_plan) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>
