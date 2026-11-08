<?= $this->extend('frontend/layouts/main') ?>

<?= $this->section('content') ?>

<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Login Siswa - Asesmen Berbasis Komputer</title>
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

	<?php $logo_app = ($setting->logo ?? null) == null ? base_url() . 'assets/img/favicon.png' : base_url() . $setting->logo; ?>
	<link rel="shortcut icon" href="<?= $logo_app ?>" type="image/x-icon">
	<meta name="description"
		content="Aplikasi Asesmen Berbasis Komputer membantu sekolah dan siswa dalam pelaksanaan penilaian di sekolah.">
	<meta name="keyword"
		content="ANBK, Ujian, Ujian Nasional, Ulangan Harian, Ulangan Semester, Mid Semester, Asesmen Nasional, Ujian Sekolah">
	<meta name="google" content="nositelinkssearchbox" />
	<meta name="robots" content="index, follow">
	<link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/bootstrap-icon/bootstrap-icons.css">
	<link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/tailwind.css">
	<link rel="stylesheet" href="<?= base_url() ?>/assets/plugins/sweetalert2/sweetalert2.min.css">
	<link rel="stylesheet" href="<?= base_url() ?>/assets/app/css/premium_adminlte.css">

	<script src="<?= base_url() ?>/assets/plugins/jquery/jquery.min.js"></script>
</head>

<body class="hold-transition m-0 p-0" style="background-color: #f4f6f9;">
	<div class="container-fluid text-white p-0"
		style="background:#00238f;background-size: contain;background-image: url('<?= base_url() ?>/assets/front_assets/images/header-bg.png');background-repeat: no-repeat;background-position: left; height:185px; position: fixed; top: 0; left: 0; width: 100%; z-index: 0;">
		<div class="row m-0">
			<div class="col-md-12 pt-5">
				<img class="img-fluid" style="position:absolute;left:50%;transform: translateX(-50%);"
					src="<?= base_url() ?>/assets/front_assets/images/logo.png" alt="DISDIK NTT">
			</div>
		</div>
	</div>

	<div class="wrapper" id="login-container" style="position: relative; z-index: 1;">
		<div id="formContent" class="shadow-lg animate__animated animate__fadeInUp">
			<div class="text-start p-5">
				<div class="mb-2">
					<h3 class="fw-bold text-dark">Selamat Datang</h3>
				</div>
				<div class="mb-4 text-muted"><small class="text-muted">Silakan login menggunakan username dan password
						yang kamu
						miliki</small>
				</div>


				<!-- Username Login Form -->
				<div id="login-username">
					<form action="<?= site_url('auth/login') ?>" id="login" class="login-form" method="POST">
						<?= csrf_field() ?>
						<input type="hidden" name="login_variant" value="siswa">
						<input type="hidden" id="cbt-only" name="cbt-only" value="1">

						<!-- Modern Input Username -->
						<div
							class="input-container mb-3 d-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
							<i class="bi bi-person-circle text-secondary me-3" style="font-size: 1.25rem;"></i>
							<input type="text" class="form-control border-0 shadow-none bg-transparent p-0 text-dark"
								placeholder="Username" name="username" id="identity" autocomplete="off" required>
							<div class="invalid-feedback"></div>
						</div>

						<!-- Modern Input Password -->
						<div
							class="input-container mb-3 d-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
							<i class="bi bi-lock-fill text-secondary me-3" style="font-size: 1.25rem;"></i>
							<input type="password"
								class="form-control border-0 shadow-none bg-transparent p-0 text-dark"
								placeholder="Password" name="password" id="password" autocomplete="off" required>
							<span class="cursor-pointer text-muted ms-2" onclick="showPassword()" id="btn-eye"
								style="cursor: pointer;">
								<i class="btn-eye bi bi-eye" style="font-size: 1.25rem;"></i>
							</span>
							<div class="invalid-feedback"></div>
						</div>

						<div class="row mt-4">
							<div class="col-12">
								<button type="submit" id="submit"
									class="btn btn-primary w-100 fw-bold py-2 rounded-pill"
									style="font-size: 1rem; background-color: #00238f; border: none; box-shadow: 0 4px 6px rgba(0, 35, 143, 0.3);">Login</button>
							</div>
						</div>
					</form>
				</div>

				<div id="loader" class="text-center">
					<img src='<?= base_url() ?>/assets/img/ajax-loader.gif' style="max-width: 100px;" />
				</div>
			</div>

		</div>
		<div id="infoMessage" class="mt-3" style="width: 90%; max-width: 450px;">
			<?php if ($message != "") { ?>
				<div class="shadow p-3 bg-white rounded-3 text-danger fw-bold text-center"><?= $message; ?></div>
			<?php } ?>
		</div>

	</div>
	<footer class="container text-center py-3 fixed-bottom" style="z-index: 2;">
		<small class="text-muted">COPYRIGHT &copy; <?php echo date('Y'); ?> |
			<?= esc($setting->nama_sekolah ?? 'IKASMEDIA') ?></small>
	</footer>

	<style>
		/* STRUCTURE */
		html,
		body {
			margin: 0 !important;
			padding: 0 !important;
			background-color: #f4f6f9;
			height: 100%;
			overflow: hidden;
			/* Prevent scrolling */
			font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
		}

		#loader {
			display: none;
			margin-top: 20px;
		}

		.wrapper {
			display: flex;
			align-items: center;
			flex-direction: column;
			justify-content: flex-start;
			width: 100%;
			height: 100vh;
			/* Fixed height match screen */
			padding: 20px;
			padding-top: 130px;
		}

		#formContent {
			border-radius: 15px;
			background: #fff;
			width: 90%;
			max-width: 450px;
			position: relative;
			padding: 0px;
			background-size: 300px;
			background-image: url('<?= base_url() ?>/assets/front_assets/images/logo-w.png');
			background-repeat: no-repeat;
			background-position: top right;
		}

		.input-container {
			transition: all 0.3s;
			background-color: #fff;
			border: 1px solid #e9ecef !important;
		}

		.input-container:focus-within {
			border-color: #007bff !important;
			box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.1);
		}

		input.form-control {
			height: auto;
			font-size: 1rem;
		}

		::placeholder {
			color: #adb5bd;
			opacity: 1;
		}

		.btn-primary:hover {
			background-color: #001861 !important;
			transform: translateY(-1px);
		}

		#infoMessage {
			transition: all 0.3s ease-in-out;
			font-weight: 500;
		}
	</style>

	<script src="<?= base_url() ?>/assets/plugins/sweetalert2/sweetalert2.min.js"></script>

	<script type="text/javascript">
		$(document).ready(function () {
			let base_url = '<?= base_url(); ?>';
			console.log('Base URL:', base_url);


			console.log('Form Action:', $('form#login').attr('action'));

			$('form#login').on('submit', function (e) {
				e.preventDefault();
				e.stopImmediatePropagation();

				var infobox = $('#infoMessage');
				infobox.html('<div class="shadow p-3 bg-white rounded-3 text-info fw-bold text-center">Checking...</div>');

				var btnsubmit = $('#submit');
				btnsubmit.attr('disabled', 'disabled').html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading...');

				$('#loader').show();

				$.ajax({
					url: $(this).attr('action'),
					type: 'POST',
					data: $(this).serialize(),
					dataType: 'json',
					success: function (data) {
						$('#loader').fadeOut();
						btnsubmit.removeAttr('disabled').text('Login');

						if (data.status) {
							infobox.html('<div class="shadow p-3 bg-white rounded-3 text-success fw-bold text-center">' + (data.message || "Login Sukses. Anda akan dialihkan...") + '</div>');
							const respData = data.data || {};
							let go = respData.redirect ? respData.redirect : base_url + (respData.url || 'dashboard');
							setTimeout(function () {
								window.location.href = go;
							}, 1000);
						} else {
							if (data.errors) {
								$.each(data.errors, function (key, val) {
									if (val) {
										$('[name="' + key + '"]').addClass('is-invalid');
									}
								});
							}
							if (data.message) {
								infobox.html('<div class="shadow p-3 bg-white rounded-3 text-danger fw-bold text-center">' + data.message + '</div>');
							} else {
								infobox.empty();
							}
						}
					},
					error: function (xhr, status, error) {
						$('#loader').fadeOut();
						btnsubmit.removeAttr('disabled').text('Login');

						if (xhr.status === 403) {
							infobox.html('<div class="shadow p-3 bg-white rounded-3 text-danger fw-bold text-center">Sesi kadaluarsa, silakan refresh halaman.</div>');
						} else {
							infobox.html('<div class="shadow p-3 bg-white rounded-3 text-danger fw-bold text-center">Terjadi kesalahan server (' + xhr.status + ').</div>');
						}
					}
				});
			});

			$('input').on('focus input', function () {
				$(this).removeClass('is-invalid');
			});
		});

		function showPassword() {
			var input = $('#password');
			var icon = $('.btn-eye');
			if (input.attr('type') === 'password') {
				icon.removeClass('bi-eye').addClass('bi-eye-slash');
				input.attr('type', 'text');
			} else {
				icon.removeClass('bi-eye-slash').addClass('bi-eye');
				input.attr('type', 'password');
			}
		}
	</script>
</body>

</html>
<?= $this->endSection() ?>