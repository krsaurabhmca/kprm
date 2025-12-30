<?php
include('op_lib.php');
// TELEGRAM BOT API //

$botToken = "6191423487:AAFJB6B10drDh_8o-jyUOres-qeZWljx0fw";
// To Set Web hook 
// https://api.telegram.org/bot6191423487:AAFJB6B10drDh_8o-jyUOres-qeZWljx0fw/setWebhook?url=https://opex.x2z.in/system/tg_bot.php

// To Check Webhook Information 
//https://api.telegram.org/bot6191423487:AAFJB6B10drDh_8o-jyUOres-qeZWljx0fw/getWebhookInfo


// Retrieve the raw POST data from Telegram
$rawData = file_get_contents('php://input');
create_log($rawData); 
$data = json_decode($rawData,true);


//Check if the received data is a message
if (isset($data['message'])) {
    
    $message = $data['message'];
    $idata['chat_id'] = $chatId = $message['chat']['id'];
    $idata['user_id'] = $message['from']['id'];
    $idata['username'] = $message['chat']['username'];
    $idata['text'] = $message['text'];
    $idata['date'] = $message['date'];
    
     if(isset($message['from']['id']))
   {
       $fileId  = $message['from']['id'];
       $idata['user_photo'] = getProfilePic($fileId);
   }
   
    if(isset($message['photo'][2]['file_id']))
   {
       $fileId  = $message['photo'][2]['file_id'];
       $idata['photo'] = getFileUrl($fileId);
       $idata['text'] = $message['caption'];
   }
   if(isset($message['document']['file_id']))
   {
       $fileId  = $message['document']['file_id'];
       $idata['document'] = getFileUrl($fileId);
   }
   if(isset($message['video']['file_id']))
   {
       $fileId  = $message['video']['file_id'];
       $idata['video'] = getFileUrl($fileId);
   }
    if(isset($message['audio']['file_id']))
   {
       $fileId  = $message['audio']['file_id'];
       $idata['audio'] = getFileUrl($fileId);
   }
   
    $idata['status'] ='ACTIVE';
    
    $res = insert_data('op_telegram', $idata);
    

    // Respond to the user
    $response = "Message received and saved.";
    sendMessage($chatId, $response);
}

// Function to send a response message via the Telegram API
function sendMessage($chatId='1171583771', $message)
{
    global $botToken;
    // Replace with your Telegram Bot API token
    $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage";
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
    ];
    echo $apiUrl;
    // Send the HTTP request to the Telegram API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    print_r($response);
    curl_close($ch);
}



function getFileUrl($fileId) {
    global $botToken;
    $url = 'https://api.telegram.org/bot' . $botToken . '/getFile?file_id=' . $fileId;
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data['ok']) {
        $fileUrl = 'https://api.telegram.org/file/bot' . $botToken . '/' . $data['result']['file_path'];
        return $fileUrl;
    } else {
        return null; // Error occurred, handle accordingly
    }
}

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
        return $photoUrl = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

        // Display the photo
        ///echo '<img src="' . $photoUrl . '" alt="User Profile Picture">';
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

