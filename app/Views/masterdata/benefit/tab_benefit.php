<div class="modal fade modal-tab" id="modal_benefit">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <ul class="nav nav-tabs nav-line nav-color-primary w-100 border-bottom-0" role="tablist">
                    <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#benefit-inti" role="tab" aria-selected="true">Benefit</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#benefit-line" role="tab" aria-selected="false">Detail Benefit</a> </li>
                </ul>
            </div>
            <div class="modal-body" id="benefit">
                <div class="row card-tab">
                    <div class="col-md-12">
                        <div class="card card-with-nav">
                            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="benefit-inti" role="tabpanel" aria-labelledby="benefit-tab">
                                    <?= $this->include('masterdata/benefit/form_benefit'); ?>
                                </div>
                                <div class="tab-pane fade" id="benefit-line" role="tabpanel" aria-labelledby="benefit-line-tab">
                                    <?= $this->include('masterdata/benefit/form_benefit_detail'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-round close_form" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-round save_form">Save changes</button>
            </div>
        </div>
    </div>
</div>