
<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<div class="card-body card-main">
    <div class="text-center mb-4">
        <h2 class="mb-2">Pusat Bantuan</h2>
        <p class="text-muted">Temukan informasi yang Anda butuhkan</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <!-- Search Container with Relative Positioning -->
                <div class="position-relative search-container">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search_help" placeholder="Cari artikel..." autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-primary" id="btn_search_help" type="button">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Live Search Results Dropdown -->
                    <div id="search_results_wrapper" class="position-absolute w-100 mt-1 shadow-lg bg-white rounded" style="z-index: 1050; display: none; max-height: 350px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Kategori</h4>
    <div class="row">
        <?php foreach ($categories as $category) : ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <a href="<?= base_url('sas/help/category/' . $category['help_category_id']) ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <i class="<?= esc($category['icon'] ?: 'fas fa-question-circle') ?> fa-2x text-primary"></i>
                            </div>
                            <h5 class="card-title"><?= esc($category['name']) ?></h5>
                            <p class="card-text text-muted"><?= esc($category['description']) ?></p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    /* 
       This completely overpowers the framework's inline 'display: none'.
       Because it's inside card-main, it will naturally hide when card-main hides,
       and instantly reappear when card-main reappears!
    */
    .card-main .filter_page {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
    }
</style>

<script>
$(document).ready(function() {
    // Move the filter inside card-main so it hitches a ride on card-main's visibility
    let myFilter = $('.filter_page').first();
    if (myFilter.length) {
        $('.card-main').prepend(myFilter);
    }
});
</script>

<?= $this->endSection() ?>