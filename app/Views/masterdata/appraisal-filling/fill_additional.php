<?php
    // Helper fallback if not provided by controller
    if (!isset($parseActions) || !is_callable($parseActions)) {
        $parseActions = function ($raw): array {
            if (empty($raw)) return [];
            if (is_array($raw)) return array_values(array_filter(array_map('trim', $raw)));
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) return array_values(array_filter(array_map('trim', $decoded)));
                return array_values(array_filter(array_map('trim', explode("\n", $raw))));
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

    $booleanSections = [];
    foreach ($sections as $sec) {
        $secType = strtoupper($sec['section_type'] ?? '');
        if ($secType === 'BOOLEAN') {
            $booleanSections[] = $sec;
        }
    }

    // Fallback if section_type is not explicitly defined
    if (empty($booleanSections) && !empty($sections)) {
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
            if ($hasBool && !$hasScore) {
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

    $canEditScores = in_array($user_mode, ['EVALUATOR', 'GM'], true);
?>

<!-- 3. KOMPETENSI TAMBAHAN -->
<div class="ap-card" id="card_section_tambahan">
    <div class="ap-card-head">
        <h5 class="ap-card-title">3. Kompetensi tambahan</h5>
        <span class="text-muted small">Centang jika terbukti</span>
    </div>

    <div class="table-responsive">
        <table class="ap-table">
            <thead>
                <tr>
                    <th style="width: 24%;">Kompetensi</th>
                    <th style="width: 32%;">Definisi</th>
                    <th style="width: 32%;">Key actions (indikator perilaku)</th>
                    <th class="text-center" style="width: 12%;">Terbukti / Ya</th>
                </tr>
            </thead>
            <?php if (!empty($allBooleanItems)): ?>
                <?php foreach ($allBooleanItems as $bIdx => $bItem): ?>
                    <?php
                        $bItemId      = (int) ($bItem['md_appraisal_template_item_id'] ?? $bItem['template_item_id']);
                        $bCfg         = (array) ($bItem['config'] ?? []);
                        $bTitle       = !empty($bCfg['competency']) ? $bCfg['competency'] : (!empty($bItem['title']) ? $bItem['title'] : ('Kompetensi ' . ($bIdx + 1)));
                        $bDef         = !empty($bItem['description']) ? $bItem['description'] : (!empty($bCfg['definition']) ? $bCfg['definition'] : '-');
                        $bKeyActions  = $parseActions($bCfg['key_actions'] ?? null);

                        $bResp       = $responses_by_item_id[$bItemId] ?? null;
                        $evalPayload = ($bResp && !empty($bResp->evaluator_response))
                            ? (is_string($bResp->evaluator_response) ? json_decode($bResp->evaluator_response, true) : $bResp->evaluator_response)
                            : [];
                        $gmPayload   = ($bResp && !empty($bResp->gm_response))
                            ? (is_string($bResp->gm_response) ? json_decode($bResp->gm_response, true) : $bResp->gm_response)
                            : [];

                        if (($user_mode ?? '') === 'GM') {
                            $checkedActions = isset($gmPayload['checked_actions'])
                                ? $gmPayload['checked_actions']
                                : ($evalPayload['checked_actions'] ?? []);
                            $booleanValue   = $gmPayload['boolean_value'] ?? $evalPayload['boolean_value'] ?? null;
                        } else {
                            $checkedActions = $evalPayload['checked_actions'] ?? [];
                            $booleanValue   = $evalPayload['boolean_value'] ?? null;
                        }

                        $totalActions = !empty($bKeyActions) ? count($bKeyActions) : 1;
                    ?>
                    <tbody>
                        <?php if (!empty($bKeyActions)): ?>
                            <?php foreach ($bKeyActions as $kIdx => $ka): ?>
                                <?php
                                    $isActionChecked = false;
                                    if (!empty($checkedActions) && is_array($checkedActions)) {
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
                                            $isActionChecked = in_array((string) $kIdx, array_map('strval', $checkedActions), true);
                                        }
                                    }
                                ?>
                                <tr class="ap-check-row competency-group-<?= $bItemId ?> <?= !$canEditScores ? 'is-disabled' : '' ?>">
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
                                        <label for="ka_check_<?= $bItemId ?>_<?= $kIdx ?>" class="mb-0 ap-action-text d-block" style="cursor:pointer;">
                                            <span class="ap-action-letter"><?= chr(97 + $kIdx) ?>.</span><?= esc($ka) ?>
                                        </label>
                                    </td>

                                    <td class="ap-check-cell">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                class="custom-control-input ka-item-checkbox"
                                                id="ka_check_<?= $bItemId ?>_<?= $kIdx ?>"
                                                name="checked_actions[<?= $bItemId ?>][]"
                                                value="<?= $kIdx ?>"
                                                data-parent-id="<?= $bItemId ?>"
                                                data-action-idx="<?= $kIdx ?>"
                                                <?= $isActionChecked ? 'checked' : '' ?>
                                                <?= !$canEditScores ? 'disabled' : '' ?>>
                                            <label class="custom-control-label" for="ka_check_<?= $bItemId ?>_<?= $kIdx ?>"></label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php
                                $isProven = in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true);
                            ?>
                            <tr class="ap-check-row <?= !$canEditScores ? 'is-disabled' : '' ?>">
                                <td class="ap-comp-cell">
                                    <div class="ap-comp-name"><span class="ap-comp-index"><?= ($bIdx + 1) ?></span><?= esc($bTitle) ?></div>
                                </td>
                                <td class="ap-comp-cell">
                                    <div class="ap-def"><?= esc($bDef) ?></div>
                                </td>
                                <td>
                                    <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                </td>
                                <td class="ap-check-cell">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                            class="custom-control-input boolean-item-checkbox"
                                            id="bool_check_<?= $bItemId ?>"
                                            name="boolean_value[<?= $bItemId ?>]"
                                            value="Y"
                                            data-parent-id="<?= $bItemId ?>"
                                            <?= $isProven ? 'checked' : '' ?>
                                            <?= !$canEditScores ? 'disabled' : '' ?>>
                                        <label class="custom-control-label" for="bool_check_<?= $bItemId ?>"></label>
                                    </div>
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
    </div>
</div>