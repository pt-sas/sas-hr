<?php
    $summary                 = $summary ?? [];
    $isOperator              = ! empty($is_operator);
    $itemsKompetensiTambahan = $summary['itemsKompetensiTambahan'] ?? [];
    $itemsNilai1             = $summary['itemsNilai1'] ?? [];
?>
<?php if ($isOperator): ?>
    <?php $itemsNilai3 = $summary['itemsNilai3'] ?? []; ?>
    <div class="ap-card">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <h5 class="ap-card-title mb-0">IV. Rekap Penilaian</h5>
        </div>
        <div class="table-responsive ap-table-wrapper">
            <table class="ap-table mb-0">
                <tbody>
                    <tr>
                        <td style="width: 25%; font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">Sangat Baik</td>
                        <td style="width: 75%; vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                            <?php if (! empty($itemsNilai3)): ?>
                                <ol type="a" class="mb-0 pl-3">
                                    <?php foreach ($itemsNilai3 as $n3Text): ?>
                                        <li class="mb-1 text-dark"><?= esc($n3Text) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada indikator dengan nilai 3</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">Kurang</td>
                        <td style="vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                            <?php if (! empty($itemsNilai1)): ?>
                                <ol type="a" class="mb-0 pl-3">
                                    <?php foreach ($itemsNilai1 as $n1Text): ?>
                                        <li class="mb-1 text-dark"><?= esc($n1Text) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada indikator dengan nilai 1</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top;">KOMPETENSI TAMBAHAN</td>
                        <td style="vertical-align: top; line-height: 1.6;">
                            <?php if (! empty($itemsKompetensiTambahan)): ?>
                                <ol type="a" class="mb-0 pl-3">
                                    <?php foreach ($itemsKompetensiTambahan as $ktText): ?>
                                        <li class="mb-1 text-dark"><?= esc($ktText) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada kompetensi tambahan yang terpilih</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php $itemsNilai4 = $summary['itemsNilai4'] ?? []; ?>
    <div class="ap-card">
        <div class="ap-card-head d-flex justify-content-between align-items-center">
            <h5 class="ap-card-title mb-0">IV. Rekap Penilaian</h5>
        </div>
        <div class="table-responsive ap-table-wrapper">
            <table class="ap-table mb-0">
                <tbody>
                    <tr>
                        <td style="width: 25%; font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">NILAI 4</td>
                        <td style="width: 75%; vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                            <?php if (! empty($itemsNilai4)): ?>
                                <ul class="mb-0 pl-3">
                                    <?php foreach ($itemsNilai4 as $n4Text): ?>
                                        <li class="mb-1 text-dark"><?= esc($n4Text) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada indikator dengan nilai 4</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top; border-bottom: 1px solid var(--ap-line);">NILAI 1</td>
                        <td style="vertical-align: top; border-bottom: 1px solid var(--ap-line); line-height: 1.6;">
                            <?php if (! empty($itemsNilai1)): ?>
                                <ul class="mb-0 pl-3">
                                    <?php foreach ($itemsNilai1 as $n1Text): ?>
                                        <li class="mb-1 text-dark"><?= esc($n1Text) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada indikator dengan nilai 1</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; background: var(--ap-surface); vertical-align: top;">KOMPETENSI TAMBAHAN</td>
                        <td style="vertical-align: top; line-height: 1.6;">
                            <?php if (! empty($itemsKompetensiTambahan)): ?>
                                <ul class="mb-0 pl-3">
                                    <?php foreach ($itemsKompetensiTambahan as $ktText): ?>
                                        <li class="mb-1 text-dark"><?= esc($ktText) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted font-italic">Tidak ada kompetensi tambahan yang terpilih</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
