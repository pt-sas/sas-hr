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

<!-- SCRIPT: OVERVIEW ACTIONS & MODAL PREVIEWS -->
<script>
(function () {
    function initTemplateActions() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initTemplateActions, 50);
            return;
        }

        // Move preview modals out of container so Process.js does not mistake them for the main edit modal
        $('#modal_preview_participants, #modal_preview_form').appendTo('body');

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
        // PUBLISH ACTION HANDLER (POST template-penilaian/publish)
        // -------------------------------------------------------------
        window.tplPublish = function (id) {
            if (!id || id <= 0) return;
            Swal.fire({
                title: 'Publish & Kunci Template?',
                text: 'Setelah dipublish, template akan aktif untuk siklus penilaian dan tidak dapat diedit secara langsung (perubahan memerlukan pembuatan versi baru).',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#31ce36',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-circle mr-1"></i> Ya, Publish Sekarang',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: 'Memproses Publish...',
                        text: 'Mengunci template dan memperbarui versi.',
                        allowOutsideClick: false,
                        onBeforeOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: ADMIN_URL + 'template-penilaian/publish/' + id,
                        type: 'POST',
                        dataType: 'JSON',
                        success: function (res) {
                            Swal.close();
                            if (res && ((res[0] && res[0].success) || res.status)) {
                                Toast.fire({ type: 'success', title: (res[0] && res[0].message) || res.message || 'Template berhasil dipublish' });
                                if (typeof reloadTable === 'function') reloadTable();
                            } else {
                                Swal.fire('Gagal Publish', (res && res[0] && res[0].message) || res.message || 'Gagal mempublish template.', 'error');
                            }
                        },
                        error: function (xhr, err, msg) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem (' + msg + ').', 'error');
                        }
                    });
                }
            });
        };

        // -------------------------------------------------------------
        // UNPUBLISH ACTION HANDLER (POST template-penilaian/unpublish)
        // -------------------------------------------------------------
        window.tplUnpublish = function (id) {
            if (!id || id <= 0) return;
            Swal.fire({
                title: 'Batalkan Publish Template?',
                text: 'Template akan dikembalikan ke status DRAFT sehingga dapat diedit kembali. Tindakan ini hanya dapat dilakukan jika belum ada penilaian karyawan yang menggunakan template ini.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffad46',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-undo mr-1"></i> Ya, Kembalikan ke Draft',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mengembalikan template ke status DRAFT.',
                        allowOutsideClick: false,
                        onBeforeOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: ADMIN_URL + 'template-penilaian/unpublish/' + id,
                        type: 'POST',
                        dataType: 'JSON',
                        success: function (res) {
                            Swal.close();
                            if (res && ((res[0] && res[0].success) || res.status)) {
                                Toast.fire({ type: 'success', title: (res[0] && res[0].message) || res.message || 'Publish template berhasil dibatalkan.' });
                                if (typeof reloadTable === 'function') reloadTable();
                            } else {
                                Swal.fire('Gagal Batal Publish', (res && res[0] && res[0].message) || res.message || 'Gagal membatalkan publish template.', 'error');
                            }
                        },
                        error: function (xhr, err, msg) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem (' + msg + ').', 'error');
                        }
                    });
                }
            });
        };

        // -------------------------------------------------------------
        // CLONE / NEW VERSION ACTION HANDLER (POST template-penilaian/clone)
        // -------------------------------------------------------------
        window.tplClone = function (id) {
            if (!id || id <= 0) return;
            Swal.fire({
                title: 'Buat Versi Baru Template?',
                text: 'Sistem akan menduplikasi struktur template ini menjadi DRAFT versi baru untuk dapat dimodifikasi.',
                type: 'info',
                showCancelButton: true,
                confirmButtonColor: '#ffad46',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-code-branch mr-1"></i> Ya, Buat Versi Baru',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: 'Menduplikasi Template...',
                        text: 'Menyiapkan draft versi baru.',
                        allowOutsideClick: false,
                        onBeforeOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: ADMIN_URL + 'template-penilaian/clone/' + id,
                        type: 'POST',
                        dataType: 'JSON',
                        success: function (res) {
                            Swal.close();
                            var ok = res && (res.status || (res[0] && res[0].success));
                            if (ok) {
                                var newTpl = res.data || (res[0] && res[0].data);
                                var newId = newTpl ? (newTpl.md_appraisal_template_id || newTpl.id) : null;
                                var ver = newTpl ? (newTpl.version || '') : '';
                                Toast.fire({
                                    type: 'success',
                                    title: 'Versi baru (v' + ver + ') berhasil dibuat sebagai DRAFT'
                                });
                                if (typeof reloadTable === 'function') reloadTable();

                                // Automatically open the newly created draft version in the editor!
                                if (newId && typeof Edit === 'function') {
                                    setTimeout(function () {
                                        Edit(newId);
                                    }, 400);
                                }
                            } else {
                                Swal.fire('Gagal Duplikasi', (res && res[0] && res[0].message) || res.message || 'Gagal membuat versi baru.', 'error');
                            }
                        },
                        error: function (xhr, err, msg) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem (' + msg + ').', 'error');
                        }
                    });
                }
            });
        };

        // -------------------------------------------------------------
        // ARCHIVE / DEACTIVATE ACTION HANDLER (POST template-penilaian/archive)
        // -------------------------------------------------------------
        window.tplArchive = function (id) {
            if (!id || id <= 0) return;
            Swal.fire({
                title: 'Nonaktifkan Template?',
                text: 'Template yang dinonaktifkan (diarsipkan) tidak akan dapat dipilih pada siklus penilaian baru, namun riwayat penilaian yang sudah ada tetap tersimpan.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#d33',
                confirmButtonText: '<i class="fas fa-power-off mr-1"></i> Ya, Nonaktifkan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Menonaktifkan template appraisal.',
                        allowOutsideClick: false,
                        onBeforeOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: ADMIN_URL + 'template-penilaian/archive/' + id,
                        type: 'POST',
                        dataType: 'JSON',
                        success: function (res) {
                            Swal.close();
                            if (res && ((res[0] && res[0].success) || res.status)) {
                                Toast.fire({
                                    type: 'success',
                                    title: (res[0] && res[0].message) || res.message || 'Template berhasil dinonaktifkan'
                                });
                                if (typeof reloadTable === 'function') reloadTable();
                            } else {
                                Swal.fire('Gagal', (res && res[0] && res[0].message) || res.message || 'Gagal menonaktifkan template.', 'error');
                            }
                        },
                        error: function (xhr, err, msg) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem (' + msg + ').', 'error');
                        }
                    });
                }
            });
        };

        // -------------------------------------------------------------
        // ACTIVATE / REACTIVATE ACTION HANDLER (POST template-penilaian/activate)
        // -------------------------------------------------------------
        window.tplActivate = function (id) {
            if (!id || id <= 0) return;
            Swal.fire({
                title: 'Aktifkan Kembali Template?',
                text: 'Template akan diubah kembali ke status PUBLISHED (aktif) dan dapat dipilih pada siklus penilaian kerja.',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-circle mr-1"></i> Ya, Aktifkan',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.value) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mengaktifkan template appraisal.',
                        allowOutsideClick: false,
                        onBeforeOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: ADMIN_URL + 'template-penilaian/activate/' + id,
                        type: 'POST',
                        dataType: 'JSON',
                        success: function (res) {
                            Swal.close();
                            if (res && ((res[0] && res[0].success) || res.status)) {
                                Toast.fire({
                                    type: 'success',
                                    title: (res[0] && res[0].message) || res.message || 'Template berhasil diaktifkan kembali'
                                });
                                if (typeof reloadTable === 'function') reloadTable();
                            } else {
                                Swal.fire('Gagal', (res && res[0] && res[0].message) || res.message || 'Gagal mengaktifkan template.', 'error');
                            }
                        },
                        error: function (xhr, err, msg) {
                            Swal.fire('Error', 'Terjadi kesalahan sistem (' + msg + ').', 'error');
                        }
                    });
                }
            });
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTemplateActions);
    } else {
        initTemplateActions();
    }
})();
</script>
<?= $this->endSection() ?>
