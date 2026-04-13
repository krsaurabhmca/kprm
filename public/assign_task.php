<?php
/**
 * KPRM - Assign Task to Verifier (AJAX)
 * Quick assignment modal handler
 */

require_once('../system/op_lib.php');
require_once('../function.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'assign_task') {
    $case_task_id = isset($_POST['case_task_id']) ? intval($_POST['case_task_id']) : 0;
    $verifier_id = isset($_POST['verifier_id']) ? intval($_POST['verifier_id']) : 0;
    $send_whatsapp = isset($_POST['send_whatsapp']) && $_POST['send_whatsapp'] == '1';
    
    if (!$case_task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    // Get task details for WhatsApp message
    $current_task = get_data('case_tasks', $case_task_id);
    if ($current_task['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
        exit;
    }
    
    $task_data = $current_task['data'];
    $case_id = $task_data['case_id'] ?? 0;
    $task_template_id = $task_data['task_template_id'] ?? 0;
    
    // Get task template name
    $task_name = 'Unknown Task';
    if ($task_template_id) {
        $task_template = get_data('tasks', $task_template_id);
        if ($task_template['count'] > 0) {
            $task_name = $task_template['data']['task_name'] ?? 'Unknown Task';
        }
    }
    
    // Get case details
    $case_info = [];
    if ($case_id) {
        $case_result = get_data('cases', $case_id);
        if ($case_result['count'] > 0) {
            $case_info = $case_result['data'];
        }
    }
    
    // If verifier_id is 0, unassign the task
    $update_data = [
        'assigned_to' => $verifier_id > 0 ? $verifier_id : null,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    // If assigning (not unassigning), set task status to IN_PROGRESS
    if ($verifier_id > 0) {
        $current_status = $task_data['task_status'] ?? 'PENDING';
        // Update to IN_PROGRESS if currently PENDING or if reassigning
        if ($current_status == 'PENDING' || $current_status == 'IN_PROGRESS') {
            $update_data['task_status'] = 'IN_PROGRESS';
        }
    } else {
        // If unassigning, set status back to PENDING
        $update_data['task_status'] = 'PENDING';
    }
    
    $result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($result['status'] == 'success') {
        $message = $verifier_id > 0 ? 'Task assigned successfully! Status updated to IN_PROGRESS.' : 'Task unassigned successfully!';
        $whatsapp_sent = false;
        $whatsapp_message = '';
        
        // Send WhatsApp if requested and verifier is assigned
        if ($send_whatsapp && $verifier_id > 0) {
            // Get verifier details
            $verifier = get_data('op_user', $verifier_id);
            if ($verifier['count'] > 0) {
                $verifier_data = $verifier['data'];
                $verifier_mobile = $verifier_data['user_mobile'] ?? '';
                $verifier_name = $verifier_data['full_name'] ?? 'Verifier';
                
                if (!empty($verifier_mobile)) {
                    // Prepare WhatsApp message
                    $application_no = $case_info['application_no'] ?? 'N/A';
                    $client_name = '';
                    if (isset($case_info['client_id'])) {
                        $client = get_data('clients', $case_info['client_id']);
                        if ($client['count'] > 0) {
                            $client_name = $client['data']['name'] ?? '';
                        }
                    }
                    
                    $whatsapp_message = "Hello {$verifier_name},\n\n";
                    $whatsapp_message .= "A new task has been assigned to you:\n\n";
                    $whatsapp_message .= "Task: {$task_name}\n";
                    $whatsapp_message .= "Application No: {$application_no}\n";
                    if (!empty($client_name)) {
                        $whatsapp_message .= "Client: {$client_name}\n";
                    }
                    $whatsapp_message .= "\nPlease complete the verification at your earliest.\n\n";
                    $whatsapp_message .= "Thank you!";
                    
                    // Send WhatsApp using template_msg.php if available
                    if (file_exists('../template_msg.php')) {
                        require_once('../template_msg.php');
                        
                        // Clean mobile number (remove +91, spaces, etc.)
                        $clean_mobile = preg_replace('/[^0-9]/', '', $verifier_mobile);
                        if (strlen($clean_mobile) == 10) {
                            // Use a simple text message format (you may need to adjust based on your WhatsApp API)
                            $whatsapp_result = sendTemplateMessage(
                                $clean_mobile,
                                'task_assignment', // Template name - adjust as needed
                                [],
                                [$task_name, $application_no, $client_name ?: 'N/A'],
                                null, // contact
                                $verifier_id, // to_user
                                'Task Assignment Notification' // title
                            );
                            
                            if (isset($whatsapp_result['success']) && $whatsapp_result['success']) {
                                $whatsapp_sent = true;
                                $message .= ' WhatsApp notification sent.';
                            } else {
                                $whatsapp_message = ' (WhatsApp sending failed: ' . ($whatsapp_result['error'] ?? 'Unknown error') . ')';
                            }
                        }
                    } else {
                        // Fallback: Create WhatsApp link and log message
                        $clean_mobile = preg_replace('/[^0-9]/', '', $verifier_mobile);
                        if (strlen($clean_mobile) == 10) {
                            // Log message even for fallback WhatsApp link
                            log_message(
                                'Task Assignment Notification',
                                $whatsapp_message,
                                $verifier_id,
                                'WhatsApp',
                                $clean_mobile,
                                'pending' // Will be updated when user clicks the link
                            );
                            
                            $encoded_message = urlencode($whatsapp_message);
                            $whatsapp_url = "https://wa.me/91{$clean_mobile}?text={$encoded_message}";
                            $whatsapp_sent = true;
                            $message .= ' WhatsApp link generated.';
                        } else {
                            $whatsapp_sent = false;
                            $message .= ' (Invalid mobile number for WhatsApp)';
                        }
                    }
                }
            }
        }
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($send_whatsapp && $verifier_id > 0) {
            $response['whatsapp_sent'] = $whatsapp_sent;
            if (isset($whatsapp_url) && !empty($whatsapp_url)) {
                $response['whatsapp_url'] = $whatsapp_url;
            }
            if (!empty($whatsapp_message)) {
                $response['whatsapp_message'] = $whatsapp_message;
            }
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to assign task: ' . ($result['message'] ?? 'Unknown error')
        ]);
    }
    exit;
    
} elseif ($action == 'get_verifiers') {
    // Get list of active FIELD VERIFIER users from op_user
    global $con;
    $sql = "
        SELECT id, full_name, user_mobile, user_email, user_type
        FROM op_user
        WHERE user_type = 'FIELD VERIFIER' AND status = 'ACTIVE'
        ORDER BY full_name ASC
    ";
    
    $res = mysqli_query($con, $sql);
    $verifiers = [];
    
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $verifiers[] = [
                'id' => intval($row['id']),
                'name' => htmlspecialchars($row['full_name'] ?? ''),
                'mobile' => htmlspecialchars($row['user_mobile'] ?? ''),
                'email' => htmlspecialchars($row['user_email'] ?? ''),
                'type' => htmlspecialchars($row['user_type'] ?? '')
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'verifiers' => $verifiers
    ]);
    exit;
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

