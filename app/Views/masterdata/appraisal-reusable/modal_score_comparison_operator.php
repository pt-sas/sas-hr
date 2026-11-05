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

    $empName    = esc($employee['name'] ?? '-');
    $empNik     = esc($employee['nik'] ?? '-');
    $statusText = esc($appraisal->status ?? 'DRAFT_EVALUATOR');

    $isReviewed = !empty($is_gm_reviewed);

    // Totals for Rekap
    $totBobot    = 0.0;
    $totEvalSkor = 0.0;
    $totEvalNilai = 0.0;
    $totGmSkor   = 0.0;
    $totGmNilai  = 0.0;

    foreach ($weighted_items ?? [] as $w) {
        $b = (float) ($w['bobot'] ?? 1.0);
        $es = (float) ($w['eval_score'] ?? 0.0);
        $en = (float) ($w['eval_nilai'] ?? ($b * $es));
        $totBobot += $b;
        $totEvalSkor += $es;
        $totEvalNilai += $en;

        if ($isReviewed && isset($w['gm_score']) && $w['gm_score'] !== null) {
            $gs = (float) $w['gm_score'];
            $gn = (float) ($w['gm_nilai'] ?? ($b * $gs));
            $totGmSkor += $gs;
            $totGmNilai += $gn;
        }
    }
?>

<div class="ap-wrap" style="max-width: 100%;">

    <!-- MODAL TOP BAR -->
    <div class="modal-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid var(--ap-line); padding: 16px 24px; background: var(--ap-surface);">
        <h5 class="modal-title font-weight-bold text-dark d-flex align-items-center mb-0" style="font-size: 16px;">
            Riwayat &amp; Komparasi Skor Penilaian (Operator / Pelaksana)
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup" style="opacity: 0.7; font-size: 24px;">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <!-- MODAL BODY -->
    <div class="modal-body p-4" style="background: var(--ap-surface-2); max-height: calc(88vh - 120px); overflow-y: auto;">

        <!-- 1. INFORMASI KARYAWAN & SIKLUS EVALUASI -->
        <div class="ap-card mb-4">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h5 class="ap-card-title d-inline-block mb-0 mr-2">Informasi Karyawan &amp; Siklus Evaluasi</h5>
                    <span class="ap-mode is-gm">Mode: <?= esc($statusText === 'FINALIZED' ? 'FINAL' : 'GM') ?></span>
                </div>
            </div>
            <div class="ap-card-body">
                <div class="ap-info-grid">
                    <div class="ap-info-item">
                        <div class="ap-info-label">NIK / Nama</div>
                        <div class="ap-info-value"><?= $empNik ?> &middot; <?= $empName ?></div>
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
                        <div class="ap-info-label">Cabang / Lokasi</div>
                        <div class="ap-info-value"><?= esc($employee['branch_name'] ?? '-') ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Penilai (Evaluator)</div>
                        <div class="ap-info-value"><?= esc($evaluator['name'] ?? '-') ?></div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Status Penilaian</div>
                        <div class="ap-info-value">
                            <span class="badge badge-light border text-dark font-weight-bold"><?= $statusText ?></span>
                        </div>
                    </div>
                    <div class="ap-info-item">
                        <div class="ap-info-label">Peninjau (Reviewer)</div>
                        <div class="ap-info-value"><?= esc($gm['name'] ?? '-') ?></div>
                    </div>

                </div>
            </div>
        </div>

        <!-- 2. KOMPARASI REKAP PENILAIAN KINERJA (WITHOUT STATUS COLUMN) -->
        <div class="ap-card mb-4">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="ap-card-title mb-0">Komparasi Rekap Penilaian Kinerja</h5>
                    <small class="text-muted d-block mt-1">Perbandingan rekapitulasi penilaian penilai vs peninjau</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="ap-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 50px; text-align: center; vertical-align: middle;">No</th>
                            <th rowspan="2" style="width: 35%; vertical-align: middle;">Aspek Penilaian</th>
                            <th rowspan="2" style="width: 10%; text-align: center; vertical-align: middle;">Bobot</th>
                            <th colspan="2" style="text-align: center; background: #eff6ff; border-left: 1px solid #bfdbfe;">PENILAI</th>
                            <th colspan="2" style="text-align: center; background: #f0fdf4; border-left: 1px solid #bbf7d0;">REVIEWER</th>
                        </tr>
                        <tr>
                            <th style="width: 12.5%; text-align: center; background: #eff6ff; border-left: 1px solid #bfdbfe;">Skor</th>
                            <th style="width: 12.5%; text-align: center; background: #eff6ff;">Nilai</th>
                            <th style="width: 12.5%; text-align: center; background: #f0fdf4; border-left: 1px solid #bbf7d0;">Skor</th>
                            <th style="width: 12.5%; text-align: center; background: #f0fdf4;">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($weighted_items)): ?>
                            <?php foreach ($weighted_items as $idx => $row): ?>
                                <?php
                                    $b  = (float) ($row['bobot'] ?? 1.0);
                                    $es = isset($row['eval_score']) && $row['eval_score'] !== null ? (float) $row['eval_score'] : null;
                                    $en = isset($row['eval_nilai']) && $row['eval_nilai'] !== null ? (float) $row['eval_nilai'] : ($es !== null ? ($b * $es) : null);

                                    $gs = isset($row['gm_score']) && $row['gm_score'] !== null ? (float) $row['gm_score'] : null;
                                    $gn = isset($row['gm_nilai']) && $row['gm_nilai'] !== null ? (float) $row['gm_nilai'] : ($gs !== null ? ($b * $gs) : null);
                                ?>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                        <?= $idx + 1 ?>
                                    </td>
                                    <td class="align-middle font-weight-600 text-dark">
                                        <?= esc($row['title'] ?? ('Aspek ' . ($idx + 1))) ?>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-dark">
                                        <?= round($b) == $b ? (int)$b : number_format($b, 1) ?>
                                    </td>

                                    <!-- PENILAI -->
                                    <td class="text-center align-middle font-weight-bold" style="background: #f8fafc; border-left: 1px solid var(--ap-line-soft);">
                                        <?= $es !== null ? (round($es) == $es ? (int)$es : number_format($es, 1)) : '-' ?>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-primary" style="background: #f8fafc;">
                                        <?= $en !== null ? (round($en) == $en ? (int)$en : number_format($en, 1)) : '-' ?>
                                    </td>

                                    <!-- REVIEWER -->
                                    <td class="text-center align-middle font-weight-bold" style="background: #fdfefe; border-left: 1px solid var(--ap-line-soft);">
                                        <?php if ($isReviewed && $gs !== null): ?>
                                            <?= round($gs) == $gs ? (int)$gs : number_format($gs, 1) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold text-success" style="background: #fdfefe;">
                                        <?php if ($isReviewed && $gn !== null): ?>
                                            <?= round($gn) == $gn ? (int)$gn : number_format($gn, 1) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- TOTAL ROW -->
                            <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                                <td colspan="2" class="text-center align-middle text-uppercase" style="letter-spacing: 0.05em; font-size: 13px;">
                                    TOTAL
                                </td>
                                <td class="text-center align-middle">
                                    <?= round($totBobot) == $totBobot ? (int)$totBobot : number_format($totBobot, 1) ?>
                                </td>
                                <td class="text-center align-middle" style="border-left: 1px solid #cbd5e1;">
                                    <?= round($totEvalSkor) == $totEvalSkor ? (int)$totEvalSkor : number_format($totEvalSkor, 1) ?>
                                </td>
                                <td class="text-center align-middle text-primary">
                                    <?= round($totEvalNilai) == $totEvalNilai ? (int)$totEvalNilai : number_format($totEvalNilai, 1) ?>
                                </td>
                                <td class="text-center align-middle" style="border-left: 1px solid #cbd5e1;">
                                    <?= $isReviewed ? (round($totGmSkor) == $totGmSkor ? (int)$totGmSkor : number_format($totGmSkor, 1)) : '-' ?>
                                </td>
                                <td class="text-center align-middle text-success">
                                    <?= $isReviewed ? (round($totGmNilai) == $totGmNilai ? (int)$totGmNilai : number_format($totGmNilai, 1)) : '-' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Tidak ada data aspek penilaian.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. KOMPARASI KRITERIA TAMBAHAN -->
        <div class="ap-card mb-4">
            <div class="ap-card-head d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="ap-card-title mb-0">Komparasi Kriteria Tambahan</h5>
                    <small class="text-muted d-block mt-1">Perbandingan checklist kriteria tambahan antara penilai dan peninjau</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="ap-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 65%;">Kriteria</th>
                            <th style="width: 15%; text-align: center; background: #eff6ff;">Penilai</th>
                            <th style="width: 15%; text-align: center; background: #f0fdf4;">Peninjau</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($single_items)): ?>
                            <?php foreach ($single_items as $idx => $row): ?>
                                <?php
                                    $isOtherRow = !empty($row['is_other']);
                                    $evalChecked = !empty($row['eval_checked']);
                                    $gmChecked   = !empty($row['gm_checked']);
                                    $evalOther   = $row['eval_other_text'] ?? '';
                                    $gmOther     = $row['gm_other_text'] ?? '';
                                ?>
                                <tr>
                                    <td class="text-center align-middle font-weight-bold" style="background: var(--ap-surface);">
                                        <?= $idx + 1 ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php if ($isOtherRow): ?>
                                            <div>
                                                <span class="font-weight-600 text-dark">Lainnya :</span>
                                                <?php if (!empty($evalOther) || !empty($gmOther)): ?>
                                                    <div class="small mt-1 text-muted">
                                                        <?php if (!empty($evalOther)): ?>
                                                            <div><span class="font-weight-bold text-primary">Penilai:</span> <?= esc($evalOther) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($isReviewed && !empty($gmOther) && $gmOther !== $evalOther): ?>
                                                            <div><span class="font-weight-bold text-success">Reviewer:</span> <?= esc($gmOther) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted font-italic">-</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-weight-600 text-dark"><?= esc($row['title'] ?? ('Kriteria ' . ($idx + 1))) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Penilai Checked -->
                                    <td class="text-center align-middle py-3" style="background: #f8fafc; border-left: 1px solid var(--ap-line-soft);">
                                        <span class="operator-comparison-chip <?= $evalChecked ? 'is-checked' : 'is-empty' ?>">
                                            <?= $evalChecked ? 'v' : '-' ?>
                                        </span>
                                    </td>

                                    <!-- Reviewer Checked -->
                                    <td class="text-center align-middle py-3" style="background: #fdfefe; border-left: 1px solid var(--ap-line-soft);">
                                        <?php if ($isReviewed): ?>
                                            <span class="operator-comparison-chip <?= $gmChecked ? 'is-checked-gm' : 'is-empty' ?>">
                                                <?= $gmChecked ? 'v' : '-' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Tidak ada data kriteria tambahan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. CATATAN -->
        <div class="ap-card mb-2">
            <div class="ap-card-head">
                <h5 class="ap-card-title">4. Catatan</h5>
            </div>
            <div class="ap-card-body p-4">
                <div class="form-group mb-3">
                    <label class="font-weight-bold text-dark small mb-2 d-block">
                        1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?
                    </label>
                    <div class="p-3 rounded bg-light border text-dark" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;"><?= !empty($evaluator_comment) ? esc(trim($evaluator_comment)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-dark small mb-2 d-block">
                        2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?
                    </label>
                    <div class="p-3 rounded bg-light border text-dark" style="white-space: pre-wrap; font-size: 13.5px; line-height: 1.5; min-height: 50px;"><?= !empty($development_plan) ? esc(trim($development_plan)) : '<span class="text-muted font-italic">(Tidak ada catatan)</span>' ?></div>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL FOOTER -->
    <div class="modal-footer d-flex justify-content-end" style="border-top: 1px solid var(--ap-line); padding: 12px 24px; background: var(--ap-surface);">
        <button type="button" class="btn btn-secondary btn-round btn-sm" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Tutup
        </button>
    </div>
</div>

<style>
.operator-comparison-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
}
.operator-comparison-chip.is-checked {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #2563eb;
}
.operator-comparison-chip.is-checked-gm {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #16a34a;
}
.operator-comparison-chip.is-empty {
    color: #94a3b8;
}
</style>
