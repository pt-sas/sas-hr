<div class="modal fade modal-tab" id="modal_employee">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <ul class="nav nav-tabs nav-line nav-color-primary w-100 border-bottom-0" role="tablist">
                    <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#karyawan" role="tab" aria-selected="true">Data Diri</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#user" role="tab" aria-selected="true" disabled>Pengguna</a> </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Keluarga</a>
                        <div class="dropdown-menu animated fadeIn">
                            <a class="dropdown-item" data-toggle="tab" href="#keluarga-inti" role="tab" aria-selected="false">Data Keluarga</a>
                            <a class="dropdown-item d-none" data-toggle="tab" href="#keluarga" role="tab" aria-selected="false">Data Keluarga Setelah Menikah</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Riwayat</a>
                        <div class="dropdown-menu animated fadeIn">
                            <a class="dropdown-item" data-toggle="tab" href="#riwayat-pendidikan" role="tab" aria-selected="false">Riwayat Pendidikan</a>
                            <a class="dropdown-item" data-toggle="tab" href="#riwayat-pekerjaan" role="tab" aria-selected="false">Riwayat Pekerjaan</a>
                            <a class="dropdown-item" data-toggle="tab" href="#riwayat-vaksin" role="tab" aria-selected="false">Riwayat Vaksin</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">Keterampilan</a>
                        <div class="dropdown-menu animated fadeIn">
                            <a class="dropdown-item" data-toggle="tab" href="#keterampilan" role="tab" aria-selected="false">Keterampilan</a>
                            <a class="dropdown-item" data-toggle="tab" href="#kursus" role="tab" aria-selected="false">Kursus</a>
                            <a class="dropdown-item" data-toggle="tab" href="#penguasaan-bahasa" role="tab" aria-selected="false">Penguasaan Bahasa</a>
                        </div>
                    </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#kontak-darurat" role="tab" aria-selected="false">Kontak Darurat</a> </li>
                    <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#sim" role="tab" aria-selected="false">SIM</a> </li>
                </ul>
            </div>
            <div class="modal-body" id="employee">
                <div class="row card-tab">
                    <div class="col-md-12">
                        <div class="card card-with-nav">
                            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="karyawan" role="tabpanel" aria-labelledby="data-diri-tab">
                                    <?= $this->include('masterdata/employee/form_employee'); ?>
                                </div>
                                <div class="tab-pane fade" id="user" role="tabpanel" aria-labelledby="user-tab">
                                    <?= $this->include('masterdata/employee/form_emp_user'); ?>
                                </div>
                                <div class="tab-pane fade" id="kontak-darurat" role="tabpanel" aria-labelledby="kontak-darurat-tab">
                                    <?= $this->include('masterdata/employee/form_emp_contact'); ?>
                                </div>
                                <div class="tab-pane fade" id="keluarga-inti" role="tabpanel" aria-labelledby="keluarga-inti-tab">
                                    <?= $this->include('masterdata/employee/form_emp_family_core'); ?>
                                </div>
                                <div class="tab-pane fade" id="keluarga" role="tabpanel" aria-labelledby="keluarga-tab">
                                    <?= $this->include('masterdata/employee/form_emp_family'); ?>
                                </div>
                                <div class="tab-pane fade" id="riwayat-pendidikan" role="tabpanel" aria-labelledby="riwayat-pendidikan-tab">
                                    <?= $this->include('masterdata/employee/form_emp_education'); ?>
                                </div>
                                <div class="tab-pane fade" id="riwayat-pekerjaan" role="tabpanel" aria-labelledby="riwayat-pekerjaan-tab">
                                    <?= $this->include('masterdata/employee/form_emp_job'); ?>
                                </div>
                                <div class="tab-pane fade" id="kursus" role="tabpanel" aria-labelledby="kursus-tab">
                                    <?= $this->include('masterdata/employee/form_emp_course'); ?>
                                </div>
                                <div class="tab-pane fade" id="penguasaan-bahasa" role="tabpanel" aria-labelledby="penguasaan-bahasa-tab">
                                    <?= $this->include('masterdata/employee/form_emp_language'); ?>
                                </div>
                                <div class="tab-pane fade" id="keterampilan" role="tabpanel" aria-labelledby="keterampilan-tab">
                                    <?= $this->include('masterdata/employee/form_emp_skill'); ?>
                                </div>
                                <div class="tab-pane fade" id="sim" role="tabpanel" aria-labelledby="sim-tab">
                                    <?= $this->include('masterdata/employee/form_emp_license'); ?>
                                </div>
                                <div class="tab-pane fade" id="riwayat-vaksin" role="tabpanel" aria-labelledby="riwayat-vaksin-tab">
                                    <?= $this->include('masterdata/employee/form_emp_vaccine'); ?>
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