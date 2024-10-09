<form class="form-horizontal" id="form_employee">
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
        <div class="col-md-6">
            <div class="form-group">
                <label></label>
                <div class="form-upload-result">
                    <label class="col-md-6 form-result" id="product-result">
                        <button type="button" class="close-img" aria-label="Close" <?= $disabled ?>>
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <img class="img-result">
                    </label>
                </div>
                <div class="form-upload">
                    <label class="col-md-6 form-upload-foto" id="product-upload">
                        <input type="file" class="control-upload-image" id="image" name="image"
                            onchange="previewImage(this)" accept="image/jpeg, image/png" <?= $disabled ?>></input>
                        <img class="img-upload" src="<?= base_url('custom/image/cameraroll.png') ?>" />
                    </label>
                    <small class="form-text text-danger" id="error_image"></small>
                </div>
            </div>
        </div>
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
        <div class="col-md-3">
            <div class="form-group">
                <label for="md_bloodtype_id">Golongan Darah </label>
                <select class="form-control select-data" id="md_bloodtype_id" name="md_bloodtype_id"
                    data-url="bloodtype/getList" <?= $disabled ?>>
                    <option value="">Select Golongan Darah</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="rhesus">Rhesus </label>
                <select class="form-control select-data" id="rhesus" name="rhesus" data-url="reference/getList/$Rhesus"
                    <?= $disabled ?>>
                    <option value="">Select Rhesus</option>
                </select>
            </div>
        </div>
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
                <label for="md_religion_id">Agama <span class="required">*</span></label>
                <select class="form-control select-data" id="md_religion_id" name="md_religion_id"
                    data-url="religion/getList" <?= $disabled ?>>
                    <option value="">Select Agama</option>
                </select>
                <small class="form-text text-danger" id="error_md_religion_id"></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="marital_status">Status Menikah <span class="required">*</span></label>
                <select class="form-control select-data" id="marital_status" name="marital_status"
                    data-url="reference/getList/$MaritalStatus" <?= $disabled ?>>
                    <option value="">Select Status Menikah</option>
                </select>
                <small class="form-text text-danger" id="error_marital_status"></small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="email">Email Pribadi </label>
                <input type="text" class="form-control" name="email" <?= $readonly ?>>
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
                <label for="homestatus">Status Rumah <span class="required">*</span></label>
                <select class="form-control select-data" id="homestatus" name="homestatus"
                    data-url="reference/getList/$HomeStatus" <?= $disabled ?>>
                    <option value="">Select Status Rumah</option>
                </select>
                <small class="form-text text-danger" id="error_homestatus"></small>
            </div>
        </div>
        <div class="col-md-6"></div>
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
        <div class="col-md-6">
            <div class="form-group">
                <label for="officephone">No HP Kantor </label>
                <input type="text" class="form-control" id="officephone" name="officephone" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="registerdate">Tanggal Bergabung </label>
                <div class="input-group">
                    <input type="text" class="form-control datepicker" id="registerdate" name="registerdate"
                        <?= $readonly ?>>
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
                <select class="form-control select-data" id="md_status_id" name="md_status_id"
                    data-url="status/getList/$EMPLOYEE" <?= $disabled ?>>
                    <option value="">Select Status</option>
                </select>
                <small class="form-text text-danger" id="error_md_status_id"></small>
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
                        <?= $readonly ?>>
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
                <input type="text" class="form-control number" id="card_id" name="card_id" <?= $disabled ?>>
                <small class="form-text text-danger" id="error_card_id"></small>
            </div>
        </div>
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="npwp_id">No NPWP </label>
                <input type="text" class="form-control npwp" id="npwp_id" name="npwp_id" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="ptkp_status">Status PTKP </label>
                <select class="form-control select2" id="ptkp_status" name="ptkp_status" <?= $disabled ?>>
                    <?php foreach ($ptkp_list as $row) : ?>
                    <option value="<?= $row->value ?>"><?= $row->name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="bpjs_kes_no">BPJS Kesehatan </label>
                <input type="text" class="form-control number" id="bpjs_kes_no" name="bpjs_kes_no" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="bpjs_kes_periode">Periode </label>
                <div class="input-group">
                    <input type="text" class="form-control datepicker" id="bpjs_kes_period" name="bpjs_kes_period"
                        <?= $readonly ?>>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="bpjs_tenaga_no">BPJS Tenaga Kerja </label>
                <input type="text" class="form-control number" id="bpjs_tenaga_no" name="bpjs_tenaga_no"
                    <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="bpjs_tenaga_periode">Periode </label>
                <div class="input-group">
                    <input type="text" class="form-control datepicker" id="bpjs_tenaga_period" name="bpjs_tenaga_period"
                        <?= $readonly ?>>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="bank">Nama Bank </label>
                <input type="text" class="form-control" id="bank" name="bank" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="bank_branch">Cabang Bank </label>
                <input type="text" class="form-control" id="bank_branch" name="bank_branch" <?= $readonly ?>>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="bank_account">No Rekening </label>
                <input type="text" class="form-control number" id="bank_account" name="bank_account" <?= $readonly ?>>
            </div>
        </div>
    </div>
</form>