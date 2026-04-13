<?php
/**
 * KPRM - Task Update API
 * Receives task status updates and attachments from external systems (RMS)
 * 
 * Expected POST fields:
 * - ref_id: internal KPRM task ID (case_task_id)
 * - status: new task status (e.g., COMPLETED, REJECTED)
 * - remarks: optional remarks/notes
 * - attachment_link: URL of the attachment file
 */

require_once('../system/op_lib.php');

// Set system user ID for API actions
$user_id = 1;

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set header to JSON
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Use POST.']);
    exit;
}

// Get POST data
$task_id = isset($_POST['ref_id']) ? intval($_POST['ref_id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
$attachment = isset($_POST['attachment_link']) ? $_POST['attachment_link'] : '';

// Validation
if ($task_id <= 0) {
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => 'Missing or invalid ref_id. Received: ' . $task_id]);
    exit;
}

// Ensure database columns for callback store exist
add_column('case_tasks', 'task_remarks', 'TEXT');
add_column('case_tasks', 'attachment_link', 'VARCHAR(255)');
add_column('case_tasks', 'verified_at', 'TIMESTAMP NULL');

// Fetch existing task data to update the JSON store
$old_task_res = get_data('case_tasks', $task_id);
if ($old_task_res['count'] == 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Task with ID ' . $task_id . ' not found in KPRM system.']);
    exit;
}

$old_task = $old_task_res['data'];
$task_meta = json_decode($old_task['task_data'] ?? '[]', true);
if (!is_array($task_meta)) $task_meta = [];

// Store all incoming POST data in task_meta even for completeness
foreach ($_POST as $key => $val) {
    if (!is_array($val)) {
        $task_meta[$key] = $val;
    }
}

// Store external updates even in the JSON field for completeness
$task_meta['external_status'] = $status;
$task_meta['external_remarks'] = $remarks;
$task_meta['external_attachment'] = $attachment;
$task_meta['updated_at_api'] = date('Y-m-d H:i:s');

// ALSO set verifier_remarks as expected by task_review.php
if (!empty($remarks)) {
    $task_meta['verifier_remarks'] = $remarks;
    $task_meta['verifier_remarks_updated_at'] = date('Y-m-d H:i:s');
    $task_meta['verifier_remarks_updated_by'] = $user_id;
} elseif (isset($_POST['external_remarks']) && !empty($_POST['external_remarks'])) {
    $task_meta['verifier_remarks'] = $_POST['external_remarks'];
    $task_meta['verifier_remarks_updated_at'] = date('Y-m-d H:i:s');
    $task_meta['verifier_remarks_updated_by'] = $user_id;
}

// Handle Attachment if provided
if (!empty($attachment)) {
    // Generate unique filename
    $file_info = pathinfo($attachment);
    $file_ext = isset($file_info['extension']) ? $file_info['extension'] : 'jpg';
    $base_name = isset($file_info['basename']) ? $file_info['basename'] : 'attachment';
    // Clean basename for filesystem
    $clean_base = preg_replace('/[^a-zA-Z0-9._-]/', '', $base_name);
    $unique_name = time() . '_' . rand(1000, 9999) . '_' . $clean_base;
    
    // Ensure it has an extension
    if (empty(pathinfo($unique_name, PATHINFO_EXTENSION)) && !empty($file_ext)) {
        $unique_name .= '.' . $file_ext;
    }
    
    $upload_dir = '../upload/task_attachments/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $target_path = $upload_dir . $unique_name;
    
    // Download file using CURL for better reliability than file_get_contents
    $ch = curl_init($attachment);
    $fp = fopen($target_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL errors for local/internal tests
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    
    if ($http_code == 200 && file_exists($target_path) && filesize($target_path) > 0) {
        $final_file_url = 'task_attachments/' . $unique_name;
    } else {
        // If curl failed, delete the empty file and use the external URL as fallback
        @unlink($target_path);
        $final_file_url = $attachment; // Use absolute URL
        // Log error in task_meta
        $task_meta['api_attachment_error'] = "Failed to download attachment. HTTP Code: $http_code. Using external link as fallback.";
    }

    // Insert into attachments table (whether download succeeded or not)
    $attachment_data = [
        'task_id' => $task_id,
        'file_name' => $base_name,
        'file_type' => 'image/' . $file_ext, // Approximation
        'file_url' => $final_file_url,
        'status' => 'ACTIVE',
        'display_in_report' => 'YES', // Auto display in report for review
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $user_id
    ];
    insert_data('attachments', $attachment_data);
}

// Prepare update data
// We force status to VERIFICATION_COMPLETED to make it show in Review
$update_data = [
    'task_status' => 'VERIFICATION_COMPLETED',
    'verified_at' => date('Y-m-d H:i:s'),
    'task_remarks' => $remarks,
    'attachment_link' => $attachment,
    'task_data' => json_encode($task_meta),
    'updated_at' => date('Y-m-d H:i:s')
];

// Perform update
$update_result = update_data('case_tasks', $update_data, $task_id);

if ($update_result['status'] == 'success') {
    echo json_encode(['status' => 'success', 'message' => 'Task updated as verified and attachment processed.']);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to update database: ' . ($update_result['message'] ?? 'Unknown DB error'),
        'sql' => $update_result['sql'] ?? ''
    ]);
}

