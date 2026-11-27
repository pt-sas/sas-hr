<?php
    helper('appraisal_view');

    $displayMode = $display_mode ?? 'edit';
    $userMode    = $user_mode ?? '';

    if ($displayMode === 'compare') {
        $booleanSections = $booleanSections ?? [];
    } else {
        $sections = $template_snapshot['sections'] ?? [];
        $items    = $template_snapshot['items'] ?? [];

        $itemsBySection = [];
        foreach ($items as $item) {
            $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
            $itemsBySection[$secId][] = $item;
        }

        $booleanSections = [];
        foreach ($sections as $sec) {
            if (strtoupper($sec['section_type'] ?? '') === 'BOOLEAN') {
                $booleanSections[] = $sec;
            }
        }

        if (empty($booleanSections) && ! empty($sections)) {
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
                if ($hasBool && ! $hasScore) {
                    $booleanSections[] = $sec;
                }
            }
        }

        $allBooleanItems = [];
        foreach ($booleanSections as $bSec) {
            $bId = (int) $bSec['md_appraisal_section_id'];
            foreach ($itemsBySection[$bId] ?? [] as $it) {
                $allBooleanItems[] = $it;
            }
        }

        $canEditScores = ($displayMode === 'edit') && (in_array($userMode, ['EVALUATOR', 'REVIEWER', 'HR'], true) || ! empty($is_preview));

        if ($displayMode === 'readonly') {
            $itemsKompetensiTambahan = $itemsKompetensiTambahan ?? [];
        }
    }
?>

<!-- III. KOMPETENSI TAMBAHAN -->
<div class="ap-card<?= $displayMode === 'compare' ? ' mb-4' : '' ?>" id="card_section_tambahan">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center" style="gap: 12px;">
            <h5 class="ap-card-title mb-0">III. Kompetensi Tambahan</h5>
            <span class="ap-category-badge">Ya / Tidak</span>
        </div>
        <?php if ($displayMode === 'edit'): ?>
            <span class="text-muted font-weight-bold" style="font-size: 13px;">*Beri tanda centang pada perilaku yang ditunjukkan</span>
        <?php endif; ?>
    </div>

    <?php if (in_array($displayMode, ['edit', 'readonly'], true)): ?>
        <div class="ap-guide">
            <p class="ap-guide-title">Panduan Pengisian Kompetensi Tambahan (Ya / Tidak)</p>
            <ul class="ap-guide-list">
                <li>Centang indikator perilaku yang secara nyata ditunjukkan oleh karyawan selama periode penilaian.</li>
                <li>Biarkan tidak tercentang apabila perilaku belum terlihat atau belum konsisten.</li>
                <li>Bagian ini bersifat kualitatif dan tidak mengubah perhitungan skor akhir.</li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="table-responsive<?= $displayMode !== 'compare' ? ' ap-table-wrapper' : '' ?>">
        <?php if ($displayMode === 'compare'): ?>
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">NO</th>
                        <th style="width: 20%;">KOMPETENSI</th>
                        <th style="width: 36%;">INDIKATOR PERILAKU</th>
                        <th style="width: 13%; text-align: center;">PENILAI</th>
                        <th style="width: 13%; text-align: center;">PENINJAU</th>
                        <th style="width: 13%; text-align: center;">HR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($booleanSections)): ?>
                        <?php $boolItemNumber = 0; ?>
                        <?php foreach ($booleanSections as $bSec): ?>
                            <?php foreach ($bSec['items'] as $bItem): ?>
                                <?php
                                    $boolItemNumber++;
                                    $bActions     = $bItem['actions'] ?? [];
                                    $hasBActions  = ! empty($bActions);
                                    $bActionCount = count($bActions);
                                    $bTitle       = esc($bItem['title'] ?? ('Kompetensi ' . $boolItemNumber));
                                    $bDef         = esc($bItem['definition'] ?? '-');
                                    $bRowSpan     = $hasBActions ? $bActionCount : 1;
                                ?>
                                <?php if ($hasBActions): ?>
                                    <?php foreach ($bActions as $bIdx => $bAct): ?>
                                        <tr>
                                            <?php if ($bIdx === 0): ?>
                                                <td rowspan="<?= $bRowSpan ?>" class="text-center font-weight-bold align-top ap-comp-cell" style="width: 50px;">
                                                    <?= $boolItemNumber ?>.
                                                </td>
                                                <td rowspan="<?= $bRowSpan ?>" class="ap-comp-cell">
                                                    <div class="ap-comp-name mb-1"><?= $bTitle ?></div>
                                                    <?php if ($bDef !== '-' && ! empty($bDef)): ?>
                                                        <div class="ap-def"><?= $bDef ?></div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php $hasLetterPrefix = preg_match('/^[a-z0-9][\.\)]\s*/i', (string) ($bAct['text'] ?? '')); ?>
                                                <div class="ap-action-text">
                                                    <?php if (! $hasLetterPrefix): ?>
                                                        <span class="ap-action-letter"><?= esc($bAct['letter']) ?>.</span>
                                                    <?php endif; ?>
                                                    <?= esc($bAct['text']) ?>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if (! empty($bAct['eval_checked'])): ?>
                                                    <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if ($bAct['reviewer_checked'] !== null): ?>
                                                    <?php if (! empty($bAct['reviewer_checked'])): ?>
                                                        <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                                    <?php elseif (! empty($bAct['is_overridden'])): ?>
                                                        <span style="color: #dc2626; font-weight: 800; font-size: 13px; background: #fef2f2; border: 1.5px solid #fecaca; padding: 3px 10px; border-radius: 4px;">&times;</span>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if (isset($bAct['hr_checked']) && $bAct['hr_checked'] !== null): ?>
                                                    <?php if (! empty($bAct['hr_checked'])): ?>
                                                        <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php $singleAct = $bActions[0] ?? []; ?>
                                    <tr>
                                        <td class="text-center font-weight-bold align-top ap-comp-cell" style="width: 50px;"><?= $boolItemNumber ?>.</td>
                                        <td class="ap-comp-cell">
                                            <div class="ap-comp-name mb-1"><?= $bTitle ?></div>
                                            <?php if ($bDef !== '-' && ! empty($bDef)): ?>
                                                <div class="ap-def"><?= $bDef ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><div class="ap-action-text text-muted font-italic">Indikator umum</div></td>
                                        <td class="text-center align-middle">
                                            <?php if (! empty($singleAct['eval_checked'])): ?>
                                                <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if (isset($singleAct['reviewer_checked']) && $singleAct['reviewer_checked'] !== null): ?>
                                                <?php if (! empty($singleAct['reviewer_checked'])): ?>
                                                    <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                                <?php elseif (! empty($singleAct['is_overridden'])): ?>
                                                    <span style="color: #dc2626; font-weight: 800; font-size: 13px; background: #fef2f2; border: 1.5px solid #fecaca; padding: 3px 10px; border-radius: 4px;">&times;</span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if (isset($singleAct['hr_checked']) && $singleAct['hr_checked'] !== null): ?>
                                                <?php if (! empty($singleAct['hr_checked'])): ?>
                                                    <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Tidak ada butir kriteria tambahan dalam template penilaian ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 24%;">Kompetensi</th>
                        <th style="width: 30%;">Definisi</th>
                        <th style="width: 32%;">Indikator Perilaku</th>
                        <th class="text-center" style="width: 14%;">Ya / Tidak</th>
                    </tr>
                </thead>
                <?php if (! empty($allBooleanItems)): ?>
                    <?php foreach ($allBooleanItems as $bIdx => $bItem): ?>
                        <?php
                            $bItemId     = (int) ($bItem['md_appraisal_template_item_id'] ?? $bItem['template_item_id']);
                            $bCfg        = (array) ($bItem['config'] ?? []);
                            $bTitle      = ! empty($bCfg['competency']) ? $bCfg['competency'] : (! empty($bItem['title']) ? $bItem['title'] : ('Kompetensi ' . ($bIdx + 1)));
                            $bDef        = ! empty($bItem['description']) ? $bItem['description'] : (! empty($bCfg['definition']) ? $bCfg['definition'] : '-');
                            $bKeyActions = appraisal_parse_actions($bCfg['key_actions'] ?? null);

                            $bResp       = $responses_by_item_id[$bItemId] ?? null;
                            $evalPayload = ($bResp && ! empty($bResp->evaluator_response))
                                ? (is_string($bResp->evaluator_response) ? json_decode($bResp->evaluator_response, true) : $bResp->evaluator_response)
                                : [];
                            $reviewerPayload = ($bResp && ! empty($bResp->reviewer_response))
                                ? (is_string($bResp->reviewer_response) ? json_decode($bResp->reviewer_response, true) : $bResp->reviewer_response)
                                : [];

                            if ($displayMode === 'readonly') {
                                $checkedActions = ! empty($reviewerPayload) && isset($reviewerPayload['checked_actions'])
                                    ? $reviewerPayload['checked_actions']
                                    : ($evalPayload['checked_actions'] ?? []);
                                $booleanValue   = $reviewerPayload['boolean_value'] ?? $evalPayload['boolean_value'] ?? null;
                            } elseif ($userMode === 'REVIEWER') {
                                $checkedActions = isset($reviewerPayload['checked_actions'])
                                    ? $reviewerPayload['checked_actions']
                                    : ($evalPayload['checked_actions'] ?? []);
                                $booleanValue   = $reviewerPayload['boolean_value'] ?? $evalPayload['boolean_value'] ?? null;
                            } else {
                                $checkedActions = $evalPayload['checked_actions'] ?? [];
                                $booleanValue   = $evalPayload['boolean_value'] ?? null;
                            }

                            $totalActions = ! empty($bKeyActions) ? count($bKeyActions) : 1;
                        ?>
                        <tbody>
                            <?php if (! empty($bKeyActions)): ?>
                                <?php foreach ($bKeyActions as $kIdx => $ka): ?>
                                    <?php
                                        $isActionChecked = false;
                                        if (! empty($checkedActions) && is_array($checkedActions)) {
                                            $hasYorN = false;
                                            foreach ($checkedActions as $v) {
                                                if (in_array(strtoupper((string) $v), ['Y', 'N'], true)) {
                                                    $hasYorN = true;
                                                    break;
                                                }
                                            }
                                            if ($hasYorN) {
                                                $val = $checkedActions[$kIdx] ?? $checkedActions[(string) $kIdx] ?? null;
                                                $isActionChecked = in_array(strtoupper((string) $val), ['Y', '1', 'TRUE'], true);
                                            } else {
                                                $isActionChecked = in_array((string) $kIdx, array_map('strval', $checkedActions), true)
                                                    || in_array($ka, $checkedActions, true);
                                            }
                                        }
                                        if ($displayMode === 'readonly' && $isActionChecked) {
                                            $itemsKompetensiTambahan[] = preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($ka));
                                        }
                                    ?>
                                    <tr class="ap-check-row competency-group-<?= $bItemId ?> <?= ! $canEditScores ? 'is-disabled' : '' ?>">
                                        <?php if ($kIdx === 0): ?>
                                            <td rowspan="<?= $totalActions ?>" class="ap-comp-cell">
                                                <div class="ap-comp-name">
                                                    <span class="ap-comp-index"><?= ($bIdx + 1) ?></span><?= esc($bTitle) ?>
                                                </div>
                                            </td>
                                            <td rowspan="<?= $totalActions ?>" class="ap-comp-cell">
                                                <div class="ap-def"><?= esc($bDef) ?></div>
                                            </td>
                                        <?php endif; ?>

                                        <td>
                                            <?php if ($displayMode === 'edit'): ?>
                                                <label for="ka_check_<?= $bItemId ?>_<?= $kIdx ?>" class="mb-0 ap-action-text d-block" style="cursor:pointer;">
                                                    <span class="ap-action-letter"><?= chr(97 + $kIdx) ?>.</span><?= esc($ka) ?>
                                                </label>
                                            <?php else: ?>
                                                <div class="ap-action-text">
                                                    <span class="ap-action-letter"><?= chr(97 + $kIdx) ?>.</span><?= esc($ka) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="<?= $displayMode === 'readonly' ? 'text-center align-middle' : 'ap-check-cell' ?>">
                                            <?php if ($displayMode === 'readonly'): ?>
                                                <input type="checkbox" <?= $isActionChecked ? 'checked' : '' ?> disabled aria-label="<?= esc($ka) ?>" style="width: 18px; height: 18px; cursor: default; accent-color: #2563eb;">
                                            <?php else: ?>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox"
                                                        class="custom-control-input ka-item-checkbox"
                                                        id="ka_check_<?= $bItemId ?>_<?= $kIdx ?>"
                                                        name="checked_actions[<?= $bItemId ?>][]"
                                                        value="<?= $kIdx ?>"
                                                        data-parent-id="<?= $bItemId ?>"
                                                        data-action-idx="<?= $kIdx ?>"
                                                        <?= $isActionChecked ? 'checked' : '' ?>
                                                        <?= ! $canEditScores ? 'disabled' : '' ?>>
                                                    <label class="custom-control-label" for="ka_check_<?= $bItemId ?>_<?= $kIdx ?>"><span class="sr-only"><?= esc($ka) ?></span></label>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php
                                    $isProven = in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true);
                                    if ($displayMode === 'readonly' && $isProven) {
                                        $itemsKompetensiTambahan[] = preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($bTitle));
                                    }
                                ?>
                                <tr class="ap-check-row <?= ! $canEditScores ? 'is-disabled' : '' ?>">
                                    <td class="ap-comp-cell">
                                        <div class="ap-comp-name"><span class="ap-comp-index"><?= ($bIdx + 1) ?></span><?= esc($bTitle) ?></div>
                                    </td>
                                    <td class="ap-comp-cell">
                                        <div class="ap-def"><?= esc($bDef) ?></div>
                                    </td>
                                    <td>
                                        <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                    </td>
                                    <td class="<?= $displayMode === 'readonly' ? 'text-center align-middle' : 'ap-check-cell' ?>">
                                        <?php if ($displayMode === 'readonly'): ?>
                                            <input type="checkbox" <?= $isProven ? 'checked' : '' ?> disabled aria-label="<?= esc($bTitle) ?>" style="width: 22px; height: 22px; cursor: default; accent-color: #0284c7;">
                                        <?php else: ?>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                    class="custom-control-input boolean-item-checkbox"
                                                    id="bool_check_<?= $bItemId ?>"
                                                    name="boolean_value[<?= $bItemId ?>]"
                                                    value="Y"
                                                    data-parent-id="<?= $bItemId ?>"
                                                    <?= $isProven ? 'checked' : '' ?>
                                                    <?= ! $canEditScores ? 'disabled' : '' ?>>
                                                <label class="custom-control-label" for="bool_check_<?= $bItemId ?>"><span class="sr-only"><?= esc($bTitle) ?></span></label>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                Tidak ada butir kompetensi checklist dalam template ini.
                            </td>
                        </tr>
                    </tbody>
                <?php endif; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
