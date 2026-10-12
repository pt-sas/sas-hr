<?php
    $status = $attendance_status ?? 'ABSENT';

    switch ($status) {
        case 'NORMAL':
            $badgeClass = 'bg-success-soft text-success';
            $badgeText  = 'Hadir Tepat Waktu';
            $badgeIcon  = 'fa-check-circle';
            break;

        case 'LATE_ARRIVAL':
            $badgeClass = 'bg-warning-soft text-warning';
            $badgeText  = 'Terlambat';
            $badgeIcon  = 'fa-clock';
            break;

        case 'LATE_ARRIVAL_MIDDAY':
            $badgeClass = 'bg-danger-soft text-danger';
            $badgeText  = 'Terlambat (> 12:00)';
            $badgeIcon  = 'fa-exclamation-triangle';
            break;

        case 'UNEXCUSED_EARLY_DEPARTURE':
        case 'EARLY_DEPARTURE_SEVERE':
            $badgeClass = 'bg-warning-soft text-warning';
            $badgeText  = 'Pulang Cepat';
            $badgeIcon  = 'fa-running';
            break;

        case 'LATE_AND_EARLY_LEAVE':
            $badgeClass = 'bg-danger-soft text-danger';
            $badgeText  = 'Terlambat & Pulang Cepat';
            $badgeIcon  = 'fa-exclamation-triangle';
            break;

        case 'MISSING_CHECK_OUT':
            $badgeClass = 'bg-info-soft text-info';
            $badgeText  = 'Sudah Check-In';
            $badgeIcon  = 'fa-sign-in-alt';
            break;

        case 'MISSING_CHECK_IN':
            $badgeClass = 'bg-warning-soft text-warning';
            $badgeText  = 'Belum Check-In';
            $badgeIcon  = 'fa-sign-out-alt';
            break;

        case 'MANUAL_HRD_OVERRIDE':
            $badgeClass = 'bg-primary-soft text-primary';
            $badgeText  = 'Koreksi HRD';
            $badgeIcon  = 'fa-user-edit';
            break;

        case 'OFF_DAY':
            $badgeClass = 'bg-light text-muted';
            $badgeText  = 'Hari Libur / Off';
            $badgeIcon  = 'fa-calendar-day';
            break;

        default: // ABSENT
            $badgeClass = 'bg-secondary-soft text-secondary';
            $badgeText  = 'Belum Check-In';
            $badgeIcon  = 'fa-exclamation-circle';
            break;
    }
?>

<div class="card border-0 shadow-sm card-round h-100 d-flex flex-column">
    <!-- Header -->
    <div class="card-header bg-transparent border-0 p-3 pb-0 d-flex align-items-center justify-content-between">
        <h5 class="fw-bold text-dark mb-0">Absensi Hari Ini</h5>
    </div>

    <!-- Body / Content -->
    <div class="card-body p-3 flex-grow-1 d-flex flex-column justify-content-between">
        <div>
            <!-- Live Clock & Status Badge -->
            <div class="d-flex align-items-baseline mb-1">
                <h1 class="fw-bold display-6 text-dark mb-0 me-2"><?= date('H:i') ?></h1>
                <span class="text-muted fw-bold fs-8">WIB</span>
            </div>

            <div class="mb-3">
                <span class="badge <?= $badgeClass ?> px-2 py-1 rounded-pill fs-8 fw-medium">
                    <i class="fas <?= $badgeIcon ?> me-1 fs-9"></i> <?= $badgeText ?>
                </span>
            </div>

            <!-- Attendance Detail List -->
            <div class="border-top border-bottom py-2">
                <div class="d-flex justify-content-between py-1 fs-8">
                    <span class="text-muted">Jadwal kerja</span>
                    <span class="fw-medium text-dark"><?= esc($schedule_range ?? '-') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 fs-8">
                    <span class="text-muted">Lokasi</span>
                    <span class="fw-medium text-dark"><?= esc($branch_name ?? '-') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 fs-8">
                    <span class="text-muted">Check-in</span>
                    <span class="fw-medium text-dark"><?= esc($clock_in_time ?? 'Belum absen') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 fs-8">
                    <span class="text-muted">Check-out</span>
                    <span class="fw-medium text-dark"><?= esc($clock_out_time ?? 'Belum absen') ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="row g-2 mt-3 pt-2">
            <div class="col-6">
                <!-- <a href="<?= site_url('sas/ijin') ?>" class="btn btn-outline-secondary w-100 btn-sm text-dark fw-medium text-center text-decoration-none">Pengajuan</a> -->
            </div>
            <div class="col-6">
                <a href="<?= site_url('sas/laporan-kehadiran') ?>" class="btn btn-outline-secondary w-100 btn-sm text-dark fw-medium text-center text-decoration-none">Riwayat</a>
            </div>
        </div>
    </div>
</div>