<?php
/**
 * KPRM - Client Wise Task Status
 * View and manage tasks by client and status
 */

require_once('../system/all_header.php');

global $con;

// Get selected client
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'PENDING';

// Get all active clients
$clients = [];
$clients_sql = "SELECT id, name FROM clients WHERE status = 'ACTIVE' ORDER BY name ASC";
$clients_res = mysqli_query($con, $clients_sql);
if ($clients_res) {
    while ($row = mysqli_fetch_assoc($clients_res)) {
        $clients[] = $row;
    }
}

// Get tasks counts by status for selected client
$task_counts = [
    'PENDING' => 0,
    'IN_PROGRESS' => 0,
    'VERIFICATION_COMPLETED' => 0,
    'COMPLETED' => 0,
    'ALL' => 0
];

if ($selected_client_id > 0) {
    // Check if case_tasks table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);
    
    if ($has_case_tasks) {
        // Get counts for each status
        $statuses = ['PENDING', 'IN_PROGRESS', 'VERIFICATION_COMPLETED', 'COMPLETED'];
        foreach ($statuses as $status) {
            if ($status == 'PENDING') {
                $count_sql = "
                    SELECT COUNT(*) as count
                    FROM case_tasks ct
                    INNER JOIN cases c ON ct.case_id = c.id
                    WHERE c.client_id = '$selected_client_id' 
                    AND ct.status = 'ACTIVE'
                    AND c.status != 'DELETED'
                    AND (ct.task_status = 'PENDING' OR ct.task_status IS NULL)
                ";
            } else {
                $count_sql = "
                    SELECT COUNT(*) as count
                    FROM case_tasks ct
                    INNER JOIN cases c ON ct.case_id = c.id
                    WHERE c.client_id = '$selected_client_id' 
                    AND ct.status = 'ACTIVE'
                    AND c.status != 'DELETED'
                    AND ct.task_status = '$status'
                ";
            }
            $count_res = mysqli_query($con, $count_sql);
            if ($count_res && $count_row = mysqli_fetch_assoc($count_res)) {
                $task_counts[$status] = (int)$count_row['count'];
            }
        }
        
        // Get total count
        $total_sql = "
            SELECT COUNT(*) as total
            FROM case_tasks ct
            INNER JOIN cases c ON ct.case_id = c.id
            WHERE c.client_id = '$selected_client_id' 
            AND ct.status = 'ACTIVE'
            AND c.status != 'DELETED'
        ";
        $total_res = mysqli_query($con, $total_sql);
        if ($total_res && $total_row = mysqli_fetch_assoc($total_res)) {
            $task_counts['ALL'] = (int)$total_row['total'];
        }
    }
}

// Get tasks for selected client and status
$tasks = [];
if ($selected_client_id > 0) {
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);
    
    if ($has_case_tasks) {
        $where_status = '';
        if ($selected_status != 'ALL') {
            if ($selected_status == 'PENDING') {
                $where_status = "AND (ct.task_status = 'PENDING' OR ct.task_status IS NULL)";
            } else {
                $where_status = "AND ct.task_status = '$selected_status'";
            }
        }
        
        $tasks_sql = "
            SELECT 
                ct.id,
                ct.case_id,
                ct.task_status,
                ct.task_name,
                ct.assigned_to,
                ct.created_at,
                ct.verified_at,
                c.application_no,
                c.client_id,
                cl.name as client_name,
                t.task_name as template_task_name
            FROM case_tasks ct
            INNER JOIN cases c ON ct.case_id = c.id
            INNER JOIN clients cl ON c.client_id = cl.id
            LEFT JOIN tasks t ON ct.task_template_id = t.id
            WHERE c.client_id = '$selected_client_id' 
            AND ct.status = 'ACTIVE'
            AND c.status != 'DELETED'
            $where_status
            ORDER BY ct.created_at DESC
        ";
        $tasks_res = mysqli_query($con, $tasks_sql);
        if ($tasks_res) {
            while ($row = mysqli_fetch_assoc($tasks_res)) {
                $tasks[] = $row;
            }
        }
    }
}

// Get verifier names - using verifier_name column
$verifiers = [];
$verifiers_sql = "SELECT id, verifier_name FROM verifier WHERE status = 'ACTIVE' ORDER BY verifier_name ASC";
$verifiers_res = mysqli_query($con, $verifiers_sql);
if ($verifiers_res) {
    while ($row = mysqli_fetch_assoc($verifiers_res)) {
        $verifiers[$row['id']] = $row['verifier_name'];
    }
}
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-tasks text-primary me-2"></i>
                    <strong>Client Wise Task Status</strong>
                </h4>
                <small class="text-muted">View and manage tasks by client</small>
            </div>
            <div>
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($selected_status); ?>">
                    <select name="client_id" id="clientSelect" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if ($selected_client_id > 0): ?>
            <!-- Status Tabs -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $selected_status == 'PENDING' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?client_id=<?php echo $selected_client_id; ?>&status=PENDING'">
                        <i class="fas fa-clock me-1"></i>Pending
                        <span class="badge bg-warning ms-2"><?php echo $task_counts['PENDING']; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $selected_status == 'IN_PROGRESS' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?client_id=<?php echo $selected_client_id; ?>&status=IN_PROGRESS'">
                        <i class="fas fa-spinner me-1"></i>In Progress
                        <span class="badge bg-info ms-2"><?php echo $task_counts['IN_PROGRESS']; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $selected_status == 'VERIFICATION_COMPLETED' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?client_id=<?php echo $selected_client_id; ?>&status=VERIFICATION_COMPLETED'">
                        <i class="fas fa-check-circle me-1"></i>Verified
                        <span class="badge bg-primary ms-2"><?php echo $task_counts['VERIFICATION_COMPLETED']; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $selected_status == 'COMPLETED' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?client_id=<?php echo $selected_client_id; ?>&status=COMPLETED'">
                        <i class="fas fa-clipboard-check me-1"></i>Completed
                        <span class="badge bg-success ms-2"><?php echo $task_counts['COMPLETED']; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $selected_status == 'ALL' ? 'active' : ''; ?>" 
                            onclick="window.location.href='?client_id=<?php echo $selected_client_id; ?>&status=ALL'">
                        <i class="fas fa-list me-1"></i>All Tasks
                        <span class="badge bg-secondary ms-2"><?php echo $task_counts['ALL']; ?></span>
                    </button>
                </li>
            </ul>

            <!-- Tasks List -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No tasks found for this status</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Application No</th>
                                        <th>Task Name</th>
                                        <th>Status</th>
                                        <th>Assigned To</th>
                                        <th>Created</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $index => $task): ?>
                                        <?php
                                        $task_status = $task['task_status'] ?? 'PENDING';
                                        $status_class = 'warning';
                                        if ($task_status == 'COMPLETED') $status_class = 'success';
                                        elseif ($task_status == 'VERIFICATION_COMPLETED') $status_class = 'primary';
                                        elseif ($task_status == 'IN_PROGRESS') $status_class = 'info';
                                        
                                        $assigned_name = '';
                                        if (!empty($task['assigned_to']) && isset($verifiers[$task['assigned_to']])) {
                                            $assigned_name = $verifiers[$task['assigned_to']];
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($task['application_no'] ?? 'N/A'); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($task['template_task_name'] ?? $task['task_name'] ?? 'Unknown Task'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($task_status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($assigned_name): ?>
                                                    <small><?php echo htmlspecialchars($assigned_name); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Not assigned</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo $task['created_at'] ? date('d M Y', strtotime($task['created_at'])) : 'N/A'; ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="view_case.php?case_id=<?php echo $task['case_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="View Case">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($task_status == 'IN_PROGRESS' || $task_status == 'VERIFICATION_COMPLETED'): ?>
                                                        <a href="task_verifier_submit.php?case_task_id=<?php echo $task['id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Verify">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($task_status == 'VERIFICATION_COMPLETED'): ?>
                                                        <a href="task_review.php?case_task_id=<?php echo $task['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning" title="Review">
                                                            <i class="fas fa-clipboard-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($task_status == 'COMPLETED'): ?>
                                                        <a href="task_review.php?case_task_id=<?php echo $task['id']; ?>" 
                                                           class="btn btn-sm btn-outline-success" title="View Review">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- No Client Selected -->
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Please select a client to view tasks</h5>
                    <p class="text-muted mb-0">Use the dropdown in the top right corner to select a client</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('../system/footer.php'); ?>

