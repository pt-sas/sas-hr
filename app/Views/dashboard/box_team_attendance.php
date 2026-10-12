<?php
/**
- app/Views/dashboard/box_team_attendance.php - Senior-Friendly (Repositioned Subtitle & AJAX-Safe Percentage Labels)
 *
- View data (all optional):
-   $team_present, $team_total, $team_on_leave      day view
-   $team_rate                                       period view (0-100)
-   $team_trend  [['label'=>'Sen','rate'=>92], ...]  bars; rate null = no data yet
-   $team_target (default 90)   $team_prev_rate (adds "▲ 3 pt" vs previous period)
-   $team_absent_names ['Rina','Dodi']               day view: who is not in yet
-   $team_trend_unit ('hari' | 'minggu')             wording of footer note
 */
$mode    = $mode ?? 'month';
$hasData = isset($team_present, $team_total) || isset($team_rate);
$total   = isset($team_total) ? max(0, (int) $team_total) : 0;
$present = isset($team_present) ? min($total, max(0, (int) $team_present)) : 0;
$leave   = isset($team_on_leave) ? min($total - $present, max(0, (int) $team_on_leave)) : 0;
$pending = max(0, $total - $present - $leave);

$pct = static fn (int $n): float => $total > 0 ? round($n / $total * 100, 1) : 0;

$rate     = isset($team_rate) ? (int) $team_rate : ($total > 0 ? (int) round(($present / $total) * 100) : 0);
$subtitle = $label ?? ($mode === 'day' ? 'Hari ini' : ($mode === 'week' ? 'Minggu ini' : 'Bulan ini'));

$target   = isset($team_target) ? (int) $team_target : 90;
$delta    = isset($team_prev_rate) ? $rate - (int) $team_prev_rate : null;
$absent   = array_values(array_filter((array) ($team_absent_names ?? []), 'strlen'));
$unit     = $team_trend_unit ?? ($mode === 'week' ? 'hari' : 'minggu');

// ---- trend bars & direct percentage formatting ----
$items = [];
$trend = is_array($team_trend ?? null) ? $team_trend : [];
foreach ($trend as $it) {
    if (! is_array($it)) continue;
    $r = $it['rate'] ?? null;
    $items[] = [
        'label' => (string) ($it['label'] ?? ''),
        'rate'  => $r === null ? null : max(0, min(100, (int) $r)),
    ];
}

$validRates = array_filter(array_column($items, 'rate'), static fn ($r) => $r !== null);
$n          = count($items);
$every      = $n > 8 ? (int) ceil($n / 8) : 1;
$measured   = count($validRates);
$below      = count(array_filter($validRates, static fn ($r) => $r < $target));
$tone       = static fn (int $r): string => $r >= $target ? 'ok' : ($r >= 75 ? 'mid' : 'low');
$note       = $measured === 0 ? '' : ($below === 0 ? 'Semua ' . $unit . ' memenuhi target ' . $target . '%'
                                                   : $below . ' dari ' . $measured . ' ' . $unit . ' di bawah target ' . $target . '%');
$ariaChart  = 'Kehadiran per ' . $unit . ': ' . implode(', ', array_map(static fn ($i) => $i['label'] . ' ' . ($i['rate'] === null ? 'belum ada data' : $i['rate'] . '%'), $items));
?>
<div class="card card-stats card-round border-0 shadow-sm h-100 overflow-hidden" data-kpi-card="team">
    <div class="card-body p-3 d-flex flex-column justify-content-between">

        <!-- HEADER & CARD SUBTITLE -->
        <div class="d-flex justify-content-between align-items-start mb-1">
            <div class="min-w-0 me-2">
                <p class="tk-card-title mb-0 text-truncate">Kehadiran Tim</p>
                <small class="tk-card-sub text-truncate d-block" data-kpi="team_subtitle"><?= esc($subtitle) ?></small>
            </div>
            <span class="tk-header-icon-wrap" aria-hidden="true">
                <i class="fas fa-users"></i>
            </span>
        </div>

        <?php if (! $hasData): ?>
            <h2 class="fw-extrabold text-muted mb-1 tk-empty-val" data-kpi="team_present">—</h2>
            <small class="tk-empty-text">Data tim belum tersedia</small>

        <?php else: ?>

            <!-- DAY MODE -->
            <div data-kpi-view="day" class="<?= $mode === 'day' ? '' : 'd-none' ?>">
                <div class="d-flex align-items-baseline justify-content-between my-2">
                    <h2 class="fw-extrabold text-dark mb-0 tk-rate-num" style="font-variant-numeric:tabular-nums" data-kpi="team_rate_day"><?= $rate ?>%</h2>
                    <span class="tk-count-text">
                        <span data-kpi="team_present"><?= $present ?></span> / <span data-kpi="team_total"><?= $total ?></span> orang
                    </span>
                </div>

                <div class="progress tk-prog w-100 mb-2" role="img" aria-label="<?= $present ?> hadir, <?= $leave ?> cuti atau izin, <?= $pending ?> belum hadir dari <?= $total ?> orang">
                    <div class="progress-bar bg-success" style="width:<?= $pct($present) ?>%" data-kpi="team_present_bar"></div>
                    <div class="progress-bar bg-warning" style="width:<?= $pct($leave) ?>%" data-kpi="team_leave_bar"></div>
                </div>

                <div class="tk-legend">
                    <span><i class="tk-dot" style="background:#059669"></i><b data-kpi="team_present_count"><?= $present ?></b> hadir</span>
                    <span><i class="tk-dot" style="background:#d97706"></i><b data-kpi="team_leave_count"><?= $leave ?></b> cuti/izin</span>
                    <span><i class="tk-dot" style="background:#94a3b8"></i><b data-kpi="team_pending_count"><?= $pending ?></b> belum hadir</span>
                </div>

                <?php if ($absent): ?>
                    <p class="tk-names mt-2 mb-0" data-kpi="team_absent_names" title="<?= esc(implode(', ', $absent), 'attr') ?>">
                        <i class="far fa-clock" aria-hidden="true"></i>
                        Belum hadir: <?= esc(implode(', ', array_slice($absent, 0, 2))) ?><?= count($absent) > 2 ? ' +' . (count($absent) - 2) : '' ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- WEEK & MONTH MODE (Period View) -->
            <div data-kpi-view="period" class="<?= $mode !== 'day' ? '' : 'd-none' ?>">
                
                <!-- Clean Primary Rate Row (Repositioned Subtitle) -->
                <div class="d-flex align-items-center justify-content-between my-1">
                    <div>
                        <div class="d-flex align-items-baseline">
                            <h2 class="fw-extrabold text-dark mb-0 me-2 tk-rate-num" style="font-variant-numeric:tabular-nums" data-kpi="team_rate"><?= $rate ?>%</h2>
                            <span class="tk-delta <?= $delta === null ? 'd-none' : ($delta >= 0 ? 'is-up' : 'is-down') ?>" data-kpi="team_delta" title="Dibanding periode sebelumnya">
                                <?php if ($delta !== null): ?><i class="fas fa-arrow-<?= $delta >= 0 ? 'up' : 'down' ?>" aria-hidden="true"></i> <?= abs($delta) ?> pt<?php endif; ?>
                            </span>
                        </div>
                        <span class="tk-rate-tag d-block mt-1" data-kpi="team_rate_label">
                            <?= $mode === 'week' ? 'Rata-rata minggu ini' : 'Rata-rata kehadiran' ?>
                        </span>
                    </div>
                </div>

                <!-- Bar Chart with Dual DOM/CSS Percentage Label Support -->
                <div class="tk-chart" role="img" aria-label="<?= esc($ariaChart, 'attr') ?>" data-kpi="team_sparklines" data-target="<?= $target ?>">
                    <?php if ($n): ?>
                        <div class="tk-plot" style="--n:<?= $n ?>">
                            <span class="tk-target" style="bottom:<?= $target ?>%" title="Target <?= $target ?>%"></span>
                            <?php foreach ($items as $i => $it): ?>
                                <?php 
                                    $valText = $it['rate'] !== null ? $it['rate'] . '%' : '—'; 
                                    $toneCls = $it['rate'] !== null ? 'is-' . $tone($it['rate']) : '';
                                ?>
                                <div class="tk-col <?= $toneCls ?>" 
                                     data-rate="<?= esc($valText, 'attr') ?>" 
                                     title="<?= esc($it['label'] . ': ' . ($it['rate'] === null ? 'belum ada data' : $it['rate'] . '%'), 'attr') ?>">
                                    <span class="tk-val <?= $toneCls ?>"><?= $valText ?></span>
                                    <div class="tk-bar-track">
                                        <?php if ($it['rate'] !== null): ?>
                                            <span class="tk-fill <?= $toneCls ?>" style="height:<?= max(6, $it['rate']) ?>%"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="tk-axis" style="--n:<?= $n ?>">
                            <?php foreach ($items as $i => $it): ?>
                                <span class="<?= $i === $n - 1 ? 'is-last' : '' ?>"><?= ($i % $every === 0 || $i === $n - 1) ? esc($it['label']) : '' ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="tk-empty">Belum ada data tren</p>
                    <?php endif; ?>
                </div>

                <p class="tk-note text-truncate mb-0" data-kpi="team_target_note"><?= esc($note) ?></p>
            </div>

        <?php endif; ?>

    </div>
</div>

<style>
/* Header Typography */
.tk-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #334155;
    line-height: 1.2;
}

.tk-card-sub {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    line-height: 1.2;
}

.tk-header-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #ecfdf5;
    color: #059669;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.05rem;
    border: 1px solid #a7f3d0;
}

/* Big Rate Display & Clean Subtitle Tag */
.tk-rate-num {
    font-size: 1.85rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1;
}

.tk-rate-tag {
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.tk-count-text {
    font-size: 0.88rem;
    font-weight: 700;
    color: #334155;
}

/* Progress Bar & Legend */
.tk-prog { 
    height: 10px; 
    border-radius: 999px; 
    background: #e2e8f0; 
    overflow: hidden; 
    width: 100%; 
}

.tk-legend { 
    display: flex; 
    flex-wrap: wrap; 
    gap: .2rem .75rem; 
    font-size: .85rem; 
    font-weight: 600;
    color: #334155; 
}

.tk-legend b { 
    color: #0f172a; 
    font-weight: 800; 
    margin-right: 2px; 
    font-variant-numeric: tabular-nums; 
}

.tk-dot { 
    display: inline-block; 
    width: 10px; 
    height: 10px; 
    border-radius: 50%; 
    margin-right: 4px; 
    vertical-align: middle; 
}

/* Absent Members Notification */
.tk-names { 
    font-size: .82rem; 
    font-weight: 700;
    color: #78350f; 
    background: #fffbeb; 
    border: 1px solid #fde68a;
    border-radius: 6px; 
    padding: .3rem .55rem; 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
}
.tk-names i { margin-right: 4px; color: #d97706; }

/* Delta Badge */
.tk-delta { 
    font-size: .85rem; 
    font-weight: 800; 
    white-space: nowrap; 
}
.tk-delta.is-up   { color: #047857; }
.tk-delta.is-down { color: #b91c1c; }
.tk-delta i { font-size: .72rem; }

/* Chart Component & Persistent Percentage Labels */
.tk-chart { margin-top: .35rem; }

.tk-plot { 
    position: relative; 
    height: 72px; 
    display: grid; 
    grid-template-columns: repeat(var(--n, 1), minmax(0, 1fr)); 
    gap: 8px; 
    width: 100%;
    border-bottom: 2px solid #cbd5e1;
}

.tk-col { 
    display: flex; 
    flex-direction: column;
    align-items: center; 
    justify-content: flex-end; 
    height: 100%; 
    min-width: 0;
    position: relative;
}

/* Percentage Label Above Bar */
.tk-val {
    font-size: 0.8rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.tk-val.is-ok  { color: #059669; }
.tk-val.is-mid { color: #d97706; }
.tk-val.is-low { color: #dc2626; }

/* Fallback: If JS dynamic updates overwrite inner HTML and remove .tk-val, render via CSS attribute */
.tk-col:not(:has(.tk-val))::before {
    content: attr(data-rate);
    font-size: 0.8rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.tk-col.is-ok:not(:has(.tk-val))::before  { color: #059669; }
.tk-col.is-mid:not(:has(.tk-val))::before { color: #d97706; }
.tk-col.is-low:not(:has(.tk-val))::before { color: #dc2626; }

.tk-bar-track {
    width: 100%;
    flex: 1;
    display: flex;
    align-items: flex-end;
    background: #f1f5f9;
    border-radius: 4px 4px 0 0;
    overflow: hidden;
}

.tk-fill { 
    display: block; 
    width: 100%; 
    border-radius: 4px 4px 0 0; 
    transition: height .3s ease; 
}

.tk-fill.is-ok  { background: #059669; }
.tk-fill.is-mid { background: #d97706; }
.tk-fill.is-low { background: #dc2626; }

/* Target Line */
.tk-target { 
    position: absolute; 
    left: 0; 
    right: 0; 
    height: 0; 
    border-top: 1.5px dashed #64748b; 
    pointer-events: none; 
    z-index: 5; 
}

.tk-axis { 
    display: grid; 
    grid-template-columns: repeat(var(--n, 1), minmax(0, 1fr)); 
    gap: 8px; 
    width: 100%;
    margin-top: 4px; 
}

.tk-axis span { 
    font-size: .8rem; 
    font-weight: 700; 
    color: #475569; 
    text-align: center; 
    white-space: nowrap; 
    overflow: hidden; 
    text-overflow: ellipsis; 
}
.tk-axis span.is-last { color: #0f172a; font-weight: 800; }

.tk-note { margin-top: .35rem; font-size: .82rem; font-weight: 600; color: #334155; line-height: 1.25; }
.tk-empty { margin: 0; padding: .75rem 0; text-align: center; font-size: .85rem; font-weight: 600; color: #64748b; }

.tk-empty-val { font-size: 1.75rem !important; }
.tk-empty-text { font-size: 0.88rem; font-weight: 600; color: #64748b; }
</style>