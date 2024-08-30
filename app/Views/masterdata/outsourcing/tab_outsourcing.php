<div class="modal fade modal-tab" id="modal_outsourcing">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <ul class="nav nav-tabs nav-line nav-color-primary w-100 border-bottom-0" role="tablist">
                    <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#outsource" role="tab"
                            aria-selected="true">Data Diri</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#kontak-darurat" role="tab"
                            aria-selected="false">Kontak Darurat</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#sim" role="tab"
                            aria-selected="false">SIM</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#hari-kerja-karyawan" role="tab"
                            aria-selected="false">Hari Kerja</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#benefit" role="tab"
                            aria-selected="false">Benefit</a> </li>
                </ul>
            </div>
            <div class="modal-body" id="employee">
                <div class="row card-tab">
                    <div class="col-md-12">
                        <div class="card card-with-nav">
                            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="outsource" role="tabpanel"
                                    aria-labelledby="data-diri-tab">
                                    <?= $this->include('masterdata/outsourcing/form_outsourcing'); ?>
                                </div>
                                <div class="tab-pane fade" id="kontak-darurat" role="tabpanel"
                                    aria-labelledby="kontak-darurat-tab">
                                    <?= $this->include('masterdata/outsourcing/form_emp_contact'); ?>
                                </div>
                                <div class="tab-pane fade" id="sim" role="tabpanel" aria-labelledby="sim-tab">
                                    <?= $this->include('masterdata/outsourcing/form_emp_license'); ?>
                                </div>
                                <div class="tab-pane fade" id="hari-kerja-karyawan" role="tabpanel"
                                    aria-labelledby="hari-kerja-karyawan-tab">
                                    <?= $this->include('masterdata/outsourcing/form_emp_workday'); ?>
                                </div>
                                <div class="tab-pane fade" id="benefit" role="tabpanel" aria-labelledby="benefit-tab">
                                    <?= $this->include('masterdata/employee/form_emp_benefit'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger btn-round close_form"
                    data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-round save_form">Save changes</button>
            </div>
        </div>
    </div>
</div>