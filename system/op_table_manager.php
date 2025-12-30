<?php require_once('all_header.php');

if (isset($_GET['table_id']) and $_GET['table_id'] != '') 
{
    $table_id = $_GET['table_id'];
    $data_table_name  = get_data('op_table',$table_id,'table_id')['data'];
}
if (isset($_GET['table_name']) and $_GET['table_name'] != '') 
{
    $data_table_name = $_GET['table_name'];
}
?>
<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <h1 class="h3 mb-3 float-start col-8">
            <a href='op_table' class='px-3 text-dark'> <i class='fa fa-arrow-left'></i> </a>
    
            Table Manager
           
            </h1>
            <div class="float-end mb-3 col-4">
                <form action='' method='get'>
                    <?php if($user_type=='DEV') { 
                        $table_list = table_list() ;   
                    ?> 
                    <select name='table_name' onChange='submit()' class='select2 form-select'>
                        <?= dropdown($table_list['data'] ,$data_table_name); ?>
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
                        <h5 class="card-title mb-0">Table Details of <span class='text-primary'><?= add_space(@$data_table_name); ?>
                        <div class="float-end">

                            <a href='op_master_table_add?table_name=<?= $data_table_name ?>' class='btn btn-dark btn-sm'>
                                <i class='fa fa-plus'></i>
                            </a>
                            <a href='op_column_sorting?table_name=<?= @$data_table_name ?>' title='Sort Columns' class='btn btn-primary btn-sm'><i class='fa fa-arrows-alt'></i></a>

                            <button class='btn btn-success btn-sm' id='update_btn'> SAVE </button>
                        </div>
                        </h5>
                    </div>
                    <div class="card-body">
                     
                           
                            <?php
                            if(isset($data_table_name))
                            {
                            $res1 = get_all('op_master_table', '*', array('is_edit' => 'YES','status'=>'ACTIVE', 'table_name' => $data_table_name),'display_id');
                            
                            $btn_arr = array(
                                "btn_view" => "Table Editor",
                                "btn_edit" => "op_table_edit",
                                "btn_remove" => "",
                            );

                           echo create_data_table('op_master_table',$res1, $btn_arr); 
                        
                            }
                            ?>
                     
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>

<script>

// $(document).ready(function()
// {
//     $("table > tbody > tr> td ").each(function () {
//     var x= $(this).text();
//     if(x.trim() =='status' )
//     {
//         $(this).closest('tr').find('.remove_btn').hide('');
//     }
        
//     });
// });
</script>