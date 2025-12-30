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
    
    if (!$case_task_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    // If verifier_id is 0, unassign the task
    $update_data = [
        'assigned_to' => $verifier_id > 0 ? $verifier_id : null,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    // If assigning (not unassigning), set task status to IN_PROGRESS
    if ($verifier_id > 0) {
        // Get current task status
        $current_task = get_data('case_tasks', $case_task_id);
        if ($current_task['count'] > 0) {
            $current_status = $current_task['data']['task_status'] ?? 'PENDING';
            // Update to IN_PROGRESS if currently PENDING or if reassigning
            if ($current_status == 'PENDING' || $current_status == 'IN_PROGRESS') {
                $update_data['task_status'] = 'IN_PROGRESS';
            }
        }
    } else {
        // If unassigning, set status back to PENDING
        $update_data['task_status'] = 'PENDING';
    }
    
    $result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($result['status'] == 'success') {
        $message = $verifier_id > 0 ? 'Task assigned successfully! Status updated to IN_PROGRESS.' : 'Task unassigned successfully!';
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to assign task: ' . ($result['message'] ?? 'Unknown error')
        ]);
    }
    exit;
    
} elseif ($action == 'get_verifiers') {
    // Get list of active verifiers
    global $con;
    $sql = "
        SELECT id, verifier_name, verifier_mobile, verifier_type
        FROM verifier
        WHERE status = 'ACTIVE'
        ORDER BY verifier_name ASC
    ";
    
    $res = mysqli_query($con, $sql);
    $verifiers = [];
    
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $verifiers[] = [
                'id' => intval($row['id']),
                'name' => htmlspecialchars($row['verifier_name']),
                'mobile' => htmlspecialchars($row['verifier_mobile'] ?? ''),
                'type' => htmlspecialchars($row['verifier_type'] ?? '')
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

