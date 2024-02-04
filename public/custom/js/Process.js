/**
 * Proses for execute form master data dynamic element HTML
 *
 * @author Oki Permana
 * @version 1.0
 */
const ADMIN = "/sas/";

let ORI_URL = window.location.origin,
  CURRENT_URL = window.location.href,
  LAST_URL = CURRENT_URL.substr(CURRENT_URL.lastIndexOf("/") + 1), //the last url
  ADMIN_URL = ORI_URL + ADMIN,
  SITE_URL = ADMIN_URL + LAST_URL;

let _table,
  _tableLine,
  ul,
  formTable,
  _tableInfo,
  formReport,
  _tableReport,
  _tableApproval;

//* Field ID for collect data id
let ID = 0;

//* Field setSave for action identification
let setSave = null;

let clear = false;

//* Field changeTab boolean value
let changeTab = false;

// Data array from option
let option = [];

// Data field array is readonly/disabled default
let fieldReadOnly = [];
// Data field array is checked default
let fieldChecked = [];
// Retrieve multiple select-one
let arrMultiSelect = [];

// Method default controller
const SHOWALL = "/showAll",
  CREATE = "/create",
  SHOW = "/show/",
  EDIT = "/edit",
  DELETE = "/destroy/",
  EXPORT = "/export",
  IMPORT = "/import",
  TABLE_LINE = "/tableLine",
  DELETE_LINE = "/destroyLine/",
  TEST_EMAIL = "/createTestEmail",
  ACCEPT = "/accept/",
  PRINT = "/print/";

// view page class on div
let cardMain = $(".card-main"),
  cardForm = $(".card-form"),
  cardBtn = $(".card-button"),
  cardTitle = $(".card-title");

// Modal
const modalForm = $(".modal_form");

const modalDialog = $(".modal-dialog"),
  modalTitle = $(".modal-title"),
  modalBody = $(".modal-body");

$(document).ready(function (e) {
  const parent = $(".container");
  const card = parent.find(".card");
  const cardMenu = parent.find(".card-action-menu");
  const actionMenu = cardMenu.attr("data-action-menu");

  if (typeof actionMenu === "undefined" && actionMenu !== "F") {
    //* Remove class is-loading
    $(".main-panel").removeClass("is-loading");
  } else {
    const form = card.find("form");
    showFormData(form);
  }

  showNotification();

  // Enable pusher logging - don't include this in production
  Pusher.logToConsole = false;

  var pusher = new Pusher("8ae4540d78a7d493226a", {
    cluster: "ap1",
  });

  var channel = pusher.subscribe("my-channel");
  channel.bind("my-event", function (data) {
    showNotification();
  });

  $(".select2").select2({
    placeholder: "Select an option",
    width: "100%",
    theme: "bootstrap",
    allowClear: true,
  });

  $(".multiple-select").select2({
    width: "100%",
    theme: "bootstrap",
    multiple: true,
  });

  $(".number").on("keypress keyup blur", function (evt) {
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

  $(this).find(".rupiah").autoNumeric("init", {
    aSep: ".",
    aDec: ",",
    mDec: "0",
  });

  Toast = Swal.mixin({
    toast: true,
    position: "top",
    showConfirmButton: false,
    timer: 4000,
  });

  $(".datepicker").datetimepicker({
    format: "DD-MMM-YYYY",
    showTodayButton: true,
    showClear: true,
    showClose: true,
    useCurrent: false,
  });

  $(".datepick").datetimepicker({
    format: "DD-MMM-YYYY",
    showTodayButton: true,
    showClear: true,
    showClose: true,
    daysOfWeekDisabled: [0, 6],
    useCurrent: false,
  });

  $(".datepick-start").datetimepicker({
    format: "DD-MMM-YYYY",
    showTodayButton: true,
    showClear: true,
    showClose: true,
    daysOfWeekDisabled: [0, 6],
    useCurrent: false,
  });

  $(".datepick-end").datetimepicker({
    format: "DD-MMM-YYYY",
    showTodayButton: true,
    showClear: true,
    showClose: true,
    daysOfWeekDisabled: [0, 6],
    useCurrent: false,
  });

  $(".datepicker-start").datetimepicker({
    format: "DD-MMM-YYYY",
    showTodayButton: true,
    showClear: true,
    showClose: true,
    daysOfWeekDisabled: [0, 6],
    useCurrent: false,
  });

  $(".datepicker-end").datetimepicker({
    format: "DD-MMM-YYYY",
    showTodayButton: true,
    showClear: true,
    showClose: true,
    daysOfWeekDisabled: [0, 6],
    useCurrent: false,
  });

  //* start date picker on change event [select minimun date for end date datepicker]
  $(".datepicker-start").on("dp.change", function (e) {
    $(".datepicker-end").data("DateTimePicker").minDate(e.date);
  });

  $(".datepick-start").on("dp.change", function (e) {
    $(".datepick-end").data("DateTimePicker").date(moment(e.date));
    console.log(e.date);
  });

  //* Start date picker on change event [select maximum date for start date datepicker]
  $(".datepicker-end").on("dp.change", function (e) {
    if ($(".datepicker-start").val() !== "") {
      $(".datepicker-start").data("DateTimePicker").maxDate(e.date);
    } else {
      $(".datepicker-start")
        .data("DateTimePicker")
        .useCurrent(true)
        .maxDate(e.date);
    }
  });

  $(".monthyear").datetimepicker({
    format: "MMM-YYYY",
    showClear: true,
    useCurrent: false,
  });

  $(".daterange").daterangepicker({
    autoUpdateInput: false,
    locale: {
      format: "YYYY-MM-DD",
      cancelLabel: "Clear",
    },
  });

  $(".daterange").on("apply.daterangepicker", function (ev, picker) {
    $(this).val(
      picker.startDate.format("YYYY-MM-DD") +
        " - " +
        picker.endDate.format("YYYY-MM-DD")
    );
  });

  $(".daterange").on("cancel.daterangepicker", function (ev, picker) {
    $(this).val("");
  });

  $(".summernote-product").summernote({
    fontNames: [
      "Arial",
      "Arial Black",
      "Comic Sans MS",
      "Courier New",
      "Times New Roman",
    ],
    tabsize: 2,
    height: 200,
    toolbar: [
      ["style", ["style", "bold", "italic", "underline", "clear"]],
      ["fontname", ["fontname"]],
      ["fontsize", ["fontsize"]],
      ["color", ["color"]],
      ["height", ["height"]],
    ],
    placeholder: "write here...",
  });

  $(".float-number").autoNumeric("init", {
    aSep: ",",
    mDec: "0",
  });

  window.setTimeout(function () {
    $(".alert")
      .fadeTo(500, 0)
      .slideUp(500, function () {
        $(this).remove();
      });
  }, 4000);

  /**
   * Button Table Display
   */
  if ($(".tb_display").length > 0) {
    new $.fn.dataTable.Buttons(_table, {
      buttons: [
        {
          extend: "collection",
          className: "btn btn-warning btn-sm btn-round ml-auto text-white mr-1",
          text: '<i class="fas fa-download fa-fw"></i> Export',
          autoClose: true,
          buttons: [
            {
              extend: "pdfHtml5",
              text: '<i class="fas fa-file-pdf"></i> PDF',
              titleAttr: "Export to PDF",
              title: "",
              pageSize: "A4",
              exportOptions: {
                columns: ":visible:not(:last-child)",
              },
            },
            {
              extend: "csvHtml5",
              text: '<i class="fas fa-file"></i> CSV',
              titleAttr: "Export to CSV",
              title: "", //Set null value first row in file
              exportOptions: {
                columns: ":visible:not(:last-child)",
              },
            },
            {
              extend: "excelHtml5",
              text: '<i class="fas fa-file-excel"></i> Excel',
              titleAttr: "Export to Excel",
              title: "", //Set null value first row in file
              customize: function (xlsx) {
                var sheet = xlsx.xl.worksheets["sheet1.xml"];
                //* Bold and Border first column
                $("row:first c", sheet).attr("s", "27");
                //* Border all column except first column
                $("row:not(:first) c", sheet).attr("s", "25");
              },
              exportOptions: {
                columns: ":visible:not(:last-child)",
              },
            },
          ],
        },
      ],
    });

    _table.buttons().container().appendTo($("#dt-button"));
  }
});

/**
 * Table Display
 */
_table = $(".tb_display")
  .DataTable({
    serverSide: true,
    ajax: {
      url: CURRENT_URL + SHOWALL,
      type: "POST",
      data: function (d, setting) {
        const container = $(setting.nTable).closest(".container");
        const filter_page = container.find(".filter_page");
        const form = filter_page.find("form");
        const disabled = form.find("[disabled]");

        //! Remove attribute disabled field
        disabled.removeAttr("disabled");

        //* Serialize form array
        formTable = form.serializeArray();

        //! Set attribute disabled field
        disabled.prop("disabled", true);

        return $.extend({}, d, {
          form: formTable,
        });
      },
    },
    columnDefs: [
      {
        // 'targets': '_all',
        targets: [1, -1],
        orderable: false,
        width: 2,
      },
      {
        targets: 0,
        visible: false, //hide column
      },
    ],
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "All"],
    ],
    order: [],
    autoWidth: false,
    scrollX: true,
    scrollY: "50vh",
    scrollCollapse: true,
    fixedColumns: checkFixedColumns(),
  })
  .columns.adjust();

/**
 * Table Display Line
 */
_tableLine = $(".tb_displayline").DataTable({
  drawCallback: function (settings) {
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
    $(this).find(".select2").select2({
      placeholder: "Select an option",
      theme: "bootstrap",
      allowClear: true,
    });
    $(this).find(".rupiah").autoNumeric("init", {
      aSep: ".",
      aDec: ",",
      mDec: "0",
    });
  },
  initComplete: function (settings, json) {
    $(".tb_displayline").wrap(
      "<div style='overflow:auto; width:100%; position:relative;'></div>"
    );
  },
  lengthChange: false,
  paging: false,
  searching: false,
  ordering: false,
  autoWidth: false,
});

/**
 * Table Tree in Role
 */
$(".tb_tree").treeFy({
  initState: "expanded",
  treeColumn: 0,
  collapseAnimateCallback: function (row) {
    row.fadeOut();
  },
  expandAnimateCallback: function (row) {
    row.fadeIn();
  },
});

/**
 * Table Info on the modal
 */
_tableInfo = $(".table_info").DataTable({
  processing: true,
  drawCallback: function (settings) {
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
    $(this).find(".select2").select2({
      placeholder: "Select an option",
      theme: "bootstrap",
      allowClear: true,
    });
    $(this).find(".rupiah").autoNumeric("init", {
      aSep: ".",
      aDec: ",",
      mDec: "0",
    });
  },
  columnDefs: [
    {
      targets: [0, 1],
      orderable: false,
      width: 2,
    },
    {
      targets: 0,
      visible: false, //hide column
    },
  ],
  displayLength: -1,
  lengthChange: false,
  info: false,
  searching: false,
  paging: false,
  autoWidth: false,
  scrollX: true,
  scrollY: "350px",
  scrollCollapse: true,
});

/**
 * Table Report
 */
_tableReport = $(".table_report")
  .DataTable({
    serverSide: true,
    processing: true,
    language: {
      processing:
        '<i class="fa fa-spinner fa-spin fa-10x fa-fw"></i><span> Processing...</span>',
    },
    ajax: {
      url: CURRENT_URL + SHOWALL,
      type: "POST",
      data: function (d, setting) {
        return $.extend({}, d, {
          form: formReport,
          clear: clear,
        });
      },
    },
    columnDefs: [
      {
        targets: "_all",
        orderable: false,
      },
      {
        targets: 0,
        width: 2,
      },
      {
        targets: 1,
        width: "10%",
      },
    ],
    order: [],
    displayLength: -1,
    lengthChange: false,
    language: {
      info: "Total Data <span class='badge badge-primary'>_TOTAL_</span>",
      infoFiltered: "",
    },
    searching: false,
    paging: false,
    autoWidth: false,
    scrollX: true,
    scrollY: "70vh",
    scrollCollapse: true,
  })
  .columns.adjust();

/**
 * Table Approval on the modal
 */
_tableApproval = $(".table_approval")
  .DataTable({
    processing: true,
    columnDefs: [
      {
        targets: "_all",
        orderable: false,
      },
      {
        targets: [0, 1, 2, 3],
        visible: false, //hide column
      },
      {
        targets: [4],
        width: "20%",
      },
    ],
    order: [],
    displayLength: -1,
    lengthChange: false,
    info: false,
    searching: false,
    paging: false,
    autoWidth: false,
    // 'scrollX': true,
    // 'scrollY': '70vh',
    // 'scrollCollapse': true
  })
  .columns.adjust();

/**
 *
 * @returns check fixed column datatable
 */
function checkFixedColumns() {
  if ($(".tb_display thead th").length > 10) {
    return {
      rightColumns: 1,
      leftColumns: 0,
    };
  } else if ($(".tb_display thead th").length > 15) {
    return {
      rightColumns: 1,
      leftColumns: 3,
    };
  }
}

/**
 * Check length head table for scrollX
 * @returns
 */
function checkScrollX() {
  return $(".tb_display thead th").length > 7 ? true : false;
}

/**
 * Check length head table for set scrollY
 * @returns
 */
function checkScrollY() {
  return $(".tb_display thead th").length > 10 ? "400px" : "";
}

function reloadTable() {
  if ($(".tb_display").length > 0) _table.ajax.reload(null, false);
  else if ($(".table_report").length > 0) _tableReport.ajax.reload(null, false);
}

/**
 * Button Save Form Data
 *
 */
$(".save_form").click(function (evt) {
  const _this = $(this);
  const target = $(evt.target);
  const container = target.closest(".container");
  const parent = target.closest(".row");
  const card = container.find(".card");
  const actionMenu = card.attr("data-action-menu");
  const div = cardForm.find("div");
  let navTab = parent.find("ul.nav-tabs");
  let oriElement = _this.html();
  let oriTitle = container.find(".page-title").text();

  cardForm = parent.find(".card-form").length
    ? parent.find(".card-form")
    : parent.find(".card-main");

  let form = cardForm.find("form");
  let tabPane = null;

  let url = CURRENT_URL + CREATE;

  if (navTab.length) {
    const parent = target.parent().parent();
    const cardTab = parent.find("div.card-tab");
    navTab = parent.find("ul.nav-tabs");
    let a = navTab.find("li a.active");
    let href = a.attr("href");
    tabPane = cardTab.find(".tab-pane.active");

    if (a.prop("classList").contains("dropdown-toggle")) {
      a = a.closest("li").find("div a.active");
      href = a.attr("href");
    }

    form = tabPane.find("form");

    href = href.substring(1, href.length);
    url = `${ADMIN_URL}${href}${CREATE}`;
  }

  let action = "create";
  let checkAccess = isAccess(action, LAST_URL);

  let formData = new FormData();

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    let field;

    //* Populate field form header
    $.each(form, function () {
      const formHeader = $(this).find(".row");
      field = $(formHeader).find("input, select, textarea").not(".line");
    });

    //? Remove attribute disabled when submit data
    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        let className = field[i].className.split(/\s+/);

        form
          .find(
            "input:checkbox[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "]"
          )
          .not(".line")
          .removeAttr("disabled");

        //* Set field and value to formData
        if (
          field[i].type == "text" ||
          field[i].type == "textarea" ||
          field[i].type == "select-one" ||
          field[i].type == "password" ||
          field[i].type == "hidden"
        )
          formData.append(field[i].name, field[i].value);

        //* Field type input radio
        if (field[i].type == "radio") {
          if (field[i].checked) formData.append(field[i].name, field[i].value);
        }

        //* Field type input file and containing class control-upload-image
        if (
          field[i].type == "file" &&
          className.includes("control-upload-image")
        ) {
          //? Check condition upload add new image or not upload
          if (field[i].files.length > 0) {
            formData.append(field[i].name, field[i].files[0]);
          } else {
            let source = form.find(".img-result").attr("src");
            let imgSrc = source;

            if (typeof source !== "undefined" && source !== "")
              imgSrc = source.substr(source.lastIndexOf("/") + 1);

            formData.append(field[i].name, imgSrc);
          }
        }

        //* Field type textarea class summernote isEmpty to set value null
        if (
          form.find("textarea.summernote[name=" + field[i].name + "]").length &&
          $("[name =" + field[i].name + "]").summernote("isEmpty")
        ) {
          formData.append(field[i].name, "");
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
          let checked = field[i].checked ? "Y" : "N";

          formData.append(field[i].name, checked);
        }

        //* Field containing class datepicker
        if (
          className.includes("datepicker") ||
          className.includes("datepicker-start") ||
          className.includes("datepicker-end") ||
          className.includes("datetimepicker-start") ||
          className.includes("datetimepicker-end") ||
          className.includes("datepick") ||
          className.includes("datepick-start") ||
          className.includes("datepick-end") ||
          className.includes("date-start") ||
          className.includes("date-end")
        ) {
          let date = field[i].value;

          if (date !== "") {
            let dateTime = moment(date).format("YYYY-MM-DD HH:mm:ss");
            formData.append(field[i].name, dateTime);
          }
        }

        //* Field containing class rupiah
        if (className.includes("rupiah")) {
          formData.append(field[i].name, replaceRupiah(field[i].value));
        }

        //* Field containing class foreignkey
        if (className.includes("foreignkey")) {
          formData.append(field[i].name, $(field[i]).attr("set-id"));
        }
      }
    }

    //? Check in form exists Table role
    if (form.find("table.tb_tree").length > 0) {
      const table = form.find("table.tb_tree");
      const input = table.find("td input:checkbox");

      let isView = [];
      let isCreate = [];
      let isUpdate = [];
      let isDelete = [];
      let accessID = [];

      $.each(input, function () {
        let row_index = $(this).parent().parent().parent().parent().index();
        let field = $(this).attr("name");
        let menu_id = $(this).val();
        let menu = $(this).attr("data-menu");

        let access_id =
          typeof $(this).attr("id") !== "undefined" ? $(this).attr("id") : 0;

        let value;

        if ($(this).is(":checked")) {
          value = "Y";
        } else {
          value = "N";
        }

        if (field == "isview") {
          isView.push({
            row: row_index,
            view: value,
            menu_id: menu_id,
            menu: menu,
          });
        } else if (field == "iscreate") {
          isCreate.push({
            row: row_index,
            create: value,
            menu_id: menu_id,
            menu: menu,
          });
        } else if (field == "isupdate") {
          isUpdate.push({
            row: row_index,
            update: value,
            menu_id: menu_id,
            menu: menu,
          });
        } else if (field == "isdelete") {
          isDelete.push({
            row: row_index,
            delete: value,
            menu_id: menu_id,
            menu: menu,
          });
        }
        if (setSave !== "add")
          accessID.push({
            row: row_index,
            access_id,
          });
      });

      accessID = removeDuplicates(accessID, (item) => item.row);

      let arrRole = mergeArrayObjects(
        isView,
        isCreate,
        isUpdate,
        isDelete,
        accessID
      );

      formData.append("roles", JSON.stringify(arrRole));
    }

    //? Check in form exists Table Line
    if (_tableLine.context.length) {
      const rows = _tableLine.rows().nodes().to$();
      const th = $(rows).closest("table").find("th");

      let output = [];
      let tableHead = [];

      $.each(th, function (i, item) {
        if ($(item).attr("field")) {
          tableHead.push({
            position: i,
            name: $(item).attr("field"),
          });
        }
      });

      $.each(rows, function (i) {
        const tag = $(this).find("input, select, button, span");
        const tr = $(this).closest("tr");
        const td = tr.find("td");

        let row = {};

        //* Table cell from tag
        $.each(tag, function () {
          let className = this.className.split(/\s+/);
          let name = $(this).attr("name");
          let value = this.value;
          let id = $(this).attr("id");
          const foreignkey = form.find("input.foreignkey");

          //* Field containing class rupiah
          if (className.includes("rupiah")) value = replaceRupiah(value);

          if (this.type === "text" || this.type === "select-one") {
            //* Field containing class datepicker
            if (className.includes("datepicker")) {
              let date = value;
              row[name] = date;

              if (date !== "") {
                let dateTime = moment(date).format("YYYY-MM-DD HH:mm:ss");
                row[name] = dateTime;
              }
            } else {
              row[name] = value;
            }
          } else if (this.type === "checkbox") {
            row[name] = $(this).is(":checked") ? "Y" : "N";
          } else if (typeof name !== "undefined") {
            if (id !== "") row[name] = id;
            else row[name] = "";

            if (className.includes("reference-key")) row[name] = value; // Get value reference key
          }

          if (foreignkey.length)
            row[foreignkey.attr("name")] = foreignkey.attr("set-id");
        });

        //* Table cell data
        $.each(td, function (i, item) {
          let txtValue = $(item).text();

          $.each(tableHead, function (idx, column) {
            if (i == column.position) row[column.name] = txtValue;
          });
        });

        output[i] = row;
      });

      formData.append("table", JSON.stringify(output));
    }

    //* Set primary key on the property "id"
    if (
      (tabPane == null && setSave !== "add") ||
      (tabPane != null &&
        typeof tabPane.attr("set-save") !== "undefined" &&
        tabPane.attr("set-save") === "update")
    )
      formData.append("id", ID);

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
        $(".x_form").prop("disabled", true);
        $(".close_form").prop("disabled", true);
        loadingForm(form.prop("id"), "facebook");
      },
      complete: function () {
        $(_this).html(oriElement).prop("disabled", false);
        $(".x_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
        hideLoadingForm(form.prop("id"));
      },
      success: function (result) {
        if (result[0].success) {
          Toast.fire({
            type: "success",
            title: result[0].message,
          });

          //? Action menu is form
          if (actionMenu === "F") {
            //TODO: Call function and set data
            showFormData(form);
            clearErrorForm(form);
          } else {
            if (container.find(".modal").length) {
              let modal = parent.find(".modal");
              let modalID = modal.attr("id");

              if (parent.find(".modal-tab").length || navTab.length) {
                const parent = target.parent().parent();
                const tabPaneAll = parent.find(".tab-pane");
                const navLink = navTab.find("li.nav-item a");
                tabPane.attr("set-save", "update");

                if (typeof result[0].foreignkey !== "undefined") {
                  ID = result[0].foreignkey;

                  tabPane.attr("set-id", ID);

                  $.each(navLink, function () {
                    if (!this.classList.contains("active"))
                      $(this).removeClass("disabled");
                  });

                  $.each(tabPaneAll, function () {
                    const form = $(this).find("form");
                    form.find("input.foreignkey").attr("set-id", ID);
                  });
                }

                if (typeof result[0].primarykey !== "undefined")
                  ID = result[0].primarykey;

                if (typeof result[0].header !== "undefined")
                  putFieldData(form, result[0].header);

                if (result[0].line) {
                  let arrLine = result[0].line;

                  if (_tableLine.context.length) {
                    _tableLine.clear().draw();

                    let line = JSON.parse(arrLine);
                    _tableLine.rows.add(line).draw(false);
                  }
                }
              } else {
                $(`#${modalID}`).modal("hide");
                clearForm(evt);
              }

              clearErrorForm(form);
            } else {
              clearForm(evt);
              const cardBody = container.find(".card-body");

              $.each(cardBody, function (idx, elem) {
                let className = elem.className.split(/\s+/);
                if (className.includes("card-main")) {
                  $(this).css("display", "block");
                  // Remove breadcrumb list
                  let li = ul.find("li");
                  $.each(li, function (idx, elem) {
                    if (idx > 2) elem.remove();
                  });
                  if (parent.find("div.filter_page").length > 0) {
                    parent.find("div.filter_page").css("display", "block");
                  }
                }
                if (className.includes("card-form")) {
                  const cardHeader = parent.find(".card-header");
                  cardHeader.find("button").show();
                  $(this).css("display", "none");
                }
              });

              cardBtn.css("display", "none");

              const cardHeader = parent.find(".card-header");
              const btnList = cardHeader.find("button").prop("classList");

              if (btnList.contains("new_form"))
                cardHeader.find("button").css("display", "block");

              cardTitle.html(oriTitle);

              //TODO: Call reloadTable();
              $(".btn_requery").click();
            }
          }
        } else if (result[0].error) {
          errorForm(form, result);
          $("html, body").animate(
            {
              scrollTop: $(".container").offset().top,
            },
            1500
          );
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

    // logic after insert / update data to set attribute based on field isactive condition
    for (let i = 0; i < field.length; i++) {
      let fieldActive = form.find("input.active");
      // Check element name is not null and any field checkbox active
      if (field[i].name !== "" && fieldActive.length) {
        let className = field[i].className.split(/\s+/);
        if (form.find("input.active").is(":checked")) {
          if (!fieldReadOnly.includes(field[i].name)) {
            form
              .find(
                "input:checkbox[name=" +
                  field[i].name +
                  "], select[name=" +
                  field[i].name +
                  "]"
              )
              .removeAttr("disabled");
          } else {
            form
              .find(
                "input:checkbox[name=" +
                  field[i].name +
                  "], select[name=" +
                  field[i].name +
                  "]"
              )
              .not(".line")
              .prop("disabled", true);
          }
        } else {
          if (!className.includes("active")) {
            form
              .find(
                "input:checkbox[name=" +
                  field[i].name +
                  "], select[name=" +
                  field[i].name +
                  "]"
              )
              .not(".line")
              .prop("disabled", true);
          }
        }
      } else {
        //? Set attribute disabled based on default field or exist attribute edit-disabled
        if (
          fieldReadOnly.includes(field[i].name) ||
          (setSave !== "add" && $(field[i]).attr("edit-disabled")) ||
          setSave === "detail"
        )
          form
            .find(
              "input:checkbox[name=" +
                field[i].name +
                "], select[name=" +
                field[i].name +
                "]"
            )
            .not(".line")
            .prop("disabled", true);
      }
    }
  } else if (checkAccess[0].success && checkAccess[0].message == "N") {
    Toast.fire({
      type: "error",
      title: "You are role don't have permission, please reload !!",
    });
  } else {
    Toast.fire({
      type: "error",
      title: checkAccess[0].message,
    });
  }
});

/**
 * Button edit data
 * Show data on the form
 */
function Edit(id, status, last_url) {
  const parent = $(".container");
  const cardBody = parent.find(".card-body");
  const cardForm = parent.find(".card-form");
  let form = cardForm.find("form");
  const main_page = parent.find(".main_page");
  const modalTab = parent.find(".modal-tab");
  let s = parent.find(".card");

  ID = id;

  let formList;
  let action = "update";

  if (typeof last_url === "undefined" || last_url === "") last_url = LAST_URL;

  let checkAccess = isAccess(action, last_url);

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    //? Identified card is more than 1 page
    if (s.length > 1) s = parent.find(".page-inner");
    else s = main_page.find(".card");

    s.length &&
      (s.addClass("is-loading"),
      setTimeout(function () {
        $.each(cardBody, function (idx, elem) {
          let className = elem.className.split(/\s+/);

          if (cardBody.length > 1) {
            if (className.includes("card-main")) {
              $(this).css("display", "none");

              const pageHeader = parent.find(".page-header");
              ul = pageHeader.find("ul.breadcrumbs");

              // Append list separator and text create
              ul.find("li.nav-item > a").attr("href", CURRENT_URL);

              let list =
                '<li class="separator">' +
                '<i class="flaticon-right-arrow"></i>' +
                "</li>";

              if (
                typeof status === "undefined" ||
                status === "" ||
                status === "DR"
              )
                list +=
                  '<li class="nav-item">' +
                  '<a class="text-primary font-weight-bold">Update</a>' +
                  "</li>";
              else
                list +=
                  '<li class="nav-item">' +
                  '<a class="text-primary font-weight-bold">Detail</a>' +
                  "</li>";

              ul.append(list);

              if (parent.find("div.filter_page").length > 0)
                parent.find("div.filter_page").css("display", "none");
            }

            if (className.includes("card-form")) {
              const card = cardForm.closest(".card");
              const cardHeader = card.find(".card-header");

              cardHeader.find("button", "a").css("display", "none");
              $(this).css("display", "block");
              cardBtn.css("display", "block");
              formList = $(this).prop("classList");
            }
          }
        });

        if (modalTab.length) {
          let modalID = modalTab.attr("id");

          $(`#${modalID}`).modal({
            backdrop: "static",
            keyboard: false,
          });

          const navLink = modalTab.find("li.nav-item a");
          const tabPane = modalTab.find(".tab-pane.active");

          $.each(navLink, function () {
            if (!this.classList.contains("active"))
              $(this).addClass("disabled");
          });

          Scrollmodal();
          form = tabPane.find("form");
        }

        let url = CURRENT_URL + SHOW + ID;

        setSave =
          typeof status === "undefined" || status === "" || status === "DR"
            ? "update"
            : "detail";

        $.ajax({
          url: url,
          type: "GET",
          cache: false,
          dataType: "JSON",
          beforeSend: function () {
            $(".save_form").attr("disabled", true);
            $(".x_form").attr("disabled", true);
            $(".close_form").attr("disabled", true);
            loadingForm(form.prop("id"), "facebook");
          },
          complete: function () {
            if (
              typeof status === "undefined" ||
              status === "" ||
              status === "DR" ||
              status === "IP"
            )
              $(".save_form").removeAttr("disabled");

            $(".x_form").removeAttr("disabled");
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

                  let btnAction = _tableLine
                    .rows()
                    .nodes()
                    .to$()
                    .find("button");

                  const field = _tableLine
                    .rows()
                    .nodes()
                    .to$()
                    .find("input, select");

                  /**
                   * Logic for set detail when status not draft
                   */
                  if (setSave === "detail" && status !== "DR") {
                    // Button add row table line
                    $(".add_row, .create_line").css("display", "none");

                    $.each(btnAction, function (index, item) {
                      let className = item.className.split(/\s+/);

                      if (className.includes("btn_accept") && status !== "IP")
                        $(this).css("display", "none");

                      if (className.includes("btn_delete"))
                        $(this).css("display", "none");
                    });

                    $.each(field, function (index, item) {
                      const tr = $(this).closest("tr");
                      const className = item.className.split(/\s+/);

                      if (!className.includes("updatable") || status !== "IP")
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
                  } else {
                    // Button add row table line
                    $(".add_row, .create_line").css("display", "block");

                    btnAction.css("display", "block");
                  }
                }
              }

              if (arrMsg.role) {
                let arrLine = arrMsg.role;

                if (form.find("table.tb_tree").length > 0) {
                  for (let i = 0; i < arrLine.length; i++) {
                    const table = form.find("table.tb_tree");
                    const input = table.find("td input:checkbox");

                    $.each(input, function (idx, elem) {
                      // Menu parent
                      if ($(elem).attr("data-menu") === "parent") {
                        if (
                          arrLine[i].sys_menu_id == $(elem).val() &&
                          arrLine[i].sys_submenu_id == 0
                        ) {
                          if (
                            (arrLine[i].isview == "Y" &&
                              $(elem).attr("name") === "isview") ||
                            (arrLine[i].iscreate == "Y" &&
                              $(elem).attr("name") === "iscreate") ||
                            (arrLine[i].isupdate == "Y" &&
                              $(elem).attr("name") === "isupdate") ||
                            (arrLine[i].isdelete == "Y" &&
                              $(elem).attr("name") === "isdelete")
                          ) {
                            $(elem).prop("checked", true);
                          } else {
                            $(elem).prop("checked", false);
                          }

                          // Set attribute id element to value sys_access_menu_id
                          $(elem).attr("id", arrLine[i].sys_access_menu_id);
                        }
                      } else {
                        if (arrLine[i].sys_submenu_id === $(elem).val()) {
                          if (
                            (arrLine[i].isview == "Y" &&
                              $(elem).attr("name") === "isview") ||
                            (arrLine[i].iscreate == "Y" &&
                              $(elem).attr("name") === "iscreate") ||
                            (arrLine[i].isupdate == "Y" &&
                              $(elem).attr("name") === "isupdate") ||
                            (arrLine[i].isdelete == "Y" &&
                              $(elem).attr("name") === "isdelete")
                          ) {
                            $(elem).prop("checked", true);
                          } else {
                            $(elem).prop("checked", false);
                          }

                          // Set attribute id element to value sys_access_menu_id
                          $(elem).attr("id", arrLine[i].sys_access_menu_id);
                        }
                      }
                    });
                  }
                }
              }

              if (arrMsg.header) {
                let data = arrMsg.header;
                putFieldData(form, data);

                if (modalTab.length) {
                  const navLink = modalTab.find("li.nav-item a");
                  const tabPaneAll = modalTab.find(".tab-pane");

                  $.each(navLink, function () {
                    if (!this.classList.contains("active"))
                      $(this).removeClass("disabled");
                  });

                  $.each(tabPaneAll, function () {
                    const form = $(this).find("form");
                    form.find("input.foreignkey").attr("set-id", id);

                    if ($(this).prop("classList").contains("active")) {
                      $(this).attr("set-save", setSave);
                      $(this).attr("set-id", id);
                    }
                  });
                } else {
                  for (let i = 0; i < data.length; i++) {
                    let fieldInput = data[i].field;
                    let label = data[i].label;

                    if (formList.contains("modal") && fieldInput === "title") {
                      modalTitle.html(capitalize(label));
                    } else if (fieldInput === "title") {
                      cardTitle.html(capitalize(label));
                    }
                  }
                }
              }

              $("html, body").animate(
                {
                  scrollTop: $(".main-panel").offset().top,
                },
                500
              );
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

        s.removeClass("is-loading");
      }, 200));
  } else if (checkAccess[0].success && checkAccess[0].message == "N") {
    Toast.fire({
      type: "error",
      title: "You are role don't have permission, please reload !!",
    });
  } else {
    Toast.fire({
      type: "error",
      title: checkAccess[0].message,
    });
  }
}

/**
 * Button delete data
 */
function Destroy(id) {
  let url = CURRENT_URL + DELETE + id;

  let action = "delete";

  let checkAccess = isAccess(action, LAST_URL);

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
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
      if (data.value)
        //value is true

        $.getJSON(url, function (result) {
          if (result[0].success) {
            Swal.fire({
              title: "Deleted!",
              text: "Your data has been deleted.",
              type: "success",
              showConfirmButton: false,
              timer: 1000,
            });

            reloadTable();
          } else if (!result[0].error) {
            Swal.fire({
              title: "Error!",
              text: result[0].message,
              type: "error",
              showConfirmButton: true,
            });

            reloadTable();
          } else {
            console.info(result);
          }
        }).fail(function (jqXHR, textStatus, errorThrown) {
          console.info(errorThrown);
          reloadTable();
        });
    });
  } else if (checkAccess[0].success && checkAccess[0].message == "N") {
    Toast.fire({
      type: "error",
      title: "You are role don't have permission, please reload !!",
    });
  } else {
    Toast.fire({
      type: "error",
      title: checkAccess[0].message,
    });
  }
}

$(".tb_displayline, .tb_displaytab").on("click", ".btn_delete", function (evt) {
  evt.preventDefault();
  const _this = $(this);
  const tr = _this.closest("tr");
  const row = _tableLine.row(tr);
  let id = this.id;

  let oriElement = _this.html();

  $(_this)
    .html(
      '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
    )
    .prop("disabled", true);

  if (id === "") {
    setTimeout(function () {
      row.remove().draw(false);
      $(_this).html(oriElement).prop("disabled", false);
    }, 100);
  } else {
    Swal.fire({
      title: "Delete?",
      text: "Are you sure to delete the selected data line ? ",
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Ok",
      cancelButtonText: "Close",
      reverseButtons: true,
    }).then((data) => {
      if (data.value) row.remove().draw(false);
    });

    $(_this).html(oriElement).prop("disabled", false);
  }
});

/**
 * Get data document action access
 * @param {*} status
 * @param {*} url
 * @returns
 */
function getDocAction(status, url) {
  let result = [];

  $.ajax({
    type: "POST",
    url: ADMIN_URL + "docaction/getDocaction",
    data: {
      status: status,
      url: url,
    },
    async: false,
    cache: false,
    dataType: "JSON",
    success: function (response) {
      result.push(response);
    },
  });

  return result;
}

/**
 * Process Document Action
 * @param {*} id
 * @param {*} status
 */
function docProcess(id, status) {
  let action = "update";
  let checkAccess = isAccess(action, LAST_URL);

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    let docAction = getDocAction(status, LAST_URL);
    let html =
      '<div class="d-flex justify-content-center">' + '<select id="docaction">';

    html += '<option value=""></option>';

    if (docAction[0].length > 0)
      $.each(docAction[0], function (i, item) {
        html += `<option value="${item.id}">${item.text}</option>`;
      });

    html += "</select>" + "</div>";

    Swal.fire({
      title: "Document Action",
      html: html,
      showCancelButton: true,
      cancelButtonColor: "#d33",
      confirmButtonText: "Ok",
      cancelButtonText: "Close",
      showLoaderOnConfirm: true,
      reverseButtons: true,
      onOpen: () => {
        $("#docaction").select2({
          placeholder: "Select an option",
          width: "40%",
          theme: "bootstrap",
          dropdownAutoWidth: true,
          allowClear: true,
        });
      },
      preConfirm: (generate) => {
        return new Promise(function (resolve) {
          let docAction = $("#docaction option:selected").val();

          let url =
            CURRENT_URL + "/processIt?id=" + id + "&docaction=" + docAction;

          $.getJSON(url, function (result) {
            if (result[0].success) {
              if (result[0].message == true) {
                Swal.fire({
                  title: "Success !!",
                  text: "Your data has been process",
                  type: "success",
                  showConfirmButton: false,
                  timer: 1000,
                });
              } else {
                Swal.fire({
                  title: "Error!",
                  text: result[0].message,
                  type: "error",
                  showConfirmButton: false,
                  timer: 1000,
                });
              }

              reloadTable();
            }

            if (
              (typeof result[0].error !== "undefined" && result[0].error) ||
              (typeof result[0].error !== "undefined" && !result[0].error)
            ) {
              Swal.showValidationMessage(result[0].message);
              resolve(false);
            }
          }).fail(function (jqXHR, textStatus, errorThrown) {
            Swal.showValidationMessage(errorThrown);
            resolve(false);
            reloadTable();
          });
        });
      },
      allowOutsideClick: () => !Swal.isLoading(),
    });
  } else if (checkAccess[0].success && checkAccess[0].message == "N") {
    Toast.fire({
      type: "error",
      title: "You are role don't have permission, please reload !!",
    });
  } else {
    Toast.fire({
      type: "error",
      title: checkAccess[0].message,
    });
  }
}

/**
 * Button close form
 * @x_form button only in modal
 * @close_form button in card-action
 */
$(document).on("click", ".x_form, .close_form, .reset_form", function (evt) {
  const target = $(evt.currentTarget);
  const container = target.closest(".container");
  const card = container.find(".card");
  const actionMenu = card.attr("data-action-menu");
  const div = card.find("div");
  const modalTab = target.closest(".modal-tab");

  let oriTitle = container.find(".page-title").text();

  setSave = null;

  if (actionMenu !== "F") {
    if (target.attr("data-dismiss") !== "modal") {
      const parent = target.closest(".container");
      const cardBody = parent.find(".card-body");

      $.each(cardBody, function (idx, elem) {
        let className = elem.className.split(/\s+/);

        if (className.includes("card-main")) {
          $(this).css("display", "block");

          // Remove breadcrumb list
          let li = ul.find("li");
          $.each(li, function (idx, elem) {
            if (idx > 2) elem.remove();
          });

          if (parent.find("div.filter_page").length > 0) {
            parent.find("div.filter_page").css("display", "block");
          }
        }

        if (className.includes("card-form")) {
          $(this).css("display", "none");
        }
      });

      cardBtn.css("display", "none");

      const cardHeader = parent.find(".card-header");
      cardHeader.find("button").show();
    }

    cardTitle.html(oriTitle);

    //TODO: Call reloadTable();
    $(".btn_requery").click();

    $("html, body").animate(
      {
        scrollTop: $(".main-panel").offset().top,
      },
      500
    );
  }

  if (modalTab.length) {
    const tabPaneAll = modalTab.find(".tab-pane");
    const navLink = modalTab.find("li.nav-item a");

    $.each(navLink, function (i) {
      if (i == 0) {
        $(this).addClass("show active");
      } else {
        $(this).removeClass("active");
      }
    });

    $.each(tabPaneAll, function (i) {
      const form = $(this).find("form");
      form.find("input.foreignkey").removeAttr("set-id");
      $(this).removeAttr("set-save");
      $(this).removeAttr("set-id");

      if (i == 0) {
        $(this).addClass("show active");
      } else {
        $(this).removeClass("show active");
      }
    });
  }

  ID = 0;

  clearForm(evt);

  //TODO: Hide content there is attribute show-after-save
  $.each(div, function () {
    if ($(this).attr("show-after-save")) {
      $(this).addClass("d-none");
    }
  });

  //! Clear button attribute disable
  $(this).removeAttr("disabled");
  $(".save_form").removeAttr("disabled");
});

/**
 * Button new data
 */
$(document).on("click", ".new_form", function (evt) {
  const target = $(evt.target);
  const parent = target.closest(".container");
  const cardBody = parent.find(".card-body");
  const main_page = parent.find(".main_page");
  const modalTab = parent.find(".modal-tab");
  let s = parent.find(".card");

  let form;
  let action = "create";
  let oriTitle = parent.find(".page-title").text();
  let _this = $(this);
  let oriElement = _this.html();
  let textElement = _this.text().trim();

  let checkAccess = isAccess(action, LAST_URL);
  $(this).tooltip("hide");

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    $(_this)
      .html(
        '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' +
          textElement
      )
      .prop("disabled", true);

    //? Identified card is more than 1 page
    if (s.length > 1) s = parent.find(".page-inner");
    else s = main_page.find(".card");

    s.length &&
      (s.addClass("is-loading"),
      setTimeout(function () {
        $.each(cardBody, function (idx, elem) {
          let className = elem.className.split(/\s+/);

          if (cardBody.length > 1) {
            if (className.includes("card-main")) {
              $(this).css("display", "none");

              ul = parent.find(".page-header > ul.breadcrumbs");

              // Append list separator and text create
              ul.find("li.nav-item > a").attr("href", CURRENT_URL);

              let list =
                '<li class="separator">' +
                '<i class="flaticon-right-arrow"></i>' +
                "</li>";

              list +=
                '<li class="nav-item">' +
                '<a class="text-primary font-weight-bold">Create</a>' +
                "</li>";

              ul.append(list);

              if (parent.find("div.filter_page").length)
                parent.find("div.filter_page").css("display", "none");
            }

            if (className.includes("card-form")) {
              const cardHeader = target.closest(".card-header");
              cardHeader.find("button").css("display", "none");

              $(this).css("display", "block");
              cardBtn.css("display", "block");

              cardTitle.html("New " + oriTitle);

              form = $(this).find("form");
            }
          }
        });

        if (modalTab.length) {
          let modalID = modalTab.attr("id");

          $(`#${modalID}`).modal({
            backdrop: "static",
            keyboard: false,
          });

          const navLink = modalTab.find("li.nav-item a");

          $.each(navLink, function () {
            if (!this.classList.contains("active"))
              $(this).addClass("disabled");
          });

          Scrollmodal();
          form = modalTab.find("form");
        }

        const field = form.find("input, textarea, select");

        for (let i = 0; i < field.length; i++) {
          let fields = [];

          if (field[i].name !== "") {
            // set field is readonly or disabled by default
            if (field[i].readOnly || field[i].disabled)
              fieldReadOnly.push(field[i].name);

            // set field is checked by default from set attribute on the field
            if (
              field[i].type == "checkbox" &&
              fieldChecked.includes(field[i].name)
            )
              form
                .find("input:checkbox[name=" + field[i].name + "]")
                .prop("checked", true);

            //? Condition field and contain attribute hide-field
            if ($(field[i]).attr("hide-field")) {
              fields = $(field[i])
                .attr("hide-field")
                .split(",")
                .map((element) => element.trim());

              if (field[i].type === "checkbox") {
                if (field[i].checked) {
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

            //? Condition field and contain attribute show-field
            if ($(field[i]).attr("show-field")) {
              fields = $(field[i])
                .attr("show-field")
                .split(",")
                .map((element) => element.trim());

              if (field[i].type === "checkbox") {
                if (field[i].checked) {
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
                } else if (field[i].type === "checkbox") {
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
          }
        }

        if (form.find("input:file.control-upload-image").length)
          form.find(".img-result").attr("src", "");

        if (form.find("input.code").length) setSeqCode(form);

        if (form.find("select.select-data").length)
          initSelectData(form.find("select.select-data"));

        if (form.find(".summernote").length)
          initSummerNote(form.find(".summernote"));

        if (form.find('input[type="checkbox"].active').length)
          form.find('input[type="checkbox"].active').prop("checked", true);

        if (form.find("button.delete_line").length)
          form.find("button.delete_line").hide();

        $(".check-all").parent().hide();

        setSave = "add";

        $(_this).html(oriElement).prop("disabled", false);
        s.removeClass("is-loading");
      }, 200));
  } else if (checkAccess[0].success && checkAccess[0].message == "N") {
    Toast.fire({
      type: "warning",
      title: "You are role don't have permission, please reload !!",
    });
  } else {
    Toast.fire({
      type: "error",
      title: checkAccess[0].message,
    });
  }
});

/**
 * Process for Export based on filter form
 */
$(".btn_export").click(function (evt) {
  const container = $(evt.target).closest(".container");
  const cardFilter = container.find(".card-filter");
  let form = cardFilter.find("form");

  let _this = $(this);
  let oriElement = _this.html();

  form.attr("action", SITE_URL + EXPORT);
  form.attr("method", "POST");

  // form submit to export data
  form.submit();

  $(_this)
    .html(
      '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
    )
    .prop("disabled", true);

  setTimeout(function () {
    $(_this).html(oriElement).prop("disabled", false);
  }, 700);
});

/**
 * Button Filter datatable form filter
 */
$(".btn_filter").click(function (evt) {
  let _this = $(this);
  const container = _this.parents(".container");
  const main_page = container.find(".main_page");
  const form = container.find("form");
  let oriElement = _this.html();
  let textElement = _this.text().trim();
  let s = container.find(".card");

  //? Identified card is more than 1 page
  if (s.length > 1) s = container.find(".page-inner");
  else s = main_page.find(".card");

  formTable = form.serializeArray();

  $(_this)
    .html(
      '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' +
        textElement
    )
    .prop("disabled", true);

  s.length &&
    (s.addClass("is-loading"),
    reloadTable(),
    setTimeout(function () {
      s.removeClass("is-loading");
      $(_this).html(oriElement).prop("disabled", false);
    }, 700));
});

/**
 * Button ReQuery DataTable
 */
$(".btn_requery").click(function () {
  let _this = $(this);
  const container = _this.parents(".container");
  const main_page = container.find(".main_page");
  let s = container.find(".card");

  //? Identified card is more than 1 page
  if (s.length > 1) s = container.find(".page-inner");
  else s = main_page.find(".card");

  clear = false;

  s.length &&
    (s.addClass("is-loading"),
    reloadTable(),
    setTimeout(function () {
      s.removeClass("is-loading");
    }, 500));
});

/**
 * Event add row table line
 */
$(".add_row").click(function (evt) {
  const target = $(evt.target);
  const modal = target.closest(".modal");
  const navTab = modal.find("ul.nav-tabs");
  const form = target.closest("form");
  let action = "create";
  let checkAccess = isAccess(action, LAST_URL);

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    let _this = $(this);
    let oriElement = _this.html();
    let textElement = _this.text().trim();

    let url = CURRENT_URL + TABLE_LINE;

    if (navTab.length) {
      const cardTab = modal.find("div.card-tab");
      let a = navTab.find("li a.active");
      let href = a.attr("href");
      tabPane = cardTab.find(".tab-pane.active");

      if (a.prop("classList").contains("dropdown-toggle")) {
        a = a.closest("li").find("div a.active");
        href = a.attr("href");
      }

      href = href.substring(1, href.length);
      url = `${ADMIN_URL}${href}${TABLE_LINE}`;
    }

    const field = form.find("input, textarea, select").not(".line");
    const errorText = form.find("small");

    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        form
          .find(
            "input:checkbox[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "]"
          )
          .removeAttr("disabled");
      }
    }

    let formData = new FormData(form[0]);

    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(".close_form").attr("disabled", true);
        $(".save_form").attr("disabled", true);
        $(_this)
          .html(
            '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' +
              textElement
          )
          .prop("disabled", true);

        for (let i = 0; i < fieldReadOnly.length; i++) {
          form
            .find(
              "input:checkbox[name=" +
                fieldReadOnly[i] +
                "], select[name=" +
                fieldReadOnly[i] +
                "]"
            )
            .attr("disabled", true);
        }
      },
      complete: function () {
        $(".close_form").removeAttr("disabled");
        $(".save_form").removeAttr("disabled");
        $(_this).html(oriElement).prop("disabled", false);
      },
      success: function (result) {
        if (result[0].error) {
          errorForm(form, result);
        } else {
          _tableLine.row.add(result).draw(false);

          for (let i = 0; i < field.length; i++) {
            if (field[i].name !== "") {
              form
                .find(
                  "input:checkbox[name=" +
                    field[i].name +
                    "], select[name=" +
                    field[i].name +
                    "]"
                )
                .closest(".form-group")
                .removeClass("has-error");
            }
          }

          // clear text error element small
          for (let l = 0; l < errorText.length; l++) {
            if (errorText[l].id !== "")
              form.find("small[id=" + errorText[l].id + "]").html("");
          }
        }
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
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

/**
 * Event create table line
 */
$(".create_line").click(function (evt) {
  let action = "create";
  let checkAccess = isAccess(action, LAST_URL);
  let formData = $(this).closest("form");

  if (checkAccess[0].success && checkAccess[0].message == "Y") {
    let _this = $(this);
    let oriElement = _this.html();
    let textElement = _this.text().trim();

    let isFree = "N";
    if (formData.find('input:checkbox[name="isinternaluse"]').is(":checked"))
      isFree = "Y";

    $(_this)
      .html(
        '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' +
          textElement
      )
      .prop("disabled", true);

    setTimeout(function () {
      $(_this).html(oriElement).prop("disabled", false);

      $("#modal_product_info").modal({
        backdrop: "static",
        keyboard: false,
      });

      loadingForm("product_info", "ios");

      $("#modal_product_info").on("shown.bs.modal", function (e) {
        e.preventDefault();
        const target = $(e.target);
        const form = target.find("form");

        let url = ADMIN_URL + "product/showProductInfo/?data=null";

        form[0].reset();

        setTimeout(function () {
          hideLoadingForm("product_info");

          if (form.find("select.select-data").length > 0) {
            let select = form.find("select.select-data");
            initSelectData(select);
          }

          if (form.find('input:hidden[name="isfree"]'))
            form.find('input:hidden[name="isfree"]').val(isFree);

          _tableInfo.ajax.url(url).load().columns.adjust();
        }, 50);
      });
    }, 100);
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

/**
 * Clear content modal product info
 */
$("#modal_product_info").on("hidden.bs.modal", function (evt) {
  const target = $(evt.target);
  const form = target.find("form");

  //TODO: Clear form content
  form[0].reset();

  //TODO: Clear datatable
  _tableInfo.clear().draw();
});

/**
 * Do Not Submit data Enter Keypress the form_product_info
 */
$("#form_product_info").on("keypress", function (evt) {
  let keyPressed = evt.keyCode || evt.which;

  if (keyPressed === 13) {
    evt.preventDefault();
    return false;
  }
});

/**
 * Refresh data table info
 */
$(".btn_requery_info").click(function (evt) {
  const target = $(evt.target);
  const modalContent = target.closest(".modal-content");
  const form = modalContent.find("form");

  let url = ADMIN_URL + "product/showProductInfo/?";
  let formData = form.serialize();

  $(this).tooltip("hide");

  _tableInfo.ajax
    .url(url + formData)
    .load()
    .columns.adjust();
});

/**
 * Btn save info for set data from table info to table line
 */
$(".btn_save_info").click(function (evt) {
  const modal = $(this).closest(".modal");
  const modalBody = modal.find(".modal-body");

  const checkbox = _tableInfo
    .rows()
    .nodes()
    .to$()
    .find('input:checkbox[name="check_data"]:checked');

  let _this = $(this);
  let oriElement = _this.html();

  if (checkbox.length > 0) {
    let url = CURRENT_URL + TABLE_LINE + CREATE;

    let output = [];

    $.each(checkbox, function (i) {
      let tr = $(this).closest("tr");
      let tag = tr.find("input, select");

      let data = [];

      $.each(tag, function (index, element) {
        let row = [];
        let name = $(element).attr("name");
        let value = $(element).val();

        if ($(element).attr("type") !== "checkbox") {
          row = {
            [name]: value,
          };
        } else {
          if (name === "check_data")
            row = {
              product_id: value,
            };
          else
            row = {
              [name]: $(element).is(":checked"),
            };
        }

        data.push(row);
      });

      output[i] = data;
    });

    let jsonString = JSON.stringify(output);

    $.ajax({
      url: url,
      type: "POST",
      data: {
        data: jsonString,
      },
      cache: false,
      dataType: "JSON",
      beforeSend: function () {
        $(_this)
          .html(
            '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
          )
          .prop("disabled", true);
        $(".btn_requery_info").attr("disabled", true);
        $(".btn_close_info").attr("disabled", true);
        loadingForm(modalBody.attr("id"), "ios");
      },
      complete: function () {
        $(_this).html(oriElement).prop("disabled", false);
        $(".btn_requery_info").removeAttr("disabled");
        $(".btn_close_info").removeAttr("disabled");
        hideLoadingForm(modalBody.attr("id"));
      },
      success: function (result) {
        $("#" + modal.attr("id")).modal("hide");
        _tableLine.rows.add(result).draw(false);
      },
      error: function (jqXHR, exception) {
        showError(jqXHR, exception);
      },
    });
  } else {
    Toast.fire({
      type: "warning",
      title: "Please selected data !!",
    });
  }
});

/**
 * Event Click Button OK Report
 */
$(".btn_ok_form").on("click", function (evt) {
  const target = $(evt.target);
  const pageInner = target.closest(".page-inner");
  const card = target.closest(".card");
  const cardTableReport = pageInner.find(".card-table-report");
  const floatRight = cardTableReport.find(".float-right");
  const form = card.find("form");
  const disabled = form.find("[disabled]");
  const checkAll = $(".ischeckall");

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

  //* Set clear to true
  clear = false;

  //* Show Toolbar Button
  floatRight.removeClass("d-none");

  //TODO: Loading and processing
  pageInner.length &&
    (pageInner.addClass("is-loading"),
    reloadTable(),
    setTimeout(function () {
      checkAll.prop("checked", true);
      pageInner.removeClass("is-loading");
    }, 700));

  //* Show Toolbar Button
  cardTableReport.addClass("d-block");

  /**
   * Button Table Report
   */
  let button = {
    buttons: [
      {
        extend: "colvis",
        className: "btn btn-primary btn-sm btn-round ml-auto text-white",
        text: '<i class="fas fa-table fa-fw"></i> Visibility',
        attr: {
          title: "Column Visibility",
        },
      },
      {
        extend: "collection",
        className: "btn btn-warning btn-sm btn-round ml-auto text-white mr-1",
        text: '<i class="fas fa-download fa-fw"></i> Export',
        attr: {
          title: "Export",
        },
        autoClose: true,
        buttons: [
          {
            extend: "excelHtml5",
            text: '<i class="fas fa-file-excel"></i> Export',
            titleAttr: "Export to Excel",
            title: "",
            customize: function (xlsx) {
              var sheet = xlsx.xl.worksheets["sheet1.xml"];
              //* Bold and Border first column
              $("row:first c", sheet).attr("s", "27");
              //* Border all column except first column
              $("row:not(:first) c", sheet).attr("s", "25");
            },
            exportOptions: {
              columns: ":visible",
            },
          },
        ],
      },
    ],
  };

  new $.fn.dataTable.Buttons(_tableReport, button);
  _tableReport.buttons().container().appendTo($("#dt-button"));

  if (checkAll.length == 0) {
    $(".btn_print_qrcode").hide();
  }
});

/**
 * Event Click Button Reset Report
 */
$(".btn_reset_form").on("click", function (evt) {
  const target = $(evt.target);
  const pageInner = target.closest(".page-inner");
  const card = target.closest(".card");
  const cardTableReport = pageInner.find(".card-table-report");
  const floatRight = cardTableReport.find(".float-right");
  const form = card.find("form");
  const field = form.find("input, select");

  for (let i = 0; i < field.length; i++) {
    if (field[i].name !== "") {
      //TODO: Collect Field yang readonly atau disabled
      if (field[i].readOnly || field[i].disabled)
        fieldReadOnly.push(field[i].name);
    }
  }

  //* Reset form parameter
  clearForm(evt);

  //* Set clear to true
  clear = true;

  //* Hide Toolbar Button
  floatRight.addClass("d-none");

  //TODO: Loading and processing
  pageInner.length &&
    (pageInner.addClass("is-loading"),
    reloadTable(),
    setTimeout(function () {
      pageInner.removeClass("is-loading");
    }, 500));

  //* Hide Table Report
  cardTableReport.removeClass("d-block");
});

/**
 * Process login
 */
$(".btn_login").click(function () {
  let _this = $(this);
  let oriElement = _this.html();

  const form = $(this).closest("form");

  let url = CURRENT_URL + "/login";

  $.ajax({
    url: url,
    type: "POST",
    data: form.serialize(),
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(this).prop("disabled", true);
      $(_this)
        .html(
          '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
        )
        .prop("disabled", true);
    },
    complete: function () {
      $(this).removeAttr("disabled");
      $(_this).html(oriElement).prop("disabled", false);
    },
    success: function (result) {
      if (result[0].success) {
        Toast.fire({
          type: "success",
          title: result[0].message,
        });

        window.open(ADMIN_URL, "_self");
      } else if (result[0].error) {
        errorForm(form, result);
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

/**
 * Enter key press button login form
 */
$(".login-form input").keypress(function (evt) {
  let key = evt.which;

  if (key == 13) $(".btn_login").click();
});

/**
 * Anchor change password on the navbar admin
 */
$(".change-password").click(function (evt) {
  ID = $(this).attr("id");
  openModalForm();
});

/**
 * Save modal password
 */
$(".save_form_pass").click(function (evt) {
  const parent = $(evt.target).closest(".modal");
  const form = parent.find("form");

  let _this = $(this);
  let oriElement = _this.html();

  let url = ADMIN_URL + "auth/changePassword";

  let formData = new FormData(form[0]);

  if (typeof ID !== "undefined" && ID !== "") formData.append("id", ID);

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".close").prop("disabled", true);
      loadingForm(form.prop("id"), "facebook");
      $(_this)
        .html(
          '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>'
        )
        .prop("disabled", true);
    },
    complete: function () {
      $(".close").prop("disabled", false);
      hideLoadingForm(form.prop("id"));
      $(_this).html(oriElement).prop("disabled", false);
    },
    success: function (result) {
      if (result[0].success) {
        Toast.fire({
          type: "success",
          title: result[0].message,
        });

        clearForm(evt);

        $(".modal_form").modal("hide");
      } else if (result[0].error) {
        let fields = result[0].message;

        $.each(fields, function (idx, elem) {
          if (elem !== "") {
            form
              .find('input:password[name="' + idx + '"]')
              .closest(".form-group")
              .addClass("has-error");

            form.find("small[id=error_" + idx + "]").html(elem);
          } else {
            form
              .find('input:password[name="' + idx + '"]')
              .closest(".form-group")
              .removeClass("has-error");

            form.find("small[id=error_" + idx + "]").html("");
          }
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

/**
 * Process for active non-active field in the form using checkbox class active
 */
$("input.active:checkbox").change(function (evt) {
  const parent = $(this).closest("form");
  const field = parent.find("input, textarea, select, button");
  let className;

  //TODO: Remove value duplicate array
  fieldReadOnly = [...new Set(fieldReadOnly)];

  if ($(this).is(":checked")) {
    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        className = field[i].className.split(/\s+/);

        // field is not readonly by default
        if (
          !fieldReadOnly.includes(field[i].name) &&
          typeof $(field[i]).attr("edit-readonly") === "undefined"
        ) {
          parent
            .find(
              "input:text[name=" +
                field[i].name +
                "], textarea[name=" +
                field[i].name +
                "], input:password[name=" +
                field[i].name +
                "]"
            )
            .not(".line")
            .removeAttr("readonly");
        }

        if (
          !className.includes("active") &&
          !fieldReadOnly.includes(field[i].name) &&
          typeof $(field[i]).attr("edit-disabled") === "undefined"
        ) {
          parent
            .find(
              "input:checkbox[name=" +
                field[i].name +
                "], input:radio[name=" +
                field[i].name +
                "], select[name=" +
                field[i].name +
                "], button[name=" +
                field[i].name +
                "]"
            )
            .not(".line")
            .removeAttr("disabled");

          if (field[i].type === "file") {
            parent
              .find("input[name=" + field[i].name + "]")
              .removeAttr("disabled");
            parent
              .find("button.close-img")
              .removeAttr("disabled")
              .css("display", "block");
          }
        }

        if (
          parent.find("textarea.summernote[name=" + field[i].name + "]").length
        )
          parent.find("[name =" + field[i].name + "]").summernote("enable");
      }
    }
  } else {
    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        className = field[i].className.split(/\s+/);

        // set field is readonly by default
        if (
          (field[i].readOnly || field[i].disabled) &&
          field[i].type !== "radio"
        )
          fieldReadOnly.push(field[i].name);

        // field is not readonly by default
        if (!fieldReadOnly.includes(field[i].name)) {
          parent
            .find(
              "input:text[name=" +
                field[i].name +
                "], textarea[name=" +
                field[i].name +
                "], input:password[name=" +
                field[i].name +
                "]"
            )
            .not(".line")
            .prop("readonly", true);
        }

        if (
          !className.includes("active") &&
          !fieldReadOnly.includes(field[i].name)
        ) {
          parent
            .find(
              "input:checkbox[name=" +
                field[i].name +
                "], input:radio[name=" +
                field[i].name +
                "], select[name=" +
                field[i].name +
                "], button[name=" +
                field[i].name +
                "]"
            )
            .not(".line")
            .prop("disabled", true);

          if (field[i].type === "file") {
            parent
              .find("input[name=" + field[i].name + "]")
              .prop("disabled", true);
            parent
              .find("button.close-img")
              .prop("disabled", true)
              .css("display", "none");
          }
        }

        if (
          parent.find("textarea.summernote[name=" + field[i].name + "]").length
        )
          parent.find("[name =" + field[i].name + "]").summernote("disable");
      }
    }
  }
});

/**
 * Button close image
 */
$(".close-img").click(function (evt) {
  const parent = $(evt.currentTarget).closest("div");
  const formGroup = parent.closest(".form-group");
  const formUpload = formGroup.find(".form-upload");
  const form = $(evt.currentTarget).closest("form");
  const field = form.find("input");

  let className = parent.find("label").prop("className");

  // set condition when add to clear all
  if (className.includes("form-result")) {
    formUpload.find("label").css("display", "block");
    parent.find("label").css("display", "none");
    formUpload.find("input:file").val("");
    parent.find(".img-result").attr("src", "");

    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        if (field[i].type === "file") {
          form.find("input[name=" + field[i].name + "]").removeAttr("disabled");
          parent
            .find("button.close-img")
            .removeAttr("disabled")
            .css("display", "block");
        }
      }
    }
  }
});

/**
 * Function to search exist value data
 * @param {*} value to search exist value
 * @param {*} arr array data
 * @returns
 */
function arrContains(value, arr) {
  var result = null;

  for (let i = 0; i < arr.length; i++) {
    var fieldName = arr[i];
    if (fieldName.toString().toLowerCase() === value.toString().toLowerCase()) {
      result = fieldName;
      break;
    }
  }

  return result;
}

/**
 * Function to show Error Validation on the field
 * @param {*} parent selector html
 * @param {*} data from database
 */
function errorForm(parent, data) {
  const errorInput = parent.find("input, select, textarea").not(".line");
  const errorText = parent.find("small");

  let arrInput = [];
  let arrText = [];

  for (let i = 0; i < errorText.length; i++) {
    if (errorText[i].id !== "") arrText.push(errorText[i].id);
  }

  for (let k = 0; k < errorInput.length; k++) {
    arrInput.push(errorInput[k].name);
  }

  for (let j = 0; j < data.length; j++) {
    let error = data[j].error;
    let field = data[j].field;
    let labelMsg = data[j].label;

    let textName = arrContains(error, arrText);
    let inputName = arrContains(field, arrInput);

    if (labelMsg !== "" && j > 0) {
      if (error !== "error_table") {
        parent.find("small[id=" + textName + "]").html(labelMsg);

        if (!parent.find("div.form-group").prop("classList").contains("row"))
          parent
            .find(
              "input:text[name=" +
                inputName +
                "], select[name=" +
                inputName +
                "], textarea[name=" +
                inputName +
                "], input:password[name=" +
                inputName +
                "]"
            )
            .not(".line")
            .closest(".form-group")
            .addClass("has-error");
        else
          parent
            .find(
              "input:text[name=" +
                inputName +
                "], select[name=" +
                inputName +
                "], textarea[name=" +
                inputName +
                "], input:password[name=" +
                inputName +
                "]"
            )
            .not(".line")
            .closest("div")
            .addClass("has-error");
      }

      // Check datatable line for get validation
      if (_tableLine.context.length) {
        // Error validation for datatable line
        if (field === "line")
          Toast.fire({
            type: "error",
            title: labelMsg,
          });

        const tdInput = _tableLine.rows().nodes().to$().find("input, select");

        let arrValue = [];

        $.each(tdInput, function (i) {
          let value = this.value;
          let name = $(this).attr("name");
          let className = $(this)[0].className.split(/\s+/);

          let index = $(this).closest("tr")[0]._DT_RowIndex;

          if ($(this).attr("required")) {
            let row = Number(index + 1);

            // Error validation for every line
            if (
              typeof error !== "undefined" &&
              error === "error_table" &&
              labelMsg !== ""
            ) {
              if (name === field && (value === "" || value == 0)) {
                $(this).closest(".form-group").addClass("has-error");
                Toast.fire({
                  type: "error",
                  title: `#${row} : ${labelMsg}`,
                });
              } else if (name === field && value !== "") {
                arrValue.push(value);

                let duplicateValue = findArrDuplicate(arrValue);
                let existsValue = labelMsg.split("|")[0].trim();

                // Duplicate value every line
                if (
                  duplicateValue.length > 0 &&
                  duplicateValue.includes(value) &&
                  className.includes("unique")
                ) {
                  $(this).closest(".form-group").addClass("has-error");
                  Toast.fire({
                    type: "error",
                    title: labelMsg,
                  });
                } else if (existsValue === value) {
                  // Value already exists from database
                  labelMsg = labelMsg.split("|")[1].trim();

                  $(this).closest(".form-group").addClass("has-error");
                  Toast.fire({
                    type: "error",
                    title: labelMsg,
                  });
                } else {
                  $(this).closest(".form-group").removeClass("has-error");
                }
              } else if (!className.includes("unique") && value !== "") {
                $(this).closest(".form-group").removeClass("has-error");
              }
            } else if (
              typeof error !== "undefined" &&
              error !== "error_table"
            ) {
              $(this).closest(".form-group").removeClass("has-error");
            }
          }
        });
      }
    } else {
      parent.find("small[id=" + textName + "]:not(.line)").html("");

      if (!parent.find("div.form-group").prop("classList").contains("row"))
        parent
          .find(
            "input:text[name=" +
              inputName +
              "]:not(.line), select[name=" +
              inputName +
              "], textarea[name=" +
              inputName +
              "], input:password[name=" +
              inputName +
              "]"
          )
          .not(".line")
          .closest(".form-group")
          .removeClass("has-error");
      else
        parent
          .find(
            "input:text[name=" +
              inputName +
              "]:not(.line), select[name=" +
              inputName +
              "], textarea[name=" +
              inputName +
              "], input:password[name=" +
              inputName +
              "]"
          )
          .not(".line")
          .closest("div")
          .removeClass("has-error");
    }
  }
}

/**
 * Function to Remove has-error and text-danger
 * @param {*} evt selector html
 */
function clearErrorForm(form) {
  const field = form.find("input, textarea, select");
  const errorText = form.find("small");

  //* Remove class has-error
  for (let i = 0; i < field.length; i++) {
    if (field[i].name !== "") {
      if (!form.find("div.form-group").prop("classList").contains("row"))
        form
          .find(
            "input[name=" +
              field[i].name +
              "], textarea[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "]"
          )
          .closest(".form-group")
          .removeClass("has-error");
      else
        form
          .find(
            "input[name=" +
              field[i].name +
              "], textarea[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "]"
          )
          .closest("div")
          .removeClass("has-error");
    }
  }

  //* Remove text error element small
  for (let l = 0; l < errorText.length; l++) {
    if (errorText[l].id !== "")
      form.find("small[id=" + errorText[l].id + "]").html("");
  }
}

function findArrDuplicate(array) {
  return array.filter(function (item, pos, self) {
    return self.indexOf(item) != pos;
  });
}

/**
 * Function to clear value and attribute on the field
 * @param {*} evt selector html
 */
function clearForm(evt) {
  const target = $(evt.target);
  const parent = target.closest(".row");
  const cardForm = parent.find(".card-form");
  const navTab = parent.find("ul.nav-tabs");
  let form = cardForm.length ? cardForm.find("form") : parent.find("form");

  if (navTab.length) {
    const cardTab = parent.find("div.card-tab");
    const tabPaneAll = cardTab.find(".tab-pane");
    const tabPane = cardTab.find(".tab-pane.active");
    const tableTab = form.find("table.tb_displaytab");

    form = tabPane.find("form");

    if (setSave === null) {
      form = tabPaneAll.find("form");

      $.each(form, function () {
        this.reset();
      });
    }
  }

  //TODO: Clear field data on the form
  form[0].reset();

  const field = form.find("input, textarea, select, button");
  const errorText = form.find("small");

  let defaultLogic = [];

  //TODO: Remove value duplicate array
  fieldReadOnly = [...new Set(fieldReadOnly)];

  //TODO: Clear data, remove attribute readonly, disabled and remove class invalid on the field
  for (let i = 0; i < field.length; i++) {
    if (field[i].name !== "") {
      if (fieldReadOnly.length == 0) {
        form
          .find(
            "input[name=" +
              field[i].name +
              "], textarea[name=" +
              field[i].name +
              "]"
          )
          .removeAttr("readonly")
          .closest(".form-group")
          .removeClass("has-error");
      } else if (fieldReadOnly.length > 0) {
        //? field is not readonly by default
        if (!fieldReadOnly.includes(field[i].name)) {
          form
            .find(
              "input[name=" +
                field[i].name +
                "], textarea[name=" +
                field[i].name +
                "]"
            )
            .removeAttr("readonly")
            .closest(".form-group")
            .removeClass("has-error");
        } else {
          form
            .find(
              "input[name=" +
                field[i].name +
                "], textarea[name=" +
                field[i].name +
                "]"
            )
            .closest(".form-group")
            .removeClass("has-error");
        }
      }

      if (
        !fieldReadOnly.includes(field[i].name) &&
        field[i].type === "checkbox"
      )
        form
          .find("input:checkbox[name=" + field[i].name + "]")
          .removeAttr("disabled");

      //TODO: Remove value duplicate array
      fieldReadOnly = [...new Set(fieldReadOnly)];

      //TODO: Clear data dropdown if not selected from the beginning
      if (
        defaultLogic.length > 0 &&
        field[i].name === defaultLogic[0].field &&
        defaultLogic[0].condition
      ) {
        if (fieldReadOnly.length == 0) {
          form
            .find("select[name=" + field[i].name + "]")
            .val(defaultLogic[0].id)
            .change()
            .removeAttr("disabled")
            .closest(".form-group")
            .removeClass("has-error");
        } else if (fieldReadOnly.length > 0) {
          // field is not readonly by default
          if (!fieldReadOnly.includes(field[i].name)) {
            form
              .find("select[name=" + field[i].name + "]")
              .val(defaultLogic[0].id)
              .change()
              .removeAttr("disabled")
              .closest(".form-group")
              .removeClass("has-error");
          } else {
            form
              .find("select[name=" + field[i].name + "]")
              .val(defaultLogic[0].id)
              .change()
              .closest(".form-group")
              .removeClass("has-error");
          }
        }
      } else {
        if (fieldReadOnly.length == 0) {
          form
            .find("select[name=" + field[i].name + "]")
            .val(null)
            .change()
            .removeAttr("disabled")
            .closest(".form-group")
            .removeClass("has-error");
        } else if (fieldReadOnly.length > 0) {
          //? field is not readonly by default
          if (!fieldReadOnly.includes(field[i].name)) {
            form
              .find("select[name=" + field[i].name + "]")
              .val(null)
              .change()
              .removeAttr("disabled")
              .closest(".form-group")
              .removeClass("has-error");
          } else {
            if (form.find("select[name=" + field[i].name + "]").length) {
              let value = form.find("select[name=" + field[i].name + "]").val();
              if (value === "")
                form
                  .find("select[name=" + field[i].name + "]")
                  .val(null)
                  .change()
                  .closest(".form-group")
                  .removeClass("has-error");
            }
            if (form.find(".datepicker[name=" + field[i].name + "]").length) {
              let value = form
                .find(".datepicker[name=" + field[i].name + "]")
                .val();

              if (value === "")
                form
                  .find(".datepicker[name=" + field[i].name + "]")
                  .data("DateTimePicker")
                  .clear();
            }
          }
        }
      }

      //? Type input file
      if (field[i].type == "file") $(".close-img").click();

      //? Textarea class summernote
      if (form.find("textarea.summernote[name=" + field[i].name + "]").length) {
        $("[name =" + field[i].name + "]").summernote("reset");
        $("[name =" + field[i].name + "]").summernote("destroy");
      }

      //? Exist table display line
      if (_tableLine.context.length) {
        _tableLine.clear().draw();

        const btnAction = _tableLine.rows().to$().find("button");

        //TODO: Show Button Add Row or Create Line
        $(".add_row, .create_line").css("display", "block");

        //TODO: Show Button Action
        btnAction.css("display", "block");
      }

      //? Check and Remove attribute disabled from field
      if ($(field[i]).attr("edit-disabled"))
        form
          .find(
            "input:checkbox[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "], input:radio[name=" +
              field[i].name +
              "]"
          )
          .removeAttr("disabled");

      //? Check and Remove attribute disabled from field
      if ($(field[i]).attr("edit-readonly"))
        form
          .find("input:text[name=" + field[i].name + "]")
          .removeAttr("readonly");

      form
        .find(
          "input:radio[name=" +
            field[i].name +
            "], button[name=" +
            field[i].name +
            "]"
        )
        .removeAttr("disabled");
    }
  }

  //TODO: clear text error element small
  for (let l = 0; l < errorText.length; l++) {
    if (errorText[l].id !== "")
      form.find("small[id=" + errorText[l].id + "]").html("");
  }

  if (form.find(".datepicker-start").length)
    form.find(".datepicker-start").data("DateTimePicker").clear();

  if (form.find(".datetimepicker-start").length)
    form.find(".datetimepicker-start").data("DateTimePicker").clear();

  if (form.find(".datepicker-end").length)
    form.find(".datepicker-end").data("DateTimePicker").clear();

  if (form.find(".datetimepicker-end").length)
    form.find(".datetimepicker-end").data("DateTimePicker").clear();

  if (form.find(".datetimepicker").length)
    form.find(".datetimepicker").data("DateTimePicker").clear();

  if (form.find(".datepick-start").length)
    form.find(".datepick-start").data("DateTimePicker").clear();

  if (form.find(".datepick-end").length)
    form.find(".datepick-end").data("DateTimePicker").clear();

  //TODO: Set to empty array option
  option = [];
  arrMultiSelect = [];
}

/**
 * Function to set field condition readonly true/false
 * @param {*} parent selector html
 * @param {*} value based on passing data (true/false)
 */
function readonly(parent, value) {
  const field = parent.find("input, textarea, select, button");

  //TODO: Remove value duplicate array
  fieldReadOnly = [...new Set(fieldReadOnly)];

  for (let i = 0; i < field.length; i++) {
    if (field[i].name !== "") {
      let className = field[i].className.split(/\s+/);

      // field is not readonly by default
      if (!fieldReadOnly.includes(field[i].name))
        parent
          .find(
            "input:text[name=" +
              field[i].name +
              "], textarea[name=" +
              field[i].name +
              "], input:password[name=" +
              field[i].name +
              "]"
          )
          .not(".line")
          .prop("readonly", value);

      if (
        field[i].type !== "text" &&
        !className.includes("active") &&
        !fieldReadOnly.includes(field[i].name)
      ) {
        parent
          .find(
            "input:checkbox[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "], input:radio[name=" +
              field[i].name +
              "], button[name=" +
              field[i].name +
              "]"
          )
          .not(".line")
          .prop("disabled", value);
      }

      if (field[i].type === "file") {
        parent
          .find("input[name=" + field[i].name + "]")
          .not(".line")
          .prop("disabled", value);
      }

      if (
        parent.find("textarea.summernote[name=" + field[i].name + "]").length >
          0 ||
        parent.find("textarea.summernote-product[name=" + field[i].name + "]")
          .length > 0
      ) {
        if (value) {
          $("[name =" + field[i].name + "]")
            .not(".line")
            .summernote("disable");
        } else {
          $("[name =" + field[i].name + "]")
            .not(".line")
            .summernote("enable");
        }
      }
    }
  }

  // check button close image based on value
  if (parent.find("button.close-img").length > 0) {
    parent.find("button.close-img").not(".line").prop("disabled", value);

    if (value) {
      parent.find("button.close-img").not(".line").css("display", "none");
    } else {
      parent.find("button.close-img").not(".line").css("display", "block");
    }
  }
}

/**
 *
 * @param {*} input selector element html
 * @param {*} id
 * @param {*} src source image
 */
function previewImage(input, id, src) {
  let labelUpload = input.closest("label");
  id = id || ".img-result";

  src = src == null ? "" : src;

  if (input.files && input.files[0]) {
    let reader = new FileReader();

    reader.onload = function (e) {
      loadingForm(labelUpload.id, "pulse");
      $(".save_form").attr("disabled", true);
      $(".x_form").attr("disabled", true);
      $(".close_form").attr("disabled", true);

      setTimeout(function () {
        $(id).attr("src", e.target.result).width("auto").height(150);

        $(".form-upload-foto").css("display", "none");
        $(".form-result").css("display", "block");

        hideLoadingForm(labelUpload.id);

        $(".save_form").removeAttr("disabled");
        $(".x_form").removeAttr("disabled");
        $(".close_form").removeAttr("disabled");
      }, 2500);
    };

    reader.readAsDataURL(input.files[0]);
  } else if (src !== "") {
    src = ORI_URL + "/" + src;

    $.ajax({
      url: src,
      type: "HEAD",
      error: function () {
        $(id).attr("src", "").width("auto").height(150);
        $(".form-upload-foto").css("display", "block");
        $(".form-result").css("display", "none");
      },
      success: function () {
        loadingForm(labelUpload.id, "pulse");
        $(".save_form").attr("disabled", true);
        $(".x_form").attr("disabled", true);
        $(".close_form").attr("disabled", true);

        setTimeout(function () {
          $(id).attr("src", src).width("auto").height(150);
          $(".form-upload-foto").css("display", "none");
          $(".form-result").css("display", "block");

          hideLoadingForm(labelUpload.id);

          $(".save_form").removeAttr("disabled");
          $(".x_form").removeAttr("disabled");
          $(".close_form").removeAttr("disabled");
        }, 500);
      },
    });
  } else {
    $(id).attr("src", "").width("auto").height(150);
    $(".form-upload-foto").css("display", "block");
    $(".form-result").css("display", "none");
  }
}

/**
 *
 * @param {*} input action "create, update, delete"
 * @param {*} last_url get the last url
 * @returns
 */
function isAccess(input, last_url) {
  let url = CURRENT_URL + "/accessmenu/getAccess";
  let value;

  $.ajax({
    url: url,
    type: "POST",
    data: {
      last_url: last_url,
      action: input,
    },
    async: false,
    dataType: "JSON",
    success: function (result) {
      value = result;
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });

  return value;
}

/**
 * Function for show code numbering based on class code
 * @param {*} form
 */
function setSeqCode(form) {
  let url = CURRENT_URL + "/getSeqCode";

  $.getJSON(url, function (result) {
    form.find("input.code").val(result[0].message);
  }).fail(function (jqXHR, exception) {
    showError(jqXHR, exception);
  });
}

/**
 * Function to show error logic when process ajax
 * @param {*} xhr
 * @param {*} exception
 */
function showError(xhr, exception) {
  let msg = "";

  if (xhr.status === 0) msg = "Not connect.\n Verify Network.";
  else if (xhr.status == 404) msg = "Requested page not found. [404]";
  else if (xhr.status == 500) msg = "Internal Server Error [500].";
  else if (exception === "parsererror") msg = "Requested JSON parse failed.";
  else if (exception === "timeout") msg = "Time out error.";
  else if (exception === "abort") msg = "Ajax request aborted.";
  else msg = "Uncaught Error.\n" + xhr.responseText;

  Toast.fire({
    type: "error",
    title: msg,
  });
}

/**
 * Function to show wait Loading
 * @param {*} selectorID form html
 * @param {*} effect
 */
function loadingForm(selectorID, effect) {
  $("#" + selectorID + "").waitMe({
    effect: effect,
    text: "Harap tunggu...",
    bg: "rgba(255,255,255,0.7)",
    color: "#000",
    maxSize: "",
    waitTime: -1,
    textPos: "vertical",
    fontSize: "100%",
    source: "",
    onClose: function () {},
  });
}

/**
 * Function to hide wait Loading
 * @param {*} selectorID form html
 */
function hideLoadingForm(selectorID) {
  $("#" + selectorID + "").waitMe("hide");
}

/**
 * Function to set text to Capitalize
 * @param {*} s string value
 * @returns
 */
const capitalize = (s) => {
  if (typeof s !== "string") return "";
  return s.charAt(0).toUpperCase() + s.slice(1);
};

/**
 * Funtion to show modal form
 */
function openModalForm() {
  return $(".modal_form").modal({
    backdrop: "static",
    keyboard: false,
  });
}

/**
 * Return call class scrollable in modal
 */
function Scrollmodal() {
  return modalDialog.addClass("modal-dialog-scrollable");
}

/**
 * Function for convert numeric to rupiah format
 * @param {*} numeric
 * @returns
 */
function formatRupiah(numeric) {
  let number_string = numeric.toString(),
    split = number_string.split(","),
    sisa = split[0].length % 3,
    rupiah = split[0].substr(0, sisa),
    ribuan = split[0].substr(sisa).match(/\d{3}/gi);

  // tambahkan titik jika yang di input sudah menjadi angka ribuan
  if (ribuan) {
    separator = sisa ? "." : "";
    rupiah += separator + ribuan.join("."); //penambahan separator titik setelah bilangan satuan
  }

  return rupiah ? rupiah : "";
}

/**
 * Function for convert rupiah to numeric
 * @param {*} numeric
 * @returns
 */
function replaceRupiah(numeric) {
  return numeric.replace(/\./g, "");
}

/**
 * Function initialize select2 dropdown based on url on the element html
 * @param {*} select
 */
function initSelectData(select, field = null, id = null) {
  $.each(select, function (i, item) {
    let url = $(item).attr("data-url");
    let defaultID = $(item).attr("default-id");
    let defaultText = $(item).attr("default-text");

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
        allowClear: true,
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
              name: lastParam,
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

      if (
        typeof defaultID !== "undefined" &&
        defaultID !== "" &&
        typeof defaultText !== "undefined" &&
        defaultText !== ""
      ) {
        let optionSelected = $("<option selected='selected'></option>")
          .val(defaultID)
          .text(defaultText);
        $(this).append(optionSelected).change();
      }
    }
  });
}

/**
 * Function for get data from database
 * @param {*} url
 * @param {*} field
 * @param {*} reference
 * @returns
 */
function getList(url, field, reference) {
  let value;

  $.ajax({
    url: ADMIN_URL + url,
    type: "POST",
    data: {
      field: field,
      reference: reference,
    },
    async: false,
    dataType: "JSON",
    success: function (response) {
      value = response;
    },
  });

  return value;
}

/**
 * Remove element from array
 * @param {*} array
 * @param {*} itemsToRemove
 * @returns
 */
function removeItems(array, itemsToRemove) {
  const index = array.indexOf(itemsToRemove);

  if (index > -1) return array.splice(index, 1);
}

/**
 * Function for get logic from controller
 * @param {*} url
 * @returns
 */
function getLogic(url) {
  let value = [];

  $.ajax({
    url: ADMIN_URL + url,
    type: "POST",
    async: false,
    dataType: "JSON",
    success: function (response) {
      value.push(response);
    },
  });

  return value;
}

/**
 * Function to initialize summernote
 * @param {*} selector
 */
function initSummerNote(selector) {
  $.each(selector, function () {
    $(this).summernote({
      fontNames: [
        "Arial",
        "Arial Black",
        "Comic Sans MS",
        "Courier New",
        "Times New Roman",
      ],
      tabsize: 2,
      height: 200,
      toolbar: [
        ["style", ["style", "bold", "italic", "underline", "clear"]],
        ["fontname", ["fontname"]],
        ["fontsize", ["fontsize"]],
        ["color", ["color"]],
        ["para", ["ul", "ol", "paragraph"]],
        ["table", ["table"]],
        ["view", ["fullscreen", "codeview", "help"]],
        ["height", ["height"]],
      ],
      placeholder: "write here...",
    });
  });
}

function showNotification() {
  let url = ADMIN_URL + "wactivity/showNotif";

  $.ajax({
    url: url,
    type: "GET",
    dataType: "JSON",
    success: function (response) {
      if (response > 0)
        $(".notif-workflow").addClass("notification").text(response);
      else $(".notif-workflow").removeClass("notification").text("");
    },
  });
}

/**
 * Function to merge Array Object
 * @param {*} arr1
 * @param {*} arr2
 * @param {*} arr3
 * @param {*} arr4
 * @param {*} arrID Access ID to retrieve edit data
 * @returns
 */
function mergeArrayObjects(arr1, arr2, arr3, arr4, arrID) {
  return arr1.map((item, i) => {
    if (
      item.row === arr2[i].row ||
      item.row === arr3[i].row ||
      item.row === arr4[i].row ||
      item.row === arrID[i].row
    )
      return Object.assign({}, item, arr2[i], arr3[i], arr4[i], arrID[i]);
  });
}

/**
 * Remove duplicate array object
 * @param {*} arr
 * @param {*} key object key to define when call function
 * @returns
 */
function removeDuplicates(arr, key) {
  return [...new Map(arr.map((item) => [key(item), item])).values()];
}

/**
 * Event checked checkbox table role
 */
$(document).on("click", "input:checkbox", function () {
  const table = $(this).closest("table");
  const tr = $(this).closest("tr");
  let th = $(this).closest("th").index();
  let cell = $(this).parent().parent().parent().index();

  // Row start from 0
  let index = cell + 1;

  let dataNode;

  if ($(this).is(":checked")) {
    // Checked all checkbox based on index header
    if (th > 0)
      table
        .find("td:nth-child(" + index + ") input:checkbox")
        .prop("checked", true);

    // Checked checkbox based on parent
    if (
      tr.hasClass("treetable-expanded") ||
      tr.hasClass("treetable-collapsed")
    ) {
      // Substring attribute data-node
      dataNode = tr.attr("data-node").substring(10);

      table
        .find(
          "tr[data-pnode=treetable-parent-" +
            dataNode +
            "] td:nth-child(" +
            index +
            ") input:checkbox"
        )
        .prop("checked", true);
    }
  } else {
    // Unchecked all checkbox based on index header
    if (th > 0)
      table
        .find("td:nth-child(" + index + ") input:checkbox")
        .prop("checked", false);

    // Unchecked checkbox based on parent
    if (
      tr.hasClass("treetable-expanded") ||
      tr.hasClass("treetable-collapsed")
    ) {
      // Substring attribute data-node
      dataNode = tr.attr("data-node").substring(10);

      table
        .find(
          "tr[data-pnode=treetable-parent-" +
            dataNode +
            "] td:nth-child(" +
            index +
            ") input:checkbox"
        )
        .prop("checked", false);
    }
  }
});

/**
 * Function check exist role on the user based on session user
 *
 * @param {*} role name
 * @returns
 */
function checkExistUserRole(role) {
  let url = ADMIN_URL + "role/getUserRoleName";
  let value;

  $.ajax({
    url: url,
    type: "POST",
    data: {
      role_name: role,
    },
    async: false,
    cache: false,
    dataType: "JSON",
    success: function (result) {
      value = result;
    },
    error: function (jqXHR, exception) {
      showError(jqXHR, exception);
    },
  });

  return value;
}

/**
 * Event change input class active type checkbox on the _tableLine
 */
_tableLine.on("change", "input.active:checkbox", function (evt) {
  const tr = $(this).closest("tr");
  const field = tr.find("input, select");
  let className;

  if ($(this).is(":checked")) {
    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        className = field[i].className.split(/\s+/);

        tr.find("input:text[name=" + field[i].name + "]").removeAttr(
          "readonly"
        );

        if (field[i].type !== "text" && !className.includes("active")) {
          tr.find(
            "input[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "]"
          ).removeAttr("disabled");
        }
      }
    }
  } else {
    for (let i = 0; i < field.length; i++) {
      if (field[i].name !== "") {
        className = field[i].className.split(/\s+/);

        tr.find("input:text[name=" + field[i].name + "]").prop(
          "readonly",
          true
        );

        if (field[i].type !== "text" && !className.includes("active")) {
          tr.find(
            "input[name=" +
              field[i].name +
              "], select[name=" +
              field[i].name +
              "]"
          ).prop("disabled", true);
        }
      }
    }
  }
});

/**
 * Event Toogle Sidebar to Resize DataTable
 */
$(".toggle-sidebar").click(function (evt) {
  $(".dataTables_scrollHeadInner").addClass("stretch");
  $(".tb_display, .table_report").css("width", "100%");
});

/**
 * Function show data form
 * @param {*} form
 */
function showFormData(form) {
  const cardMain = form.closest(".card-main");
  const div = cardMain.find("div");

  let url = CURRENT_URL + SHOWALL;

  $.ajax({
    url: url,
    type: "GET",
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(".reset_form").prop("disabled", true);
      $(".save_form").prop("disabled", true);
    },
    complete: function () {
      $(".reset_form").removeAttr("disabled");
      $(".save_form").removeAttr("disabled");
      $(".main-panel").removeClass("is-loading");
    },
    success: function (result) {
      if (result[0].success) {
        let arrMsg = result[0].message;

        if (arrMsg.header) {
          let data = arrMsg.header;
          let length = data.length;

          if (length > 1) {
            putFieldData(form, data);

            for (let i = 0; i < data.length; i++) {
              let label = data[i].label;
              let primarykey = data[i].primarykey;

              if (primarykey) {
                setSave = "update";
                ID = label;
              }
            }

            $.each(div, function () {
              if ($(this).attr("show-after-save")) {
                $(this).removeClass("d-none");
              }
            });
          } else {
            const field = form.find("input, textarea, select");

            for (let i = 0; i < field.length; i++) {
              let fields = [];

              if (field[i].name !== "") {
                //? Condition field and contain attribute hide-field
                if ($(field[i]).attr("hide-field")) {
                  fields = $(field[i])
                    .attr("hide-field")
                    .split(",")
                    .map((element) => element.trim());

                  if (field[i].type === "checkbox") {
                    if (field[i].checked) {
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

                //? Condition field and contain attribute show-field
                if ($(field[i]).attr("show-field")) {
                  fields = $(field[i])
                    .attr("show-field")
                    .split(",")
                    .map((element) => element.trim());

                  if (field[i].type === "checkbox") {
                    if (field[i].checked) {
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
                    } else if (field[i].type === "checkbox") {
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
              }
            }
          }
        }

        if (arrMsg.barcode) {
          let data = arrMsg.barcode;

          $.each(div, function () {
            let className = this.className.split(/\s+/);

            if (className.includes("card-barcode")) {
              const barcode = $(this).find(".barcode");

              var html = "";

              if (data.barcodetype === "JPG" || data.barcodetype === "PNG") {
                html +=
                  '<img src="data:image/png;base64,' + data.barcode + '"/>';
              } else {
                html += data.barcode;
              }

              if (data.position !== "" && data.size != 0)
                html +=
                  '<p class="' +
                  data.position +
                  '" style="font-size: ' +
                  data.size +
                  'px">' +
                  data.text +
                  "</p>";

              barcode.html(html);
            }
          });
        }
      }
    },
  });
}

/**
 * Function to put data on the field from database
 * @param {*} form
 * @param {*} data
 */
function putFieldData(form, data) {
  const modalTab = form.closest(".modal-tab");

  if (data.length > 1) {
    const field = form.find("input, textarea, select").not(".line");

    if (form.find("select.select-data").length > 0) {
      let select = form.find("select.select-data");
      initSelectData(select, data[1].field, data[1].label);
    }

    if (modalTab.length) {
      const form = modalTab.find("form");

      $.each(form, function () {
        const field = $(this).find("input, textarea, select").not(".line");

        for (let i = 0; i < field.length; i++) {
          //? Retrieve field name default is readonly/disabled in the attribute field
          if (
            (field[i].readOnly || field[i].disabled) &&
            field[i].type !== "radio" &&
            !changeTab
          )
            fieldReadOnly.push(field[i].name);

          //? Retrieve field name default checked in the attribute field
          if (field[i].checked && !changeTab) fieldChecked.push(field[i].name);
        }
      });
    } else {
      for (let i = 0; i < field.length; i++) {
        //? Retrieve field name default is readonly/disabled in the attribute field
        if (
          (field[i].readOnly || field[i].disabled) &&
          field[i].type !== "radio"
        )
          fieldReadOnly.push(field[i].name);

        //? Retrieve field name default checked in the attribute field
        if (field[i].checked) {
          fieldChecked.push(field[i].name);
        }
      }
    }

    if (setSave === "detail") readonly(form, true);

    for (let i = 0; i < data.length; i++) {
      let fieldInput = data[i].field;
      let label = data[i].label;

      for (let i = 0; i < field.length; i++) {
        let fields = [];
        let fieldName = field[i].name;

        if (fieldName !== "" && fieldName === fieldInput) {
          let className = field[i].className.split(/\s+/);

          if (className.includes("datepicker")) {
            if (label !== null)
              form
                .find("input:text[name=" + fieldName + "]")
                .not(".line")
                .val(moment(label).format("DD-MMM-Y"));
          } else if (className.includes("rupiah")) {
            form
              .find("input:text[name=" + fieldName + "]")
              .not(".line")
              .val(formatRupiah(label));
          } else if (
            typeof $(field[i]).attr("set-id") === "undefined" &&
            field[i].type !== "file"
          ) {
            form
              .find(
                "input:text[name=" +
                  fieldName +
                  "], input:hidden[name=" +
                  fieldName +
                  "], textarea[name=" +
                  fieldName +
                  "], input:password[name=" +
                  fieldName +
                  "] "
              )
              .not(".line")
              .val(label);
          }

          if (
            form.find("textarea.summernote[name=" + fieldName + "]").length >
              0 ||
            form.find("textarea.summernote-product[name=" + fieldName + "]")
              .length > 0
          ) {
            $("[name =" + fieldName + "]")
              .not(".line")
              .summernote("code", label);
          }

          if (field[i].type === "select-one") {
            if (typeof label === "object" && label !== null) {
              let option_ID = label.id;
              let option_Txt = label.name;

              option.push({
                fieldName,
                option_ID,
                option_Txt,
              });

              let newOption = $("<option selected='selected'></option>")
                .val(option_ID)
                .text(option_Txt);
              form
                .find("select[name=" + fieldName + "]")
                .not(".line")
                .append(newOption)
                .change();
            } else if (
              typeof label === "string" &&
              (label !== null || label != 0)
            ) {
              option.push({
                fieldName,
                label,
              });

              form
                .find("select[name=" + fieldName + "]")
                .not(".line")
                .val(label)
                .change();
            }
          }

          if (field[i].type === "select-multiple" && label !== null) {
            // array label explode into array
            if (typeof label === "object") {
              label = label.id;

              arrMultiSelect.push(label);
              form
                .find("select[name=" + fieldName + "]")
                .not(".line")
                .val(arrMultiSelect)
                .change();
            }

            if (typeof label === "string") {
              let arrLabel = label.split(",");

              if (arrLabel.length > 1) {
                form
                  .find("select[name=" + fieldName + "]")
                  .not(".line")
                  .val(arrLabel)
                  .change();
              } else {
                arrMultiSelect.push(label);
                form
                  .find("select[name=" + fieldName + "]")
                  .not(".line")
                  .val(arrMultiSelect)
                  .change();
              }
            }
          }

          //? Check exist attribute edit-disabled
          if ($(field[i]).attr("edit-disabled")) {
            form
              .find(
                "input:checkbox[name=" +
                  fieldName +
                  "], select[name=" +
                  fieldName +
                  "], input:radio[name=" +
                  fieldName +
                  "]"
              )
              .not(".line")
              .prop("disabled", true);
          }

          //? Check exist attribute edit-readonly
          if ($(field[i]).attr("edit-readonly")) {
            form
              .find("input:text[name=" + fieldName + "]")
              .not(".line")
              .prop("readonly", true);
          }

          if (field[i].type === "checkbox") {
            if (label === "Y")
              form
                .find("input[name=" + fieldName + "]")
                .not(".line")
                .prop("checked", true);
            else if (label === "N")
              form
                .find("input[name=" + fieldName + "]")
                .not(".line")
                .removeAttr("checked");

            if (className.includes("active") && field[i].checked)
              readonly(form, false);
            else if (className.includes("active") && !field[i].checked)
              readonly(form, true);
          }

          // Set value checked for field type Radio Button
          if (field[i].type == "radio") {
            if (field[i].value == label) {
              field[i].checked = true;
            }
          }

          // Pass data form input file to function previewImage
          if (field[i].type === "file") {
            if (className.includes("control-upload-image")) {
              previewImage(
                form.find("input[name=" + fieldName + "]")[0],
                "",
                label
              );
            }
          }
        }

        //? Condition field and contain attribute hide-field
        if ($(field[i]).attr("hide-field") && fieldName !== "") {
          fields = $(field[i])
            .attr("hide-field")
            .split(",")
            .map((element) => element.trim());

          //TODO: Checkbox
          if (field[i].type === "checkbox") {
            if (field[i].checked) {
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
                  .not(".line")
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
                  .not(".line")
                  .closest(".form-group, .form-check");
                formGroup.show();
              }
            }
          }

          //TODO: Dropdown select
          if (field[i].type === "select-one") {
            for (let i = 0; i < fields.length; i++) {
              const select = form
                .find("select[name=" + fields[i] + "]")
                .not(".line");
              let formGroup = [];

              if ($(select).val() === null && $(select).val() === "") {
                formGroup = $(select).closest(".form-group");
                formGroup.hide();
              } else if ($(select).val() !== null && $(select).val() !== "") {
                formGroup = $(select).closest(".form-group");
                formGroup.show();
              } else if ($(select).val() === null) {
                formGroup = $(select).closest(".form-group");
                formGroup.hide();
              }
            }
          }

          //TODO: Radio Button
          if (field[i].type === "radio") {
            if (field[i].checked) {
              for (let i = 0; i < fields.length; i++) {
                const input = form
                  .find(
                    "input[name=" +
                      fields[i] +
                      "], textarea[name=" +
                      fields[i] +
                      "], select[name=" +
                      fields[i] +
                      "]"
                  )
                  .not(".line");

                //? Condition field is not null
                if (input.val() !== null) {
                  input.closest(".form-group").show();
                } else {
                  input.closest(".form-group").hide();
                }
              }
            }
          }
        }

        //? Condition field and contain attribute show-field
        if ($(field[i]).attr("show-field") && fieldName !== "") {
          fields = $(field[i])
            .attr("show-field")
            .split(",")
            .map((element) => element.trim());

          //TODO: Checkbox
          if (field[i].type === "checkbox") {
            if (field[i].checked) {
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
                  .not(".line")
                  .closest(".form-group, .form-check");
                formGroup.show();
              }
            } else if (field[i].type === "checkbox") {
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
                  .not(".line")
                  .closest(".form-group, .form-check");
                formGroup.hide();
              }
            }
          }
        }
      }
    }
  }
}

/**
 * Function to execute Test Email
 * @param {*} identifier
 */
function prosesTestEmail(identifier) {
  const form = $(identifier).closest("form");

  let url = CURRENT_URL + TEST_EMAIL;
  let formData = new FormData(form[0]);

  Swal.fire({
    title: "Test EMail",
    text: "Test EMail Connection on info defined.",
    type: "info",
    showCancelButton: true,
    cancelButtonColor: "#d33",
    confirmButtonText: "Ok",
    cancelButtonText: "Close",
    showLoaderOnConfirm: true,
    reverseButtons: true,
    onOpen: () => {},
    preConfirm: (generate) => {
      return new Promise(function (resolve) {
        $.ajax({
          type: "POST",
          url: url,
          data: formData,
          processData: false,
          contentType: false,
          cache: false,
          dataType: "JSON",
          success: function (result) {
            if (result[0].success) {
              Swal.fire({
                title: "Test EMail",
                text: result[0].message,
                type: "success",
                showConfirmButton: true,
              });

              clearErrorForm(form);
            } else if (result[0].error) {
              if (result.length > 1) {
                errorForm(form, result);
                resolve(true);
              } else {
                Swal.showValidationMessage(result[0].message);
                resolve(false);
              }
            } else {
              Swal.showValidationMessage(result[0].message);
              resolve(false);
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            Swal.showValidationMessage(errorThrown);
            resolve(false);
          },
        });
      });
    },
    allowOutsideClick: () => !Swal.isLoading(),
  });
}

$("#task_activity").click(function (e) {
  e.preventDefault();

  $("#modal_activity_info").modal({
    backdrop: "static",
    keyboard: false,
  });

  $("#modal_activity_info").on("shown.bs.modal", function (e) {
    const target = $(e.target);
    const form = target.find("form");

    let url = ADMIN_URL + "wactivity/showActivityInfo";

    form.find("input").prop("readonly", true);
    form.find('select[name="isanswer"]').hide();
    form.find("button").prop("disabled", true);

    // form[0].reset();

    // loadingForm('modal_activity_info', 'facebook');
    setTimeout(function () {
      // hideLoadingForm('modal_activity_info');

      //     if (form.find('select.select-data').length > 0) {
      //         let select = form.find('select.select-data');
      //         initSelectData(select);
      //     }

      //     if (form.find('input:hidden[name="isfree"]'))
      //         form.find('input:hidden[name="isfree"]').val(isFree);
      _tableApproval.ajax.url(url).load().columns.adjust();
    }, 50);
  });
});

$(".table_approval tbody").on("click", "tr", function () {
  const modalBody = $(this).closest(".modal-body");
  const form = modalBody.find("form");

  if ($(this).hasClass("selected")) {
    $(this).removeClass("selected");

    ID = 0;
    form.find("input").prop("readonly", true);
    form.find('select[name="isanswer"]').val("N").prop("disabled", true);
    form.find("button").prop("disabled", true);
  } else {
    _tableApproval.$("tr.selected").removeClass("selected");
    $(this).addClass("selected");

    let data = _tableApproval.row(this).data();

    if (typeof data !== "undefined") {
      ID = data[0];
      form.find("input").removeAttr("readonly");
      form
        .find('select[name="isanswer"]')
        .val("N")
        .removeAttr("disabled")
        .show();
      form.find("button").removeAttr("disabled");
    }
  }
});

$(".btn_ok_answer").click(function (evt) {
  evt.preventDefault();
  let _this = $(this);
  let oriElement = _this.html();

  const modalBody = _this.closest(".modal-body");
  const form = _this.closest("form");

  let formData = new FormData(form[0]);
  let url = ADMIN_URL + "wactivity" + CREATE;

  formData.append("record_id", ID);

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
      // $('.x_form').prop('disabled', true);
      // $('.close_form').prop('disabled', true);
      loadingForm(modalBody.prop("id"), "facebook");
    },
    complete: function () {
      $(_this).html(oriElement).prop("disabled", false);
      // $('.x_form').removeAttr('disabled');
      // $('.close_form').removeAttr('disabled');
      hideLoadingForm(modalBody.prop("id"));
    },
    success: function (result) {
      if (result) {
        ID = 0;
        form.find("input").prop("readonly", true);
        form.find('select[name="isanswer"]').val("N").prop("disabled", true);
        form.find("button").prop("disabled", true);

        url = ADMIN_URL + "wactivity" + "/showActivityInfo";
        _tableApproval.ajax.url(url).load().columns.adjust();
      }
    },
  });
});

$(".btn_record_info").click(function (evt) {
  const tr = _tableApproval.$("tr.selected");
  const td = tr.find("td");
  const row = _tableApproval.row(td).data();

  ID = row[0];
  let record_id = row[1];
  let table = row[2];
  let menu = row[3];

  let arrData = {
    id: ID,
    record_id: record_id,
    table: table,
    menu: menu,
  };

  arrData = JSON.stringify(arrData);

  sessionStorage.setItem("reloading", "true");
  sessionStorage.setItem("data", arrData);

  window.open(ADMIN_URL + menu, "_self");
});

window.onload = function () {
  let reloading = sessionStorage.getItem("reloading");
  let data = JSON.parse(sessionStorage.getItem("data"));

  if (reloading) {
    sessionStorage.removeItem("reloading");
    Edit(data.record_id, "IP", data.menu);
  }
};

$(".ischeckall").click(function (evt) {
  const target = $(evt.target);
  const cardTableReport = target.closest(".card-table-report");
  const floatRight = cardTableReport.find(".float-right");
  const checkbox = _tableReport.rows().nodes().to$().find("input.check-data");

  if (this.checked) {
    $.each(checkbox, function (i) {
      $(this).prop("checked", true);
    });

    floatRight.removeClass("d-none");
  } else {
    $.each(checkbox, function (i) {
      $(this).prop("checked", false);
    });

    floatRight.addClass("d-none");
  }
});

_tableReport.on("click", ".check-data", function (evt) {
  const target = $(evt.target);
  const cardTableReport = target.closest(".card-table-report");
  const floatRight = cardTableReport.find(".float-right");
  const checkbox = _tableReport.rows().nodes().to$().find("input.check-data");

  //* Checked checkbox
  if ($(this).is(":checked")) {
    let noChkdData = [];

    $.each(checkbox, function (idx, item) {
      if (!$(item).is(":checked")) noChkdData.push(item.value);
    });

    floatRight.removeClass("d-none");

    if (noChkdData.length == 0) $(".ischeckall").prop("checked", true);
  } else {
    let chkdData = [];

    $.each(checkbox, function (idx, item) {
      if ($(item).is(":checked")) chkdData.push(item.value);
    });

    if (chkdData.length == 0) floatRight.addClass("d-none");

    $(".ischeckall").prop("checked", false);
  }
});

$(".btn_print_qrcode").on("click", function (evt) {
  let _this = $(this);
  let oriElement = _this.html();
  let textElement = _this.text().trim();

  let formData = new FormData();

  let checkedCbx = _tableReport
    .rows()
    .nodes()
    .to$()
    .find("input.check-data:checked");

  let row = [];

  $.each(checkedCbx, function (idx, item) {
    row.push(item.value);
  });

  formData.append("assetcode", JSON.stringify(row));

  $.ajax({
    url: CURRENT_URL + "/print",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      $(_this)
        .html(
          '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' +
            textElement
        )
        .prop("disabled", true);
      $(".btn_ok_form").prop("disabled", true);
      $(".btn_reset_form").prop("disabled", true);
    },
    complete: function () {
      $(_this).html(oriElement).prop("disabled", false);
      $(".btn_ok_form").removeAttr("disabled");
      $(".btn_reset_form").removeAttr("disabled");
    },
    success: function (response) {
      downloadFile(response);
    },
  });
});

function downloadFile(url) {
  let fileName = url.substr(url.lastIndexOf("/") + 1);

  $.ajax({
    url: url,
    method: "GET",
    xhrFields: {
      responseType: "blob",
    },
    success: function (data) {
      var a = document.createElement("a");
      var url = window.URL.createObjectURL(data);
      a.href = url;
      a.download = fileName;
      document.body.append(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    },
  });
}

$('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
  const target = $(e.target);
  const modal = target.closest(".modal");
  const tabPane = modal.find(".tab-pane.active");
  const form = tabPane.find("form");
  const tableTab = form.find("table.tb_displaytab");
  const inputForeign = form.find("input.foreignkey");
  let href = target.attr("href");

  let data = [];
  let id = tabPane.attr("set-id");
  href = href.substring(1, href.length);

  changeTab = true;

  let tableID = tableTab.attr("id");

  let url = `${ADMIN_URL}${href}${SHOW}${id}`;

  if (typeof id === "undefined") url = `${ADMIN_URL}${href}${SHOW}`;

  if (tableTab.length > 1) tableID = $(tableTab[1]).attr("id");

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

  const field = form.find("input, textarea, select").not(".line");

  for (let i = 0; i < field.length; i++) {
    let fields = [];

    if (field[i].name !== "") {
      // set field is checked by default from set attribute on the field
      if (field[i].type == "checkbox" && fieldChecked.includes(field[i].name))
        form
          .find("input:checkbox[name=" + field[i].name + "]")
          .prop("checked", true);

      //? Condition field and contain attribute hide-field
      if ($(field[i]).attr("hide-field")) {
        fields = $(field[i])
          .attr("hide-field")
          .split(",")
          .map((element) => element.trim());

        if (field[i].type === "checkbox") {
          if (field[i].checked) {
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

      //? Condition field and contain attribute show-field
      if ($(field[i]).attr("show-field")) {
        fields = $(field[i])
          .attr("show-field")
          .split(",")
          .map((element) => element.trim());

        if (field[i].type === "checkbox") {
          if (field[i].checked) {
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
          } else if (field[i].type === "checkbox") {
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
    }
  }

  if (inputForeign.length) {
    id = inputForeign.attr("set-id");

    const SHOW = "/show";
    url = `${ADMIN_URL}${href}${SHOW}`;

    data = {
      [inputForeign.attr("name")]: id,
    };
  }

  clearErrorForm(form);

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

        if (arrMsg.header) {
          let data = arrMsg.header;
          putFieldData(form, data);

          for (let i = 0; i < data.length; i++) {
            let fieldInput = data[i].field;
            let label = data[i].label;
            let primarykey = data[i].primarykey;

            if (primarykey) {
              ID = label;
              tabPane.attr("set-save", "update");
            }
          }
        } else {
          clearForm(e);

          if (form.find('input[type="checkbox"].active').length)
            form.find('input[type="checkbox"].active').prop("checked", true);
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
});

function showForeignKey(url, id, field) {
  url = `${ADMIN_URL}${url}/${id}`;

  $.getJSON(url, function (result) {
    field.val(result.text);
  }).fail(function (jqXHR, textStatus, errorThrown) {
    console.info(errorThrown);
  });
}

function Print(id) {
  const parent = $(".container");
  const main_page = parent.find(".main_page");
  let s = parent.find(".card");

  if (s.length > 1) s = parent.find(".page-inner");
  else s = main_page.find(".card");

  $.ajax({
    url: CURRENT_URL + PRINT + id,
    type: "POST",
    data: formData,
    cache: false,
    dataType: "JSON",
    beforeSend: function () {
      s.addClass("is-loading");
    },
    complete: function () {
      s.removeClass("is-loading");
    },
    success: function (response) {
      downloadFile(response);
    },
  });
}
