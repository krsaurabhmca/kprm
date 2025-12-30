<?php
// ==== CONFIG ====
include_once('function.php');
header('Content-Type: application/json');

$api_key = 'AIzaSyAuH9teLxySp8QtEeLegENoC-GYvSVqsXA';
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$api_key";

// ==== Input Validation ====
$input = $_REQUEST; // json_decode(file_get_contents("php://input"), true);

if (!isset($input['task_id']) || !isset($input['task_table'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing task_id or task_table"]);
    exit;
}

$task_id = $input['task_id'];
$task_table = $input['task_table'];
//$remarks = $input['remarks'];

$task_data  = get_data($task_table, $task_id)['data'];
$task_type  = get_data('task_list', $task_data['task_type_id'])['data'];
$template   = $task_type['task_template'];


if ($task_type['task_type'] == 'PHYSICAL') {
    $data = [
        'applicant_name' => $task_data['applicant_name'],
        'address'        => $task_data['address'],
        'requirement'    => $task_data['requirement'] ,
        'remarks'        => $task_data['remarks'],
        'case_status'    => $task_data['case_status']
    ];
}

else if ($task_type['task_type'] == 'BANKING') {
    $data = [
        'applicant_name' => $task_data['applicant_name'],
        'requirement'    => $task_data['requirement'] ,
        'remarks'        => $task_data['remarks'],
        'case_status'    => $task_data['case_status']
    ];
}

else if ($task_type['task_type'] == 'ITO') {
    $data = [
        'applicant_name' => $task_data['applicant_name'],
        'requirement'    => $task_data['requirement'] ,
        'remarks'        => $task_data['remarks'],
        'case_status'    => $task_data['case_status'],
        'information'    => $task_data['other_information']
    ];
}

$prompt = 'Template : '. $template . "\n Data : " . json_encode($data) . "\n regenerate Report on basis of template using my data only. Return final Report in plane text only";

// ==== API Call to Gemini ====
$request_data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        "status" => "success",
        //"prompt" => $prompt,
        "response" => $result['candidates'][0]['content']['parts'][0]['text']
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to get valid response",
        "raw" => $result
    ]);
}
