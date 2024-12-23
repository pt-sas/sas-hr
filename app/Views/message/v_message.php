<?= $this->extend('backend/_partials/overview'); ?>
<?= $this->section('content') ?>
<div class="row main_page">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="float-left">
                    <h4 class="card-title">Notifikasi</h4>
                </div>
                <div class="float-right d-none">
                    <button class="btn btn-icon btn-round btn-primary set-read">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button class="btn btn-icon btn-round btn-danger multiple-delete">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <?= $this->include('message/form_message') ?>
            <div class="card-body card-main" id="card-notif">
                <table class="table table-hover tb_notification" style="width: 100%;">
                    <thead>
                        <th>ID</th>
                        <th>Aksi</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Date</th>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>