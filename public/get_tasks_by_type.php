<?php
/**
 * KPRM - Get Tasks by Type (AJAX)
 * Returns list of tasks filtered by task type
 * Version: 3.0
 */

require_once('../system/op_lib.php');
require_once('../function.php');

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login.',
        'tasks' => []
    ]);
    exit;
}

// Get and validate task type
$task_type = isset($_GET['task_type']) ? trim($_GET['task_type']) : '';

if (empty($task_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'Task type is required',
        'tasks' => []
    ]);
    exit;
}

// Validate task type (must be one of the allowed values)
$allowed_types = ['PHYSICAL', 'ITO', 'BANKING'];
if (!in_array(strtoupper($task_type), $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid task type. Allowed types: PHYSICAL, ITO, BANKING',
        'tasks' => []
    ]);
    exit;
}

// Normalize task type to uppercase
$task_type = strtoupper($task_type);

try {
    // Get tasks from database
    $tasks = get_all('tasks', ['id', 'task_name', 'task_type'], [
        'task_type' => $task_type,
        'status' => 'ACTIVE'
    ], 'task_name ASC');
    
    // Prepare response
    if ($tasks['count'] > 0 && !empty($tasks['data'])) {
        // Format response data
        $formatted_tasks = [];
        foreach ($tasks['data'] as $task) {
            $formatted_tasks[] = [
                'id' => intval($task['id']),
                'task_name' => htmlspecialchars($task['task_name']),
                'task_type' => htmlspecialchars($task['task_type'] ?? $task_type)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tasks loaded successfully',
            'task_type' => $task_type,
            'count' => count($formatted_tasks),
            'tasks' => $formatted_tasks
        ]);
    } else {
        // No tasks found, but still success
        echo json_encode([
            'success' => true,
            'message' => 'No tasks found for this type',
            'task_type' => $task_type,
            'count' => 0,
            'tasks' => []
        ]);
    }
    
} catch (Exception $e) {
    // Handle any errors
    echo json_encode([
        'success' => false,
        'message' => 'Error loading tasks: ' . $e->getMessage(),
        'task_type' => $task_type,
        'count' => 0,
        'tasks' => []
    ]);
    exit;
}
