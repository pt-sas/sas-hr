<!-- Core JS Files -->
<script src="<?= base_url('atlantis-pro/js/core/jquery.3.2.1.min.js') ?>"></script>
<script src="<?= base_url('atlantis-pro/js/core/popper.min.js') ?>"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- jQuery UI -->
<script src="<?= base_url('atlantis-pro/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js') ?>"></script>
<script src="<?= base_url('atlantis-pro/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js') ?>"></script>
<!-- jQuery Auto Complete -->
<script src="<?= base_url('atlantis-pro/js/core/jquery.autocomplete.js') ?>"></script>
<!-- jQuery Scrollbar -->
<script src="<?= base_url('atlantis-pro/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') ?>"></script>
<!-- Moment JS -->
<script src="<?= base_url('atlantis-pro/js/plugin/moment/moment.min.js') ?>"></script>
<!-- Chart JS -->
<script src="<?= base_url('atlantis-pro/js/plugin/chart.js/chart.min.js') ?>"></script>
<!-- Datatables -->
<script src="<?= base_url('atlantis-pro/js/plugin/datatables/datatables.min.js') ?>"></script>
<script src="<?= base_url('atlantis-pro/js/plugin/datatables-bs4/js/dataTables.bootstrap4.js') ?>"></script>
<script src="<?= base_url('atlantis-pro/js/plugin/datatables-fixedcolumns/js/dataTables.fixedColumns.min.js') ?>">
</script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
<!-- DateTimePicker -->
<script src="<?= base_url('atlantis-pro/js/plugin/datepicker/bootstrap-datetimepicker.min.js') ?>"></script>
<!-- DateRangePicker -->
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- DatePicker -->
<script src="<?= base_url('atlantis-pro/js/plugin/bootstrap-datepicker/js/bootstrap-datepicker.min.js') ?>"></script>
<!-- Summernote -->
<script src="<?= base_url('atlantis-pro/js/plugin/summernote/summernote-bs4.min.js') ?>"></script>
<!-- Select2 -->
<script src="<?= base_url('atlantis-pro/js/plugin/select2/select2.full.min.js') ?>"></script>
<!-- Sweet Alert -->
<script src="<?= base_url('atlantis-pro/js/plugin/sweetalert/sweetalert.min.js') ?>"></script>
<!-- SweetAlert2 -->
<script src="<?= base_url('atlantis-pro/js/plugin/sweetalert2/sweetalert2.min.js') ?>"></script>
<!-- Loader waitMe -->
<script src="<?= base_url('atlantis-pro/js/plugin/loader/waitMe.min.js') ?>"></script>
<!-- Atlantis JS -->
<script src="<?= base_url('atlantis-pro/js/atlantis.min.js') ?>"></script>
<!-- AutoNumeric Rupiah -->
<script src="<?= base_url('atlantis-pro/js/plugin/auto-numeric/autoNumeric.js') ?>"></script>
<!-- Table Treefy -->
<script src="<?= base_url('atlantis-pro/js/plugin/bootstrap-treefy/js/bootstrap-treefy.min.js') ?>"></script>
<!-- Scanner Auto Focus -->
<script src="<?= base_url('atlantis-pro/js/plugin/jquery-scanner/jquery.scannerdetection.js') ?>"></script>
<!-- Owl Carousel -->
<script src="<?= base_url('atlantis-pro/js/plugin/owl-carousel/owl.carousel.min.js') ?>"></script>
<!-- Bootstrap Toogle -->
<script src="<?= base_url('atlantis-pro/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js') ?>"></script>
<!-- Websocket Pusher -->
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<!-- Custom Process -->
<script src="<?= base_url('custom/js/Process.js') ?>"></script>
<!-- Event For Table Line -->
<script src="<?= base_url('custom/js/Event.js') ?>"></script>
<!-- Custom Logic -->
<script src="<?= base_url('custom/js/Logic.js') ?>"></script>
<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
<!-- PDF to Image View -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.13.216/pdf.min.js"></script>
<script type="text/javascript">
const preview = document.getElementById('preview');

let scanner = new Instascan.Scanner({
    video: preview
});
scanner.addListener('scan', function(content) {
    let formData = new FormData();
    let date = new Date();
    let datetime = moment(date).format('YYYY-MM-DD HH:mm:ss');
    formData.append("nik", content);
    formData.append("checktime", datetime);

    let url = CURRENT_URL + CREATE;

    $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "JSON",
        beforeSend: function() {
            loadingForm('scan_preview', "facebook");
        },
        complete: function() {
            hideLoadingForm('scan_preview');
        },
        success: function(result) {
            if (result[0].success) {
                speak("Terima kasih data berhasil tersimpan");
                reloadTable();
            } else {
                speak(result[0].message);
                Toast.fire({
                    type: "error",
                    title: result[0].message,
                });
            }
        },
    });
});
Instascan.Camera.getCameras().then(function(cameras) {
    if (cameras.length) {
        let selectedCamera = cameras[0];

        cameras.forEach(function(camera) {
            if (camera.name.toLowerCase().includes('back')) {
                selectedCamera = camera;
            }
        });

        scanner.start(selectedCamera);
    } else {
        console.error('No cameras found.');
    }
}).catch(function(e) {
    console.error(e);
});

$(function() {
    let serverTime = <?php if (!empty($timestamp)) {
                                echo $timestamp;
                            } ?>;
    let counterTime = 0;
    let date;

    setInterval(function() {
        date = new Date();

        serverTime = serverTime + 1;

        date.setTime(serverTime * 1000);
        let dateOnly = moment(date).format('D-MMM-YYYY');
        time = date.toLocaleTimeString('it-IT');

        $("#timestamp").html(dateOnly + " " + time);
    }, 1000);
});

function speak(text) {
    var speech = new SpeechSynthesisUtterance();
    speech.lang = "id-ID"; // Set the language
    speech.text = text; // Set the text to be spoken
    speech.volume = 1; // Volume level from 0 to 1
    speech.rate = 1; // Rate of speech (1 is normal speed)
    speech.pitch = 1; // Pitch level (1 is normal pitch)
    window.speechSynthesis.speak(speech);
}
</script>