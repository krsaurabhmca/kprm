<?php
/**
 * KPRM - Get Task Fields HTML (AJAX)
 * Returns HTML form fields for a specific task
 * Only shows fields where by_client = 'YES'
 * Version: 3.2 - Error Handling Version
 */

// Start output buffering to catch any errors
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: text/html; charset=utf-8', true, 500);
        echo '<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> 
            <strong>Fatal Error:</strong> ' . htmlspecialchars($error['message']) . '<br>
            <small>File: ' . htmlspecialchars($error['file']) . ' (Line: ' . $error['line'] . ')</small>
        </div>';
        exit;
    }
});

try {
    require_once('../system/op_lib.php');
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8', true, 500);
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> 
        <strong>Error loading op_lib.php:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>';
    exit;
}

try {
    require_once('../function.php');
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8', true, 500);
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> 
        <strong>Error loading function.php:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>';
    exit;
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Debug mode - set to false in production
$debug_mode = true;

// Check authentication
if (!isset($_SESSION['user_id'])) {
    ob_end_flush();
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> Unauthorized access. Please login.
    </div>';
    if ($debug_mode) {
        echo '<!-- DEBUG: No session user_id -->';
    }
    exit;
}

// Get and validate task_id
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;

if ($debug_mode) {
    error_log("get_task_fields.php - task_id from GET: " . (isset($_GET['task_id']) ? $_GET['task_id'] : 'NOT SET'));
    error_log("get_task_fields.php - task_id after intval: " . $task_id);
}

if (!$task_id || $task_id <= 0) {
    ob_end_flush();
    echo '<div class="alert alert-warning">
        <i class="fas fa-info-circle"></i> Please select a valid task. (Task ID: ' . $task_id . ')
    </div>';
    if ($debug_mode) {
        echo '<!-- DEBUG: Invalid task_id: ' . $task_id . ' -->';
    }
    exit;
}

// Verify task exists
try {
    $task_check = get_data('tasks', $task_id);
} catch (Exception $e) {
    ob_end_clean();
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> 
        <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
    </div>';
    exit;
}

if ($debug_mode) {
    error_log("get_task_fields.php - Task check result count: " . $task_check['count']);
}

if ($task_check['count'] == 0) {
    ob_end_flush();
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> Task not found (ID: ' . $task_id . ').
    </div>';
    if ($debug_mode) {
        echo '<!-- DEBUG: Task not found in database -->';
    }
    exit;
}

$task_info = $task_check['data'];
$task_name = htmlspecialchars($task_info['task_name'] ?? 'Unknown Task');

if ($debug_mode) {
    error_log("get_task_fields.php - Task found: " . $task_name);
}

// Get task meta fields where by_client = YES
global $con;

if (!isset($con) || !$con) {
    ob_end_clean();
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> Database connection not available.
    </div>';
    exit;
}

// Sanitize task_id for SQL
$task_id_escaped = intval($task_id);

$sql = "
    SELECT 
        field_name, 
        display_name, 
        input_type, 
        default_value, 
        is_required
    FROM tasks_meta
    WHERE task_id = '$task_id_escaped'
      AND status = 'ACTIVE'
      AND by_client = 'YES'
    ORDER BY id ASC
";

if ($debug_mode) {
    error_log("get_task_fields.php - SQL Query: " . $sql);
}

$res = mysqli_query($con, $sql);

if (!$res) {
    $error_msg = mysqli_error($con);
    ob_end_clean();
    echo '<div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> Database error: ' . htmlspecialchars($error_msg) . '<br>
        <small>SQL: ' . htmlspecialchars($sql) . '</small>
    </div>';
    if ($debug_mode) {
        error_log("get_task_fields.php - SQL Error: " . $error_msg);
    }
    exit;
}

$row_count = mysqli_num_rows($res);
if ($debug_mode) {
    error_log("get_task_fields.php - Rows found: " . $row_count);
}

if ($row_count == 0) {
    ob_end_flush();
    echo '<div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        <strong>No client fields configured for this task.</strong><br>
        Task: ' . $task_name . ' (ID: ' . $task_id . ')<br>
        <small>Fields will be auto-generated when you save the task with report formats containing #variables#.</small>
    </div>';
    if ($debug_mode) {
        echo '<!-- DEBUG: No rows found for task_id: ' . $task_id . ' -->';
        // Let's also check if there are any fields at all for this task
        $check_all_sql = "SELECT COUNT(*) as total FROM tasks_meta WHERE task_id = '$task_id_escaped' AND status = 'ACTIVE'";
        $check_res = mysqli_query($con, $check_all_sql);
        if ($check_res) {
            $check_row = mysqli_fetch_assoc($check_res);
            echo '<!-- DEBUG: Total fields for this task (all types): ' . $check_row['total'] . ' -->';
        }
        $check_by_client_sql = "SELECT COUNT(*) as total FROM tasks_meta WHERE task_id = '$task_id_escaped' AND status = 'ACTIVE' AND by_client = 'YES'";
        $check_by_client_res = mysqli_query($con, $check_by_client_sql);
        if ($check_by_client_res) {
            $check_by_client_row = mysqli_fetch_assoc($check_by_client_res);
            echo '<!-- DEBUG: Fields with by_client=YES: ' . $check_by_client_row['total'] . ' -->';
        }
    }
    exit;
}

// Start form fields container
ob_end_flush();
echo '<div class="row">';
echo '<div class="col-12 mb-3">';
echo '<h6 class="text-muted"><i class="fas fa-list"></i> Task: ' . $task_name . '</h6>';
echo '<hr>';
echo '</div>';
echo '</div>';

echo '<div class="row">';

$field_count = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $field_count++;
    $name = htmlspecialchars($row['field_name']);
    $label = htmlspecialchars($row['display_name']);
    $type = strtoupper(trim($row['input_type']));
    $value = htmlspecialchars($row['default_value'] ?? '');
    $required = (strtoupper($row['is_required'] ?? 'NO') == 'YES') ? 'required' : '';
    
    if ($debug_mode && $field_count == 1) {
        error_log("get_task_fields.php - Processing first field: " . $name);
    }
    
    // Determine column width based on field type
    $col_class = 'col-md-6';
    if ($type == 'TEXTAREA') {
        $col_class = 'col-md-12';
    }
    
    echo '<div class="' . $col_class . ' mb-3">';
    echo '<label class="form-label"><strong>' . $label . '</strong>';
    if ($required) {
        echo ' <span class="text-danger">*</span>';
    }
    echo '</label>';
    
    switch ($type) {
        case 'DATE':
            echo '<input type="date" name="task_meta[' . $name . ']" class="form-control" value="' . $value . '" ' . $required . '>';
            break;
            
        case 'NUMBER':
            echo '<input type="number" name="task_meta[' . $name . ']" class="form-control" value="' . $value . '" step="any" ' . $required . '>';
            break;
            
        case 'SELECT':
            echo '<select name="task_meta[' . $name . ']" class="form-select" ' . $required . '>';
            echo '<option value="">Select ' . $label . '</option>';
            echo '</select>';
            break;
            
        case 'TEXTAREA':
            echo '<textarea name="task_meta[' . $name . ']" class="form-control" rows="3" ' . $required . '>' . $value . '</textarea>';
            break;
            
        case 'TEXT':
        default:
            echo '<input type="text" name="task_meta[' . $name . ']" class="form-control" value="' . $value . '" ' . $required . '>';
            break;
    }
    
    echo '</div>';
}

echo '</div>';

// Show summary
if ($field_count > 0) {
    echo '<div class="row mt-2">';
    echo '<div class="col-12">';
    echo '<small class="text-muted"><i class="fas fa-info-circle"></i> ' . $field_count . ' field(s) found for this task.</small>';
    echo '</div>';
    echo '</div>';
} else {
    if ($debug_mode) {
        echo '<!-- DEBUG: field_count is 0 after processing rows -->';
    }
}

if ($debug_mode) {
    error_log("get_task_fields.php - Completed. Field count: " . $field_count);
    echo '<!-- DEBUG: Script completed successfully. Field count: ' . $field_count . ' -->';
}
