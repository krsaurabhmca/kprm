<?php require_once('all_header.php');
$table_list = table_list();
$table_name='';
if (isset($_GET['table_name']) and $_GET['table_name'] != '') {
    $table_name = $_GET['table_name'];
    $res = get_all($table_name, '*', array('status' => 'DELETED'));
}

?>

<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <h1 class="h3 mb-3 float-start col-8">
            <a href='op_table' class='px-3 text-dark'> <i class='fa fa-arrow-left'></i> </a>
    
            Recycle Bin</h1>
            <div class="float-end mb-3 col-4">
                <form action='' method='get'>
                    <select name='table_name' onChange='submit()' class='select2 form-select'>
                        <option value=''>Select A Table </option>
                        <?php echo dropdown($table_list['data'], @$table_name); ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="row">
        <?php if (isset($_GET['table_name']) and $_GET['table_name'] != '') { ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Removed Data of <span class='text-primary'><?= add_space($table_name); ?>
                        <div class="float-end">
                            <button class='btn btn-danger active_block'> Restore </button>
                        </div>
                        </h5>
                    </div>
                    <div class="card-body">
                            <?php
                           
                            $btn_arr = array(
                                'btn_restore' =>'ACTIVE',
                                'btn_view' => 'Removed Data',
                                //'btn_edit' => 'add_'.$table_name,
                                'btn_delete' => ''
                            );
                            echo create_data_table($table_name, $res, $btn_arr);
                        
                            ?>
						</div>
                     
                    </div>
                </div>
                </div>
            <?php } ?>
           
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>