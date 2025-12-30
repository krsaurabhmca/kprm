<?php
/**
 * KPRM - Edit Case Task
 * Edit an existing task that's been added to a case
 */

require_once('../system/all_header.php');

// Get parameters
$case_task_id = isset($_GET['case_task_id']) ? intval($_GET['case_task_id']) : 0;
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;

if (!$case_task_id || !$task_id) {
    $_SESSION['error_message'] = 'Invalid parameters. Missing case_task_id or task_id.';
    header('Location: add_new_case.php');
    exit;
}

// Get case task data
$case_task = get_data('case_tasks', $case_task_id);
if ($case_task['count'] == 0) {
    $_SESSION['error_message'] = 'Case task not found.';
    header('Location: add_new_case.php');
    exit;
}

$case_task_data = $case_task['data'];
$case_id = $case_task_data['case_id'];

// Get task template
$task_template = get_data('tasks', $task_id);
if ($task_template['count'] == 0) {
    $_SESSION['error_message'] = 'Task template not found.';
    header('Location: add_new_case.php?step=3&case_id=' . $case_id);
    exit;
}

$task_template_data = $task_template['data'];
$task_name = $task_template_data['task_name'];
$task_type = $task_template_data['task_type'];

// Parse existing task data
$existing_task_data = json_decode($case_task_data['task_data'] ?? '{}', true);
if (!is_array($existing_task_data)) {
    $existing_task_data = [];
}

// Get case info
$case_info = get_data('cases', $case_id);
$case_data = $case_info['count'] > 0 ? $case_info['data'] : null;

// Get client info
$client_id = $case_data ? $case_data['client_id'] : 0;
$client_info = $client_id > 0 ? get_data('clients', $client_id) : ['count' => 0];
$client_name = $client_info['count'] > 0 ? $client_info['data']['client_name'] : 'Unknown Client';

// Display messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-check-circle"></i> ' . $_SESSION['success_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-edit"></i> Edit Task: <?php echo htmlspecialchars($task_name); ?>
                        </h4>
                        <div class="card-tools">
                            <?php
                            // Workflow-based button display
                            $current_status = $case_task_data['task_status'] ?? 'PENDING';
                            
                            // PENDING: Show Assign only
                            if ($current_status == 'PENDING'):
                            ?>
                                <button type="button" class="btn btn-success btn-sm" onclick="window.location.href='add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>'">
                                    <i class="fas fa-user-plus"></i> Assign Task
                                </button>
                            <?php
                            // IN_PROGRESS (Assigned): Show Reassign and Verify
                            elseif ($current_status == 'IN_PROGRESS'):
                            ?>
                                <?php if (!empty($case_task_data['assigned_to'])): ?>
                                    <a href="task_verifier_submit.php?case_task_id=<?php echo $case_task_id; ?>" class="btn btn-info btn-sm" title="Verify Task">
                                        <i class="fas fa-check-circle"></i> Verify
                                    </a>
                                <?php endif; ?>
                            <?php
                            // VERIFICATION_COMPLETED: Show Review only
                            elseif ($current_status == 'VERIFICATION_COMPLETED'):
                            ?>
                                <a href="task_review.php?case_task_id=<?php echo $case_task_id; ?>" class="btn btn-warning btn-sm" title="Review Task">
                                    <i class="fas fa-clipboard-check"></i> Review
                                </a>
                            <?php
                            // COMPLETED: Show completed badge
                            elseif ($current_status == 'COMPLETED'):
                            ?>
                                <span class="badge bg-success">Task Completed</span>
                            <?php endif; ?>
                            
                            <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Case Tasks
                            </a>
                        </div>
                    </div>
                <div class="card-body">
                    <!-- Case & Client Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Case ID:</strong> <?php echo $case_id; ?><br>
                            <strong>Client:</strong> <?php echo htmlspecialchars($client_name); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Task Type:</strong> <?php echo htmlspecialchars($task_type); ?><br>
                            <strong>Task Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $case_task_data['task_status'] == 'COMPLETED' ? 'success' : 
                                    ($case_task_data['task_status'] == 'IN_PROGRESS' ? 'info' : 
                                    ($case_task_data['task_status'] == 'REJECTED' ? 'danger' : 'warning')); 
                            ?>">
                                <?php echo htmlspecialchars($case_task_data['task_status'] ?? 'PENDING'); ?>
                            </span>
                        </div>
                    </div>

                    <hr>

                    <!-- Edit Form -->
                    <form id="editTaskForm" method="POST" action="save_case_step.php">
                        <input type="hidden" name="action" value="update_case_task">
                        <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
                        <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                        <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                        
                        <div id="formError" class="alert alert-danger" style="display:none;"></div>
                        <div id="formSuccess" class="alert alert-success" style="display:none;"></div>

                        <!-- Task Status and Assignment -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Task Status</strong></label>
                                <select name="task_status" class="form-select">
                                    <option value="PENDING" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'PENDING' ? 'selected' : ''; ?>>PENDING</option>
                                    <option value="IN_PROGRESS" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'IN_PROGRESS' ? 'selected' : ''; ?>>IN_PROGRESS</option>
                                    <option value="COMPLETED" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'COMPLETED' ? 'selected' : ''; ?>>COMPLETED</option>
                                    <option value="REJECTED" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'REJECTED' ? 'selected' : ''; ?>>REJECTED</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Assign To Verifier</strong></label>
                                <select name="assigned_to" class="form-select">
                                    <option value="">-- Not Assigned --</option>
                                    <?php
                                    // Get all active verifiers
                                    global $con;
                                    $verifier_sql = "
                                        SELECT id, verifier_name, verifier_mobile, verifier_type
                                        FROM verifier
                                        WHERE status = 'ACTIVE'
                                        ORDER BY verifier_name ASC
                                    ";
                                    $verifier_res = mysqli_query($con, $verifier_sql);
                                    if ($verifier_res && mysqli_num_rows($verifier_res) > 0) {
                                        while ($verifier = mysqli_fetch_assoc($verifier_res)) {
                                            $selected = ($case_task_data['assigned_to'] ?? 0) == $verifier['id'] ? 'selected' : '';
                                            $display_name = htmlspecialchars($verifier['verifier_name']);
                                            if (!empty($verifier['verifier_mobile'])) {
                                                $display_name .= ' (' . htmlspecialchars($verifier['verifier_mobile']) . ')';
                                            }
                                            echo '<option value="' . $verifier['id'] . '" ' . $selected . '>' . $display_name . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Task Meta Fields -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-list"></i> Task Fields</h5>
                            </div>
                        </div>

                        <div id="taskFieldsContainer">
                            <?php
                            // Get all task meta fields for this task
                            global $con;
                            $sql = "
                                SELECT 
                                    field_name, 
                                    display_name, 
                                    input_type, 
                                    default_value, 
                                    is_required,
                                    by_client,
                                    by_verifier,
                                    by_findings
                                FROM tasks_meta
                                WHERE task_id = '$task_id'
                                  AND status = 'ACTIVE'
                                ORDER BY id ASC
                            ";

                            $res = mysqli_query($con, $sql);

                            if ($res && mysqli_num_rows($res) > 0) {
                                echo '<div class="row">';
                                while ($row = mysqli_fetch_assoc($res)) {
                                    $name = htmlspecialchars($row['field_name']);
                                    $label = htmlspecialchars($row['display_name']);
                                    $type = strtoupper(trim($row['input_type']));
                                    $default_value = htmlspecialchars($row['default_value'] ?? '');
                                    $is_required = (strtoupper($row['is_required'] ?? 'NO') == 'YES');
                                    
                                    // Get existing value
                                    $existing_value = isset($existing_task_data[$row['field_name']]) 
                                        ? htmlspecialchars($existing_task_data[$row['field_name']]) 
                                        : $default_value;
                                    
                                    // Determine column width
                                    $col_class = 'col-md-6';
                                    if ($type == 'TEXTAREA') {
                                        $col_class = 'col-md-12';
                                    }
                                    
                                    echo '<div class="' . $col_class . ' mb-3">';
                                    echo '<label class="form-label"><strong>' . $label . '</strong>';
                                    if ($is_required) {
                                        echo ' <span class="text-danger">*</span>';
                                    }
                                    
                                    // Show field source badges
                                    $badges = [];
                                    if ($row['by_client'] == 'YES') $badges[] = '<span class="badge bg-primary">Client</span>';
                                    if ($row['by_verifier'] == 'YES') $badges[] = '<span class="badge bg-info">Verifier</span>';
                                    if ($row['by_findings'] == 'YES') $badges[] = '<span class="badge bg-success">Findings</span>';
                                    if (!empty($badges)) {
                                        echo ' ' . implode(' ', $badges);
                                    }
                                    
                                    echo '</label>';
                                    
                                    // Generate input field
                                    switch ($type) {
                                        case 'DATE':
                                            echo '<input type="date" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" ' . ($is_required ? 'required' : '') . '>';
                                            break;
                                            
                                        case 'NUMBER':
                                            echo '<input type="number" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" step="any" ' . ($is_required ? 'required' : '') . '>';
                                            break;
                                            
                                        case 'SELECT':
                                            echo '<select name="task_meta[' . $name . ']" class="form-select" ' . ($is_required ? 'required' : '') . '>';
                                            echo '<option value="">Select ' . $label . '</option>';
                                            // TODO: Load options from master/config if available
                                            if (!empty($existing_value)) {
                                                echo '<option value="' . $existing_value . '" selected>' . $existing_value . '</option>';
                                            }
                                            echo '</select>';
                                            break;
                                            
                                        case 'TEXTAREA':
                                            echo '<textarea name="task_meta[' . $name . ']" class="form-control" rows="3" ' . ($is_required ? 'required' : '') . '>' . $existing_value . '</textarea>';
                                            break;
                                            
                                        case 'TEXT':
                                        default:
                                            echo '<input type="text" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" ' . ($is_required ? 'required' : '') . '>';
                                            break;
                                    }
                                    
                                    echo '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info">';
                                echo '<i class="fas fa-info-circle"></i> No fields configured for this task.';
                                echo '</div>';
                            }
                            ?>
                        </div>

                        <!-- Form Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Task
                                </button>
                                <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Handle form submission with AJAX
document.getElementById('editTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var form = this;
    var formData = new FormData(form);
    var errorDiv = document.getElementById('formError');
    var successDiv = document.getElementById('formSuccess');
    
    // Hide previous messages
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    // Show loading
    var submitBtn = form.querySelector('button[type="submit"]');
    var originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Submit via AJAX
    $.ajax({
        url: 'save_case_step.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            if (response && (response.status === 'success' || response.success)) {
                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + (response.message || 'Task updated successfully!');
                successDiv.style.display = 'block';
                
                // Redirect after short delay
                setTimeout(function() {
                    window.location.href = 'add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>';
                }, 1500);
            } else {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (response.message || 'Failed to update task');
                errorDiv.style.display = 'block';
            }
        },
        error: function(xhr, status, error) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            var errorMsg = 'Error: ' + error + ' (Status: ' + xhr.status + ')';
            
            // Try to parse error response
            try {
                var errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.message) {
                    errorMsg = errorResponse.message;
                }
            } catch(e) {
                // Use default error message
            }
            
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMsg;
            errorDiv.style.display = 'block';
            console.error('AJAX Error:', xhr.responseText);
        }
    });
    
    return false;
});
</script>

<?php require_once('../system/footer.php'); ?>

