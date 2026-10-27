<?php
/**
- app/Views/dashboard/calendar_filter.php
- FullCalendar header filter partial - Senior-Friendly Accessibility Version
 */
$divisions = $divisions ?? [];
$branches  = $branches  ?? [];

$categories = [
    'leave'   => ['label' => 'Cuti',          'color' => '#d97706'],
    'holiday' => ['label' => 'Hari Libur',    'color' => '#dc2626'],
    'bday'    => ['label' => 'Ulang Tahun',   'color' => '#7c3aed'],
    'team'    => ['label' => 'Aktivitas Tim', 'color' => '#059669'],
];
?>

<div id="fc-filter-template" class="d-none">
  <div class="kf">

    <!-- Filter Toggle Button -->
    <button type="button" class="kf-btn" data-kf-toggle aria-haspopup="dialog" aria-expanded="false">
      <i class="fas fa-sliders-h" aria-hidden="true"></i>
      <span>Filter Agenda</span>
      <b class="kf-badge" hidden>0</b>
    </button>

    <!-- Dropdown Panel -->
    <div class="kf-panel" role="dialog" aria-label="Filter agenda">

      <div class="kf-head">
        <span class="kf-title"><i class="fas fa-filter me-1 text-primary"></i> Filter Agenda</span>
        <button type="button" class="kf-reset kf-btn-reset">Atur ulang</button>
      </div>

      <!-- Kategori -->
      <div class="kf-field">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="kf-label mb-0">Kategori</span>
        </div>
        <div class="kf-cats" role="group" aria-label="Filter kategori">
          <?php foreach ($categories as $key => $cat): ?>
            <button type="button" 
                    class="kf-chip is-active" 
                    data-type="<?= $key ?>" 
                    aria-pressed="true" 
                    style="--chip-c: <?= $cat['color'] ?>;">
              <span class="kf-chip-dot" aria-hidden="true"></span>
              <span class="kf-chip-text"><?= esc($cat['label']) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Divisi + Cabang -->
      <div class="kf-grid">
        <div class="kf-field">
          <label class="kf-label">Divisi</label>
          <select class="kf-select kf-select-division">
            <option value="">Semua Divisi</option>
            <?php foreach ($divisions as $div): 
              $divId   = is_array($div) ? ($div['id'] ?? '') : ($div->id ?? '');
              $divName = is_array($div) ? ($div['name'] ?? '') : ($div->name ?? '');
            ?>
              <option value="<?= esc($divId, 'attr') ?>"><?= esc($divName) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="kf-field">
          <label class="kf-label">Cabang</label>
          <select class="kf-select kf-select-branch">
            <option value="">Semua Cabang</option>
            <?php foreach ($branches as $br): 
              $brId   = is_array($br) ? ($br['id'] ?? '') : ($br->id ?? '');
              $brName = is_array($br) ? ($br['name'] ?? '') : ($br->name ?? '');
            ?>
              <option value="<?= esc($brId, 'attr') ?>"><?= esc($brName) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <p class="kf-foot"><i class="far fa-check-circle" aria-hidden="true"></i> Filter diterapkan otomatis</p>
    </div>
  </div>
</div>

<style>
.kf { position: relative; display: inline-block; }
.kf *, .kf *::before, .kf *::after { box-sizing: border-box; }

/* Accessible Toggle Button */
.kf-btn {
  display: inline-flex; align-items: center; gap: .6rem;
  padding: .5rem 1rem; border: 2px solid #94a3b8; border-radius: 8px; background: #fff; color: #0f172a;
  font-size: .95rem; font-weight: 700; line-height: 1.5; cursor: pointer; transition: all .15s ease;
  min-height: 42px;
}
.kf-btn i { color: #334155; font-size: .9rem; }
.kf-btn:hover { background: #f1f5f9; border-color: #64748b; }
.kf.is-open .kf-btn { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29, 78, 216, .2); }

/* Badge Sizing */
.kf-badge {
  min-width: 22px; height: 22px; padding: 0 6px; border-radius: 11px; background: #1d4ed8; color: #fff;
  font-size: .8rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;
}
.kf-badge[hidden] { display: none; }

/* High-Contrast Dropdown Panel */
.kf-panel {
  display: none; position: absolute; z-index: 1050; top: calc(100% + 8px); left: 0;
  width: min(360px, calc(100vw - 32px)); padding: 18px;
  background: #fff; border: 2px solid #cbd5e1; border-radius: 14px;
  box-shadow: 0 12px 30px -8px rgba(15, 23, 42, .25); text-align: left; font-weight: 400;
}
.kf.is-open .kf-panel { display: block; animation: kf-in .12s ease-out; }
.kf-panel.align-right { left: auto; right: 0; }

@keyframes kf-in { from { opacity: 0; transform: translateY(-4px); } }

.kf-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.kf-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
.kf-reset { border: 0; background: none; padding: .3rem .6rem; font-size: .88rem; font-weight: 700; color: #dc2626; cursor: pointer; border-radius: 6px; }
.kf-reset:hover { background: #fee2e2; text-decoration: underline; }

.kf-field { margin-bottom: 16px; min-width: 0; }
.kf-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.kf-label { display: block; margin: 0 0 8px; font-size: .9rem; font-weight: 700; color: #0f172a; }

/* Senior-Friendly Category Chips */
.kf-cats { display: flex; flex-wrap: wrap; gap: 8px; }
.kf-chip {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .5rem .9rem; border: 2px solid #cbd5e1; border-radius: 999px;
  background: #f8fafc; color: #334155; font-size: .9rem; font-weight: 700;
  cursor: pointer; transition: background .12s ease, border-color .12s ease, opacity .12s ease; user-select: none;
  min-height: 38px;
}
.kf-chip * { pointer-events: none; }

.kf-chip-dot {
  width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0;
  background-color: #94a3b8; transition: background-color .12s ease;
}

.kf-chip:hover { background: #e2e8f0; border-color: #94a3b8; color: #0f172a; }

/* Active Pressed State */
.kf-chip.is-active,
.kf-chip[aria-pressed="true"] {
  background: #fff !important;
  border-color: var(--chip-c, #1d4ed8) !important;
  color: #0f172a !important;
  opacity: 1 !important;
  box-shadow: 0 2px 4px rgba(0,0,0,.08);
}
.kf-chip.is-active .kf-chip-dot,
.kf-chip[aria-pressed="true"] .kf-chip-dot {
  background-color: var(--chip-c, #1d4ed8) !important;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9), 0 0 6px var(--chip-c, #1d4ed8);
}

/* Inactive State */
.kf-chip:not(.is-active):not([aria-pressed="true"]) {
  opacity: .6 !important;
  background: #f1f5f9 !important;
  border-color: #cbd5e1 !important;
  color: #64748b !important;
}

/* Large Select Controls */
.kf-select {
  width: 100%; height: 44px; padding: 0 .85rem; border: 2px solid #cbd5e1; border-radius: 8px;
  background: #fff; color: #0f172a; font-size: .9rem; font-weight: 600; transition: all .15s ease;
  appearance: none; -webkit-appearance: none; padding-right: 32px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none' stroke='%230f172a' stroke-width='2'%3E%3Cpath d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 10px center;
}
.kf-select:focus { outline: 0; border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29, 78, 216, .2); }

.kf-foot { margin: 4px 0 0; padding-top: 10px; border-top: 2px solid #f1f5f9; font-size: .85rem; font-weight: 600; color: #334155; }
.kf-foot i { color: #059669; font-size: 1rem; margin-right: 6px; }

@media (max-width: 575.98px) {
  .kf-grid { grid-template-columns: 1fr; }
}
</style>