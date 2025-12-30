<?php require_once("../system/all_header.php"); 

        $table_name = "tasks";
        
        if (isset($_GET["link"]) and $_GET["link"] != "") {
            $branch = decode($_GET["link"]);
            $id = $branch["id"];
            $isedit ="yes";
        } else {
            $branch = insert_row($table_name);
            $id = $branch["id"];
            $isedit ="no";
        }
        
        if ($id != "") {
            $res = get_data($table_name, $id);
            if ($res["count"] > 0 and $res["status"] == "success") {
                extract($res["data"]);
            }
        }
        ?>
        
        <main class="content">
            <div class="container-fluid p-0">
        
                <h1 class="h3 mb-3"><?= add_space($table_name) ?></h1>
        
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"> Tasks Details
                                 <?= btn_save($table_name); ?>
                                 
                                 <?php if ($isedit == "yes" && isset($id) && $id > 0): ?>
                                 <a href="tasks_meta_manage.php?link=<?php echo encode('id='.$id); ?>" class="btn btn-info btn-sm">
                                     <i class="fa fa-cog"></i> Manage Task Fields (Meta)
                                 </a>
                                 <?php endif; ?>
                                 
                                 <a href="tasks_manage.php" class="btn btn-secondary btn-sm float-end">
                                     <i class="fa fa-arrow-left"></i> Back to Tasks
                                 </a>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $form = create_form($table_name, $id, $isedit, 'task_update'); 
                                
                                // Always display form regardless of roles/table_id
                                if (!empty($form) && is_array($form)) {
                                    foreach((array)$form as $el) {
                                        echo $el;
                                    }
                                } else {
                                    // Fallback: Show message if form is empty
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>Note:</strong> Form configuration not found. Please ensure the table "tasks" is configured in op_table and op_master_table.';
                                    echo '<br><br>';
                                    echo 'You can still manually add/edit tasks using direct SQL or configure the table structure first.';
                                    echo '</div>';
                                }
                                ?>
                                
                                <!-- Auto Update Tasks Meta Info -->
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Note:</strong> When you save this task, the system will automatically extract variables 
                                    from the report formats (positive_format, negative_format, cnv_format) and update tasks_meta.
                                    Variables are marked with <code>#variable_name#</code> in the formats.
                                    <br><br>
                                    <strong>Example:</strong> <code>#applicant_name#</code>, <code>#address#</code>, <code>#met_with#</code>, etc.
                                </div>
                                
                                <?php if (isset($_SESSION['meta_update_message'])): ?>
                                <div class="alert alert-success mt-3">
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo $_SESSION['meta_update_message']; ?>
                                    <?php unset($_SESSION['meta_update_message']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
        
            </div>
        </main>
        
        <?php 
        require_once("../system/footer.php"); ?>

