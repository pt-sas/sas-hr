<!-- AUDIT LOG MODAL -->
<div class="modal fade" id="modal_audit_log" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content" style="border-radius: 14px; border: 0;">
            <div class="modal-header" style="border-bottom: 1px solid var(--ap-line);">
                <h5 class="modal-title font-weight-bold" style="color: var(--ap-ink); font-size: 16px;">
                    <i class="fas fa-history mr-2" style="color: var(--ap-accent);" aria-hidden="true"></i>Riwayat Alur Penilaian
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                <div id="audit_timeline_container">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                        <p class="mt-2 mb-0">Memuat riwayat log...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ap-line);">
                <button type="button" class="ap-btn ap-btn-ghost" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
