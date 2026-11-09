<?php
/**
 * View template for Operator / Pelaksana Performance Appraisal PDF
 * Rendered and converted via TCPDF, adhering uniformly to standard staff appraisal layout
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

$items = $snapshot['items'] ?? [];
$sections = $snapshot['sections'] ?? [];

$weightedItems = [];
$singleItems   = [];

foreach ($items as $it) {
    $itType = strtoupper($it['input_type'] ?? '');
    if ($itType === 'WEIGHTED_CHECKLIST') {
        $weightedItems[] = $it;
    } elseif ($itType === 'SINGLE_CHECKLIST') {
        $singleItems[] = $it;
    } else {
        $weightedItems[] = $it;
    }
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

// Track indicators for Detail Kinerja & Pencapaian Kompetensi
$itemsNilai3             = [];
$itemsNilai1             = [];
$itemsKompetensiTambahan = [];

$totBobot = 0;
$totSkor  = 0;
$totNilai = 0;
?>

<style>
    table { border-collapse: collapse; font-family: helvetica, sans-serif; font-size: 8pt; color: #1e293b; }
    .sec-title { background-color: #e2e8f0; font-weight: bold; font-size: 8.5pt; color: #0f172a; }
    .lbl-cell { background-color: #f8fafc; font-weight: bold; color: #334155; }
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

<?php
// Hitung data dan total sebelum render HTML agar skor total langsung siap di banner atas
$renderedRows = [];
$totBobot = 0;
$totSkor  = 0;
$totNilai = 0;

if (!empty($weightedItems)) {
    foreach ($weightedItems as $idx => $it) {
        $itemId = (int) ($it['md_appraisal_template_item_id'] ?? $it['template_item_id'] ?? ($idx + 1));
        $cfg    = (array) ($it['config'] ?? []);
        $title  = !empty($it['title']) ? $it['title'] : ($cfg['aspect'] ?? ('Aspek Operasional ' . ($idx + 1)));
        $desc   = !empty($it['description']) ? $it['description'] : ($cfg['description'] ?? '');
        
        $bobot  = (int) round((float) ($it['weight'] ?? $cfg['bobot'] ?? 1));
        if ($bobot <= 0) $bobot = 1;

        $resp = $responses_by_item_id[$itemId] ?? null;
        $score = null;
        if ($resp) {
            if (!empty($resp->gm_response)) {
                $gmPayload = is_string($resp->gm_response) ? json_decode($resp->gm_response, true) : $resp->gm_response;
                if (isset($gmPayload['score']) && $gmPayload['score'] !== '') {
                    $score = (int) round((float) $gmPayload['score']);
                }
            }
            if ($score === null && !empty($resp->evaluator_response)) {
                $evalPayload = is_string($resp->evaluator_response) ? json_decode($resp->evaluator_response, true) : $resp->evaluator_response;
                if (isset($evalPayload['score']) && $evalPayload['score'] !== '') {
                    $score = (int) round((float) $evalPayload['score']);
                }
            }
            if ($score === null && $resp->score !== null) {
                $score = (int) round((float) $resp->score);
            }
        }

        $subtotal = ($score !== null) ? ($bobot * $score) : null;
        $totBobot += $bobot;
        if ($score !== null) {
            $totSkor  += $score;
            $totNilai += $subtotal;

            $cleanedTitle = $cleanActionText($title);
            if ($score >= 3) {
                $itemsNilai3[] = $cleanedTitle;
            } elseif ($score === 1) {
                $itemsNilai1[] = $cleanedTitle;
            }
        }

        $renderedRows[] = [
            'no'       => $idx + 1,
            'title'    => $title,
            'desc'     => $desc,
            'bobot'    => $bobot,
            'score'    => $score,
            'subtotal' => $subtotal
        ];
    }
}
?>

<?php
// --- 1. Hitung Banyaknya Checklist yang Tercentang (Bagian III) ---
$checkedCount = 0;
if (!empty($singleItems)) {
    foreach ($singleItems as $sIdx => $sItem) {
        $sItemId = (int) ($sItem['md_appraisal_template_item_id'] ?? $sItem['template_item_id'] ?? ($sIdx + 1));
        $sCfg    = (array) ($sItem['config'] ?? []);
        $sActions = $parseActions($sCfg['key_actions'] ?? null);

        $sResp       = $responses_by_item_id[$sItemId] ?? null;
        $evalPayload = ($sResp && !empty($sResp->evaluator_response))
            ? (is_string($sResp->evaluator_response) ? json_decode($sResp->evaluator_response, true) : $sResp->evaluator_response)
            : [];
        $gmPayload   = ($sResp && !empty($sResp->gm_response))
            ? (is_string($sResp->gm_response) ? json_decode($sResp->gm_response, true) : $sResp->gm_response)
            : [];

        $checkedActions = isset($gmPayload['checked_actions'])
            ? $gmPayload['checked_actions']
            : ($evalPayload['checked_actions'] ?? []);
        $booleanValue   = $gmPayload['boolean_value'] ?? $evalPayload['boolean_value'] ?? null;

        if (!empty($sActions)) {
            foreach ($sActions as $kIdx => $ka) {
                if (in_array((string)$kIdx, array_map('strval', $checkedActions), true) || in_array($ka, $checkedActions, true)) {
                    $checkedCount++;
                    $itemsKompetensiTambahan[] = $cleanActionText($ka);
                }
            }
        } else {
            if (in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true)) {
                $checkedCount++;
                $sTitle = !empty($sItem['title']) ? $sItem['title'] : ($sCfg['competency'] ?? ('Item Checklist ' . ($sIdx + 1)));
                $itemsKompetensiTambahan[] = $cleanActionText($sTitle);
            }
        }
    }
}

// Generate string A (contoh: 2 checklist = 'AA')
$suffixA = str_repeat('A', $checkedCount);
$displaySkorAkhir = ((int) $totNilai) . $suffixA;

$finalScoreInt = (int) $totNilai;
if ($finalScoreInt >= 13 && $finalScoreInt <= 15) {
    $officialRating = 'Sangat Baik';
} elseif ($finalScoreInt >= 8 && $finalScoreInt <= 12) {
    $officialRating = 'Baik';
} elseif ($finalScoreInt >= 5 && $finalScoreInt <= 7) {
    $officialRating = 'Kurang';
} else {
    $officialRating = '-';
}
?>

<!-- II. PENILAIAN KINERJA -->
<table width="100%" cellpadding="3.5" cellspacing="0" border="0.5">
    <thead>
        <tr style="background-color: #e2e8f0;">
            <th colspan="5" class="sec-title">II. PENILAIAN KINERJA</th>
        </tr>
        <tr style="background-color: #f1f5f9;">
            <th colspan="5" align="left" width="100%" style="font-size: 8.5pt; font-weight: bold; color: #0f172a;">
                SKOR AKHIR: <?= number_format((int) $totNilai, 0) ?>
            </th>
        </tr>
        <tr align="center" style="background-color: #f8fafc; font-weight: bold; font-size: 7.5pt;">
            <th width="5%">NO</th>
            <th width="50%">ASPEK PEKERJAAN / TUGAS RUTIN</th>
            <th width="15%">BOBOT</th>
            <th width="15%">SKOR</th>
            <th width="15%">SUB-TOTAL</th>
        </tr>
    </thead>
    <tbody>

    <?php if (!empty($renderedRows)): ?>
        <?php foreach ($renderedRows as $row): ?>
            <tr nobr="true">
                <td width="5%" align="center"><?= $row['no'] ?></td>
                <td width="50%">
                    <b><?= esc($row['title']) ?></b>
                    <?php if (!empty($row['desc'])): ?>
                        <br><span style="color:#64748b; font-size:7pt;"><?= esc($row['desc']) ?></span>
                    <?php endif; ?>
                </td>
                <td width="15%" align="center"><?= number_format($row['bobot'], 0) ?></td>
                <td width="15%" align="center"><?= ($row['score'] !== null ? number_format($row['score'], 0) : '-') ?></td>
                <td width="15%" align="center"><b><?= ($row['subtotal'] !== null ? number_format($row['subtotal'], 0) : '-') ?></b></td>
            </tr>
        <?php endforeach; ?>
        <tr nobr="true" style="background-color: #f8fafc; font-weight: bold;">
            <td colspan="2" width="55%" align="right">TOTAL BOBOT &amp; NILAI :</td>
            <td width="15%" align="center"><?= number_format((int) $totBobot, 0) ?></td>
            <td width="15%" align="center"><b><?= number_format((int) $totSkor, 0) ?></b></td>
            <td width="15%" align="center"><b><?= number_format((int) $totNilai, 0) ?></b></td>
        </tr>
    <?php else: ?>
        <tr nobr="true">
            <td colspan="5" align="center" style="color: #64748b;">Tidak ada data penilaian aspek operasional.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<br><br>

<?php if (!empty($singleItems)): ?>
<!-- III. PENILAIAN TAMBAHAN -->
<table width="100%" cellpadding="3.5" cellspacing="0" border="0.5">
    <thead>
        <tr style="background-color: #e2e8f0;">
            <th colspan="3" class="sec-title">III. PENILAIAN TAMBAHAN</th>
        </tr>
        <tr align="center" style="background-color: #f8fafc; font-weight: bold; font-size: 7.5pt;">
            <th width="5%">NO</th>
            <th width="75%">PARAMETER PEMERIKSAAN</th>
            <th width="20%">STATUS</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($singleItems as $sIdx => $sItem): ?>
        <?php
            $sItemId = (int) ($sItem['md_appraisal_template_item_id'] ?? $sItem['template_item_id'] ?? ($sIdx + 1));
            $sCfg    = (array) ($sItem['config'] ?? []);
            $sTitle  = !empty($sItem['title']) ? $sItem['title'] : ($sCfg['competency'] ?? ('Item Checklist ' . ($sIdx + 1)));
            $sDesc   = !empty($sItem['description']) ? $sItem['description'] : ($sCfg['description'] ?? 'Pemeriksaan standar operasional');
            $sActions = $parseActions($sCfg['key_actions'] ?? null);

            $sResp        = $responses_by_item_id[$sItemId] ?? null;
            $evalPayload  = ($sResp && !empty($sResp->evaluator_response))
                ? (is_string($sResp->evaluator_response) ? json_decode($sResp->evaluator_response, true) : $sResp->evaluator_response)
                : [];
            $gmPayload    = ($sResp && !empty($sResp->gm_response))
                ? (is_string($sResp->gm_response) ? json_decode($sResp->gm_response, true) : $sResp->gm_response)
                : [];

            $checkedActions = isset($gmPayload['checked_actions'])
                ? $gmPayload['checked_actions']
                : ($evalPayload['checked_actions'] ?? []);
            $booleanValue   = $gmPayload['boolean_value'] ?? $evalPayload['boolean_value'] ?? null;
        ?>
        <?php if (!empty($sActions)): ?>
            <?php foreach ($sActions as $kIdx => $ka): ?>
                <?php
                    $isActionChecked = in_array((string)$kIdx, array_map('strval', $checkedActions), true)
                        || in_array($ka, $checkedActions, true);
                ?>
                <tr nobr="true">
                    <?php if ($kIdx === 0): ?>
                        <td width="5%" align="center" rowspan="<?= count($sActions) ?>"><?= ($sIdx + 1) ?></td>
                        <td width="75%" rowspan="<?= count($sActions) ?>"><b><?= esc($sTitle) ?></b></td>
                    <?php endif; ?>
                    <td width="20%" align="center"><b><?= ($isActionChecked ? '[X]' : '[ &nbsp; ]') ?></b></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <?php
                $isProven = in_array(strtoupper((string) ($booleanValue ?? '')), ['Y', '1', 'TRUE'], true);
            ?>
            <tr nobr="true">
                <td width="5%" align="center"><?= ($sIdx + 1) ?></td>
                <td width="75%"><b><?= esc($sTitle) ?></b></td>
                <td width="20%" align="center"><b><?= ($isProven ? '[X]' : '[ &nbsp; ]') ?></b></td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<br><br>
<?php endif; ?>

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
                <b>Sangat Baik</b>
            </td>
            <td width="72%" style="vertical-align: top; line-height: 1.5;">
                <?php if (!empty($itemsNilai3)): ?>
                    <table width="100%" cellpadding="2" cellspacing="0" border="0">
                        <?php foreach ($itemsNilai3 as $idx => $n3Text): ?>
                            <tr>
                                <td width="4%" style="vertical-align: top;"><?= chr(97 + $idx) ?>.</td>
                                <td width="96%" style="vertical-align: top; line-height: 1.45; color: #334155;"><?= esc($n3Text) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <span style="color: #94a3b8; font-style: italic;">Tidak ada indikator dengan nilai 3</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr nobr="true">
            <td width="28%" class="lbl-cell" style="vertical-align: top;">
                <b>Kurang</b>
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
            <?= esc($displaySkorAkhir) ?>
        </td>
        <td width="50%" style="font-size: 10pt; font-weight: bold; color: #0f172a; line-height: 1.8;">
            <?= esc($officialRating) ?>
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
