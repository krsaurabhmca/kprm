<?php 
/**
 * KPRM - Client Dashboard
 * Dashboard for Clients
 * Shows view-only access to their own cases and tasks
 */
require_once('all_header.php');
require_once('../function.php');

global $con;

// Get client ID from user - assuming op_user has a client_id field or linked through another table
// Adjust based on your schema
$client_user_id = $user_id;
$client_id = 0;

// Try to get client_id from user record
$user_data = get_data('op_user', $user_id);
if ($user_data['count'] > 0) {
    $user_record = $user_data['data'];
    // Check if user has client_id field or get from clients table
    if (isset($user_record['client_id']) && !empty($user_record['client_id'])) {
        $client_id = intval($user_record['client_id']);
    } else {
        // Try to get client_id from clients table matching user_name or email
        $client_sql = "SELECT id FROM clients WHERE status = 'ACTIVE' AND (user_name = '{$user_record['user_name']}' OR email = '{$user_record['user_email']}') LIMIT 1";
        $client_res = mysqli_query($con, $client_sql);
        if ($client_res && $client_row = mysqli_fetch_assoc($client_res)) {
            $client_id = intval($client_row['id']);
        }
    }
}

// Get date filter parameter
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$today = date('Y-m-d');
$date_where = '';

switch ($date_filter) {
    case 'today':
        $date_where = "AND DATE(created_at) = '$today'";
        $date_where_tasks = "AND DATE(created_at) = '$today'";
        break;
    case 'this_week':
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $date_where = "AND DATE(created_at) >= '$week_start'";
        $date_where_tasks = "AND DATE(created_at) >= '$week_start'";
        break;
    case 'this_month':
        $month_start = date('Y-m-01');
        $date_where = "AND DATE(created_at) >= '$month_start'";
        $date_where_tasks = "AND DATE(created_at) >= '$month_start'";
        break;
    case 'this_year':
        $year_start = date('Y-01-01');
        $date_where = "AND DATE(created_at) >= '$year_start'";
        $date_where_tasks = "AND DATE(created_at) >= '$year_start'";
        break;
    default:
        $date_where = '';
        $date_where_tasks = '';
        break;
}

// Check if case_tasks table exists
$has_case_tasks = false;
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    $has_case_tasks = true;
}

// Cases Statistics (only this client's cases)
$cases_stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'closed' => 0,
    'active' => 0
];

$client_name = 'Unknown Client';
if ($client_id > 0) {
    // Get client name
    $client_name_sql = "SELECT name FROM clients WHERE id = '$client_id' AND status = 'ACTIVE'";
    $client_name_res = mysqli_query($con, $client_name_sql);
    if ($client_name_res && $client_name_row = mysqli_fetch_assoc($client_name_res)) {
        $client_name = $client_name_row['name'];
    }
    
    $cases_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN case_status = 'ACTIVE' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN case_status = 'PENDING' OR case_status IS NULL THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN case_status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN case_status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN case_status = 'CLOSED' THEN 1 ELSE 0 END) as closed
        FROM cases 
        WHERE status != 'DELETED' AND client_id = '$client_id' $date_where";
    $cases_res = mysqli_query($con, $cases_sql);
    if ($cases_res) {
        $cases_row = mysqli_fetch_assoc($cases_res);
        $cases_stats = [
            'total' => (int)$cases_row['total'],
            'active' => (int)$cases_row['active'],
            'pending' => (int)$cases_row['pending'],
            'in_progress' => (int)$cases_row['in_progress'],
            'completed' => (int)$cases_row['completed'],
            'closed' => (int)$cases_row['closed']
        ];
    }
}

// Tasks Statistics (only this client's tasks)
$tasks_stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'verification_completed' => 0,
    'completed' => 0
];

if ($has_case_tasks && $client_id > 0) {
    $tasks_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ct.task_status = 'PENDING' OR ct.task_status IS NULL THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN ct.task_status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN ct.task_status = 'VERIFICATION_COMPLETED' THEN 1 ELSE 0 END) as verification_completed,
        SUM(CASE WHEN ct.task_status = 'COMPLETED' THEN 1 ELSE 0 END) as completed
        FROM case_tasks ct
        JOIN cases c ON ct.case_id = c.id
        WHERE ct.status = 'ACTIVE' AND c.client_id = '$client_id' $date_where_tasks";
    $tasks_res = mysqli_query($con, $tasks_sql);
    if ($tasks_res) {
        $tasks_row = mysqli_fetch_assoc($tasks_res);
        $tasks_stats = [
            'total' => (int)$tasks_row['total'],
            'pending' => (int)$tasks_row['pending'],
            'in_progress' => (int)$tasks_row['in_progress'],
            'verification_completed' => (int)$tasks_row['verification_completed'],
            'completed' => (int)$tasks_row['completed']
        ];
    }
}

// Recent Cases (Last 5) - Only this client's cases
$recent_cases = [];
if ($client_id > 0) {
    $recent_sql = "SELECT c.*, cl.name as client_name 
        FROM cases c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        WHERE c.status != 'DELETED' AND c.client_id = '$client_id'
        ORDER BY c.created_at DESC 
        LIMIT 5";
    $recent_res = mysqli_query($con, $recent_sql);
    if ($recent_res) {
        while ($row = mysqli_fetch_assoc($recent_res)) {
            $recent_cases[] = $row;
        }
    }
}

// Pending Tasks (Last 5) - Only this client's tasks
$pending_tasks = [];
if ($has_case_tasks && $client_id > 0) {
    $pending_tasks_sql = "SELECT ct.*, c.application_no, cl.name as client_name 
        FROM case_tasks ct 
        LEFT JOIN cases c ON ct.case_id = c.id 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        WHERE ct.status = 'ACTIVE' AND c.client_id = '$client_id' AND (ct.task_status = 'PENDING' OR ct.task_status IS NULL)
        ORDER BY ct.created_at DESC 
        LIMIT 5";
    $pending_tasks_res = mysqli_query($con, $pending_tasks_sql);
    if ($pending_tasks_res) {
        while ($row = mysqli_fetch_assoc($pending_tasks_res)) {
            $pending_tasks[] = $row;
        }
    }
}

// Daily statistics (last 30 days)
$daily_stats = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_label = date('M d', strtotime("-$i days"));
    
    $cases_count = 0;
    if ($client_id > 0) {
        $daily_sql = "SELECT COUNT(*) as cases_count
            FROM cases 
            WHERE status != 'DELETED' AND client_id = '$client_id' AND DATE(created_at) = '$date'";
        $daily_res = mysqli_query($con, $daily_sql);
        if ($daily_res && $row = mysqli_fetch_assoc($daily_res)) {
            $cases_count = (int)$row['cases_count'];
        }
    }
    
    $tasks_count = 0;
    if ($has_case_tasks && $client_id > 0) {
        $daily_tasks_sql = "SELECT COUNT(*) as tasks_count
            FROM case_tasks ct
            JOIN cases c ON ct.case_id = c.id
            WHERE ct.status = 'ACTIVE' AND c.client_id = '$client_id' AND DATE(ct.created_at) = '$date'";
        $daily_tasks_res = mysqli_query($con, $daily_tasks_sql);
        if ($daily_tasks_res && $row = mysqli_fetch_assoc($daily_tasks_res)) {
            $tasks_count = (int)$row['tasks_count'];
        }
    }
    
    $daily_stats[] = [
        'date' => $day_label,
        'cases' => $cases_count,
        'tasks' => $tasks_count
    ];
}
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">
                    <i class="fas fa-user text-primary me-2"></i>
                    <strong>Client Dashboard</strong>
                </h4>
                <p class="text-muted small mb-0">View-only access to your cases and tasks</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bolt me-1"></i>Quick Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= $base_url?>public/client_case_status.php<?= $client_id > 0 ? '?client_id=' . $client_id : '' ?>">
                                <i class="fas fa-list text-info me-2"></i>View All Cases
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= $base_url?>public/client_case_status.php?client_id=<?= $client_id ?>&case_status=PENDING">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>Pending Cases
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= $base_url?>public/client_case_status.php?client_id=<?= $client_id ?>&case_status=CLOSED">
                                <i class="fas fa-check-circle text-success me-2"></i>Closed Cases
                            </a>
                        </li>
                    </ul>
                </div>
                <span class="btn btn-sm btn-dark">
                    <i class="fas fa-user me-1"></i><?= $user_name ?>
                </span>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><i class="fas fa-filter text-primary me-2"></i>Filter Statistics</h6>
                        <small class="text-muted">Select a time period</small>
                    </div>
                    <div class="btn-group" role="group">
                        <a href="?date_filter=today" class="btn btn-sm <?= $date_filter == 'today' ? 'btn-primary' : 'btn-outline-primary' ?>">Today</a>
                        <a href="?date_filter=this_week" class="btn btn-sm <?= $date_filter == 'this_week' ? 'btn-primary' : 'btn-outline-primary' ?>">This Week</a>
                        <a href="?date_filter=this_month" class="btn btn-sm <?= $date_filter == 'this_month' ? 'btn-primary' : 'btn-outline-primary' ?>">This Month</a>
                        <a href="?date_filter=this_year" class="btn btn-sm <?= $date_filter == 'this_year' ? 'btn-primary' : 'btn-outline-primary' ?>">This Year</a>
                        <a href="?date_filter=all" class="btn btn-sm <?= $date_filter == 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All Time</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($client_id == 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Client account not linked.</strong> Please contact Administrator to link your account to a client.
            </div>
        <?php else: ?>

        <!-- Client Info -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i><?= htmlspecialchars($client_name) ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>View-Only Access:</strong> You can view all your cases and tasks but cannot create or modify them.
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Total Cases</div>
                                <div class="h5 mb-0 fw-bold text-primary"><?= $cases_stats['total'] ?></div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-folder fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-warning border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Pending Cases</div>
                                <div class="h5 mb-0 fw-bold text-warning"><?= $cases_stats['pending'] ?></div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-info border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">In Progress</div>
                                <div class="h5 mb-0 fw-bold text-info"><?= $cases_stats['in_progress'] ?></div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-spinner fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-success border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Total Tasks</div>
                                <div class="h5 mb-0 fw-bold text-primary"><?= $tasks_stats['total'] ?></div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-tasks fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-warning border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Pending Tasks</div>
                                <div class="h5 mb-0 fw-bold text-warning"><?= $tasks_stats['pending'] ?></div>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-info border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">In Progress</div>
                                <div class="h5 mb-0 fw-bold text-info"><?= $tasks_stats['in_progress'] ?></div>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-spinner fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-primary border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Awaiting Review</div>
                                <div class="h5 mb-0 fw-bold text-primary"><?= $tasks_stats['verification_completed'] ?></div>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-eye fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card border-0 shadow-sm border-start border-success border-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">Completed</div>
                                <div class="h5 mb-0 fw-bold text-success"><?= $tasks_stats['completed'] ?></div>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-double fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie text-primary me-2"></i> Task Status Distribution</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="taskStatusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line text-primary me-2"></i> Daily Activity (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyActivityChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-folder-open me-2"></i> Recent Cases</h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (count($recent_cases) > 0): ?>
                            <?php foreach ($recent_cases as $case): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="<?= $base_url?>public/view_case.php?case_id=<?= $case['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($case['application_no'] ?? 'N/A') ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i> <?= date('d M Y', strtotime($case['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php
                                            $status = $case['case_status'] ?? 'PENDING';
                                            $badge_class = 'secondary';
                                            if ($status == 'COMPLETED') $badge_class = 'success';
                                            elseif ($status == 'IN_PROGRESS') $badge_class = 'info';
                                            elseif ($status == 'CLOSED') $badge_class = 'dark';
                                            elseif ($status == 'ACTIVE') $badge_class = 'primary';
                                            elseif ($status == 'PENDING') $badge_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>"><?= $status ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">No recent cases</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center bg-white">
                        <a href="<?= $base_url?>public/client_case_status.php?client_id=<?= $client_id ?>" class="btn btn-sm btn-info">View All</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark py-3">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-tasks me-2"></i> Pending Tasks</h6>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php if (count($pending_tasks) > 0): ?>
                            <?php foreach ($pending_tasks as $task): ?>
                                <div class="mb-2 pb-2 border-bottom">
                                    <small>
                                        <a href="<?= $base_url?>public/view_case.php?case_id=<?= $task['case_id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($task['application_no'] ?? 'N/A') ?> - <?= htmlspecialchars($task['task_name'] ?? 'N/A') ?>
                                        </a>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center small">No pending tasks</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Task Status Pie Chart
const taskStatusCtx = document.getElementById('taskStatusChart');
if (taskStatusCtx) {
    new Chart(taskStatusCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Awaiting Review', 'Completed'],
            datasets: [{
                data: [
                    <?= $tasks_stats['pending'] ?>,
                    <?= $tasks_stats['in_progress'] ?>,
                    <?= $tasks_stats['verification_completed'] ?>,
                    <?= $tasks_stats['completed'] ?>
                ],
                backgroundColor: ['#ffc107', '#17a2b8', '#007bff', '#28a745'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// Daily Activity Line Chart
const dailyActivityCtx = document.getElementById('dailyActivityChart');
if (dailyActivityCtx) {
    const dailyLabels = <?= json_encode(array_column($daily_stats, 'date')) ?>;
    new Chart(dailyActivityCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Cases',
                data: <?= json_encode(array_column($daily_stats, 'cases')) ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Tasks',
                data: <?= json_encode(array_column($daily_stats, 'tasks')) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}
</script>

<?php require_once('footer.php'); ?>

