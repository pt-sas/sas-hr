<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>

<!-- Filter Section -->
<!-- Form Section -->
<?= $this->include('masterdata/appraisal-cycle/form_appraisal_cycle'); ?>

<!-- Modals -->
<?= $this->include('masterdata/appraisal-cycle/modal_preview_blast'); ?>
<?= $this->include('masterdata/appraisal-cycle/modal_cycle_progress'); ?>

<!-- Main Datatables Overview Section -->
<div class="card-body card-main">
    <div class="table-responsive">
        <table class="table table-striped table-hover tb_display" style="width: 100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th style="width: 45px;" class="text-center">No</th>
                    <th>Nama Siklus Penilaian</th>
                    <th style="width: 90px;" class="text-center">Semester</th>
                    <th style="width: 170px;">Periode</th>
                    <th style="width: 105px;" class="text-center">Pengisian</th>
                    <th style="width: 105px;" class="text-center">Peninjauan</th>
                    <th style="width: 105px;" class="text-center">Pembacaan</th>
                    <th style="width: 105px;" class="text-center">Intervensi HR</th>
                    <th style="width: 90px;" class="text-center">Status</th>
                    <th style="width: 130px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated via Server-Side DataTables -->
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var dtBlast = null;
    var dtProgDiv = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // =========================================================================
    // 1. OPEN CYCLE (Buka Siklus & Batch Generate)
    // =========================================================================
    window.cycOpen = function (id) {
        if (!id || id <= 0) return;

        Swal.fire({
            title: 'Buka Siklus Penilaian?',
            text: 'Membuka siklus akan secara otomatis mendistribusikan form penilaian kepada seluruh karyawan aktif sesuai matriks template.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#31ce36',
            cancelButtonColor: '#d33',
            confirmButtonText: '<i class="fas fa-play mr-1"></i> Ya, Buka & Generate!',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.value) {
                Swal.fire({
                    title: 'Memproses Distribusi Form...',
                    text: 'Sedang membekukan snapshot template dan membuat form penilaian karyawan.',
                    allowOutsideClick: false,
                    onBeforeOpen: function () {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: CURRENT_URL + '/open/' + id,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    success: function (res) {
                        if (res && res[0] && res[0].success) {
                            Swal.fire({
                                title: 'Siklus Berhasil Dibuka!',
                                text: res[0].message || 'Form penilaian berhasil didistribusikan.',
                                type: 'success'
                            });
                            if (typeof reloadTable === 'function') reloadTable();
                        } else {
                            var errMsg = (res && res[0] && res[0].message) ? res[0].message : 'Gagal membuka siklus.';
                            Swal.fire('Gagal Membuka Siklus', errMsg, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        var msg = (xhr.responseJSON && xhr.responseJSON[0] && xhr.responseJSON[0].message)
                            ? xhr.responseJSON[0].message
                            : 'Terjadi kesalahan sistem (' + error + ').';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    };

    // =========================================================================
    // 2. CLOSE CYCLE (Tutup Siklus)
    // =========================================================================
    window.cycClose = function (id) {
        if (!id || id <= 0) return;

        Swal.fire({
            title: 'Tutup Siklus Penilaian?',
            text: 'Setelah siklus ditutup, form penilaian pada siklus ini tidak dapat diubah kembali.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f25961',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-lock mr-1"></i> Ya, Tutup Siklus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.value) {
                $.ajax({
                    url: CURRENT_URL + '/close/' + id,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    success: function (res) {
                        if (res && res[0] && res[0].success) {
                            Toast.fire({
                                type: 'success',
                                title: res[0].message || 'Siklus penilaian berhasil ditutup.'
                            });
                            if (typeof reloadTable === 'function') reloadTable();
                        } else {
                            var errMsg = (res && res[0] && res[0].message) ? res[0].message : 'Gagal menutup siklus.';
                            Swal.fire('Gagal', errMsg, 'error');
                        }
                    },
                    error: function (xhr, status, error) {
                        var msg = (xhr.responseJSON && xhr.responseJSON[0] && xhr.responseJSON[0].message)
                            ? xhr.responseJSON[0].message
                            : 'Terjadi kesalahan sistem (' + error + ').';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    };

    // =========================================================================
    // 3. PREVIEW BLAST (Simulasi Distribusi Karyawan & Template)
    // =========================================================================
    window.cycPreviewBlast = function (id) {
        var url = CURRENT_URL + '/preview-blast' + (id ? '/' + id : '');

        Swal.fire({
            title: 'Menyiapkan Simulasi Blast...',
            text: 'Memverifikasi kecocokan master template dan hierarki karyawan.',
            allowOutsideClick: false,
            onBeforeOpen: function () {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'JSON',
            success: function (res) {
                Swal.close();

                if (!res || !res[0] || !res[0].success) {
                    var errMsg = (res && res[0] && res[0].message) ? res[0].message : 'Gagal memuat data simulasi.';
                    Swal.fire('Gagal', errMsg, 'error');
                    return;
                }

                var payload = res[0].data || res[0].message || {};
                var cycle   = payload.cycle || null;
                var summary = payload.summary || {};
                var rows    = payload.rows || [];

                // Header Cycle Info
                if (cycle && cycle.title) {
                    $('#blast_cycle_info').html('&mdash; Siklus: <span class="font-weight-600 text-dark">' + esc(cycle.title) + '</span>');
                } else {
                    $('#blast_cycle_info').html('&mdash; <span class="text-muted">Simulasi Global</span>');
                }

                // Update KPI Cards
                $('#blast_kpi_total').text(summary.total || 0);
                $('#blast_kpi_ready').text(summary.ready || 0);
                $('#blast_kpi_missing_tpl').text(summary.missing_template || 0);
                $('#blast_kpi_missing_eval').text(summary.missing_evaluator || 0);

                var $incompleteBadge = $('#blast_kpi_incomplete_badge');
                if ((summary.incomplete || 0) > 0) {
                    $incompleteBadge.text('+' + summary.incomplete + ' belum lengkap').removeClass('d-none');
                } else {
                    $incompleteBadge.addClass('d-none');
                }

                // Alert Banner Status
                var $alert = $('#blast_alert_status');
                var $alertText = $('#blast_alert_text');
                if (summary.missing_template > 0 || summary.missing_evaluator > 0 || summary.incomplete > 0) {
                    $alert.removeClass('alert-info alert-success').addClass('alert-warning');
                    $alertText.html('<b>Perhatian:</b> Terdapat <b>' + (summary.missing_template || 0) + '</b> karyawan tanpa template, <b>' + (summary.missing_evaluator || 0) + '</b> tanpa atasan langsung, dan <b>' + (summary.incomplete || 0) + '</b> belum lengkap. Karyawan yang belum siap akan dilewati saat generate form.');
                } else if (summary.draft_template > 0) {
                    $alert.removeClass('alert-info alert-success').addClass('alert-warning');
                    $alertText.html('<b>Simulasi Template DRAFT:</b> Ditemukan <b>' + (summary.draft_template || 0) + '</b> karyawan cocok dengan template berstatus DRAFT. Terbitkan (publish) template tersebut terlebih dahulu agar form dapat dibuat saat siklus dibuka.');
                } else {
                    $alert.removeClass('alert-info alert-warning').addClass('alert-success');
                    $alertText.html('<b>Semua Karyawan Siap:</b> 100% karyawan aktif telah terhubung dengan template yang tepat dan atasan langsung.');
                }

                // Action Button "Buka Siklus Sekarang"
                var $btnOpen = $('#btn_blast_open_cycle');
                if (cycle && cycle.id && cycle.status === 'DRAFT' && summary.can_blast) {
                    $btnOpen.show().off('click').on('click', function () {
                        $('#modal_preview_blast').modal('hide');
                        window.cycOpen(cycle.id);
                    });
                } else {
                    $btnOpen.hide();
                }

                // Open Modal & Initialize/Populate Datatable
                var $modal = $('#modal_preview_blast');
                $modal.appendTo('body').modal('show');

                if ($('#filter_preview_blast').length) {
                    $('#filter_preview_blast')[0].reset();
                }

                // Populate dynamic options for Branch, Division, Level, Position
                var branches  = {};
                var divisions = {};
                var levels    = {};
                var positions = {};

                rows.forEach(function (r) {
                    if (r.branch) {
                        r.branch.split(',').forEach(function (b) {
                            var trimmed = b.trim();
                            if (trimmed && trimmed !== '-') branches[trimmed] = true;
                        });
                    }
                    if (r.division) {
                        r.division.split(',').forEach(function (d) {
                            var trimmed = d.trim();
                            if (trimmed && trimmed !== '-') divisions[trimmed] = true;
                        });
                    }
                    if (r.level) {
                        r.level.split(',').forEach(function (l) {
                            var trimmed = l.trim();
                            if (trimmed && trimmed !== '-') levels[trimmed] = true;
                        });
                    }
                    if (r.position) {
                        var trimmedPos = String(r.position).trim();
                        if (trimmedPos && trimmedPos !== '-') positions[trimmedPos] = true;
                    }
                });

                var populateSelect = function ($sel, items, defaultLabel) {
                    var currentVal = $sel.val();
                    $sel.empty().append('<option value="">' + defaultLabel + '</option>');
                    Object.keys(items).sort().forEach(function (item) {
                        $sel.append('<option value="' + esc(item) + '">' + esc(item) + '</option>');
                    });
                    if (currentVal && items[currentVal]) {
                        $sel.val(currentVal);
                    }
                };

                populateSelect($('#blast_filter_branch'), branches, 'Semua Cabang');
                populateSelect($('#blast_filter_division'), divisions, 'Semua Divisi');
                populateSelect($('#blast_filter_level'), levels, 'Semua Level');
                populateSelect($('#blast_filter_position'), positions, 'Semua Posisi');

                // Initialize Select2 on blast filters if plugin is present
                if ($.fn.select2) {
                    $('.select2-blast').each(function () {
                        if ($(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({
                            theme: 'bootstrap',
                            dropdownParent: $modal
                        });
                    });
                }

                // Register custom DataTables search filter for blast table
                if (!window._dtBlastFilterRegistered) {
                    $.fn.dataTable.ext.search.push(function (settings, searchData, index, rowData, counter) {
                        if (settings.nTable.id !== 'tb_preview_blast') {
                            return true;
                        }

                        var fReadiness = $('#blast_filter_readiness').val();
                        var fBranch    = $('#blast_filter_branch').val();
                        var fDivision  = $('#blast_filter_division').val();
                        var fLevel     = $('#blast_filter_level').val();
                        var fPosition  = $('#blast_filter_position').val();

                        if (fReadiness && rowData.readiness_status !== fReadiness) {
                            return false;
                        }
                        if (fBranch) {
                            var rowBranches = (rowData.branch || '').split(',').map(function (s) { return s.trim(); });
                            if (rowBranches.indexOf(fBranch) === -1) {
                                return false;
                            }
                        }
                        if (fDivision) {
                            var rowDivisions = (rowData.division || '').split(',').map(function (s) { return s.trim(); });
                            if (rowDivisions.indexOf(fDivision) === -1) {
                                return false;
                            }
                        }
                        if (fLevel) {
                            var rowLevels = (rowData.level || '').split(',').map(function (s) { return s.trim(); });
                            if (rowLevels.indexOf(fLevel) === -1) {
                                return false;
                            }
                        }
                        if (fPosition) {
                            var rowPosition = (rowData.position || '').trim();
                            if (rowPosition !== fPosition) {
                                return false;
                            }
                        }

                        return true;
                    });
                    window._dtBlastFilterRegistered = true;
                }

                // Bind Filter and Reset Button Actions
                $('.btn_filter_blast').off('click').on('click', function () {
                    if (dtBlast) {
                        dtBlast.draw();
                    }
                });

                $('#btn_reset_filter_blast, .btn_reset_blast').off('click').on('click', function () {
                    $('#filter_preview_blast')[0].reset();
                    $('.select2-blast').val('').trigger('change');
                    if (dtBlast) {
                        dtBlast.draw();
                    }
                });

                // Clicking KPI ribbon cards or sub-badges acts as instant quick-filter
                $('.kpi-sub-filter').off('click').on('click', function (e) {
                    e.stopPropagation();
                    var status = $(this).attr('data-kpi-filter') || '';
                    $('#blast_filter_readiness').val(status).trigger('change');
                    if (dtBlast) {
                        dtBlast.draw();
                    }
                });

                $('.kpi-card-clickable').off('click').on('click', function () {
                    var status = $(this).attr('data-kpi-filter') || '';
                    $('#blast_filter_readiness').val(status).trigger('change');
                    if (dtBlast) {
                        dtBlast.draw();
                    }
                });

                $modal.off('shown.bs.modal').on('shown.bs.modal', function () {
                    var $table = $('#tb_preview_blast');

                    if ($.fn.DataTable.isDataTable($table)) {
                        $table.DataTable().destroy();
                        $table.find('tbody').empty();
                    }

                    dtBlast = $table.DataTable({
                        processing: true,
                        searching: true,
                        lengthChange: true,
                        pageLength: 10,
                        scrollY: '52vh',
                        scrollCollapse: true,
                        autoWidth: false,
                        data: rows,
                        columns: [
                            {
                                data: null,
                                className: 'text-center align-middle',
                                orderable: false,
                                render: function (d, t, r, meta) {
                                    return meta.row + 1;
                                }
                            },
                            {
                                data: 'nik',
                                className: 'align-middle font-weight-bold text-dark',
                                render: function (d) {
                                    return esc(d);
                                }
                            },
                            {
                                data: 'name',
                                className: 'align-middle font-weight-bold text-dark',
                                render: function (d) {
                                    return esc(d ? String(d).toUpperCase() : '');
                                }
                            },
                            {
                                data: 'position',
                                className: 'align-middle',
                                render: function (d) {
                                    return esc(d || '-');
                                }
                            },
                            {
                                data: 'branch',
                                className: 'align-middle',
                                render: function (d) { return esc(d); }
                            },
                            {
                                data: 'division',
                                className: 'align-middle',
                                render: function (d) { return esc(d); }
                            },
                            {
                                data: 'level',
                                className: 'align-middle',
                                render: function (d) {
                                    return esc(d);
                                }
                            },
                            {
                                data: 'template_name',
                                className: 'align-middle',
                                render: function (d, t, r) {
                                    if (!d || d === 'Belum ditugaskan' || d === '-' || !r.template_id || r.template_id <= 0) {
                                        return '<span class="text-danger font-weight-bold">Belum ditugaskan</span>';
                                    }
                                    // Extract template code e.g. from "[TPL-CODE] Title" -> "TPL-CODE"
                                    var match = String(d).match(/^\[([^\]]+)\]/);
                                    var code = match ? match[1] : d;
                                    return '<span class="font-weight-600 text-dark">' + esc(code) + '</span>';
                                }
                            },
                            {
                                data: 'evaluator_name',
                                className: 'align-middle',
                                render: function (d, t, r) {
                                    if (r.evaluator_id > 0) {
                                        return '<span class="text-dark font-weight-500">' + esc(d ? String(d).toUpperCase() : '') + '</span>';
                                    }
                                    return '<span class="text-danger font-weight-bold" style="font-size:11.5px;">Belum Terdata</span>';
                                }
                            },
                            {
                                data: 'reviewer_name',
                                className: 'align-middle',
                                render: function (d, t, r) {
                                    return r.reviewer_id > 0 ? '<span class="text-muted font-weight-500">' + esc(d ? String(d).toUpperCase() : '') + '</span>' : '<span class="text-muted">-</span>';
                                }
                            },
                            {
                                data: 'readiness_status',
                                className: 'text-center align-middle',
                                render: function (d, t, r) {
                                    var note = r.status_note ? ' data-toggle="tooltip" data-placement="left" title="' + esc(r.status_note) + '"' : '';
                                    if (d === 'READY') {
                                        return '<span class="badge badge-success px-2 py-1 font-weight-bold"' + note + '><i class="fas fa-check-circle mr-1"></i>Siap Rilis</span>';
                                    } else if (d === 'TEMPLATE_DRAFT') {
                                        return '<span class="badge badge-secondary px-2 py-1 font-weight-bold"' + note + '><i class="fas fa-edit mr-1"></i>Template Draft</span>';
                                    } else if (d === 'ALREADY_GENERATED') {
                                        return '<span class="badge badge-info px-2 py-1 font-weight-bold"' + note + '><i class="fas fa-file-alt mr-1"></i>Form Aktif</span>';
                                    } else if (d === 'MISSING_TEMPLATE') {
                                        return '<span class="badge badge-warning px-2 py-1 font-weight-bold text-dark"' + note + '><i class="fas fa-exclamation-triangle mr-1"></i>Tanpa Template</span>';
                                    } else if (d === 'INCOMPLETE') {
                                        return '<span class="badge badge-danger px-2 py-1 font-weight-bold"' + note + '><i class="fas fa-times-circle mr-1"></i>Belum Lengkap</span>';
                                    } else {
                                        return '<span class="badge badge-danger px-2 py-1 font-weight-bold"' + note + '><i class="fas fa-user-slash mr-1"></i>Tanpa Atasan</span>';
                                    }
                                }
                            }
                        ],
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "Cari Karyawan / Template...",
                            zeroRecords: "Tidak ada data karyawan yang cocok",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ karyawan",
                            infoEmpty: "0 karyawan",
                            paginate: {
                                previous: '<i class="fas fa-chevron-left"></i>',
                                next: '<i class="fas fa-chevron-right"></i>'
                            }
                        }
                    });
                });
            },
            error: function (xhr, status, error) {
                Swal.close();
                var msg = (xhr.responseJSON && xhr.responseJSON[0] && xhr.responseJSON[0].message)
                    ? xhr.responseJSON[0].message
                    : 'Gagal memuat simulasi (' + error + ').';
                Swal.fire('Error', msg, 'error');
            }
        });
    };

    // =========================================================================
    // 4. PROGRESS MONITORING MODAL
    // =========================================================================
    window.cycProgress = function (id) {
        if (!id || id <= 0) return;

        Swal.fire({
            title: 'Memuat Progres Evaluasi...',
            text: 'Mengumpulkan metrik penilaian dari database.',
            allowOutsideClick: false,
            onBeforeOpen: function () {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: CURRENT_URL + '/progress/' + id,
            type: 'GET',
            dataType: 'JSON',
            success: function (res) {
                Swal.close();

                if (!res || !res[0] || !res[0].success) {
                    var errMsg = (res && res[0] && res[0].message) ? res[0].message : 'Gagal memuat data progres.';
                    Swal.fire('Gagal', errMsg, 'error');
                    return;
                }

                var payload   = res[0].data || res[0].message || {};
                var cycle     = payload.cycle || {};
                var summary   = payload.summary || {};
                var divisions = payload.divisions || [];

                // Header Details
                $('#prog_cycle_title').text(cycle.title || '-');
                var deadlineText = 'Periode: ' + (cycle.start_date || '-') + ' s/d ' + (cycle.end_date || '-');
                var dlFill = cycle.deadline_filling;
                var dlRev  = cycle.deadline_review;
                var dlRead = cycle.deadline_reading;
                var dlOver = cycle.deadline_override;
                if (dlFill && dlFill !== '-') {
                    deadlineText += ' | Due Filling: ' + dlFill;
                }
                if (dlRev && dlRev !== '-') {
                    deadlineText += ' | Due Review: ' + dlRev;
                }
                if (dlRead && dlRead !== '-') {
                    deadlineText += ' | Due Reading: ' + dlRead;
                }
                if (dlOver && dlOver !== '-') {
                    deadlineText += ' | Due Override: ' + dlOver;
                }
                $('#prog_cycle_deadlines').text(deadlineText);

                // Overall Progress Bar
                var pct = summary.completion_percent || 0.0;
                $('#prog_overall_percent_text').text(pct.toFixed(1) + '%');
                $('#prog_overall_percent_bar')
                    .css('width', pct + '%')
                    .attr('aria-valuenow', pct)
                    .text(pct.toFixed(1) + '%');

                // KPI Metric Cards
                $('#prog_kpi_total').text(summary.total || 0);
                $('#prog_kpi_draft').text(summary.draft_evaluator || 0);
                var reviewReviewerTotal = (summary.submitted_evaluator || 0) + (summary.review_reviewer || 0) + (summary.pending_ack || 0);
                $('#prog_kpi_review').text(reviewReviewerTotal);
                $('#prog_kpi_finalized').text(summary.finalized || 0);

                // Open Modal & Populate Division Breakdown Datatable
                var $modal = $('#modal_cycle_progress');
                $modal.appendTo('body').modal('show');

                $modal.off('shown.bs.modal').on('shown.bs.modal', function () {
                    var $table = $('#tb_progress_divisions');

                    if ($.fn.DataTable.isDataTable($table)) {
                        $table.DataTable().destroy();
                        $table.find('tbody').empty();
                    }

                    dtProgDiv = $table.DataTable({
                        processing: true,
                        searching: false,
                        lengthChange: false,
                        pageLength: 8,
                        data: divisions,
                        columns: [
                            {
                                data: null,
                                className: 'text-center align-middle',
                                orderable: false,
                                render: function (d, t, r, meta) {
                                    return meta.row + 1;
                                }
                            },
                            {
                                data: 'division_name',
                                className: 'font-weight-bold text-dark align-middle',
                                render: function (d) { return esc(d); }
                            },
                            {
                                data: 'total',
                                className: 'text-center align-middle font-weight-bold',
                                render: function (d) { return d || 0; }
                            },
                            {
                                data: 'in_progress',
                                className: 'text-center align-middle text-warning font-weight-bold',
                                render: function (d) { return d || 0; }
                            },
                            {
                                data: 'finalized',
                                className: 'text-center align-middle text-success font-weight-bold',
                                render: function (d) { return d || 0; }
                            },
                            {
                                data: 'percent',
                                className: 'align-middle',
                                render: function (d) {
                                    var val = parseFloat(d) || 0;
                                    return '<div class="d-flex align-items-center" style="gap: 8px;">' +
                                        '<div class="progress flex-grow-1" style="height: 8px;">' +
                                            '<div class="progress-bar bg-success" style="width:' + val + '%;"></div>' +
                                        '</div>' +
                                        '<small class="font-weight-bold" style="width: 40px; text-align: right;">' + val.toFixed(1) + '%</small>' +
                                    '</div>';
                                }
                            }
                        ],
                        language: {
                            zeroRecords: "Belum ada evaluasi yang didistribusikan pada siklus ini",
                            info: "Menampilkan _START_ - _END_ dari _TOTAL_ divisi",
                            infoEmpty: "0 divisi"
                        }
                    });
                });
            },
            error: function (xhr, status, error) {
                Swal.close();
                var msg = (xhr.responseJSON && xhr.responseJSON[0] && xhr.responseJSON[0].message)
                    ? xhr.responseJSON[0].message
                    : 'Gagal memuat progres (' + error + ').';
                Swal.fire('Error', msg, 'error');
            }
        });
    };
})();
</script>

<?= $this->endSection() ?>
