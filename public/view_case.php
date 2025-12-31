<?php
/**
 * KPRM - View Case Details
 * Display complete case information including client info and all tasks
 */

require_once('../system/all_header.php');

// Get case_id
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

if (!$case_id) {
    $_SESSION['error_message'] = 'Invalid case ID.';
    header('Location: case_manage.php');
    exit;
}

// Get case data
$case_result = get_data('cases', $case_id);
if ($case_result['count'] == 0) {
    $_SESSION['error_message'] = 'Case not found.';
    header('Location: case_manage.php');
    exit;
}

$case = $case_result['data'];
$client_id = $case['client_id'];

// Get client information
$client_info = get_data('clients', $client_id);
$client_name = $client_info['count'] > 0 ? $client_info['data']['name'] : 'Unknown Client';
$client_email = $client_info['count'] > 0 ? ($client_info['data']['email'] ?? '') : '';

// Parse case_info JSON
$case_info_data = [];
if (!empty($case['case_info'])) {
    $case_info_data = json_decode($case['case_info'], true);
    if (!is_array($case_info_data)) {
        $case_info_data = [];
    }
}

// Get all tasks for this case
global $con;
$table_exists = false;
$existing_tasks = [];

$table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
if (mysqli_num_rows($table_check) > 0) {
    $table_exists = true;
    $tasks_result = get_all('case_tasks', '*', ['case_id' => $case_id, 'status' => 'ACTIVE'], 'id ASC');
    if ($tasks_result['count'] > 0) {
        $existing_tasks = $tasks_result['data'];
    }
}

// Get template for this client (for report generation)
$template_id = null;
$template_name = null;
$table_check_template = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
if ($table_check_template && mysqli_num_rows($table_check_template) > 0) {
    // First try to get default template
    $template_query = "SELECT id, template_name FROM report_templates 
                       WHERE client_id = '$client_id' 
                       AND status = 'ACTIVE' 
                       AND is_default = 'YES' 
                       LIMIT 1";
    $template_result = mysqli_query($con, $template_query);
    if ($template_result && mysqli_num_rows($template_result) > 0) {
        $template_row = mysqli_fetch_assoc($template_result);
        $template_id = $template_row['id'];
        $template_name = $template_row['template_name'];
    } else {
        // If no default, get first active template for this client
        $template_query = "SELECT id, template_name FROM report_templates 
                           WHERE client_id = '$client_id' 
                           AND status = 'ACTIVE' 
                           LIMIT 1";
        $template_result = mysqli_query($con, $template_query);
        if ($template_result && mysqli_num_rows($template_result) > 0) {
            $template_row = mysqli_fetch_assoc($template_result);
            $template_id = $template_row['id'];
            $template_name = $template_row['template_name'];
        }
    }
}

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
            <!-- Header -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-0">
                                <i class="fas fa-folder-open"></i> Case #<?php echo $case_id; ?>
                            </h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="case_manage.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to Cases
                            </a>
                            <?php if ($template_id): ?>
                                <a href="generate_report.php?template_id=<?php echo $template_id; ?>&case_id=<?php echo $case_id; ?>" class="btn btn-success btn-sm" target="_blank" title="Generate Report using <?php echo htmlspecialchars($template_name); ?>">
                                    <i class="fas fa-file-pdf"></i> Generate Report
                                </a>
                            <?php else: ?>
                                <button class="btn btn-success btn-sm" disabled title="No template configured for this client">
                                    <i class="fas fa-file-pdf"></i> Generate Report
                                </button>
                                <small class="text-muted d-block mt-1">
                                    <a href="template_editor.php?client_id=<?php echo $client_id; ?>" class="text-decoration-none">Create Template</a>
                                </small>
                            <?php endif; ?>
                            <a href="add_new_case.php?step=2&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit Case Info
                            </a>
                            <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-tasks"></i> Manage Tasks
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column: Case & Client Info -->
                <div class="col-md-4">
                    <!-- Case Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Case Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Case ID:</strong></td>
                                    <td><?php echo $case_id; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Application No:</strong></td>
                                    <td><?php echo $case['application_no'] ?: '<span class="text-muted">N/A</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Case Status:</strong></td>
                                    <td>
                                        <?php 
                                        $case_status = $case['case_status'] ?? 'ACTIVE';
                                        $status_colors = [
                                            'ACTIVE' => 'success',
                                            'PENDING' => 'warning',
                                            'COMPLETED' => 'info',
                                            'ON_HOLD' => 'secondary'
                                        ];
                                        $status_color = $status_colors[$case_status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $status_color; ?>">
                                            <?php echo htmlspecialchars($case_status); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($case['status'] == 'ACTIVE' ? 'success' : 'secondary'); ?>">
                                            <?php echo htmlspecialchars($case['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td><?php echo $case['created_at'] ? date('d M Y, h:i A', strtotime($case['created_at'])) : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Updated:</strong></td>
                                    <td><?php echo $case['updated_at'] ? date('d M Y, h:i A', strtotime($case['updated_at'])) : 'N/A'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Client Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building"></i> Client Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Client ID:</strong></td>
                                    <td><?php echo $client_id; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Client Name:</strong></td>
                                    <td><strong><?php echo htmlspecialchars($client_name); ?></strong></td>
                                </tr>
                                <?php if ($client_email): ?>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($client_email); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Case Info (from JSON) -->
                    <?php if (!empty($case_info_data)): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list"></i> Case Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <?php
                                foreach ($case_info_data as $key => $value) {
                                    if (!empty($value)) {
                                        $display_key = ucwords(str_replace('_', ' ', $key));
                                        echo '<tr>';
                                        echo '<td><strong>' . htmlspecialchars($display_key) . ':</strong></td>';
                                        echo '<td>' . htmlspecialchars($value) . '</td>';
                                        echo '</tr>';
                                    }
                                }
                                ?>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Tasks -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tasks"></i> Tasks 
                                        <span class="badge bg-info"><?php echo count($existing_tasks); ?></span>
                                    </h5>
                                </div>
                                <div class="col-md-6 text-end">
                                    <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Add Task
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!$table_exists): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Warning:</strong> The case_tasks table does not exist. 
                                    Please run: <code>SOURCE db/create_case_tasks_table.sql;</code> in your database.
                                </div>
                            <?php elseif (empty($existing_tasks)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No tasks added to this case yet.
                                    <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="alert-link">Add your first task</a>.
                                </div>
                            <?php else: ?>
                                <!-- Tasks Accordion -->
                                <div class="accordion" id="tasksAccordion">
                                    <?php
                                    foreach ($existing_tasks as $index => $task) {
                                        $task_template = get_data('tasks', $task['task_template_id']);
                                        $task_name = $task_template['count'] > 0 ? $task_template['data']['task_name'] : 'Unknown Task';
                                        $task_type = $task_template['count'] > 0 ? $task_template['data']['task_type'] : '';
                                        $task_data = json_decode($task['task_data'] ?? '{}', true);
                                        $task_status = $task['task_status'] ?? 'PENDING';
                                        
                                        $status_badge = [
                                            'PENDING' => 'warning',
                                            'IN_PROGRESS' => 'info',
                                            'VERIFICATION_COMPLETED' => 'primary',
                                            'COMPLETED' => 'success',
                                            'REJECTED' => 'danger'
                                        ];
                                        $badge_color = $status_badge[$task_status] ?? 'secondary';
                                        ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $task['id']; ?>">
                                                <button class="accordion-button <?php echo $index == 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $task['id']; ?>">
                                                    <div class="w-100 d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="fas fa-tasks me-2"></i>
                                                            <strong><?php echo htmlspecialchars($task_name); ?></strong>
                                                            <span class="badge bg-<?php echo $badge_color; ?> ms-2"><?php echo $task_status; ?></span>
                                                            <span class="badge bg-secondary ms-2"><?php echo $task_type; ?></span>
                                                        </div>
                                                        <div>
                                                            <span class="text-muted small">Task ID: <?php echo $task['id']; ?></span>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $task['id']; ?>" class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" data-bs-parent="#tasksAccordion">
                                                <div class="accordion-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <?php
                                                            // Show assigned verifier info
                                                            if (!empty($task['assigned_to'])) {
                                                                $verifier_info = get_data('verifier', $task['assigned_to']);
                                                                if ($verifier_info['count'] > 0) {
                                                                    $verifier = $verifier_info['data'];
                                                                    ?>
                                                                    <div class="alert alert-info mb-0">
                                                                        <i class="fas fa-user-check"></i> 
                                                                        <strong>Assigned To:</strong> 
                                                                        <?php echo htmlspecialchars($verifier['verifier_name'] ?? 'Unknown'); ?>
                                                                        <?php if (!empty($verifier['verifier_mobile'])): ?>
                                                                            <br><small>Mobile: <?php echo htmlspecialchars($verifier['verifier_mobile']); ?></small>
                                                                        <?php endif; ?>
                                                                       
                                                                    </div>
                                                                    <?php
                                                                }
                                                            } else {
                                                                ?>
                                                                <div class="alert alert-warning mb-0">
                                                                    <i class="fas fa-user-times"></i> <strong>Not Assigned</strong>
                                                                </div>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="col-md-6 text-end">
                                                            <?php
                                                            // Workflow-based button display
                                                            $current_status = $task['task_status'] ?? 'PENDING';
                                                            
                                                            // PENDING: Show Assign only
                                                            if ($current_status == 'PENDING'):
                                                            ?>
                                                                <button type="button" class="btn btn-sm btn-success" onclick="assignTask(<?php echo $task['id']; ?>, 0)">
                                                                    <i class="fas fa-user-plus"></i> Assign
                                                                </button>
                                                            <?php
                                                            // IN_PROGRESS (Assigned): Show Reassign and Verify
                                                            elseif ($current_status == 'IN_PROGRESS'):
                                                            ?>
                                                                <button type="button" class="btn btn-sm btn-success" onclick="assignTask(<?php echo $task['id']; ?>, <?php echo $task['assigned_to'] ?? 0; ?>)">
                                                                    <i class="fas fa-user-edit"></i> Reassign
                                                                </button>
                                                                <?php if (!empty($task['assigned_to'])): ?>
                                                                    <a href="task_verifier_submit.php?case_task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info" title="Verify Task">
                                                                        <i class="fas fa-check-circle"></i> Verify
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php
                                                            // VERIFICATION_COMPLETED: Show Review only
                                                            elseif ($current_status == 'VERIFICATION_COMPLETED'):
                                                            ?>
                                                                <a href="task_review.php?case_task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning" title="Review Task">
                                                                    <i class="fas fa-clipboard-check"></i> Review
                                                                </a>
                                                                <?php endif; ?>
                                                          
                                                            
                                                            <!-- Always show Edit and Delete for ADMIN/DEV -->
                                                            <?php if ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV'): ?>
                                                                <a href="edit_case_task.php?case_task_id=<?php echo $task['id']; ?>&task_id=<?php echo $task['task_template_id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask(<?php echo $task['id']; ?>)">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Task Fields Display -->
                                                    <div class="row">
                                                        <?php
                                                        // Get task meta fields
                                                        $task_meta_fields = get_all('tasks_meta', '*', ['task_id' => $task['task_template_id'], 'status' => 'ACTIVE'], 'id ASC');
                                                        if ($task_meta_fields['count'] > 0) {
                                                            foreach ($task_meta_fields['data'] as $field) {
                                                                $field_value = isset($task_data[$field['field_name']]) ? $task_data[$field['field_name']] : '';
                                                                ?>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label"><strong><?php echo htmlspecialchars($field['display_name']); ?></strong></label>
                                                                    <div class="form-control-plaintext bg-light p-2 rounded">
                                                                        <?php echo htmlspecialchars($field_value ?: 'Not filled'); ?>
                                                                    </div>
                                                                </div>
                                                                <?php
                                                            }
                                                        } else {
                                                            echo '<div class="col-12"><p class="text-muted">No fields configured for this task.</p></div>';
                                                        }
                                                        ?>
                                                    </div>
                                                    
                                                    <!-- Verifier Remarks (Only show if verification completed) -->
                                                    <?php
                                                    $task_data_json = json_decode($task['task_data'] ?? '{}', true);
                                                    $show_verifier_remarks = ($task_status == 'VERIFICATION_COMPLETED' || $task_status == 'COMPLETED') && isset($task_data_json['verifier_remarks']) && !empty($task_data_json['verifier_remarks']);
                                                    if ($show_verifier_remarks):
                                                    ?>
                                                        <hr>
                                                        <div class="card border-info mb-3">
                                                            <div class="card-header bg-info text-white">
                                                                <h6 class="mb-0">
                                                                    <i class="fas fa-comment-alt"></i> Verifier Remarks & Findings
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($task_data_json['verifier_remarks'])); ?></p>
                                                                <?php if (isset($task_data_json['verifier_remarks_updated_at'])): ?>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-clock"></i> Updated: <?php echo date('d M Y, h:i A', strtotime($task_data_json['verifier_remarks_updated_at'])); ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Review Status & Final Report (Only show if completed) -->
                                                    <?php
                                                    $show_review = ($task_status == 'COMPLETED') && isset($task_data_json['review_status']) && !empty($task_data_json['review_status']);
                                                    if ($show_review):
                                                        $review_status = $task_data_json['review_status'];
                                                        $review_remarks = $task_data_json['review_remarks'] ?? '';
                                                    ?>
                                                        <hr>
                                                        <div class="card border-warning mb-3">
                                                            <div class="card-header bg-warning text-dark">
                                                                <h6 class="mb-0">
                                                                    <i class="fas fa-clipboard-check"></i> Review Status & Final Report
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <strong>Review Status:</strong> 
                                                                        <span class="badge bg-<?php 
                                                                            echo $review_status == 'POSITIVE' ? 'success' : ($review_status == 'NEGATIVE' ? 'danger' : 'warning');
                                                                        ?>">
                                                                            <?php echo htmlspecialchars($review_status); ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <?php if (isset($task_data_json['review_updated_at'])): ?>
                                                                            <strong>Reviewed On:</strong> 
                                                                            <?php echo date('d M Y, h:i A', strtotime($task_data_json['review_updated_at'])); ?>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <?php if (!empty($review_remarks)): ?>
                                                                    <div class="alert alert-light border">
                                                                        <strong>Final Report:</strong>
                                                                        <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($review_remarks)); ?></p>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Attachments -->
                                                    <?php
                                                    global $con;
                                                    $attachments_sql = "
                                                        SELECT id, file_type, file_name, file_url, display_in_report, created_at
                                                        FROM attachments
                                                        WHERE task_id = '{$task['id']}' AND status = 'ACTIVE'
                                                        ORDER BY display_in_report DESC, created_at DESC
                                                    ";
                                                    $attachments_res = mysqli_query($con, $attachments_sql);
                                                    $has_attachments = ($attachments_res && mysqli_num_rows($attachments_res) > 0);
                                                    
                                                    // Separate selected and unselected attachments
                                                    $selected_attachments = [];
                                                    $unselected_attachments = [];
                                                    if ($has_attachments) {
                                                        mysqli_data_seek($attachments_res, 0); // Reset pointer
                                                        while ($attachment = mysqli_fetch_assoc($attachments_res)) {
                                                            if (($attachment['display_in_report'] ?? 'NO') == 'YES') {
                                                                $selected_attachments[] = $attachment;
                                                            } else {
                                                                $unselected_attachments[] = $attachment;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <?php if ($has_attachments): ?>
                                                        <hr>
                                                        <div class="card border-info">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0">
                                                                    <i class="fas fa-paperclip text-info"></i> Attachments
                                                                    <span class="badge bg-info"><?php echo count($selected_attachments) + count($unselected_attachments); ?> total</span>
                                                                    <?php if (!empty($selected_attachments)): ?>
                                                                        <span class="badge bg-success"><?php echo count($selected_attachments); ?> in report</span>
                                                                    <?php endif; ?>
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Selected Attachments (In Report) -->
                                                                <?php if (!empty($selected_attachments)): ?>
                                                                    <div class="mb-4">
                                                                        <h6 class="text-success">
                                                                            <i class="fas fa-check-circle"></i> Selected for Report
                                                                        </h6>
                                                                        <div class="row">
                                                                            <?php foreach ($selected_attachments as $attachment): ?>
                                                                                <div class="col-md-4 mb-3">
                                                                                    <div class="card border-success shadow-sm">
                                                                                        <div class="card-body p-3">
                                                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                                                <div>
                                                                                                    <i class="fas fa-file text-primary fa-2x"></i>
                                                                                                </div>
                                                                                                <span class="badge bg-success">
                                                                                                    <i class="fas fa-check"></i> In Report
                                                                                                </span>
                                                                                            </div>
                                                                                            <strong class="d-block mb-1"><?php echo htmlspecialchars($attachment['file_name']); ?></strong>
                                                                                            <small class="text-muted d-block mb-2">
                                                                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($attachment['file_type'] ?? 'Unknown'); ?><br>
                                                                                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($attachment['created_at'])); ?>
                                                                                            </small>
                                                                                            <div class="d-grid gap-2">
                                                                                                <button type="button" class="btn btn-sm btn-primary" onclick="previewFile('<?php echo htmlspecialchars($attachment['file_url']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', '<?php echo htmlspecialchars($attachment['file_type'] ?? ''); ?>')">
                                                                                                    <i class="fas fa-eye"></i> View
                                                                                                </button>
                                                                                                <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-sm btn-success" download>
                                                                                                    <i class="fas fa-download"></i> Download
                                                                                                </a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                                
                                                                <!-- Unselected Attachments -->
                                                                <?php if (!empty($unselected_attachments)): ?>
                                                                    <div>
                                                                        <h6 class="text-muted">
                                                                            <i class="fas fa-file"></i> Other Attachments
                                                                        </h6>
                                                                        <div class="row">
                                                                            <?php foreach ($unselected_attachments as $attachment): ?>
                                                                                <div class="col-md-4 mb-3">
                                                                                    <div class="card border-secondary">
                                                                                        <div class="card-body p-3">
                                                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                                                <div>
                                                                                                    <i class="fas fa-file text-muted fa-2x"></i>
                                                                                                </div>
                                                                                                <span class="badge bg-secondary">Not Selected</span>
                                                                                            </div>
                                                                                            <strong class="d-block mb-1"><?php echo htmlspecialchars($attachment['file_name']); ?></strong>
                                                                                            <small class="text-muted d-block mb-2">
                                                                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($attachment['file_type'] ?? 'Unknown'); ?><br>
                                                                                                <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($attachment['created_at'])); ?>
                                                                                            </small>
                                                                                            <div class="d-grid gap-2">
                                                                                                <button type="button" class="btn btn-sm btn-primary" onclick="previewFile('<?php echo htmlspecialchars($attachment['file_url']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', '<?php echo htmlspecialchars($attachment['file_type'] ?? ''); ?>')">
                                                                                                    <i class="fas fa-eye"></i> View
                                                                                                </button>
                                                                                                <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-sm btn-info" download>
                                                                                                    <i class="fas fa-download"></i> Download
                                                                                                </a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                   
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Task Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Task to Verifier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="assignError" class="alert alert-danger" style="display:none;"></div>
                <div class="mb-3">
                    <label class="form-label"><strong>Select Verifier</strong></label>
                    <select id="assignVerifierSelect" class="form-select">
                        <option value="">Loading verifiers...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAssignBtn">Assign</button>
            </div>
        </div>
    </div>
</div>


<?php require_once('../system/footer.php'); ?>


<script>
// Assign Task Modal
var assignCurrentTaskId = 0;

function assignTask(taskId, currentVerifierId) {
    assignCurrentTaskId = taskId;
    
    // Load verifiers
    $.ajax({
        url: 'assign_task.php',
        type: 'POST',
        data: {
            action: 'get_verifiers'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var select = $('#assignVerifierSelect');
                select.empty();
                select.append('<option value="0">-- Not Assigned --</option>');
                
                response.verifiers.forEach(function(verifier) {
                    var displayText = verifier.name;
                    if (verifier.mobile) {
                        displayText += ' (' + verifier.mobile + ')';
                    }
                    var selected = (currentVerifierId == verifier.id) ? 'selected' : '';
                    select.append('<option value="' + verifier.id + '" ' + selected + '>' + displayText + '</option>');
                });
                
                // Reset error message
                $('#assignError').hide();
                
                $('#assignTaskModal').modal('show');
            } else {
                alert('Error loading verifiers: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading verifiers:', error, xhr.responseText);
            alert('Error loading verifiers. Please try again.');
        }
    });
}

$(document).ready(function() {
    $('#confirmAssignBtn').off('click').on('click', function() {
        var verifierId = $('#assignVerifierSelect').val();
        
        if (verifierId === '') {
            $('#assignError').text('Please select a verifier or choose "Not Assigned"').show();
            return;
        }
        
        $('#confirmAssignBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Assigning...');
        $('#assignError').hide();
        
        $.ajax({
            url: 'assign_task.php',
            type: 'POST',
            data: {
                action: 'assign_task',
                case_task_id: assignCurrentTaskId,
                verifier_id: verifierId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#assignTaskModal').modal('hide');
                    // Show success message
                    alert(response.message || 'Task assigned successfully!');
                    location.reload();
                } else {
                    $('#assignError').text(response.message || 'Failed to assign task').show();
                    $('#confirmAssignBtn').prop('disabled', false).html('Assign');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error assigning task:', error, xhr.responseText);
                var errorMsg = 'Error assigning task. Please try again.';
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMsg = errorResponse.message;
                    }
                } catch(e) {
                    // Use default error message
                }
                $('#assignError').text(errorMsg).show();
                $('#confirmAssignBtn').prop('disabled', false).html('Assign');
            }
        });
    });
});

function deleteTask(taskInstanceId) {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        $.ajax({
            url: 'save_case_step.php',
            type: 'POST',
            data: {
                action: 'delete_task',
                case_task_id: taskInstanceId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to delete task'));
                }
            },
            error: function() {
                alert('Error deleting task. Please try again.');
            }
        });
    }
}

// File Preview Function - Opens in resizable, movable window popup
function previewFile(fileUrl, fileName, fileType) {
    var previewUrl = 'file_preview.php?file=' + encodeURIComponent(fileUrl) + '&name=' + encodeURIComponent(fileName);
    
    // Open in a resizable, movable popup window
    var popup = window.open(
        previewUrl,
        'filePreview',
        'width=1200,height=800,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no'
    );
    
    // Focus the popup window
    if (popup) {
        popup.focus();
    } else {
        alert('Please allow popups for this site to view files.');
    }
}
</script>

