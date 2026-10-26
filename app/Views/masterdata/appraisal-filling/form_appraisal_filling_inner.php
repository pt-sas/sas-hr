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

                <!-- 2. PERFORMA -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_performance'); ?>

                <!-- 3. KOMPETENSI TAMBAHAN -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_additional'); ?>

                <!-- 4. CATATAN & FEEDBACK EVALUATOR -->
                <?= $this->include('Views/masterdata/appraisal-filling/fill_feedback'); ?>
            </div>
        </div>
    </form>
</div>

<script>
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

        var label = 'Kurang (Unsatisfactory)';
        var badgeCls = 'r-low';

        if (score >= 3.50) {
            label = 'Sangat Baik (Exceeds Expectations)';
            badgeCls = 'r-great';
        } else if (score >= 2.75) {
            label = 'Baik (Meets Expectations)';
            badgeCls = 'r-good';
        } else if (score >= 2.00) {
            label = 'Cukup (Needs Improvement)';
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
                badgeVal.textContent = val.toFixed(2);
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

            if (weight > 0) {
                totalWeightedScore += (itemAvg * weight);
                totalWeight += weight;
            }

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

    document.querySelectorAll('.action-slider').forEach(function (el) {
        el.addEventListener('input', recalcTotals);
        el.addEventListener('change', recalcTotals);
    });

    document.querySelectorAll('.ka-item-checkbox, .boolean-item-checkbox').forEach(function (el) {
        el.addEventListener('change', recalcTotals);
    });

    recalcTotals();
})();
</script>
