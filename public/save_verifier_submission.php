<?php
/**
 * KPRM - Save Verifier Submission
 * Handles saving remarks and uploading attachments for tasks
 */

require_once('../system/op_lib.php');
require_once('../function.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'save_verifier_submission') {
    $case_task_id = isset($_POST['case_task_id']) ? intval($_POST['case_task_id']) : 0;
    $verifier_remarks = isset($_POST['verifier_remarks']) ? trim($_POST['verifier_remarks']) : '';
    
    if (!$case_task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    // Verify task exists and user is assigned
    $case_task = get_data('case_tasks', $case_task_id);
    if ($case_task['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    $case_task_data = $case_task['data'];
    $assigned_to = $case_task_data['assigned_to'] ?? 0;
    $is_assigned = ($assigned_to == $_SESSION['user_id']) || ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV');
    
    if (!$is_assigned && $assigned_to > 0) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this task']);
        exit;
    }
    
    // Update task_data with verifier remarks
    $task_data = json_decode($case_task_data['task_data'] ?? '{}', true);
    if (!is_array($task_data)) {
        $task_data = [];
    }
    
    if (!empty($verifier_remarks)) {
        $task_data['verifier_remarks'] = $verifier_remarks;
        $task_data['verifier_remarks_updated_at'] = date('Y-m-d H:i:s');
        $task_data['verifier_remarks_updated_by'] = $_SESSION['user_id'];
    }
    
    // Update case_tasks with new task_data
    $update_data = [
        'task_data' => json_encode($task_data),
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    // Don't auto-change status - user must explicitly mark verification as complete
    $update_result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($update_result['status'] != 'success') {
        echo json_encode(['success' => false, 'message' => 'Failed to save remarks: ' . ($update_result['message'] ?? 'Unknown error')]);
        exit;
    }
    
    // Handle file uploads
    $uploaded_files = [];
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        global $con;
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../upload/task_attachments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $files = $_FILES['attachments'];
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];
                
                // Get file extension
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Generate unique filename
                $unique_name = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                $target_path = $upload_dir . $unique_name;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $target_path)) {
                    // Insert into attachments table
                    $attachment_data = [
                        'task_id' => $case_task_id,
                        'file_name' => $file_name,
                        'file_type' => $file_type,
                        'file_url' => 'task_attachments/' . $unique_name,
                        'status' => 'ACTIVE',
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_by' => $_SESSION['user_id']
                    ];
                    
                    $insert_result = insert_data('attachments', $attachment_data);
                    
                    if ($insert_result['status'] == 'success') {
                        $uploaded_files[] = $file_name;
                    }
                }
            }
        }
    }
    
    $message = 'Submission saved successfully!';
    if (!empty($uploaded_files)) {
        $message .= ' ' . count($uploaded_files) . ' file(s) uploaded.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'files_uploaded' => count($uploaded_files)
    ]);
    exit;
    
} elseif ($action == 'delete_attachment') {
    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    
    if (!$attachment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid attachment ID']);
        exit;
    }
    
    // Get attachment info
    $attachment = get_data('attachments', $attachment_id);
    if ($attachment['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Attachment not found']);
        exit;
    }
    
    $attachment_data = $attachment['data'];
    $task_id = $attachment_data['task_id'];
    
    // Verify user has permission (assigned to task or ADMIN/DEV)
    $case_task = get_data('case_tasks', $task_id);
    if ($case_task['count'] > 0) {
        $case_task_data = $case_task['data'];
        $assigned_to = $case_task_data['assigned_to'] ?? 0;
        $is_assigned = ($assigned_to == $_SESSION['user_id']) || ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV');
        
        if (!$is_assigned && $assigned_to > 0) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this attachment']);
            exit;
        }
    }
    
    // Delete file from server
    $file_path = '../upload/' . $attachment_data['file_url'];
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
    
    // Soft delete from database
    $delete_data = [
        'status' => 'DELETED',
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    $delete_result = update_data('attachments', $delete_data, $attachment_id);
    
    if ($delete_result['status'] == 'success') {
        echo json_encode(['success' => true, 'message' => 'Attachment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete attachment: ' . ($delete_result['message'] ?? 'Unknown error')]);
    }
    exit;
    
} elseif ($action == 'complete_verification') {
    $case_task_id = isset($_POST['case_task_id']) ? intval($_POST['case_task_id']) : 0;
    
    if (!$case_task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    // Verify task exists and user is assigned
    $case_task = get_data('case_tasks', $case_task_id);
    if ($case_task['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    $case_task_data = $case_task['data'];
    $assigned_to = $case_task_data['assigned_to'] ?? 0;
    $current_status = $case_task_data['task_status'] ?? 'PENDING';
    
    // Only verifier assigned to task can complete verification (or ADMIN/DEV)
    $is_assigned = ($assigned_to == $_SESSION['user_id']) || ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV');
    
    if (!$is_assigned && $assigned_to > 0) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this task']);
        exit;
    }
    
    // Only allow if status is IN_PROGRESS
    if ($current_status != 'IN_PROGRESS' && ($_SESSION['user_type'] != 'ADMIN' && $_SESSION['user_type'] != 'DEV')) {
        echo json_encode(['success' => false, 'message' => 'Task must be IN_PROGRESS to complete verification. Current status: ' . $current_status]);
        exit;
    }
    
    // Update task status to VERIFICATION_COMPLETED
    $update_data = [
        'task_status' => 'VERIFICATION_COMPLETED',
        'verified_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    $update_result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($update_result['status'] == 'success') {
        echo json_encode([
            'success' => true,
            'message' => 'Verification marked as complete! Task is now ready for review.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to complete verification: ' . ($update_result['message'] ?? 'Unknown error')
        ]);
    }
    exit;
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

