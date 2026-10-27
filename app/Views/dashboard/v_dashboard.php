<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<div class="page-inner pt-0 dash-tight">

    <!-- 1. Greetings Header -->
    <div class="mb-3">
        <?= $this->include('dashboard/box_greetings') ?>
    </div>

    <!-- 2. Sticky Date & Scope Control Bar -->
    <div id="datePickerWrapper" class="mb-3" style="min-height: 48px;">
        <div id="stickyDatePicker" class="bg-white py-2 px-3 border shadow-xs transition-all" style="border-radius: 8px !important;">
            <?= $this->include('dashboard/date_picker', [
                'levels'    => $levels,
                'level'     => $level,
                'mode'      => $mode,
                'date'      => $date,
                'label'     => $dateLabel,
                'prevDate'  => $prevDate,
                'nextDate'  => $nextDate,
                'dateLabel' => $dateLabel
            ]) ?>
        </div>
    </div>

    <!-- 3. KPI Carousel -->
    <div class="kc-wrap mb-3">

        <button type="button"
                class="kc-arrow kc-prev"
                aria-label="Kartu sebelumnya"
                disabled>
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>

        <button type="button"
                class="kc-arrow kc-next"
                aria-label="Kartu berikutnya">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>

        <div id="kpiCarousel"
            class="snap-slider"
            role="region"
            aria-label="Ringkasan KPI"
            tabindex="0">

            <!-- Filled Card 1: Personal Attendance -->
            <div class="kc-slide">
                <?= $this->include('dashboard/box_attendance') ?>
            </div>

            <!-- Filled Card 2 (Conditional): Team Attendance -->
            <?php if (!empty($show_team_card)): ?>
                <div class="kc-slide">
                    <?= $this->include('dashboard/box_team_attendance') ?>
                </div>
            <?php endif; ?>

            <!-- Filled Card 3: Approvals -->
            <div class="kc-slide">
                <?= $this->include('dashboard/box_approval') ?>
            </div>

            <!-- Placeholders (Pushed to the far right to maintain 4 total slides) -->
            <?php if (empty($show_team_card)): ?>
                <div class="kc-slide">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center p-4 text-center text-muted">
                            <i class="fas fa-layer-group fa-2x mb-2 opacity-25"></i>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="kc-slide">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center p-4 text-center text-muted">
                        <i class="fas fa-layer-group fa-2x mb-2 opacity-25"></i>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- 5. Calendar Activity (Di bawah) -->
    <div class="row g-3 mb-4">
        <div class="col-12 position-relative">
            <?= $this->include('dashboard/calendar_activity') ?>
        </div>
    </div>

    <!-- 4. Operational Status & Announcements (Di atas) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <?= $this->include('dashboard/box_announcement') ?>
        </div>
    </div>

</div>

<style>
/* =========================================================
   DASHBOARD LAYOUT
   ========================================================= */

.dash-tight .card {
    margin-bottom: 0;
}

.dash-tight .card .card-body {
    padding: 1rem !important;
}

.dash-tight .card .card-body > .mb-4 {
    margin-bottom: .75rem !important;
}

.dash-tight .card .card-body > .mb-3 {
    margin-bottom: .5rem !important;
}


/* =========================================================
   STICKY FILTER
   ========================================================= */

#stickyDatePicker {
    transition: all .25s ease;
}

#stickyDatePicker.is-docked {
    background: rgba(255, 255, 255, .88) !important;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-color: rgba(226, 232, 240, .8) !important;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, .08) !important;
}


/* =========================================================
   KPI CAROUSEL & FIXED HEIGHT SLIDES
   ========================================================= */

.kc-wrap {
    position: relative;
    /* padding: 0 20px; */
}

.snap-slider {
    --kc-gap: .75rem;
    --kc-count: 1.08;

    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;

    gap: var(--kc-gap);

    overflow-x: auto;
    overflow-y: hidden;

    padding: 2px 2px 8px;

    scroll-snap-type: x mandatory;
    scroll-padding-inline: 2px;

    overscroll-behavior-x: contain;
    -webkit-overflow-scrolling: touch;

    scrollbar-width: none;
    height: 225px !important;
}

.kc-slide {
    flex: 0 0 calc(
        (100% - (var(--kc-count) - 1) * var(--kc-gap))
        / var(--kc-count)
    );

    min-width: 0;
    scroll-snap-align: start;
    display: flex;
    height: 100% !important;
}

.kc-slide > .card {
    width: 100%;
    height: 100% !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.kc-slide > .card > .card-body {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    /* padding: 1rem !important; */
    padding: 0rem;
}


/* Tablet */
@media (min-width: 576px) {
    .snap-slider {
        --kc-count: 3;
        --kc-gap: 1rem;
    }
}


/* Medium */
@media (min-width: 768px) {
    .snap-slider {
        --kc-count: 3.15;
    }
}


/* Desktop */
@media (min-width: 1200px) {
    .snap-slider {
        --kc-count: 4;
    }
}


/* =========================================================
   KPI CARDS
   ========================================================= */

.kpi-card,
.kc-slide .card {
    border-radius: 14px !important;
    transition:
        transform .2s ease,
        box-shadow .2s ease,
        opacity .25s ease;
}

.kpi-card:hover,
.kc-slide .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px -6px rgba(15, 23, 42, .08) !important;
}

.kpi-loading {
    opacity: 0.45;
    pointer-events: none;
    filter: blur(0.5px);
}


/* =========================================================
   KPI ARROWS
   ========================================================= */

.kc-arrow {
    position: absolute;
    top: 50%;
    z-index: 5;

    width: 36px;
    height: 36px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 0;

    border: 1px solid #e2e8f0;
    border-radius: 50%;

    background: rgba(255, 255, 255, .95);
    color: #334155;

    box-shadow: 0 4px 14px rgba(15, 23, 42, .1);

    transform: translateY(-50%);
    transition: all .2s ease;
}

.kc-prev {
    left: -4px;
}

.kc-next {
    right: -4px;
}

.kc-arrow:hover {
    color: #2563eb;
    border-color: #bfdbfe;
    background: #fff;
    transform: translateY(-50%) scale(1.08);
}

.kc-arrow:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
}

.kc-arrow:disabled {
    opacity: 0;
    pointer-events: none;
}

.kc-wrap.is-static .kc-arrow {
    display: none;
}


/* =========================================================
   SMALL SCREEN
   ========================================================= */

@media (max-width: 575.98px) {

    .dash-tight .card .card-body {
        padding: .75rem !important;
    }

    .kc-wrap {
        padding: 0;
    }

    .snap-slider {
        --kc-count: 1.08;
        --kc-gap: .75rem;
    }

    .kc-arrow {
        display: none;
    }
}


/* =========================================================
   HELPERS
   ========================================================= */

.bg-primary-soft {
    background-color: #e8f1ff;
    color: #1572e8;
}

.bg-success-soft {
    background-color: #e6f9ed;
    color: #31ce36;
}

.bg-warning-soft {
    background-color: #fff8e6;
    color: #ffad46;
}

.fs-7 {
    font-size: .875rem;
}

.fs-8 {
    font-size: .8rem;
}

.fs-9 {
    font-size: .725rem;
}
</style>

<script>
window.APP_URLS = window.APP_URLS || {};
window.APP_URLS.pesanUpdateRead = '<?= base_url('sas/pesan/updateRead') ?>';
</script>

<?= $this->endSection() ?>

