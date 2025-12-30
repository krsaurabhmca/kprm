<?php require_once("../system/all_header.php"); 
        $table_name = "clients_meta";
        // $res= create_server_table($table_name);
        $link = decode($_GET['link']);
        $res= get_all($table_name,'*',['client_id'=>$link['id']]);

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
							
							$btn_arr =[
							    'btn_view'=>'',
							    'btn_edit'=>'clients_add'
							    
							 ];
							
							echo create_data_table($table_name, $res, $btn_arr);
                            //echo $res["html"]; 
                            
                            ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>
<?php 
require_once("../system/footer.php"); ?>