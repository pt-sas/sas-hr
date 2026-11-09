<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">

<style>
    /* Hide top toolbar (+ Add New, Refresh) on form filling page */
    .card-header .float-right,
    .card-header .new_form,
    .card-header .btn_requery {
        display: none !important;
    }

    /* Ensure the template action buttons (Save changes / Close / Reset) are displayed cleanly */
    .card-action,
    .card-action.card-button {
        display: flex !important;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        padding: 16px 24px;
        background: #ffffff;
        border-top: 1px solid #ebedf2;
    }
</style>

<div class="card-body card-main ap-wrap pb-3">

    <!-- MAIN EVALUATION FORM -->
    <form id="form_appraisal_evaluation">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group d-none"></div>
                <input type="hidden" name="md_appraisal_id" id="md_appraisal_id" value="<?= (int) $appraisal_id ?>">
                <input type="hidden" name="final_score" id="final_score" value="<?= number_format((float) ($appraisal->final_score ?? 3.00), 2) ?>">

                <!-- 1. INFO CARD -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_info_card'); ?>

                <!-- 2. PERFORMA -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_performance'); ?>

                <!-- 3. KOMPETENSI TAMBAHAN -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_additional'); ?>

                <!-- 4. CATATAN & FEEDBACK EVALUATOR -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_feedback'); ?>

                <!-- 5. KONFIRMASI & PENGAJUAN -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_confirmation'); ?>
            </div>
        </div>

    </form>
</div>

<!-- AUDIT LOG MODAL -->
<div class="modal fade" id="modal_audit_log" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 14px; border: 0;">
            <div class="modal-header" style="border-bottom: 1px solid var(--ap-line);">
                <h5 class="modal-title" style="font-weight:700; color: var(--ap-ink); font-size: 16px;">
                    <i class="fas fa-history mr-2" style="color: var(--ap-accent);"></i>Riwayat alur penilaian
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div id="audit_timeline_container">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
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

<script>
(function () {
    'use strict';

    var appraisalId = <?= (int) $appraisal_id ?>;

    function parseNum(val, fallback) {
        if (val === null || val === undefined) return fallback;
        var s = String(val).trim().replace(',', '.');
        var n = parseFloat(s);
        return isNaN(n) ? fallback : n;
    }

    function updateRatingBadge(score) {
        var badge = document.getElementById('badge_overall_rating');
        if (!badge) return;

        var label = 'Kurang';
        var badgeCls = 'r-low';

        if (score >= 3.50) {
            label = 'Sangat Baik';
            badgeCls = 'r-great';
        } else if (score >= 2.75) {
            label = 'Baik';
            badgeCls = 'r-good';
        } else if (score >= 2.00) {
            label = 'Cukup';
            badgeCls = 'r-fair';
        }

        badge.className = 'ap-rating ' + badgeCls;
        badge.textContent = label;
    }

    function recalcTotals() {
        var sliders = document.querySelectorAll('.action-slider');
        if (sliders.length === 0) {
            var initialScore = parseNum(document.getElementById('final_score') ? document.getElementById('final_score').value : 3.0, 3.0);
            updateRatingBadge(initialScore);
            return;
        }

        var items = {};
        sliders.forEach(function (slider) {
            var itemId = slider.dataset.itemId;
            var actionIdx = slider.dataset.actionIdx;
            var val = parseNum(slider.value, 3.0);

            var badgeVal = document.getElementById('badge_val_' + itemId + '_' + actionIdx);
            if (badgeVal) {
                // If it's an input box, set .value; otherwise set .textContent
                if ('value' in badgeVal) {
                    if (document.activeElement !== badgeVal) {
                        badgeVal.value = val.toFixed(2);
                    }
                } else {
                    badgeVal.textContent = val.toFixed(2);
                }
            }

            if (!items[itemId]) {
                items[itemId] = [];
            }
            items[itemId].push(val);
        });

        var totalItemScores = 0;
        var itemCount = 0;

        for (var itemId in items) {
            var scores = items[itemId];
            var itemAvg = 0;
            if (scores.length > 0) {
                var sum = 0;
                for (var i = 0; i < scores.length; i++) {
                    sum += scores[i];
                }
                itemAvg = sum / scores.length;
            }

            var avgDisplay = document.getElementById('avg_display_' + itemId);
            if (avgDisplay) {
                avgDisplay.textContent = itemAvg.toFixed(2);
            }
            var itemScoreInput = document.getElementById('item_score_' + itemId);
            if (itemScoreInput) {
                itemScoreInput.value = itemAvg.toFixed(2);
            }

            totalItemScores += itemAvg;
            itemCount++;
        }

        var overallScore = itemCount > 0 ? (totalItemScores / itemCount) : 3.0;

        var badgeOverall = document.getElementById('badge_total_overall_score');
        if (badgeOverall) {
            badgeOverall.textContent = overallScore.toFixed(2);
        }
        var finalScoreInput = document.getElementById('final_score');
        if (finalScoreInput) {
            finalScoreInput.value = overallScore.toFixed(2);
        }

        updateRatingBadge(overallScore);
    }

    function updateSliderVisual(slider, val) {
        if (!slider) return;
        var pct = Math.max(0, Math.min(100, (val / 4.0) * 100));
        slider.style.setProperty('--fill', pct.toFixed(1) + '%');

        var col = slider.closest('.ap-slider-col');
        if (col) {
            var roundVal = Math.round(val);
            col.querySelectorAll('.ap-ticks span').forEach(function (tick) {
                if (tick.dataset && tick.dataset.val) {
                    if (parseInt(tick.dataset.val, 10) === roundVal) {
                        tick.classList.add('is-active');
                    } else {
                        tick.classList.remove('is-active');
                    }
                }
            });
        }
    }

    document.querySelectorAll('.action-slider').forEach(function (el) {
        updateSliderVisual(el, parseFloat(el.value) || 3.0);
        el.addEventListener('input', function () {
            var val = parseFloat(this.value);
            updateSliderVisual(this, val);
            var numInput = document.getElementById('badge_val_' + this.dataset.itemId + '_' + this.dataset.actionIdx);
            if (numInput && 'value' in numInput) {
                numInput.value = val.toFixed(2);
                numInput.classList.add('has-score');
            }
            var hidden = document.getElementById('action_score_hidden_' + this.dataset.itemId + '_' + this.dataset.actionIdx);
            if (hidden) {
                hidden.value = val.toFixed(2);
            }
            recalcTotals();
        });
        el.addEventListener('change', recalcTotals);
    });

    // Clickable tick numbers (1, 2, 3, 4)
    document.querySelectorAll('.ap-ticks span').forEach(function (tick) {
        tick.addEventListener('click', function () {
            var targetVal = parseFloat(this.dataset.val);
            if (isNaN(targetVal)) return;

            var col = this.closest('.ap-slider-col');
            if (!col) return;
            var slider = col.querySelector('.action-slider');
            if (slider && !slider.disabled) {
                slider.value = targetVal;
                updateSliderVisual(slider, targetVal);

                var numInput = document.getElementById('badge_val_' + slider.dataset.itemId + '_' + slider.dataset.actionIdx);
                if (numInput && 'value' in numInput) {
                    numInput.value = targetVal.toFixed(2);
                    numInput.classList.add('has-score');
                }
                var hidden = document.getElementById('action_score_hidden_' + slider.dataset.itemId + '_' + slider.dataset.actionIdx);
                if (hidden) {
                    hidden.value = targetVal.toFixed(2);
                }
                recalcTotals();
            }
        });
    });

    // Bidirectional sync: live typing into score number inputs
    document.addEventListener('input', function (e) {
        if (e.target && e.target.classList.contains('action-score-input')) {
            var input = e.target;
            var val = parseFloat(input.value);
            if (!isNaN(val)) {
                var slider = document.querySelector('.action-slider[data-item-id="' + input.dataset.itemId + '"][data-action-idx="' + input.dataset.actionIdx + '"]');
                if (slider) {
                    slider.value = val;
                    updateSliderVisual(slider, val);
                }
                input.classList.add('has-score');
                var hidden = document.getElementById('action_score_hidden_' + input.dataset.itemId + '_' + input.dataset.actionIdx);
                if (hidden) {
                    hidden.value = val.toFixed(2);
                }
                recalcTotals();
            }
        }
    });

    // Clamp value on change/blur for typed score inputs
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('action-score-input')) {
            var input = e.target;
            var val = parseFloat(input.value);
            if (isNaN(val)) {
                val = 3.00;
            }
            val = Math.max(0.00, Math.min(4.00, Math.round(val / 0.05) * 0.05));
            input.value = val.toFixed(2);
            input.classList.add('has-score');

            var slider = document.querySelector('.action-slider[data-item-id="' + input.dataset.itemId + '"][data-action-idx="' + input.dataset.actionIdx + '"]');
            if (slider) {
                slider.value = val;
                updateSliderVisual(slider, val);
            }
            var hidden = document.getElementById('action_score_hidden_' + input.dataset.itemId + '_' + input.dataset.actionIdx);
            if (hidden) {
                hidden.value = val.toFixed(2);
            }
            recalcTotals();
        }
    });

    document.querySelectorAll('.ka-item-checkbox, .boolean-item-checkbox').forEach(function (el) {
        el.addEventListener('change', function () {
            recalcTotals();
        });
    });

    recalcTotals();

    function executeSave(btn) {
        if (typeof window.jQuery === 'undefined') {
            alert('Memuat dependensi sistem, silakan coba sesaat lagi...');
            return;
        }
        var $ = window.jQuery;
        var $btn = $(btn);
        var oriHtml = $btn.html();
        var formEl = document.getElementById('form_appraisal_evaluation');
        var formData = new FormData(formEl);

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');

        $.ajax({
            url: '<?= site_url("sas/penilaian/create") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'JSON',
            success: function (res) {
                $btn.prop('disabled', false).html(oriHtml);
                var r = Array.isArray(res) ? res[0] : res;
                var isOk = !!(r && (r.success === true || r.success === 'true'));
                var msg  = (r && r.message) || 'Data penilaian berhasil disimpan.';

                if (isOk) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: msg,
                        type: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    });

                    setTimeout(function () {
                        window.location.href = '<?= site_url("sas/penilaian") ?>';
                    }, 1200);
                } else {
                    Swal.fire('Gagal Menyimpan', msg, 'error');
                }
            },
            error: function (xhr, status, err) {
                $btn.prop('disabled', false).html(oriHtml);
                var resp = xhr.responseJSON;
                var r = Array.isArray(resp) ? resp[0] : resp;
                var errMsg = (r && r.message)
                    ? r.message
                    : 'Terjadi kesalahan sistem (' + err + ').';
                Swal.fire('Error', errMsg, 'error');
            }
        });
    }

    // Capture phase interception: catches click before Process.js or jQuery bubbling handlers can run
    document.addEventListener('click', function (e) {
        var saveBtn = e.target.closest('.save_form');
        if (saveBtn) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            executeSave(saveBtn);
            return false;
        }

        var closeBtn = e.target.closest('.close_form');
        if (closeBtn) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            window.location.href = '<?= site_url("sas/penilaian") ?>';
            return false;
        }
    }, true);

    function initFormActions() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(initFormActions, 30);
            return;
        }
        var $ = window.jQuery;

        $('.main-panel').removeClass('is-loading');

        $('.save_form').off('click');
        $('.close_form').off('click');

        window.clearErrorForm = function (f) {
            $('.main-panel').removeClass('is-loading');
            if ($.fn.waitMe) {
                $('*').waitMe('hide');
            }
        };
    }

    initFormActions();

    // Always define window.showAuditLogModal safely on window
    window.showAuditLogModal = function (id) {
        var targetId = id || appraisalId;
        if (typeof window.jQuery === 'undefined') {
            alert('Memuat dependensi sistem, silakan coba sesaat lagi...');
            return;
        }
        var $ = window.jQuery;
        $('#modal_audit_log').modal('show');
        $('#audit_timeline_container').html(
            '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Memuat riwayat log...</p></div>'
        );

        $.ajax({
            url: '<?= base_url('sas/penilaian/auditLogs') ?>/' + targetId,
            type: 'GET',
            dataType: 'JSON',
            success: function (res) {
                var logs = (res && res[0] && res[0].data) ? res[0].data : (res && res.data ? res.data : []);

                if (!logs || logs.length === 0) {
                    $('#audit_timeline_container').html(
                        '<div class="text-center py-4 text-muted"><i class="fas fa-info-circle fa-2x mb-2 d-block"></i><p class="mb-0">Belum ada riwayat aktivitas untuk form penilaian ini.</p></div>'
                    );
                    return;
                }

                var html = '<ul class="ap-timeline">';
                $.each(logs, function (i, log) {
                    var actionTitle = log.action || '-';
                    var timeStr     = log.created_at ? log.created_at : '-';
                    var actor       = log.actor_name ? (log.actor_name + (log.actor_username ? ' (' + log.actor_username + ')' : '')) : ('User ID: ' + (log.actor_id || '-'));
                    var notes       = log.notes || '-';

                    var badgeClass = '';
                    if (actionTitle === 'KICKBACK_GM')          badgeClass = 'is-danger';
                    else if (actionTitle === 'GM_OVERRIDE_ITEM') badgeClass = 'is-warning';
                    else if (actionTitle === 'UPDATE_ITEM_SCORE') badgeClass = 'is-info';
                    else if (actionTitle === 'ACKNOWLEDGE_EMPLOYEE') badgeClass = 'is-success';

                    html += '<li class="ap-timeline-item">' +
                        '<div class="ap-timeline-head">' +
                            '<span class="ap-log-badge ' + badgeClass + '">' + $('<div>').text(actionTitle).html() + '</span>' +
                            '<span class="ap-timeline-time"><i class="far fa-clock mr-1"></i>' + $('<div>').text(timeStr).html() + '</span>' +
                        '</div>' +
                        '<div class="ap-timeline-actor"><strong>Aktor:</strong> ' + $('<div>').text(actor).html() + '</div>' +
                        '<div class="ap-timeline-note">' + $('<div>').text(notes).html() + '</div>' +
                    '</li>';
                });
                html += '</ul>';

                $('#audit_timeline_container').html(html);
            },
            error: function () {
                $('#audit_timeline_container').html(
                    '<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i><p class="mb-0">Gagal memuat log riwayat aktivitas.</p></div>'
                );
            }
        });
    };
})();
</script>
<?= $this->endSection() ?>
