<div class="d-flex justify-content-between align-items-center my-3">
    <h6 class="font-weight-bold mb-0 text-primary">
        <i class="fas fa-sliders-h mr-1"></i> 1. Item Skala Penilaian (Stepped Slider 0 - 4)
    </h6>
    <button type="button" class="btn btn-primary btn-sm" id="btn_add_slider_item">
        <i class="fas fa-plus fa-fw"></i> Tambah Item
    </button>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm mb-4" id="table_slider_items">
        <thead>
            <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th style="width: 160px;">Kategori</th>
                <th style="width: 180px;">Kompetensi</th>
                <th>Definisi</th>
                <th>Key Actions</th>
                <!-- <th style="width: 200px;">Preview Slider Control</th> -->
                <th style="width: 90px;">Bobot (%)</th>
                <th style="width: 50px;" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rows injected via JS -->
        </tbody>
    </table>
</div>