<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content'); ?>
<?= $this->include('generate/listabsent/modal_attendance') ?>
<?= $this->include($filter) ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="float-right">
                    <?= $toolbarRealization ?>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-hover table_report">
                    <thead>
                        <tr>
                            <th>
                                <!-- <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input ischeckall-absent" id="ischeckall" name="ischeckall">
                                        <span class="form-check-sign"></span>
                                    </label>
                                </div> -->
                                #
                            </th>
                            <th>NIK</th>
                            <th>Karyawan</th>
                            <th>Tanggal Tidak Absen</th>
                            <!-- <th>Keterangan</th> -->
                            <!-- <th class="text-center">Aksi</th> -->
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>
</div>



<?= $this->endSection() ?>