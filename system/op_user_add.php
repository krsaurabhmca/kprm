<?php require_once('all_header.php'); 

$table_name = 'op_user';

if (isset($_GET['link']) and $_GET['link'] != '') {
	$complain = decode($_GET['link']);
	$id = $complain['id'];
    $isedit ='yes';
} else {

	$complain = insert_row($table_name);
	$id = $complain['id'];
    $isedit ='no';
}

if ($id != '') {
	$res = get_data($table_name, $id);
	if ($res['count'] > 0 and $res['status'] == 'success') {
		extract($res['data']);
	}
}
?>

	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Profile</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">User Profile
							<button class='btn btn-primary btn-sm float-end' id='update_btn'> SAVE </button>
							</h5>
						</div>
						<div class="card-body">
                            <?php 
                            
                            $form  = create_form($table_name, $id, $isedit, 'update_profile', 'system'); 
                            
                                foreach($form as $el)
                                {
                                echo $el;
                                }
                            ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>

<?php 
require_once('footer.php'); ?>		

<script>
function check_fv() {
    let user_type = $("#user_type").val();

    if (user_type === 'FIELD VERIFIER') {
        // Show Area ID
        $("#area_id").parent(".form-group").css('display', 'block');
        $("#area_id").attr("name", "area_id");

         // Show Rate (if needed)
        $("#rate").parent(".form-group").css('display', 'block');
        $("#rate").attr("name", "rate");
        
         $(".form-group .label:contains('Field Verifier Details')")
            .parent(".form-group").css('display','block');
        
    } else if(user_type==='TL'){
         // Show Manager (if needed)
        $("#manager_id").parent(".form-group").css('display', 'block');
        $("#manager_id").attr("name", "rate");
        
    }else if(user_type==='VERIFIER' || user_type==='BEO' || user_type==='REVIEWER'){
         // Show Manager (if needed)
        $("#tl_id").parent(".form-group").css('display', 'block');
        $("#tl_id").attr("name", "rate");
        
    }
    
    else {
        // Hide Area ID
        $("#area_id").parent(".form-group").css('display', 'none');
        $("#area_id").removeAttr("name");

        
        // Hide Rate
        $("#rate").parent(".form-group").css('display', 'none');
        $("#rate").removeAttr("name");
        // Hide Rate
        $("#manager_id").parent(".form-group").css('display', 'none');
        $("#manager_id").removeAttr("name");
        // Hide Rate
        $("#tl_id").parent(".form-group").css('display', 'none');
        $("#tl_id").removeAttr("name");
        
           $(".form-group .label:contains('Field Verifier Details')")
            .parent(".form-group").css('display','none');
    }
}

$(document).ready(function() {
    var isedit = $("input[name=isedit]").val();
    if (isedit === 'yes') {
        $("#user_pass").val('');
        $("#user_pass").removeAttr('required');
    }

    // Run once on page load
    check_fv();
});

// Run on change
$(document).on("change", "#user_type", function() {
    check_fv();
});
</script>
