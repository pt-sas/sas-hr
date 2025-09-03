<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_movement">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <select class="form-control select2" name="md_status_id" style="width: 100%;">
                                    <option value="">Select Status</option>
                                    <?php foreach ($status as $row) : ?>
                                        <option value="<?= $row->md_status_id ?>" <?= ($row->md_status_id == 100001) ? "selected" : "" ?>><?= $row->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-sm btn-round ml-auto btn_filter" title="Filter">
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