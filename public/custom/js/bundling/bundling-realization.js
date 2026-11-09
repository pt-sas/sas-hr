/**
 * Proses Realisasi Pengajuan Paket (modal #modal_bundling_realization_agree)
 *
 * Submit memakai handler umum .btn_ok_realization dan .btn_close_realization (js global),
 * jadi di sini hanya mengisi form: field "employees" & "lines" (JSON), "description"
 * dan file "photo_N". Tidak memakai name berkurung ([]) agar aman dengan clearForm().
 */
const BR_MODAL = "#modal_bundling_realization_agree";
const BR_FORM = "#form_bundling_realization_agree";

// TODO: ganti absen dengan url get data absen yang sebenarnya jika sudah ada
const BR_URL = {
  detail: `${SITE_URL}/getRealizationData`,
  absen: `${SITE_URL}/getAttendance`,
};

const BR_PHOTO = {
  MAX_FILES: 20,
  MAX_SIZE: 10 * 1024 * 1024, // 10 MB
  ALLOWED: ["png", "jpg", "jpeg", "webp", "gif"],
};

//* Data pengajuan dan baris jam: { [md_employee_id]: { [date]: { starttime, endtime, clock_in, clock_out } } }
let brData = {};
let brLines = {};

function brEscape(text) {
  return $("<div>").text(text == null ? "" : text).html();
}

function brMinutes(time) {
  if (!time) return NaN;

  const t = time.split(":");
  return Number(t[0]) * 60 + Number(t[1]);
}

function brDuration(minute) {
  return Math.floor(minute / 60) + " j " + (minute % 60) + " m";
}

/**
 * Validasi satu baris jam
 * @param {*} line
 * @returns [class badge, teks]
 */
function brStatus(line) {
  const start = brMinutes(line.starttime);
  const end = brMinutes(line.endtime);

  if (isNaN(start) || isNaN(end)) return ["warning", "Lengkapi jam"];
  if (end <= start) return ["danger", "Selesai harus setelah mulai"];
  if (line.clock_out && end > brMinutes(line.clock_out))
    return ["danger", "Melebihi check out absen (" + line.clock_out + ")"];
  if (brData.max && end - start > brData.max * 60)
    return ["danger", "Maksimal " + brData.max + " jam"];
  if (brData.min && end - start < brData.min * 60)
    return ["danger", "Minimal " + brData.min + " jam"];

  return ["success", brDuration(end - start)];
}

// --- Photo staging module (closure hides File list) ---
const BundlingPhotoManager = (function () {
  let staged = [];
  let onChange = null;

  function setOnChange(fn) { onChange = fn; }

  function files() { return staged; }

  function reset() {
    staged = [];
    if (typeof onChange === "function") onChange();
  }

  function add(newFiles) {
    let changed = false;

    for (let i = 0; i < newFiles.length; i++) {
      const file = newFiles[i];
      const ext = (file.name.split(".").pop() || "").toLowerCase();

      if (staged.length >= BR_PHOTO.MAX_FILES) {
        Toast.fire({ type: "error", title: `Batas maksimal adalah ${BR_PHOTO.MAX_FILES} file.` });
        break;
      }
      if (!BR_PHOTO.ALLOWED.includes(ext)) {
        Toast.fire({ type: "error", title: `File "${file.name}" ditolak. Format tidak diizinkan.` });
        continue;
      }
      if (file.size > BR_PHOTO.MAX_SIZE) {
        Toast.fire({ type: "error", title: `File "${file.name}" melebihi batas ukuran 10 MB.` });
        continue;
      }

      // Dedupe: skip identical name + size + lastModified
      const duplicate = staged.some(
        (f) => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
      );
      if (duplicate) continue;

      staged.push(file);
      changed = true;
    }

    if (changed && typeof onChange === "function") onChange();
    return changed;
  }

  function removeAt(index) {
    staged.splice(index, 1);
    if (typeof onChange === "function") onChange();
  }

  return { setOnChange, files, reset, add, removeAt };
})();

/**
 * Render daftar foto dan input file tersembunyi (photo_0, photo_1, ...) agar ikut FormData
 */
function brRenderPhotos() {
  const $list = $("#br_photo_list");
  const $inputs = $("#br_photo_inputs");
  const files = BundlingPhotoManager.files();

  $list.empty();
  $inputs.empty();

  $.each(files, function (index, file) {
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    const row = `
      <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-3 bg-light mb-1 border rounded">
        <span class="text-truncate mr-2" style="max-width: 80%;">
          <img src="${URL.createObjectURL(file)}" class="mr-2 rounded" style="height: 32px; width: 32px; object-fit: cover;">
          <strong>${brEscape(file.name)}</strong>
          <small class="text-muted">(${sizeMB} MB)</small>
        </span>
        <button type="button" class="btn btn-xs btn-outline-danger btn-cancel-photo" data-index="${index}" title="Batal Upload">
          <i class="fas fa-times"></i>
        </button>
      </li>`;
    $list.append(row);

    const dt = new DataTransfer();
    dt.items.add(file);

    const input = $(`<input type="file" name="photo_${index}">`)[0];
    input.files = dt.files;
    $inputs.append(input);
  });

  brSync();
}

BundlingPhotoManager.setOnChange(brRenderPhotos);

/**
 * Sinkronkan state ke field form (JSON) dan ringkasan footer
 */
function brSync() {
  const form = $(BR_FORM);
  const ids = $("#br_employee").val() || [];

  let lines = [];
  let total = 0;

  $.each(ids, function (i, id) {
    $.each(brLines[id] || {}, function (date, line) {
      const diff = brMinutes(line.endtime) - brMinutes(line.starttime);

      if (diff > 0) total += diff;

      lines.push({
        md_employee_id: id,
        date: date,
        starttime: line.starttime,
        endtime: line.endtime,
      });
    });
  });

  form.find("input[name=employees]").val(ids.length ? JSON.stringify(ids) : "");
  form.find("input[name=lines]").val(brData.tipe === "jam" && lines.length ? JSON.stringify(lines) : "");

  if (brData.tipe === "jam")
    $("#br_sum").html(`<b>${ids.length}</b> karyawan · <b>${lines.length}</b> tanggal · total <b>${brDuration(total)}</b>`);
  else
    $("#br_sum").html(`<b>${ids.length}</b> karyawan · <b>${BundlingPhotoManager.files().length}</b> foto`);
}

function brRowHtml(id, date, line) {
  const status = brStatus(line);
  const absen = line.clock_out ? `Absen ${line.clock_in || "-"} – ${line.clock_out}` : "Tanpa data absen";

  const time = (field) => `
    <div class="input-icon">
      <input type="text" class="form-control br-time" data-field="${field}" value="${line[field] || ""}">
      <span class="input-icon-addon">
        <i class="fa fa-clock"></i>
      </span>
    </div>`;

  return `
    <div class="row align-items-center border-top py-2 br-row" data-id="${id}" data-date="${date}">
      <div class="col-md-2"><b>${moment(date).format("ddd, DD MMM")}</b></div>
      <div class="col-md-3"><small class="text-muted">${absen}</small></div>
      <div class="col-md-2">${time("starttime")}</div>
      <div class="col-md-2">${time("endtime")}</div>
      <div class="col-md-3 br-status"><span class="badge badge-${status[0]}">${status[1]}</span></div>
    </div>`;
}

function brRenderRows(id) {
  const box = $(`.br-rows[data-id="${id}"]`);
  const dates = Object.keys(brLines[id] || {}).sort();

  box.find(".br-time").each(function () {
    if ($(this).data("DateTimePicker")) $(this).data("DateTimePicker").destroy();
  });

  box.html(
    dates.length
      ? dates.map((date) => brRowHtml(id, date, brLines[id][date])).join("")
      : '<div class="text-muted">Belum ada tanggal dipilih.</div>'
  );

  box.find(".br-time").datetimepicker({
    format: "HH:mm",
    showClear: true,
    showClose: true,
    useCurrent: false,
  });
}

/**
 * Ambil jam absen karyawan pada satu tanggal, lalu isi barisnya
 * @param {*} id md_employee_id
 * @param {*} date YYYY-MM-DD
 */
function brAddDate(id, date) {
  brLines[id] = brLines[id] || {};
  brLines[id][date] = { starttime: "17:00", endtime: "", clock_in: null, clock_out: null };

  let url = BR_URL.absen;

  $.ajax({
    url: url,
    type: "POST",
    data: { md_employee_id: id, date: date },
    cache: false,
    dataType: "JSON",
    success: function (result) {
      if (!brLines[id] || !brLines[id][date]) return;

      if (result && result.clock_out) {
        brLines[id][date].clock_in = result.clock_in ? result.clock_in.substr(0, 5) : null;
        brLines[id][date].clock_out = result.clock_out.substr(0, 5);
        brLines[id][date].endtime = brLines[id][date].clock_out;
      }

      brRenderRows(id);
      brSync();
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}

/**
 * Cek plugin bootstrap-datepicker yang mendukung multidate (versi 1.4 ke atas)
 */
function brHasMultidate() {
  return typeof $.fn.datepicker === "function" && !!$.fn.datepicker.defaults && "multidate" in $.fn.datepicker.defaults;
}

function brAddCard(id) {
  const name = $("#br_employee option[value=" + id + "]").text();
  let html = `<div class="card card-body br-card mb-3" data-id="${id}"><h5 class="card-title">${brEscape(name)}</h5>`;

  //* Bulanan: pilih tanggal mana saja dalam periode pengajuan (datepicker multidate)
  if (brData.per === "bulanan") {
    html += `
      <div class="form-group">
        <label>Tanggal Lembur</label>
        <div class="input-icon">
          <input type="text" class="form-control br-dates" data-id="${id}" placeholder="Pilih tanggal lembur" autocomplete="off" readonly>
          <span class="input-icon-addon">
            <i class="fa fa-calendar"></i>
          </span>
        </div>
      </div>`;
  }

  html += `<div class="br-rows" data-id="${id}"></div></div>`;
  $("#br_cards").append(html);

  if (brData.per === "bulanan") {
    if (brHasMultidate()) {
      //? Tanggal lembur tidak boleh melewati hari ini
      $(`.br-dates[data-id="${id}"]`).datepicker({
        format: "dd-M-yyyy",
        multidate: true,
        multidateSeparator: ", ",
        startDate: moment(brData.from).toDate(),
        endDate: moment.min(moment(brData.to), moment()).toDate(),
        autoclose: false,
        clearBtn: true,
        todayHighlight: true,
      });
    } else {
      Toast.fire({ type: "error", title: "Plugin bootstrap-datepicker (multidate) belum tersedia" });
    }

    brRenderRows(id);
  } else {
    //* Harian: tanggal mengikuti startdate pengajuan
    brAddDate(id, brData.start);
  }
}

function brReset() {
  brData = {};
  brLines = {};

  $("#br_cards, #br_photo_list, #br_photo_inputs, #br_sum").empty();
  $("#br_jam, #br_hasil").addClass("d-none");
  $(BR_FORM).find("input[name=employees], input[name=lines], textarea[name=description]").val("");

  BundlingPhotoManager.reset();
  clearErrorForm($(BR_FORM));
}

/**
 * Event modal tampil: ambil data pengajuan (ID di-set oleh handler .btn_agree)
 */
$(document).on("shown.bs.modal", BR_MODAL, function (e) {
  if (e.target !== this) return;

  brReset();

  let url = BR_URL.detail;

  $.ajax({
    url: url,
    type: "POST",
    data: { id: ID },
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".btn_ok_realization").prop("disabled", true);
      loadingForm("realization", "facebook");
    },
    complete: function () {
      $(".btn_ok_realization").removeAttr("disabled");
      hideLoadingForm("realization");
    },
    success: function (result) {
      const start = moment(result.startdate).format("YYYY-MM-DD");
      const end = result.enddate ? moment(result.enddate).format("YYYY-MM-DD") : start;
      const isMonth = result.period_type === "month";

      brData = {
        tipe: result.result_oriented === "task" ? "hasil" : "jam",
        per: isMonth ? "bulanan" : "harian",
        start: start,
        end: end,
        //* Bulanan: tanggal lembur boleh dipilih di seluruh bulan pengajuan
        from: isMonth ? moment(start).startOf("month").format("YYYY-MM-DD") : start,
        to: isMonth ? moment(end).endOf("month").format("YYYY-MM-DD") : end,
        min: parseFloat(result.minimum_hour) || 0,
        max: parseFloat(result.maximum_hour) || 0,
        task: result.task_description || "",
      };

      $("#br_documentno").val(result.documentno);
      $("#br_applicant").val(result.employee_name);
      $("#br_tipe").val(brData.tipe === "jam" ? "Jam - " + (brData.per === "harian" ? "Harian" : "Bulanan") : "Hasil");

      const $employee = $("#br_employee");

      if ($employee.hasClass("select2-hidden-accessible")) $employee.select2("destroy");

      $employee.empty();

      $.each(result.employees || [], function (i, item) {
        $employee.append(new Option(item.name, item.id, false, false));
      });

      $employee.select2({
        placeholder: "Pilih karyawan",
        width: "100%",
        theme: "bootstrap",
        multiple: true,
        allowClear: true,
        dropdownParent: $(BR_MODAL),
      });

      if (brData.tipe === "jam") {
        const range =
          brData.per === "harian"
            ? "Tanggal lembur: <b>" + moment(brData.start).format("DD MMM YYYY") + "</b>"
            : "Periode: <b>" + moment(brData.from).format("DD MMM YYYY") + " - " + moment(brData.to).format("DD MMM YYYY") + "</b> &middot; tanggal setelah hari ini belum dapat dipilih";
        const limit = brData.min || brData.max ? " &middot; Durasi per tanggal: " + (brData.min || 0) + " - " + (brData.max || "tanpa batas") + " jam" : "";

        $("#br_info").html(range + limit);
        $("#br_jam").removeClass("d-none");
      } else {
        $("#br_task").html("<b>Task:</b> " + brEscape(brData.task));
        $("#br_hasil").removeClass("d-none");
      }

      //* Tipe Jam: karyawan pada transaksi langsung terpilih (harian: tanggal + jam absen langsung diambil)
      if (brData.tipe === "jam")
        $employee
          .val($.map(result.employees || [], (item) => String(item.id)))
          .trigger("change");

      brSync();
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
});

$(document).on("hidden.bs.modal", BR_MODAL, function (e) {
  if (e.target === this) brReset();
});

/**
 * Event pilih karyawan
 */
$(document).on("change", "#br_employee", function () {
  const ids = ($(this).val() || []).map(String);

  //* Hapus card karyawan yang tidak dipilih lagi
  $("#br_cards .br-card").each(function () {
    const id = String($(this).data("id"));

    if (!ids.includes(id)) {
      delete brLines[id];
      $(this).find(".br-dates").datepicker("destroy");
      $(this).remove();
    }
  });

  //* Tambah card karyawan baru
  if (brData.tipe === "jam")
    $.each(ids, function (i, id) {
      if (!$(`#br_cards .br-card[data-id="${id}"]`).length) brAddCard(id);
    });

  brSync();
});

/**
 * Event pilih tanggal lembur per karyawan (periode bulanan)
 */
$(document).on("changeDate", BR_MODAL + " .br-dates", function () {
  const id = $(this).data("id");
  const selected = $.map($(this).datepicker("getUTCDates"), (date) => moment.utc(date).format("YYYY-MM-DD"));

  brLines[id] = brLines[id] || {};

  //* Hapus tanggal yang tidak dipilih lagi
  $.each(Object.keys(brLines[id]), function (i, date) {
    if (!selected.includes(date)) delete brLines[id][date];
  });

  //* Tanggal baru: ambil data absen
  $.each(selected, function (i, date) {
    if (!brLines[id][date]) brAddDate(id, date);
  });

  brRenderRows(id);
  brSync();
});

/**
 * Event ubah jam mulai / selesai
 */
$(document).on("dp.change change", BR_MODAL + " .br-time", function () {
  const row = $(this).closest(".br-row");
  const id = row.data("id");
  const date = row.data("date");

  if (!brLines[id] || !brLines[id][date]) return;

  brLines[id][date][$(this).data("field")] = $(this).val();

  const status = brStatus(brLines[id][date]);

  row.find(".br-status").html(`<span class="badge badge-${status[0]}">${status[1]}</span>`);
  brSync();
});

/**
 * Event foto hasil: pilih dan batalkan
 */
$(document).on("change", "#br_photo_pick", function () {
  BundlingPhotoManager.add(this.files);
  $(this).val("");
});

$(document).on("click", ".btn-cancel-photo", function () {
  BundlingPhotoManager.removeAt($(this).data("index"));
});