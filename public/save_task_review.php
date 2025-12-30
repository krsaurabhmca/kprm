<?php
/**
 * KPRM - Save Task Review
 * Handles saving review status, remarks, and attachment display settings
 */

require_once('../system/op_lib.php');
require_once('../function.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'save_review') {
    $case_task_id = isset($_POST['case_task_id']) ? intval($_POST['case_task_id']) : 0;
    $review_status = isset($_POST['review_status']) ? trim($_POST['review_status']) : '';
    $review_remarks = isset($_POST['review_remarks']) ? trim($_POST['review_remarks']) : '';
    $attachment_ids = isset($_POST['attachment_ids']) ? $_POST['attachment_ids'] : '';
    
    if (!$case_task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    if (empty($review_status)) {
        echo json_encode(['success' => false, 'message' => 'Please select a review status']);
        exit;
    }
    
    // Verify task exists
    $case_task = get_data('case_tasks', $case_task_id);
    if ($case_task['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    $case_task_data = $case_task['data'];
    
    // Update task_data with review information
    $task_data = json_decode($case_task_data['task_data'] ?? '{}', true);
    if (!is_array($task_data)) {
        $task_data = [];
    }
    
    $task_data['review_status'] = $review_status;
    $task_data['review_remarks'] = $review_remarks;
    $task_data['review_updated_at'] = date('Y-m-d H:i:s');
    $task_data['review_updated_by'] = $_SESSION['user_id'];
    
    // Update case_tasks with new task_data
    $update_data = [
        'task_data' => json_encode($task_data),
        'task_status' => 'COMPLETED', // Mark task as completed after review
        'reviewed_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    $update_result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($update_result['status'] != 'success') {
        echo json_encode(['success' => false, 'message' => 'Failed to save review: ' . ($update_result['message'] ?? 'Unknown error')]);
        exit;
    }
    
    // Handle attachment display_in_report updates
    global $con;
    
    // First, set all attachments for this task to NO
    $reset_sql = "UPDATE attachments SET display_in_report = 'NO', updated_at = NOW(), updated_by = {$_SESSION['user_id']} WHERE task_id = '$case_task_id' AND status = 'ACTIVE'";
    mysqli_query($con, $reset_sql);
    
    // Then set selected attachments to YES
    if (!empty($attachment_ids)) {
        if (is_string($attachment_ids)) {
            $attachment_ids = explode(',', $attachment_ids);
        }
        
        if (is_array($attachment_ids) && !empty($attachment_ids)) {
            $attachment_ids = array_map('intval', $attachment_ids);
            $attachment_ids = array_filter($attachment_ids);
            
            if (!empty($attachment_ids)) {
                $ids_str = implode(',', $attachment_ids);
                $update_sql = "UPDATE attachments SET display_in_report = 'YES', updated_at = NOW(), updated_by = {$_SESSION['user_id']} WHERE id IN ($ids_str) AND task_id = '$case_task_id' AND status = 'ACTIVE'";
                mysqli_query($con, $update_sql);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Review saved successfully! Task marked as completed.'
    ]);
    exit;
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

