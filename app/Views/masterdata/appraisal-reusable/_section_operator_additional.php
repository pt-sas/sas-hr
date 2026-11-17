<?php
    $displayMode = $display_mode ?? 'edit';
    $userMode    = $user_mode ?? '';

    if ($displayMode === 'compare') {
        $isReviewed  = ! empty($is_reviewer_reviewed);
        $singleItems = $single_items ?? [];
    } else {
        $sections = $template_snapshot['sections'] ?? [];
        $items    = $template_snapshot['items'] ?? [];

        $itemsBySection = [];
        foreach ($items as $item) {
            $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
            $itemsBySection[$secId][] = $item;
        }

        $singleSections = [];
        foreach ($sections as $sec) {
            if (strtoupper($sec['section_type'] ?? '') === 'SINGLE_CHECKLIST') {
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

        if (empty($singleItems) && ! empty($items)) {
            foreach ($items as $it) {
                if (strtoupper($it['input_type'] ?? '') === 'SINGLE_CHECKLIST') {
                    $singleItems[] = $it;
                }
            }
        }

        $canEditScores = ($displayMode === 'edit') && (in_array($userMode, ['EVALUATOR', 'REVIEWER', 'HR'], true) || ! empty($is_preview));
    }
?>

<div class="ap-card<?= $displayMode === 'compare' ? ' mb-4' : '' ?>" id="card_section_tambahan">
    <div class="ap-card-head d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center" style="gap: 12px;">
            <h5 class="ap-card-title mb-0">III. Penilaian Tambahan</h5>
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
                <li>Beri tanda centang pada kriteria yang terpenuhi / teramati selama periode penilaian; biarkan kosong jika tidak berlaku.</li>
                <li>Jika mencentang Lainnya, tuliskan keterangan kriteria tambahan pada kolom teks yang tersedia.</li>
                <li>Setiap kriteria yang dicentang akan menambahkan poin tambahan berupa huruf <strong>"A"</strong> pada Skor Akhir (contoh: skor 8 dengan 2 kriteria tercentang menjadi
                <strong>8AA</strong>).</li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="table-responsive<?= $displayMode !== 'compare' ? ' ap-table-wrapper' : '' ?>">
        <?php if ($displayMode === 'compare'): ?>
            <table class="ap-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">NO</th>
                        <th style="width: 65%;">KRITERIA</th>
                        <th style="width: 15%; text-align: center;">PENILAI</th>
                        <th style="width: 15%; text-align: center;">PENINJAU</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($singleItems)): ?>
                        <?php foreach ($singleItems as $idx => $row): ?>
                            <?php
                                $isOtherRow      = ! empty($row['is_other']);
                                $evalChecked     = ! empty($row['eval_checked']);
                                $reviewerChecked = ! empty($row['reviewer_checked']);
                                $evalOther       = $row['eval_other_text'] ?? '';
                                $reviewerOther   = $row['reviewer_other_text'] ?? '';
                            ?>
                            <tr>
                                <td class="text-center font-weight-bold align-middle" style="width: 50px; background: var(--ap-surface);"><?= $idx + 1 ?>.</td>
                                <td class="align-middle">
                                    <?php if ($isOtherRow): ?>
                                        <div class="ap-action-text">
                                            <span class="font-weight-bold">Lainnya :</span>
                                            <span class="font-italic text-muted">
                                                <?= ! empty($reviewerOther ?: $evalOther) ? esc($reviewerOther ?: $evalOther) : '-' ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="ap-action-text"><?= esc($row['title'] ?? ('Kriteria ' . ($idx + 1))) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($evalChecked): ?>
                                        <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 13px; font-weight: 600;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle">
                                    <?php if ($isReviewed): ?>
                                        <?php if ($reviewerChecked): ?>
                                            <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">&check;</span>
                                        <?php elseif ($evalChecked): ?>
                                            <span style="color: #dc2626; font-weight: 800; font-size: 13px; background: #fef2f2; border: 1.5px solid #fecaca; padding: 3px 10px; border-radius: 4px;">&times;</span>
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
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Tidak ada butir kriteria tambahan dalam template penilaian ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="ap-table" id="table_operator_additional">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 81%;">Kriteria</th>
                        <th class="text-center" style="width: 14%;">Ya / Tidak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($singleItems)): ?>
                        <?php foreach ($singleItems as $idx => $item): ?>
                            <?php
                                $itemId    = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id'] ?? ($idx + 1));
                                $title     = ! empty($item['title']) ? $item['title'] : ('Kriteria ' . ($idx + 1));
                                $resp      = $responses_by_item_id[$itemId] ?? null;
                                $isChecked = false;
                                $otherText = '';

                                if ($resp) {
                                    $activePayload = [];
                                    if ($displayMode === 'readonly' && ! empty($resp->reviewer_response)) {
                                        $activePayload = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                                    } elseif ($userMode === 'REVIEWER' && ! empty($resp->reviewer_response)) {
                                        $activePayload = is_string($resp->reviewer_response) ? json_decode($resp->reviewer_response, true) : $resp->reviewer_response;
                                    } elseif (! empty($resp->evaluator_response)) {
                                        $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                                    }

                                    if (isset($activePayload['boolean_value'])) {
                                        $isChecked = in_array(strtoupper((string) $activePayload['boolean_value']), ['Y', '1', 'TRUE'], true);
                                    } elseif (! empty($activePayload['checked_actions'])) {
                                        $isChecked = true;
                                    }

                                    if (! empty($activePayload['other_text'])) {
                                        $otherText = (string) $activePayload['other_text'];
                                    }
                                }

                                $isOtherRow = (stripos(trim($title), 'lainnya') === 0 || stripos(trim($title), 'lain-lain') === 0);
                            ?>
                            <tr class="ap-check-row operator-kriteria-row <?= ! $canEditScores ? 'is-disabled' : '' ?>" data-item-id="<?= $itemId ?>">
                                <td class="text-center align-middle" style="background: var(--ap-surface);">
                                    <span class="ap-comp-index" style="margin-right: 0;"><?= $idx + 1 ?></span>
                                </td>
                                <td class="align-middle">
                                    <?php if ($isOtherRow): ?>
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                            <?php if ($displayMode === 'edit'): ?>
                                                <label for="op_check_<?= $itemId ?>" class="mb-0 ap-action-text" style="cursor: pointer; font-weight: 700;">Lainnya :</label>
                                            <?php else: ?>
                                                <span class="ap-action-text font-weight-bold">Lainnya :</span>
                                            <?php endif; ?>
                                            <?php if ($canEditScores): ?>
                                                <input type="text"
                                                       name="operator_other_text[<?= $itemId ?>]"
                                                       value="<?= esc($otherText) ?>"
                                                       class="form-control form-control-sm operator-other-input"
                                                       data-item-id="<?= $itemId ?>"
                                                       placeholder="Tuliskan kriteria tambahan..."
                                                       style="flex: 1; min-width: 200px; height: 32px; border-radius: 4px; font-size: 13.5px;">
                                            <?php else: ?>
                                                <span class="ap-action-text font-italic text-muted"><?= ! empty($otherText) ? esc($otherText) : '-' ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($displayMode === 'edit'): ?>
                                            <label for="op_check_<?= $itemId ?>" class="mb-0 ap-action-text d-block" style="cursor: pointer;"><?= esc($title) ?></label>
                                        <?php else: ?>
                                            <div class="ap-action-text"><?= esc($title) ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $displayMode === 'readonly' ? 'text-center align-middle' : 'ap-check-cell' ?>">
                                    <?php if ($displayMode === 'readonly'): ?>
                                        <input type="checkbox" <?= $isChecked ? 'checked' : '' ?> disabled aria-label="<?= esc($title) ?>" style="width: 18px; height: 18px; cursor: default; accent-color: #2563eb;">
                                    <?php else: ?>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   id="op_check_<?= $itemId ?>"
                                                   name="operator_kriteria[<?= $itemId ?>]"
                                                   value="Y"
                                                   class="custom-control-input operator-kriteria-checkbox"
                                                   data-item-id="<?= $itemId ?>"
                                                   <?= $isChecked ? 'checked' : '' ?>
                                                   <?= ! $canEditScores ? 'disabled' : '' ?>>
                                            <label class="custom-control-label" for="op_check_<?= $itemId ?>">
                                                <span class="sr-only"><?= esc($title) ?></span>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                Belum ada butir kriteria tambahan dalam template penilaian ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
