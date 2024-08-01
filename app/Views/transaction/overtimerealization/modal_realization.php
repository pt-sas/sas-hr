<div class="modal fade" id="modal_overtime_realization_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_overtime_realization_agree">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="starttime">Jam Masuk<span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control timepicker" name="starttime" id="starttime">
                                    <span class=" input-icon-addon">
                                        <i class="fa fa-clock"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_submissiondate"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="enddate">Tanggal Selesai</label>
                                <div class="input-icon">
                                    <input type="text" class="form-control" name="enddate" id="enddate" disabled>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_submissiondate"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endtime">Jam Selesai</label>
                                <div class="input-icon">
                                    <input type="text" class="form-control" name="endtime" id="endtime" disabled>
                                    <span class=" input-icon-addon">
                                        <i class="fa fa-clock"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_submissiondate"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="enddate_att">Tanggal Check Out</label>
                                <div class="input-icon">
                                    <input type="text" class="form-control" name="enddate_att" id="enddate_att"
                                        disabled>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endtime_att">Jam Check Out</label>
                                <div class="input-icon">
                                    <input type="text" class="form-control" name="endtime_att" id="endtime_att"
                                        disabled>
                                    <span class=" input-icon-addon">
                                        <i class="fa fa-clock"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="enddate_realization">Tanggal Realisasi <span
                                        class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control datepicker" name="enddate_realization"
                                        id="enddate_realization">
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_enddate_att"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="endtime_realization">Jam Realisasi <span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control timepicker-end" name="endtime_realization"
                                        id="endtime_realization">
                                    <span class=" input-icon-addon">
                                        <i class="fa fa-clock"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_endtime_att"></small>
                            </div>
                        </div>
                        <input type="hidden" name="startdate">
                        <input type="hidden" class="form-control" name="isagree">
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

<div class="modal fade" id="modal_overtime_realization_not_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_overtime_realization_not_agree">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="reason">Alasan </label>
                                <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="isagree">
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