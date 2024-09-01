<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <div class="float-right">
                    <span id="timestamp"></span>
                </div>
            </div>
            <div class="card-body" id="scan_preview">
                <video id="preview" style="width:100%;"></video>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <table class="table table-head-bg-primary table-bordered table-hover table_report">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NIK</th>
                            <th>Karyawan</th>
                            <th>Tanggal</th>
                            <th>Jam Absen</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>
<?= $this->endSection() ?>