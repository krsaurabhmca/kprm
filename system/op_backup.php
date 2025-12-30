<?php require_once('all_header.php'); ?>

<style>
    body{
        background:azure;
        color:gray;
        font-family:calibri;
        font-size:16px;
    }
    #main{
        border:dotted 5px #ddd;
        padding:20px;
        text-align:center;
        margin:auto;
        width:60%;
        margin-top:4%;
        margin-bottom:4%;
    }
</style>
	<main class="content">
		<div class="container-fluid p-0">

			<h1 class="h3 mb-3">Backup
			
			     
			</h1>

			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="card-title mb-0">Database Backup Till Now
							<span class='float-end'>
							       <button class='btn btn-success btn-sm' id='create_opex'> SETUP </button>
                                    <button class='btn btn-danger btn-sm' id='reset_opex'> RESET </button>
							</span>
							</h5>
						</div>
						<div class="card-body" id="main">

						<?php

                    	$file_name  = create_backup();
                        $html ="<h3> Database Backup of " .$inst_name ."</h3>";
                        $html .="<li> Created by " .$user_name ."(".$full_name .") </li>";
                        $html .="<li> Created at " .$current_date_time. " </li>";
                        $html .="<li> Thanks for Choosing $app_name </li>";
                        
                        $m = send_mail($inst_email, "$inst_name Databse Backup Till $current_date_time" ,$html, $file_name);
                        if($m)
                        {
                           echo "<div class='alert alert-success text-center p-2'> ✉️ Mail Sent Successfully to <b> ". $inst_email ." </b> </div>";
                        }
                    	echo "<a href='$file_name' class='btn btn-success btn-sm mt-3' > Click to Download </a>";

						?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</main>

<?php require_once('footer.php'); ?>		