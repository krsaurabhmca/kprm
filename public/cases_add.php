<?php require_once("../system/all_header.php"); 
        $client_id = $_GET["client_id"];
        $client_name = get_data("clients", $client_id,'client_name')['data'];
        $table_name = "cases";
        
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
        
                    <h1 class="h3 mb-3" >Cases
                    <form>
                    <select onchange='submit()' name='client_id' class='form-select select2 float-end'>
                        <?= dropdown_list("clients","id","name", $client_id); ?>
                    </select>
                    </form>
                    </h1>
        
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"> Cases Details
                                     <?= btn_save($table_name); ?>
                                   
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $form  = create_form($table_name, $id, $isedit); 
									$table_id = get_data("op_table", $table_name, "id","table_id")["data"];
                                    $res =  get_multi_data("op_role", array("table_id"=>$table_id, "role_name"=>$user_type));

									if($res["count"]>0)
									{
										
										foreach((array)$form as $el)
										{
											echo $el;
										}
										
									}
									
									echo build_client_form($client_id);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
        
                </div>
            </main>
        
        <?php 
        require_once("../system/footer.php"); ?>