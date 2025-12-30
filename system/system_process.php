<?php
require_once('op_lib.php');
$_POST = post_clean($_POST);
$_GET = post_clean($_GET);
if (isset($_GET['task'])) {
	$task = xss_clean($_GET['task']);
	$user_id = (isset($_SESSION['user_id']))?$_SESSION['user_id']:'';
	if($task !='')
	{
		$data['task_name'] 	=	$task;
		
		if(isset($_REQUEST['table_name']))
		{
			$data['table_name']=$_REQUEST['table_name'];
			$data['user_id']=$user_id;
		}
		$adata['date_time'] = $current_date_time;
		$adata['user_id']   = $_SESSION['user_id']?? $_POST['user_name']??0;
		$adata['date_time'] = $current_date_time;
		$adata['task_name'] = $task;
		$adata['status']    =	'ACTIVE';
		$adata['request_data'] = json_encode($_POST);
		$adata['ip_address'] = get_ip();
		//print_r($adata);
		// SQL Query with prepared statement
        // $sql = "INSERT INTO activity_log (`status`, `created_at`, `created_by`, 
        //         `user_id`, `date_time`, `task_name`, `request_data`, `ip_address`) 
        //         VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)";
        
        // // Prepare bind execute
        // $stmt = mysqli_prepare($con, $sql);
        
        // if (!$stmt) {
        //     die("SQL Error: " . mysqli_error($con));
        // }
        
        // // Bind Parameters
        // $created_by = $adata['user_id']; // If created_by = user_id
        // mysqli_stmt_bind_param($stmt, "siissss",
        //     $adata['status'],
        //     $created_by,
        //     $adata['user_id'],
        //     $adata['date_time'],
        //     $adata['task_name'],
        //     $adata['request_data'],
        //     $adata['ip_address']
        // );
        
        // // Execute
        // if (!mysqli_stmt_execute($stmt)) {
        //     echo "Insert Error: " . mysqli_stmt_error($stmt);
        // }
        
        // mysqli_stmt_close($stmt);
	}
	switch ($task) {

		case "reset_opex":
			create_backup();
			$tres = get_all('op_table','*',array('status'=>"ACTIVE"));
			direct_sql('START TRANSACTION','set');
			foreach((array)$tres['data'] as $table)
			{
				$id = $table['id'];
				$table_name  = get_data('op_table',$id,'table_id')['data'];
				$res[] = delete_multi_data('op_master_table',array('table_name'=>$table_name));
				$res[] = delete_multi_data('op_menu',array('table_id'=>$id));
				$res[] = delete_multi_data('op_role',array('table_id'=>$id));
				$res[] = delete_data('op_table',$id);
			
				if(file_exists('../public/'.$table_name."_add.php"))
				{
					unlink('../public/'.$table_name."_add.php");
				}

				if(file_exists('../public/'.$table_name."_manage.php"))
				{
					unlink('../public/'.$table_name."_manage.php");
				}	
				$res[] = direct_sql("drop table $table_name " ,"set");

				$role_list = get_config('role_name');

				$res[] = direct_sql("update op_config set option_value = default_value","set");

				$sql1 ="delete from op_role where role_name not in ('DEV')";
				$res[]= direct_sql($sql1,"set");
				$sql2 ="delete from op_config where created_by > 0";
				$res[]= direct_sql($sql2,"set");
				$sql3 ="delete from op_user where user_type<>'DEV'";
			
        	}
			
			foreach($res as $r)
			{
				if($r['status']=='success')
				{
					direct_sql("commit","set");
					$f['status'] ='success';
					$f['msg'] ='Mission Success';
				}
				else{
					direct_sql("rollback","set");
					$f['status'] ='error';
					$f['msg'] ='Something Went Wrong';
					$f['data'][] =$r['msg'];
				}
			}
				$res[]= mysqli_query($con, "delete FROM op_master_table  where table_name not like 'op_%'");
				$res[]= mysqli_query($con, "TRUNCATE op_msg");
				$res[]= mysqli_query($con, "TRUNCATE op_menu");
				$res[]= mysqli_query($con, "TRUNCATE op_telegram");
				$res[]= mysqli_query($con, "TRUNCATE op_log");
			$f['url'] = "op_table";
			echo json_encode($f);
			break;


		case "create_opex":
			$tres = get_all('op_table','*',array('status'=>"ACTIVE"));
			foreach((array)$tres['data'] as $table)
			{
				$table_id = $table['id'];
				sync_table($table_id); 
				add_in_menu($table_id);
				
			}
			$res['url'] ='op_table';
			echo json_encode($res);
			break;

		case "add_table":
		    ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
			extract($_REQUEST);
			$table_name  = remove_space($table_name);
			$res = create_table($table_name);
			$table_id = $res['id'];
			add_in_menu($table_id);
			sync_table($table_id);
			$res['url'] ='op_table';
			echo json_encode($res);
			break;	
		
		case "delete_table":
			extract($_REQUEST);
			$table_name  = get_data('op_table',$id,'table_id')['data'];
			delete_multi_data('op_master_table',array('table_name'=>$table_name));
			delete_multi_data('op_menu',array('table_id'=>$id));
			delete_multi_data('op_role',array('table_id'=>$id));
			delete_data('op_table',$id);
		
			if(file_exists('../public/'.$table_name."_add.php"))
				{
					unlink('../public/'.$table_name."_add.php");
				}

			if(file_exists('../public/'.$table_name."_manage.php"))
    			{
    				unlink('../public/'.$table_name."_manage.php");
    			}	
				
			$res = direct_sql("drop table $table_name " ,"set");
			$res['msg'] = "All Deleted Successfully";
			$res['status'] = "success";
			$res['url'] = "op_table";
			echo json_encode($res);
			break;
		
		case "add_role":
			extract($_REQUEST);
			$new_role  = add_to_string(get_config('role_list'), $role_name);
			set_config('role_list',$new_role);
			$ct = create_role($role_name);
			if($ct>0)
			{
				$res['msg'] ='Role Added Sucessfully';
				$res['status'] ='success';
			}
			else
			{
				$res['msg'] ='Something Went Wrong or Role Already Created.';
				$res['status'] ='error';
			}
			echo json_encode($res);
			break;	
		
				
		case "get_column":
			extract($_REQUEST);
			$res = column_list($table_name)['data'];
			foreach((array) $res as $row)
			{
				echo "<option value='".$row['COLUMN_NAME']."'>". $row['COLUMN_NAME'] ."</option>";
			}
			break;	

		case "sort_column" :
			extract($_POST);
			$i =$ct =1;
			foreach((array) $columns as $column_id)
			{
				$res =update_data('op_master_table',array('display_id'=>$i), $column_id);
				if($res['status']=='success')
				{
					$ct++;
				}
				$i++;
			}
			$res['status'] = 'success';
			$res['msg'] = $ct .' Chanages found';
			echo json_encode($res);
			break;	

		case "sort_menu" :
			extract($_POST);
			$i =$ct =1;
			foreach((array) $columns as $column_id)
			{
				$res =update_data('op_menu',array('display_id'=>$i), $column_id);
				if($res['status']=='success')
				{
					$ct++;
				}
				$i++;
			}
			$res['status'] = 'success';
			$res['msg'] = $ct .' Chanages found';
			$res['url'] = 'op_menu_sorting';
			echo json_encode($res);
			break;	

		case "update_table":
			extract($_POST);
			unset($_POST['isedit']);
			if ($input_type == 'List-Dynamic' or $input_type == 'CheckList-Dynamic'  or $input_type == 'List-Where' ) {
				$_POST['input_value'] = implode(',', $_POST['dynamic_input']);
			}
			else{
				$_POST['input_value'] = $_POST['static_input'];	
			}
			unset($_POST['dynamic_input']);
			unset($_POST['static_input']);
			$default_value= (isset($_POST['default_value']))?$_POST['default_value']:null; 
			$input_type = $_POST['input_type'];
			$_POST['column_name'] = remove_space($column_name);
			$_POST['display_name'] = ($_POST['display_name']=='')?add_space($column_name):$_POST['display_name'];
			
			$fun_name  = ($isedit=='no')?'add_column':'update_column';
		
			    if($input_type=='Rs')
			    {
				    $fun_name($table_name, remove_space($column_name),'float(12,2)', $default_value);
			    }
			    else if ($input_type=='RTF') {
			        $fun_name($table_name, remove_space($column_name),'longtext');
			    }
			    else if ($input_type=='Date') {
			        $fun_name($table_name, remove_space($column_name),'date', $default_value);
			    }
			    else if ($input_type=='Datetime') {
			        $fun_name($table_name, remove_space($column_name),'datetime', $default_value);
			    }
			    else if ($input_type=='Number') {
			        $fun_name($table_name, remove_space($column_name),'int(11)', $default_value);
			    }
			    else if ($input_type=='Mobile' or $input_type=='Whatsapp' or $input_type=='Color' ) {
			        $fun_name($table_name, remove_space($column_name),'varchar(25)', $default_value);
			    }
			    else{
			      $fun_name($table_name, remove_space($column_name), 'varchar(128)', $default_value);  
			    }
			
			$res = update_data('op_master_table', $_POST, $id);
			
			$res['url']="op_table_manager?table_name=$table_name";
			echo json_encode($res);
			break;	
		
		case "upload" :
			$result =upload_img('uploadimg', 'rand','../upload', $_POST['size']);
			$result['src'] = $base_url."upload/".$result['id'];
			echo json_encode($result);
			break;
		
		case "multi_upload" :
			$res = multi_upload('uploadimg','../upload');
			$res['src'] ='upload';
			echo json_encode($res);
			break;
			
		case "master_update_data":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = 'op_manage_'.substr($table_name,3, strlen($table_name));	
			}
			else{
				$res['url'] = 'op_add_'.substr($table_name,3,strlen($table_name));
			}
			echo json_encode($res);
			break;

		case "update_profile":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			if($_POST['user_pass']!='')
			{
			$_POST['user_pass']= md5($_POST['user_pass']);
			}
			else{
			    unset($_POST['user_pass']);
			}
			$res = update_data($table_name, $_POST, $id);
			if($send_mail=='YES')
			{
			    if($isedit=='no')
			    {
				$msg ='<div style="max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
  <h1 style="text-align: center; color: #333333;">Welcome to '. $inst_name.'!</h1>
  <p style="color: #555555;">Thank you for signing up. Here are your login details:</p>
  <p style="color: #555555;"><strong>Username:</strong> <span style="color: #007bff;">'.$user_name.'</span></p>
  <p style="color: #555555;"><strong>Password:</strong> <span style="color: #007bff;">'.$user_pass.'</span></p>
  <p style="text-align: center;"><a href="'.$base_url.'" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;">Login Now</a></p>
</div>';
				// send_mail($user_email, "New User Registration",  $msg );
			    }
			    else{
			        if($user_pass !='')
			        {
			    $msg = "Thanks for Joining $inst_name , Your Login Details is as follows User Name : $user_name & Password : $user_pass";
				// send_mail($user_email, "Profile Updated",  $msg );    
			        }
			    }
			}
			echo json_encode($res);
			break;
		
		
		case "add_data":
			extract($_POST);
			$res = update_data($table_name, array($col=>$value), $id);
			echo json_encode($res);
			break;
	
				
	case "update_master_permission":
		extract($_POST);
		
		if($column=='is_unique')
		{
		  $col_name =  get_data('op_master_table',$id,'column_name')['data'];
		  $tab_name =  get_data('op_master_table',$id,'table_name')['data'];
		  if($status=='YES')
		  {
		  $sql ="ALTER TABLE $tab_name ADD UNIQUE($col_name)";
		  direct_sql($sql,"set");
		  }
		  else{
		       $sql ="ALTER TABLE $tab_name drop INDEX $col_name";
		      direct_sql($sql,"set");
		  }
		    $data = array($column=>$status);
	        $res = update_data($table, $data, $id); 
		}
		else{
		   $data = array($column=>$status);
	       $res = update_data($table, $data, $id); 
		}
		echo json_encode($res);
		break;	

	case 'sync_table':
		if(isset($_GET['table_name']) and $_GET['table_name']!='')
		{
			$tables['data'][] = $_GET['table_name'];
			
		}else{
			$tables  = table_list();	
		}

		foreach($tables['data'] as $table)
		{
			$table_id  = get_data('op_table',$table,'id','table_id')['data'];
			check_table($table);
			sync_table($table_id);
		}
		break;


        case "verify_login":
        
            extract($_POST);
            $user_name = strtolower(preg_replace("/[^a-zA-Z0-9.@]+/", "_", $user_name));
        
            // Fetch user first (even if password wrong)
            $user = getUserByUsername($con, $user_name);
        
            if ($user) {
        
                // Check if blocked
                if ($user['user_status'] === 'BLOCKED') {
                    echo json_encode([
                        "status" => "error",
                        "msg"    => "Your account is blocked due to multiple invalid attempts."
                    ]);
                    break;
                }
        
                // Verify password
                if ($user['user_pass'] === md5($user_pass)) {
        
                    // SUCCESS → Reset login attempts
                    resetLoginAttempt($con, $user['id']);
        
                    // Your existing success flow
                    $res = get_all('op_user', '*', array(
                        'user_name' => $user_name,
                        'user_pass' => md5($user_pass),
                        'user_status' => 'ACTIVE'
                    ));
        
                    if ($res['count'] == 1) {
        
                        session_regenerate_id();
                        if (trim(get_data('op_user', remove_space($user_name), 'allow_otp', 'user_name')['data']) == 'YES') {
        
                            // OTP Flow
                            $otp = rand(1000, 9999);
                            $salt = rnd_str(6);
                            $_SESSION['initiated'] = "NO";
                            $sms = "Your App Login OTP is $otp";
                            send_msg($user['user_mobile'], $sms, "1207166936475956518");
                            $ref = encode("otp=$otp&user_name=$user_name&salt=$salt");
        
                            $res['otp'] = $otp;
                            $res['url'] = $base_url . "system/op_otp?ref=$ref";
        
                        } else {
        
                            // Normal login
                            $_SESSION['initiated'] = "YES";
                            $_SESSION['bine_token'] = $token = md5(uniqid(rand(), TRUE));
                            $_SESSION['user_id'] = $user_id = $user['id'];
                            $_SESSION['user_name'] = $user['user_name'];
                            $_SESSION['user_type'] = $user['user_type'];
        
                            update_data('op_user', ['token' => $token, 'status' => 'ACTIVE'], $user_id);
        
                            $res['url'] = $base_url . 'system/op_dashboard';
                        }
        
                        echo json_encode($res);
                        break;
                    }
        
                } else {
                    // ❌ WRONG PASSWORD – Increase attempt
        
                    $attempt = $user['login_attempt'] + 1;
                    updateLoginAttempt($con, $user['id'], $attempt);
        
                    if ($attempt >= 3) {
                        blockUser($con, $user['id']);
                        echo json_encode([
                            "status" => "error",
                            "msg" => "Your account is blocked after 3 invalid attempts."
                        ]);
                    } else {
                        echo json_encode([
                            "status" => "error",
                            "msg" => "Invalid login. Attempt $attempt of 3"
                        ]);
                    }
                    break;
                }
            }
        
            // If no user found
            echo json_encode([
                "status" => "error",
                "msg" => "Invalid Username"
            ]);
            break;

		
	case "verify_otp": // Delete Any Data From Table 
		extract($_POST);
		$table_name ='op_user';
		if ($_POST['sotp'] == $_POST['uotp']) {
			$res = get_all('op_user', '*', array('user_name' => remove_space($user_name),'user_status'=>'ACTIVE'));
		
			session_regenerate_id();
			$_SESSION['initiated'] = "YES";
			$_SESSION['bine_token'] = $token  = md5(uniqid(rand(), TRUE));
			$_SESSION['token_time'] = time();
			$_SESSION['user_agent'] = 'bine_' . $_SERVER['HTTP_USER_AGENT'];
			$_SESSION['user_id'] = $user_id = $res['data'][0]['id'];
			$_SESSION['user_name'] = $res['data'][0]['user_name'];
			$mobile = $res['data'][0]['user_mobile'];
			$_SESSION['user_type'] = $user_type = $res['data'][0]['user_type'];
			update_data('op_user', array('token' => $token, 'status' => 'ACTIVE'), $user_id);					
			$res['url'] = $base_url.'index';
			$res['msg'] =  "OTP Verified Successfully";
			session_regenerate_id();
		} else {
			$res['status'] =  "error";
			$res['msg'] =  "OTP Not Matched";
			$res['url'] =  $_SERVER['HTTP_REFERER'];
		}
		echo json_encode($res);
		break;


		case "login_as":
			extract($_POST);
			$user_name = strtolower(preg_replace("/[^a-zA-Z0-9.@]+/", "_", $user_name));
			$res = direct_sql("select * from op_user where user_name ='$user_name' and user_pass ='$user_pass' and status not in('BLOCK','DELETED','AUTO')");
			if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV') {
				$outh = 'yes';
			} else {
				$outh = 'no';
			}
			if ($res['status'] == 'success' and $res['count'] == 1) {
				$uid = $res['data'][0]['id'];
				$cuser =get_data('op_user',$user_id)['data'];
				$udata = array('status' => 'ACTIVE', 'token' => $cuser['token']);
				$result = update_data('op_user', $udata, $uid, 'id');
				// 	$_SESSION['login_type'] ='ADMIN';
				// 	$_SESSION['old_user_id'] =   $_SESSION['user_id'];
				$_SESSION['admin_data'] = array('user_id' => $_SESSION['user_id'], 'user_type' => 'ADMIN', 'user_outh' => $outh);
				$_SESSION['user_id']    = $res['data'][0]['id'];
				$_SESSION['user_type']  = $res['data'][0]['user_type'];
				$_SESSION['user_name']  = $res['data'][0]['user_name'];
				//setcookie("username", $_SESSION['user_name'], time()+3600, "/", "",  0);
			} else {
				$result['id'] = 0;
				$result['status'] = 'fail';
				$result['msg'] = 'system is already Login';
			}
			echo json_encode($result);
			break;


		case "change_password": // Change Password of Logged in User
			$current_pass = md5($_POST['current_password']);
			$new_password = md5($_POST['new_password']);
			$where = array('id' => $user_id, 'user_pass' => $current_pass);
			$res = update_multi_data('op_user', array('user_pass' => $new_password), $where);
// 			$notice  = "$user_name changed their password";
			echo json_encode($res);
			break;
		
		case "update_password": // From Reset Link
			extract($_POST);
			$new_password = md5($_POST['new_password']);
			$where = array('id' => $id, 'user_name' => $user_name);
			$res = update_multi_data('op_user', array('user_pass' => $new_password), $where);
			if($res['status']=='success')
			{
				$res['msg'] ='Password Chnaged Successfully';
				$res['url'] ='op_login';
			}
			echo json_encode($res);
			break;

		case "master_delete": // Delete Any Data From Table 
			extract($_POST);
			if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV' ) {
				$searchdata  = get_data($table, $id);
				
				if ($searchdata['count'] > 0) {
					if($table=='op_master_table')
					{
						$col_name = $searchdata['data']['column_name'];
						$table_name = $searchdata['data']['table_name'];
						remove_column($table_name, $col_name);
					}
					$res = delete_data($table, $id, $pkey);
				}
			} else {
				$res = array('msg' => "Don't  have permission", 'status' => 'error');
			}
			echo json_encode($res);
			break;

			case "master_delete_multiple": // Delete Selected Record Any Data From Table 
				extract($_POST);
				if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV' ) {
					
					foreach($sel_id as $id)
					{
					$searchdata  = get_data($table_name, $id);
						if ($searchdata['count'] > 0) {
							$info[] = delete_data($table_name, $id);
						}
					}
					$res['status'] ='success';
					$res['msg'] = count($sel_id) ." record(s) deleted sucessfully";
					$res['data'] =$info;
				} else {
					$res = array('msg' => "Don't  have permission", 'status' => 'error');
				}
				echo json_encode($res);
				break;
		
				case "master_remove_multiple": // Delete Selected Record Any Data From Table 
					extract($_POST);
					if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV' ) {
						
						foreach($sel_id as $id)
						{
						$searchdata  = get_data($table_name, $id);
							if ($searchdata['count'] > 0) {
								$info[] = update_data($table_name, array('status'=>'DELETED'), $id);
							}
						}
						$res['status'] ='success';
						$res['msg'] = count($sel_id) ." record(s) removed sucessfully";
						$res['data'] =$info;
					} else {
						$res = array('msg' => "Don't  have permission", 'status' => 'error');
					}
					echo json_encode($res);
					break;

		case "master_remove": // Delete Any Data From Table 
			extract($_POST);
			$res = remove_data($table, $id);
			echo json_encode($res);
			break;

		case "add_template": // SMS SMS any TIME
			extract($_POST);
			$_POST['status'] = 'ACTIVE';
			$res = insert_data('sms_template', $_POST);
			echo json_encode($res);
			$notice  = "New SMS Template is added by {$_SESSION['user_name']}";
			create_log($notice);
			break;

		case "send_sms" : // SMS any TIME
		   	extract($_POST);
		   	$templateid = get_data('sms_template',$template_id, 'template_id')['data'];
		   	$ctype = get_data('sms_template',$template_id, 'content_type')['data'];
			$res = send_msg($mobile,$sms,$templateid,$ctype);
			echo json_encode($res);
			break;

		
		case "master_block": // BLOCK Any Data From Table 
			extract($_POST);
			//print_r($_POST);
			$bdata = array('status' => 'BLOCK');
			$res = update_data($table, $bdata, $id, $pkey);
			$notice  = "Record Id $id of $table is blocked by {$_SESSION['user_name']}";
			create_log($notice);
			echo json_encode($res);
			break;
		
		case "active_block": // Active Block Any Data From Table 
			extract($_POST);
			$ndata = array('status' => $status);
			$res = update_data($table, $ndata, $id);
			echo json_encode($res);
			break;
		
		case "logout":
		case "auto_logout":
			$rtype = 'direct';
			extract($_POST);
			if (isset($_SESSION['bine_token']) && $_SESSION['bine_token'] != '') {
			    $user_id = $_SESSION['user_id'];
				$user_type = $_SESSION['user_type'];
				$result = update_data('op_user', array('token' => '', 'status' => 'LOGOUT'), $user_id);
				if ($result['status'] == 'success') {
					unset($_SESSION['user_name']);
					unset($_SESSION['user_type']);
					unset($_SESSION['user_id']);
					unset($_SESSION['bine_token']);
					session_destroy();
					$url = $base_url.'system/op_login';
				} else {
				    // $url = $base_url.'system/op_dashboard';
				    $url = $base_url.'system/op_login';
				}
			} else {
				$url = $base_url.'system/op_login';
				
			}
			if ($rtype == 'AJAX') {
				$result['url'] = $url;
				echo json_encode($result);
			} else {
				$result['url'] = $base_url.'system/op_login';
				echo "<script> window.location ='{$base_url}system/op_login.php' </script>";   
				json_encode($result);
			}
			break;

		case "forget_password":
			$user_name  = $_POST['user_name'];
			$sql = "select * from op_user where user_name ='$user_name' and status not in ('AUTO','DELETED')";
			$res = direct_sql($sql);
			if ($res['count'] > 0) {
				$id = $res['data'][0]['id'];
				$user_type = $res['data'][0]['user_type'];
				$email = $res['data'][0]['user_email'];
				$mobile = $res['data'][0]['user_mobile'];
				$name = $res['data'][0]['full_name'];

				$link  = encode("user_name=$user_name&user_id=$id");
				$rlink = $base_url."system/op_reset_password?link=$link";
			//	$sms = "<p>Dear  $name  \n Click on below link $rlink  \n to change password. \n Kindly change after login  $inst_name </p>";
				$sms = '<div class="container" style="max-width: 600px; margin: 50px auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
        <h2 style="color: #333;">Forgot Password?</h2>
        <p style="color: #555;">Dear '.$name.', We received a request to reset your password. If you didnot make this request, you can ignore this email.</p>
        <p style="color: #555;">If you did request a password reset, click the link below:</p>
        <a class="reset-link" href="'.$rlink.'" style="display: inline-block; padding: 10px 20px; background-color: #007BFF; color: #fff; text-decoration: none; border-radius: 5px;">Reset Password</a>
        <p style="color: #555;">This link will expire in 24 hours for security reasons.</p>
        <p style="color: #555;">If you have any issues, please contact our support team.</p>
        <br>
        Regards<br>
        <b>'. $inst_name.'
    </div>';
				// send_mail($email, "Password Recovery mail from $inst_name ", $sms);
				//bulk_sms($mobile,$sms);
				$data['id'] = $id;
				$data['status'] = 'success';
				$data['msg'] = "Password Reset Link Successfully Send to $email";
			} else {
				$data['id'] = 0;
				$data['status'] = 'error';
				$data['msg'] = 'No any user exist with this ID. Try Again';
			}
			echo json_encode($data);
			break;

   
		case "bulk_import":
			extract($_POST);
			echo "<pre>";
			$res = csv_import($table, $pkey);
			//print_r($res);
			echo "<script> window.location='".$base_url."system/bulk_import?res=".json_encode($res)."' </script>";
			break;

		case "import_sql":
			if($_SESSION['user_type']=='DEV')
			{
				echo $target_file = date('ymdhis').".sql";
				if (move_uploaded_file($_FILES['file']["tmp_name"], "../upload/" . $target_file)) {
					$res['msg'] = "The file " . basename($_FILES['file']["name"]) . " has been uploaded.";
					$res['id'] = $target_file;
					$res['status'] = 'success';
					$res = direct_sql_file("../upload/".$target_file);
				} else {
					$res['msg'] = "Sorry, there was an error uploading your file.";
				}
			}
			echo "<script> window.location='".$base_url."system/op_import' </script>";
			//echo json_encode($res);
			break;

		case "bulk_export":
			if ($_SESSION['user_type'] == 'ADMIN' or $_SESSION['user_type'] == 'DEV') {
				$status = $_GET['status'];
				csv_export($_REQUEST['table']);
			}
			break;

		case "update_user":
			$_POST['user_name'] = remove_space($_POST['user_name']);
			$_POST['user_pass'] = md5($_POST['user_pass']);
			$res = update_data('op_user', $_POST, $_POST['id']);
			$res['url'] = 'add_user';
			$notice  = "User Info of {$_POST['user_name']} updated  by {$_SESSION['user_name']}";
			create_log($notice);
			echo json_encode($res);
			break;

        case "send_whatsapp":
			$wa_sms = urlencode($wa_sms);
			$wa_link ="http://148.251.129.118/wapp/api/send?apikey={$wa_api_key}&mobile=$student_whatsapp&msg=$wa_sms";
			$st = api_call($wa_link);
			//wa_text($student_mobile, $sms);
			$notice  = "Enquiry data updated by {$_SESSION['user_name']}";
			create_log($notice);
			echo json_encode($res);
			break;

	
    	case "save_photo" :
				$baseFromJavascript = $_POST['student_photo']; //your data in base64 'data:image/png....';
                $base_to_php = explode(',', $baseFromJavascript);
                $data = base64_decode($base_to_php[1]);
                $file_name = date('ymdhis')."_".rnd_str(5).".png";	
                $filepath = "upload/image.png "; //.$file_name; // or image.jpg
                file_put_contents($filepath,$data);
                rename($filepath, 'upload/'.$file_name);
                $res['msg'] = "The file ". $file_name. " has been uploaded.";
                $res['id'] = $file_name;
				$res['status'] ='success';
				echo json_encode($res);
				break;

		case "add_config":
			unset($_POST['table_name']);
			unset($_POST['isedit']);
			extract($_POST);
			$_POST['option_name'] = remove_space($_POST['option_name']);
			$res = update_data('op_config', $_POST,$_POST['id']);
			if($option_name=='role_list')
			{
				$role_arr = explode(',',$option_value);
				foreach($role_arr as $role_name)
				{
					$mdata = array('table_name'=>'op_menu','column_name'=>remove_space($role_name),'display_name'=>$role_name,'input_type'=>'Permission','show_in_table'=>'YES','display_id'=>99);
					$ires = insert_data('op_master_table',$mdata);
					if($ires['status']=='success')
					{
					add_column('op_menu',remove_space($role_name));
					$table_id  = get_data('op_table','op_menu','id','table_id')['data'];
					sync_table($table_id);
					}
				}
			}
			$res['url'] = 'op_config_manage';
			echo json_encode($res);
			break;


		case "update_config":
			extract($_POST);
			$res = update_data('op_config', array('option_value'=>$option_value),$option_name, 'option_name');
			echo json_encode($res);
			break;

		case "update_settings":
			$alldata = $_POST;
			$ct = 0;
			$x=array();
			foreach((array) $alldata as $key=>$value)
			{
				$ures = update_data('op_config', array('option_value'=>$value), $key,'option_name');
				$x[] =$ures;
				if($ures['status'] =="success")
				{
					$ct++;
				}
			}
			$res['sql'] = $x;
			$res['msg'] = $ct . " Update found";
			$res['status'] = "success";
			echo json_encode($res);
			break;

		case "reset_config":
			$sql ="update op_config set option_value = default_value";
			$res = direct_sql($sql,'set');
			echo json_encode($res);
			break;
		

		case "add_menu":
			extract($_POST);
			unset($_POST['isedit']);
			unset($_POST['table_name']);
			//$_POST['table_id'] = get_data('op_table',$table_id,'table_id')['data']; // Get Table Name
		    $_POST['display_id'] = !empty($_POST['display_id']) ? $_POST['display_id'] : 0;

			$res = update_data($table_name, $_POST, $id);
			if ($isedit == "yes") {
				$res['url'] = 'op_menu_manage';	
			}
			else{
				$res['url'] = 'op_menu_add';
			}
			echo json_encode($res);
			break;
		
		case "send_chat":
			extract($_POST);
			$res = insert_data('op_msg', array('message'=>$message,'to_user'=>$to_user));
			echo json_encode($res);
			break;
			
		case "bulk_menu_update":
			extract($_POST);
			foreach($sel_id as $id)
			{
			$res = update_data('op_menu', array('parent'=>$parent), $id);
			}
			echo json_encode($res);
			break;
			
		case "quick_update_data": // Double Click to Edit and Save
			extract($_POST);
			unset($_POST['table_name']);
			unset($_POST['value']);
			unset($_POST['column']);
			$_POST[$column] =$value;
			$_POST = array_to_string($_POST);
			$res = update_data($table_name, $_POST, $id);
			echo json_encode($res);
			break;
		
				
		case  "get_state":
				extract($_REQUEST);
				$str ='';
				$slist = state_list();
				foreach ((array)$slist as $s_name) {
					$str .= "<option value='" . $s_name . "'>" . $s_name . "</option>";
				}
				echo $str;
				break;

		case  "get_dist":
			extract($_REQUEST);
			$str ='';
			$dlist = district_list($state);
			foreach ((array)$dlist as $d_name) {
				$str .= "<option value='" . $d_name . "'>" . $state ."->". $d_name . "</option>";
			}
			echo $str;
			break;

		case  "get_block":
			extract($_REQUEST);
			$str ='';
			$blist = block_list($district);
			foreach ((array)$blist as $b_name) {
				$str .= "<option value='" .  $b_name . "'>" . $district ."->".$b_name . "</option>";
			}
			echo $str;
			break;
			
	    // WHATSAPP QR API LIST
	    case "create_instance": // CREATE NEW INSTANCE ID 
	        $res = create_instance();
			echo json_encode($res);
			break;
		
    	case "get_waqr": // CREATE NEW INSTANCE ID 
            $res = wa_qr();
    		echo json_encode($res);
    		break;
			
			
		default:
			echo "<script> alert('Invalid Action'); window.location ='" . $_SERVER['HTTP_REFERER'] . "' </script>";
	}
}
?>