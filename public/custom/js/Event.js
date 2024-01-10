$(document).ready(function () {
  $(".multiple-select-employee").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "karyawan/getList",
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
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
});

$("#md_employee_id").change(function (e) {
  let _this = $(this);
  const form = _this.closest("form");
  let value = this.value;
  let formData = new FormData();

  formData.append("md_employee_id", value);

  let url = ADMIN_URL + "karyawan/getDetail";

  if (value === "") {
    if (form.find("input[name=nik]").length)
      form.find("input[name=nik]").val(null);

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
        if (result.length) {
          if (form.find("input[name=nik]").length)
            form.find("input[name=nik]").val(result[0].nik);

          if (form.find("select[name=md_branch_id]").length)
            getOptionBranch(_this, result[0].md_branch_id);

          if (form.find("select[name=md_division_id]").length)
            getOptionDivision(_this, result[0].md_division_id);
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
});

function getOptionBranch(elem, branch) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_branch_id]");

  const id = branch.id;

  let url = ADMIN_URL + "branch/getList";
  formData.append("md_branch_id", branch.id);

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

        let md_branch_id = 0;

        if (option.length) {
          $.each(option, function (i, item) {
            if (item.fieldName == "md_branch_id") md_branch_id = item.label;
          });
        }

        $.each(result, function (idx, item) {
          if (id.length == 1 || md_branch_id == item.id) {
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

function getOptionDivision(elem, division) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_division_id]");
  const id = division.id;

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

        let md_division_id = 0;

        if (option.length) {
          $.each(option, function (i, item) {
            if (item.fieldName == "md_division_id") md_division_id = item.label;
          });
        }

        $.each(result, function (idx, item) {
          if (id.length == 1 || md_division_id == item.id) {
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
