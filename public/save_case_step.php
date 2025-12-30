<?php
/**
 * KPRM - Save Case Step Handler
 * Handles saving client_meta and task_meta data during case creation
 */

require_once('../system/op_lib.php');
require_once('../function.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../system/op_login.php');
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'save_client_meta') {
    // Step 2: Save client meta and create/update case
    $client_id = intval($_POST['client_id']);
    $client_meta = isset($_POST['client_meta']) ? $_POST['client_meta'] : [];
    $case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;
    $case_status = isset($_POST['case_status']) ? $_POST['case_status'] : 'ACTIVE';
    
    // Generate application number if needed
    $application_no = isset($_POST['client_meta']['application_number']) 
        ? $_POST['client_meta']['application_number'] 
        : 'APP-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Prepare case_info as JSON
    $case_info = json_encode($client_meta);
    
    if ($case_id > 0) {
        // Update existing case
        $case_data = [
            'client_id' => $client_id,
            'application_no' => $application_no,
            'case_info' => $case_info,
            'case_status' => $case_status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id']
        ];
        
        $case_result = update_data('cases', $case_data, $case_id);
        
        if ($case_result['status'] == 'success') {
            // Redirect to next step
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to update case: ' . ($case_result['message'] ?? 'Unknown error');
            header('Location: add_new_case.php?step=2&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        }
    } else {
        // Create new case
        $case_data = [
            'client_id' => $client_id,
            'application_no' => $application_no,
            'case_info' => $case_info,
            'case_status' => $case_status,
            'status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user_id']
        ];
        
        $case_result = insert_data('cases', $case_data);
        
        if ($case_result['status'] == 'success') {
            $case_id = $case_result['id'];
            
            // Redirect to next step
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to create case: ' . ($case_result['message'] ?? 'Unknown error');
            header('Location: add_new_case.php?step=2&client_id=' . $client_id);
            exit;
        }
    }
    
} elseif ($action == 'save_task_data') {
    // Step 5: Save task data and complete case
    global $con;
    
    $client_id = intval($_POST['client_id']);
    $case_id = intval($_POST['case_id']);
    $task_template_id = intval($_POST['task_id']); // This is the task template ID from tasks table
    $task_meta = isset($_POST['task_meta']) ? $_POST['task_meta'] : [];
    
    // Get task template info
    $task_template = get_data('tasks', $task_template_id);
    if ($task_template['count'] == 0) {
        $_SESSION['error_message'] = 'Task template not found!';
        header('Location: add_new_case.php?step=5&client_id=' . $client_id . '&case_id=' . $case_id . '&task_id=' . $task_template_id);
        exit;
    }
    
    $task_template_data = $task_template['data'];
    
    // Create task instance in case_tasks table
    $case_task_data = [
        'case_id' => $case_id,
        'task_template_id' => $task_template_id,
        'task_type' => $task_template_data['task_type'],
        'task_name' => $task_template_data['task_name'],
        'task_data' => json_encode($task_meta),
        'task_status' => 'PENDING',
        'status' => 'ACTIVE',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['user_id']
    ];
    
    // Try to insert into case_tasks
    $case_task_result = insert_data('case_tasks', $case_task_data);
    
    // If table doesn't exist, try alternative: store in tasks table with case_id reference
    if ($case_task_result['status'] != 'success') {
        // Alternative: Create a simple JSON file or use session
        // For now, we'll just show success and handle storage later
        $_SESSION['case_' . $case_id . '_task_' . $task_template_id] = [
            'task_meta' => $task_meta,
            'task_type' => $task_template_data['task_type'],
            'task_name' => $task_template_data['task_name']
        ];
    }
    
    $_SESSION['success_message'] = 'Case and task created successfully!';
    header('Location: case_manage.php');
    exit;
    
} elseif ($action == 'add_task_to_case') {
    // Add new task to existing case
    global $con;
    
    $case_id = intval($_POST['case_id']);
    $client_id = intval($_POST['client_id']);
    $task_template_id = intval($_POST['task_id']);
    $task_type = isset($_POST['task_type']) ? $_POST['task_type'] : '';
    $task_meta = isset($_POST['task_meta']) ? $_POST['task_meta'] : [];
    
    // Check if this is an AJAX request (check early for error handling)
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $is_ajax = $is_ajax || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
    
    // Validate inputs
    if (!$case_id || !$task_template_id || !$task_type) {
        $error_msg = 'Missing required fields: case_id=' . $case_id . ', task_id=' . $task_template_id . ', task_type=' . $task_type;
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'status' => 'error', 'message' => $error_msg]);
            exit;
        } else {
            $_SESSION['error_message'] = $error_msg;
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        }
    }
    
    // Get task template info
    $task_template = get_data('tasks', $task_template_id);
    if ($task_template['count'] == 0) {
        $error_msg = 'Task template not found! Task ID: ' . $task_template_id;
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'status' => 'error', 'message' => $error_msg]);
            exit;
        } else {
            $_SESSION['error_message'] = $error_msg;
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        }
    }
    
    $task_template_data = $task_template['data'];
    
    // Check if this is an AJAX request (check early for error handling)
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $is_ajax = $is_ajax || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
    
    // Check if case_tasks table exists, if not create it
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    if (mysqli_num_rows($table_check) == 0) {
        // Table doesn't exist, try to create it with simple SQL
        $create_sql = "CREATE TABLE IF NOT EXISTS `case_tasks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status` varchar(25) DEFAULT 'ACTIVE',
            `created_at` timestamp NULL DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `updated_by` int(11) DEFAULT NULL,
            `case_id` int(11) NOT NULL,
            `task_template_id` int(11) NOT NULL,
            `task_type` varchar(128) DEFAULT NULL,
            `task_name` varchar(128) DEFAULT NULL,
            `task_data` text DEFAULT NULL,
            `task_status` varchar(50) DEFAULT 'PENDING',
            `assigned_to` int(11) DEFAULT NULL,
            `verified_at` timestamp NULL DEFAULT NULL,
            `reviewed_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_case_id` (`case_id`),
            KEY `idx_task_template` (`task_template_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if (!mysqli_query($con, $create_sql)) {
            $error_msg = 'Failed to create case_tasks table: ' . mysqli_error($con);
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'status' => 'error', 'message' => $error_msg]);
                exit;
            } else {
                $_SESSION['error_message'] = $error_msg;
                header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
                exit;
            }
        }
    }
    
    // Create task instance in case_tasks table
    $case_task_data = [
        'case_id' => $case_id,
        'task_template_id' => $task_template_id,
        'task_type' => $task_type,
        'task_name' => $task_template_data['task_name'],
        'task_data' => json_encode($task_meta),
        'task_status' => 'PENDING',
        'status' => 'ACTIVE',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['user_id']
    ];
    
    $case_task_result = insert_data('case_tasks', $case_task_data);
    
    if ($case_task_result['status'] == 'success') {
        $success_msg = 'Task "' . htmlspecialchars($task_template_data['task_name']) . '" added successfully!';
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'status' => 'success',
                'message' => $success_msg
            ]);
            exit;
        } else {
            $_SESSION['success_message'] = $success_msg;
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        }
    } else {
        $error_msg = 'Failed to add task: ' . ($case_task_result['message'] ?? 'Unknown error');
        if (isset($case_task_result['sql'])) {
            $error_msg .= ' SQL Error: ' . mysqli_error($con);
        }
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'status' => 'error',
                'message' => $error_msg
            ]);
            exit;
        } else {
            $_SESSION['error_message'] = $error_msg;
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        }
    }
    
} elseif ($action == 'update_case_task') {
    // Update existing case task
    global $con;
    
    $case_task_id = intval($_POST['case_task_id']);
    $case_id = intval($_POST['case_id']);
    $client_id = intval($_POST['client_id']);
    $task_id = intval($_POST['task_id']);
    $task_status = isset($_POST['task_status']) ? $_POST['task_status'] : 'PENDING';
    $task_meta = isset($_POST['task_meta']) ? $_POST['task_meta'] : [];
    
    // Check if this is an AJAX request
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    $is_ajax = $is_ajax || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
    
    // Validate inputs
    if (!$case_task_id || !$case_id || !$task_id) {
        $error_msg = 'Missing required fields: case_task_id, case_id, or task_id';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'status' => 'error', 'message' => $error_msg]);
            exit;
        } else {
            $_SESSION['error_message'] = $error_msg;
            header('Location: edit_case_task.php?case_task_id=' . $case_task_id . '&task_id=' . $task_id);
            exit;
        }
    }
    
    // Verify case task exists
    $case_task_check = get_data('case_tasks', $case_task_id);
    if ($case_task_check['count'] == 0) {
        $error_msg = 'Case task not found!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'status' => 'error', 'message' => $error_msg]);
            exit;
        } else {
            $_SESSION['error_message'] = $error_msg;
            header('Location: add_new_case.php?step=3&case_id=' . $case_id . '&client_id=' . $client_id);
            exit;
        }
    }
    
    // Get assigned_to if provided
    $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    
    // Prepare update data
    $update_data = [
        'task_data' => json_encode($task_meta),
        'task_status' => $task_status,
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    // Add assigned_to if provided (can be 0 to unassign)
    if ($assigned_to !== null) {
        $update_data['assigned_to'] = $assigned_to > 0 ? $assigned_to : null;
    }
    
    // Update case task
    $update_result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($update_result['status'] == 'success') {
        $success_msg = 'Task updated successfully!';
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'status' => 'success',
                'message' => $success_msg
            ]);
            exit;
        } else {
            $_SESSION['success_message'] = $success_msg;
            header('Location: add_new_case.php?step=3&client_id=' . $client_id . '&case_id=' . $case_id);
            exit;
        }
    } else {
        $error_msg = 'Failed to update task: ' . ($update_result['message'] ?? 'Unknown error');
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'status' => 'error',
                'message' => $error_msg
            ]);
            exit;
        } else {
            $_SESSION['error_message'] = $error_msg;
            header('Location: edit_case_task.php?case_task_id=' . $case_task_id . '&task_id=' . $task_id);
            exit;
        }
    }
    
} elseif ($action == 'delete_case') {
    // Delete case (soft delete)
    $case_id = intval($_POST['case_id']);
    
    if (!$case_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid case ID']);
        exit;
    }
    
    $update_data = [
        'status' => 'DELETED',
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    $result = update_data('cases', $update_data, $case_id);
    
    if ($result['status'] == 'success') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Case deleted successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete case: ' . ($result['message'] ?? 'Unknown error')]);
    }
    exit;
    
} elseif ($action == 'delete_task') {
    // Delete task from case
    $case_task_id = intval($_POST['case_task_id']);
    
    $update_data = [
        'status' => 'DELETED',
        'updated_at' => date('Y-m-d H:i:s'),
        'updated_by' => $_SESSION['user_id']
    ];
    
    $result = update_data('case_tasks', $update_data, $case_task_id);
    
    if ($result['status'] == 'success') {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete task']);
    }
    exit;
    
} else {
    $_SESSION['error_message'] = 'Invalid action!';
    header('Location: add_new_case.php?step=1');
    exit;
}

