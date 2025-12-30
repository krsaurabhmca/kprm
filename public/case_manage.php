<?php 
/**
 * KPRM - Case Management
 * List and manage all cases
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../system/all_header.php"); 

$table_name = "cases";

// Get all cases with client information
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

// Debug output (remove in production)
error_log("Case Manage - SQL: " . $sql);
error_log("Case Manage - Result: " . print_r($res, true));

// Debug: Check if query failed
if (!isset($res) || !is_array($res)) {
    $res = ['count' => 0, 'data' => [], 'status' => 'error', 'message' => 'Query failed - direct_sql returned invalid result'];
} elseif (isset($res['status']) && $res['status'] == 'error') {
    // Query returned error status
    $res['message'] = $res['message'] ?? 'SQL query failed';
} elseif (!isset($res['count'])) {
    // Missing count field
    $res['count'] = isset($res['data']) && is_array($res['data']) ? count($res['data']) : 0;
}

// Debug: Log the result
error_log("Case Manage - Final count: " . (isset($res['count']) ? $res['count'] : 'NOT SET'));
error_log("Case Manage - Has data: " . (isset($res['data']) && is_array($res['data']) ? 'YES (' . count($res['data']) . ')' : 'NO'));

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

<main class="content">
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3">
            <i class="fas fa-folder-open"></i> Case Management
        </h1>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            All Cases
                            <a href="add_new_case.php" class="btn btn-primary btn-sm ms-2">
                                <i class="fas fa-plus"></i> Add New Case
                            </a>
                            
                            <span class="float-end">
                                <div class="float-end">
                                    <button class="btn btn-warning btn-sm"> 
                                        <input type="checkbox" title="Select All" id="selectAll" class="btn btn-dark btn-sm"> 
                                    </button>
                                    <?php echo btn_delete_multiple($table_name); ?>
                                    
                                    <button class="btn btn-primary btn-sm my-1" title="Show /Hide Columns" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                                        <i class="fa fa-columns"></i>
                                    </button>
                                </div>
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Debug info (temporary)
                        echo '<!-- DEBUG: has_case_info=' . ($has_case_info ? 'YES' : 'NO') . ', has_case_status=' . ($has_case_status ? 'YES' : 'NO') . ', has_case_tasks=' . ($has_case_tasks ? 'YES' : 'NO') . ' -->';
                        echo '<!-- DEBUG: SQL=' . htmlspecialchars($sql) . ' -->';
                        echo '<!-- DEBUG: Result count=' . (isset($res['count']) ? $res['count'] : 'NOT SET') . ' -->';
                        echo '<!-- DEBUG: Result status=' . (isset($res['status']) ? $res['status'] : 'NOT SET') . ' -->';
                        
                        // Handle direct_sql response - it returns 'error' status when count is 0, which is not really an error
                        // Check if we have valid data structure
                        $case_count = isset($res['count']) ? intval($res['count']) : 0;
                        $case_data = isset($res['data']) && is_array($res['data']) ? $res['data'] : [];
                        
                        // If status is 'error' but count is 0 and we have valid structure, treat as success (no results)
                        if (isset($res['status']) && $res['status'] == 'error' && $case_count == 0 && isset($res['data'])) {
                            // This is not an error, just no results
                            $res['status'] = 'success';
                        }
                        
                        // Show error only if it's a real error (not just no results)
                        if (isset($res['status']) && $res['status'] == 'error' && $case_count > 0) {
                            echo '<div class="alert alert-danger">';
                            echo '<strong>Database Error:</strong> ' . htmlspecialchars($res['message'] ?? 'Unknown error');
                            if (isset($sql)) {
                                echo '<br><small>SQL: ' . htmlspecialchars($sql) . '</small>';
                            }
                            if (isset($res['sql'])) {
                                echo '<br><small>Executed SQL: ' . htmlspecialchars($res['sql']) . '</small>';
                            }
                            echo '</div>';
                        }
                        
                        if ($case_count > 0 && !empty($case_data)) {
                            ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="casesTable">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="selectAllTable" title="Select All">
                                            </th>
                                            <th>ID</th>
                                            <th>Application No</th>
                                            <th>Client</th>
                                            <th>Case Status</th>
                                            <th>Case Info</th>
                                            <th>Tasks</th>
                                            <!-- <th>Status</th> -->
                                            <!-- <th>Created At</th> -->
                                            <!-- <th>Updated At</th> -->
                                            <th width="150">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($case_data as $row) {
                                            $case_id = $row['id'];
                                            $client_id = $row['client_id'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="chk" name="ids[]" value="<?php echo $case_id; ?>">
                                                </td>
                                                <td><?php echo $case_id; ?></td>
                                                <td><?php echo $row['application_no'] ?: '<span class="text-muted">N/A</span>'; ?></td>
                                                <td>
                                                    <strong><?php echo $row['client_name'] ?: '<span class="text-muted">Unknown Client</span>'; ?></strong>
                                                    <?php if ($row['client_code']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($row['client_code']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $case_status = $row['case_status'] ?? 'ACTIVE';
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
                                                <td>
                                                    <?php
                                                    if (!empty($row['case_info'])) {
                                                        $case_info = json_decode($row['case_info'], true);
                                                        if (is_array($case_info) && !empty($case_info)) {
                                                            echo '<div class="text-truncate" style="max-width: 200px;" title="' . htmlspecialchars(json_encode($case_info, JSON_PRETTY_PRINT)) . '">';
                                                            $info_items = [];
                                                            foreach (array_slice($case_info, 0, 3) as $key => $val) {
                                                                if (!empty($val)) {
                                                                    $info_items[] = '<strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars(substr($val, 0, 30));
                                                                }
                                                            }
                                                            echo implode('<br>', $info_items);
                                                            if (count($case_info) > 3) {
                                                                echo '<br><small class="text-muted">+' . (count($case_info) - 3) . ' more</small>';
                                                            }
                                                            echo '</div>';
                                                        } else {
                                                            echo '<span class="text-muted">No info</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">No info</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo $row['task_count']; ?> task(s)</span></td>
                                                <!-- <td>
                                                    <span class="badge bg-<?php echo ($row['status'] == 'ACTIVE' ? 'success' : 'secondary'); ?>">
                                                        <?php echo htmlspecialchars($row['status']); ?>
                                                    </span>
                                                </td> -->
                                                <!-- <td><?php echo $row['created_at'] ? date('d M Y, h:i A', strtotime($row['created_at'])) : 'N/A'; ?></td> -->
                                                <!-- <td><?php echo $row['updated_at'] ? date('d M Y, h:i A', strtotime($row['updated_at'])) : 'N/A'; ?></td> -->
                                                <td>
                                                    <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-info btn-sm" title="View Case">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="add_new_case.php?step=2&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-warning btn-sm" title="Edit Case Info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>&client_id=<?php echo $client_id; ?>" class="btn btn-primary btn-sm" title="Edit Tasks">
                                                        <i class="fas fa-tasks"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteCase(<?php echo $case_id; ?>)" title="Delete Case">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No cases found. 
                                <a href="add_new_case.php" class="alert-link">Create your first case</a>.
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
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
            "order": [[8, "desc"]], // Sort by Created At descending
            "columnDefs": [
                { "orderable": false, "targets": [0, 10] } // Disable sorting on checkbox and actions columns
            ]
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
