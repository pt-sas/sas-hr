<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>

<?= $this->include('backend/configuration/menu/form_menu'); ?>
<div class="card-body card-main">
  <table class="table table-striped table-hover tb_display">
    <thead>
      <tr>
        <th>ID</th>
        <th>No</th>
        <th>Name</th>
        <th>Url</th>
        <th>Sequence</th>
        <th>Icon</th>
        <th>Active</th>
        <th>Actions</th>
      </tr>
    </thead>
  </table>
</div>
<?= $this->endSection() ?>