<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('backend/configuration/user/form_user'); ?>
<div class="card-body card-main">
  <table class="table table-striped table-hover tb_display">
    <thead>
      <tr>
        <th>ID</th>
        <th>No</th>
        <th>Username</th>
        <th>Name</th>
        <th>Description</th>
        <th>Email</th>
        <th>Active</th>
        <th>Actions</th>
      </tr>
    </thead>
  </table>
</div>
<?= $this->endSection() ?>