<!-- =================================================================== -->
<!-- REUSABLE ATLANTIS ACTIVITY FEED MODAL COMPONENT                    -->
<!-- =================================================================== -->
<style>
    #modal_activity_feed .ap-feed-list {
        margin: 0;
        padding: 4px 4px 4px 12px;
    }
    #modal_activity_feed .ap-feed-list .feed-item {
        padding-bottom: 18px;
        padding-left: 26px;
        border-left: 2px solid var(--ap-line-soft, #cbd5e1);
    }
    #modal_activity_feed .ap-feed-list .feed-item:last-child {
        padding-bottom: 4px;
        border-left-color: transparent;
    }
    #modal_activity_feed .ap-feed-list .feed-item::after {
        top: 3px;
        left: -7px;
        width: 12px;
        height: 12px;
        box-shadow: 0 0 0 3px #ffffff;
    }
    #modal_activity_feed .ap-feed-card {
        background: var(--ap-surface, #ffffff);
        border: 1.5px solid var(--ap-line-soft, #cbd5e1);
        border-radius: var(--ap-radius, 4px);
        padding: 12px 14px;
    }
    #modal_activity_feed .ap-feed-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    #modal_activity_feed .ap-feed-time {
        font-family: var(--ap-mono, monospace);
        font-size: 12px;
        font-weight: 600;
        color: var(--ap-muted, #475569);
    }
    #modal_activity_feed .ap-feed-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        border: 1px solid transparent;
    }
    #modal_activity_feed .ap-feed-badge.is-info {
        background: #e0f2fe;
        color: #0369a1;
        border-color: #bae6fd;
    }
    #modal_activity_feed .ap-feed-badge.is-warning {
        background: #fef3c7;
        color: #92400e;
        border-color: #fde68a;
    }
    #modal_activity_feed .ap-feed-badge.is-success {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }
    #modal_activity_feed .ap-feed-badge.is-secondary {
        background: #f1f5f9;
        color: #334155;
        border-color: #cbd5e1;
    }
    #modal_activity_feed .ap-feed-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 14px;
        font-size: 13px;
        color: var(--ap-ink-2, #111827);
        margin-bottom: 6px;
    }
    #modal_activity_feed .ap-feed-status-pill {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 700;
        background: var(--ap-surface-2, #f8fafc);
        border: 1px solid var(--ap-line-soft, #cbd5e1);
        color: var(--ap-ink, #0f172a);
    }
    #modal_activity_feed .ap-feed-note {
        background: var(--ap-surface-2, #f8fafc);
        border: 1px solid var(--ap-line-soft, #cbd5e1);
        border-radius: var(--ap-radius, 4px);
        padding: 8px 10px;
        font-size: 12.5px;
        color: var(--ap-ink-2, #111827);
        line-height: 1.5;
        margin-top: 6px;
    }
</style>

<div class="modal fade" id="modal_activity_feed" modal-type="not-main" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg ap-modal-uniform-dialog" role="document">
        <div class="modal-content shadow-lg position-relative ap-modal-uniform-content">
            <div class="ap-wrap" style="max-width: 100%;">
                <div class="modal-header ap-modal-header d-flex justify-content-between align-items-center">
                    <h5 class="modal-title ap-modal-title font-weight-bold d-flex align-items-center mb-0">
                        <i class="fas fa-stream text-primary mr-2" aria-hidden="true"></i>Riwayat Aktivitas Penilaian
                    </h5>
                    <button type="button" class="btn btn-sm btn-close-preview" data-dismiss="modal" aria-label="Tutup">
                        <i class="fas fa-times mr-1" aria-hidden="true"></i> Tutup
                    </button>
                </div>
                <div class="modal-body p-3 p-md-4 ap-modal-uniform-body">
                    <div class="ap-card mb-0">
                        <div class="ap-card-head d-flex justify-content-between align-items-center">
                            <h5 class="ap-card-title mb-0">Log Aktivitas &amp; Perubahan Status</h5>
                            <span class="ap-category-badge" id="activity_feed_count">0 Aktivitas</span>
                        </div>
                        <div class="ap-card-body" id="activity_feed_container">
                            <!-- Dynamically populated via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var ACTION_META = {
        'SUBMIT_EVALUATOR':     { label: 'Submit Penilai',       tone: 'info',      badge: 'is-info',      icon: 'fa-paper-plane' },
        'REVIEW':               { label: 'Review Peninjau',      tone: 'info',      badge: 'is-info',      icon: 'fa-check-double' },
        'HR_INTERVENTION':      { label: 'Intervensi HR',        tone: 'warning',   badge: 'is-warning',   icon: 'fa-user-shield' },
        'ACKNOWLEDGE_EMPLOYEE': { label: 'Konfirmasi Karyawan',  tone: 'success',   badge: 'is-success',   icon: 'fa-signature' },
        'ACKNOWLEDGE_DUTA':     { label: 'Konfirmasi Duta',      tone: 'success',   badge: 'is-success',   icon: 'fa-user-check' },
        'NUDGED':               { label: 'Pengingat HR',         tone: 'secondary', badge: 'is-secondary', icon: 'fa-bell' }
    };

    var STATUS_LABELS = {
        'PENILAIAN':        'Penilaian',
        'SUBMIT_PENILAIAN': 'Dinilai',
        'REVIEW':           'Peninjauan',
        'SUBMIT_REVIEW':    'Ditinjau',
        'FINAL':            'Selesai'
    };

    function escFeed(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function formatFeedDate(raw) {
        if (!raw) return '-';
        var m = String(raw).match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
        if (!m) return raw;
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var mon = months[parseInt(m[2], 10) - 1] || m[2];
        return parseInt(m[3], 10) + ' ' + mon + ' ' + m[1] + ', ' + m[4] + ':' + m[5];
    }

    function formatStatus(code) {
        if (!code) return '-';
        return STATUS_LABELS[code] || code;
    }

    window.showActivityFeedModal = function(appraisalId) {
        if (!appraisalId) return;

        var $modal = $('#modal_activity_feed');
        var $container = $('#activity_feed_container');
        var $count = $('#activity_feed_count');

        $count.text('Memuat...');
        $modal.modal('show');
        $container.html(
            '<div class="text-center py-4 text-muted">' +
                '<i class="fas fa-spinner fa-spin fa-2x text-primary" aria-hidden="true"></i>' +
                '<p class="mt-2 mb-0 small font-weight-bold">Memuat riwayat aktivitas...</p>' +
            '</div>'
        );

        var endpointUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '<?= base_url() ?>').replace(/\/+$/, '') + '/sas/penilaian/auditLogs/' + appraisalId;

        $.ajax({
            url: endpointUrl,
            type: 'GET',
            dataType: 'JSON',
            success: function(res) {
                var logs = (res && res[0] && res[0].data) ? res[0].data : (res && res.data ? res.data : []);
                if (!logs || logs.length === 0) {
                    $count.text('0 Aktivitas');
                    $container.html(
                        '<div class="text-center py-4 text-muted">' +
                            '<i class="fas fa-info-circle fa-2x mb-2 d-block" aria-hidden="true"></i>' +
                            '<p class="mb-0 small">Belum ada riwayat aktivitas untuk form penilaian ini.</p>' +
                        '</div>'
                    );
                    return;
                }

                $count.text(logs.length + ' Aktivitas');
                var html = '<ol class="activity-feed ap-feed-list">';
                $.each(logs, function(i, log) {
                    var actionCode = log.action || '-';
                    var meta = ACTION_META[actionCode] || { label: actionCode, tone: 'primary', badge: 'is-secondary', icon: 'fa-circle' };
                    var timeStr = formatFeedDate(log.created_at);
                    var actor = log.actor_name
                        ? (log.actor_name + (log.actor_username ? ' (' + log.actor_username + ')' : ''))
                        : ('User #' + (log.actor_id || '-'));

                    var statusHtml = '';
                    if (log.from_status || log.to_status) {
                        statusHtml =
                            '<span class="d-inline-flex align-items-center" style="gap: 5px;">' +
                                '<span class="ap-feed-status-pill">' + escFeed(formatStatus(log.from_status)) + '</span>' +
                                '<i class="fas fa-long-arrow-alt-right text-muted" aria-hidden="true"></i>' +
                                '<span class="ap-feed-status-pill">' + escFeed(formatStatus(log.to_status)) + '</span>' +
                            '</span>';
                    }

                    var notesHtml = log.notes
                        ? '<div class="ap-feed-note">' + escFeed(log.notes) + '</div>'
                        : '';

                    html += '<li class="feed-item feed-item-' + meta.tone + '">' +
                        '<div class="ap-feed-card">' +
                            '<div class="ap-feed-head">' +
                                '<span class="ap-feed-badge ' + meta.badge + '">' +
                                    '<i class="fas ' + meta.icon + '" aria-hidden="true"></i>' +
                                    escFeed(meta.label) +
                                '</span>' +
                                '<span class="ap-feed-time"><i class="far fa-clock mr-1" aria-hidden="true"></i>' + escFeed(timeStr) + '</span>' +
                            '</div>' +
                            '<div class="ap-feed-meta">' +
                                '<span><i class="far fa-user text-muted mr-1" aria-hidden="true"></i><strong>' + escFeed(actor) + '</strong></span>' +
                                statusHtml +
                            '</div>' +
                            notesHtml +
                        '</div>' +
                    '</li>';
                });
                html += '</ol>';

                $container.html(html);
            },
            error: function(xhr, status, err) {
                $count.text('Gagal');
                $container.html(
                    '<div class="text-center py-4 text-danger">' +
                        '<i class="fas fa-exclamation-triangle fa-2x mb-2 d-block" aria-hidden="true"></i>' +
                        '<p class="mb-0 font-weight-bold">Gagal memuat riwayat aktivitas penilaian.</p>' +
                        '<small class="text-muted">ID Penilaian: #' + escFeed(appraisalId) + ' (' + escFeed(err || status) + ')</small>' +
                    '</div>'
                );
            }
        });
    };

    $(document).off('click', '.btn_activity_feed').on('click', '.btn_activity_feed', function(e) {
        e.preventDefault();
        var id = $(this).data('id') || $(this).attr('data-id');
        if (id) {
            window.showActivityFeedModal(id);
        }
    });
})();
</script>
