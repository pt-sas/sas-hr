<form class="form-horizontal" id="form_outsourcing">
    <?= csrf_field(); ?>
    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="value">Value <span class="required">*</span></label>
                        <input type="text" class="form-control" id="value" name="value" <?= $readonly ?>>
                        <small class="form-text text-danger" id="error_value"></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nik">NIK <span class="required">*</span></label>
                        <input type="text" class="form-control number" id="nik" name="nik" edit-readonly="nik"
                            <?= $readonly ?>>
                        <small class="form-text text-danger" id="error_nik"></small>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="fullname">Nama Lengkap <span class="required">*</span></label>
                <input type="text" class="form-control" id="fullname" name="fullname" <?= $readonly ?>>
                <small class="form-text text-danger" id="error_fullname"></small>
            </div>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="nickname">Nama Panggilan </label>
                <input type="text" class="form-control" id="nickname" name="nickname" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check mt-4">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input active" name="isactive" <?= $disabled ?>>
                    <span class="form-check-sign">Aktif</span>
                </label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="pob">Tempat Lahir <span class="required">*</span></label>
                <input type="text" class="form-control" id="pob" name="pob" <?= $readonly ?>>
                <small class="form-text text-danger" id="error_pob"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="birthday">Tanggal Lahir <span class="required">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control datepicker" id="birthday" name="birthday" <?= $readonly ?>>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
                <small class="form-text text-danger" id="error_birthday"></small>
            </div>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-3">
            <div class="form-check">
                <label>Jenis Kelamin <span class="required">*</span></label><br />
                <?php foreach ($ref_list as $row) : ?>
                    <label class="form-radio-label">
                        <input class="form-radio-input" type="radio" name="gender" value="<?= $row->value ?>"
                            <?= $disabled ?>>
                        <span class="form-radio-sign"><?= $row->name ?></span>
                    </label>
                <?php endforeach; ?>
                <small class="form-text text-danger" id="error_gender"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="nationality">Kewarganegaraan <span class="required">*</span></label>
                <select class="form-control select-data" id="nationality" name="nationality"
                    data-url="reference/getList/$Nationality" <?= $disabled ?>>
                    <option value="">Select Kewarganegaraan</option>
                </select>
                <small class="form-text text-danger" id="error_nationality"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="phone">No HP Pribadi <span class="required">*</span></label>
                <input type="text" class="form-control" id="phone" name="phone" <?= $readonly ?>>
                <small class="form-text text-danger" id="error_phone"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="phone2">No HP Pribadi 2 </label>
                <input type="text" class="form-control" id="phone2" name="phone2" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_branch_id">Cabang <span class="required">*</span></label>
                <div class="select2-input select2-primary">
                    <select class="form-control multiple-select" id="md_branch_id" name="md_branch_id"
                        multiple="multiple" style="width: 100%;" <?= $disabled ?>>
                        <?php foreach ($branch as $row) : ?>
                            <option value="<?= $row->getBranchId(); ?>"><?= $row->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_md_branch_id"></small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_division_id">Divisi <span class="required">*</span></label>
                <div class="select2-input select2-primary">
                    <select class="form-control multiple-select" id="md_division_id" name="md_division_id"
                        multiple="multiple" style="width: 100%;" <?= $disabled ?>>
                        <?php foreach ($division as $row) : ?>
                            <option value="<?= $row->getDivisionId(); ?>"><?= $row->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-danger" id="error_md_division_id"></small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="superior_id">Superior</label>
                <select class="form-control select-data" id="superior_id" name="superior_id"
                    data-url="karyawan/superior" <?= $disabled ?>>
                    <option value="">Select Superior</option>
                </select>
                <small class="form-text text-danger" id="error_superior_id"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="md_position_id">Jabatan <span class="required">*</span></label>
                <select class="form-control select-data" id="md_position_id" name="md_position_id"
                    data-url="position/getList" <?= $disabled ?>>
                    <option value="">Select Jabatan</option>
                </select>
                <small class="form-text text-danger" id="error_md_position_id"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="md_levelling_id">Level <span class="required">*</span></label>
                <select class="form-control select-data" id="md_levelling_id" name="md_levelling_id"
                    data-url="levelling/getList" <?= $disabled ?>>
                    <option value="">Select Level</option>
                </select>
                <small class="form-text text-danger" id="error_md_levelling_id"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="registerdate">Tanggal Bergabung </label>
                <div class="input-group">
                    <input type="text" class="form-control datepicker" id="registerdate" name="registerdate"
                        <?= $disabled ?>>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="md_status_id">Status Karyawan <span class="required">*</span></label>
                <select class="form-control select-data" id="md_status_id" name="md_status_id" hide-field="resigndate"
                    data-url="status/getList/$OUTSOURCING" <?= $disabled ?>>
                    <option value="">Select Status</option>
                </select>
                <small class="form-text text-danger" id="error_md_status_id"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="resigndate">Tanggal Berhenti <span class="required">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control datepicker" id="resigndate" name="resigndate"
                        <?= $readonly ?>>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
                <small class="form-text text-danger" id="error_resigndate"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_supplier_id">Vendor</label>
                <div class="select2-input select2-primary">
                    <select class="form-control select-data" id="md_supplier_id" name="md_supplier_id"
                        data-url="supplier/getList" <?= $disabled ?>>
                    </select>
                    <small class="form-text text-danger" id="error_md_supplier_id"></small>
                </div>
            </div>
        </div>
    </div>
    <div class="separator-solid"></div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="address_dom">Alamat Domisili <span class="required">*</span></label>
                <textarea type="text" class="form-control" id="address_dom" name="address_dom" rows="2"
                    <?= $readonly ?>></textarea>
                <small class="form-text text-danger" id="error_address_dom"></small>
            </div>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_country_id">Negara <span class="required">*</span></label>
                <select class="form-control select-data" id="md_country_dom_id" name="md_country_dom_id"
                    data-url="country/getList" <?= $disabled ?>>
                    <option value="">Select Negara</option>
                </select>
                <small class="form-text text-danger" id="error_md_country_dom_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_province_id">Provinsi <span class="required">*</span></label>
                <select class="form-control select2" id="md_province_dom_id" name="md_province_dom_id" <?= $disabled ?>>
                    <option value="">Select Provinsi</option>
                </select>
                <small class="form-text text-danger" id="error_md_province_dom_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_city_dom_id">Kota <span class="required">*</span></label>
                <select class="form-control select2" id="md_city_dom_id" name="md_city_dom_id" <?= $disabled ?>>
                    <option value="">Select Kota</option>
                </select>
                <small class="form-text text-danger" id="error_md_city_dom_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_district_dom_id">Kecamatan <span class="required">*</span></label>
                <select class="form-control select2" id="md_district_dom_id" name="md_district_dom_id" <?= $disabled ?>>
                    <option value="">Select Kecamatan</option>
                </select>
                <small class="form-text text-danger" id="error_md_district_dom_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_subdistrict_dom_id">Kelurahan <span class="required">*</span></label>
                <select class="form-control select2" id="md_subdistrict_dom_id" name="md_subdistrict_dom_id"
                    <?= $disabled ?>>
                    <option value="">Select Kelurahan</option>
                </select>
                <small class="form-text text-danger" id="error_md_subdistrict_dom_id"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="postalcode_dom">Kode Pos <span class="required">*</span></label>
                <input type="text" class="form-control" id="postalcode_dom" name="postalcode_dom" <?= $readonly ?>>
                <small class="form-text text-danger" id="error_postalcode_dom"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <label class="form-check-label">
                    <input type="checkbox" class="form-check-input" id="issameaddress" name="issameaddress" checked
                        hide-field="address,md_country_id,md_province_id,md_city_id,md_district_id,md_subdistrict_id,postalcode"
                        <?= $disabled ?>>
                    <span class=" form-check-sign">Sama dengan alamat domisili</span>
                </label>
            </div>
            <div class="form-group">
                <label for="address">Alamat KTP <span class="required">*</span></label>
                <textarea type="text" class="form-control" id="address" name="address" rows="2"
                    <?= $readonly ?>></textarea>
                <small class="form-text text-danger" id="error_address"></small>
            </div>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_country_id">Negara <span class="required">*</span></label>
                <select class="form-control select-data" id="md_country_id" name="md_country_id"
                    data-url="country/getList" <?= $disabled ?>>
                    <option value="">Select Negara</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_province_id">Provinsi <span class="required">*</span></label>
                <select class="form-control select2" id="md_province_id" name="md_province_id" <?= $disabled ?>>
                    <option value="">Select Provinsi</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_city_id">Kota <span class="required">*</span></label>
                <select class="form-control select2" id="md_city_id" name="md_city_id" <?= $disabled ?>>
                    <option value="">Select Kota</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_district_id">Kecamatan <span class="required">*</span></label>
                <select class="form-control select2" id="md_district_id" name="md_district_id" <?= $disabled ?>>
                    <option value="">Select Kecamatan</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="md_subdistrict_id">Kelurahan <span class="required">*</span></label>
                <select class="form-control select2" id="md_subdistrict_id" name="md_subdistrict_id" <?= $disabled ?>>
                    <option value="">Select Kelurahan</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="postalcode">Kode Pos <span class="required">*</span></label>
                <input type="text" class="form-control" id="postalcode" name="postalcode" <?= $readonly ?>>
            </div>
        </div>
    </div>
    <div class="separator-solid"></div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="card_id">No KTP <span class="required">*</span></label>
                <input type="text" class="form-control number" id="card_id" name="card_id" <?= $readonly ?>>
                <small class="form-text text-danger" id="error_card_id"></small>
            </div>
        </div>
    </div>
</form>