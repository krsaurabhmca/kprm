<?php require_once("all_header.php"); 
        $table_name = "op_settings";
        //$res= create_server_table($table_name);
        $id = 1;
        $master = get_multi_data('op_master_table', array('table_name'=>$table_name,'is_edit'=>'YES','status'=>'ACTIVE'),'order by display_id');
        $data  = get_data($table_name, $id)['data'];
?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3"><?= add_space($table_name) ?></h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">Setting Master

                            
                            <div class="float-end">
                                	<button class="btn btn-primary btn-sm my-1"   title="Show /Hide Columns" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="fa fa-columns"></i></button>
								<?=  btn_edit($table_name, $id) ?>
									
							</div>
                           
							</h5>
						</div>
						<div class="card-body">
                            	<?php
	$info = "<table class='table'>"; 
	$info .= "<tbody>"; 

	
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
	$info = $info . "</tbody></table>";
	echo $info;
	?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>
<?php 
require_once("footer.php"); ?>