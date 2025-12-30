<?php require_once('function.php'); 


?>
<!DOCTYPE html>
<html lang="en">
<!-- Added by HTTrack -->
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<!-- /Added by HTTrack -->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="NIDHI COMPANY SOFTWARE">
	<meta name="author" content="OfferPlant">
	<meta name="keywords" content="">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<!--<link rel="canonical" href="pages-sign-in.html" />-->

	<title><?= @$inst_name ?> </title>

	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
	<!-- Choose your prefered color scheme -->
	<!-- <link href="css/light.css" rel="stylesheet"> -->
	<!-- <link href="css/dark.css" rel="stylesheet"> -->

	<!-- BEGIN SETTINGS -->
	<!-- Remove this after purchasing -->
	<link class="js-stylesheet" href="css/light.css" rel="stylesheet">
	<link class="js-stylesheet" href="<?= $base_url ?>css/op.css" rel="stylesheet">
	<script src="js/settings.js"></script>
	<style>
		body {
			font-family: 'Roboto', sans-serif;
			opacity: 0;
		}
	</style>
	<!-- END SETTINGS -->

<!--
  HOW TO USE: 
  data-theme: default (default), dark, light, colored
  data-layout: fluid (default), boxed
  data-sidebar-position: left (default), right
  data-sidebar-layout: default (default), compact
-->

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
	<main class="d-flex w-100 h-100">
		<div class="container d-flex flex-column">
			<div class="row vh-100">
				<div class="col-sm-10 col-md-8 col-lg-5 mx-auto d-table h-100">
					<div class="d-table-cell align-middle">

						<div class="card">
							<div class="card-body">
								

								<div class="m-sm-4">
									<div class="text-center">
										<img src="img/logo.png" alt="Logo" class="img-fluid" width="132" height="132" />
											<h1 class="h2"><?= $full_name; ?></h1>
									</div>
<?php
if(isset($_GET['ref']) and $_GET['ref'] !="")
{
	$data = decode($_GET['ref']);
	$sotp =  $data['otp'];
	$user_name =  $data['user_name'];
}
?>
									<form id='update_frm' action='verify_otp' method="post" type="system">
									<div class='alert alert-primary p-2'>
									📩 OTP send successfully on your mail & mobile.
									</div>
										<div class="mb-3">
											<label class="form-label">Enter OTP</label>
											<input class="form-control form-control-lg" type="hidden" id='sotp' name='user_name' value="<?= $user_name?>" />
											<input class="form-control form-control-lg" type="hidden" id='sotp' name ='sotp' value="<?= $sotp?>" readonly />
											<input class="form-control form-control-lg" type="number" maxlength='4' minlength="4" id='uotp' required name ='uotp' placeholder="Enter OTP " />
										</div>
                                    </form>	
										<div class="text-center mt-3">
											<span class="btn btn-lg btn-primary" id='update_btn'>Verify OTP</span>
											<a href="login" class="btn btn-border border-dark" >Resend OTP</a>
										</div>
									
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
<script src="<?= $base_url?>js/app.js"></script>
<script src="<?= $base_url?>js/validate.js"></script>
<script src="<?= $base_url?>js/notify.min.js"></script>
<script src="<?= $base_url?>js/shortcut.js"></script>
<script src="<?= $base_url?>js/bootbox.all.js"></script>
<script src="<?= $base_url?>js/op.js"></script>

<script>
    $(document).ready(function() {
      $(window).keydown(function(event) {
        if (event.keyCode == 13) {
          event.preventDefault();
		  $("#login_btn").trigger('click');
        }
      });
    });

</script>

</body>
</html>