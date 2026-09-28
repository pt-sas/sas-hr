<div class="card-body card-form">
    <form class="form-horizontal" id="form_article" enctype="multipart/form-data">
        <?= csrf_field(); ?>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sys_ref_detail_id">Kategori <span class="required">*</span></label>
                    <select class="form-control select-data" id="sys_ref_detail_id" name="sys_ref_detail_id"
                            data-url="help-article/getListDocCategory">
                        <option value="">Pilih Kategori</option>
                    </select>
                    <small class="form-text text-danger" id="error_sys_ref_detail_id"></small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="d-block">&nbsp;</label>
                    <div class="form-check mt-2">
                        <label class="form-check-label">
                            <input type="checkbox"
                                   class="form-check-input"
                                   id="isactive"
                                   name="isactive"
                                   value="Y"
                                   checked>
                            <span class="form-check-sign">Status Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="title">Judul Artikel <span class="required">*</span></label>
                    <input type="text" class="form-control" id="title" name="title">
                    <small class="form-text text-danger" id="error_title"></small>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group mb-3">
                    <label for="attachments" class="form-label">Lampiran File</label>
                    <input type="file" class="form-control-file" id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf,.odt,.ods,.odp,.zip,.rar,.png,.jpg,.jpeg,.webp,.gif">
                    <small class="form-text text-muted d-block mt-1">
                        Format: PDF, DOC, DOCX. Maksimal 10 MB per file. Bisa memilih lebih dari 1 file.
                    </small>
                    <small class="form-text text-danger" id="error_attachments"></small>
                    
                    <!-- 1. Preview list for newly selected files (with Cancel button) -->
                    <div id="selected_files_wrapper" class="mt-2 d-none">
                        <small class="text-muted font-weight-bold d-block mb-1">File Baru Ditambahkan:</small>
                        <ul class="list-group" id="selected_files_list"></ul>
                    </div>

                    <!-- 2. Existing saved files during Edit mode -->
                    <div id="existing_files_wrapper" class="mt-3"></div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="content">Text <span class="required">*</span></label>
                    <textarea class="form-control summernote" id="content" name="content" rows="6"></textarea>
                    <small class="form-text text-danger" id="error_content"></small>
                </div>
            </div>
        </div>
    </form>
</div>
