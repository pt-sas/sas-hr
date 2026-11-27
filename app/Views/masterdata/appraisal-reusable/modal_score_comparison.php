<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">

<!-- =================================================================== -->
<!-- REUSABLE SCORE COMPARISON MODAL COMPONENT                          -->
<!-- =================================================================== -->
<div class="modal fade" id="modal_score_comparison" modal-type="not-main" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl ap-modal-uniform-dialog" role="document">
        <div class="modal-content shadow-lg position-relative ap-modal-uniform-content">
            <div id="score_comparison_container" style="width: 100%;">
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