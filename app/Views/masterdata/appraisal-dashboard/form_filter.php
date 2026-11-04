<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_appraisal_dashboard">
                    <div class="row align-items-end">
                        <!-- Siklus Penilaian -->
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label for="filter_cycle" class="font-weight-bold text-muted small mb-1">Siklus Penilaian</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_cycle" name="cycle_id" style="width: 100%;">
                                        <option value="0">Semua Siklus (Semua Periode)</option>
                                        <?php if (!empty($cycles)) : ?>
                                            <?php foreach ($cycles as $c) : ?>
                                                <?php
                                                    $cId      = (int) $c['md_appraisal_cycle_id'];
                                                    $status   = $c['status'] ?? 'OPEN';
                                                    $tag      = ($status === 'OPEN') ? '[OPEN]' : '[' . $status . ']';
                                                    $dates    = (!empty($c['start_date']) && !empty($c['end_date']))
                                                                ? ' (' . date('d/m/Y', strtotime($c['start_date'])) . ' - ' . date('d/m/Y', strtotime($c['end_date'])) . ')'
                                                                : '';
                                                    $selected = ($cId === (int) ($default_cycle_id ?? 0)) ? 'selected' : '';
                                                ?>
                                                <option value="<?= $cId ?>" <?= $selected ?>>
                                                    <?= $tag ?> <?= esc($c['title']) ?><?= $dates ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Divisi -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label for="filter_division" class="font-weight-bold text-muted small mb-1">Divisi</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_division" name="division_id" style="width: 100%;">
                                        <option value="0">Semua Divisi</option>
                                        <?php if (!empty($divisions)) : ?>
                                            <?php foreach ($divisions as $d) : ?>
                                                <option value="<?= (int) $d['md_division_id'] ?>"><?= esc($d['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Status Form Penilaian -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label for="filter_status" class="font-weight-bold text-muted small mb-1">Status Form</label>
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_status" name="status" style="width: 100%;">
                                        <option value="ALL">Semua Status</option>
                                        <option value="PENILAIAN">Penilaian</option>
                                        <option value="SUBMIT_PENILAIAN">Submit Penilaian</option>
                                        <option value="REVIEW">Review</option>
                                        <option value="SUBMIT_REVIEW">Submit Review</option>
                                        <option value="FINAL">Final</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-2">
                            <div class="form-group mb-0 d-flex align-items-center">
                                <button type="button" class="btn btn-primary btn-sm btn-round btn_filter" title="Terapkan Filter">
                                    <i class="fas fa-search fa-fw"></i> Filter
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-round ml-2 btn_reset" id="btn_reset_filter" title="Reset Semua Filter">
                                    <i class="fas fa-undo fa-fw"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
