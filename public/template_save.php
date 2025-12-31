<?php
/**
 * KPRM - Save Template (Simple)
 * Handles saving HTML templates
 */
require_once('../system/op_lib.php');
require_once('../function.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

global $con;

// Check if table exists
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
$table_exists = ($table_check && mysqli_num_rows($table_check) > 0);

if (!$table_exists) {
    echo json_encode(['success' => false, 'message' => 'Database table not found']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'create' || $action == 'update') {
    $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
    $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
    $template_name = isset($_POST['template_name']) ? trim($_POST['template_name']) : '';
    $template_type = isset($_POST['template_type']) ? trim($_POST['template_type']) : 'STANDARD';
    $template_html = isset($_POST['template_html']) ? $_POST['template_html'] : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    if (empty($client_id) || empty($template_name) || empty($template_html)) {
        echo json_encode(['success' => false, 'message' => 'Client, template name, and HTML are required']);
        exit;
    }
    
    $data = [
        'client_id' => $client_id,
        'template_name' => $template_name,
        'template_type' => $template_type,
        'template_html' => $template_html,
        'template_css' => '',
        'description' => $description,
        'status' => 'ACTIVE'
    ];
    
    if ($action == 'create') {
        $data['created_by'] = $_SESSION['user_id'];
        $result = insert_data('report_templates', $data);
        
        if ($result['status'] == 'success') {
            echo json_encode(['success' => true, 'message' => 'Template saved successfully', 'id' => $result['id']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save template: ' . $result['msg']]);
        }
    } else {
        $data['updated_by'] = $_SESSION['user_id'];
        $result = update_data('report_templates', $data, $template_id);
        
        if ($result && isset($result['status']) && $result['status'] == 'success') {
            echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update template']);
        }
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
        echo json_encode(['success' => false, 'message' => 'Failed to delete template']);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

