<div class="sidebar sidebar-style-2" data-background-color="dark">
  <div class="sidebar-wrapper scrollbar scrollbar-inner">
    <div class="sidebar-content">
      <div class="user">
        <div class="avatar-sm float-left mr-2">
          <img src="<?= $foto ?>" alt="..." class="avatar-img rounded-circle">
        </div>
        <div class="info">
          <a href="<?= site_url('sas') ?>">
            <span>
              <?= $username; ?>
              <span class="user-level"><?= $level; ?></span>
            </span>
          </a>
        </div>
      </div>
      <?= $sidebar; ?>
    </div>
  </div>
</div>