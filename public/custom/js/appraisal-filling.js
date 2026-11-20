(function (window, document) {
    'use strict';

    var activeHostConfig = null;
    var hostClickBound = false;

    function parseNum(val, fallback) {
        if (val === null || val === undefined || val === '') return fallback;
        var s = String(val).trim().replace(',', '.');
        var n = parseFloat(s);
        return isNaN(n) ? fallback : n;
    }

    function byId(root, id) {
        return root ? root.querySelector('[id="' + id + '"]') : null;
    }

    function formatOperatorSuffix(checkedCount) {
        if (!checkedCount || checkedCount <= 0) return '';
        if (checkedCount <= 3) return 'A'.repeat(checkedCount);
        return ' (+' + checkedCount + 'A)';
    }

    function updateRatingBadge(formEl, score, isOperator) {
        var badge = byId(formEl, 'badge_overall_rating');
        if (!badge) return;

        if (score === null || score === undefined || isNaN(score)) {
            badge.className = 'ap-rating r-muted';
            badge.textContent = 'Belum Dinilai';
            return;
        }

        var label = 'Kurang';
        var badgeCls = 'r-low';

        if (isOperator) {
            var finalScoreInt = Math.round(score);
            if (finalScoreInt >= 13 && finalScoreInt <= 15) {
                label = 'Sangat Baik';
                badgeCls = 'r-great';
            } else if (finalScoreInt >= 8 && finalScoreInt <= 12) {
                label = 'Baik';
                badgeCls = 'r-good';
            } else if (finalScoreInt >= 5 && finalScoreInt <= 7) {
                label = 'Kurang';
                badgeCls = 'r-low';
            } else {
                label = '-';
                badgeCls = 'r-muted';
            }
        } else {
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
        }

        badge.className = 'ap-rating ' + badgeCls;
        badge.textContent = label;
    }

    function initOperatorForm(formEl) {
        function syncOperatorPayload() {
            var payloadArr = [];
            var totalWeightedScore = 0;
            var totalWeight = 0;

            formEl.querySelectorAll('.operator-aspect-row').forEach(function (row) {
                var itemId = row.dataset.itemId;
                var weight = parseNum(row.dataset.weight, 1.0);
                var checkedRadio = row.querySelector('.operator-score-radio:checked');
                var score = checkedRadio ? parseNum(checkedRadio.value, null) : null;

                if (score !== null) {
                    totalWeightedScore += (score * weight);
                    totalWeight += weight;
                }

                payloadArr.push({
                    md_appraisal_template_item_id: itemId,
                    score: score !== null ? score : null
                });
            });

            var checkedCount = 0;
            formEl.querySelectorAll('.operator-kriteria-row').forEach(function (row) {
                var itemId = row.dataset.itemId;
                var cb = row.querySelector('.operator-kriteria-checkbox');
                var isChecked = cb ? cb.checked : false;
                if (isChecked) {
                    checkedCount++;
                }
                var otherInput = row.querySelector('.operator-other-input');
                var otherText = otherInput ? otherInput.value.trim() : '';

                var itemObj = {
                    md_appraisal_template_item_id: itemId,
                    boolean_value: isChecked ? 'Y' : 'N'
                };
                if (otherText !== '') {
                    itemObj.other_text = otherText;
                }
                payloadArr.push(itemObj);
            });

            var hasScoredItem = totalWeight > 0;
            var totNilai = hasScoredItem ? totalWeightedScore : null;
            var suffixA = formatOperatorSuffix(checkedCount);
            var displaySkorAkhir = totNilai !== null ? (String(Math.round(totNilai)) + suffixA) : '—';

            var badgeOverall = byId(formEl, 'badge_total_overall_score');
            if (badgeOverall) {
                badgeOverall.textContent = displaySkorAkhir;
            }
            var finalScoreInput = byId(formEl, 'final_score');
            if (finalScoreInput) {
                finalScoreInput.value = totNilai !== null ? totNilai.toFixed(2) : '';
            }

            updateRatingBadge(formEl, totNilai, true);

            var payloadEl = byId(formEl, 'hidden_responses_payload');
            if (payloadEl) {
                payloadEl.value = JSON.stringify(payloadArr);
            }
        }

        formEl.addEventListener('change', function (e) {
            if (!e.target) return;
            if (
                e.target.classList.contains('operator-score-radio') ||
                e.target.classList.contains('operator-kriteria-checkbox') ||
                e.target.classList.contains('operator-other-input')
            ) {
                syncOperatorPayload();
            }
        });

        formEl.addEventListener('input', function (e) {
            if (e.target && e.target.classList.contains('operator-other-input')) {
                syncOperatorPayload();
            }
        });

        syncOperatorPayload();
    }

    function initStaffForm(formEl) {
        function syncPayload(itemsData) {
            var payloadArr = [];
            for (var itemId in itemsData) {
                if (Object.prototype.hasOwnProperty.call(itemsData, itemId)) {
                    payloadArr.push(itemsData[itemId]);
                }
            }

            var additionalItems = {};
            formEl.querySelectorAll('.ka-item-checkbox').forEach(function (cb) {
                var pId = cb.dataset.parentId;
                if (!additionalItems[pId]) {
                    additionalItems[pId] = {
                        md_appraisal_template_item_id: pId,
                        checked_actions: [],
                        boolean_value: 'N'
                    };
                }
                if (cb.checked) {
                    var parsedIdx = parseInt(cb.value, 10);
                    additionalItems[pId].checked_actions.push(isNaN(parsedIdx) ? cb.value : parsedIdx);
                    additionalItems[pId].boolean_value = 'Y';
                }
            });

            formEl.querySelectorAll('.boolean-item-checkbox').forEach(function (cb) {
                var match = cb.name ? cb.name.match(/\[(\d+)\]/) : null;
                var pId = cb.dataset.parentId || (match ? match[1] : null);
                if (pId) {
                    additionalItems[pId] = {
                        md_appraisal_template_item_id: pId,
                        boolean_value: cb.checked ? 'Y' : 'N'
                    };
                }
            });

            for (var addId in additionalItems) {
                if (Object.prototype.hasOwnProperty.call(additionalItems, addId)) {
                    payloadArr.push(additionalItems[addId]);
                }
            }

            var payloadEl = byId(formEl, 'hidden_responses_payload');
            if (payloadEl) {
                payloadEl.value = JSON.stringify(payloadArr);
            }
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

        function syncActionInputs(itemId, actionIdx, val) {
            var numInput = byId(formEl, 'badge_val_' + itemId + '_' + actionIdx);
            if (numInput && 'value' in numInput) {
                numInput.value = val.toFixed(2);
                numInput.classList.add('has-score');
            }
            var hidden = byId(formEl, 'action_score_hidden_' + itemId + '_' + actionIdx);
            if (hidden) {
                hidden.value = val.toFixed(2);
            }
        }

        function recalcTotals() {
            var sliders = formEl.querySelectorAll('.action-slider');
            if (sliders.length === 0) {
                var scoreInput = byId(formEl, 'final_score');
                var initialScore = parseNum(scoreInput ? scoreInput.value : null, null);
                updateRatingBadge(formEl, initialScore, false);
                return;
            }

            var items = {};
            var itemsPayload = {};

            sliders.forEach(function (slider) {
                var itemId = slider.dataset.itemId;
                var actionIdx = slider.dataset.actionIdx;
                var val = parseNum(slider.value, 3.0);

                var badgeVal = byId(formEl, 'badge_val_' + itemId + '_' + actionIdx);
                if (badgeVal) {
                    if ('value' in badgeVal) {
                        if (document.activeElement !== badgeVal) {
                            badgeVal.value = val.toFixed(2);
                        }
                    } else {
                        badgeVal.textContent = val.toFixed(2);
                    }
                }
                var hiddenActionInput = byId(formEl, 'action_score_hidden_' + itemId + '_' + actionIdx);
                if (hiddenActionInput) {
                    hiddenActionInput.value = val.toFixed(2);
                }

                if (!items[itemId]) {
                    items[itemId] = [];
                    itemsPayload[itemId] = {
                        md_appraisal_template_item_id: itemId,
                        action_scores: []
                    };
                }
                items[itemId].push(val);
                itemsPayload[itemId].action_scores.push(val);
            });

            var totalWeightedScore = 0;
            var totalWeight = 0;
            var totalUnweightedScore = 0;
            var itemCount = 0;

            for (var itemId in items) {
                if (!Object.prototype.hasOwnProperty.call(items, itemId)) continue;
                var scores = items[itemId];
                var itemAvg = 0;
                if (scores.length > 0) {
                    var sum = 0;
                    for (var i = 0; i < scores.length; i++) {
                        sum += scores[i];
                    }
                    itemAvg = sum / scores.length;
                }

                var avgDisplay = byId(formEl, 'avg_display_' + itemId);
                if (avgDisplay) {
                    avgDisplay.textContent = itemAvg.toFixed(2);
                }
                var itemScoreInput = byId(formEl, 'item_score_' + itemId);
                if (itemScoreInput) {
                    itemScoreInput.value = itemAvg.toFixed(2);
                }

                var blockEl = byId(formEl, 'competency_block_' + itemId);
                var weight = blockEl ? parseNum(blockEl.dataset.weight, 1.0) : 1.0;
                if (weight <= 0) weight = 1.0;

                totalWeightedScore += (itemAvg * weight);
                totalWeight += weight;
                totalUnweightedScore += itemAvg;
                itemCount++;
            }

            var overallScore = 3.0;
            if (totalWeight > 0) {
                overallScore = totalWeightedScore / totalWeight;
            } else if (itemCount > 0) {
                overallScore = totalUnweightedScore / itemCount;
            }

            var badgeOverall = byId(formEl, 'badge_total_overall_score');
            if (badgeOverall) {
                badgeOverall.textContent = overallScore.toFixed(2);
            }
            var finalScoreInput = byId(formEl, 'final_score');
            if (finalScoreInput) {
                finalScoreInput.value = overallScore.toFixed(2);
            }

            updateRatingBadge(formEl, overallScore, false);
            syncPayload(itemsPayload);
        }

        formEl.querySelectorAll('.action-slider').forEach(function (el) {
            updateSliderVisual(el, parseNum(el.value, 3.0));
        });

        formEl.addEventListener('input', function (e) {
            var target = e.target;
            if (!target) return;

            if (target.classList.contains('action-slider')) {
                var sliderVal = parseNum(target.value, 3.0);
                updateSliderVisual(target, sliderVal);
                syncActionInputs(target.dataset.itemId, target.dataset.actionIdx, sliderVal);
                recalcTotals();
                return;
            }

            if (target.classList.contains('action-score-input')) {
                var val = parseFloat(target.value);
                if (!isNaN(val)) {
                    var slider = formEl.querySelector(
                        '.action-slider[data-item-id="' + target.dataset.itemId + '"][data-action-idx="' + target.dataset.actionIdx + '"]'
                    );
                    if (slider) {
                        slider.value = val;
                        updateSliderVisual(slider, val);
                    }
                    target.classList.add('has-score');
                    var hidden = byId(formEl, 'action_score_hidden_' + target.dataset.itemId + '_' + target.dataset.actionIdx);
                    if (hidden) hidden.value = val.toFixed(2);
                    recalcTotals();
                }
            }
        });

        formEl.addEventListener('change', function (e) {
            var target = e.target;
            if (!target) return;

            if (
                target.classList.contains('action-slider') ||
                target.classList.contains('ka-item-checkbox') ||
                target.classList.contains('boolean-item-checkbox')
            ) {
                recalcTotals();
                return;
            }

            if (target.classList.contains('action-score-input')) {
                var val = parseFloat(target.value);
                if (isNaN(val)) val = 3.00;
                val = Math.max(0.00, Math.min(4.00, Math.round(val / 0.05) * 0.05));
                target.value = val.toFixed(2);
                target.classList.add('has-score');

                var slider = formEl.querySelector(
                    '.action-slider[data-item-id="' + target.dataset.itemId + '"][data-action-idx="' + target.dataset.actionIdx + '"]'
                );
                if (slider) {
                    slider.value = val;
                    updateSliderVisual(slider, val);
                }
                var hidden = byId(formEl, 'action_score_hidden_' + target.dataset.itemId + '_' + target.dataset.actionIdx);
                if (hidden) hidden.value = val.toFixed(2);
                recalcTotals();
            }
        });

        formEl.addEventListener('click', function (e) {
            var tick = e.target ? e.target.closest('.ap-ticks span') : null;
            if (!tick || !formEl.contains(tick)) return;

            var targetVal = parseFloat(tick.dataset.val);
            if (isNaN(targetVal)) return;

            var col = tick.closest('.ap-slider-col');
            var slider = col ? col.querySelector('.action-slider') : null;
            if (slider && !slider.disabled) {
                slider.value = targetVal;
                updateSliderVisual(slider, targetVal);
                syncActionInputs(slider.dataset.itemId, slider.dataset.actionIdx, targetVal);
                recalcTotals();
            }
        });

        recalcTotals();
    }

    function initConfirmationToggle(formEl) {
        formEl.querySelectorAll('[data-ap-confirm-callout]').forEach(function (box) {
            var chk = box.querySelector('[data-ap-confirm-checkbox]');
            var scope = box.parentElement || formEl;
            var act = scope.querySelector('[data-ap-confirm-action]');
            var hintDraft = box.querySelector('[data-ap-confirm-hint-draft]');
            var hintSubmit = box.querySelector('[data-ap-confirm-hint-submit]');

            if (!chk || !act) return;

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
        });
    }

    function resolveHostForm(config) {
        if (!config) return null;
        var selector = config.formSelector || '#form_appraisal_evaluation';
        var formEl = document.querySelector(selector);
        if (!formEl || formEl.dataset.mode === 'preview') {
            return null;
        }
        return formEl;
    }

    function executeSave(btn, formEl, config) {
        if (typeof window.jQuery === 'undefined') {
            alert('Memuat dependensi sistem, silakan coba sesaat lagi...');
            return;
        }
        var $ = window.jQuery;
        var $btn = $(btn);
        var oriHtml = $btn.html();
        var formData = new FormData(formEl);

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Menyimpan...');

        $.ajax({
            url: config.saveUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'JSON',
            success: function (res) {
                $btn.prop('disabled', false).html(oriHtml);
                var r = Array.isArray(res) ? res[0] : res;
                var isOk = !!(r && (r.success === true || r.success === 'true'));
                var msg = (r && r.message) || 'Data penilaian berhasil disimpan.';

                if (isOk) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: msg,
                        type: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    if (config.redirectUrl) {
                        setTimeout(function () {
                            window.location.href = config.redirectUrl;
                        }, 1200);
                    }
                } else {
                    Swal.fire('Gagal Menyimpan', msg, 'error');
                }
            },
            error: function (xhr, status, err) {
                $btn.prop('disabled', false).html(oriHtml);
                var resp = xhr.responseJSON;
                var r = Array.isArray(resp) ? resp[0] : resp;
                var errMsg = (r && r.message) ? r.message : 'Terjadi kesalahan sistem (' + err + ').';
                Swal.fire('Error', errMsg, 'error');
            }
        });
    }

    function bindAuditLogModal(config) {
        window.showAuditLogModal = function (id) {
            var formEl = resolveHostForm(activeHostConfig || config);
            var targetId = id || (formEl ? formEl.dataset.appraisalId : null) || (activeHostConfig && activeHostConfig.appraisalId);
            if (!targetId) return;

            if (typeof window.jQuery === 'undefined') {
                alert('Memuat dependensi sistem, silakan coba sesaat lagi...');
                return;
            }
            var $ = window.jQuery;
            var baseUrl = ((activeHostConfig && activeHostConfig.auditLogsUrl) || config.auditLogsUrl || '').replace(/\/+$/, '');
            if (!baseUrl) return;

            $('#modal_audit_log').modal('show');
            $('#audit_timeline_container').html(
                '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Memuat riwayat log...</p></div>'
            );

            $.ajax({
                url: baseUrl + '/' + targetId,
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
                        var timeStr = log.created_at ? log.created_at : '-';
                        var actor = log.actor_name
                            ? (log.actor_name + (log.actor_username ? ' (' + log.actor_username + ')' : ''))
                            : ('User ID: ' + (log.actor_id || '-'));
                        var notes = log.notes || '-';

                        var badgeClass = '';
                        if (actionTitle === 'KICKBACK_REVIEWER' || actionTitle === 'KICKBACK_GM') badgeClass = 'is-danger';
                        else if (actionTitle === 'REVIEWER_OVERRIDE_ITEM' || actionTitle === 'GM_OVERRIDE_ITEM') badgeClass = 'is-warning';
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
    }

    function mount(formOrSelector) {
        var formEl = typeof formOrSelector === 'string'
            ? document.querySelector(formOrSelector)
            : formOrSelector;

        if (!formEl || formEl.dataset.apMounted === '1') {
            return;
        }
        formEl.dataset.apMounted = '1';

        var isOperator = formEl.dataset.isOperator === '1';
        if (isOperator) {
            initOperatorForm(formEl);
        } else {
            initStaffForm(formEl);
        }

        initConfirmationToggle(formEl);
    }

    function bindHostActions(config) {
        if (!config || !config.saveUrl) return;
        activeHostConfig = config;

        if (!hostClickBound) {
            hostClickBound = true;
            document.addEventListener('click', function (e) {
                if (!activeHostConfig) return;

                var saveBtn = e.target ? e.target.closest('.save_form') : null;
                if (saveBtn) {
                    var saveFormEl = resolveHostForm(activeHostConfig);
                    if (saveFormEl) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        executeSave(saveBtn, saveFormEl, activeHostConfig);
                        return false;
                    }
                }

                var closeBtn = e.target ? e.target.closest('.close_form') : null;
                if (closeBtn) {
                    var closeFormEl = resolveHostForm(activeHostConfig);
                    if (closeFormEl && activeHostConfig.isStandalone && activeHostConfig.redirectUrl) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        window.location.href = activeHostConfig.redirectUrl;
                        return false;
                    }
                    if (closeFormEl && typeof activeHostConfig.onClose === 'function') {
                        activeHostConfig.onClose(closeFormEl);
                    }
                }
            }, true);
        }

        function syncHostShellWithJQuery() {
            if (typeof window.jQuery === 'undefined') {
                setTimeout(syncHostShellWithJQuery, 30);
                return;
            }
            var $ = window.jQuery;
            $('.main-panel').removeClass('is-loading');
            $('.save_form').off('click');
            if (activeHostConfig && activeHostConfig.isStandalone) {
                $('.close_form').off('click');
            }
            window.clearErrorForm = function () {
                $('.main-panel').removeClass('is-loading');
                if ($.fn.waitMe) {
                    $('*').waitMe('hide');
                }
            };
        }
        syncHostShellWithJQuery();

        if (config.auditLogsUrl) {
            bindAuditLogModal(config);
        }
    }

    window.AppraisalForm = {
        mount: mount,
        bindHostActions: bindHostActions
    };
})(window, document);
