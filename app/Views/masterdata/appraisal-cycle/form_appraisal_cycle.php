<div class="card-body card-form">
    <form class="form-horizontal" id="form_appraisal_cycle">
        <?= csrf_field() ?>

        <div class="row">
            <!-- Hidden ID untuk create/update SAS-HR -->
            <input type="hidden" id="md_appraisal_cycle_id" name="md_appraisal_cycle_id">

            <!-- Tombol Simulasi Distribusi (Ditempatkan rapi di col-md-12) -->
            <div class="col-md-12 mb-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-warning btn-sm btn-round"
                        id="btn_preview_blast"
                        onclick="window.cycPreviewBlast(parseInt($('#md_appraisal_cycle_id').val(), 10) || 0)"
                        title="Simulasi Distribusi Karyawan">
                    <i class="fas fa-paper-plane mr-1"></i> Simulasi Distribusi Form
                </button>
            </div>

                <!-- Field Nama Siklus & Semester -->
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="title">Nama Siklus Penilaian <span class="required text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                               placeholder="Contoh: Performance Appraisal 2026">
                        <small class="form-text text-danger" id="error_title"></small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="semester">Semester <span class="required text-danger">*</span></label>
                        <select class="form-control select2" id="semester" name="semester">
                            <option value="">Pilih Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                        <small class="form-text text-danger" id="error_semester"></small>
                    </div>
                </div>

                <!-- Tanggal Mulai & Selesai -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="start_date">Tgl Mulai Periode <span class="required text-danger">*</span></label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="start_date" name="start_date" placeholder="DD-Mmm-YYYY">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-danger" id="error_start_date"></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="end_date">Tgl Selesai Periode <span class="required text-danger">*</span></label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="end_date" name="end_date" placeholder="DD-Mmm-YYYY">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-danger" id="error_end_date"></small>
                    </div>
                </div>

                <!-- Deadlines -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="deadline_filling">Due Pengisian (Atasan)</label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="deadline_filling" name="deadline_filling" placeholder="DD-Mmm-YYYY">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-muted" style="font-size:11px;">Batas pengisian Atasan</small>
                        <small class="form-text text-danger" id="error_deadline_filling"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="deadline_review">Due Peninjau (Reviewer)</label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="deadline_review" name="deadline_review" placeholder="DD-Mmm-YYYY">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-muted" style="font-size:11px;">Batas peninjauan oleh reviewer</small>
                        <small class="form-text text-danger" id="error_deadline_review"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="deadline_reading">Due Pembacaan (Karyawan)</label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="deadline_reading" name="deadline_reading" placeholder="DD-Mmm-YYYY">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-muted" style="font-size:11px;">Batas konfirmasi Karyawan</small>
                        <small class="form-text text-danger" id="error_deadline_reading"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="deadline_override">Masa Tenggang / Override HR</label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="deadline_override" name="deadline_override" placeholder="DD-Mmm-YYYY">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-muted" style="font-size:11px;">Batas rekonsiliasi HR</small>
                        <small class="form-text text-danger" id="error_deadline_override"></small>
                    </div>
                </div>

                <!-- Info banner -->
                <div class="col-md-12">
                    <div class="card bg-light border-0 shadow-none mb-3">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle text-primary mt-1 mr-2" style="font-size:14px;"></i>
                                <div>
                                    <strong class="d-block text-dark mb-1" style="font-size:13px;">Otomasi Distribusi Template</strong>
                                    <p class="text-muted mb-0" style="font-size:12px; line-height:1.45;">
                                        Saat siklus <b>DIBUKA</b>, sistem akan mendistribusikan form penilaian kepada seluruh karyawan aktif sesuai <b>Template Penilaian</b>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>