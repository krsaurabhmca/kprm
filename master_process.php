<?php
require_once('function.php');
$_POST = post_clean($_POST);
$_GET = post_clean($_GET);
if (isset($_GET['task'])) {
	$task = xss_clean($_GET['task']);
	$user_id = (isset($_SESSION['user_id']))?$_SESSION['user_id']:'';
	

	switch ($task) {
        
		case "master_update_data":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;
        
       case "task_update":
           	extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$res = update_data($table_name, $_POST, $id);
			get_task_meta_from_template( $id, $positive_format);
			get_task_meta_from_template( $id, $negative_format);
			get_task_meta_from_template( $id, $cnv_format);
			if ($isedit == "yes") {
				$res['url'] = $table_name.'_manage';	
			}
			else{
				$res['url'] = $table_name.'_add';
			}
			echo json_encode($res);
			break;
        
        
		default:
			echo "<script> alert('Invalid Action'); window.location ='" . $_SERVER['HTTP_REFERER'] . "' </script>";
	}
}
