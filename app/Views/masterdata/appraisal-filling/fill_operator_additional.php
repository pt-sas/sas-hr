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

    $canEditScores = in_array($user_mode, ['EVALUATOR', 'GM'], true) || !empty($is_preview);
?>

<div class="ap-card mb-4">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div>
            <h5 class="ap-card-title mb-0">Kriteria Tambahan</h5>
            <small class="text-muted d-block mt-1">Daftar kriteria inisiatif dan kontribusi kerja tambahan</small>
        </div>
    </div>

    <div class="table-responsive">
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
                                        <span class="font-weight-600 text-dark" style="font-size: 13.5px;">Lainnya :</span>
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
                                    <span class="font-weight-600 text-dark" style="font-size: 13.5px;"><?= esc($title) ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center align-middle py-3">
                                <?php if ($canEditScores): ?>
                                    <label class="operator-checkbox-label mb-0 cursor-pointer p-2">
                                        <input type="checkbox" 
                                               name="operator_kriteria[<?= $itemId ?>]" 
                                               value="Y" 
                                               class="operator-kriteria-checkbox"
                                               data-item-id="<?= $itemId ?>"
                                               <?= $isChecked ? 'checked' : '' ?>>
                                        <span class="operator-check-indicator font-weight-bold">v</span>
                                    </label>
                                <?php else: ?>
                                    <span class="operator-readonly-indicator font-weight-bold <?= $isChecked ? 'text-primary' : 'text-muted-light' ?>">
                                        <?= $isChecked ? 'v' : '-' ?>
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
.operator-checkbox-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: background 0.15s ease;
}
.operator-checkbox-label:hover {
    background: #f1f5f9;
}
.operator-kriteria-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.operator-kriteria-checkbox:checked + .operator-check-indicator {
    background: #3b82f6;
    border-color: #2563eb;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
}
.operator-checkbox-label:focus-within .operator-check-indicator {
    outline: 2px solid #93c5fd;
}
</style>
