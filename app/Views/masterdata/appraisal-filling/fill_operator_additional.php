<?php
    $sections = $template_snapshot['sections'] ?? [];
    $items    = $template_snapshot['items'] ?? [];

    $itemsBySection = [];
    foreach ($items as $item) {
        $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
        $itemsBySection[$secId][] = $item;
    }

    $singleSections = [];
    foreach ($sections as $sec) {
        $secType = strtoupper($sec['section_type'] ?? '');
        if ($secType === 'SINGLE_CHECKLIST') {
            $singleSections[] = $sec;
        }
    }

    $singleItems = [];
    foreach ($singleSections as $sSec) {
        $secId = (int) $sSec['md_appraisal_section_id'];
        foreach ($itemsBySection[$secId] ?? [] as $it) {
            $singleItems[] = $it;
        }
    }

    // Fallback if sections were not tagged with SINGLE_CHECKLIST
    if (empty($singleItems) && !empty($items)) {
        foreach ($items as $it) {
            if (strtoupper($it['input_type'] ?? '') === 'SINGLE_CHECKLIST') {
                $singleItems[] = $it;
            }
        }
    }

    $canEditScores = in_array($user_mode, ['EVALUATOR', 'GM', 'HR'], true) || !empty($is_preview);
?>

<div class="ap-card mb-4">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center" style="gap: 12px;">
            <h5 class="ap-card-title mb-0">III. Penilaian Tambahan</h5>
            <span class="ap-category-badge">Ya / Tidak</span>
        </div>
        <span class="text-muted font-weight-bold" style="font-size: 13px;">*Beri tanda centang pada perilaku yang ditunjukkan</span>
    </div>

    <div class="table-responsive ap-table-wrapper">
        <table class="ap-table mb-0" id="table_operator_additional">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th style="width: 80%;">Kriteria</th>
                    <th style="width: 15%; text-align: center;">Ya</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($singleItems)): ?>
                    <?php foreach ($singleItems as $idx => $item): ?>
                        <?php
                            $itemId = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                            $title  = !empty($item['title']) ? $item['title'] : ('Kriteria ' . ($idx + 1));

                            $resp = $responses_by_item_id[$itemId] ?? null;
                            $isChecked = false;
                            $otherText = '';

                            if ($resp) {
                                $activePayload = [];
                                if ($user_mode === 'GM' && !empty($resp->gm_response)) {
                                    $activePayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                                } elseif (!empty($resp->evaluator_response)) {
                                    $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                }

                                if (isset($activePayload['boolean_value'])) {
                                    $isChecked = in_array(strtoupper((string) $activePayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                                } elseif (!empty($activePayload['checked_actions'])) {
                                    $isChecked = true;
                                }

                                if (!empty($activePayload['other_text'])) {
                                    $otherText = (string) $activePayload['other_text'];
                                }
                            }

                            // Detect if item is "Lainnya"
                            $isOtherRow = (stripos(trim($title), 'lainnya') === 0 || stripos(trim($title), 'lain-lain') === 0);
                        ?>
                        <tr class="operator-kriteria-row" data-item-id="<?= $itemId ?>">
                            <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                <?= $idx + 1 ?>
                            </td>

                            <td class="align-middle">
                                <?php if ($isOtherRow): ?>
                                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                        <label for="op_check_<?= $itemId ?>" class="font-weight-600 text-dark mb-0 cursor-pointer" style="font-size: 13.5px;">Lainnya :</label>
                                        <?php if ($canEditScores): ?>
                                            <input type="text" 
                                                   name="operator_other_text[<?= $itemId ?>]" 
                                                   value="<?= esc($otherText) ?>"
                                                   class="form-control form-control-sm operator-other-input"
                                                   data-item-id="<?= $itemId ?>"
                                                   placeholder="Tuliskan kriteria tambahan..."
                                                   style="flex: 1; min-width: 200px; height: 32px; border-radius: 6px; font-size: 13px;">
                                        <?php else: ?>
                                            <span class="text-dark font-italic"><?= !empty($otherText) ? esc($otherText) : '-' ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <label for="op_check_<?= $itemId ?>" class="font-weight-600 text-dark mb-0 cursor-pointer d-block" style="font-size: 13.5px;"><?= esc($title) ?></label>
                                <?php endif; ?>
                            </td>

                            <td class="text-center align-middle py-3 ap-check-cell">
                                <?php if ($canEditScores): ?>
                                    <div class="custom-control custom-checkbox d-inline-flex justify-content-center align-items-center">
                                        <input type="checkbox" 
                                               id="op_check_<?= $itemId ?>"
                                               name="operator_kriteria[<?= $itemId ?>]" 
                                               value="Y" 
                                               class="custom-control-input operator-kriteria-checkbox"
                                               data-item-id="<?= $itemId ?>"
                                               <?= $isChecked ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="op_check_<?= $itemId ?>"></label>
                                    </div>
                                <?php else: ?>
                                    <span class="operator-readonly-indicator font-weight-bold <?= $isChecked ? 'text-success' : 'text-muted-light' ?>">
                                        <?= $isChecked ? '<i class="fas fa-check text-success"></i>' : '-' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle mr-1"></i> Belum ada butir Kriteria Tambahan terdaftar.
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
.operator-kriteria-row td {
    transition: all 0.15s ease;
}
</style>
