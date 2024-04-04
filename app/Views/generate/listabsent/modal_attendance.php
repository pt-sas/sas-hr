<div class="modal fade" id="modal_attendance">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Kehadiran</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_attendance">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description">Keterangan <span class="required">*</span></label>
                                <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                                <small class="form-text text-danger" id="error_description"></small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-icon btn-round btn-danger btn_close_attendance" data-toggle="tooltip" data-placement="top" title="Cancel" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
                <button type="button" class="btn btn-icon btn-round btn-primary btn_ok_attendance" data-toggle="tooltip" data-placement="top" title="OK">
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>
</div>