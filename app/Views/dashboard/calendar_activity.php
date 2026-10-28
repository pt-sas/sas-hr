<?php
/**
- app/Views/dashboard/calendar_activity.php
- ThemeKita Pro calendar (FullCalendar v3) - Senior-Friendly Sunday-First Version
 */
$events   = $calendar_events ?? [];
$focus    = $date ?? date('Y-m-d');
$mode     = $mode ?? 'month';

$lockToMonth = true;

$editable = false;            // allow create / drag / resize / delete
$saveUrl  = '';              // e.g. site_url('calendar/save')

$assets   = rtrim(base_url(''), '/');

// Path assets tanpa '/public/'
$fcJs     = $assets . '/atlantis-pro/js/plugin/fullcalendar/fullcalendar.min.js';
$momentJs = $assets . '/atlantis-pro/js/plugin/moment/moment.min.js';
$swalJs   = $assets . '/atlantis-pro/js/plugin/sweetalert/sweetalert.min.js';
$csrfName = function_exists('csrf_token') ? csrf_token() : '';
$csrfHash = function_exists('csrf_hash')  ? csrf_hash()  : '';
?>

<div id="kalender" class="card border-0 shadow-sm card-round">
  <div class="card-body p-3 p-md-4">

    <!-- Side-by-Side Layout Row -->
    <div class="row g-4">
      
      <!-- LEFT COLUMN: Filter Chips + Calendar (8 Cols) -->
      <div class="col-12 col-lg-7 col-xl-8">

        <!-- Main Calendar Target -->
         <?= $this->include('dashboard/calendar_filter', [
            'divisions' => $divisions,
            'branches'  => $branches
        ]) ?>

        <div class="k-legend-bar" aria-label="Petunjuk Kategori">
          <span class="k-legend-item t-leave"><i></i>Cuti / Izin</span>
          <span class="k-legend-item t-team"><i></i>Aktivitas Tim</span>
          <span class="k-legend-item t-bday"><i></i>Ulang Tahun</span>
        </div>

        <div id="fc-calendar"></div>
      </div>

      <!-- RIGHT COLUMN: Selected Day Detail Panel / Agendas (4 Cols) -->
      <div class="col-12 col-lg-5 col-xl-4">
        <div class="k-panel" aria-live="polite">
          <h3 id="k-detail-title" class="pb-2 border-bottom"></h3>
          <div id="k-detail" class="k-detail-scroll"></div>
        </div>
      </div>

    </div>

  </div>
</div>

<style>
/* ==========================================================================
   Color Palette & Accessible High-Contrast Variables
   ========================================================================== */
#kalender {
  --c-company: #1d4ed8; --bg-company: #eff6ff; --txt-company: #0f172a;
  --c-leave:   #d97706; --bg-leave:   #fffbeb; --txt-leave:   #78350f;
  --c-team:    #059669; --bg-team:    #ecfdf5; --txt-team:    #064e3b;
  --c-bday:    #7c3aed; --bg-bday:    #f3e8ff; --txt-bday:    #4c1d95;
  
  --line:  #cbd5e1;
  --muted: #334155;
  --ink:   #0f172a;
}

/* ==========================================================================
   Filter Chips (Legend) Styling - Accessible Buttons
   ========================================================================== */
#kalender .k-filters { display: flex; flex-wrap: wrap; gap: .6rem; }
#kalender .k-chip {
  display: inline-flex; align-items: center; gap: .6rem;
  padding: .5rem 1rem; border: 2px solid var(--line); border-radius: 999px;
  background: #fff; font-size: .95rem; font-weight: 600; color: var(--ink);
  cursor: pointer; transition: all .15s ease;
}
#kalender .k-chip i { width: 12px; height: 12px; border-radius: 50%; background: var(--dot); }

#kalender .k-chip[aria-pressed="false"] { color: #64748b; background: #f8fafc; border-color: #cbd5e1; }
#kalender .k-chip[aria-pressed="false"] i { background: #94a3b8; }
#kalender .t-company { --dot: var(--c-company); --bg: var(--bg-company); --txt: var(--txt-company); }
#kalender .t-leave   { --dot: var(--c-leave);   --bg: var(--bg-leave);   --txt: var(--txt-leave); }
#kalender .t-team    { --dot: var(--c-team);    --bg: var(--bg-team);    --txt: var(--txt-team); }
#kalender .t-bday    { --dot: var(--c-bday);    --bg: var(--bg-bday);    --txt: var(--txt-bday); }
#kalender .t-holiday { --dot: #dc2626;          --bg: #fef2f2;           --txt: #991b1b; }

/* FullCalendar Agenda / List View Dots */
#kalender .fc-list-item .fc-event-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}
#kalender .fc-list-item.t-company .fc-event-dot { background-color: var(--c-company) !important; }
#kalender .fc-list-item.t-leave .fc-event-dot   { background-color: var(--c-leave) !important; }
#kalender .fc-list-item.t-team .fc-event-dot    { background-color: var(--c-team) !important; }
#kalender .fc-list-item.t-bday .fc-event-dot    { background-color: var(--c-bday) !important; }
#kalender .fc-list-item.t-holiday .fc-event-dot { background-color: #dc2626 !important; }

/* Accessible Center-Aligned Calendar Legend Bar */
.k-legend-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.25rem 2rem;
  flex-wrap: wrap;
  padding: .65rem 1rem;
  background: #f1f5f9;
  border: 2px solid #cbd5e1;
  border-radius: 10px;
  margin-bottom: 1rem;
}

.k-legend-item {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  font-size: .95rem;
  font-weight: 700;
  color: #0f172a;
  user-select: none;
}

.k-legend-item i {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--dot);
  display: inline-block;
  flex-shrink: 0;
}

.k-legend-item.t-company { --dot: var(--c-company); }
.k-legend-item.t-leave   { --dot: var(--c-leave); }
.k-legend-item.t-team    { --dot: var(--c-team); }
.k-legend-item.t-bday    { --dot: var(--c-bday); }

/* ==========================================================================
   FullCalendar Sizing & High-Contrast Visuals
   ========================================================================== */
#kalender .fc-toolbar {
  margin-bottom: 1rem !important;
  flex-wrap: wrap;
  gap: .75rem;
}
#kalender .fc-toolbar h2 {
  font-size: 1.35rem !important;
  font-weight: 800 !important;
  color: var(--ink);
}

/* Toolbar Buttons - Large Touch Targets */
#kalender .fc-button {
  background: #fff !important;
  border: 2px solid #94a3b8 !important;
  color: var(--ink) !important;
  font-size: .95rem !important;
  font-weight: 700 !important;
  border-radius: 8px !important;
  padding: .45rem .85rem !important;
  min-height: 42px !important;
  box-shadow: none !important;
  text-shadow: none !important;
}
#kalender .fc-button.fc-state-active,
#kalender .fc-button:active {
  background: var(--c-company) !important;
  border-color: var(--c-company) !important;
  color: #fff !important;
}

/* Day Header Grid */
#kalender .fc-head-container, #kalender .fc td, #kalender .fc th {
  border-color: var(--line) !important;
}
#kalender .fc th {
  font-size: .95rem !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  color: #0f172a !important;
  padding: .6rem 0 !important;
  background: #e2e8f0 !important;
}
#kalender .fc-day-number {
  font-size: 1rem !important;
  font-weight: 700 !important;
  color: var(--ink) !important;
  padding: 4px 8px !important;
}

/* Today & Selected Day */
#kalender .fc-today {
  background: #e2e8f0 !important;
}
#kalender .fc-today .fc-day-number {
  background: var(--c-company) !important;
  color: #fff !important;
  border-radius: 50% !important;
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin: 2px;
  padding: 0 !important;
}
#kalender .fc-bg td.is-selected {
  box-shadow: inset 0 0 0 3px var(--c-company);
}

/* ==========================================================================
   Sunday Holiday Tinting Styles
   ========================================================================== */
#kalender .fc th.fc-sun {
  color: #991b1b !important;
  background: #fee2e2 !important;
}
#kalender .fc-bg td.fc-sun {
  background: #fef2f2 !important;
}
#kalender .fc-day-top.fc-sun:not(.fc-today) .fc-day-number {
  color: #991b1b !important;
  font-weight: 800 !important;
}

/* Event Pills */
#kalender .fc-day-grid .fc-row {
  min-height: 85px !important;
}
#kalender .fc-day-grid-event {
  border: 0 !important;
  border-left: 4px solid var(--dot) !important;
  background: var(--bg) !important;
  color: var(--txt) !important;
  font-size: .85rem !important;
  font-weight: 700 !important;
  padding: 4px 6px !important;
  margin: 3px 2px !important;
  border-radius: 6px !important;
}

/* ==========================================================================
   Month View Category Rows
   ========================================================================== */
#kalender .fc-month-view .fc-content-skeleton tbody { display: none; }

#kalender .fc-month-view .fc-day-top { text-align: left; vertical-align: top; padding: 4px 6px 8px; }
#kalender .fc-month-view .fc-day-top .fc-day-number {
  float: none !important; display: inline-flex; align-items: center; justify-content: center;
  min-width: 28px; height: 28px; margin: 2px 0 4px -2px;
}

#kalender .k-dots { display: flex; flex-direction: column; gap: 4px; }
#kalender .k-row  { display: flex; align-items: center; gap: 6px; min-width: 0; font-size: .85rem; line-height: 1.3; }
#kalender .k-row i { width: 10px; height: 10px; border-radius: 50%; background: var(--dot); flex: none; }
#kalender .k-row b { font-weight: 800; color: var(--txt); }
#kalender .k-row span { color: var(--muted); font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
#kalender .k-holrow span { color: #991b1b; font-weight: 800; }
#kalender .fc-other-month .k-dots { opacity: .5; }

#kalender .fc-bg td.k-hol { background: #fee2e2 !important; }
#kalender .fc-day-top.k-hol:not(.fc-today) .fc-day-number { color: #991b1b !important; font-weight: 800; }

@media (max-width: 575.98px) {
  #kalender .fc-month-view .fc-day-top { text-align: center; padding: 2px 2px 4px; }
  #kalender .fc-month-view .fc-day-top .fc-day-number { margin-left: 0; }
  #kalender .k-dots { flex-direction: row; flex-wrap: wrap; justify-content: center; gap: 3px 6px; }
  #kalender .k-row span { display: none; }
  #kalender .k-row i { width: 8px; height: 8px; }

  #kalender .fc-toolbar .fc-left {
    display: flex !important;
    align-items: center;
  }

  #kalender .fc-toolbar .fc-left .btn-sm {
    padding: .4rem .8rem !important;
    font-size: .85rem !important;
    border-radius: 6px !important;
  }

  #kalender .fc-toolbar .dropdown-menu {
    box-shadow: 0 10px 25px rgba(0,0,0,.15) !important;
    border-radius: 8px !important;
  }
}

/* ==========================================================================
   Side Panel (Agendas Detail)
   ========================================================================== */
#kalender .row > [class*="col-"] {
  display: flex;
  flex-direction: column;
}

#kalender .k-panel {
  border: 2px solid var(--line);
  border-radius: 12px;
  padding: 1.25rem;
  background: #f8fafc;
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

#kalender .k-panel h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--ink);
  margin: 0 0 .85rem;
  flex: 0 0 auto;
}

#kalender .k-detail-scroll {
  flex: 1 1 0;
  min-height: 0;
  overflow-y: auto;
  padding-right: 6px;
}

/* Event Cards */
#kalender .k-card {
  display: flex;
  align-items: flex-start;
  gap: .85rem;
  padding: .85rem 1rem;
  background: #fff;
  border-radius: 10px;
  border: 2px solid var(--line);
  margin-bottom: .75rem;
}
#kalender .k-card:last-child { margin-bottom: 0; }

#kalender .k-bar {
  width: 6px;
  align-self: stretch;
  border-radius: 3px;
  background: var(--dot);
  flex: none;
}

#kalender .k-card > div:nth-child(2) {
  flex: 1;
  min-width: 0;
}

#kalender .k-card b {
  display: block;
  color: var(--ink);
  font-size: .95rem;
  font-weight: 700;
  line-height: 1.4;
}

#kalender .k-desc {
  font-size: .88rem;
  color: var(--muted);
  margin-top: .3rem;
  line-height: 1.4;
  word-break: break-word;
}

#kalender .k-meta {
  display: flex;
  flex-wrap: wrap;
  gap: .3rem 1rem;
  color: var(--muted);
  font-size: .85rem;
  font-weight: 600;
  margin-top: .4rem;
}

#kalender .k-empty {
  color: var(--muted);
  font-size: .95rem;
  font-weight: 600;
  margin: 0;
  padding: 2.5rem 0;
  text-align: center;
}

/* Card Box */
.k-card-box {
  background: #ffffff;
  border: 2px solid #cbd5e1;
  border-left: 6px solid var(--dot, #1d4ed8);
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: .85rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}

.k-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  padding-bottom: .5rem;
  margin-bottom: .5rem;
  border-bottom: 2px solid #e2e8f0;
}

.k-card-head-title {
  font-size: .95rem;
  font-weight: 800;
  color: #0f172a;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.k-time-tag {
  color: #1d4ed8;
  font-weight: 800;
  font-size: .9rem;
}

.k-card-body-content {
  font-size: .88rem;
  color: #1e293b;
  line-height: 1.5;
}

.k-card-row {
  margin-bottom: .3rem;
}

.k-card-row strong {
  color: #0f172a;
  font-weight: 700;
}

.k-sep {
  margin: 0 .4rem;
  color: #94a3b8;
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

<script>
window.CAL_CFG = {
    firstDay:  0, // 0 = Sunday (Minggu)
    focus:     '<?= esc($focus, 'js') ?>',
    mode:      '<?= esc($mode, 'js') ?>',
    editable:  <?= $editable ? 'true' : 'false' ?>,
    lockMonth: <?= $lockToMonth ? 'true' : 'false' ?>,
    saveUrl:   '<?= esc($saveUrl, 'js') ?>',
    csrfName:  '<?= esc($csrfName, 'js') ?>',
    csrfHash:  '<?= esc($csrfHash, 'js') ?>',
    fcJs:      '<?= esc($fcJs, 'js') ?>',
    moment:    '<?= esc($momentJs, 'js') ?>',
    swal:      '<?= esc($swalJs, 'js') ?>',
    eventsUrl: '<?= site_url('sas/get-calendar-events') ?>'
};
</script>