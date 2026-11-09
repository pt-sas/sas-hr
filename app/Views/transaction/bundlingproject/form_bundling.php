<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_bundling" autocomplete="off">
        <?= csrf_field(); ?>
        <section class="bundling-section">
            <div class="bundling-section__head">
                <span class="bundling-section__icon"><i class="fas fa-user"></i></span>
                <div>
                    <h5 class="bundling-section__title">Informasi Pemohon</h5>
                    <small class="text-muted">Data pemohon dan dokumen</small>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-xl-6">
                    <div class="form-group">
                        <label for="md_employee_id">Pemohon <span class="required">*</span></label>
                        <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                            data-url="employee/getList/$Spesific">
                            <option value="">Select Karyawan</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_employee_id"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <div class="form-group">
                        <label for="documentno">No Form</label>
                        <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="md_branch_id">Cabang <span class="required">*</span></label>
                        <select class="form-control select2" id="md_branch_id" name="md_branch_id" disabled>
                            <option value="">Select Cabang</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_branch_id"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="md_division_id">Divisi <span class="required">*</span></label>
                        <select class="form-control select2" id="md_division_id" name="md_division_id" disabled>
                            <option value="">Select Divisi</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_division_id"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="submissiondate">Tanggal Pembuatan</label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                                value="<?= esc($today, 'attr') ?>" disabled>
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-danger" id="error_submissiondate"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="approveddate">Tanggal Disetujui</label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" id="approveddate" name="approveddate" readonly>
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-danger" id="error_approveddate"></small>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 2. PAKET & PERIODE ============ -->
        <section class="bundling-section">
            <div class="bundling-section__head">
                <span class="bundling-section__icon"><i class="fas fa-box-open"></i></span>
                <div>
                    <h5 class="bundling-section__title">Paket &amp; Periode</h5>
                    <small class="text-muted">Pilih paket, lalu tentukan tanggal</small>
                </div>
            </div>

            <div class="row">
                <!-- Paket + tombol Detail persis di sebelahnya -->
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="trx_master_bundling_id">Paket <span class="required">*</span></label>
                        <div class="paket-field d-flex align-items-start">
                            <div class="flex-grow-1 paket-field__select">
                                <select class="form-control select2" id="trx_master_bundling_id"
                                    name="trx_master_bundling_id" style="width: 100%">
                                    <option value="">Select Paket</option>
                                    <?php foreach ($masterBundling as $d) : ?>
                                        <option value="<?= $d->getMasterBundlingId() ?>"><?= esc($d->getName()) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <small class="form-text text-danger" id="error_trx_master_bundling_id"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="period_type">Periode</label>
                        <select class="form-control select2" id="period_type" name="period_type" disabled>
                            <option value="">Pilih Tipe</option>
                            <option value="day" selected>Harian</option>
                            <option value="month">Bulanan</option>
                        </select>
                        <small class="form-text text-danger" id="error_period_type"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="compensation_type">Sifat Kompensasi <span class="required">*</span></label>
                        <select class="form-control select2" id="compensation_type" name="compensation_type" disabled>
                            <option value="">Pilih Kompensasi</option>
                            <option value="fix_per_divisi">Fix Per Divisi</option>
                            <option value="fix_per_nilai" selected>Fix Per Orang</option>
                        </select>
                        <small class="form-text text-danger" id="error_compensation_type"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="project_value">Nominal <span class="required">*</span></label>
                        <input type="text" class="form-control rupiah" id="project_value" name="project_value" value="25000" readonly>
                        <small class="form-text text-danger" id="error_project_value"></small>
                    </div>
                </div>
                <!-- Tanggal: Harian = 1 tanggal, Bulanan = rentang (Mulai - Selesai) -->
                <div class="col-12 col-md-6 col-xl-3" id="col_startdate">
                    <div class="form-group">
                        <label for="startdate">
                            <span id="label_startdate_text">Tanggal Mulai</span> <span class="required">*</span>
                        </label>
                        <div class="input-icon">
                            <input type="text" class="form-control date-start" name="startdate" id="startdate" autocomplete="off">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-muted" id="hint_date"></small>
                        <small class="form-text text-danger" id="error_startdate"></small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3" id="col_enddate">
                    <div class="form-group">
                        <label for="enddate">Tanggal Selesai <span class="required">*</span></label>
                        <div class="input-icon">
                            <input type="text" class="form-control date-end" name="enddate" id="enddate" autocomplete="off">
                            <span class="input-icon-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <small class="form-text text-danger" id="error_enddate"></small>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 3. PENILAIAN ============ -->
        <section class="bundling-section">
            <div class="bundling-section__head">
                <span class="bundling-section__icon"><i class="fas fa-clipboard-check"></i></span>
                <div>
                    <h5 class="bundling-section__title">Penilaian</h5>
                    <small class="text-muted">Dinilai berdasarkan jam atau hasil</small>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="form-group">
                        <label for="result_oriented">Tipe Penilaian <span class="required">*</span></label>
                        <select class="form-control select2" id="result_oriented" name="result_oriented">
                            <option value="">Pilih Tipe Penilaian</option>
                            <option value="time">Jam</option>
                            <option value="task">Hasil</option>
                        </select>
                        <small class="form-text text-muted" id="hint_result"></small>
                        <small class="form-text text-danger" id="error_result_oriented"></small>
                    </div>
                </div>

                <!-- Hanya tampil jika Tipe Penilaian = Jam -->
                <div class="col-12 col-md-6 col-xl-3 js-hour-col d-none">
                    <div class="form-group">
                        <label for="minimum_hour">Minimal jam <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control number" id="minimum_hour" name="minimum_hour" readonly>
                            <div class="input-group-append"><span class="input-group-text">jam</span></div>
                        </div>
                        <small class="form-text text-danger" id="error_minimum_hour"></small>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"></textarea>
                        <small class="form-text text-danger" id="error_description"></small>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ 4. DETAIL KARYAWAN ============ -->
        <section class="bundling-section">
            <div class="bundling-section__head">
                <span class="bundling-section__icon"><i class="fas fa-users"></i></span>
                <div class="flex-grow-1">
                    <h5 class="bundling-section__title">Detail Karyawan</h5>
                    <small class="text-muted">Karyawan yang masuk dalam paket</small>
                </div>
                <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto add_row"
                    title="Tambah Baru"><i class="fa fa-plus fa-fw"></i> Tambah</button>
            </div>

            <div class="table-responsive">
                <table class="table table-light table-hover tb_displayline tb_childrow" id="table_overtime" style="width: 100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Karyawan</th>
                            <th>Total Jam</th>
                            <th>Total Rupiah</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>
    </form>
</div>

<link rel="stylesheet" href="<?= base_url('custom/css/bundling-form.css') ?>">