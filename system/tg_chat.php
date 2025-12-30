<?php require_once("all_header.php");
$botToken = "6191423487:AAFJB6B10drDh_8o-jyUOres-qeZWljx0fw";
function getProfilePic($userId)
{
    global $botToken;

    // Get the user's profile photos
    $response = file_get_contents("https://api.telegram.org/bot{$botToken}/getUserProfilePhotos?user_id={$userId}");
    $data = json_decode($response, true);

    if ($data['ok'] && $data['result']['total_count'] > 0) {
    // Get the file ID of the first photo
    $fileId = $data['result']['photos'][0][0]['file_id'];

    // Get the file path using the file ID
    $response = file_get_contents("https://api.telegram.org/bot{$botToken}/getFile?file_id={$fileId}");
    $data = json_decode($response, true);

    if ($data['ok']) {
        // Get the file path
        $filePath = $data['result']['file_path'];

        // Generate the URL for the photo
        $photoUrl = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

        // Display the photo
        echo '<img src="' . $photoUrl . '" alt="User Profile Picture" style="width:50px;height:50px;border-radius:50%">';
        } else {
            echo 'Error retrieving file path.';
            return false;
        }
    } else {
        echo 'User has no profile photos.';
         return false;
    }

}
?>
<style>
	.delete_btn{
		background-color:#f5f7fb;
		color:#cc0000;
		font-size:12px;
		float:right;
		border:none;
	}
	
	.dimg
	{
	    height:120px;
	    border-radius:5px;
	}
/* Input type File  */

.container {
  display: flex;
  align-items: flex-start;
  justify-content: flex-start;
  width: 100%;
}

.custom-file{
    /*background:#0000dd;*/
    /*color:#fff;*/
    font-size:16px;
    width:40px;
    text-align:center;
}

</style>
			<main class="content">
				<div class="container-fluid p-0">

					<div class="mb-3">
						<h1 class="h3 d-inline align-middle">Telegram Chat</h1>
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
							<?php 
							$sql ="select distinct(user_id),username, status from op_telegram where user_id <>'' ";
							$all_user = direct_sql($sql);
							foreach((array)$all_user['data'] as $user) { ?>
								<a href='tg_chat?link=<?= encode("user_id={$user['user_id']}"); ?>' class="list-group-item list-group-item-action border-0">
									<div class="badge bg-success float-end"><i class='fa fa-refresh'></i></div>
									<div class="d-flex align-items-start">
									<?= getProfilePic($user['user_id']); ?>
										<div class="flex-grow-1 ms-3">
											<?= $user['username'] ?>
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
							
							
							?>
							<div class="col-12 col-lg-7 col-xl-9">
								<div class="py-2 px-4 border-bottom d-none d-lg-block">
									<div class="d-flex align-items-center py-1">
										<div class="position-relative">
										<?= getProfilePic($user['user_id']); ?>
										</div>
										<div class="flex-grow-1 ps-3">
											<strong><?= $sel_id; ?></strong>
											<div class="text-muted small"><em>Active</em></div>
										</div>
									
									</div>
								</div>

								<div class="position-relative">
									<div class="chat-messages p-4">
									<?php 
										$sql_chat ="SELECT * FROM op_telegram WHERE user_id in ($sel_id,$user_id) order by id limit 100";
										$chats = direct_sql($sql_chat);

										foreach((array)$chats['data'] as $chat)
										{
											
									?>	
									<div class="chat-message-right pb-4">
											<div>
											<?= getProfilePic($chat['user_id']); ?>
											<div class="text-primary small text-nowrap mt-2"><?= time_gap($chat['created_at']); ?> Ago</div>
											</div>
											<div class="flex-shrink-1 bg-light rounded py-2 px-3 ms-3">
												<div class="font-weight-bold mb-1"><?= $chat['user_id']; ?></div>
												<i><?= $chat['text'] ?></i>
												<?= ($chat['photo']=="")?"":"<img src={$chat['photo']} class='dimg'  >"; ?>
												<?= ($chat['audio']=="")?"":"<audio controls class='daudio'  ><source  src={$chat['audio']} ></audio>"; ?>
												<?= ($chat['video']=="")?"":"<video controls class='dvideo  ><source  src={$chat['video']}  ></video>"; ?>
												<?= ($chat['document']=="")?"":"<a  href={$chat['document']} class='ddcoument' download ><i class='fa fa-download fa-2x'></i></a>"; ?>
												<span style='float-right'><?= btn_delete('op_telegram',$chat['id']) ?></span>
											</div>
										</div>
										<!--<div class="chat-message-left pb-4">-->
										<!--	<div>-->
										<!--	<?= show_img($chat_by['user_photo']); ?>	-->
										<!--	<div class="text-primary small text-nowrap mt-2"><?= time_gap($chat['created_at']); ?> ago </div>-->
										<!--	</div>-->
										<!--	<div class="flex-shrink-1 bg-light rounded py-2 px-3 me-3">-->
										<!--		<div class="font-weight-bold mb-1"><?= $chat_by['full_name']; ?></div>-->
										<!--		<i><?= $chat['message'] ?></i>-->
										<!--	</div>-->
										<!--</div>-->
								
									<?php } // Loop Close ?> 
										


									</div>
								</div>

								<div class="flex-grow-0 py-3 px-4 border-top">
									<form id="tg_form" enctype="multipart/form-data">
									    <input type='hidden' value='<?= $chat['user_id'] ?>'  name='to_user'> 
									    <div class="input-group">
									        <div class="custom-file btn btn-primary">
                                            <input type="file" name="file" class="custom-file-input" id="fileInput" aria-describedby="fileInputGroupAddon" hidden>
                                            <label class="custom-file-label" for="fileInput">
                                              <i class="fa-solid fa-file-lines"></i>
                                            </label>
                                          </div>
                                          <input type="text" name="message" id="message" class="form-control" placeholder="Enter your message">
                                          <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit" id="uploadButton">Upload</button>
                                          </div>
                                        </div>
                                    </form>
                                    
                                
								</div>

							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</main>
<?php require_once("footer.php"); ?>


<script>

$(document).ready(function() {
  $('#tg_form').submit(function(e) {
    e.preventDefault();

    var formData = new FormData(this);

    $.ajax({
      url: 'tg_send.php', // Replace with the PHP file that handles the server-side processing
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      success: function(response) {
         notyf("Message Sent Successfully", "success");
         $('#tg_form')[0].reset();
        console.log(response); // You can handle the response here
        // Display success message or perform any other actions
      },
      error: function(xhr, status, error) {
        console.log(error); // Handle the error
        // Display error message or perform any other actions
      }
    });
  });
});


</script>