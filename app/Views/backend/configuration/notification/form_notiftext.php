<div class="card-body card-form">
    <form class="form-horizontal" id="form_notiftext">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Nama <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="subject">Subjek <span class="required">*</span></label>
                    <input type="text" class="form-control" id="subject" name="subject">
                    <small class="form-text text-danger" id="error_subject"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="text">Text <span class="required">*</span></label>
                    <textarea type="text" class="form-control summernote" id="text" name="text" rows="2"></textarea>
                    <small class="form-text text-danger" id="error_text"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="notiftype">Tipe Notifikasi <span class="required">*</span></label>
                    <select class="form-control select-data" id="notiftype" name="notiftype" data-url="reference/getList/$SYS_NotificationType">
                        <option value="">Select Notification Type</option>
                    </select>
                    <small class="form-text text-danger" id="error_notiftype"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Active</span>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>