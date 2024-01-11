<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Ijin Resmi</h4>
            </div>
            <div class="card-body">
                <form class="form-horizontal">
                    <?= csrf_field(); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="value">Nama Karyawan <span class="required">*</span></label>
                                <select class="form-control select2" name="leader_id">
                                    <option value="">Select Nama</option>
                                    <option value="Kristen">Wempy Kurnialim</option>
                                    <option value="Islam">Oki Permana</option>
                                    <option value="Katolik">Wisnu Tri Prakoso</option>
                                    <option value="Buddha">Alvine Aditya</option>
                                    <option value="Hindu">Irvan Januwar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">No Form <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="name">NIK <span class="required">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" readonly>
                                <small class="form-text text-danger" id="error_name"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="nik">Divisi <span class="required">*</span></label>
                                <select class="form-control select2" name="leader_id">
                                    <option value="">Select Nama</option>
                                    <option value="Kristen">IT</option>
                                    <option value="Islam">FINANCE</option>
                                    <option value="Katolik">HRD</option>
                                    <option value="Buddha">GAF</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="value">Tanggal Pengajuan <span class="required">*</span></label>
                                <input type="text" class="form-control datepicker" id="fullname" name="fullname">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="value">Tanggal Diterima HRD <span class="required">*</span></label>
                                <input type="text" class="form-control datepicker" id="fullname" name="fullname" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="leader_id">Jenis Ijin </label>
                                <select class="form-control select2" name="leader_id">
                                    <option value="">Select Golongan Darah</option>
                                    <option value="A">Pekerja Menikah (3 Hari) </option>
                                    <option value="B">Pekerja Menikahkan Anaknya (2 Hari)</option>
                                    <option value="B">Pekerja Mengkhitankan Anaknya (2 Hari)</option>
                                    <option value="B">Pekerja Membaptiskan Anaknya (2 Hari)</option>
                                    <option value="B">Istri Pekerja Melahirkan/Keguguran (2 Hari)</option>
                                    <option value="B">Pekerja Melahirkan (90 Hari)</option>
                                    <option value="B">Pekerja Keguguran (45 Hari)</option>
                                    <option value="B">Suami/Istri, Orang Tua/Mertua, Anak/Menantu Meninggal (3 Hari)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="value">Tanggal Mulai <span class="required">*</span></label>
                                <input type="text" class="form-control datepicker" name="fullname">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="value">Tanggal Selesai <span class="required">*</span></label>
                                <input type="text" class="form-control datepicker" name="fullname">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="address">Alasan </label>
                                <textarea type="text" class="form-control" name="address" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>