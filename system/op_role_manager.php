<?php require_once('all_header.php');
$table_name ='op_role';
if(isset($_GET['role_name']) && $_GET['role_name'])
    {
        $role_name = $_GET['role_name'];
    }
    else{
        $role_name = $user_type;
    }
    create_role($role_name);
?>
<main class="content">
    <div class="container-fluid p-0">
        <div class="row">
            <h1 class="h3 mb-3 float-start col-8">
         
            Role Manager
            
        </h1>
            <div class="float-end mb-3 col-4">
                <form action='' method='get'>
                    <select name='role_name' onChange='submit()' class='select2 form-select'>
                        <?php echo dropdown($role_list, $role_name); ?>
                    </select>
                   
                </form>
                
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Role Details
                        <div class="float-end">
                            <button class='btn btn-dark btn-sm' id='add_role'> <i class='fa fa-plus'></i> </button>
                        </div>
                        </h5>
                    </div>
                    <div class="card-body">
                    <?php 
                    if(isset($role_name))
                    {
                        if($user_type=='DEV')
                        {
                        $res = get_all($table_name,'*', array('role_name'=>$role_name));
                        }
                        else{
                            $res = get_all($table_name,'*', array('role_name'=>$role_name,'status'=>'ACTIVE'));
                        }

                        $btn_arr = array(
                            "btn_view" => "Role List",
                            "btn_remove" => "",
                        );
                        echo create_data_table($table_name, $res, $btn_arr);
                    }

                    ?>
                       
                       </div>
                    <div class="card-footer">
                        <table class='table'>
                            <tr>
                                <td>
                                    <input class='form-check-input' type='checkbox' role='switch' id='can_view'>
                                    All Can View 
                                </td>
                               
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>

<script>
   
    $('#can_view').change(function() {
        console.log($('#can_view').prop('checked'));
       if($('#can_view').prop('checked')==true)
       {
        $(".can_view").removeAttr('checked');
       }
       else{
        $(".can_view").attr('checked','checked ');
       }
    });
</script>
