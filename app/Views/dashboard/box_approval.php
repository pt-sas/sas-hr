<?php
/**
- app/Views/dashboard/box_approval.php - Senior-Friendly Accessibility Version
 */
$hasData  = isset($pending_approvals);
$count    = $hasData ? max(0, (int) $pending_approvals) : 0;
$dateText = $label ?? ($dateLabel ?? '');

// 1. Past Date Check: If selected period/date is prior to today, tag as "Riwayat"
$todayStr  = date('Y-m-d');
$checkDate = $rangeEnd ?? ($date ?? $todayStr);
$isPast    = !empty($checkDate) && $checkDate < $todayStr;

if ($isPast) {
    $subTitleText = $dateText !== '' ? 'Riwayat (' . $dateText . ')' : 'Riwayat pengajuan';
} else {
    $subTitleText = $dateText !== '' ? $dateText : 'Menunggu persetujuan';
}

// 2. Build Full Summary Breakdown String (No "+X lainnya" truncation)
$summaryParts = [];
if (! empty($pending_breakdown) && is_array($pending_breakdown)) {
    foreach ($pending_breakdown as $name => $n) {
        if ((int) $n > 0) {
            $summaryParts[] = (int) $n . ' ' . mb_strtolower((string) $name);
        }
    }
}

$summary = !empty($summaryParts) ? implode(', ', $summaryParts) : ($pending_summary ?? '');
$approvalUrl = $approval_url ?? site_url('submission');
?>
<div class="card card-stats card-round border-0 shadow-sm h-100" data-kpi-card="approval">
    <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <p class="ap-card-title mb-1">Pengajuan Berjalan</p>
                <small class="ap-card-sub" data-kpi="approval_date"><?= esc($subTitleText) ?></small>
            </div>
            <span class="ap-header-icon-wrap" aria-hidden="true">
                <i class="far fa-file-alt"></i>
            </span>
        </div>

        <div>
            <div class="d-flex align-items-baseline mb-2">
                <h2 class="fw-extrabold text-dark mb-0 me-2 ap-count-num" style="font-variant-numeric:tabular-nums" data-kpi="pending_approvals"><?= $count ?></h2>
                <span class="ap-count-unit">pengajuan</span>
            </div>

            <!-- Full Breakdown Display (High-contrast & readable font size) -->
            <div class="ap-summary-text lh-base mt-2" data-kpi="pending_summary" title="<?= esc($summary !== '' ? $summary : 'Tidak ada yang tertunda', 'attr') ?>">
                <?php if ($count === 0): ?>
                    <span class="ap-status-clear"><i class="fas fa-check-circle me-1" aria-hidden="true"></i>Tidak ada yang tertunda</span>
                <?php else: ?>
                    <span class="ap-status-pending"><i class="fas fa-exclamation-circle me-1" aria-hidden="true"></i><?= esc($summary !== '' ? $summary : 'Perlu ditinjau') ?></span>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
/* Card Header Typography & Icons */
.ap-card-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: #334155;
}

.ap-card-sub {
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569;
}

.ap-header-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #fffbeb;
    color: #d97706;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.15rem;
    border: 1px solid #fde68a;
}

/* Number Display */
.ap-count-num {
    font-size: 1.85rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
}

.ap-count-unit {
    font-size: 0.95rem;
    font-weight: 700;
    color: #334155;
}

/* Summary / Status Display */
.ap-summary-text {
    font-size: 0.92rem;
    font-weight: 600;
    color: #1e293b;
}

.ap-status-clear {
    color: #047857;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}

.ap-status-clear i {
    font-size: 1rem;
    color: #059669;
}

.ap-status-pending {
    color: #b45309;
    font-weight: 700;
    display: inline-flex;
    align-items: flex-start;
}

.ap-status-pending i {
    font-size: 1rem;
    color: #d97706;
    margin-top: 0.15rem;
}
</style>`