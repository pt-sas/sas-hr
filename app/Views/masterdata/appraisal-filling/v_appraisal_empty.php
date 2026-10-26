<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<div class="card-body card-main py-5">
    <div class="text-center py-5">
        <div class="mb-4">
            <span class="avatar avatar-xxl rounded-circle bg-light text-primary p-4 shadow-sm">
                <i class="fas fa-clipboard-check fa-4x text-muted"></i>
            </span>
        </div>
        <h4 class="font-weight-bold text-dark mb-2">Tidak Ada Tugas Penilaian Saat Ini</h4>
        <p class="text-muted mx-auto" style="max-width: 520px; font-size: 15px;">
            Saat ini tidak ada siklus evaluasi kinerja yang aktif, atau semua form penilaian yang ditugaskan kepada Anda sebagai Atasan, Reviewer (GM), maupun Karyawan telah selesai diproses.
        </p>
        <div class="mt-4">
            <a href="<?= base_url('sas/penilaian') ?>" class="btn btn-outline-primary btn-round mr-2">
                <i class="fas fa-sync-alt mr-1"></i> Muat Ulang
            </a>
            <a href="<?= base_url('sas') ?>" class="btn btn-primary btn-round">
                <i class="fas fa-home mr-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
