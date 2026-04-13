<?php
/**
 * KPRM - AJAX Update Task Stage
 * Allows Admin and Developer to manually override task stage
 */

require_once('../system/op_lib.php');

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Check if user is Admin or Developer
if ($_SESSION['user_type'] !== 'ADMIN' && $_SESSION['user_type'] !== 'DEV') {
    echo json_encode(['success' => false, 'message' => 'Only Admin and Developer can change task stages.']);
    exit;
}

// Get data
$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$new_stage = isset($_POST['new_stage']) ? $_POST['new_stage'] : '';

if (!$task_id || empty($new_stage)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

// Allowed stages
$allowed_stages = ['PENDING', 'IN_PROGRESS', 'VERIFICATION_COMPLETED', 'COMPLETED', 'CANCELLED'];
if (!in_array($new_stage, $allowed_stages)) {
    echo json_encode(['success' => false, 'message' => 'Invalid stage selected.']);
    exit;
}

// Prepare update data
$update_data = [
    'task_status' => $new_stage,
    'updated_at' => date('Y-m-d H:i:s'),
    'updated_by' => $_SESSION['user_id']
];

// Special handling for COMPLETED stage
if ($new_stage === 'COMPLETED') {
    // If moving to COMPLETED, ensure we have some basic review status if none exists
    $task_res = get_data('case_tasks', $task_id);
    if ($task_res['count'] > 0) {
        $task = $task_res['data'];
        $task_data = json_decode($task['task_data'] ?? '{}', true);
        if (!isset($task_data['review_status']) || empty($task_data['review_status'])) {
            $task_data['review_status'] = 'POSITIVE'; // Default to Positive for manual completions
            $task_data['review_updated_at'] = date('Y-m-d H:i:s');
            $task_data['review_updated_by'] = $_SESSION['user_id'];
            $task_data['review_remarks'] = 'Stage manually changed to Reviewed by ' . $_SESSION['user_type'];
            $update_data['task_data'] = json_encode($task_data);
        }
    }
}

// Update database
$result = update_data('case_tasks', $update_data, $task_id);

if ($result['status'] === 'success') {
    echo json_encode(['success' => true, 'message' => 'Task stage updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update task stage: ' . ($result['message'] ?? 'Unknown error')]);
}
