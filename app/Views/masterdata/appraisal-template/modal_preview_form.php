<div class="modal fade" id="modal_preview_form" modal-type="not-main" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 70vw;">
        <div class="modal-content border-0 shadow-lg position-relative" style="border-radius: 12px; overflow: hidden; background: #ffffff;">

            <!-- Pure Body Canvas -->
            <div class="modal-body p-3 p-md-4" style="min-height: 400px;">
                
                <!-- Loading State -->
                <div id="preview_form_loader" class="text-center py-5 my-4">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 2rem; height: 2rem; border-width: 2.5px;"></div>
                    <p class="font-weight-bold text-dark mb-1" style="font-size: 13.5px;">Menyiapkan Pratinjau Form...</p>
                    <small class="text-muted" style="font-size: 11.5px;">Memuat komponen visual evaluasi kinerja secara riil.</small>
                </div>

                <!-- Dynamic Content Hook -->
                <div id="preview_form_content" style="display: none;"></div>

            </div>

        </div>
    </div>
</div>