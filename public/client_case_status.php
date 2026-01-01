<?php
/**
 * KPRM - Client Case Status Report
 * Display cases with client meta columns, aggregated tasks, and case status
 */

require_once('../system/all_header.php');

global $con;

// Get selected client and case status filter
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$selected_case_status = isset($_GET['case_status']) ? $_GET['case_status'] : 'ALL';

// Get all active clients
$clients = [];
$clients_sql = "SELECT id, name FROM clients WHERE status = 'ACTIVE' ORDER BY name ASC";
$clients_res = mysqli_query($con, $clients_sql);
if ($clients_res) {
    while ($row = mysqli_fetch_assoc($clients_res)) {
        $clients[] = $row;
    }
}

// Get client meta fields for selected client
$client_meta_fields = [];
$client_name = '';
if ($selected_client_id > 0) {
    // Get client name
    $client_sql = "SELECT name FROM clients WHERE id = '$selected_client_id' AND status = 'ACTIVE'";
    $client_res = mysqli_query($con, $client_sql);
    if ($client_res && $client_row = mysqli_fetch_assoc($client_res)) {
        $client_name = $client_row['name'];
    }
    
    // Get client meta fields
    $meta_sql = "
        SELECT field_name, display_name, input_type
        FROM clients_meta
        WHERE client_id = '$selected_client_id'
          AND status = 'ACTIVE'
          AND by_client = 'YES'
        ORDER BY id ASC
    ";
    $meta_res = mysqli_query($con, $meta_sql);
    if ($meta_res) {
        while ($row = mysqli_fetch_assoc($meta_res)) {
            $client_meta_fields[$row['field_name']] = $row['display_name'];
        }
    }
}

// Get cases data with aggregated tasks
$cases_data = [];
$case_status_counts = ['PENDING' => 0, 'IN_PROGRESS' => 0, 'CLOSED' => 0, 'ALL' => 0];

// Get current user info for role-based visibility
$current_user_id = $_SESSION['user_id'] ?? 0;
$current_user_type = $_SESSION['user_type'] ?? '';

if ($selected_client_id > 0) {
    // Check if case_tasks table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);
    
    // Get all cases for this client
    $cases_sql = "
        SELECT 
            c.id as case_id,
            c.application_no,
            c.created_at as case_created_at,
            c.case_status,
            c.case_info,
            cl.name as client_name
        FROM cases c
        INNER JOIN clients cl ON c.client_id = cl.id
        WHERE c.client_id = '$selected_client_id' 
        AND c.status != 'DELETED'
        ORDER BY c.id DESC
    ";
    
    $cases_res = mysqli_query($con, $cases_sql);
    if ($cases_res) {
        while ($case_row = mysqli_fetch_assoc($cases_res)) {
            $case_id = $case_row['case_id'];
            
            // Parse case_info JSON for client meta values
            $case_info_data = [];
            if (!empty($case_row['case_info'])) {
                $case_info_data = json_decode($case_row['case_info'], true);
                if (!is_array($case_info_data)) {
                    $case_info_data = [];
                }
            }
            
            // Get tasks for this case
            $tasks = [];
            
            if ($has_case_tasks) {
                $tasks_sql = "
                    SELECT 
                        ct.id as task_id,
                        ct.task_name,
                        ct.task_status,
                        ct.assigned_to,
                        ct.verified_at,
                        ct.reviewed_at,
                        ct.task_data,
                        t.task_name as template_task_name
                    FROM case_tasks ct
                    LEFT JOIN tasks t ON ct.task_template_id = t.id
                    WHERE ct.case_id = '$case_id'
                    AND ct.status = 'ACTIVE'
                    ORDER BY ct.id ASC
                ";
                $tasks_res = mysqli_query($con, $tasks_sql);
                if ($tasks_res) {
                    while ($task_row = mysqli_fetch_assoc($tasks_res)) {
                        $task_name = $task_row['template_task_name'] ?? $task_row['task_name'] ?? 'Unknown Task';
                        $task_status_db = $task_row['task_status'] ?? 'PENDING';
                        $task_data_json = json_decode($task_row['task_data'] ?? '{}', true);
                        $reviewed_at = $task_row['reviewed_at'];
                        
                        // Get display status using helper function
                        $display_status = get_task_status_display($task_status_db, $task_data_json);
                        
                        // Check role-based visibility
                        $task_for_check = [
                            'task_status' => $task_status_db,
                            'assigned_to' => $task_row['assigned_to'] ?? null
                        ];
                        
                        if (!can_user_view_task($task_for_check, $current_user_id, $current_user_type)) {
                            continue; // Skip tasks user cannot view
                        }
                        
                        $tasks[] = [
                            'id' => $task_row['task_id'],
                            'name' => $task_name,
                            'status' => $display_status,
                            'db_status' => $task_status_db,
                            'assigned_to' => $task_row['assigned_to'],
                            'reviewed_at' => $reviewed_at,
                            'task_data' => $task_data_json
                        ];
                    }
                }
            }
            
            // Calculate case status using helper function
            $case_status = calculate_case_status($tasks);
            
            // Apply case status filter
            if ($selected_case_status != 'ALL' && $case_status != $selected_case_status) {
                continue;
            }
            
            // Check role-based visibility for case
            if (!can_user_view_case(['id' => $case_id], $current_user_id, $current_user_type)) {
                continue;
            }
            
            // Count case statuses
            $case_status_counts[$case_status]++;
            $case_status_counts['ALL']++;
            
            // Prepare tasks grouped by display status
            $tasks_by_status = [
                'Pending' => [],
                'Assigned' => [],
                'Verified' => [],
                'Reviewed' => [],
                'Closed' => []
            ];
            
            foreach ($tasks as $task) {
                // Get display status using helper function
                $display_status = get_task_status_display($task['db_status'], $task['task_data'] ?? []);
                
                // Ensure status exists in array
                if (!isset($tasks_by_status[$display_status])) {
                    $display_status = 'Pending'; // Fallback
                }
                $tasks_by_status[$display_status][] = $task;
            }
            
            $cases_data[] = [
                'case_id' => $case_id,
                'application_no' => $case_row['application_no'] ?? 'N/A',
                'case_created_at' => $case_row['case_created_at'] ?? '',
                'case_status' => $case_status,
                'client_name' => $case_row['client_name'] ?? 'N/A',
                'case_info' => $case_info_data,
                'tasks' => $tasks,
                'tasks_by_status' => $tasks_by_status
            ];
        }
    }
}
?>

<style>
    .column-toggle {
        cursor: pointer;
        user-select: none;
    }
    .column-toggle:hover {
        background-color: #f8f9fa;
    }
    .task-status-badge {
        font-size: 0.75rem;
        margin: 2px;
        display: inline-block;
    }
    .task-status-link {
        text-decoration: none;
        margin-right: 5px;
    }
    .case-status-pending {
        color: #ffc107;
    }
    .case-status-completed {
        color: #198754;
    }
    #columnToggleMenu {
        max-height: 400px;
        overflow-y: auto;
    }
</style>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-list-alt text-primary me-2"></i>
                    <strong>Client Case Status Report</strong>
                </h4>
                <small class="text-muted">View cases with client meta columns and task statistics</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="columnToggleBtn" data-bs-toggle="dropdown">
                        <i class="fas fa-columns me-1"></i> Show/Hide Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="columnToggleMenu" aria-labelledby="columnToggleBtn">
                        <li class="dropdown-header">Toggle Column Visibility</li>
                        <li><hr class="dropdown-divider"></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-case-id" checked>
                            Case ID
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-application-no" checked>
                            Application No
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-case-status" checked>
                            Case Status
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-case-created" checked>
                            Case Created
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-task-pending" checked>
                            Pending Tasks
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-task-assigned" checked>
                            Assigned Tasks
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-task-verified" checked>
                            Verified Tasks
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-task-reviewed" checked>
                            Reviewed Tasks
                        </label></li>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-task-closed" checked>
                            Closed Tasks
                        </label></li>
                        <?php foreach ($client_meta_fields as $field_name => $display_name): ?>
                        <li><label class="dropdown-item column-toggle">
                            <input type="checkbox" class="form-check-input me-2 column-toggle-checkbox" data-column="col-meta-<?php echo htmlspecialchars($field_name); ?>" checked>
                            <?php echo htmlspecialchars($display_name); ?>
                        </label></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <form method="GET" action="" class="d-flex align-items-center gap-2" id="filterForm">
                    <select name="case_status" id="caseStatusSelect" class="form-select form-select-sm" style="min-width: 150px;" <?php echo $selected_client_id == 0 ? 'disabled' : ''; ?>>
                        <option value="ALL" <?php echo $selected_case_status == 'ALL' ? 'selected' : ''; ?>>All Cases</option>
                        <option value="PENDING" <?php echo $selected_case_status == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                        <option value="IN_PROGRESS" <?php echo $selected_case_status == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="CLOSED" <?php echo $selected_case_status == 'CLOSED' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                    <input type="hidden" name="client_id" id="filterClientId" value="<?php echo $selected_client_id; ?>">
                    <button type="submit" class="btn btn-sm btn-primary" <?php echo $selected_client_id == 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </form>
                <form method="GET" action="" class="d-flex align-items-center gap-2" id="clientForm">
                    <select name="client_id" id="clientSelect" class="form-select form-select-sm" style="min-width: 200px;" onchange="updateFilterAndSubmit()">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_case_status != 'ALL'): ?>
                        <input type="hidden" name="case_status" value="<?php echo htmlspecialchars($selected_case_status); ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($selected_client_id > 0): ?>
            <!-- Statistics Cards -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Cases</h6>
                            <h3 class="mb-0"><?php echo $case_status_counts['ALL']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-warning">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Pending Cases</h6>
                            <h3 class="mb-0 text-warning"><?php echo $case_status_counts['PENDING']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-info">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">In Progress</h6>
                            <h3 class="mb-0 text-info"><?php echo $case_status_counts['IN_PROGRESS']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-success">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Closed Cases</h6>
                            <h3 class="mb-0 text-success"><?php echo $case_status_counts['CLOSED']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0">
                                <i class="fas fa-building text-primary me-2"></i>
                                <strong>Client:</strong> <?php echo htmlspecialchars($client_name); ?>
                            </h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info">
                                <i class="fas fa-list me-1"></i>
                                Showing <?php echo count($cases_data); ?> case(s)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cases Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-table text-primary me-2"></i>Cases Data
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($cases_data)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No cases found<?php 
                                if ($selected_case_status != 'ALL') {
                                    $filter_display = ($selected_case_status == 'COMPLETED') ? 'CLOSED' : $selected_case_status;
                                    echo ' for ' . htmlspecialchars($filter_display) . ' status';
                                }
                            ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm mb-0" id="casesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="col-case-id">Case ID</th>
                                        <th class="col-application-no">Application No</th>
                                        <th class="col-case-status">Case Status</th>
                                        <th class="col-case-created">Case Created</th>
                                        <th class="col-task-pending">Pending</th>
                                        <th class="col-task-assigned">Assigned</th>
                                        <th class="col-task-verified">Verified</th>
                                        <th class="col-task-reviewed">Reviewed</th>
                                        <th class="col-task-closed">Closed</th>
                                        <?php foreach ($client_meta_fields as $field_name => $display_name): ?>
                                            <th class="col-meta-<?php echo htmlspecialchars($field_name); ?>"><?php echo htmlspecialchars($display_name); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cases_data as $case_data): ?>
                                        <tr>
                                            <td class="col-case-id">
                                                <a href="view_case.php?case_id=<?php echo $case_data['case_id']; ?>" class="text-decoration-none">
                                                    <?php echo $case_data['case_id']; ?>
                                                </a>
                                            </td>
                                            <td class="col-application-no">
                                                <strong><?php echo htmlspecialchars($case_data['application_no']); ?></strong>
                                            </td>
                                            <td class="col-case-status">
                                                <?php
                                                $case_stat = $case_data['case_status'];
                                                if ($case_stat == 'CLOSED') {
                                                    $status_class = 'success';
                                                    $status_icon = 'check-circle';
                                                } elseif ($case_stat == 'IN_PROGRESS') {
                                                    $status_class = 'info';
                                                    $status_icon = 'spinner';
                                                } else {
                                                    $status_class = 'warning';
                                                    $status_icon = 'clock';
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($case_stat); ?>
                                                </span>
                                            </td>
                                            <td class="col-case-created">
                                                <small><?php echo $case_data['case_created_at'] ? date('d M Y', strtotime($case_data['case_created_at'])) : '-'; ?></small>
                                            </td>
                                            <?php
                                            // Display tasks in respective status columns
                                            $task_status_columns = ['Pending', 'Assigned', 'Verified', 'Reviewed', 'Closed'];
                                            foreach ($task_status_columns as $status_col):
                                                $tasks_in_status = $case_data['tasks_by_status'][$status_col] ?? [];
                                                $column_class = 'col-task-' . strtolower($status_col);
                                            ?>
                                                <td class="<?php echo $column_class; ?>">
                                                    <?php if (count($tasks_in_status) > 0): ?>
                                                        <?php foreach ($tasks_in_status as $idx => $task): ?>
                                                            <?php if ($idx > 0): ?><br><?php endif; ?>
                                                            <?php
                                                            // Get task URL using helper function
                                                            $task_url = get_task_action_url($task, $case_data['case_id'], $current_user_type);
                                                            
                                                            // Check if user can view this task
                                                            $task_for_check = [
                                                                'task_status' => $task['db_status'],
                                                                'assigned_to' => $task['assigned_to'] ?? null
                                                            ];
                                                            
                                                            if (!can_user_view_task($task_for_check, $current_user_id, $current_user_type)) {
                                                                continue; // Skip if user cannot view
                                                            }
                                                            
                                                            // Badge color based on status
                                                            $badge_colors = [
                                                                'Pending' => 'secondary',
                                                                'Assigned' => 'info',
                                                                'Verified' => 'primary',
                                                                'Reviewed' => 'warning',
                                                                'Closed' => 'success'
                                                            ];
                                                            $badge_color = $badge_colors[$status_col] ?? 'secondary';
                                                            ?>
                                                            <a href="<?php echo $task_url; ?>" 
                                                               class="task-link d-inline-block mb-1 text-decoration-none" 
                                                               title="<?php echo htmlspecialchars($task['name']); ?>">
                                                                <small class="badge bg-<?php echo $badge_color; ?>">
                                                                    <?php echo htmlspecialchars($task['name']); ?>
                                                                </small>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <?php foreach ($client_meta_fields as $field_name => $display_name): ?>
                                                <td class="col-meta-<?php echo htmlspecialchars($field_name); ?>">
                                                    <small>
                                                        <?php
                                                        $value = isset($case_data['case_info'][$field_name]) ? $case_data['case_info'][$field_name] : '';
                                                        echo htmlspecialchars($value ?: '-');
                                                        ?>
                                                    </small>
                                                </td>
                                            <?php endforeach; ?>
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
                    <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Please select a client to view case status</h5>
                    <p class="text-muted mb-0">Use the dropdown in the top right corner to select a client</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('../system/footer.php'); ?>

<script>
// Update filter form when client changes
function updateFilterAndSubmit() {
    const clientSelect = document.getElementById('clientSelect');
    const filterClientId = document.getElementById('filterClientId');
    const selectedClientId = clientSelect.value;
    
    // Update hidden input in filter form
    if (filterClientId) {
        filterClientId.value = selectedClientId;
    }
    
    // Enable/disable filter based on client selection
    const caseStatusSelect = document.getElementById('caseStatusSelect');
    const filterBtn = document.querySelector('#filterForm button[type="submit"]');
    if (caseStatusSelect && filterBtn) {
        if (selectedClientId == 0 || selectedClientId == '') {
            caseStatusSelect.disabled = true;
            filterBtn.disabled = true;
        } else {
            caseStatusSelect.disabled = false;
            filterBtn.disabled = false;
        }
    }
    
    // Submit client form
    document.getElementById('clientForm').submit();
}

// Column visibility toggle
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter state
    const selectedClientId = <?php echo $selected_client_id; ?>;
    const caseStatusSelect = document.getElementById('caseStatusSelect');
    const filterBtn = document.querySelector('#filterForm button[type="submit"]');
    if (caseStatusSelect && filterBtn) {
        if (selectedClientId == 0) {
            caseStatusSelect.disabled = true;
            filterBtn.disabled = true;
        }
    }
    
    const checkboxes = document.querySelectorAll('.column-toggle-checkbox');
    const table = document.getElementById('casesTable');
    
    if (!table) return;
    
    // Load saved column visibility preferences
    const savedVisibility = localStorage.getItem('columnVisibility');
    if (savedVisibility) {
        try {
            const visibility = JSON.parse(savedVisibility);
            checkboxes.forEach(checkbox => {
                const column = checkbox.getAttribute('data-column');
                if (visibility.hasOwnProperty(column)) {
                    checkbox.checked = visibility[column];
                    toggleColumn(column, visibility[column]);
                }
            });
        } catch (e) {
            console.error('Error loading column visibility:', e);
        }
    }
    
    // Handle checkbox changes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const column = this.getAttribute('data-column');
            const isVisible = this.checked;
            toggleColumn(column, isVisible);
            saveColumnVisibility();
        });
    });
    
    function toggleColumn(column, isVisible) {
        const headers = table.querySelectorAll('thead th.' + column);
        const cells = table.querySelectorAll('tbody td.' + column);
        
        headers.forEach(header => {
            header.style.display = isVisible ? '' : 'none';
        });
        
        cells.forEach(cell => {
            cell.style.display = isVisible ? '' : 'none';
        });
    }
    
    function saveColumnVisibility() {
        const visibility = {};
        checkboxes.forEach(checkbox => {
            const column = checkbox.getAttribute('data-column');
            visibility[column] = checkbox.checked;
        });
        localStorage.setItem('columnVisibility', JSON.stringify(visibility));
    }
    
    // Prevent dropdown from closing when clicking inside
    document.getElementById('columnToggleMenu').addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

</body>
</html>

