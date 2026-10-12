<?php
/**
- app/Views/dashboard/box_attendance.php - Senior-Friendly (Position-Locked Version)
 */
$mode    = $mode ?? 'month';

// Robust key normalization supporting both backend key formats
$days   = (int) ($attendance_days ?? ($days ?? 0));
$target = (int) ($work_days ?? ($target ?? 22));
$rate   = (int) ($my_rate ?? ($rate ?? 0));
$leave  = (int) ($my_leave_count ?? ($leave ?? 0));
$absent = (int) ($my_absent_count ?? ($absent ?? 0));
$late   = (int) ($late_count ?? ($late ?? 0));
$streak = (int) ($my_streak ?? ($streak ?? 0));
$ontime = (int) ($my_ontime_count ?? max(0, $days - $late));

$statusText = $my_status_text ?? 'Belum Absen';
$clockIn    = $my_clock_in_text ?? '-';
$punctual   = $my_punctuality_text ?? 'Belum ada catatan';
$tone       = $my_tone ?? 'secondary';

$toneIcons  = ['success' => 'fa-check-circle', 'warning' => 'fa-exclamation-circle', 'danger' => 'fa-times-circle', 'info' => 'fa-info-circle', 'primary' => 'fa-info-circle', 'secondary' => 'fa-hourglass-half'];
$toneIcon   = $toneIcons[$tone] ?? $toneIcons['secondary'];

// ---- day view ----
$none     = ['', '-', '—', 'Belum', 'Belum absen', 'Belum Absen'];
$hasIn    = isset($my_clock_in_raw) ? trim((string) $my_clock_in_raw) !== '' : ! in_array(trim((string) $clockIn), $none, true);
$outText  = trim((string) ($my_clock_out_text ?? ($my_clock_out_raw ?? '')));
$hasOut   = ! in_array($outText, $none, true);
$schedule = (! empty($my_shift_start) && ! empty($my_shift_end)) ? $my_shift_start . ' – ' . $my_shift_end : '';
$showChip = $hasIn && trim((string) $punctual) !== '';
$showNote = ! $hasIn && ! in_array(trim((string) $punctual), ['', 'Belum ada catatan'], true);

// ---- period view width calculations ----
$denom   = max(1, $target);
$ontimeW = min(100, max(0, ($ontime / $denom) * 100));
$lateW   = min(100 - $ontimeW, max(0, ($late / $denom) * 100));
$leaveW  = min(100 - $ontimeW - $lateW, max(0, ($leave / $denom) * 100));
$absentW = min(100 - $ontimeW - $lateW - $leaveW, max(0, ($absent / $denom) * 100));

$ariaSeg = $days . ' dari ' . $target . ' hari hadir' . ($leave ? ', ' . $leave . ' cuti' : '') . ($absent ? ', ' . $absent . ' alpa' : '');
?>
<div class="card card-stats card-round border-0 shadow-sm h-100 overflow-hidden" data-kpi-card="attendance">
    <div class="card-body p-3 d-flex flex-column justify-content-between">

        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="min-w-0 me-2">
                <p class="ta-card-title mb-0 text-truncate">Kehadiran Saya</p>
                <small class="ta-card-sub text-truncate d-block" data-kpi="label"><?= esc($label ?? '') ?></small>
            </div>
            <span class="ta-header-icon-wrap" aria-hidden="true">
                <i class="far fa-calendar-check"></i>
            </span>
        </div>

        <!-- DAY MODE -->
        <div data-kpi-view="day" class="<?= $mode === 'day' ? '' : 'd-none' ?>">
            <div class="dy-head">
                <span class="ta-ico ta-ico--lg text-<?= esc($tone, 'attr') ?>" data-kpi="my_status_icon" aria-hidden="true"><i class="fas <?= $toneIcon ?>"></i></span>
                <div class="dy-text min-w-0">
                    <h2 class="fw-extrabold text-dark mb-0 dy-status-title text-truncate" data-kpi="my_status_text"><?= esc($statusText) ?></h2>
                    <p class="dy-detail" data-kpi="my_day_detail">
                        <span class="<?= $hasIn ? '' : 'd-none' ?>">Masuk <b data-kpi="my_clock_in_text"><?= esc($clockIn) ?></b></span>
                        <span class="<?= $hasOut ? '' : 'd-none' ?>">· Pulang <b data-kpi="my_clock_out_text"><?= esc($outText) ?></b></span>
                        <span class="<?= $showNote ? '' : 'd-none' ?>" data-kpi="my_day_note"><?= esc($punctual) ?></span>
                        <span class="<?= (! $hasIn && ! $showNote && $schedule !== '') ? '' : 'd-none' ?>">Jadwal <b data-kpi="my_schedule_text"><?= esc($schedule) ?></b></span>
                    </p>
                </div>
            </div>

            <!-- Position-locked Chip Footer Slot -->
            <div class="dy-foot">
                <span class="ta-chip ta-chip--<?= esc($tone, 'attr') ?> <?= $showChip ? '' : 'v-hidden' ?>" data-kpi="my_punctuality_text"><?= esc($punctual) ?></span>
            </div>

            <span class="d-none" aria-hidden="true"><i data-kpi="my_day_bar"></i><i data-kpi="my_clock_mark"></i><i data-kpi="my_shift_start"></i><i data-kpi="my_shift_end"></i></span>
        </div>

        <!-- WEEK & MONTH MODE -->
        <div data-kpi-view="period" class="<?= $mode !== 'day' ? '' : 'd-none' ?>">
            <div class="d-flex align-items-baseline justify-content-between mb-2">
                <h2 class="fw-extrabold text-dark mb-0 ta-rate-num" style="font-variant-numeric:tabular-nums" data-kpi="my_rate"><?= $rate ?>%</h2>
                <span class="ta-period-count">
                    <span data-kpi="attendance_days"><?= $days ?></span> / <span data-kpi="work_days"><?= $target ?></span> hari
                </span>
            </div>

            <div class="ta-track w-100" role="img" aria-label="<?= esc($ariaSeg, 'attr') ?>">
                <div class="progress ta-prog w-100 mb-2">
                    <div class="progress-bar bg-success" style="width:<?= $ontimeW ?>%" data-kpi="my_ontime_bar"></div>
                    <div class="progress-bar ta-late" style="width:<?= $lateW ?>%" data-kpi="my_late_bar"></div>
                    <div class="progress-bar ta-leave" style="width:<?= $leaveW ?>%" data-kpi="my_leave_bar"></div>
                    <div class="progress-bar ta-absent" style="width:<?= $absentW ?>%" data-kpi="my_absent_bar"></div>
                </div>
            </div>

            <div class="ta-meta" data-kpi="my_period_meta">
                <div class="ta-legend">
                    <span><i class="ta-sw bg-success"></i><b data-kpi="my_ontime_count"><?= $ontime ?></b> tepat waktu</span>
                    <?php if ($late > 0): ?><span><i class="ta-sw ta-late"></i><b data-kpi="my_late_count"><?= $late ?></b> terlambat</span><?php endif; ?>
                    <?php if ($leave > 0): ?><span><i class="ta-sw ta-leave"></i><b data-kpi="my_leave_count"><?= $leave ?></b> izin/cuti</span><?php endif; ?>
                    <?php if ($absent > 0 && $mode !== 'week'): ?><span><i class="ta-sw ta-absent"></i><b data-kpi="my_absent_count"><?= $absent ?></b> alpa</span><?php endif; ?>
                </div>
                
                <!-- Position-locked Streak Chips Slot -->
                <div class="ta-chips mt-1">
                    <span class="ta-chip ta-chip--info <?= $streak >= 3 ? '' : 'v-hidden' ?>"><i class="fas fa-bolt" aria-hidden="true"></i> <?= $streak ?> hari beruntun</span>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Position Lock Utilities */
.v-hidden {
    visibility: hidden !important;
}

/* Header Title & Subtitle */
.ta-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #334155;
    line-height: 1.25;
}

.ta-card-sub {
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    line-height: 1.25;
}

.ta-header-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #eff6ff;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.05rem;
    border: 1px solid #bfdbfe;
}

/* Day View Styles */
.dy-head { 
    display: flex; 
    align-items: center; 
    gap: .75rem; 
    min-height: 48px; 
}

.dy-status-title {
    font-size: 1.35rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.2;
}

.dy-detail { 
    margin: 2px 0 0; 
    font-size: .88rem; 
    font-weight: 600;
    color: #334155; 
    display: flex; 
    flex-wrap: wrap; 
    gap: 0 .35rem; 
    line-height: 1.25;
}

.dy-detail b { 
    color: #0f172a; 
    font-weight: 800; 
    font-variant-numeric: tabular-nums; 
}

.dy-foot { 
    display: flex; 
    align-items: center; 
    gap: .5rem; 
    margin-top: .5rem; 
    min-height: 26px; /* Guaranteed fixed slot height */
}

/* Icons and Visual Badges */
.ta-ico { 
    width: 30px; 
    height: 30px; 
    border-radius: 50%; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    flex: none; 
    font-size: .95rem;
    background: color-mix(in srgb, currentColor 18%, transparent); 
}

.ta-ico--lg { 
    width: 44px; 
    height: 44px; 
    font-size: 1.35rem; 
}

/* Period View & Progress Bar */
.ta-rate-num {
    font-size: 1.75rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.1;
}

.ta-period-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: #334155;
}

.ta-prog { 
    height: 10px; 
    border-radius: 999px; 
    background: #e2e8f0; 
    overflow: hidden; 
    width: 100%; 
}

.ta-track { 
    position: relative; 
    width: 100%; 
    display: block; 
}

.ta-late   { background: #d97706; }
.ta-leave  { background: #0284c7; }
.ta-absent { background: repeating-linear-gradient(45deg, #dc2626 0 6px, #fecaca 6px 12px); }

.ta-meta { 
    display: flex; 
    flex-direction: column; 
    gap: 4px; 
    margin-top: .35rem; 
}

.ta-legend { 
    display: flex; 
    flex-wrap: wrap; 
    gap: .2rem .75rem; 
    font-size: .85rem; 
    font-weight: 600;
    color: #334155; 
}

.ta-legend b { 
    color: #0f172a; 
    font-weight: 800; 
    margin-right: 2px; 
    font-variant-numeric: tabular-nums; 
}

.ta-sw { 
    display: inline-block; 
    width: 10px; 
    height: 10px; 
    border-radius: 50%; 
    margin-right: 4px; 
    vertical-align: middle; 
}

.ta-chips { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 6px; 
    min-height: 26px; /* Guaranteed fixed slot height */
}

/* Status Chips & Badges */
.ta-chip { 
    display: inline-flex; 
    align-items: center; 
    gap: 4px; 
    padding: 3px 10px; 
    border-radius: 999px; 
    font-size: .82rem; 
    font-weight: 700; 
    background: #f1f5f9; 
    color: #1e293b; 
    border: 1px solid #cbd5e1;
}

.ta-chip--success { background: #ecfdf5; color: #064e3b; border-color: #a7f3d0; }
.ta-chip--warning { background: #fffbeb; color: #78350f; border-color: #fde68a; }
.ta-chip--danger  { background: #fef2f2; color: #7f1d1d; border-color: #fca5a5; }
.ta-chip--info    { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
</style>