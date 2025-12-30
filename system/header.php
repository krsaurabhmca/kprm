<?php require_once('../function.php'); 
$_POST = post_clean($_POST);
$_GET = post_clean($_GET);

if (!isset($_SESSION['initiated'])) {
  echo "<script> window.location ='{$base_url}system/op_login.php' </script>";   
}
else if ($_SESSION['initiated']=="NO") {
	echo "<script> window.location ='{$base_url}system/system_process?task=logout' </script>";   
}
else{
  $user_id = $_SESSION['user_id'];
  $udata = get_data('op_user', $user_id)['data'];
  $user_name = $udata['user_name'];
  $user_photo = $base_url.'upload/'.$udata['user_photo'];
  $user_type = $udata['user_type'];
  session_regenerate_id();
  $ut = get_data('op_user',$user_id,'token')['data'];
  if(isset($_SESSION['dev_mode']) and $_SESSION['dev_mode'] =='LIVE')
  {
      if ($_SESSION['bine_token']!=$ut) {
       	echo "<script> window.location ='{$base_url}system/system_process?task=logout' </script>";   
      }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Best Platform form any type of web applicatedtion development">
	<meta name="author" content="OfferPlant">
	<meta name="keywords" content="School Mangment, ERP, CRM, Lead Genration, AI Building">

	<link rel="preconnect" href="https://fonts.gstatic.com/">
	<link rel="shortcut icon" href="<?= $base_url ?>img/icons/icon-48x48.png" />
	
    <link rel="manifest" href="<?= $base_url ?>manifest.json">
    <meta name="theme-color" content="#384350">

	<title><?= @$inst_name; ?> </title>
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
	<!-- Choose your prefered color scheme -->
	 <link href="<?= $base_url ?>system/css/light.css" rel="stylesheet"> 
	 <link href="<?= $base_url ?>system/css/op.css" rel="stylesheet"> 
	 <!--<link href="css/dark.css" rel="stylesheet"> -->

	<!-- BEGIN SETTINGS -->
	<!--<script src="<?= $base_url ?>system/js/settings.js"></script>-->
	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
	<!-- END SETTINGS -->
</head>
<!--
  HOW TO USE: 
  data-theme: default (default), dark, light, colored
  data-layout: fluid (default), boxed
  data-sidebar-position: left (default), right
  data-sidebar-layout: default (default), compact
-->
<style>
 /* Content Division */
 .wrapper .main .content{
  padding-left:20px;
  padding-right:20px;
  padding-top:20px;
 }

</style>
<?php if($user_type == 'ADMIN' or $user_type=='DEV'){ ?>
<style>
    .sidebar {
        /*visibility: hidden;*/
        visibility: visible;
    }
</style>
<?php }else{ ?>
<style>
    .sidebar {
        visibility: hidden;
    }
    .simplebar-content{
        display: none;
    }
</style>
<script>
// document.addEventListener("DOMContentLoaded", function () {
//     // Collapse sidebar
//     var sidebar = document.getElementById("sidebar");
//     if (sidebar) {
//         sidebar.classList.add("collapsed");
//         sidebar.style.visibility = 'visible';
//     }

//     // Hide toggle
//     var toggle = document.querySelector('.sidebar-toggle.js-sidebar-toggle');
//     if (toggle) {
//         toggle.style.display = 'none';
//     }
//     // Hide preloader
//     var preloader = document.getElementById("preloader");
//     if (preloader) {
//         preloader.style.opacity = '0';
//         setTimeout(function () {
//             preloader.style.display = 'none';
//         }, 300); 
//     }
// });
</script>
<?php } ?>
