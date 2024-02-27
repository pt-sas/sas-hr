$(document).ready(function () {
  $(".multiple-select-branch").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "branch/getList",
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

  $(".multiple-select-division").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "division/getList",
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

  $("#form_employee input[name=nik]").autocomplete({
    serviceUrl: ADMIN_URL + "karyawan/get-nik",
    dataType: "JSON",
    tabDisabled: false,
  });
});

$(".form-absent").on("change", "#md_employee_id", function (e) {
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

$(".tb_displaytab").on("change", "select[name=status]", function (e) {
  const tr = $(this).closest("tr");
  let value = this.value;

  if (value === "MENINGGAL") {
    tr.find("input[name=dateofdeath]").removeAttr("disabled");
  } else {
    tr.find("input[name=dateofdeath]").attr("disabled", true);
  }
});

$("#form_employee").on("change", "select[name=marital_status]", function (e) {
  const target = $(e.target);
  const modalTab = target.closest(".modal-tab");
  const a = modalTab.find("li>div.dropdown-menu a");
  let value = this.value.toUpperCase();
  const BELUM_KAWIN = "BELUM KAWIN";

  $.each(a, function () {
    let href = $(this).attr("href");

    if (value !== BELUM_KAWIN && href === "#keluarga")
      $(this).removeClass("d-none");

    if (value === BELUM_KAWIN && href === "#keluarga")
      $(this).addClass("d-none");
  });
});

$("#form_rule").on("change", "select[name=isdetail]", function (e) {
  const target = $(e.target);
  const modalTab = target.closest(".modal-tab");
  const a = modalTab.find("li a");
  let value = this.value;

  if (value !== "" && value === "Y")
    $.each(a, function () {
      let href = $(this).attr("href");
      const li = $(this).closest("li");

      if (li.prop("classList").contains("d-none") && href === "#rule-detail")
        li.removeClass("d-none");
    });
  else
    $.each(a, function () {
      let href = $(this).attr("href");
      const li = $(this).closest("li");

      if (href === "#rule-detail") li.addClass("d-none");
    });
});

$(".tb_displaytab").on("click", ".btn_isdetail", function (e) {
  e.preventDefault();
  const _this = $(this);
  const target = $(e.target);
  const parent = target.closest(".container");
  const modalTab = target.closest(".modal-tab");
  const modal = parent.find("#modal_rule_value");

  if (modal.length) {
    let modalID = modalTab.attr("id");
    const form = modal.find("form");
    const tabPane = modal.find(".tab-pane.active");
    const inputForeign = form.find("input.foreignkey");
    const tableTab = form.find("table.tb_displaytab");
    let href = tabPane.attr("id");
    let tableID = tableTab.attr("id");
    let id = _this.attr("id");

    ID = id;

    //TODO: Hide main modal
    $(`#${modalID}`).modal("hide");

    modalID = modal.attr("id");

    $(`#${modalID}`).modal({
      backdrop: "static",
      keyboard: false,
    });

    if (tableTab.length > 1) tableID = $(tableTab[1]).attr("id");

    if (tableTab.length) {
      _tableLine.destroy();

      _tableLine = form.find(`#${tableID}`).DataTable({
        columnDefs: [
          {
            targets: 0,
            visible: false, //hide column
          },
        ],
        lengthChange: false,
        pageLength: 10,
        searching: false,
        ordering: false,
        autoWidth: false,
      });
    }

    if (inputForeign.length) {
      inputForeign.attr("set-id", id);

      const SHOW = "/show";
      url = `${ADMIN_URL}${href}${SHOW}`;

      data = {
        [inputForeign.attr("name")]: id,
      };
    }

    if (_tableLine.context.length) _tableLine.clear().draw();

    $.ajax({
      url: url,
      type: "GET",
      data: data,
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(".save_form").prop("disabled", true);
        $(".close_rule_value").prop("disabled", true);
        loadingForm(form.prop("id"), "ios");
      },
      complete: function () {
        $(".save_form").removeAttr("disabled");
        $(".close_rule_value").removeAttr("disabled");
        hideLoadingForm(form.prop("id"));
      },
      success: function (result) {
        if (result[0].success) {
          let arrMsg = result[0].message;

          // Show datatable line
          if (arrMsg.line) {
            let arrLine = arrMsg.line;

            if (_tableLine.context.length) {
              tabPane.attr("set-save", "update");

              let line = JSON.parse(arrLine);
              _tableLine.rows.add(line).draw(false);
            }
          }

          if (inputForeign.length) {
            url = inputForeign.attr("data-url");
            showForeignKey(url, id, inputForeign);
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
});

$(".close_rule_value").on("click", function (e) {
  e.preventDefault();
  const _this = $(this);
  const target = $(e.target);
  const parent = target.closest(".container");
  const modalTab = parent.find(".modal-tab");
  const modal = target.closest(".modal");

  modal.modal("hide");

  if (modalTab.length) {
    let modalID = modalTab.attr("id");
    const form = modalTab.find("form");
    const tabPane = modalTab.find(".tab-pane.active");
    const inputForeign = form.find("input.foreignkey");
    const tableTab = form.find("table.tb_displaytab");
    let href = tabPane.attr("id");
    let tableID = tableTab.attr("id");
    let id = inputForeign.attr("set-id");

    ID = id;

    $(`#${modalID}`).modal({
      backdrop: "static",
      keyboard: false,
    });

    if (tableTab.length > 1) tableID = $(tableTab[1]).attr("id");

    if (tableTab.length) {
      _tableLine.destroy();

      _tableLine = form.find(`#${tableID}`).DataTable({
        drawCallback: function (settings) {
          if ($(this).find(".select2").length)
            $(this)
              .find(".number")
              .on("keypress keyup blur", function (evt) {
                $(this).val(
                  $(this)
                    .val()
                    .replace(/[^\d-].+/, "")
                );
                if (
                  (evt.which < 48 && evt.which != 45) ||
                  (evt.which > 57 && evt.which != 189)
                ) {
                  evt.preventDefault();
                }
              });

          if ($(this).find(".select2").length)
            $(this).find(".select2").select2({
              placeholder: "Select an option",
              theme: "bootstrap",
              allowClear: true,
            });

          if ($(this).find(".datepicker").length)
            $(this).find(".datepicker").datepicker({
              format: "dd-M-yyyy",
              autoclose: true,
              clearBtn: true,
              todayHighlight: true,
              todayBtn: true,
            });

          if ($(this).find(".yearpicker").length)
            $(this).find(".yearpicker").datepicker({
              format: "yyyy",
              viewMode: "years",
              minViewMode: "years",
              autoclose: true,
              clearBtn: true,
            });
        },
        columnDefs: [
          {
            targets: 0,
            visible: false, //hide column
          },
        ],
        lengthChange: false,
        pageLength: 10,
        searching: false,
        ordering: false,
        autoWidth: false,
        scrollX: true,
        scrollY: "50vh",
        scrollCollapse: true,
      });
    }

    if (inputForeign.length) {
      inputForeign.attr("set-id", id);

      const SHOW = "/show";
      url = `${ADMIN_URL}${href}${SHOW}`;

      data = {
        [inputForeign.attr("name")]: id,
      };
    }

    if (_tableLine.context.length) _tableLine.clear().draw();

    $.ajax({
      url: url,
      type: "GET",
      data: data,
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(".save_form").prop("disabled", true);
        $(".close_form").prop("disabled", true);
        loadingForm(form.prop("id"), "ios");
      },
      complete: function () {
        $(".save_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm(form.prop("id"));
      },
      success: function (result) {
        if (result[0].success) {
          let arrMsg = result[0].message;
          // Show datatable line
          if (arrMsg.line) {
            let arrLine = arrMsg.line;

            if (_tableLine.context.length) {
              let line = JSON.parse(arrLine);
              _tableLine.rows.add(line).draw(false);
            }
          }

          if (inputForeign.length) {
            url = inputForeign.attr("data-url");
            showForeignKey(url, id, inputForeign);
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
});

/**
 * Event Listener Responsible Type
 */
$("#form_responsible").on(
  "change",
  "select[name=responsibletype]",
  function (evt) {
    const target = $(evt.target);
    const form = target.closest("form");
    const value = this.value;

    //? Condition field and contain attribute hide-field
    if ($(this).attr("hide-field")) {
      if (value === "R") {
        form.find("select[name=sys_role_id]").closest(".form-group").show();
      } else {
        form.find("select[name=sys_role_id]").closest(".form-group").hide();
      }

      if (value === "U") {
        form.find("select[name=sys_user_id]").closest(".form-group").show();
      } else {
        form.find("select[name=sys_user_id]").closest(".form-group").hide();
      }
    }
  }
);

$("#form_employee").on(
  "change",
  "select[name=md_country_id], select[name=md_country_dom_id]",
  function (e) {
    const _this = $(this);
    const target = $(e.target);
    const form = target.closest("form");
    let value = this.value;
    let formData = new FormData();

    let field = form.find("select[name=md_province_id]");

    if (_this.attr("name") === "md_country_dom_id") {
      field = form.find("select[name=md_province_dom_id]");
      form
        .find(
          "select[name=md_city_dom_id], select[name=md_district_dom_id], select[name=md_subdistrict_dom_id]"
        )
        .empty();
    } else {
      form
        .find(
          "select[name=md_city_id], select[name=md_district_id], select[name=md_subdistrict_id]"
        )
        .empty();
    }

    let url = ADMIN_URL + "province/getList";

    field.empty();

    if (value) {
      formData.append("md_country_id", value);

      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        beforeSend: function () {
          $(".save_form").prop("disabled", true);
          $(".close_form").prop("disabled", true);
          loadingForm(form.prop("id"), "ios");
        },
        complete: function () {
          $(".save_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        success: function (result) {
          if (result.length) {
            field.append('<option value=""></option>');

            let md_province_id = 0,
              md_province_dom_id = 0;

            if (option.length) {
              let index = 0;

              $.each(option, function (i, item) {
                if (
                  ((_this.attr("name") === "md_country_id" &&
                    item.fieldName === "md_country_id") ||
                    (_this.attr("name") === "md_country_dom_id" &&
                      item.fieldName === "md_country_dom_id")) &&
                  item.option_ID != value
                )
                  index = option.findIndex(
                    (item) =>
                      (_this.attr("name") === "md_country_id" &&
                        item.fieldName === "md_province_id") ||
                      (_this.attr("name") === "md_country_dom_id" &&
                        item.fieldName === "md_province_dom_id")
                  );
              });

              if (index != 0) option.splice(index, 1);

              $.each(option, function (i, item) {
                if (item.fieldName === "md_province_id")
                  md_province_id = item.label;

                if (item.fieldName === "md_province_dom_id")
                  md_province_dom_id = item.label;
              });
            }

            if (!result[0].error) {
              $.each(result, function (idx, item) {
                if (
                  (_this.attr("name") === "md_country_id" &&
                    md_province_id == item.id) ||
                  (_this.attr("name") === "md_country_dom_id" &&
                    md_province_dom_id == item.id)
                ) {
                  field.append(
                    '<option value="' +
                      item.id +
                      '" selected>' +
                      item.text +
                      "</option>"
                  );
                } else {
                  field.append(
                    '<option value="' + item.id + '">' + item.text + "</option>"
                  );
                }
              });
            } else {
              Swal.fire({
                type: "error",
                title: result[0].message,
                showConfirmButton: false,
                timer: 1500,
              });
            }
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    } else {
      option.splice(
        option.findIndex(
          (item) =>
            (_this.attr("name") === "md_country_id" &&
              item.fieldName === "md_province_id") ||
            (_this.attr("name") === "md_country_dom_id" &&
              item.fieldName === "md_province_dom_id")
        ),
        1
      );
    }
  }
);

$("#form_employee").on(
  "change",
  "select[name=md_province_id], select[name=md_province_dom_id]",
  function (e) {
    const _this = $(this);
    const target = $(e.target);
    const form = target.closest("form");
    let value = this.value;
    let formData = new FormData();

    let field = form.find("select[name=md_city_id]");

    if (_this.attr("name") === "md_province_dom_id") {
      field = form.find("select[name=md_city_dom_id]");
      form
        .find(
          "select[name=md_district_dom_id], select[name=md_subdistrict_dom_id]"
        )
        .empty();
    } else {
      form
        .find("select[name=md_district_id], select[name=md_subdistrict_id]")
        .empty();
    }

    let url = ADMIN_URL + "city/getList";

    field.empty();

    if (option.length) {
      $.each(option, function (i, item) {
        if (typeof _this.find(":selected").val() === "undefined") {
          if (
            (_this.attr("name") === "md_province_id" &&
              item.fieldName === "md_province_id") ||
            (_this.attr("name") === "md_province_dom_id" &&
              item.fieldName === "md_province_dom_id")
          )
            value = item.label;
        }
      });
    }

    if (value) {
      formData.append("md_province_id", value);

      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        beforeSend: function () {
          $(".save_form").prop("disabled", true);
          $(".close_form").prop("disabled", true);
          loadingForm(form.prop("id"), "ios");
        },
        complete: function () {
          $(".save_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        success: function (result) {
          if (result.length) {
            field.append('<option value=""></option>');

            let md_city_id = 0,
              md_city_dom_id = 0;

            if (option.length) {
              let index = 0;

              $.each(option, function (i, item) {
                if (
                  ((_this.attr("name") === "md_province_id" &&
                    item.fieldName === "md_province_id") ||
                    (_this.attr("name") === "md_province_dom_id" &&
                      item.fieldName === "md_province_dom_id")) &&
                  item.label != value
                )
                  index = option.findIndex(
                    (item) =>
                      (_this.attr("name") === "md_province_id" &&
                        item.fieldName === "md_city_id") ||
                      (_this.attr("name") === "md_province_dom_id" &&
                        item.fieldName === "md_city_dom_id")
                  );
              });

              if (index != 0) option.splice(index, 1);

              $.each(option, function (i, item) {
                if (item.fieldName === "md_city_id") md_city_id = item.label;

                if (item.fieldName === "md_city_dom_id")
                  md_city_dom_id = item.label;
              });
            }

            if (!result[0].error) {
              $.each(result, function (idx, item) {
                if (
                  (_this.attr("name") === "md_province_id" &&
                    md_city_id == item.id) ||
                  (_this.attr("name") === "md_province_dom_id" &&
                    md_city_dom_id == item.id)
                ) {
                  field.append(
                    '<option value="' +
                      item.id +
                      '" selected>' +
                      item.text +
                      "</option>"
                  );
                } else {
                  field.append(
                    '<option value="' + item.id + '">' + item.text + "</option>"
                  );
                }
              });
            } else {
              Swal.fire({
                type: "error",
                title: result[0].message,
                showConfirmButton: false,
                timer: 1500,
              });
            }
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    } else {
      option.splice(
        option.findIndex(
          (item) =>
            (_this.attr("name") === "md_province_id" &&
              item.fieldName === "md_city_id") ||
            (_this.attr("name") === "md_province_dom_id" &&
              item.fieldName === "md_city_dom_id")
        ),
        1
      );
    }
  }
);

$("#form_employee").on(
  "change",
  "select[name=md_city_id], select[name=md_city_dom_id]",
  function (e) {
    const _this = $(this);
    const target = $(e.target);
    const form = target.closest("form");
    let value = this.value;
    let formData = new FormData();

    let field = form.find("select[name=md_district_id]");

    if (_this.attr("name") === "md_city_dom_id") {
      field = form.find("select[name=md_district_dom_id]");
      form.find("select[name=md_subdistrict_dom_id]").empty();
    } else {
      form.find("select[name=md_subdistrict_id]").empty();
    }

    let url = ADMIN_URL + "district/getList";

    field.empty();

    if (option.length) {
      $.each(option, function (i, item) {
        if (typeof _this.find(":selected").val() === "undefined") {
          if (
            (_this.attr("name") === "md_city_id" &&
              item.fieldName === "md_city_id") ||
            (_this.attr("name") === "md_city_dom_id" &&
              item.fieldName === "md_city_dom_id")
          )
            value = item.label;
        }
      });
    }

    if (value) {
      formData.append("md_city_id", value);

      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        beforeSend: function () {
          $(".save_form").prop("disabled", true);
          $(".close_form").prop("disabled", true);
          loadingForm(form.prop("id"), "ios");
        },
        complete: function () {
          $(".save_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        success: function (result) {
          if (result.length) {
            field.append('<option value=""></option>');

            let md_district_id = 0,
              md_district_dom_id = 0;

            if (option.length) {
              let index = 0;

              $.each(option, function (i, item) {
                if (
                  ((_this.attr("name") === "md_city_id" &&
                    item.fieldName === "md_city_id") ||
                    (_this.attr("name") === "md_city_dom_id" &&
                      item.fieldName === "md_city_dom_id")) &&
                  item.label != value
                )
                  index = option.findIndex(
                    (item) =>
                      (_this.attr("name") === "md_city_id" &&
                        item.fieldName === "md_district_id") ||
                      (_this.attr("name") === "md_city_dom_id" &&
                        item.fieldName === "md_district_dom_id")
                  );
              });

              if (index != 0) option.splice(index, 1);

              $.each(option, function (i, item) {
                if (item.fieldName === "md_district_id")
                  md_district_id = item.label;

                if (item.fieldName === "md_district_dom_id")
                  md_district_dom_id = item.label;
              });
            }

            if (!result[0].error) {
              $.each(result, function (idx, item) {
                if (
                  (_this.attr("name") === "md_city_id" &&
                    md_district_id == item.id) ||
                  (_this.attr("name") === "md_city_dom_id" &&
                    md_district_dom_id == item.id)
                ) {
                  field.append(
                    '<option value="' +
                      item.id +
                      '" selected>' +
                      item.text +
                      "</option>"
                  );
                } else {
                  field.append(
                    '<option value="' + item.id + '">' + item.text + "</option>"
                  );
                }
              });
            } else {
              Swal.fire({
                type: "error",
                title: result[0].message,
                showConfirmButton: false,
                timer: 1500,
              });
            }
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    } else {
      option.splice(
        option.findIndex(
          (item) =>
            (_this.attr("name") === "md_city_id" &&
              item.fieldName === "md_district_id") ||
            (_this.attr("name") === "md_city_dom_id" &&
              item.fieldName === "md_district_dom_id")
        ),
        1
      );
    }
  }
);

$("#form_employee").on(
  "change",
  "select[name=md_district_id], select[name=md_district_dom_id]",
  function (e) {
    const _this = $(this);
    const target = $(e.target);
    const form = target.closest("form");
    let value = this.value;
    let formData = new FormData();

    let field = form.find("select[name=md_subdistrict_id]");

    if (_this.attr("name") === "md_district_dom_id")
      field = form.find("select[name=md_subdistrict_dom_id]");

    let url = ADMIN_URL + "subdistrict/getList";

    field.empty();

    if (option.length) {
      $.each(option, function (i, item) {
        if (typeof _this.find(":selected").val() === "undefined") {
          if (
            (_this.attr("name") === "md_district_id" &&
              item.fieldName === "md_district_id") ||
            (_this.attr("name") === "md_district_dom_id" &&
              item.fieldName === "md_district_dom_id")
          )
            value = item.label;
        }
      });
    }

    if (value) {
      formData.append("md_district_id", value);

      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        beforeSend: function () {
          $(".save_form").prop("disabled", true);
          $(".close_form").prop("disabled", true);
          loadingForm(form.prop("id"), "ios");
        },
        complete: function () {
          $(".save_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        success: function (result) {
          if (result.length) {
            field.append('<option value=""></option>');

            let md_subdistrict_id = 0,
              md_subdistrict_dom_id = 0;

            if (option.length) {
              let index = 0;

              $.each(option, function (i, item) {
                if (
                  ((_this.attr("name") === "md_district_id" &&
                    item.fieldName === "md_district_id") ||
                    (_this.attr("name") === "md_district_dom_id" &&
                      item.fieldName === "md_district_dom_id")) &&
                  item.label != value
                )
                  index = option.findIndex(
                    (item) =>
                      (_this.attr("name") === "md_district_id" &&
                        item.fieldName === "md_subdistrict_id") ||
                      (_this.attr("name") === "md_district_dom_id" &&
                        item.fieldName === "md_subdistrict_dom_id")
                  );
              });

              if (index != 0) option.splice(index, 1);

              $.each(option, function (i, item) {
                if (item.fieldName === "md_subdistrict_id")
                  md_subdistrict_id = item.label;

                if (item.fieldName === "md_subdistrict_dom_id")
                  md_subdistrict_dom_id = item.label;
              });
            }

            if (!result[0].error) {
              $.each(result, function (idx, item) {
                if (
                  (_this.attr("name") === "md_district_id" &&
                    md_subdistrict_id == item.id) ||
                  (_this.attr("name") === "md_district_dom_id" &&
                    md_subdistrict_dom_id == item.id)
                ) {
                  field.append(
                    '<option value="' +
                      item.id +
                      '" selected>' +
                      item.text +
                      "</option>"
                  );
                } else {
                  field.append(
                    '<option value="' + item.id + '">' + item.text + "</option>"
                  );
                }
              });
            } else {
              Swal.fire({
                type: "error",
                title: result[0].message,
                showConfirmButton: false,
                timer: 1500,
              });
            }
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    } else {
      option.splice(
        option.findIndex(
          (item) =>
            (_this.attr("name") === "md_district_id" &&
              item.fieldName === "md_subdistrict_id") ||
            (_this.attr("name") === "md_district_dom_id" &&
              item.fieldName === "md_subdistrict_dom_id")
        ),
        1
      );
    }
  }
);

$("#form_employee").on("change", "input[name=issameaddress]", function (e) {
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
        .hide();
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
    }
  }
});

_table.on("click", "a.popup-image", function (e) {
  e.preventDefault();
  const _this = $(this);
  const parent = _this.closest(".container");
  const main_page = parent.find(".main_page");
  const modalImg = $("#modal_image_info");
  let s = parent.find(".card");

  if (s.length > 1) s = parent.find(".page-inner");
  else s = main_page.find(".card");

  s.length &&
    (s.addClass("is-loading"),
    setTimeout(function () {
      modalImg.modal({
        backdrop: "static",
        keyboard: false,
      });

      modalImg.find(".modal-title").html(_this.attr("value"));
      modalImg.find(".imagepreview").attr("src", _this.find("img").attr("src"));
      s.removeClass("is-loading");
    }, 200));
});
