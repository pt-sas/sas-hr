<?= $this->extend('backend/_partials/overview') ?>
<?= $this->section('content') ?>

<style>
    /* Article content: constrain media, preserve readability */
    .article-content {
        max-width: 100%;
        overflow-wrap: break-word;
        word-wrap: break-word;
        line-height: 1.7;
        font-size: 1rem;
        color: #2c2c2c;
    }

    .article-content img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 1rem auto;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .article-content iframe,
    .article-content video,
    .article-content embed {
        max-width: 100%;
        height: auto;
    }

    .article-content pre,
    .article-content code {
        background: #f4f4f4;
        padding: 0.5rem;
        border-radius: 4px;
        overflow-x: auto;
        max-width: 100%;
    }

    .article-content table {
        width: 100%;
        display: block;
        overflow-x: auto;
        border-collapse: collapse;
    }

    .article-content table td,
    .article-content table th {
        border: 1px solid #ddd;
        padding: 8px;
    }

    .article-content blockquote {
        border-left: 4px solid #3b82f6;
        padding-left: 1rem;
        color: #555;
        margin: 1rem 0;
        font-style: italic;
    }

    .article-content h1, .article-content h2, .article-content h3 {
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    /* Sidebar scrolls independently if long */
    .sidebar-list {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>

<div class="card-body card-main">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="<?= base_url('sas/help') ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-arrow-left"></i> Kembali ke Pusat Bantuan
        </a>
    </div>

    <div class="row">
        <!-- Left Column: Category Sidebar Menu -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card shadow-sm border">
                <div class="card-header bg-primary text-white font-weight-bold text-uppercase py-3">
                    <i class="fas fa-list-alt mr-2"></i> <?= esc($activeArticle->category_name ?? 'Kategori') ?>
                </div>
                <div class="list-group list-group-flush sidebar-list">
                    <?php foreach ($sidebarArticles as $sidebarItem) : ?>
                        <?php $isActive = ($sidebarItem->md_article_id == $activeArticle->md_article_id); ?>
                        <a href="<?= base_url('sas/help/article/' . $sidebarItem->md_article_id) ?>"
                           class="list-group-item list-group-item-action py-3 <?= $isActive ? 'active font-weight-bold' : '' ?>">
                            <i class="fas fa-file-alt mr-2 <?= $isActive ? '' : 'text-muted' ?>"></i>
                            <?= esc($sidebarItem->title) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Active Article Reader -->
        <div class="col-md-8 col-lg-9">
            <div class="card shadow-sm border">
                <div class="card-body p-4 p-md-5">
                    <h2 class="font-weight-bold mb-3"><?= esc($activeArticle->title) ?></h2>

                    <!-- Article Metadata Info -->
                    <div class="article-meta text-muted mb-3 d-flex flex-wrap align-items-center" style="font-size: 0.875rem;">
                        <?php $pubDate = $activeArticle->published_at ?? $activeArticle->created_at ?? null; ?>
                        <?php if (!empty($pubDate)) : ?>
                            <span class="mb-2">
                                <i class="far fa-calendar-alt mr-1"></i>
                                Dipublikasikan pada: <strong class="text-dark"><?= date('d M Y, H:i', strtotime($pubDate)) ?></strong>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($activeArticle->updated_at)) : ?>
                            <span class="mx-2 mb-2">&middot;</span>
                            <span class="mb-2">
                                <i class="fas fa-history mr-1"></i>
                                Diperbarui: <strong class="text-dark"><?= date('d M Y, H:i', strtotime($activeArticle->updated_at)) ?></strong>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($activeArticle->created_by_name)) : ?>
                            <span class="mx-2 mb-2">&middot;</span>
                            <span class="mb-2">
                                <i class="far fa-user mr-1"></i>
                                Penulis: <strong class="text-dark"><?= esc($activeArticle->created_by_name) ?></strong>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($attachments)) : ?>
                            <span class="mx-2 mb-2">&middot;</span>
                            <span class="mb-2">
                                <a href="#article-attachments" class="badge badge-pill badge-primary px-2 py-1 text-decoration-none">
                                    <i class="fas fa-paperclip mr-1"></i> <?= count($attachments) ?> Lampiran
                                </a>
                            </span>
                        <?php endif; ?>
                    </div>
                    <hr class="mb-4">
                    <div class="article-content">
                        <?= $activeArticle->content ?>
                    </div>

                    <!-- Component Lampiran File -->
                    <?php if (!empty($attachments)) : ?>
                        <div id="article-attachments" class="card mt-4 border shadow-sm">
                            <div class="card-header bg-light font-weight-bold py-2.5 d-flex align-items-center">
                                <i class="fas fa-paperclip text-secondary mr-2"></i> 
                                <span>Lampiran File <span class="badge badge-secondary badge-pill ml-1"><?= count($attachments) ?></span></span>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($attachments as $file) : ?>
                                    <?php
                                        // Resolve attributes cleanly with entity methods + variable fallbacks
                                        $fileName  = method_exists($file, 'getFileName') ? $file->getFileName() : ($file->file_name ?? $file->filename ?? 'Lampiran File');
                                        $fileUrl   = method_exists($file, 'getFileUrl') ? $file->getFileUrl() : base_url($file->file_path ?? $file->filepath ?? '#');
                                        $fileSize  = method_exists($file, 'getFormattedSize') ? $file->getFormattedSize() : '0 KB';
                                        $iconClass = method_exists($file, 'getIconClass') ? $file->getIconClass() : 'far fa-file text-muted';

                                        // PDF / Viewable file detection
                                        $checkType = strtolower(($file->file_type ?? '') . ' ' . $fileName);
                                        $isPdf     = str_contains($checkType, 'pdf');
                                    ?>
                                    <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center py-3 px-3 hover-shadow">
                                        <div class="d-flex align-items-center mb-2 mb-sm-0 mr-sm-3">
                                            <i class="<?= $iconClass ?> fa-2x mr-3 text-primary opacity-75"></i>
                                            <div>
                                                <span class="font-weight-bold d-block text-dark line-height-sm"><?= esc($fileName) ?></span>
                                                <small class="text-muted"><?= $fileSize ?></small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-end flex-shrink-0">
                                            <?php if ($isPdf) : ?>
                                                <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-outline-info mr-1">
                                                    <i class="far fa-eye mr-1"></i> Lihat
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= $fileUrl ?>" download="<?= esc($fileName) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download mr-1"></i> Unduh
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>