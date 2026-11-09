/**
 * bundling-form.js
 * Dimuat SETELAH file JS utama (butuh jQuery, moment, bootstrap-datetimepicker,
 * ADMIN_URL, dan Toast dari file JS utama).
 *
 * Catatan: pastikan .date-start dan .date-end sudah diinisialisasi sebagai
 * datetimepicker oleh JS global. Skrip ini hanya mengatur perilakunya.
 */
(function ($) {
    'use strict';

    var $form = $('#form_bundling');
    if (!$form.length) return;

    var $paket    = $form.find('#trx_master_bundling_id');
    var $period   = $form.find('#period_type');
    var $result   = $form.find('#result_oriented');
    var $start    = $form.find('#startdate');
    var $end      = $form.find('#enddate');
    var $colEnd   = $form.find('#col_enddate');
    var $hourCols = $form.find('.js-hour-col');
    var $hours    = $form.find('#minimum_hour, #maximum_hour');

    var stash = {};      // simpan nilai jam saat disembunyikan
    var syncing = false; // cegah loop event dp.change

    function picker($el) { return $el.data('DateTimePicker'); }

    /* =========================================================
     * 1. Tipe Penilaian: Jam -> tampilkan min/max, Hasil -> sembunyikan
     * ========================================================= */
    function syncAssessment() {
        var type   = $result.val();
        var isTime = (type === 'time');

        $hourCols.toggleClass('d-none', !isTime);

        $hours.each(function () {
            var $el = $(this);
            var id  = this.id;

            if (isTime) {
                // tampil lagi: kembalikan nilai yang sempat disimpan (jika kosong)
                if (!$el.val() && stash[id]) $el.val(stash[id]);
                delete stash[id];
            } else {
                // sembunyi: simpan lalu kosongkan agar tidak ikut terkirim
                if ($el.val()) stash[id] = $el.val();
                $el.val('');
                $('#error_' + id).text('');
            }
        });

        $('#hint_result').text(
            type === 'time' ? 'Dinilai berdasarkan jam kerja (min/max mengikuti paket).' :
            type === 'task' ? 'Dinilai berdasarkan hasil pekerjaan.' : ''
        );
    }

    $form.on('change', '#result_oriented', syncAssessment);

    /* =========================================================
     * 2. Tipe Periode: Harian -> 1 tanggal, Bulanan -> rentang tanggal
     * ========================================================= */
    function syncPeriod() {
        var type  = $period.val();
        var isDay = (type === 'day');
        var s = picker($start);
        var e = picker($end);

        $colEnd.toggleClass('d-none', isDay);
        $('#label_startdate_text').text(isDay ? 'Tanggal' : 'Tanggal Mulai');
        $('#hint_date').text(
            isDay ? 'Periode harian: pilih 1 tanggal.' :
            type === 'month' ? 'Periode bulanan: pilih rentang tanggal mulai - selesai.' : ''
        );

        if (!s || !e) return;

        syncing = true;
        try {
            if (isDay) {
                s.maxDate(false);
                e.minDate(false);
                e.date(s.date() || null);       // selesai = mulai
                $('#error_enddate').text('');
            } else {
                var sd = s.date();
                var ed = e.date();
                if (sd && ed && ed.isBefore(sd, 'day')) e.date(null);
                e.minDate(sd || false);
                s.maxDate(e.date() || false);
            }
        } finally {
            syncing = false;
        }
    }

    $form.on('change', '#period_type', syncPeriod);

    // Sinkronisasi antar tanggal
    $form.on('dp.change', '#startdate', function (ev) {
        if (syncing) return;
        var e = picker($end);
        if (!e) return;

        syncing = true;
        try {
            if ($period.val() === 'day') {
                e.date(ev.date || null);
            } else {
                var ed = e.date();
                if (ev.date && ed && ed.isBefore(ev.date, 'day')) e.date(null);
                e.minDate(ev.date || false);
            }
        } finally {
            syncing = false;
        }
    });

    $form.on('dp.change', '#enddate', function (ev) {
        if (syncing || $period.val() === 'day') return;
        var s = picker($start);
        if (!s) return;

        syncing = true;
        try {
            s.maxDate(ev.date || false);
        } finally {
            syncing = false;
        }
    });

    $modal.on('hidden.bs.modal', function () {
        if (detailXhr) detailXhr.abort();
    });

    /* =========================================================
     * Publik: panggil setelah field diisi lewat kode (getDetail / mode edit)
     * ========================================================= */
    window.BundlingForm = {
        syncAssessment: syncAssessment,
        syncPeriod: syncPeriod,
        syncAll: function () { syncPeriod(); syncAssessment(); }
    };

    // State awal
    window.BundlingForm.syncAll();

})(jQuery);
