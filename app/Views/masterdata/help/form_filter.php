<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_help">
                    <div class="row">
                        <!-- Category Filter -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_sys_ref_detail_id">Kategori Artikel</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_sys_ref_detail_id" name="sys_ref_detail_id">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($categories as $row) : ?>
                                            <?php 
                                                $catId = is_array($row) 
                                                    ? ($row['sys_ref_detail_id'] ?? $row['id'] ?? $row['md_doctype_id']) 
                                                    : ($row->sys_ref_detail_id ?? $row->id ?? $row->md_doctype_id);
                                                $catName = is_array($row) 
                                                    ? ($row['name'] ?? $row['text']) 
                                                    : ($row->name ?? $row->text);
                                            ?>
                                            <option value="<?= $catId ?>"><?= esc($catName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Active State Filter -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_isactive">Status Aktif</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_isactive" name="isactive">
                                        <option value="">Semua Status</option>
                                        <option value="Y">Aktif</option>
                                        <option value="N">Tidak Aktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-sm btn-round btn_filter" title="Filter">
                                    <i class="fas fa-search fa-fw"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>