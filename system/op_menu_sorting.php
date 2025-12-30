<?php require_once('all_header.php');?>
<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">
        <a href='op_menu_manage' class='px-3 text-dark'> <i class='fa fa-arrow-left'></i> </a>

        Arrange Column </h1>
         
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    
                                    Menu Mannagement
                                 
                                    <div class="page-title-right">
                                    <button class='btn btn-success btn-sm' id='save'>
                                        Save 
                                    </button>
                                    </form>
                                    </div>

                                </div>
                    </div>
                    <div class="card-body">
                    <ul id="simpleList" class="list-group">
                    <?php

                    $res = get_all('op_menu','*',array('type'=>'MAIN', 'status'=>'ACTIVE'), 'display_id');

                    if($res['count'] > 0)
                    {
                        foreach((array) $res['data'] as $row)
                        {
                            $display_id =$row['id'];
                            $title =$row['title']. " <span class='badge bg-success'> ". $row['type'] ."</span>";
                            $cls = $req= '';
                            
                            echo "<li class='list-group-item $cls' data-id='$display_id'>";
                            echo "<i class='fa fa-arrows-alt' ></i>  $title </li>";
                                ?>
                        <?php
                        } 
                    }
                    ?>
                                       
                    </ul> 

                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once('footer.php'); ?>



<script src="https://SortableJS.github.io/Sortable/Sortable.js"></script>
    <script>
        $(document).on('click','.ls-modal', function(e){
          e.preventDefault();
          $('#view_data').modal('show').find('.modal-title').html($(this).attr('data-title'));
          $('#view_data').modal('show').find('.modal-body').load($(this).attr('href'));
        });
    </script>
    
    
<script>
  Sortable.create(simpleList, { });
	  
$(document).on('click', '#save', function()
{
var a =[];
	$('#simpleList .list-group-item').each(function() {
       a.push($(this).attr('data-id'));
	});
	$.ajax({
			'type':'POST',
			'url':'system_process?task=sort_menu',
			'data':{'columns':a},
			success: function(data){
			    var obj = JSON.parse(data);
				if (obj.url != null) {
					bootbox.alert(obj.msg, function () {
						window.location.replace(obj.url);
					});
				}
				else {
					$.notify(obj.msg, obj.status);
				}
			}
		});
});

</script>
