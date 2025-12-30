<?php require_once('all_header.php'); 

$table_name = 'op_user';
if($user_type=='DEV')
{
	$res= get_all($table_name);
}
else if($user_type=='ADMIN'){
	$sql ="select * from op_user where user_type not in ('DEV') "; 
	$res= direct_sql($sql);
}

                            if(isset($res) && $res['status']=='success')
                            {
?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Users</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">View users
							<a href='op_user_add' class='btn btn-dark btn-sm float-end' > Add New </a>
							</h5>
						</div>
						<div class="card-body">
                            <?php
                            
							$btn_arr = array(
								'btn_view' => 'User Details',
								'btn_edit' => 'op_user_profile',
								'btn_login_as' => '',
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

}

require_once('footer.php'); ?>		