<?php 
/**
 * KPRM - Case Management
 * List and manage all cases
 */

require_once("../system/all_header.php"); 

$table_name = "cases";
global $con;

// Check if case_info, case_status columns and case_tasks table exist
$has_case_info = false;
$has_case_status = false;
$has_case_tasks = false;

try {
    $check_cols = mysqli_query($con, "SHOW COLUMNS FROM cases LIKE 'case_info'");
    $has_case_info = ($check_cols && mysqli_num_rows($check_cols) > 0);
    
    $check_cols2 = mysqli_query($con, "SHOW COLUMNS FROM cases LIKE 'case_status'");
    $has_case_status = ($check_cols2 && mysqli_num_rows($check_cols2) > 0);
    
    $check_table = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($check_table && mysqli_num_rows($check_table) > 0);
} catch (Exception $e) {
    // If checks fail, use safe defaults
}

// Get Case Statistics
if ($has_case_status) {
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN case_status = 'ACTIVE' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN case_status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN case_status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN case_status = 'ON_HOLD' THEN 1 ELSE 0 END) as on_hold
        FROM cases WHERE status != 'DELETED'";
} else {
    // If case_status column doesn't exist, count all as active
    $stats_sql = "SELECT 
        COUNT(*) as total,
        COUNT(*) as active,
        0 as pending,
        0 as completed,
        0 as on_hold
        FROM cases WHERE status != 'DELETED'";
}
$stats_res = mysqli_query($con, $stats_sql);
$stats = ['total' => 0, 'active' => 0, 'pending' => 0, 'completed' => 0, 'on_hold' => 0];
if ($stats_res) {
    $stats_row = mysqli_fetch_assoc($stats_res);
    $stats = [
        'total' => (int)$stats_row['total'],
        'active' => (int)$stats_row['active'],
        'pending' => (int)$stats_row['pending'],
        'completed' => (int)$stats_row['completed'],
        'on_hold' => (int)$stats_row['on_hold']
    ];
}

// Build SQL query based on available columns/tables
$case_info_col = $has_case_info ? 'c.case_info' : "'' as case_info";
$case_status_col = $has_case_status ? 'c.case_status' : "'ACTIVE' as case_status";
$task_count_col = $has_case_tasks ? 'COUNT(ct.id) as task_count' : '0 as task_count';
$case_tasks_join = $has_case_tasks ? 'LEFT JOIN case_tasks ct ON c.id = ct.case_id AND ct.status = \'ACTIVE\'' : '';
$group_by = $has_case_tasks ? 'GROUP BY c.id' : '';

$sql = "
    SELECT 
        c.id,
        c.status,
        c.created_at,
        c.created_by,
        c.updated_at,
        c.updated_by,
        c.client_id,
        c.application_no,
        {$case_info_col},
        {$case_status_col},
        cl.name as client_name,
        '' as client_code,
        {$task_count_col}
    FROM cases c
    LEFT JOIN clients cl ON c.client_id = cl.id
    {$case_tasks_join}
    WHERE c.status != 'DELETED'
    {$group_by}
    ORDER BY c.created_at DESC
";

// Execute query with error handling
$res = direct_sql($sql);

// Handle query response
if (!isset($res) || !is_array($res)) {
    $res = ['count' => 0, 'data' => [], 'status' => 'error', 'message' => 'Query failed'];
} elseif (isset($res['status']) && $res['status'] == 'error') {
    $res['message'] = $res['message'] ?? 'SQL query failed';
} elseif (!isset($res['count'])) {
    $res['count'] = isset($res['data']) && is_array($res['data']) ? count($res['data']) : 0;
}

$case_count = isset($res['count']) ? intval($res['count']) : 0;
$case_data = isset($res['data']) && is_array($res['data']) ? $res['data'] : [];

// If status is 'error' but count is 0 and we have valid structure, treat as success (no results)
if (isset($res['status']) && $res['status'] == 'error' && $case_count == 0 && isset($res['data'])) {
    $res['status'] = 'success';
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

// Show error only if it's a real error
if (isset($res['status']) && $res['status'] == 'error' && $case_count > 0) {
    echo '<div class="alert alert-danger shadow-sm">';
    echo '<strong><i class="fas fa-exclamation-triangle me-2"></i>Database Error:</strong> ' . htmlspecialchars($res['message'] ?? 'Unknown error');
    echo '</div>';
}
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-folder-open text-primary me-2"></i> Case Management
                </h4>
                <p class="text-muted small mb-0">Manage and track all your cases</p>
            </div>
            <a href="add_new_case.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Add New Case
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Total Cases</div>
                                <div class="h5 mb-0 fw-bold"><?php echo $stats['total']; ?></div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-folder fa-2x opacity-50"></i>
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
                                <div class="text-muted small mb-1">Active</div>
                                <div class="h5 mb-0 fw-bold text-success"><?php echo $stats['active']; ?></div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
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
                                <div class="h5 mb-0 fw-bold text-warning"><?php echo $stats['pending']; ?></div>
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
                                <div class="text-muted small mb-1">Completed</div>
                                <div class="h5 mb-0 fw-bold text-info"><?php echo $stats['completed']; ?></div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-check-double fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-secondary border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">On Hold</div>
                                <div class="h5 mb-0 fw-bold text-secondary"><?php echo $stats['on_hold']; ?></div>
                            </div>
                            <div class="text-secondary">
                                <i class="fas fa-pause fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Total Tasks</div>
                                <div class="h5 mb-0 fw-bold text-primary">
                                    <?php 
                                    if ($has_case_tasks) {
                                        $total_tasks_sql = "SELECT COUNT(*) as total FROM case_tasks WHERE status = 'ACTIVE'";
                                        $total_tasks_res = mysqli_query($con, $total_tasks_sql);
                                        echo $total_tasks_res ? (int)mysqli_fetch_assoc($total_tasks_res)['total'] : '0';
                                    } else {
                                        echo '0';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-tasks fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cases Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-list me-2 text-primary"></i> All Cases
                        <span class="badge bg-secondary ms-2"><?php echo $case_count; ?></span>
                    </h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-warning" title="Select All">
                            <input type="checkbox" id="selectAll" class="form-check-input me-1">
                            <small>Select All</small>
                        </button>
                        <?php echo btn_delete_multiple($table_name); ?>
                        <!-- <button class="btn btn-sm btn-outline-primary" title="Show/Hide Columns" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight">
                            <i class="fas fa-columns"></i>
                        </button> -->
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($case_count > 0 && !empty($case_data)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="casesTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">
                                    <input type="checkbox" id="selectAllTable" title="Select All">
                                </th>
                                <th>ID</th>
                                <th>Application No</th>
                                <th>Client</th>
                                <th>Case Status</th>
                                <th>Key Information</th>
                                <th class="text-center">Tasks</th>
                                <th>Created</th>
                                <th width="180" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($case_data as $row): 
                                $case_id = $row['id'];
                                $client_id = $row['client_id'];
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="chk" name="ids[]" value="<?php echo $case_id; ?>">
                                    </td>
                                    <td>
                                        <span class="fw-bold text-muted">#<?php echo $case_id; ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($row['application_no'] ?: 'N/A'); ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i class="fas fa-user-circle text-muted"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['client_name'] ?: 'Unknown Client'); ?></div>
                                                <?php if ($row['client_code']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['client_code']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $case_status = $row['case_status'] ?? 'ACTIVE';
                                        $status_config = [
                                            'ACTIVE' => ['color' => 'success', 'icon' => 'check-circle'],
                                            'PENDING' => ['color' => 'warning', 'icon' => 'clock'],
                                            'COMPLETED' => ['color' => 'info', 'icon' => 'check-double'],
                                            'ON_HOLD' => ['color' => 'secondary', 'icon' => 'pause']
                                        ];
                                        $status_info = $status_config[$case_status] ?? ['color' => 'secondary', 'icon' => 'circle'];
                                        ?>
                                        <span class="badge bg-<?php echo $status_info['color']; ?>">
                                            <i class="fas fa-<?php echo $status_info['icon']; ?> me-1"></i>
                                            <?php echo htmlspecialchars($case_status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if (!empty($row['case_info'])) {
                                            $case_info = json_decode($row['case_info'], true);
                                            if (is_array($case_info) && !empty($case_info)) {
                                                // Get important fields (region, branch, product, etc.)
                                                $key_fields = ['region', 'branch', 'product', 'state', 'location'];
                                                $display_items = [];
                                                foreach ($key_fields as $field) {
                                                    if (isset($case_info[$field]) && !empty($case_info[$field])) {
                                                        $display_items[] = '<span class="badge bg-light text-dark me-1"><small>' . htmlspecialchars(ucfirst($field)) . ': ' . htmlspecialchars(substr($case_info[$field], 0, 20)) . '</small></span>';
                                                        if (count($display_items) >= 2) break;
                                                    }
                                                }
                                                if (empty($display_items)) {
                                                    // Fallback: show first 2 non-empty values
                                                    $count = 0;
                                                    foreach ($case_info as $key => $val) {
                                                        if (!empty($val) && $count < 2) {
                                                            $display_items[] = '<span class="badge bg-light text-dark me-1"><small>' . htmlspecialchars(ucfirst($key)) . ': ' . htmlspecialchars(substr($val, 0, 20)) . '</small></span>';
                                                            $count++;
                                                        }
                                                    }
                                                }
                                                echo !empty($display_items) ? implode('', $display_items) : '<span class="text-muted small">Details available</span>';
                                            } else {
                                                echo '<span class="text-muted small">No info</span>';
                                            }
                                        } else {
                                            echo '<span class="text-muted small">No info</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $task_count = (int)($row['task_count'] ?? 0);
                                        $task_badge_class = $task_count > 0 ? 'bg-primary' : 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $task_badge_class; ?>">
                                            <i class="fas fa-tasks me-1"></i><?php echo $task_count; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            if (!empty($row['created_at'])) {
                                                $created = new DateTime($row['created_at']);
                                                $now = new DateTime();
                                                $diff = $now->diff($created);
                                                
                                                if ($diff->days == 0) {
                                                    echo 'Today, ' . $created->format('h:i A');
                                                } elseif ($diff->days == 1) {
                                                    echo 'Yesterday, ' . $created->format('h:i A');
                                                } elseif ($diff->days < 7) {
                                                    echo $diff->days . ' days ago';
                                                } else {
                                                    echo $created->format('d M Y');
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-sm btn-info" title="View Case">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="add_new_case.php?step=2&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-warning" title="Edit Case Info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary" title="Manage Tasks">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                            <!-- <button type="button" class="btn btn-sm btn-danger" onclick="deleteCase(<?php echo $case_id; ?>)" title="Delete Case">
                                                <i class="fas fa-trash"></i>
                                            </button> -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-folder-open fa-4x text-muted opacity-50"></i>
                    </div>
                    <h5 class="text-muted mb-2">No Cases Found</h5>
                    <p class="text-muted small mb-4">Get started by creating your first case</p>
                    <a href="add_new_case.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Create Your First Case
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    // Select all checkbox
    $("#selectAll, #selectAllTable").change(function() {
        $(".chk").prop("checked", $(this).prop('checked'));
    });
    
    // Initialize DataTable if available
    if ($.fn.DataTable) {
        $('#casesTable').DataTable({
            "pageLength": 25,
            "order": [[7, "desc"]], // Sort by Created At descending
            "columnDefs": [
                { "orderable": false, "targets": [0, 8] }, // Disable sorting on checkbox and actions columns
                { "type": "num", "targets": [1] } // ID column
            ],
            "language": {
                "search": "Search cases:",
                "lengthMenu": "Show _MENU_ cases per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ cases",
                "infoEmpty": "No cases to show",
                "infoFiltered": "(filtered from _MAX_ total cases)"
            }
        });
    }
});

function deleteCase(caseId) {
    if (confirm('Are you sure you want to delete this case? This action cannot be undone.')) {
        $.ajax({
            url: 'save_case_step.php',
            type: 'POST',
            data: {
                action: 'delete_case',
                case_id: caseId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to delete case'));
                }
            },
            error: function() {
                alert('Error deleting case. Please try again.');
            }
        });
    }
}
</script>

<?php 
require_once("../system/footer.php"); 
?>
