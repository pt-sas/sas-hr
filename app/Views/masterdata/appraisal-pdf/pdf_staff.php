<?php
/**
 * View template for Staff / Non-Operator Performance Appraisal PDF Report
 * Rendered and converted via TCPDF, adhering uniformly to standard corporate styling
 */

$cycleTitle = is_object($cycle) ? ($cycle->title ?? '-') : ($cycle['title'] ?? '-');
$rawSem     = is_object($cycle) ? ($cycle->semester ?? '1') : ($cycle['semester'] ?? '1');
$cycleTitle = $cycleTitle . (!empty($rawSem) ? ' - Semester ' . $rawSem : '');
$startDate  = is_object($cycle) ? ($cycle->start_date ?? null) : ($cycle['start_date'] ?? null);
$endDate    = is_object($cycle) ? ($cycle->end_date ?? null) : ($cycle['end_date'] ?? null);

$periodeText = '-';
if (!empty($startDate) && !empty($endDate)) {
    $periodeText = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
} elseif (!empty($cycleTitle)) {
    $periodeText = $cycleTitle;
}

// Helper to parse key actions or indicators
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

// Helper to clean action text from leading numbering/lettering (e.g., 'a. ', '1. ')
$cleanActionText = function (string $text): string {
    return preg_replace('/^[a-z0-9][\.\)]\s*/i', '', trim($text));
};

// Partition sections into SCORE and BOOLEAN
$sections = $snapshot['sections'] ?? [];
$items    = $snapshot['items'] ?? [];

$itemsBySection = [];
foreach ($items as $item) {
    $secId = (int) ($item['md_appraisal_section_id'] ?? 0);
    $itemsBySection[$secId][] = $item;
}

$scoreSections   = [];
$booleanSections = [];

foreach ($sections as $sec) {
    $secType = strtoupper($sec['section_type'] ?? '');
    if ($secType === 'SCORE') {
        $scoreSections[] = $sec;
    } elseif ($secType === 'BOOLEAN') {
        $booleanSections[] = $sec;
    }
}

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

// Track indicators for Detail Kinerja & Pencapaian Kompetensi
$itemsNilai4             = [];
$itemsNilai1             = [];
$itemsKompetensiTambahan = [];
?>

<style>
    table { border-collapse: collapse; font-family: helvetica, sans-serif; font-size: 8pt; color: #1e293b; }
    .sec-title { background-color: #e2e8f0; font-weight: bold; font-size: 8.5pt; color: #0f172a; }
    .lbl-cell { background-color: #f8fafc; font-weight: bold; color: #334155; }
    .eval-box { background-color: #ffffff; border: 0.5px solid #cbd5e1; padding: 5px; }
</style>

<!-- HEADER -->
<table width="100%" cellpadding="3" cellspacing="0" border="0" style="border-bottom: 1.5px solid #0f172a;">
    <tr>
        <td width="26%" style="font-size: 7pt; font-weight: bold; color: #0f172a; vertical-align: middle;">
            PT. SAHABAT ABADI SEJAHTERA
        </td>
        <td width="48%" align="center" style="font-size: 9pt; font-weight: bold; color: #0f172a; vertical-align: middle;">
            FORM EVALUASI KINERJA KARYAWAN<br>
            <span style="font-size: 8pt; font-weight: normal; color: #475569;">PERFORMANCE APPRAISAL (PA) REPORT</span>
        </td>
        <td width="26%" align="right" style="font-size: 7.5pt; color: #334155; vertical-align: middle;">
            <table cellpadding="1" cellspacing="0" border="0" width="100%">
                <tr>
                    <td width="15%" align="left"><b>No</b></td>
                    <td width="5%" align="center"><b>:</b></td>
                    <td width="80%" align="left"><?= esc($docNo) ?></td>
                </tr>
                <tr>
                    <td width="15%" align="left"><b>Hal</b></td>
                    <td width="5%" align="center"><b>:</b></td>
                    <td width="80%" align="left"><?= isset($pdf) ? ($pdf->getAliasNumPage() . ' dari ' . $pdf->getAliasNbPages()) : '1 dari 1' ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br>

<!-- I. DATA KARYAWAN & SIKLUS PENILAIAN -->
<table width="100%" cellpadding="3.5" cellspacing="0" border="0.5">
    <tr>
        <td colspan="4" class="sec-title">I. DATA KARYAWAN &amp; SIKLUS PENILAIAN</td>
    </tr>
    <tr>
        <td width="16%" class="lbl-cell">NIK</td>
        <td width="34%"><?= esc($employee['nik'] ?? '-') ?></td>
        <td width="18%" class="lbl-cell">Siklus Penilaian</td>
        <td width="32%"><?= esc($cycleTitle) ?></td>
    </tr>
    <tr>
        <td class="lbl-cell">Nama Karyawan</td>
        <td><b><?= esc(mb_strtoupper($employee['name'] ?? '-', 'UTF-8')) ?></b></td>
        <td class="lbl-cell">Periode Evaluasi</td>
        <td><?= esc($periodeText) ?></td>
    </tr>
    <tr>
        <td class="lbl-cell">Divisi / Posisi</td>
        <td><?= esc($employee['division_name'] ?? '-') ?> / <?= esc($employee['position_name'] ?? '-') ?></td>
        <td class="lbl-cell">Penilai</td>
        <td><b><?= esc(mb_strtoupper($evaluator['name'] ?? '-', 'UTF-8')) ?></b></td>
    </tr>
    <tr>
        <td class="lbl-cell">Lokasi / Cabang</td>
        <td><?= esc($employee['branch_name'] ?? '-') ?></td>
        <td class="lbl-cell">Peninjau</td>
        <td><b><?= esc(mb_strtoupper($reviewer['name'] ?? (!empty($appraisal->snapshot_gm_id) ? '-' : 'Tidak Diperlukan'), 'UTF-8')) ?></b></td>
    </tr>
</table>
<br><br>

<!-- II. PERFORMA -->
<table width="100%" cellpadding="3.5" cellspacing="0" border="0.5">
    <thead>
        <tr style="background-color: #e2e8f0;">
            <th colspan="5" class="sec-title">II. PERFORMA</th>
        </tr>
        <tr style="background-color: #f1f5f9;">
            <th colspan="5" align="left" style="font-size: 8.5pt; font-weight: bold; color: #0f172a;">
                SKOR AKHIR: <?= number_format((float) $finalScoreVal, 2) ?> / 4.00
            </th>
        </tr>
        <tr align="center" style="background-color: #f8fafc; font-weight: bold; font-size: 7.5pt;">
            <th width="5%">NO</th>
            <th width="23%">KOMPETENSI</th>
            <th width="48%">INDIKATOR PERILAKU</th>
            <th width="10%">SKOR</th>
            <th width="14%">SUB-TOTAL</th>
        </tr>
    </thead>
    <tbody>

    <?php if (!empty($allScoreItems)): ?>
        <?php foreach ($allScoreItems as $cIdx => $item): ?>
            <?php
                $itemId      = (int) ($item['md_appraisal_template_item_id'] ?? $item['template_item_id']);
                $cfg         = (array) ($item['config'] ?? []);
                $compTitle   = !empty($cfg['competency']) ? $cfg['competency'] : (!empty($item['title']) ? $item['title'] : ('Kompetensi ' . ($cIdx + 1)));
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

                $hasKeyActions    = !empty($actions);
                $totalActions     = $hasKeyActions ? count($actions) : 1;
                $actionSum        = 0;
                $actionScoresList = [];

                if ($hasKeyActions) {
                    foreach ($actions as $aIdx => $actionText) {
                        if (isset($savedActionScores[$aIdx]) && $savedActionScores[$aIdx] !== '') {
                            $sc = (float) $savedActionScores[$aIdx];
                        } elseif (isset($savedActionScores[(string) $aIdx]) && $savedActionScores[(string) $aIdx] !== '') {
                            $sc = (float) $savedActionScores[(string) $aIdx];
                        } elseif ($savedItemScore !== null) {
                            $sc = $savedItemScore;
                        } else {
                            $sc = 3.00;
                        }
                        $actionScoresList[$aIdx] = $sc;
                        $actionSum += $sc;

                        // Track Score 4 and Score 1
                        $cleaned = $cleanActionText($actionText);
                        if (round($sc, 2) >= 3.99) {
                            $itemsNilai4[] = $cleaned;
                        } elseif (round($sc, 2) <= 1.01) {
                            $itemsNilai1[] = $cleaned;
                        }
                    }
                } else {
                    $sc = ($savedItemScore !== null) ? $savedItemScore : 3.00;
                    $actionScoresList[0] = $sc;
                    $actionSum = $sc;

                    $cleaned = $cleanActionText($compTitle);
                    if (round($sc, 2) >= 3.99) {
                        $itemsNilai4[] = $cleaned;
                    } elseif (round($sc, 2) <= 1.01) {
                        $itemsNilai1[] = $cleaned;
                    }
                }

                $avgScore = $totalActions > 0 ? ($actionSum / $totalActions) : 3.00;
            ?>

            <?php if ($hasKeyActions): ?>
                <?php foreach ($actions as $aIdx => $actionText): ?>
                    <?php
                        $letter = chr(97 + $aIdx);
                        $hasPrefix = preg_match('/^[a-z0-9][\.\)]\s*/i', $actionText);
                        $actionLabel = $hasPrefix ? esc($actionText) : ($letter . '. ' . esc($actionText));
                    ?>
                    <tr nobr="true">
                        <?php if ($aIdx === 0): ?>
                            <td width="5%" rowspan="<?= $totalActions ?>" align="center"><?= ($cIdx + 1) ?></td>
                            <td width="23%" rowspan="<?= $totalActions ?>"><b><?= esc($compTitle) ?></b></td>
                        <?php endif; ?>
                        <td width="48%"><?= $actionLabel ?></td>
                        <td width="10%" align="center"><?= number_format($actionScoresList[$aIdx], 2) ?></td>
                        <?php if ($aIdx === 0): ?>
                            <td width="14%" rowspan="<?= $totalActions ?>" align="center" style="font-weight: bold; background-color: #f8fafc;">
                                <?= number_format($avgScore, 2) ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr nobr="true">
                    <td width="5%" align="center"><?= ($cIdx + 1) ?></td>
                    <td width="23%"><b><?= esc($compTitle) ?></b></td>
                    <td width="48%">Indikator umum</td>
                    <td width="10%" align="center"><?= number_format($actionScoresList[0], 2) ?></td>
                    <td width="14%" align="center" style="font-weight: bold; background-color: #f8fafc;">
                        <?= number_format($avgScore, 2) ?>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr nobr="true">
            <td colspan="5" align="center" style="color: #64748b;">Tidak ada data kompetensi performa.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<br><br>

<!-- III. KOMPETENSI TAMBAHAN -->
<table width="100%" cellpadding="3.5" cellspacing="0" border="0.5">
    <thead>
        <tr style="background-color: #e2e8f0;">
            <th colspan="4" class="sec-title">III. KOMPETENSI TAMBAHAN</th>
        </tr>
        <tr align="center" style="background-color: #f8fafc; font-weight: bold; font-size: 7.5pt;">
            <th width="5%">NO</th>
            <th width="27%">KOMPETENSI</th>
            <th width="50%">INDIKATOR PERILAKU</th>
            <th width="18%">STATUS</th>
        </tr>
    </thead>
    <tbody>

    <?php if (!empty($allBooleanItems)): ?>
        <?php foreach ($allBooleanItems as $bIdx => $bItem): ?>
            <?php
                $bItemId      = (int) ($bItem['md_appraisal_template_item_id'] ?? $bItem['template_item_id']);
                $bCfg         = (array) ($bItem['config'] ?? []);
                $bTitle       = !empty($bCfg['competency']) ? $bCfg['competency'] : (!empty($bItem['title']) ? $bItem['title'] : ('Kompetensi ' . ($bIdx + 1)));
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
                                $isActionChecked = in_array((string) $kIdx, array_map('strval', $checkedActions), true)
                                    || in_array($ka, $checkedActions, true);
                            }
                        }

                        if ($isActionChecked) {
                            $itemsKompetensiTambahan[] = $cleanActionText($ka);
                        }
                    ?>
                    <tr nobr="true">
                        <?php if ($kIdx === 0): ?>
                            <td width="5%" rowspan="<?= $totalActions ?>" align="center"><?= ($bIdx + 1) ?></td>
                            <td width="27%" rowspan="<?= $totalActions ?>"><b><?= esc($bTitle) ?></b></td>
                        <?php endif; ?>
                        <td width="50%"><?= chr(97 + $kIdx) ?>. <?= esc($ka) ?></td>
                        <td width="18%" align="center"><b><?= ($isActionChecked ? '[X]' : '[ &nbsp; ]') ?></b></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                    $isProven = in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true);
                    if ($isProven) {
                        $itemsKompetensiTambahan[] = $cleanActionText($bTitle);
                    }
                ?>
                <tr nobr="true">
                    <td width="5%" align="center"><?= ($bIdx + 1) ?></td>
                    <td width="27%"><b><?= esc($bTitle) ?></b></td>
                    <td width="50%">Indikator umum</td>
                    <td width="18%" align="center"><b><?= ($isProven ? '[X]' : '[ &nbsp; ]') ?></b></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr nobr="true">
            <td colspan="4" align="center" style="color: #64748b;">Tidak ada data kompetensi tambahan.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<br><br>

<!-- IV. REKAP PENILAIAN -->
<table width="100%" cellpadding="4.5" cellspacing="0" border="0.5">
    <thead>
        <tr style="background-color: #e2e8f0;">
            <th colspan="2" class="sec-title">IV. REKAP PENILAIAN</th>
        </tr>
    </thead>
    <tbody>
        <tr nobr="true">
            <td width="28%" class="lbl-cell" style="vertical-align: top;">
                <b>NILAI 4</b>
            </td>
            <td width="72%" style="vertical-align: top; line-height: 1.5;">
                <?php if (!empty($itemsNilai4)): ?>
                    <table width="100%" cellpadding="2" cellspacing="0" border="0">
                        <?php foreach ($itemsNilai4 as $idx => $n4Text): ?>
                            <tr>
                                <td width="4%" style="vertical-align: top;"><?= chr(97 + $idx) ?>.</td>
                                <td width="96%" style="vertical-align: top; line-height: 1.45; color: #334155;"><?= esc($n4Text) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <span style="color: #94a3b8; font-style: italic;">Tidak ada indikator dengan nilai 4</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr nobr="true">
            <td width="28%" class="lbl-cell" style="vertical-align: top;">
                <b>NILAI 1</b>
            </td>
            <td width="72%" style="vertical-align: top; line-height: 1.5;">
                <?php if (!empty($itemsNilai1)): ?>
                    <table width="100%" cellpadding="2" cellspacing="0" border="0">
                        <?php foreach ($itemsNilai1 as $idx => $n1Text): ?>
                            <tr>
                                <td width="4%" style="vertical-align: top;"><?= chr(97 + $idx) ?>.</td>
                                <td width="96%" style="vertical-align: top; line-height: 1.45; color: #334155;"><?= esc($n1Text) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <span style="color: #94a3b8; font-style: italic;">Tidak ada indikator dengan nilai 1</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr nobr="true">
            <td width="28%" class="lbl-cell" style="vertical-align: top;">
                <b>KOMPETENSI TAMBAHAN</b>
            </td>
            <td width="72%" style="vertical-align: top; line-height: 1.5;">
                <?php if (!empty($itemsKompetensiTambahan)): ?>
                    <table width="100%" cellpadding="2" cellspacing="0" border="0">
                        <?php foreach ($itemsKompetensiTambahan as $idx => $ktText): ?>
                            <tr>
                                <td width="4%" style="vertical-align: top;"><?= chr(97 + $idx) ?>.</td>
                                <td width="96%" style="vertical-align: top; line-height: 1.45; color: #334155;"><?= esc($ktText) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <span style="color: #94a3b8; font-style: italic;">Tidak ada kompetensi tambahan yang terpilih</span>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>
<br><br>

<!-- V. CATATAN PENILAIAN -->
<table width="100%" cellpadding="4.5" cellspacing="0" border="0.5">
    <thead>
        <tr style="background-color: #e2e8f0;">
            <th colspan="2" class="sec-title">V. CATATAN PENILAIAN</th>
        </tr>
    </thead>
    <tbody>
        <tr nobr="true">
            <td colspan="2" style="line-height: 1.5;">
                <b>1. Apakah kompetensi yang paling menonjol dan sudah baik dari karyawan yang bersangkutan?</b><br>
                <span style="color: #334155;">
                    <?php if (!empty($appraisal->evaluator_comment)): ?>
                        <?= nl2br(esc($appraisal->evaluator_comment)) ?>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-style: italic;">Tidak ada catatan khusus</span>
                    <?php endif; ?>
                </span>
            </td>
        </tr>
        <tr nobr="true">
            <td colspan="2" style="line-height: 1.5;">
                <b>2. Apakah kompetensi yang masih perlu ditingkatkan dari karyawan yang bersangkutan?</b><br>
                <span style="color: #334155;">
                    <?php if (!empty($appraisal->development_plan)): ?>
                        <?= nl2br(esc($appraisal->development_plan)) ?>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-style: italic;">Tidak ada catatan khusus</span>
                    <?php endif; ?>
                </span>
            </td>
        </tr>
        <?php if (!empty($appraisal->employee_comment)): ?>
        <tr nobr="true">
            <td colspan="2" style="line-height: 1.5;">
                <b>Tanggapan / Catatan Karyawan:</b><br>
                <span style="color: #334155;">
                    <?= nl2br(esc($appraisal->employee_comment)) ?>
                </span>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<br><br>

<!-- VI. HASIL AKHIR & PREDIKAT PENILAIAN -->
<table nobr="true" width="100%" cellpadding="6" cellspacing="0" border="0.5">
    <tr>
        <td colspan="2" class="sec-title">VI. HASIL AKHIR &amp; PREDIKAT PENILAIAN</td>
    </tr>
    <tr align="center" style="background-color: #f8fafc; font-size: 7.5pt; font-weight: bold; color: #475569;">
        <td width="50%">SKOR AKHIR PENILAIAN</td>
        <td width="50%">PREDIKAT PENILAIAN</td>
    </tr>
    <tr align="center" style="background-color: #ffffff;">
        <td width="50%" style="font-size: 14pt; font-weight: bold; color: #0f172a; line-height: 1.6;">
            <?= number_format((float) $finalScoreVal, 2) ?> / 4.00
        </td>
        <td width="50%" style="font-size: 10pt; font-weight: bold; color: #0f172a; line-height: 1.8;">
            <?= esc($ratingText) ?>
        </td>
    </tr>
</table>
<br><br>

<!-- VII. PENGESAHAN / TANDA TANGAN -->
<table nobr="true" width="100%" cellpadding="3" cellspacing="0" border="0.5">
    <tr nobr="true">
        <td colspan="3" class="sec-title">VII. PENGESAHAN / TANDA TANGAN</td>
    </tr>
    <tr nobr="true" align="center" style="background-color: #f8fafc; font-weight: bold;">
        <td width="33.33%">Karyawan yang Dinilai</td>
        <td width="33.34%">Penilai</td>
        <td width="33.33%">Peninjau</td>
    </tr>
    <tr nobr="true" align="center">
        <!-- Stamp row: &nbsp; guarantees the 54pt height never collapses in TCPDF -->
        <td width="33.33%" height="54" align="center" style="text-align: center; vertical-align: middle;">&nbsp;</td>
        <td width="33.34%" height="54" align="center" style="text-align: center; vertical-align: middle;">&nbsp;</td>
        <td width="33.33%" height="54" align="center" style="text-align: center; vertical-align: middle;">&nbsp;</td>
    </tr>
    <tr nobr="true" align="center">
        <!-- Col 1: Employee -->
        <td width="33.33%" align="center" style="text-align: center; vertical-align: middle; line-height: 1.4; padding: 6px 0;"><b><?= esc(mb_strtoupper($employee['name'] ?? '-', 'UTF-8')) ?></b></td>

        <!-- Col 2: Evaluator -->
        <td width="33.34%" align="center" style="text-align: center; vertical-align: middle; line-height: 1.4; padding: 6px 0;"><b><?= esc(mb_strtoupper($evaluator['name'] ?? '-', 'UTF-8')) ?></b></td>

        <!-- Col 3: Reviewer -->
        <td width="33.33%" align="center" style="text-align: center; vertical-align: middle; line-height: 1.4; padding: 6px 0;"><b><?= !empty($reviewer['name']) ? esc(mb_strtoupper($reviewer['name'], 'UTF-8')) : '................................' ?></b></td>
    </tr>
</table>