<?php
    helper('appraisal_view');

    $displayMode = $display_mode ?? 'edit';
    $userMode    = $user_mode ?? '';

    if ($displayMode === 'compare') {
        $scoreSections = $scoreSections ?? [];
    } else {
        $sections = $template_snapshot['sections'] ?? [];
        $items    = $template_snapshot['items'] ?? [];

        $itemsBySection = [];
        foreach ($items as $item) {
            $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
            $itemsBySection[$secId][] = $item;
        }

        $scoreSections = [];
        foreach ($sections as $sec) {
            if (strtoupper($sec['section_type'] ?? 'SCORE') === 'SCORE') {
                $scoreSections[] = $sec;
            }
        }

        if (empty($scoreSections) && ! empty($sections)) {
            foreach ($sections as $sec) {
                $secId    = (int) $sec['md_appraisal_section_id'];
                $secItems = $itemsBySection[$secId] ?? [];
                $hasScore = false;
                $hasBool  = false;
                foreach ($secItems as $si) {
                    $it = strtoupper($si['input_type'] ?? '');
                    if ($it === 'SCORE') $hasScore = true;
                    if ($it === 'BOOLEAN') $hasBool = true;
                }
                if (! ($hasBool && ! $hasScore)) {
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

        $canEditScores       = ($displayMode === 'edit') && in_array($userMode, ['EVALUATOR', 'GM', 'HR'], true);
        $initialOverallScore = $appraisal->final_score !== null ? (float) $appraisal->final_score : null;
        $initialRating       = appraisal_rating_meta($initialOverallScore, false);

        if ($displayMode === 'readonly') {
            $itemsNilai4 = $itemsNilai4 ?? [];
            $itemsNilai1 = $itemsNilai1 ?? [];
        }
    }
?>

<!-- II. PERFORMA -->
<div class="ap-card<?= $displayMode === 'compare' ? ' mb-4' : '' ?>">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center" style="gap: 12px;">
            <h5 class="ap-card-title mb-0">II. Performa</h5>
            <span class="ap-category-badge">Skala 0.00 – 4.00</span>
        </div>
        <?php if ($displayMode === 'edit'): ?>
            <span class="text-muted font-weight-bold" style="font-size: 13px;">*Geser slider atau sesuaikan nilai target</span>
        <?php endif; ?>
    </div>

    <?php if ($displayMode === 'compare'): ?>
        <div class="ap-score-bar" role="status" aria-live="polite">
            <div class="d-flex align-items-center flex-wrap" style="gap: 16px; font-size: 13.5px;">
                <span class="ap-score-label font-weight-bold">Skor Akhir:</span>
                <div class="d-inline-flex align-items-center" style="gap: 8px;">
                    <span class="text-muted font-weight-bold">Penilai =</span>
                    <span class="ap-score-num"><?= number_format((float) $eval_overall_score, 2) ?></span>
                    <span class="ap-rating <?= esc($eval_rating['class']) ?>"><?= esc($eval_rating['label']) ?></span>
                </div>
                <span class="text-muted font-weight-bold">|</span>
                <div class="d-inline-flex align-items-center" style="gap: 8px;">
                    <span class="text-muted font-weight-bold">Peninjau =</span>
                    <?php if (! empty($is_gm_reviewed) && $gm_overall_score !== null): ?>
                        <?php
                            $gmBarColor = '';
                            if ($delta > 0) {
                                $gmBarColor = 'color: #059669; border-color: #a7f3d0; background: #ecfdf5; font-weight: 700;';
                            } elseif ($delta < 0) {
                                $gmBarColor = 'color: #dc2626; border-color: #fecaca; background: #fef2f2; font-weight: 700;';
                            }
                        ?>
                        <span class="ap-score-num" style="<?= $gmBarColor ?>">
                            <?= number_format((float) $gm_overall_score, 2) ?>
                        </span>
                        <span class="ap-rating <?= esc($gm_rating['class']) ?>"><?= esc($gm_rating['label']) ?></span>
                        <?php if ($delta !== null && round($delta, 2) != 0.0): ?>
                            <span class="font-weight-bold ml-1" style="color: <?= $delta > 0 ? '#059669' : '#dc2626' ?>; font-size: 13px;">
                                (<?= ($delta > 0 ? '+' : '') . number_format($delta, 2) ?>)
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="ap-score-num text-muted">-</span>
                        <span class="text-muted small">Menunggu Peninjau</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($displayMode === 'edit'): ?>
        <div class="ap-score-bar" role="status" aria-live="polite">
            <span class="ap-score-label">Skor Akhir (Pratinjau)</span>
            <span class="ap-score-group">
                <span class="ap-score-num" id="badge_total_overall_score"><?= $initialOverallScore !== null ? number_format($initialOverallScore, 2) : '—' ?></span>
                <span class="ap-rating <?= esc($initialRating['class']) ?>" id="badge_overall_rating"><?= esc($initialRating['label']) ?></span>
            </span>
        </div>
    <?php endif; ?>

    <div class="table-responsive<?= $displayMode !== 'compare' ? ' ap-table-wrapper' : '' ?>">
        <?php if ($displayMode === 'compare'): ?>
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">NO</th>
                        <th style="width: 20%;">KOMPETENSI</th>
                        <th style="width: 45%;">KEY ACTIONS (INDIKATOR PERILAKU)</th>
                        <th style="width: 15%; text-align: center;">SKOR PENILAI</th>
                        <th style="width: 15%; text-align: center;">SKOR PENINJAU</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($scoreSections)): ?>
                        <?php $scoreItemNumber = 0; ?>
                        <?php foreach ($scoreSections as $sec): ?>
                            <?php foreach ($sec['items'] as $item): ?>
                                <?php
                                    $scoreItemNumber++;
                                    $actions      = $item['actions'] ?? [];
                                    $hasActions   = ! empty($actions);
                                    $actionCount  = count($actions);
                                    $itemTitle    = esc($item['title'] ?? ('Kompetensi ' . $scoreItemNumber));
                                    $itemDef      = esc($item['definition'] ?? '-');
                                    $evalSubtotal = $item['eval_avg'] ?? 3.00;
                                    $gmSubtotal   = $item['gm_avg'] ?? null;
                                    $totalRowSpan = $hasActions ? $actionCount : 1;
                                ?>
                                <?php if ($hasActions): ?>
                                    <?php foreach ($actions as $aIdx => $act): ?>
                                        <?php
                                            $gmScore   = $act['gm_score'];
                                            $evalScore = $act['eval_score'];
                                            $gmStyle   = 'color: #334155; font-weight: 700;';
                                            if ($gmScore !== null) {
                                                if (round($gmScore, 2) > round($evalScore, 2)) {
                                                    $gmStyle = 'color: #059669; font-weight: 800; background: #ecfdf5; padding: 2px 8px; border-radius: 4px;';
                                                } elseif (round($gmScore, 2) < round($evalScore, 2)) {
                                                    $gmStyle = 'color: #dc2626; font-weight: 800; background: #fef2f2; padding: 2px 8px; border-radius: 4px;';
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <?php if ($aIdx === 0): ?>
                                                <td rowspan="<?= $totalRowSpan ?>" class="text-center font-weight-bold align-top ap-comp-cell" style="width: 50px;">
                                                    <?= $scoreItemNumber ?>.
                                                </td>
                                                <td rowspan="<?= $totalRowSpan ?>" class="ap-comp-cell">
                                                    <div class="ap-comp-name mb-1"><?= $itemTitle ?></div>
                                                    <?php if ($itemDef !== '-' && ! empty($itemDef)): ?>
                                                        <div class="ap-def"><?= $itemDef ?></div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="ap-action-text">
                                                    <span class="ap-action-letter"><?= esc($act['letter']) ?>.</span>
                                                    <?= esc($act['text']) ?>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle font-weight-bold">
                                                <?= number_format((float) $evalScore, 2) ?>
                                            </td>
                                            <td class="text-center align-middle font-weight-bold">
                                                <?php if ($gmScore !== null): ?>
                                                    <span style="<?= $gmStyle ?>"><?= number_format((float) $gmScore, 2) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted font-weight-normal">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td class="text-center font-weight-bold align-top ap-comp-cell" style="width: 50px;"><?= $scoreItemNumber ?>.</td>
                                        <td class="ap-comp-cell">
                                            <div class="ap-comp-name mb-1"><?= $itemTitle ?></div>
                                            <?php if ($itemDef !== '-' && ! empty($itemDef)): ?>
                                                <div class="ap-def"><?= $itemDef ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><div class="ap-action-text text-muted font-italic">Indikator umum</div></td>
                                        <td class="text-center align-middle font-weight-bold"><?= number_format((float) $evalSubtotal, 2) ?></td>
                                        <td class="text-center align-middle font-weight-bold">
                                            <?php if ($gmSubtotal !== null): ?>
                                                <?php
                                                    $singleColor = '';
                                                    if (round($gmSubtotal, 2) > round($evalSubtotal, 2)) {
                                                        $singleColor = 'color: #059669; font-weight: 800; background: #ecfdf5; padding: 2px 8px; border-radius: 4px;';
                                                    } elseif (round($gmSubtotal, 2) < round($evalSubtotal, 2)) {
                                                        $singleColor = 'color: #dc2626; font-weight: 800; background: #fef2f2; padding: 2px 8px; border-radius: 4px;';
                                                    }
                                                ?>
                                                <span style="<?= $singleColor ?>"><?= number_format((float) $gmSubtotal, 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted font-weight-normal">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr class="ap-subtotal">
                                    <td colspan="3" class="ap-subtotal-label font-weight-bold text-dark">Rata-rata <?= $itemTitle ?></td>
                                    <td class="text-center">
                                        <span class="ap-avg-chip font-weight-bold"><?= number_format((float) $evalSubtotal, 2) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($gmSubtotal !== null): ?>
                                            <?php
                                                $chipStyle = '';
                                                $numStyle  = 'color: var(--ap-ink); font-weight: 800;';
                                                if (round($gmSubtotal, 2) > round($evalSubtotal, 2)) {
                                                    $chipStyle = 'background: #ecfdf5; border-color: #a7f3d0;';
                                                    $numStyle  = 'color: #059669; font-weight: 800;';
                                                } elseif (round($gmSubtotal, 2) < round($evalSubtotal, 2)) {
                                                    $chipStyle = 'background: #fef2f2; border-color: #fecaca;';
                                                    $numStyle  = 'color: #dc2626; font-weight: 800;';
                                                }
                                            ?>
                                            <span class="ap-avg-chip" style="<?= $chipStyle ?>">
                                                <span class="ap-avg-num" style="<?= $numStyle ?>"><?= number_format((float) $gmSubtotal, 2) ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted font-weight-normal">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Tidak ada butir indikator performa dalam template penilaian ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Kompetensi</th>
                        <th style="width: 25%;">Definisi</th>
                        <th style="width: 30%;">Key Actions (Indikator Perilaku)</th>
                        <th style="width: 25%;<?= $displayMode === 'readonly' ? ' text-align: center;' : '' ?>">Skor Penilaian</th>
                    </tr>
                </thead>

                <?php if (! empty($allScoreItems)): ?>
                    <?php foreach ($allScoreItems as $cIdx => $item): ?>
                        <?php
                            $itemId      = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id']);
                            $cfg         = (array) ($item['config'] ?? []);
                            $compTitle   = ! empty($cfg['competency']) ? $cfg['competency'] : (! empty($item['title']) ? $item['title'] : ('Kompetensi ' . ($cIdx + 1)));
                            $definition  = ! empty($item['description']) ? $item['description'] : (! empty($cfg['definition']) ? $cfg['definition'] : '-');
                            $actions     = appraisal_parse_actions($cfg['key_actions'] ?? null);
                            $itemWeight  = (float) ($item['weight'] ?? 1.0);

                            $resp          = $responses_by_item_id[$itemId] ?? null;
                            $activePayload = [];
                            if ($displayMode === 'readonly') {
                                if ($resp && ! empty($resp->gm_response)) {
                                    $activePayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                                } elseif ($resp && ! empty($resp->evaluator_response)) {
                                    $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                }
                            } else {
                                if ($userMode === 'GM' && ! empty($resp->gm_response)) {
                                    $activePayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                                } elseif ($resp && ! empty($resp->evaluator_response)) {
                                    $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                }
                            }

                            $savedActionScores = $activePayload['action_scores'] ?? [];
                            $savedItemScore    = null;
                            if (isset($activePayload['score']) && $activePayload['score'] !== '') {
                                $savedItemScore = (float) $activePayload['score'];
                            } elseif ($resp && $resp->score !== null) {
                                $savedItemScore = (float) $resp->score;
                            }

                            $hasKeyActions = ! empty($actions);
                            $totalActions  = $hasKeyActions ? count($actions) : 1;
                            $actionSum     = 0;
                        ?>
                        <tbody class="competency-block" id="competency_block_<?= $itemId ?>" data-item-id="<?= $itemId ?>" data-weight="<?= $itemWeight ?>">
                            <?php if ($hasKeyActions): ?>
                                <?php foreach ($actions as $aIdx => $actionText): ?>
                                    <?php
                                        if (isset($savedActionScores[$aIdx]) && $savedActionScores[$aIdx] !== '') {
                                            $actionScore = (float) $savedActionScores[$aIdx];
                                        } elseif (isset($savedActionScores[(string) $aIdx]) && $savedActionScores[(string) $aIdx] !== '') {
                                            $actionScore = (float) $savedActionScores[(string) $aIdx];
                                        } elseif ($savedItemScore !== null) {
                                            $actionScore = $savedItemScore;
                                        } else {
                                            $actionScore = 3.00;
                                        }
                                        $actionScore = round($actionScore / 0.05) * 0.05;
                                        $actionScore = max(1.00, min(4.00, $actionScore));
                                        $actionSum  += $actionScore;

                                        if ($displayMode === 'readonly') {
                                            $cleanText = preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($actionText));
                                            if ($actionScore >= 3.99) {
                                                $itemsNilai4[] = $cleanText;
                                            } elseif ($actionScore <= 1.01) {
                                                $itemsNilai1[] = $cleanText;
                                            }
                                        }

                                        $letter          = chr(97 + $aIdx);
                                        $hasLetterPrefix = preg_match('/^[a-z0-9][\.\)]\s*/i', $actionText);
                                    ?>
                                    <tr>
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
                                                <?php if (! $hasLetterPrefix): ?>
                                                    <span class="ap-action-letter"><?= $letter ?>.</span>
                                                <?php endif; ?>
                                                <?= esc($actionText) ?>
                                            </div>
                                        </td>

                                        <?php if ($displayMode === 'readonly'): ?>
                                            <td class="text-center align-middle">
                                                <span class="ap-avg-chip font-weight-bold"><?= number_format($actionScore, 2) ?></span>
                                            </td>
                                        <?php else: ?>
                                            <td>
                                                <div class="ap-slider-wrap">
                                                    <div class="ap-slider-col">
                                                        <?php
                                                            $pct          = round(($actionScore / 4.0) * 100, 1);
                                                            $roundedScore = round($actionScore);
                                                        ?>
                                                        <input type="range"
                                                            class="ap-range action-slider"
                                                            min="0.0" max="4.0" step="0.25"
                                                            value="<?= number_format($actionScore, 2, '.', '') ?>"
                                                            data-item-id="<?= $itemId ?>"
                                                            data-action-idx="<?= $aIdx ?>"
                                                            style="--fill: <?= $pct ?>%;"
                                                            aria-label="Skor <?= esc($actionText) ?>"
                                                            <?= ! $canEditScores ? 'disabled' : '' ?>>
                                                        <input type="hidden"
                                                            class="action-score-hidden"
                                                            name="action_scores[<?= $itemId ?>][<?= $aIdx ?>]"
                                                            id="action_score_hidden_<?= $itemId ?>_<?= $aIdx ?>"
                                                            value="<?= number_format($actionScore, 2, '.', '') ?>">
                                                        <div class="ap-ticks" aria-hidden="true">
                                                            <?php for ($t = 0; $t <= 4; $t++): ?>
                                                                <span data-val="<?= $t ?>" class="<?= $roundedScore == $t ? 'is-active' : '' ?>"><?= $t ?></span>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    <div class="ap-val-box ml-2">
                                                        <input type="number"
                                                            class="form-control form-control-sm action-score-input has-score"
                                                            id="badge_val_<?= $itemId ?>_<?= $aIdx ?>"
                                                            min="0.00" max="4.00" step="0.25"
                                                            value="<?= number_format($actionScore, 2, '.', '') ?>"
                                                            data-item-id="<?= $itemId ?>"
                                                            data-action-idx="<?= $aIdx ?>"
                                                            aria-label="Nilai angka <?= esc($actionText) ?>"
                                                            <?= ! $canEditScores ? 'readonly disabled' : '' ?>>
                                                    </div>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php
                                    $actionScore = ($savedItemScore !== null) ? $savedItemScore : 3.00;
                                    $actionScore = round($actionScore / 0.05) * 0.05;
                                    $actionScore = max(1.00, min(4.00, $actionScore));
                                    $actionSum   = $actionScore;

                                    if ($displayMode === 'readonly') {
                                        $cleanTitle = preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($compTitle));
                                        if ($actionScore >= 3.99) {
                                            $itemsNilai4[] = $cleanTitle;
                                        } elseif ($actionScore <= 1.01) {
                                            $itemsNilai1[] = $cleanTitle;
                                        }
                                    }
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
                                    <?php if ($displayMode === 'readonly'): ?>
                                        <td class="text-center align-middle">
                                            <span class="ap-avg-chip font-weight-bold"><?= number_format($actionScore, 2) ?></span>
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            <div class="ap-slider-wrap">
                                                <div class="ap-slider-col">
                                                    <?php
                                                        $pct0          = round(($actionScore / 4.0) * 100, 1);
                                                        $roundedScore0 = round($actionScore);
                                                    ?>
                                                    <input type="range"
                                                        class="ap-range action-slider"
                                                        min="0.0" max="4.0" step="0.25"
                                                        value="<?= number_format($actionScore, 2, '.', '') ?>"
                                                        data-item-id="<?= $itemId ?>"
                                                        data-action-idx="0"
                                                        style="--fill: <?= $pct0 ?>%;"
                                                        aria-label="Skor <?= esc($compTitle) ?>"
                                                        <?= ! $canEditScores ? 'disabled' : '' ?>>
                                                    <input type="hidden"
                                                        class="action-score-hidden"
                                                        name="action_scores[<?= $itemId ?>][0]"
                                                        id="action_score_hidden_<?= $itemId ?>_0"
                                                        value="<?= number_format($actionScore, 2, '.', '') ?>">
                                                    <div class="ap-ticks" aria-hidden="true">
                                                        <?php for ($t = 0; $t <= 4; $t++): ?>
                                                            <span data-val="<?= $t ?>" class="<?= $roundedScore0 == $t ? 'is-active' : '' ?>"><?= $t ?></span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div class="ap-val-box ml-2">
                                                    <input type="number"
                                                        class="form-control form-control-sm action-score-input has-score"
                                                        id="badge_val_<?= $itemId ?>_0"
                                                        min="0.00" max="4.00" step="0.25"
                                                        value="<?= number_format($actionScore, 2, '.', '') ?>"
                                                        data-item-id="<?= $itemId ?>"
                                                        data-action-idx="0"
                                                        aria-label="Nilai angka <?= esc($compTitle) ?>"
                                                        <?= ! $canEditScores ? 'readonly disabled' : '' ?>>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endif; ?>

                            <?php
                                $compAvg = $totalActions > 0 ? ($actionSum / $totalActions) : 3.00;
                                if ($savedItemScore !== null && ! $hasKeyActions) {
                                    $compAvg = $savedItemScore;
                                }
                            ?>
                            <tr class="ap-subtotal">
                                <td colspan="3" class="ap-subtotal-label">Rata-rata <?= esc($compTitle) ?></td>
                                <td class="<?= $displayMode === 'readonly' ? 'text-center' : '' ?>">
                                    <span class="ap-avg-chip">
                                        Rata-rata
                                        <span class="ap-avg-num" id="avg_display_<?= $itemId ?>"><?= number_format($compAvg, 2) ?></span>
                                    </span>
                                    <?php if ($displayMode === 'edit'): ?>
                                        <input type="hidden" id="item_score_<?= $itemId ?>" value="<?= number_format($compAvg, 2) ?>">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-2x mb-2 d-block" aria-hidden="true"></i>
                                Tidak ada butir indikator performa dalam template penilaian ini.
                            </td>
                        </tr>
                    </tbody>
                <?php endif; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
