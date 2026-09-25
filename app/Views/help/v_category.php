<?= $this->extend('backend/_partials/overview') ?>
<?= $this->section('content') ?>

<div class="card-body card-main">
    <!-- Header & Back Button -->
    <div class="mb-4">
        <a href="<?= base_url('sas/help') ?>" class="btn btn-outline-primary btn-sm mb-3">
            <i class="fa fa-arrow-left"></i> Kembali ke Pusat Bantuan
        </a>
        <h2><?= esc($category['name']) ?></h2>
        <p class="text-muted"><?= esc($category['description'] ?? 'Daftar artikel dalam kategori ini') ?></p>
    </div>

    <!-- Article Grid -->
    <h4 class="mb-3">Daftar Artikel</h4>
    <div class="row">
        <?php if (!empty($articles)) : ?>
            <?php foreach ($articles as $article) : ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title font-weight-bold"><?= esc($article->title) ?></h5>
                            <p class="card-text text-muted flex-grow-1">
                                <?= character_limiter(strip_tags($article->content), 100) ?>
                            </p>
                            <a href="<?= base_url('sas/help/article/' . $article->article_id) ?>" class="btn btn-primary btn-sm mt-2">
                                Baca Artikel <i class="fa fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle mr-1"></i> Belum ada artikel dalam kategori ini.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>