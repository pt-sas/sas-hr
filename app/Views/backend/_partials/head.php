	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title>SAS <?= $title ? ' | ' . $title : '' ?></title>
		<meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
		<link rel="icon" href="<?= base_url('custom/image/logo.png') ?>" type="image/x-icon" />

		<!-- Fonts and icons -->
		<script src="<?= base_url('atlantis-pro/js/plugin/webfont/webfont.min.js') ?>"></script>
		<script>
			WebFont.load({
				google: {
					"families": ["Lato:300,400,700,900"]
				},
				custom: {
					"families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands",
						"simple-line-icons"
					],
					urls: ['<?= base_url('atlantis-pro/css/fonts.css') ?>']
				},
				active: function() {
					sessionStorage.fonts = true;
				}
			});
		</script>

		<!-- Custom styles for this template-->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/css/atlantis.css') ?>">
		<!-- SweetAlert2 -->
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/js/plugin/sweetalert2/sweetalert2.min.css') ?>">
		<!-- Loader waitMe -->
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/js/plugin/loader/waitMe.min.css') ?>">
		<!-- DataTables -->
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/js/plugin/datatables-bs4/css/dataTables.bootstrap4.css') ?>">
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/js/plugin/datatables-fixedcolumns/css/fixedColumns.bootstrap4.min.css') ?>">
		<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
		<!-- Custom Style -->
		<link rel="stylesheet" href="<?= base_url('custom/css/custom.css') ?>">
		<!-- Table Treefy -->
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/js/plugin/bootstrap-treefy/css/bootstrap-treefy.min.css') ?>">
		<!-- DateRangePicker -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
		<!-- DatePicker -->
		<link rel="stylesheet" href="<?= base_url('atlantis-pro/js/plugin/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') ?>">
	</head>