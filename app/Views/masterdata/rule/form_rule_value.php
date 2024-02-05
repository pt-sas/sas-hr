<div class="modal fade" id="modal_rule_value">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <ul class="nav nav-tabs nav-line nav-color-primary w-100 border-bottom-0" role="tablist">
                    <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#rule-value" role="tab" aria-selected="true">Rule Value</a> </li>
                </ul>
            </div>
            <div class="modal-body" id="rule">
                <div class="row card-tab">
                    <div class="col-md-12">
                        <div class="card card-with-nav">
                            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="rule-value" role="tabpanel" aria-labelledby="rule-tab">
                                    <form class="form-horizontal" id="form_rule_value">
                                        <?= csrf_field(); ?>
                                        <h4 class="card-title ml-2">Rule Value</h4>
                                        <div class="form-group row">
                                            <label for="name" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Detail Rule <span class="required-label">*</span></label>
                                            <div class="col-lg-4 col-md-4 col-sm-4">
                                                <input type="text" class="form-control foreignkey" name="md_rule_detail_id" data-url="rule-detail/getDataBy" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="text-right">
                                                        <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto add_row" title="Create Line"><i class="fa fa-plus fa-fw"></i> Tambah Data</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group table-responsive">
                                                    <table class="table-rounded table-head-bg-primary table-hover tb_displaytab" id="table-rule-value" style="width: 100%">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th class="text-center">Nama</th>
                                                                <th class="text-center">Value</th>
                                                                <th class="text-center">Aksi</th>
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
                <button type="button" class="btn btn-outline-danger btn-round close_rule_value" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-round save_form">Save changes</button>
            </div>
        </div>
    </div>
</div>