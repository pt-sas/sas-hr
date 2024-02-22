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

// $("#form_overtime").on(
//   "change",
//   "select[name=md_branch_id], select[name=md_division_id]",
//   function () {
//     let _this = $(this);
//     const form = _this.closest("form");
//     let value = this.value;
//     let formData = new FormData();

//     formData.append(this.name, value);

//     if (this.name === "md_division_id")
//       formData.append("md_branch_id", form.find("[name=md_branch_id]").val());

//     if (this.name === "md_branch_id")
//       formData.append(
//         "md_division_id",
//         form.find("[name=md_division_id]").val()
//       );

//   }
// );

function destroyAllLine(field, id) {
  let url = CURRENT_URL + "/destroyAllLine";

  $.ajax({
    url: url,
    type: "POST",
    data: {
      trx_overtime_id: id,
    },
    cache: false,
    dataType: "JSON",
    success: function (result) {
      console.log(result);
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}
