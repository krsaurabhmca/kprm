<?php
require_once('op_lib.php');
$param = decode($_GET['link']);
$table_name = $param['table'];
$id = $param['id'];
$master = get_multi_data('op_master_table', array('table_name'=>$table_name,'is_edit'=>'YES','status'=>'ACTIVE'),'order by display_id');
$data  = get_data($table_name, $id)['data'];
?>

<style>
	/*body {*/
	/*	-webkit-user-select: none;*/
	/*	-moz-user-select: -moz-none;*/
	/*	-ms-user-select: none;*/
	/*	user-select: none;*/
	/*}*/
		
	@media print {
		#print_area {
			break-inside: avoid;
		}
		#btn_print {
			display: none;
		}
		.label{
			margin:auto;
			text-align: center;
		}
	}
</style>
<script>
	document.addEventListener('contextmenu', event => event.preventDefault());
</script>
<div class="content-fluid p-2" id='print_area'>
	
	<?php
	$info = "<table class='table'>"; 
// 	$info .= "<thead>"; 
// 	$info .= "<tr><td colspan='3' style='font-size:12px' align='center'>
// 	<span style='font-size:16px;font-weight:800;'>". $full_name ."</span><br>". $inst_address1 ;
// 	$info .=  "<br>". $base_url ." | ". $inst_contact ;
// 	$info .="</td></tr></thead>"; 
	$info .="<tbody>"; 
	
	
	foreach ($master['data'] as $col) {
		$key_value  = $data[$col['column_name']];
		$dWithId = ['colum'=>$key_value,'id'=>$id];
		$display_key  = ($col['display_name']=='')?add_space($col['column_name']):$col['display_name'];
		$display_type  = $col['input_type'];
		$extra  = $col['extra'];
		//$display_val = display_value($key_value, $display_type);
		if($display_type=='List-Dynamic')
			{
				$input  = explode(',',$col['input_value']);
				$dval1 = get_data($input[0], $key_value, $input[1])['data'];
				$dval2 = '';
				if(isset($input[2]) and $input[2]!='')
				{
				$dval2 = " [". get_data($input[0],$key_value,$input[2])['data']. "]"; 
				}
				$display_val = $dval1 . $dval2;
			} else if($display_type =='Text-anchor')
			{
				$display_val = display_value($dWithId, 'Text-anchor', $col['extra'], $id); 				
			} 
			else if($display_type =='Photo')
			{
				$display_val = display_value($key_value, $display_type); 				
			} 
			else if($display_key =='ATTACHMENT')
			{
			    if (strpos($key_value, 'http') === 0) {
                    $url = $key_value;
                } else {
                    $url = $base_url .'upload/'. $key_value;
                }
                
                $display_val = "<a href='$url' download>Download</a>";
				//($key_value, $display_type); 				
			} 
			else {
				$display_val = display_value($key_value, $display_type, 'popup');
			}
		if($display_type=='Label')
		{
			$info = $info . "<tr><td colspan='3' class='label' > <i class='fa $extra'></i>  " . strtoupper($display_key) . "</td></tr>";

		} else {
			$info = $info . "<tr><td><b>" . $display_key . "</b></td><td>:</td><td>" . $display_val . "</td></tr>";
		}
	}
	//$info = $info . "<tr><td colspan='2'> ". display_value(, $display_type, 'popup') ."<br> Checked By </td> <td align='right'> <br> Authorised Signature </td></tr>";

// 	$info = $info . "</tbody></table></div>";
// 	$info = $info ."<center> <input type='number' id='no_of_copy' style='width:45px' min='1' value='1'> <button onclick='PrintDiv()' id='btn_print' class='btn btn-pill btn-success btn-sm'> <i class='fa fa-print'></i>  Print </button>";
// 	$info = $info ." <a href='{$base_url}system/view_in_pdf.php?link={$_GET['link']}' class='btn btn-pill btn-danger btn-sm'> <i class='fa fa-file-pdf'></i> Download </a></center>";
	echo $info;
	?>

</div>

<script>
function PrintDiv(elem)
{
	var cp = $("#no_of_copy").val();

    var mywindow = window.open('', 'PRINT', 'height=400,width=600');

    mywindow.document.write('<html><head><title>' + document.title  + '</title>');
	mywindow.document.write('<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&display=swap" rel="stylesheet">');
	mywindow.document.write("<style> body{ font-family: 'Roboto', sans-serif;' } </style>");
    mywindow.document.write('</head><body >');
	for(var i=1; i<=cp; i++)
	{
    mywindow.document.write(document.getElementById("print_area").innerHTML);
    mywindow.document.write("<div style='height:20px'></div>");
	}
    mywindow.document.write('</body></html>');

    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10*/

    mywindow.print();
    mywindow.close();

    return true;
}
</script>