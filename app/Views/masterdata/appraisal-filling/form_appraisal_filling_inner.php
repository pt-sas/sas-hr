<?php
    $snapshot = $template_snapshot ?? [];
    $sections = $snapshot['sections'] ?? [];
    $items    = $snapshot['items'] ?? [];

    $is_operator = false;
    foreach ($sections as $s) {
        $st = strtoupper($s['section_type'] ?? '');
        if ($st === 'WEIGHTED_CHECKLIST' || $st === 'SINGLE_CHECKLIST') {
            $is_operator = true;
            break;
        }
    }
    if (!$is_operator) {
        foreach ($items as $it) {
            $itType = strtoupper($it['input_type'] ?? '');
            if ($itType === 'WEIGHTED_CHECKLIST' || $itType === 'SINGLE_CHECKLIST') {
                $is_operator = true;
                break;
            }
        }
    }
?>
<link rel="stylesheet" href="/form_appraisal_filling_style.css">

<div class="ap-wrap pb-3">
    <!-- MAIN EVALUATION FORM (Action T standard) -->
    <form id="form_appraisal_evaluation">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group d-none"></div>
                <input type="hidden" name="md_appraisal_id" id="md_appraisal_id" value="<?= (int) $appraisal_id ?>">
                <input type="hidden" name="final_score" id="final_score" value="<?= number_format((float) ($appraisal->final_score ?? 3.00), 2) ?>">
                <input type="hidden" name="responses_json" id="hidden_responses_payload">

                <!-- 1. INFO CARD -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_info_card'); ?>

                <?php if ($is_operator): ?>
                    <!-- 2. OPERATOR PENILAIAN KINERJA -->
                    <?= $this->include('Views/masterdata/appraisal-filling/fill_operator_performance'); ?>

                    <!-- 3. OPERATOR KRITERIA TAMBAHAN -->
                    <?= $this->include('Views/masterdata/appraisal-filling/fill_operator_additional'); ?>
                <?php else: ?>
                    <!-- 2. PERFORMA -->
                    <?= $this->include('Views/masterdata/appraisal-filling/fill_performance'); ?>

                    <!-- 3. KOMPETENSI TAMBAHAN -->
                    <?= $this->include('Views/masterdata/appraisal-filling/fill_additional'); ?>
                <?php endif; ?>

                <!-- 4. CATATAN & FEEDBACK EVALUATOR -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_feedback'); ?>

                <!-- 5. KONFIRMASI & PENGAJUAN -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_confirmation'); ?>
            </div>
        </div>
    </form>
</div>

<script>
<?php if ($is_operator): ?>
(function () {
    'use strict';

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

        if (score >= 2.60) {
            label = 'Sangat Baik';
            badgeCls = 'r-great';
        } else if (score >= 1.80) {
            label = 'Baik';
            badgeCls = 'r-good';
        } else {
            label = 'Kurang';
            badgeCls = 'r-low';
        }

        badge.className = 'ap-rating ' + badgeCls;
        badge.textContent = label;
    }

    function syncOperatorPayload() {
        var payloadArr = [];
        var totalWeightedScore = 0;
        var totalWeight = 0;

        // Collect Weighted Checklist responses
        document.querySelectorAll('.operator-aspect-row').forEach(function (row) {
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

        // Collect Single Checklist responses
        document.querySelectorAll('.operator-kriteria-row').forEach(function (row) {
            var itemId = row.dataset.itemId;
            var cb = row.querySelector('.operator-kriteria-checkbox');
            var isChecked = cb ? cb.checked : false;
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

        var finalScore = 0.00;
        if (totalWeight > 0) {
            finalScore = totalWeightedScore / totalWeight;
        }

        var badgeOverall = document.getElementById('badge_total_overall_score');
        if (badgeOverall) {
            badgeOverall.textContent = finalScore > 0 ? finalScore.toFixed(2) : '-';
        }
        var finalScoreInput = document.getElementById('final_score');
        if (finalScoreInput) {
            finalScoreInput.value = finalScore.toFixed(2);
        }

        updateRatingBadge(finalScore);

        var payloadEl = document.getElementById('hidden_responses_payload');
        if (payloadEl) {
            payloadEl.value = JSON.stringify(payloadArr);
        }
    }

    document.querySelectorAll('.operator-score-radio').forEach(function (el) {
        el.addEventListener('change', syncOperatorPayload);
    });

    document.querySelectorAll('.operator-kriteria-checkbox').forEach(function (el) {
        el.addEventListener('change', syncOperatorPayload);
    });

    document.querySelectorAll('.operator-other-input').forEach(function (el) {
        el.addEventListener('input', syncOperatorPayload);
        el.addEventListener('change', syncOperatorPayload);
    });

    syncOperatorPayload();
})();
<?php else: ?>
(function () {
    'use strict';

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

    function syncPayload(itemsData) {
        var payloadArr = [];
        for (var itemId in itemsData) {
            payloadArr.push(itemsData[itemId]);
        }

        // Gather all additional boolean/checklist responses (both checked and unchecked)
        var additionalItems = {};
        document.querySelectorAll('.ka-item-checkbox').forEach(function (cb) {
            var pId = cb.dataset.parentId;
            if (!additionalItems[pId]) {
                additionalItems[pId] = {
                    md_appraisal_template_item_id: pId,
                    checked_actions: [],
                    boolean_value: 'N'
                };
            }
            if (cb.checked) {
                additionalItems[pId].checked_actions.push(cb.value);
                additionalItems[pId].boolean_value = 'Y';
            }
        });

        document.querySelectorAll('.boolean-item-checkbox').forEach(function (cb) {
            var pId = cb.dataset.parentId || (cb.name && cb.name.match(/\[(\d+)\]/) ? cb.name.match(/\[(\d+)\]/)[1] : null);
            if (pId) {
                additionalItems[pId] = {
                    md_appraisal_template_item_id: pId,
                    boolean_value: cb.checked ? 'Y' : 'N'
                };
            }
        });

        for (var addId in additionalItems) {
            payloadArr.push(additionalItems[addId]);
        }

        var payloadEl = document.getElementById('hidden_responses_payload');
        if (payloadEl) {
            payloadEl.value = JSON.stringify(payloadArr);
        }
    }

    function recalcTotals() {
        var sliders = document.querySelectorAll('.action-slider');
        if (sliders.length === 0) {
            var initialScore = parseNum(document.getElementById('final_score') ? document.getElementById('final_score').value : 3.0, 3.0);
            updateRatingBadge(initialScore);
            return;
        }

        var items = {};
        var itemsPayload = {};

        sliders.forEach(function (slider) {
            var itemId = slider.dataset.itemId;
            var actionIdx = slider.dataset.actionIdx;
            var val = parseNum(slider.value, 3.0);

            var badgeVal = document.getElementById('badge_val_' + itemId + '_' + actionIdx);
            if (badgeVal) {
                if ('value' in badgeVal) {
                    if (document.activeElement !== badgeVal) {
                        badgeVal.value = val.toFixed(2);
                    }
                } else {
                    badgeVal.textContent = val.toFixed(2);
                }
            }
            var hiddenActionInput = document.getElementById('action_score_hidden_' + itemId + '_' + actionIdx);
            if (hiddenActionInput) {
                hiddenActionInput.value = val.toFixed(2);
            }

            if (!items[itemId]) {
                items[itemId] = [];
                itemsPayload[itemId] = {
                    md_appraisal_template_item_id: itemId,
                    score: 0,
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

            itemsPayload[itemId].score = itemAvg.toFixed(2);

            var blockEl = document.getElementById('competency_block_' + itemId);
            var weight = blockEl ? parseNum(blockEl.dataset.weight, 1.0) : 1.0;
            if (weight <= 0) {
                weight = 1.0;
            }

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

        var badgeOverall = document.getElementById('badge_total_overall_score');
        if (badgeOverall) {
            badgeOverall.textContent = overallScore.toFixed(2);
        }
        var finalScoreInput = document.getElementById('final_score');
        if (finalScoreInput) {
            finalScoreInput.value = overallScore.toFixed(2);
        }

        updateRatingBadge(overallScore);
        syncPayload(itemsPayload);
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
        updateSliderVisual(el, parseNum(el.value, 3.0));
        el.addEventListener('input', function () {
            var val = parseNum(this.value, 3.0);
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
})();
<?php endif; ?>
</script>
