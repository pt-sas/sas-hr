<div class="modal fade" id="modal_attendance_realization_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_attendance_realization_agree">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="submissiondate">Tanggal Tidak Lengkap<span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control datepicker" name="submissiondate" disabled>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_submissiondate"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="enddate_att">Tanggal Absen</label>
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
                                <label for="endtime_att">Jam Absen</label>
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
                                        id="enddate_realization" disabled>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
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
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="isagree">
                        <input type="hidden" class="form-control" name="md_leavetype_id">
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

<div class="modal fade" id="modal_realization_attendance_not_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_realization_not_agree">
                    <div class="row">
                        <input type="hidden" class="form-control" name="isagree">
                        <input type="hidden" class="form-control" name="md_leavetype_id">
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