<?php
require_once('../system/op_lib.php');
require_once('../function.php');

$case_task_id = isset($_GET['case_task_id']) ? intval($_GET['case_task_id']) : 0;
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;

if (!$case_task_id || !$task_id) {
    echo '<div class="alert alert-danger">Invalid parameters.</div>';
    exit;
}

$case_task = get_data('case_tasks', $case_task_id);
if ($case_task['count'] == 0) {
    echo '<div class="alert alert-danger">Task data not found.</div>';
    exit;
}

$case_task_data = $case_task['data'];
$existing_task_data = json_decode($case_task_data['task_data'] ?? '{}', true);

// Fetch client_id for save_case_step.php
$case_res = get_data('cases', $case_task_data['case_id']);
$client_id = $case_res['count'] > 0 ? $case_res['data']['client_id'] : 0;

// Include necessary IDs for save_case_step.php
echo '<input type="hidden" name="case_id" value="' . $case_task_data['case_id'] . '">';
echo '<input type="hidden" name="client_id" value="' . $client_id . '">';
echo '<input type="hidden" name="task_id" value="' . $task_id . '">';

$task_meta_fields = get_all('tasks_meta', '*', ['task_id' => $task_id, 'status' => 'ACTIVE'], 'id ASC');

if ($task_meta_fields['count'] > 0) {
    echo '<div class="row">';
    foreach ($task_meta_fields['data'] as $row) {
        $name = htmlspecialchars($row['field_name']);
        $label = htmlspecialchars($row['display_name']);
        $type = strtoupper(trim($row['input_type']));
        $default_value = htmlspecialchars($row['default_value'] ?? '');
        $is_required = (strtoupper($row['is_required'] ?? 'NO') == 'YES');
        
        $raw_value = isset($existing_task_data[$row['field_name']]) ? $existing_task_data[$row['field_name']] : '';
        $existing_value = is_array($raw_value) ? json_encode($raw_value) : htmlspecialchars($raw_value ?? '');
        
        if (empty($existing_value) && !empty($default_value)) {
            $existing_value = $default_value;
        }

        $is_json_table = false;
        if ($type == 'JSON_TABLE' || $type == 'COMPARISON_TABLE') {
            $is_json_table = true;
        } else {
            // Check for valid JSON structure in raw value (array or string)
            $check_data = $raw_value;
            if (is_string($check_data) && (strpos($check_data, '{') === 0 || strpos($check_data, '[') === 0)) {
                $check_data = json_decode($check_data, true);
            }
            
            if (is_array($check_data)) {
                if (isset($check_data['P & L Statement']) || isset($check_data['Balance Sheet Statement'])) {
                    $is_json_table = true;
                } else {
                    $first_row = @reset($check_data);
                    if (is_array($first_row) && isset($first_row['section']) && isset($first_row['particular'])) {
                        $is_json_table = true;
                    }
                }
            }
        }

        $col_class = 'col-md-6';
        if ($type == 'TEXTAREA' || $is_json_table) {
            $col_class = 'col-md-12';
        }

        echo '<div class="' . $col_class . ' mb-3">';
        echo '<label class="form-label"><strong>' . $label . '</strong></label>';

        switch ($type) {
            case 'DATE':
                echo '<input type="date" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '">';
                break;
            case 'NUMBER':
                echo '<input type="number" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '" step="any">';
                break;
            case 'TEXTAREA':
                echo '<textarea name="task_meta[' . $name . ']" class="form-control" rows="3">' . $existing_value . '</textarea>';
                break;
            default:
                if ($is_json_table) {
                    $config_obj = json_decode(htmlspecialchars_decode($default_value ?: '{}'), true);
                    $config_js = json_encode($config_obj ?: new stdClass());
                    
                    // Always use json_encode on the raw value for JS injection to be safe
                    $json_data_js = (!empty($raw_value) || is_array($raw_value)) ? json_encode($raw_value) : 'null';

                    echo '<div id="json_table_container_' . $name . '" class="json-table-wrapper"></div>';
                    echo '<input type="hidden" name="task_meta[' . $name . ']" id="json_table_input_' . $name . '" value="">';
                    echo '<script>
                        (function() {
                            const initEditTable = () => {
                                if (typeof initJsonTable === "function") {
                                    initJsonTable("' . $name . '", ' . $config_js . ', ' . $json_data_js . ');
                                } else {
                                    setTimeout(initEditTable, 100);
                                }
                            };
                            initEditTable();
                        })();
                    </script>';
                } else {
                    echo '<input type="text" name="task_meta[' . $name . ']" class="form-control" value="' . $existing_value . '">';
                }
                break;
        }
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<div class="alert alert-info">No fields to edit.</div>';
}
?>
