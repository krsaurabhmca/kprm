<?php
/**
 * KPRM - Save Report Template
 * Handles creating, updating, and deleting report templates
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

require_once('../system/op_lib.php');
require_once('../function.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if table exists
global $con;
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
$table_exists = ($table_check && mysqli_num_rows($table_check) > 0);

if (!$table_exists) {
    echo json_encode(['success' => false, 'message' => 'Database tables not found. Please run: SOURCE db/create_report_templates_table.sql;']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_FILES['file']) ? 'upload_logo_stamp' : '');

if ($action == 'create' || $action == 'update') {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $template_name = isset($_POST['template_name']) ? trim($_POST['template_name']) : '';
    $template_type = isset($_POST['template_type']) ? trim($_POST['template_type']) : 'STANDARD';
    $task_type = isset($_POST['task_type']) ? trim($_POST['task_type']) : null;
    $template_html = isset($_POST['template_html']) ? $_POST['template_html'] : '';
    $template_css = isset($_POST['template_css']) ? $_POST['template_css'] : '';
    $is_default = isset($_POST['is_default']) ? $_POST['is_default'] : 'NO';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($client_id) || empty($template_name) || empty($template_html)) {
        echo json_encode(['success' => false, 'message' => 'Client, template name, and HTML are required']);
        exit;
    }
    
    $data = [
        'client_id' => $client_id,
        'template_name' => $template_name,
        'template_type' => $template_type,
        'task_type' => $task_type,
        'template_html' => $template_html,
        'template_css' => $template_css,
        'is_default' => $is_default,
        'description' => $description,
        'status' => 'ACTIVE'
    ];
    
    if ($action == 'create') {
        $data['created_by'] = $_SESSION['user_id'];
        $result = insert_data('report_templates', $data);
        
        if ($result['status'] == 'success') {
            // If set as default, unset other defaults for this client
            if ($is_default == 'YES') {
                global $con;
                mysqli_query($con, "UPDATE report_templates SET is_default = 'NO' WHERE client_id = '$client_id' AND id != '{$result['id']}'");
            }
            
            echo json_encode(['success' => true, 'message' => 'Template created successfully', 'id' => $result['id']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create template: ' . $result['msg']]);
        }
    } else {
        $data['updated_by'] = $_SESSION['user_id'];
        // Fix: update_data signature is (table_name, ArrayData, id, pkey)
        $result = update_data('report_templates', $data, $template_id);
        
        if ($result && isset($result['status']) && $result['status'] == 'success') {
            // If set as default, unset other defaults for this client
            if ($is_default == 'YES') {
                global $con;
                mysqli_query($con, "UPDATE report_templates SET is_default = 'NO' WHERE client_id = '$client_id' AND id != '$template_id'");
            }
            
            echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
        } else {
            $error_msg = isset($result['msg']) ? $result['msg'] : (isset($result['message']) ? $result['message'] : 'Unknown error');
            error_log("Update template error: " . print_r($result, true));
            echo json_encode(['success' => false, 'message' => 'Failed to update template: ' . $error_msg]);
        }
    }
}
elseif ($action == 'update_html') {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $template_html = isset($_POST['template_html']) ? $_POST['template_html'] : '';
    
    if (!$template_id || empty($template_html)) {
        echo json_encode(['success' => false, 'message' => 'Template ID and HTML are required']);
        exit;
    }
    
    $data = [
        'template_html' => $template_html,
        'updated_by' => $_SESSION['user_id']
    ];
    
    $result = update_data('report_templates', $data, $template_id);
    
    if ($result && isset($result['status']) && $result['status'] == 'success') {
        echo json_encode(['success' => true, 'message' => 'Template HTML updated successfully']);
    } else {
        $error_msg = isset($result['msg']) ? $result['msg'] : (isset($result['message']) ? $result['message'] : 'Unknown error');
        error_log("Update HTML error: " . print_r($result, true));
        echo json_encode(['success' => false, 'message' => 'Failed to update template: ' . $error_msg]);
    }
}
elseif ($action == 'delete') {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    
    if (!$template_id) {
        echo json_encode(['success' => false, 'message' => 'Template ID is required']);
        exit;
    }
    
    $data = [
        'status' => 'INACTIVE',
        'updated_by' => $_SESSION['user_id']
    ];
    
    $result = update_data('report_templates', $data, $template_id);
    
    if ($result && isset($result['status']) && $result['status'] == 'success') {
        echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
    } else {
        $error_msg = isset($result['msg']) ? $result['msg'] : (isset($result['message']) ? $result['message'] : 'Unknown error');
        error_log("Delete template error: " . print_r($result, true));
        echo json_encode(['success' => false, 'message' => 'Failed to delete template: ' . $error_msg]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

