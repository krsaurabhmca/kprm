<?php require_once('all_header.php');
$table_name = 'op_config';
?>
<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">Update Config</h1>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title mb-0">
                            <h5 class="float-start">Update Config</h5>
                            <div class="float-end">
                                <button class='btn btn-danger btn-sm' id='reset_config'> RESET </button>
                                <button class="btn btn-success btn-sm" id='update_btn'> Save </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action='update_settings' id='update_frm' method='post' enctype='multipart/form-data' type='system'>

                            <div class='row'>
                                <?php
                                $res = get_all($table_name, '*', array('status' => 'ACTIVE','allow_edit'=>'YES'), 'id');
                                if ($res['count'] > 0) {
                                    foreach ((array) $res['data'] as $row) {
                                        $ref_id = $row['id'];
                                        $status = $row['status'];

                                        if ($row['option_type'] == 'SINGLE') {
                                            echo "<div class='col-md-6 mb-3'><label>" . add_space($row['option_name']) . "</label>";
                                            echo "<input type='text' class='form-control text-primary' name='". remove_space($row['option_name'])."' placeholder='{$row['default_value']}' value='{$row['option_value']}' required/>";
                                            echo "</div>";
                                        } else {
                                            echo "<div class='col-md-6 mb-3'><label>" . add_space($row['option_name']) . "</label>";
                                            echo "<textarea class='form-control text-primary' rows='2' name='".remove_space($row['option_name'])."' placeholder='{$row['default_value']}' required/>".$row['option_value']."</textarea>";
                                            echo "</div>";
                                        }
                                    }
                                }
                                ?>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>