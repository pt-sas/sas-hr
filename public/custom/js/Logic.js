$(".timepicker").datetimepicker({
  format: "HH:mm",
  useCurrent: false,
});

$(".timepicker-start").datetimepicker({
  format: "HH:mm",
  useCurrent: false,
});

$(".timepicker-end").datetimepicker({
  format: "HH:mm",
  useCurrent: false,
});

$(".timepicker-start").on("dp.change", function (e) {
  $(".timepicker-end").data("DateTimePicker").minDate(e.date);
});

$(".date-start").on("dp.change", function (e) {
  $(".date-end").data("DateTimePicker").minDate(e.date);
});

$(".date-start").datetimepicker({
  format: "DD-MMM-YYYY",
  showTodayButton: true,
  showClear: true,
  showClose: true,
  useCurrent: false,
});

$(".date-end").datetimepicker({
  format: "DD-MMM-YYYY",
  showTodayButton: true,
  showClear: true,
  showClose: true,
  useCurrent: false,
});

$("#form_official_permission").on(
  "change dp.change",
  "select[name=md_leavetype_id], input[name=startdate]",
  function (e) {
    let _this = $(this);
    const form = _this.closest("form");
    let value = this.value;
    let formData = new FormData();

    let url = ADMIN_URL + "ijin-resmi/getEndDate";

    if (option.length) {
      $.each(option, function (i, item) {
        if (typeof _this.find(":selected").val() === "undefined") {
          if (
            _this.attr("name") === "md_leavetype_id" &&
            item.fieldName === "md_leavetype_id"
          )
            value = item.label;
        }
      });
    }

    formData.append(this.name, value);

    if (this.name === "md_leavetype_id") {
      if (value != 100003) {
        $(".date-start").data("DateTimePicker").destroy();

        $(".date-start").datetimepicker({
          format: "DD-MMM-YYYY",
          showTodayButton: true,
          showClear: true,
          showClose: true,
          daysOfWeekDisabled: [0, 6],
          disabledDates: getHolidayDate(),
          useCurrent: false,
        });
      } else {
        $(".date-start").data("DateTimePicker").destroy();

        $(".date-start").datetimepicker({
          format: "DD-MMM-YYYY",
          showTodayButton: true,
          showClear: true,
          showClose: true,
          useCurrent: false,
        });
      }

      formData.append("startdate", form.find("[name=startdate]").val());
    }

    if (this.name === "startdate")
      formData.append(
        "md_leavetype_id",
        form.find("[name=md_leavetype_id]").val()
      );

    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "JSON",
      success: function (result) {
        if (
          form.find("[name=md_leavetype_id]").val() !== "" &&
          form.find("[name=startdate]").val() !== ""
        )
          form.find("[name=enddate]").val(moment(result).format("DD-MMM-Y"));
        else form.find("[name=enddate]").val(null);
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
);

$("#form_overtime").on("dp.change", "input[name=startdate]", function (e) {
  $("[name=enddate]").data("DateTimePicker").date(moment(e.date));

  const rows = _tableLine.rows().nodes().to$();

  rows.find("input[name=datestart]").val(this.value);
  rows.find("input[name=dateend]").val(this.value);
});

$("#form_overtime").on(
  "change",
  "#md_branch_id, #md_division_id",
  function (evt) {
    const form = $(this).closest("form");
    const field = form.find("select");
    const attrName = $(this).attr("name");
    let value = this.value;
    let fields = [];

    if (attrName === "movementtype")
      for (let i = 0; i < field.length; i++) {
        if (typeof $(field[i]).attr("hide-field") !== "undefined")
          fields = $(field[i])
            .attr("hide-field")
            .split(",")
            .map((element) => element.trim());

        select.val(null).change();
      }

    if (setSave === "add") {
      _tableLine.clear().draw(false);
    }
    // update data
    $.each(option, function (idx, elem) {
      if (elem.fieldName === attrName && setSave !== "add") {
        if (
          (attrName === "md_branch_id" || attrName === "md_division_id") &&
          value !== "" &&
          elem.label !== "undefined" &&
          value != elem.label &&
          _tableLine.data().any()
        ) {
          Swal.fire({
            title: "Delete?",
            text: "Are you sure you want to change all data ? ",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Okay",
            cancelButtonText: "Close",
            reverseButtons: true,
          }).then((data) => {
            if (data.value) {
              _tableLine.clear().draw(false);
            } else {
              form
                .find("select[name=" + attrName + "]")
                .val(elem.label)
                .change();
            }
          });
        }
      }
    });
  }
);

_tableReport.on("click", ".btn_edit_att", function (e) {
  const tr = $(this).closest("tr");
  let description = tr.find("td:eq(4)").text();
  let id = this.id;

  $("#modal_attendance").modal({
    backdrop: "static",
    keyboard: false,
  });

  const form = $("#form_attendance");
  form.find("textarea[name=description]").val(description);

  ID = id;
});

$(".btn_ok_attendance").click(function (e) {
  e.preventDefault();
  let _this = $(this);
  const parent = $(this).closest(".modal");
  const form = parent.find("form");
  const field = form.find("input, select, textarea");
  let formData = new FormData(form[0]);

  for (let i = 0; i < field.length; i++) {
    if (field[i].name !== "") {
      //* Set field and value to formData
      if (
        field[i].type == "text" ||
        field[i].type == "textarea" ||
        field[i].type == "select-one" ||
        field[i].type == "password" ||
        field[i].type == "hidden"
      )
        formData.append(field[i].name, field[i].value);
    }
  }

  if (ID != 0) formData.append("id", ID);

  let url = `${SITE_URL}${CREATE}`;

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      _this.prop("disabled", true);
      $(".btn_close_attendance").prop("disabled", true);
      loadingForm(form.prop("id"), "facebook");
    },
    complete: function () {
      _this.removeAttr("disabled");
      $(".btn_close_attendance").removeAttr("disabled");
      hideLoadingForm(form.prop("id"));
    },
    success: function (result) {
      if (result[0].success) {
        Toast.fire({
          type: "success",
          title: result[0].message,
        });

        $(`#${parent.attr("id")}`).modal("hide");
        clearForm(e);
        clearErrorForm(form);
        reloadTable();
      } else if (result[0].error) {
        errorForm(form, result);
      } else {
        Toast.fire({
          type: "error",
          title: result[0].message,
        });

        clearErrorForm(form);
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
});

$(".btn_close_attendance").click(function (e) {
  e.preventDefault();
  const parent = $(this).closest(".modal");
  const form = parent.find("form");

  clearForm(e);
  clearErrorForm(form);
  reloadTable();
});

_tableReport.on("click", ".check-alpa", function (e) {
  const target = $(e.target);
  const card = target.closest(".card");
  const cardHeader = card.find(".card-header");
  const floatRight = cardHeader.find(".float-right");
  const checkbox = _tableReport.rows().nodes().to$().find("input.check-alpa");

  let checkData = [];

  $.each(checkbox, function (idx, item) {
    if ($(this).is(":checked")) {
      if ($(item).is(":checked")) checkData.push(item.value);
      floatRight.removeClass("d-none");
    } else {
      if (checkData.length == 0) floatRight.addClass("d-none");
    }
  });
});

function Generate() {
  let formData = new FormData();
  let url = ADMIN_URL + "alpa/generate";

  const checkbox = _tableReport.rows().nodes().to$().find("input.check-alpa");

  $.each(checkbox, function (idx, item) {
    if ($(this).is(":checked")) {
      formData.append("trx_attendance_id[]", item.value);
    }
  });

  $.ajax({
    url: url,
    type: "post",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    success: function (result) {
      if (result[0].success) {
        Toast.fire({
          type: "success",
          title: result[0].message,
        });
        reloadTable();
      }
    },
  });
}

_tableRealization.on("click", ".btn_agree", function (e) {
  const form = $("#form_overtime_realization_agree");
  let startdate = new Date(form.find("[name=startdate]").val());
  let date_out = new Date(form.find("[name=enddate_att]").val());
  let maxtgl = new Date(startdate.getTime() + 24 * 60 * 60 * 1000);
  // let maxtime = new textToTime(form.find("[name=endtime_att]").val());
  let mintime;
  let maxtime;

  if (form.is($("#form_overtime_realization_agree"))) {
    mintime = new textToTime(form.find("[name=starttime]").val());

    if (form.find("[name=endtime_att]").val() != "") {
      maxtime = new textToTime(form.find("[name=endtime_att]").val());
    } else {
      maxtime = new textToTime("05:00");
    }

    /**
     * Logic for refresh and set min & max value of Date
     */
    $(".datepicker").data("DateTimePicker").maxDate(false).minDate(false);
    $(".timepicker-end").data("DateTimePicker").maxDate(false).minDate(false);

    // Check if Data valid in Date Out and Clock Out
    if (date_out != "Invalid Date") {
      $(".datepicker")
        .data("DateTimePicker")
        .maxDate(date_out)
        .minDate(startdate);
      if (maxtime != "Invalid Date") {
        $(".timepicker-end")
          .data("DateTimePicker")
          .date(moment(maxtime))
          .maxDate(maxtime)
          .minDate(mintime);
      }
    } else {
      $(".datepicker")
        .data("DateTimePicker")
        .maxDate(maxtgl)
        .minDate(startdate);
      $(".timepicker-end")
        .data("DateTimePicker")
        .maxDate(false)
        .minDate(mintime);
    }

    // This logic for set mix and max time base on enddate changes
    $(".datepicker").on("dp.change", function (e) {
      let dateend = new Date(e.date);

      form.find("[name=endtime_realization]").val("");

      if (startdate < dateend) {
        $(".timepicker-end")
          .data("DateTimePicker")
          .maxDate(false)
          .minDate(false);
        $(".timepicker-end")
          .data("DateTimePicker")
          .maxDate(maxtime)
          .minDate(false);
      } else {
        $(".timepicker-end")
          .data("DateTimePicker")
          .maxDate(false)
          .minDate(false);
        if (form.find("[name=endtime_att]").val() != "") {
          $(".timepicker-end")
            .data("DateTimePicker")
            .maxDate(maxtime)
            .minDate(mintime);
        } else {
          $(".timepicker-end")
            .data("DateTimePicker")
            .maxDate(false)
            .minDate(mintime);
        }
      }
    });
  }
});

//** Function for convert Time String to Date**/

function textToTime(time) {
  const [hours, minutes] = time.split(":");
  // const date = new Date(date1);
  const date = new Date();
  date.setHours(Number(hours), Number(minutes), 0);
  return date;
}

$(".week-picker")
  .datepicker({
    clearBtn: true,
    weekStart: 1,
    calendarWeeks: true,
    forceParse: false,
  })
  .on("changeDate", function (e) {
    var date = e.date;
    let startDate = new Date(
      date.getFullYear(),
      date.getMonth(),
      date.getDate() - date.getDay() + 1
    );
    let endDate = new Date(
      date.getFullYear(),
      date.getMonth(),
      date.getDate() - date.getDay() + 7
    );
    $("#week-picker").datepicker("update", startDate);
    $("#week-picker").val(
      moment(startDate).format("DD-MMM-YYYY") +
        " - " +
        moment(endDate).format("DD-MMM-YYYY")
    );
  });

$("#form_special_office_duties").on(
  "dp.change",
  "input[name=startdate]",
  function (e) {
    $("[name=enddate]").data("DateTimePicker").date(moment(e.date));
  }
);
