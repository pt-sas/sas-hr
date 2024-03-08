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

    formData.append(this.name, value);

    if (this.name === "md_leavetype_id")
      formData.append("startdate", form.find("[name=startdate]").val());

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
        console.log(result);
        if (form.find("[name=startdate]").val() !== "") {
          form.find("[name=enddate]").val(moment(result).format("DD-MMM-Y"));
        } else {
        }
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

function Generate(id) {
  let formData = new FormData();
  let url = ADMIN_URL + "alpa/generate"
  let ID = id;
  formData.append('trx_attendance_id', ID);

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
  })

}
