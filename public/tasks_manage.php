<?php require_once("../system/all_header.php"); 
        $table_name = "tasks";
        $res = get_all($table_name, '*', [], 'task_name ASC');
        ?>
        
        <main class="content">
            <div class="container-fluid p-0">
        
                <h1 class="h3 mb-3"><?= add_space($table_name) ?></h1>
        
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">All <?= add_space($table_name) ?> <?= btn_add($table_name) ?>
        
                                <span class="float-end">
                                <div class="float-end">
                                    <button class="btn btn-warning btn-sm"> <input type="checkbox" title="select All" id="selectAll" class="btn btn-dark btn-sm"> </button>
                                    <?= btn_delete_multiple($table_name) ?>
                            
                                    <button class="btn btn-primary btn-sm my-1" title="Show /Hide Columns" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i class="fa fa-columns"></i></button>
                                </div>
                                </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                
                                $btn_arr=[
                                    'btn_edit'=>'tasks_add',
                                    'btn_delete' => '',
                                    'btn_meta' => 'tasks_meta_manage'
                                    ];
                                    
                                echo create_data_table($table_name, $res, $btn_arr);
                                
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
        
            </div>
        </main>
        
        <script>
        $(document).ready(function() {
            $("#selectAll").change(function() {
                $(".chk").prop("checked", $(this).prop('checked'));
            });
        });
        </script>
        
        <?php 
        require_once("../system/footer.php"); ?>

