<?php
// Database connection parameters
require_once('op_lib.php');
$table_name = $_REQUEST['table_name']; //'student';

// DataTables request parameters
$draw = $_POST['draw'];
$start = $_POST['start'];
$length = $_POST['length'];
$searchValue = $_POST['search']['value'];
$sortColumn = $_POST['order'][0]['column'];
$sortDir = $_POST['order'][0]['dir'];

// Create Column List for Sorting 
$get_cols = get_all('op_master_table', '*', array('table_name' => $table_name,'status'=>'ACTIVE', 'is_edit' => 'YES', 'show_in_table' => 'YES'), 'display_id');
$columns[] ='status';
foreach((array) $get_cols['data'] as $col) {
            $columns[] = $col['column_name'];
		}

$totalRecords  = get_all($table_name)['count'];
$search_text ='';
if($searchValue<>'')
{
    $search_text =' and ';
    foreach((array) $columns as $col_name) {
           $search_text .=  $col_name. " LIKE '%$searchValue%' OR " ;
		}
	$search_text = substr($search_text, 0, -3);
}

// Query to get filtered records
$sql = "SELECT * FROM $table_name WHERE status not in ('AUTO','DELETED') $search_text ORDER BY " . $columns[$sortColumn] . " $sortDir LIMIT $start, $length";

$res = direct_sql($sql);

$recordsFiltered = ($searchValue=='')?$totalRecords:$res['count'];

// Data array to store records
$data = array();

// Loop through filtered records
foreach ((array)$res['data'] as $row ) {
   $id = $row['id'];
   $jdata = json_encode($row);
   $row2['id'] = "<input type='checkbox' value='$id' class='chk' data-json='$jdata'>";
   
   	foreach((array) $get_cols['data'] as $col) {
				$ddata = $row[$col['column_name']]; //Display Data
			
				if($col['input_type']=='List-Dynamic' or $col['input_type']=='List-Where')
				{
				    $maxLength =20;
					$input_arr  = explode(',',$col['input_value']);
					if($ddata !=''){
					   $data_arr =  explode(',',$ddata);
					   $dval_arr =[];
        				 foreach((array)$data_arr as $d)
        				 {
        					$val2 = (isset($input_arr[2])) ? " (".get_data($input_arr[0],$d,$input_arr[2])['data'].")" : "";
        					
        					$dval_arr[] = get_data($input_arr[0],$d,$input_arr[1])['data'] .$val2;
        				 }
        				 $value1 = implode(",",$dval_arr);
			    	}
				    else{
				        $value1 = get_data($input_arr[0],$ddata,$input_arr[1])['data']; 
				    }
				    if ($value1 !='' and strlen($value1) > $maxLength) {
                        $short = substr($value1, 0, $maxLength) . '...';
                        $x = "<span title='" . htmlspecialchars($value1) . "'>$short</span>";
                    } else {
                        $x = $value1;
                    }
				} 
				
				else if($col['input_type']=='Permission')
				{
					$x = show_switch($table_name, $id, $col['column_name'], $ddata); 
					
				} 
				
				else if($col['input_type']=='Text-Info')
				{
					$x = btn_about($table_name, $id, $ddata); 
			
				} 	
				else if($col['input_type']=='RTF')
				{
					$x = display_value($ddata, $col['input_type'],'data-table'); 
			
				} 
				else if($col['input_type']=='Edit-Box')
				{
				    $x ="<span class='edit_box p-1' title='Double Click to Edit' data-table='$table_name' data-id='$id' data-column='{$col['column_name']}'> $ddata </span>";
				} 
				else {
					$x = display_value($ddata, $col['input_type']);
				}
				
				$row2[$col['column_name']] = $x;
			}
	// Add Action Button 
	$btn_str  = btn_view($table_name, $id ,'Details of '. add_space($table_name)) . btn_edit($table_name, $id, 'add');
	if(isset($_POST['btn_list']) and $_POST['btn_list'] !=="")
	{
		foreach((array)$_POST['btn_list'] as $btn)
		{
			$btn_str .=	$btn($table_name, $id);
		}
	}
	$row2['action'] = $btn_str;
	//$row2['action'] = 
	$data[] = $row2;	
}

// Prepare response JSON
$response = array(
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data,
);
header('Content-Type: application/json');
echo json_encode($response);
?>