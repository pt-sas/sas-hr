<div class="card-body card-form">
    <form class="form-horizontal" id="form_broadcast">
        <?= csrf_field(); ?>
        <div class="row">

            <div class="col-md-6">
                <div class="form-group">
                    <label for="title">Judul <span class="required">*</span></label>
                    <input type="text" class="form-control" id="title" name="title">
                    <small class="form-text text-danger" id="error_title"></small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_employee_id">Nama Karyawan</label>
                    <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                        data-url="employee/getList/$Access">
                        <option value="">Select Karyawan</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_employee_id"></small>
                </div>
            </div>

            <!-- Message -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="message">Pesan <span class="required">*</span></label>
                    <textarea type="text" class="form-control summernote" id="message" name="message" rows="2"></textarea>
                    <small class="form-text text-danger" id="error_message"></small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_branch_id">Cabang</label>
                    <select class="form-control select-data" id="md_branch_id" name="md_branch_id"
                        data-url="branch/getList">>
                        <option value="">Select Cabang</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
                </div>
                <div class="form-group">
                    <label for="effective_date">Tanggal Efektif</label>
                    <div class="input-icon">
                        <input type="text"
                            class="form-control datepicker"
                            name="effective_date">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_effective_date"></small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="md_division_id">Divisi</label>
                    <select class="form-control select-data" id="md_division_id" name="md_division_id"
                        data-url="division/getList">
                        <option value="">Select Divisi</option>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
                </div>
                <div class="form-group">
                    <label for="lastupdate">Terakhir Dikirim <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="lastupdate" name="lastupdate" disabled>
                    <small class="form-text text-danger" id="error_lastupdate"></small>
                </div>
            </div>

            <!-- Send Method -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Send Method</label><br>

                    <div class="form-check d-inline-block mr-2">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" id="send_email" name="send_email" value="E">
                            <span class="form-check-sign">Email</span>
                        </label>
                    </div>

                    <div class="form-check d-inline-block mr-2">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" id="send_notification" name="send_notification" value="N">
                            <span class="form-check-sign">Notification</span>
                        </label>
                    </div>

                    <div class="form-check d-inline-block">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" id="send_telegram" name="send_telegram" value="T">
                            <span class="form-check-sign">Telegram</span>
                        </label>
                    </div>

                    <small class="form-text text-danger" id="error_sentmethod"></small>
                </div>
            </div>

            <!-- Publish Status -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Publish Status</label><br>
                    <div class="form-check d-inline-block mr-2">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" id="is_sent" name="is_sent" value="Y" disabled>
                            <span class="form-check-sign">Published</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- <div class='col-md-6'></div> -->

            <!-- Attachment 1 -->
            <div class="col-md-4">
                <div class="form-group">
                    <label>Attachment</label>
                    <div class="form-upload-result">
                        <label class="col-md-4 form-result">
                            <button type="button" class="close-img" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <img class="img-result">
                        </label>
                    </div>
                    <div class="form-file-result" style="display: none;">
                        <div class="file-preview">
                            <button type="button" class="close-file" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <span class="file-name"></span>
                        </div>
                    </div>
                    <div class="form-upload">
                        <label class="col-md-4 form-upload-foto" id="image-upload">
                            <input type="file" class="control-upload-image" id="attachment" name="attachment"
                                onchange="previewAll(this, '', '')"  accept="image/jpeg, image/png, application/pdf, .xls, .xlsx, .doc, .docx"></input>
                            <img class="img-upload" src="<?= base_url('custom/image/cameraroll.png') ?>" />
                        </label>
                        <small class="form-text text-danger" id="error_image"></small>
                    </div>
                </div>
            </div>

            <!-- Attachment 2 -->
            <div class="col-md-4">
                <div class="form-group">
                    <label>Attachment 2 </label>
                    <div class="form-upload-result">
                        <label class="col-md-4 form-result">
                            <button type="button" class="close-img" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <img class="img-result">
                        </label>
                    </div>
                    <div class="form-file-result" style="display: none;">
                        <div class="file-preview">
                            <button type="button" class="close-file" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <span class="file-name"></span>
                        </div>
                    </div>
                    <div class="form-upload">
                        <label class="col-md-4 form-upload-foto" id="image2-upload">
                            <input type="file" class="control-upload-image" id="attachment2" name="attachment2"
                                onchange="previewAll(this, '', '')"  accept="image/jpeg, image/png, application/pdf, .xls, .xlsx, .doc, .docx"></input>
                            <img class="img-upload" src="<?= base_url('custom/image/cameraroll.png') ?>" />
                        </label>
                        <small class="form-text text-danger" id="error_image2"></small>
                    </div>
                </div>
            </div>

            <!-- Attachment 3 -->
            <div class="col-md-4">
                <div class="form-group">
                    <label>Attachment 3 </label>
                    <div class="form-upload-result">
                        <label class="col-md-4 form-result">
                            <button type="button" class="close-img" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <img class="img-result">
                        </label>
                    </div>
                    <div class="form-file-result" style="display: none;">
                        <div class="file-preview">
                            <button type="button" class="close-file" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <span class="file-name"></span>
                        </div>
                    </div>
                    <div class="form-upload">
                        <label class="col-md-4 form-upload-foto" id="image3-upload">
                            <input type="file" class="control-upload-image" id="attachment3" name="attachment3"
                                onchange="previewAll(this, '', '')"  accept="image/jpeg, image/png, application/pdf, .xls, .xlsx, .doc, .docx"></input>
                            <img class="img-upload" src="<?= base_url('custom/image/cameraroll.png') ?>" />
                        </label>
                        <small class="form-text text-danger" id="error_image3"></small>
                    </div>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" style="width: 100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>Metode Pengiriman</th>
                                <th>Pesan Error</th>
                                <th>Tanggal Pengiriman</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </form>
</div>