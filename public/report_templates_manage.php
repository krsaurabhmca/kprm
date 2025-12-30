<?php
/**
 * KPRM - Report Templates Management
 * Manage report format templates for clients
 */

// Enable ALL error reporting FIRST
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Test if we can output
echo "<!-- Debug: Script started -->\n";

try {
    // Check if header file exists
    $header_path = '../system/all_header.php';
    if (!file_exists($header_path)) {
        die('ERROR: Header file not found at: ' . $header_path);
    }
    
    require_once($header_path);
    echo "<!-- Debug: Header loaded -->\n";
    
    // Check if database connection exists
    global $con;
    if (!isset($con) || !$con) {
        die('ERROR: Database connection ($con) is not available. Check database configuration.');
    }
    
    // Check if functions are available
    if (!function_exists('get_all')) {
        die('ERROR: Function get_all() is not defined. Check if function.php is included in all_header.php');
    }
    
    echo "<!-- Debug: Functions available -->\n";
    
} catch (Exception $e) {
    ob_end_clean();
    die('FATAL ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
} catch (Error $e) {
    ob_end_clean();
    die('FATAL ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Check if table exists
global $con;
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
if (!$table_check) {
    die('ERROR: Database query failed: ' . mysqli_error($con));
}
$table_exists = ($table_check && mysqli_num_rows($table_check) > 0);

if (!$table_exists) {
    echo '<div class="container-fluid mt-4">';
    echo '<div class="alert alert-warning">';
    echo '<i class="fas fa-exclamation-triangle"></i> ';
    echo '<strong>Database tables not found!</strong> Please run: <code>SOURCE db/create_report_templates_table.sql;</code> in your database.';
    echo '<br><a href="report_templates_setup.php" class="btn btn-primary mt-2">Go to Setup Page</a>';
    echo '</div>';
    echo '</div>';
    require_once('../system/footer.php');
    exit;
}

// Get client_id filter
$client_filter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Build query
$where = ['status' => 'ACTIVE'];
if ($client_filter > 0) {
    $where['client_id'] = $client_filter;
}

// Error handling for get_all
$templates_result = ['count' => 0, 'data' => [], 'status' => 'error'];
try {
    $templates_result = get_all('report_templates', '*', $where, 'client_id ASC, template_name ASC');
    if (!isset($templates_result['count'])) {
        $templates_result = ['count' => 0, 'data' => [], 'status' => 'error', 'message' => 'Invalid result structure from get_all()'];
    }
} catch (Exception $e) {
    error_log("Error in report_templates_manage.php: " . $e->getMessage());
    $templates_result = ['count' => 0, 'data' => [], 'status' => 'error', 'message' => $e->getMessage()];
} catch (Error $e) {
    error_log("Fatal error in report_templates_manage.php: " . $e->getMessage());
    $templates_result = ['count' => 0, 'data' => [], 'status' => 'error', 'message' => $e->getMessage()];
}

// Get all clients for filter dropdown
$clients_result = ['count' => 0, 'data' => []];
try {
    $clients_result = get_all('clients', 'id, name', ['status' => 'ACTIVE'], 'name ASC');
    if (!isset($clients_result['count'])) {
        $clients_result = ['count' => 0, 'data' => []];
    }
} catch (Exception $e) {
    error_log("Error getting clients: " . $e->getMessage());
} catch (Error $e) {
    error_log("Fatal error getting clients: " . $e->getMessage());
}

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

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0">
                                <i class="fas fa-file-alt"></i> Report Templates
                            </h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <form method="GET" class="d-inline-block me-2">
                                <select name="client_id" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                    <option value="0">All Clients</option>
                                    <?php
                                    if ($clients_result['count'] > 0) {
                                        foreach ($clients_result['data'] as $client) {
                                            $selected = ($client_filter == $client['id']) ? 'selected' : '';
                                            echo '<option value="' . $client['id'] . '" ' . $selected . '>' . htmlspecialchars($client['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </form>
                            <a href="report_templates_add.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add New Template
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($templates_result['status']) && $templates_result['status'] == 'error'): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Error loading templates:</strong> 
                            <?php echo isset($templates_result['message']) ? htmlspecialchars($templates_result['message']) : 'Unknown error occurred'; ?>
                            <br><small>Please check database connection and table structure.</small>
                            <br><small>SQL: <?php echo isset($templates_result['sql']) ? htmlspecialchars($templates_result['sql']) : 'N/A'; ?></small>
                        </div>
                    <?php elseif ($templates_result['count'] == 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No report templates found. 
                            <a href="report_templates_add.php" class="alert-link">Create your first template</a>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Template Name</th>
                                        <th>Client</th>
                                        <th>Type</th>
                                        <th>Task Type</th>
                                        <th>Default</th>
                                        <th>Status</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $index = 1;
                                    if (isset($templates_result['data']) && is_array($templates_result['data'])) {
                                        foreach ($templates_result['data'] as $template) {
                                            $client_name = 'Unknown';
                                            try {
                                                $client_info = get_data('clients', $template['client_id']);
                                                $client_name = ($client_info['count'] > 0 && isset($client_info['data']['name'])) ? $client_info['data']['name'] : 'Unknown';
                                            } catch (Exception $e) {
                                                error_log("Error getting client info: " . $e->getMessage());
                                            }
                                            ?>
                                        <tr>
                                            <td><?php echo $index++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($template['template_name']); ?></strong>
                                                <?php if (!empty($template['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($template['description'], 0, 50)); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($client_name); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($template['template_type']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo !empty($template['task_type']) ? htmlspecialchars($template['task_type']) : '<span class="text-muted">N/A</span>'; ?>
                                            </td>
                                            <td>
                                                <?php if ($template['is_default'] == 'YES'): ?>
                                                    <span class="badge bg-success">Default</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($template['status'] == 'ACTIVE' ? 'success' : 'secondary'); ?>">
                                                    <?php echo htmlspecialchars($template['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="report_templates_add.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="report_templates_designer.php?template_id=<?php echo $template['id']; ?>" class="btn btn-sm btn-warning" title="Design">
                                                    <i class="fas fa-paint-brush"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Flush output buffer
ob_end_flush();
require_once('../system/footer.php'); 
?>

<script>
function deleteTemplate(templateId) {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        $.ajax({
            url: 'save_report_template.php',
            type: 'POST',
            data: {
                action: 'delete',
                template_id: templateId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to delete template'));
                }
            },
            error: function() {
                alert('Error deleting template. Please try again.');
            }
        });
    }
}
</script>
