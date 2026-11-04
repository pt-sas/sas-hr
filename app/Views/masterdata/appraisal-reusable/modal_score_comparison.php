<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">

<style>
/* Scoped tokens & styles for Reusable Score Comparison Modal */
#modal_score_comparison {
    --ap-ink: #0f172a;
    --ap-ink-2: #334155;
    --ap-muted: #64748b;
    --ap-line: #e5e7eb;
    --ap-line-soft: #f1f5f9;
    --ap-surface: #ffffff;
    --ap-surface-2: #f8fafc;
    --ap-accent: #2563eb;
    --ap-accent-soft: #eff6ff;
    --ap-accent-ring: rgba(37, 99, 235, 0.18);
    --ap-success: #059669;
    --ap-warning: #d97706;
    --ap-danger: #dc2626;
    --ap-radius: 12px;
    --ap-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

#modal_score_comparison .modal-content {
    border-radius: 12px;
    border: 0;
    overflow: hidden;
    background: var(--ap-surface-2);
}

#modal_score_comparison .ap-card {
    background: var(--ap-surface);
    border: 1px solid var(--ap-line);
    border-radius: var(--ap-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    margin-bottom: 20px;
    overflow: hidden;
}

#modal_score_comparison .ap-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 22px;
    border-bottom: 1px solid var(--ap-line);
    background: var(--ap-surface);
}

#modal_score_comparison .ap-card-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--ap-ink);
}

#modal_score_comparison .ap-card-body {
    padding: 20px 22px;
}

#modal_score_comparison .ap-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0;
}

#modal_score_comparison .ap-info-item {
    padding: 12px 0;
    border-bottom: 1px solid var(--ap-line-soft);
}

#modal_score_comparison .ap-info-item:nth-child(odd) {
    padding-right: 24px;
}

#modal_score_comparison .ap-info-item:nth-child(even) {
    padding-left: 24px;
    border-left: 1px solid var(--ap-line-soft);
}

#modal_score_comparison .ap-info-item:nth-last-child(-n+2) {
    border-bottom: 0;
}

#modal_score_comparison .ap-info-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ap-muted);
    margin-bottom: 4px;
}

#modal_score_comparison .ap-info-value {
    font-size: 14.5px;
    font-weight: 600;
    color: var(--ap-ink);
}

#modal_score_comparison .ap-mode {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: var(--ap-accent-soft);
    color: var(--ap-accent);
}

#modal_score_comparison .ap-mode::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

#modal_score_comparison .ap-mode.is-gm {
    background: #ecfeff;
    color: #0e7490;
}

#modal_score_comparison .ap-score-bar {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 12px 22px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: saturate(160%) blur(8px);
    border-bottom: 1px solid var(--ap-line);
}

#modal_score_comparison .ap-score-num {
    font-family: var(--ap-mono);
    font-size: 18px;
    font-weight: 700;
    color: var(--ap-ink);
    min-width: 72px;
    text-align: center;
    padding: 6px 10px;
    border-radius: 8px;
    background: var(--ap-surface-2);
    border: 1px solid var(--ap-line);
}

#modal_score_comparison .ap-rating {
    font-size: 12.5px;
    font-weight: 700;
    padding: 7px 12px;
    border-radius: 999px;
}

#modal_score_comparison .ap-rating.r-great {
    background: #ecfdf5;
    color: var(--ap-success);
}

#modal_score_comparison .ap-rating.r-good {
    background: var(--ap-accent-soft);
    color: var(--ap-accent);
}

#modal_score_comparison .ap-rating.r-fair {
    background: #fffbeb;
    color: var(--ap-warning);
}

#modal_score_comparison .ap-rating.r-low {
    background: #fef2f2;
    color: var(--ap-danger);
}

#modal_score_comparison .ap-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

#modal_score_comparison .ap-table thead th {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ap-muted);
    background: var(--ap-surface-2);
    padding: 12px 16px;
    border-bottom: 1px solid var(--ap-line);
}

#modal_score_comparison .ap-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    font-size: 14px;
    color: var(--ap-ink-2);
    border-bottom: 1px solid var(--ap-line-soft);
}

#modal_score_comparison .ap-table .ap-comp-cell {
    background: var(--ap-surface);
    vertical-align: top;
    padding-top: 18px;
}

#modal_score_comparison .ap-comp-name {
    font-size: 14.5px;
    font-weight: 700;
    color: var(--ap-ink);
    line-height: 1.4;
}

#modal_score_comparison .ap-def {
    font-size: 13px;
    color: var(--ap-muted);
    line-height: 1.55;
}

#modal_score_comparison .ap-action-text {
    font-size: 13.5px;
    color: var(--ap-ink-2);
    line-height: 1.5;
}

#modal_score_comparison .ap-action-letter {
    color: var(--ap-muted);
    font-weight: 700;
    margin-right: 4px;
}

#modal_score_comparison .ap-subtotal td {
    background: var(--ap-surface-2);
    padding: 10px 16px !important;
    border-bottom: 1px solid var(--ap-line) !important;
}

#modal_score_comparison .ap-avg-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--ap-surface);
    border: 1px solid var(--ap-line);
    font-size: 12px;
    color: var(--ap-muted);
}

#modal_score_comparison .ap-avg-num {
    font-family: var(--ap-mono);
    font-weight: 700;
    color: var(--ap-accent);
    font-size: 13.5px;
}
</style>

<!-- =================================================================== -->
<!-- REUSABLE SCORE COMPARISON MODAL COMPONENT                          -->
<!-- =================================================================== -->
<div class="modal fade" id="modal_score_comparison" modal-type="not-main" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document" style="max-width: 1280px; width: 95vw;">
        <div class="modal-content shadow-lg" style="border-radius: 12px; border: 0; overflow: hidden; background: var(--ap-surface-2);">
            <div id="score_comparison_container">
                <!-- Dynamically populated via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Universal Global Function
    window.showScoreComparisonModal = function(appraisalId) {
        if (!appraisalId) return;

        var $modal = $('#modal_score_comparison');
        var $container = $('#score_comparison_container');

        $modal.modal('show');
        $container.html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x text-primary" aria-hidden="true"></i>
                <p class="mt-2 mb-0 small font-weight-bold">Memuat riwayat &amp; komparasi skor penilaian...</p>
            </div>
        `);

        // Menentukan base endpoint yang support root maupun subfolder /sas
        var endpointUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '<?= base_url() ?>').replace(/\/+$/, '') + '/sas/penilaian/scoreComparison/' + appraisalId;

        $.ajax({
            url: endpointUrl,
            type: 'GET',
            success: function(html) {
                $container.html(html);
            },
            error: function(xhr, status, err) {
                $container.html(`
                    <div class="text-center py-5 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2" aria-hidden="true"></i>
                        <p class="mb-0 font-weight-bold">Gagal memuat data komparasi skor penilaian.</p>
                        <small class="text-muted">ID Penilaian: #${appraisalId} (${err || status})</small>
                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                `);
            }
        });
    };

    // Universal Data-Attribute Event Delegation (Auto-bind)
    $(document).off('click', '.btn_score_comparison').on('click', '.btn_score_comparison', function(e) {
        e.preventDefault();
        var id = $(this).data('id') || $(this).attr('data-id');
        if (id) {
            window.showScoreComparisonModal(id);
        }
    });
})();
</script>