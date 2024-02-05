<!DOCTYPE html>
<html>
<?= $this->include('backend/_partials/head') ?>

<body data-background-color="bg3">
  <div class="wrapper">
    <div class="main-header">
      <?= $this->include('backend/_partials/logo') ?>
      <?= $this->include('backend/_partials/navbar') ?>
    </div>
    <?= $this->include('backend/_partials/sidebar') ?>

    <div class="main-panel is-loading">
      <div class="container">
        <div class="page-inner">
          <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
              <?= session()->getFlashdata('success'); ?>
            </div>
          <?php elseif (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
              <?= session()->getFlashdata('error'); ?>
            </div>
          <?php endif; ?>

          <!-- Show Breadcrumb -->
          <?= $this->include('backend/_partials/breadcrumb') ?>

          <?php if ($action_menu === 'F' || $action_menu === 'T' || $action_menu === 'R') : ?>
            <!-- Show Page Filter -->
            <?= $filter && $action_menu === 'T' ? $this->include($filter) : '' ?>

            <!-- Section Row Main Page-->
            <div class="row main_page">
              <div class="col-md-12">
                <div class="card card-action-menu" data-action-menu="<?= $action_menu ?>">
                  <div class="card-header">
                    <div class="float-left">
                      <h4 class="card-title"><?= $title; ?></h4>
                    </div>
                    <?php if ($action_menu !== 'R') : ?>
                      <div class="float-right">
                        <?= $toolbar_button ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?= $this->renderSection('content') ?>
                  <?= $action_button ?>
                </div>
              </div>
            </div>

            <!-- View Table Report -->
            <?php if ($action_menu === 'R') : ?>
              <div class="row">
                <div class="col-md-12 card-table-report">
                  <div class="card">
                    <div class="card-header">
                      <div class="float-right d-none">
                        <?= $toolbar_button ?>
                      </div>
                    </div>
                    <?= $table_report ? $this->include($table_report) : '' ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          <?php else : ?>
            <?= $this->renderSection('content') ?>
          <?php endif; ?>
        </div>
      </div>
      <!-- <? //= $this->include('backend/_partials/footer') 
            ?> -->
    </div>

    <?= $this->include('backend/_partials/quicksidebar') ?>
  </div>
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>
  <?= $this->include('backend/auth/form_password') ?>
  <?= $this->include('backend/modal/activity_info') ?>

  <?= $this->include('backend/_partials/js') ?>
</body>

</html>