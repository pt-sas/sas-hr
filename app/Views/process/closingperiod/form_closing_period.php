<div class="card-body card-form">
    <form class="form-horizontal still-open" id="form_closing_period">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="year">Tahun <span class="required">*</span></label>
                    <div class="input-icon">
                        <input type="text" class="form-control yearpicker" id="year" name="year">
                        <span class="input-icon-addon">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                    <small class="form-text text-danger" id="error_year"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea type="text" class="form-control" id="description" name="description" rows="2"></textarea>
                    <small class="form-text text-danger" id="error_description"></small>
                </div>
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="text-right">
                        <button type="button" class="btn btn-secondary generate-period">Buat Periode</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Nama Periode</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Kontrol Periode</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>