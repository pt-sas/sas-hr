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

// $("#form_special_office_duties").on(
//   "dp.change",
//   "input[name=startdate]",
//   function (e) {
//     $("[name=enddate]").data("DateTimePicker").date(moment(e.date));
//   }
// );

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
  $("#form_submission_cancel #reference_id")
    .on("focus", function (e) {
      prevData = this.value;
    })
    .change(function (evt) {
      const form = $(this).closest("form");
      const attrName = $(this).attr("name");

      let ref_id = this.value;
      let docno = $(this).find(":selected").text();

      // create data
      if (ref_id !== "" && setSave === "add") {
        _tableLine.clear().draw(false);
        setSubmissionDetail(form, ref_id, 0, docno);
      }

      // update data
      $.each(option, function (idx, elem) {
        if (elem.fieldName === attrName && setSave !== "add") {
          if (
            ref_id !== "" &&
            ref_id != elem.option_ID &&
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
                setSubmissionDetail(form, ref_id, ID, docno);
              } else {
                form
                  .find("select[name=" + attrName + "]")
                  .val(elem.option_ID)
                  .change();
              }
            });
          }

          if (
            ref_id !== "" &&
            ref_id != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            setSubmissionDetail(form, ref_id, 0, docno);
          }

          if (
            typeof prev !== "undefined" &&
            prev !== "" &&
            ref_id !== "" &&
            prev != ref_id &&
            prev != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            _tableLine.clear().draw(false);
            setSubmissionDetail(form, ref_id, 0, docno);
          }
        }
      });

      prevData = this.value;
    });
});

function setSubmissionDetail(form, id, cancel_id = 0, docno) {
  let url = ADMIN_URL + "pembatalan/getSubmissionDetail";
  let formData = new FormData();

  formData.append("id", id);
  formData.append("cancel_id", cancel_id);
  formData.append("document_no", docno);

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

$("#form_submission_cancel").on(
  "change",
  "#md_employee_id, #ref_submissiontype",
  function (e) {
    let _this = $(this);
    let formData = new FormData();
    const form = _this.closest("form");
    const field = form.find("select[name=reference_id]");
    let employeeID = form.find("select[name=md_employee_id]").val();
    let submissionType = form.find("select[name=ref_submissiontype]").val();

    let url = `${ADMIN_URL}pembatalan/get-list`;

    // _tableLine.clear().draw(false);

    formData.append("md_employee_id", employeeID);
    formData.append("ref_submissiontype", submissionType);
    formData.append("id", ID);

    field.empty();

    if (employeeID !== "" && submissionType !== "") {
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
  }
);

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

/** This Section for Event Handler Table Childrow */
$(".tb_childrow").on("click", "td.details-control", function () {
  var tr = $(this).closest("tr");
  var row = _tableLine.row(tr);
  var id = tr.find("[name=md_employee_id]").data("line-id");

  if (row.child.isShown()) {
    row.child.hide();
    tr.removeClass("shown");
  } else {
    getAssignmentDate(id, function (tableHtml) {
      row.child(tableHtml).show();
      tr.addClass("shown");
    });
  }
});

function getAssignmentDate(id, callback) {
  let url = `${SITE_URL}/getAssignmentDate`;

  const form = $("#form_office_duties, #form_special_office_duties");

  $.ajax({
    url: url,
    type: "POST",
    data: { id: id },
    success: function (result) {
      if (result[0].success) {
        let data = result[0].message;
        let tableHtml = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                    <th width="10%">Tanggal</th>
                                    <th width="30%">Deskripsi</th>
                `;

        if (form.is($("#form_special_office_duties"))) {
          tableHtml += `
                                    <th width="10%">Absen Masuk</th>
                                    <th width="10%">Jam</th>
                                    <th width="10%">Absen Pulang</th>
                                    <th width="10%">Jam</th>`;
        }

        tableHtml += `
                                    <th width="10%">Status</th>
                                    <th width="10%">Reference</th>
                                </tr>
                                </thead>
                            <tbody>
                `;

        data.forEach((item) => {
          tableHtml += `
                        <tr>
                            <td>${item.date}</td>
                            <td>${item.description}</td>
                            `;

          if (form.is($("#form_special_office_duties"))) {
            tableHtml += `
                            <td>${item.branch_in}</td>
                            <td>${item.clock_in}</td>
                            <td>${item.branch_out}</td>
                            <td>${item.clock_out}</td>`;
          }

          tableHtml += `
                            <td>${item.isagree}</td>
                            <td>${item.reference_id}</td>
                        </tr>
          `;
        });

        tableHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

        callback(tableHtml);
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}
$("#form_assignment_realization_sup_agree").on(
  "change",
  "#branch_in, #branch_out",
  function (e) {
    let _this = $(this);
    let value = this.value;
    const form = _this.closest("form");
    let formData = new FormData();
    let url = ADMIN_URL + "Kehadiran/getJamAbsen";

    formData.append("id", ID);
    formData.append("md_branch_id", value);
    formData.append("typeform", 100008);

    if (value.length) {
      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        success: function (result) {
          let data = result.clock;
          if (_this.attr("id") == "branch_in") {
            form.find("input[name=starttime_att]").val(data.clock_in);
          } else if (_this.attr("id") == "branch_out") {
            form.find("input[name=endtime_att]").val(data.clock_out);
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    }
  }
);

_tableNotification.on(
  "click",
  ".row-notif td:nth-child(2),.row-notif td:nth-child(3),.row-notif td:nth-child(4)",
  function () {
    // Event Handler for avoiding spam click
    const clickedElement = $(this);
    if (clickedElement.data("processing")) return;
    clickedElement.data("processing", true);

    const tr = $(this).closest("tr");
    const checkbox = tr.find(".check-message");
    const id = checkbox.val();
    const isRead = checkbox.attr("data-isread");
    let formData = new FormData();
    let url = ADMIN_URL + "pesan/updateRead";
    formData.append("trx_message_id", id);

    if (isRead == "N") {
      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        complete: function () {
          Edit(id, "IP");
        },
        success: function (result) {
          if (result[0].success) reloadTable();
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    } else {
      Edit(id, "IP");
    }

    setTimeout(() => {
      clickedElement.data("processing", false);
    }, 1000);
  }
);

_tableNotification.on("click", ".check-message", function (e) {
  const card = $(e.target).closest(".card");
  const floatRight = card.find(".card-header .float-right");

  const checkedCheckboxes = _tableNotification
    .rows()
    .nodes()
    .to$()
    .find("input.check-message:checked");

  floatRight.toggleClass("d-none", checkedCheckboxes.length === 0);
});

/**
 * Delete Data for Notification
 * **/
$(".multiple-delete").on("click", function () {
  let formData = new FormData();
  let url = CURRENT_URL + DELETE;
  let row = [];

  _tableNotification
    .rows()
    .nodes()
    .to$()
    .find("input.check-message:checked")
    .each(function () {
      row.push(this.value);
    });

  formData.append("id", row);

  Swal.fire({
    title: "Delete ?",
    text: "Are you sure you wish to delete the selected data ? ",
    type: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    confirmButtonText: "Ok",
    cancelButtonText: "Close",
    reverseButtons: true,
  }).then((data) => {
    if (data.value) {
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
              title: "Deleted!",
              text: "Your data has been deleted.",
              type: "success",
              showConfirmButton: false,
              timer: 1000,
            });
            reloadTable();
          } else if (result[0].error) {
            Toast.fire({
              type: "error",
              title: "Error!",
              text: result[0].message,
              showConfirmButton: true,
            });
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    }
  });
});
function showNotifMessage() {
  let url = ADMIN_URL + "pesan/showNotif";

  $.ajax({
    url: url,
    type: "POST",
    dataType: "JSON",
    data: { type: "count" },
    success: function (response) {
      if (response > 0)
        $(".notif-message").addClass("notification").text(response);
      else $(".notif-message").removeClass("notification").text("");
    },
  });
}

$(".bell-notif").on("click", function (e) {
  let url = ADMIN_URL + "pesan/showNotif";
  const parent = $(this).parent();

  $.ajax({
    url: url,
    type: "POST",
    dataType: "JSON",
    beforeSend: function () {
      parent.find(".notif-div").removeAttr("style");
    },
    success: function (response) {
      parent.find(".dropdown-title").html(response.total);
      parent.find(".list-notif").html(response.data);
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
});

$(document).on("click", ".action-notif", function (e) {
  e.preventDefault();
  const _this = $(this);
  let record_id = _this.attr("data-url");
  let menu = "pesan";

  if (ADMIN_URL + menu == CURRENT_URL) {
    Edit(record_id, "IP");
  } else {
    let arrData = {
      record_id: record_id,
      menu: menu,
    };
    arrData = JSON.stringify(arrData);
    sessionStorage.setItem("reloading", "true");
    sessionStorage.setItem("data", arrData);

    window.open(ADMIN_URL + menu, "_self");
  }
});

$(document).ready(function (evt) {
  $("#form_proxy_special #sys_user_from")
    .on("focus", function (e) {
      prevData = this.value;
    })
    .change(function (evt) {
      const _this = $(this);
      const form = _this.closest("form");
      const field = form.find("select[name=sys_user_to]");
      const attrName = $(this).attr("name");
      let user_id = this.value;

      // create data
      if (user_id !== "" && setSave === "add") {
        field.empty();
        _tableLine.clear().draw(false);
        getUser(form, user_id, field);
        getUserRole(form, user_id);
      }

      // update data
      $.each(option, function (idx, elem) {
        if (elem.fieldName === attrName && setSave !== "add") {
          if (
            user_id !== "" &&
            user_id != elem.option_ID &&
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
                field.empty();
                _tableLine.clear().draw(false);
                getUser(form, user_id, field);
                getUserRole(form, user_id);
              } else {
                form
                  .find("select[name=" + attrName + "]")
                  .val(elem.option_ID)
                  .change();
              }
            });
          }

          if (
            user_id !== "" &&
            user_id != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            field.empty();
            getUser(form, user_id, field);
            getUserRole(form, user_id);
          }

          if (
            typeof prev !== "undefined" &&
            prev !== "" &&
            user_id !== "" &&
            prev != user_id &&
            prev != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            field.empty();
            _tableLine.clear().draw(false);
            getUser(form, user_id, field);
            getUserRole(form, user_id);
          }
        }
      });

      prevData = this.value;
    });
});

function getUser(form, id, field) {
  let formData = new FormData();
  formData.append("sys_user_id", id);

  $.ajax({
    type: "POST",
    url: ADMIN_URL + "user/getList",
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

function getUserRole(form, id) {
  let formData = new FormData();
  formData.append("sys_user_id", id);

  $.ajax({
    type: "POST",
    url: ADMIN_URL + "proxy-khusus/getUserRole",
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

$("#form_medical_certificate").on("change", "#trx_absent_id", function (e) {
  let _this = $(this);
  const form = _this.closest("form");
  let value = this.value;
  let formData = new FormData();
  const field = form.find("select[name=date]");

  formData.append("trx_absent_id", value);

  let url = ADMIN_URL + "sakit/getDetail";

  field.empty();

  if (value === "") {
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
        if (result && Object.keys(result).length > 0) {
          if (form.find("select[name=md_branch_id]").length)
            getBranch(_this, result.md_branch_id);

          if (form.find("select[name=md_division_id]").length)
            getDivision(_this, result.md_division_id);

          if (form.find("select[name=md_employee_id]").length)
            getEmployee(_this, result.md_employee_id);

          if (field.length) {
            field.append('<option value=""></option>');

            let date = null;

            if (option.length) {
              $.each(option, function (i, item) {
                if (item.fieldName == "date")
                  date = moment(item.label).format("YYYY-MM-DD");
              });
            }

            $.each(result.date, function (idx, item) {
              if (date == item.id) {
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
              } else {
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
                    '<option value="' + item.id + '">' + item.text + "</option>"
                  );
              }
            });
          }
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
});

// Function to render PDF to canvas
function renderPDF(pdfUrl, index) {
  const canvas = document.getElementById("pdf-canvas-" + index);
  const context = canvas.getContext("2d");

  pdfjsLib.getDocument(pdfUrl).promise.then((pdf) => {
    pdf.getPage(1).then((page) => {
      const scale = 1.5;
      const viewport = page.getViewport({ scale });

      canvas.height = viewport.height;
      canvas.width = viewport.width;

      page.render({ canvasContext: context, viewport });
    });
  });
}

$(document).ready(function (evt) {
  $("#form_delegation_transfer #employee_from")
    .on("focus", function (e) {
      prevData = this.value;
    })
    .change(function (evt) {
      const _this = $(this);
      const form = _this.closest("form");
      const field = form.find("select[name=employee_to]");
      const attrName = $(this).attr("name");
      let employee_id = this.value;

      // create data
      if (employee_id !== "" && setSave === "add") {
        field.empty();
        _tableLine.clear().draw(false);
        getEmployeeDetail(form, employee_id);
        getEmployeeDelegation(form, employee_id);
      }

      // update data
      $.each(option, function (idx, elem) {
        if (elem.fieldName === attrName && setSave !== "add") {
          if (
            employee_id !== "" &&
            employee_id != elem.option_ID &&
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
                field.empty();
                _tableLine.clear().draw(false);
                getEmployeeDetail(form, employee_id);
                getEmployeeDelegation(form, employee_id);
              } else {
                form
                  .find("select[name=" + attrName + "]")
                  .val(elem.option_ID)
                  .change();
              }
            });
          }

          if (
            employee_id !== "" &&
            employee_id != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            field.empty();
            getEmployeeDetail(form, employee_id);
            getEmployeeDelegation(form, employee_id);
          }

          if (
            typeof prev !== "undefined" &&
            prev !== "" &&
            employee_id !== "" &&
            prev != employee_id &&
            prev != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            field.empty();
            _tableLine.clear().draw(false);
            getEmployeeDetail(form, employee_id);
            getEmployeeDelegation(form, employee_id);
          }
        }
      });

      prevData = this.value;
    });
});

function getEmployeeDelegation(elem, id) {
  const form = elem.closest("form");
  let formData = new FormData();
  formData.append("md_employee_id", id);

  $.ajax({
    type: "POST",
    url: ADMIN_URL + "transfer-duta/getEmployeeDelegation",
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

function getEmployeeDetail(elem, employee_id) {
  const form = elem.closest("form");
  let formData = new FormData();
  formData.append("md_employee_id", employee_id);

  let url = ADMIN_URL + "karyawan/getDetail";

  if (employee_id === "") {
    if (form.find("input[name=nik]").length)
      form.find("input[name=nik]").val(null);

    if (form.find("input[name=fullname]").length)
      form.find("input[name=fullname]").val(null);

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
  }

  if (employee_id !== "")
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
          if (form.find("input[name=nik]").length)
            form.find("input[name=nik]").val(result[0].nik);

          if (form.find("input[name=fullname]").length)
            form.find("input[name=fullname]").val(result[0].fullname);

          if (form.find("select[name=md_branch_id]").length)
            getOptionBranch(form, result[0].md_branch_id);

          if (form.find("select[name=md_division_id]").length)
            getOptionDivision(form, result[0].md_division_id);
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
}

$("#form_delegation_transfer, #form_proxy_special").on(
  "change",
  "#ispermanent",
  function (e) {
    const _this = $(this);
    const target = $(e.target);
    const form = target.closest("form");

    const fields = _this
      .attr("hide-field")
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
          .val(null)
          .hide();

        if (form.find("select[name=" + fields[i] + "]").length)
          form
            .find("select[name=" + fields[i] + "]")
            .val(null)
            .change();

        if (form.find("input[name=" + fields[i] + "]").length)
          form
            .find("input[name=" + fields[i] + "]")
            .val(null)
            .change();
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
          .show();

        if (form.find("select[name=" + fields[i] + "]").length)
          form
            .find("select[name=" + fields[i] + "]")
            .val(null)
            .change();

        if (form.find("input[name=" + fields[i] + "]").length)
          form
            .find("input[name=" + fields[i] + "]")
            .val(null)
            .change();
      }
    }
  }
);

function initSelectMultipleData(
  select,
  field = null,
  id = null,
  employee_id = null
) {
  $.each(select, function (i, item) {
    let url = $(item).attr("data-url");

    let lastParam = "";

    if (url.lastIndexOf("$") != -1) {
      lastParam = url.substr(url.lastIndexOf("$") + 1);
      url = url.substr(0, url.lastIndexOf("$") - 1);
    }

    if (typeof url !== "undefined" && url !== "") {
      if (field !== null && id !== null)
        url = ADMIN_URL + url + "?" + field + "=" + id;
      else url = ADMIN_URL + url;

      $(this).select2({
        placeholder: "Select an option",
        width: "100%",
        theme: "bootstrap",
        multiple: true,
        // minimumInputLength: 3,
        ajax: {
          dataType: "JSON",
          url: function () {
            return url;
          },
          delay: 250,
          data: function (params) {
            return {
              search: params.term,
              ref_id: employee_id,
            };
          },
          processResults: function (data, page) {
            return {
              results: data,
            };
          },
          cache: true,
        },
      });
    }
  });
}

$("#form_user").on("change", "select[name=md_employee_id]", function (e) {
  let _this = $(this);
  const form = _this.closest("form");
  const field = form.find("select[name=sys_emp_delegation_id]");
  let employee_id = this.value;

  form.find("select[name=sys_emp_delegation_id]").not(".line").empty();

  initSelectMultipleData(field, null, null, employee_id);
});

$("#form_delegation_transfer").on("change", "#employee_from", function (e) {
  let _this = $(this);
  const form = _this.closest("form");
  const field = form.find("select[name=employee_to]");
  let employee_id = this.value;

  getEmployeeTo(field, employee_id);
});

function getEmployeeTo(elem, employee) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=employee_to]");
  const id = employee;

  let url = ADMIN_URL + "employee/getList";
  formData.append("ref_id", id);

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
      loadingForm(form.prop("id"), "facebook");
    },
    complete: function () {
      $(".x_form").removeAttr("disabled");
      $(".close_form").removeAttr("disabled");
      hideLoadingForm(form.prop("id"));
    },
    success: function (result) {
      if (result.length) {
        field.append('<option value=""></option>');

        let employee_to = 0;

        if (option.length) {
          $.each(option, function (i, item) {
            if (item.fieldName == "employee_to") employee_to = item.label;
          });
        }

        $.each(result, function (idx, item) {
          if (id.length == 1 || employee_to == item.id) {
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
          } else {
            if (setSave === "detail")
              field
                .append(
                  '<option value="' + item.id + '">' + item.text + "</option>"
                )
                .prop("disabled", true);
            else
              field
                .append(
                  '<option value="' + item.id + '">' + item.text + "</option>"
                )
                .removeAttr("disabled");
          }
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}
