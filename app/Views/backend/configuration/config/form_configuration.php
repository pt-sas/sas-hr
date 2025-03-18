<?= $this->extend('backend/_partials/overview') ?>
<?= $this->section('content') ?>

<div class="card-body card-main">
    <form id="form-configuration" enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Auto Reject Approval</label>
                    <input type="text" class="form-control number" id="auto_reject_approval"
                        name="auto_reject_approval">
                    <small class="form-text text-danger" id="error_auto_reject_approval"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Auto Approve Realisasi</label>
                    <input type="text" class="form-control number" id="auto_approve_realization"
                        name="auto_approve_realization">
                    <small class="form-text text-danger" id="error_auto_approve_realization"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tanggal Cut Off Cuti</label>
                    <input type="text" class="form-control number" id="day_cut_off_leave" name="day_cut_off_leave">
                    <small class="form-text text-danger" id="error_day_cut_off_leave"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tanggal Cut Off Kehadiran</label>
                    <input type="text" class="form-control number" id="day_cut_off_allowance"
                        name="day_cut_off_allowance">
                    <small class="form-text text-danger" id="error_day_cut_off_allowance"></small>
                </div>
            </div>
        </div>
</div>
</form>
</div>
<?= $this->endSection() ?>