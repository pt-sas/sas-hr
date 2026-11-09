<?php
    $cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
    $startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
    $endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

    $periodeText = '-';
    if (!empty($startDate) && !empty($endDate)) {
        $periodeText = date('d M Y', strtotime($startDate)) . ' s/d ' . date('d M Y', strtotime($endDate));
    } elseif (!empty($cycleTitle)) {
        $periodeText = $cycleTitle;
    }

    $empName = esc($employee['name'] ?? '-');
    $empNik  = esc($employee['nik'] ?? '-');
    $statusText = esc($appraisal->status ?? 'DRAFT_EVALUATOR');

    // Separate score sections (performa) and boolean sections (tambahan)
    $scoreSections   = [];
    $booleanSections = [];
    foreach ($sections as $s) {
        if (($s['section_type'] ?? '') === 'BOOLEAN') {
            $booleanSections[] = $s;
        } else {
            $scoreSections[] = $s;
        }
    }
?>

<div class="ap-wrap" style="max-width: 100%;">

    <!-- MODAL TOP BAR -->
    <div class="modal-header d-flex justify-content-between align-items-center" style="border-bottom: 2px solid #94a3b8; padding: 14px 20px; background: #e2eaf4;">
        <h5 class="modal-title font-weight-bold d-flex align-items-center mb-0" style="font-size: 16px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.3px;">
            Riwayat &amp; Komparasi Skor Penilaian
        </h5>
        <button type="button" class="btn btn-sm btn-close-preview" data-dismiss="modal" aria-label="Tutup">
            <i class="fas fa-times mr-1"></i> Tutup
        </button>
    </div>

    <!-- MODAL BODY -->
    <div class="modal-body p-3 p-md-4 ap-modal-uniform-body">

        <!-- =================================================================== -->
        <!-- 1. INFORMASI KARYAWAN & SIKLUS EVALUASI                             -->
        <!-- =================================================================== -->
        <div class="ap-card mb-4">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <div class="ap-card-title-group d-flex align-items-center">
                    <h5 class="ap-card-title d-inline-block mb-0 mr-2">I. Informasi Karyawan &amp; Siklus Evaluasi PA</h5>
                    <span class="ap-mode is-gm">STATUS: KOMPARASI SKOR</span>
                </div>
            </div>
            <div class="ap-card-body">
                <div class="ap-info-grid">
                    <div class="ap-info-item">
                        <div class="ap-info-label">NIK / Nama</div>
                        <div class="ap-info-value highlight"><?= $empNik ?> &middot; <?= mb_strtoupper($empName, 'UTF-8') ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Siklus Penilaian</div>
                        <div class="ap-info-value"><?= esc($cycleTitle) ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Divisi / Posisi</div>
                        <div class="ap-info-value"><?= esc($employee['division_name'] ?? '-') ?> / <?= esc($employee['position_name'] ?? '-') ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Periode Evaluasi</div>
                        <div class="ap-info-value"><?= esc($periodeText) ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Cabang</div>
                        <div class="ap-info-value"><?= esc($employee['branch_name'] ?? '-') ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Penilai</div>
                        <div class="ap-info-value"><?= esc(mb_strtoupper($evaluator['name'] ?? '-', 'UTF-8')) ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Status Penilaian</div>
                        <div class="ap-info-value"><?= $statusText ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Peninjau</div>
                        <div class="ap-info-value"><?= esc(mb_strtoupper($gm['name'] ?? '-', 'UTF-8')) ?></div>
                    </div>

                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 2. PERFORMA                                                         -->
        <!-- =================================================================== -->
        <div class="ap-card mb-4">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <h5 class="ap-card-title mb-0">II. Performa</h5>
                    <span class="ap-category-badge">Skala 0.00 – 4.00</span>
                </div>
            </div>

            <!-- Sticky-style Score Bar with Penilai vs Reviewer comparison -->
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
                        <?php if ($is_gm_reviewed && $gm_overall_score !== null): ?>
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

            <div class="table-responsive">
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
                        <?php if (!empty($scoreSections)): ?>
                            <?php $scoreItemNumber = 0; ?>
                            <?php foreach ($scoreSections as $sec): ?>
                                <?php foreach ($sec['items'] as $item): ?>
                                    <?php
                                        $scoreItemNumber++;
                                        $actions      = $item['actions'] ?? [];
                                        $hasActions   = !empty($actions);
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
                                                        <div class="ap-comp-name mb-1">
                                                            <?= $itemTitle ?>
                                                        </div>
                                                        <?php if ($itemDef !== '-' && !empty($itemDef)): ?>
                                                            <div class="ap-def">
                                                                <?= $itemDef ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                                <td>
                                                    <div class="ap-action-text">
                                                        <span class="ap-action-letter"><?= esc($act['letter']) ?>.</span>
                                                        <?= esc($act['text']) ?>
                                                    </div>
                                                </td>

                                                <!-- Skor Penilai -->
                                                <td class="text-center align-middle font-weight-bold">
                                                    <?= number_format((float) $evalScore, 2) ?>
                                                </td>

                                                <!-- Skor Reviewer (Red/Green Diff) -->
                                                <td class="text-center align-middle font-weight-bold">
                                                    <?php if ($gmScore !== null): ?>
                                                        <span style="<?= $gmStyle ?>">
                                                            <?= number_format((float) $gmScore, 2) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted font-weight-normal">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td class="text-center font-weight-bold align-top ap-comp-cell" style="width: 50px;">
                                                <?= $scoreItemNumber ?>.
                                            </td>
                                            <td class="ap-comp-cell">
                                                <div class="ap-comp-name mb-1">
                                                    <?= $itemTitle ?>
                                                </div>
                                                <?php if ($itemDef !== '-' && !empty($itemDef)): ?>
                                                    <div class="ap-def">
                                                        <?= $itemDef ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                            </td>
                                            <td class="text-center align-middle font-weight-bold">
                                                <?= number_format((float) $evalSubtotal, 2) ?>
                                            </td>
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
                                                    <span style="<?= $singleColor ?>">
                                                        <?= number_format((float) $gmSubtotal, 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted font-weight-normal">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- Subtotal Row -->
                                    <tr class="ap-subtotal">
                                        <td colspan="3" class="ap-subtotal-label font-weight-bold text-dark">
                                            Rata-rata <?= $itemTitle ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="ap-avg-chip font-weight-bold">
                                                <?= number_format((float) $evalSubtotal, 2) ?>
                                            </span>
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
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 3. KOMPETENSI TAMBAHAN                                              -->
        <!-- =================================================================== -->
        <div class="ap-card mb-4">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center" style="gap: 12px;">
                    <h5 class="ap-card-title mb-0">III. Kompetensi Tambahan</h5>
                    <span class="ap-category-badge">Ya / Tidak</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="ap-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">NO</th>
                            <th style="width: 20%;">KOMPETENSI</th>
                            <th style="width: 45%;">KEY ACTIONS (INDIKATOR PERILAKU)</th>
                            <th style="width: 15%; text-align: center;">PENILAI</th>
                            <th style="width: 15%; text-align: center;">PENINJAU</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($booleanSections)): ?>
                            <?php $boolItemNumber = 0; ?>
                            <?php foreach ($booleanSections as $bSec): ?>
                                <?php foreach ($bSec['items'] as $bItem): ?>
                                    <?php
                                        $boolItemNumber++;
                                        $bActions     = $bItem['actions'] ?? [];
                                        $hasBActions  = !empty($bActions);
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
                                                        <div class="ap-comp-name mb-1">
                                                            <?= $bTitle ?>
                                                        </div>
                                                        <?php if ($bDef !== '-' && !empty($bDef)): ?>
                                                            <div class="ap-def">
                                                                <?= $bDef ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>

                                                <td>
                                                    <div class="ap-action-text">
                                                        <span class="ap-action-letter"><?= esc($bAct['letter']) ?>.</span>
                                                        <?= esc($bAct['text']) ?>
                                                    </div>
                                                </td>

                                                <!-- Penilai Check [ ✓ ] or [   ] -->
                                                <td class="text-center align-middle">
                                                    <?php if (!empty($bAct['eval_checked'])): ?>
                                                        <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                                            &check; 
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 13px; font-weight: 600;">
                                                            &mdash;
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Reviewer Check [ ✓ ] or [   ] -->
                                                <td class="text-center align-middle">
                                                    <?php if ($bAct['gm_checked'] !== null): ?>
                                                        <?php if (!empty($bAct['gm_checked'])): ?>
                                                            <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                                                &check; 
                                                            </span>
                                                        <?php else: ?>
                                                            <?php if (!empty($bAct['is_overridden'])): ?>
                                                                <span style="color: #dc2626; font-weight: 800; font-size: 13px; background: #fef2f2; border: 1.5px solid #fecaca; padding: 3px 10px; border-radius: 4px;">
                                                                    &times;
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted" style="font-size: 13px; font-weight: 600;">
                                                                    &mdash;
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 13px; font-weight: 600;">
                                                            &mdash;
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td class="text-center font-weight-bold align-top ap-comp-cell" style="width: 50px;">
                                                <?= $boolItemNumber ?>.
                                            </td>
                                            <td class="ap-comp-cell">
                                                <div class="ap-comp-name mb-1">
                                                    <?= $bTitle ?>
                                                </div>
                                                <?php if ($bDef !== '-' && !empty($bDef)): ?>
                                                    <div class="ap-def">
                                                        <?= $bDef ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="ap-action-text text-muted font-italic">Indikator umum</div>
                                            </td>
                                            <?php $singleAct = $bActions[0] ?? []; ?>
                                            <td class="text-center align-middle">
                                                <?php if (!empty($singleAct['eval_checked'])): ?>
                                                    <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                                        &check; 
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">
                                                        &mdash;
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <?php if (isset($singleAct['gm_checked']) && $singleAct['gm_checked'] !== null): ?>
                                                    <?php if (!empty($singleAct['gm_checked'])): ?>
                                                        <span style="color: #16a34a; font-weight: 800; font-size: 14px; background: #f0fdf4; border: 1.5px solid #bbf7d0; padding: 3px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                                            &check; 
                                                        </span>
                                                    <?php else: ?>
                                                        <?php if (!empty($singleAct['is_overridden'])): ?>
                                                            <span style="color: #dc2626; font-weight: 800; font-size: 13px; background: #fef2f2; border: 1.5px solid #fecaca; padding: 3px 10px; border-radius: 4px;">
                                                                &times;
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted" style="font-size: 13px; font-weight: 600;">
                                                                &mdash;
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 13px; font-weight: 600;">
                                                        &mdash;
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Tidak ada butir kriteria tambahan dalam template penilaian ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- 4. CATATAN & FEEDBACK                                               -->
        <!-- =================================================================== -->
        <div class="ap-card mb-0">
            <div class="ap-card-head">
                <h5 class="ap-card-title">IV. Catatan</h5>
            </div>
            <div class="ap-card-body p-0">
                <div class="ap-feedback-section">
                    <div class="ap-feedback-card">
                        <div class="ap-feedback-item">
                            <div class="ap-feedback-q">
                                1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                            </div>
                            <div class="ap-feedback-block"><?= !empty($evaluator_comment) ? esc(trim($evaluator_comment)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                        </div>

                        <div class="ap-feedback-item">
                            <div class="ap-feedback-q">
                                2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                            </div>
                            <div class="ap-feedback-block"><?= !empty($development_plan) ? esc(trim($development_plan)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                        </div>
                    </div>

                    <?php if (!empty($appraisal->employee_comment)): ?>
                    <div class="ap-feedback-card">
                        <div class="ap-feedback-header">
                            <i class="fas fa-signature text-success mr-1"></i>
                            <span>Tanggapan / Catatan Konfirmasi Karyawan (<?= $empName ?>)</span>
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

    <!-- MODAL FOOTER -->
    <!-- <div class="modal-footer d-flex justify-content-end" style="border-top: 2px solid #94a3b8; padding: 12px 24px; background: #e2eaf4;">
        <button type="button" class="btn btn-sm btn-close-preview" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Tutup (Esc)
        </button>
    </div> -->

</div>
