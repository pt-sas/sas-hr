    <div class="card-body card-form">
        <form class="form-horizontal" id="form_exit_interview">
            <?= csrf_field(); ?>
            <!-- <div class="card"> -->
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Doc Resign <span class="required">*</span></label><br />
                        <select class="form-control select2" id="reference_id" name="reference_id">
                            <option value="">Pilih Doc Resign</option>
                            <?php foreach ($resign_list as $row) : ?>
                            <option value="<?= $row->trx_employee_departure_id ?>"><?= $row->docvalue ?> </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-danger" id="error_reference_id"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="documentno">No Form</label>
                        <input type="text" class="form-control" id="documentno" name="documentno" placeholder="[auto]"
                            readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="submissiondate">Tanggal Interview <span class="required">*</span></label>
                        <input type="text" class="form-control datepicker" id="submissiondate" name="submissiondate"
                            value=<?= $today ?>>
                        <small class="form-text text-danger" id="error_submissiondate"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="approveddate">Tanggal Disetujui</label>
                        <input type="text" class="form-control datepicker" id="approveddate" name="approveddate"
                            readonly>
                        <small class="form-text text-danger" id="error_approveddate"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="md_employee_id">Nama Karyawan <span class="required">*</span></label>
                        <select class="form-control select-data" id="md_employee_id" name="md_employee_id"
                            data-url="employee/getList/$Access" disabled>
                            <option value="">Select Karyawan</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_employee_id"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="nik">NIK <span class="required">*</span></label>
                        <input type="text" class="form-control" id="nik" name="nik" readonly>
                        <small class="form-text text-danger" id="error_nik"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="md_branch_id">Cabang <span class="required">*</span></label>
                        <select class="form-control select2" id="md_branch_id" name="md_branch_id" disabled>
                            <option value="">Select Cabang</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_division_id"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="md_division_id">Divisi <span class="required">*</span></label>
                        <select class="form-control select2" id="md_division_id" name="md_division_id" disabled>
                            <option value="">Select Divisi</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_division_id"></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="description">Alasan Berhenti <span class="required">*</span></label>
                        <textarea type="text" class="form-control" name="description" rows="4"></textarea>
                        <small class="form-text text-danger" id="error_description"></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="md_position_id">Jabatan <span class="required">*</span></label>
                        <select class="form-control select2" id="md_position_id" name="md_position_id" disabled>
                            <option value="">Pilih Jabatan</option>
                        </select>
                        <small class="form-text text-danger" id="error_md_position_id"></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="terminatedate">Tanggal Efektif <span class="required">*</span></label>
                        <div class="input-icon">
                            <input type="text" class="form-control datepicker" name="terminatedate">
                            <span class="input-icon-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                        </div>
                        <small class="form-text text-danger" id="error_terminatedate"></small>
                    </div>
                </div>
            </div>
            <!-- </div> -->
            <?php foreach ($quest_group as $key => $row) : ?>
            <hr>
            <div class="card card-section">
                <div class="card-header">
                    <div class="float-middle">
                        <h4 class="text-center"><?= $row->name; ?></h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group table-responsive">
                            <table class="table table-hover tb_question" id="<?= $row->md_question_group_id ?>"
                                style="width: 100%">
                                <thead>
                                    <tr>
                                        <th style="display: none;">primarykey</th>
                                        <th style="display: none;">group</th>
                                        <th class="text-center">No</th>
                                        <th>Pertanyaan</th>
                                        <th class="text-center">Jawaban</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Question -->
                                    <?php foreach ($question as $key2 => $row2) : ?>
                                    <?php if ($row2->md_question_group_id == $row->md_question_group_id) { ?>
                                    <tr>
                                        <td class="primarykey" style="display: none;" data-type="">
                                            <input type="text" name="trx_interview_detail_id" id="primarykey">
                                        </td>
                                        <td class="question-group-id" data-type="<?= $row2->md_question_group_id ?>"
                                            style="display: none;">
                                        </td>
                                        <td class="text-center no" data-type="<?= $row2->no ?>">
                                            <?= $row2->no; ?>
                                        </td>
                                        <td class="question" data-type="<?= $row2->md_question_id; ?>">
                                            <?= $row2->question; ?>
                                        </td>
                                        <?php if ($row2->answertype == "checkbox") { ?>
                                        <td class="text-center answer" data-type="<?= $row2->answertype ?>">
                                            <div class="form-check"><label class="form-check-label"><input
                                                        type="checkbox" class="form-check-input line active"
                                                        name="answer" value="Y" checked="" id="answer"><span
                                                        class="form-check-sign"></span></label></div>
                                        </td>
                                        <?php } else if ($row2->answertype == "list") { ?>
                                        <td class="answer" data-type="<?= $row2->answertype ?>">
                                            <div class="form-group">
                                                <select class="form-control line select2" name="answer" id="answer">
                                                    <option value="">Pilih Kriteria</option>
                                                </select>
                                            </div>
                                        </td>
                                        <?php } else if ($row2->answertype == "scale") { ?>
                                        <td class="answer" data-type="<?= $row2->answertype ?>">
                                            <div class="selectgroup w-100">
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="answer_<?= $key2 ?>"
                                                        id="answer_<?= $key2 ?>" value="1"
                                                        class="selectgroup-input <?= $key2 ?>" checked="">
                                                    <span class="selectgroup-button">1</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="answer_<?= $key2 ?>"
                                                        id="answer_<?= $key2 ?>" value="2"
                                                        class="selectgroup-input <?= $key2 ?>">
                                                    <span class="selectgroup-button">2</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="answer_<?= $key2 ?>"
                                                        id="answer_<?= $key2 ?>" value="3"
                                                        class="selectgroup-input <?= $key2 ?>">
                                                    <span class="selectgroup-button">3</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="answer_<?= $key2 ?>"
                                                        id="answer_<?= $key2 ?>" value="4"
                                                        class="selectgroup-input <?= $key2 ?>">
                                                    <span class="selectgroup-button">4</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="answer_<?= $key2 ?>"
                                                        id="answer_<?= $key2 ?>" value="5"
                                                        class="selectgroup-input <?= $key2 ?>">
                                                    <span class="selectgroup-button">5</span>
                                                </label>
                                            </div>
                                        </td>
                                        <?php } else { ?>
                                        <td class="answer" data-type="<?= $row2->answertype ?>">
                                            <div class="form-group">
                                                <input type="text" class="form-control line" name="answer" id="answer"
                                                    required>
                                            </div>
                                        </td>
                                        <?php } ?>
                                    </tr>
                                    <?php } ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
    </div>