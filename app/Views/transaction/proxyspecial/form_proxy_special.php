<div class="card-body card-form">
    <form class="form-horizontal form-absent" id="form_proxy_special">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="sys_user_from">Pengguna <span class="required">*</span></label>
                    <select class="form-control select-data" id="sys_user_from" name="sys_user_from"
                        data-url="user/getList">
                        <option value="">Pilih Pengguna</option>
                    </select>
                    <small class="form-text text-danger" id="error_sys_user_from"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="sys_user_to">Diwakilkan oleh <span class="required">*</span></label>
                    <select class="form-control select2" id="sys_user_to" name="sys_user_to">
                        <option value="">Pilih Pengguna</option>
                    </select>
                    <small class="form-text text-danger" id="error_sys_user_to"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="documentno">No Form</label>
                    <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]"
                        readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Mulai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-start" name="startdate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_startdate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date">Tanggal Selesai <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control datepicker-end" name="enddate">
                        <span class="input-icon-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_enddate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="submissiondate">Tanggal Pembuatan <span class="required">*</span></label>
                    <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                        value=<?= $today ?> disabled>
                    <small class="form-text text-danger" id="error_submissiondate"></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="receiveddate">Tanggal Diterima</label>
                    <input type="text" class="form-control datepicker" id="receiveddate" name="receiveddate" readonly>
                    <small class="form-text text-danger" id="error_receiveddate"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="reason">Alasan</label>
                    <textarea type="text" class="form-control" name="reason" rows="4"></textarea>
                    <small class="form-text text-danger" id="error_reason"></small>
                </div>
            </div>
            <!-- <div class="col-md-3">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="ispermanent" name="ispermanent"
                            hide-field="enddate">
                        <span class="form-check-sign">Permanent</span>
                    </label>
                </div>
            </div> -->
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>