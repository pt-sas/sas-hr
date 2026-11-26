<!-- V. KONFIRMASI & PENGAJUAN -->
<div class="ap-card" id="card_section_confirmation">
    <div class="ap-card-head">
        <h5 class="ap-card-title">
            <?= esc($confirm_section_number ?? 'V') ?>. Pernyataan Konfirmasi &amp; Pengajuan
        </h5>
    </div>

    <div class="ap-card-body p-3">
        <?php if ($user_mode === 'EVALUATOR'): ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="evaluator_action_type" value="save_draft" data-ap-confirm-action>

            <div class="confirm-callout" id="callout_evaluator" data-ap-confirm-callout>
                <label class="confirm-checkbox-label" for="check_finalize">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize" name="is_finalize" value="Y" data-ap-confirm-checkbox>
                    <span class="confirm-text">
                        Saya menyatakan bahwa penilaian ini telah selesai diisi oleh Penilai dan siap diajukan ke <?= !empty($has_reviewer) ? 'Peninjau' : 'Karyawan' ?>.
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="submit_hint_draft" class="status-msg is-draft" data-ap-confirm-hint-draft>
                        <span><strong>Status: DRAFT PENILAIAN.</strong> Nilai dan catatan disimpan sementara. Penilaian belum diajukan ke <?= !empty($has_reviewer) ? 'Peninjau' : 'Karyawan' ?>.</span>
                    </div>
                    <div id="submit_hint_submit" class="status-msg is-final d-none" data-ap-confirm-hint-submit>
                        <span><strong>Status: SIAP DIAJUKAN.</strong> Dokumen penilaian akan langsung diteruskan ke <strong><?= !empty($has_reviewer) ? 'Peninjau' : 'Karyawan' ?></strong> saat tombol simpan ditekan.</span>
                    </div>
                </div>
            </div>

        <?php elseif ($user_mode === 'REVIEWER'): ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="reviewer_action_type" value="save_draft" data-ap-confirm-action>

            <div class="confirm-callout" id="callout_reviewer" data-ap-confirm-callout>
                <label class="confirm-checkbox-label" for="check_finalize_reviewer">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize_reviewer" name="is_finalize" value="Y" data-ap-confirm-checkbox>
                    <span class="confirm-text">
                        Saya menyetujui hasil peninjauan evaluasi kinerja ini dan meneruskannya ke tahap Pembacaan Karyawan.
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="reviewer_hint_draft" class="status-msg is-draft" data-ap-confirm-hint-draft>
                        <span><strong>Status: DRAFT PENINJAUAN.</strong> Catatan atau penyesuaian nilai disimpan sementara. Dokumen belum diteruskan ke karyawan.</span>
                    </div>
                    <div id="reviewer_hint_submit" class="status-msg is-final d-none" data-ap-confirm-hint-submit>
                        <span><strong>Status: SIAP DITERUSKAN.</strong> Hasil peninjauan akan diteruskan ke karyawan untuk tahap pembacaan &amp; konfirmasi.</span>
                    </div>
                </div>
            </div>

        <?php elseif ($user_mode === 'HR'): ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="hr_action_type" value="save_draft" data-ap-confirm-action>

            <div class="confirm-callout" id="callout_hr" data-ap-confirm-callout>
                <label class="confirm-checkbox-label" for="check_finalize_hr">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize_hr" name="is_finalize" value="Y" data-ap-confirm-checkbox>
                    <span class="confirm-text">
                        Intervensi HR: Saya telah memeriksa/merevisi hasil evaluasi ini dan menyetujui untuk memfinalisasi penilaian kinerja ini.
                    </span>
                </label>

                <div class="confirm-status-box">
                    <div id="hr_hint_draft" class="status-msg is-draft" data-ap-confirm-hint-draft>
                        <span><strong>Status: MODE INTERVENSI HR.</strong> Anda dapat menyunting skor dan catatan. Simpan sebagai draf, atau centang kotak di atas untuk memfinalisasi form ini.</span>
                    </div>
                    <div id="hr_hint_submit" class="status-msg is-final d-none" data-ap-confirm-hint-submit>
                        <span><strong>Status: SIAP FINALISASI.</strong> Form evaluasi akan segera difinalisasi dan rapor kinerja karyawan ditetapkan resmi.</span>
                    </div>
                </div>
            </div>

        <?php elseif ($user_mode === 'EMPLOYEE'): ?>
            <?php
                $currEmpId = (int) (session()->get('md_employee_id') ?? 0);
                $isDifferentUser = ($currEmpId !== (int) ($appraisal->md_employee_id ?? 0));
                $isDutaAck = !empty($is_duta_acknowledgment);
            ?>
            <input type="hidden" name="is_finalize" value="N">
            <input type="hidden" name="action_type" id="employee_action_type" value="save_draft" data-ap-confirm-action>

            <div class="confirm-callout" id="callout_emp" data-ap-confirm-callout>
                <label class="confirm-checkbox-label" for="check_finalize_employee">
                    <input type="checkbox" class="confirm-checkbox" id="check_finalize_employee" name="is_finalize" value="Y" data-ap-confirm-checkbox>
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
                    <div id="emp_hint_draft" class="status-msg is-draft" data-ap-confirm-hint-draft>
                        <span><strong>Status: BELUM DIKONFIRMASI.</strong> Centang kotak pernyataan di atas untuk mengonfirmasi bahwa Anda telah menerima hasil evaluasi ini.</span>
                    </div>
                    <div id="emp_hint_submit" class="status-msg is-final d-none" data-ap-confirm-hint-submit>
                        <span><strong>Status: KONFIRMASI DITERIMA.</strong> Tanda konfirmasi karyawan telah tercatat resmi di dalam sistem.</span>
                    </div>
                </div>
            </div>

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
