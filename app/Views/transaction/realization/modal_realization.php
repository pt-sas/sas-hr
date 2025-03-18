<div class="modal fade" id="modal_realization_hr_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_realization_hr_agree">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="submissiondate">Tanggal Tidak Masuk<span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control datepicker" name="submissiondate" readonly>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_submissiondate"></small>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="isagree">
                        <input type="hidden" class="form-control" name="md_leavetype_id">
                        <input type="hidden" class="form-control" name="submissionform">
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

<div class="modal fade" id="modal_realization_hr_not_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_realization_hr_not_agree">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="submissiondate">Tanggal Tidak Masuk<span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control datepicker" name="submissiondate" readonly>
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_submissiondate"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="reason">Alasan </label>
                                <textarea type="text" class="form-control" name="reason" rows="4"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="submissiontype">Tipe Form <span class="required">*</span></label>
                                <select class="form-control select-data" id="submissiontype"
                                    name="submissiontype"></select>
                                <small class="form-text text-danger" id="error_submissiontype"></small>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="isagree">
                        <input type="hidden" class="form-control" name="foreignkey">
                        <input type="hidden" class="form-control" name="md_leavetype_id">
                        <input type="hidden" class="form-control" name="submissionform">
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

<div class="modal fade" id="modal_assignment_realization_hr_agree">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Realisasi</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_assignment_realization_hr_agree">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="submissiondate">Tanggal <span class="required">*</span></label>
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
                                <label for="branch_in">Absen Masuk <span class="required">*</span></label>
                                <select class="form-control select-data" id="branch_in" name="branch_in"
                                    data-url="branch/getList" disabled>
                                    <option value="">Pilih Cabang</option>
                                </select>
                                <small class="form-text text-danger" id="error_branch_in"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="starttime_att">Jam Check In</label>
                                <div class="input-icon">
                                    <input type="text" class="form-control" name="starttime_att" id="starttime_att"
                                        disabled>
                                    <span class=" input-icon-addon">
                                        <i class="fa fa-clock"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_starttime_att"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="branch_out">Absen Keluar <span class="required">*</span></label>
                                <select class="form-control select-data" id="branch_out" name="branch_out"
                                    data-url="branch/getList" disabled>
                                    <option value="">Pilih Cabang</option>
                                </select>
                                <small class="form-text text-danger" id="error_branch_out"></small>
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
                                <small class="form-text text-danger" id="error_endtime_att"></small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="comment">Pesan Manager</span></label>
                                <textarea type="text" class="form-control" name="comment" rows="4" readonly></textarea>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="isagree">
                        <input type="hidden" class="form-control" name="submissionform">
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