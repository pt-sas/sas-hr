<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<?php
    $hasActiveCycle = !empty($active_cycle);
    $cycId      = is_object($active_cycle) ? ($active_cycle->md_appraisal_cycle_id ?? '') : ($active_cycle['md_appraisal_cycle_id'] ?? '');
    $cycTitle   = is_object($active_cycle) ? ($active_cycle->title ?? '') : ($active_cycle['title'] ?? '');
    $cycStart   = is_object($active_cycle) ? ($active_cycle->start_date ?? '') : ($active_cycle['start_date'] ?? '');
    $cycEnd     = is_object($active_cycle) ? ($active_cycle->end_date ?? '') : ($active_cycle['end_date'] ?? '');
    $cycDlFill  = is_object($active_cycle) ? ($active_cycle->deadline_filling ?? '') : ($active_cycle['deadline_filling'] ?? '');
    $cycDlRev   = is_object($active_cycle) ? ($active_cycle->deadline_review ?? '') : ($active_cycle['deadline_review'] ?? '');
    $cycDlRead  = is_object($active_cycle) ? ($active_cycle->deadline_reading ?? '') : ($active_cycle['deadline_reading'] ?? '');
    $cycStatus  = is_object($active_cycle) ? ($active_cycle->status ?? 'OPEN') : ($active_cycle['status'] ?? 'OPEN');

    $cycCode     = $cycId !== '' ? '#' . $cycId : '-';
    $deadlineRaw = !empty($cycDlFill) ? $cycDlFill : (!empty($cycEnd) ? $cycEnd : '');
    $deadlineTs  = $deadlineRaw !== '' ? strtotime($deadlineRaw) : false;
    $deadlineStr = $deadlineTs ? date('d M Y', $deadlineTs) : '-';
    $periodStr   = (!empty($cycStart) && !empty($cycEnd))
        ? date('d M Y', strtotime($cycStart)) . ' – ' . date('d M Y', strtotime($cycEnd))
        : '';

    $activeDate = !empty($current_date) ? $current_date : date('Y-m-d');

    // Deadline state: ok / soon (<= 3 days) / late / none
    $daysLeft = $deadlineTs ? (int) floor(($deadlineTs - strtotime($activeDate)) / 86400) : null;
    if ($daysLeft === null) {
        $dlState = 'none';
        $dlText  = 'Tanpa batas waktu';
    } elseif ($daysLeft < 0) {
        $dlState = 'late';
        $dlText  = 'Terlambat ' . abs($daysLeft) . ' hari';
    } elseif ($daysLeft === 0) {
        $dlState = 'soon';
        $dlText  = 'Berakhir hari ini';
    } else {
        $dlState = $daysLeft <= 3 ? 'soon' : 'ok';
        $dlText  = $daysLeft . ' hari lagi';
    }

    // Workflow stages
    if (!empty($active_cycle) && (method_exists($active_cycle, 'getStageDeadlines') || method_exists($active_cycle, 'getStagedDeadlines') || method_exists($active_cycle, 'getStagedDeadline'))) {
        $stages = method_exists($active_cycle, 'getStageDeadlines') ? $active_cycle->getStageDeadlines() : (method_exists($active_cycle, 'getStagedDeadlines') ? $active_cycle->getStagedDeadlines() : $active_cycle->getStagedDeadline());
    } else {
        $stages = [
            1 => ['name' => 'Assignment',         'sub' => 'HR memulai',          'date' => null, 'date_fmt' => '-'],
            2 => ['name' => 'Form filling',       'sub' => 'Penilai mengisi',     'date' => null, 'date_fmt' => '-'],
            3 => ['name' => 'Review superior',     'sub' => 'Atasan meninjau',     'date' => null, 'date_fmt' => '-'],
            4 => ['name' => 'Pembacaan karyawan', 'sub' => 'Karyawan menyetujui', 'date' => null, 'date_fmt' => '-'],
            5 => ['name' => 'Finalisasi',         'sub' => 'Finalisasi penilaian', 'date' => null, 'date_fmt' => '-'],
        ];
    }

    // Active stage resolution based on active date
    $currentStage = isset($current_stage) 
        ? (int) $current_stage 
        : (($activeCycle && method_exists($activeCycle, 'getCurrentStage')) ? $activeCycle->getCurrentStage($activeDate) : 2);
?>

<link rel="stylesheet" href="/form_appraisal_filling_style.css">

<style>
    /* Hide template Add New button for Task List since appraisals are cycle-generated */
    .card-header .new_form { display: none !important; }

    /* Scoped styles for the appraisal task list (Atlantis Pro tokens) */
    .atl-page { --atl-primary:#1572E8; --atl-success:#31CE36; --atl-warning:#FFAD46; --atl-info:#48ABF7; --atl-danger:#F25961;
                --atl-ink:#1f2937; --atl-sub:#4b5563; --atl-line:#e5e9f0; --atl-soft:#f6f8fb; }
    .atl-page .atl-card { background:#fff; border:1px solid var(--atl-line); border-radius:12px; margin-bottom:20px; box-shadow:0 1px 2px rgba(16,24,40,.04); }

    /* Hero */
    .atl-hero { padding:22px 24px; display:flex; flex-wrap:wrap; gap:20px; align-items:center; justify-content:space-between;
                border-left:4px solid var(--atl-primary); }
    .atl-eyebrow { font-size:12px; letter-spacing:.06em; text-transform:uppercase; color:var(--atl-sub); font-weight:700; margin:0 0 4px; }
    .atl-hero-title { font-size:22px; font-weight:800; color:var(--atl-ink); margin:0 0 10px; line-height:1.25; }
    .atl-chips { display:flex; flex-wrap:wrap; gap:8px; }
    .atl-chip { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--atl-ink); background:var(--atl-soft);
                border:1px solid var(--atl-line); border-radius:999px; padding:4px 12px; }
    .atl-chip i { color:var(--atl-sub); }
    .atl-hero-side { display:flex; align-items:stretch; gap:12px; flex-wrap:wrap; }
    .atl-deadline { min-width:190px; border-radius:10px; padding:12px 16px; border:1px solid var(--atl-line); background:var(--atl-soft); }
    .atl-deadline small { display:block; font-size:12px; color:var(--atl-sub); font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
    .atl-deadline strong { display:block; font-size:18px; color:var(--atl-ink); margin:2px 0; }
    .atl-deadline span { font-size:13px; font-weight:700; }
    .atl-deadline--ok span { color:#1a7f1f; }
    .atl-deadline--soon { background:#fff7ea; border-color:#ffd9a3; } .atl-deadline--soon span { color:#8a4b00; }
    .atl-deadline--late { background:#fdecee; border-color:#f7b9bd; } .atl-deadline--late span { color:#a5262e; }
    .atl-deadline--none span { color:var(--atl-sub); }

    /* Pills (never color-only: always icon + text) */
    .atl-pill { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; border-radius:999px; padding:4px 10px; white-space:nowrap; border:1px solid transparent; }
    .atl-pill--ok      { background:#e8f8e9; color:#1a7f1f; border-color:#bfe9c1; }
    .atl-pill--warning { background:#fff3e0; color:#8a4b00; border-color:#ffd9a3; }
    .atl-pill--danger  { background:#fdecee; color:#a5262e; border-color:#f7b9bd; }
    .atl-pill--info    { background:#e6f3fe; color:#0b5c9e; border-color:#b8dcfa; }
    .atl-pill--primary { background:#e7f0fd; color:#0d4fb3; border-color:#b5cff7; }
    .atl-pill--muted   { background:#eef0f4; color:#374151; border-color:#d9dde5; }

    /* Stepper */
    .atl-stepper { list-style:none; margin:0; padding:18px 24px; display:flex; gap:0; }
    .atl-step { flex:1; position:relative; text-align:center; padding:0 6px; }
    .atl-step::before { content:""; position:absolute; top:15px; left:-50%; width:100%; height:2px; background:var(--atl-line); z-index:0; }
    .atl-step:first-child::before { display:none; }
    .atl-step.is-done::before, .atl-step.is-current::before { background:var(--atl-primary); }
    .atl-dot { position:relative; z-index:1; width:32px; height:32px; margin:0 auto 8px; border-radius:50%; display:flex; align-items:center; justify-content:center;
               font-size:13px; font-weight:800; background:#fff; border:2px solid var(--atl-line); color:var(--atl-sub); }
    .atl-step.is-done .atl-dot { background:var(--atl-success); border-color:var(--atl-success); color:#fff; }
    .atl-step.is-current .atl-dot { background:var(--atl-primary); border-color:var(--atl-primary); color:#fff; box-shadow:0 0 0 4px rgba(21,114,232,.15); }
    .atl-step-name { font-size:14px; font-weight:700; color:var(--atl-ink); margin:0; }
    .atl-step-sub { font-size:12px; color:var(--atl-sub); margin:0; }
    .atl-step:not(.is-done):not(.is-current) .atl-step-name { color:var(--atl-sub); }
    .atl-step-clickable { cursor: pointer; transition: transform .15s ease; }
    .atl-step-clickable:hover { transform: translateY(-2px); }
    .atl-step-clickable:hover .atl-dot { border-color: var(--atl-primary); color: var(--atl-primary); box-shadow: 0 0 0 3px rgba(21,114,232,.15); }
    .atl-debug-bar { border-left: 4px solid var(--atl-warning); background: #fffdf8; }

    /* Task panel */
    .atl-panel-head { padding:20px 24px 0; }
    .atl-panel-title { font-size:18px; font-weight:800; color:var(--atl-ink); margin:0; }
    .atl-panel-sub { font-size:13px; color:var(--atl-sub); margin:2px 0 0; }
    .atl-tabs { display:flex; gap:4px; flex-wrap:wrap; margin:16px 0 0; padding:0 24px; border-bottom:1px solid var(--atl-line); list-style:none; }
    .atl-tab { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; font-size:14px; font-weight:700; color:var(--atl-sub);
               border:0; background:transparent; border-bottom:3px solid transparent; margin-bottom:-1px; cursor:pointer; min-height:44px; }
    .atl-tab:hover { color:var(--atl-ink); }
    .atl-tab[aria-selected="true"] { color:var(--atl-primary); border-bottom-color:var(--atl-primary); }
    .atl-count { min-width:24px; padding:1px 8px; border-radius:999px; font-size:12px; font-weight:800; background:#eef0f4; color:#374151; text-align:center; }
    .atl-tab[aria-selected="true"] .atl-count { background:var(--atl-primary); color:#fff; }
    .atl-toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; padding:14px 24px; }
    .atl-segment { display:flex; flex-wrap:wrap; gap:6px; }
    .atl-seg { border:1px solid var(--atl-line); background:#fff; color:var(--atl-ink); font-size:13px; font-weight:600; border-radius:999px; padding:6px 14px; min-height:36px; cursor:pointer; }
    .atl-seg:hover { background:var(--atl-soft); }
    .atl-seg[aria-pressed="true"] { background:var(--atl-ink); color:#fff; border-color:var(--atl-ink); }
    .atl-reload { width:40px; height:40px; border-radius:50%; border:1px solid var(--atl-line); background:#fff; color:var(--atl-sub); cursor:pointer; }
    .atl-reload:hover { background:var(--atl-soft); color:var(--atl-primary); }
    .atl-reload.is-spinning i { animation:atl-spin .8s linear infinite; }
    @keyframes atl-spin { to { transform:rotate(360deg); } }
    .atl-page :focus-visible { outline:3px solid rgba(21,114,232,.55); outline-offset:2px; border-radius:6px; }

    /* Table */
    .atl-table-wrap { padding:0 24px 20px; }
    #table_task_list thead th { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--atl-sub); background:var(--atl-soft); border-bottom:1px solid var(--atl-line) !important; border-top:0; padding:12px; }
    #table_task_list tbody td { padding:14px 12px; border-top:1px solid var(--atl-line); vertical-align:middle; }
    #table_task_list tbody tr:hover { background:#f9fbff; }
    .atl-emp { display:flex; align-items:center; gap:12px; text-align:left; }
    .atl-avatar { flex:0 0 40px; width:40px; height:40px; border-radius:50%; background:#e7f0fd; color:#0d4fb3; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; }
    .atl-emp-name { font-weight:700; color:var(--atl-ink); margin:0; line-height:1.25; }
    .atl-emp-meta { font-size:12.5px; color:var(--atl-sub); margin:2px 0 0; }
    .atl-steps-mini { display:inline-flex; gap:4px; vertical-align:middle; margin-right:8px; }
    .atl-steps-mini i { width:18px; height:6px; border-radius:3px; background:var(--atl-line); display:block; }
    .atl-steps-mini i.on { background:var(--atl-primary); }
    .atl-steps-mini.is-final i.on { background:var(--atl-success); }
    .atl-stage-label { font-size:13px; font-weight:700; color:var(--atl-ink); }
    .atl-empty, .atl-error { text-align:center; padding:36px 12px; color:var(--atl-sub); }
    .atl-empty i, .atl-error i { font-size:34px; margin-bottom:10px; display:block; }
    .atl-error { color:#a5262e; }
    .atl-processing { font-size:13px; color:var(--atl-sub); }
    .atl-alert { margin:0 24px 16px; border-radius:10px; border:1px solid #f7b9bd; background:#fdecee; color:#a5262e; padding:12px 16px; display:none; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .atl-alert.is-visible { display:flex; }
    .atl-vh { position:absolute !important; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }

    /* Empty cycle */
    .atl-nocycle { text-align:center; padding:56px 20px; color:var(--atl-sub); }
    .atl-nocycle i { font-size:44px; color:#9aa4b5; margin-bottom:12px; display:block; }
    .atl-nocycle h4 { color:var(--atl-ink); font-weight:800; }

    /* Audit timeline */
    .atl-tl { list-style:none; margin:0; padding:0 0 0 22px; border-left:2px solid var(--atl-line); }
    .atl-tl-item { position:relative; padding:0 0 18px 18px; }
    .atl-tl-item::before { content:""; position:absolute; left:-30px; top:4px; width:14px; height:14px; border-radius:50%; background:#fff; border:3px solid var(--atl-primary); }
    .atl-tl-head { display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:4px; }
    .atl-tl-time { font-size:12.5px; color:var(--atl-sub); }
    .atl-tl-line { font-size:13px; color:var(--atl-sub); margin:2px 0; }
    .atl-tl-note { font-size:13px; color:var(--atl-ink); background:var(--atl-soft); border:1px solid var(--atl-line); border-radius:8px; padding:8px 10px; margin-top:6px; }

    /* Mobile: stepper vertical, table rows become cards */
    @media (max-width: 767.98px) {
        .atl-hero, .atl-stepper, .atl-panel-head, .atl-toolbar, .atl-table-wrap { padding-left:16px; padding-right:16px; }
        .atl-tabs { padding:0 8px; }
        .atl-stepper { flex-direction:column; gap:14px; }
        .atl-step { display:flex; align-items:center; gap:12px; text-align:left; padding:0; }
        .atl-step::before { display:none; }
        .atl-dot { margin:0; flex:0 0 32px; }
        #table_task_list thead { display:none; }
        #table_task_list, #table_task_list tbody, #table_task_list tr, #table_task_list td { display:block; width:100% !important; }
        #table_task_list tbody tr { border:1px solid var(--atl-line); border-radius:10px; margin-bottom:12px; padding:6px 4px; }
        #table_task_list tbody td { border:0; display:flex; justify-content:space-between; align-items:center; gap:12px; text-align:right !important; padding:8px 12px; }
        #table_task_list tbody td::before { content:attr(data-label); font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--atl-sub); font-weight:700; text-align:left; flex:0 0 auto; }
        #table_task_list tbody td.atl-td-emp { display:block; text-align:left !important; }
        #table_task_list tbody td.atl-td-emp::before { display:none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .atl-reload.is-spinning i, .fa-spin { animation:none !important; }
    }
</style>

<!-- CARD FORM (Action T standard: hidden by default in custom.css) -->
<div class="card-body card-form" id="card_form_container"></div>

<!-- Filter form for Process.js datatables -->
<div class="d-none filter_page">
    <form id="filter_task_list">
        <input type="hidden" name="role_tab" id="filter_role_tab" value="<?= esc($default_tab ?? 'EVALUATOR') ?>">
    </form>
</div>

<div class="card-body card-main atl-page">

    <?php if (!$hasActiveCycle): ?>
    <!-- =================================================================== -->
    <!-- EMPTY STATE: NO ACTIVE CYCLE                                        -->
    <!-- =================================================================== -->
    <div class="atl-card">
        <div class="atl-nocycle">
            <i class="far fa-calendar-times" aria-hidden="true"></i>
            <h4>Belum ada siklus penilaian aktif</h4>
            <p class="mb-0">Tugas evaluasi akan muncul di sini setelah HR membuka siklus penilaian.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- =================================================================== -->
    <!-- 1. SIKLUS AKTIF (HERO)                                              -->
    <!-- =================================================================== -->
    <section class="atl-card atl-hero" aria-label="Siklus aktif">
        <div>
            <p class="atl-eyebrow">Siklus penilaian aktif</p>
            <h1 class="atl-hero-title"><?= esc($cycTitle ?: 'Performance Review ' . date('Y')) ?></h1>
            <div class="atl-chips">
                <span class="atl-chip"><i class="fas fa-hashtag" aria-hidden="true"></i>Kode <strong><?= esc($cycCode) ?></strong></span>
                <?php if ($periodStr !== ''): ?>
                <span class="atl-chip"><i class="far fa-calendar-alt" aria-hidden="true"></i>Periode <strong><?= esc($periodStr) ?></strong></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="atl-hero-side">
            <div id="hero_deadline_box" class="atl-deadline atl-deadline--<?= $dlState ?>" data-fill="<?= esc($cycDlFill) ?>" data-review="<?= esc($cycDlRev) ?>" data-reading="<?= esc($cycDlRead) ?>" data-end="<?= esc($cycEnd) ?>">
                <small id="hero_deadline_label">Deadline Pengisian</small>
                <strong id="hero_deadline_date"><?= esc($deadlineStr) ?></strong>
                <span id="hero_deadline_desc">
                    <i class="<?= $dlState === 'late' ? 'fas fa-exclamation-circle' : ($dlState === 'soon' ? 'fas fa-hourglass-half' : 'far fa-clock') ?> mr-1" aria-hidden="true"></i><?= esc($dlText) ?>
                </span>
            </div>
            <div class="d-flex align-items-start">
                <!-- <span class="atl-pill atl-pill--ok">
                    <i class="fas fa-play-circle" aria-hidden="true"></i><?= esc($cycStatus === 'OPEN' ? 'IN PROGRESS' : $cycStatus) ?>
                </span> -->
            </div>
        </div>
    </section>

    <!-- =================================================================== -->
    <!-- 2. TAHAPAN WORKFLOW (STEPPER)                                       -->
    <!-- =================================================================== -->
    <nav class="atl-card" aria-label="Tahapan workflow">
        <ol class="atl-stepper">
            <?php foreach ($stages as $n => $st):
                $cls = $n < $currentStage ? 'is-done' : ($n === $currentStage ? 'is-current' : '');
            ?>
            <li class="atl-step <?= $cls ?>" <?= $n === $currentStage ? 'aria-current="step"' : '' ?>>
                <div class="atl-dot"><?= $n ?></div>
                <p class="atl-step-name"><?= esc($st['name']) ?></p>
                <p class="atl-step-sub"><?= esc($st['sub']) ?></p>
                <?php if (!empty($st['date_fmt']) && $st['date_fmt'] !== '-'): ?>
                    <small class="text-muted d-block mt-1" style="font-size: 11.5px; font-weight: 600;">
                        </i><?= esc($st['date_fmt']) ?>
                    </small>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <!-- =================================================================== -->
    <!-- 3. DAFTAR TUGAS EVALUASI KARYAWAN                                   -->
    <!-- =================================================================== -->
    <section class="atl-card" aria-label="Daftar tugas evaluasi karyawan">

        <!-- Role Tabs Navigation -->
        <ul class="atl-tabs" id="appraisal_role_tabs" role="tablist" aria-label="Peran penilaian">
            <?php if (!empty($can_evaluator)): ?>
            <li role="presentation">
                <button type="button" class="atl-tab" id="tab_evaluator" role="tab" aria-selected="<?= ($default_tab ?? 'EVALUATOR') === 'EVALUATOR' ? 'true' : 'false' ?>" tabindex="<?= ($default_tab ?? 'EVALUATOR') === 'EVALUATOR' ? '0' : '-1' ?>" data-role="EVALUATOR">
                    <i class="fas fa-edit" aria-hidden="true"></i> Penilaian Langsung
                    <span class="atl-count ml-1"><?= (int) ($count_evaluator ?? 0) ?></span>
                </button>
            </li>
            <?php endif; ?>
            <?php if (!empty($can_gm)): ?>
            <li role="presentation">
                <button type="button" class="atl-tab" id="tab_gm" role="tab" aria-selected="<?= ($default_tab ?? 'EVALUATOR') === 'GM' ? 'true' : 'false' ?>" tabindex="<?= ($default_tab ?? 'EVALUATOR') === 'GM' ? '0' : '-1' ?>" data-role="GM">
                    <i class="fas fa-user-tie" aria-hidden="true"></i> Review Atasan
                    <span class="atl-count ml-1"><?= (int) ($count_gm ?? 0) ?></span>
                </button>
            </li>
            <?php endif; ?>
            <?php if (!empty($can_employee)): ?>
            <li role="presentation">
                <button type="button" class="atl-tab" id="tab_employee" role="tab" aria-selected="<?= ($default_tab ?? 'EVALUATOR') === 'EMPLOYEE' ? 'true' : 'false' ?>" tabindex="<?= ($default_tab ?? 'EVALUATOR') === 'EMPLOYEE' ? '0' : '-1' ?>" data-role="EMPLOYEE">
                    <i class="fas fa-user-check" aria-hidden="true"></i> Penilaian Saya
                    <span class="atl-count ml-1"><?= (int) ($count_employee ?? 0) ?></span>
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Main Datatables Overview Section -->
        <div class="atl-table-wrap">
            <div class="table-responsive">
                <table class="table table-striped table-hover tb_display" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th>Karyawan</th>
                            <th>Divisi & Posisi</th>
                            <th>Penilai</th>
                            <th class="text-center">Siklus Penilaian</th>
                            <th class="text-center">Tahapan</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-center" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated via Server-Side DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <?php endif; ?>

</div>

<!-- =================================================================== -->
<!-- SCORE COMPARISON & HISTORY MODAL                                    -->
<!-- =================================================================== -->
<div class="modal fade" id="modal_score_comparison" modal-type="not-main" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document" style="max-width: 1280px; width: 95vw;">
        <div class="modal-content shadow-lg" style="border-radius: 12px; border: 0; overflow: hidden; background: var(--ap-surface-2);">
            <div id="score_comparison_container">
                <!-- Dynamically populated via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- AUDIT LOG MODAL                                                     -->
<!-- =================================================================== -->
<div class="modal fade" id="modal_audit_log" modal-type="not-main" tabindex="-1" role="dialog" aria-labelledby="modal_audit_title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="modal_audit_title">
                    <i class="fas fa-history mr-2 text-primary" aria-hidden="true"></i>Riwayat Alur Penilaian (Audit Trail)
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="audit_timeline_container" aria-live="polite">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                        <p class="mt-2 mb-0 small">Memuat riwayat log...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function initTaskList() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(initTaskList, 30);
            return;
        }
        var $ = window.jQuery;
        var activeRole = $('#filter_role_tab').val() || 'EVALUATOR';
        var auditTrigger = null;

        function esc(v) {
            return String(v === null || v === undefined ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Standard Action T Dynamic Form Population (matching v_appraisal_template.php:414-426)
        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (settings.url && settings.url.indexOf('penilaian/show/') !== -1) {
                try {
                    var res = xhr.responseJSON || JSON.parse(xhr.responseText);
                    var r = Array.isArray(res) ? res[0] : res;
                    if (r && r.success && r.message && r.message.html) {
                        $('#card_form_container').html(r.message.html);
                        // Ensure save button in card header is enabled and accessible
                        $('.save_form').removeAttr('disabled').prop('disabled', false);
                    }
                } catch (e) {}
            }
        });

        // Role Tab Switch (ARIA tabs update filter and query showAll via .btn_requery)
        function selectTab($tab) {
            $('#appraisal_role_tabs .atl-tab').attr({ 'aria-selected': 'false', tabindex: '-1' });
            $tab.attr({ 'aria-selected': 'true', tabindex: '0' }).trigger('focus');
            activeRole = $tab.data('role') || 'EVALUATOR';
            updateHeroDeadline(activeRole);
            $('#filter_role_tab').val(activeRole);
            $('.btn_requery').click();
        }

        function updateHeroDeadline(role) {
            var $box = $('#hero_deadline_box');
            if (!$box.length) return;

            var rawDate = '';
            var label = 'Deadline';
            if (role === 'GM') {
                rawDate = $box.data('review') || $box.data('end') || '';
                label = 'Deadline Review';
            } else if (role === 'EMPLOYEE') {
                rawDate = $box.data('reading') || $box.data('end') || '';
                label = 'Deadline Pembacaan';
            } else {
                rawDate = $box.data('fill') || $box.data('end') || '';
                label = 'Deadline Pengisian';
            }

            $('#hero_deadline_label').text(label);

            if (!rawDate) {
                $box.attr('class', 'atl-deadline atl-deadline--none');
                $('#hero_deadline_date').text('-');
                $('#hero_deadline_desc').html('<i class="far fa-clock mr-1"></i>Tanpa batas waktu');
                return;
            }

            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var target = new Date(rawDate);
            target.setHours(0, 0, 0, 0);
            var daysLeft = Math.floor((target - today) / (1000 * 60 * 60 * 24));

            var state = 'ok';
            var desc = daysLeft + ' hari lagi';
            var icon = 'far fa-clock';

            if (daysLeft < 0) {
                state = 'late';
                desc = 'Terlambat ' + Math.abs(daysLeft) + ' hari';
                icon = 'fas fa-exclamation-circle';
            } else if (daysLeft === 0) {
                state = 'soon';
                desc = 'Berakhir hari ini';
                icon = 'fas fa-hourglass-half';
            } else if (daysLeft <= 3) {
                state = 'soon';
                desc = daysLeft + ' hari lagi';
                icon = 'fas fa-hourglass-half';
            }

            var parts = rawDate.split('-');
            var formattedStr = rawDate;
            if (parts.length === 3) {
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                var mIdx = parseInt(parts[1], 10) - 1;
                formattedStr = parts[2] + ' ' + (months[mIdx] || parts[1]) + ' ' + parts[0];
            }

            $box.attr('class', 'atl-deadline atl-deadline--' + state);
            $('#hero_deadline_date').text(formattedStr);
            $('#hero_deadline_desc').html('<i class="' + icon + ' mr-1"></i>' + desc);
        }

        // Initialize hero deadline based on active role
        updateHeroDeadline(activeRole);

        $('#appraisal_role_tabs').on('click', '.atl-tab', function () { selectTab($(this)); });
        $('#appraisal_role_tabs').on('keydown', '.atl-tab', function (e) {
            var $tabs = $('#appraisal_role_tabs .atl-tab'), i = $tabs.index(this);
            if (e.key === 'ArrowRight') { e.preventDefault(); selectTab($tabs.eq((i + 1) % $tabs.length)); }
            if (e.key === 'ArrowLeft')  { e.preventDefault(); selectTab($tabs.eq((i - 1 + $tabs.length) % $tabs.length)); }
        });

        // View Score Comparison Modal Handler
        window.showScoreComparisonModal = function (appraisalId) {
            if (!appraisalId) return;

            $('#modal_score_comparison').modal('show');
            $('#score_comparison_container').html(`
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary" aria-hidden="true"></i>
                    <p class="mt-2 mb-0 small font-weight-bold">Memuat riwayat &amp; komparasi skor penilaian...</p>
                </div>
            `);

            $.ajax({
                url: '<?= base_url('sas/penilaian/scoreComparison') ?>/' + appraisalId,
                type: 'GET',
                success: function (html) {
                    $('#score_comparison_container').html(html);
                },
                error: function (xhr, status, err) {
                    $('#score_comparison_container').html(`
                        <div class="text-center py-5 text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2" aria-hidden="true"></i>
                            <p class="mb-0 font-weight-bold">Gagal memuat data komparasi skor penilaian.</p>
                            <small class="text-muted">ID Penilaian: #${appraisalId} (${err || status})</small>
                            <div class="mt-3">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    `);
                }
            });
        };

        // View Audit Logs Modal Handler
        window.showAuditLogModal = function (appraisalId) {
            if (!appraisalId) return;

            auditTrigger = document.activeElement;
            $('#modal_audit_log').modal('show');
            $('#audit_timeline_container').html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x" aria-hidden="true"></i>
                    <p class="mt-2 mb-0 small">Memuat riwayat log...</p>
                </div>
            `);

            $.ajax({
                url: '<?= base_url('penilaian/auditLogs') ?>/' + appraisalId,
                type: 'GET',
                dataType: 'JSON',
                success: function (res) {
                    var logs = [];
                    if (res && res[0] && res[0].data) {
                        logs = res[0].data;
                    } else if (res && res.data) {
                        logs = res.data;
                    }

                    if (!logs || logs.length === 0) {
                        $('#audit_timeline_container').html(`
                            <div class="atl-empty">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <p class="mb-0 small">Belum ada riwayat aktivitas untuk form penilaian ini.</p>
                            </div>
                        `);
                        return;
                    }

                    var html = '<ol class="atl-tl">';
                    $.each(logs, function (i, log) {
                        var actionTitle = log.action || '-';
                        var timeStr = log.created_at ? log.created_at : '-';
                        var actor = log.actor_name ? (log.actor_name + (log.actor_username ? ' (' + log.actor_username + ')' : '')) : ('User ID: ' + (log.actor_id || '-'));
                        var notes = log.notes || '-';

                        var pillTone = 'primary';
                        if (actionTitle === 'KICKBACK_GM') pillTone = 'danger';
                        else if (actionTitle === 'GM_OVERRIDE_ITEM') pillTone = 'warning';
                        else if (actionTitle === 'UPDATE_ITEM_SCORE') pillTone = 'info';
                        else if (actionTitle === 'ACKNOWLEDGE_EMPLOYEE') pillTone = 'ok';

                        var prevVal = null;
                        var newVal  = null;
                        try {
                            prevVal = typeof log.previous_value === 'string' ? JSON.parse(log.previous_value) : log.previous_value;
                            newVal  = typeof log.new_value === 'string' ? JSON.parse(log.new_value) : log.new_value;
                        } catch (e) {}

                        var detailsHtml = '';
                        if (log.from_status || log.to_status) {
                            detailsHtml += `
                                <div class="atl-tl-line"><strong>Transisi Status:</strong>
                                    <span class="atl-pill atl-pill--muted">${esc(log.from_status || '-')}</span>
                                    <i class="fas fa-long-arrow-alt-right mx-1" aria-hidden="true"></i>
                                    <span class="atl-pill atl-pill--info">${esc(log.to_status || '-')}</span>
                                </div>
                            `;
                        }

                        if (log.md_appraisal_template_item_id) {
                            var prevScore = (prevVal && prevVal.score !== null && prevVal.score !== undefined) ? Number(prevVal.score).toFixed(2) : '-';
                            var newScore  = (newVal && newVal.score !== null && newVal.score !== undefined) ? Number(newVal.score).toFixed(2) : '-';
                            detailsHtml += `
                                <div class="atl-tl-line">
                                    <strong>Butir Item ID:</strong> #${esc(log.md_appraisal_template_item_id)} |
                                    <strong>Revisi Skor:</strong>
                                    <span class="atl-pill atl-pill--muted">${esc(prevScore)}</span>
                                    <i class="fas fa-long-arrow-alt-right mx-1" aria-hidden="true"></i>
                                    <span class="atl-pill atl-pill--ok">${esc(newScore)}</span>
                                </div>
                            `;
                        }

                        html += `
                            <li class="atl-tl-item">
                                <div class="atl-tl-head">
                                    <span class="atl-pill atl-pill--${pillTone} text-uppercase">${esc(actionTitle)}</span>
                                    <span class="atl-tl-time"><i class="far fa-clock mr-1" aria-hidden="true"></i>${esc(timeStr)}</span>
                                </div>
                                <div class="atl-tl-line"><strong>Aktor:</strong> ${esc(actor)}</div>
                                ${detailsHtml}
                                <div class="atl-tl-note">${esc(notes)}</div>
                            </li>
                        `;
                    });
                    html += '</ol>';

                    $('#audit_timeline_container').html(html);
                },
                error: function () {
                    $('#audit_timeline_container').html(`
                        <div class="atl-error">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            <p class="mb-0 small">Gagal memuat log riwayat aktivitas.</p>
                        </div>
                    `);
                }
            });
        };

        // Return focus to the trigger when the modal closes
        $('#modal_audit_log').on('hidden.bs.modal', function () {
            if (auditTrigger && document.body.contains(auditTrigger)) { $(auditTrigger).trigger('focus'); }
            auditTrigger = null;
        });
    }

    initTaskList();
})();
</script>
<?= $this->endSection() ?>
