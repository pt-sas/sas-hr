$(document).ready(function () {
  $(".multiple-select-branch").select2({
    placeholder: "Pilih opsi cabang",
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
    placeholder: "Pilih opsi divisi",
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

  $(".select-submissiontype").select2({
    placeholder: "Pilih opsi tipe form",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "document-type/getList",
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

  $(".multiple-select-submissiontype").select2({
    placeholder: "Pilih opsi tipe form",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "document-type/getList",
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

  $(".multiple-select-realizationtype").select2({
    placeholder: "Pilih opsi tipe form",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "document-type/getList?isinternal=Y",
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

  if ($(this).find("#form_absent_manual").is($("#form_absent_manual"))) {
    initSelectData($(this).find("select.select-data"));
  }
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

    if (form.find("select[name=md_levelling_id]").length)
      form
        .find("select[name=md_levelling_id]")
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
        console.log(result);
        if (result.length) {
          if (form.find("input[name=nik]").length)
            form.find("input[name=nik]").val(result[0].nik);

          if (form.find("input[name=fullname]").length)
            form.find("input[name=fullname]").val(result[0].fullname);

          if (form.find("select[name=md_branch_id]").length)
            getOptionBranch(_this, result[0].md_branch_id);

          if (form.find("select[name=md_division_id]").length)
            getOptionDivision(_this, result[0].md_division_id);

          if (form.find("select[name=md_levelling_id]").length)
            getOptionLevelling(_this, result[0].md_levelling_id);

          if (form.find("select[name=md_position_id]").length)
            getPosition(_this, result[0].md_position_id);

          if (form.find("input[name=registerdate]").length)
            form
              .find("input[name=registerdate]")
              .val(moment(result[0].registerdate).format("DD-MMM-Y"));

          if (form.find(".datepicker-start").length) {
            form.find(".datepicker-start").data("DateTimePicker").destroy();

            form.find(".datepicker-start").datetimepicker({
              format: "DD-MMM-YYYY",
              showTodayButton: true,
              showClear: true,
              showClose: true,
              daysOfWeekDisabled: getDaysOfWeek(value),
              disabledDates: getHolidayDate(),
              useCurrent: false,
            });
          }

          if (form.find(".datepicker-end").length) {
            form.find(".datepicker-end").data("DateTimePicker").destroy();

            form.find(".datepicker-end").datetimepicker({
              format: "DD-MMM-YYYY",
              showTodayButton: true,
              showClear: true,
              showClose: true,
              daysOfWeekDisabled: getDaysOfWeek(value),
              disabledDates: getHolidayDate(),
              useCurrent: false,
            });
          }
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

        if (form.is($("#form_office_duties"))) {
          getOptionBranchTo(elem);
        }
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}

function getOptionBranchTo(elem) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=branch_to]");
  let branch_id = form.find("select[name=md_branch_id]").val();

  let url = ADMIN_URL + "branch/getList";
  formData.append("md_branch_id", branch_id);
  formData.append("isbranch", "Y");

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

        let branch_to = 0;

        if (option.length) {
          $.each(option, function (i, item) {
            if (item.fieldName == "branch_to") branch_to = item.label;
          });
        }

        $.each(result, function (idx, item) {
          if (branch_id.length == 1 || branch_to == item.id) {
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

function getOptionLevelling(elem, level) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_levelling_id]");
  const id = level;

  let url = ADMIN_URL + "levelling/getList";
  formData.append("md_levelling_id", id);

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

$(".tb_displaytab").on(
  "click",
  ".btn_isdetail, .btn_isdetailbenefit",
  function (e) {
    e.preventDefault();
    const _this = $(this);
    const target = $(e.target);
    const parent = target.closest(".container");
    const modalTab = target.closest(".modal-tab");

    let modal;

    if (_this.hasClass("btn_isdetailbenefit")) {
      modal = parent.find("#modal_benefit_detail");
    } else {
      modal = parent.find("#modal_rule_value");
    }

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
  }
);

$(".close_rule_value, .close_benefit_detail").on("click", function (e) {
  e.preventDefault();
  const _this = $(this);
  const target = $(e.target);
  const parent = target.closest(".container");
  const modalTab = parent.find(".modal-tab");
  const modal = target.closest(".modal");

  modal.modal("hide");

  if (modalTab.length) {
    let modalID = modalTab.attr("id");
    const tabPane = modalTab.find(".tab-pane.active");
    const form = tabPane.find("form");
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

$("#form_employee, #form_outsourcing").on(
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

$("#form_employee, #form_outsourcing").on(
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

$("#form_employee, #form_outsourcing").on(
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

$("#form_employee, #form_outsourcing").on(
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

$("#form_employee, #form_outsourcing").on(
  "change",
  "input[name=issameaddress]",
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
  }
);

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

$("#modal_image_info").on("click", ".btn_download_img", function (e) {
  const _this = $(this);
  const parent = _this.closest(".modal");
  const modalBody = parent.find(".modal-body");
  let url = modalBody.find("img").attr("src");

  loadingForm(modalBody.attr("id"), "facebook"),
    setTimeout(function () {
      downloadFile(url);
      hideLoadingForm(modalBody.attr("id"));
    }, 200);
});

/**
 * Field All Checkbox Realize
 */
$(".ischeckall-realize").click(function (e) {
  const target = $(e.target);
  const card = target.closest(".card");
  const cardHeader = card.find(".card-header");
  const floatRight = cardHeader.find(".float-right");
  const checkbox = _tableReport
    .rows()
    .nodes()
    .to$()
    .find("input.check-realize");

  if (this.checked) {
    checkbox.prop("checked", true);

    if (_tableReport.data().any()) floatRight.removeClass("d-none");
  } else {
    checkbox.prop("checked", false);

    floatRight.addClass("d-none");
  }
});

/**
 * Field Checkbox Realize
 */
_tableReport.on("click", ".check-realize", function (e) {
  const target = $(e.target);
  const card = target.closest(".card");
  const cardHeader = card.find(".card-header");
  const floatRight = cardHeader.find(".float-right");
  const checkbox = _tableReport
    .rows()
    .nodes()
    .to$()
    .find("input.check-realize");

  //* Checked checkbox
  if ($(this).is(":checked")) {
    let noChkdData = [];

    $.each(checkbox, function (idx, item) {
      if (!$(item).is(":checked")) noChkdData.push(item.value);
    });

    floatRight.removeClass("d-none");

    if (noChkdData.length == 0) $(".ischeckall-realize").prop("checked", true);
  } else {
    let chkdData = [];

    $.each(checkbox, function (idx, item) {
      if ($(item).is(":checked")) chkdData.push(item.value);
    });

    if (chkdData.length == 0) floatRight.addClass("d-none");

    $(".ischeckall-realize").prop("checked", false);
  }
});

/**
 * Event Click Button Filter Realize
 */
$(".btn_filter_realize").on("click", function (e) {
  const target = $(e.target);
  const pageInner = target.closest(".page-inner");
  const card = target.closest(".card");
  const form = card.find("form");
  const disabled = form.find("[disabled]");
  const cardHeader = pageInner.find(".card-header");
  const floatRight = cardHeader.find(".float-right");
  const checkAll = $(".ischeckall-realize");

  //! Remove attribute disabled field
  disabled.removeAttr("disabled");

  //TODO: Collect field array
  let field = form
    .find("input, select")
    .map(function () {
      if (typeof $(this).attr("name") !== "undefined") {
        let row = {};

        row["name"] = $(this).attr("name");

        if (this.type !== "checkbox" && this.type !== "select-multiple")
          row["value"] = this.value;
        else if (this.type === "select-multiple") row["value"] = $(this).val();
        else row["value"] = this.checked ? "Y" : "N";

        row["type"] = this.type;

        return row;
      }
    })
    .get();

  formReport = field;

  //! Set attribute disabled field
  disabled.prop("disabled", true);

  //TODO: Loading and processing
  pageInner.length &&
    (pageInner.addClass("is-loading"),
    reloadTable(),
    setTimeout(function () {
      checkAll.prop("checked", false);
      if (pageInner.find(".table_report").length) floatRight.addClass("d-none");
      pageInner.removeClass("is-loading");
    }, 700));
});

_tableRealization.on("click", ".btn_agree, .btn_not_agree", function (e) {
  const tr = $(this).closest("tr");
  let formType;
  let submissionDate;
  let description;
  let startdate;
  let enddate;
  let starttime;
  let endtime;
  let date_out;
  let clock_out;
  let id = this.id;
  let leaveTypeID = 0;

  const form = $(
    "#form_realization_agree, #form_overtime_realization_agree, #form_attendance_realization_agree"
  );

  if (form.is($("#form_realization_agree"))) {
    formType = tr.find("td:eq(2)").text();
    submissionDate = tr.find("td:eq(1)").text();
    description = tr.find("td:eq(6)");
    if (description.find("span").length)
      leaveTypeID = description.find("span").attr("id");
  } else if (form.is($("#form_overtime_realization_agree"))) {
    startdate = tr.find("td:eq(5)").text();
    enddate = tr.find("td:eq(6)").text();
    starttime = tr.find("td:eq(7)").text();
    endtime = tr.find("td:eq(8)").text();
    date_out = tr.find("td:eq(9)").text();
    clock_out = tr.find("td:eq(10)").text();
  } else if (form.is($("#form_attendance_realization_agree"))) {
    submissionDate = tr.find("td:eq(1)").text();
    date_out = tr.find("td:eq(8)").text();
    clock_out = tr.find("td:eq(9)").text();
  }

  if (this.name === "agree") {
    $(
      "#modal_realization_agree, #modal_overtime_realization_agree, #modal_attendance_realization_agree"
    ).modal({
      backdrop: "static",
      keyboard: false,
    });

    if (form.is($("#form_realization_agree"))) {
      form.find("input[name=submissiondate]").val(submissionDate);
      form.find("input[name=isagree]").val("Y");
      form.find("input[name=md_leavetype_id]").val(leaveTypeID);
      ID = id;
    } else if (form.is($("#form_overtime_realization_agree"))) {
      form.find("input[name=enddate]").val(enddate);
      form.find("input[name=enddate_realization]").val(enddate);
      form.find("input[name=startdate]").val(startdate);
      form.find("input[name=starttime]").val(starttime);
      form.find("input[name=endtime]").val(endtime);
      form.find("input[name=enddate_att]").val(date_out);
      form.find("input[name=endtime_att]").val(clock_out);
      form.find("input[name=isagree]").val("Y");
      ID = id;
    } else if (form.is($("#form_attendance_realization_agree"))) {
      form.find("input[name=submissiondate]").val(submissionDate);
      form.find("input[name=isagree]").val("Y");
      form.find("input[name=enddate_att]").val(date_out);
      form.find("input[name=endtime_att]").val(clock_out);
      form.find("input[name=enddate_realization]").val(submissionDate);
      ID = id;

      if (date_out !== "" || clock_out !== "") {
        form
          .find("input[name=endtime_realization]")
          .val(clock_out)
          .change()
          .prop("disabled", true);
      } else if (date_out === "" || clock_out === "") {
        form
          .find("input[name=endtime_realization]")
          .val(null)
          .change()
          .prop("disabled", false);
      }
    }
  } else {
    $(
      "#modal_realization_not_agree, #modal_overtime_realization_not_agree"
    ).modal({
      backdrop: "static",
      keyboard: false,
    });

    const form = $(
      "#form_realization_not_agree, #form_overtime_realization_not_agree"
    );

    if (form.is($("#form_realization_not_agree"))) {
      formType = tr.find("td:eq(2)").text();
      submissionDate = tr.find("td:eq(1)").text();
      description = tr.find("td:eq(6)");
      if (description.find("span").length)
        leaveTypeID = description.find("span").attr("id");
    }

    if (form.is($("#form_realization_not_agree"))) {
      form.find("input[name=submissiondate]").val(submissionDate);
      form.find("input[name=isagree]").val("N");
      form.find("input[name=foreignkey]").val(id);
      form.find("input[name=md_leavetype_id]").val(leaveTypeID);

      if (form.find("select.select-data").length) {
        form
          .find("select.select-data")
          .attr("data-url", "realisasi/getList/$" + formType);

        initSelectData(form.find("select.select-data"));
      }
    } else if (form.is($("#form_overtime_realization_not_agree"))) {
      form.find("input[name=isagree]").val("N");
      ID = id;
    }
  }
});

$(".btn_ok_realization").click(function (e) {
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

  url = `${SITE_URL}${CREATE}`;

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
      $(".close").prop("disabled", true);
      $(".btn_close_realization").prop("disabled", true);
      loadingForm(form.prop("id"), "facebook");
    },
    complete: function () {
      _this.removeAttr("disabled");
      $(".close").removeAttr("disabled");
      $(".btn_close_realization").removeAttr("disabled");
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

$(".btn_close_realization").click(function (e) {
  e.preventDefault();
  const parent = $(this).closest(".modal");
  const form = parent.find("form");

  clearForm(e);
  clearErrorForm(form);
  reloadTable();
});

$(".datepicker-year").datepicker({
  format: "M-yyyy",
  startView: "months",
  minViewMode: "months",
  autoclose: true,
  clearBtn: true,
});

$(".yearpicker").datepicker({
  format: "yyyy",
  startView: "years",
  minViewMode: "years",
  autoclose: true,
});

$("#form_official_permission").on("change", "#md_employee_id", function (e) {
  let _this = $(this);
  const target = $(e.target);
  const form = _this.closest("form");
  let value = this.value;
  let formData = new FormData();

  let field = form.find("select[name=md_leavetype_id]");

  let url = ADMIN_URL + "leavetype/getList";

  field.empty();

  if (value) {
    formData.append("md_employee_id", value);

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

          let md_leavetype_id = 0;

          if (option.length) {
            let index = 0;

            $.each(option, function (i, item) {
              if (
                _this.attr("name") === "md_employee_id" &&
                item.fieldName === "md_employee_id" &&
                item.option_ID != value
              )
                index = option.findIndex(
                  (item) =>
                    _this.attr("name") === "md_employee_id" &&
                    item.fieldName === "md_leavetype_id"
                );
            });

            if (index != 0) option.splice(index, 1);

            $.each(option, function (i, item) {
              if (item.fieldName === "md_leavetype_id")
                md_leavetype_id = item.label;
            });
          }

          if (!result[0].error) {
            $.each(result, function (idx, item) {
              if (md_leavetype_id == item.id) {
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
    form.find("input[name=startdate], input[name=enddate]").val(null);
  }
});

/**
 * Event Click Button OK Anulir
 */
$(".btn_ok_anulir").on("click", function (evt) {
  const target = $(evt.target);
  const pageInner = target.closest(".page-inner");
  const card = target.closest(".card");
  const form = card.find("form");

  let _this = $(this);
  let oriElement = _this.html();
  let formData = new FormData();

  const field = form.find("input, select");

  //? Populate value
  for (let i = 0; i < field.length; i++) {
    if (field[i].name !== "") {
      formData.append(field[i].name, field[i].value);
    }
  }

  let url = CURRENT_URL + CREATE;

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      loadingForm(form.prop("id"), "facebook");
      $(_this)
        .html(
          '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
        )
        .prop("disabled", true);
    },
    complete: function () {
      hideLoadingForm(form.prop("id"));
      $(_this).html(oriElement).prop("disabled", false);
    },
    success: function (result) {
      if (result[0].success) {
        Toast.fire({
          type: "success",
          title: result[0].message,
        });
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

_tableRealization.on("click", ".btn_view_image", function (e) {
  const tr = $(this).closest("tr");
  let submissionDate = tr.find("td:eq(1)").text();
  let formType = tr.find("td:eq(2)").text();
  let employee = tr.find("td:eq(5)").text();
  let id = this.id;

  const modal = $("#modal_image_slide");
  const modalBody = modal.find(".modal-body");
  let url = CURRENT_URL + "/show-image/" + id;

  modal.modal({
    backdrop: "static",
    keyboard: false,
  });

  let title = `${employee} / ${submissionDate} / ${formType}`;

  modal.find(".modal-title").html(title);

  $.ajax({
    url: url,
    type: "GET",
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      loadingForm(modalBody.prop("id"), "facebook");
    },
    complete: function () {
      hideLoadingForm(modalBody.prop("id"));
    },
    success: function (result) {
      if (result.length) {
        let html =
          '<div class="owl-carousel owl-theme owl-img-responsive image-carousel">';

        $.each(result, function (i, item) {
          html += '<div class="item">';
          html += '<img class="img-thumbnail" src="' + item + '">';
          html += "</div>";
        });
        html += "</div>";

        modalBody.html(html);

        $(".image-carousel").owlCarousel({
          nav: true, // Show next and prev buttons
          autoplaySpeed: 300,
          navSpeed: 400,
          items: 1,
        });
      }
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
});

$(".form-half-day").on(
  "change dp.change",
  "#nik, #startdate, #submissiondate",
  function (e) {
    let _this = $(this);
    const form = _this.closest("form");
    let formData = new FormData();
    let nik = $("#nik").val();
    let startdate = $("#startdate").val();
    let typeForm;

    if (form.is($("#form_permission_arrived"))) {
      // DocumentType Datang Terlambat
      typeForm = 100012;
    } else if (form.is($("#form_permission_leave_early"))) {
      // DocumentType Pulang Cepat
      typeForm = 100013;
    }

    formData.append("startdate", startdate);
    formData.append("nik", nik);
    formData.append("typeform", typeForm);

    let url = ADMIN_URL + "Kehadiran/getJamAbsen";

    if (
      new Date($("#startdate").val()).getTime() <=
      new Date($("#submissiondate").val()).getTime()
    ) {
      $("#starttime").prop("disabled", true);
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
          form.find("[name=starttime]").val(result.clock);
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    } else {
      $("#starttime").prop("disabled", false);
    }
  }
);

$("#form_employee, #form_outsourcing").on(
  "change",
  "select[name=md_status_id]",
  function (e) {
    const _this = $(this);
    const target = $(e.target);
    const form = target.closest("form");
    let value = this.value;

    //? Condition field and contain attribute hide-field
    if (_this.attr("hide-field"))
      if (value === "100004")
        form.find("input[name=resigndate]").closest(".form-group").show();
      else form.find("input[name=resigndate]").closest(".form-group").hide();
  }
);

$("#form_overtime").on("change", "input[name=isemployee]", function (e) {
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
    }
  }
});

_tableReport.on("click", ".btn_generate_memo", function (e) {
  const _this = $(this);
  const tr = $(this).closest("tr");
  let oriElement = _this.html();

  let formData = new FormData();
  let url = `${SITE_URL}/generate`;

  let nik = tr.find("td:eq(0)").text();
  let name = tr.find("td:eq(1)").text();
  let branch = tr.find("td:eq(2)").text();
  let division = tr.find("td:eq(3)").text();
  let criteria = tr.find("td:eq(4)").text();
  let period = tr.find("td:eq(5)").text();
  let total = tr.find("td:eq(6)").text();

  let row = [];

  row.push({
    md_employee_id: _this.prop("id"),
    nik: nik,
    name: name,
    branch: branch,
    division: division,
    criteria: criteria,
    period: period,
    total: total,
  });

  formData.append("memos", JSON.stringify(row));

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(_this)
        .html(
          '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
        )
        .prop("disabled", true);
    },
    success: function (result) {
      if (result[0].success) {
        reloadTable();
        Toast.fire({
          type: "success",
          title: result[0].message,
        });
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
});

function getPosition(elem, position) {
  const form = elem.closest("form");
  let formData = new FormData();
  const field = form.find("select[name=md_position_id]");
  const id = position;

  let url = ADMIN_URL + "position/getList";
  formData.append("md_position_id", id);

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

$("#form_leave").on(
  "change dp.change",
  "#md_employee_id, #startdate, #enddate",
  function (e) {
    let _this = $(this);
    const target = $(e.target);
    const form = _this.closest("form");
    let value = this.value;

    let employeeID = form
      .find("select[name=md_employee_id] option:selected")
      .val();
    let startDate = form.find("input[name=startdate]").val();
    let endDate = form.find("input[name=enddate]").val();

    let url = `${SITE_URL}/available-days`;

    if (employeeID && startDate && endDate) {
      let data = {};

      if (employeeID && form.find("select[name=md_employee_id]"))
        data["md_employee_id"] = employeeID;

      if (startDate && form.find("input[name=startdate]"))
        data["startdate"] = moment(startDate).format("YYYY-MM-DD HH:mm:ss");

      if (endDate && form.find("input[name=enddate]"))
        data["enddate"] = moment(endDate).format("YYYY-MM-DD HH:mm:ss");

      $.ajax({
        url: url,
        type: "GET",
        data: data,
        dataType: "JSON",
        success: function (result) {
          console.log(result);
          if (result[0].success) {
            let balance = result[0].message;
            form.find("input[name=availableleavedays]").val(balance);
          }
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    }
  }
);
