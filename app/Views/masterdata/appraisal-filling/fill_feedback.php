<!-- 4. CATATAN & FEEDBACK -->
<div class="ap-card" id="card_section_feedback">
    <div class="ap-card-head">
        <h5 class="ap-card-title">
            4. Catatan
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

            <div class="mt-3 p-3 bg-light rounded border">
                <input type="hidden" name="is_finalize" value="N">
                <input type="hidden" name="action_type" id="evaluator_action_type" value="save_draft">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="check_finalize" name="is_finalize" value="Y">
                    <label class="custom-control-label font-weight-bold text-primary" for="check_finalize" style="cursor: pointer;">
                        <i class="fas fa-paper-plane mr-1"></i> <?= !empty($has_reviewer) ? 'Submit ke Reviewer' : 'Submit Penilaian Langsung ke Karyawan' ?>
                    </label>
                </div>
                <div id="submit_hint_draft" class="text-muted small mt-2">
                    <i class="fas"></i><b>Hapus centang</b> untuk menyimpan dalam bentuk draft.
                </div>
                <div id="submit_hint_submit" class="text-success small mt-2 d-none font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i>Penilaian akan diajukan ke <?= !empty($has_reviewer) ? 'Reviewer' : 'Karyawan' ?>.
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize');
                var act = document.getElementById('evaluator_action_type');
                var hintDraft = document.getElementById('submit_hint_draft');
                var hintSubmit = document.getElementById('submit_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            if (hintDraft) hintDraft.classList.remove('d-none');
                            if (hintSubmit) hintSubmit.classList.add('d-none');
                        }
                    }

                    chk.addEventListener('change', updateSubmitState);
                    updateSubmitState();
                }
            })();
            </script>

        <?php elseif ($user_mode === 'GM'): ?>
            <!-- Evaluator's original comments (Read-only preview) -->
            <div class="form-group mb-3">
                <label class="font-weight-bold text-muted small text-uppercase">
                    <i class="fas fa-comment-dots mr-1"></i> Catatan &amp; Feedback Evaluator (Penilai Langsung):
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-2" style="white-space: pre-wrap; font-size: 13.5px;">
                    <div class="font-weight-bold text-primary small mb-1">1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?</div>
                    <?= esc($appraisal->evaluator_comment ?: '(Tidak ada catatan)') ?>
                </div>
                <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 13.5px;">
                    <div class="font-weight-bold text-primary small mb-1">2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?</div>
                    <?= esc($appraisal->development_plan ?: '(Tidak ada catatan)') ?>
                </div>
                <!-- Hidden inputs to preserve evaluator comments on save -->
                <input type="hidden" name="evaluator_comment" value="<?= esc($appraisal->evaluator_comment ?? '') ?>">
                <input type="hidden" name="development_plan" value="<?= esc($appraisal->development_plan ?? '') ?>">
            </div>

            <!-- Action toggle: like manager evaluation -->
            <div class="mt-3 p-3 bg-light rounded border">
                <input type="hidden" name="is_finalize" value="N">
                <input type="hidden" name="action_type" id="gm_action_type" value="save_draft">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="check_finalize_gm" name="is_finalize" value="Y">
                    <label class="custom-control-label font-weight-bold text-primary" for="check_finalize_gm" style="cursor: pointer;">
                        <i class="fas fa-paper-plane mr-1"></i> Submit untuk finalisasi
                    </label>
                </div>
                <div id="gm_hint_draft" class="text-muted small mt-2">
                    <i class="fas"></i>Hapus centang untuk finalisasi
                </div>
                <div id="gm_hint_submit" class="text-success small mt-2 d-none font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Penilaian akan difinalisasi
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize_gm');
                var act = document.getElementById('gm_action_type');
                var hintDraft = document.getElementById('gm_hint_draft');
                var hintSubmit = document.getElementById('gm_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            if (hintDraft) hintDraft.classList.remove('d-none');
                            if (hintSubmit) hintSubmit.classList.add('d-none');
                        }
                    }

                    chk.addEventListener('change', updateSubmitState);
                    updateSubmitState();
                }
            })();
            </script>

        <?php elseif ($user_mode === 'EMPLOYEE'): ?>
            <div class="form-group mb-3">
                <label class="font-weight-bold text-muted small text-uppercase">
                    Catatan &amp; Feedback Evaluator:
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-2" style="white-space: pre-wrap; font-size: 13.5px;">
                    <div class="font-weight-bold text-primary small mb-1">1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?</div>
                    <?= esc($appraisal->evaluator_comment ?: '(Tidak ada catatan)') ?>
                </div>
                <div class="p-3 bg-light rounded border text-dark mb-3" style="white-space: pre-wrap; font-size: 13.5px;">
                    <div class="font-weight-bold text-primary small mb-1">2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?</div>
                    <?= esc($appraisal->development_plan ?: '(Tidak ada catatan)') ?>
                </div>
            </div>

            <!-- Confirmation toggle for employee reading sign-off -->
            <div class="mt-3 p-3 bg-light rounded border">
                <?php 
                    $currEmpId = (int) (session()->get('md_employee_id') ?? 0);
                    $isDifferentUser = ($currEmpId !== (int) ($appraisal->md_employee_id ?? 0));
                    $isDutaAck = !empty($is_duta_acknowledgment);
                ?>
                <?php if ($isDutaAck): ?>
                    <div class="alert alert-warning py-2 px-3 mb-2 font-weight-bold" style="font-size: 12px;">
                        <i class="fas fa-user-shield mr-1"></i> Mode Konfirmasi Duta: Anda sedang menandatangani evaluasi kinerja ini mewakili <?= esc($employee['name'] ?? 'karyawan') ?> (Karyawan tanpa akun sistem).
                    </div>
                <?php elseif ($isDifferentUser): ?>
                    <div class="alert alert-info py-2 px-3 mb-2 font-weight-bold" style="font-size: 12px;">
                        <i class="fas fa-user-shield mr-1"></i> Mode Intervensi HR / Admin: Anda sedang melakukan konfirmasi penerimaan dan TTD digital atas nama karyawan.
                    </div>
                <?php endif; ?>
                <input type="hidden" name="is_finalize" value="N">
                <input type="hidden" name="action_type" id="employee_action_type" value="save_draft">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="check_finalize_employee" name="is_finalize" value="Y">
                    <label class="custom-control-label font-weight-bold text-primary" for="check_finalize_employee" style="cursor: pointer;">
                        <i class="fas fa-signature mr-1"></i> 
                        <?php if ($isDutaAck): ?>
                            Saya selaku Duta mewakili <?= esc($employee['name'] ?? 'karyawan') ?> telah membaca dan menyetujui hasil penilaian ini (Konfirmasi TTD Digital oleh Duta)
                        <?php elseif ($isDifferentUser): ?>
                            Konfirmasi dan tandatangani hasil penilaian ini atas nama karyawan (TTD Digital HR/Admin)
                        <?php else: ?>
                            Saya telah membaca dan menyetujui hasil penilaian ini (Konfirmasi TTD Digital)
                        <?php endif; ?>
                    </label>
                </div>
                <div id="emp_hint_draft" class="text-muted small mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Mode saat ini: <strong>Belum dikonfirmasi</strong> (centang kotak di atas untuk menyetujui hasil evaluasi dan melakukan konfirmasi TTD digital).
                </div>
                <div id="emp_hint_submit" class="text-success small mt-2 d-none font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Mode saat ini: <strong>Konfirmasi penilaian</strong> (penilaian akan difinalisasi dan rapor resmi diterbitkan).
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize_employee');
                var act = document.getElementById('employee_action_type');
                var hintDraft = document.getElementById('emp_hint_draft');
                var hintSubmit = document.getElementById('emp_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            if (hintDraft) hintDraft.classList.remove('d-none');
                            if (hintSubmit) hintSubmit.classList.add('d-none');
                        }
                    }

                    chk.addEventListener('change', updateSubmitState);
                    updateSubmitState();
                }
            })();
            </script>

        <?php else: ?>
            <!-- VIEW ONLY MODE / PREVIEW -->
            <div class="form-group mb-3">
                <label class="font-weight-bold text-dark small mb-2 d-block">
                    1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-3" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;">
                    <?= !empty($appraisal->evaluator_comment) ? esc($appraisal->evaluator_comment) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?>
                </div>
                <label class="font-weight-bold text-dark small mb-2 d-block">
                    2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                </label>
                <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;">
                    <?= !empty($appraisal->development_plan) ? esc($appraisal->development_plan) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>