/**
 * Event Listener Quotation Detail
 */
_tableLine.on('change', 'input[name="isspare"]', function (evt) {
    const tr = _tableLine.$(this).closest('tr');

    tr.find('select[name="md_employee_id"]')
        .val(null).change()
        .removeAttr('disabled');

    if (tr.find('select[name="md_branch_id"]').length > 0) {
        tr.find('select[name="md_branch_id"]')
            .val(null).change()
            .removeAttr('disabled');
    }

    if (tr.find('select[name="md_division_id"]').length > 0) {
        tr.find('select[name="md_division_id"]')
            .val(null).change()
            .removeAttr('disabled');
    }

    if (tr.find('select[name="md_room_id"]').length > 0) {
        tr.find('select[name="md_room_id"]').empty();
    }
});

// update field input name line amount
_tableLine.on('keyup', 'input[name="qtyentered"], input[name="unitprice"]', function (evt) {
    const tr = _tableLine.$(this).closest('tr');

    let value = this.value;
    let lineamt, qty, unitprice = 0;

    const referenceField = tr.find('input[name="qtyentered"], input[name="unitprice"]');

    if (referenceField.length > 1) {
        if ($(this).attr('name') == 'unitprice') {
            qty = replaceRupiah(tr.find('input[name="qtyentered"]').val());
            value = replaceRupiah(this.value);

            lineamt = (value * qty);
        }

        if ($(this).attr('name') == 'qtyentered') {
            unitprice = replaceRupiah(tr.find('input[name="unitprice"]').val());

            lineamt = (value * unitprice);
        }

        tr.find('input[name="lineamt"]').val(formatRupiah(lineamt));
    }

    if (tr.find('input[name="priceaftertax"]').length > 0 && $(this).attr('name') == 'unitprice') {
        let priceAfterTax = parseInt(value);
        priceAfterTax += (priceAfterTax * 0.11);

        tr.find('input[name="priceaftertax"]').val(formatRupiah(priceAfterTax));
    }
});

/**
 * Event Listener Receipt Detail
 */
let prev;

$(document).ready(function (evt) {
    $('#trx_quotation_id').on('focus', function (e) {
        prev = this.value;
    }).change(function (evt) {
        const form = $(this).closest('form');
        const attrName = $(this).attr('name');

        let quotation_id = this.value;

        // create data
        if (quotation_id !== '' && setSave === 'add') {
            _tableLine.clear().draw(false);
            setReceiptDetail(form, attrName, quotation_id);
        }

        // update data
        $.each(option, function (idx, elem) {
            if (elem.fieldName === attrName && setSave !== 'add') {
                // Logic quotation_id is not null and current value not same value from database and datatable is not empty
                if (quotation_id !== '' && quotation_id != elem.option_ID && _tableLine.data().any()) {
                    Swal.fire({
                        title: 'Delete?',
                        text: "Are you sure you want to change all data ? ",
                        type: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Okay',
                        cancelButtonText: 'Close',
                        reverseButtons: true
                    }).then((data) => {
                        if (data.value) {
                            _tableLine.clear().draw(false);
                            setReceiptDetail(form, attrName, quotation_id, ID);
                        } else {
                            form.find('select[name=' + attrName + ']').val(elem.option_ID).change();
                        }
                    });
                }

                // Logic quotation_id is not null and not same value from database and datatable is empty
                if (quotation_id !== '' && quotation_id != elem.option_ID && !_tableLine.data().any()) {
                    setReceiptDetail(form, attrName, quotation_id);
                }

                // Logic prev data not same currentvalue and value from database and datatable is empty
                if (typeof prev !== 'undefined' && prev !== '' && quotation_id !== '' && prev != quotation_id && prev != elem.option_ID && !_tableLine.data().any()) {
                    _tableLine.clear().draw(false);
                    setReceiptDetail(form, attrName, quotation_id);
                }
            }
        });

        // callback value to prev
        prev = this.value;
    });
});

_tableLine.on('change', 'select[name="md_employee_id"]', function (evt) {
    const tr = _tableLine.$(this).closest('tr');
    let employee_id = this.value;

    if (employee_id !== '') {
        // Column Branch
        if (tr.find('select[name="md_branch_id"]').length > 0)
            getOption('branch', 'md_branch_id', tr, null, employee_id);
        // Column Division
        if (tr.find('select[name="md_division_id"]').length > 0)
            getOption('division', 'md_division_id', tr, null, employee_id);
        // Column Room
        if (tr.find('select[name="md_room_id"]').length > 0)
            getOption('room', 'md_room_id', tr, null, employee_id);
    }
});

// Function for getter datatable from quotation
function setReceiptDetail(form, fieldName, id, receipt_id = 0) {
    const field = form.find('input, select, textarea');
    let url = SITE_URL + '/getDetailQuotation';

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            id: id,
            receipt_id: receipt_id
        },
        cache: false,
        dataType: 'JSON',
        beforeSend: function () {
            $('.x_form').prop('disabled', true);
            $('.close_form').prop('disabled', true);
            loadingForm(form.prop('id'), 'facebook');
        },
        complete: function () {
            $('.x_form').removeAttr('disabled');
            $('.close_form').removeAttr('disabled');
            hideLoadingForm(form.prop('id'));
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
                            if (field[i].name !== '' && field[i].name === fieldInput) {
                                const select = form.find('select[name=' + field[i].name + ']');

                                if ($(field[i]).attr('hide-field'))
                                    fields = $(field[i]).attr('hide-field').split(',').map(element => element.trim());

                                if (field[i].type === 'select-one' && fieldName !== fieldInput) {
                                    if (typeof label === 'object' && label !== null && fields.includes(field[i].name)) {
                                        let newOption = $("<option selected='selected'></option>").val(label.id).text(label.name);
                                        select.append(newOption).change();

                                        let formGroup = select.closest('.form-group, .form-check');
                                        formGroup.show();
                                    } else if (typeof label === 'string' && label !== null) {
                                        select.val(label).change();
                                    } else {
                                        select.val(null).change();

                                        let formGroup = select.closest('.form-group, .form-check');
                                        formGroup.hide();
                                    }
                                }

                                if (field[i].type === 'select-one' && fieldName === fieldInput && setSave !== 'add')
                                    select.prop('disabled', true);

                                if (field[i].type === 'textarea' && label !== '')
                                    form.find('textarea[name=' + field[i].name + ']').val(label);
                                else
                                    form.find('textarea[name=' + field[i].name + ']').val(null);


                                if (field[i].type === 'checkbox' && label === 'Y')
                                    form.find('input:checkbox[name=' + field[i].name + ']').prop('checked', true);
                                else
                                    form.find('input:checkbox[name=' + field[i].name + ']').prop('checked', false);

                                if (field[i].type === 'text') {
                                    if (fieldInput === 'docreference') {
                                        if (label !== '') {
                                            form.find('input[name=' + field[i].name + ']').val(label);

                                            //* Field Invoice No
                                            form.find('input[name=invoiceno]').val('-');
                                        } else {
                                            form.find('input[name=' + field[i].name + ']').val(null);

                                            //* Field Invoice No
                                            form.find('input[name=invoiceno]').val(null);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (arrMsg.line) {
                    if (form.find('table.tb_displayline').length > 0) {
                        let line = JSON.parse(arrMsg.line);

                        $.each(line, function (idx, elem) {
                            _tableLine.row.add(elem).draw(false);
                        });

                        const input = _tableLine.rows().nodes().to$().find('input, select');

                        $.each(input, function (idx, item) {
                            const tr = $(item).closest('tr');
                            let employee_id = tr.find('select[name="md_employee_id"]').val();

                            // Column Branch
                            if (this.name === 'md_branch_id')
                                getOption('branch', 'md_branch_id', tr, null, employee_id);
                            // Column Division
                            if (this.name === 'md_division_id')
                                getOption('division', 'md_division_id', tr, null, employee_id);
                            // Column Room
                            if (this.name === 'md_room_id')
                                getOption('room', 'md_room_id', tr, null, employee_id);
                        });
                    }
                }
            } else {
                Toast.fire({
                    type: 'error',
                    title: result[0].message
                });
            }
        },
        error: function (jqXHR, exception) {
            showError(jqXHR, exception);
        }
    });
}

function getOption(controller, field, tr, selected_id, ref_id = null) {
    let url = ADMIN_URL + controller + '/getList';
    const form = tr.closest('form');

    tr.find('select[name =' + field + ']').empty();

    $.ajax({
        url: url,
        type: 'POST',
        cache: false,
        data: {
            reference: ref_id
        },
        dataType: 'JSON',
        beforeSend: function () {
            $('.x_form').prop('disabled', true);
            $('.close_form').prop('disabled', true);
            loadingForm(form.prop('id'), 'facebook');
        },
        complete: function () {
            $('.x_form').removeAttr('disabled');
            $('.close_form').removeAttr('disabled');
            hideLoadingForm(form.prop('id'));
        },
        success: function (result) {
            tr.find('select[name =' + field + ']').append('<option value=""></option>');

            if (!result[0].error) {
                $.each(result, function (idx, item) {
                    // Check property key isset and key equal id or set selected equal id
                    if (typeof item.key !== 'undefined' && item.key == item.id || selected_id == item.id) {
                        tr.find('select[name =' + field + ']').append('<option value="' + item.id + '" selected>' + item.text + '</option>')
                        tr.find('select[name =' + field + ']').attr('value', item.id);
                    } else {
                        tr.find('select[name =' + field + ']').append('<option value="' + item.id + '">' + item.text + '</option>');
                    }
                });
            } else {
                Swal.fire({
                    type: 'error',
                    title: result[0].message,
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        },
        error: function (jqXHR, exception) {
            showError(jqXHR, exception);
        }
    });
}

/**
 * MASTER DATA EMPLOYEE
 */
$('#form_employee, #form_opname').on('change', '#md_branch_id', function (evt) {
    let _this = $(this);
    let url = ADMIN_URL + 'room' + '/getList';
    let value = this.value;
    const form = _this.closest('form');

    form.find('[name="md_room_id"]').empty();

    if (value !== '') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value,
                key: 'branch'
            },
            beforeSend: function () {
                $('.save_form').attr('disabled', true);
                $('.close_form').attr('disabled', true);
                loadingForm(form.prop('id'), 'pulse');
            },
            complete: function () {
                $('.save_form').removeAttr('disabled');
                $('.close_form').removeAttr('disabled');
                hideLoadingForm(form.prop('id'));
            },
            dataType: 'JSON',
            success: function (result) {
                form.find('[name="md_room_id"]').append('<option value=""></option>');

                let md_room_id = 0;

                $.each(option, function (i, item) {
                    if (item.fieldName == 'md_room_id')
                        md_room_id = item.label;
                });

                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        if (form.find('[name="md_room_id"]').length > 0) {
                            if (md_room_id == item.id) {
                                form.find('[name="md_room_id"]').append('<option value="' + item.id + '" selected>' + item.text + '</option>');
                            } else {
                                form.find('[name="md_room_id"]').append('<option value="' + item.id + '">' + item.text + '</option>');
                            }
                        } else {
                            Swal.fire({
                                type: 'error',
                                title: 'Field is not found',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

/**
 * Event Listener Movement Detail
 */
_tableLine.on('change', 'select[name="assetcode"]', function (evt) {
    const tr = _tableLine.$(this).closest('tr');

    let url = ADMIN_URL + 'inventory' + '/getAssetDetail';
    let value = this.value;

    $.ajax({
        url: url,
        type: 'POST',
        cache: false,
        data: {
            assetcode: value
        },
        dataType: 'JSON',
        success: function (result) {
            if (result[0].success) {
                $.each(result[0].message, function (idx, item) {
                    if (tr.find('select[name="md_product_id"]').length > 0) {
                        tr.find('select[name="md_product_id"]').val(item.md_product_id).change();
                    }

                    if (tr.find('select[name="employee_from"]').length > 0) {
                        tr.find('select[name="employee_from"]').val(item.md_employee_id).change();
                    }

                    if (tr.find('select[name="branch_from"]').length > 0) {
                        tr.find('select[name="branch_from"]').val(item.md_branch_id).change();
                    }

                    if (tr.find('select[name="division_from"]').length > 0) {
                        tr.find('select[name="division_from"]').val(item.md_division_id).change();
                    }

                    if (tr.find('select[name="room_from"]').length > 0) {
                        tr.find('select[name="room_from"]').val(item.md_room_id).change();
                    }
                });
            } else {
                Toast.fire({
                    type: 'error',
                    title: result[0].message
                });
            }
        },
        error: function (jqXHR, exception) {
            showError(jqXHR, exception);
        }
    });
});

// Event change field Status
_tableLine.on('change', 'select[name="md_status_id"]', function (evt) {
    const tr = _tableLine.$(this).closest('tr');
    let value = $(this).find('option:selected').text();

    if (value === 'RUSAK') {
        getOption('employee', 'employee_to', tr, 100130); // Selected Employee IT
        tr.find('select[name="employee_to"]').attr('disabled', true);
        // Column Branch
        getOption('branch', 'branch_to', tr, 100001); // Selected Branch Sunter
        // Column Division
        getOption('division', 'division_to', tr, 100006); // Selected Division IT
        // Column Room
        getOption('room', 'room_to', tr, 100041); // Selected Room To BARANG RUSAK
        tr.find('select[name="room_to"]').attr('disabled', true);
    } else {
        if (checkExistUserRole('W_View_All_Movement')) {
            tr.find('select[name="employee_to"]')
                .val(null).change()
                .removeAttr('disabled');
        } else {
            getOption('employee', 'employee_to', tr, null, value);
            tr.find('select[name="employee_to"]').removeAttr('disabled');
        }

        //* Set null value on the field dropdown change status 
        tr.find('select[name="branch_to"]').val(null).change();
        tr.find('select[name="division_to"]').val(null).change();
        tr.find('select[name="room_to"]').val(null).change();
    }
});

// Event change field Employee To
_tableLine.on('change', 'select[name="employee_to"]', function (evt) {
    const tr = _tableLine.$(this).closest('tr');
    let value = $(this).find('option:selected').text();
    let status = tr.find('select[name="status_id"] option:selected').text();
    let employee_id = this.value;

    if (value === 'IT') {
        // Column Branch
        getOption('branch', 'branch_to', tr, 100001);
        // Column Division
        getOption('division', 'division_to', tr, 100006);

        if (status === 'RUSAK') {
            // Column Room
            getOption('room', 'room_to', tr, 100041);
            tr.find('select[name="room_to"]').attr('disabled', true);
        }

        if (status === 'BAGUS') {
            // Column Room
            getOption('room', 'room_to', tr, null, 'IT');
            tr.find('select[name="room_to"]').removeAttr('disabled');
        }
    } else {
        // Column Branch
        getOption('branch', 'branch_to', tr, null, employee_id);
        // Column Division
        getOption('division', 'division_to', tr, null, employee_id);
        // Column Room
        getOption('room', 'room_to', tr, null, employee_id);
        tr.find('select[name="room_to"]').removeAttr('disabled');
    }

    if (value === '') {
        tr.find('select[name="branch_to"]').val(null).change();
        tr.find('select[name="division_to"]').val(null).change();
        tr.find('select[name="room_to"]').val(null).change();
    }
});

/**
 * Event Menu Inventory
 */
// Form Inventory
$('#form_inventory').on('change', '#md_branch_id', function (evt) {
    let url = ADMIN_URL + 'room' + '/getList';
    let value = this.value;

    $('#md_room_id').empty();

    if (value !== '') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value,
                key: 'all'
            },
            beforeSend: function () {
                $('.save_form').attr('disabled', true);
                $('.close_form').attr('disabled', true);
                loadingForm('form_inventory', 'pulse');
            },
            complete: function () {
                $('.save_form').removeAttr('disabled');
                $('.close_form').removeAttr('disabled');
                hideLoadingForm('form_inventory');
            },
            dataType: 'JSON',
            success: function (result) {
                $('#md_room_id').append('<option value=""></option>');

                let md_room_id = 0;

                $.each(option, function (i, item) {
                    if (item.fieldName == 'md_room_id')
                        md_room_id = item.label;
                });

                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        if (md_room_id == item.id) {
                            $('#md_room_id').append('<option value="' + item.id + '" selected>' + item.text + '</option>');
                        } else {
                            $('#md_room_id').append('<option value="' + item.id + '">' + item.text + '</option>');
                        }
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

$('#form_inventory').on('change', '#md_room_id', function (evt) {
    let url = ADMIN_URL + 'employee' + '/getList';
    let value = this.value;
    let md_room_id = 0;
    let md_branch_id = $('#md_branch_id option:selected').val();

    $.each(option, function (i, item) {
        if (item.fieldName == 'md_room_id') {
            md_room_id = item.label;
        }
    });

    $('#md_employee_id').empty();

    if ((value !== '' || md_room_id !== '') && md_branch_id !== '') {
        let refValue = value !== '' ? value : md_room_id;

        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: refValue,
                branch: md_branch_id
            },
            beforeSend: function () {
                $('.save_form').attr('disabled', true);
                $('.close_form').attr('disabled', true);
                loadingForm('form_inventory', 'pulse');
            },
            complete: function () {
                $('.save_form').removeAttr('disabled');
                $('.close_form').removeAttr('disabled');
                hideLoadingForm('form_inventory');
            },
            dataType: 'JSON',
            success: function (result) {
                $('#md_employee_id').append('<option value=""></option>');

                let md_employee_id = 0;

                $.each(option, function (i, item) {
                    if (item.fieldName == 'md_employee_id') {
                        md_employee_id = item.label;
                    }
                });

                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        // Check employee from db and event first change edit is null value or event change get value
                        if (md_employee_id == item.id && value == '' || md_employee_id == item.id && value == md_room_id) {
                            $('#md_employee_id').append('<option value="' + item.id + '" selected>' + item.text + '</option>');
                        } else {
                            $('#md_employee_id').append('<option value="' + item.id + '">' + item.text + '</option>');
                        }
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

// Form Filter
$(document).ready(function (e) {
    $('.select-product').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        minimumInputLength: 3,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'product/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-branch').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'branch/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-branch').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'branch/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-division').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'division/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-division').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'division/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-room').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'room/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-room').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'room/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-employee').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        minimumInputLength: 3,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'employee/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-supplier').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'supplier/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-supplier').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'supplier/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-groupasset').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'groupasset/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-groupasset').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'groupasset/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-brand').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'brand/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-brand').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'brand/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-category').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'category/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-category').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'category/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-subcategory').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'subcategory/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-subcategory').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'subcategory/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.select-type').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        allowClear: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'type/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-type').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'type/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-assetcode').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'inventory/getAssetCode',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('.multiple-select-employee').select2({
        placeholder: 'Select an option',
        width: '100%',
        theme: 'bootstrap',
        multiple: true,
        ajax: {
            dataType: 'JSON',
            url: ADMIN_URL + 'employee/getList',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                }
            },
            processResults: function (data, page) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

});

$('#filter_inventory').on('change', '[name="md_branch_id"]', function (evt) {
    let url = ADMIN_URL + 'room' + '/getList';
    let value = this.value;

    $('[name="md_room_id"]').empty();

    // Set condition when clear or value zero
    if (value !== '' && value !== '0') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value,
                key: 'all'
            },
            dataType: 'JSON',
            success: function (result) {
                $('[name="md_room_id"]').append('<option value=""></option>');

                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        $('[name="md_room_id"]').append('<option value="' + item.id + '">' + item.text + '</option>');
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

/**
 * Event Listener Sequence
 */
$('#form_sequence').on('click', '#isautosequence, #isgassetlevelsequence, #iscategorylevelsequence, #startnewyear', function (evt) {
    const target = $(evt.target);
    const form = target.closest('form');

    //? Condition field and contain attribute hide-field
    if ($(this).attr('hide-field')) {
        let fields = $(this).attr('hide-field').split(',').map(element => element.trim());

        if ($(this).is(':checked')) {
            for (let i = 0; i < fields.length; i++) {
                let formGroup = form.find('input[name=' + fields[i] + '], textarea[name=' + fields[i] + '], select[name=' + fields[i] + ']').closest('.form-group, .form-check');
                formGroup.hide();
            }
        } else {
            for (let i = 0; i < fields.length; i++) {
                let formGroup = form.find('input[name=' + fields[i] + '], textarea[name=' + fields[i] + '], select[name=' + fields[i] + ']').closest('.form-group, .form-check');
                formGroup.show();
            }
        }
    }

    //? Condition field and contain attribute show-field
    if ($(this).attr('show-field')) {
        let fields = $(this).attr('show-field').split(',').map(element => element.trim());

        if ($(this).is(':checked')) {
            for (let i = 0; i < fields.length; i++) {
                let formGroup = form.find('input[name=' + fields[i] + '], textarea[name=' + fields[i] + '], select[name=' + fields[i] + ']').closest('.form-group, .form-check');
                formGroup.show();
            }
        } else {
            for (let i = 0; i < fields.length; i++) {
                let formGroup = form.find('input[name=' + fields[i] + '], textarea[name=' + fields[i] + '], select[name=' + fields[i] + ']').closest('.form-group, .form-check');
                formGroup.hide();
            }
        }
    }
});

$('.upload_form').click(function (evt) {
    console.log(evt)
    $('.modal_upload').modal({
        backdrop: 'static',
        keyboard: false
    });
    Scrollmodal();
})

$('.custom-file-input').change(function (e) {
    var name = document.getElementById("customFileInput").files[0].name;
    var nextSibling = e.target.nextElementSibling
    nextSibling.innerText = name
});

$('.save_upload').click(function (evt) {
    var fd = new FormData();

    fd.append('file', $('#customFileInput')[0].files[0])
    $.ajax({
        url: SITE_URL + '/import',
        type: "POST",
        data: fd,
        processData: false, // important
        contentType: false, // important
        dataType: "JSON",
        success: function (response) {
            console.log(response)
        }
    });

    // console.log(fd)
});

/**
 * Event Listener Responsible Type
 */
$('#form_responsible').on('change', '#responsibletype', function (evt) {
    const target = $(evt.target);
    const form = target.closest('form');
    const value = this.value;

    //? Condition field and contain attribute hide-field
    if ($(this).attr('hide-field')) {
        if (value === 'R') {
            form.find('select[name=sys_role_id]').closest('.form-group').show();
        } else {
            form.find('select[name=sys_role_id]').closest('.form-group').hide();
        }

        if (value === 'H') {
            form.find('select[name=sys_user_id]').closest('.form-group').show();
        } else {
            form.find('select[name=sys_user_id]').closest('.form-group').hide();
        }
    }
});

$('#parameter_assetdetail').on('change', '[name="md_branch_id"]', function (evt) {
    let url = ADMIN_URL + 'room' + '/getList';
    let value = this.value;

    $('[name="md_room_id"]').empty();

    // Set condition when clear or value zero
    if (value !== '' && value !== '0') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value,
                key: 'all'
            },
            dataType: 'JSON',
            success: function (result) {
                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        $('[name="md_room_id"]').append('<option value="' + item.id + '">' + item.text + '</option>');
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

$('#form_internaluse').on('click', '[name="isfrom"]', function (evt) {
    const target = $(evt.target);
    const form = target.closest('form');

    if ($(this).attr('hide-field')) {
        let fields = $(this).attr('hide-field').split(',').map(element => element.trim());

        if ($(this).is(':checked')) {
            for (let i = 0; i < fields.length; i++) {
                if (this.id === 'supplier') {
                    form.find('select[name=md_supplier_id]').not('.line')
                        .val(null).change()
                        .closest('.form-group').show();
                    form.find('select[name=md_employee_id]').not('.line')
                        .val(null).change()
                        .closest('.form-group').hide();
                } else if (this.id === 'employee') {
                    form.find('select[name=md_supplier_id]').not('.line')
                        .val(null).change()
                        .closest('.form-group').hide();
                    form.find('select[name=md_employee_id]').not('.line')
                        .val(null).change()
                        .closest('.form-group').show();
                } else if (this.id === 'other') {
                    form.find('select[name=md_supplier_id]').not('.line')
                        .val(null).change()
                        .closest('.form-group').hide();
                    form.find('select[name=md_employee_id]').not('.line')
                        .val(null).change()
                        .closest('.form-group').hide();
                }
            }
        }
    }
});

/**
 * Event Listener With Text Barcode
 */
$('#form_barcode').on('change', '#iswithtext', function (evt) {
    const target = $(evt.target);
    const form = target.closest('form');
    const value = this.value;

    //? Condition field and contain attribute show-field
    if ($(this).attr('show-field')) {
        let fields = $(this).attr('show-field').split(',').map(element => element.trim());

        if ($(this).is(':checked')) {
            for (let i = 0; i < fields.length; i++) {
                form.find('input[name=' + fields[i] + '], select[name=' + fields[i] + ']')
                    .closest('.form-group')
                    .show();
            }
        } else {
            for (let i = 0; i < fields.length; i++) {
                form.find('input[name=' + fields[i] + '], select[name=' + fields[i] + ']')
                    .val(null).change()
                    .closest('.form-group')
                    .hide();
            }
        }
    }
});

/**
 * Event Listener Product Form
 */
$('#form_product, #form_product_info').on('change', '#md_category_id', function (evt) {
    const form = $(this).closest('form');

    let url = ADMIN_URL + 'subcategory' + '/getList';
    let value = this.value;

    $('#md_subcategory_id').empty();

    if (value !== '') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value
            },
            beforeSend: function () {
                $('.save_form').attr('disabled', true);
                $('.close_form').attr('disabled', true);
                $('.x_form').attr('disabled', true);
                $('.btn_requery_info').attr('disabled', true);
                $('.btn_close_info').attr('disabled', true);
                $('.btn_save_info').attr('disabled', true);
                loadingForm(form.prop('id'), 'pulse');
            },
            complete: function () {
                $('.save_form').removeAttr('disabled');
                $('.close_form').removeAttr('disabled');
                $('.x_form').removeAttr('disabled');
                $('.btn_requery_info').removeAttr('disabled');
                $('.btn_close_info').removeAttr('disabled');
                $('.btn_save_info').removeAttr('disabled');
                hideLoadingForm(form.prop('id'));
            },
            dataType: 'JSON',
            success: function (result) {
                $('#md_subcategory_id').append('<option value=""></option>');

                let md_subcategory_id = 0;

                $.each(option, function (i, item) {
                    if (item.fieldName == 'md_subcategory_id')
                        md_subcategory_id = item.label;
                });

                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        if (md_subcategory_id == item.id) {
                            $('#md_subcategory_id').append('<option value="' + item.id + '" selected>' + item.text + '</option>');
                        } else {
                            $('#md_subcategory_id').append('<option value="' + item.id + '">' + item.text + '</option>');
                        }
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

$('#form_product, #form_product_info').on('change', '#md_subcategory_id', function (evt) {
    const form = $(this).closest('form');

    let url = ADMIN_URL + 'type' + '/getList';
    let value = this.value;

    $('#md_type_id').empty();

    if (value !== '') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value
            },
            beforeSend: function () {
                $('.save_form').attr('disabled', true);
                $('.close_form').attr('disabled', true);
                $('.x_form').attr('disabled', true);
                $('.btn_requery_info').attr('disabled', true);
                $('.btn_close_info').attr('disabled', true);
                $('.btn_save_info').attr('disabled', true);
                loadingForm(form.prop('id'), 'pulse');
            },
            complete: function () {
                $('.save_form').removeAttr('disabled');
                $('.close_form').removeAttr('disabled');
                $('.x_form').removeAttr('disabled');
                $('.btn_requery_info').removeAttr('disabled');
                $('.btn_close_info').removeAttr('disabled');
                $('.btn_save_info').removeAttr('disabled');
                hideLoadingForm(form.prop('id'));
            },
            dataType: 'JSON',
            success: function (result) {
                $('#md_type_id').append('<option value=""></option>');

                let md_type_id = 0;

                $.each(option, function (i, item) {
                    if (item.fieldName == 'md_type_id')
                        md_type_id = item.label;
                });

                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        if (md_type_id == item.id) {
                            $('#md_type_id').append('<option value="' + item.id + '" selected>' + item.text + '</option>');
                        } else {
                            $('#md_type_id').append('<option value="' + item.id + '">' + item.text + '</option>');
                        }
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

$('#parameter_barcode').on('change', '#md_branch_id', function (evt) {
    let url = ADMIN_URL + 'room' + '/getList';
    let value = this.value;

    $('#md_room_id').empty();

    // Set condition when clear or value zero
    if (value !== '' && value !== '0') {
        $.ajax({
            url: url,
            type: 'POST',
            cache: false,
            data: {
                reference: value,
                key: 'all'
            },
            dataType: 'JSON',
            success: function (result) {
                if (!result[0].error) {
                    $.each(result, function (idx, item) {
                        $('#md_room_id').append('<option value="' + item.id + '">' + item.text + '</option>');
                    });
                } else {
                    Swal.fire({
                        type: 'error',
                        title: result[0].message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

$('#form_opname').on('change', '#md_employee_id', function (evt) {
    const form = $(this).closest('form');
    let formData = new FormData();

    trx_opname_id = this.value;

    formData.append('md_employee_id', trx_opname_id);

    let url = SITE_URL + '/getDetailAsset';

    if (trx_opname_id !== '' && setSave === 'add') {
        _tableLine.clear().draw(false);

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'JSON',
            beforeSend: function () {
                $('.x_form').prop('disabled', true);
                $('.close_form').prop('disabled', true);
                loadingForm(form.prop('id'), 'facebook');
            },
            complete: function () {
                $('.x_form').removeAttr('disabled');
                $('.close_form').removeAttr('disabled');
                hideLoadingForm(form.prop('id'));
            },
            success: function (result) {
                // console.log(result)
                if (result[0].success) {
                    let arrMsg = result[0].message;

                    if (arrMsg.line) {
                        if (form.find('table.tb_displayline').length > 0) {
                            let line = JSON.parse(arrMsg.line);

                            $.each(line, function (idx, elem) {
                                _tableLine.row.add(elem).draw(false);
                            });
                        }
                    }

                    $('.check-all').parent().show();
                } else {
                    Toast.fire({
                        type: 'error',
                        title: result[0].message
                    });
                }
            },
            error: function (jqXHR, exception) {
                showError(jqXHR, exception);
            }
        });
    }
});

_tableLine.on('change', 'input.check-data', function (evt) {
    const tr = _tableLine.$(this).closest('tr');

    if (this.checked) {
        $("button.delete_line").show();
    } else {
        let checkbox = _tableLine.rows().nodes().to$().find('input.check-data');

        let output = [];
        $.each(checkbox, function (i) {
            if (this.checked) {
                output.push("Y");
            } else {
                output.push("N");
            }

        });

        if (!output.includes("Y")) {
            $("button.delete_line").hide();
        }
    }
});