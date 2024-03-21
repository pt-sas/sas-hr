<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<form id="import_attendance" enctype="multipart/form-data">
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label>File Excel</label>
                <input type="file" name="file" class="form-control" id="file" required accept=".xls, .xlsx" /></p>
                <small class="form-text text-danger" id="error_file"></small>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary ml-auto import_file">Upload</button>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection() ?>