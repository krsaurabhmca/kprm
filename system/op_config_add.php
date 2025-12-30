<?php 
require_once('all_header.php'); 

$table_name = 'op_config';

if (isset($_GET['link']) and $_GET['link'] != '') {
	$complain = decode($_GET['link']);
	$id = $complain['id'];
    $isedit ='yes';
} else {
	$complain = insert_row($table_name);
	$id = $complain['id'];
    $isedit ='no';
}

if ($id != '') {
	$res = get_data($table_name, $id);
	if ($res['count'] > 0 and $res['status'] == 'success') {
		extract($res['data']);
	}
}
?>
	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Add Config</h1>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">Add to Config
								<div class="float-end">
								<button class="btn btn-success btn-sm" id='update_btn'> Save </button>
								</div>
							</h5>
						</div>
						<div class="card-body">
							<?php $form  = create_form($table_name, $id, $isedit, 'add_config' ,'system'); 
														
							foreach($form as $el)
							{
							echo $el;
							}
							?>
						
						
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>

<?php require_once('footer.php'); ?>		