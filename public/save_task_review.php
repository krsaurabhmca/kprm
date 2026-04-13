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

if ($action == 'paste_image') {
    // Handle pasted image upload
    $case_task_id = isset($_POST['case_task_id']) ? intval($_POST['case_task_id']) : 0;
    $case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;
    
    if (!$case_task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    // Verify task exists
    $case_task = get_data('case_tasks', $case_task_id);
    if ($case_task['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        global $con;
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../upload/task_attachments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type'];
        
        // Generate unique filename
        $unique_name = time() . '_' . rand(1000, 9999) . '_pasted_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
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
                echo json_encode([
                    'success' => true,
                    'message' => 'Image pasted and attached successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save attachment: ' . ($insert_result['message'] ?? 'Unknown error')
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No image file received']);
    }
    exit;
    
} elseif ($action == 'save_review') {
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
    $case_id = $case_task_data['case_id'] ?? 0;
    
    // Get client info for status words
    $case_info = get_data('cases', $case_id);
    $case_data = $case_info['count'] > 0 ? $case_info['data'] : null;
    $client_id = $case_data['client_id'] ?? 0;
    
    $client_info = get_data('clients', $client_id);
    $client_data = $client_info['count'] > 0 ? $client_info['data'] : null;
    $positive_status = $client_data['positve_status'] ?? 'Positive';
    $negative_status = $client_data['negative_status'] ?? 'Negative';
    $cnv_status = $client_data['cnv_status'] ?? 'CNV';
    
    // Get client status word based on review status
    $client_status_word = '';
    if ($review_status == 'POSITIVE') {
        $client_status_word = $positive_status;
    } elseif ($review_status == 'NEGATIVE') {
        $client_status_word = $negative_status;
    } elseif ($review_status == 'CNV') {
        $client_status_word = $cnv_status;
    }
    
    // Replace #status# in review remarks with client-defined status word
    // This ensures that even if client-side replacement didn't work, server-side will handle it
    if (!empty($client_status_word)) {
        $review_remarks = str_replace('#status#', $client_status_word, $review_remarks);
        // Also handle case-insensitive replacement
        $review_remarks = preg_replace('/#status#/i', $client_status_word, $review_remarks);
    }
    
    // Update task_data with review information
    $task_data = json_decode($case_task_data['task_data'] ?? '{}', true);
    if (!is_array($task_data)) {
        $task_data = [];
    }
    
    $task_data['review_status'] = $review_status;
    $task_data['review_remarks'] = $review_remarks;
    $task_data['review_updated_at'] = date('Y-m-d H:i:s');
    $task_data['review_updated_by'] = $_SESSION['user_id'];
    
    // Also include any task meta updates (like financial table edits)
    if (isset($_POST['task_meta']) && is_array($_POST['task_meta'])) {
        foreach ($_POST['task_meta'] as $key => $val) {
            $task_data[$key] = $val;
        }
    }
    
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
        'message' => 'Review saved successfully! Task marked as completed.',
        'redirect' => 'view_case.php?case_id=' . $case_task_data['case_id']
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
    
} elseif ($action == 'bulk_delete_attachments') {
    $attachment_ids = isset($_POST['attachment_ids']) ? trim($_POST['attachment_ids']) : '';
    
    if (empty($attachment_ids)) {
        echo json_encode(['success' => false, 'message' => 'No attachments selected']);
        exit;
    }
    
    $ids_array = array_map('intval', explode(',', $attachment_ids));
    $ids_array = array_filter($ids_array);
    
    if (empty($ids_array)) {
        echo json_encode(['success' => false, 'message' => 'Invalid attachment IDs']);
        exit;
    }
    
    global $con;
    $deleted_count = 0;
    
    foreach ($ids_array as $id) {
        // Get attachment info
        $attachment = get_data('attachments', $id);
        if ($attachment['count'] > 0) {
            $attachment_data = $attachment['data'];
            
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
            
            $delete_result = update_data('attachments', $delete_data, $id);
            if ($delete_result['status'] == 'success') {
                $deleted_count++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Deleted ' . $deleted_count . ' attachment(s) successfully',
        'deleted_count' => $deleted_count
    ]);
    exit;
    
} elseif ($action == 'get_log_details') {
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    
    if (!$log_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
        exit;
    }
    
    $log = get_data('activity_log', $log_id);
    
    if ($log['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
        exit;
    }
    
    $log_data = $log['data'];
    
    // Format date_time
    if (!empty($log_data['date_time'])) {
        $log_data['date_time'] = date('d M Y H:i:s', strtotime($log_data['date_time']));
    }
    
    // Format created_at
    if (!empty($log_data['created_at'])) {
        $log_data['created_at'] = date('d M Y H:i:s', strtotime($log_data['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'log' => $log_data
    ]);
    exit;
    
} elseif ($action == 'delete_activity_log') {
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    
    if (!$log_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
        exit;
    }
    
    // Soft delete - set status to DELETED
    $delete_data = [
        'status' => 'DELETED',
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    $delete_result = update_data('activity_log', $delete_data, $log_id);
    
    if ($delete_result['status'] == 'success') {
        echo json_encode(['success' => true, 'message' => 'Activity log deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete log: ' . ($delete_result['message'] ?? 'Unknown error')]);
    }
    exit;
    
} elseif ($action == 'generate_ai_remarks') {
    $case_task_id = isset($_POST['case_task_id']) ? intval($_POST['case_task_id']) : 0;
    $review_status = isset($_POST['review_status']) ? trim($_POST['review_status']) : '';
    
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
    $task_template_id = $case_task_data['task_template_id'];
    $case_id = $case_task_data['case_id'];
    
    // Get case and client info for status words
    $case_info = get_data('cases', $case_id);
    $case_data = $case_info['count'] > 0 ? $case_info['data'] : null;
    $client_id = $case_data['client_id'] ?? 0;
    
    // Get client info for status options
    $client_info = get_data('clients', $client_id);
    $client_data = $client_info['count'] > 0 ? $client_info['data'] : null;
    $positive_status = $client_data['positve_status'] ?? 'Positive';
    $negative_status = $client_data['negative_status'] ?? 'Negative';
    $cnv_status = $client_data['cnv_status'] ?? 'CNV';
    
    // Get task template to check task type
    $task_template = get_data('tasks', $task_template_id);
    if ($task_template['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Task template not found']);
        exit;
    }
    
    $task_template_data = $task_template['data'];
    $task_type = $task_template_data['task_type'] ?? '';
    
    // Only generate AI remarks for PHYSICAL task types
    if (strtoupper($task_type) != 'PHYSICAL') {
        echo json_encode(['success' => false, 'message' => 'AI remarks generation is only available for PHYSICAL task types']);
        exit;
    }
    
    // Get the format template based on review status
    $format = '';
    $status_label = '';
    $client_status_word = '';
    
    if ($review_status == 'POSITIVE') {
        $format = $task_template_data['positive_format'] ?? '';
        $status_label = 'Positive';
        $client_status_word = $positive_status;
    } elseif ($review_status == 'NEGATIVE') {
        $format = $task_template_data['negative_format'] ?? '';
        $status_label = 'Negative';
        $client_status_word = $negative_status;
    } elseif ($review_status == 'CNV') {
        $format = $task_template_data['cnv_format'] ?? '';
        $status_label = 'CNV';
        $client_status_word = $cnv_status;
    }
    
    if (empty($format)) {
        echo json_encode(['success' => false, 'message' => 'Template format not found for selected status']);
        exit;
    }
    
    // Replace #status# in format template with client-defined status word
    $format = str_replace('#status#', $client_status_word, $format);
    
    // Get task data
    $task_data = json_decode($case_task_data['task_data'] ?? '{}', true);
    if (!is_array($task_data)) {
        $task_data = [];
    }
    
    // Prepare data for AI generation (convert to JSON string)
    $data_json = json_encode($task_data);
    
    // Generate AI remarks
    $ai_remarks = gen_ai_remarks($format, $data_json, $status_label);
    
    if (empty($ai_remarks)) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate AI remarks. Please try again.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'remarks' => $ai_remarks
    ]);
    exit;
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

