<?php 

include_once('config.php');
function sendTemplateMessage($phoneNumber, $templateName, $header = [], $fields = [], $contact = null) {
    $apiBaseUrl = "https://whatsapp.x2z.in/api";
    $vendorUid  = "d6213789-b0da-4393-bfc6-b1a824175df7";
    $templateLanguage = "en_US";
    $bearerToken = "28HlzRLdAhlZ40CjbNobGDPiY611TCn2p5M2PYPzK0nJHkFVvpl1E2hdFDdqvpwl";

    // Build payload
    $payload = [
        "phone_number"      => "91".$phoneNumber,
        "template_name"     => $templateName,
        "template_language" => $templateLanguage,
    ];

    // Add header (only one allowed)
    if (!empty($header)) {
        if (isset($header['image'])) {
            $payload["header_image"] = $header['image'];
        } elseif (isset($header['video'])) {
            $payload["header_video"] = $header['video'];
        } elseif (isset($header['document'])) {
            $payload["header_document"] = $header['document'];
            if (!empty($header['document_name'])) {
                $payload["header_document_name"] = $header['document_name'];
            }
        }
    }

    // Add fields if provided
    if (!empty($fields)) {
        $i = 1;
        foreach ($fields as $value) {
            if ($value !== null && $value !== "") {
                $payload["field_{$i}"] = $value;
            }
            $i++;
        }
    }

    // Prepare cURL request
    $url = rtrim($apiBaseUrl, "/") . "/{$vendorUid}/contact/send-template-message";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$bearerToken}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ["success" => false, "error" => $error];
    }

    $responseData = json_decode($response, true);

    // ✅ Insert/Update into DB
    if (isset($responseData['response']['data'])) {
        $data = $responseData['response']['data'];

        // DB connection (adjust host/user/pass/dbname as per your setup)
        $mysqli = new mysqli("localhost", "db_user", "db_pass", "db_name");
        if ($mysqli->connect_error) {
            return ["success" => false, "error" => "DB Connection failed: " . $mysqli->connect_error];
        }

        $stmt = $mysqli->prepare("
            INSERT INTO messages 
                (contact_uid, whatsapp_business_phone_number_id, whatsapp_message_id, replied_to_whatsapp_message_id, 
                 is_new_message, body, status, media_type, media_link, media_caption, 
                 mime_type, file_name, original_filename, created_at, log_uid, phone_number, msg_type, is_read) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
        ");

        $is_new_message = 1;
        $body           = $templateName; // store template name as body for trace
        $status         = $data['status'] ?? null;
        $msg_type       = "template";
        $is_read        = 0;

        $stmt->bind_param(
            "ssssisssssssssss",
            $data['contact_uid'],
            $data['whatsapp_business_phone_number_id'] ?? null,
            $data['wamid'],
            $replied_to = null,
            $is_new_message,
            $body,
            $status,
            $media_type = null,
            $media_link = null,
            $media_caption = null,
            $mime_type = null,
            $file_name = null,
            $original_filename = null,
            $data['log_uid'],
            $data['phone_number'],
            $msg_type,
            $is_read
        );

        $stmt->execute();
        $stmt->close();
        $mysqli->close();
    }

    return ["success" => true, "response" => $responseData];
}
