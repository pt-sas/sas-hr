<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_realization">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="input-icon">
                                    <input type="text" class="form-control daterange" name="date"
                                        value="<?= $date_range ?>" placeholder="Tanggal mulai dan selesai">
                                    <span class="input-icon-addon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <button type="button"
                                    class="btn btn-primary btn-sm btn-round ml-auto btn_filter_realize" title="Filter">
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