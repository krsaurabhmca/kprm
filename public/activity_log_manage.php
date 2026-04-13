<?php
/**
 * KPRM - Activity Log Management
 * Internal system audit log viewer and management
 * Tracks all user activities, system changes, and requests
 */

require_once('../system/all_header.php');

// Debug: Test if table exists and has data (add ?debug=1 to URL)
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    $test_query = "SELECT COUNT(*) as total FROM activity_log";
    $test_result = mysqli_query($con, $test_query);
    if ($test_result) {
        $test_row = mysqli_fetch_assoc($test_result);
        echo "<pre style='padding:20px; background:#f5f5f5;'>";
        echo "=== ACTIVITY LOG DEBUG ===\n\n";
        echo "Total logs in database: " . $test_row['total'] . "\n\n";
        $sample_query = "SELECT id, user_id, task_name, date_time, status FROM activity_log ORDER BY id DESC LIMIT 5";
        $sample_result = mysqli_query($con, $sample_query);
        if ($sample_result && mysqli_num_rows($sample_result) > 0) {
            echo "Sample records (last 5):\n";
            while ($sample = mysqli_fetch_assoc($sample_result)) {
                print_r($sample);
                echo "\n";
            }
        } else {
            echo "No records found in activity_log table.\n";
        }
        echo "</pre>";
        exit;
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"');
    
    // Output UTF-8 BOM for Excel compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, ['ID', 'Date & Time', 'User ID', 'User Name', 'Task Name', 'IP Address', 'Status', 'Request Data', 'Created At']);
    
    // Build same query as main page
    $where_conditions = [];
    
    $filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $filter_task = isset($_GET['task_name']) ? trim($_GET['task_name']) : '';
    $filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $filter_ip = isset($_GET['ip_address']) ? trim($_GET['ip_address']) : '';
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
    
    if ($filter_status != '') {
        $status_escaped = mysqli_real_escape_string($con, $filter_status);
        $where_conditions[] = "al.status = '$status_escaped'";
    } else {
        // Show ACTIVE and NULL status by default (NULL for old records)
        $where_conditions[] = "(al.status = 'ACTIVE' OR al.status IS NULL)";
    }
    
    if ($filter_user > 0) {
        // user_id is varchar, so handle as string
        $user_escaped = mysqli_real_escape_string($con, $filter_user);
        $where_conditions[] = "al.user_id = '$user_escaped'";
    }
    
    if ($filter_task != '') {
        $task_escaped = mysqli_real_escape_string($con, $filter_task);
        $where_conditions[] = "al.task_name = '$task_escaped'";
    }
    
    if (!empty($filter_date_from)) {
        $date_from_escaped = mysqli_real_escape_string($con, $filter_date_from);
        $where_conditions[] = "DATE(al.date_time) >= '$date_from_escaped'";
    }
    
    if (!empty($filter_date_to)) {
        $date_to_escaped = mysqli_real_escape_string($con, $filter_date_to);
        $where_conditions[] = "DATE(al.date_time) <= '$date_to_escaped'";
    }
    
    if (!empty($filter_ip)) {
        $ip_escaped = mysqli_real_escape_string($con, $filter_ip);
        $where_conditions[] = "al.ip_address LIKE '%$ip_escaped%'";
    }
    
    if (!empty($search_query)) {
        $search_escaped = mysqli_real_escape_string($con, $search_query);
        $where_conditions[] = "(
            al.task_name LIKE '%$search_escaped%' OR
            al.user_id LIKE '%$search_escaped%' OR
            al.ip_address LIKE '%$search_escaped%' OR
            al.request_data LIKE '%$search_escaped%' OR
            u.name LIKE '%$search_escaped%' OR
            u.user_name LIKE '%$search_escaped%'
        )";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Fix user_id join - user_id is VARCHAR, so convert to INT for join
    $query = "SELECT al.*, 
             u.name as user_name, 
             u.user_name as user_login
             FROM activity_log al
             LEFT JOIN op_user u ON CAST(al.user_id AS UNSIGNED) = u.id
             $where_clause
             ORDER BY al.date_time DESC, al.id DESC
             LIMIT 10000";
    
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $user_display = $row['user_name'] ?? $row['user_login'] ?? 'User ID: ' . ($row['user_id'] ?? 'N/A');
            $request_data = $row['request_data'] ?? '';
            
            fputcsv($output, [
                $row['id'],
                $row['date_time'] ?? 'N/A',
                $row['user_id'] ?? 'N/A',
                $user_display,
                $row['task_name'] ?? 'N/A',
                $row['ip_address'] ?? 'N/A',
                $row['status'] ?? 'ACTIVE',
                $request_data,
                $row['created_at'] ?? 'N/A'
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Get filters
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_task = isset($_GET['task_name']) ? trim($_GET['task_name']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$filter_ip = isset($_GET['ip_address']) ? trim($_GET['ip_address']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

$page_title = "Activity Log Management";
?>
<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-history text-primary me-2"></i>
                    <strong><?php echo $page_title; ?></strong>
                </h4>
                <small class="text-muted">Internal System Audit Trail</small>
            </div>
            <div class="btn-group">
                <a href="?debug=1" class="btn btn-sm btn-outline-warning" target="_blank" title="Debug Info">
                    <i class="fas fa-bug me-1"></i> Debug
                </a>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="exportLogs()">
                    <i class="fas fa-download me-1"></i> Export Logs
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filters & Search
                </h6>
            </div>
            <div class="card-body p-3">
                <form method="GET" action="" id="filterForm">
                    <!-- Search Bar -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Search by task name, user, IP address, or request data..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Row 1 -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">User</label>
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="0">All Users</option>
                                <?php
                                $users_query = "SELECT DISTINCT al.user_id, u.name, u.user_name 
                                               FROM activity_log al
                                               LEFT JOIN op_user u ON CAST(al.user_id AS UNSIGNED) = u.id
                                               WHERE (al.status = 'ACTIVE' OR al.status IS NULL)
                                               ORDER BY CAST(al.user_id AS UNSIGNED) DESC
                                               LIMIT 100";
                                $users_result = mysqli_query($con, $users_query);
                                if ($users_result) {
                                    while ($user_row = mysqli_fetch_assoc($users_result)) {
                                        $user_display = $user_row['name'] ?? $user_row['user_name'] ?? 'User ID: ' . $user_row['user_id'];
                                        $selected = ($filter_user == $user_row['user_id']) ? 'selected' : '';
                                        echo '<option value="' . $user_row['user_id'] . '" ' . $selected . '>' . htmlspecialchars($user_display) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Task Name</label>
                            <select name="task_name" class="form-select form-select-sm">
                                <option value="">All Tasks</option>
                                <?php
                                $tasks_query = "SELECT DISTINCT task_name 
                                               FROM activity_log 
                                               WHERE (status = 'ACTIVE' OR status IS NULL) AND task_name IS NOT NULL AND task_name != ''
                                               ORDER BY task_name ASC
                                               LIMIT 100";
                                $tasks_result = mysqli_query($con, $tasks_query);
                                if ($tasks_result) {
                                    while ($task_row = mysqli_fetch_assoc($tasks_result)) {
                                        $selected = ($filter_task == $task_row['task_name']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($task_row['task_name']) . '" ' . $selected . '>' . htmlspecialchars($task_row['task_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Date From</label>
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control form-control-sm" 
                                   value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Date To</label>
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control form-control-sm" 
                                   value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                    </div>
                    
                    <!-- Filter Row 2 -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">IP Address</label>
                            <input type="text" 
                                   name="ip_address" 
                                   class="form-control form-control-sm" 
                                   placeholder="e.g., 192.168.1.1"
                                   value="<?php echo htmlspecialchars($filter_ip); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="ACTIVE" <?php echo $filter_status == 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                <option value="DELETED" <?php echo $filter_status == 'DELETED' ? 'selected' : ''; ?>>Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                            <a href="activity_log_manage.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                    
                    <!-- Active Filters Info -->
                    <div class="row">
                        <div class="col-12 text-end">
                            <?php
                            // Show active filter count
                            $filter_count = 0;
                            if ($filter_user > 0) $filter_count++;
                            if ($filter_task != '') $filter_count++;
                            if ($filter_date_from != '') $filter_count++;
                            if ($filter_date_to != '') $filter_count++;
                            if ($filter_ip != '') $filter_count++;
                            if ($filter_status != '') $filter_count++;
                            if ($search_query != '') $filter_count++;
                            
                            if ($filter_count > 0) {
                                echo '<small class="text-muted"><i class="fas fa-info-circle me-1"></i>' . $filter_count . ' filter(s) active</small>';
                            }
                            
                            // Debug: Show total records in database
                            $debug_query = "SELECT COUNT(*) as total FROM activity_log";
                            $debug_result = mysqli_query($con, $debug_query);
                            if ($debug_result) {
                                $debug_row = mysqli_fetch_assoc($debug_result);
                                $total_in_db = $debug_row['total'];
                                echo '<br><small class="text-info"><i class="fas fa-database me-1"></i>Total logs in DB: ' . $total_in_db . '</small>';
                            }
                            ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-list me-2"></i>Activity Logs
                </h6>
                <div>
                    <small class="text-muted" id="logCount">Loading...</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-hover table-sm mb-0 table-striped align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="60">ID</th>
                                <th width="120">Date & Time</th>
                                <th width="100">User</th>
                                <th width="150">Task Name</th>
                                <th>Request Data</th>
                                <th width="120">IP Address</th>
                                <th width="80">Status</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query
                            $where_conditions = [];
                            
                            // Handle status filter - include NULL and ACTIVE by default
                            if ($filter_status != '') {
                                $status_escaped = mysqli_real_escape_string($con, $filter_status);
                                $where_conditions[] = "al.status = '$status_escaped'";
                            } else {
                                // Show ACTIVE and NULL status by default (NULL for old records)
                                $where_conditions[] = "(al.status = 'ACTIVE' OR al.status IS NULL)";
                            }
                            
                            // Debug output (remove after testing)
                            // echo "<!-- WHERE conditions: " . print_r($where_conditions, true) . " -->";
                            
                            if ($filter_user > 0) {
                                // user_id is varchar, so handle as string
                                $user_escaped = mysqli_real_escape_string($con, $filter_user);
                                $where_conditions[] = "al.user_id = '$user_escaped'";
                            }
                            
                            if ($filter_task != '') {
                                $task_escaped = mysqli_real_escape_string($con, $filter_task);
                                $where_conditions[] = "al.task_name = '$task_escaped'";
                            }
                            
                            // Date range filter
                            if (!empty($filter_date_from)) {
                                $date_from_escaped = mysqli_real_escape_string($con, $filter_date_from);
                                $where_conditions[] = "DATE(al.date_time) >= '$date_from_escaped'";
                            }
                            
                            if (!empty($filter_date_to)) {
                                $date_to_escaped = mysqli_real_escape_string($con, $filter_date_to);
                                $where_conditions[] = "DATE(al.date_time) <= '$date_to_escaped'";
                            }
                            
                            // IP address filter
                            if (!empty($filter_ip)) {
                                $ip_escaped = mysqli_real_escape_string($con, $filter_ip);
                                $where_conditions[] = "al.ip_address LIKE '%$ip_escaped%'";
                            }
                            
                            // Search filter
                            if (!empty($search_query)) {
                                $search_escaped = mysqli_real_escape_string($con, $search_query);
                                $where_conditions[] = "(
                                    al.task_name LIKE '%$search_escaped%' OR
                                    al.user_id LIKE '%$search_escaped%' OR
                                    al.ip_address LIKE '%$search_escaped%' OR
                                    al.request_data LIKE '%$search_escaped%' OR
                                    u.name LIKE '%$search_escaped%' OR
                                    u.user_name LIKE '%$search_escaped%'
                                )";
                            }
                            
                            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
                            
                            // Count total records - Fix user_id join
                            $count_query = "SELECT COUNT(*) as total 
                                           FROM activity_log al
                                           LEFT JOIN op_user u ON CAST(al.user_id AS UNSIGNED) = u.id
                                           $where_clause";
                            $count_result = mysqli_query($con, $count_query);
                            $total_records = 0;
                            if ($count_result) {
                                $count_row = mysqli_fetch_assoc($count_result);
                                $total_records = $count_row['total'];
                            }
                            
                            // Fix user_id join - user_id is VARCHAR, so convert to INT for join
                            // Handle NULL date_time in ORDER BY
                            $query = "SELECT al.*, 
                                     u.name as user_name, 
                                     u.user_name as user_login
                                     FROM activity_log al
                                     LEFT JOIN op_user u ON CAST(al.user_id AS UNSIGNED) = u.id
                                     $where_clause
                                     ORDER BY COALESCE(al.date_time, al.created_at, '1970-01-01') DESC, al.id DESC
                                     LIMIT 1000";
                            
                            // Debug: Log query for troubleshooting
                            // error_log("Activity Log Query: " . $query);
                            
                            $result = mysqli_query($con, $query);
                            
                            // Debug: Check if query executed successfully
                            if (!$result) {
                                $error_msg = mysqli_error($con);
                                error_log("Activity Log Query Error: " . $error_msg);
                                error_log("Activity Log Query: " . $query);
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-danger">
                                        <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                                        <p class="mb-2"><strong>Database Query Error</strong></p>
                                        <p class="mb-2"><?php echo htmlspecialchars(mysqli_error($con)); ?></p>
                                        <details class="text-start mt-3">
                                            <summary class="text-danger cursor-pointer">Show Query</summary>
                                            <pre class="bg-light p-2 mt-2 text-start small"><?php echo htmlspecialchars($query); ?></pre>
                                        </details>
                                    </td>
                                </tr>
                                <?php
                            } else {
                                $num_rows = mysqli_num_rows($result);
                                
                                // Debug: Show query info in HTML comment (view page source to see)
                                echo "<!-- Activity Log Query Debug: Rows found: $num_rows, Total records: $total_records -->\n";
                                
                                if ($num_rows > 0) {
                                    $row_count = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $row_count++;
                                    // Use date_time, or fallback to created_at
                                    $date_time_value = $row['date_time'] ?? $row['created_at'] ?? null;
                                    $date_time = $date_time_value ? date('d M Y H:i:s', strtotime($date_time_value)) : 'N/A';
                                    $user_display = $row['user_name'] ?? $row['user_login'] ?? 'User ID: ' . ($row['user_id'] ?? 'N/A');
                                    $task_name = htmlspecialchars($row['task_name'] ?? 'N/A');
                                    $ip_address = htmlspecialchars($row['ip_address'] ?? 'N/A');
                                    $status = $row['status'] ?? 'N/A';
                                    
                                    // Parse request data
                                    $request_data = $row['request_data'] ?? '';
                                    $request_display = 'N/A';
                                    $request_preview = '';
                                    
                                    if (!empty($request_data)) {
                                        $request_json = json_decode($request_data, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($request_json)) {
                                            // Format JSON nicely
                                            $request_preview = json_encode($request_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                            $request_display = mb_substr($request_preview, 0, 100) . (mb_strlen($request_preview) > 100 ? '...' : '');
                                        } else {
                                            $request_preview = $request_data;
                                            $request_display = mb_substr($request_data, 0, 100) . (mb_strlen($request_data) > 100 ? '...' : '');
                                        }
                                    }
                                    
                                    // Status badge color
                                    if ($status == 'ACTIVE') {
                                        $status_badge = 'success';
                                    } elseif ($status == 'DELETED') {
                                        $status_badge = 'secondary';
                                    } else {
                                        $status_badge = 'info'; // For NULL or other statuses
                                        $status = $status == 'N/A' ? 'N/A' : $status;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">#<?php echo $row['id']; ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo $date_time; ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($user_display); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $task_name; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted" title="<?php echo htmlspecialchars($request_preview); ?>">
                                                <?php echo htmlspecialchars($request_display); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small><code><?php echo $ip_address; ?></code></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_badge; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-sm" 
                                                        onclick="viewLogDetails(<?php echo $row['id']; ?>)" 
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteLog(<?php echo $row['id']; ?>)" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    } // End while loop
                                    
                                    // Update log count
                                    echo '<script>
                                    document.getElementById("logCount").innerHTML = "<strong>' . $row_count . '</strong> of <strong>' . $total_records . '</strong> logs";
                                    </script>';
                                } else {
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-history fa-4x text-muted mb-3 d-block opacity-50"></i>
                                            <h6 class="text-muted mb-2">No Activity Logs Found</h6>
                                            <?php if ($filter_count > 0 || !empty($search_query)): ?>
                                                <p class="text-muted small mb-2">No logs match your current filters or search criteria.</p>
                                                <a href="activity_log_manage.php" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-redo me-1"></i> Clear Filters
                                                </a>
                                            <?php else: ?>
                                                <p class="text-muted small mb-0">Activity logs will appear here as users perform actions in the system.</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                } // End else (no rows)
                            } // End else (query succeeded)
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Activity Log Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p>Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once('../system/footer.php'); ?>

<script>
// Set max date for date inputs (today)
$(document).ready(function() {
    var today = new Date().toISOString().split('T')[0];
    $('input[name="date_from"], input[name="date_to"]').attr('max', today);
    
    // Validate date range
    $('input[name="date_from"]').on('change', function() {
        var dateFrom = $(this).val();
        var dateTo = $('input[name="date_to"]').val();
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('Date From cannot be greater than Date To');
            $(this).val('');
        }
    });
    
    $('input[name="date_to"]').on('change', function() {
        var dateFrom = $('input[name="date_from"]').val();
        var dateTo = $(this).val();
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('Date To cannot be less than Date From');
            $(this).val('');
        }
    });
    
    // Enter key on search input submits form
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });
});

// View Log Details
function viewLogDetails(logId) {
    $('#logDetailsModal').modal('show');
    $('#logDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading...</p></div>');
    
    $.ajax({
        url: 'save_task_review.php',
        type: 'POST',
        data: {
            action: 'get_log_details',
            log_id: logId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var log = response.log;
                var html = '<div class="row">';
                html += '<div class="col-md-6 mb-3"><strong>Log ID:</strong><br><span class="text-muted">#' + log.id + '</span></div>';
                html += '<div class="col-md-6 mb-3"><strong>Status:</strong><br><span class="badge bg-' + (log.status == 'ACTIVE' ? 'success' : 'secondary') + '">' + log.status + '</span></div>';
                html += '<div class="col-md-6 mb-3"><strong>Date & Time:</strong><br><span class="text-muted">' + (log.date_time || 'N/A') + '</span></div>';
                html += '<div class="col-md-6 mb-3"><strong>User ID:</strong><br><span class="text-muted">' + (log.user_id || 'N/A') + '</span></div>';
                html += '<div class="col-md-12 mb-3"><strong>Task Name:</strong><br><span class="badge bg-info">' + (log.task_name || 'N/A') + '</span></div>';
                html += '<div class="col-md-12 mb-3"><strong>IP Address:</strong><br><code>' + (log.ip_address || 'N/A') + '</code></div>';
                html += '<div class="col-md-12 mb-3"><strong>Request Data:</strong><br>';
                html += '<pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 12px;">';
                
                if (log.request_data) {
                    try {
                        var jsonData = JSON.parse(log.request_data);
                        html += JSON.stringify(jsonData, null, 2);
                    } catch(e) {
                        html += log.request_data;
                    }
                } else {
                    html += 'N/A';
                }
                
                html += '</pre></div>';
                html += '<div class="col-md-6 mb-3"><strong>Created At:</strong><br><span class="text-muted">' + (log.created_at || 'N/A') + '</span></div>';
                html += '<div class="col-md-6 mb-3"><strong>Created By:</strong><br><span class="text-muted">' + (log.created_by || 'N/A') + '</span></div>';
                html += '</div>';
                
                $('#logDetailsContent').html(html);
            } else {
                $('#logDetailsContent').html('<div class="alert alert-danger">Error loading log details: ' + (response.message || 'Unknown error') + '</div>');
            }
        },
        error: function() {
            $('#logDetailsContent').html('<div class="alert alert-danger">Error loading log details. Please try again.</div>');
        }
    });
}

// Delete Log
function deleteLog(logId) {
    if (!confirm('Are you sure you want to delete this activity log? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: 'save_task_review.php',
        type: 'POST',
        data: {
            action: 'delete_activity_log',
            log_id: logId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Activity log deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete log'));
            }
        },
        error: function() {
            alert('Error deleting log. Please try again.');
        }
    });
}

// Export Logs
function exportLogs() {
    // Get current filter parameters
    var params = new URLSearchParams(window.location.search);
    params.append('export', 'csv');
    
    window.location.href = 'activity_log_manage.php?' + params.toString();
}
</script>

