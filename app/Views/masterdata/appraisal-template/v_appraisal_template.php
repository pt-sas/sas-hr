<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<!-- MAIN FORM CONTAINER -->
<?= $this->include('masterdata/appraisal-template/form_appraisal_template'); ?>
<?= $this->include('masterdata/appraisal-template/modal_preview_participants'); ?>
<?= $this->include('masterdata/appraisal-template/modal_preview_form'); ?>
<!-- DataTables Overview -->
<div class="card-body card-main">
    <table class="table table-striped table-hover tb_display" style="width: 100%;">
        <thead>
            <tr>
                <th>ID</th>
                <th style="width: 60px;">No</th>
                <th>Cabang</th>
                <th>Divisi</th>
                <th>Level</th>
                <th style="width: 15%;">No Doc</th>
                <th style="width: 60px;">Versi</th>
                <th style="width: 110px;">Tanggal</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 100px;">Aksi</th>
            </tr>
        </thead>
    </table>
</div>

<style>
    .tb_display {
        width: 100% !important;
    }

    .tb_display th,
    .tb_display td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }

    .tb_display th:nth-child(7),
    .tb_display td:nth-child(7),
    .tb_display th:nth-child(8),
    .tb_display td:nth-child(8),
    .tb_display th:nth-child(9),
    .tb_display td:nth-child(9),
    .tb_display th:nth-child(10),
    .tb_display td:nth-child(10) {
        white-space: nowrap !important;
    }
</style>

<!-- SCRIPT 1: DYNAMIC CARD & LINE ITEMS ENGINE -->
<script>
(function () {
    function initCardEngine() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initCardEngine, 50);
            return;
        }

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

        // ---- Markup builders ----
        function fieldHtml(label, inner, required) {
            return '<div class="tpl-field"><label>' + label +
                   (required ? ' <span class="text-danger">*</span>' : '') +
                   '</label>' + inner + '</div>';
        }

        function inputHtml(name, value, placeholder, required) {
            return '<input type="text" name="' + name + '" class="form-control form-control-sm" value="' +
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
                ? '<input type="hidden" name="weight[]" value="0">'
                : '<div class="col-md-3">' + fieldHtml('Bobot (%)',
                    '<div class="input-group input-group-sm">' +
                        '<input type="number" name="weight[]" class="form-control text-right font-weight-bold" value="' + esc(data.weight || 0) + '" step="0.1" min="0" max="100">' +
                        '<div class="input-group-append"><span class="input-group-text">%</span></div>' +
                    '</div>') + '</div>';

            return '<div class="tpl-card-body">' +
                '<input type="hidden" name="md_appraisal_template_item_id[]" value="' + esc(data.md_appraisal_template_item_id) + '">' +
                '<input type="hidden" name="input_type[]" value="' + esc(type) + '">' +
                '<div class="row">' +
                    '<div class="col-md-4">' + fieldHtml('Kategori',
                        inputHtml('category[]', data.category, isChecklist ? 'Contoh: COMPLIANCE' : 'Contoh: PERFORMA'), true) + '</div>' +
                    '<div class="col-md-' + (isChecklist ? '8' : '5') + '">' + fieldHtml('Nama Kompetensi',
                        inputHtml('competency[]', data.competency, isChecklist ? 'Contoh: Security Audit' : 'Contoh: Job Knowledge'), true) + '</div>' +
                    weightCol +
                    '<div class="col-12">' + fieldHtml('Definisi Kompetensi',
                        '<textarea name="definition[]" class="form-control form-control-sm" rows="2" placeholder="Jelaskan secara singkat deskripsi atau ekspektasi dari kompetensi ini...">' + esc(data.definition) + '</textarea>') + '</div>' +
                '</div>' +
                '<div class="tpl-actions">' +
                    '<div class="tpl-actions-head">' +
                        '<label class="mb-0">Daftar Indikator / Key Actions <span class="tpl-count-actions">0</span></label>' +
                        '<small class="text-muted">Tekan <b>Enter</b> untuk menambah baris</small>' +
                    '</div>' +
                    '<div class="tpl-action-list"></div>' +
                    '<textarea name="key_actions[]" class="d-none tpl-hidden-actions"></textarea>' +
                    '<a href="javascript:void(0)" role="button" class="tpl-add-row" data-tpl="add-row">' +
                        '<i class="fas fa-plus"></i> Tambah Baris Indikator' +
                    '</a>' +
                '</div>' +
            '</div>';
        }

        // ---- State helpers ----
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
                    $c.find('.tpl-sub').text((cat ? '[' + cat + '] · ' : '') + n + ' indikator');
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

        // ---- Indicator rows ----
        window.tplAppendActionRow = function ($container, val, skipRefresh) {
            $container.append(
                '<div class="tpl-action-row">' +
                    '<span class="tpl-row-no" aria-hidden="true"></span>' +
                    '<input type="text" class="form-control form-control-sm tpl-action-input" value="' + esc(val) + '" placeholder="Ketik indikator perilaku kerja, tekan Enter untuk baris baru...">' +
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

        // ---- Cards ----
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
                    actions = data.key_actions.split("\n");
                }
            } else if (Array.isArray(data.key_actions)) {
                actions = data.key_actions;
            }
            if (actions.length === 0) { actions = ['']; }
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
         * Render cards from the unified `sections[].items[]` structure returned by show().
         * Item fields are mapped back to the card model: title -> category,
         * description -> definition, config.competency / config.key_actions.
         */
        window.renderTemplateItems = function (sections) {
            $('#tpl_container_checklist').empty().removeData('sectionId');
            $('#tpl_container_slider').empty().removeData('sectionId');

            if (Array.isArray(sections) && sections.length > 0) {
                var seen = { BOOLEAN: 0, SCORE: 0 };
                var total = { BOOLEAN: 0, SCORE: 0 };
                var typeOf = function (x) { return x === 'BOOLEAN' ? 'BOOLEAN' : 'SCORE'; };

                sections.forEach(function (sec) {
                    (sec.items || []).forEach(function (it) { total[typeOf(it.input_type)]++; });
                });

                sections.forEach(function (sec) {
                    var sType = typeOf(sec.section_type);
                    $(TYPES[sType].box).data('sectionId', sec.md_appraisal_section_id || null);

                    (sec.items || []).forEach(function (it) {
                        var type = typeOf(it.input_type);
                        var cfg = it.config || {};
                        var data = {
                            md_appraisal_template_item_id: it.md_appraisal_template_item_id,
                            category: it.title,
                            competency: cfg.competency || '',
                            definition: it.description || '',
                            key_actions: cfg.key_actions || [],
                            weight: it.weight
                        };
                        var collapsed = total[type] > COLLAPSE_AFTER && seen[type] > 0;
                        seen[type]++;
                        window.tplAddCard(type, data, { collapsed: collapsed, skipRefresh: true });
                    });
                });
            }
            window.tplRefresh();
        };

        // Preload Select2 scope options from show() response ({id, text} lists)
        window.tplRenderScoping = function (scoping) {
            scoping = scoping || {};
            [['#md_levelling_id', 'levelling_ids', 'position_ids'], ['#md_division_id', 'division_ids', null], ['#md_branch_id', 'branch_ids', null]].forEach(function (pair) {
                var $el = $(pair[0]);
                $el.empty();
                var list = scoping[pair[1]] || (pair[2] ? scoping[pair[2]] : []) || [];
                list.forEach(function (o) {
                    $el.append(new Option(o.text, o.id, true, true));
                });
                $el.trigger('change');
            });
        };

        // Validation + payload building live in the second script block below (tplValidate / tplPrepareSave)

        // ---- Delegated card events ----
        $(document).on('click', '[data-tpl="toggle"]', function () {
            window.tplToggleCard($(this).closest('.tpl-card'));
        });

        $(document).on('click', '[data-tpl="del-card"]', function () { window.tplRemoveCard(this); });
        $(document).on('click', '[data-tpl="del-row"]',  function () { window.tplRemoveActionRow(this); });
        $(document).on('click', '[data-tpl="add-row"]',  function () {
            var $list = $(this).closest('.tpl-card').find('.tpl-action-list');
            window.tplAppendActionRow($list, '');
            $list.find('.tpl-action-input').last().focus();
        });

        $(document).on('keydown', '.tpl-action-input', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            if ($.trim($(this).val()) === '') return;
            var $list = $(this).closest('.tpl-action-list');
            window.tplAppendActionRow($list, '');
            $list.find('.tpl-action-input').last().focus();
        });

        $(document).on('input change', '.tpl-card input[name="competency[]"], .tpl-card input[name="category[]"], .tpl-card input[name="weight[]"], .tpl-action-input', function () {
            window.tplSyncActions($(this).closest('.tpl-card'));
            window.tplRefresh();
        });

        $(document).on('submit', '#form_appraisal_template', function () {
            $('.tpl-card').each(function () { window.tplSyncActions($(this)); });
        });

        document.addEventListener('invalid', function (e) {
            var $c = $(e.target).closest('.tpl-card');
            if (!$c.length) return;
            var $pane = $c.closest('.tab-pane');
            if ($pane.length && !$pane.hasClass('active')) {
                $('a[data-toggle="pill"][href="#' + $pane.attr('id') + '"]').tab('show');
            }
            window.tplToggleCard($c, true);
        }, true);

        // Lifecycle: Reset cards when New Form button is clicked
        $(document).on('click', '.new_form', function () {
            window.renderTemplateItems([]);
            window.tplRenderScoping({});
        });

        // Lifecycle: Populate items on Edit response
        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (settings.url && settings.url.indexOf('template-penilaian/show/') !== -1) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res[0] && res[0].success && res[0].message && res[0].message.sections) {
                        window.renderTemplateItems(res[0].message.sections);
                        window.tplRenderScoping(res[0].message.scoping);
                    }
                } catch (e) {
                    console.error('Error loading template items:', e);
                }
            }
        });

        // -------------------------------------------------------------
        // PREVIEW PESERTA (SIMULASI TARGET KARYAWAN)
        // -------------------------------------------------------------
        $(document).on('click', '#btn_preview_peserta', function (e) {
            e.preventDefault();

            var $modal = $('#modal_preview_participants');
            if (!$modal.length) {
                alert('Modal preview peserta tidak ditemukan.');
                return;
            }

            var branchTexts = $('#md_branch_id option:selected').map(function () { return $(this).text(); }).get();
            var divTexts    = $('#md_division_id option:selected').map(function () { return $(this).text(); }).get();
            var lvlTexts    = $('#md_levelling_id option:selected').map(function () { return $(this).text(); }).get();

            $('#preview_scope_branch').text(branchTexts.length ? branchTexts.join(', ') : 'Semua Cabang');
            $('#preview_scope_division').text(divTexts.length ? divTexts.join(', ') : 'Semua Divisi');
            $('#preview_scope_levelling').text(lvlTexts.length ? lvlTexts.join(', ') : 'Semua Level');

            var payload = window.tplCollectData ? window.tplCollectData() : null;
            var scoping = payload && payload.scoping ? payload.scoping : {
                branch_ids:    $('#md_branch_id').val() || [],
                division_ids:  $('#md_division_id').val() || [],
                levelling_ids: $('#md_levelling_id').val() || []
            };

            var $tbody = $('#tb_preview_participants_body');
            $tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data peserta...</td></tr>');
            $('#badge_preview_total_peserta').text('Memuat...');

            $modal.appendTo('body').modal('show');

            $.ajax({
                url: ADMIN_URL + 'template-penilaian/previewParticipants',
                type: 'POST',
                data: {
                    scoping: scoping,
                    md_appraisal_template_id: $('input[name="md_appraisal_template_id"]').val() || 0
                },
                dataType: 'JSON',
                success: function (res) {
                    if (res && res.status && Array.isArray(res.data)) {
                        $('#badge_preview_total_peserta').text('Total: ' + (res.total || res.data.length) + ' Karyawan');
                        if (res.data.length === 0) {
                            $tbody.html('<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada karyawan aktif yang cocok dengan kriteria cakupan.</td></tr>');
                            return;
                        }

                        var rowsHtml = '';
                        res.data.forEach(function (emp) {
                            rowsHtml += '<tr>' +
                                '<td class="text-center text-muted">' + (emp.no || '-') + '</td>' +
                                '<td class="font-weight-bold text-primary">' + (emp.nik || '-') + '</td>' +
                                '<td><div class="font-weight-600">' + (emp.employee_name || '-') + '</div></td>' +
                                '<td>' + (emp.branch_name || '-') + '</td>' +
                                '<td>' + (emp.division_name || '-') + '</td>' +
                                '<td><span class="badge badge-light border">' + (emp.level_name || emp.position_name || '-') + '</span></td>' +
                                '<td><small class="text-muted d-block font-weight-600">' + (emp.evaluator_name || '-') + '</small></td>' +
                                '<td><small class="text-muted d-block">' + (emp.reviewer_name || '-') + '</small></td>' +
                            '</tr>';
                        });
                        $tbody.html(rowsHtml);
                    } else {
                        $tbody.html('<tr><td colspan="8" class="text-center py-4 text-danger">' + (res && res.message ? res.message : 'Gagal memuat peserta.') + '</td></tr>');
                    }
                },
                error: function (xhr, err, msg) {
                    $tbody.html('<tr><td colspan="8" class="text-center py-4 text-danger">Terjadi kesalahan koneksi (' + msg + ').</td></tr>');
                }
            });
        });

        // -------------------------------------------------------------
        // PREVIEW FORM EVALUASI (REUSE FORM APPRAISAL VIEW MODE)
        // -------------------------------------------------------------
        window.openTemplatePreview = function (params) {
            var $modal = $('#modal_preview_form');
            if (!$modal.length) {
                alert('Modal preview form tidak ditemukan.');
                return;
            }

            $('#preview_form_tpl_name').text('Template: ' + (params.title || 'Memuat...'));
            $('#preview_form_loader').show();
            $('#preview_form_content').hide().empty();

            $modal.appendTo('body').modal('show');

            $.ajax({
                url: params.url || (ADMIN_URL + 'template-penilaian/previewForm'),
                type: params.method || 'POST',
                data: params.data || {},
                dataType: 'JSON',
                success: function (res) {
                    $('#preview_form_loader').hide();
                    if (res && res.status && res.html) {
                        $('#preview_form_tpl_name').text('Template: ' + (res.title || params.title || '-'));
                        $('#preview_form_content').html(res.html).show();
                    } else {
                        var errMsg = res && res.message ? res.message : 'Gagal memuat pratinjau form.';
                        $('#preview_form_content').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle mr-2"></i>' + errMsg + '</div>').show();
                    }
                },
                error: function (xhr) {
                    $('#preview_form_loader').hide();
                    var errMsg = 'Terjadi kesalahan sistem saat memuat pratinjau form evaluasi.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#preview_form_content').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-triangle mr-2"></i>' + errMsg + '</div>').show();
                }
            });
        };

        // 1. Triggered from Builder Toolbar button (#btn_preview_form)
        $(document).on('click', '#btn_preview_form', function (e) {
            e.preventDefault();

            var payload = window.tplCollectData ? window.tplCollectData() : null;
            var tplTitle = (payload && payload.title) ? payload.title : ($('#title').val() || 'Draft Template Penilaian');
            var tplId = $('input[name="md_appraisal_template_id"]').val() || 0;

            window.openTemplatePreview({
                url: ADMIN_URL + 'template-penilaian/previewForm',
                method: 'POST',
                title: tplTitle,
                data: {
                    id: tplId,
                    payload: payload ? JSON.stringify(payload) : ''
                }
            });
        });

        // 2. Triggered from Datatable row button (.btn_preview_template)
        $(document).on('click', '.btn_preview_template', function (e) {
            e.preventDefault();
            var tplId = $(this).data('id');
            if (!tplId) return;

            window.openTemplatePreview({
                url: ADMIN_URL + 'template-penilaian/previewForm/' + tplId,
                method: 'GET',
                title: 'ID #' + tplId,
                data: { id: tplId }
            });
        });

        // -------------------------------------------------------------
        // ARCHIVE ACTION HANDLER (POST template-penilaian/archive)
        // -------------------------------------------------------------
        window.tplArchive = function (id) {
            if (!id || id <= 0) return;
            Swal.fire({
                title: 'Arsipkan Template?',
                text: 'Template yang diarsipkan tidak akan muncul pada pilihan siklus penilaian baru.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e05260',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Arsipkan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) {
                    $.ajax({
                        url: ADMIN_URL + 'template-penilaian/archive/' + id,
                        type: 'POST',
                        dataType: 'JSON',
                        success: function (res) {
                            if (res && (res[0] && res[0].success || res.status)) {
                                Toast.fire({ type: 'success', title: 'Template berhasil diarsipkan' });
                                if (typeof reloadTable === 'function') reloadTable();
                            } else {
                                Swal.fire('Error', (res && res[0] && res[0].message) || res.message || 'Gagal mengarsipkan template.', 'error');
                            }
                        },
                        error: function (xhr, err, msg) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem (' + msg + ').', 'error');
                        }
                    });
                }
            });
        };

        window.tplRefresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCardEngine);
    } else {
        initCardEngine();
    }
})();
</script>
<?= $this->endSection() ?>