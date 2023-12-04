<?= $this->extend('backend/_partials/overview') ?>

<?= $this->section('content') ?>
<?= $this->include('backend/configuration/role/form_role'); ?>
<div class="card-body card-main">
  <table class="table table-striped table-hover tb_display">
    <thead>
      <tr>
        <th>ID</th>
        <th>No</th>
        <th>Name</th>
        <th>Description</th>
        <th>Manual</th>
        <th>Can Export</th>
        <th>Can Report</th>
        <th>Allow Multiple Print</th>
        <th>Active</th>
        <th>Actions</th>
      </tr>
    </thead>
  </table>
</div>
<?= $this->endSection() ?>