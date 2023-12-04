<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<div class="card-body card-main">
    <form class="form-horizontal" id="form_mail">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="smtphost">Mail Host <span class="required">*</span></label>
                    <input type="text" class="form-control" id="smtphost" name="smtphost">
                    <small class="form-text text-danger" id="error_smtphost"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="protocol">Protocol </label>
                    <input type="text" class="form-control" id="protocol" name="protocol">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="smtpport">SMTP Port <span class="required">*</span></label>
                    <input type="text" class="form-control number" id="smtpport" name="smtpport">
                    <small class="form-text text-danger" id="error_smtpport"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="smtpcrypto">SMTP <span class="required">*</span></label>
                    <input type="text" class="form-control" id="smtpcrypto" name="smtpcrypto">
                    <small class="form-text text-danger" id="error_smtpcrypto"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="smtpuser">Request User <span class="required">*</span></label>
                    <input type="text" class="form-control" id="smtpuser" name="smtpuser">
                    <small class="form-text text-danger" id="error_smtpuser"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="smtppassword">Request User Password <span class="required">*</span></label>
                    <input type="password" class="form-control" id="smtppassword" name="smtppassword">
                    <small class="form-text text-danger" id="error_smtppassword"></small>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="requestemail">Request Email <span class="required">*</span></label>
                    <input type="text" class="form-control col-md-6" id="requestemail" name="requestemail">
                    <small class="form-text text-danger" id="error_requestemail"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive" checked>
                        <span class="form-check-sign">Active</span>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <button type="button" class="btn btn-outline-dark ml-auto col-md-12" name="btn_test_email" onclick="prosesTestEmail(this)">Test Email</button>
                </div>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>