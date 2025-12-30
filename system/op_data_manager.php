<?php require_once('all_header.php');

if (isset($_GET['table_id']) and $_GET['table_id'] != '') 
{
    $table_id = $_GET['table_id'];
    $table_name  = get_data('op_table',$table_id,'table_id')['data'];
}
if (isset($_GET['table_name']) and $_GET['table_name'] != '') 
{
    $table_name = $_GET['table_name'];
}
?>
<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <h1 class="h3 mb-3 float-start col-8">
            <a href='op_table' class='px-3 text-dark'> <i class='fa fa-arrow-left'></i> </a>
    
            Data Manager
            <button class="btn btn-primary btn-sm my-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class='fa fa-columns'></i></button>
            </h1>
            <div class="float-end mb-3 col-4">
                <form action='' method='get'>
                    <?php if($user_type=='DEV') { 
                        $table_list = table_list() ;   
                    ?> 
                    <select name='table_name' onChange='submit()' class='select2 form-select'>
                        <?= dropdown($table_list['data'] ,$table_name); ?>
                    </select>
                    <?php  } else { ?> 
                    <select name='table_id' onChange='submit()' class='select2 form-select'>
                        <?= dropdown_list('op_table','id','table_id',$table_id); ?>
                    </select>
                    <?php  } ?> 
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">All Data of <span class='text-primary'><?= add_space(@$table_name); ?>
                        <div class="float-end">
                            <input type='checkbox' id='selectAll' > Select All
                            <button class='btn btn-dark btn-sm' id='remove_btn' data-table ='<?= $table_name?>'> REMOVE </button>
                            <button class='btn btn-danger btn-sm' id='delete_btn' data-table ='<?= $table_name?>'> DELETE </button>
                        </div>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                           
                            <?php
                            if(isset($table_name))
                            {
                           
                            // $btn_arr = array(
                            //     "btn_view" => "Table Editor",
                            //     "btn_edit" => "add",
                            // );
                            // $res = get_all($table_name);
                            // echo create_data_table($table_name,$res, $btn_arr); 
                            $res =  create_server_table($table_name); 
                            echo $res['html'];
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>

<script>

$(document).ready(function()
{
   $("#selectAll").change(function(){
    $(".chk").prop("checked", $("#selectAll").prop('checked'));
   });
});
</script>