<?php
/**
 * AJAX Handler to get Task Edit form body
 */
require_once('../system/op_lib.php');
require_once('../function.php');

if (!isset($_SESSION['user_id'])) {
    die('<div class="alert alert-danger">Session expired. Please login again.</div>');
}

$case_task_id = isset($_GET['case_task_id']) ? intval($_GET['case_task_id']) : 0;
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;

if (!$case_task_id || !$task_id) {
    die('<div class="alert alert-danger">Invalid parameters.</div>');
}

// Get case task data
$case_task = get_data('case_tasks', $case_task_id);
if ($case_task['count'] == 0) {
    die('<div class="alert alert-danger">Case task not found.</div>');
}

$case_task_data = $case_task['data'];
$case_id = $case_task_data['case_id'];

// Get task template
$task_template = get_data('tasks', $task_id);
if ($task_template['count'] == 0) {
    die('<div class="alert alert-danger">Task template not found.</div>');
}

$task_template_data = $task_template['data'];
$task_name = $task_template_data['task_name'];
$task_type = $task_template_data['task_type'];

// Parse existing task data
$existing_task_data = json_decode($case_task_data['task_data'] ?? '{}', true);
if (!is_array($existing_task_data)) {
    $existing_task_data = [];
}

// Get case info for extra context if needed
$case_info = get_data('cases', $case_id);
$case_data = $case_info['count'] > 0 ? $case_info['data'] : null;
$client_id = $case_data ? $case_data['client_id'] : 0;
?>

<form id="ajaxEditTaskForm">
    <input type="hidden" name="action" value="update_case_task">
    <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
    <input type="hidden" name="ajax" value="1">

    <div id="ajaxFormError" class="alert alert-danger" style="display:none;"></div>
    <div id="ajaxFormSuccess" class="alert alert-success" style="display:none;"></div>

    <div class="row">
        <!-- Task Status and Assignment -->
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold small text-uppercase">Task Status</label>
            <select name="task_status" class="form-select">
                <option value="PENDING" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'PENDING' ? 'selected' : ''; ?>>Fresh Case</option>
                <option value="IN_PROGRESS" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'IN_PROGRESS' ? 'selected' : ''; ?>>Assigned</option>
                <option value="VERIFICATION_COMPLETED" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'VERIFICATION_COMPLETED' ? 'selected' : ''; ?>>Verified</option>
                <option value="COMPLETED" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'COMPLETED' ? 'selected' : ''; ?>>Reviewed</option>
                <option value="REJECTED" <?php echo ($case_task_data['task_status'] ?? 'PENDING') == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold small text-uppercase">Assign To Verifier</label>
            <select name="assigned_to" class="form-select">
                <option value="">-- Not Assigned --</option>
                <?php
                global $con;
                $verifier_sql = "SELECT id, verifier_name, verifier_mobile FROM verifier WHERE status = 'ACTIVE' ORDER BY verifier_name ASC";
                $verifier_res = mysqli_query($con, $verifier_sql);
                if ($verifier_res && mysqli_num_rows($verifier_res) > 0) {
                    while ($verifier = mysqli_fetch_assoc($verifier_res)) {
                        $selected = ($case_task_data['assigned_to'] ?? 0) == $verifier['id'] ? 'selected' : '';
                        $display_name = htmlspecialchars($verifier['verifier_name']);
                        if (!empty($verifier['verifier_mobile'])) {
                            $display_name .= ' (' . htmlspecialchars($verifier['verifier_mobile']) . ')';
                        }
                        echo '<option value="' . $verifier['id'] . '" ' . $selected . '>' . $display_name . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <hr class="my-3">
    
    <h6 class="mb-3 fw-bold"><i class="fas fa-list-ul me-2"></i>Task Data Fields</h6>
    <div class="row">
        <?php
        $sql = "SELECT field_name, display_name, input_type, default_value, is_required, by_client, by_verifier, by_findings 
                FROM tasks_meta WHERE task_id = '$task_id' AND status = 'ACTIVE' ORDER BY id ASC";
        $res = mysqli_query($con, $sql);

        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $name = htmlspecialchars($row['field_name']);
                $label = htmlspecialchars($row['display_name']);
                $type = strtoupper(trim($row['input_type']));
                $default_value = htmlspecialchars($row['default_value'] ?? '');
                $is_required = (strtoupper($row['is_required'] ?? 'NO') == 'YES');
                $existing_value = isset($existing_task_data[$row['field_name']]) 
                                  ? htmlspecialchars($existing_task_data[$row['field_name']]) 
                                  : $default_value;

                // ROBUST TABLE DETECTION
                $is_json_table = ($type == 'JSON_TABLE' || $type == 'COMPARISON_TABLE');
                $raw_val = isset($existing_task_data[$row['field_name']]) ? $existing_task_data[$row['field_name']] : null;
                
                if (!$is_json_table && !empty($raw_val)) {
                    $check_data = $raw_val;
                    if (is_string($check_data) && (strpos($check_data, '{') === 0 || strpos($check_data, '[') === 0)) {
                        $check_data = json_decode($check_data, true);
                    }
                    if (is_array($check_data)) {
                        if (isset($check_data['P & L Statement']) || isset($check_data['Balance Sheet Statement'])) {
                            $is_json_table = true;
                        } else {
                            $first_r = @reset($check_data);
                            if (is_array($first_r) && isset($first_r['section']) && isset($first_r['particular'])) {
                                $is_json_table = true;
                            }
                        }
                    }
                }

                $field_id = preg_replace('/[^a-zA-Z0-9_]/', '_', $row['field_name']);

                $col_class = ($type == 'TEXTAREA' || $is_json_table) ? 'col-12' : 'col-md-6';
                ?>
                <div class="<?php echo $col_class; ?> mb-3">
                    <label class="form-label small fw-bold">
                        <?php echo $label; ?>
                        <?php if ($is_required) echo ' <span class="text-danger">*</span>'; ?>
                    </label>
                    <?php
                    switch ($type) {
                        case 'DATE':
                            echo '<input type="date" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" ' . ($is_required ? 'required' : '') . '>';
                            break;
                        case 'NUMBER':
                            echo '<input type="number" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" step="any" ' . ($is_required ? 'required' : '') . '>';
                            break;
                        case 'TEXTAREA':
                            echo '<textarea name="task_meta[' . $name . ']" class="form-control" rows="3" ' . ($is_required ? 'required' : '') . '>' . $existing_value . '</textarea>';
                            break;
                        case 'JSON_TABLE':
                        case 'COMPARISON_TABLE':
                        default:
                            if ($is_json_table) {
                                $config_obj = json_decode(htmlspecialchars_decode($default_value ?: '{}'), true);
                                $config_js = json_encode($config_obj ?: new stdClass());
                                $json_data = isset($existing_task_data[$row['field_name']]) ? json_encode($existing_task_data[$row['field_name']]) : 'null';
                                ?>
                                <div id="modal_json_table_container_<?php echo $field_id; ?>" class="json-table-wrapper"></div>
                                <input type="hidden" name="task_meta[<?php echo $f_name; ?>]" id="modal_json_table_input_<?php echo $field_id; ?>" value="">
                                <script>
                                    (function() {
                                        const f_id = <?php echo json_encode($field_id); ?>;
                                        const f_conf = <?php echo !empty($row['default_value']) ? $row['default_value'] : '{}'; ?>;
                                        const f_dt = <?php echo json_encode($raw_val); ?>;
                                        const initTbl = () => {
                                            if (typeof initJsonTable === "function") {
                                                // Note: we use a different prefix for modal to avoid ID collisions
                                                initJsonTable(f_id, f_conf, f_dt, "modal_json_table_");
                                            } else { setTimeout(initTbl, 150); }
                                        };
                                        initTbl();
                                    })();
                                </script>
                                <?php
                            } else {
                                echo '<input type="text" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" ' . ($is_required ? 'required' : '') . '>';
                            }
                            break;
                    }
                    ?>
                </div>
            <?php }
        } else {
            echo '<div class="col-12"><div class="alert alert-info">No fields configured.</div></div>';
        }
        ?>
    </div>
</form>
