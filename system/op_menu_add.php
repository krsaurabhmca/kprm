<?php require_once('all_header.php'); 

$table_name = 'op_menu';

if (isset($_GET['link']) and $_GET['link'] != '') {
	$menu = decode($_GET['link']);
	$id = $menu['id'];
    $isedit ='yes';
} else {

	$menu = insert_row($table_name);
	$id = $menu['id'];
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

			<h1 class="h3 mb-3">Menu</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0"> Menu Details
							<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button>
							</h5>
						</div>
						<div class="card-body">
                            <?php 
							$form2  = create_form('op_menu', $id, $isedit, "add_menu", "system"); 
                            
                                foreach($form2 as $el)
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

<?php 
require_once('footer.php'); ?>		

<script>
$(document).on('change blur', '#type', function()
{

    var mtype = $(this).val();
        if(mtype=='SUB')
        {
            $("#icon").closest(".form-group").css('display','none');
            $("#parent").closest(".form-group").css('display','block');
            $("#quick_lunch").closest(".form-group").css('display','block');
        }
        else{
            $("#icon").closest(".form-group").css('display','block');
            $("#parent").closest(".form-group").css('display','none');
			$("#quick_lunch").closest(".form-group").css('display','none');
        }
});
</script>
