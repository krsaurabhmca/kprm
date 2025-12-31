<?php
/**
 * KPRM - Add New Case (Multi-Step Form with Multiple Tasks)
 * Step 1: Select Client
 * Step 2: Fill Client Meta Form
 * Step 3: Add Multiple Tasks (Accordion Interface)
 */

require_once('../system/op_lib.php');
require_once('../function.php');

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

$page_title = "Add New Case";
include('../system/all_header.php');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-folder-plus"></i> <?php echo $page_title; ?>
                    </h3>
                    <div class="card-tools">
                        <a href="case_manage.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Cases
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Progress Steps -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <ul class="nav nav-pills nav-justified" id="stepIndicator">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $step >= 1 ? 'active' : ''; ?>" href="#">
                                        <i class="fas fa-building"></i><br>Select Client
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $step >= 2 ? 'active' : ''; ?>" href="#">
                                        <i class="fas fa-edit"></i><br>Client Info
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $step >= 3 ? 'active' : ''; ?>" href="#">
                                        <i class="fas fa-tasks"></i><br>Add Tasks
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step Content -->
                    <div class="row">
                        <div class="col-12">
                            <?php
                            switch ($step) {
                                case 1:
                                    // Step 1: Select Client
                                    ?>
                                    <form method="GET" action="">
                                        <input type="hidden" name="step" value="2">
                                        <div class="form-group mb-3">
                                            <label>Select Client <span class="text-danger">*</span></label>
                                            <select name="client_id" id="client_id" class="form-select select2" required>
                                                <?php echo dropdown_list("clients","id","name", $client_id); ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            Next <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </form>
                                    <?php
                                    break;

                                case 2:
                                    // Step 2: Client Meta Form
                                    if (!$client_id) {
                                        echo '<div class="alert alert-danger">Please select a client first!</div>';
                                        echo '<a href="?step=1" class="btn btn-secondary">Go Back</a>';
                                        break;
                                    }

                                    $client = get_data('clients', $client_id);
                                    if ($client['count'] == 0) {
                                        echo '<div class="alert alert-danger">Client not found!</div>';
                                        echo '<a href="?step=1" class="btn btn-secondary">Go Back</a>';
                                        break;
                                    }
                                    $client_name = $client['data']['name'];
                                    
                                    // Load existing case data if editing
                                    $existing_case_info = [];
                                    $existing_case_status = 'ACTIVE';
                                    if ($case_id > 0) {
                                        $case_data = get_data('cases', $case_id);
                                        if ($case_data['count'] > 0) {
                                            $case_row = $case_data['data'];
                                            if (!empty($case_row['case_info'])) {
                                                $existing_case_info = json_decode($case_row['case_info'], true);
                                                if (!is_array($existing_case_info)) {
                                                    $existing_case_info = [];
                                                }
                                            }
                                            $existing_case_status = $case_row['case_status'] ?? 'ACTIVE';
                                        }
                                    }
                                    ?>
                                    <h4>Client: <?php echo htmlspecialchars($client_name); ?></h4>
                                    <form method="POST" action="save_case_step.php" id="clientMetaForm">
                                        <input type="hidden" name="action" value="save_client_meta">
                                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                                        <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                                        
                                        <!-- Case Status -->
                                        <!-- <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>Case Status</strong></label>
                                                <select name="case_status" class="form-select">
                                                    <option value="ACTIVE" <?php echo $existing_case_status == 'ACTIVE' ? 'selected' : ''; ?>>ACTIVE</option>
                                                    <option value="PENDING" <?php echo $existing_case_status == 'PENDING' ? 'selected' : ''; ?>>PENDING</option>
                                                    <option value="COMPLETED" <?php echo $existing_case_status == 'COMPLETED' ? 'selected' : ''; ?>>COMPLETED</option>
                                                    <option value="ON_HOLD" <?php echo $existing_case_status == 'ON_HOLD' ? 'selected' : ''; ?>>ON HOLD</option>
                                                </select>
                                            </div>
                                        </div> -->
                                        
                                        <?php echo build_client_meta_form($client_id, $existing_case_info); ?>
                                        
                                        <div class="form-group mt-3">
                                            <button type="button" class="btn btn-secondary" onclick="window.location.href='?step=1'">
                                                <i class="fas fa-arrow-left"></i> Back
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                Save & Continue <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </form>
                                    <?php
                                    break;

                                case 3:
                                    // Step 3: Add Multiple Tasks (Accordion Interface)
                                    if (!$client_id || !$case_id) {
                                        echo '<div class="alert alert-danger">Please complete previous steps!</div>';
                                        echo '<a href="?step=1" class="btn btn-secondary">Start Over</a>';
                                        break;
                                    }

                                    // Get existing tasks for this case
                                    $existing_tasks = [];
                                    // Check if case_tasks table exists by trying to query it
                                    global $con;
                                    $table_exists = false;
                                    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
                                    if (mysqli_num_rows($table_check) > 0) {
                                        $table_exists = true;
                                        $tasks_result = get_all('case_tasks', '*', ['case_id' => $case_id, 'status' => 'ACTIVE'], 'id ASC');
                                        if ($tasks_result['count'] > 0) {
                                            $existing_tasks = $tasks_result['data'];
                                        }
                                    }

                                    $case_info = get_data('cases', $case_id);
                                    $application_no = $case_info['count'] > 0 ? $case_info['data']['application_no'] : '';
                                    ?>
                                    
                                    <?php if (!$table_exists): ?>
                                    <div class="alert alert-warning mb-3">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        <strong>Warning:</strong> The case_tasks table does not exist. 
                                        Please run: <code>SOURCE db/create_case_tasks_table.sql;</code> in your database.
                                        <br><br>
                                        <a href="../db/create_case_tasks_table.sql" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-download"></i> View SQL File
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h4>Case: <?php echo htmlspecialchars($application_no); ?></h4>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <?php if ($table_exists): ?>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                                <i class="fas fa-plus"></i> Add New Task
                                            </button>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-success" disabled title="case_tasks table not found">
                                                <i class="fas fa-plus"></i> Add New Task (Table Missing)
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Tasks Accordion -->
                                    <div class="accordion" id="tasksAccordion">
                                        <?php
                                        if (empty($existing_tasks)) {
                                            ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No tasks added yet. Click "Add New Task" to get started.
                                            </div>
                                            <?php
                                        } else {
                                            foreach ($existing_tasks as $index => $task) {
                                                $task_template = get_data('tasks', $task['task_template_id']);
                                                $task_name = $task_template['count'] > 0 ? $task_template['data']['task_name'] : 'Unknown Task';
                                                $task_type = $task_template['count'] > 0 ? $task_template['data']['task_type'] : '';
                                                $task_data = json_decode($task['task_data'] ?? '{}', true);
                                                $task_status = $task['task_status'] ?? 'PENDING';
                                                
                                                $status_badge = [
                                                    'PENDING' => 'warning',
                                                    'IN_PROGRESS' => 'info',
                                                    'COMPLETED' => 'success',
                                                    'REJECTED' => 'danger'
                                                ];
                                                $badge_color = $status_badge[$task_status] ?? 'secondary';
                                                ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading<?php echo $task['id']; ?>">
                                                        <button class="accordion-button <?php echo $index == 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $task['id']; ?>" aria-expanded="<?php echo $index == 0 ? 'true' : 'false'; ?>">
                                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
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
                                                                            <div class="alert alert-info mb-0 py-2">
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
                                                                        <div class="alert alert-warning mb-0 py-2">
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
                                                                    <?php
                                                                    // COMPLETED: Show view only
                                                                    elseif ($current_status == 'COMPLETED'):
                                                                    ?>
                                                                        <span class="badge bg-success">Completed</span>
                                                                    <?php endif; ?>
                                                                    
                                                                    <!-- Always show Edit and Delete for ADMIN/DEV -->
                                                                    <?php if ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV'): ?>
                                                                        <button type="button" class="btn btn-sm btn-primary" onclick="editTask(<?php echo $task['id']; ?>, <?php echo $task['task_template_id']; ?>)">
                                                                            <i class="fas fa-edit"></i> Edit
                                                                        </button>
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
                                                                        <div class="col-md-4 mb-3">
                                                                            <label class="form-label"><strong><?php echo htmlspecialchars($field['display_name']); ?></strong></label>
                                                                            <div class="form-control-plaintext">
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
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>

                                    <!-- Add Task Modal -->
                                    <div class="modal fade" id="addTaskModal" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add New Task</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form id="addTaskForm" onsubmit="return submitTaskForm(event)">
                                                    <input type="hidden" name="action" value="add_task_to_case">
                                                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                                                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                                                    
                                                    <div class="modal-body">
                                                        <div id="modalError" class="alert alert-danger" style="display:none;"></div>
                                                        <div id="modalSuccess" class="alert alert-success" style="display:none;"></div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label>Task Type <span class="text-danger">*</span></label>
                                                                <select name="task_type" id="modal_task_type" class="form-select" required onchange="loadTaskNames()">
                                                                    <option value="">Select Task Type</option>
                                                                    <option value="PHYSICAL">Physical</option>
                                                                    <option value="ITO">ITO</option>
                                                                    <option value="BANKING">Banking</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label>Task Name <span class="text-danger">*</span></label>
                                                                <select name="task_id" id="modal_task_id" class="form-select" required>
                                                                    <option value="">Select Task Type First</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div id="taskFieldsContainer"></div>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" id="submitTaskBtn">
                                                            <i class="fas fa-spinner fa-spin" id="submitSpinner" style="display:none;"></i>
                                                            Add Task
                                                        </button>
                                                    </div>
                                                </form>
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

                                    <!-- Success/Error Messages -->
                                    <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle"></i> 
                                        <?php echo $_SESSION['success_message']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        <?php unset($_SESSION['success_message']); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle"></i> 
                                        <?php echo $_SESSION['error_message']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        <?php unset($_SESSION['error_message']); ?>
                                    </div>
                                    <?php endif; ?>

                                    <div class="mt-4">
                                        <a href="case_manage.php" class="btn btn-success">
                                            <i class="fas fa-check"></i> Complete Case Setup
                                        </a>
                                    </div>
                                    <?php
                                    break;

                                default:
                                    echo '<div class="alert alert-warning">Invalid step!</div>';
                                    echo '<a href="?step=1" class="btn btn-secondary">Start Over</a>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link {
    border-radius: 0;
    padding: 15px;
}
.nav-pills .nav-link.active {
    background-color: #007bff;
    color: white;
}
.nav-pills .nav-link:not(.active) {
    background-color: #f8f9fa;
    color: #6c757d;
}
.accordion-button {
    font-weight: 500;
}
.accordion-body {
    background-color: #f8f9fa;
}
</style>
<?php include('../system/footer.php'); ?>

<script>
function loadTaskNames() {
    var taskType = document.getElementById('modal_task_type').value;
    var taskSelect = document.getElementById('modal_task_id');
    var fieldsContainer = document.getElementById('taskFieldsContainer');
    
    if (!taskType) {
        taskSelect.innerHTML = '<option value="">Select Task Type First</option>';
        fieldsContainer.innerHTML = '';
        return;
    }
    
    // Show loading
    taskSelect.innerHTML = '<option value="">Loading tasks...</option>';
    fieldsContainer.innerHTML = '';
    
    // Load tasks via AJAX
    $.ajax({
        url: 'get_tasks_by_type.php',
        type: 'GET',
        data: { task_type: taskType },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                taskSelect.innerHTML = '<option value="">Select Task</option>';
                if (response.tasks && response.tasks.length > 0) {
                    response.tasks.forEach(function(task) {
                        taskSelect.innerHTML += '<option value="' + task.id + '">' + task.task_name + '</option>';
                    });
                } else {
                    taskSelect.innerHTML = '<option value="">No tasks found for this type</option>';
                }
                
                // Load fields when task is selected
                taskSelect.onchange = function() {
                    loadTaskFields(this.value);
                };
            } else {
                taskSelect.innerHTML = '<option value="">Error loading tasks</option>';
                alert('Error: ' + (response.message || 'Failed to load tasks'));
            }
        },
        error: function(xhr, status, error) {
            taskSelect.innerHTML = '<option value="">Error loading tasks</option>';
            console.error('AJAX Error:', error);
            alert('Error loading tasks. Please check console for details.');
        }
    });
}

function loadTaskFields(taskId) {
    if (!taskId) {
        document.getElementById('taskFieldsContainer').innerHTML = '';
        return;
    }
    
    // Show loading
    document.getElementById('taskFieldsContainer').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading fields...</div>';
    
    $.ajax({
        url: 'get_task_fields.php',
        type: 'GET',
        data: { task_id: taskId },
        dataType: 'html',
        success: function(html) {
            console.log('AJAX Success - Response length:', html.length);
            console.log('AJAX Success - Response preview:', html.substring(0, 200));
            if (!html || html.trim() === '') {
                document.getElementById('taskFieldsContainer').innerHTML = '<div class="alert alert-warning">No response received from server. Please check browser console and server logs.</div>';
            } else {
                document.getElementById('taskFieldsContainer').innerHTML = html;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error Details:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            var errorMsg = 'Error loading fields: ' + error;
            if (xhr.responseText) {
                errorMsg += '<br><small>Response: ' + xhr.responseText.substring(0, 200) + '</small>';
            }
            document.getElementById('taskFieldsContainer').innerHTML = '<div class="alert alert-danger">' + errorMsg + '</div>';
        }
    });
}

function editTask(taskInstanceId, taskTemplateId) {
    // Load task data and show in modal for editing
    window.location.href = 'edit_case_task.php?case_task_id=' + taskInstanceId + '&task_id=' + taskTemplateId;
}

function submitTaskForm(event) {
    event.preventDefault();
    
    var taskType = document.getElementById('modal_task_type').value;
    var taskId = document.getElementById('modal_task_id').value;
    var errorDiv = document.getElementById('modalError');
    var successDiv = document.getElementById('modalSuccess');
    var submitBtn = document.getElementById('submitTaskBtn');
    var spinner = document.getElementById('submitSpinner');
    var form = document.getElementById('addTaskForm');
    
    // Hide previous messages
    errorDiv.style.display = 'none';
    errorDiv.innerHTML = '';
    successDiv.style.display = 'none';
    successDiv.innerHTML = '';
    
    // Validate
    if (!taskType) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select a task type';
        errorDiv.style.display = 'block';
        return false;
    }
    
    if (!taskId) {
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select a task';
        errorDiv.style.display = 'block';
        return false;
    }
    
    // Show loading spinner
    spinner.style.display = 'inline-block';
    submitBtn.disabled = true;
    
    // Get form data
    var formData = new FormData(form);
    
    // Add AJAX flag
    formData.append('ajax', '1');
    
    // Submit via AJAX
    $.ajax({
        url: 'save_case_step.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            spinner.style.display = 'none';
            submitBtn.disabled = false;
            
            if (response && (response.status === 'success' || response.success)) {
                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + (response.message || 'Task added successfully!');
                successDiv.style.display = 'block';
                
                // Close modal and reload after short delay
                setTimeout(function() {
                    $('#addTaskModal').modal('hide');
                    location.reload();
                }, 1500);
            } else {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (response.message || 'Failed to add task');
                errorDiv.style.display = 'block';
            }
        },
        error: function(xhr, status, error) {
            spinner.style.display = 'none';
            submitBtn.disabled = false;
            
            var errorMsg = 'Error: ' + error + ' (Status: ' + xhr.status + ')';
            
            // Try to parse error response
            try {
                var errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.message) {
                    errorMsg = errorResponse.message;
                }
            } catch(e) {
                // Use default error message
            }
            
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMsg;
            errorDiv.style.display = 'block';
            console.error('AJAX Error:', xhr.responseText);
        }
    });
    
    return false;
}

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
                
                $('#assignTaskModal').modal('show');
            } else {
                alert('Error loading verifiers: ' + (response.message || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error loading verifiers. Please try again.');
        }
    });
}

$(document).ready(function() {
    // Remove any existing handlers and attach new one
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
    if (confirm('Are you sure you want to delete this task?')) {
        $.ajax({
            url: 'save_case_step.php',
            type: 'POST',
            data: {
                action: 'delete_task',
                case_task_id: taskInstanceId
            },
            success: function(response) {
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch(e) {}
                }
                if (response && response.success) {
                    location.reload();
                } else {
                    alert('Error deleting task: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error deleting task. Please try again.');
            }
        });
    }
}
</script>

