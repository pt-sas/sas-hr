<?php
    $isOperator    = ! empty($is_operator);
    $summary       = $summary ?? [];
    $finalScoreVal = isset($finalScoreVal) ? (float) $finalScoreVal : (float) ($appraisal->final_score ?? 0);
    $ratingMeta    = $ratingMeta ?? ($isOperator
        ? appraisal_rating_meta((float) ($summary['totNilai'] ?? 0), true)
        : appraisal_rating_meta(($appraisal->final_score ?? null) !== null ? $finalScoreVal : null, false));
    $scoreSectionNum = $score_section_number ?? 'VI';
?>
<div class="ap-card mb-4">
    <div class="ap-card-head">
        <h5 class="ap-card-title"><?= esc($scoreSectionNum) ?>. Ringkasan Skor Akhir</h5>
    </div>
    <div class="ap-card-body p-0">
        <div class="ap-score-summary-grid">
            <div class="ap-score-col">
                <div class="ap-summary-label">Skor Akhir Performa</div>
                <div class="ap-summary-score">
                    <?php if ($isOperator): ?>
                        <?= esc($summary['displaySkorAkhir'] ?? '0') ?>
                    <?php else: ?>
                        <?= number_format($finalScoreVal, 2) ?> <small>/ 4.00</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ap-score-col">
                <div class="ap-summary-label">Predikat Penilaian</div>
                <div>
                    <span class="ap-rating ap-summary-rating <?= esc($ratingMeta['class']) ?>">
                        <?= esc($isOperator ? $ratingMeta['label'] : ($appraisal->rating ?? $ratingMeta['label'])) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
