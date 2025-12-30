<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('op_config.php');
use PHPMailer\PHPMailer\PHPMailer;
global $db_name;
$con = mysqli_connect($host_name, $db_user, $db_password, $db_name)
	or die("Unable to Connect, Check the Connection Parameter. " . mysqli_error($con));

// === OFFERPLANT MASTER FUNTION FOR EVERY WHERE ==== //

//  INSERT ( insert_row, insert_data, insert_html )
// 	UPDATE (update_date, update_multi_data)
// 	REMOVE (remove_data, remove_multi_data)
// 	DELETE (delete_data, delete_multi_data)
// 	COPY (copy_table)
//	FETCH	(get_data, get_all, get_multi_data, get_not, direct_sql)
//	CRYPTO (encode, decode)
//	STRING (rnd_str, add_space, remove_space)
//	SECURITY (xss_clean, post_clean)r
//	ACCESS	(verify, verify_request)
//	EXCEL 	(csv_import, csv_export)
//	YOUTUBE ( ytid, get_vid)
// 	COMM	(send_msg, send_sms, rtfmail ,wasend )
//	API 	(api_call)
//	QRcode	(qrcode)
//	IMAGE 	(uploadimg, remote_file_size, remote_file_exists)
// 	DATABASE Sturucture (table_list, Create_table, direct_sql_file,add_column, remove_column)
// 	CONFIG 	(set_config, update_config,delete_config, all_config,all_back_images, get_config)
//	HTML 	(input_text, input_date, btn_view, btn_about, btn_edit, btn_delete, create_data_table, create_form,  ) 
//	UI DROPDOWN (dropdown, dropdown_list, dropdown_list_multiple, dropdown_list_where,  create_list)

// Create Table with Basic Structure  

function create_table($table_name)
{
	$table_name = remove_space($table_name);
	global $con;
	$sql1 = "CREATE TABLE IF NOT EXISTS $table_name (
	  id int(11) NOT NULL,
	  status varchar(25) DEFAULT NULL,
	  created_at timestamp NULL DEFAULT NULL,
	  created_by int(11) DEFAULT NULL,
	  updated_at timestamp NULL DEFAULT NULL,
	  updated_by int(11) DEFAULT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

	$res[] = mysqli_query($con, $sql1) or die("Error In Createting Table : " . mysqli_error($con));

	$sql2 = "ALTER TABLE $table_name  ADD PRIMARY KEY (id)";
	$res[] = mysqli_query($con, $sql2) or die("Error In Assigning Primary Key : " . mysqli_error($con));

	$sql3 = " ALTER TABLE $table_name  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";

	$res[] = mysqli_query($con, $sql3) or die("Error In Creating Auto Increment ID  : " . mysqli_error($con));

	$sql4 = "ALTER TABLE $table_name CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
	$res[] = mysqli_query($con, $sql4) or die("Error In Assign Updated at Default Value as Current Timestamp : " . mysqli_error($con));
	
	$res1 = insert_data('op_table', array('table_id'=>$table_name,'status'=>'ACTIVE'));
	return $res1;
}

// Convert Any Table into Opex table 
function check_table($table_name)
{
    global $con;
   
   // Removing Auto Increment Features
    $sql61 = "SHOW COLUMNS FROM $table_name WHERE Extra = 'auto_increment'";
    $res61 = direct_sql($sql61);
    if($res61['count']>0)
    {
        $col_name = $res61['data'][0]['Field'];
        $sql61_1 = "ALTER TABLE $table_name MODIFY $col_name INT ";
        $sql61_1 = mysqli_query($con, $sql61_1) or die("Error In Removing Auto increment key : ". mysqli_error($con));
       
    }
    
    $sql60 = "SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'";
    $res60 = direct_sql($sql60);
    if($res60['count']>0)
    {
        $res60 =  "ALTER TABLE $table_name DROP PRIMARY KEY";
        $res60_1 = mysqli_query($con, $res60) or die("Error In Removing Primary key : ". mysqli_error($con));
    }
    
    // Creating New Fileds
    
    $query0 = "SHOW COLUMNS FROM $table_name LIKE 'id'";
    $res0 = direct_sql($query0,'set');
    if ($res0['count'] == 0) {
        $sql0 = "ALTER TABLE $table_name ADD COLUMN id INT "; //NOT NULL AUTO_INCREMENT ";
        $res[] = mysqli_query($con, $sql0) or die("Error In Adding ID Column : " . mysqli_error($con));
    }
    
    $query1 = "SHOW COLUMNS FROM $table_name LIKE 'status'";
    $res1 = direct_sql($query1,'set');
    if ($res1['count'] == 0) {
        $sql1 = "ALTER TABLE $table_name ADD COLUMN status VARCHAR(50) DEFAULT 'ACTIVE'";
       	$res[] = mysqli_query($con, $sql1) or die("Error In Adding status Column : ". mysqli_error($con));
    }
    
    $query2 = "SHOW COLUMNS FROM $table_name LIKE 'created_at'";
    $res2 = direct_sql($query2,'set');
    if ($res2['count'] == 0) {
        $sql2 = "ALTER TABLE $table_name ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
        $res[] = mysqli_query($con, $sql2) or die("Error In created_at status Column : ". mysqli_error($con));
    }
    
    $query3 = "SHOW COLUMNS FROM $table_name LIKE 'updated_at'";
    $res3 = direct_sql($query3,'set');
    if ($res3['count'] == 0) {
        $sql3 = "ALTER TABLE $table_name ADD COLUMN updated_at TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
        $res[] = mysqli_query($con, $sql3) or die("Error In Adding updated_at Column : ". mysqli_error($con));
    }
    
    $query4 = "SHOW COLUMNS FROM $table_name LIKE 'created_by'";
    $res4 = direct_sql($query4,'set');
    if ($res4['count'] == 0) {
        $sql4 = "ALTER TABLE $table_name ADD COLUMN created_by INT NULL ";
        $res[] = mysqli_query($con, $sql4) or die("Error In Adding created_by Column : ". mysqli_error($con));
    }
    
    $query5 = "SHOW COLUMNS FROM $table_name LIKE 'updated_by'";
    $res5   = direct_sql($query5,'set');
    if ($res5['count'] == 0) {
        $sql5 = "ALTER TABLE $table_name ADD COLUMN updated_by INT NULL ";
        $res[] = mysqli_query($con, $sql5) or die("Error In Adding updated_by Column : ". mysqli_error($con));
    }
    
    $sql6 = "ALTER TABLE $table_name MODIFY id INT NOT NULL AUTO_INCREMENT , ADD PRIMARY KEY (id)";
	$res[] = mysqli_query($con, $sql6) or die("Error In Assigning Primary Key : " . mysqli_error($con));

    
	$sql8  = "ALTER TABLE $table_name CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
	$res[] = mysqli_query($con, $sql8) or die("Error In Assign Updated at Default Value as Current Timestamp : " . mysqli_error($con));
}

// List of all Table Exist in databse 
function table_list() 
{
	global $con;
    global $db_name;
	$result = array();
	$res  = direct_sql("show tables");
	$ct  = direct_sql("show tables")['count'];
	if ($res['count'] >= 1) {
		foreach((array)$res['data'] as $row) {
			$data[] = $table_name= $row['Tables_in_'. $db_name];
			$tbls = get_all('op_table', '*', array('table_id'=>$table_name));
			if ($tbls['count']==0){
				insert_data('op_table', array('table_id'=>$table_name,'status'=>'ACTIVE'));
			}
		}
		$sql ="update op_table set status ='LOCKED' where table_id like 'op_%'";
		direct_sql($sql,'set');
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	return $result;
}


function column_list($table_name = 'users')
{
	global $con;
	global $db_name;
	$result = array();
	$sql = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_DEFAULT,  EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table_name'";
	$res = mysqli_query($con, $sql) or die("Error in Creating Table List" . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	if ($ct >= 1) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	return $result;
}


function table_key_list($table_name = 'users')
{
	global $con;
	global $db_name;
	$result = array();
	$sql = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_DEFAULT,  EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table_name' and COLUMN_KEY in('PRI','UNI')";
	$res = mysqli_query($con, $sql) or die("Error in Creating Table List" . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	if ($ct >= 1) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	return $result;
}


// // ENCODE STRING INTO NON READABLE STRING  
function encode($input)
{
	$salt = rnd_str(16);
	$input = $input."&salt=$salt";
	return strtr(base64_encode($input), '+/=', '._-');
}

// DECODE STRING FROM NON READABLE STRING TO READABLE
function decode($input)
{
	$url = base64_decode(strtr($input, '._-', '+/='));
	//$parts = parse_url($url);
	parse_str($url, $query);
	return $query;
}


// AES Encode Function
// function encode($input)
// {
//     // Add random salt
//     $salt = rnd_str(16);
//     $input = $input . "&salt=" . $salt;

//     // Generate random IV (16 bytes)
//     $iv = openssl_random_pseudo_bytes(16);

//     // Encrypt
//     $encrypted = openssl_encrypt($input, ENC_METHOD, ENC_KEY, 0, $iv);

//     // Return Base64 with IV prepend
//     return base64_encode($iv . $encrypted);
// }

// // AES Decode Function
// function decode($input)
// {
//     // Base64 decode first
//     $data = base64_decode($input);

//     // IV 16 bytes → extract
//     $iv = substr($data, 0, 16);

//     // Remaining part encrypted text → extract
//     $encryptedText = substr($data, 16);

//     // Decrypt
//     $decrypted = openssl_decrypt($encryptedText, ENC_METHOD, ENC_KEY, 0, $iv);

//     // Convert back into array using parse_str
//     parse_str($decrypted, $query);

//     return $query;
// }



// USE TO CREATE STRING REPLACE SPACE WITH UNDERSCORE FORM STRING 

function remove_space($str)
{
	$str = ($str!='')?trim($str):'';
	return strtolower(preg_replace("/[^a-zA-Z0-9]+/", "_", $str));
}

function remove_only_space($str)
		{
		$str =trim($str);
		return strtolower(preg_replace("/[^a-zA-Z0-9.]+/", "_", $str));
		}

// USE TO CREATE STRING REPLACE UNDERSCORE WITH SPACE FORM STRING 

function add_space($str)
{
	if($str!='')
	{
	$str = trim($str);
	return ucwords(str_replace('_', ' ', $str));
	}
}

// GET VIDEO ID FROM YOUTUBE LINK 

function get_vid($url)
{
	parse_str(parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
	return $my_array_of_vars['v'];
}

// USE To CREATE A RANDOM STRING OF SPECIFIC LINK 
function rnd_str($length_of_string)
{
	// String of all alphanumeric character 
	$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

	// Shufle the $str_result and returns substring of specified length 
	return strtoupper(substr(str_shuffle($str_result), 0, $length_of_string));
}

// USE TO CLEAN DATE AND REMOVE HAKABLE CODE 
function xss_clean($data)
{
   
	// Fix &entity\n;
	$data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
	$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
	$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
	$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

	// Remove any attribute starting with "on" or xmlns
	$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

	// Remove javascript: and vbscript: protocols
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

	// Remove namespaced elements (we do not need them)
	$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

	do {
		// Remove really unwanted tags
		$old_data = $data;
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
	} while ($old_data !== $data);

	// we are done...
	return $data;
}

// USE TO CLEAN MULTI LEVEL ARRAY DATA
function post_clean($arr_data)
{
	if (is_array($arr_data)) {
		foreach($arr_data as $data) {

			$key = array_search($data, $arr_data);
			if (is_array($data)) {
				post_clean($data);
			} else {
				$arr_data[$key] = xss_clean($data);
			}
		}
	}
	else if($arr_data!='') {
		xss_clean($arr_data);
	}
// 	else {
// 		xss_clean($arr_data);
// 	}
	return $arr_data;
}

function array_to_string($arr_data)
{
	if (is_array($arr_data)) {
		foreach((array)$arr_data as $data) {

			$key = array_search($data, $arr_data);
			if (is_array($data)) {
				$arr_data[$key] = implode(",",$data);
			}
		}
	}
	return $arr_data;
}

// CHECK ORIGIN OF REQUESTED URL 
function verify_request()
{
	if (isset($_SERVER['HTTP_REFERER'])) {
		$ref = parse_url($_SERVER["HTTP_REFERER"]);
		$rh  = $ref['host'];
		$mh = $_SERVER['HTTP_HOST'];

		if ($rh <> $mh) {
			return false;
		} else {
			return true;
		}
	}
}


function verify($user_type)
{
	$actual_link = "http://" . $_SERVER['HTTP_HOST']; //$_SERVER['REQUEST_URI'];
	//die($actual_link);
	$current_page = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
	if ($user_type == 'ADMIN') {
		global $ADMIN_role;
		$all_page = $ADMIN_role;
	} else if ($user_type == 'CLIENT') {
		global $client_role;
		$all_page = $client_role;
	} else {
		die("Invalid User ! Don't Have Permission");
	}

	if (!array_search($current_page, $all_page)) {
		die("Don't have Permission");
	}
}


	
function format_interval(DateInterval $interval) {
    $result = "";
    if ($interval->y) { $result .= $interval->format("%yy "); }
    if ($interval->m and $interval->y <1) { $result .= $interval->format("%mmonth "); }
    if ($interval->d and $interval->m <1) { $result .= $interval->format("%dd "); }
    if ($interval->h and $interval->d <1) { $result .= $interval->format("%hh "); }
    if ($interval->i and $interval->h <1) { $result .= $interval->format("%im "); }
    if ($interval->s and $interval->i <1) { $result .= $interval->format("%ss"); }
    
    return $result;
}

function time_gap($action_time)
{
	date_default_timezone_set('Asia/Kolkata');
    $cdate = date('Y-m-d H:i:s');
    $first_date = new DateTime($action_time);
    $second_date = new DateTime($cdate);
    $difference = $first_date->diff($second_date);
    return format_interval($difference);
}

// TO ADD COLUMN IN TABLE 
// To check and Add Column in Table
function add_column($table_name, $col_name, $data_type ='varchar(255)', $default =null )
	{
		global $con;
		$default = ($default==null)?null: " DEFAULT '$default'";
		$exist = direct_sql("SHOW COLUMNS FROM $table_name LIKE '$col_name'");
		if($exist['count']==0)
		{ 
		$sql ="alter table $table_name add column $col_name $data_type $default"; 
		$res =mysqli_query($con,$sql) or die("Error in Adding Coumn". mysqli_error($con));
		}
		
	}
	
// To check and Update Column Settings Table
function update_column($table_name, $col_name, $data_type ='varchar(255)', $default =null )
	{
		global $con;
		$default = ($default==null)?null: " DEFAULT '$default'";
		$exist = direct_sql("SHOW COLUMNS FROM $table_name LIKE '$col_name'");
		if($exist['count']>0)
		{ 
		$sql ="alter table $table_name CHANGE $col_name $col_name $data_type $default"; 
		$res =mysqli_query($con,$sql) or die("Error in Adding Coumn". mysqli_error($con));
		}
		
	}

// To Remove a Column from Table
function remove_column($table_name, $col_name )
	{
		global $con;
		$exist = direct_sql("SHOW COLUMNS FROM $table_name LIKE '$col_name'");
		if($exist['count']==1)
		{
		echo $sql ="alter table $table_name drop column $col_name "; 
		$res =mysqli_query($con,$sql) or die("Error in Removing Column". mysqli_error($con));
			
			$result['status'] = 'success';
			$result['msg'] = "Deleted Successfully";
			
		}
		else{
			$result['status'] = 'error';
			$result['msg'] = "Column Not Exist ". mysqli_error($con);
		}
	return $result;
	}

// TO INSERT BLANK ROW IN A TABLE  	
function insert_row($table_name)
{
	global $con;
	global $user_id;
	global $user_type;
	global $current_date_time;
	$result = get_multi_data($table_name, array('created_by' => $user_id, 'status' => 'AUTO'), ' order by id desc limit 1');

	if ($result['count'] < 1) {
		$result = insert_data($table_name, array('status' => 'AUTO', 'created_at' => $current_date_time));
		$id = $result['id'];
	} else {
		$id = $result['data'][0]['id'];
	}

	$result['table']		= $table_name;
	$result['id']			= $id;
	$result['status']		= "success";
	$result['msg']			= 'New Row Added Successfully';
	$result['permission']	= check_role($table_name, $user_type, 'can_add')['status'];

	return $result;
}




// TO INSERT DATA IN A TABLE  		
function insert_data_old($table_name, $ArrayData)
{
	global $con;
	global $user_id;
	global $current_date_time;
	//echo"<pre>";
	//print_r($ArrayData);
	$ArrayData['created_by'] = $user_id;
	$ArrayData['created_at'] = $current_date_time;

	$columns = implode(", ", array_keys($ArrayData));
	$escaped_values = array_values($ArrayData);
	foreach((array)$escaped_values as $newvalue) {
		$newvalues[] = "'" . post_clean($newvalue) . "'";
	}
	//$data = mysqli_escape_string ($escaped_values);
	$values  = implode(", ", $newvalues);

	$sql = "INSERT IGNORE INTO $table_name ($columns) VALUES ($values)";

	$res = mysqli_query($con, $sql) or die("Error in Inserting Data" . mysqli_error($con));
	$id = mysqli_insert_id($con);
	if (mysqli_affected_rows($con) > 0) {
		$result['id'] = $id;
		$result['status'] = 'success';
		$result['msg'] = " Data Added Successfully";
	} else {
		$result['id'] = 0;
		$result['status'] = 'error';
		$result['msg'] = mysqli_error($con);
	}
	$result['sql'] = $sql;
	return $result;
}

// TO INSERT DATA FROM RTF TEXTAREA 


// TO INSERT DATA IN A TABLE  		
function insert_data($table_name, $ArrayData)
{
	global $con;
	global $user_id;
	global $current_date_time;
	$result =[];
	
    if($table_name!='')
    {
	// Add user_id and created_at to the data array
	$ArrayData['created_by'] = $user_id;
	$ArrayData['created_at'] = $current_date_time;
    
   
	// Extract column names and values from the array
	$columns = implode(", ", array_keys($ArrayData));
	$values = array_map(function($value) use ($con) {
	    $value = ($value!='')?mysqli_real_escape_string($con, $value):$value;
		return "'" . $value . "'";
	}, array_values($ArrayData));

	// Prepare the values part of the SQL query
	$values_str = implode(", ", $values);

	// Prepare the SQL query with ON DUPLICATE KEY UPDATE
	    $sql = "INSERT INTO $table_name ($columns) VALUES ($values_str) 
	        ON DUPLICATE KEY UPDATE created_by = VALUES(created_by), created_at = VALUES(created_at)";

	// Execute the query
    	$res = mysqli_query($con, $sql);
        
    
    	// Check if insertion was successful
    	if ($res) {
    		$id = mysqli_insert_id($con);
    		if ($id > 0) {
    			$result['id'] = $id;
    			$result['status'] = 'success';
    			$result['msg'] = "Data Added Successfully";
    		} else {
    			$result['id'] = 0;
    			$result['status'] = 'error';
    			$result['msg'] = "Error inserting data";
    		}
    	} else {
    		// Duplicate entry or other error occurred
    		$result['id'] = 0;
    		$result['status'] = 'error';
    		$result['msg'] = mysqli_error($con);
    	}
    
	$result['sql'] = $sql;
    }
	return $result;
}

// TO UPDATE SINGLE RECORD OF TABLE 
// function update_data($table_name, $ArrayData, $id, $pkey = 'id')
// {

// 	global $con;
// 	global $user_id;
// 	global $current_date_time;

// 	$ArrayData['updated_at'] = $current_date_time;
// 	$ArrayData['updated_by'] = $user_id;

// 	$cols = array();
// 	foreach((array)$ArrayData as $key => $value) {
		
// 		$newvalue = post_clean($value);
// 		$cols[] = "$key = '$newvalue'";
// 	}
// 	$sql = "UPDATE $table_name SET " . implode(', ', $cols) . " WHERE $pkey  ='" . $id . "'";
// 	$res = mysqli_query($con, $sql) or mysqli_error($con);
// 	$num = mysqli_affected_rows($con);
// 	if ($num > 0) {
// 		$result['id'] = $id;
// 		$result['status'] = 'success';
// 		$result['msg'] = $num . " Record Updated Successfully";
// 	} else {
// 		$result['id'] = $id;
// 		$result['status'] = 'error';
// 		$result['msg'] = "Sorry ! No Update Found" . mysqli_error($con);
// 	}
// 	$result['sql'] = $sql;
// 	return $result;
// }

function old_update_data($table_name, $ArrayData, $id, $pkey = 'id')
{
    global $con;
    global $user_id;
    global $current_date_time;

    try {
        // Add updated_at and updated_by to the data array
        $ArrayData['updated_at'] = $current_date_time;
        $ArrayData['updated_by'] = $user_id;

        // Build the SET part of the SQL query
        $cols = array();
        foreach ((array)$ArrayData as $key => $value) {
            $newvalue = post_clean($value);
            $newvalue = ($newvalue=='')?$newvalue: mysqli_real_escape_string($con, $newvalue);
            $cols[] = "$key = '" .$newvalue. "'";
        }
        $set_clause = implode(', ', $cols);

        // Prepare and execute the UPDATE query
        $id= ($id!='')?mysqli_real_escape_string($con, $id):$id;
        $sql = "UPDATE $table_name SET $set_clause WHERE $pkey = '" . $id . "'";
        $res = mysqli_query($con, $sql);

        if (!$res) {
            throw new Exception(mysqli_error($con));
        }

        $num = mysqli_affected_rows($con);
        if ($num > 0) {
            // Record updated successfully
            $result['id'] = $id;
            $result['status'] = 'success';
            $result['msg'] = $num . " Record Updated Successfully";
        } else {
            // No records were updated
            $result['id'] = $id;
            $result['status'] = 'error';
            $result['msg'] = "No records updated";
        }

        $result['sql'] = $sql;
        return $result;
    } catch (Exception $e) {
        // Handle the exception (e.g., log the error, return a specific error message)
        $result['id'] = $id;
        $result['status'] = 'error';
        $result['msg'] = "Error: " . $e->getMessage();
        return $result;
    }
}


function update_data($table_name, $ArrayData, $id, $pkey = 'id')
{
    global $con;
    global $user_id;
    global $current_date_time;

    try {
        // Add updated_at and updated_by
        $ArrayData['updated_at'] = $current_date_time;
        $ArrayData['updated_by'] = intval($user_id);

        $cols = array();
        foreach ((array)$ArrayData as $key => $value) {
            // Clean input
            $newvalue = post_clean($value);

            if ($newvalue === null || strtolower($newvalue) === "null") {
                // If NULL → write SQL as NULL (without quotes)
                $cols[] = "$key = NULL";
            } else {
                // Normal escape
                $escaped = mysqli_real_escape_string($con, $newvalue);
                $cols[] = "$key = '" . $escaped . "'";
            }
        }

        $set_clause = implode(', ', $cols);

        // Escape ID
        $id = ($id != '') ? mysqli_real_escape_string($con, $id) : $id;

        $sql = "UPDATE $table_name SET $set_clause WHERE $pkey = '" . $id . "'";
        $res = mysqli_query($con, $sql);

        if (!$res) {
            throw new Exception(mysqli_error($con));
        }

        $num = mysqli_affected_rows($con);
        if ($num > 0) {
            $result['id'] = $id;
            $result['status'] = 'success';
            $result['msg'] = $num . " Record Updated Successfully";
        } else {
            $result['id'] = $id;
            $result['status'] = 'error';
            $result['msg'] = "No records updated";
        }

        $result['sql'] = $sql;
        return $result;

    } catch (Exception $e) {
        $result['id'] = $id;
        $result['status'] = 'error';
        $result['msg'] = "Error: " . $e->getMessage();
        return $result;
    }
}


// TO UPDATE MULTIPLE RECORD OF TABLE BASED ON CONDITION

function update_multi_data($table_name, $ArrayData, $whereArr)
{
	global $con;
	$cols = array();
	foreach((array)$ArrayData as $key => $value) {
		$newvalue = post_clean($value);
		$cols[] = "$key = '$newvalue'";
	}

	foreach((array)$whereArr as $key => $value) {
		$newvalue = post_clean($value);
		$where[] = "$key = '$newvalue'";
	}

	$sql = "UPDATE $table_name SET " . implode(', ', $cols) . " WHERE " . implode('and ', $where);
	$res = mysqli_query($con, $sql) or mysqli_error($con);
	$num = mysqli_affected_rows($con);
	if ($num > 0) {
		$result['count'] = $num;
		$result['status'] = 'success';
		$result['msg'] = $num . " Multi Record Updated Successfully";
	} else {
		$result['status'] = 'error';
		$result['msg'] = "Sorry ! No Update Found" . mysqli_error($con);
	}
	$result['sql'] = $sql;
	return $result;
}

// SOFT DELETE SINGLE RECORD FROM TABLE

function remove_data($table_name, $id, $pkey = 'id')
{
	global $con;
	global $user_id;
	global $current_date_time;

	$sql = "UPDATE $table_name SET status = 'DELETED' , updated_by = '$user_id', updated_at ='$current_date_time' WHERE $pkey  ='" . $id . "'";
	$res = mysqli_query($con, $sql) or die("Error in Deleting Data" . mysqli_error($con));
	$num = mysqli_affected_rows($con);
	if ($num >= 1) {
		$result['id'] = $id;
		$result['status'] = 'success';
		$result['msg'] = $num . " Record removed successfully";
	} else {
		$result['id'] = $id;
		$result['status'] = 'error';
		$result['msg'] = "Sorry ! No record found to delete";
	}
	$result['sql'] = $sql;
	return $result;
}

// SOFT DELETE MULTIPLE RECORD BASED ON CONDITION 

function remove_multi_data($table_name, $whereArr)
{
	global $con;
	global $user_id;
	global $current_date_time;
	foreach((array)$whereArr as $key => $value) {
		$newvalue = preg_replace('/[^A-Za-z.@,:+0-9\-]/', ' ', $value);
		$where[] = "$key = '$newvalue'";
	}
	$sql = "update " . $table_name . " set status ='DELETED' updated_by = '$user_id', updated_at ='$current_date_time' WHERE " . implode('and ', $where);
	$res = mysqli_query($con, $sql) or die("Error in Deleting Data" . mysqli_error($con));
	$num = mysqli_affected_rows($con);
	if ($num >= 1) {
		$result['count'] = $num;
		$result['status'] = 'success';
		$result['msg'] = $num . " Record deleted successfully";
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['msg'] = "Soory ! No Record found to delete";
	}
	$result['sql'] = $sql;
	return $result;
}


// HARD DELETE SINGLE RECORD FROM TABLE

function delete_data($table_name, $id, $pkey = 'id')
{
	global $con;
	$sql = "delete from $table_name WHERE $pkey  ='" . $id . "'";
	$res = mysqli_query($con, $sql) or die("Error in Deleting Data" . mysqli_error($con));
	$num = mysqli_affected_rows($con);
	if ($num >= 1) {
		$result['id'] = $id;
		$result['status'] = 'success';
		$result['msg'] = $num . " Record deleted successfully";
	} else {
		$result['id'] = $id;
		$result['status'] = 'error';
		$result['msg'] = "Sorry ! No record found to delete";
	}
	$result['sql'] = $sql;
	return $result;
}

// HARD DELETE MULTIPLE RECORD BASED ON CONDITION 

function delete_multi_data($table_name, $whereArr)
{
	global $con;
	foreach((array)$whereArr as $key => $value) {
		$newvalue = preg_replace('/[^A-Za-z.@,:+0-9\-]/', ' ', $value);
		$where[] = "$key = '$newvalue'";
	}
	$sql = "delete from " . $table_name . " WHERE " . implode('and ', $where);
	$res = mysqli_query($con, $sql) or die("Error in Deleting Multi Data" . mysqli_error($con));
	$num = mysqli_affected_rows($con);
	if ($num >= 1) {
		$result['count'] = $num;
		$result['status'] = 'success';
		$result['msg'] = $num . " Record deleted successfully";
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['msg'] = "Soory ! No Record found to delete";
	}
	$result['sql'] = $sql;
	return $result;
}

//COPY TABLE STRUCTURE
function copy_table($original_table, $new_table) {
    global $con;
    $original_table = mysqli_real_escape_string($con, $original_table);
    $new_table = mysqli_real_escape_string($con, $new_table);
    $check_query = "SHOW TABLES LIKE '$new_table'";
    $result = mysqli_query($con, $check_query);
    if (mysqli_num_rows($result) > 0) {
        return "Error: Table `$new_table` already exists.";
    }
    $create_query = "CREATE TABLE `$new_table` LIKE `$original_table`";
    if (!mysqli_query($con, $create_query)) {
        return "Failed to create table: " . mysqli_error($con);
    }

    return "Table `$original_table` successfully copied to `$new_table`.";
}


// FETCH ALL DATA BASED On CONDITION (Optional)	

function get_all($table_name, $column_list = '*', $whereArr = null, $orderby = 'id DESC')
{
	global $con;
	global $user_id;
	global $user_type;
	$orderby = ' order by ' . $orderby;
	if ($column_list <> '*') {
		$column_list = implode(',', $column_list);
	}

	if ($whereArr <> null) {
		foreach((array)$whereArr as $key => $value) {
			$key = trim($key);
			$newvalue = preg_replace('/[^A-Za-z.@,:+0-9\-_]/', ' ', $value);
			$where[] = "$key = '$newvalue'";
		}
		$sql = "SELECT $column_list FROM $table_name where " . implode(' and ', $where);
	} else {
		$sql = "SELECT $column_list FROM $table_name where status not in ('AUTO', 'DELETED')  ";
	}
	$sql = $sql . $orderby;
	$res = mysqli_query($con, $sql) or die("Error In Loading Data : " . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	if ($ct >= 1) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	if (isset($user_type) and $user_type != '') {
		//	$result['permission'] = check_role($table_name, $user_type,'can_view')['status'];
	}
	$result['sql'] = $sql;
	return $result;
}

// FETCH ALL DATA NOT On CONDITION (Optional)	

function get_not($table_name, $column_list = '*', $whereArr = null, $orderby = 'id DESC')
{
	global $con;
	global $user_id;
	$orderby = ' order by ' . $orderby;
	if ($column_list <> '*') {
		$column_list = implode(',', $column_list);
	}

	if ($whereArr <> null) {
		foreach((array)$whereArr as $key => $value) {
			$key = trim($key);
			$newvalue = preg_replace('/[^A-Za-z.@,:+0-9\-_]/', ' ', $value);
			$where[] = "$key <> '$newvalue'";
		}
		$sql = "SELECT $column_list FROM $table_name where " . implode('and ', $where);
	} else {
		$sql = "SELECT $column_list FROM $table_name where status <>'AUTO' ";
	}

	$res = mysqli_query($con, $sql . $orderby) or die("Error In Loading Data : " . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	if ($ct >= 1) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	$result['sql'] = $sql;
	$result['permission'] = check_role($table_name, $user_id, 'can_view')['status'];
	return $result;
}

// EXECUTE ANY SQL STATMENT DIRECTLY AND GET FORMATED RESULT

function direct_sql($sql, $type = 'get')
{
	global $con;
	global $user_id;
	$data = null;
	$res = mysqli_query($con, $sql) or die("Error In Loding Data : " . mysqli_error($con));
	if ($type == 'set') // 
	{
		$ct = mysqli_affected_rows($con);
	} else {  // FOR SELECT COMMAND 
		$ct = mysqli_num_rows($res);
		if ($ct >= 1) {
			while ($row = mysqli_fetch_assoc($res)) {
				$data[] = $row;
			}
		}
	}
	if ($ct >= 1) {
		$result['count'] = $ct;
		$result['status'] = 'success';
		$result['data'] = $data;
	} else {
		$result['count'] = 0;
		$result['status'] = 'error';
		$result['data'] = null;
	}
	$result['sql'] = $sql;
	return $result;
}

function direct_sql_file($filename)
{
	global $con;
	// Temporary variable, used to store current query
	$templine = '';
	// Read in entire file
	$lines = file($filename);
	// Loop through each line
	foreach((array)$lines as $line) {
		// Skip it if it's a comment
		if (substr($line, 0, 2) == '--' || $line == '')
			continue;

		// Add this line to the current segment
		$templine .= $line;
		// If it has a semicolon at the end, it's the end of the query
		if (substr(trim($line), -1, 1) == ';') {
			// Perform the query
			$con->query($templine) or print('Error performing query \'<strong>' . $templine . '\': ' . mysqli_error($con) . '<br /><br />');
			// Reset temp variable to empty
			$templine = '';
		}
	}
	$res['msg'] = $filename . " imported successfully";
	$res['status'] = "success";
	return $res;
}

// GET SINGLE DATA FORM TABLE BASED ON CONDITION

function get_data($table_name, $id, $field_name = null, $pkey = 'id')
{
	global $con;
	$result['count'] = 0;
	$result['status'] = 'error';
	$sql = "SELECT * FROM $table_name where $pkey ='$id' ";
	$res = mysqli_query($con, $sql) or die(" Data Information Error : " . mysqli_error($con));
	$ct = mysqli_num_rows($res);
	$result['count'] = $ct;
	if ($ct >0) {
		$row = mysqli_fetch_assoc($res);
	//	extract($row);
		if ($field_name) {
			$result['status'] = 'success';
			$result['data'] = $row[$field_name];
		} else {
			$result['status'] = 'success';
			$result['data'] = $row;
		}
	} else {
		$result['count'] = 0;
		$result['status'] = 'success';
		$result['data'] = null;
	}
	$result['sql'] = $sql;
	return $result;
}

// GET DATA FORM TABLE BASED ON MULTIPLE CONDITION

function get_multi_data($table_name, $whereArr, $order = null)
{
	global $con;

	foreach((array)$whereArr as $key => $value) {
		if($value!='')
		{
		$newvalue = preg_replace('/[^A-Za-z.@_,:+0-9\-_]/', ' ', $value);
		$where[] = "$key = '$newvalue'";
		}
	}

	$sql = "select * from " . $table_name . " WHERE " . implode(' and ', $where) . $order;
	$res = mysqli_query($con, $sql) or mysqli_error($con);
	$num = mysqli_num_rows($res);
	if ($num > 0) {
		while ($row = mysqli_fetch_assoc($res)) {
			$data[] = $row;
		}
		$result['status'] = 'success';
		$result['count'] = $num;
		$result['data'] = $data;
	} else {
		$result['status'] = 'error';
		$result['count'] = 0;
		$result['data'] = mysqli_error($con);
	}
	$result['sql'] = $sql;
	return $result;
}

function upload_img($file_name, $imgkey = 'rand', $target_dir = "upload", $size ='10000')
{
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0755, true);
	}
	if ($imgkey == 'rand') {
		$imgkey = rand(10000, 99999);
	}
	$target_file = $imgkey . "_" . basename($_FILES[$file_name]["name"]);
	$target_file = strtolower(preg_replace("/[^a-zA-Z0-9._]+/", "", $target_file));
	$uploadOk = 1;

	$res['id'] = 0;
	$res['status'] = 'error';
	$res['msg'] = '';
	$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
	// Check if image file is a actual image or fake image


	// Check if file already exists
	if (file_exists($target_file)) {
		unlink($target_file);
		$res['msg'] = "Sorry, file already exists.";
		$uploadOk = 1;
	}
	// Check file size
	$file_in_kb = round($_FILES[$file_name]["size"]/1024);
	if ($file_in_kb > $size) {
	    
	    $res['msg']= "Sorry, your file is greater than $size KB File Size : $file_in_kb KB";
	    $uploadOk = 0;
	}
	// Allow certain file formats
	$valid_extension = array("png","jpg","jpeg","pdf","zip","xls","doc","rar","xlsx","docx","pptx","ppt","gif","rtf","txt","csv","mp3","mp4","ogg","wav","amr","dat","vob");
	if(!in_array($imageFileType, $valid_extension)){
	//if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "pdf") {
		$res['msg'] = "Sorry, files formated are allowed.";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		$msg = "Sorry, your file was not uploaded.";
		// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES[$file_name]["tmp_name"], $target_dir . "/" . $target_file)) {
			$res['msg'] = "The file " . basename($_FILES[$file_name]["name"]) . " has been uploaded.";
			$res['id'] = $target_file;
			$res['status'] = 'success';
		} else {
			$res['msg'] = "Sorry, there was an error uploading your file.";
		}
	}
	return $res;
}


// function multi_upload($fileArr='uploadimg',$target_dir = "../upload"){
//       $img_name='';
// 	  $files = $_FILES[$fileArr];
// 	  if($files['name'] != ''){
// 	  	$file_names = '';
// 	  	$total = count($files['name']);
// 	  	for($i=0; $i<$total; $i++){
// 	  		$file_name = $files['name'][$i];
// 	  		$extention = pathinfo($file_name, PATHINFO_EXTENSION);
// 	  		$valid_extension = array("png","jpg","jpeg","pdf","zip","xls","doc","rar","xlsx","docx","pptx","ppt","gif","rtf","txt","csv","mp3","mp4","ogg","wav","amr","dat","vob");
// 	  if(in_array($extention, $valid_extension)){
// 	  	$new_name = rand(100,999).remove_only_space($file_name);
// 	  	$path = "$target_dir/" . $new_name;
// 	  	$resp = move_uploaded_file($files['tmp_name'][$i],$path);
// 	  	 if ($resp ==1){
// 	  	 	// $file_names .= $new_name . ",";
// 	  	// $name_arr = array($img_for_field=>$id,$img_field=>$new_name,'type'=>$type);
// 	  	$img_name .= $new_name . ",";
// 	  	// $res = insert_data($tbl,$name_arr);
// 	    $res['img_name'] = rtrim($img_name,',');
// 	    $res['id'] = explode(',', rtrim($img_name, ","));
	  	
// 	  }else{
// 	  	$res = $resp;
// 	  }
//     	}
//         }
//     	}else {
//     		$res['id'] =0;
// 		    $res['status'] ='error';
// 		    $res['msg'] ='';
//      	 }
//     	return $res;
//      }
     
     function multi_upload($fileKey = 'uploadimg', $target_dir = '../upload')
{
    if (!isset($_FILES[$fileKey])) {
        return [
            'status' => 'error',
            'msg'    => 'No file uploaded under key "' . $fileKey . '"',
            'id'     => []
        ];
    }

    $files = $_FILES[$fileKey];
    $allowedExt = ["png", "jpg", "jpeg", "gif", "pdf", "zip", "xls", "xlsx", "doc", "docx", "ppt", "pptx", "rar", "rtf", "txt", "csv", "mp3", "mp4", "ogg", "wav", "amr", "dat", "vob"];
    $img_name = '';
    $response = ['id' => [], 'img_name' => '', 'status' => 'success', 'msg' => ''];

    $total = count($files['name']);
    for ($i = 0; $i < $total; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;

        $newName = mt_rand(100, 999) . str_replace(' ', '', $files['name'][$i]);
        $uploadPath = rtrim($target_dir, '/') . '/' . $newName;

        if (move_uploaded_file($files['tmp_name'][$i], $uploadPath)) {
            $response['id'][] = $newName;
            $img_name .= $newName . ",";
        }
    }

    $response['img_name'] = rtrim($img_name, ',');
    $response['msg'] = count($response['id']) ? 'Files uploaded successfully' : 'No valid files uploaded';

    return $response;
}


function send_mail($to, $subject, $msg, $att_arr='', $name='')
{
   require 'vendor/autoload.php';
   global $inst_name;
   global $inst_email;
   global $noreply_email;
   global $inst_email_password;
   $mail = new PHPMailer;
   $mail->isSMTP();
   $mail->SMTPDebug = 0;// 2 for debug
   $mail->Host = 'smtp.hostinger.com';
   $mail->Port = 587;
   $mail->SMTPAuth = true;
   $mail->Username = $inst_email;
   $mail->Password = $inst_email_password;
   $mail->setFrom($inst_email, $inst_name);
   $mail->addReplyTo($noreply_email, $inst_name);
   $mail->addAddress($to, $name);
   $mail->Subject = $subject;
   $mail->isHTML(true);
   $mail->Body = $msg;
       foreach((array)$att_arr as $att)
       {
            $mail->addAttachment($att);
       }
   if (!$mail->send()) {
       echo 'Mailer Error: ' . $mail->ErrorInfo;
       return false;
   } else {
       return true;
   }
}

function api_call($api_url)
{
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $api_url);
	// Execute
	$result = curl_exec($ch);
	// Closing
	curl_close($ch);
	return $result;
}

function csv_export($table_name, $col_list = '*')
{
	global $con;
	global $db_name;
	$filename = $table_name . ".csv";
	$fp = fopen('php://output', 'w');

	if ($col_list == '*') {
		$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$db_name' AND TABLE_NAME='$table_name'";
		$result = mysqli_query($con, $query);
		while ($row = mysqli_fetch_row($result)) {
			$header[] = $row[0];
		}
	} else {
		$header = explode(',', $col_list);
	}

	header('Content-type: application/csv');
	header('Content-Disposition: attachment; filename=' . $filename);
	fputcsv($fp, $header);

	$query = "SELECT $col_list FROM $table_name";
	$result = mysqli_query($con, $query);
	while ($row = mysqli_fetch_row($result)) {
		fputcsv($fp, $row);
	}
	//exit;
}


function csv_import($table, $pkey = 'id') // Import CSV FILE to Table
{
	// Allowed mime types
	$csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
	$change = $new = 0;
	// Validate whether selected file is a CSV file
	if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)) {
		
		if (is_uploaded_file($_FILES['file']['tmp_name'])) {

			// Open uploaded CSV file with read-only mode
			$csvFile = fopen($_FILES['file']['tmp_name'], 'r');
			echo $col_list = array_map('trim', fgetcsv($csvFile));
			print_r($col_list);
			while (($line = fgetcsv($csvFile)) !== FALSE) {
				$all_data = array_combine($col_list, $line);
				//$search[$pkey] =trim($all_data[$pkey]);
				//$search_result = get_all($table,'*', $search, $pkey);
				$search_result = get_data($table, $all_data[$pkey], null, $pkey);
				echo "<pre>";
				print_r($search_result);
				if ($search_result['count'] < 1) {
					$res = insert_data($table, $all_data);
					if ($res['id'] != 0) {
						$new++;
					}
				} else {
					//echo $all_data[$pkey];
					$res = update_data($table, $all_data, $all_data[$pkey], $pkey);
					if ($res['status'] == 'success') {
						$change++;
					}
				}
				$res = array('status' => 'success', 'change' => $change, 'new' => $new, 'msg' => " $new New Data and $change change found and updated.");
			}
		}
	} else {
		$res = array('status' => 'error', 'change' => $change, 'new' => $new, 'msg' => 'Please upload a valid CSV file.');
	}
	return  $res;
}


function get_bal_msg()
{
	global $auth_key_msg;
	$api_url = 'http://mysms.msgclub.net/rest/services/sendSMS/getClientRouteBalance?AUTH_KEY=' . $auth_key_msg;
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $api_url);
	// Execute
	$result = curl_exec($ch);
	// Closing
	curl_close($ch);
	$data  = json_decode($result, true);
	return $data[0]['routeBalance'];
}


function get_bal_sms()
{
	global $auth_key_sms;
	$api_url = 'http://sms.morg.in/api/balance.php?&type=4&authkey=' . $auth_key_sms;
	//  Initiate curl
	$ch = curl_init();
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// Set the url
	curl_setopt($ch, CURLOPT_URL, $api_url);
	// Execute
	$result = curl_exec($ch);
	// Closing
	curl_close($ch);
	$data  = json_decode($result, true);
	return $data;
}

function send_msg($number,$sms,$templateid)
		{
			$res =null;
			$numarr = explode(',', $number);
		foreach((array) $numarr as $num)
			{
		    global $user_id;
			global $sender_id;
			global $auth_key;
			global $current_date_time;
			$ctype ="English";
			
			$no ='91'.urlencode($num);
			$msg = substr(urlencode($sms),0,2000);
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$url="http://sms.morg.in/api/sendhttp.php?authkey=$auth_key&mobiles=$no&message=$msg&sender=$sender_id&route=4&country=91&DLT_TE_ID=$templateid";
			
	        curl_setopt($ch,CURLOPT_URL, $url);
	    	$res= curl_exec($ch);
	    	$data =json_decode($res, true);
	    	curl_close($ch);
			}
			return $res;
		}	


function date_range($gap = 15)
{

	$startDate = date('Y-m-d');
	$endDate = date("Y-m-d", strtotime("+$gap days", strtotime($startDate)));
	$startStamp = strtotime($startDate);
	$endStamp   = strtotime($endDate);

	if ($endStamp > $startStamp) {
		while ($endStamp >= $startStamp) {

			$data['dv'] = date('Y-m-d', $startStamp);
			$data['dd'] = date('d M D', $startStamp);
			$data['day'] = date('D', $startStamp);
			$dateArr[] = $data; // date( 'Y-m-d', $startStamp );

			$startStamp = strtotime(' +1 day ', $startStamp);
		}
		return $dateArr;
	} else {
		return $startDate;
	}
}

// HTML UI CREATE

function create_input($name, $type , $value=null, $display_name='', $extra='', $size = 'col-md-4')
{
    
    if($type =='hidden')
    {
        $str="<input type ='hidden' class='form-control' value='$value' name='$name' id ='$name'  $extra>";
    }
    else{
	$str = "<div class='form-group $size'>
                <label> ". $display_name ." </label>
                <input type ='$type' class='form-control' value='$value' name='$name' id ='$name'  $extra>
            </div>";
    }
	return $str;
}


function display_img($photo, $width = '100px', $height = '100px')
{
	global $base_url;
	$str = "<img src='$base_url/upload/$photo' width='$width'  height='$height'  class='img-thumbnail d-self-centered'>";
	return $str;
}



function dropdown($array_list, $selected = null)
{
	$str = '<option value=""> Select A value </option>';
	foreach((array)$array_list as $list) {
	$sel = ($list == $selected)?'selected':'';
	$str .="<option value='".$list."' ". $sel. ">". $list."</option>";
	}
	return $str;
}

function dropdown_with_key($array_list, $selected = null)
{
	foreach((array)$array_list as $list) {
		$key = array_search($list, $array_list);
	?>
		<option value='<?php echo $key; ?>' <?php if ($key == $selected) echo "selected"; ?>><?php echo $list; ?></option>
	<?php
	}
}

function dropdown_where($table_name, $id, $list, $whereArr, $selected = null)
{
	global $con;
	$str = '<option value=""> Select A value </option>';
	foreach((array)$whereArr as $key => $value) {
		$newvalue = post_clean($value);
		$where[] = "$key = '$newvalue'";
	}

	$sql = "select * from " .  $table_name . " WHERE " . implode('and ', $where);
	$res = mysqli_query($con, $sql) or mysqli_error($con);
	while ($row = mysqli_fetch_array($res)) {
		$id_inner = $row[$id];
		$show = $row[$list];
	$sel = ($id_inner == $selected) ? "selected" :"";
	$str .=	"<option value='$id_inner' $sel > $show </option>";
	
	}
	return $str;
}

function dropdown_multiple($array_list, $selectedArr = null)
{
	foreach((array)$array_list as $list) {
		//$key=-1;
		$key = array_search($list, $selectedArr);
	?>
		<option value='<?php echo $list; ?>' <?php if ($key > -1) echo "selected"; ?>><?php echo $list . "-" . $key; ?></option>
	<?php
	}
}



// function dropdown_list($tablename, $value, $list, $selected = null, $list2 = null)
// {
// 	global $con;
// 	$i = 0;
// 	$query = "select * from $tablename where status ='ACTIVE' order by $list";
// 	$res = mysqli_query($con, $query) or die(" Creating Drop down Error : " . mysqli_error($con));
// 	$str = '<option value=""> Search & Select </option>';
// 	while ($row = mysqli_fetch_array($res)) {
// 		$key = $row[$value];
// 		$show = $row[$list];
// 		$col2 = ($list2 <> "") ? " [ " . $row[$list2] . " ]":"";
// 		$sel = ($key == $selected) ? 'selected' : '';
//  	$str .="<option value='$key' $sel > $show  $col2 </option>";
// 	}
// 	return $str;
// }

function dropdown_list($tablename, $value, $list, $selected = null, $list2 = null)
{
	global $con;

	// Convert comma-separated string to array if needed
	if (!is_array($selected) && !empty($selected)) {
		$selected = explode(',', $selected);
	}

	$query = "SELECT * FROM $tablename WHERE status = 'ACTIVE' ORDER BY $list";
	$res = mysqli_query($con, $query) or die("Creating Drop down Error: " . mysqli_error($con));

	$str = '<option value=""> Search & Select </option>';

	while ($row = mysqli_fetch_array($res)) {
		$key = $row[$value];
		$show = $row[$list];
		$col2 = ($list2 != "") ? " [ " . $row[$list2] . " ]" : "";

		// Mark selected if in array
		$sel = (is_array($selected) && in_array($key, $selected)) ? 'selected' : '';

		$str .= "<option value='$key' $sel > $show $col2 </option>";
	}
	return $str;
}




function dropdown_list_multiple($tablename, $value, $list, $selectedArr = null)
{
	global $con;
	$i = 0;
	$query = "select * from $tablename where status ='ACTIVE' order by $list";
	$res = mysqli_query($con, $query) or die(" Creating Drop down Error : " . mysqli_error($con));
	while ($row = mysqli_fetch_array($res)) {
		$key = $row[$value];
		$show = $row[$list];
		$found = array_search($key, $selectedArr);
	?>
		<option value='<?php echo $key; ?>' <?php if ($found != '') echo "selected"; ?>><?php echo $show; ?></option>
	<?php
	}
}

function check_list($name, $array_list, $selected_str = null, $height = '160px')
{
	$selected = explode(',', $selected_str);
	$str ='';
	$str .= "<div style='overflow-y:auto;height:$height' class='mt-1'>";
	$fun = "selectall('$name')";
	$str .= "<div class='flex alert alert-primary alert-dismissible p-2'> <span onclick=$fun>  <input type ='checkbox' style='float-right' >  Click to Select All  </span> </div>";

	
	foreach((array)array_filter($array_list) as $list) {
		$checked = null;
		$x = array_search(trim($list), array_map('trim', $selected));

		if ($x >= -1) {
			$checked = 'checked';
		}
		//$str .='<div class="input-group-text">';
		$str .='<div class="checkbox">';
		$str .='<input type="checkbox" value="'.$list.'" id="Checkbox_'. $list . '"'.$checked.' name="'. $name . '[]"> ';

		//$str .='</div><input type="text" class="form-control" placeholder="Checkbox">';
		$str .='<label for="Checkbox_'. $list .'"> '. $list .'</label>';
		$str .='</div>';

	}
	$str .='</div>';
	return $str;
}

function create_list($table_name, $field,  $whereArr = null)
{
	global $con;
	$cols = array();
	if ($whereArr != null) {
		foreach((array)$whereArr as $key => $value) {
			$newvalue = preg_replace('/[^A-Za-z.@,:+0-9\-_]/', ' ', $value);
			$where[] = "$key = '$newvalue'";
		}
		$sql = "select distinct($field) from " . $table_name . " WHERE " . implode('and ', $where);
	} else {
		$sql = "select distinct($field) from " . $table_name;
	}

	$res = mysqli_query($con, $sql) or die(" Error in creating List : " . mysqli_error($con));
	if (mysqli_num_rows($res) >= 1) {
		while ($row = mysqli_fetch_assoc($res)) {
			$list[] = $row[$field];
		}
	} else {
		return null;
	}
	return $list;
}

function state_list()
{
	return create_list('op_sdb', 'state');
}

function district_list($state = 'bihar')
{
	return create_list('op_sdb', 'district', ['state' => $state]);
}

function block_list($district = 'saran')
{
	return create_list('op_sdb', 'block', ['district' => $district]);
}
// GET REMOTE FILE SIZE 
function remote_file_size($url)
{
	// Assume failure.
	$result = -1;

	$curl = curl_init($url);

	// Issue a HEAD request and follow any redirects.
	curl_setopt($curl, CURLOPT_NOBODY, true);
	curl_setopt($curl, CURLOPT_HEADER, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	//curl_setopt( $curl, CURLOPT_USERAGENT, get_user_agent_string() );

	$data = curl_exec($curl);
	curl_close($curl);

	if ($data) {
		$content_length = "unknown";
		$status = "unknown";

		if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
			$status = (int)$matches[1];
		}

		if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
			$content_length = (int)$matches[1];
		}

		// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		if ($status == 200 || ($status > 300 && $status <= 308)) {
			$result = $content_length;
		}
	}
	$filesize = round($result / (1024 * 1024), 2); // kilobytes with two digits
	return $filesize;
}
/*=============== CONFIG MANAGAMENT ===========*/

function string_to_array($inputString) {
    // Check if the input string is in JSON object format
    $isObject = is_object(json_decode($inputString));

    // Check if the input string is in JSON array format
    $isArray = is_array(json_decode($inputString));

    // Check if the input string is a comma-separated value
    $isCSV = strpos($inputString, ',') !== false;

    // Initialize the result array
    $resultArray = [];

    if ($isObject || $isArray) {
        // If it's a JSON object or array, directly decode it
        $resultArray = json_decode($inputString, true);
    } elseif ($isCSV) {
        // If it's a comma-separated value, explode and trim the values
        $csvArray = explode(',', $inputString);
        $resultArray = array_map('trim', $csvArray);
    } else {
        // If none of the above conditions match, return an empty array
        $resultArray = [];
    }

    return $resultArray;
}


function update_config()
{
	global $_CONFIG;
	foreach((array)$_CONFIG as $key => $value) {
		$arr['option_name'] = $key;
		if (is_array($value)) {
			$arr['option_type'] = 'LIST';
			$arr['option_value'] = implode(",", $value);
		} else {
			$arr['option_type'] = 'SINGLE';
			$arr['option_value'] = $value;
		}

		$rescheck = get_data('op_config', $key, null, 'option_name');

		if ($rescheck['count'] == 0) {
			$res = insert_data('op_config', $arr);
		} else {
			$res = update_data('op_config', $arr, $key, 'option_name');
		}
	}
	//print_r($res);
	//return $res;
}

function set_config($key, $value = null)
{
	$arr['option_name'] = $key;
	if (is_array($value)) {
		$arr['option_value'] = json_encode($value);
	} else {
		$arr['option_value'] = $value;
	}
	$rescheck = get_data('op_config', $key, null, 'option_name');
	if ($rescheck['count'] == 0) {
		$res = insert_data('op_config', $arr);
	} else {
		$res = update_data('op_config', $arr, $key, 'option_name');
	}
	return $res;
}

function get_config($key)
{
	$res = get_data('op_config', $key, 'option_value', 'option_name');
	if ($res['count'] > 0) {
		return $res['data'];
	} else {
		return null;
	}
}

function delete_config($key)
{
	$res = delete_data('op_config', $key, 'option_name');
	return $res;
}

function all_config()
{
    $tbls = table_list();
    
    if($tbls['count']==0)
    {
       $res = direct_sql_file("system/opex_db.sql");
    }
	$vardata=[];
	$res = get_all('op_config');
	foreach((array)$res['data'] as $data) {
		$key  = $data['option_name'];
		$value  = $data['option_value'];
		
    	if ($data['option_type'] == 'LIST') {
 			// $value = explode(",", $data['option_value']); //Old Concept
            $vardata[$key] = string_to_array($value);
		}
		else{
		    $vardata[$key] = $value;
		}
	}
	return $vardata;
}

function create_log($arMsg)
{

    // Check Log Folder Exist or Create
    if (!file_exists('../log')) { 
	    mkdir("../log", 0755, true);
    }
	//define empty string                                 
	$stEntry = "";
	//get the event occur date time,when it will happened  
	$arLogData['event_datetime'] = '[' . date('D Y-m-d h:i:s A') . '] [client ' . $_SERVER['REMOTE_ADDR'] . ']';
	//if message is array type  
	if (is_array($arMsg)) {
		//concatenate msg with datetime  
		foreach((array)$arMsg as $msg)
			$stEntry .= $arLogData['event_datetime'] . " " . $msg . "\r\n";
	} else {   //concatenate msg with datetime  

		$stEntry .= $arLogData['event_datetime'] . " " . $arMsg . "\r\n";
	}
	//create file with current date name  
	$stCurLogFileName = '../log/log_' . date('Ymd') . '.txt';
	//open the file append mode,dats the log file will create day wise  
	$fHandler = fopen($stCurLogFileName, 'a+');
	//write the info into the file  
	fwrite($fHandler, $stEntry);
	//close handler  
	fclose($fHandler);
}

function amount_in_word($number)
{
	$decimal = round($number - ($no = floor($number)), 2) * 100;
	$hundred = null;
	$digits_length = strlen($no);
	$i = 0;
	$str = array();
	$words = array(
		0 => '', 1 => 'one', 2 => 'two',
		3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
		7 => 'seven', 8 => 'eight', 9 => 'nine',
		10 => 'ten', 11 => 'eleven', 12 => 'twelve',
		13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
		16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
		19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
		40 => 'forty', 50 => 'fifty', 60 => 'sixty',
		70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
	);
	$digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
	while ($i < $digits_length) {
		$divider = ($i == 2) ? 10 : 100;
		$number = floor($no % $divider);
		$no = floor($no / $divider);
		$i += $divider == 10 ? 1 : 2;
		if ($number) {
			$plural = (($counter = count($str)) && $number > 9) ? 's' : null;
			$hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
			$str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
		} else $str[] = null;
	}
	$Rupees = implode('', array_reverse($str));
	$paise = ($decimal > 0) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
	$netamt =  ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
	return ucwords($netamt);
}

function expiry($start_date) // service Start Date
{
    $exp_date = date('Y-m-d',strtotime("+365 day", strtotime($start_date)));
    $cur_date= date("Y-m-d");
    $earlier = new DateTime($exp_date);
    $later = new DateTime($cur_date);

    $da = $later->diff($earlier)->format("%r%a");
	if ($da < 0 ) {
		die("Subscription Expired ! Please Contact to Service Provider");
	} else {
		return "$da days";
	}
}


function find_in_string($str, $item)
{
	$parts = explode(',', $str);
	$st = "NO";
	while (($i = array_search(trim($item), $parts)) !== false) {
		$st = "YES";
		break;
	}
	return $st;
}

function add_to_string($str, $item)
{
	if ($str != '') {
		$parts = explode(',', $str);

		if (array_search($item, $parts) > -1) {
			return $str;
		} else {
			array_push($parts, trim($item));
			return implode(',', $parts);
		}
	} else {
		return $item;
	}
}


function remove_from_string($str, $item)
{
	$parts = explode(',', $str);

	while (($i = array_search(trim($item), $parts)) !== false) {
		unset($parts[$i]);
	}

	return implode(',', $parts);
}

function resize_image($file, $w, $h, $crop = FALSE)
{
	list($width, $height) = getimagesize($file);
	$r = $width / $height;
	if ($crop) {
		if ($width > $height) {
			$width = ceil($width - ($width * abs($r - $w / $h)));
		} else {
			$height = ceil($height - ($height * abs($r - $w / $h)));
		}
		$newwidth = $w;
		$newheight = $h;
	} else {
		if ($w / $h > $r) {
			$newwidth = $h * $r;
			$newheight = $h;
		} else {
			$newheight = $w / $r;
			$newwidth = $w;
		}
	}
	$src = imagecreatefromjpeg($file);
	$dst = imagecreatetruecolor($newwidth, $newheight);
	imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

	return $dst;
}


// FORM AAROGYAARTH ROLE & PERMISSION 

function check_role($table_name, $user_type, $role_name = 'can_view')
{
		$result['data'] = '<input type="checkbox" class="update_role" value="remove" data-table="' . $table_name . '"  data-user="' . $user_type . '" data-role="' . $role_name . '" checked>';
		$result['status'] = 'YES';
		return $result;
}



function btn_delete($table, $id,  $disabled = "")
{
	global $user_id;
	global $user_type;
	$str = '';
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN'){
		$str = "<button class='delete_btn btn btn-danger btn-sm'  data-table='$table' data-id='$id' data-pkey='id' title='Detete This Permanently' $disabled > <i class='fa fa-trash'></i> </button> ";
	} else {
		if (check_role($table, $user_id, 'can_delete')['status'] == 'YES') {
			$str = "<button class='delete_btn btn btn-danger btn-sm'  data-table='$table' data-id='$id' data-pkey='id' title='Detete This Permanently' $disabled > <i class='fa fa-trash'></i> </button> ";
		}
	}
	return $str;
}


function btn_delete_multiple($table_name)
{
	global $user_id;
	global $user_type;
	$str = '';
	if ($user_type == 'DEV') {
		$str = "<button class='btn btn-danger btn-sm' id='delete_btn' title='Delete Selected' data-table ='$table_name'> <i class='fa fa-trash'></i> </button>";
	} else {
		if (check_role($table_name, $user_id, 'can_delete')['status'] == 'YES') {
			$str = "<button class='btn btn-danger btn-sm' id='delete_btn' title='Delete Selected' data-table ='$table_name'> <i class='fa fa-trash'></i> </button>";
		}
	}
	return $str;
}

function btn_simple($table,$id, $arr)
{
  global $user_type;
  global $user_name;
  $str = '';
    $arr = explode(',',$arr);
  $clname = $arr[0];
  $icon = $arr[1];
  $title = $arr[2];
  $data = json_encode(get_data($table,$id)['data']);
  $res =  get_multi_data('op_role', array('table_id'=>$table, 'role_name'=>$user_type));

  if($res['count']>0)
  {
    $permission =$res['data'][0]; // Check Permission 
  
      if($user_type =='DEV')
        {
          $str = "<a href='javascript:void(0)'  type='button' data-all='$data' class='$clname btn btn-info btn-sm text-light' data-title='$title'><i class='fa fa-$icon'></i></a>&nbsp;";
        }
      else if($permission['can_edit'] =='YES'){
           $str = "<a href='javascript:void(0)'  type='button' data-all='$data' class='$clname btn btn-info btn-sm text-light' data-title='$title'><i class='fa fa-$icon'></i></a>&nbsp;";
        } else {
        $str ='';
      }
  }
  return $str;
}

function btn_remove($table_name, $id,  $disabled = "")
{
	global $user_name;
	global $user_type;

	$str ='';
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
	    {
	    $str = "<button class='remove_btn btn btn-dark btn-sm'  data-table='$table_name' data-id='$id' data-pkey='id' title='Sure to Remove Data' $disabled > <i class='fa fa-close'></i> </button> ";
		}
	else{
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
    	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$row =get_data($table_name, $id)['data'];
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    		if($permission['can_remove'] =='YES')
    			{
    		$str = "<button class='remove_btn btn btn-dark btn-sm'  data-table='$table_name' data-id='$id' data-pkey='id' title='Sure to Remove Data' $disabled > <i class='fa fa-close'></i> </button> ";
    			}
    		else{
    		$str ='';
    		}
    	  }
	}
	return $str;
}

function btn_remove_multiple($table_name)
{
	global $user_name;
	global $user_type;

	$str ='';
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
	    {
	    $str = "<button class='btn btn-dark btn-sm' id='remove_btn' title='Remove Selected' data-table ='$table_name'> <i class='fa fa-close'></i> </button>";
		}
	else{
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
    	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    		
    		if($permission['can_remove'] =='YES')
    			{
    		$str = "<button class='btn btn-dark btn-sm' id='remove_btn' title='Remove Selected' data-table ='$table_name'> <i class='fa fa-close'></i> </button>";
    			}
    		else{
    		$str ='';
    		}
    	  }
	}
	return $str;
}

function btn_restore($table, $id, $status)
{
	$str = "<button class='active_block btn btn-dark btn-sm'  data-table='$table' data-id='$id' data-pkey='id' data-status='$status' title='Sure to Restore Data'> <i class='fa fa-undo'></i> </button> ";
	return $str;
}

function btn_login_as($table, $id, $status)
{
	$udata = get_data($table,$id)['data'];
	$user_name = $udata['user_name'];
	$user_pass = $udata['user_pass'];
	$str = "<button class='login_as btn btn-warning btn-sm'  data-table='$table' data-id='$user_name' data-pkey='id' data-code='$user_pass' title='Login As This Account'> <i class='fa fa-key'></i> </button> ";
	return $str;
}

function btn_edit($table_name, $id, $page_url='add', $btn = 'btn-info', $icon = 'fa-edit', $title = 'Edit')
{
	global $user_type;
	global $user_name;
	global $base_url;
	$folder = (get_data('op_table',$table_name,'status','table_id')['data'] =='LOCKED')?"system":"public";
	if($page_url='add')
	{
	  $page_url = $base_url. $folder."/".$table_name.'_add';  
	}
	$link = $page_url . "?link=" . encode("id=" . $id);
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = "<a href='$link' class='edit_data btn $btn btn-sm text-light' title='$title'> <i class='fa $icon'></i></a> ";
		}
	else{
	        $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
        	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
        	$str = '';
        	if($res['count']>0)
        	{
        		$permission =$res['data'][0]; // Check Permission 
        	
        		if($permission['can_edit'] =='YES'){
        			$str = "<a href='$link' class='edit_data btn $btn btn-sm text-light' title='$title'> <i class='fa $icon'></i></a> ";
        		}
        		else{
        		$str = '';
        		}
        	}
	}
	return $str;
}
function btn_add($table_name)
{
	global $user_type;
	global $user_name;
	if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = '<a href="'.$table_name.'_add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
			//$str = '<a href="add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
		}
	else{
	        $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
        	$res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
        	$str = '';
        	if($res['count']>0)
        	{
        		$permission =$res['data'][0]; // Check Permission 
        	
        		if($permission['can_add'] =='YES'){
        			//$str = '<a href="'.$table_name.'_add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
        			$str = '<a href="add" class="btn btn-success btn-sm"   title="Add New"> <i class="fa fa-plus"></i> </a>';
        		}
        		else{
        		$str = '';
        		}
        	}
	}
	return $str;
}

function btn_view($table_name, $id, $title = '')
{
    global $base_url;
	global $user_type;
	global $user_name;
	$view_link = $base_url.'system/view_data.php?link=' . encode('table=' . $table_name . '&id=' . $id);
    if($_SESSION['user_type'] =='DEV' or $_SESSION['user_type'] =='ADMIN')
		{
			$str = "<a data-href='$view_link' class='view_data btn btn-success btn-sm text-light' data-title='$title'><i class='fa fa-eye'></i></a> ";
		}
	else{
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_view'] =='YES'){
    
    			$str = "<a data-href='$view_link' class='view_data btn btn-success btn-sm text-light' data-title='$title'><i class='fa fa-eye'></i></a> ";
    		} else {
    			$str ='';
    		}
    	}
	}
	return $str;
}
function btn_save($table_name)
{
	global $user_type;
	global $user_name;

	if($user_type =='DEV')
		{
			$str = "<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button> ";
		}
	else{
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$user_type));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_add'] =='YES' || $permission['can_edit'] =='YES'){
    
    			$str = "<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button>";
    		} else {
    			$str ='';
    		}
    	}
	}
	return $str;
}


function btn_about($table, $id ,$title ='About' )
	{
		global $user_type;
		global $user_name;
		$view_link = 'system/view_data.php?link='.encode('table='.$table.'&id='.$id);
		if($user_type =='DEV')
			{
			$str = " <a data-href='$view_link' class='view_data ' title='click to view details' data-title='$title'><i class='fa fa-info-circle'></i></a> ";
			}
		else{
		    $res =  get_multi_data('op_role', array('table_id'=>$table, 'role_name'=>$user_type));
    
        		if($res['count']>0)
        		{
        			$permission =$res['data'][0]; // Check Permission 
        		}
        		else if($permission['can_view'] =='YES'){
        
        			$str = " <a data-href='$view_link' class='view_data' title='click to view details' data-title='$title'><i class='fa fa-info-circle'></i></a> ";
        		} else {
        			$str ='';
        		}
			}
		return $str;
									
	}

function btn_reply($table, $id ,$col, $msg ='Reply Box' )
{
	$view_link = 'view_data.php?link='.encode('table='.$table.'&id='.$id);
	$str ="<button class='reply_btn btn btn-dark btn-sm' data-id='$id' data-table='$table' data-col='$col' ><i class='fa fa-reply'></button>";
	return $str ;										
}

function btn_link($table, $id ,$link, $icon='link')
{
	$view_link = $link.'?link='.encode('table='.$table.'&id='.$id);
	$str ="<a class='reply_btn btn btn-dark btn-sm' href='$view_link' ><i class='fa fa-$icon'></a>";
	return $str ;										
}	

	
function all_back_images()
{
	$vardata=[];
	$res = get_all('back_img', '*', ['status' => 'ACTIVE']);
	foreach((array)$res['data'] as $data) {
		$key  = $data['type'];
		$value  = $data['photo'];
		if ($data['status'] == 'ACTIVE') {
			$value = $data['photo'];
		}
		$vardata[$key] = $value;
	}
	return $vardata;
	//	extract($vardata);
}
function remote_file_exists($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); # handles 301/2 redirects
	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpCode == 200) {
		return false;
	}else{
		return true;
	}
}


function print_button($link,$icon='file-pdf',$title=null,$bsClr='success'){
    $btn = "<a href='$link' target='_blank' title='$title' class='mx-1'>
    <box-icon type='solid' class='rounded bg-$bsClr' name='$icon' color='#ffffff'></box-icon></a>";
	return $btn;
}


function curr_url(){
   $url = "HTTP" . (($_SERVER['SERVER_PORT'] == 443) ? "S" : "") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
   return $url;
}


function show_img2($filePath) {
    // Check if the Fileinfo extension is available
    if (function_exists('finfo_open')) {
        // Create a fileinfo resource
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        // Get the MIME type of the file
        $fileType = finfo_file($finfo, $filePath);

        // Close the fileinfo resource
        finfo_close($finfo);
    } else {
        // If Fileinfo extension is not available, use a fallback method to get the MIME type
        $fileType = mime_content_type($filePath);
    }

    // Check if it's an image
    if (strpos($fileType, 'image/') === 0) {
        // Display the image preview
        echo "<img src='$filePath' alt='Image Preview' />";
    }
    // Check if it's an audio
    elseif (strpos($fileType, 'audio/') === 0) {
        // Display the audio player
        echo "<audio controls>
                  <source src='$filePath' type='$fileType'>
                  Your browser does not support the audio element.
              </audio>";
    }
    // Check if it's a video
    elseif (strpos($fileType, 'video/') === 0) {
        // Display the video player with a preview image
        echo "<video width='320' height='240' controls>
                  <source src='$filePath' type='$fileType'>
                  Your browser does not support the video tag.
              </video>";
    }
    // For any other file type, provide a download link
    else {
        echo "<a href='$filePath' download>Download File</a>";
    }
}


function show_img($img_name, $width='50', $height='50')
{
	global $base_url;
	$str ='';
	
	$remote_url = $base_url.'upload/' . $img_name;
	$no_img_url = $base_url.'upload/no_photo.jpg';
	$image_url = (remote_file_exists($remote_url) or $img_name =='')?$no_img_url:$remote_url;
// 		$extention = pathinfo($img_name, PATHINFO_EXTENSION);

	$str = "<img src='$image_url' width='$width' height='$height'>";
	$finfo = finfo_open(FILEINFO_MIME_TYPE);

        // Get the MIME type of the file
    //$fileType = finfo_file($finfo, '..upload/' . $img_name);
	return $str; //$fileType;
} 

function show_status($status, $type ='badge')
{
    $str ='';
    if($status=='ACTIVE' or $status=='YES' or $status=='PAID' or $status=='INCOME' or $status=='DISPATCHED' or $status=='BOOKED' or  $status=='TEAM'  )
    {
       $str ="<span class='$type badge badge-success-light'> $status </span>"; 
    }
    else if($status=='BLOCK' or $status=='NO' or $status=='UNPAID'  or $status=='EXPENCE' or $status=='REJECTED' or $status=='EXPIRED'   or $status=='SELF')
    {
       $str ="<span class='$type badge badge-danger-light'> $status </span>";
    }
    else if($status=='SHOW' or $status=='VERIFIED'  or $status=='CLIENT' or $status=='RESERVED'  )
    {
      $str ="<span class='$type badge badge-primary-light'> $status </span>";
    }
    else if($status=='PENDING' or $status=='HIDE' or $status=='RESULT OUT' or $status=='REGISTERED' )
    {
       $str ="<span class='$type badge badge-warning-light'> $status </span>";
    }
    else{
        $str ="<span class='$type badge badge-info-light'> $status </span>";
    }
    return "<span class='margin-auto'> $str </span>";
}


function create_form($table_name, $id, $isedit='yes', $action ='master_update_data', $type='master', $field_list = []) {
    // Get the column names from the database
    global $user_type;
	global $today;
	global $working_date;
	$farr = [];
	if($_SESSION['user_type'] !='DEV')
		{
	
	   
	    $table_id  = get_data('op_table', $table_name,'id','table_id')['data'];
	    $res =  get_multi_data('op_role', array('table_id'=>$table_id, 'role_name'=>$_SESSION['user_type']));
    	$str = '';
    	if($res['count']>0)
    	{
    		$permission =$res['data'][0]; // Check Permission 
    	
    		if($permission['can_add'] =='NO' and $isedit =='no')
    		{
				$farr['add_msg']='<div class="alert alert-danger " role="alert">
				<div class="alert-icon">
					<i class="fas fa-exclamation-triangle"></i>
				</div>
				<div class="alert-message">
					<strong>Sorry !</strong> Dont Have permission  to add !
				</div>
			</div>';
    		    return $farr;
    		}
			
    		if($permission['can_edit'] =='NO' and $isedit =='yes')
    		{
				$farr['edit_msg']='<div class="alert alert-warning " role="alert">
				<div class="alert-icon">
					<i class="fas fa-exclamation-triangle"></i>
				</div>
				<div class="alert-message">
					<strong>Sorry !</strong> Dont Have permission  to Edit !
				</div>
			</div>';
    		    return $farr;
    		}
    	}
	    }
	
	$res  = get_all('op_master_table', '*', array('table_name' => $table_name,'is_edit'=>'YES','status'=>'ACTIVE'), 'display_id');
		
	$farr['form_start'] = "<form action ='$action' method ='post' enctype='multipart/form-data' id='update_frm' type='$type'>";
	$farr['id'] = "<input type='hidden' name='id' value='$id'>";
	$farr['table_name'] = "<input type='hidden' name='table_name' value='$table_name'>";
	$farr['isedit'] = "<input type='hidden' name='isedit' value='$isedit'>";
// 	$farr['alert']	= '<div class="alert alert-warning alert-dismissible" role="alert">
// 	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
// 	<div class="alert-icon">
// 		<i class="far fa-fw fa-bell"></i>
// 	</div>
// 	<div class="alert-message">
// 		<strong>Notice</strong> All field with star(*) marks is mandatory !
// 	</div>
// </div>';
	$farr['row_start']	= "<div class='row'>";
	
	if($res['count']>0)
	{
		foreach((array)$res['data'] as $row )
		{
		    if (!empty($field_list) && !in_array($row['column_name'], $field_list)) {
                continue; // Skip this field if not in the list
            }
            
         	$value= get_data($row['table_name'], $id,$row['column_name'])['data'];
         	//print_r($value);
		
			$extra = ($row['is_required'] =='YES')? " required ":" ";
			$mark = ($row['is_required'] =='YES')? "<span class='text-danger' title='This field is mendatory'>* </span>":" ";
			$extra .= $row['extra'];
			$display_name = ($row['display_name'] ==null)? add_space($row['column_name']): $row['display_name'];
			$display_name = $display_name . $mark;
			if ($row['is_display'] == 'YES') {
				//echo $row['input_type'];

				switch ($row['input_type']) {
					case 'Date':
						$value = ($value == '0000-00-00' or $value =='') ? $today : $value;
						$str = create_input($row['column_name'], 'date', $value, $display_name, $extra);
						break;

					case 'Datetime':
						$str= create_input($row['column_name'], 'datetime-local', $value, $display_name);
						break;
					
					case 'Color':
						$str= create_input($row['column_name'], 'Color', $value, $display_name);
						break;

					case 'Time':
						$str=  create_input($row['column_name'], 'time', $value, $display_name);
						break;
						
					case 'Year':
						$str ='<div class="form-group col-md-4">';
						$str .=	"<label>$display_name</label>";
						$str .= '<input type="number" class="form-control" min="1950" max="2099" step="1" name = "'.$row['column_name'].'" value="'.$value.'" />';
						$str .='</div>';
						break;
					
					case 'Month':
						$str=  create_input($row['column_name'], 'month', $value, $display_name);
						break;
					
					case 'Week':
						$str=  create_input($row['column_name'], 'week', $value, $display_name);
						break;
					
					case 'Label':
						$str ='<div class="form-group col-12">';
						$str .=	"<div class='label'><i class='fa $extra'> </i> $display_name</div></div>";
						break;

					case 'Camera':
						$path = ($value=='')?'no_image.jpg':$value;
						$str ='<div class="form-group col-md-4">';
						$str .=	"<label>$display_name</label>";
						$str .="<div id='display'>";
						$str .="<img src='upload/$path' width='150px' height='160px' id='result'></div>";
						$str .="<input type='hidden' name='{$row['column_name']}' id='targetimg' class='form-control' readonly value='$value'>";
						$str .="<span id='uploadarea' class='btn btn-secondary'>UPLOAD /CHANGE PHOTO </span></div></div>";
						break;

					case 'Whatsapp':
					case 'Mobile':
						$extra .= " maxlength='10' minlength='10' ";
						$str = create_input($row['column_name'], 'text', $value, $display_name, $extra);
						break;

					case 'Email':
						$str= create_input($row['column_name'], 'email', $value, $display_name, $extra);
						break;

					case 'Permission':
						$switch_str= show_switch($table_name,  $id, $row['column_name'], $value );
						$str= '<div class="form-group col-md-3">
						<label> ' . $display_name . ' </label>'.
						$switch_str
						.'</div>';
						break;

					case 'Number':
					case 'Rs':
						$extra .= " min =0 ";
						$str= create_input($row['column_name'], 'number', $value, $display_name, $extra);
						break;

					case 'RTF':
					    $value =($value =='')?$value:base64_decode($value);
						$str = '<div class="form-group col-12">
						<label> ' . $display_name . ' </label>' .
								'<textarea class="summernote" style="width:100" ' . $extra . '>' . $value . '</textarea>
						<input type="hidden" class="summerdata" name="' . $row['column_name'] . '">
						</div>';
						break;

					
					case 'Multiline':
						$str = '<div class="form-group mt-2 col-4">
					<label> ' . $display_name . ' </label>
					<textarea  name="'.$row['column_name'] .'" id="'.$row['column_name'] .'" class="form-control ">' .$value. '</textarea>
					</div>';
						break;

					case 'List-Dynamic':
						$inputvalue = explode(',', $row['input_value']);
						//print_r($inputvalue);
						$fname = str_contains($extra, 'multiple') ? $row['column_name'].'[]' : $row['column_name'];
						$str = '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
						$str .= "<select name='{$fname}'  id='{$row['column_name']}' class='form-select select2 ' $extra >";
						$str .= dropdown_list($inputvalue[0], 'id', $inputvalue[1], $value, @$inputvalue[2]);
						$str .= "</select></div>";
						break;
						
					case 'List-Where':
						$inputvalue = explode(',', $row['input_value']);
						//print_r($inputvalue);
						$str = '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
						$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='form-select select2 required'>";
						
						$ex_arr = explode(",",$extra);
						$str .= dropdown_where($inputvalue[0], 'id', $inputvalue[1], array($ex_arr[0]=>$ex_arr[1]), $value);  
					    
						$str .= "</select></div>";
						break;

					case 'List-Static':
					case 'Status':
						$str ='';
						if ($isedit =='no' and $row['input_type'] =='Status'){
							$str =  create_input($row['column_name'], 'hidden', 'ACTIVE', $display_name, 'Readonly');
						}
						else{
						$option_value 	= get_data('op_config', $row['input_value'], 'option_value')['data'];
						$option_arr 	= explode(',', $option_value);
						$str =  '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
						$str .=  "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='form-select select2' $extra>";
						$str .=  dropdown($option_arr, $value);
						$str .= "</select></div>";
						}
						
						break;
					
					case 'CheckList-Dynamic':
						$inputvalue = explode(',', $row['input_value']);
						$arr_list = create_list($inputvalue[0],$inputvalue[1]);
						$str =  '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
						$str .= check_list($row['column_name'],$arr_list, $value);
						$str .=  '</div>';
						break;
					
					case 'CheckList-Static':
							$option_value 	= get_data('op_config', $row['input_value'], 'option_value')['data'];
							$option_arr 	= explode(',', $option_value);

							$str =  '<div class="form-group mt-2 col-md-4">
							<label> ' . $display_name . ' </label>';
							$str .= check_list($row['column_name'],$option_arr, $value);
							$str .=  '</div>';
							break;
					
					case 'Photo':
					case 'Docs':
						$str =  '<div class="form-group  col-md-4 mt-3">
						<label>'. $display_name .'</label>
						<input type="hidden" name="'.$row['column_name'].'" id="target_'.$row['column_name'].'" value="'.$value.'">
						<input class="upload_img form-control" type="file" id="'.$row['column_name'].'" accept="image" data-table="'.$table_name.'" data-field="'.$row['column_name'].'" '. $extra.'>
						<small> Only a valid images or Docs file. </small>';
						$str .=  '<div id="' . $row['column_name'] . '_display">';
						if ($isedit == 'yes') {
							//$str .=  show_img($value);
							$str .=  "<a href='../upload/{$value}' class='btn btn-border border-primary'> <i class='fa fa-download'></i> Download </a>";
						}
						$str .=  '</div></div>';
						break;
                    
                   case 'Multi-Photo':
                   case 'Multi-Docs':
                        $str = '<div class="form-group col-md-4 mt-3">
                            <label>'. $display_name .'</label>
                            <input type="hidden" name="'.$row['column_name'].'" id="target_'.$row['column_name'].'" value="'.$value.'">
                            <input class="upload_multi_img form-control" multiple type="file" 
                                id="'.$row['column_name'].'" 
                                accept="image/png, image/gif, image/jpeg, application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document" 
                                data-table="'.$table_name.'" data-field="'.$row['column_name'].'" '. $extra.'>
                            <small>Allowed: JPG, PNG, PDF, DOC, DOCX</small>';
                        
                        $str .= '<div id="'.$row['column_name'].'_display">';
                        if ($isedit == 'yes' && !empty($value)) {
                            $images = explode(',', $value);
                            foreach ($images as $img) {
                                $file = basename($img); // prevent path traversal
                                $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                if (in_array($ext, ['pdf','doc','docx'])) {
                                    $str .= "<a href='../upload/$file' download>Download ($ext)</a><br>";
                                } else {
                                    $str .= show_img($file);
                                }
                            }
                        }
                        $str .= '</div></div>';
                        break;

					case 'State':
						$str = '';
						if ($isedit == 'no' and $row['input_type'] == 'Status') {
							$str = create_input($row['column_name'], 'text', 'ACTIVE', $display_name, 'Readonly');
						} else {
							$option_arr = state_list();
							$str = '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
							$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='state_name form-select select2' $extra>";
							$str .= dropdown($option_arr, $value);
							$str .= "</select></div>";
						}
						break;

					case 'District':
						$str = '';
						if ($isedit == 'no' and $row['input_type'] == 'Status') {
							$str = create_input($row['column_name'], 'text', 'ACTIVE', $display_name, 'Readonly');
						} else {
							$option_arr = district_list();
							$str = '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
							$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='district_name form-select select2' $extra>";
							$str .= dropdown($option_arr, $value);
							$str .= "</select></div>";
						}
						break;

					case 'Block':
						$str = '';
						if ($isedit == 'no' and $row['input_type'] == 'Status') {
							$str = create_input($row['column_name'], 'text', 'ACTIVE', $display_name, 'Readonly');
						} else {
							$option_arr = block_list();
							$str = '<div class="form-group mt-2 col-md-4">
						<label> ' . $display_name . ' </label>';
							$str .= "<select name='{$row['column_name']}'  id='{$row['column_name']}' class='block_name form-select select2' $extra>";
							$str .= dropdown($option_arr, $value);
							$str .= "</select></div>";
						}
						break;
					case 'Popup':
						$str = create_input($row['column_name'], 'text', $value, $display_name, 'readonly');
						break;	
					case 'Edit-Box':
					    $str =  create_input($row['column_name'], 'text', $value, $display_name, 'edit_box');
						break;
						
					default:
						$str =  create_input($row['column_name'], 'text', $value, $display_name, $extra);

				}
				$farr[$row['column_name']] = $str;
			}
		}
	}
	$farr['form_end']	= "</div></form>";

	return $farr;
}

function old_create_data_table($table_name, $res, $btn_arr=['btn_view'=>''], $idName ='data-tbl', $dtclass ='data-tbl')
{

	$str = '<div class="table-responsive1">';
	//$str .= '<table id="data-tbl" class="table table-bordered table-striped">';

	$str .= "<table class='$dtclass table table-bordered table-striped' id='$idName' >";

	if($dtclass =='data-tbl')
	{
		$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name, 'status'=>'ACTIVE','is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	}
	else if($dtclass =='simple'){
		$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name, 'status'=>'ACTIVE','is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	}
	else{
		$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name,'status'=>'ACTIVE', 'allow_global_search' => 'YES', 'is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
	}
	if ($get_cols['count'] > 0) {
		$str .= "<thead><tr><td>#</td>";
		foreach((array) $get_cols['data'] as $col) {
			$col_name = ($col['display_name'] == '') ? add_space($col['column_name']) : $col['display_name'];
			$str .= "<th>" . $col_name . "</th>";
		}
		if($dtclass !=''){
		$str .= "<th> Action </th>";
		}
		$str .= "</tr></thead>";
	}


	$str .= '<tbody>';

	if ($res['count'] > 0) {

		foreach((array)$res['data'] as $row) {
			$id = $row['id'];
			$jdata = json_encode($row);
			$str .= "<tr><td><input type='checkbox' value='$id' class='chk' data-json='$jdata'>";
			foreach((array) $get_cols['data'] as $col) {
				$ddata = $row[$col['column_name']]; //Display Data
				if($col['input_type']=='List-Dynamic' or $col['input_type']=='List-Where')
				{
					$input  = explode(',',$col['input_value']);
					
					$dval1 = get_data($input[0],$ddata,$input[1])['data'];

					$dval2 = '';
					if(isset($input[2]) and $input[2]!='')
					{
					$dval2 = " [". get_data($input[0],$ddata,$input[2])['data']. "]"; 
					}
					$x = $dval1 . $dval2; //.$info;
				} 
				else if($col['input_type']=='Permission')
				{
					$x = show_switch($table_name, $id, $col['column_name'], $ddata); 
					
				} 
				
				else if($col['input_type']=='Text-Info')
				{
					$x = btn_about($table_name, $id, $ddata); 
				} 
				
				else if($col['input_type']=='Edit-Box')
				{
				    $ddata= ($ddata=='')?'----':$ddata;
				    $x ="<span class='edit_box p-1' title='Double Click to Edit' data-table='$table_name' data-id='$id' data-column='{$col['column_name']}'> $ddata </span>";
				} 
				
				else if($col['input_type']=='RTF')
				{
					$x = display_value($ddata, $col['input_type'],'data-table'); 
			
				} 
				else {
					$x = display_value($ddata, $col['input_type']);
				}
				$str .= "<td>" . $x . "</td>";
			}
			
			//print_r($btn_arr);
			if($dtclass !='')
			{
				$str .= "<td align='right'>";
				foreach((array)$btn_arr as $fn=>$arg)
				{
					$str .= $fn($table_name, $id, $arg);
				}
				$str .= "</td>";
			}
			
			$str .= "</tr>";
		}
	}
	$str .= '</tbody>';

	$str .= '</table>';
	$str .= '</div>';
	return $str;
}

function create_data_table_hold($table_name, $res, $btn_arr=['btn_view'=>''], $idName ='data-tbl', $dtclass ='data-tbl')
{
    $str = '<div class="table-responsive1">';
    $str .= "<table class='$dtclass table table-bordered table-striped' id='$idName' >";

    // get table column meta
    if($dtclass =='data-tbl' || $dtclass =='simple'){
        $get_cols = get_all('op_master_table', '*', [
            'table_name' => $table_name,
            'status'=>'ACTIVE',
            'is_edit' => 'YES',
            'show_in_table' => 'YES'
        ], 'display_id');
    } else {
        $get_cols = get_all('op_master_table', '*', [
            'table_name' => $table_name,
            'status'=>'ACTIVE',
            'allow_global_search' => 'YES',
            'is_edit' => 'YES',
            'show_in_table' => 'YES'
        ], 'display_id');
    }

    // collect columns that are in $res
    $res_cols = [];
    if (!empty($res['data'][0])) {
        $res_cols = array_keys($res['data'][0]);
    }

    // table header
    if ($get_cols['count'] > 0) {
        $str .= "<thead><tr><td>#</td>";
        foreach((array) $get_cols['data'] as $col) {
            if (!in_array($col['column_name'], $res_cols)) continue; // ✅ skip if not in result

            $col_name = ($col['display_name'] == '') ? add_space($col['column_name']) : $col['display_name'];
            $str .= "<th>" . $col_name . "</th>";
        }
        if($dtclass !=''){
            $str .= "<th> Action </th>";
        }
        $str .= "</tr></thead>";
    }

    // table body
    $str .= '<tbody>';
    if ($res['count'] > 0) {
        foreach((array)$res['data'] as $row) {
            $id = $row['id'];
            $jdata = json_encode($row);
            $str .= "<tr><td><input type='checkbox' value='$id' class='chk' data-json='$jdata'>";

            foreach((array) $get_cols['data'] as $col) {
                if (!in_array($col['column_name'], $res_cols)) continue; // ✅ skip if not in result

                $ddata = $row[$col['column_name']];
                if($col['input_type']=='List-Dynamic' or $col['input_type']=='List-Where') {
                    $input  = explode(',',$col['input_value']);
                    $dval1 = get_data($input[0],$ddata,$input[1])['data'];
                    $dval2 = '';
                    if(isset($input[2]) and $input[2]!='') {
                        $dval2 = " [". get_data($input[0],$ddata,$input[2])['data']. "]"; 
                    }
                    $x = $dval1 . $dval2;
                } else if($col['input_type']=='Permission') {
                    $x = show_switch($table_name, $id, $col['column_name'], $ddata); 
                } else if($col['input_type']=='Text-Info') {
                    $x = btn_about($table_name, $id, $ddata); 
                } else if($col['input_type']=='Edit-Box') {
                    $ddata= ($ddata=='')?'----':$ddata;
                    $x ="<span class='edit_box p-1' title='Double Click to Edit' data-table='$table_name' data-id='$id' data-column='{$col['column_name']}'> $ddata </span>";
                } else if($col['input_type']=='RTF') {
                    $x = display_value($ddata, $col['input_type'],'data-table'); 
                } else {
                    $x = display_value($ddata, $col['input_type']);
                }
                $str .= "<td>" . $x . "</td>";
            }

            if($dtclass !='') {
                $str .= "<td align='right'>";
                foreach((array)$btn_arr as $fn=>$arg) {
                    $str .= $fn($table_name, $id, $arg);
                }
                $str .= "</td>";
            }

            $str .= "</tr>";
        }
    }
    $str .= '</tbody>';

    $str .= '</table>';
    $str .= '</div>';
    return $str;
}


function create_data_table(
    $table_name,
    $res,
    $btn_arr = ['btn_view' => ''],
    $idName = 'data-tbl',
    $dtclass = 'data-tbl',
    $selected_cols = [] // ✅ new param
) {
    $str = '<div class="table-responsive1">';
    $str .= "<table class='$dtclass table table-bordered table-striped' id='$idName' >";

    // get table column meta
    if ($dtclass == 'data-tbl' || $dtclass == 'simple') {
        $get_cols = get_all('op_master_table', '*', [
            'table_name' => $table_name,
            'status' => 'ACTIVE',
            'is_edit' => 'YES',
            'show_in_table' => 'YES'
        ], 'display_id');
    } else {
        $get_cols = get_all('op_master_table', '*', [
            'table_name' => $table_name,
            'status' => 'ACTIVE',
            'allow_global_search' => 'YES',
            'is_edit' => 'YES',
            'show_in_table' => 'YES'
        ], 'display_id');
    }

    // collect columns that are in $res
    $res_cols = [];
    if (!empty($res['data'][0])) {
        $res_cols = array_keys($res['data'][0]);
    }
    
    // ✅ filter by selected_cols if provided
    if (!empty($selected_cols)) {
        $res_cols = array_intersect($res_cols, $selected_cols);
    }
    // table header
    if ($get_cols['count'] > 0) {
        $str .= "<thead><tr><td>#</td>";
        foreach ((array) $get_cols['data'] as $col) {
            if (!in_array($col['column_name'], $res_cols)) continue;

            $col_name = ($col['display_name'] == '') ? add_space($col['column_name']) : $col['display_name'];
            $str .= "<th>" . $col_name . "</th>";
        }
        if ($dtclass != '') {
            $str .= "<th> Action </th>";
        }
        $str .= "</tr></thead>";
    }

    // table body
    $str .= '<tbody>';
    if ($res['count'] > 0) {
        foreach ((array) $res['data'] as $row) {
            $id = $row['id'];
            $jdata = json_encode($row);
            $str .= "<tr><td><input type='checkbox' value='$id' class='chk' data-json='$jdata'>";

            foreach ((array) $get_cols['data'] as $col) {
                if (!in_array($col['column_name'], $res_cols)) continue;

                $ddata = $row[$col['column_name']];
                if ($col['input_type'] == 'List-Dynamic' or $col['input_type'] == 'List-Where') {
                    $input  = explode(',', $col['input_value']);
                    $dval1 = get_data($input[0], $ddata, $input[1])['data'];
                    $dval2 = '';
                    if (isset($input[2]) and $input[2] != '') {
                        $dval2 = " [" . get_data($input[0], $ddata, $input[2])['data'] . "]";
                    }
                    $x = $dval1 . $dval2;
                } else if ($col['input_type'] == 'Permission') {
                    $x = show_switch($table_name, $id, $col['column_name'], $ddata);
                } else if ($col['input_type'] == 'Text-Info') {
                    $x = btn_about($table_name, $id, $ddata);
                } else if ($col['input_type'] == 'Edit-Box') {
                    $ddata = ($ddata == '') ? '----' : $ddata;
                    $x = "<span class='edit_box p-1' title='Double Click to Edit' data-table='$table_name' data-id='$id' data-column='{$col['column_name']}'> $ddata </span>";
                } else if ($col['input_type'] == 'RTF') {
                    $x = display_value($ddata, $col['input_type'], 'data-table');
                } else {
                    $x = display_value($ddata, $col['input_type']);
                }
                $str .= "<td>" . $x . "</td>";
            }

            if ($dtclass != '') {
                $str .= "<td align='right'>";
                foreach ((array) $btn_arr as $fn => $arg) {
                    $str .= $fn($table_name, $id, $arg);
                }
                $str .= "</td>";
            }

            $str .= "</tr>";
        }
    }
    $str .= '</tbody>';

    $str .= '</table>';
    $str .= '</div>';
    return $str;
}



function create_report_table($table_name, $sql, $btn_arr='',  $dtclass ='report-tbl')
{
    global $con;
    $res = direct_sql($sql);
	$str = '<div class="table-responsive1">';
	$str .= "<table id='data-tbl' class='$dtclass table table-bordered table-striped'>";
	
    $get_cols  = mysqli_query($con, $sql);
	    $rct = mysqli_num_rows($get_cols);
	    $cct = mysqli_field_count($con);
	    $str .= "<thead><tr>";
	    for($i = 0; $i < $cct; $i++)
            {
                $field = mysqli_fetch_field_direct($get_cols, $i);
                $col_name = $field->name ;
                if( get_all('op_master_table', '*', array('table_name' => $table_name,'column_name' => $col_name))['count']>0)
                {
              	$cols[] = get_all('op_master_table', '*', array('table_name' => $table_name,'column_name' => $col_name))['data'][0];
              	$col = get_all('op_master_table', '*', array('table_name' => $table_name,'column_name' => $col_name))['data'][0];
              	$col_name = ($col['display_name'] == '') ? add_space($col['column_name']) : $col['display_name'];
                }
                else{
                $cols[] =  $field->name;  
                }
              
              $str .= "<th>" . $col_name . "</th>";
            }
            if($btn_arr !=''){
	        $str .= "<th> Action </th>";
		    }
            $str .= "</tr></thead>";

	$str .= '<tbody>';

	if ($res['count'] > 0) {

		foreach((array)$res['data'] as $row) {
			$row = array_merge($row,['table_name'=>$table_name]);
			$jdata = json_encode($row);
			$id = $row['id'];
			$str .= "<tr>";
			foreach((array) $cols as $col) {
				$ddata = $row[$col['column_name']]; //Display Data
				
				if($col['input_type']=='List-Dynamic' or $col['input_type']=='List-Where')
				{
					$input  = explode(',',$col['input_value']);
					
					$dval1 = get_data($input[0],$ddata,$input[1])['data'];
                	$dval2 = '';
					if(isset($input[2]) and $input[2]!='')
					{
					$dval2 = " [". get_data($input[0],$ddata,$input[2])['data']. "]"; 
					}
					$x = $dval1 . $dval2; //.$info;
				} 
				else if($col['column_name']=='id') {
					$x = display_value($ddata, 'Checkbox','report-tbl', $jdata);
				}
				else if($col['input_type']=='Permission')
				{
					$x = show_switch($table_name, $id, $col['column_name'], $ddata); 
					
				} 
				else if($col['input_type']=='Text-Info')
				{
					$x = btn_about($table_name, $id, $ddata); 
			
				} 
				else {
					$x = display_value($ddata, $col['input_type'],'report-tbl', $jdata);
				}
				$str .= "<td>" . $x . "</td>";
			}
			
			//print_r($btn_arr);
			if($btn_arr !='')
			{
				$str .= "<td align='right'>";
				foreach((array)$btn_arr as $fn=>$arg)
				{
					$str .= $fn($table_name, $id, $arg);
				}
				$str .= "</td>";
			}
			
			$str .= "</tr>";
		}
	}
	$str .= '</tbody>';

	$str .= '</table>';
	$str .= '</div>';
	return $str;
}


function create_server_table($table_name)
    {
        // Create Basic data Table
        $str ="<table class='table table-bordered table-striped' id='server_table' >";
        $str .="<thead><tr>";
        $str .="<th>#</th>";
        
        // Get Visible Column List from Op_master_table
        $get_cols = get_all('op_master_table', '*', array('table_name' => $table_name,'status'=>'ACTIVE', 'is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
        $jdata[] =array("data"=>'id');
        $cols[] ='ID';
         foreach((array)$get_cols['data'] as $col) 
            {
                $jdata[] = array("data"=>$col['column_name']);
                $cols[] = $col_name= add_space($col['column_name']) ;
                $str .= "<th>". $col_name ."</th>"; // Create Column Header 
        	}
        	
        // completing table structure 
        $str .=  "<th>Action</th>";
        $str .=  "</tr></thead>";
	    $str .=  "<tbody></tbody>";
	    $str .=  "</table>";
                                    	    
        $cols[] ='Action';
        $jdata[] =array("data"=>'action');
        
        $res['json'] = json_encode($jdata);
        $res['html'] = $str;
     return $res;
    }

function validate_date($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function display_value($value,$input_type, $container ='data-table')
{
	$str = '';
	switch ($input_type) {
		case 'Date':
			if ($value != '' && validate_date($value)) {
                $str = date('d-M-Y', strtotime($value));
            }
			break;

		case 'Datetime':
			if ($value != '') {
			     $pattern = '/^[0-9]+$/';
			    if (preg_match($pattern, $value)) {
				   	$str = date('d-M-Y h:i A', $value);    
			    }
			    else{
			    	 $str = date('d-M-Y h:i A', strtotime($value));
			    }
			}
			break;

		case 'Time':
			if ($value != '') {
				$str = date('h:i A', strtotime($value));
			}
			break;
		
		case 'Email':
			$str = "<a href='mailto:$value'> $value </a>";
			break;
		
		case 'Whatsapp':
			$str = "<a href='https://wa.me/+91$value'> $value </a>";
			break;
			
		case 'RTF':
			if($container =='data-table')
				{
					$xvalue = substr($value, 0, 3)."****".substr($value, -3); 
					$str = " RTF";
				}
				else{
					$str = base64_decode($value);
				}
			break;

				
		case 'Mobile':
			if($container =='data-table' and $value !='')
				{
					$xvalue = substr($value, 0, 3)."****".substr($value, -3); 
					$str = "<a href='tel:$value'> $xvalue </a>";
				}
				else{
					$str = "<a href='tel:$value'> $value </a>";
				}
			
			break;

		case 'Bloodgroup':
			$str = "🩸 $value ";
			break;

		case 'Text-info':
			$str = "<button  data-bs-container='body' data-bs-toggle='popover' data-bs-placement='top' data-bs-content='$container'>$value</button>";
			break;
		
		case 'Checkbox':
			$str = "<Input type='checkbox' value='$value'/>";
			break;

		case 'Rs':
			
				if($container =='data-table' or 'report-table')
				{
					$str = "<span class='float-end'> ₹ ". $value ."</span>";
				}
				else if($container !='data-table' and $value !=''){
					$str = "₹ ". $value . " (". amount_in_word($value).") ";
				}
				else{
					$str = "₹ ";
				}
			break;


		case 'CheckList-Dynamic':
		case 'CheckList-Static':
			$str ='';
			if($container !='data-table' and $value !=''){
				$arr = explode("," , $value);
				foreach($arr as $el)
				{
					$str .="<li>". $el ."</li>";
				}
			}
			else{
				$str = $value ;
			}
		break;
		
		case 'Photo':
		case 'Image':
		case 'Camera':
			$str = show_img($value);
			break;
		
		case 'Multi-Photo':
		    $arr = explode(",", $value);
		    foreach($arr as $img)
		    {
			$ext = pathinfo($img, PATHINFO_EXTENSION);
		       
		         $str .=  "<a href='{$base_url}upload/$img' download>Download </a>";
		    }
		    break;
		
		case 'Status':
			$str = show_status($value);
			break;
			
		case 'Link':
		case 'Url':
		    $path = parse_url($value, PHP_URL_PATH);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if($ext=='jpg' or $ext =='png' or $ext =='jpeg' or $ext =='gif' )
            {
               $str =($value=="")?"": "<a href='$value' download><img src='$value' width='100' height='60px' download /></a>"; 
            }
            else if($ext=='mp3' or $ext =='wav' or $ext =='amr' or $ext =='opus' )
            {
               $str =($value=="")?"": "<audio controls style='width:100px;height:25px;'><source src='$value' /></audio>"; 
            }
            else if($ext=='mp4' or $ext =='avi' or $ext =='dat' or $ext =='opus' )
            {
               $str =($value=="")?"": "<video controls style='width:180px;height:100px;'><source src='$value' /></video>"; 
            }
            else{
    		$str =($value=="")?"": "<a href='$value' class='badge badge-primary-light' target='blank' download >Download </a>";
            }
    		break;
		
		default:
		    
		    if($container =='data-table' and $value <>'')
				{
		    $link = "<a title='".$value."'>". substr($value,0,20)."..</a>";
		    $str = ($value!='' and strlen($value)<30)?$value:$link;    
				}
			else{
			    $str = $value;
			}

	}
	return $str;
}

function show_switch($table, $id, $name, $status )
{
	if($status =='YES' or $status =='ACTIVE' or $status =='ON')
	{
		$value ='checked';
	}
	else{
		$value ='';
	}

	$str="<div class='form-check form-switch'>
  		<input class='$name yesno form-check-input' type='checkbox' role='switch' data-table='$table' data-id='$id' data-column='$name' data-status='$status' $value>
  		<label class='form-check-label' for='$name' >$status</label>
	</div>";
	return $str;
}

function create_check($name, $value = null, $class = 'fee-month', $checked = null)
{
	$id = remove_space($name);
	$check = "<div class='checkbox'> <input type='checkbox' name ='$id' id='$id' value='$value' class='$class' $checked > <label for='$id'> $name </label></div>";
	echo $check;
}

// Menu Creator 

function create_menu()
{
	global $base_url;
	global $user_type;
	global $user_name;
	$str = '';
	$main = get_all('op_menu', '*', array('type' => 'MAIN', 'status' => 'ACTIVE'), 'display_id');
	foreach ((array) $main['data'] as $menu) {

		if ($main['count'] > 0) {
	
			// Checking Submenu Exist or Not 
			if($_SESSION['user_type']=='DEV' or $_SESSION['user_type']=='ADMIN' )
			{
			    $sub = get_all('op_menu', '*', array('type' => 'SUB', 'parent' => $menu['id'], 'status' => 'ACTIVE'), 'display_id');
			}
			else{
			    $sub = get_all('op_menu', '*', array('type' => 'SUB', 'parent' => $menu['id'], 'status' => 'ACTIVE',remove_space($_SESSION['user_type'])=>'YES'), 'display_id');
			}
			
			if ( $sub['count'] > 0) {
				$str .= "<li class='sidebar-item'>";
				$str .= "<a data-bs-target='#" . remove_space($menu['title']) . "' data-bs-toggle='collapse' class='sidebar-link collapsed'>";

				$str .= "<i class='fa fa-{$menu['icon']}'></i> <span class='align-middle'>{$menu['title']}</span></a>";

				if ($sub['count'] > 0 ) {
					$str .= "<ul id='" . remove_space($menu['title']) . "' class='sidebar-dropdown list-unstyled collapse' data-bs-parent='#sidebar'>";
					foreach ((array) $sub['data'] as $submenu) {
					    $permission = $submenu[remove_space($_SESSION['user_type'])];
						if ($_SESSION['user_type'] == 'DEV') {
							$str .= "<li class='sidebar-item'><a class='sidebar-link' href='$base_url{$submenu['link']}'>{$submenu['title']} <span class='badge bg-warning float-end'>{$submenu['extra']}</span></a></li>";
							
						} else if ($permission == 'YES') {
							$str .= "<li class='sidebar-item'><a class='sidebar-link' href='$base_url{$submenu['link']}'>{$submenu['title']} <span class='badge bg-warning float-end'>{$submenu['extra']}</span></a></li>";
						}
						
					}
					$str .= "</ul>";
				}
				$str .= "</li>";
			}
		}
	}
	return $str;
}


function sync_table($table_id)
{
	$table_name  = get_data('op_table',$table_id,'table_id')['data'];
	//check_table($table_name);
	
	$column = column_list($table_name);
	foreach((array)$column['data'] as $cname)
	{
		$data['table_name'] = $table_name;
		$data['column_name'] = $cname['COLUMN_NAME'];
	

		$fres = get_all('op_master_table', '*', $data );
		if ($fres['count'] == 0) {


			if($cname['COLUMN_NAME'] =='status')
			{
				$data['input_type'] = 'Status';
				$data['input_value'] = '32';
				$data['default_value'] = 'ACTIVE';
			}
			elseif($cname['DATA_TYPE'] =='date')
			{
				$data['input_type'] = 'Date';
			}
			elseif($cname['DATA_TYPE'] =='time')
			{
				$data['input_type'] = 'Time';
			}
			// elseif($cname['DATA_TYPE'] =='int')
			// {
			// 	$data['input_type'] = 'number';
			// }
			elseif($cname['DATA_TYPE'] =='timestamp')
			{
				$data['input_type'] = 'Datetime';
			}
			else{
				$data['input_type'] = 'Text';
			}
			insert_data('op_master_table', $data);
		} else {
			$id = $fres['data'][0]['id'];
			update_data('op_master_table', $data, $id);
		}
	}
	$sql = "UPDATE op_master_table set is_edit='NO' where column_name='created_at' or column_name='updated_at' or column_name='created_by' or column_name='updated_by' or column_name='id'";
	$res0 = direct_sql($sql, 'set');

	return $res0;
}


function add_in_menu($table_id)
{
	$table_name  = get_data('op_table',$table_id,'table_id')['data'];
	create_add_page(remove_space($table_name), );
	create_manage_page(remove_space($table_name), );

			$data0['type'] ='MAIN';
			$data0['parent'] =0;
			$data0['title'] = add_space($table_name);
			$data0['link'] = "#";
			$data0['status'] ='ACTIVE';
			$data0['icon'] ='table';
			$data0['table_id'] =$table_id;

			$mainmenu = insert_data('op_menu',$data0);
			$page_id =$mainmenu['id'];

			$data1['type'] ='SUB';
			$data1['parent'] =$page_id;
			$data1['title'] ='Add '.add_space($table_name);
			//$data1['link'] =remove_space($table_name).'/add'; // Using Folder
			$data1['link'] ='public/'.remove_space($table_name).'_add';
			$data1['status'] ='ACTIVE';
			$data1['table_id'] =$table_id;
			insert_data('op_menu', $data1);

			$data2['type'] ='SUB';
			$data2['parent'] =$page_id;
			$data2['title'] ='Manage '.add_space($table_name);
			$data2['link'] ='public/'.remove_space($table_name).'_manage';
			$data2['status'] ='ACTIVE';
			$data2['table_id'] =$table_id;
			insert_data('op_menu', $data2);
			sync_table($table_name);
			create_role('DEV');
}


function create_add_page($table_name, $create_folder ="NO")
	{
		global $user_name;
		global $user_type;
		
		if(strtoupper($create_folder) =="YES")
	    {
	        // Check Folder Exist or Create
    	    if (!file_exists($table_name)) { 
    		    mkdir("../".$table_name, 0755, true);
    	    }
    		// With Seperate Folder
    		$myFile = "../".$table_name."/add.php"; // or .php  
	    }
	    else{
    		// With Prefix Table Name 
    		$myFile = "../public/".$table_name."_add.php"; // or .php 
	    }
	    
		$fh = fopen($myFile, "w"); // or die("error");  
		$stringData = '<?php require_once("../system/all_header.php"); 

        $table_name = "'.$table_name.'";
        
        if (isset($_GET["link"]) and $_GET["link"] != "") {
            $branch = decode($_GET["link"]);
            $id = $branch["id"];
            $isedit ="yes";
        } else {
        
            $branch = insert_row($table_name);
            $id = $branch["id"];
            $isedit ="no";
        }
        
        if ($id != "") {
            $res = get_data($table_name, $id);
            if ($res["count"] > 0 and $res["status"] == "success") {
                extract($res["data"]);
            }
        }
        ?>
        
            <main class="content">
                <div class="container-fluid p-0">
        
                    <h1 class="h3 mb-3" >'.add_space($table_name).'</h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> '.add_space($table_name) .' Details
                                     <?= btn_save($table_name); ?>
                                   
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php $form  = create_form($table_name, $id, $isedit); 
									$table_id = get_data("op_table", $table_name, "id","table_id")["data"];
                                    $res =  get_multi_data("op_role", array("table_id"=>$table_id, "role_name"=>$user_type));

									if($res["count"]>0)
									{
										
										foreach((array)$form as $el)
										{
											echo $el;
										}
										
									}
									
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
        
                </div>
            </main>
        
        <?php 
        require_once("../system/footer.php"); ?>';   
		fwrite($fh, $stringData);
		fclose($fh);
	}

function create_manage_page( $table_name, $create_folder="NO")	
	{
	    if(strtoupper($create_folder) =="YES")
	    {
	        // Check Folder Exist or Create
    	    if (!file_exists($table_name)) { 
    		    mkdir("../".$table_name, 0755, true);
    	    }
    		// With Seperate Folder
    		$myFile = "../".$table_name."/manage.php"; // or .php  
	    }
	    else{
    		// With Prefix Table Name 
    		$myFile = "../public/".$table_name."_manage.php"; // or .php 
	    }
    	$fh = fopen($myFile, "w"); // or die("error"); 
    
		$stringData = '<?php require_once("../system/all_header.php"); 
        $table_name = "'.$table_name.'";
        $res= create_server_table($table_name);

        ?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3"><?= add_space($table_name) ?></h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">All <?= add_space($table_name) ?> <?= btn_add($table_name) ?>

                            <span class="float-end">
                            <div class="float-end">
								<button class="btn btn-warning btn-sm"> <input type="checkbox" title="select All" id="selectAll" class="btn btn-dark btn-sm"> </button>
								<?= btn_remove_multiple($table_name) ?>
								<?= btn_delete_multiple($table_name) ?>
						
								<button class="btn btn-primary btn-sm my-1"   title="Show /Hide Columns" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="fa fa-columns"></i></button>
								<button class="btn btn-info btn-sm" title="Download XLS" onclick="exportxls()"> <i class="fa fa-file-excel"></i> </button>
							</div>
                            </span>
							</h5>
						</div>
						<div class="card-body">
                            <?php
							
                            echo $res["html"]; 
                            
                            ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>
<?php 
require_once("../system/footer.php"); ?>';
fwrite($fh, $stringData);
fclose($fh);	
    
}


function create_role($role_name)
{
	$ct=0;
	$res = get_all('op_table');
	foreach((array)$res['data'] as $table)
	{
		$find_role  =get_all('op_role','*',array('table_id'=>$table['id'], 'role_name'=>$role_name));

		if($find_role['count']==0)
		{
			insert_data('op_role', array('table_id'=>$table['id'], 'role_name'=>$role_name, 'show_menu'=>'YES','can_view'=>'YES', 'can_add'=>'YES','can_edit'=>'NO','can_remove'=>'NO','status'=>$table['status']));
			$ct++;
		}
		
	}
	return $ct; 
}


function create_widget($table_name, $status = 'ACTIVE')
{
    global $user_type;
    global $branch_id;
    $table_id = get_data('op_table',$table_name,'id','table_id')['data'];
    if($user_type=='ADMIN' or $user_type=='DEV')
    {
	    $ct = get_all($table_name, '*', array('status' => $status))['count'];
    }
    else{
        $ct = get_all($table_name, '*', array('status' => $status))['count'];
    }
	$iconres = get_multi_data('op_menu', array('type' => 'MAIN', 'table_id' => $table_id));
	if ($iconres['count'] > 0) {
		$icon = $iconres['data'][0]['icon'];
	} else {
		$icon = 'table';
	}

	$str = '<div class="col-sm-6 col-xl-3">
		<div class="card">
			<div class="card-body">
				<div class="row">
					<div class="col mt-0">
						<h5 class="card-title">' . add_space($table_name) . '</h5>
					</div>

					<div class="col-auto">
						<div class="stat text-warning">
							<i class="fa fa-' . $icon . '"></i>
						</div>
					</div>
				</div>
				<h1 class="mt-1 mb-3">' . get_all($table_name)['count'] . '</h1>
				<div class="mb-0">
					<a href="../public/' . $table_name . '_manage.php" class="badge badge-warning-light"> <i class="mdi mdi-arrow-bottom-right"></i>' . $ct . '</a>
					<span class="text-muted">' . $status . '</span>
				</div>
			</div>
		</div>
	</div>';

	return $str;
}

function recent_widget($table_name)
	{
		
		$cols = create_list('op_master_table','column_name',array('table_name'=> $table_name, 'allow_global_search'=>'YES'));
		
		if(is_array($cols))
		{
		$cols_list = implode(',',$cols);
		
		$sql ="select id, $cols_list from $table_name where status not in ('AUTO','DELETED') order by id desc limit 10";
		$res = direct_sql($sql);

		
		$str ='<div class="col-12 col-lg-6 d-flex">
		<div class="card flex-fill w-100">
			<div class="card-header">
				
				<h5 class="card-title mb-0">'. add_space($table_name).'</h5>
			</div>
			<div class="card-body pt-2 pb-3">'.
				create_data_table($table_name,$res, [], '')
			.'</div>
		</div>
		</div>';

		return $str;
		}
	}
	

function create_backup($tables = '*')
	{
	global $con;
	$return ='';
		//get all of the tables
		if($tables == '*')
		{
			$tables = array();
			$result = mysqli_query($con, 'SHOW TABLES');
			while($row = mysqli_fetch_array($result))
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',',$tables);
		}

		//cycle through
		foreach($tables as $table)
		{
			global $inst_name;
			$site_name= strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $inst_name));
			$result = mysqli_query($con, 'SELECT * FROM '.$table);
			$num_fields = mysqli_num_fields($result);

			$return.= 'DROP TABLE '.$table.';';
			$row2 = mysqli_fetch_array(mysqli_query($con, 'SHOW CREATE TABLE '.$table));
			$return.= "\n\n".$row2[1].";\n\n";

			for ($i = 0; $i < $num_fields; $i++)
			{
				while($row = mysqli_fetch_array($result))
				{
					$return.= 'INSERT INTO '.$table.' VALUES(';
					for($j=0; $j<$num_fields; $j++)
					{
						//$row[$j] = addslashes($row[$j]);
						//$row[$j] = preg_replace("/\\n/","\\n",$row[$j]);
						if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
						if ($j<($num_fields-1)) { $return.= ','; }
					}
					$return.= ");\n";
				}
			}
			$return.="\n\n\n";
		}

		//save file
		
		if (!file_exists('../backup')) {
		    mkdir('../backup', 0777, true);
		}
		
		
		$filename = '../backup/db-'.$site_name .'-'.date('ymd').'.sql.gz';
		$handle = fopen($filename,'w+');
		$gzdata = gzencode($return, 9);
		fwrite($handle,$gzdata);
		fclose($handle);
		return $filename;
	}


function push_msg($message, $title='', $link='') {
    global $base_url;
    global $push_token;
    global $inst_name ;
    $url = 'https://api.truepush.com/api/v1/createCampaign'; // Replace with the actual API endpoint URL
    
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjcmVhdGVkRGF0ZSI6MTY4NTYyMTk4OTI5MCwiaWQiOiI2NDc4OGM0NWY0NWRjYWZlODA1NzRiN2UiLCJ1c2VySWQiOiI2NDc3OWNlZmYzZjMyNjJkODNhOTExMTAiLCJpYXQiOjE2ODU2MjE5ODl9.bfb_S1J2Tkbj6ZAyN4n57JyPD4jb3i46j_K0CG6qxhI'; // Replace with your REST API token

    $headers = array(
        'Authorization: ' . $push_token,
        'Content-Type: application/json'
    );

    $data = array(
        'title' => ($title=='')?$inst_name:$title,
        'message' => 'Notification with API message',
        'link' => $base_url,
        'image' => $base_url.'img/push.jpg',
        'icon' =>  $base_url.'img/logo.png',
        'scheduled' => false,
        'tag'=> array("op_user_name"),
        'buttons' => array(
            array(
                'text' => 'Show Details',
                'link' => ($link=='')?$base_url:$link,
            ),
            array(
                'text' => 'WhatsApp',
                'link' => "https://wa.me/919431426600?text=$inst_name"
            )
        )
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}


	
function get_filesize($filename) {

    $bytes = filesize($filename);
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }
        return $bytes;
}

// Create Single Form Element 

function create_form_element($id, $did='') 
    {
    global $today;
     $str = '';
    $res =  get_data('op_master_table', intval($id));
    
    if($res['count']>0)
    {
        $row =$res['data'];
        $udata = get_data($row['table_name'], $did)['data'];
        extract($row);
        $value =$udata[$column_name];
    	$extra = ($row['is_required'] =='YES')? " required ":" ";
		$mark = ($row['is_required'] =='YES')? " <span class='text-danger' title='This field is mendatory'>* </span>":" ";
		$extra .= $row['extra'];
		
   
    $display_name = ($display_name == '') ? ucfirst(str_replace('_', ' ', $column_name)) : $display_name .$mark;

    switch ($input_type) {
        
    	case 'Label':
					$str ='<div class="form-group col-12">';
					$str .=	"<div class='label'><i class='fa $extra'> </i> $display_name </div></div>";
					break;
					
        case 'Date':
            //$value = ($value == '0000-00-00' || $value == '') ? $today : $value;
            $str = create_input($column_name, 'date', $value, $display_name, $extra);
            break;

        case 'Datetime':
            $str = create_input($column_name, 'datetime-local', $value, $display_name, $extra);
            break;

        case 'Color':
            $str = create_input($column_name, 'color', $value, $display_name, $extra);
            break;

        case 'Time':
            $str = create_input($column_name, 'time', $value, $display_name, $extra);
            break;

        case 'Year':
            $str = '<div class="form-group col-md-4">';
            $str .= "<label>$display_name</label>";
            $str .= '<input type="number" class="form-control" min="1950" max="2099" step="1" 
                        name="'.$column_name.'" value="'.$value.'" '.$extra.' />';
            $str .= '</div>';
            break;

        case 'Month':
            $str = create_input($column_name, 'month', $value, $display_name, $extra);
            break;

        case 'Week':
            $str = create_input($column_name, 'week', $value, $display_name, $extra);
            break;

        case 'Email':
            $str = create_input($column_name, 'email', $value, $display_name, $extra);
            break;

        case 'Number':
            $extra .= " min=0 ";
            $str = create_input($column_name, 'number', $value, $display_name, $extra);
            break;

        case 'Multiline':
            $str = '<div class="form-group col-md-4">
                        <label>'.$display_name.'</label>
                        <textarea name="'.$column_name.'" id="'.$column_name.'" 
                            class="form-control" '.$extra.'>'.$value.'</textarea>
                    </div>';
            break;

        case 'Photo':
            $str = '<div class="form-group col-md-4 mt-3">
                        <label>'.$display_name.'</label>
                        <input type="file" class="form-control" name="'.$column_name.'" id="'.$column_name.'" '.$extra.'>
                        <div id="'.$column_name.'_display">';
            if (!empty($value)) {
                $str .= "<a href='../upload/$value' target='_blank'>View</a>";
            }
            $str .= '</div></div>';
            break;
        
    	case 'Docs':
			$str =  '<div class="form-group  col-md-4 mt-3">
			<label>'. $display_name .'</label>
			<input type="hidden" name="'.$row['column_name'].'" id="target_'.$row['column_name'].'" value="'.$value.'">
			<input class="upload_img form-control" type="file" id="'.$row['column_name'].'" accept="image" data-table="'.$table_name.'" data-field="'.$row['column_name'].'" '. $extra.'>
			<small> Only a valid images or Docs file. </small>';
			$str .=  '<div id="' . $row['column_name'] . '_display">';
			if ($isedit == 'yes') {
				//$str .=  show_img($value);
				$str .=  "<a href='../upload/{$value}' class='btn btn-border border-primary'> <i class='fa fa-download'></i> Download </a>";
			}
			$str .=  '</div></div>';
			break;
        
        case 'Multi-Photo':
        case 'Multi-Docs':
            $str = '<div class="form-group col-md-4 mt-3">
                <label>'. $display_name .'</label>
                <input type="hidden" name="'.$row['column_name'].'" id="target_'.$row['column_name'].'" value="'.$value.'">
                <input class="upload_multi_img form-control" multiple type="file" 
                    id="'.$row['column_name'].'" 
                    accept="image/png, image/gif, image/jpeg, application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document" 
                    data-table="'.$table_name.'" data-field="'.$row['column_name'].'" '. $extra.'>
                <small>Allowed: JPG, PNG, PDF, DOC, DOCX.</small>';
            
            $str .= '<div id="'.$row['column_name'].'_display">';
            
            //if ($isedit == 'yes' && !empty($value)) {
                $images = explode(',', $value);
               // print_r($images);
                foreach ((array)$images as $img) {
                    $file = basename($img); // prevent path traversal
                    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['pdf','doc','docx'])) {
                        $str .= "<a href='upload/$file' download>Download ($ext)</a><br>";
                    } else {
                        $str .= show_img($file);
                    }
                }
            //}
            $str .= '</div></div>';
            break;

        
        default:
            $str = create_input($column_name, 'text', $value, $display_name, $extra);
            break;
        }
    }

    return $str;
}

// Activate All VARIIABLE ans Settings 
//++++++++++++++++++++++++++++++++++++++++++++++++//

$a = all_config();
extract($a);
extract(get_data('op_settings',1)['data']);

//++++++++++++++++++++++++++++++++++++++++++++++++//

/*
// Show Query in Direct table 

function display_data_table($query, $actionLinks = []) {
    global $con;
    // Fetch data from MySQL
    $result = mysqli_query($con, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($con));
    }

    // Display table header
    echo '<table id="example" class="data-tbl  table table-bordered table-striped" style="width:100%">
        <thead>
            <tr>
                <th></th>'; // Empty th for checkbox

    $fields = mysqli_fetch_fields($result);
    foreach ($fields as $field) {
        if($field->name != 'id') { // Exclude 'id' from header
            echo "<th>". add_space($field->name). "</th>";
        }
    }

    echo '<th>Actions</th></tr>
        </thead>
        <tbody>';

    // Display table rows with checkbox
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>
                <td><input type="checkbox" name="checkbox[]" value="'.$row['id'].'" class="chk"></td>'; // Checkbox column

        foreach ($row as $key => $value) {
            if($key != 'id') { // Exclude 'id' from row
                echo "<td>{$value}</td>";
            }
        }

        // Add action links
        echo '<td>';
        foreach ((array)$actionLinks as $link) {
           $url= $link['url'];
           $value= (isset($link['value']) and $link['value'] !='')?$link['value']:'id';
           $icon= (isset($link['icon']) and $link['icon'] !='')?$link['icon']:'link';
           $color= (isset($link['color']) and $link['color'] !='')?$link['color']:'dark';
           
           $button = "<button class='btn btn-sm btn-$color'><i class='fa fa-$icon'></i></button>";
           echo ' <a href="'.$url.'?'.$value.'='.$row[$value].'">'.$button.'</a> ';
        }
        // Add more links or values as needed
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>
        </table>';

    // Free result set
    mysqli_free_result($result);
}

*/

// Advance Login 

// ========================
// GET USER BY USERNAME
// ========================
function getUserByUsername($con, $username)
{
    $sql = "SELECT * FROM op_user WHERE user_name=?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// ========================
// UPDATE LOGIN ATTEMPT
// ========================
function updateLoginAttempt($con, $user_id, $attempt)
{
    $sql = "UPDATE op_user SET login_attempt=? WHERE id=?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $attempt, $user_id);
    mysqli_stmt_execute($stmt);
}

// ========================
// BLOCK USER
// ========================
function blockUser($con, $user_id)
{
    $sql = "UPDATE op_user SET user_status='BLOCKED' WHERE id=?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
}

// ========================
// RESET ATTEMPT ON SUCCESS
// ========================
function resetLoginAttempt($con, $user_id)
{
    $sql = "UPDATE op_user SET login_attempt=0 WHERE id=?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
}


function get_ip(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

?>