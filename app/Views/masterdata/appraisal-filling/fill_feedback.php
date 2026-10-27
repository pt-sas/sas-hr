<!-- 4. CATATAN & FEEDBACK -->
<div class="ap-card" id="card_section_feedback">
    <div class="ap-card-head">
        <h5 class="ap-card-title">
            <?php if ($user_mode === 'GM'): ?>
                4. Catatan &amp; Feedback Review Atasan (GM)
            <?php elseif ($user_mode === 'EMPLOYEE'): ?>
                4. Catatan &amp; Feedback Penilaian
            <?php else: ?>
                4. Catatan &amp; Feedback Evaluator<span class="ap-required">*</span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="ap-card-body">
        <?php if ($user_mode === 'EVALUATOR'): ?>
            <div class="form-group mb-0">
                <textarea class="form-control ap-textarea"
                          name="evaluator_comment"
                          id="evaluator_comment"
                          rows="4"
                          placeholder="Tuliskan apresiasi, masukan, atau poin pengembangan untuk karyawan..."><?= esc($appraisal->evaluator_comment ?? '') ?></textarea>
            </div>

            <div class="mt-3 p-3 bg-light rounded border">
                <input type="hidden" name="is_finalize" value="N">
                <input type="hidden" name="action_type" id="evaluator_action_type" value="save_draft">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="check_finalize" name="is_finalize" value="Y">
                    <label class="custom-control-label font-weight-bold text-primary" for="check_finalize" style="cursor: pointer;">
                        <i class="fas fa-paper-plane mr-1"></i> <?= !empty($has_reviewer) ? 'Submit ke Reviewer GM (Bukan sekadar draft)' : 'Submit Penilaian Langsung ke Karyawan' ?>
                    </label>
                </div>
                <div id="submit_hint_draft" class="text-muted small mt-2">
                    <i class="fas fa-save mr-1"></i> Mode saat ini: <strong>Simpan Draft</strong> (penilaian hanya disimpan sementara, belum dikirim ke tahap berikutnya).
                </div>
                <div id="submit_hint_submit" class="text-success small mt-2 d-none font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Mode saat ini: <strong>Kirim Penilaian</strong> (penilaian akan diajukan ke <?= !empty($has_reviewer) ? 'Reviewer Atasan (GM)' : 'Karyawan' ?>).
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
                <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 14px; min-height: 70px;">
                    <?= esc($appraisal->evaluator_comment ?: '(Tidak ada catatan dari penilai)') ?>
                </div>
                <!-- Hidden input to preserve evaluator comment on save -->
                <input type="hidden" name="evaluator_comment" value="<?= esc($appraisal->evaluator_comment ?? '') ?>">
            </div>

            <!-- GM's comment textarea -->
            <div class="form-group mb-3">
                <label class="font-weight-bold" for="gm_comment">
                    <i class="fas fa-user-tie mr-1"></i> Catatan &amp; Arahan Atasan (GM):
                </label>
                <textarea class="form-control ap-textarea"
                          name="gm_comment"
                          id="gm_comment"
                          rows="4"
                          placeholder="Tuliskan catatan persetujuan, arahan, atau catatan review GM..."><?= esc($appraisal->gm_comment ?? '') ?></textarea>
            </div>

            <!-- Action toggle: like manager evaluation -->
            <div class="mt-3 p-3 bg-light rounded border">
                <input type="hidden" name="is_finalize" value="N">
                <input type="hidden" name="action_type" id="gm_action_type" value="save_draft">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="check_finalize_gm" name="is_finalize" value="Y">
                    <label class="custom-control-label font-weight-bold text-primary" for="check_finalize_gm" style="cursor: pointer;">
                        <i class="fas fa-check-double mr-1"></i> Setujui &amp; Teruskan ke Karyawan (Bukan sekadar draft)
                    </label>
                </div>
                <div id="gm_hint_draft" class="text-muted small mt-2">
                    <i class="fas fa-save mr-1"></i> Mode saat ini: <strong>Simpan Draft Review</strong> (review hanya disimpan sementara, belum diteruskan ke karyawan).
                </div>
                <div id="gm_hint_submit" class="text-success small mt-2 d-none font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Mode saat ini: <strong>Setujui &amp; Teruskan</strong> (penilaian disetujui dan diteruskan ke Karyawan untuk tahap pembacaan).
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
                    <i class="fas fa-comment-dots mr-1"></i> Catatan &amp; Feedback Evaluator:
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-3" style="white-space: pre-wrap; font-size: 14px;">
                    <?= esc($appraisal->evaluator_comment ?: '(Tidak ada catatan)') ?>
                </div>
                <?php if (!empty($appraisal->gm_comment)): ?>
                    <label class="font-weight-bold text-muted small text-uppercase">
                        <i class="fas fa-user-tie mr-1"></i> Catatan Review Atasan (GM):
                    </label>
                    <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 14px;">
                        <?= esc($appraisal->gm_comment) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Confirmation toggle for employee reading sign-off -->
            <div class="mt-3 p-3 bg-light rounded border">
                <input type="hidden" name="is_finalize" value="N">
                <input type="hidden" name="action_type" id="employee_action_type" value="save_draft">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="check_finalize_employee" name="is_finalize" value="Y">
                    <label class="custom-control-label font-weight-bold text-primary" for="check_finalize_employee" style="cursor: pointer;">
                        <i class="fas fa-signature mr-1"></i> Saya telah membaca dan menyetujui hasil penilaian ini (Konfirmasi TTD Digital)
                    </label>
                </div>
                <div id="emp_hint_draft" class="text-muted small mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Mode saat ini: <strong>Belum dikonfirmasi</strong> (centang kotak di atas untuk menyetujui hasil evaluasi dan melakukan konfirmasi TTD digital).
                </div>
                <div id="emp_hint_submit" class="text-success small mt-2 d-none font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Mode saat ini: <strong>Konfirmasi &amp; TTD Digital</strong> (penilaian akan difinalisasi dan rapor resmi diterbitkan).
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
            <!-- VIEW ONLY MODE -->
            <div class="form-group mb-3">
                <label class="font-weight-bold text-muted small text-uppercase">
                    <i class="fas fa-comment-dots mr-1"></i> Catatan &amp; Feedback Evaluator:
                </label>
                <div class="p-3 bg-light rounded border text-dark mb-3" style="white-space: pre-wrap; font-size: 14px;">
                    <?= esc($appraisal->evaluator_comment ?: '(Tidak ada catatan)') ?>
                </div>
                <?php if (!empty($appraisal->gm_comment)): ?>
                    <label class="font-weight-bold text-muted small text-uppercase">
                        <i class="fas fa-user-tie mr-1"></i> Catatan Review Atasan (GM):
                    </label>
                    <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 14px;">
                        <?= esc($appraisal->gm_comment) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>