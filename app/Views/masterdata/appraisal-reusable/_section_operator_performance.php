<?php
    helper('appraisal_view');

    $displayMode = $display_mode ?? 'edit';
    $userMode    = $user_mode ?? '';

    if ($displayMode === 'compare') {
        $isReviewed    = ! empty($is_reviewer_reviewed);
        $isHr          = ! empty($is_hr_intervened);
        $weightedItems = $weighted_items ?? [];
        $totBobot      = 0.0;
        $totEvalSkor   = 0.0;
        $totEvalNilai  = 0.0;
        $totGmSkor     = 0.0;
        $totGmNilai    = 0.0;
        $totHrSkor     = 0.0;
        $totHrNilai    = 0.0;

        foreach ($weightedItems as $w) {
            $b  = (float) ($w['bobot'] ?? 1.0);
            $es = (float) ($w['eval_score'] ?? 0.0);
            $en = (float) ($w['eval_nilai'] ?? ($b * $es));
            $totBobot     += $b;
            $totEvalSkor  += $es;
            $totEvalNilai += $en;

            if ($isReviewed && isset($w['reviewer_score']) && $w['reviewer_score'] !== null) {
                $gs = (float) $w['reviewer_score'];
                $gn = (float) ($w['reviewer_nilai'] ?? ($b * $gs));
                $totGmSkor  += $gs;
                $totGmNilai += $gn;
            }

            if ($isHr && isset($w['hr_score']) && $w['hr_score'] !== null) {
                $hs = (float) $w['hr_score'];
                $hn = (float) ($w['hr_nilai'] ?? ($b * $hs));
                $totHrSkor  += $hs;
                $totHrNilai += $hn;
            }
        }
    } else {
        $sections = $template_snapshot['sections'] ?? [];
        $items    = $template_snapshot['items'] ?? [];

        $itemsBySection = [];
        foreach ($items as $item) {
            $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
            $itemsBySection[$secId][] = $item;
        }

        $weightedSections = [];
        foreach ($sections as $sec) {
            if (strtoupper($sec['section_type'] ?? '') === 'WEIGHTED_CHECKLIST') {
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

        if (empty($weightedItems) && ! empty($items)) {
            foreach ($items as $it) {
                if (strtoupper($it['input_type'] ?? '') === 'WEIGHTED_CHECKLIST') {
                    $weightedItems[] = $it;
                }
            }
        }

        $canEditScores       = ($displayMode === 'edit') && (in_array($userMode, ['EVALUATOR', 'REVIEWER', 'HR'], true) || ! empty($is_preview));
        $opSummary           = appraisal_extract_complete_summary($template_snapshot ?? [], $responses_by_item_id ?? [], true);
        $hasInitialScore     = ((float) ($opSummary['totNilai'] ?? 0)) > 0;
        $initialOverallScore = $hasInitialScore ? (float) $opSummary['totNilai'] : null;
        $initialDisplayScore = $hasInitialScore ? ($opSummary['displaySkorAkhir'] ?? '0') : '—';
        $initialRating       = appraisal_rating_meta($initialOverallScore, true);

        if ($displayMode === 'readonly') {
            $totBobot = 0.0;
            $totSkor  = 0.0;
            $totNilai = 0.0;
        }
    }
?>

<div class="ap-card mb-4">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center" style="gap: 12px;">
            <h5 class="ap-card-title mb-0">II. Penilaian Kinerja</h5>
            <span class="ap-category-badge">Sangat Baik / Baik / Kurang</span>
        </div>
        <?php if ($displayMode === 'edit'): ?>
            <span class="text-muted font-weight-bold" style="font-size: 13px;">*Beri tanda centang pada kolom yang ingin diisi</span>
        <?php endif; ?>
    </div>

    <?php if ($displayMode === 'edit'): ?>
        <div class="ap-score-bar" role="status" aria-live="polite">
            <span class="ap-score-label">Skor Akhir (Pratinjau)</span>
            <span class="ap-score-group">
                <span class="ap-score-num" id="badge_total_overall_score"><?= esc($initialDisplayScore) ?></span>
                <span class="ap-rating <?= esc($initialRating['class']) ?>" id="badge_overall_rating"><?= esc($initialRating['label']) ?></span>
            </span>
        </div>
    <?php endif; ?>

    <?php if (in_array($displayMode, ['edit', 'readonly'], true)): ?>
        <div class="ap-guide">
            <p class="ap-guide-title">Ringkasan Kategori Penilaian Operator (1–3)</p>
            <ul class="ap-guide-list">
                <li><strong>Nilai 1 (Kurang):</strong> Memerlukan perbaikan signifikan untuk memenuhi standar kinerja yang ditetapkan.</li>
                <li><strong>Nilai 2 (Baik):</strong> Secara konsisten memenuhi standar kinerja yang ditetapkan secara memadai.</li>
                <li><strong>Nilai 3 (Sangat Baik):</strong> Sering melampaui standar kinerja yang ditetapkan.</li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="table-responsive<?= $displayMode !== 'compare' ? ' ap-table-wrapper' : '' ?>">
        <?php if ($displayMode === 'compare'): ?>
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 50px; text-align: center; vertical-align: middle;">No</th>
                        <th rowspan="2" style="width: 30%; vertical-align: middle;">Aspek Penilaian</th>
                        <th rowspan="2" style="width: 10%; text-align: center; vertical-align: middle;">Bobot</th>
                        <th colspan="2" style="text-align: center; background: #eff6ff; border-left: 1px solid #bfdbfe;">PENILAI</th>
                        <th colspan="2" style="text-align: center; background: #f0fdf4; border-left: 1px solid #bbf7d0;">REVIEWER</th>
                        <th colspan="2" style="text-align: center; background: #fffbeb; border-left: 1px solid #fde68a;">HR</th>
                    </tr>
                    <tr>
                        <th style="width: 10%; text-align: center; background: #eff6ff; border-left: 1px solid #bfdbfe;">Skor</th>
                        <th style="width: 10%; text-align: center; background: #eff6ff;">Nilai</th>
                        <th style="width: 10%; text-align: center; background: #f0fdf4; border-left: 1px solid #bbf7d0;">Skor</th>
                        <th style="width: 10%; text-align: center; background: #f0fdf4;">Nilai</th>
                        <th style="width: 10%; text-align: center; background: #fffbeb; border-left: 1px solid #fde68a;">Skor</th>
                        <th style="width: 10%; text-align: center; background: #fffbeb;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($weightedItems)): ?>
                        <?php foreach ($weightedItems as $idx => $row): ?>
                            <?php
                                $b  = (float) ($row['bobot'] ?? 1.0);
                                $es = isset($row['eval_score']) && $row['eval_score'] !== null ? (float) $row['eval_score'] : null;
                                $en = isset($row['eval_nilai']) && $row['eval_nilai'] !== null ? (float) $row['eval_nilai'] : ($es !== null ? ($b * $es) : null);
                                $gs = isset($row['reviewer_score']) && $row['reviewer_score'] !== null ? (float) $row['reviewer_score'] : null;
                                $gn = isset($row['reviewer_nilai']) && $row['reviewer_nilai'] !== null ? (float) $row['reviewer_nilai'] : ($gs !== null ? ($b * $gs) : null);
                                $hs = isset($row['hr_score']) && $row['hr_score'] !== null ? (float) $row['hr_score'] : null;
                                $hn = isset($row['hr_nilai']) && $row['hr_nilai'] !== null ? (float) $row['hr_nilai'] : ($hs !== null ? ($b * $hs) : null);
                            ?>
                            <tr>
                                <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);"><?= $idx + 1 ?></td>
                                <td class="align-middle font-weight-600 text-dark"><?= esc($row['title'] ?? ('Aspek ' . ($idx + 1))) ?></td>
                                <td class="text-center align-middle font-weight-bold text-dark"><?= round($b) == $b ? (int) $b : number_format($b, 1) ?></td>
                                <td class="text-center align-middle font-weight-bold" style="background: #f8fafc; border-left: 1px solid var(--ap-line-soft);">
                                    <?= $es !== null ? (round($es) == $es ? (int) $es : number_format($es, 1)) : '-' ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-primary" style="background: #f8fafc;">
                                    <?= $en !== null ? (round($en) == $en ? (int) $en : number_format($en, 1)) : '-' ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold" style="background: #fdfefe; border-left: 1px solid var(--ap-line-soft);">
                                    <?php if ($isReviewed && $gs !== null): ?>
                                        <?= round($gs) == $gs ? (int) $gs : number_format($gs, 1) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-success" style="background: #fdfefe;">
                                    <?php if ($isReviewed && $gn !== null): ?>
                                        <?= round($gn) == $gn ? (int) $gn : number_format($gn, 1) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold" style="background: #fffdf5; border-left: 1px solid var(--ap-line-soft);">
                                    <?php if ($isHr && $hs !== null): ?>
                                        <?= round($hs) == $hs ? (int) $hs : number_format($hs, 1) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-warning" style="background: #fffdf5;">
                                    <?php if ($isHr && $hn !== null): ?>
                                        <?= round($hn) == $hn ? (int) $hn : number_format($hn, 1) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                            <td colspan="2" class="text-center align-middle text-uppercase" style="letter-spacing: 0.05em; font-size: 13px;">TOTAL</td>
                            <td class="text-center align-middle"><?= round($totBobot) == $totBobot ? (int) $totBobot : number_format($totBobot, 1) ?></td>
                            <td class="text-center align-middle" style="border-left: 1px solid #cbd5e1;"><?= round($totEvalSkor) == $totEvalSkor ? (int) $totEvalSkor : number_format($totEvalSkor, 1) ?></td>
                            <td class="text-center align-middle text-primary"><?= round($totEvalNilai) == $totEvalNilai ? (int) $totEvalNilai : number_format($totEvalNilai, 1) ?></td>
                            <td class="text-center align-middle" style="border-left: 1px solid #cbd5e1;"><?= $isReviewed ? (round($totGmSkor) == $totGmSkor ? (int) $totGmSkor : number_format($totGmSkor, 1)) : '-' ?></td>
                            <td class="text-center align-middle text-success"><?= $isReviewed ? (round($totGmNilai) == $totGmNilai ? (int) $totGmNilai : number_format($totGmNilai, 1)) : '-' ?></td>
                            <td class="text-center align-middle" style="border-left: 1px solid #cbd5e1;"><?= $isHr ? (round($totHrSkor) == $totHrSkor ? (int) $totHrSkor : number_format($totHrSkor, 1)) : '-' ?></td>
                            <td class="text-center align-middle text-warning"><?= $isHr ? (round($totHrNilai) == $totHrNilai ? (int) $totHrNilai : number_format($totHrNilai, 1)) : '-' ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data aspek penilaian.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php elseif ($displayMode === 'readonly'): ?>
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 50%;">Aspek Penilaian</th>
                        <th style="width: 15%; text-align: center;">Bobot</th>
                        <th style="width: 15%; text-align: center;">Skor</th>
                        <th style="width: 15%; text-align: center;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($weightedItems)): ?>
                        <?php foreach ($weightedItems as $idx => $item): ?>
                            <?php
                                $itemId = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                                $cfg    = (array) ($item['config'] ?? []);
                                $title  = ! empty($item['title']) ? $item['title'] : ($cfg['aspect'] ?? ('Aspek ' . ($idx + 1)));
                                $bobot  = (float) ($item['weight'] ?? $cfg['bobot'] ?? 1.0);
                                if ($bobot <= 0) $bobot = 1.0;

                                $resp  = $responses_by_item_id[$itemId] ?? null;
                                $score = null;
                                if ($resp) {
                                    if (! empty($resp->reviewer_response)) {
                                        $payload = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                                        $score   = isset($payload['score']) ? (float) $payload['score'] : ($resp->score !== null ? (float) $resp->score : null);
                                    } elseif (! empty($resp->evaluator_response)) {
                                        $payload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                        $score   = isset($payload['score']) ? (float) $payload['score'] : ($resp->score !== null ? (float) $resp->score : null);
                                    } elseif ($resp->score !== null) {
                                        $score = (float) $resp->score;
                                    }
                                }
                                $nilai     = $score !== null ? ($bobot * $score) : 0.0;
                                $totBobot += $bobot;
                                $totSkor  += (float) ($score ?? 0);
                                $totNilai += $nilai;
                            ?>
                            <tr>
                                <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);"><?= $idx + 1 ?></td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-dark" style="font-size: 13.5px;"><?= esc($title) ?></div>
                                    <?php if (! empty($item['description'])): ?>
                                        <small class="text-muted d-block"><?= esc($item['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold"><?= round($bobot) == $bobot ? (int) $bobot : number_format($bobot, 1) ?></td>
                                <td class="text-center align-middle font-weight-bold"><?= $score !== null ? (round($score) == $score ? (int) $score : number_format($score, 1)) : '-' ?></td>
                                <td class="text-center align-middle font-weight-bold text-primary"><?= $score !== null ? (round($nilai) == $nilai ? (int) $nilai : number_format($nilai, 1)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                            <td colspan="2" class="text-center align-middle text-uppercase" style="letter-spacing: 0.05em; font-size: 13px;">TOTAL</td>
                            <td class="text-center align-middle"><?= round($totBobot) == $totBobot ? (int) $totBobot : number_format($totBobot, 1) ?></td>
                            <td class="text-center align-middle"><?= round($totSkor) == $totSkor ? (int) $totSkor : number_format($totSkor, 1) ?></td>
                            <td class="text-center align-middle text-primary"><?= round($totNilai) == $totNilai ? (int) $totNilai : number_format($totNilai, 1) ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada butir Aspek Penilaian Kinerja terdaftar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
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
                    <?php if (! empty($weightedItems)): ?>
                        <?php $scoreOptions = [3 => 'Sangat Baik', 2 => 'Baik', 1 => 'Kurang']; ?>
                        <?php foreach ($weightedItems as $idx => $item): ?>
                            <?php
                                $itemId = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                                $cfg    = (array) ($item['config'] ?? []);
                                $title  = ! empty($item['title']) ? $item['title'] : ($cfg['aspect'] ?? ('Aspek ' . ($idx + 1)));
                                $weight = (float) ($item['weight'] ?? $cfg['bobot'] ?? 1.0);
                                if ($weight <= 0) $weight = 1.0;

                                $resp       = $responses_by_item_id[$itemId] ?? null;
                                $savedScore = null;
                                if ($resp) {
                                    if ($userMode === 'REVIEWER' && ! empty($resp->reviewer_response)) {
                                        $payload    = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                                        $savedScore = isset($payload['score']) ? (float) $payload['score'] : ($resp->score !== null ? (float) $resp->score : null);
                                    } elseif (! empty($resp->evaluator_response)) {
                                        $payload    = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                        $savedScore = isset($payload['score']) ? (float) $payload['score'] : ($resp->score !== null ? (float) $resp->score : null);
                                    } elseif ($resp->score !== null) {
                                        $savedScore = (float) $resp->score;
                                    }
                                }
                            ?>
                            <tr class="operator-aspect-row" data-item-id="<?= $itemId ?>" data-weight="<?= $weight ?>">
                                <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);"><?= $idx + 1 ?></td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-dark" style="font-size: 13.5px;"><?= esc($title) ?></div>
                                    <?php if (! empty($item['description'])): ?>
                                        <small class="text-muted d-block"><?= esc($item['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($scoreOptions as $val => $optLabel): ?>
                                    <td class="text-center align-middle py-3 ap-check-cell">
                                        <?php if ($canEditScores): ?>
                                            <div class="custom-control custom-radio d-inline-flex justify-content-center align-items-center">
                                                <input type="radio"
                                                       id="op_score_<?= $itemId ?>_<?= $val ?>"
                                                       name="operator_score[<?= $itemId ?>]"
                                                       value="<?= $val ?>"
                                                       class="custom-control-input operator-score-radio"
                                                       data-item-id="<?= $itemId ?>"
                                                       data-weight="<?= $weight ?>"
                                                       <?= ($savedScore !== null && round($savedScore) == $val) ? 'checked' : '' ?>>
                                                <label class="custom-control-label" for="op_score_<?= $itemId ?>_<?= $val ?>">
                                                    <span class="sr-only"><?= esc($title . ' - ' . $optLabel) ?></span>
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <span class="operator-readonly-indicator font-weight-bold <?= ($savedScore !== null && round($savedScore) == $val) ? 'text-success' : 'text-muted-light' ?>">
                                                <?= ($savedScore !== null && round($savedScore) == $val) ? '<i class="fas fa-check text-success" aria-label="' . esc($optLabel) . '"></i>' : '-' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Belum ada butir Aspek Penilaian Kinerja terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
