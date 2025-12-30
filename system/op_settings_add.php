<?php require_once("../system/all_header.php"); 

        $table_name = "op_settings";
        
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
        
                    <h1 class="h3 mb-3" >Op Settings</h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> Op Settings Details
                                     <?= btn_save($table_name); ?>
                                   
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php $form  = create_form($table_name, $id, $isedit); 
									$table_id = get_data("op_table", $table_name, "id","table_id")["data"];
                                    $res =  get_multi_data("op_role", array("table_id"=>$table_id, "role_name"=>$user_type));

									if($res["count"]>0)
									{
										
										foreach((array)$form as $el)
										{
											echo $el;
										}
										
									}
									
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
        
                </div>
            </main>
        
        <?php 
        require_once("../system/footer.php"); ?>