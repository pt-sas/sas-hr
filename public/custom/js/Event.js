/**
 * Event Listener Quotation Detail
 */
_tableLine.on("change", 'input[name="isspare"]', function (evt) {
  const tr = _tableLine.$(this).closest("tr");

  if ($(this).is(":checked")) {
    let url = ADMIN_URL + "category/getPic";
    let product = { name: tr.find('input[name="md_product_id"]').val() };

    if (tr.find('select[name="md_product_id"]').length > 0)
      product = { id: tr.find('select[name="md_product_id"]').val() };

    $.ajax({
      url: url,
      type: "POST",
      data: product,
      dataType: "JSON",
      success: function (response) {
        tr.find('select[name="md_employee_id"]')
          .val(response)
          .change()
          .attr("disabled", true);
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  } else {
    tr.find('select[name="md_employee_id"]')
      .val(null)
      .change()
      .removeAttr("disabled");
  }

  if (tr.find('select[name="md_branch_id"]').length > 0) {
    tr.find('select[name="md_branch_id"]')
      .val(null)
      .change()
      .removeAttr("disabled");
  }

  if (tr.find('select[name="md_division_id"]').length > 0) {
    tr.find('select[name="md_division_id"]')
      .val(null)
      .change()
      .removeAttr("disabled");
  }

  if (tr.find('select[name="md_room_id"]').length > 0) {
    tr.find('select[name="md_room_id"]')
      .empty()
      .change()
      .removeAttr("disabled");
  }
});

// update field input name line amount
_tableLine.on(
  "keyup",
  'input[name="qtyentered"], input[name="unitprice"]',
  function (evt) {
    const tr = _tableLine.$(this).closest("tr");

    let value = this.value;
    let lineamt,
      qty,
      unitprice = 0;

    const referenceField = tr.find(
      'input[name="qtyentered"], input[name="unitprice"]'
    );

    if (referenceField.length > 1) {
      if ($(this).attr("name") == "unitprice") {
        qty = replaceRupiah(tr.find('input[name="qtyentered"]').val());
        value = replaceRupiah(this.value);

        lineamt = value * qty;
      }

      if ($(this).attr("name") == "qtyentered") {
        unitprice = replaceRupiah(tr.find('input[name="unitprice"]').val());

        lineamt = value * unitprice;
      }

      tr.find('input[name="lineamt"]').val(formatRupiah(lineamt));
    }

    if (
      tr.find('input[name="priceaftertax"]').length > 0 &&
      $(this).attr("name") == "unitprice"
    ) {
      let priceAfterTax = parseInt(value);
      priceAfterTax += priceAfterTax * 0.11;

      tr.find('input[name="priceaftertax"]').val(formatRupiah(priceAfterTax));
    }
  }
);

/**
 * Event Listener Receipt Detail
 */
let prev;

$(document).ready(function (evt) {
  $("#trx_quotation_id")
    .on("focus", function (e) {
      prev = this.value;
    })
    .change(function (evt) {
      const form = $(this).closest("form");
      const attrName = $(this).attr("name");

      let quotation_id = this.value;

      // create data
      if (quotation_id !== "" && setSave === "add") {
        _tableLine.clear().draw(false);
        setReceiptDetail(form, attrName, quotation_id);
      }

      // update data
      $.each(option, function (idx, elem) {
        if (elem.fieldName === attrName && setSave !== "add") {
          // Logic quotation_id is not null and current value not same value from database and datatable is not empty
          if (
            quotation_id !== "" &&
            quotation_id != elem.option_ID &&
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
                setReceiptDetail(form, attrName, quotation_id, ID);
              } else {
                form
                  .find("select[name=" + attrName + "]")
                  .val(elem.option_ID)
                  .change();
              }
            });
          }

          // Logic quotation_id is not null and not same value from database and datatable is empty
          if (
            quotation_id !== "" &&
            quotation_id != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            setReceiptDetail(form, attrName, quotation_id);
          }

          // Logic prev data not same currentvalue and value from database and datatable is empty
          if (
            typeof prev !== "undefined" &&
            prev !== "" &&
            quotation_id !== "" &&
            prev != quotation_id &&
            prev != elem.option_ID &&
            !_tableLine.data().any()
          ) {
            _tableLine.clear().draw(false);
            setReceiptDetail(form, attrName, quotation_id);
          }
        }
      });

      // callback value to prev
      prev = this.value;
    });
});

_tableLine.on("change", 'select[name="md_employee_id"]', function (evt) {
  const tr = _tableLine.$(this).closest("tr");
  let employee_id = this.value;

  if (employee_id !== "") {
    // Column Branch
    if (tr.find('select[name="md_branch_id"]').length > 0)
      getOption("branch", "md_branch_id", tr, null, employee_id);
    // Column Division
    if (tr.find('select[name="md_division_id"]').length > 0)
      getOption("division", "md_division_id", tr, null, employee_id);
    // Column Room
    if (tr.find('select[name="md_room_id"]').length > 0)
      getOption("room", "md_room_id", tr, null, employee_id);
  }
});

// Function for getter datatable from quotation
function setReceiptDetail(form, fieldName, id, receipt_id = 0) {
  const field = form.find("input, select, textarea");
  let url = SITE_URL + "/getDetailQuotation";

  $.ajax({
    url: url,
    type: "POST",
    data: {
      id: id,
      receipt_id: receipt_id,
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

        if (arrMsg.header) {
          let header = arrMsg.header;
          let fields = [];

          for (let i = 0; i < header.length; i++) {
            let fieldInput = header[i].field;
            let label = header[i].label;

            for (let i = 0; i < field.length; i++) {
              // To set value on the field from quotation
              if (field[i].name !== "" && field[i].name === fieldInput) {
                const select = form.find("select[name=" + field[i].name + "]");

                if ($(field[i]).attr("hide-field"))
                  fields = $(field[i])
                    .attr("hide-field")
                    .split(",")
                    .map((element) => element.trim());

                if (
                  field[i].type === "select-one" &&
                  fieldName !== fieldInput
                ) {
                  if (
                    typeof label === "object" &&
                    label !== null &&
                    fields.includes(field[i].name)
                  ) {
                    let newOption = $("<option selected='selected'></option>")
                      .val(label.id)
                      .text(label.name);
                    select.append(newOption).change();

                    let formGroup = select.closest(".form-group, .form-check");
                    formGroup.show();
                  } else if (typeof label === "string" && label !== null) {
                    select.val(label).change();
                  } else {
                    select.val(null).change();

                    let formGroup = select.closest(".form-group, .form-check");
                    formGroup.hide();
                  }
                }

                if (
                  field[i].type === "select-one" &&
                  fieldName === fieldInput &&
                  setSave !== "add"
                )
                  select.prop("disabled", true);

                if (field[i].type === "textarea" && label !== "")
                  form.find("textarea[name=" + field[i].name + "]").val(label);
                else
                  form.find("textarea[name=" + field[i].name + "]").val(null);

                if (field[i].type === "checkbox" && label === "Y")
                  form
                    .find("input:checkbox[name=" + field[i].name + "]")
                    .prop("checked", true);
                else
                  form
                    .find("input:checkbox[name=" + field[i].name + "]")
                    .prop("checked", false);

                if (field[i].type === "text") {
                  if (fieldInput === "docreference") {
                    if (label !== "") {
                      form.find("input[name=" + field[i].name + "]").val(label);

                      //* Field Invoice No
                      form.find("input[name=invoiceno]").val("-");
                    } else {
                      form.find("input[name=" + field[i].name + "]").val(null);

                      //* Field Invoice No
                      form.find("input[name=invoiceno]").val(null);
                    }
                  }
                }
              }
            }
          }
        }

        if (arrMsg.line) {
          if (form.find("table.tb_displayline").length > 0) {
            let line = JSON.parse(arrMsg.line);

            $.each(line, function (idx, elem) {
              _tableLine.row.add(elem).draw(false);
            });

            const input = _tableLine.rows().nodes().to$().find("input, select");

            $.each(input, function (idx, item) {
              const tr = $(item).closest("tr");
              let employee_id = tr.find('select[name="md_employee_id"]').val();

              // Column Branch
              if (this.name === "md_branch_id")
                getOption("branch", "md_branch_id", tr, null, employee_id);
              // Column Division
              if (this.name === "md_division_id")
                getOption("division", "md_division_id", tr, null, employee_id);
              // Column Room
              if (this.name === "md_room_id")
                getOption("room", "md_room_id", tr, null, employee_id);
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

function getOption(controller, field, tr, selected_id, ref_id = null) {
  let url = ADMIN_URL + controller + "/getList";
  const form = tr.closest("form");

  tr.find("select[name =" + field + "]").empty();

  $.ajax({
    url: url,
    type: "POST",
    cache: false,
    data: {
      reference: ref_id,
    },
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
      tr.find("select[name =" + field + "]").append(
        '<option value=""></option>'
      );

      if (!result[0].error) {
        $.each(result, function (idx, item) {
          // Check property key isset and key equal id or set selected equal id
          if (
            (typeof item.key !== "undefined" && item.key == item.id) ||
            selected_id == item.id
          ) {
            tr.find("select[name =" + field + "]").append(
              '<option value="' +
                item.id +
                '" selected>' +
                item.text +
                "</option>"
            );
            tr.find("select[name =" + field + "]").attr("value", item.id);
          } else {
            tr.find("select[name =" + field + "]").append(
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
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });
}

/**
 * MASTER DATA EMPLOYEE
 */
$("#form_employee, #form_opname").on("change", "#md_branch_id", function (evt) {
  let _this = $(this);
  let url = ADMIN_URL + "room/getList";
  let value = this.value;
  const form = _this.closest("form");

  form.find('[name="md_room_id"]').empty();

  if (value !== "") {
    $.ajax({
      url: url,
      type: "POST",
      cache: false,
      data: {
        reference: value,
        key: "branch",
      },
      beforeSend: function () {
        $(".close_form").attr("disabled", true);
        loadingForm(form.prop("id"), "pulse");
      },
      complete: function () {
        $(".close_form").removeAttr("disabled");
        hideLoadingForm(form.prop("id"));
      },
      dataType: "JSON",
      success: function (result) {
        if (result.length > 0) {
          form.find('[name="md_room_id"]').append('<option value=""></option>');

          let md_room_id = 0;

          $.each(option, function (i, item) {
            if (item.fieldName == "md_room_id") md_room_id = item.label;
          });

          if (!result[0].error) {
            $.each(result, function (idx, item) {
              if (form.find('[name="md_room_id"]').length > 0) {
                if (md_room_id == item.id) {
                  form
                    .find('[name="md_room_id"]')
                    .append(
                      '<option value="' +
                        item.id +
                        '" selected>' +
                        item.text +
                        "</option>"
                    );
                } else {
                  form
                    .find('[name="md_room_id"]')
                    .append(
                      '<option value="' +
                        item.id +
                        '">' +
                        item.text +
                        "</option>"
                    );
                }
              } else {
                Swal.fire({
                  type: "error",
                  title: "Field is not found",
                  showConfirmButton: false,
                  timer: 1500,
                });
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
  }
});

/**
 * Event Listener Movement Detail
 */
_tableLine.on("change", 'select[name="assetcode"]', function (evt) {
  const tr = _tableLine.$(this).closest("tr");

  let url = ADMIN_URL + "inventory/getAssetDetail";
  let value = this.value;

  $.ajax({
    url: url,
    type: "POST",
    cache: false,
    data: {
      assetcode: value,
    },
    dataType: "JSON",
    success: function (result) {
      if (result[0].success) {
        $.each(result[0].message, function (idx, item) {
          if (tr.find('select[name="md_product_id"]').length > 0) {
            if (value === "OTHER") {
              tr.find('select[name="md_product_id"]')
                .val(item.md_product_id)
                .change()
                .removeAttr("disabled");
            } else {
              tr.find('select[name="md_product_id"]')
                .val(item.md_product_id)
                .change()
                .prop("disabled", true);
            }
          } else if (tr.find('input[name="md_product_id"]').length > 0) {
            tr.find('input[name="md_product_id"]').val(item.md_product_id.name);
          }

          if (tr.find('select[name="employee_from"]').length > 0) {
            tr.find('select[name="employee_from"]')
              .val(item.md_employee_id)
              .change();
          } else if (tr.find('input[name="employee_from"]').length > 0) {
            tr.find('input[name="employee_from"]').val(item.employee_from.name);
          }

          if (tr.find('select[name="branch_from"]').length > 0) {
            tr.find('select[name="branch_from"]')
              .val(item.md_branch_id)
              .change();
          } else if (tr.find('input[name="branch_from"]').length > 0) {
            tr.find('input[name="branch_from"]').val(item.branch_from.name);
          }

          if (tr.find('select[name="division_from"]').length > 0) {
            tr.find('select[name="division_from"]')
              .val(item.md_division_id)
              .change();
          } else if (tr.find('input[name="division_from"]').length > 0) {
            tr.find('input[name="division_from"]').val(item.division_from.name);
          }

          if (tr.find('select[name="room_from"]').length > 0) {
            tr.find('select[name="room_from"]').val(item.md_room_id).change();
          } else if (tr.find('input[name="room_from"]').length > 0) {
            tr.find('input[name="room_from"]').val(item.room_from.name);
          }

          if (tr.find('input:checkbox[name="isnew"]').length > 0 && item.isnew === "Y") {
            tr.find('input:checkbox[name="isnew"]').prop("checked", true);
          } else {
            tr.find('input:checkbox[name="isnew"]').prop("checked", false);
          }
        });
      } else if (!result[0].success) {
        if (tr.find('select[name="md_product_id"]').length > 0) {
            tr.find('select[name="md_product_id"]')
              .val(null)
              .change()
              .prop("disabled", true);
        } else if (tr.find('input[name="md_product_id"]').length > 0) {
          tr.find('input[name="md_product_id"]').val(null);
        }

        if (tr.find('select[name="employee_from"]').length > 0) {
          tr.find('select[name="employee_from"]')
            .val(null)
            .change();
        } else if (tr.find('input[name="employee_from"]').length > 0) {
          tr.find('input[name="employee_from"]').val(null);
        }

        if (tr.find('select[name="branch_from"]').length > 0) {
          tr.find('select[name="branch_from"]')
            .val(null)
            .change();
        } else if (tr.find('input[name="branch_from"]').length > 0) {
          tr.find('input[name="branch_from"]').val(null);
        }

        if (tr.find('select[name="division_from"]').length > 0) {
          tr.find('select[name="division_from"]')
            .val(null)
            .change();
        } else if (tr.find('input[name="division_from"]').length > 0) {
          tr.find('input[name="division_from"]').val(null);
        }

        if (tr.find('select[name="room_from"]').length > 0) {
          tr.find('select[name="room_from"]').val(null).change();
        } else if (tr.find('input[name="room_from"]').length > 0) {
          tr.find('input[name="room_from"]').val(null);
        }

        if (tr.find('input:checkbox[name="isnew"]').length > 0) {
          tr.find('input:checkbox[name="isnew"]').prop("checked", false);
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
});

// Event change field Status
_tableLine.on("change", 'select[name="md_status_id"]', function (evt) {
  const tr = _tableLine.$(this).closest("tr");
  let value = $(this).find("option:selected").text();

  if (value === "RUSAK") {
    getOption("employee", "employee_to", tr, 100130); // Selected Employee IT
    tr.find('select[name="employee_to"]').attr("disabled", true);
    // Column Branch
    getOption("branch", "branch_to", tr, 100001); // Selected Branch Sunter
    // Column Division
    getOption("division", "division_to", tr, 100006); // Selected Division IT
    // Column Room
    getOption("room", "room_to", tr, 100041); // Selected Room To BARANG RUSAK
    tr.find('select[name="room_to"]').attr("disabled", true);
  } else {
    if (checkExistUserRole("W_View_All_Movement")) {
      tr.find('select[name="employee_to"]')
        .val(null)
        .change()
        .removeAttr("disabled");
    } else {
      getOption("employee", "employee_to", tr, null, value);
      tr.find('select[name="employee_to"]').removeAttr("disabled");
    }

    //* Set null value on the field dropdown change status
    tr.find('select[name="branch_to"]').val(null).change();
    tr.find('select[name="division_to"]').val(null).change();
    tr.find('select[name="room_to"]').val(null).change();
  }
});

// Event change field Employee To
_tableLine.on("change", 'select[name="employee_to"]', function (evt) {
  const tr = _tableLine.$(this).closest("tr");
  let value = $(this).find("option:selected").text();
  let status = tr.find('select[name="status_id"] option:selected').text();
  let employee_id = this.value;

  if (value === "IT") {
    // Column Branch
    getOption("branch", "branch_to", tr, 100001);
    // Column Division
    getOption("division", "division_to", tr, 100006);

    if (status === "RUSAK") {
      // Column Room
      getOption("room", "room_to", tr, 100041);
      tr.find('select[name="room_to"]').attr("disabled", true);
    }

    if (status === "BAGUS") {
      // Column Room
      getOption("room", "room_to", tr, null, "IT");
      tr.find('select[name="room_to"]').removeAttr("disabled");
    }
  } else {
    // Column Branch
    getOption("branch", "branch_to", tr, null, employee_id);
    // Column Division
    getOption("division", "division_to", tr, null, employee_id);
    // Column Room
    getOption("room", "room_to", tr, null, employee_id);
    tr.find('select[name="room_to"]').removeAttr("disabled");
  }

  if (value === "") {
    tr.find('select[name="branch_to"]').val(null).change();
    tr.find('select[name="division_to"]').val(null).change();
    tr.find('select[name="room_to"]').val(null).change();
  }
});

/**
 * Event Menu Inventory
 */
// Form Inventory
$("#form_inventory").on("change", "#md_product_id", function (evt) {
  let url = ADMIN_URL + "/groupasset/getList";
  let value = this.value;

  $("#md_groupasset_id").empty();

  if (value !== "") {
    $.ajax({
      url: url,
      type: "POST",
      cache: false,
      data: {
        reference: value,
      },
      beforeSend: function () {
        $(".save_form").attr("disabled", true);
        $(".close_form").attr("disabled", true);
        loadingForm("form_inventory", "pulse");
      },
      complete: function () {
        $(".save_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm("form_inventory");
      },
      dataType: "JSON",
      success: function (result) {
        $("#md_groupasset_id").append('<option value=""></option>');

        let md_groupasset_id = 0;

        $.each(option, function (i, item) {
          if (item.fieldName == "md_groupasset_id")
            md_groupasset_id = item.label;
        });

        if (!result[0].error) {
          $.each(result, function (idx, item) {
            if (
              (typeof item.key !== "undefined" && item.key == item.id) ||
              (setSave === "update" && md_groupasset_id == item.id)
            ) {
              $("#md_groupasset_id").append(
                '<option value="' +
                  item.id +
                  '" selected>' +
                  item.text +
                  "</option>"
              );

              md_groupasset_id = 0;
            } else {
              $("#md_groupasset_id").append(
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
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
});

$("#form_inventory").on("change", "#md_branch_id", function (evt) {
  let url = ADMIN_URL + "room/getList";
  let value = this.value;

  $("#md_room_id").empty();

  if (value !== "") {
    $.ajax({
      url: url,
      type: "POST",
      cache: false,
      data: {
        reference: value,
        key: "all",
      },
      beforeSend: function () {
        $(".save_form").attr("disabled", true);
        $(".close_form").attr("disabled", true);
        loadingForm("form_inventory", "pulse");
      },
      complete: function () {
        $(".save_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm("form_inventory");
      },
      dataType: "JSON",
      success: function (result) {
        $("#md_room_id").append('<option value=""></option>');

        let md_room_id = 0;

        $.each(option, function (i, item) {
          if (item.fieldName == "md_room_id") md_room_id = item.label;
        });

        if (!result[0].error) {
          $.each(result, function (idx, item) {
            if (md_room_id == item.id) {
              $("#md_room_id").append(
                '<option value="' +
                  item.id +
                  '" selected>' +
                  item.text +
                  "</option>"
              );
            } else {
              $("#md_room_id").append(
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
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
});

$("#form_inventory").on("change", "#md_employee_id", function (evt) {
  let url = ADMIN_URL + "division/getList";
  let value = this.value;

  $("#md_division_id").empty();

  if (value !== "") {
    $.ajax({
      url: url,
      type: "POST",
      cache: false,
      data: {
        reference: value,
      },
      beforeSend: function () {
        $(".save_form").attr("disabled", true);
        $(".close_form").attr("disabled", true);
        loadingForm("form_inventory", "pulse");
      },
      complete: function () {
        $(".save_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm("form_inventory");
      },
      dataType: "JSON",
      success: function (result) {
        $("#md_division_id").append('<option value=""></option>');

        let md_division_id = 0;

        $.each(option, function (i, item) {
          if (item.fieldName == "md_division_id") md_division_id = item.label;
        });

        if (!result[0].error) {
          $.each(result, function (idx, item) {
            if (
              (typeof item.key !== "undefined" && item.key == item.id) ||
              (setSave === "update" && md_division_id == item.id)
            ) {
              $("#md_division_id").append(
                '<option value="' +
                  item.id +
                  '" selected>' +
                  item.text +
                  "</option>"
              );

              //TODO: Set md_division_id to zero after set selected
              md_division_id = 0;
            } else {
              $("#md_division_id").append(
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
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
});

// Form Filter
$(document).ready(function (e) {
  $(".select-product").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    minimumInputLength: 3,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "product/getList",
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

  $(".select-branch").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
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

  $(".select-division").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
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

  $(".select-room").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "room/getList",
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

  $(".multiple-select-room").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "room/getList",
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

  $(".select-employee").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    minimumInputLength: 3,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "employee/getList",
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

  $(".select-supplier").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "supplier/getList",
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

  $(".multiple-select-supplier").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "supplier/getList",
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

  $(".select-groupasset").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "groupasset/getList",
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

  $(".multiple-select-groupasset").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "groupasset/getList",
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

  $(".select-brand").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "brand/getList",
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

  $(".multiple-select-brand").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "brand/getList",
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

  $(".select-category").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "category/getList",
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

  $(".multiple-select-category").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "category/getList",
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

  $(".select-subcategory").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "subcategory/getList",
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

  $(".multiple-select-subcategory").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "subcategory/getList",
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

  $(".select-type").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "type/getList",
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

  $(".multiple-select-type").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "type/getList",
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

  $(".select-assetcode").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    minimumInputLength: 2,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "inventory/getAssetCode",
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

  $(".select-assetcode-plate").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    minimumInputLength: 2,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "inventory/getAssetCode",
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          plate: "Y",
        };
      },
      processResults: function (data, page) {
        console.log(data);
        return {
          results: data,
        };
      },
      cache: true,
    },
  });

  $(".multiple-select-assetcode").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "inventory/getAssetCode",
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
      url: ADMIN_URL + "employee/getList",
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

  $(".multiple-select-receipt").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "receipt/getList",
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

  $(".multiple-select-user").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    multiple: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "user/getList",
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

  $(".select-movementtype").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
    ajax: {
      dataType: "JSON",
      url: ADMIN_URL + "reference/getList",
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          name: "MovementType",
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

$("#filter_inventory").on("change", '[name="md_branch_id"]', function (evt) {
  let url = ADMIN_URL + "room/getList";
  let value = this.value;

  $('[name="md_room_id"]').empty();

  // Set condition when clear or value zero
  if (value !== "" && value !== "0") {
    $.ajax({
      url: url,
      type: "POST",
      cache: false,
      data: {
        reference: value,
        key: "all",
      },
      dataType: "JSON",
      success: function (result) {
        $('[name="md_room_id"]').append('<option value=""></option>');

        if (!result[0].error) {
          $.each(result, function (idx, item) {
            $('[name="md_room_id"]').append(
              '<option value="' + item.id + '">' + item.text + "</option>"
            );
          });
        } else {
          Swal.fire({
            type: "error",
            title: result[0].message,
            showConfirmButton: false,
            timer: 1500,
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
 * Event Listener Sequence
 */
$("#form_sequence").on(
  "click",
  "#isautosequence, #isgassetlevelsequence, #iscategorylevelsequence, #startnewyear",
  function (evt) {
    const target = $(evt.target);
    const form = target.closest("form");

    //? Condition field and contain attribute hide-field
    if ($(this).attr("hide-field")) {
      let fields = $(this)
        .attr("hide-field")
        .split(",")
        .map((element) => element.trim());

      if ($(this).is(":checked")) {
        for (let i = 0; i < fields.length; i++) {
          let formGroup = form
            .find(
              "input[name=" +
                fields[i] +
                "], textarea[name=" +
                fields[i] +
                "], select[name=" +
                fields[i] +
                "]"
            )
            .closest(".form-group, .form-check");
          formGroup.hide();
        }
      } else {
        for (let i = 0; i < fields.length; i++) {
          let formGroup = form
            .find(
              "input[name=" +
                fields[i] +
                "], textarea[name=" +
                fields[i] +
                "], select[name=" +
                fields[i] +
                "]"
            )
            .closest(".form-group, .form-check");
          formGroup.show();
        }
      }
    }

    //? Condition field and contain attribute show-field
    if ($(this).attr("show-field")) {
      let fields = $(this)
        .attr("show-field")
        .split(",")
        .map((element) => element.trim());

      if ($(this).is(":checked")) {
        for (let i = 0; i < fields.length; i++) {
          let formGroup = form
            .find(
              "input[name=" +
                fields[i] +
                "], textarea[name=" +
                fields[i] +
                "], select[name=" +
                fields[i] +
                "]"
            )
            .closest(".form-group, .form-check");
          formGroup.show();
        }
      } else {
        for (let i = 0; i < fields.length; i++) {
          let formGroup = form
            .find(
              "input[name=" +
                fields[i] +
                "], textarea[name=" +
                fields[i] +
                "], select[name=" +
                fields[i] +
                "]"
            )
            .closest(".form-group, .form-check");
          formGroup.hide();
        }
      }
    }
  }
);

$(".upload_form").click(function (evt) {
  console.log(evt);
  $(".modal_upload").modal({
    backdrop: "static",
    keyboard: false,
  });
  Scrollmodal();
});

$(".custom-file-input").change(function (e) {
  var name = document.getElementById("customFileInput").files[0].name;
  var nextSibling = e.target.nextElementSibling;
  nextSibling.innerText = name;
});

$(".save_upload").click(function (evt) {
  var fd = new FormData();

  fd.append("file", $("#customFileInput")[0].files[0]);
  $.ajax({
    url: SITE_URL + "/import",
    type: "POST",
    data: fd,
    processData: false, // important
    contentType: false, // important
    dataType: "JSON",
    success: function (response) {
      console.log(response);
    },
  });

  // console.log(fd)
});

/**
 * Event Listener Responsible Type
 */
$("#form_responsible").on("change", "#responsibletype", function (evt) {
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

    if (value === "H") {
      form.find("select[name=sys_user_id]").closest(".form-group").show();
    } else {
      form.find("select[name=sys_user_id]").closest(".form-group").hide();
    }
  }
});

$("#parameter_report").on("change", '[name="md_branch_id"]', function (evt) {
  let url = ADMIN_URL + "room/getList";
  let value = this.value;

  $('[name="md_room_id"]').empty();

  // Set condition when clear or value zero
  if (value !== "" && value !== "0") {
    $.ajax({
      url: url,
      type: "POST",
      cache: false,
      data: {
        reference: value,
        key: "all",
      },
      dataType: "JSON",
      success: function (result) {
        if (!result[0].error) {
          $.each(result, function (idx, item) {
            $('[name="md_room_id"]').append(
              '<option value="' + item.id + '">' + item.text + "</option>"
            );
          });
        } else {
          Swal.fire({
            type: "error",
            title: result[0].message,
            showConfirmButton: false,
            timer: 1500,
          });
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  }
});

$("#form_internaluse").on("click", '[name="isfrom"]', function (evt) {
  const target = $(evt.target);
  const form = target.closest("form");

  if ($(this).attr("hide-field")) {
    let fields = $(this)
      .attr("hide-field")
      .split(",")
      .map((element) => element.trim());

    if ($(this).is(":checked")) {
      for (let i = 0; i < fields.length; i++) {
        if (this.id === "supplier") {
          form
            .find("select[name=md_supplier_id]")
            .not(".line")
            .val(null)
            .change()
            .closest(".form-group")
            .show();
          form
            .find("select[name=md_employee_id]")
            .not(".line")
            .val(null)
            .change()
            .closest(".form-group")
            .hide();
        } else if (this.id === "employee") {
          form
            .find("select[name=md_supplier_id]")
            .not(".line")
            .val(null)
            .change()
            .closest(".form-group")
            .hide();
          form
            .find("select[name=md_employee_id]")
            .not(".line")
            .val(null)
            .change()
            .closest(".form-group")
            .show();
        } else if (this.id === "other") {
          form
            .find("select[name=md_supplier_id]")
            .not(".line")
            .val(null)
            .change()
            .closest(".form-group")
            .hide();
          form
            .find("select[name=md_employee_id]")
            .not(".line")
            .val(null)
            .change()
            .closest(".form-group")
            .hide();
        }
      }
    }
  }
});

/**
 * Event Listener With Text Barcode
 */
$("#form_barcode").on("change", "#iswithtext", function (evt) {
  const target = $(evt.target);
  const form = target.closest("form");
  const value = this.value;

  //? Condition field and contain attribute show-field
  if ($(this).attr("show-field")) {
    let fields = $(this)
      .attr("show-field")
      .split(",")
      .map((element) => element.trim());

    if ($(this).is(":checked")) {
      for (let i = 0; i < fields.length; i++) {
        form
          .find("input[name=" + fields[i] + "], select[name=" + fields[i] + "]")
          .closest(".form-group")
          .show();
      }
    } else {
      for (let i = 0; i < fields.length; i++) {
        form
          .find("input[name=" + fields[i] + "], select[name=" + fields[i] + "]")
          .val(null)
          .change()
          .closest(".form-group")
          .hide();
      }
    }
  }
});

/**
 * Event Listener Product Form
 */
$("#form_product, #form_product_info").on(
  "change",
  "#md_category_id",
  function (evt) {
    const form = $(this).closest("form");

    let url = ADMIN_URL + "subcategory/getList";
    let value = this.value;

    $("#md_subcategory_id").empty();

    if (value !== "") {
      $.ajax({
        url: url,
        type: "POST",
        cache: false,
        data: {
          reference: value,
        },
        beforeSend: function () {
          $(".save_form").attr("disabled", true);
          $(".close_form").attr("disabled", true);
          $(".x_form").attr("disabled", true);
          $(".btn_requery_info").attr("disabled", true);
          $(".btn_close_info").attr("disabled", true);
          $(".btn_save_info").attr("disabled", true);
          loadingForm(form.prop("id"), "pulse");
        },
        complete: function () {
          $(".save_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
          $(".x_form").removeAttr("disabled");
          $(".btn_requery_info").removeAttr("disabled");
          $(".btn_close_info").removeAttr("disabled");
          $(".btn_save_info").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        dataType: "JSON",
        success: function (result) {
          $("#md_subcategory_id").append('<option value=""></option>');

          let md_subcategory_id = 0;

          $.each(option, function (i, item) {
            if (item.fieldName == "md_subcategory_id")
              md_subcategory_id = item.label;
          });

          if (!result[0].error) {
            $.each(result, function (idx, item) {
              if (md_subcategory_id == item.id) {
                $("#md_subcategory_id").append(
                  '<option value="' +
                    item.id +
                    '" selected>' +
                    item.text +
                    "</option>"
                );
              } else {
                $("#md_subcategory_id").append(
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
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    }
  }
);

$("#form_product, #form_product_info").on(
  "change",
  "#md_subcategory_id",
  function (evt) {
    const form = $(this).closest("form");

    let url = ADMIN_URL + "type/getList";
    let value = this.value;

    $("#md_type_id").empty();

    if (value !== "") {
      $.ajax({
        url: url,
        type: "POST",
        cache: false,
        data: {
          reference: value,
        },
        beforeSend: function () {
          $(".save_form").attr("disabled", true);
          $(".close_form").attr("disabled", true);
          $(".x_form").attr("disabled", true);
          $(".btn_requery_info").attr("disabled", true);
          $(".btn_close_info").attr("disabled", true);
          $(".btn_save_info").attr("disabled", true);
          loadingForm(form.prop("id"), "pulse");
        },
        complete: function () {
          $(".save_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
          $(".x_form").removeAttr("disabled");
          $(".btn_requery_info").removeAttr("disabled");
          $(".btn_close_info").removeAttr("disabled");
          $(".btn_save_info").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        dataType: "JSON",
        success: function (result) {
          $("#md_type_id").append('<option value=""></option>');

          let md_type_id = 0;

          $.each(option, function (i, item) {
            if (item.fieldName == "md_type_id") md_type_id = item.label;
          });

          if (!result[0].error) {
            $.each(result, function (idx, item) {
              if (md_type_id == item.id) {
                $("#md_type_id").append(
                  '<option value="' +
                    item.id +
                    '" selected>' +
                    item.text +
                    "</option>"
                );
              } else {
                $("#md_type_id").append(
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
        },
        error: function (jqXHR, exception) {
          showError(jqXHR, exception);
        },
      });
    }
  }
);

/**
 * Event Menu Disposal
 */
$("#form_disposal").on("change", "#disposaltype", function (e) {
  const form = $(this).closest("form");
  const tr = _tableLine.rows().nodes().to$("tr");
  let value = this.value;

  if (setSave === "add") {
    if (value === "SL") {
      let select = form.find("select[name=md_supplier_id]");
      select.closest(".form-group").show();
      select.val(null).change();

      let input = form.find("input[name=sjkno], input[name=bpkno]");
      input.closest(".form-group").show();
      input.val(null);

      input = form.find("input[name=bapno]");
      input.closest(".form-group").hide();
      input.val(null);

      tr.find('input[name="unitprice"]').val(null);
    } else {
      let select = form.find("select[name=md_supplier_id]");
      select.closest(".form-group").hide();
      select.val(null).change();

      let input = form.find("input[name=sjkno], input[name=bpkno]");
      input.closest(".form-group").hide();
      input.val(null);

      input = form.find("input[name=bapno]");
      input.closest(".form-group").show();
      input.val(null);

      tr.find('input[name="unitprice"]').val(0);
    }
  }

  $.each(option, function (idx, elem) {
    if (setSave !== "add" && _tableLine.data().any()) {
      if (value === "SL") {
        let select = form.find("select[name=md_supplier_id]");
        select.closest(".form-group").show();
        select.val(null).change();

        let input = form.find("input[name=sjkno], input[name=bpkno]");
        input.closest(".form-group").show();
        input.val(null);

        input = form.find("input[name=bapno]");
        input.closest(".form-group").hide();
        input.val(null);

        tr.find('input[name="unitprice"]').val(elem.label);
      } else {
        let select = form.find("select[name=md_supplier_id]");
        select.closest(".form-group").hide();
        select.val(null).change();

        let input = form.find("input[name=sjkno], input[name=bpkno]");
        input.closest(".form-group").hide();
        input.val(null);

        input = form.find("input[name=bapno]");
        input.closest(".form-group").show();
        input.val(null);

        tr.find('input[name="unitprice"]').val(0);
      }
    }
  });
});

$("#form_movement").on(
  "change",
  "#md_branch_id, #movementtype, #md_branchto_id, #md_divisionto_id, #movementstatus",
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

        if (fields.includes(field[i].name)) {
          const select = form.find("select[name=" + field[i].name + "]");
          let formGroup = select.closest(".form-group");

          if (value === "KIRIM") {
            formGroup.show();
            $(".add_row").show();
          } else if (value === "TERIMA") {
            formGroup.hide();
            $(".add_row").hide();
          }

          select.val(null).change();
        }
      }

    if (setSave === "add") {
      _tableLine.clear().draw(false);
    }

    // Untuk mengakomodir kebutuhan divisi rusak 
    if (attrName === "md_divisionto_id" && ($(this).find("option:selected").text() === "HRD-RUSAK" || $(this).find("option:selected").text() === "IT-RUSAK")) {
      form.find("select[name=movementstatus]").val(null).change().attr("disabled", true);
    } else {
      form.find("select[name=movementstatus]").removeAttr("disabled", true);
    }

    // update data
    $.each(option, function (idx, elem) {
      if (elem.fieldName === attrName && setSave !== "add") {
        if (
          attrName === "movementtype" &&
          value !== "" &&
          value != elem.option_ID &&
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
              destroyAllLine("trx_movement_id", ID);
            } else {
              form
                .find("select[name=" + attrName + "]")
                .val(elem.label)
                .change();

              if (elem.label === "Kirim") {
                $("#md_branchto_id").closest(".form-group").show();
                $(".add_row").show();
                $("#md_branchto_id").val(option[2].option_ID).change();
              } else if (value === "Terima") {
                $("#md_branchto_id").closest(".form-group").hide();
                $(".add_row").hide();
                $("#md_branchto_id").val(null).change();
              }
            }
          });
        }

        if (
          attrName !== "movementtype" &&
          value !== "" &&
          (typeof elem.option_ID !== "undefined" && value != elem.option_ID || typeof elem.label !== "undefined" && value != elem.label) &&
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
              destroyAllLine("trx_movement_id", ID);
            } else {
              if (typeof elem.option_ID !== "undefined")
                form
                  .find("select[name=" + attrName + "]")
                  .val(elem.option_ID)
                  .change();

              if (typeof elem.label !== "undefined")
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

function destroyAllLine(field, id) {
  let url = CURRENT_URL + "/destroyAllLine";

  $.ajax({
    url: url,
    type: "POST",
    data: {
      trx_movement_id: id,
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

/**
 * Form Opname Scan
 */
$("#form_opname").on("keyup", "#scan_assetcode", function (evt) {
  if (evt.key === "Enter" || evt.keyCode === 13) {
    $(".btn_scan").click();
    $(this).focus();
  }
});

$(".btn_scan").click(function (evt) {
  const form = $(this).closest("form");
  let action = "create";
  let checkAccess = isAccess(action, LAST_URL);

  let formData = new FormData();

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    let _this = $(this);
    let fieldScanAsset = form.find('input[name="scan_assetcode"]');

    if (fieldScanAsset.val() !== "") {
      $.each(form, function () {
        const formHeader = $(this).find(".row")[0];
        field = $(formHeader).find("input, select, textarea").not(".line");
      });

      //* Form Header
      for (let i = 0; i < field.length; i++) {
        if (field[i].name !== "") {
          let className = field[i].className.split(/\s+/);

          //* Set field and value to formData
          if (
            field[i].type == "text" ||
            field[i].type == "textarea" ||
            field[i].type == "select-one" ||
            field[i].type == "hidden"
          )
            formData.append(field[i].name, field[i].value);

          //* Field type input radio
          if (field[i].type == "radio") {
            if (field[i].checked) {
              formData.append(field[i].name, field[i].value);
            }
          }

          //* Field type Multiple select
          if (field[i].type === "select-multiple") {
            formData.append(
              field[i].name,
              $("[name = " + field[i].name + "]").val()
            );
          }

          //* Field type input checkbox
          if (field[i].type == "checkbox") {
            if (field[i].checked) {
              formData.append(field[i].name, "Y");
            } else {
              formData.append(field[i].name, "N");
            }
          }

          //* Field containing class datepicker
          if (className.includes("datepicker")) {
            let date = field[i].value;
            let time = "00:00:00";

            if (date !== "") {
              let timeAndDate = moment(date + " " + time);
              formData.append(field[i].name, timeAndDate._i);
            }
          }
        }
      }

      const fAssetCode = _tableLine
        .rows()
        .nodes()
        .to$()
        .find('input[name="assetcode"]');

      let status = false;

      $.each(fAssetCode, function () {
        let tr = $(this).closest("tr");

        if (fieldScanAsset.val().toUpperCase() === this.value.toUpperCase()) {
          status = true;

          let noc = parseInt(tr.find("td:eq(6)").text());

          if (noc != 0) formData.append("noc", noc + 1);
          else formData.append("noc", noc);
        }
      });

      formData.append("status", status);

      let url = CURRENT_URL + TABLE_LINE;

      $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        beforeSend: function () {
          $(_this).prop("disabled", true);
          $(".close_form").attr("disabled", true);
          $(".save_form").attr("disabled", true);
          loadingForm(form.prop("id"), "ios");
        },
        complete: function () {
          $(_this).prop("disabled", false);
          $(".close_form").removeAttr("disabled");
          $(".save_form").removeAttr("disabled");
          hideLoadingForm(form.prop("id"));
        },
        success: function (result) {
          if (result[0].success) {
            let arrMsg = result[0].message;

            form.find("select").prop("disabled", true);

            if (arrMsg.new) {
              _tableLine.row.add(arrMsg.new).draw(false);
            }

            if (arrMsg.edit) {
              $.each(fAssetCode, function () {
                let tr = $(this).closest("tr");

                if (
                  fieldScanAsset.val().toUpperCase() ===
                  this.value.toUpperCase()
                ) {
                  tr.find("td:eq(2)").html(arrMsg.edit.isbranch);
                  tr.find("td:eq(3)").html(arrMsg.edit.isroom);
                  tr.find("td:eq(4)").html(arrMsg.edit.isemployee);
                  tr.find("td:eq(5)").html(arrMsg.edit.isnew);
                  tr.find("td:eq(6)").html(arrMsg.edit.nocheck);
                }
              });
            }

            fieldScanAsset.val(null);
            clearErrorForm(form);
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
    }
  } else if (checkAccess[0].success && checkAccess[0].message == "N") {
    Toast.fire({
      type: "warning",
      title: "You are role don't have permission !!",
    });
  } else {
    Toast.fire({
      type: "error",
      title: checkAccess[0].message,
    });
  }
});

$("#form_opname").on("change", "#md_employee_id", function (evt) {
  const form = $(this).closest("form");
  let formData = new FormData(form[0]);
  let _this = $(this);

  let trx_opname_id = this.value;

  formData.append("md_employee_id", trx_opname_id);

  const field = form.find("input, textarea, select").not(".line");
  const errorText = form.find("small");

  if (trx_opname_id !== "" && setSave === "add") {
    _tableLine.clear().draw(false);

    let url = SITE_URL + "/getDetailAsset";
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
        $("[name=scan_assetcode]").val(null);

        if (result[0].success) {
          let arrMsg = result[0].message;

          if (arrMsg.line) {
            if (form.find("table.tb_displayline").length > 0) {
              let line = JSON.parse(arrMsg.line);

              $.each(line, function (idx, elem) {
                _tableLine.row.add(elem).draw(false);
              });
            }

            for (let i = 0; i < field.length; i++) {
              if (field[i].name !== "") {
                form
                  .find(
                    "input[name=" +
                      field[i].name +
                      "], select[name=" +
                      field[i].name +
                      "]"
                  )
                  .closest(".form-group")
                  .removeClass("has-error");
              }

              // clear text error element small
              for (let l = 0; l < errorText.length; l++) {
                if (errorText[l].id !== "")
                  form.find("small[id=" + errorText[l].id + "]").html("");
              }
            }
          }
        } else if (result[0].error) {
          errorForm(form, result);
          _this.val(null).change();
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
  }
});

_tableInfo.on("change", 'input[name="isspare"]', function (evt) {
  const tr = _tableInfo.$(this).closest("tr");

  if ($(this).is(":checked")) {
    let url = ADMIN_URL + "category/getPic";
    let product = tr.find("td:eq(1)").text();

    $.ajax({
      url: url,
      type: "POST",
      data: {
        name: product,
      },
      dataType: "JSON",
      success: function (response) {
        tr.find('select[name="employee_id"]')
          .val(response)
          .change()
          .attr("disabled", true);
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  } else {
    tr.find('select[name="employee_id"]')
      .val(null)
      .change()
      .removeAttr("disabled");
  }
});
