<div class="card-body card-form">
    <form class="form-horizontal" id="form_wscenario">
        <?= csrf_field(); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="form-text text-danger" id="error_name"></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="lineno">Line No </label>
                    <input type="text" class="form-control number" id="lineno" name="lineno" value="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="menu">Menu <span class="required">*</span></label>
                    <select class="form-control select2" id="menu" name="menu">
                        <option value="">Select Menu</option>
                        <?php foreach ($menu as $row) : ?>
                            <option value="<?= $row['url']; ?>"><?= $row['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_status_id">Status </label>
                    <select class="form-control select2" id="md_status_id" name="md_status_id">
                        <option value="">Select Status</option>
                        <?php foreach ($status as $row) : ?>
                            <option value="<?= $row->getStatusId() ?>"><?= $row->getName() ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_branch_id">Branch </label>
                    <select class="form-control select-data" id="md_branch_id" name="md_branch_id" data-url="branch/getList">
                        <option value="">Select Branch</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="md_division_id">Division </label>
                    <select class="form-control select-data" id="md_division_id" name="md_division_id" data-url="division/getList">
                        <option value="">Select Division</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="grandtotal">Grand Total </label>
                    <input type="text" class="form-control number" id="grandtotal" name="grandtotal" value="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="scenariotype">Scenario Type </label>
                    <select class="form-control select2" id="scenariotype" name="scenariotype">
                        <option value="">Select Type</option>
                        <?php foreach ($ref_list as $row) : ?>
                            <option value="<?= $row->value ?>"><?= $row->name ?></option>
                        <?php endforeach; ?>

                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="description">Description </label>
                    <textarea type="text" class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="col-md-2 mt-4">
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input active" id="isactive" name="isactive">
                        <span class="form-check-sign">Active</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="text-right">
                        <button type="button" name="button" class="btn btn-primary btn-sm btn-round ml-auto add_row" title="Add New"><i class="fa fa-plus fa-fw"></i> Add New</button>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group table-responsive">
                    <table class="table table-light table-hover tb_displayline" id="table_scenario" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Line No</th>
                                <th>Grand Total</th>
                                <th>Workflow Responsible</th>
                                <th>Notification Template</th>
                                <th>Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>