<!-- V. KONFIRMASI & PENGAJUAN -->
<div class="ap-card" id="card_section_confirmation">
    <div class="ap-card-head">
        <h5 class="ap-card-title">
            V. Pernyataan Konfirmasi &amp; Pengajuan
        </h5>
    </div>

    <div class="ap-card-body p-3">
        <?php if ($user_mode === 'EVALUATOR'): ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="evaluator_action_type" value="save_draft">

            <div class="confirm-callout" id="callout_evaluator">
                <label class="confirm-checkbox-label" for="check_finalize">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize" name="is_finalize" value="Y">
                    <span class="confirm-text">
                        Saya menyatakan bahwa penilaian ini telah selesai diisi oleh Penilai dan siap diajukan ke <?= !empty($has_reviewer) ? 'Peninjau' : 'Karyawan' ?>.
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="submit_hint_draft" class="status-msg is-draft">
                        <span><strong>Status: DRAFT PENILAIAN.</strong> Nilai dan catatan disimpan sementara. Penilaian belum diajukan ke <?= !empty($has_reviewer) ? 'Peninjau' : 'Karyawan' ?>.</span>
                    </div>
                    <div id="submit_hint_submit" class="status-msg is-final d-none">
                        <span><strong>Status: SIAP DIAJUKAN.</strong> Dokumen penilaian akan langsung diteruskan ke <strong><?= !empty($has_reviewer) ? 'Peninjau' : 'Karyawan' ?></strong> saat tombol simpan ditekan.</span>
                    </div>
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize');
                var act = document.getElementById('evaluator_action_type');
                var box = document.getElementById('callout_evaluator');
                var hintDraft = document.getElementById('submit_hint_draft');
                var hintSubmit = document.getElementById('submit_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            box.classList.add('is-ready');
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            box.classList.remove('is-ready');
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
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="gm_action_type" value="save_draft">

            <div class="confirm-callout" id="callout_gm">
                <label class="confirm-checkbox-label" for="check_finalize_gm">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize_gm" name="is_finalize" value="Y">
                    <span class="confirm-text">
                        Saya menyetujui hasil peninjauan evaluasi kinerja ini dan meneruskannya ke tahap Pembacaan Karyawan.
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="gm_hint_draft" class="status-msg is-draft">
                        <span><strong>Status: DRAFT PENINJAUAN.</strong> Catatan atau penyesuaian nilai disimpan sementara. Dokumen belum diteruskan ke karyawan.</span>
                    </div>
                    <div id="gm_hint_submit" class="status-msg is-final d-none">
                        <span><strong>Status: SIAP DITERUSKAN.</strong> Hasil peninjauan akan diteruskan ke karyawan untuk tahap pembacaan &amp; konfirmasi.</span>
                    </div>
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize_gm');
                var act = document.getElementById('gm_action_type');
                var box = document.getElementById('callout_gm');
                var hintDraft = document.getElementById('gm_hint_draft');
                var hintSubmit = document.getElementById('gm_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            box.classList.add('is-ready');
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            box.classList.remove('is-ready');
                            if (hintDraft) hintDraft.classList.remove('d-none');
                            if (hintSubmit) hintSubmit.classList.add('d-none');
                        }
                    }
                    chk.addEventListener('change', updateSubmitState);
                    updateSubmitState();
                }
            })();
            </script>

        <?php elseif ($user_mode === 'HR'): ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="hr_action_type" value="save_draft">

            <div class="confirm-callout" id="callout_hr">
                <label class="confirm-checkbox-label" for="check_finalize_hr">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize_hr" name="is_finalize" value="Y">
                    <span class="confirm-text">
                        Intervensi HR: Saya telah memeriksa/merevisi hasil evaluasi ini dan menyetujui untuk memfinalisasi penilaian kinerja ini.
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="hr_hint_draft" class="status-msg is-draft">
                        <span><strong>Status: MODE INTERVENSI HR.</strong> Anda dapat menyunting skor dan catatan. Simpan sebagai draf, atau centang kotak di atas untuk memfinalisasi form ini.</span>
                    </div>
                    <div id="hr_hint_submit" class="status-msg is-final d-none">
                        <span><strong>Status: SIAP FINALISASI.</strong> Form evaluasi akan segera difinalisasi dan rapor kinerja karyawan ditetapkan resmi.</span>
                    </div>
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize_hr');
                var act = document.getElementById('hr_action_type');
                var box = document.getElementById('callout_hr');
                var hintDraft = document.getElementById('hr_hint_draft');
                var hintSubmit = document.getElementById('hr_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            box.classList.add('is-ready');
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            box.classList.remove('is-ready');
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
            <?php 
                $currEmpId = (int) (session()->get('md_employee_id') ?? 0);
                $isDifferentUser = ($currEmpId !== (int) ($appraisal->md_employee_id ?? 0));
                $isDutaAck = !empty($is_duta_acknowledgment);
            ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="employee_action_type" value="save_draft">

            <div class="confirm-callout" id="callout_emp">
                <label class="confirm-checkbox-label" for="check_finalize_employee">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize_employee" name="is_finalize" value="Y">
                    <span class="confirm-text">
                        <?php if ($isDutaAck): ?>
                            Saya selaku Duta mewakili <strong><?= esc(mb_strtoupper($employee['name'] ?? 'karyawan', 'UTF-8')) ?></strong> telah membaca dan mengonfirmasi hasil penilaian ini.
                        <?php elseif ($isDifferentUser): ?>
                            Konfirmasi dan verifikasi persetujuan hasil penilaian atas nama karyawan bersangkutan (Perwakilan HR).
                        <?php else: ?>
                            Saya telah membaca, memahami, dan mengonfirmasi hasil penilaian dari Penilai serta Peninjau.
                        <?php endif; ?>
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="emp_hint_draft" class="status-msg is-draft">
                        <span><strong>Status: BELUM DIKONFIRMASI.</strong> Centang kotak pernyataan di atas untuk mengonfirmasi bahwa Anda telah menerima hasil evaluasi ini.</span>
                    </div>
                    <div id="emp_hint_submit" class="status-msg is-final d-none">
                        <span><strong>Status: KONFIRMASI DITERIMA.</strong> Tanda konfirmasi karyawan telah tercatat resmi di dalam sistem.</span>
                    </div>
                </div>
            </div>

            <script>
            (function () {
                var chk = document.getElementById('check_finalize_employee');
                var act = document.getElementById('employee_action_type');
                var box = document.getElementById('callout_emp');
                var hintDraft = document.getElementById('emp_hint_draft');
                var hintSubmit = document.getElementById('emp_hint_submit');

                if (chk && act) {
                    function updateSubmitState() {
                        if (chk.checked) {
                            act.value = 'submit';
                            box.classList.add('is-ready');
                            if (hintDraft) hintDraft.classList.add('d-none');
                            if (hintSubmit) hintSubmit.classList.remove('d-none');
                        } else {
                            act.value = 'save_draft';
                            box.classList.remove('is-ready');
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
            <div class="confirm-callout" style="background-color: #f8fafc; border-color: #cbd5e1;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lock mr-3 fa-2x text-muted"></i>
                    <div>
                        <div class="confirm-text mb-1" style="color: #334155;">
                            Mode Simulasi / Pratinjau (Hanya Lihat)
                        </div>
                        <div class="status-msg is-draft" style="color: #64748b; font-size: 13.5px;">
                            Pernyataan dan pengesahan dinonaktifkan dalam mode pratinjau ini. Formulir ini hanya ditampilkan sebagai simulasi tampilan evaluasi kinerja.
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
