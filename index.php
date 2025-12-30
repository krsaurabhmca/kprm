<?php 
require_once('function.php');

if (!isset($_SESSION['initiated'])) {
  echo "<script> window.location ='{$base_url}system/op_login.php' </script>";   
}
else if ($_SESSION['initiated']=="NO") {
	echo "<script> window.location ='{$base_url}system/system_process?task=logout' </script>";   
}
else{
    echo "<script> window.location ='{$base_url}system/op_dashboard.php' </script>";   
}

// File Structute

    //📄 .htaccess use for force SSL and remove .php extension 
    //📄 index.php
    //📄 functiom.php
    //📄 master_process.php 
    //📁 upload ( Stores All Document Uploaded by User)
    //📁 system ( Don't Tocuh it All Mendatory File inside)
    //📁 backup ( Stote User Created Databse .sql Backup File)
    //📁 install ( Stote All Medatory Setup to Run Application)

// End of File Structure
?>
