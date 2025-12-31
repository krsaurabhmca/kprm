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
$task_stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'verification_completed' => 0, 'completed' => 0];

$table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
if (mysqli_num_rows($table_check) > 0) {
    $table_exists = true;
    $tasks_result = get_all('case_tasks', '*', ['case_id' => $case_id, 'status' => 'ACTIVE'], 'id ASC');
    if ($tasks_result['count'] > 0) {
        $existing_tasks = $tasks_result['data'];
        $task_stats['total'] = count($existing_tasks);
        
        // Calculate task statistics
        foreach ($existing_tasks as $task) {
            $status = $task['task_status'] ?? 'PENDING';
            if ($status == 'PENDING') $task_stats['pending']++;
            elseif ($status == 'IN_PROGRESS') $task_stats['in_progress']++;
            elseif ($status == 'VERIFICATION_COMPLETED') $task_stats['verification_completed']++;
            elseif ($status == 'COMPLETED') $task_stats['completed']++;
        }
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
    echo '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">';
    echo '<i class="fas fa-check-circle me-2"></i>' . $_SESSION['success_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">';
    echo '<i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['error_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid py-3">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-folder-open text-primary me-2"></i>
                Case #<?php echo $case_id; ?>
                <?php 
                $case_status = $case['case_status'] ?? 'ACTIVE';
                $status_config = [
                    'ACTIVE' => ['color' => 'success', 'icon' => 'check-circle'],
                    'PENDING' => ['color' => 'warning', 'icon' => 'clock'],
                    'COMPLETED' => ['color' => 'info', 'icon' => 'check-double'],
                    'ON_HOLD' => ['color' => 'secondary', 'icon' => 'pause']
                ];
                $status_info = $status_config[$case_status] ?? ['color' => 'secondary', 'icon' => 'circle'];
                ?>
                <span class="badge bg-<?php echo $status_info['color']; ?> ms-2">
                    <i class="fas fa-<?php echo $status_info['icon']; ?> me-1"></i>
                    <?php echo htmlspecialchars($case_status); ?>
                </span>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($client_name); ?>
                <?php if (!empty($case['application_no'])): ?>
                    <span class="ms-3"><i class="fas fa-hashtag me-1"></i><?php echo htmlspecialchars($case['application_no']); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="case_manage.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <?php if ($template_id): ?>
                <a href="generate_report.php?template_id=<?php echo $template_id; ?>&case_id=<?php echo $case_id; ?>" class="btn btn-success btn-sm" target="_blank" title="Generate Report using <?php echo htmlspecialchars($template_name); ?>">
                    <i class="fas fa-file-pdf me-1"></i> Generate Report
                </a>
            <?php else: ?>
                <button class="btn btn-success btn-sm" disabled title="No template configured">
                    <i class="fas fa-file-pdf me-1"></i> Generate Report
                </button>
            <?php endif; ?>
            <a href="add_new_case.php?step=2&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-warning btn-sm" title="Edit Case Information">
                <i class="fas fa-edit me-1"></i> Edit Info
            </a>
            <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm" title="Manage Tasks">
                <i class="fas fa-tasks me-1"></i> Manage Tasks
            </a>
        </div>
    </div>

    <!-- Task Statistics Cards -->
    <?php if ($table_exists && $task_stats['total'] > 0): ?>
    <div class="row mb-4">
        <div class="col-md-3 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Total Tasks</div>
                            <div class="h5 mb-0 fw-bold text-primary"><?php echo $task_stats['total']; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-tasks fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Pending</div>
                            <div class="h5 mb-0 fw-bold text-warning"><?php echo $task_stats['pending']; ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">In Progress</div>
                            <div class="h5 mb-0 fw-bold text-info"><?php echo $task_stats['in_progress']; ?></div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-spinner fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Verification Done</div>
                            <div class="h5 mb-0 fw-bold text-primary"><?php echo $task_stats['verification_completed']; ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Completed</div>
                            <div class="h5 mb-0 fw-bold text-success"><?php echo $task_stats['completed']; ?></div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-double fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column: Case & Client Info -->
        <div class="col-md-4">
            <!-- Case Information -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Case Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Case ID</div>
                        <div class="fw-bold">#<?php echo $case_id; ?></div>
                    </div>
                    <?php if (!empty($case['application_no'])): ?>
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Application Number</div>
                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($case['application_no']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Case Status</div>
                        <div>
                            <span class="badge bg-<?php echo $status_info['color']; ?>">
                                <i class="fas fa-<?php echo $status_info['icon']; ?> me-1"></i>
                                <?php echo htmlspecialchars($case_status); ?>
                            </span>
                        </div>
                    </div>
                    <!-- <div class="mb-3">
                        <div class="text-muted small mb-1">Record Status</div>
                        <div>
                            <span class="badge bg-<?php echo ($case['status'] == 'ACTIVE' ? 'success' : 'secondary'); ?>">
                                <?php echo htmlspecialchars($case['status']); ?>
                            </span>
                        </div>
                    </div> -->
                    <hr class="my-3">
                    <!-- <div class="mb-2">
                        <div class="text-muted small mb-1">
                            <i class="fas fa-calendar-plus me-1"></i> Created
                        </div>
                        <div class="small">
                            <?php 
                            if (!empty($case['created_at'])) {
                                $created = new DateTime($case['created_at']);
                                echo $created->format('d M Y, h:i A');
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div> -->
                    <div>
                        <div class="text-muted small mb-1">
                            <i class="fas fa-calendar-check me-1"></i> Last Updated
                        </div>
                        <div class="small">
                            <?php 
                            if (!empty($case['updated_at'])) {
                                $updated = new DateTime($case['updated_at']);
                                echo $updated->format('d M Y, h:i A');
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Information -->
            <!-- <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-building text-primary me-2"></i> Client Information
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Client ID</div>
                        <div class="fw-bold">#<?php echo $client_id; ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Client Name</div>
                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($client_name); ?></div>
                    </div>
                    <?php if ($client_email): ?>
                    <div>
                        <div class="text-muted small mb-1">
                            <i class="fas fa-envelope me-1"></i> Email
                        </div>
                        <div class="small">
                            <a href="mailto:<?php echo htmlspecialchars($client_email); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($client_email); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div> -->

            <!-- Case Info (from JSON) -->
            <?php if (!empty($case_info_data)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-list text-primary me-2"></i> Case Details
                    </h6>
                </div>
                <div class="card-body p-3">
                    <?php
                    $key_fields = ['region', 'branch', 'product', 'state', 'location', 'loan_amount'];
                    $displayed_fields = [];
                    foreach ($key_fields as $field) {
                        if (isset($case_info_data[$field]) && !empty($case_info_data[$field])) {
                            $displayed_fields[$field] = $case_info_data[$field];
                        }
                    }
                    // Add remaining fields
                    foreach ($case_info_data as $key => $value) {
                        if (!empty($value) && !isset($displayed_fields[$key]) && !in_array($key, $key_fields)) {
                            $displayed_fields[$key] = $value;
                        }
                    }
                    
                    foreach ($displayed_fields as $key => $value):
                        $display_key = ucwords(str_replace('_', ' ', $key));
                    ?>
                        <div class="mb-3">
                            <div class="text-muted small mb-1"><?php echo htmlspecialchars($display_key); ?></div>
                            <div class="fw-bold"><?php echo htmlspecialchars($value); ?></div>
                        </div>
                    <?php 
                    endforeach;
                    if (empty($displayed_fields)):
                    ?>
                        <p class="text-muted small mb-0">No details available</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Tasks -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-tasks text-primary me-2"></i> Tasks
                            <span class="badge bg-secondary ms-2"><?php echo count($existing_tasks); ?></span>
                        </h6>
                        <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Task
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <?php if (!$table_exists): ?>
                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle fa-2x me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading mb-2">Database Table Missing</h6>
                                <p class="mb-2">The case_tasks table does not exist. Please run the following SQL command in your database:</p>
                                <code class="d-block mb-2 p-2 bg-light">SOURCE db/create_case_tasks_table.sql;</code>
                                <a href="../db/create_case_tasks_table.sql" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-download me-1"></i> View SQL File
                                </a>
                            </div>
                        </div>
                    <?php elseif (empty($existing_tasks)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-tasks fa-4x text-muted opacity-50"></i>
                            </div>
                            <h6 class="text-muted mb-2">No Tasks Added Yet</h6>
                            <p class="text-muted small mb-4">Get started by adding your first task to this case</p>
                            <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Add Your First Task
                            </a>
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
                                
                                $status_config = [
                                    'PENDING' => ['color' => 'warning', 'icon' => 'clock'],
                                    'IN_PROGRESS' => ['color' => 'info', 'icon' => 'spinner'],
                                    'VERIFICATION_COMPLETED' => ['color' => 'primary', 'icon' => 'check-circle'],
                                    'COMPLETED' => ['color' => 'success', 'icon' => 'check-double'],
                                    'REJECTED' => ['color' => 'danger', 'icon' => 'times-circle']
                                ];
                                $status_info = $status_config[$task_status] ?? ['color' => 'secondary', 'icon' => 'circle'];
                                ?>
                                <div class="accordion-item mb-2 border rounded">
                                    <h2 class="accordion-header" id="heading<?php echo $task['id']; ?>">
                                        <button class="accordion-button <?php echo $index == 0 ? '' : 'collapsed'; ?> py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $task['id']; ?>">
                                            <div class="w-100 d-flex justify-content-between align-items-center me-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-tasks text-primary me-2"></i>
                                                    <strong><?php echo htmlspecialchars($task_name); ?></strong>
                                                    <span class="badge bg-<?php echo $status_info['color']; ?> ms-2">
                                                        <i class="fas fa-<?php echo $status_info['icon']; ?> me-1"></i>
                                                        <?php echo $task_status; ?>
                                                    </span>
                                                    <span class="badge bg-secondary ms-1"><?php echo $task_type; ?></span>
                                                </div>
                                                <small class="text-muted">ID: <?php echo $task['id']; ?></small>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $task['id']; ?>" class="accordion-collapse collapse <?php echo $index == 0 ? 'show' : ''; ?>" data-bs-parent="#tasksAccordion">
                                        <div class="accordion-body bg-light p-3">
                                            <div class="row mb-3">
                                                <div class="col-md-6 mb-2">
                                                    <?php
                                                    // Show assigned verifier info
                                                    if (!empty($task['assigned_to'])) {
                                                        $verifier_info = get_data('verifier', $task['assigned_to']);
                                                        if ($verifier_info['count'] > 0) {
                                                            $verifier = $verifier_info['data'];
                                                            ?>
                                                            <div class="p-2 bg-info bg-opacity-10 border border-info rounded">
                                                                <small class="text-muted d-block">Assigned To</small>
                                                                <strong><?php echo htmlspecialchars($verifier['verifier_name'] ?? 'Unknown'); ?></strong>
                                                                <?php if (!empty($verifier['verifier_mobile'])): ?>
                                                                    <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($verifier['verifier_mobile']); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php
                                                        }
                                                    } else {
                                                        ?>
                                                        <div class="p-2 bg-warning bg-opacity-10 border border-warning rounded">
                                                            <small class="text-muted d-block">Status</small>
                                                            <strong>Not Assigned</strong>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                                                        <?php
                                                        // Workflow-based button display
                                                        $current_status = $task['task_status'] ?? 'PENDING';
                                                        
                                                        // PENDING: Show Assign only
                                                        if ($current_status == 'PENDING'):
                                                        ?>
                                                            <button type="button" class="btn btn-sm btn-success" onclick="assignTask(<?php echo $task['id']; ?>, 0)">
                                                                <i class="fas fa-user-plus me-1"></i> Assign
                                                            </button>
                                                        <?php
                                                        // IN_PROGRESS (Assigned): Show Reassign and Verify
                                                        elseif ($current_status == 'IN_PROGRESS'):
                                                        ?>
                                                            <button type="button" class="btn btn-sm btn-success" onclick="assignTask(<?php echo $task['id']; ?>, <?php echo $task['assigned_to'] ?? 0; ?>)">
                                                                <i class="fas fa-user-edit me-1"></i> Reassign
                                                            </button>
                                                            <?php if (!empty($task['assigned_to'])): ?>
                                                                <a href="task_verifier_submit.php?case_task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info" title="Verify Task">
                                                                    <i class="fas fa-check-circle me-1"></i> Verify
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php
                                                        // VERIFICATION_COMPLETED: Show Review only
                                                        elseif ($current_status == 'VERIFICATION_COMPLETED'):
                                                        ?>
                                                            <a href="task_review.php?case_task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning" title="Review Task">
                                                                <i class="fas fa-clipboard-check me-1"></i> Review
                                                            </a>
                                                        <?php
                                                        // COMPLETED: Show view only
                                                        elseif ($current_status == 'COMPLETED'):
                                                        ?>
                                                            <span class="badge bg-success fs-6 px-3 py-2">
                                                                <i class="fas fa-check-circle me-1"></i> Completed
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Always show Edit and Delete for ADMIN/DEV -->
                                                        <?php if ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV'): ?>
                                                            <a href="edit_case_task.php?case_task_id=<?php echo $task['id']; ?>&task_id=<?php echo $task['task_template_id']; ?>" class="btn btn-sm btn-primary" title="Edit Task">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Delete Task">
                                                                <i class="fas fa-trash me-1"></i> Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Task Fields Display -->
                                            <div class="mt-3 pt-3 border-top">
                                                <h6 class="mb-2 text-muted small">
                                                    <i class="fas fa-list-ul me-1"></i> Task Details
                                                </h6>
                                                <div class="row">
                                                    <?php
                                                    // Get task meta fields
                                                    $task_meta_fields = get_all('tasks_meta', '*', ['task_id' => $task['task_template_id'], 'status' => 'ACTIVE'], 'id ASC');
                                                    if ($task_meta_fields['count'] > 0) {
                                                        foreach ($task_meta_fields['data'] as $field) {
                                                            $field_value = isset($task_data[$field['field_name']]) ? $task_data[$field['field_name']] : '';
                                                            ?>
                                                            <div class="col-md-6 mb-2">
                                                                <label class="form-label text-muted small mb-0"><?php echo htmlspecialchars($field['display_name']); ?></label>
                                                                <div class="field-value p-1 bg-white border rounded small">
                                                                    <?php echo !empty($field_value) ? htmlspecialchars($field_value) : '<span class="text-muted fst-italic">Not filled</span>'; ?>
                                                                </div>
                                                            </div>
                                                            <?php
                                                        }
                                                    } else {
                                                        echo '<div class="col-12"><p class="text-muted small text-center py-2"><i class="fas fa-info-circle me-1"></i>No fields configured for this task.</p></div>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Verifier Remarks -->
                                            <?php
                                            $task_data_json = json_decode($task['task_data'] ?? '{}', true);
                                            $show_verifier_remarks = ($task_status == 'VERIFICATION_COMPLETED' || $task_status == 'COMPLETED') && isset($task_data_json['verifier_remarks']) && !empty($task_data_json['verifier_remarks']);
                                            if ($show_verifier_remarks):
                                            ?>
                                                <div class="mt-3 pt-3 border-top">
                                                    <div class="card border-info">
                                                        <div class="card-header bg-info text-white py-2">
                                                            <h6 class="mb-0 small">
                                                                <i class="fas fa-comment-alt me-1"></i> Verifier Remarks & Findings
                                                            </h6>
                                                        </div>
                                                        <div class="card-body p-3">
                                                            <p class="mb-2 small"><?php echo nl2br(htmlspecialchars($task_data_json['verifier_remarks'])); ?></p>
                                                            <?php if (isset($task_data_json['verifier_remarks_updated_at'])): ?>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i> Updated: <?php echo date('d M Y, h:i A', strtotime($task_data_json['verifier_remarks_updated_at'])); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Review Status & Final Report -->
                                            <?php
                                            $show_review = ($task_status == 'COMPLETED') && isset($task_data_json['review_status']) && !empty($task_data_json['review_status']);
                                            if ($show_review):
                                                $review_status = $task_data_json['review_status'];
                                                $review_remarks = $task_data_json['review_remarks'] ?? '';
                                            ?>
                                                <div class="mt-3 pt-3 border-top">
                                                    <div class="card border-warning">
                                                        <div class="card-header bg-warning text-dark py-2">
                                                            <h6 class="mb-0 small">
                                                                <i class="fas fa-clipboard-check me-1"></i> Review Status & Final Report
                                                            </h6>
                                                        </div>
                                                        <div class="card-body p-3">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <small class="text-muted d-block mb-1">Review Status</small>
                                                                    <span class="badge bg-<?php 
                                                                        echo $review_status == 'POSITIVE' ? 'success' : ($review_status == 'NEGATIVE' ? 'danger' : 'warning');
                                                                    ?>">
                                                                        <?php echo htmlspecialchars($review_status); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <?php if (isset($task_data_json['review_updated_at'])): ?>
                                                                        <small class="text-muted d-block mb-1">Reviewed On</small>
                                                                        <small><?php echo date('d M Y, h:i A', strtotime($task_data_json['review_updated_at'])); ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($review_remarks)): ?>
                                                                <div class="alert alert-light border small mb-0">
                                                                    <strong>Final Report:</strong>
                                                                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($review_remarks)); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Attachments -->
                                            <?php
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
                                                mysqli_data_seek($attachments_res, 0);
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
                                                <div class="mt-3 pt-3 border-top">
                                                    <h6 class="mb-2 text-muted small">
                                                        <i class="fas fa-paperclip me-1"></i> Attachments
                                                        <span class="badge bg-info"><?php echo count($selected_attachments) + count($unselected_attachments); ?> total</span>
                                                        <?php if (!empty($selected_attachments)): ?>
                                                            <span class="badge bg-success"><?php echo count($selected_attachments); ?> in report</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <div class="row">
                                                        <?php foreach (array_merge($selected_attachments, $unselected_attachments) as $attachment): 
                                                            $is_selected = ($attachment['display_in_report'] ?? 'NO') == 'YES';
                                                        ?>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="card border-<?php echo $is_selected ? 'success' : 'secondary'; ?> h-100">
                                                                    <div class="card-body p-2">
                                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                                            <i class="fas fa-file text-primary"></i>
                                                                            <?php if ($is_selected): ?>
                                                                                <span class="badge bg-success small">In Report</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <strong class="d-block small mb-1" style="font-size: 11px;"><?php echo htmlspecialchars($attachment['file_name']); ?></strong>
                                                                        <small class="text-muted d-block mb-2" style="font-size: 10px;">
                                                                            <?php echo htmlspecialchars($attachment['file_type'] ?? 'Unknown'); ?>
                                                                        </small>
                                                                        <div class="d-grid gap-1">
                                                                            <button type="button" class="btn btn-xs btn-primary btn-sm" onclick="previewFile('<?php echo htmlspecialchars($attachment['file_url']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', '<?php echo htmlspecialchars($attachment['file_type'] ?? ''); ?>')">
                                                                                <i class="fas fa-eye me-1"></i> View
                                                                            </button>
                                                                            <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-xs btn-<?php echo $is_selected ? 'success' : 'info'; ?> btn-sm" download>
                                                                                <i class="fas fa-download me-1"></i> Download
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

<!-- Assign Task Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i> Assign Task to Verifier
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

// File Preview Function
function previewFile(fileUrl, fileName, fileType) {
    var previewUrl = 'file_preview.php?file=' + encodeURIComponent(fileUrl) + '&name=' + encodeURIComponent(fileName);
    
    var popup = window.open(
        previewUrl,
        'filePreview',
        'width=1200,height=800,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no'
    );
    
    if (popup) {
        popup.focus();
    } else {
        alert('Please allow popups for this site to view files.');
    }
}
</script>

<style>
.field-value {
    min-height: 28px;
    word-break: break-word;
    font-size: 13px;
}
</style>
