<div class="card card-stats card-round border-0 shadow-sm">
    <div class="card-body p-3">
        <p class="text-muted mb-1 fs-7 fw-medium">Sisa cuti tahunan</p>
        <div class="d-flex align-items-baseline mb-1">
            <h2 class="fw-bold text-dark mb-0 me-2"><?= $leave_remaining ?? 8 ?></h2>
            <span class="fs-6 text-muted">hari</span>
        </div>
        <small class="text-muted fs-8">Dari hak cuti <?= $leave_total ?? 12 ?> hari</small>
    </div>
</div>