<?php require_once('all_header.php');
if($user_type =='DEV')
{
$res = get_all('op_table');
}
else{
    $res = get_all('op_table','*',array('status'=>'ACTIVE'));  
}
?>
<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">Table List

        <button class='btn btn-success btn-sm' id='create_opex'> SETUP </button>
        <button class='btn btn-danger btn-sm' id='reset_opex'> RESET </button>
        </h1>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Table List
                        <div class="float-end">
                            <button class='btn btn-dark btn-sm' id='add_table'> <i class='fa fa-plus'></i> </button>

                            <button class='sync_table btn btn-danger btn-sm'><i class='fa fa-refresh'></i></button>

                            <button class='btn btn-success btn-sm' id='update_btn'> SAVE </button>
                            
                        </div>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class='table-responsive1'>
                        <table class="data-tbl  table table-hover" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Column Count</th>
                                    <th>Row Count</th>
                                    <th>Allow Search</th>
                                    <th title='Show Statics on Dashboard' >Dashboad</th>
                                    <th>Status</th>
                                    <th>Deleted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ((array) $res['data'] as $row) {
                                   
                                    $table = $row['table_id'];
                                    $id = $row['id'];
                                    $agc = $row['allow_global_search'];
                                     check_table($table);
                                    $ct = get_all('op_master_table', '*', array('table_name' => $table), 'table_name')['count'];
                                    echo "<tr>";
                                    echo "<td> " . $table . "</td>";
                                    echo "<td> " . $ct . "</a></td>";
                                    echo "<td> <a href='op_data_manager?table_name=$table' >" . get_all($table)['count'] . "</a></td>";
                                    echo "<td> " . show_switch('op_table',$id,'allow_global_search',$row['allow_global_search']) . "</td>";
                                    echo "<td> " . show_switch('op_table',$id,'show_in_dashboard',$row['show_in_dashboard']) . "</td>";
                                    echo "<td> " . show_status($row['status']) . "</td>";
                                    echo "<td><a href='op_recycle_bin?table_name=$table'>" . get_all($table,'*',array('status'=>'DELETED'))['count'] . "</a></td>";

                                    echo "<td align='right'>";

                                    echo "&nbsp; <a href='op_master_table_add?table_name=$table' class='btn btn-dark btn-sm'> 
                                            <i class='fa fa-plus'></i></a>";

                                    echo "&nbsp; <button data-table='$table' class='sync_table btn btn-success btn-sm'> 
                                            <i class='fa fa-refresh'></i></button>";

                                    echo "&nbsp; <a href='op_table_manager?table_id=$id' class='btn btn-info btn-sm'> 
                                            <i class='fa fa-pencil'></i></a>";
                                    
                                        if($row['status'] !='LOCKED' and $user_type=='DEV')
                                        {
                                echo "&nbsp; <span data-table='$table' data-id='$id' class='delete_table btn btn-danger btn-sm'> 
                                        <i class='fa fa-trash'></i></span>";
                                        }
                                    echo "</td></tr>";
                                }
                                ?>

                            </tbody>
                        </table>
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
    $("table > tbody > tr> td ").each(function () {
    var x= $(this).text();
    if(x.trim() =='LOCKED' )
    {
        $(this).closest('tr').find('.yesno').closest('td').html('');
    }
        
    });
});
</script>