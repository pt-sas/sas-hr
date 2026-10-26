<?php
    // Ensure helper function is available even if not passed from controller
    if (!isset($parseActions) || !is_callable($parseActions)) {
        $parseActions = function ($raw): array {
            if (empty($raw)) return [];
            if (is_array($raw)) return array_values(array_filter(array_map('trim', $raw)));
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('trim', $decoded)));
                }
                $lines = explode("\n", $raw);
                return array_values(array_filter(array_map('trim', $lines)));
            }
            return [];
        };
    }

    $sections = $template_snapshot['sections'] ?? [];
    $items    = $template_snapshot['items'] ?? [];

    $itemsBySection = [];
    foreach ($items as $item) {
        $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
        $itemsBySection[$secId][] = $item;
    }

    $scoreSections = [];
    foreach ($sections as $sec) {
        $secType = strtoupper($sec['section_type'] ?? 'SCORE');
        if ($secType === 'SCORE') {
            $scoreSections[] = $sec;
        }
    }

    // Fallback if section_type is not explicitly set
    if (empty($scoreSections) && !empty($sections)) {
        foreach ($sections as $sec) {
            $secId = (int) $sec['md_appraisal_section_id'];
            $secItems = $itemsBySection[$secId] ?? [];
            $hasScore = false;
            $hasBool  = false;
            foreach ($secItems as $si) {
                $it = strtoupper($si['input_type'] ?? '');
                if ($it === 'SCORE') $hasScore = true;
                if ($it === 'BOOLEAN') $hasBool = true;
            }
            if (!($hasBool && !$hasScore)) {
                $scoreSections[] = $sec;
            }
        }
    }

    $allScoreItems = [];
    foreach ($scoreSections as $sSec) {
        $sId = (int) $sSec['md_appraisal_section_id'];
        foreach ($itemsBySection[$sId] ?? [] as $it) {
            $allScoreItems[] = $it;
        }
    }

    $isEvaluatorEditable = ($user_mode === 'EVALUATOR');
    $isGmEditable        = ($user_mode === 'GM');
    $canEditScores       = ($isEvaluatorEditable || $isGmEditable);
    $initialOverallScore = $appraisal->final_score !== null ? (float) $appraisal->final_score : 3.00;
?>

<!-- 2. PERFORMA -->
<div class="ap-card">
    <div class="ap-card-head">
        <h5 class="ap-card-title">2. Performa</h5>
        <span class="text-muted small">Skala 1.00 – 4.00</span>
    </div>

    <div class="ap-score-bar" role="status" aria-live="polite">
        <span class="ap-score-label">Skor akhir (pratinjau)</span>
        <span class="ap-score-group">
            <span class="ap-score-num" id="badge_total_overall_score"><?= number_format($initialOverallScore, 2) ?></span>
            <span class="ap-rating r-good" id="badge_overall_rating">-</span>
        </span>
    </div>

    <div class="table-responsive">
        <table class="ap-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Kompetensi</th>
                    <th style="width: 25%;">Definisi</th>
                    <th style="width: 30%;">Key actions (indikator perilaku)</th>
                    <th style="width: 25%;">Skor</th>
                </tr>
            </thead>

            <?php if (!empty($allScoreItems)): ?>
                <?php foreach ($allScoreItems as $cIdx => $item): ?>
                    <?php
                        $itemId      = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id']);
                        $cfg         = is_string($item['config'] ?? null) ? json_decode($item['config'], true) : ($item['config'] ?? []);
                        $compTitle   = !empty($cfg['competency']) ? $cfg['competency'] : (!empty($item['title']) ? $item['title'] : ('Kompetensi ' . ($cIdx + 1)));
                        $definition  = !empty($item['description']) ? $item['description'] : (!empty($cfg['definition']) ? $cfg['definition'] : '-');
                        $actions     = $parseActions($cfg['key_actions'] ?? null);
                        $itemWeight  = (float) ($item['weight'] ?? 1.0);

                        $resp              = $responses_by_item_id[$itemId] ?? null;
                        $activePayload     = [];
                        if (($user_mode ?? '') === 'GM' && !empty($resp->gm_response)) {
                            $activePayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                        } elseif ($resp && !empty($resp->evaluator_response)) {
                            $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                        }

                        $savedActionScores = $activePayload['action_scores'] ?? [];
                        $savedItemScore    = null;
                        if (($user_mode ?? '') === 'GM' && isset($activePayload['score']) && $activePayload['score'] !== '') {
                            $savedItemScore = (float) $activePayload['score'];
                        } elseif ($resp && $resp->score !== null) {
                            $savedItemScore = (float) $resp->score;
                        } elseif (isset($activePayload['score']) && $activePayload['score'] !== '') {
                            $savedItemScore = (float) $activePayload['score'];
                        }

                        $hasKeyActions = !empty($actions);
                        $totalActions  = $hasKeyActions ? count($actions) : 1;
                        $actionSum     = 0;
                    ?>

                    <tbody class="competency-block" id="competency_block_<?= $itemId ?>" data-item-id="<?= $itemId ?>" data-weight="<?= $itemWeight ?>">
                        <?php if ($hasKeyActions): ?>
                            <?php foreach ($actions as $aIdx => $actionText): ?>
                                <?php
                                    $defaultScore = 3.00;
                                    if (isset($savedActionScores[$aIdx]) && $savedActionScores[$aIdx] !== '') {
                                        $actionScore = (float) $savedActionScores[$aIdx];
                                    } elseif (isset($savedActionScores[(string) $aIdx]) && $savedActionScores[(string) $aIdx] !== '') {
                                        $actionScore = (float) $savedActionScores[(string) $aIdx];
                                    } elseif ($savedItemScore !== null) {
                                        $actionScore = $savedItemScore;
                                    } else {
                                        $actionScore = $defaultScore;
                                    }
                                    // Snap to 0.05 step between 1.00 and 4.00
                                    $actionScore = round($actionScore / 0.05) * 0.05;
                                    $actionScore = max(1.00, min(4.00, $actionScore));
                                    $actionSum += $actionScore;

                                    $letter = chr(97 + $aIdx);
                                    $actionLabel = $actionText;
                                    $hasLetterPrefix = preg_match('/^[a-z0-9][\.\)]\s*/i', $actionText);
                                    $isLastAction = ($aIdx === $totalActions - 1);
                                ?>
                                <tr class="<?= $isLastAction ? '' : '' ?>">
                                    <?php if ($aIdx === 0): ?>
                                        <td rowspan="<?= $totalActions ?>" class="ap-comp-cell">
                                            <div class="ap-comp-name">
                                                <span class="ap-comp-index"><?= ($cIdx + 1) ?></span><?= esc($compTitle) ?>
                                            </div>
                                        </td>
                                        <td rowspan="<?= $totalActions ?>" class="ap-comp-cell">
                                            <div class="ap-def"><?= esc($definition) ?></div>
                                        </td>
                                    <?php endif; ?>

                                    <td>
                                        <div class="ap-action-text">
                                            <?php if (!$hasLetterPrefix): ?>
                                                <span class="ap-action-letter"><?= $letter ?>.</span>
                                            <?php endif; ?>
                                            <?= esc($actionText) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="ap-slider-wrap">
                                            <div class="ap-slider-col">
                                                <input type="range"
                                                    class="ap-range action-slider"
                                                    min="1.0" max="4.0" step="0.05"
                                                    value="<?= number_format($actionScore, 2, '.', '') ?>"
                                                    data-item-id="<?= $itemId ?>"
                                                    data-action-idx="<?= $aIdx ?>"
                                                    aria-label="Skor <?= esc($actionText) ?>"
                                                    <?= !$canEditScores ? 'disabled' : '' ?>>
                                                <input type="hidden"
                                                    class="action-score-hidden"
                                                    name="action_scores[<?= $itemId ?>][<?= $aIdx ?>]"
                                                    id="action_score_hidden_<?= $itemId ?>_<?= $aIdx ?>"
                                                    value="<?= number_format($actionScore, 2, '.', '') ?>">
                                                <div class="ap-ticks" aria-hidden="true">
                                                    <span>1</span><span>2</span><span>3</span><span>4</span>
                                                </div>
                                            </div>
                                            <div class="ap-val" id="badge_val_<?= $itemId ?>_<?= $aIdx ?>">
                                                <?= number_format($actionScore, 2) ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php
                                $actionScore = ($savedItemScore !== null) ? $savedItemScore : 3.00;
                                $actionScore = round($actionScore / 0.05) * 0.05;
                                $actionScore = max(1.00, min(4.00, $actionScore));
                                $actionSum   = $actionScore;
                            ?>
                            <tr>
                                <td class="ap-comp-cell">
                                    <div class="ap-comp-name">
                                        <span class="ap-comp-index"><?= ($cIdx + 1) ?></span><?= esc($compTitle) ?>
                                    </div>
                                </td>
                                <td class="ap-comp-cell">
                                    <div class="ap-def"><?= esc($definition) ?></div>
                                </td>
                                <td>
                                    <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                </td>
                                <td>
                                    <div class="ap-slider-wrap">
                                        <div class="ap-slider-col">
                                            <input type="range"
                                                class="ap-range action-slider"
                                                min="1.0" max="4.0" step="0.05"
                                                value="<?= number_format($actionScore, 2, '.', '') ?>"
                                                data-item-id="<?= $itemId ?>"
                                                data-action-idx="0"
                                                aria-label="Skor <?= esc($compTitle) ?>"
                                                <?= !$canEditScores ? 'disabled' : '' ?>>
                                            <input type="hidden"
                                                class="action-score-hidden"
                                                name="action_scores[<?= $itemId ?>][0]"
                                                id="action_score_hidden_<?= $itemId ?>_0"
                                                value="<?= number_format($actionScore, 2, '.', '') ?>">
                                            <div class="ap-ticks" aria-hidden="true">
                                                <span>1</span><span>2</span><span>3</span><span>4</span>
                                            </div>
                                        </div>
                                        <div class="ap-val" id="badge_val_<?= $itemId ?>_0">
                                            <?= number_format($actionScore, 2) ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php
                            $compAvg = $totalActions > 0 ? ($actionSum / $totalActions) : 3.00;
                            if ($savedItemScore !== null && !$hasKeyActions) {
                                $compAvg = $savedItemScore;
                            }
                        ?>

                        <tr class="ap-subtotal">
                            <td colspan="3" class="ap-subtotal-label">Rata-rata <?= esc($compTitle) ?></td>
                            <td class="text-center">
                                <span class="ap-avg-chip">
                                    Avg
                                    <span class="ap-avg-num" id="avg_display_<?= $itemId ?>"><?= number_format($compAvg, 2) ?></span>
                                </span>
                                <input type="hidden" id="item_score_<?= $itemId ?>" value="<?= number_format($compAvg, 2) ?>">
                            </td>
                        </tr>
                    </tbody>
                <?php endforeach; ?>
            <?php else: ?>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
                            Tidak ada butir indikator performa dalam template penilaian ini.
                        </td>
                    </tr>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
</div>