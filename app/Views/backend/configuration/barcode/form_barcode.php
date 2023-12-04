<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<div class="card-body card-main">
    <form class="form-horizontal" id="form_barcode">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="barcodetype">Barcode Type <span class="required">*</span></label>
                    <select class="form-control select2" id="barcodetype" name="barcodetype">
                        <option value="">Select Barcode Type</option>
                        <?php foreach ($ref_list_barcode as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_barcodetype"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="generatortype">Generator Type <span class="required">*</span></label>
                    <select class="form-control select2" id="generatortype" name="generatortype">
                        <option value="">Select Generator Type</option>
                        <?php foreach ($ref_list_generator as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_generatortype"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="widthfactor">Width Factor <span class="required">*</span></label>
                    <input type="text" class="form-control number" id="widthfactor" name="widthfactor">
                    <small class="form-text text-danger" id="error_widthfactor"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="height">Height <span class="required">*</span></label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control number" id="height" name="height">
                        <div class="input-group-append">
                            <span class="input-group-text" id="basic-addon2">Pixels</span>
                        </div>
                        <span class="w-100"></span>
                        <small class="form-text text-danger" id="error_sizetext"></small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive" checked>
                        <span class="form-check-sign">Active</span>
                    </label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" id="iswithtext" name="iswithtext" show-field="sizetext, positiontext" checked>
                        <span class="form-check-sign">With Text</span>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="text">Text <span class="required">*</span></label>
                    <input type="text" class="form-control" id="text" name="text">
                    <small class="form-text text-danger" id="error_text"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="positiontext">Position <span class="required">*</span></label>
                    <select class="form-control select2" id="positiontext" name="positiontext">
                        <option value="">Select Position</option>
                        <?php foreach ($ref_list_position as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_positiontext"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sizetext">Font Size <span class="required">*</span></label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control number" id="sizetext" name="sizetext">
                        <div class="input-group-append">
                            <span class="input-group-text" id="basic-addon2">Pixels</span>
                        </div>
                        <span class="w-100"></span>
                        <small class="form-text text-danger" id="error_sizetext"></small>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="card-barcode d-none" show-after-save="true">
        <div class="separator-solid"></div>
        <div class="d-flex justify-content-center">
            <div class="form-group barcode"></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>