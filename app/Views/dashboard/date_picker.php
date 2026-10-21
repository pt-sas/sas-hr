<?php
/**
- app/Views/dashboard/date_picker.php - Senior-Friendly Accessibility Version
 */

/*
- Scope feature for higher employees in hierarchy to see 
- either whole view (div and branch under) or team
*/
$modes  = $modes ?? ['day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan'];
$mode   = in_array($mode ?? 'month', ['day', 'week', 'month'], true) ? $mode : 'month';

$levels = $levels ?? ['me' => 'Saya', 'team' => 'Tim Saya', 'all' => 'Semua'];
$level  = $level ?? 'me';
$level  = array_key_exists($level, $levels) ? $level : array_key_first($levels);
$levelIcons = ['me' => 'far fa-user', 'team' => 'fas fa-user-friends', 'all' => 'far fa-building'];

$today  = $today ?? date('Y-m-d');
$date   = $date ?? $today;
$label  = $label ?? date('M Y', strtotime($date));
?>

<div class="dp" data-dp data-mode="<?= esc($mode, 'attr') ?>" data-level="<?= esc($level, 'attr') ?>" data-date="<?= esc($date, 'attr') ?>" data-today="<?= esc($today, 'attr') ?>">
  <div class="dp-bar">

    <!-- LEFT: period label -->
    <div class="dp-now" data-dp-now aria-live="polite"></div>

    <!-- CENTER: scope (Commented out in original, styled and ready if enabled) -->
    <!-- <div class="dp-center">
      <?php if (count($levels) > 1): ?>
        <div class="dp-seg dp-seg--scope" role="group" aria-label="Lingkup data">
          <?php foreach ($levels as $lKey => $lText): ?>
            <button type="button" data-dp-level="<?= esc($lKey, 'attr') ?>" aria-pressed="<?= $level === $lKey ? 'true' : 'false' ?>">
              <i class="<?= esc($levelIcons[$lKey] ?? 'far fa-circle', 'attr') ?>" aria-hidden="true"></i><span><?= esc($lText) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div> -->

    <!-- RIGHT: mode + nav + today -->
    <div class="dp-right">
      <div class="dp-seg" role="group" aria-label="Tampilan periode">
        <?php foreach ($modes as $key => $text): ?>
          <button type="button" data-dp-mode="<?= esc($key, 'attr') ?>" aria-pressed="<?= $mode === $key ? 'true' : 'false' ?>"><?= esc($text) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="dp-nav">
        <button type="button" class="dp-step" data-dp-nav="-1" aria-label="Sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
        <button type="button" class="dp-label" data-dp-toggle aria-haspopup="dialog" aria-expanded="false">
          <i class="far fa-calendar-alt" aria-hidden="true"></i><span data-dp-label><?= esc($label) ?></span>
        </button>
        <button type="button" class="dp-step" data-dp-nav="1" aria-label="Berikutnya"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
      </div>

      <button type="button" class="dp-today" data-dp-today>Hari ini</button>
    </div>
  </div>

  <div class="dp-pop" data-pop role="dialog" aria-label="Pilih tanggal" hidden></div>
</div>

<style>
.dp { 
  --dp-accent: #1d4ed8; 
  --dp-soft: #eff6ff; 
  --dp-scope: #0369a1; 
  --dp-line: #cbd5e1; 
  --dp-ink: #0f172a; 
  --dp-mute: #334155;
  position: relative; 
  display: inline-block; 
  z-index: 100; 
  width: 100%; 
}

.dp-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  width: 100%;
}

.dp button { font: inherit; cursor: pointer; border: 0; background: none; color: inherit; }
.dp button:focus-visible { outline: 3px solid var(--dp-accent); outline-offset: 2px; }

/* LEFT */
.dp-now {
  justify-self: start;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--dp-ink);
  white-space: nowrap;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* CENTER */
.dp-center {
  justify-self: center;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}

/* RIGHT */
.dp-right {
  justify-self: end;
  display: inline-flex;
  align-items: center;
  gap: .75rem;
}

/* Segmented Controls - Large Touch Targets */
.dp-seg { 
  display: inline-flex; 
  gap: 4px; 
  padding: 4px; 
  background: #e2e8f0; 
  border-radius: 999px; 
  border: 1px solid #cbd5e1;
}
.dp-seg button {
  display: inline-flex; 
  align-items: center; 
  gap: .5rem; 
  height: 40px; 
  padding: 0 1.1rem; 
  border-radius: 999px;
  font-size: .9rem; 
  font-weight: 700; 
  color: var(--dp-mute); 
  transition: background .15s, color .15s, box-shadow .15s; 
  white-space: nowrap;
}
.dp-seg button i { font-size: .85rem; opacity: .9; }
.dp-seg button:hover { color: var(--dp-ink); background: #f1f5f9; }
.dp-seg button[aria-pressed="true"] { 
  background: #fff; 
  color: var(--dp-accent); 
  font-weight: 800; 
  box-shadow: 0 2px 4px rgba(15, 23, 42, .15); 
}
.dp-seg--scope button[aria-pressed="true"] { color: var(--dp-scope); }

/* Navigation Pill - High Contrast & Large Touch Area */
.dp-nav { 
  display: inline-flex; 
  align-items: center; 
  height: 44px; 
  padding: 0 4px; 
  background: #fff; 
  border: 2px solid #94a3b8; 
  border-radius: 999px; 
}
.dp-step { 
  width: 36px; 
  height: 36px; 
  border-radius: 50%; 
  color: var(--dp-ink); 
  font-size: .85rem; 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  transition: background .15s, color .15s; 
}
.dp-step:hover { background: var(--dp-soft); color: var(--dp-accent); }

.dp-label { 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  gap: .55rem; 
  height: 36px; 
  min-width: 150px; 
  padding: 0 1rem; 
  border-radius: 999px;
  font-size: .95rem; 
  font-weight: 800; 
  color: var(--dp-ink); 
  white-space: nowrap; 
  transition: background .15s; 
}
.dp-label i { color: var(--dp-accent); font-size: .95rem; }
.dp-label:hover, .dp-label[aria-expanded="true"] { background: var(--dp-soft); }

/* Today Button */
.dp-today { 
  height: 44px; 
  padding: 0 1.25rem; 
  border: 2px solid #94a3b8; 
  border-radius: 999px; 
  background: #fff; 
  font-size: .9rem; 
  font-weight: 800; 
  color: var(--dp-accent); 
  transition: all .15s; 
  white-space: nowrap; 
}
.dp-today:hover:not(:disabled) { 
  background: var(--dp-accent); 
  border-color: var(--dp-accent); 
  color: #fff; 
}
.dp-today:disabled { color: #64748b; background: #f1f5f9; border-color: #cbd5e1; cursor: default; }

/* Accessible Popover Calendar */
.dp-pop { 
  position: absolute; 
  z-index: 1050; 
  top: calc(100% + 8px); 
  right: 0; 
  width: 340px; 
  padding: 1rem; 
  background: #fff; 
  border: 2px solid #cbd5e1; 
  border-radius: 16px; 
  box-shadow: 0 16px 36px -10px rgba(15, 23, 42, .25); 
}
.dp-pop[hidden] { display: none; }

.dp-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem; }
.dp-title { font-weight: 800; font-size: 1rem; padding: .35rem .6rem; border-radius: 8px; color: var(--dp-ink); }
button.dp-title:hover { background: var(--dp-soft); color: var(--dp-accent); }

.dp-row { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; border-radius: 10px; }
.dp-dow { text-align: center; font-size: .85rem; font-weight: 700; color: var(--dp-mute); padding: .35rem 0; }
.dp-months { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }

.dp-cell { height: 42px; border-radius: 8px; font-size: .9rem; font-weight: 700; color: var(--dp-ink); }
.dp-months .dp-cell { height: 48px; }
.dp-cell:hover { background: var(--dp-soft); color: var(--dp-accent); }
.dp-cell.is-today { box-shadow: inset 0 0 0 2px var(--dp-accent); }
.dp-cell.is-sel, .dp-cell.is-sel:hover { background: var(--dp-accent); color: #fff; font-weight: 800; }

.dp-foot { margin-top: .75rem; padding-top: .65rem; border-top: 2px solid #f1f5f9; display: flex; justify-content: flex-end; }
.dp-foot button { font-size: .85rem; font-weight: 800; color: var(--dp-accent); padding: .35rem .75rem; border-radius: 8px; }
.dp-foot button:hover { background: var(--dp-soft); }

.dp-weeks .dp-row:not(:first-child):hover { background: var(--dp-soft); }
.dp-weeks .dp-row.is-selweek { background: var(--dp-soft); }
.dp-weeks .dp-cell:hover { background: transparent; }
.dp-weeks .dp-row.is-selweek .dp-cell:not(.is-sel) { background: transparent; }

@media (max-width: 767.98px) {
  .dp-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .dp-now    { text-align: left; }
  .dp-center { justify-content: flex-start; }
  .dp-right  { justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
  .dp-seg    { display: flex; flex: 1 1 100%; }
  .dp-seg button { flex: 1; justify-content: center; }
  .dp-nav    { flex: 1; justify-content: space-between; }
  .dp-label  { flex: 1; }
  .dp-today  { flex: 1 1 100%; text-align: center; }
  .dp-pop    { right: auto; left: 0; width: min(340px, calc(100vw - 32px)); }
}

@media (prefers-reduced-motion: no-preference) { .dp-pop:not([hidden]) { animation: dp-in .12s ease-out; } }
@keyframes dp-in { from { opacity: 0; transform: translateY(-4px); } }
</style>