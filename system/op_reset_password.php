<?php require_once('op_lib.php');

if(isset($_GET['link']) and $_GET['link']!='')
{
    $data  = decode($_GET['link']);
    extract($data);
}


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
	<meta name="description" content="">
	<meta name="author" content="OfferPlant">
	<meta name="keywords" content="">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	
	<title><?= @$inst_name ?> </title>

	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">

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
                                            <hr>
                                          
									</div>
                                    <div class='alert alert-danger p-2'> ⚠️ Reset Password</div>
                                <form  id='update_frm' action ='update_password' method='post' type='system'>
                                <input class="form-control form-control-lg" type="hidden" name="id" value='<?= $user_id; ?>' />
                                <input class="form-control form-control-lg" type="hidden" name="user_name" value='<?= $user_name; ?>'  />
                                    

                                <div class="mb-1">
                                    <label>New Password</label>
                                    <input class="form-control" type='password' id='new_password' name='new_password' required minlength='5'>
                                    <span id="StrengthDisp" class="badge displayBadge badge-light text-light float-right mt-2 p-1">Weak</span>
                                </div>
    
                                <div class="mb-0">
                                    <label>Confirm Password <span id='matched' class='badge badge-light'> </span> </label>
                                    <input class="form-control" id='repeat_password' type='password' required minlength='5'>
    
                                </div>
										
										<div class="text-center mt-3">
											<button class="btn btn-lg btn-primary" id='update_btn'>Change Password</button>
											
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
<script src="js/app.js"></script>
<script src="js/validate.js"></script>
<script src="js/bootbox.all.js"></script>
<script src="js/notify.min.js"></script>
<script src="js/shortcut.js"></script>
<script src="js/op.js"></script>

<script>
    $(document).on('keyup', "#repeat_password", function() {
        var a = $("#new_password").val();
        var b = $("#repeat_password").val();
        if (a == b) {
            $("#matched").html("<b class ='text-success mt-1'> Matched </b>");
            $("#update_btn").attr("disabled", false);
        } else {
            $("#matched").html("<b class ='text-danger mt-1'> Not Matched </b>");
            $("#update_btn").attr("disabled", true);
        }
    });
    // timeout before a callback is called

    let timeout;

    // traversing the DOM and getting the input and span using their IDs

    let password = document.getElementById('new_password')
    let strengthBadge = document.getElementById('StrengthDisp')

    // The strong and weak password Regex pattern checker

    let strongPassword = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})')
    let mediumPassword = new RegExp('((?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{6,}))|((?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=.{8,}))')

    function StrengthChecker(PasswordParameter) {
        // We then change the badge's color and text based on the password strength

        if (strongPassword.test(PasswordParameter)) {
            strengthBadge.style.backgroundColor = "green"
            strengthBadge.textContent = 'Strong'
        } else if (mediumPassword.test(PasswordParameter)) {
            strengthBadge.style.backgroundColor = 'skyblue'
            strengthBadge.textContent = 'Medium'
        } else {
            strengthBadge.style.backgroundColor = 'orangered'
            strengthBadge.textContent = 'Weak'
        }
    }

    // Adding an input event listener when a user types to the  password input 

    password.addEventListener("input", () => {

        //The badge is hidden by default, so we show it

        strengthBadge.style.display = 'block'
        clearTimeout(timeout);

        //We then call the StrengChecker function as a callback then pass the typed password to it

        timeout = setTimeout(() => StrengthChecker(password.value), 500);

        //Incase a user clears the text, the badge is hidden again

        if (password.value.length !== 0) {
            strengthBadge.style.display != 'block'
        } else {
            strengthBadge.style.display = 'none'
        }
    });
</script>
</body>
</html>