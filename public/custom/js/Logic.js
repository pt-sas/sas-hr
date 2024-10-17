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
  "#md_branch_id, #md_division_id, #md_supplier_id",
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
          (attrName === "md_branch_id" ||
            attrName === "md_division_id" ||
            attrName === "md_supplier_id") &&
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

  let row = [];

  $.each(checkbox, function (idx, item) {
    if ($(this).is(":checked")) {
      const tr = $(this).closest("tr");
      let nik = tr.find("td:eq(1)").text();
      let date = tr.find("td:eq(3)").text();

      row.push({
        id: item.value,
        nik: nik,
        date: date,
      });
    }
  });

  formData.append("employee", JSON.stringify(row));

  $.ajax({
    url: url,
    type: "POST",
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
      } else if (result[0].error) {
        Toast.fire({
          type: "error",
          title: result[0].message,
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
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
    format: "dd-M-Y",
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

$("#form_office_duties").on("change", "input[name=isbranch]", function (e) {
  const _this = $(this);
  const target = $(e.target);
  const form = target.closest("form");

  const fields = _this
    .attr("hide-id")
    .split(",")
    .map((element) => element.trim());

  if (_this.is(":checked")) {
    for (let i = 0; i < fields.length; i++) {
      form
        .find(
          "input[name=" +
            fields[i] +
            "], textarea[name=" +
            fields[i] +
            "], select[name=" +
            fields[i] +
            "]"
        )
        .not(".line")
        .closest(".form-group")
        .show();
    }
  } else {
    for (let i = 0; i < fields.length; i++) {
      form
        .find(
          "input[name=" +
            fields[i] +
            "], textarea[name=" +
            fields[i] +
            "], select[name=" +
            fields[i] +
            "]"
        )
        .not(".line")
        .closest(".form-group")
        .hide();
    }
  }
});

$("#form_office_duties").on("change", "#md_branch_id", function (e) {
  let _this = $(this);
  getOptionBranchTo(_this);
});

$("#form_resign").on("change", "#departuretype", function (e) {
  let _this = $(this);
  let value = this.value;
  let formData = new FormData();
  const form = _this.closest("form");

  let url = `${ADMIN_URL}reference/getList`;

  formData.append("criteria", value);

  if (value === "") {
    if (form.find("select[name=departurerule]").length)
      form.find("select[name=departurerule]").val(null).change();
  }

  form.find("select[name=departurerule]").empty();

  if (value !== "") {
    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(".x_form").prop("disabled", true);
        $(".close_form").prop("disabled", true);
        loadingForm(form.prop("id"), "facebook");
      },
      complete: function () {
        $(".x_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm(form.prop("id"));
      },
      success: function (result) {
        if (result.length) {
          form
            .find("select[name=departurerule]")
            .append('<option value=""></option>');

          let departurerule = "";

          if (option.length) {
            $.each(option, function (i, item) {
              if (item.fieldName == "departurerule") departurerule = item.label;
            });
          }

          $.each(result, function (idx, item) {
            if (departurerule == item.id) {
              form
                .find("select[name=departurerule]")
                .append(
                  '<option value="' +
                    item.id +
                    '" selected>' +
                    item.text +
                    "</option>"
                );
            } else {
              form
                .find("select[name=departurerule]")
                .append(
                  '<option value="' + item.id + '">' + item.text + "</option>"
                );
            }
          });
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
});

let prevData;

$(document).ready(function (evt) {
  $("#form_leave_cancel #reference_id")
    .on("focus", function (e) {
      prevData = this.value;
    })
    .change(function (evt) {
      const form = $(this).closest("form");
      const attrName = $(this).attr("name");

      let leave_id = this.value;

      // create data
      if (leave_id !== "" && setSave === "add") {
        _tableLine.clear().draw(false);
        setLeaveDetail(form, leave_id);
      }

      // update data
      $.each(option, function (idx, elem) {
        if (elem.fieldName === attrName && setSave !== "add") {
          if (
            leave_id !== "" &&
            leave_id != elem.option_ID &&
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
                setLeaveDetail(form, leave_id, ID);
              } else {
                form
                  .find("select[name=" + attrName + "]")
                  .val(elem.option_ID)
                  .change();
              }
            });
          }

          if (
            leave_id !== "" &&
            leave_id != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            setLeaveDetail(form, leave_id);
          }

          if (
            typeof prev !== "undefined" &&
            prev !== "" &&
            leave_id !== "" &&
            prev != leave_id &&
            prev != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            _tableLine.clear().draw(false);
            setLeaveDetail(form, leave_id);
          }
        }
      });

      prevData = this.value;
    });
});

function setLeaveDetail(form, id, cancel_id = 0) {
  let url = ADMIN_URL + "pembatalan-cuti/getLeaveDetail";

  $.ajax({
    url: url,
    type: "POST",
    data: {
      id: id,
      cancel_id: cancel_id,
    },
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".x_form").prop("disabled", true);
      $(".close_form").prop("disabled", true);
      loadingForm(form.prop("id"), "facebook");
    },
    complete: function () {
      $(".x_form").removeAttr("disabled");
      $(".close_form").removeAttr("disabled");
      hideLoadingForm(form.prop("id"));
    },
    success: function (result) {
      if (result[0].success) {
        let arrMsg = result[0].message;

        if (arrMsg.line) {
          if (_tableLine.context.length) {
            let line = JSON.parse(arrMsg.line);

            _tableLine.rows.add(line).draw(false);

            const field = _tableLine.rows().nodes().to$().find("input, select");

            $.each(field, function (index, item) {
              const tr = $(this).closest("tr");

              if (item.type !== "text") {
                tr.find(
                  "input:checkbox[name=" +
                    item.name +
                    "], select[name=" +
                    item.name +
                    "], input:radio[name=" +
                    item.name +
                    "]"
                ).prop("disabled", true);
              } else {
                tr.find(
                  "input:text[name=" +
                    item.name +
                    "], textarea[name=" +
                    item.name +
                    "]"
                ).prop("readonly", true);
              }
            });
          }
        }
      } else {
        Toast.fire({
          type: "error",
          title: result[0].message,
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}

$("#form_leave_cancel").on("change", "#md_employee_id", function (e) {
  let _this = $(this);
  let value = this.value;
  let formData = new FormData();
  const form = _this.closest("form");
  const field = form.find("select[name=reference_id]");

  let url = `${ADMIN_URL}cuti/get-list`;

  formData.append("md_employee_id", value);
  formData.append("id", ID);

  field.empty();

  if (value !== "") {
    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(".x_form").prop("disabled", true);
        $(".close_form").prop("disabled", true);
      },
      complete: function () {
        $(".x_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
      },
      success: function (result) {
        if (result.length) {
          field.append('<option value=""></option>');

          let reference_id = 0;

          if (option.length) {
            $.each(option, function (i, item) {
              if (item.fieldName == "reference_id") reference_id = item.label;
            });
          }

          $.each(result, function (idx, item) {
            if (setSave === "detail" && reference_id == item.id)
              field
                .append(
                  '<option value="' +
                    item.id +
                    '" selected>' +
                    item.text +
                    "</option>"
                )
                .prop("disabled", true);
            else
              field.append(
                '<option value="' + item.id + '">' + item.text + "</option>"
              );
          });
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
});

$("#form_exit_interview").on("change", "#reference_id", function (e) {
  let _this = $(this);
  const form = _this.closest("form");
  let value = this.value;
  let formData = new FormData();

  formData.append("reference_id", value);

  let url = ADMIN_URL + "resign/getDetail";

  if (value === "") {
    if (form.find("input[name=nik]").length)
      form.find("input[name=nik]").val(null);

    if (form.find("select[name=md_employee_id]").length)
      form
        .find("select[name=md_employee_id]")
        .val(null)
        .change()
        .prop("disabled", true);

    if (form.find("select[name=md_branch_id]").length)
      form
        .find("select[name=md_branch_id]")
        .val(null)
        .change()
        .prop("disabled", true);

    if (form.find("select[name=md_division_id]").length)
      form
        .find("select[name=md_division_id]")
        .val(null)
        .change()
        .prop("disabled", true);

    if (form.find("select[name=md_position_id]").length)
      form
        .find("select[name=md_position_id]")
        .val(null)
        .change()
        .prop("disabled", true);
  }

  if (value !== "")
    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(".x_form").prop("disabled", true);
        $(".close_form").prop("disabled", true);
        loadingForm(form.prop("id"), "facebook");
      },
      complete: function () {
        $(".x_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm(form.prop("id"));
      },
      success: function (result) {
        // console.log(result);
        if (result.length) {
          if (form.find("input[name=nik]").length)
            form.find("input[name=nik]").val(result[0].nik);

          if (form.find("input[name=terminatedate]").length && setSave == "add")
            form
              .find("input[name=terminatedate]")
              .val(moment(result[0].date).format("DD-MMM-YYYY"));

          if (form.find("select[name=md_branch_id]").length)
            getBranch(_this, result[0].md_branch_id);

          if (form.find("select[name=md_division_id]").length)
            getDivision(_this, result[0].md_division_id);

          if (form.find("select[name=md_position_id]").length)
            getPosition(_this, result[0].md_position_id);

          if (form.find("select[name=md_employee_id]").length)
            getEmployee(_this, result[0].md_employee_id);
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
});

function getBranch(elem, branch) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_branch_id]");
  const id = branch;

  let url = ADMIN_URL + "branch/getList";
  formData.append("md_branch_id", id);

  field.empty();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".x_form").prop("disabled", true);
      $(".close_form").prop("disabled", true);
    },
    complete: function () {
      $(".x_form").removeAttr("disabled");
      $(".close_form").removeAttr("disabled");
    },
    success: function (result) {
      if (result.length) {
        field.append('<option value=""></option>');

        $.each(result, function (idx, item) {
          if (setSave === "detail")
            field
              .append(
                '<option value="' +
                  item.id +
                  '" selected>' +
                  item.text +
                  "</option>"
              )
              .prop("disabled", true);
          else
            field.append(
              '<option value="' +
                item.id +
                '" selected>' +
                item.text +
                "</option>"
            );
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}

function getDivision(elem, division) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_division_id]");
  const id = division;

  let url = ADMIN_URL + "division/getList";
  formData.append("md_division_id", id);

  field.empty();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".x_form").prop("disabled", true);
      $(".close_form").prop("disabled", true);
    },
    complete: function () {
      $(".x_form").removeAttr("disabled");
      $(".close_form").removeAttr("disabled");
    },
    success: function (result) {
      if (result.length) {
        field.append('<option value=""></option>');

        $.each(result, function (idx, item) {
          if (setSave === "detail")
            field
              .append(
                '<option value="' +
                  item.id +
                  '" selected>' +
                  item.text +
                  "</option>"
              )
              .prop("disabled", true);
          else
            field.append(
              '<option value="' +
                item.id +
                '" selected>' +
                item.text +
                "</option>"
            );
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}

function getEmployee(elem, employee) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_employee_id]");
  const id = employee;

  let url = ADMIN_URL + "karyawan/getList";
  formData.append("md_employee_id", id);

  field.empty();

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".x_form").prop("disabled", true);
      $(".close_form").prop("disabled", true);
    },
    complete: function () {
      $(".x_form").removeAttr("disabled");
      $(".close_form").removeAttr("disabled");
    },
    success: function (result) {
      if (result.length) {
        field.append('<option value=""></option>');

        $.each(result, function (idx, item) {
          if (setSave === "detail")
            field
              .append(
                '<option value="' +
                  item.id +
                  '" selected>' +
                  item.text +
                  "</option>"
              )
              .prop("disabled", true);
          else
            field.append(
              '<option value="' +
                item.id +
                '" selected>' +
                item.text +
                "</option>"
            );
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}
