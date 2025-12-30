<?php require_once('op_lib.php'); 
$login_video_url = ($login_video_url=='')?'https://media.istockphoto.com/id/1497945391/video/young-beautiful-hispanic-woman-business-worker-using-calculator-working-at-office.mp4?s=mp4-640x640-is&k=20&c=1XxBuXvvsPBXRW1MSneEHSgu3x5_Ylh16dt1hpU2fpI=':$login_video_url;
$logo = ($login_video_url=='')?$base_url.'system/img/logo.png':$base_url.'upload/'.$logo;
?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="COMPANY SOFTWARE">
	<meta name="author" content="OfferPlant">
	<meta name="keywords" content="">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<title><?= @$inst_name ?> </title>

	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

	<link class="js-stylesheet" href="<?= $base_url ?>system/css/light.css" rel="stylesheet">
	<link class="js-stylesheet" href="<?= $base_url ?>system/css/op.css" rel="stylesheet">
	<script src="js/settings.js"></script>
	
	<style>
		:root {
			--primary-blue: #2563eb;
			--sky-blue: #0ea5e9;
			--light-blue: #38bdf8;
			--blue-50: #eff6ff;
			--blue-100: #dbeafe;
			--blue-200: #bfdbfe;
			--blue-300: #93c5fd;
			--text-dark: #1e293b;
			--text-light: #64748b;
			--text-muted: #94a3b8;
			--white: #ffffff;
			--gray-50: #f8fafc;
			--gray-100: #f1f5f9;
			--border-light: #e2e8f0;
			--gradient-blue: linear-gradient(135deg, #2563eb 0%, #0ea5e9 50%, #38bdf8 100%);
			--shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
			--shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
			--shadow-large: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
		}

		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: 'Poppins', sans-serif;
			background: var(--gray-50);
			min-height: 100vh;
			overflow-x: hidden;
		}

		/* Main Container */
		.login-container {
			min-height: 100vh;
			display: flex;
		}

		/* Left Panel - Pattern & Logo */
		.left-panel {
			flex: 1;
			background: var(--gradient-blue);
			position: relative;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			padding: 3rem;
			overflow: hidden;
			background-color: #1f1e00;
            /*background-image: url("https://www.transparenttextures.com/patterns/p2.png");*/
            background-image: url("img/back.jpg");
            background-size:cover ;
		}

		.logo-section {
			z-index: 3;
			position: relative;
		}

		.logo-container {
			display: flex;
			align-items: center;
			gap: 1rem;
		}

		.logo-container img {
			height: 75px;
			width: auto;
			filter: brightness(0) invert(1);
		}
        .right_logo{
            float:right;
            top:50px;
        }
        
		.brand-text {
			color: var(--white);
			font-size: 1.1rem;
			font-weight: 600;
			letter-spacing: -0.025em;
		}

		/* Animated Vector Pattern */
		.pattern-container {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			overflow: hidden;
		}

		.geometric-shapes {
			position: absolute;
			width: 100%;
			height: 100%;
		}

		.shape {
			position: absolute;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.1);
			animation: float 8s ease-in-out infinite;
		}

		.shape:nth-child(1) {
			width: 100px;
			height: 100px;
			top: 20%;
			right: 10%;
			animation-delay: 0s;
		}

		.shape:nth-child(2) {
			width: 60px;
			height: 60px;
			top: 60%;
			right: 30%;
			animation-delay: 2s;
		}

		.shape:nth-child(3) {
			width: 80px;
			height: 80px;
			top: 40%;
			right: 60%;
			animation-delay: 4s;
		}

		.shape:nth-child(4) {
			width: 40px;
			height: 40px;
			top: 70%;
			right: 15%;
			animation-delay: 6s;
		}

		@keyframes float {
			0%, 100% { 
				transform: translateY(0px) rotate(0deg) scale(1);
				opacity: 0.1;
			}
			50% { 
				transform: translateY(-30px) rotate(180deg) scale(1.1);
				opacity: 0.2;
			}
		}

		/* Vector Lines */
		.vector-lines {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
		}

		.line {
			position: absolute;
			background: rgba(255, 255, 255, 0.15);
			border-radius: 2px;
		}

		.line-1 {
			width: 2px;
			height: 200px;
			top: 30%;
			right: 20%;
			animation: lineGrow 4s ease-in-out infinite;
			animation-delay: 1s;
		}

		.line-2 {
			width: 150px;
			height: 2px;
			top: 50%;
			right: 25%;
			animation: lineGrow 4s ease-in-out infinite;
			animation-delay: 3s;
		}

		.line-3 {
			width: 2px;
			height: 120px;
			top: 60%;
			right: 40%;
			animation: lineGrow 4s ease-in-out infinite;
			animation-delay: 5s;
		}

		@keyframes lineGrow {
			0%, 100% { 
				transform: scale(1);
				opacity: 0.15;
			}
			50% { 
				transform: scale(1.2);
				opacity: 0.3;
			}
		}

		/* Dots Pattern */
		.dots-pattern {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-image: radial-gradient(circle, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
			background-size: 30px 30px;
			animation: dotsMove 20s linear infinite;
		}

		@keyframes dotsMove {
			0% { transform: translate(0, 0); }
			100% { transform: translate(30px, 30px); }
		}

		/* Welcome Content */
		.welcome-content {
			z-index: 3;
			position: relative;
			color: var(--white);
		}

		.welcome-title {
			font-size: 2.5rem;
			font-weight: 700;
			line-height: 1.2;
			margin-bottom: 1rem;
		}

		.welcome-subtitle {
			font-size: 1.1rem;
			opacity: 0.9;
			font-weight: 300;
			line-height: 1.6;
		}

		/* Right Panel - Login Form */
		.right-panel {
			flex: 1;
			background: var(--white);
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 3rem;
			/*background-color: #f5fafa;*/
   /*         background-image: url("https://www.transparenttextures.com/patterns/mirrored-squares.png");*/
            background-color: #fafeff;
            background-image: url("https://www.transparenttextures.com/patterns/gradient-squares.png");

		}

		.login-form-container {
			width: 100%;
			max-width: 400px;
		

		}

		.form-header {
			text-align: center;
			margin-bottom: 2.5rem;
		}

		.form-title {
			color: var(--text-dark);
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: 0.5rem;
		}

		.form-subtitle {
			color: var(--text-light);
			font-size: 0.95rem;
		}

		/* Form Styles */
		.form-group {
			margin-bottom: 1.5rem;
		}

		.form-label {
			display: block;
			color: var(--text-dark);
			font-size: 0.875rem;
			font-weight: 500;
			margin-bottom: 0.5rem;
		}

		.input-wrapper {
			position: relative;
		}

		.form-input {
			width: 100%;
			padding: 0.875rem 1rem 0.875rem 3rem;
			border: 2px solid var(--border-light);
			border-radius: 12px;
			font-size: 0.95rem;
			color: var(--text-dark);
			background: var(--white);
			transition: all 0.3s ease;
			outline: none;
		}

		.form-input::placeholder {
			color: var(--text-muted);
		}

		.form-input:focus {
			border-color: var(--primary-blue);
			box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
		}

		.input-icon {
			position: absolute;
			left: 1rem;
			top: 50%;
			transform: translateY(-50%);
			color: var(--text-muted);
			font-size: 1rem;
			transition: color 0.3s ease;
		}

		.form-input:focus + .input-icon {
			color: var(--primary-blue);
		}

		.password-toggle {
			position: absolute;
			right: 1rem;
			top: 50%;
			transform: translateY(-50%);
			color: var(--text-muted);
			cursor: pointer;
			font-size: 1rem;
			transition: color 0.3s ease;
		}

		.password-toggle:hover {
			color: var(--primary-blue);
		}

		/* Form Options */
		.form-options {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin: 1.5rem 0;
		}

		.checkbox-wrapper {
			display: flex;
			align-items: center;
			cursor: pointer;
		}

		.checkbox-input {
			width: 18px;
			height: 18px;
			margin-right: 0.75rem;
			accent-color: var(--primary-blue);
			cursor: pointer;
		}

		.checkbox-label {
			color: var(--text-light);
			font-size: 0.875rem;
			cursor: pointer;
		}

		.forgot-link {
			color: var(--primary-blue);
			text-decoration: none;
			font-size: 0.875rem;
			font-weight: 500;
			transition: color 0.3s ease;
		}

		.forgot-link:hover {
			color: var(--sky-blue);
		}

		/* Login Button */
		.login-button {
			width: 100%;
			padding: 1rem;
			background: var(--gradient-blue);
			color: var(--white);
			border: none;
			border-radius: 12px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.login-button:hover {
			transform: translateY(-2px);
			box-shadow: var(--shadow-large);
		}

		.login-button:active {
			transform: translateY(0);
		}

		.login-button::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
			transition: left 0.5s;
		}

		.login-button:hover::before {
			left: 100%;
		}

		/* Loading State */
		.login-button.loading {
			opacity: 0.8;
			pointer-events: none;
		}

		.login-button.loading::after {
			content: '';
			position: absolute;
			top: 50%;
			left: 50%;
			width: 20px;
			height: 20px;
			margin: -10px 0 0 -10px;
			border: 2px solid rgba(255, 255, 255, 0.3);
			border-top: 2px solid var(--white);
			border-radius: 50%;
			animation: spin 1s linear infinite;
		}

		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}

		/* Mobile Responsive */
		@media (max-width: 968px) {
			.login-container {
				flex-direction: column;
			}

			.left-panel {
				min-height: 40vh;
				padding: 2rem;
			}

			.right-panel {
				flex: 1;
				padding: 2rem;
			}

			.welcome-title {
				font-size: 2rem;
			}
		}

		@media (max-width: 640px) {
			.left-panel {
				min-height: 30vh;
				padding: 1.5rem;
			}

			.right-panel {
				padding: 1.5rem;
			}

			.form-title {
				font-size: 1.75rem;
			}

			.welcome-title {
				font-size: 1.75rem;
			}

			.brand-text {
				font-size: 1rem;
			}
		}

		/* Accessibility */
		@media (prefers-reduced-motion: reduce) {
			*,
			*::before,
			*::after {
				animation-duration: 0.01ms !important;
				animation-iteration-count: 1 !important;
				transition-duration: 0.01ms !important;
			}
		}
	</style>
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<div class="login-container">
		<!-- Left Panel - Pattern & Logo -->
		<div class="left-panel">
			<div class="logo-section">
				<div class="logo-container">
					<img src='img/logo.png' alt="Logo" >
				
				</div>
			</div>

			<!-- Animated Pattern Background -->
			<div class="pattern-container">
				<div class="dots-pattern"></div>
				
				<div class="geometric-shapes">
					<div class="shape"></div>
					<div class="shape"></div>
					<div class="shape"></div>
					<div class="shape"></div>
				</div>

				<div class="vector-lines">
					<div class="line line-1"></div>
					<div class="line line-2"></div>
					<div class="line line-3"></div>
				</div>
			</div>

			<div class="welcome-content p-3">
				<!--<h1 class="welcome-title">Welcome Back ! </h1>-->
				<p class="welcome-subtitle">
				Where secure operations meet precise results — “Designed for complete process control, accuracy & speed.”
				</p>
			</div>
		</div>

		<!-- Right Panel - Login Form -->
		<div class="right-panel">
		    

			<div class="login-form-container">
			   
			    
				<div class="form-header">
					<h2 class="form-title">Sign In</h2>
					<span>Built for Speed & trust For Accuracy</span>
				</div>

				<form id='login_frm' method='post' type='system'>
					<div class="form-group">
						<label class="form-label">Username</label>
						<div class="input-wrapper">
							<input class="form-input" autocomplete="username" type="text" name="user_name" placeholder="Enter your username">
							<i class="fas fa-user input-icon"></i>
						</div>
					</div>

					<div class="form-group">
						<label class="form-label">Password</label>
						<div class="input-wrapper">
							<input class="form-input" type="password" autocomplete="new-password" name="user_pass" placeholder="Enter your password" id="password-field">
							<i class="fas fa-lock input-icon"></i>
							<i class="fas fa-eye password-toggle" id="password-toggle"></i>
						</div>
					</div>

					<div class="form-options d-none">
						<label class="checkbox-wrapper">
							<input class="checkbox-input" type="checkbox" value="remember-me" name="remember-me" checked id="remember-checkbox">
							<span class="checkbox-label">Remember me</span>
						</label>
						<a href="#" id='forget_password' class='forgot-link'>Forgot password?</a>
					</div>

					<button type="button" class="login-button" id='login_btn'>
						<i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
						Sign In
					</button>
				</form>
				
			</div>
			<div style="position: fixed; top: 20px; right: 20px; padding: 20px; width: 100px; height: 100px; z-index: 100;">
    <img src="img/kprm.jpg" alt="Logo" height="80px">
</div>

		</div>
		
	</div>

	<script src="<?= $base_url?>system/js/app.js"></script>
	<script src="<?= $base_url?>system/js/validate.js"></script>
	<script src="<?= $base_url?>system/js/bootbox.all.js"></script>
	<script src="<?= $base_url?>system/js/notify.min.js"></script>
	<script src="<?= $base_url?>system/js/shortcut.js"></script>

	<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-ko-KR.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    	<script src="<?= $base_url?>system/js/op.js"></script>
	<script>
		$(document).ready(function() {
			// Keep original Enter key functionality
			$(window).keydown(function(event) {
				if (event.keyCode == 13) {
					event.preventDefault();
					$("#login_btn").trigger('click');
				}
			});

			// Password toggle functionality
			$('#password-toggle').click(function() {
				const passwordField = $('#password-field');
				const passwordToggle = $('#password-toggle');
				
				if (passwordField.attr('type') === 'password') {
					passwordField.attr('type', 'text');
					passwordToggle.removeClass('fa-eye').addClass('fa-eye-slash');
				} else {
					passwordField.attr('type', 'password');
					passwordToggle.removeClass('fa-eye-slash').addClass('fa-eye');
				}
			});


			// Enhanced input focus effects
			$('.form-input').focus(function() {
				$(this).siblings('.input-icon').css('color', 'var(--primary-blue)');
			});

			$('.form-input').blur(function() {
				$(this).siblings('.input-icon').css('color', 'var(--text-muted)');
			});

			// Add subtle animations on load
			$('.login-form-container').css({
				'opacity': '0',
				'transform': 'translateY(20px)'
			}).animate({
				'opacity': '1'
			}, 600).animate({
				'transform': 'translateY(0px)'
			}, 600);

			$('.welcome-content').css({
				'opacity': '0',
				'transform': 'translateX(-20px)'
			}).delay(200).animate({
				'opacity': '1'
			}, 600).animate({
				'transform': 'translateX(0px)'
			}, 600);
		});
	</script>
</body>
</html>