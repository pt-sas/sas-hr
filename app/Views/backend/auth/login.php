<?= $this->extend('backend/auth/_partials/overview') ?>

<?= $this->section('content') ?>
<div class="wrapper wrapper-login wrapper-login-full p-0">
  <div class="login-aside w-50 d-flex flex-column align-items-center justify-content-center text-center bg-secondary-gradient">
    <h1 class="title fw-bold text-white mb-3">PT. Sahabat Abadi Sejahtera</h1>
    <!-- <p class="subtitle text-white op-7">All In One Service Portal</p> -->
    <p class="subtitle text-white op-7">IT Aset</p>
  </div>
  <div class="login-aside w-50 d-flex align-items-center justify-content-center bg-white">
    <div class="container container-login container-transparent animated fadeIn">
      <h3 class="text-center">Sign In</h3>
      <form class="login-form" id="login-form">
        <div class="form-group">
          <label for="username" class="placeholder"><b>Username</b></label>
          <input type="text" class="form-control" id="username" name="username" autocomplete="off" autofocus>
          <small class="form-text text-danger" id="error_username"></small>
        </div>
        <div class="form-group">
          <label for="password" class="placeholder"><b>Password</b></label>
          <div class="position-relative">
            <input type="password" class="form-control" id="password" name="password">
            <div class="show-password">
              <span toggle="#password" class="far fa-eye toggle-password"></span>
            </div>
          </div>
          <small class="form-text text-danger" id="error_password"></small>
        </div>
        <div class="form-group form-action-d-flex mb-3">
          <!-- <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="rememberme">
            <label class="custom-control-label m-0" for="rememberme">Remember Me</label>
          </div> -->
          <button type="button" class="btn btn-secondary col-md-5 float-right mt-3 mt-sm-0 fw-bold btn_login">Login</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>