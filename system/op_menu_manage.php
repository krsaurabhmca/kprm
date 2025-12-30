<?php require_once('all_header.php'); 

$table_name = 'op_menu';
$res= get_all($table_name);

?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Menu Manager</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">All Menu & Submenu

                            <span class='float-end'>
                                <button class='btn btn-danger btn-sm' id='change_btn' > <i class='fa fa-chart-bar'></i> </button>
                                <a href='op_menu_sorting?table_name=<?= @$table_name ?>' title='Sort Menu' class='btn btn-primary btn-sm'><i class='fa fa-arrows-alt'></i></a>
                             <a href='op_menu_add' class='btn btn-dark btn-sm' > Add New </a>
    						 
                            </span>
							</h5>
						</div>
						<div class="card-body">
                            <?php
							$btn_arr = array(
								'btn_view' => 'Menu Details',
								'btn_edit' => 'op_add_menu',
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
	
<div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id='change_menu'>
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="exampleModalCenterTitle"> Change Parent Menu</h4>
      	<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
       <from action='' method='post'>
      <div class="modal-body">
         
               <label> Select Parent Menu </label>
              <select name='main_menu' id='main_menu' class='form-select'>
              <?= dropdown_where('op_menu','id','title',['type'=>'MAIN']); ?>
              </select>
              <button class='btn btn-success mt-3' id='change_menu_btn'> Save </button>
         
      </div>
       </from>
    </div>
  </div>
</div>

<?php 
require_once('footer.php'); ?>		

<script>

$(document).ready(function()
{
$("table > tbody > tr> td ").each(function () {
   var x= $(this).text();

   if(x =='MAIN' )
   {
	$(this).closest('tr').find('.yesno').closest('td').html('');
	$(this).closest('tr').find('.chk').closest('td').html('');
   }
   
//     if(x =='SUB' )
//   {
// 	$(this).closest('tr').find('.edit_box').closest('td').html('');
//   }
	
 });
});

$(document).on('click',"#change_btn",function(){
    $("#change_menu").modal('show');
});
</script>