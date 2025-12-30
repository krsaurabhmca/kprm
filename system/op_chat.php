<?php require_once("all_header.php"); ?>
<style>
	.delete_btn{
		background-color:#f5f7fb;
		color:#cc0000;
		font-size:12px;
		float:right;
		border:none;
	}

</style>
			<main class="content">
				<div class="container-fluid p-0">

					<div class="mb-3">
						<h1 class="h3 d-inline align-middle">Chat/ Message</h1>
					</div>

					<div class="card">
						<div class="row g-0">
							<div class="col-12 col-lg-5 col-xl-3 border-end list-group">

								<div class="px-4 d-none d-md-block">
									<div class="d-flex align-items-center">
										<div class="flex-grow-1">
											<input type="text" class="form-control my-3" placeholder="Search...">
										</div>
									</div>
								</div>
							<?php $all_user = get_all('op_user');
							foreach((array)$all_user['data'] as $user) { ?>
								<a href='op_chat?link=<?= encode("user_id={$user['id']}"); ?>' class="list-group-item list-group-item-action border-0">
									<div class="badge bg-success float-end"><i class='fa fa-refresh'></i></div>
									<div class="d-flex align-items-start">
									<?= show_img($user['user_photo']); ?>
										<div class="flex-grow-1 ms-3">
											<?= $user['user_name'] ?>
											<div class="small"><span class="fas fa-circle chat-online"></span> <?= $user['status'] ?></div>
										</div>
									</div>
								</a>
							<?php } ?>	
								<hr class="d-block d-lg-none mt-1 mb-0" />
							</div>

							<?php if(isset($_GET['link']))
							{
								$data = decode($_GET['link']);
								$sel_id  = $data['user_id'];
								$seluser = get_data('op_user',$sel_id)['data'];
							
							?>
							<div class="col-12 col-lg-7 col-xl-9">
								<div class="py-2 px-4 border-bottom d-none d-lg-block">
									<div class="d-flex align-items-center py-1">
										<div class="position-relative">
										    
										<?= show_img($seluser['user_photo']); ?>	
										<?php //echo show_img($seluser['user_photo']); ?>	
										</div>
										<div class="flex-grow-1 ps-3">
											<strong><?= $seluser['full_name']; ?></strong>
											<div class="text-muted small"><em><?= $seluser['status']; ?></em></div>
										</div>
										<div>
											<a href="tel:<?= $seluser['user_mobile']; ?>" class="btn btn-primary btn-lg me-1 px-3"><i class="feather-lg" data-feather="phone"></i></a>
											<a href="https://wa.me/91<?= $seluser['user_mobile']; ?>" class="btn btn-success btn-lg me-1 px-3"><i class="fab fa-whatsapp"></i></a>
											<a href="mailto:<?= $seluser['user_email']; ?>" class="btn btn-primary btn-lg me-1 px-3"><i class="feather-lg" data-feather="mail"></i></a>
											
											
										</div>
									</div>
								</div>

								<div class="position-relative">
									<div class="chat-messages p-4">
									<?php 
										$sql_chat ="SELECT * FROM `op_msg` WHERE to_user in ($sel_id,$user_id) and created_by in($sel_id,$user_id) order by id limit 100";
										$chats = direct_sql($sql_chat);

										foreach((array)$chats['data'] as $chat)
										{
											if($chat['created_by'] == $user_id)
											{
												$chat_by = get_data('op_user',$user_id)['data'];
									?>	
									<div class="chat-message-right pb-4">
											<div>
											<?= show_img($chat_by['user_photo']); ?>
											<div class="text-primary small text-nowrap mt-2"><?= time_gap($chat['created_at']); ?> Ago</div>
											</div>
											<div class="flex-shrink-1 bg-light rounded py-2 px-3 ms-3">
												<div class="font-weight-bold mb-1"><?= $chat_by['full_name']; ?></div>
												<i><?= $chat['message'] ?></i>
												<span style='float-right'><?= btn_delete('op_msg',$chat['id']) ?></span>
											</div>
										</div>
									<?php } else {
										
										$chat_by = get_data('op_user',$chat['created_by'])['data'];
										?>
										<div class="chat-message-left pb-4">
											<div>
											<?= show_img($chat_by['user_photo']); ?>	
											<div class="text-primary small text-nowrap mt-2"><?= time_gap($chat['created_at']); ?> ago </div>
											</div>
											<div class="flex-shrink-1 bg-light rounded py-2 px-3 me-3">
												<div class="font-weight-bold mb-1"><?= $chat_by['full_name']; ?></div>
												<i><?= $chat['message'] ?></i>
											</div>
										</div>
									<?php } // Else Close ?>
									<?php } // Loop Close ?> 
										


									</div>
								</div>

								<div class="flex-grow-0 py-3 px-4 border-top">
									
									<div class="input-group">
										<input type="hidden" class="form-control" id='to_user' value='<?= $sel_id; ?>'>
										<input type="text" class="form-control" id='chat_msg' placeholder="Type your message">
										<button class="btn btn-primary" id='send_chat'>Send</button>
									</div>
								</div>

							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</main>
<?php require_once("footer.php"); ?>


<script>
//===========ADD SINGLE DATA ===========//
$("#send_chat").click(function () {
	var msg = $("#chat_msg").val();
	var to_user = $("#to_user").val();
	if(msg=='')
	{
		notyf("Enter a Valid Message", "error");
	}
	else{
		$.ajax({
			'type': 'POST',
			'url': sys_url+"send_chat",
			'data': {
				"message":msg,
				"to_user":to_user
			},
			success: function (data) {
				var obj = JSON.parse(data);
				notyf("Message Send Successfully", obj.status);
				setTimeout(location.reload(),2000);
			}
		});
	}
});

</script>