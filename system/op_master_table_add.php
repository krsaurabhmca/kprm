<?php require_once('all_header.php');
$table = 'op_master_table';
$table_list = table_list();
if (isset($_GET['link'])) {
    $arr = decode($_GET['link']);
    $data  = get_data($table, $arr['id'])['data'];
    $isedit = 'yes';
    extract($data);
} else {
    $res  = insert_row($table);
    $id = $res['id'];
    $isedit = 'no';
    $data  = get_data($table, $id)['data'];
    extract($data);
    
}
@$input_arr = explode(',',$input_value);
$status = ($status =='AUTO')?'ACTIVE':$status;
?>
<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">Field/Column Manager</h1>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title mb-0">
                            <h5 class="float-start"> About Column </h5>
                         
                            <div class="float-end">
                                <button class="btn btn-success btn-sm" id='update_btn' accesskey='s'> SAVE </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                    <form id='update_frm' action='update_table' enctype='multipart/form-data' type='system'>
                        <div class="row">
                       
                        <div class="col-md-6">
                       
                        <div class="form-group mb-4">
                            <label>Table Name </label>
                            <input type='hidden' name='id' value='<?php echo $id; ?>'>
                            <input type='hidden' name='isedit' value='<?php echo $isedit; ?>'>
                            <?php if($isedit =='no'){ ?> 
                            <select class="form-select select2" name='table_name' id='table_name'  required>
                                <?php echo dropdown($table_list['data'], $_GET['table_name']); ?>
                            </select>
                            <?php  } else{?> 
                            <input class="form-control"  name='table_name' value="<?php echo $table_name ?>" readonly>
                            <?php } ?> 
                        </div>  
                        
                        <div class="form-group mb-4">
                            <label>Column Name </label>
                            <input class="form-control"  name='column_name' value="<?php echo $column_name ?>" required 
                            <?php echo ($isedit == 'yes') ? 'readonly' : ''; ?> >
                        </div>  

                        <div class="form-group mb-4">
                            <label>Display Name </label>
                            <input class="form-control"  name='display_name' value="<?php echo $display_name ?>" >
                        </div>  
                        
                        <div class="form-group mb-4">
                                <label>Default Value </label>
                                <input class="form-control"  name='default_value' value="<?php echo $default_value ?>" >
                            </div> 
                    
                    </div>
                    <div class="col-md-6">
                       
                        <div class="form-group mb-4">
                            <label>Select Input Type </label>
                            <select class="form-select select2" name='input_type' id='input_type'  required>
                                <?php echo dropdown($input_type_list,$input_type); ?>
                            </select>
                        </div>
                      

                        <div class="form-group mb-2 bg-warning p-2 text-white" id='static_list_area'>
                                <label>Select Column Name </label>
                                <select class="form-select select2" name='static_input'  required autofocus>
                                <?php echo dropdown_where('op_config','id','option_name',array('option_type'=>'LIST'),$input_arr[0]); ?>
                                </select>
                        </div>


                        <div class='bg-success p-2 mb-2 text-white' id='dynamic_list_area' >
                            <div class="form-group mb-4">
                                <label>Select Table </label>
                                <select class="form-select select2" name='dynamic_input[]' id='table_list'  required>
                                <?php echo dropdown($table_list['data'], $input_arr[0]); ?>
                                </select>
                            </div>
                        
                            <div class="form-group mb-4">
                                <label>Display Column </label>
                                <select class="form-select select2" multiple name='dynamic_input[]' id='column_list' required>
                                <?php echo dropdown_where('op_master_table','column_name','column_name',array('table_name'=>$input_arr[0]),$input_arr[1]); ?>
                              
                                </select>
                            </div>

                        
                        </div>

                       
                        
                        <div class="form-group mb-4">
                            <label>Extra Info </label>
                            <input class="form-control"  name='extra' value="<?php echo $extra ?>" >
                        </div>  

                            <div class="form-group mb-4">
                                <label>Status </label>
                                <select class="form-select " name='status'  required>
                                    <?php echo dropdown($status_list,$status); ?>
                                </select>
                            </div>
                        
                    </div>  

                   
                    </div>  
                    <div class='row bg-purple'>
                                                               
                        <div class="form-group col-md-3">
                            <label> Show in Form </label>
                            <?php echo show_switch('op_master_table',$id, 'is_display', $is_display); ?>
                       
                        </div>

                        <div class="form-group col-md-3">
                            <label>Mendatory </label>
                            <?php echo show_switch('op_master_table',$id, 'is_required', $is_required); ?>
                        </div>   
                        
                        <div class="form-group col-md-3">
                            <label> Make Unique </label>
                            <?php echo show_switch('op_master_table',$id, 'is_unique', $is_unique); ?>
                          
                        </div>

                        <div class="form-group col-md-3">
                            <label> Show in Table </label>
                            <?php echo show_switch('op_master_table',$id, 'show_in_table', $show_in_table); ?>
                          
                        </div>
                    </div>


                    </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>
<script>
    $(document).on("change blur ready","#input_type", function(){
        var itype = $(this).val();
        console.log(itype);
        if(itype=='List-Dynamic' || itype=='CheckList-Dynamic' || itype=='List-Where')
        {
            $("#dynamic_list_area").css('display','block');
            $("#static_list_area").css('display','none');
        }
        else if(itype=='Date' || itype=='Date-Time' )
        {
            $("#alertbox").css('display','block');
            $("#dynamic_list_area").css('display','none');
        }
        else if(itype=='List-Static' || itype=='Status')
        {
            $("#static_list_area").css('display','block');
            $("#dynamic_list_area").css('display','none');
        }
        else{
            $("#dynamic_list_area").css('display','none');
            $("#static_list_area").css('display','none');
        }
    });


    $(document).ready(function(){
        var itype = $("#input_type").val();
        console.log(itype);
        if(itype=='List-Dynamic' ||  itype=='CheckList-Dynamic' || itype=='List-Where')
        {
            $("#dynamic_list_area").css('display','block');
            $("#static_list_area").css('display','none');
        }
        else if(itype=='List-Static' || itype=='Status' || itype=='CheckList-Static')
        {
            $("#static_list_area").css('display','block');
            $("#dynamic_list_area").css('display','none');
        }
        else{
            $("#dynamic_list_area").css('display','none');
            $("#static_list_area").css('display','none');
        }
    });
</script>