<?php require_once('all_header.php'); ?>
<?php
$table_name = 'op_config';

$res= get_all($table_name);

?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Configuration Manager</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">All Config
							<button class='btn btn-danger btn-sm' id='reset_config'> RESET </button>
							<a href='op_config_add' class='btn btn-dark btn-sm float-end' > Add New </a>
							</h5>
						</div>
						<div class="card-body">
                            <?php
							$btn_arr = array(
								'btn_view' => 'Config Details',
								'btn_edit' => 'op_add_config',
								'btn_remove' => '',
							);
                            echo create_data_table($table_name,$res, $btn_arr); 
                            
                            ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>

<?php 
require_once('footer.php'); ?>		