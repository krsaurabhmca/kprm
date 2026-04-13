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
$client_data = $client_info['count'] > 0 ? $client_info['data'] : [];
$positive_status = $client_data['positve_status'] ?? 'Positive';
$negative_status = $client_data['negative_status'] ?? 'Negative';
$cnv_status = $client_data['cnv_status'] ?? 'CNV';

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
$task_stats = ['total' => 0, 'pending' => 0, 'assigned' => 0, 'verified' => 0, 'reviewed' => 0, 'closed' => 0];

$table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
if (mysqli_num_rows($table_check) > 0) {
    $table_exists = true;
    $tasks_result = get_all('case_tasks', '*', ['case_id' => $case_id, 'status' => 'ACTIVE'], 'id ASC');
    if ($tasks_result['count'] > 0) {
        $existing_tasks = $tasks_result['data'];
        $task_stats['total'] = count($existing_tasks);
        
        // Get current user info for role-based visibility
        $current_user_id = $_SESSION['user_id'] ?? 0;
        $current_user_type = $_SESSION['user_type'] ?? '';
        
        // Filter tasks based on role-based visibility
        $filtered_tasks = [];
        foreach ($existing_tasks as $task) {
            $task_for_check = [
                'task_status' => $task['task_status'] ?? 'PENDING',
                'assigned_to' => $task['assigned_to'] ?? null
            ];
            
            if (can_user_view_task($task_for_check, $current_user_id, $current_user_type)) {
                $filtered_tasks[] = $task;
                
                // Calculate task statistics using display status
                $task_data_json = json_decode($task['task_data'] ?? '{}', true);
                $display_status = get_task_status_display($task['task_status'] ?? 'PENDING', $task_data_json);
                
                if ($display_status == 'Fresh Case') $task_stats['pending']++;
                elseif ($display_status == 'Assigned') $task_stats['assigned']++;
                elseif ($display_status == 'Verified') $task_stats['verified']++;
                elseif ($display_status == 'Reviewed') $task_stats['reviewed']++;
                // Note: Closed status is no longer used, all COMPLETED tasks are Reviewed
            }
        }
        $existing_tasks = $filtered_tasks;
        
        // Recalculate case status based on filtered tasks
        $case_tasks_for_status = [];
        foreach ($existing_tasks as $task) {
            $case_tasks_for_status[] = [
                'db_status' => $task['task_status'] ?? 'PENDING',
                'task_data' => $task['task_data'] ?? null
            ];
        }
        $calculated_case_status = calculate_case_status($case_tasks_for_status);
        
        // Update case status if different
        if (isset($case['case_status']) && $case['case_status'] != $calculated_case_status) {
            // Optionally update case status in database
            // update_data('cases', ['case_status' => $calculated_case_status], $case_id);
        }
    }
}

// Get REPORT template for this client (for report generation)
$template_id = null;
$template_name = null;
$table_check_template = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
if ($table_check_template && mysqli_num_rows($table_check_template) > 0) {
    // First try to get default REPORT template for this client
    $template_query = "SELECT id, template_name FROM report_templates 
                       WHERE client_id = '$client_id' 
                       AND template_type = 'REPORT'
                       AND status = 'ACTIVE' 
                       AND is_default = 'YES' 
                       LIMIT 1";
    $template_result = mysqli_query($con, $template_query);
    if ($template_result && mysqli_num_rows($template_result) > 0) {
        $template_row = mysqli_fetch_assoc($template_result);
        $template_id = $template_row['id'];
        $template_name = $template_row['template_name'];
    } else {
        // If no default, get first active REPORT template for this client
        $template_query = "SELECT id, template_name FROM report_templates 
                           WHERE client_id = '$client_id' 
                           AND template_type = 'REPORT'
                           AND status = 'ACTIVE' 
                           LIMIT 1";
        $template_result = mysqli_query($con, $template_query);
        if ($template_result && mysqli_num_rows($template_result) > 0) {
            $template_row = mysqli_fetch_assoc($template_result);
            $template_id = $template_row['id'];
            $template_name = $template_row['template_name'];
        } else {
            // If no REPORT template for this client, try to get any active REPORT template (fallback)
            // This allows generating reports even if client-specific template doesn't exist
            $template_query = "SELECT id, template_name FROM report_templates 
                               WHERE template_type = 'REPORT'
                               AND status = 'ACTIVE' 
                               ORDER BY is_default DESC, id DESC 
                               LIMIT 1";
            $template_result = mysqli_query($con, $template_query);
            if ($template_result && mysqli_num_rows($template_result) > 0) {
                $template_row = mysqli_fetch_assoc($template_result);
                $template_id = $template_row['id'];
                $template_name = $template_row['template_name'];
            }
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
                // Use calculated case status if available, otherwise use database value
                $case_status = isset($calculated_case_status) ? $calculated_case_status : ($case['case_status'] ?? 'PENDING');
                $status_config = [
                    'PENDING' => ['color' => 'warning', 'icon' => 'clock'],
                    'IN_PROGRESS' => ['color' => 'info', 'icon' => 'spinner'],
                    'COMPLETED' => ['color' => 'success', 'icon' => 'check-circle'],
                    'ACTIVE' => ['color' => 'success', 'icon' => 'check-circle'],
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
            
            <?php 
            // Unified Report Link
            $report_url = "";
            $is_jio = (stripos($client_name, 'Jio') !== false);
            
            if ($is_jio) {
                // Jio uses the new High-Fidelity PDF Engine
                $report_url = "jio_report_export.php?case_id=" . $case_id;
            } elseif ($template_id) {
                // Fallback to standard Report template
                $report_url = "generate_report.php?template_id=" . $template_id . "&case_id=" . $case_id;
            }
            ?>
            
            <?php if ($report_url): ?>
                <a href="<?php echo $report_url; ?>" class="btn btn-success btn-sm" target="_blank" title="Download Report in Professional PDF Format">
                    <i class="fas fa-file-pdf me-1"></i> Generate Report
                </a>
            <?php else: ?>
                <button class="btn btn-success btn-sm" disabled title="No report template configured">
                    <i class="fas fa-file-pdf me-1"></i> Generate Report
                </button>
            <?php endif; ?>

            <a href="mis_export.php?case_id=<?php echo $case_id; ?>" class="btn btn-dark btn-sm" target="_blank" title="Download MIS with Client Format Auto mapped">
                <i class="fas fa-file-excel me-1"></i> Download MIS
            </a>

            <a href="add_new_case.php?step=2&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-warning btn-sm" title="Edit Case Information">
                <i class="fas fa-edit me-1"></i> Edit
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
                            <div class="text-muted small mb-1">Fresh Case</div>
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
                            <div class="text-muted small mb-1">Assigned</div>
                            <div class="h5 mb-0 fw-bold text-info"><?php echo $task_stats['assigned']; ?></div>
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
                            <div class="text-muted small mb-1">Verified</div>
                            <div class="h5 mb-0 fw-bold text-primary"><?php echo $task_stats['verified']; ?></div>
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
                            <div class="text-muted small mb-1">Reviewed</div>
                            <div class="h5 mb-0 fw-bold text-success"><?php echo $task_stats['reviewed']; ?></div>
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
                                $task_status_db = $task['task_status'] ?? 'PENDING';
                                $task_data_json = json_decode($task['task_data'] ?? '{}', true);
                                
                                // Get display status
                                $task_status_display = get_task_status_display($task_status_db, $task_data_json);
                                
                                // Map display status to badge config
                                $display_status_config = [
                                    'Fresh Case' => ['color' => 'warning', 'icon' => 'clock'],
                                    'Assigned' => ['color' => 'info', 'icon' => 'user-check'],
                                    'Verified' => ['color' => 'primary', 'icon' => 'check-circle'],
                                    'Reviewed' => ['color' => 'success', 'icon' => 'clipboard-check']
                                ];
                                $status_info = $display_status_config[$task_status_display] ?? ['color' => 'secondary', 'icon' => 'circle'];
                                
                                // Keep original for workflow logic
                                $task_status = $task_status_db;
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
                                                        <?php echo $task_status_display; ?>
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
                                                            <?php
                                                            // Check if user can review
                                                            $task_for_action = [
                                                                'task_status' => $current_status,
                                                                'assigned_to' => $task['assigned_to'] ?? null
                                                            ];
                                                            if (can_user_action_task($task_for_action, 'review', $_SESSION['user_id'] ?? 0, $_SESSION['user_type'] ?? '')):
                                                            ?>
                                                                <a href="task_review.php?case_task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning" title="Review Task">
                                                                    <i class="fas fa-clipboard-check me-1"></i> Review
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php
                                                        // COMPLETED: Show view only
                                                        elseif ($current_status == 'COMPLETED'):
                                                        ?>
                                                            <span class="badge bg-success fs-6 px-3 py-2">
                                                                <i class="fas fa-check-circle me-1"></i> <?php echo $task_status_display; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <!-- Always show Edit and Delete for ADMIN/DEV -->
                                                        <?php if ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV'): ?>
                                                            <button type="button" class="btn btn-sm btn-primary" onclick="openEditTaskModal(<?php echo $task['id']; ?>, <?php echo $task['task_template_id']; ?>)" title="Edit Task">
                                                                <i class="fas fa-edit me-1"></i> Edit
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Delete Task">
                                                                <i class="fas fa-trash me-1"></i> Delete
                                                            </button>

                                                            <!-- Manual Stage Override -->
                                                            <div class="dropdown d-inline-block">
                                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i class="fas fa-layer-group me-1"></i> Stage Override
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="border-radius: 8px; border: none; font-size: 0.85rem; z-index: 1060;">
                                                                    <li><h6 class="dropdown-header text-primary fw-bold"><i class="fas fa-tools me-1"></i> Manual Stage Override</h6></li>
                                                                    <li><hr class="dropdown-divider"></li>
                                                                    <li><a class="dropdown-item <?php echo $task_status_db == 'PENDING' ? 'active disabled' : ''; ?>" href="javascript:void(0)" onclick="changeTaskStage(<?php echo $task['id']; ?>, 'PENDING')">
                                                                        <i class="fas fa-clock text-warning me-2"></i> Set as Fresh Case
                                                                    </a></li>
                                                                    <li><a class="dropdown-item <?php echo $task_status_db == 'IN_PROGRESS' ? 'active disabled' : ''; ?>" href="javascript:void(0)" onclick="changeTaskStage(<?php echo $task['id']; ?>, 'IN_PROGRESS')">
                                                                        <i class="fas fa-user-check text-info me-2"></i> Set as Assigned
                                                                    </a></li>
                                                                    <li><a class="dropdown-item <?php echo $task_status_db == 'VERIFICATION_COMPLETED' ? 'active disabled' : ''; ?>" href="javascript:void(0)" onclick="changeTaskStage(<?php echo $task['id']; ?>, 'VERIFICATION_COMPLETED')">
                                                                        <i class="fas fa-check-circle text-primary me-2"></i> Set as Verified
                                                                    </a></li>
                                                                    <li><a class="dropdown-item <?php echo $task_status_db == 'COMPLETED' ? 'active disabled' : ''; ?>" href="javascript:void(0)" onclick="changeTaskStage(<?php echo $task['id']; ?>, 'COMPLETED')">
                                                                        <i class="fas fa-clipboard-check text-success me-2"></i> Set as Reviewed
                                                                    </a></li>
                                                                </ul>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Task Fields Display -->
                                            <div class="mt-3 pt-3 border-top">
                                                <h6 class="mb-3 text-muted small fw-bold">
                                                    <i class="fas fa-list-ul me-1"></i> Data Points
                                                </h6>
                                                <div class="row g-3">
                                                    <?php
                                                    // Get task meta fields
                                                    $task_meta_fields = get_all('tasks_meta', '*', ['task_id' => $task['task_template_id'], 'status' => 'ACTIVE'], 'id ASC');
                                                    if ($task_meta_fields['count'] > 0) {
                                                        foreach ($task_meta_fields['data'] as $field) {
                                                            $field_value = isset($task_data[$field['field_name']]) ? $task_data[$field['field_name']] : '';
                                                            $is_table = (is_array($field_value) || (is_string($field_value) && (strpos($field_value, '[{"section"') === 0 || strpos($field_value, '{"') === 0)));
                                                            ?>
                                                            <div class="<?php echo $is_table ? 'col-12' : 'col-md-6 col-lg-4'; ?>">
                                                                <div class="<?php echo $is_table ? '' : 'field-group'; ?>">
                                                                    <div class="info-label"><?php echo htmlspecialchars($field['display_name']); ?></div>
                                                                     <div class="field-value">
                                                                        <?php 
                                                                        if ($is_table) {
                                                                            echo render_financial_table_readonly($field_value);
                                                                        } else {
                                                                            echo !empty($field_value) ? '<span class="fw-bold">' . htmlspecialchars($field_value) . '</span>' : '<span class="text-muted fst-italic">N/A</span>';
                                                                        }
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php
                                                        }
                                                    } else {
                                                        echo '<div class="col-12"><p class="text-muted small text-center py-2 bg-white border rounded"><i class="fas fa-info-circle me-1"></i>No fields configured for this task.</p></div>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Verifier Remarks -->
                                            <?php
                                            $task_data_json = json_decode($task['task_data'] ?? '{}', true);
                                            $show_verifier_remarks = (in_array($task_status_db, ['VERIFICATION_COMPLETED', 'COMPLETED'])) && isset($task_data_json['verifier_remarks']) && !empty($task_data_json['verifier_remarks']);
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
                                            $show_review = ($task_status_db == 'COMPLETED') && isset($task_data_json['review_status']) && !empty($task_data_json['review_status']);
                                            if ($show_review):
                                                $review_status = $task_data_json['review_status'];
                                                $review_remarks = $task_data_json['review_remarks'] ?? '';
                                                
                                                // Replace status words in review remarks with client-defined status words
                                                if (!empty($review_remarks)) {
                                                    // Get client status word based on review status
                                                    $client_status_word = '';
                                                    if ($review_status == 'POSITIVE') {
                                                        $client_status_word = $positive_status;
                                                    } elseif ($review_status == 'NEGATIVE') {
                                                        $client_status_word = $negative_status;
                                                    } elseif ($review_status == 'CNV') {
                                                        $client_status_word = $cnv_status;
                                                    }
                                                    
                                                    // Replace database status words with client status words in remarks
                                                    // Replace "Positive" with client's positive status word
                                                    $review_remarks = str_replace('Positive', $positive_status, $review_remarks);
                                                    $review_remarks = str_replace('positive', strtolower($positive_status), $review_remarks);
                                                    
                                                    // Replace "Negative" with client's negative status word
                                                    $review_remarks = str_replace('Negative', $negative_status, $review_remarks);
                                                    $review_remarks = str_replace('negative', strtolower($negative_status), $review_remarks);
                                                    
                                                    // Replace "CNV" with client's CNV status word
                                                    $review_remarks = str_replace('CNV', $cnv_status, $review_remarks);
                                                    $review_remarks = str_replace('cnv', strtolower($cnv_status), $review_remarks);
                                                    
                                                    // Also replace the specific status word if it appears
                                                    if (!empty($client_status_word)) {
                                                        // Replace any remaining occurrences of the database status word
                                                        $db_status_words = ['Positive', 'Negative', 'CNV'];
                                                        $client_status_words = [$positive_status, $negative_status, $cnv_status];
                                                        for ($i = 0; $i < count($db_status_words); $i++) {
                                                            $review_remarks = str_ireplace($db_status_words[$i], $client_status_words[$i], $review_remarks);
                                                        }
                                                    }
                                                }
                                                
                                                // Get display status word for badge
                                                $review_status_display = '';
                                                if ($review_status == 'POSITIVE') {
                                                    $review_status_display = $positive_status;
                                                } elseif ($review_status == 'NEGATIVE') {
                                                    $review_status_display = $negative_status;
                                                } elseif ($review_status == 'CNV') {
                                                    $review_status_display = $cnv_status;
                                                } else {
                                                    $review_status_display = $review_status;
                                                }
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
                                                                        <?php echo htmlspecialchars($review_status_display); ?>
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
                                                                <div class="alert alert-light border small mb-3">
                                                                    <strong>Final Report:</strong>
                                                                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($review_remarks)); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php 
                                                            // Check if there is a financial table to preview in review card
                                                            foreach ($task_data_json as $key => $val) {
                                                                if (is_array($val) || (is_string($val) && (strpos($val, '[{"section"') === 0 || strpos($val, '{"') === 0))) {
                                                                    echo '<div class="mt-3">';
                                                                    echo '<h6 class="small fw-bold text-primary mb-2"><i class="fas fa-table me-1"></i> Financial Data Comparison:</h6>';
                                                                    echo render_financial_table_readonly($val);
                                                                    echo '</div>';
                                                                }
                                                            }
                                                            ?>
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
                    <label class="form-label"><strong>Select Field Verifier</strong></label>
                    <select id="assignVerifierSelect" class="form-select">
                        <option value="">Loading verifiers...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sendWhatsAppCheck" checked>
                        <label class="form-check-label" for="sendWhatsAppCheck">
                            <i class="fab fa-whatsapp text-success me-1"></i> Send WhatsApp notification with task details
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAssignBtn">Assign</button>
            </div>
        </div>
    </div>
</div>

<!-- Task Edit Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i> Edit Task Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="modalLoading" class="text-center py-5" style="display:none;">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading task data...</p>
            </div>
            <div class="modal-body" id="editTaskModalBody">
                <!-- Data loaded via AJAX -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="saveTaskBtn" onclick="submitAjaxEditTask()">
                    <i class="fas fa-save me-1"></i> Update Task
                </button>
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
        var sendWhatsApp = $('#sendWhatsAppCheck').is(':checked') ? 1 : 0;
        
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
                verifier_id: verifierId,
                send_whatsapp: sendWhatsApp
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#assignTaskModal').modal('hide');
                    var message = response.message || 'Task assigned successfully!';
                    
                    // If WhatsApp URL is provided, show option to open it
                    if (response.whatsapp_url) {
                        if (confirm(message + '\n\nOpen WhatsApp to send message?')) {
                            window.open(response.whatsapp_url, '_blank');
                        }
                    } else {
                        alert(message);
                    }
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

    // Task Edit Modal Functionality
    function openEditTaskModal(caseTaskId, taskId) {
        var modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        var body = document.getElementById('editTaskModalBody');
        var loading = document.getElementById('modalLoading');
        
        body.innerHTML = '';
        loading.style.display = 'block';
        modal.show();
        
        $.ajax({
            url: 'ajax_get_edit_task_form.php',
            type: 'GET',
            data: {
                case_task_id: caseTaskId,
                task_id: taskId
            },
            success: function(html) {
                loading.style.display = 'none';
                body.innerHTML = html;
            },
            error: function() {
                loading.style.display = 'none';
                body.innerHTML = '<div class="alert alert-danger">Failed to load edit form.</div>';
            }
        });
    }

    // Submit AJAX Edit Task
    function submitAjaxEditTask() {
        var form = document.getElementById('ajaxEditTaskForm');
        if (!form) return;
        
        var formData = new FormData(form);
        var saveBtn = document.getElementById('saveTaskBtn');
        var originalText = saveBtn.innerHTML;
        var errorDiv = document.getElementById('ajaxFormError');
        var successDiv = document.getElementById('ajaxFormSuccess');
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
        
        $.ajax({
            url: 'save_case_step.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    successDiv.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + (response.message || 'Task updated successfully!');
                    successDiv.style.display = 'block';
                    errorDiv.style.display = 'none';
                    
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> ' + (response.message || 'Error updating task.');
                    errorDiv.style.display = 'block';
                }
            },
            error: function() {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Connection error.';
                errorDiv.style.display = 'block';
            }
        });
    }

    // Existing functions...
    // Change Task Stage (Admin/Dev only)
    function changeTaskStage(taskId, newStage) {
        if (!confirm('Are you sure you want to manually change this task\'s stage? This will override the standard workflow.')) {
            return;
        }

        $.ajax({
            url: 'ajax_update_task_stage.php',
            type: 'POST',
            data: {
                task_id: taskId,
                new_stage: newStage
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Connection error. Failed to update stage.');
            }
        });
    }
</script>

<style>
.field-group {
    padding: 10px;
    background: #fdfdfd;
    border: 1px solid #efefef;
    border-radius: 5px;
    height: 100%;
}
.field-label {
    font-size: 11px;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 3px;
}
.field-value {
    font-size: 13px;
    color: #333;
    min-height: 20px;
}
.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #000;
}
.dropdown-menu-override {
    z-index: 9999 !important;
}
</style>
