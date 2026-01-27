<div class="modal fade" id="modal_generate_period" modal-type="not-main">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buat Periode</h4>
            </div>
            <div class="modal-body" id="realization">
                <form class="form-horizontal" id="form_generate_period">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="startdate">Tanggal Mulai Periode <span class="required">*</span></label>
                                <div class="input-icon">
                                    <input type="text" class="form-control datepicker" name="startdate" autocomplete="off">
                                    <span class="input-icon-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>
                                <small class="form-text text-danger" id="error_startdate"></small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-icon btn-round btn-danger btn_close_generate_period"
                    data-toggle="tooltip" data-placement="top" title="Cancel" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
                <button type="button" class="btn btn-icon btn-round btn-primary btn_ok_generate_period"
                    data-toggle="tooltip" data-placement="top" title="OK">
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_period_control" modal-type="not-main">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Kontrol Periode</h4>
            </div>
            <div class="modal-body" id="period_control">
                <div class="row card-tab">
                    <div class="col-md-12">
                        <div class="card card-with-nav">
                            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="period-control" role="tabpanel" aria-labelledby="rule-tab">
                                    <form class="form-horizontal" id="form_period_control">
                                        <?= csrf_field(); ?>
                                        <!-- <h4 class="card-title ml-2">Kontrol Periode</h4> -->
                                        <div class="form-group row">
                                            <label for="name" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Nama Periode <span class="required-label">*</span></label>
                                            <div class="col-lg-4 col-md-4 col-sm-4">
                                                <input type="text" class="form-control foreignkey" name="md_period_id" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="text-right">
                                                        <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto statusaction">Open / Close All</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group table-responsive">
                                                    <table class="table-rounded table-head-bg-primary table-hover tb_displaytab" id="table-period-control" style="width: 100%">
                                                        <thead>
                                                            <tr>
                                                                <th></th>
                                                                <th class="text-center">#</th>
                                                                <th class="text-center">Tipe Form</th>
                                                                <th class="text-center">Period Status</th>
                                                            </tr>
                                                        </thead>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-round close_period" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-round save_period">Save changes</button>
            </div>
        </div>
    </div>
</div>