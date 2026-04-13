<?php
/**
 * KPRM - Financial Data Entry
 * Specialized page for entering P&L and Balance Sheet details
 */

require_once('../system/all_header.php');

// Get parameters
$case_task_id = isset($_GET['case_task_id']) ? intval($_GET['case_task_id']) : 0;

if (!$case_task_id) {
    $_SESSION['error_message'] = 'Invalid case task ID.';
    header('Location: case_manage.php');
    exit;
}

// Get case task data
$case_task = get_data('case_tasks', $case_task_id);
if ($case_task['count'] == 0) {
    $_SESSION['error_message'] = 'Task not found.';
    header('Location: case_manage.php');
    exit;
}

$task_row = $case_task['data'];
$case_id = $task_row['case_id'];
$existing_data = json_decode($task_row['task_data'] ?? '{}', true);

// Find the field that contains the financial table
// Usually it's named 'financial_table' or similar, or it's the only JSON_TABLE field
$field_name = 'financial_table'; // Default mapping
$table_config = '{}';

// Let's check tasks_meta to find the correct field and its config
global $con;
$meta_sql = "SELECT field_name, default_value FROM tasks_meta WHERE task_id = '{$task_row['task_template_id']}' AND (input_type = 'JSON_TABLE' OR input_type = 'COMPARISON_TABLE') LIMIT 1";
$meta_res = mysqli_query($con, $meta_sql);
if ($meta_res && mysqli_num_rows($meta_res) > 0) {
    $meta_row = mysqli_fetch_assoc($meta_res);
    $field_name = $meta_row['field_name'];
    $table_config = $meta_row['default_value'] ?: '{}';
}

// Get case info for header
$case_info = get_data('cases', $case_id);
$case_data = $case_info['data'] ?? null;
$application_no = $case_data['application_no'] ?? 'N/A';

// Get client name
$client_id = $case_data['client_id'] ?? 0;
$client_info = get_data('clients', $client_id);
$client_name = $client_info['data']['name'] ?? 'Unknown Client';

?>
<link rel="stylesheet" href="../system/css/json_table.css">
<script src="../system/js/json_table.js"></script>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                        <strong>Financial Data Entry</strong>
                    </h4>
                    <p class="text-muted mb-0">
                        Case #<?php echo $case_id; ?> | 
                        Client: <strong><?php echo htmlspecialchars($client_name); ?></strong> | 
                        App No: <strong><?php echo htmlspecialchars($application_no); ?></strong>
                    </p>
                </div>
                <a href="add_new_case.php?step=3&case_id=<?php echo $case_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>

            <!-- Form Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form id="financialForm" method="POST" action="save_case_step.php">
                        <input type="hidden" name="action" value="update_case_task">
                        <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
                        <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                        <input type="hidden" name="task_id" value="<?php echo $task_row['task_template_id']; ?>">
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                        <input type="hidden" name="task_status" value="<?php echo $task_row['task_status']; ?>">
                        
                        <!-- All other existing meta fields should be preserved -->
                        <?php 
                        foreach($existing_data as $key => $val) {
                            if($key != $field_name && !is_array($val)) {
                                echo '<input type="hidden" name="task_meta['.htmlspecialchars($key).']" value="'.htmlspecialchars($val).'">';
                            }
                        }
                        ?>

                        <div id="json_table_container_<?php echo $field_name; ?>"></div>
                        <input type="hidden" name="task_meta[<?php echo $field_name; ?>]" id="json_table_input_<?php echo $field_name; ?>" value="">

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                                <i class="fas fa-save me-2"></i> Save Financial Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize the table
    const config = <?php echo $table_config; ?>;
    const existingData = <?php echo json_encode($existing_data[$field_name] ?? null); ?>;
    
    initJsonTable("<?php echo $field_name; ?>", config, existingData);

    // Form submission
    $('#financialForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');
        
        $.ajax({
            url: 'save_case_step.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success' || response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Financial data updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'add_new_case.php?step=3&case_id=<?php echo $case_id; ?>';
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to save data', 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                Swal.fire('Error', 'Network error or server-side failure', 'error');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<?php require_once('../system/footer.php'); ?>
