/**
 * Master Paket: lapisan UI di atas process.js
 * Memakai global dari process.js: _table, _tableLine, setSave, formatRupiah, replaceRupiah
 */
$(function () {
  const $form = $('#form_master_bundling');
  if (!$form.length) return;

  const PER = { day: 'hari', month: 'bulan' };
  const UNIT = { fix_per_nilai: 'per orang', fix_per_divisi: 'per divisi' };
  const g = (name) => $form.find('[name="' + name + '"]');
  const isDetail = () => typeof setSave !== 'undefined' && setSave === 'detail';

  // ── Pratinjau ────────────────────────────────────────────────────────
  function refreshPreview() {
    const period = g('period_type').filter(':checked').val() || 'day';
    const comp = g('compensation_type').filter(':checked').val() || 'fix_per_nilai';
    const min = parseInt(g('minimum_hour').val(), 10) || 0;
    const nominal = parseInt(replaceRupiah(g('project_value').val() || '0'), 10) || 0;
    const ruleOn = $('#divisionrule').is(':checked');
    const ruleCount = _tableLine.rows().count();

    $('#lbl_nominal').html((comp === 'fix_per_divisi' ? 'Nominal total divisi' : 'Nominal per orang') + ' <span class="required">*</span>');
    $('#pv_code').text(g('value').val() || 'Kode otomatis');
    $('#pv_name').text($.trim(g('name').val()) || 'Nama paket belum diisi');
    $('#pv_period').text(period === 'day' ? 'Harian' : 'Bulanan');
    $('#pv_amount').text('Rp ' + (formatRupiah(String(nominal)) || '0'));
    $('#pv_unit').text(UNIT[comp] + ' tiap ' + PER[period]);
    $('#pv_min').text(min > 0 ? min + ' jam' : 'Tanpa minimum');
    $('#pv_prorate').text($('#isprorate').is(':checked') ? 'Ya' : 'Tidak');
    $('#pv_rules').text(ruleOn ? ruleCount + ' divisi dibatasi' : 'Tidak dibatasi');
  }

  $form.on('input change', refreshPreview);
  _tableLine.on('draw.dt', refreshPreview);

  // ── Aturan per divisi ────────────────────────────────────────────────
  function ruleRow(r) {
    r = r || {};
    const locked = isDetail();
    const pk = r.id || '';

    const $sel = $('<select name="md_division_id" class="form-control select2 line"></select>')
      .html($('#mp_division_opts').html());
    if (r.md_division_id) $sel.find('option[value="' + r.md_division_id + '"]').attr('selected', 'selected');
    if (locked) $sel.attr('disabled', 'disabled');

    const $qty = $('<input type="text" name="maximum_submission" class="form-control number line" maxlength="3" inputmode="numeric">')
      .attr('value', r.maximum_submission || '0');
    if (locked) $qty.attr('readonly', 'readonly');

    // reference-key: nilainya dibaca process.js sebagai primary key baris
    const $pk = $('<input type="hidden" name="trx_bundling_division_rule_id" class="reference-key line">').attr('value', pk);

    // .btn_delete ditangani process.js: id kosong = hapus langsung, id terisi = minta konfirmasi
    const del = '<button type="button" class="btn btn-icon btn-link btn-danger btn-sm btn_delete" id="' + pk +
      '" title="Hapus baris"' + (locked ? ' style="display:none"' : '') + '><i class="fa fa-times"></i></button>';

    return [$sel.prop('outerHTML') + $pk.prop('outerHTML'), $qty.prop('outerHTML'), del];
  }

  function toggleRules() {
    const on = $('#divisionrule').is(':checked');
    $('#mp_rules').toggleClass('d-none', !on);
    if (on) setTimeout(function () { _tableLine.columns.adjust(); }, 0);
    refreshPreview();
  }

  $(document).on('change', '#divisionrule', toggleRules);

  $(document).on('click', '.btn_add_rule', function () {
    _tableLine.row.add(ruleRow()).draw(false);
    _tableLine.page('last').draw(false);
  });

  // ── Form baru ────────────────────────────────────────────────────────
  $(document).on('click', '.new_form', function () {
    // new_form memakai setTimeout 200ms; tunggu sampai selesai
    setTimeout(function () {
      _tableLine.clear().draw(false);
      $('#mp_rules').addClass('d-none');
      $('.btn_add_rule').show();
      refreshPreview();
    }, 400);
  });

  // ── Form edit/detail: isi baris aturan setelah show/{id} selesai ─────
  $(document).ajaxComplete(function (e, xhr, settings) {
    if (!settings || !settings.url) return;

    if (/\/show\/\d+/.test(settings.url)) {
      const res = xhr.responseJSON;
      const msg = res && res[0] && res[0].message;
      if (!msg || typeof msg !== 'object') return;

      _tableLine.clear();
      (msg.rules || []).forEach(function (r) { _tableLine.row.add(ruleRow(r)); });
      _tableLine.draw(false);

      $('.btn_add_rule').toggle(!isDetail());
      toggleRules();
    }

    if (/getSeqCode/.test(settings.url)) setTimeout(refreshPreview, 0);
  });

  // ── Tab status & filter periode ──────────────────────────────────────
  // $(document).on('click', '.mp-tab', function () {
  //   $('.mp-tab').removeClass('is-active').attr('aria-pressed', 'false');
  //   $(this).addClass('is-active').attr('aria-pressed', 'true');
  //   $('#form_filter_paket [name=docstatus]').val($(this).data('status'));
  //   _table.ajax.reload(); // kembali ke halaman 1
  // });

  // $(document).on('change', '#form_filter_paket select', function () {
  //   _table.ajax.reload();
  // });

  // // Angka pada tab ikut diperbarui setiap tabel dimuat ulang
  // $(document).on('xhr.dt', function (e, settings, json) {
  //   if (!json || !json.stats) return;
  //   $.each(json.stats, function (key, val) {
  //     $('.mp-tab b[data-count="' + key + '"]').text(val);
  //   });
  // });

  refreshPreview();
});