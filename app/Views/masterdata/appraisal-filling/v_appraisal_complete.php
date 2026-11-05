<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?= $this->include('Views/masterdata/appraisal-reusable/modal_score_comparison') ?>
<link rel="stylesheet" href="<?= base_url('form_appraisal_filling_style.css') ?>">


<style>
/* Layout tweaks for complete view */
/* Prevent Atlantis panel spinner from hanging on this page */
.main-panel.is-loading:after {
    display: none !important;
}

/* Hide top toolbar (+ Add New, Refresh) */
.card-header .float-right,
.card-header .new_form,
.card-header .btn_requery,
.new_form {
    display: none !important;
}

.ap-report-header {
    background: #ffffff;
    border: 1px solid var(--ap-line);
    border-radius: var(--ap-radius);
    padding: 14px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.ap-report-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--ap-ink);
    letter-spacing: 0.04em;
    margin: 0;
    text-transform: uppercase;
}

.ap-score-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0;
    text-align: center;
}

.ap-score-col {
    padding: 24px 20px;
}

.ap-score-col:first-child {
    border-right: 1px solid var(--ap-line-soft);
}

.ap-summary-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ap-muted);
    margin-bottom: 8px;
}

.ap-summary-score {
    font-family: var(--ap-mono);
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--ap-ink);
    line-height: 1.1;
    margin-bottom: 4px;
}

.ap-summary-score small {
    font-size: 1.25rem;
    color: var(--ap-muted);
    font-weight: 600;
}

.ap-summary-rating {
    display: inline-block;
    margin-top: 6px;
    font-size: 14px;
    font-weight: 700;
    padding: 8px 18px;
    border-radius: 999px;
}

/* Catatan / Feedback Card styling */
.ap-feedback-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ap-feedback-card {
    background: #ffffff;
    border: 1px solid var(--ap-line);
    border-radius: 10px;
    padding: 16px 18px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.ap-feedback-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--ap-ink);
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--ap-line-soft);
}

.ap-feedback-item {
    margin-bottom: 14px;
}

.ap-feedback-item:last-child {
    margin-bottom: 0;
}

.ap-feedback-q {
    font-size: 13px;
    font-weight: 700;
    color: var(--ap-ink-2);
    margin-bottom: 6px;
    line-height: 1.4;
}

.ap-feedback-block {
    background: var(--ap-surface-2);
    border: 1px solid var(--ap-line);
    border-radius: 8px;
    padding: 12px 16px;
    min-height: 48px;
    font-size: 13.5px;
    color: var(--ap-ink-2);
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-word;
}

.ap-feedback-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ap-muted);
    margin-bottom: 6px;
}

@media print {
    .main-header, .sidebar, .breadcrumbs, .page-header, .action-top-bar, .modal, .btn, .badge-action, .ap-report-header button, .ap-report-header a {
        display: none !important;
    }
    .main-panel {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .content {
        padding: 0 !important;
    }
    .ap-wrap {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .ap-card {
        border: 1px solid #d1d5db !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 16px !important;
    }
    .ap-card-head {
        background-color: #f8fafc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ap-table thead th {
        background-color: #f1f5f9 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ap-subtotal td {
        background-color: #f8fafc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ap-avg-chip, .badge, .ap-summary-rating {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?php
    // Helper closures
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

    // Cycle & evaluation date format
    $cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
    $startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
    $endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

    $periodeText = '-';
    if (!empty($startDate) && !empty($endDate)) {
        $periodeText = date('d M Y', strtotime($startDate)) . ' s/d ' . date('d M Y', strtotime($endDate));
    } elseif (!empty($cycleTitle)) {
        $periodeText = $cycleTitle;
    }

    // Sections and items breakdown
    $sections = $template_snapshot['sections'] ?? [];
    $items    = $template_snapshot['items'] ?? [];

    $itemsBySection = [];
    foreach ($items as $item) {
        $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
        $itemsBySection[$secId][] = $item;
    }

    // Partition into SCORE (Performance) and BOOLEAN (Additional) sections
    $scoreSections = [];
    $booleanSections = [];

    foreach ($sections as $sec) {
        $secType = strtoupper($sec['section_type'] ?? '');
        if ($secType === 'SCORE') {
            $scoreSections[] = $sec;
        } elseif ($secType === 'BOOLEAN') {
            $booleanSections[] = $sec;
        }
    }

    // Fallbacks if section_type is not explicitly defined
    if (empty($scoreSections) && empty($booleanSections) && !empty($sections)) {
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
            } else {
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

    $allBooleanItems = [];
    foreach ($booleanSections as $bSec) {
        $bId = (int) $bSec['md_appraisal_section_id'];
        foreach ($itemsBySection[$bId] ?? [] as $it) {
            $allBooleanItems[] = $it;
        }
    }

    $finalScoreVal = (float) ($appraisal->final_score ?? 0);
    $ratingClass = 'r-good';
    if ($finalScoreVal >= 3.75) {
        $ratingClass = 'r-great';
    } elseif ($finalScoreVal >= 3.00) {
        $ratingClass = 'r-good';
    } elseif ($finalScoreVal >= 2.00) {
        $ratingClass = 'r-fair';
    } else {
        $ratingClass = 'r-low';
    }

    $backUrl = base_url('sas/penilaian');
    if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'monitoring-penilaian') !== false || strpos($_SERVER['HTTP_REFERER'], 'performance-appraisal') !== false)) {
        $backUrl = base_url('sas/monitoring-penilaian');
    }
?>

<div class="card-body card-main ap-wrap pb-5">

    <!-- TOP HEADER / ACTION BAR -->
    <div class="ap-report-header action-top-bar">
        <div>
            <a href="<?= $backUrl ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Tugas
            </a>
        </div>
        <div class="text-center flex-grow-1 mx-3">
            <h5 class="ap-report-title">
                <i class="fas fa-file-invoice mr-2 text-primary"></i>Laporan Akhir Penilaian Kinerja (PA)
            </h5>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showScoreComparisonModal('<?= (int) $appraisal_id ?>')">
                <i class="fas fa-history mr-1"></i> Riwayat Komparasi Skor
            </button>
            <a href="<?= base_url('sas/penilaian/exportPDF/' . (int) $appraisal_id) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-pdf mr-1"></i> Unduh PDF
            </a>
        </div>
    </div>

    <!-- 1. INFORMASI KARYAWAN & SIKLUS EVALUASI -->
    <div class="ap-card">
        <div class="ap-card-head">
            <h5 class="ap-card-title">Informasi Karyawan &amp; Siklus Evaluasi</h5>
            <!-- <span class="badge badge-success px-3 py-1 font-weight-bold text-uppercase" style="letter-spacing: 0.05em;">
                <i class="fas fa-check-double mr-1"></i> Finalized
            </span> -->
        </div>
        <div class="ap-card-body">
            <div class="ap-info-grid">
                <div class="ap-info-item">
                    <div class="ap-info-label">NIK / Nama</div>
                    <div class="ap-info-value"><?= esc($employee['nik'] ?? '-') ?> - <?= esc($employee['name'] ?? '-') ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Siklus Penilaian</div>
                    <div class="ap-info-value"><?= esc($cycleTitle) ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Divisi / Pos</div>
                    <div class="ap-info-value"><?= esc($employee['division_name'] ?? '-') ?> / <?= esc($employee['position_name'] ?? '-') ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Periode Evaluasi</div>
                    <div class="ap-info-value"><?= esc($periodeText) ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Cabang / Lokasi</div>
                    <div class="ap-info-value"><?= esc($employee['branch_name'] ?? '-') ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Penilai (Evaluator)</div>
                    <div class="ap-info-value"><?= esc($evaluator['name'] ?? '-') ?></div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Status Penilaian</div>
                    <div class="ap-info-value font-weight-bold text-dark">
                        <?php
                            $curSt = $appraisal->status ?? 'FINALIZED';
                            $statusLabel = \App\Models\M_Appraisal::getStatusLabel($curSt);
                        ?>
                        <?= esc($statusLabel) ?>
                    </div>
                </div>
                <div class="ap-info-item">
                    <div class="ap-info-label">Peninjau (Reviewer)</div>
                    <div class="ap-info-value"><?= esc($gm['name'] ?? '-') ?></div>
                </div>

            </div>
        </div>
    </div>

    <!-- RINGKASAN SKOR AKHIR -->
    <div class="ap-card">
        <div class="ap-card-head">
            <h5 class="ap-card-title">Ringkasan Skor Akhir</h5>
        </div>
        <div class="ap-card-body p-0">
            <div class="ap-score-summary-grid">
                <div class="ap-score-col">
                    <div class="ap-summary-label">Skor Akhir Performa</div>
                    <div class="ap-summary-score">
                        <?= number_format($finalScoreVal, 2) ?> <small>/ 4.00</small>
                    </div>
                </div>
                <div class="ap-score-col">
                    <div class="ap-summary-label">Predikat Evaluasi Resmi</div>
                    <div>
                        <!-- <span class="ap-rating ap-summary-rating <?= $ratingClass ?>"> -->
                            <?= esc($appraisal->rating ?? '-') ?>
                        <!-- </span> -->
                    </div>
                    <!-- <small class="text-muted d-block mt-2">Kategori evaluasi berdasarkan akumulasi pembobotan standar</small> -->
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DETAIL PERFORMA (SKALA 1.00 - 4.00) -->
    <div class="ap-card">
        <div class="ap-card-head">
            <h5 class="ap-card-title">2. Detail Performa (Skala 1.00 - 4.00)</h5>
            <span class="text-muted small">Evaluasi per indikator perilaku</span>
        </div>
        <div class="table-responsive">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="width: 25%;">Kompetensi</th>
                        <th style="width: 55%;">Indikator Perilaku (Key Actions)</th>
                        <th style="width: 15%;" class="text-center">Skor</th>
                    </tr>
                </thead>
                <?php if (!empty($allScoreItems)): ?>
                    <?php foreach ($allScoreItems as $cIdx => $item): ?>
                        <?php
                            $itemId      = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id']);
                            $cfg         = (array) ($item['config'] ?? []);
                            $compTitle   = !empty($cfg['competency']) ? $cfg['competency'] : (!empty($item['title']) ? $item['title'] : ('Kompetensi ' . ($cIdx + 1)));
                            $definition  = !empty($item['description']) ? $item['description'] : (!empty($cfg['definition']) ? $cfg['definition'] : '-');
                            $actions     = $parseActions($cfg['key_actions'] ?? null);

                            $resp          = $responses_by_item_id[$itemId] ?? null;
                            $activePayload = [];
                            if ($resp && !empty($resp->gm_response)) {
                                $activePayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                            } elseif ($resp && !empty($resp->evaluator_response)) {
                                $activePayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                            }

                            $savedActionScores = $activePayload['action_scores'] ?? [];
                            $savedItemScore    = null;
                            if (isset($activePayload['score']) && $activePayload['score'] !== '') {
                                $savedItemScore = (float) $activePayload['score'];
                            } elseif ($resp && $resp->score !== null) {
                                $savedItemScore = (float) $resp->score;
                            }

                            $hasKeyActions = !empty($actions);
                            $totalActions  = $hasKeyActions ? count($actions) : 1;
                            $actionSum     = 0;
                        ?>
                        <tbody>
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
                                        $actionSum += $actionScore;

                                        $letter = chr(97 + $aIdx);
                                        $hasLetterPrefix = preg_match('/^[a-z0-9][\.\)]\s*/i', $actionText);
                                    ?>
                                    <tr>
                                        <?php if ($aIdx === 0): ?>
                                            <td rowspan="<?= $totalActions ?>" class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                                <?= $cIdx + 1 ?>
                                            </td>
                                            <td rowspan="<?= $totalActions ?>" class="ap-comp-cell">
                                                <div class="ap-comp-name mb-1"><?= esc($compTitle) ?></div>
                                                <div class="ap-def"><span class="font-weight-bold">Def:</span> <?= esc($definition) ?></div>
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

                                        <td class="text-center align-middle font-weight-bold text-dark">
                                            <?= number_format($actionScore, 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php
                                    $actionScore = ($savedItemScore !== null) ? $savedItemScore : 3.00;
                                    $actionSum   = $actionScore;
                                ?>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                        <?= $cIdx + 1 ?>
                                    </td>
                                    <td class="ap-comp-cell">
                                        <div class="ap-comp-name mb-1"><?= esc($compTitle) ?></div>
                                        <div class="ap-def"><span class="font-weight-bold">Def:</span> <?= esc($definition) ?></div>
                                    </td>
                                    <td>
                                        <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-dark">
                                        <?= number_format($actionScore, 2) ?>
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
                                <td colspan="3" class="ap-subtotal-label font-weight-bold">
                                    Rata-rata <?= esc($compTitle) ?>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="ap-avg-chip font-weight-bold">
                                        <?= number_format($compAvg, 2) ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Tidak ada data butir penilaian performa.
                            </td>
                        </tr>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- 3. KOMPETENSI TAMBAHAN -->
    <div class="ap-card">
        <div class="ap-card-head">
            <h5 class="ap-card-title">3. Kompetensi Tambahan</h5>
            <span class="text-muted small">Kriteria perilaku tambahan</span>
        </div>
        <div class="table-responsive">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="width: 25%;">Kompetensi</th>
                        <th style="width: 55%;">Indikator Perilaku</th>
                        <th style="width: 15%;" class="text-center">Terbukti?</th>
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

                            $checkedActions = isset($gmPayload['checked_actions'])
                                ? $gmPayload['checked_actions']
                                : ($evalPayload['checked_actions'] ?? []);
                            $booleanValue   = $gmPayload['boolean_value'] ?? $evalPayload['boolean_value'] ?? null;

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
                                    <tr>
                                        <?php if ($kIdx === 0): ?>
                                            <td rowspan="<?= $totalActions ?>" class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                                <?= $bIdx + 1 ?>
                                            </td>
                                            <td rowspan="<?= $totalActions ?>" class="ap-comp-cell">
                                                <div class="ap-comp-name mb-1"><?= esc($bTitle) ?></div>
                                                <?php if (!empty($bDef) && $bDef !== '-'): ?>
                                                    <div class="ap-def"><?= esc($bDef) ?></div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <td>
                                            <div class="ap-action-text">
                                                <span class="ap-action-letter"><?= chr(97 + $kIdx) ?>.</span><?= esc($ka) ?>
                                            </div>
                                        </td>

                                        <td class="text-center align-middle">
                                            <input type="checkbox" <?= $isActionChecked ? 'checked' : '' ?> disabled style="width: 18px; height: 18px; cursor: default; accent-color: #2563eb;">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php
                                    $isProven = in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true);
                                ?>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                        <?= $bIdx + 1 ?>
                                    </td>
                                    <td class="ap-comp-cell">
                                        <div class="ap-comp-name mb-1"><?= esc($bTitle) ?></div>
                                        <?php if (!empty($bDef) && $bDef !== '-'): ?>
                                            <div class="ap-def"><span class="font-weight-bold">Def:</span> <?= esc($bDef) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <input type="checkbox" <?= $isProven ? 'checked' : '' ?> disabled style="width: 18px; height: 18px; cursor: default; accent-color: #2563eb;">
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Tidak ada data kompetensi tambahan.
                            </td>
                        </tr>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- 4. CATATAN & FEEDBACK PENILAIAN -->
    <div class="ap-card">
        <div class="ap-card-head">
            <h5 class="ap-card-title">4. Catatan &amp; Evaluasi</h5>
        </div>
        <div class="ap-card-body">
            <div class="ap-feedback-section">
                <!-- Catatan Evaluator -->
                <div class="ap-feedback-card">
                    <div class="ap-feedback-item">
                        <div class="ap-feedback-q">
                            1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                        </div>
                        <div class="ap-feedback-block"><?= esc(!empty($appraisal->evaluator_comment) ? trim($appraisal->evaluator_comment) : '(Tidak ada catatan)') ?></div>
                    </div>

                    <div class="ap-feedback-item">
                        <div class="ap-feedback-q">
                            2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                        </div>
                        <div class="ap-feedback-block"><?= esc(!empty($appraisal->development_plan) ? trim($appraisal->development_plan) : '(Tidak ada catatan)') ?></div>
                    </div>
                </div>

                <?php if (!empty($appraisal->employee_comment)): ?>
                <!-- Catatan Karyawan / Duta -->
                <div class="ap-feedback-card">
                    <div class="ap-feedback-header">
                        <i class="fas fa-signature text-success mr-1"></i>
                        <span>Tanggapan / Catatan Konfirmasi Karyawan (<?= esc($employee['name'] ?? 'Karyawan') ?>)</span>
                    </div>
                    <div class="ap-feedback-item">
                        <div class="ap-feedback-block"><?= esc(trim($appraisal->employee_comment)) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- AUDIT LOG MODAL -->
<div class="modal fade" id="modal_audit_log" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 14px; border: 0;">
            <div class="modal-header" style="border-bottom: 1px solid var(--ap-line);">
                <h5 class="modal-title font-weight-bold" style="color: var(--ap-ink); font-size: 16px;">
                    <i class="fas fa-history mr-2 text-primary"></i>Riwayat Alur Penilaian (Audit Trail)
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div id="audit_timeline_container">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2 mb-0">Memuat riwayat log...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ap-line);">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var mp = document.querySelector('.main-panel');
    if (mp) mp.classList.remove('is-loading');
})();

$(document).ready(function () {
    $('.main-panel').removeClass('is-loading');
    $('.new_form, .btn_requery, .card-header .float-right').hide();
    var appraisalId = <?= (int) $appraisal_id ?>;

    window.showAuditLogModal = function (id) {
        var targetId = id || appraisalId;
        $('#modal_audit_log').modal('show');
        $('#audit_timeline_container').html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2 mb-0">Memuat riwayat log...</p>
            </div>
        `);

        $.ajax({
            url: '<?= base_url('penilaian/auditLogs') ?>/' + targetId,
            type: 'GET',
            dataType: 'JSON',
            success: function (res) {
                var logs = [];
                if (res && res[0] && res[0].data) {
                    logs = res[0].data;
                } else if (res && res.data) {
                    logs = res.data;
                }

                if (!logs || logs.length === 0) {
                    $('#audit_timeline_container').html(`
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <p class="mb-0">Belum ada riwayat aktivitas untuk form penilaian ini.</p>
                        </div>
                    `);
                    return;
                }

                var html = '<ul class="timeline" style="list-style: none; padding-left: 0;">';
                $.each(logs, function (i, log) {
                    var actionTitle = log.action || '-';
                    var timeStr = log.created_at ? log.created_at : '-';
                    var actor = log.actor_name ? (log.actor_name + (log.actor_username ? ' (' + log.actor_username + ')' : '')) : ('User ID: ' + (log.actor_id || '-'));
                    var notes = log.notes || '-';
                    var statusChange = (log.from_status || '-') + ' &rarr; ' + (log.to_status || '-');

                    html += `
                        <li class="timeline-item mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-primary font-weight-bold text-uppercase px-2 py-1">${actionTitle}</span>
                                <small class="text-muted"><i class="far fa-clock mr-1"></i>${timeStr}</small>
                            </div>
                            <div class="small text-muted mb-1"><strong>Aktor:</strong> ${actor}</div>
                            <div class="small text-muted mb-1"><strong>Transisi Status:</strong> <span class="text-dark font-weight-bold">${statusChange}</span></div>
                            <div class="small p-2 bg-light rounded border text-dark mt-1">${notes}</div>
                        </li>
                    `;
                });
                html += '</ul>';

                $('#audit_timeline_container').html(html);
            },
            error: function () {
                $('#audit_timeline_container').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0">Gagal memuat log riwayat aktivitas.</p>
                    </div>
                `);
            }
        });
    };
});
</script>

<?= $this->endSection() ?>
