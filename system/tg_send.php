<?php
// Set your bot token
$botToken = "6191423487:AAFJB6B10drDh_8o-jyUOres-qeZWljx0fw";
// Get the user input
$message = $_POST['message'];
$chatId = $_POST['to_user'];
$file = $_FILES['file'];

if (!empty($message) || !empty($file['tmp_name'])) {
    // Set the chat ID
    //$chatId = 1171583771; // Replace with the actual chat ID

    // Send a text message
    if (!empty($message)) {
        sendMessage($botToken, $chatId, $message);
    }

    // Send a file
    if (!empty($file['tmp_name'])) {
        sendDocument($botToken, $chatId, $file);
    }
}

function sendMessage($botToken, $chatId, $message)
{
    // Prepare the message data
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
    ];

    // Send the message using the Telegram Bot API
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    sendRequest($url, $data);
}

function sendDocument($botToken, $chatId, $file)
{
    // Prepare the file data
    $data = [
        'chat_id' => $chatId,
        'document' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
    ];

    // Send the document using the Telegram Bot API
    $url = "https://api.telegram.org/bot{$botToken}/sendDocument";
    sendRequest($url, $data);
}

function sendRequest($url, $data)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);
    curl_close($curl);

    if ($result === false) {
        echo 'Error sending message or file.';
    } else {
        echo 'Message or file sent successfully.';
    }
}
?>
