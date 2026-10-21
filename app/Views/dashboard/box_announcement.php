<?php
/**
- app/Views/dashboard/announcements.php - Senior-Friendly Accessibility Version
 */
$announcements = $announcements ?? [];
$perPage       = 5;
$totalItems    = count($announcements);
$totalPages    = (int) ceil($totalItems / $perPage);
?>

<div class="card border-0 shadow-sm card-round h-100 d-flex flex-column" id="boxAnnouncementCard" data-per-page="<?= $perPage ?>">
    <!-- Header -->
    <div class="card-header bg-transparent border-0 p-3 p-md-4 pb-0 d-flex align-items-center justify-content-between">
        <h5 class="fw-extrabold text-dark mb-0 ann-card-title">Pengumuman &amp; Pesan</h5>
        <a href="<?= base_url('sas/pesan') ?>" class="ann-all">
            Lihat semua <i class="fas fa-chevron-right ms-1" style="font-size: 0.85rem;" aria-hidden="true"></i>
        </a>
    </div>

    <!-- Body / List -->
    <div class="card-body p-3 p-md-4 flex-grow-1">
        <div class="ann-list" id="annList" style="<?= $totalItems === 0 ? 'display: none;' : '' ?>">
            <?php foreach ($announcements as $index => $row): 
                $a       = (array) $row; 
                $pageNum = (int) floor($index / $perPage) + 1;
            ?>
                <div class="ann-row is-unread"
                     role="button"
                     tabindex="0"
                     data-page="<?= $pageNum ?>"
                     style="<?= $pageNum > 1 ? 'display: none;' : '' ?>"
                     data-id="<?= esc($a['trx_message_id'] ?? '', 'attr') ?>"
                     data-title="<?= esc($a['subject'] ?? 'Pesan Baru', 'attr') ?>"
                     data-sender="<?= esc($a['author'] ?? 'System', 'attr') ?>"
                     data-date="<?= esc($a['messagedate'] ?? '', 'attr') ?>"
                     data-body="<?= esc($a['body'] ?? '', 'attr') ?>">
                    <span class="ann-dot" aria-hidden="true"></span>
                    <div class="ann-body">
                        <div class="ann-title"><?= esc($a['subject'] ?? 'Pesan Baru') ?></div>
                        <div class="ann-snippet"><?= esc(mb_strimwidth(strip_tags($a['body'] ?? ''), 0, 120, '…')) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty State Container -->
        <div class="text-center py-5" id="annEmptyState" style="<?= $totalItems > 0 ? 'display: none;' : '' ?>">
            <i class="far fa-envelope-open fa-3x d-block mb-3 ann-empty-icon" aria-hidden="true"></i>
            <span class="ann-empty-text">Belum ada pengumuman atau pesan baru.</span>
        </div>
    </div>

    <!-- Footer / Pagination -->
    <!-- <div class="card-footer bg-transparent border-top-0 px-3 px-md-4 pb-4 pt-0 d-flex align-items-center justify-content-between" id="annFooter" style="<?= $totalPages <= 1 ? 'display: none !important;' : '' ?>">
        <span class="fw-bold" id="annPageIndicator" style="font-size: 0.95rem; color: #334155;">
            Halaman 1 dari <?= $totalPages ?>
        </span>
        <div class="ann-btn-group">
            <button type="button" class="ann-nav-btn" id="annPrevBtn" disabled aria-label="Halaman sebelumnya" title="Sebelumnya">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="ann-nav-btn" id="annNextBtn" aria-label="Halaman berikutnya" title="Berikutnya">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div> -->
</div>

<!-- Accessible Modal Detail -->
<div class="modal fade" id="modalAnnouncementDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom p-4">
                <div>
                    <h5 class="modal-title fw-extrabold mb-2" id="annTitle" style="font-size: 1.25rem; color: #0f172a;">Pesan Baru</h5>
                    <div class="ann-modal-meta">
                        <i class="far fa-user me-1" aria-hidden="true"></i><span id="annSender">System</span>
                        <span class="mx-2">·</span>
                        <i class="far fa-calendar-alt me-1" aria-hidden="true"></i><span id="annDate"></span>
                    </div>
                </div>
                <button type="button" class="btn-close close ann-modal-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body lh-lg p-4" id="annContent" style="font-size: 1rem; color: #1e293b;"></div>
        </div>
    </div>
</div>

<style>
.ann-card-title {
    font-size: 1.35rem !important;
    font-weight: 800 !important;
    color: #0f172a !important;
}

.ann-all { 
    font-size: 0.95rem; 
    font-weight: 800; 
    color: #1d4ed8; 
    text-decoration: none; 
}
.ann-all:hover { 
    text-decoration: underline; 
    color: #1e40af;
}

.ann-row {
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    padding: .85rem 1rem;
    border-top: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all .15s ease;
}
.ann-row:first-child { border-top: 0; }
.ann-row:hover, .ann-row:focus-visible { 
    background: #eff6ff; 
    outline: 2px solid #1d4ed8;
}

.ann-dot {
    flex: none;
    width: 12px; 
    height: 12px;
    margin-top: .45rem;
    border-radius: 50%;
    background: #1d4ed8;
    box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.2);
}

.ann-body { min-width: 0; flex: 1; }

.ann-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.35;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ann-snippet {
    font-size: 0.92rem;
    font-weight: 600;
    color: #334155;
    margin-top: .2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Empty state styling */
.ann-empty-icon {
    color: #64748b;
}

.ann-empty-text {
    font-size: 1rem;
    font-weight: 700;
    color: #334155;
}

#annContent img { max-width: 100%; height: auto; border-radius: 8px; }

/* Large Touch Target Pagination Buttons */
.ann-btn-group { display: flex; gap: 8px; }
.ann-nav-btn {
    width: 40px; 
    height: 40px;
    border: 2px solid #94a3b8; 
    background: #fff; 
    color: #0f172a;
    border-radius: 8px; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center;
    font-size: 0.9rem; 
    font-weight: 800;
    cursor: pointer; 
    transition: all 0.15s ease;
}
.ann-nav-btn:hover:not(:disabled) { 
    background: #1d4ed8; 
    color: #fff; 
    border-color: #1d4ed8; 
}
.ann-nav-btn:disabled { 
    opacity: 0.4; 
    cursor: not-allowed; 
    background: #f1f5f9; 
    border-color: #cbd5e1;
}

/* Modal Styling */
.ann-modal-meta {
    font-size: 0.92rem;
    font-weight: 700;
    color: #475569;
}

.ann-modal-close {
    font-size: 1.5rem;
    opacity: 0.8;
}
.ann-modal-close:hover {
    opacity: 1;
}
</style>