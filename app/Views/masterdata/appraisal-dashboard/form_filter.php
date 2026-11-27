<?php $defaultTargetGroup = strtoupper((string) ($default_target_group ?? 'PERMANENT')) === 'OPERATOR' ? 'OPERATOR' : 'PERMANENT'; ?>
<div class="row filter_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body card-filter">
                <form class="form-horizontal" id="filter_appraisal_dashboard">
                    <div class="row align-items-end">
                        <!-- Siklus Penilaian -->
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_cycle" name="cycle_id" style="width: 100%;" data-placeholder="Pilih opsi siklus">
                                        <option value="0">Semua Siklus (Semua Periode)</option>
                                        <?php if (!empty($cycles)) : ?>
                                            <?php foreach ($cycles as $c) : ?>
                                                <?php
                                                    $cId      = (int) $c['md_appraisal_cycle_id'];
                                                    $status   = $c['status'] ?? 'OPEN';
                                                    $semester = !empty($c['semester']) ? ' Sem ' . $c['semester'] : '';
                                                    $selected = ($cId === (int) ($default_cycle_id ?? 0)) ? 'selected' : '';
                                                ?>
                                                <option value="<?= $cId ?>" <?= $selected ?>>
                                                    <?= $status ?> - <?= esc($c['title']) ?> - <?= $semester ?>
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
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_division" name="division_id" style="width: 100%;" data-placeholder="Pilih opsi divisi">
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
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_status" name="status" style="width: 100%;" data-placeholder="Pilih opsi status">
                                        <option value="ALL">Semua Status</option>
                                        <?php foreach (($status_labels ?? \App\Models\M_Appraisal::getStatusLabels()) as $statusCode => $statusName) : ?>
                                            <option value="<?= esc($statusCode) ?>"><?= esc($statusName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tipe Form (Global Permanent / Operator Filter) -->
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <div class="select2-input select2-primary">
                                    <select class="form-control select2" id="filter_target_group" name="target_group" style="width: 100%;" data-placeholder="Pilih opsi tipe form">
                                        <option value="PERMANENT" <?= $defaultTargetGroup === 'PERMANENT' ? 'selected' : '' ?>>Karyawan Tetap</option>
                                        <option value="OPERATOR" <?= $defaultTargetGroup === 'OPERATOR' ? 'selected' : '' ?>>Operator</option>
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
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
