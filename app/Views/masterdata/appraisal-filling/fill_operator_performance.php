<?php
    $sections = $template_snapshot['sections'] ?? [];
    $items    = $template_snapshot['items'] ?? [];

    $itemsBySection = [];
    foreach ($items as $item) {
        $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
        $itemsBySection[$secId][] = $item;
    }

    $weightedSections = [];
    foreach ($sections as $sec) {
        $secType = strtoupper($sec['section_type'] ?? '');
        if ($secType === 'WEIGHTED_CHECKLIST') {
            $weightedSections[] = $sec;
        }
    }

    $weightedItems = [];
    foreach ($weightedSections as $wSec) {
        $secId = (int) $wSec['md_appraisal_section_id'];
        foreach ($itemsBySection[$secId] ?? [] as $it) {
            $weightedItems[] = $it;
        }
    }

    // Fallback if sections were not tagged with WEIGHTED_CHECKLIST
    if (empty($weightedItems) && !empty($items)) {
        foreach ($items as $it) {
            if (strtoupper($it['input_type'] ?? '') === 'WEIGHTED_CHECKLIST') {
                $weightedItems[] = $it;
            }
        }
    }

    $canEditScores = in_array($user_mode, ['EVALUATOR', 'GM', 'HR'], true) || !empty($is_preview);
    $initialOverallScore = $appraisal->final_score !== null ? (float) $appraisal->final_score : 3.00;
?>

<div class="ap-card mb-4">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center" style="gap: 12px;">
            <h5 class="ap-card-title mb-0">II. Penilaian Kinerja</h5>
            <span class="ap-category-badge">Sangat Baik / Baik / Kurang</span>
        </div>
        <span class="text-muted font-weight-bold" style="font-size: 13px;">*Beri tanda centang pada kolom yang ingin diisi</span>
    </div>

    <div class="ap-score-bar" role="status" aria-live="polite">
        <span class="ap-score-label">Skor Akhir (Pratinjau)</span>
        <span class="ap-score-group">
            <span class="ap-score-num" id="badge_total_overall_score"><?= number_format($initialOverallScore, 2) ?></span>
            <span class="ap-rating r-good" id="badge_overall_rating">-</span>
        </span>
    </div>

    <div class="table-responsive ap-table-wrapper">
        <table class="ap-table mb-0" id="table_operator_performance">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 50%;">Aspek Penilaian</th>
                    <th style="width: 15%; text-align: center;">Sangat Baik</th>
                    <th style="width: 15%; text-align: center;">Baik</th>
                    <th style="width: 15%; text-align: center;">Kurang</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($weightedItems)): ?>
                    <?php foreach ($weightedItems as $idx => $item): ?>
                        <?php
                            $itemId   = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                            $cfg      = (array) ($item['config'] ?? []);
                            $title    = !empty($item['title']) ? $item['title'] : ($cfg['aspect'] ?? ('Aspek ' . ($idx + 1)));
                            $weight   = (float) ($item['weight'] ?? $cfg['bobot'] ?? 1.0);
                            if ($weight <= 0) $weight = 1.0;

                            $resp = $responses_by_item_id[$itemId] ?? null;
                            $savedScore = null;

                            if ($resp) {
                                if ($user_mode === 'GM' && !empty($resp->gm_response)) {
                                    $payload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                                    $savedScore = isset($payload['score']) ? (float) $payload['score'] : ($resp->score !== null ? (float) $resp->score : null);
                                } elseif (!empty($resp->evaluator_response)) {
                                    $payload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                    $savedScore = isset($payload['score']) ? (float) $payload['score'] : ($resp->score !== null ? (float) $resp->score : null);
                                } elseif ($resp->score !== null) {
                                    $savedScore = (float) $resp->score;
                                }
                            }
                        ?>
                        <tr class="operator-aspect-row" 
                            data-item-id="<?= $itemId ?>" 
                            data-weight="<?= $weight ?>">
                            <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                <?= $idx + 1 ?>
                            </td>
                            <td class="align-middle">
                                <div class="font-weight-600 text-dark" style="font-size: 13.5px;"><?= esc($title) ?></div>
                                <?php if (!empty($item['description'])): ?>
                                    <small class="text-muted d-block"><?= esc($item['description']) ?></small>
                                <?php endif; ?>
                            </td>

                            <!-- Sangat Baik (Score 3) -->
                            <td class="text-center align-middle py-3 ap-check-cell">
                                <?php if ($canEditScores): ?>
                                    <div class="custom-control custom-radio d-inline-flex justify-content-center align-items-center">
                                        <input type="radio" 
                                               id="op_score_<?= $itemId ?>_3"
                                               name="operator_score[<?= $itemId ?>]" 
                                               value="3" 
                                               class="custom-control-input operator-score-radio"
                                               data-item-id="<?= $itemId ?>"
                                               data-weight="<?= $weight ?>"
                                               <?= ($savedScore !== null && round($savedScore) == 3) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="op_score_<?= $itemId ?>_3"></label>
                                    </div>
                                <?php else: ?>
                                    <span class="operator-readonly-indicator font-weight-bold <?= ($savedScore !== null && round($savedScore) == 3) ? 'text-success' : 'text-muted-light' ?>">
                                        <?= ($savedScore !== null && round($savedScore) == 3) ? '<i class="fas fa-check text-success"></i>' : '-' ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Baik (Score 2) -->
                            <td class="text-center align-middle py-3 ap-check-cell">
                                <?php if ($canEditScores): ?>
                                    <div class="custom-control custom-radio d-inline-flex justify-content-center align-items-center">
                                        <input type="radio" 
                                               id="op_score_<?= $itemId ?>_2"
                                               name="operator_score[<?= $itemId ?>]" 
                                               value="2" 
                                               class="custom-control-input operator-score-radio"
                                               data-item-id="<?= $itemId ?>"
                                               data-weight="<?= $weight ?>"
                                               <?= ($savedScore !== null && round($savedScore) == 2) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="op_score_<?= $itemId ?>_2"></label>
                                    </div>
                                <?php else: ?>
                                    <span class="operator-readonly-indicator font-weight-bold <?= ($savedScore !== null && round($savedScore) == 2) ? 'text-success' : 'text-muted-light' ?>">
                                        <?= ($savedScore !== null && round($savedScore) == 2) ? '<i class="fas fa-check text-success"></i>' : '-' ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Kurang (Score 1) -->
                            <td class="text-center align-middle py-3 ap-check-cell">
                                <?php if ($canEditScores): ?>
                                    <div class="custom-control custom-radio d-inline-flex justify-content-center align-items-center">
                                        <input type="radio" 
                                               id="op_score_<?= $itemId ?>_1"
                                               name="operator_score[<?= $itemId ?>]" 
                                               value="1" 
                                               class="custom-control-input operator-score-radio"
                                               data-item-id="<?= $itemId ?>"
                                               data-weight="<?= $weight ?>"
                                               <?= ($savedScore !== null && round($savedScore) == 1) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="op_score_<?= $itemId ?>_1"></label>
                                    </div>
                                <?php else: ?>
                                    <span class="operator-readonly-indicator font-weight-bold <?= ($savedScore !== null && round($savedScore) == 1) ? 'text-success' : 'text-muted-light' ?>">
                                        <?= ($savedScore !== null && round($savedScore) == 1) ? '<i class="fas fa-check text-success"></i>' : '-' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle mr-1"></i> Belum ada butir Aspek Penilaian Kinerja terdaftar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.cursor-pointer {
    cursor: pointer;
}
.text-muted-light {
    color: #d1d5db;
}
.operator-radio-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: background 0.15s ease, border-color 0.15s ease;
}
.operator-radio-label:hover {
    background: #f1f5f9;
}
.operator-score-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.operator-check-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 2px solid #475569;
    background: #ffffff;
    color: transparent;
    font-size: 13px;
    line-height: 1;
    transition: all 0.15s ease;
    user-select: none;
}
.operator-score-radio:checked + .operator-check-indicator {
    background: #16a34a;
    border-color: #15803d;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(22, 163, 74, 0.3);
}
.operator-radio-label:focus-within .operator-check-indicator {
    outline: 2px solid #86efac;
}
.operator-readonly-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 4px;
    font-size: 14px;
}
</style>
