<div class="card-body card-form mp" style="display:none">
    <form id="form_master_bundling" autocomplete="off">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-lg-12">
                <section class="form-section">
                    <h5>Informasi paket</h5>
                    <p class="form-section-hint">Nama paket dan jenis paket.</p>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="value">Kode</label>
                                <input type="text" class="form-control code" id="value" name="value" maxlength="10" readonly placeholder="[auto]">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="md_doctype_id">Jenis paket <span class="required">*</span></label>
                                <select class="form-control select-data" id="md_doctype_id" name="md_doctype_id" data-url="document-type/getList/$Paket"></select>
                                <small class="form-text text-muted">Misalnya Paket Regular atau Paket Project.</small>
                                <small class="form-text text-danger" id="error_md_doctype_id"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nama paket <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" maxlength="255" placeholder="Contoh: Lembur closing akhir bulan">
                                <small class="form-text text-danger" id="error_name"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label for="description">Deskripsi</label>
                                <textarea class="form-control" id="description" name="description" rows="3" maxlength="510" placeholder="Ketentuan singkat yang perlu diketahui approver"></textarea>
                                <small class="form-text text-danger" id="error_description"></small>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h5>Nominal dan periode</h5>
                    <p class="form-section-hint">Cara paket dihitung dan dibayarkan.</p>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Periode <span class="required">*</span></label>
                                <div class="selectgroup w-100">
                                    <label class="selecgroup-item">
                                        <input type="radio" name="period_type" value="day" class="selectgroup-input" checked>
                                        <span class="selectgroup-button">Harian</span>
                                    </label>
                                    <label class="selecgroup-item">
                                        <input type="radio" name="period_type" value="month" class="selectgroup-input">
                                        <span class="selectgroup-button">Bulanan</span>
                                    </label>
                                </div>
                                <small class="form-text text-danger" id="error_period_type"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sifat kompensasi <span class="required">*</span></label>
                                <div class="selectgroup w-100">
                                    <label class="selecgroup-item">
                                        <input type="radio" name="compensation_type" value="fix_per_nilai" class="selectgroup-input" checked>
                                        <span class="selectgroup-button">Fix per orang</span>
                                    </label>
                                    <label class="selecgroup-item">
                                        <input type="radio" name="compensation_type" value="fix_per_divisi" class="selectgroup-input">
                                        <span class="selectgroup-button">Fix per divisi</span>
                                    </label>
                                </div>
                                <small class="form-text text-danger" id="error_compensation_type"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="project_value" id="lbl_nominal">Nominal <span class="required">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="text" class="form-control rupiah" id="project_value" name="project_value" inputmode="numeric">
                                </div>
                                <small class="form-text text-danger" id="error_project_value"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="minimum_hour">Minimal jam <span class="required">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control number" id="minimum_hour" name="minimum_hour" value="0" inputmode="numeric">
                                    <div class="input-group-append"><span class="input-group-text">jam</span></div>
                                </div>
                                <small class="form-text text-muted">Isi 0 jika paket tanpa minimum jam.</small>
                                <small class="form-text text-danger" id="error_minimum_hour"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="isprorate" name="isprorate" value="Y">
                                <label class="custom-control-label" for="isprorate">Hitung prorata</label>
                            </div>
                            <small class="form-text text-muted">Nominal dihitung proporsional bila karyawan tidak mengikuti periode penuh.</small>
                        </div>
                    </div>
                </section>
                <section class="form-section mb-0">
                    <h5>Batas pengajuan per divisi</h5>
                    <p class="form-section-hint">Untuk paket yang kuotanya berbeda tiap divisi, misalnya closing.</p>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="divisionrule" name="divisionrule" value="Y">
                        <label class="custom-control-label" for="divisionrule">Batasi pengajuan tiap divisi per bulan</label>
                    </div>

                    <div id="mp_rules" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-light table-hover tb_displayline" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Divisi</th>
                                        <th style="width:34%">Maks. pengajuan per bulan</th>
                                        <th style="width:48px"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn_add_rule">
                            <i class="fa fa-plus mr-1"></i> Tambah divisi
                        </button>
                        <small class="form-text text-danger" id="error_line"></small>
                    </div>
                </section>
            </div>

            <!-- <div class="col-lg-4">
                <aside class="mp-preview" aria-live="polite">
                    <div class="mp-ticket">
                        <div class="mp-ticket-top">
                            <span class="mp-code" id="pv_code">Kode otomatis</span>
                            <span class="mp-chip" id="pv_period">Harian</span>
                        </div>
                        <h5 id="pv_name">Nama paket belum diisi</h5>
                        <div class="mp-ticket-amount">
                            <span id="pv_amount">Rp 0</span>
                            <small id="pv_unit">per orang tiap hari</small>
                        </div>
                        <dl class="mp-ticket-rows">
                            <div>
                                <dt>Jam minimum</dt>
                                <dd id="pv_min">Tanpa minimum</dd>
                            </div>
                            <div>
                                <dt>Prorata</dt>
                                <dd id="pv_prorate">Tidak</dd>
                            </div>
                            <div>
                                <dt>Batas divisi</dt>
                                <dd id="pv_rules">Tidak dibatasi</dd>
                            </div>
                        </dl>
                    </div>
                    <p class="form-section-hint mt-2">Begini paket ini terlihat oleh pengusul dan approver.</p>
                </aside>
            </div> -->
        </div>
    </form>

    <!-- Template opsi divisi untuk baris aturan (tidak ikut terkirim saat simpan) -->
    <template id="mp_division_opts">
        <option value="">Pilih divisi</option>
        <?php foreach ($division as $d) : ?>
            <option value="<?= $d->getDivisionId() ?>"><?= esc($d->getName()) ?></option>
        <?php endforeach; ?>
    </template>
</div>