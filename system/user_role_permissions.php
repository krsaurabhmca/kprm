<?php 
/**
 * KPRM - User Role Permissions Management
 * Admin can assign/revoke clients and tasks for users
 */
require_once('all_header.php');

// Only Admin and DEV can access this page
if (!isset($user_type) || ($user_type != 'ADMIN' && $user_type != 'DEV')) {
    die("Access Denied. Admin or DEV access required.");
}

$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_user = null;
if ($selected_user_id > 0) {
    $user_result = get_data('op_user', $selected_user_id);
    if ($user_result['count'] > 0) {
        $selected_user = $user_result['data'];
    }
}

// Get all users for dropdown
$all_users = get_all('op_user', '*', ['status' => 'ACTIVE']);
$users_list = [];
if ($all_users['count'] > 0) {
    foreach ($all_users['data'] as $user) {
        if ($user['user_type'] != 'DEV') { // Exclude DEV users
            $users_list[] = $user;
        }
    }
    // Sort by user_type, then full_name
    usort($users_list, function($a, $b) {
        if ($a['user_type'] == $b['user_type']) {
            return strcmp($a['full_name'] ?? '', $b['full_name'] ?? '');
        }
        return strcmp($a['user_type'] ?? '', $b['user_type'] ?? '');
    });
}

// Get all clients
$all_clients = get_all('clients', '*', ['status' => 'ACTIVE']);
$clients_list = [];
if ($all_clients['count'] > 0) {
    $clients_list = $all_clients['data'];
    // Sort by name
    usort($clients_list, function($a, $b) {
        return strcmp($a['name'] ?? '', $b['name'] ?? '');
    });
}

// Get all tasks
$all_tasks = get_all('tasks', '*', ['status' => 'ACTIVE']);
$tasks_list = [];
if ($all_tasks['count'] > 0) {
    $tasks_list = $all_tasks['data'];
    // Sort by task_name
    usort($tasks_list, function($a, $b) {
        return strcmp($a['task_name'] ?? '', $b['task_name'] ?? '');
    });
}

// Get user's allowed clients
global $con;
$user_allowed_clients = [];
if ($selected_user_id > 0 && isset($con) && $con) {
    // Check if user_clients table exists before querying
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'user_clients'");
    if ($table_check && mysqli_num_rows($table_check) > 0) {
        $allowed_clients_result = direct_sql("SELECT client_id FROM user_clients WHERE user_id = '$selected_user_id' AND status = 'ACTIVE'");
        if ($allowed_clients_result && isset($allowed_clients_result['count']) && $allowed_clients_result['count'] > 0) {
            foreach ($allowed_clients_result['data'] as $row) {
                $user_allowed_clients[] = $row['client_id'];
            }
        }
    }
}

// Get user's allowed tasks
$user_allowed_tasks = [];
if ($selected_user_id > 0 && isset($con) && $con) {
    // Check if user_tasks table exists before querying
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'user_tasks'");
    if ($table_check && mysqli_num_rows($table_check) > 0) {
        $allowed_tasks_result = direct_sql("SELECT task_id FROM user_tasks WHERE user_id = '$selected_user_id' AND status = 'ACTIVE'");
        if ($allowed_tasks_result && isset($allowed_tasks_result['count']) && $allowed_tasks_result['count'] > 0) {
            foreach ($allowed_tasks_result['data'] as $row) {
                $user_allowed_tasks[] = $row['task_id'];
            }
        }
    }
}
?>

<main class="content">
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-shield me-2"></i>User Role Permissions
            </h1>
            <a href="op_user_manage" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-users me-1"></i>Back to Users
            </a>
        </div>

        <!-- User Selection -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Select User</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">User</label>
                        <select name="user_id" class="form-select" onchange="this.form.submit()" required>
                            <option value="">-- Select User --</option>
                            <?php foreach ($users_list as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $selected_user_id == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['user_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <?php if ($selected_user_id > 0): ?>
                            <div>
                                <strong>Current Role:</strong> <span class="badge bg-primary"><?= htmlspecialchars($selected_user['user_type'] ?? 'N/A') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_user_id > 0 && $selected_user): ?>
            <!-- Allowed Clients -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>Allowed Clients
                        <?php if ($selected_user['user_type'] == 'BEO' || $selected_user['user_type'] == 'TL' || $selected_user['user_type'] == 'MANAGER'): ?>
                            <small class="text-muted">(Select clients this user can access)</small>
                        <?php else: ?>
                            <small class="text-muted">(Admin/Client users have different access rules)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($selected_user['user_type'] == 'BEO' || $selected_user['user_type'] == 'TL' || $selected_user['user_type'] == 'MANAGER'): ?>
                        <form id="clientsForm">
                            <input type="hidden" name="user_id" value="<?= $selected_user_id ?>">
                            <div class="row">
                                <?php foreach ($clients_list as $client): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input client-checkbox" 
                                                   type="checkbox" 
                                                   name="client_ids[]" 
                                                   value="<?= $client['id'] ?>"
                                                   id="client_<?= $client['id'] ?>"
                                                   <?= in_array($client['id'], $user_allowed_clients) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="client_<?= $client['id'] ?>">
                                                <?= htmlspecialchars($client['name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" onclick="saveClientPermissions()">
                                    <i class="fas fa-save me-1"></i>Save Client Permissions
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAllClients()">
                                    <i class="fas fa-check-double me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="deselectAllClients()">
                                    <i class="fas fa-times me-1"></i>Deselect All
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php if ($selected_user['user_type'] == 'ADMIN' || $selected_user['user_type'] == 'DEV'): ?>
                                Admin and DEV users have access to all clients.
                            <?php elseif ($selected_user['user_type'] == 'CLIENT'): ?>
                                Client users have access only to their own client data.
                            <?php else: ?>
                                This role does not require client permissions.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Allowed Tasks -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tasks me-2"></i>Allowed Tasks
                        <?php if ($selected_user['user_type'] == 'TL' || $selected_user['user_type'] == 'MANAGER'): ?>
                            <small class="text-muted">(Select tasks this user can access)</small>
                        <?php else: ?>
                            <small class="text-muted">(BEO/Admin users have different access rules)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($selected_user['user_type'] == 'TL' || $selected_user['user_type'] == 'MANAGER'): ?>
                        <form id="tasksForm">
                            <input type="hidden" name="user_id" value="<?= $selected_user_id ?>">
                            <div class="row">
                                <?php foreach ($tasks_list as $task): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input task-checkbox" 
                                                   type="checkbox" 
                                                   name="task_ids[]" 
                                                   value="<?= $task['id'] ?>"
                                                   id="task_<?= $task['id'] ?>"
                                                   <?= in_array($task['id'], $user_allowed_tasks) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="task_<?= $task['id'] ?>">
                                                <?= htmlspecialchars($task['task_name']) ?> 
                                                <small class="text-muted">(<?= htmlspecialchars($task['task_type'] ?? 'N/A') ?>)</small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" onclick="saveTaskPermissions()">
                                    <i class="fas fa-save me-1"></i>Save Task Permissions
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAllTasks()">
                                    <i class="fas fa-check-double me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="deselectAllTasks()">
                                    <i class="fas fa-times me-1"></i>Deselect All
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php if ($selected_user['user_type'] == 'ADMIN' || $selected_user['user_type'] == 'DEV'): ?>
                                Admin and DEV users have access to all tasks.
                            <?php elseif ($selected_user['user_type'] == 'BEO'): ?>
                                BEO users have access to all tasks for their allowed clients.
                            <?php elseif ($selected_user['user_type'] == 'CLIENT'): ?>
                                Client users have view-only access to tasks in their cases.
                            <?php else: ?>
                                This role does not require task permissions.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>User Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-warning w-100" onclick="changeUserPassword(<?= $selected_user_id ?>)">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </div>
                        <div class="col-md-6">
                            <?php if ($selected_user['user_status'] == 'ACTIVE'): ?>
                                <button type="button" class="btn btn-danger w-100" onclick="blockUser(<?= $selected_user_id ?>)">
                                    <i class="fas fa-ban me-2"></i>Block Account
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success w-100" onclick="activateUser(<?= $selected_user_id ?>)">
                                    <i class="fas fa-check me-2"></i>Activate Account
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Please select a user to manage their permissions.
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('footer.php'); ?>

<script>
function saveClientPermissions() {
    const form = document.getElementById('clientsForm');
    const formData = new FormData(form);
    const clientIds = formData.getAll('client_ids[]');
    const userId = formData.get('user_id');
    
    $.ajax({
        url: 'system_process.php?task=save_user_clients',
        method: 'POST',
        data: {
            user_id: userId,
            client_ids: clientIds
        },
        dataType: 'json',
        success: function(response) {
            if (response.status == 'success') {
                alert('Client permissions saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.msg || 'Failed to save permissions'));
            }
        },
        error: function() {
            alert('Error: Failed to save permissions. Please try again.');
        }
    });
}

function saveTaskPermissions() {
    const form = document.getElementById('tasksForm');
    const formData = new FormData(form);
    const taskIds = formData.getAll('task_ids[]');
    const userId = formData.get('user_id');
    
    $.ajax({
        url: 'system_process.php?task=save_user_tasks',
        method: 'POST',
        data: {
            user_id: userId,
            task_ids: taskIds
        },
        dataType: 'json',
        success: function(response) {
            if (response.status == 'success') {
                alert('Task permissions saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.msg || 'Failed to save permissions'));
            }
        },
        error: function() {
            alert('Error: Failed to save permissions. Please try again.');
        }
    });
}

function selectAllClients() {
    document.querySelectorAll('.client-checkbox').forEach(cb => cb.checked = true);
}

function deselectAllClients() {
    document.querySelectorAll('.client-checkbox').forEach(cb => cb.checked = false);
}

function selectAllTasks() {
    document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = true);
}

function deselectAllTasks() {
    document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = false);
}

function changeUserPassword(userId) {
    const newPassword = prompt('Enter new password for user:');
    if (newPassword && newPassword.length >= 6) {
        $.ajax({
            url: 'system_process.php?task=change_user_password',
            method: 'POST',
            data: {
                user_id: userId,
                new_password: newPassword
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    alert('Password changed successfully!');
                } else {
                    alert('Error: ' + (response.msg || 'Failed to change password'));
                }
            },
            error: function() {
                alert('Error: Failed to change password. Please try again.');
            }
        });
    } else if (newPassword) {
        alert('Password must be at least 6 characters long.');
    }
}

function blockUser(userId) {
    if (confirm('Are you sure you want to block this user account?')) {
        $.ajax({
            url: 'system_process.php?task=update_user_status',
            method: 'POST',
            data: {
                user_id: userId,
                user_status: 'BLOCKED'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    alert('User account blocked successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.msg || 'Failed to block account'));
                }
            },
            error: function() {
                alert('Error: Failed to block account. Please try again.');
            }
        });
    }
}

function activateUser(userId) {
    $.ajax({
        url: 'system_process.php?task=update_user_status',
        method: 'POST',
        data: {
            user_id: userId,
            user_status: 'ACTIVE'
        },
        dataType: 'json',
        success: function(response) {
            if (response.status == 'success') {
                alert('User account activated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.msg || 'Failed to activate account'));
            }
        },
        error: function() {
            alert('Error: Failed to activate account. Please try again.');
        }
    });
}
</script>

