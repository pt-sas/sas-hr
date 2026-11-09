<!-- 5. FOOTER ACTIONS -->
<div class="ap-footer">
    <a href="<?= base_url('sas/penilaian') ?>" class="ap-btn ap-btn-ghost" id="btn_cancel">
        <i class="fas fa-arrow-left"></i> Batal
    </a>

    <div class="ap-actions">
        <?php if ($user_mode === 'EVALUATOR'): ?>
            <button type="button" class="ap-btn ap-btn-soft-warn" id="btn_save_draft">
                <i class="fas fa-save"></i> Simpan draft
            </button>
            <button type="button" class="ap-btn ap-btn-primary" id="btn_submit_evaluator">
                <i class="fas fa-paper-plane"></i> Submit ke reviewer
            </button>
        <?php elseif ($user_mode === 'GM'): ?>
            <button type="button" class="ap-btn ap-btn-soft-warn" id="btn_save_draft">
                <i class="fas fa-save"></i> Simpan draft review
            </button>
            <button type="button" class="ap-btn ap-btn-danger-ghost" id="btn_kickback_gm">
                <i class="fas fa-undo"></i> Kembalikan untuk revisi
            </button>
            <button type="button" class="ap-btn ap-btn-primary" id="btn_submit_gm">
                <i class="fas fa-check-double"></i> Setujui &amp; teruskan
            </button>
        <?php elseif ($user_mode === 'EMPLOYEE'): ?>
            <button type="button" class="ap-btn ap-btn-success" id="btn_submit_ack">
                <i class="fas fa-signature"></i> Tandatangani &amp; selesai
            </button>
        <?php endif; ?>

        <button type="button" class="ap-btn ap-btn-ghost ap-btn-icon" onclick="showAuditLogModal(<?= (int) $appraisal_id ?>)" title="Riwayat aktivitas (audit log)">
            <i class="fas fa-history"></i>
        </button>
    </div>
</div>