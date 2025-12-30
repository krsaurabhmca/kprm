<?php require_once("all_header.php"); 
       $res = get_all('op_table','*',array('allow_global_search'=>'YES'));
       $search_term =$_POST['search_term'];
?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Global Search Result</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
					
						<div class="card-body">
                            <?php


						foreach((array)$res['data'] as $table)
						{
							$table_name = $table['table_id'];

							$sql = "select * from $table_name where ";
							$flist = get_all('op_master_table','*',array('table_name'=>$table_name,'allow_global_search'=>'YES'));

							if($flist['count']>0)
							{
								$filter =[];
								foreach((array)$flist['data'] as $row)
								{
									$col_name =$row['column_name'];
									$filter[] = "$col_name like '%$search_term%' ";
								}
								$sql = $sql . implode( 'or ', $filter);
								
								$fres = direct_sql($sql);
									if($fres['count']>0)
									{
									echo "<b> Search Result form ". add_space($table_name) ."</b><hr>";
									echo create_data_table($table_name, $fres );
									
									}
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
require_once("footer.php"); ?>