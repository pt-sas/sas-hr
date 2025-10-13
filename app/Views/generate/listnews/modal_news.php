<div class="modal fade" id="modal_input_news">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Input Kabar</h4>
            </div>
            <div class="modal-body" id="news">
                <form class="form-horizontal" id="form_input_news">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="date">Tanggal Tidak Masuk<span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control datepicker" name="date" disabled>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_date"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="reason">Kabar </label>
                                <textarea type="text" class="form-control" name="reason" rows="4"></textarea>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="md_employee_id">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-icon btn-round btn-danger btn_close_realization"
                    data-toggle="tooltip" data-placement="top" title="Cancel" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
                <button type="button" class="btn btn-icon btn-round btn-primary btn_ok_realization"
                    data-toggle="tooltip" data-placement="top" title="OK">
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>
</div>