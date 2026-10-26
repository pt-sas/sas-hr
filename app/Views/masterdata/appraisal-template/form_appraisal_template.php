<div class="card-body card-form">
    <form class="form-horizontal" id="form_appraisal_template">
        <?= csrf_field(); ?>

        <!-- HEADER FIELDS -->
        <div class="row" id="tpl_header_row">
            <input type="hidden" id="md_appraisal_template_id" name="md_appraisal_template_id">

            <div class="col-md-6">
                <div class="form-group">
                    <label for="template_code">Kode Template <span class="required">*</span></label>
                    <input type="text" class="form-control" id="template_code" name="template_code"
                           placeholder="Contoh: TPL-IT-SPECIALIST" required>
                    <small class="form-text text-danger" id="error_template_code"></small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="version">Versi</label>
                    <input type="text" class="form-control" id="version" name="version" value="1" readonly edit-readonly="true">
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label class="d-block">&nbsp;</label>
                    <div class="form-check mt-2">
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input active" id="isactive"
                                   name="isactive" value="Y" checked>
                            <span class="form-check-sign">Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="title">Judul Template <span class="required">*</span></label>
                    <input type="text" class="form-control" id="title" name="title"
                           placeholder="Contoh: Performance Appraisal IT & Digital Division" required>
                    <small class="form-text text-danger" id="error_title"></small>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="description">Deskripsi Template</label>
                    <textarea class="form-control" id="description" name="description" rows="2"
                              placeholder="Deskripsi singkat mengenai peruntukan template penilaian ini (opsional)..."></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="md_levelling_id">Jenjang / Level Target</label>
                    <select class="form-control multiple-select-levelling access" id="md_levelling_id" name="md_levelling_id" multiple="multiple">
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="md_division_id">Divisi Target</label>
                    <select class="form-control multiple-select-division access" id="md_division_id" name="md_division_id" multiple="multiple">
                    </select>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="md_branch_id">Cabang Target</label>
                    <select class="form-control multiple-select-branch access" id="md_branch_id" name="md_branch_id" multiple="multiple">
                    </select>
                </div>
            </div>
            
            <!-- Toolbar Preview Target & Form (Fixed Style) -->
            <div class="col-md-12 mt-1 mb-2">
                <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded" style="background: #fbfcfd; border: 1px solid #ebedf2;">
                    <!-- Sisi Kiri: Status Live Target -->
                    <div class="d-flex align-items-center">
                        <span class="text-primary mr-2" style="font-size: 1rem;">
                            <i class="fas fa-user-check"></i>
                        </span>
                        <span class="small text-muted font-weight-normal">
                            Target Peserta: 
                            <strong class="text-dark ml-1" id="lbl_target_summary">Semua Karyawan (Global)</strong>
                        </span>
                    </div>

                    <!-- Sisi Kanan: Action Buttons -->
                    <div class="d-flex align-items-center">
                        <button type="button" 
                                class="btn btn-outline-secondary btn-round btn-sm" 
                                id="btn_preview_peserta"
                                title="Lihat simulasi daftar peserta">
                            <i class="fas fa-users mr-1"></i> Preview Peserta
                        </button>

                        <button type="button" 
                                class="btn btn-outline-secondary btn-round btn-sm" 
                                id="btn_preview_form"
                                title="Lihat simulasi lembar form evaluasi">
                            <i class="fas fa-file-alt mr-1"></i> Preview Form
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABBED LINE ITEMS SECTION -->
        <div class="mt-4 tpl-wrap">
            <div class="tpl-tabs-bar">
                <ul class="nav nav-line nav-color-primary tpl-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-checklist" data-toggle="pill" href="#checklist_pane" data-target="#checklist_pane" role="tab" aria-controls="checklist_pane" aria-selected="true">
                            Checklist
                            <span class="badge badge-info tpl-count" data-tpl-count="BOOLEAN">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-slider" data-toggle="pill" href="#slider_pane" data-target="#slider_pane" role="tab" aria-controls="slider_pane" aria-selected="false">
                            Skala
                            <span class="badge badge-primary tpl-count" data-tpl-count="SCORE">0</span>
                            <i class="fas fa-exclamation-triangle text-warning tpl-flag" id="tpl_weight_flag" hidden title="Total bobot belum 100%"></i>
                        </a>
                    </li>
                </ul>
                <div class="tpl-tabs-actions">
                    <a href="javascript:void(0)" role="button" class="tpl-collapse-all" onclick="window.tplToggleAll(false);" title="Tutup Semua Kelompok">
                        <i class="fas fa-compress-alt"></i> Ringkas Semua
                    </a>
                    <span class="text-muted mx-1">&middot;</span>
                    <a href="javascript:void(0)" role="button" class="tpl-collapse-all" onclick="window.tplToggleAll(true);" title="Buka Semua Kelompok">
                        <i class="fas fa-expand-alt"></i> Buka Semua
                    </a>
                </div>
            </div>

            <div class="tab-content pt-3">

                <!-- TAB 1: CHECKLIST -->
                <div class="tab-pane fade show active" id="checklist_pane" role="tabpanel" aria-labelledby="tab-checklist">
                    <div class="tpl-pane-head">
                        <div>
                            <h6 class="mb-0 font-weight-bold">Kriteria Kepatuhan (Pass / Fail)</h6>
                            <small class="text-muted">Evaluasi biner Ya / Tidak tanpa bobot persentase.</small>
                        </div>
                        <a href="javascript:void(0)" role="button" class="btn btn-info btn-sm btn-round" onclick="window.tplAddCard('BOOLEAN');">
                            <i class="fas fa-plus fa-fw"></i> Tambah Kelompok
                        </a>
                    </div>

                    <div id="tpl_container_checklist"></div>

                    <div class="tpl-empty" data-tpl-empty="BOOLEAN">
                        <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                        <div class="font-weight-bold">Belum ada kelompok checklist</div>
                        <small>Contoh: K3, Security Audit, Kerahasiaan Data.</small>
                    </div>

                    <a href="javascript:void(0)" role="button" class="tpl-add-bottom" data-tpl-bottom="BOOLEAN" hidden onclick="window.tplAddCard('BOOLEAN');">
                        <i class="fas fa-plus fa-fw"></i> Tambah Kelompok Checklist
                    </a>
                </div>

                <!-- TAB 2: SKALA -->
                <div class="tab-pane fade" id="slider_pane" role="tabpanel" aria-labelledby="tab-slider">
                    <div class="tpl-pane-head">
                        <div>
                            <h6 class="mb-0 font-weight-bold">Kompetensi &amp; Performa Kerja (Skor 0&ndash;4)</h6>
                            <small class="text-muted">Total bobot seluruh kelompok harus tepat 100%.</small>
                        </div>
                        <a href="javascript:void(0)" role="button" class="btn btn-primary btn-sm btn-round" onclick="window.tplAddCard('SCORE');">
                            <i class="fas fa-plus fa-fw"></i> Tambah Kelompok
                        </a>
                    </div>

                    <!-- Weight Meter Bar -->
                    <div class="tpl-weight" id="tpl_weight" data-state="low" hidden>
                        <div class="tpl-weight-top">
                            <span>Total Bobot</span>
                            <b id="tpl_weight_total">0%</b>
                            <span id="tpl_weight_msg" class="tpl-weight-msg">Kurang 100%</span>
                        </div>
                        <div class="tpl-weight-bar"><span id="tpl_weight_fill"></span></div>
                    </div>

                    <div id="tpl_container_slider"></div>

                    <div class="tpl-empty" data-tpl-empty="SCORE">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <div class="font-weight-bold">Belum ada kelompok skala penilaian</div>
                        <small>Contoh: Job Knowledge, Problem Solving, Leadership.</small>
                    </div>

                    <a href="javascript:void(0)" role="button" class="tpl-add-bottom" data-tpl-bottom="SCORE" hidden onclick="window.tplAddCard('SCORE');">
                        <i class="fas fa-plus fa-fw"></i> Tambah Kelompok Skala
                    </a>
                </div>

            </div>
        </div>
    </form>
</div>

<style>
/* Appraisal Template Builder - simplified, uses Atlantis Pro theme tokens */
.tpl-wrap { width: 100%; max-width: 100%; overflow-x: hidden; }
.tpl-wrap *, .tpl-wrap *::before, .tpl-wrap *::after { box-sizing: border-box; }
.tpl-tabs-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; border-bottom: 1px solid #ebedf2; }
.tpl-tabs { border-bottom: 0; flex-wrap: wrap; overflow-x: visible; max-width: 100%; margin-bottom: -1px; }
.tpl-tabs .nav-link { white-space: nowrap; padding: 12px 0 !important; margin-right: 28px; }
.tpl-tabs .nav-link .badge { margin-left: 4px; }
.tpl-tabs-actions { font-size: 12px; }
.tpl-collapse-all { color: #6b6d7e; text-decoration: none !important; }
.tpl-collapse-all:hover { color: #1572e8; }
[hidden].tpl-flag, .tpl-weight[hidden], .tpl-empty[hidden], .tpl-add-bottom[hidden] { display: none !important; }

.tpl-pane-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .75rem; margin-bottom: 1rem; }
.tpl-pane-head h6 { color: #2a2f5b; }

/* Weight meter */
.tpl-weight { margin-bottom: 1rem; padding: .75rem 1rem; border: 1px solid #ebedf2; border-radius: 4px; background: #fff; }
.tpl-weight-top { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; font-size: 12px; color: #6b6d7e; }
.tpl-weight-top b { font-size: 1rem; color: #2a2f5b; }
.tpl-weight-msg { margin-left: auto; font-weight: 600; }
.tpl-weight-bar { height: 6px; margin-top: .5rem; border-radius: 3px; background: #ebedf2; overflow: hidden; }
.tpl-weight-bar span { display: block; height: 100%; width: 0; background: #ffad46; transition: width .25s ease; }
.tpl-weight[data-state="ok"] .tpl-weight-bar span { background: #31ce36; }
.tpl-weight[data-state="ok"] .tpl-weight-msg { color: #31ce36; }
.tpl-weight[data-state="low"] .tpl-weight-msg { color: #ffad46; }
.tpl-weight[data-state="high"] .tpl-weight-bar span { background: #f25961; }
.tpl-weight[data-state="high"] .tpl-weight-msg { color: #f25961; }

/* Empty state & bottom add */
.tpl-empty { padding: 2rem 1rem; border: 1px dashed #ebedf2; border-radius: 4px; text-align: center; color: #8d9498; }
.tpl-empty i { color: #c9ced6; }
.tpl-add-bottom { display: block; padding: .6rem; border: 1px dashed #ebedf2; border-radius: 4px; text-align: center; font-size: 13px; font-weight: 600; color: #1572e8; text-decoration: none !important; }
.tpl-add-bottom:hover { background: #f8f9fa; color: #1572e8; }

/* Competency card */
.tpl-card { margin-bottom: .75rem; border: 1px solid #ebedf2; border-radius: 4px; background: #fff; box-shadow: none; overflow: hidden; max-width: 100%; }
.tpl-card--info { --tpl-accent: #48abf7; }
.tpl-card--primary { --tpl-accent: #1572e8; }
.tpl-card-head { display: flex; align-items: center; gap: .6rem; padding: .55rem .85rem; background: #f8f9fa; border-bottom: 1px solid #ebedf2; border-left: 3px solid var(--tpl-accent, #1572e8); }
.tpl-toggle { display: flex; align-items: center; gap: .6rem; flex: 1 1 auto; min-width: 0; color: inherit; text-decoration: none !important; cursor: pointer; }
.tpl-chev { font-size: .7rem; color: #8d9498; transition: transform .2s ease; }
.tpl-card.is-collapsed .tpl-chev { transform: rotate(-90deg); }
.tpl-card.is-collapsed .tpl-card-body { display: none; }
.tpl-card.is-collapsed .tpl-card-head { border-bottom-color: transparent; }
.tpl-num { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: var(--tpl-accent, #1572e8); color: #fff; font-size: .7rem; font-weight: 700; }
.tpl-titles { display: flex; align-items: baseline; gap: .5rem; flex-wrap: wrap; min-width: 0; }
.tpl-title-text { color: #2a2f5b; font-size: 14px; font-weight: 600; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tpl-sub { color: #8d9498; font-size: 12px; }
.tpl-weight-chip { flex: none; padding: 1px 8px; border-radius: 3px; background: rgba(21,114,232,.1); color: #1572e8; font-size: 12px; font-weight: 600; }
.tpl-del { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 4px; color: #8d9498; cursor: pointer; }
.tpl-del:hover { background: rgba(242,89,97,.1); color: #f25961; text-decoration: none; }
.tpl-card-body { padding: 1rem; min-width: 0; }
.tpl-card-body > .row { margin-left: -.5rem; margin-right: -.5rem; }
.tpl-card-body > .row > [class*="col-"] { padding-left: .5rem; padding-right: .5rem; min-width: 0; }

.tpl-field { margin-bottom: .75rem; }
.tpl-field label, .tpl-actions-head label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #575962; }

/* Key actions */
.tpl-actions { padding: .75rem; border: 1px solid #ebedf2; border-radius: 4px; background: #fbfcfd; min-width: 0; }
.tpl-actions-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .25rem; margin-bottom: .5rem; }
.tpl-count-actions { display: inline-block; min-width: 18px; margin-left: 4px; padding: 0 6px; border-radius: 9px; background: #ebedf2; color: #575962; font-size: 11px; text-align: center; }
.tpl-action-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .4rem; width: 100%; }
.tpl-row-no { flex: none; width: 20px; text-align: center; font-size: 12px; font-weight: 600; color: #8d9498; }
.tpl-action-row .form-control { flex: 1 1 auto; min-width: 0; width: 100%; background: #fff; }
.tpl-row-del { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 4px; color: #8d9498; font-size: .8rem; cursor: pointer; }
.tpl-row-del:hover { background: rgba(242,89,97,.1); color: #f25961; text-decoration: none; }
.tpl-add-row { display: inline-flex; align-items: center; gap: .4rem; margin-top: .25rem; font-size: 12px; font-weight: 600; color: #1572e8; cursor: pointer; text-decoration: none !important; }
.tpl-add-row:hover { text-decoration: underline !important; }
.tpl-card.is-new { animation: tplIn .22s ease-out; }
@keyframes tplIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
.tpl-card .is-invalid { border-color: #f25961; }
</style>

<!-- UNIFIED APPRAISAL TEMPLATE BUILDER & PAYLOAD ENGINE (Self-Bootstrapping) -->
<script>
(function () {
    function bootAppraisalTemplateForm() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(bootAppraisalTemplateForm, 50);
            return;
        }

        var $ = window.jQuery;
        var $form = $('#form_appraisal_template');
        if (!$form.length) return;

        var esc = function (s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        };
        window.tplEsc = esc;

        var TYPES = {
            BOOLEAN: { box: '#tpl_container_checklist', tone: 'info',    pane: '#checklist_pane' },
            SCORE:   { box: '#tpl_container_slider',    tone: 'primary', pane: '#slider_pane' }
        };

        var COLLAPSE_AFTER = 3;

        function fieldHtml(label, inner, required) {
            return '<div class="tpl-field"><label>' + label +
                   (required ? ' <span class="text-danger">*</span>' : '') +
                   '</label>' + inner + '</div>';
        }

        function inputHtml(name, value, placeholder, required) {
            return '<input type="text" name="' + name + '" class="form-control form-control-sm line" value="' +
                   esc(value) + '" placeholder="' + esc(placeholder) + '"' + (required ? ' required' : '') + '>';
        }

        function cardHeadHtml(isChecklist) {
            return '<div class="tpl-card-head">' +
                '<a href="javascript:void(0)" role="button" class="tpl-toggle" data-tpl="toggle" aria-expanded="true">' +
                    '<i class="fas fa-chevron-down tpl-chev" aria-hidden="true"></i>' +
                    '<span class="tpl-num" aria-hidden="true"></span>' +
                    '<span class="tpl-titles">' +
                        '<b class="tpl-title-text">Kelompok Kompetensi Baru</b>' +
                        '<small class="tpl-sub"></small>' +
                    '</span>' +
                '</a>' +
                (isChecklist ? '' : '<span class="tpl-weight-chip" title="Bobot Kelompok">0%</span>') +
                '<a href="javascript:void(0)" role="button" class="tpl-del" data-tpl="del-card" title="Hapus Kelompok">' +
                    '<i class="far fa-trash-alt"></i>' +
                '</a>' +
            '</div>';
        }

        function cardBodyHtml(type, data, isChecklist) {
            var weightCol = isChecklist
                ? '<input type="hidden" name="weight[]" class="line" value="0">'
                : '<div class="col-md-3">' + fieldHtml('Bobot (%)',
                    '<div class="input-group input-group-sm">' +
                        '<input type="number" name="weight[]" class="form-control text-right font-weight-bold line" value="' + esc(data.weight || 0) + '" step="0.1" min="0" max="100">' +
                        '<div class="input-group-append"><span class="input-group-text">%</span></div>' +
                    '</div>') + '</div>';

            return '<div class="tpl-card-body">' +
                '<input type="hidden" name="md_appraisal_template_item_id[]" class="line" value="' + esc(data.md_appraisal_template_item_id) + '">' +
                '<input type="hidden" name="input_type[]" class="line" value="' + esc(type) + '">' +
                '<div class="row">' +
                    '<div class="col-md-4">' + fieldHtml('Kategori',
                        inputHtml('category[]', data.category, isChecklist ? 'Contoh: COMPLIANCE' : 'Contoh: PERFORMA'), true) + '</div>' +
                    '<div class="col-md-' + (isChecklist ? '8' : '5') + '">' + fieldHtml('Nama Kompetensi',
                        inputHtml('competency[]', data.competency, isChecklist ? 'Contoh: Security Audit' : 'Contoh: Job Knowledge'), true) + '</div>' +
                    weightCol +
                    '<div class="col-12">' + fieldHtml('Definisi Kompetensi',
                        '<textarea name="definition[]" class="form-control form-control-sm line" rows="2" placeholder="Jelaskan secara singkat deskripsi atau ekspektasi dari kompetensi ini...">' + esc(data.definition) + '</textarea>') + '</div>' +
                '</div>' +
                '<div class="tpl-actions">' +
                    '<div class="tpl-actions-head">' +
                        '<label class="mb-0">Daftar Indikator / Key Actions <span class="tpl-count-actions">0</span></label>' +
                        '<small class="text-muted">Tekan <b>Enter</b> untuk menambah baris</small>' +
                    '</div>' +
                    '<div class="tpl-action-list"></div>' +
                    '<textarea name="key_actions[]" class="d-none tpl-hidden-actions line"></textarea>' +
                    '<a href="javascript:void(0)" role="button" class="tpl-add-row" data-tpl="add-row">' +
                        '<i class="fas fa-plus"></i> Tambah Baris Indikator' +
                    '</a>' +
                '</div>' +
            '</div>';
        }

        function filledActions($card) {
            return $card.find('.tpl-action-input').filter(function () { return $.trim($(this).val()) !== ''; });
        }

        window.tplSyncActions = function ($card) {
            var lines = [];
            $card.find('.tpl-action-input').each(function () {
                var val = $.trim($(this).val());
                if (val.length > 0) { lines.push(val); }
            });
            $card.find('.tpl-hidden-actions').val(JSON.stringify(lines));
        };

        window.tplRefresh = function () {
            var total = 0;
            Object.keys(TYPES).forEach(function (t) {
                var $cards = $(TYPES[t].box).children('.tpl-card');
                $cards.each(function (i) {
                    var $c   = $(this);
                    var comp = $.trim($c.find('input[name="competency[]"]').val());
                    var cat  = $.trim($c.find('input[name="category[]"]').val());
                    var n    = filledActions($c).length;

                    $c.find('.tpl-num').first().text(i + 1);
                    $c.find('.tpl-title-text').text(comp || 'Kelompok Kompetensi Baru');
                    $c.find('.tpl-sub').text((cat ? '[' + cat + '] \u00B7 ' : '') + n + ' indikator');
                    $c.find('.tpl-count-actions').text(n);
                    $c.find('.tpl-action-row').each(function (j) {
                        $(this).find('.tpl-row-no').text(j + 1);
                    });

                    if (t === 'SCORE') {
                        var w = parseFloat($c.find('input[name="weight[]"]').val()) || 0;
                        total += w;
                        $c.find('.tpl-weight-chip').text((Math.round(w * 10) / 10) + '%');
                    }
                });

                $('[data-tpl-count="' + t + '"]').text($cards.length);
                $('[data-tpl-empty="' + t + '"]').prop('hidden', $cards.length > 0);
                $('[data-tpl-bottom="' + t + '"]').prop('hidden', $cards.length === 0);
            });

            var hasScore = $(TYPES.SCORE.box).children('.tpl-card').length > 0;
            var sum      = Math.round(total * 10) / 10;
            var diff     = Math.round((sum - 100) * 10) / 10;
            var state    = Math.abs(diff) < 0.05 ? 'ok' : (diff < 0 ? 'low' : 'high');

            $('#tpl_weight').prop('hidden', !hasScore).attr('data-state', state);
            $('#tpl_weight_total').text(sum + '%');
            $('#tpl_weight_fill').css('width', Math.min(100, Math.max(0, sum)) + '%');
            $('#tpl_weight_msg').text(
                state === 'ok'
                    ? 'Pas 100% (Siap Simpan)'
                    : (state === 'low' ? 'Kurang ' + Math.abs(diff) + '%' : 'Lebih ' + diff + '%')
            );
            $('#tpl_weight_flag').prop('hidden', !hasScore || state === 'ok');
        };

        window.tplToggleCard = function ($card, open) {
            var collapse = (open === undefined) ? !$card.hasClass('is-collapsed') : !open;
            $card.toggleClass('is-collapsed', collapse);
            $card.find('[data-tpl="toggle"]').attr('aria-expanded', collapse ? 'false' : 'true');
        };

        window.tplToggleAll = function (open) {
            $('.tab-pane.active .tpl-card').each(function () {
                window.tplToggleCard($(this), open);
            });
        };

        window.tplAppendActionRow = function ($container, val, skipRefresh) {
            $container.append(
                '<div class="tpl-action-row">' +
                    '<span class="tpl-row-no" aria-hidden="true"></span>' +
                    '<input type="text" class="form-control form-control-sm tpl-action-input line" value="' + esc(val) + '" placeholder="Ketik indikator perilaku kerja, tekan Enter untuk baris baru...">' +
                    '<a href="javascript:void(0)" role="button" class="tpl-row-del" data-tpl="del-row" title="Hapus indikator"><i class="fas fa-times"></i></a>' +
                '</div>'
            );
            if (!skipRefresh) { window.tplRefresh(); }
        };

        window.tplRemoveActionRow = function (elem) {
            var $row  = $(elem).closest('.tpl-action-row');
            var $card = $row.closest('.tpl-card');
            if ($card.find('.tpl-action-row').length > 1) {
                $row.remove();
            } else {
                $row.find('.tpl-action-input').val('').focus();
            }
            window.tplSyncActions($card);
            window.tplRefresh();
        };

        function removeCard($card) {
            $card.remove();
            window.tplRefresh();
        }

        window.tplRemoveCard = function (elem) {
            var $card = $(elem).closest('.tpl-card');
            var filled = $.trim($card.find('input[name="competency[]"], input[name="category[]"], textarea[name="definition[]"]').map(function () { return $(this).val(); }).get().join('')) !== '' ||
                         (parseFloat($card.find('input[name="weight[]"]').val()) || 0) > 0 ||
                         filledActions($card).length > 0;

            if (!filled) { removeCard($card); return; }

            Swal.fire({
                title: 'Hapus kelompok?',
                text: 'Kelompok kompetensi ini beserta seluruh indikatornya akan dihapus.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (r) {
                if (r.value) { removeCard($card); }
            });
        };

        window.tplAddCard = function (type, data, opts) {
            var isNew = !data || $.isEmptyObject(data);
            data = data || {};
            opts = opts || {};
            var t = TYPES[type] || TYPES.SCORE;
            var isChecklist = (type === 'BOOLEAN');

            var $card = $(
                '<div class="card tpl-card tpl-card--' + t.tone + (isNew ? ' is-new' : '') + '">' +
                    cardHeadHtml(isChecklist) + cardBodyHtml(type, data, isChecklist) +
                '</div>'
            );

            $(t.box).append($card);

            var $list = $card.find('.tpl-action-list');
            var actions = [];
            if (typeof data.key_actions === 'string' && $.trim(data.key_actions) !== '') {
                try {
                    actions = JSON.parse(data.key_actions);
                } catch (e) {
                    actions = data.key_actions.split('\n');
                }
            } else if (Array.isArray(data.key_actions)) {
                actions = data.key_actions;
            }
            if (!Array.isArray(actions) || actions.length === 0) { actions = ['']; }
            actions.forEach(function (act) { window.tplAppendActionRow($list, act, true); });

            window.tplSyncActions($card);
            if (opts.collapsed) { window.tplToggleCard($card, false); }
            if (!opts.skipRefresh) { window.tplRefresh(); }

            if (isNew) {
                $card.one('animationend', function () { $card.removeClass('is-new'); });
                requestAnimationFrame(function () {
                    if ($card[0].scrollIntoView) { $card[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
                    $card.find('input[name="category[]"]').focus();
                });
            }
            return $card;
        };

        /**
         * Render cards from either:
         * - Unified redesign `sections` array (`[{ md_appraisal_section_id, section_type, items: [...] }]`), OR
         * - Legacy flat `items` array (`[{ input_type, category, competency, ... }]`)
         */
        window.renderTemplateItems = function (sectionsOrItems) {
            $('#tpl_container_checklist').empty().removeData('sectionId');
            $('#tpl_container_slider').empty().removeData('sectionId');

            if (Array.isArray(sectionsOrItems) && sectionsOrItems.length > 0) {
                var seen = { BOOLEAN: 0, SCORE: 0 };
                var total = { BOOLEAN: 0, SCORE: 0 };
                var typeOf = function (x) { return x === 'BOOLEAN' ? 'BOOLEAN' : 'SCORE'; };

                var isNestedSections = Array.isArray(sectionsOrItems[0].items) || sectionsOrItems[0].section_type !== undefined;

                if (isNestedSections) {
                    sectionsOrItems.forEach(function (sec) {
                        (sec.items || []).forEach(function (it) { total[typeOf(it.input_type || sec.section_type)]++; });
                    });

                    sectionsOrItems.forEach(function (sec) {
                        var sType = typeOf(sec.section_type);
                        $(TYPES[sType].box).data('sectionId', sec.md_appraisal_section_id || null);

                        (sec.items || []).forEach(function (it) {
                            var type = typeOf(it.input_type || sec.section_type);
                            var cfg = it.config || {};
                            if (typeof cfg === 'string') {
                                try { cfg = JSON.parse(cfg); } catch (e) { cfg = {}; }
                            }
                            var cardData = {
                                md_appraisal_template_item_id: it.md_appraisal_template_item_id || '',
                                category: it.title || it.category || '',
                                competency: cfg.competency || it.competency || '',
                                definition: it.description || it.definition || '',
                                key_actions: cfg.key_actions || it.key_actions || [],
                                weight: it.weight || 0
                            };
                            var collapsed = total[type] > COLLAPSE_AFTER && seen[type] > 0;
                            seen[type]++;
                            window.tplAddCard(type, cardData, { collapsed: collapsed, skipRefresh: true });
                        });
                    });
                } else {
                    sectionsOrItems.forEach(function (it) { total[typeOf(it.input_type)]++; });
                    sectionsOrItems.forEach(function (it) {
                        var type = typeOf(it.input_type);
                        var collapsed = total[type] > COLLAPSE_AFTER && seen[type] > 0;
                        seen[type]++;
                        window.tplAddCard(type, it, { collapsed: collapsed, skipRefresh: true });
                    });
                }
            }
            window.tplRefresh();
        };

        /**
         * Preload Select2 scoping options ({ branch_ids, division_ids, levelling_ids })
         */
        window.tplRenderScoping = function (scoping) {
            scoping = scoping || {};
            var pairs = [
                ['#md_levelling_id, #md_position_id', 'levelling_ids', 'position_ids'],
                ['#md_division_id', 'division_ids', null],
                ['#md_branch_id',   'branch_ids', null]
            ];
            pairs.forEach(function (pair) {
                var $el = $form.find(pair[0]).first();
                if (!$el.length) return;
                $el.empty();
                var list = scoping[pair[1]] || (pair[2] ? scoping[pair[2]] : []) || [];
                list.forEach(function (entry) {
                    var id   = (typeof entry === 'object' && entry !== null) ? (entry.id || entry.md_levelling_id || entry.md_position_id || entry.md_division_id || entry.md_branch_id) : entry;
                    var text = (typeof entry === 'object' && entry !== null) ? (entry.text || entry.name || String(id)) : String(id);
                    if (id !== undefined && id !== null && id !== '') {
                        $el.append(new Option(text, id, true, true));
                    }
                });
                $el.trigger('change');
            });
            window.tplUpdateTargetSummary();
        };

        /**
         * 1. Universal Data Collector
         * Bundles all frontend state into a single unified object matching CreatePerformanceAppraisalRedesign.
         */
        window.tplCollectData = function () {
            var form = $('#form_appraisal_template');
            if (!form.length) return null;

            var toIntArray = function (raw) {
                return (Array.isArray(raw) ? raw : [raw])
                    .filter(Boolean)
                    .map(function (v) { return parseInt(v, 10); })
                    .filter(function (v) { return !isNaN(v) && v > 0; });
            };

            var $lvlSelect = form.find('#md_levelling_id, #md_position_id').first();
            var scoping = {
                branch_ids:    toIntArray(form.find('#md_branch_id').val() || []),
                division_ids:  toIntArray(form.find('#md_division_id').val() || []),
                levelling_ids: toIntArray($lvlSelect.val() || []),
                position_ids:  toIntArray($lvlSelect.val() || [])
            };

            var sectionsConfig = [
                {
                    containerId: '#tpl_container_checklist',
                    defaultName: 'Kriteria Kepatuhan',
                    sectionType: 'BOOLEAN',
                    defaultWeight: 0.00,
                    sequenceOrder: 1,
                    description: 'Evaluasi biner Ya / Tidak tanpa bobot persentase'
                },
                {
                    containerId: '#tpl_container_slider',
                    defaultName: 'Kompetensi & Performa Kerja',
                    sectionType: 'SCORE',
                    defaultWeight: 100.00,
                    sequenceOrder: 2,
                    description: 'Total bobot seluruh kelompok harus tepat 100%'
                }
            ];

            var totalScoreWeight = 0;
            var sections = [];

            sectionsConfig.forEach(function (cfg) {
                var container = form.find(cfg.containerId);
                var cards = container.find('.tpl-card');
                var items = [];
                var sectionWeight = 0;
                var itemOrder = 0;

                cards.each(function () {
                    var card = $(this);
                    if (window.tplSyncActions) window.tplSyncActions(card);

                    itemOrder++;
                    var getVal = function (name) {
                        return card.find('[name="' + name + '"], [name="' + name + '[]"]').val() || '';
                    };

                    var cardTitleText = card.find('.tpl-title-text').text().trim();
                    var rawCat        = $.trim(getVal('category'));
                    var title         = rawCat || (cardTitleText && cardTitleText !== 'Kelompok Kompetensi Baru' ? cardTitleText : '') || ('Item ' + itemOrder);
                    var competency    = $.trim(getVal('competency'));
                    var definition    = $.trim(getVal('definition'));
                    var rawKeyActions = getVal('key_actions') || '[]';

                    var keyActionsList = [];
                    try {
                        keyActionsList = typeof rawKeyActions === 'string' ? JSON.parse(rawKeyActions) : rawKeyActions;
                        if (!Array.isArray(keyActionsList)) keyActionsList = [];
                    } catch (e) {
                        keyActionsList = [];
                    }

                    var inputType  = getVal('input_type') || cfg.sectionType;
                    var itemWeight = (inputType === 'BOOLEAN') ? 0 : (parseFloat(getVal('weight')) || 0);
                    var itemId     = parseInt(getVal('md_appraisal_template_item_id'), 10) || null;

                    if (inputType === 'SCORE') {
                        sectionWeight += itemWeight;
                        totalScoreWeight += itemWeight;
                    }

                    var configObj = {
                        competency: competency,
                        key_actions: keyActionsList,
                        min_score: 0,
                        max_score: (inputType === 'BOOLEAN' ? 1 : 4)
                    };

                    items.push({
                        md_appraisal_template_item_id: itemId,
                        title: title,
                        description: definition,
                        input_type: inputType,
                        weight: itemWeight,
                        sequence_order: itemOrder,
                        is_required: 'Y',
                        config: configObj
                    });
                });

                if (items.length === 0) return;

                var finalSecWeight = (cfg.sectionType === 'BOOLEAN')
                    ? 0.00
                    : (sectionWeight > 0 ? parseFloat(sectionWeight.toFixed(2)) : cfg.defaultWeight);

                sections.push({
                    md_appraisal_section_id: container.data('sectionId') || null,
                    name: cfg.defaultName,
                    section_type: cfg.sectionType,
                    weight: finalSecWeight,
                    sequence_order: cfg.sequenceOrder,
                    description: cfg.description,
                    items: items
                });
            });

            var rawTemplateId = parseInt($('#md_appraisal_template_id').val(), 10) || (typeof ID !== 'undefined' && parseInt(ID, 10) > 0 ? parseInt(ID, 10) : null);

            return {
                md_appraisal_template_id: rawTemplateId,
                template_code: $.trim($('#template_code').val() || ''),
                title: $.trim($('#title').val() || ''),
                description: $.trim($('#description').val() || ''),
                version: parseInt($('#version').val(), 10) || 1,
                isactive: $('#isactive').is(':checked') ? 'Y' : 'N',
                scoping: scoping,
                sections: sections,
                total_score_weight: parseFloat(totalScoreWeight.toFixed(2)),
                is_weight_valid: Math.abs(totalScoreWeight - 100.0) <= 0.05
            };
        };

        /**
         * 2. Frontend Validator
         * Validates header fields, competency cards, indicators, and 100% weight rule.
         */
        window.tplValidate = function (data) {
            var fail = function (msg, $el) { return { ok: false, msg: msg, $el: $el || null }; };
            var form = $('#form_appraisal_template');

            if (!$.trim(data.template_code)) return fail('Kode template wajib diisi.', $('#template_code'));
            if (!$.trim(data.title))         return fail('Judul template wajib diisi.', $('#title'));
            if (!data.sections || data.sections.length === 0) {
                return fail('Template harus memiliki minimal satu kelompok kompetensi.');
            }

            var bad = null;
            form.find('.tpl-card').each(function () {
                var card = $(this);
                var $f;
                if (($f = card.find('input[name="category[]"]')).length && !$.trim($f.val())) {
                    bad = fail('Kategori wajib diisi.', $f); return false;
                }
                if (($f = card.find('input[name="competency[]"]')).length && !$.trim($f.val())) {
                    bad = fail('Nama kompetensi wajib diisi.', $f); return false;
                }
                if (filledActions(card).length === 0) {
                    bad = fail('Minimal satu indikator per kelompok.', card.find('.tpl-action-input').first()); return false;
                }
            });
            if (bad) return bad;

            var hasScore = data.sections.some(function (sec) { return sec.section_type === 'SCORE'; });
            if (hasScore && !data.is_weight_valid) {
                return fail('Total bobot kelompok skala harus tepat 100%. Saat ini: ' + data.total_score_weight + '%');
            }

            return { ok: true };
        };

        /**
         * 3. Cleanup helper after save completes
         */
        window.tplCleanupAfterSave = function () {
            var form = $('#form_appraisal_template');
            form.find('.tpl-tmp-line').removeClass('line tpl-tmp-line');
            form.find('.custom-tpl-item').remove();
        };

        /**
         * 4. Pre-Save Processor (called directly by Process.js before collecting form fields)
         * Validates data, masks internal card/scoping inputs with .line, and injects unified `payload`.
         */
        window.tplPrepareSave = function () {
            var form = $('#form_appraisal_template');
            if (!form.length || !form.is(':visible')) return true;

            window.tplCleanupAfterSave();

            var data = window.tplCollectData();
            if (!data) return true;

            var result = window.tplValidate(data);
            if (!result.ok) {
                var $card = result.$el ? result.$el.closest('.tpl-card') : $();
                var $pane = $card.closest('.tab-pane');
                if ($pane.length && !$pane.hasClass('active')) {
                    $('a[data-toggle="pill"][href="#' + $pane.attr('id') + '"]').tab('show');
                }
                if ($card.length && window.tplToggleCard) window.tplToggleCard($card, true);
                if (result.$el && result.$el.length) {
                    result.$el.addClass('is-invalid').one('input', function () { $(this).removeClass('is-invalid'); }).focus();
                }
                if (typeof Toast !== 'undefined') {
                    Toast.fire({ type: 'warning', title: result.msg });
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({ type: 'warning', title: result.msg });
                }
                return false;
            }

            // Mask card inputs & target scoping selects so Process.js only serializes header + unified payload
            form.find('.tpl-card :input, #md_position_id, #md_levelling_id, #md_division_id, #md_branch_id')
                .not('.line')
                .addClass('line tpl-tmp-line');

            var holder = form.find('#tpl_header_row');
            if (!holder.length) holder = form.find('.row').first();

            $('<input>', {
                type: 'hidden',
                'class': 'custom-tpl-item',
                name: 'payload',
                value: JSON.stringify(data)
            }).appendTo(holder);

            return true;
        };

        /**
         * 5. Live Target Summary Indicator
         */
        window.tplUpdateTargetSummary = function () {
            var rawPositions = $('#md_position_id, #md_levelling_id').first().val() || [];
            var rawDivisions = $('#md_division_id').val() || [];
            var rawBranches  = $('#md_branch_id').val() || [];

            var posCount = (Array.isArray(rawPositions) ? rawPositions : [rawPositions]).filter(Boolean).length;
            var divCount = (Array.isArray(rawDivisions) ? rawDivisions : [rawDivisions]).filter(Boolean).length;
            var brCount  = (Array.isArray(rawBranches) ? rawBranches : [rawBranches]).filter(Boolean).length;

            var parts = [];
            if (posCount) parts.push(posCount + ' Level');
            if (divCount) parts.push(divCount + ' Divisi');
            if (brCount)  parts.push(brCount + ' Cabang');

            if (parts.length === 0) {
                $('#lbl_target_summary').text('Semua Karyawan (Global)');
            } else {
                $('#lbl_target_summary').text(parts.join(' \u00B7 '));
            }
        };

        // Self-contained AJAX hooks for Edit / Save / Reset so global Process.js / Event.js remain untouched
        if (!window.__tplEventsBound) {
            window.__tplEventsBound = true;

            // 1. Intercept Save / Edit / Clear via jQuery.ajaxPrefilter & ajaxComplete
            $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
                var $f = $('#form_appraisal_template');
                if (!$f.length || !$f.is(':visible')) return;

                var type = String(options.type || originalOptions.type || 'GET').toUpperCase();
                var url  = String(options.url || '');

                // Intercept Save Form POST with FormData
                if (type === 'POST' && options.data instanceof FormData && url.indexOf('/create') !== -1) {
                    var data = window.tplCollectData();
                    if (!data) return;

                    var result = window.tplValidate(data);
                    if (!result.ok) {
                        jqXHR.abort();
                        var $card = result.$el ? result.$el.closest('.tpl-card') : $();
                        var $pane = $card.closest('.tab-pane');
                        if ($pane.length && !$pane.hasClass('active')) {
                            $('a[data-toggle="pill"][href="#' + $pane.attr('id') + '"]').tab('show');
                        }
                        if ($card.length && window.tplToggleCard) window.tplToggleCard($card, true);
                        if (result.$el && result.$el.length) {
                            result.$el.addClass('is-invalid').one('input', function () { $(this).removeClass('is-invalid'); }).focus();
                        }
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({ type: 'warning', title: result.msg });
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({ type: 'warning', title: result.msg });
                        }
                        return;
                    }

                    // Ensure unified payload is attached to FormData even if Process.js built FormData before our handler
                    options.data.set('payload', JSON.stringify(data));
                    if (data.md_appraisal_template_id) {
                        options.data.set('md_appraisal_template_id', String(data.md_appraisal_template_id));
                        options.data.set('id', String(data.md_appraisal_template_id));
                    }
                    // Strip raw card array keys if any leaked into FormData
                    ['category[]', 'competency[]', 'weight[]', 'definition[]', 'key_actions[]', 'input_type[]', 'md_appraisal_template_item_id[]',
                     'category', 'competency', 'weight', 'definition', 'key_actions', 'input_type', 'md_appraisal_template_item_id'].forEach(function (k) {
                        if (typeof options.data.delete === 'function') {
                            options.data.delete(k);
                        }
                    });
                }
            });

            // 2. Listen for show/edit AJAX response to render cards & scoping automatically
            $(document).ajaxSuccess(function (event, xhr, settings, response) {
                var $f = $('#form_appraisal_template');
                if (!$f.length) return;

                var url = String((settings && settings.url) || '');
                if (url.indexOf('/show/') === -1) return;

                var res = response;
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); } catch (e) { return; }
                }
                if (!Array.isArray(res) || !res[0] || !res[0].message) return;

                var msg = res[0].message;
                if (msg.sections || msg.items) {
                    setTimeout(function () {
                        window.renderTemplateItems(msg.sections || msg.items || []);
                    }, 30);
                }
                if (msg.scoping) {
                    setTimeout(function () {
                        window.tplRenderScoping(msg.scoping || {});
                    }, 30);
                }
            });

            // 3. Reset cards & scoping when New / Close / X button is clicked
            $(document).on('click', '.new_form, .close_form, .x_form', function () {
                var $f = $('#form_appraisal_template');
                if (!$f.length) return;
                setTimeout(function () {
                    window.tplCleanupAfterSave();
                    if (!$('#md_appraisal_template_id').val()) {
                        $f.find('#version').val('1').prop('readonly', true);
                        window.renderTemplateItems([]);
                        window.tplRenderScoping({});
                    }
                }, 50);
            });

            $(document).off('click', '[data-tpl="toggle"]').on('click', '[data-tpl="toggle"]', function () {
                window.tplToggleCard($(this).closest('.tpl-card'));
            });

            $(document).off('click', '[data-tpl="del-card"]').on('click', '[data-tpl="del-card"]', function () {
                window.tplRemoveCard(this);
            });

            $(document).off('click', '[data-tpl="del-row"]').on('click', '[data-tpl="del-row"]', function () {
                window.tplRemoveActionRow(this);
            });

            $(document).off('click', '[data-tpl="add-row"]').on('click', '[data-tpl="add-row"]', function () {
                var $list = $(this).closest('.tpl-card').find('.tpl-action-list');
                window.tplAppendActionRow($list, '');
                $list.find('.tpl-action-input').last().focus();
            });

            $(document).off('keydown', '.tpl-action-input').on('keydown', '.tpl-action-input', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                if ($.trim($(this).val()) === '') return;
                var $list = $(this).closest('.tpl-action-list');
                window.tplAppendActionRow($list, '');
                $list.find('.tpl-action-input').last().focus();
            });

            $(document)
                .off('input change', '.tpl-card input[name="competency[]"], .tpl-card input[name="category[]"], .tpl-card input[name="weight[]"], .tpl-action-input')
                .on('input change', '.tpl-card input[name="competency[]"], .tpl-card input[name="category[]"], .tpl-card input[name="weight[]"], .tpl-action-input', function () {
                    window.tplSyncActions($(this).closest('.tpl-card'));
                    window.tplRefresh();
                });

            $(document).on('change', '#md_position_id, #md_levelling_id, #md_division_id, #md_branch_id', window.tplUpdateTargetSummary);
        }

        window.tplUpdateTargetSummary();
        window.tplRefresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootAppraisalTemplateForm);
    } else {
        bootAppraisalTemplateForm();
    }
})();
</script>
