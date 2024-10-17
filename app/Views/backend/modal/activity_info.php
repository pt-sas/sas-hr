<div class="modal fade" id="modal_activity_info">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Workflow Activity</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="activity_info">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group table-responsive">
                            <table class="table table-bordered table-hover table-pointer table_approval" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>#Record ID</th>
                                        <th>#Table</th>
                                        <th>#Menu</th>
                                        <th>Node</th>
                                        <th>Created</th>
                                        <th>Scenario</th>
                                        <th>Summary</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <form class="form-horizontal" id="form_activity_info">
                    <div class="form-group row">
                        <label for="isanswer" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Answer </label>
                        <div class="col-lg-7">
                            <select class="form-control col-md-3" id="isanswer" name="isanswer">
                                <option value="N" selected>No</option>
                                <option value="Y">Yes</option>
                                <option value="W">Yes (With Note)</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <button type="button" class="btn btn-icon btn-primary btn_record_info" data-toggle="tooltip" data-placement="top" title="Record Info">
                                <i class="fas fa-search fa-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="textmsg" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Message </label>
                        <div class="col-lg-7">
                            <input type="text" class="form-control col-md-12" id="textmsg" name="textmsg">
                            <small class="form-text text-danger" id="error_textmsg"></small>
                        </div>
                        <div class="col-lg-2">
                            <button type="button" class="btn btn-icon btn-success btn_ok_answer" data-toggle="tooltip" data-placement="top" title="OK">
                                <i class="fas fa-check fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>