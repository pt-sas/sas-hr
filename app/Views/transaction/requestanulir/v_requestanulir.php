<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form class="form-horizontal" id="form_requestanulir">
                    <!-- <div class="form-group row">
                        <label for="submissiontype" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Tipe Form <span class="required">*</span></label>
                        <div class="col-lg-6 col-md-9 col-sm-8">
                            <select class="form-control select-submissiontype" id="submissiontype" name="submissiontype"></select>
                            <small class="form-text text-danger" id="error_submissiontype"></small>
                        </div>
                    </div> -->
                    <div class="form-group row">
                        <label for="documentno" class="col-lg-3 col-md-3 col-sm-4 mt-sm-2 text-right">Doc No <span class="required">*</span></label>
                        <div class="col-lg-6 col-md-9 col-sm-8">
                            <input type="text" class="form-control" name="documentno">
                            <small class="form-text text-danger" id="error_documentno"></small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-action d-flex justify-content-center">
                <div>
                    <button type="button" class="btn btn-danger btn-sm btn-round ml-auto btn_reset_form"><i class="fas fa-undo-alt fa-fw"></i> Reset</button>
                    <button type="button" class="btn btn-success btn-sm btn-round ml-auto btn_ok_anulir"><i class="fas fa-check fa-fw"></i> OK</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>