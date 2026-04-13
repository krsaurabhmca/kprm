<?php
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once('system/op_lib.php');

function btn_meta($table, $id, $link, $icon = 'link')
{
    $view_link = $link . '?link=' . encode('table=' . $table . '&id=' . $id);
    $str = "<a class='btn btn-dark btn-sm' href='$view_link' ><i class='fa fa-$icon'></i></a>";
    return $str;
}

function btn_stucture($table, $id, $link, $icon = 'cubes')
{
    $view_link = $link . '?link=' . encode('table=' . $table . '&id=' . $id);
    $str = " <a class='btn btn-danger btn-sm' href='$view_link' ><i class='fa fa-$icon'></i></a>";
    return $str;
}

/**
 * Extract variables from report format text
 */
function extract_variables_from_text($text)
{
    if (empty($text))
        return [];
    preg_match_all('/#([a-zA-Z0-9_]+)#/', $text, $matches);
    return array_unique($matches[1]);
}

/**
 * Get field properties based on variable name
 */
function get_field_properties_from_variable($var_name)
{
    $properties = [
        'field_name' => $var_name,
        'display_name' => ucwords(str_replace('_', ' ', $var_name)),
        'input_type' => 'TEXT',
        'by_client' => 'NO',
        'by_verifier' => 'NO',
        'by_findings' => 'NO',
        'is_required' => 'NO'
    ];

    $var_lower = strtolower($var_name);

    // Date fields
    if (
        strpos($var_lower, 'date') !== false || strpos($var_lower, 'dob') !== false ||
        strpos($var_lower, 'joining') !== false || strpos($var_lower, 'registration') !== false ||
        strpos($var_lower, 'closing') !== false || strpos($var_lower, 'dorf') !== false
    ) {
        $properties['input_type'] = 'DATE';
    }

    // Number fields
    if (
        strpos($var_lower, 'amount') !== false || strpos($var_lower, 'income') !== false ||
        strpos($var_lower, 'rent') !== false || strpos($var_lower, 'fee') !== false ||
        strpos($var_lower, 'area') !== false || strpos($var_lower, 'rate') !== false ||
        strpos($var_lower, 'tax') !== false || strpos($var_lower, 'turnover') !== false ||
        strpos($var_lower, 'outstanding') !== false || strpos($var_lower, 'payment') !== false ||
        strpos($var_lower, 'students') !== false || strpos($var_lower, 'teacher') !== false ||
        strpos($var_lower, 'staff') !== false || strpos($var_lower, 'family') !== false
    ) {
        $properties['input_type'] = 'NUMBER';
    }

    // Textarea fields
    if (
        strpos($var_lower, 'remark') !== false || strpos($var_lower, 'address') !== false ||
        strpos($var_lower, 'transaction') !== false || strpos($var_lower, 'tpc') !== false
    ) {
        $properties['input_type'] = 'TEXTAREA';
    }

    // Client provided fields
    if (
        strpos($var_lower, 'applicant') !== false || strpos($var_lower, 'document_no') !== false ||
        strpos($var_lower, 'pan') !== false || strpos($var_lower, 'aadhar') !== false ||
        strpos($var_lower, 'bank') !== false || strpos($var_lower, 'account') !== false ||
        strpos($var_lower, 'financial_year') !== false || strpos($var_lower, 'ay') !== false ||
        strpos($var_lower, 'tenant') !== false || strpos($var_lower, 'seller') !== false ||
        strpos($var_lower, 'dealer') !== false
    ) {
        $properties['by_client'] = 'YES';
        $properties['is_required'] = 'YES';
    }

    // Verifier fields
    if (
        strpos($var_lower, 'met_with') !== false || strpos($var_lower, 'verification') !== false ||
        strpos($var_lower, 'locality') !== false || strpos($var_lower, 'ownership') !== false ||
        strpos($var_lower, 'nob') !== false || strpos($var_lower, 'time_period') !== false ||
        strpos($var_lower, 'business_period') !== false
    ) {
        $properties['by_verifier'] = 'YES';
    }

    // System generated fields
    if (strpos($var_lower, 'status') !== false) {
        $properties['by_findings'] = 'YES';
        $properties['input_type'] = 'SELECT';
    }

    return $properties;
}

/**
 * Display name mapping for common variables
 */
function get_display_name_for_variable($var_name)
{
    $display_name_map = [
        'applicant_name' => 'Applicant Name',
        'document_no' => 'Document Number',
        'address' => 'Address',
        'met_with' => 'Met With',
        'time_period' => 'Time Period',
        'business_period' => 'Business Period',
        'ownership' => 'Ownership',
        'nob' => 'Nature of Business',
        'locality' => 'Locality',
        'area' => 'Area',
        'tpc' => 'TPC Confirmation',
        'business_name_office_name' => 'Business/Office Name',
        'owner_name' => 'Owner Name',
        'type_of_firm' => 'Type of Firm',
        'designation' => 'Designation',
        'financial_year' => 'Financial Year',
        'ay' => 'Assessment Year',
        'ack_no' => 'ACK Number',
        'dorf' => 'Date of Return Filing',
        'total_income' => 'Total Income',
        'employer_name' => 'Employer Name',
        'tax_amount' => 'Tax Amount',
        'assesment_no' => 'Assessment Number',
        'father_name' => 'Father Name',
        'date_of_birth' => 'Date of Birth',
        'member_id' => 'Member ID',
        'date_of_joining' => 'Date of Joining',
        'trade_name' => 'Trade Name',
        'date_of_registration' => 'Date of Registration',
        'proprietor_name' => 'Proprietor Name',
        'bank_name' => 'Bank Name',
        'account_no' => 'Account Number',
        'transction' => 'Transaction',
        'pan_no' => 'PAN Number',
        'aod' => 'AOD',
        'closing_date' => 'Closing Date',
        'outstanding' => 'Outstanding Amount',
        'family' => 'Family Members',
        'market_rate' => 'Market Rate',
        'seller_name' => 'Seller Name',
        'sale_amount' => 'Sale Amount',
        'advance_amount' => 'Advance Amount',
        'tenant_name' => 'Tenant Name',
        'owner_name_landlord_name' => 'Owner/Landlord Name',
        'rent' => 'Rent',
        'patwari_name' => 'Patwari Name',
        'dealer_name' => 'Dealer Name',
        'full_amount' => 'Full Amount',
        'down_payment' => 'Down Payment',
        'school_classes' => 'School Classes',
        'students' => 'Number of Students',
        'teacher' => 'Number of Teachers',
        'all_staff' => 'Total Staff',
        'school_fee' => 'School Fee',
        'requirement' => 'Requirement',
        'total_turnover' => 'Total Turnover',
        'igst' => 'IGST',
        'cgst' => 'CGST',
        'sgst' => 'SGST',
        'mobile_no' => 'Mobile Number',
        'status' => 'Status'
    ];

    return $display_name_map[$var_name] ?? ucwords(str_replace('_', ' ', $var_name));
}

/**
 * Render a financial table (JSON data) in a professional read-only format
 * @param mixed $data The JSON string or array to render
 * @return string HTML table
 */
function render_financial_table_readonly($data)
{
    if (empty($data))
        return '<span class="text-muted fst-italic">Not filled</span>';

    // Decode if string
    if (is_string($data) && (strpos($data, '{') === 0 || strpos($data, '[') === 0)) {
        $temp = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $temp;
        }
    }

    if (!is_array($data)) {
        return htmlspecialchars($data);
    }

    // Check if it's actually our dynamic table structure
    $is_dynamic_table = false;
    foreach ($data as $row) {
        if (is_array($row) && isset($row['section']) && isset($row['particular'])) {
            $is_dynamic_table = true;
            break;
        }
    }

    if (!$is_dynamic_table) {
        return '<pre class="mb-0" style="font-size: 11px; white-space: pre-wrap;">' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
    }

    // Group by section
    $sections = [];
    foreach ($data as $row_data) {
        $sec = $row_data['section'] ?? 'General';
        $sections[$sec][] = $row_data;
    }

    $html = '<div class="json-table-wrapper">';
    $html .= '<table class="jt-table">';
    foreach ($sections as $secName => $rows) {
        $html .= '<tr class="jt-header-section"><th colspan="5">' . htmlspecialchars($secName) . '</th></tr>';
        $html .= '<tr class="jt-header-cols">
                <th>Particular</th>
                <th style="width:150px">Amount as per Provided copy</th>
                <th style="width:150px">Amount as per ITO record</th>
                <th>Remark</th>
               </tr>';
        foreach ($rows as $r) {
            $provided = $r['provided'] ?? '';
            $ito = $r['ito'] ?? '';
            $remark_info = get_financial_remark($provided, $ito);
            $row_style = $remark_info['style'] ?? '';
            
            // Calculate Diff for display
            $p = floatval(str_replace(',', '', $provided));
            $i = floatval(str_replace(',', '', $ito));
            $diff = $i - $p;
            $diffText = (is_numeric($p) && is_numeric($i)) ? number_format($diff, 2) : '0.00';
            $diff_color = (abs($diff) > 0.01) ? '#dc3545' : '#198754';

            $html .= '<tr class="jt-row" style="' . $row_style . '">';
            $html .= '<td class="jt-particular">' . htmlspecialchars($r['particular'] ?? '') . '</td>';
            $html .= '<td class="text-end px-2">' . htmlspecialchars($provided) . '</td>';
            $html .= '<td class="text-end px-2">' . htmlspecialchars($ito) . '</td>';
            
            $disp_remark = !empty($r['remark']) ? $r['remark'] : $remark_info['text'];
            $html .= '<td class="px-2">' . htmlspecialchars($disp_remark) . '</td>';
            $html .= '</tr>';
        }
    }
    $html .= '</table>';
    $html .= '</div>';
    return $html;
}

/**
 * Update tasks_meta from task report formats
 * Extracts variables from positive_format, negative_format, and cnv_format
 */
function update_tasks_meta_from_formats($task_id, $positive_format = '', $negative_format = '', $cnv_format = '')
{
    global $con;
    global $user_id;

    if (empty($user_id)) {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    }

    // Extract variables from all formats
    $all_variables = [];
    $all_variables = array_merge($all_variables, extract_variables_from_text($positive_format));
    $all_variables = array_merge($all_variables, extract_variables_from_text($negative_format));
    $all_variables = array_merge($all_variables, extract_variables_from_text($cnv_format));
    $all_variables = array_unique($all_variables);

    if (empty($all_variables)) {
        return ['success' => true, 'message' => 'No variables found in formats'];
    }

    $inserted = 0;
    $updated = 0;
    $order = 1;

    foreach ($all_variables as $var) {
        $props = get_field_properties_from_variable($var);
        $display_name = get_display_name_for_variable($var);

        $var_escaped = mysqli_real_escape_string($con, $var);
        $task_id_escaped = mysqli_real_escape_string($con, $task_id);
        $display_name_escaped = mysqli_real_escape_string($con, $display_name);
        $input_type_escaped = mysqli_real_escape_string($con, $props['input_type']);

        // Check if field already exists
        $checkSql = "
            SELECT id
            FROM tasks_meta
            WHERE task_id = '$task_id_escaped'
              AND field_name = '$var_escaped'
            LIMIT 1
        ";

        $checkRes = mysqli_query($con, $checkSql);

        if (!$checkRes) {
            continue; // Skip on error
        }

        if (mysqli_num_rows($checkRes) > 0) {
            // UPDATE existing meta
            $row = mysqli_fetch_assoc($checkRes);
            $meta_id = $row['id'];

            $updateSql = "
                UPDATE tasks_meta SET
                    display_name = '$display_name_escaped',
                    input_type = '$input_type_escaped',
                    by_client = '{$props['by_client']}',
                    by_verifier = '{$props['by_verifier']}',
                    by_findings = '{$props['by_findings']}',
                    updated_at = NOW(),
                    updated_by = '$user_id'
                WHERE id = '$meta_id'
            ";

            if (mysqli_query($con, $updateSql)) {
                $updated++;
            }
        } else {
            // INSERT new meta
            $insertSql = "
                INSERT INTO tasks_meta
                (
                    task_id,
                    field_name,
                    display_name,
                    input_type,
                    by_client,
                    by_verifier,
                    by_findings,
                    status,
                    created_at,
                    created_by
                )
                VALUES
                (
                    '$task_id_escaped',
                    '$var_escaped',
                    '$display_name_escaped',
                    '$input_type_escaped',
                    '{$props['by_client']}',
                    '{$props['by_verifier']}',
                    '{$props['by_findings']}',
                    'ACTIVE',
                    NOW(),
                    '$user_id'
                )
            ";

            if (mysqli_query($con, $insertSql)) {
                $inserted++;
            }
        }
        $order++;
    }

    return [
        'success' => true,
        'inserted' => $inserted,
        'updated' => $updated,
        'total' => count($all_variables)
    ];
}

// Legacy function for backward compatibility
function get_task_meta_from_template($task_id, $report_template)
{
    return update_tasks_meta_from_formats($task_id, $report_template, '', '');
}

function build_client_meta_form($client_id, $existing_values = [])
{
    global $con;
    $html = '';

    $sql = "
        SELECT field_name, display_name, input_type, default_value, is_unique
        FROM clients_meta
        WHERE client_id = '$client_id'
          AND status = 'ACTIVE'
          AND by_client = 'YES'
        ORDER BY id ASC
    ";

    $res = mysqli_query($con, $sql);

    if (!$res || mysqli_num_rows($res) == 0) {
        return '<div class="alert alert-info">No client fields configured. Click Continue to proceed.</div>';
    }

    $html .= '<div class="row">';

    while ($row = mysqli_fetch_assoc($res)) {
        $name = htmlspecialchars($row['field_name']);
        $label = htmlspecialchars($row['display_name']);
        $type = strtoupper($row['input_type']);

        // Use existing value if available, otherwise use default (but not for SELECT with comma-separated lists)
        $value = '';
        if (isset($existing_values[$row['field_name']]) && !empty($existing_values[$row['field_name']])) {
            $value = htmlspecialchars($existing_values[$row['field_name']]);
        } elseif (!empty($row['default_value']) && strtoupper($type) !== 'SELECT') {
            // For non-SELECT fields, use default_value as default
            $value = htmlspecialchars($row['default_value']);
        } elseif (!empty($row['default_value']) && strtoupper($type) === 'SELECT' && strpos($row['default_value'], ',') === false) {
            // For SELECT fields, only use default_value if it's a single value (not comma-separated)
            $value = htmlspecialchars(trim($row['default_value']));
        }

        $required = ($row['is_unique'] == 'YES') ? 'required' : '';

        $html .= '<div class="col-md-4 mb-3">';
        $html .= "<label><strong>{$label}</strong>";
        if ($required) {
            $html .= " <span class='text-danger'>*</span>";
        }
        $html .= "</label>";

        switch ($type) {
            case 'DATE':
                $html .= "<input type='date' name='client_meta[{$name}]' class='form-control' value='{$value}' {$required}>";
                break;

            case 'NUMBER':
                $html .= "<input type='number' name='client_meta[{$name}]' class='form-control' value='{$value}' {$required}>";
                break;

            case 'SELECT':
                $html .= "<select name='client_meta[{$name}]' class='form-select' {$required}>";
                $html .= "<option value=''>Select {$label}</option>";

                // Check if default_value contains a comma-separated list
                $options = [];
                if (!empty($row['default_value'])) {
                    // Check if it's a comma-separated list
                    if (strpos($row['default_value'], ',') !== false) {
                        // Split by comma and trim each value
                        $options = array_map('trim', explode(',', $row['default_value']));
                        $options = array_filter($options); // Remove empty values
                    } else {
                        // Single value, add it as an option
                        $options = [trim($row['default_value'])];
                    }
                }

                // Generate options from the list
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $option_escaped = htmlspecialchars($option);
                        $selected = (!empty($value) && $value === $option) ? 'selected' : '';
                        $html .= "<option value='{$option_escaped}' {$selected}>{$option_escaped}</option>";
                    }
                } else {
                    // If no options from default_value but value exists, show it
                    if (!empty($value)) {
                        $value_escaped = htmlspecialchars($value);
                        $html .= "<option value='{$value_escaped}' selected>{$value_escaped}</option>";
                    }
                }

                $html .= "</select>";
                break;

            case 'TEXTAREA':
                $html .= "<textarea name='client_meta[{$name}]' class='form-control' rows='3' {$required}>" . $value . "</textarea>";
                break;

            case 'TEXT':
            default:
                $html .= "<input type='text' name='client_meta[{$name}]' class='form-control' value='{$value}' {$required}>";
                break;
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

function build_task_meta_form($task_id)
{
    global $con;
    $html = '';

    $sql = "
        SELECT field_name, display_name, input_type, default_value, by_client, by_verifier, by_findings
        FROM tasks_meta
        WHERE task_id = '$task_id'
          AND status = 'ACTIVE'
        ORDER BY id ASC
    ";

    $res = mysqli_query($con, $sql);

    if (!$res || mysqli_num_rows($res) == 0) {
        return '<div class="alert alert-info">No task fields configured for this task.</div>';
    }

    $html .= '<div class="row">';

    while ($row = mysqli_fetch_assoc($res)) {
        $name = htmlspecialchars($row['field_name']);
        $label = htmlspecialchars($row['display_name']);
        $type = strtoupper($row['input_type']);
        $value = htmlspecialchars($row['default_value'] ?? '');
        $by_client = ($row['by_client'] == 'YES');
        $by_verifier = ($row['by_verifier'] == 'YES');
        $by_findings = ($row['by_findings'] == 'YES');

        // Show only client-provided fields in case entry (by_client = YES)
        if (!$by_client) {
            continue;
        }

        $html .= '<div class="col-md-4 mb-3">';
        $html .= "<label><strong>{$label}</strong>";
        if ($by_client) {
            $html .= " <span class='text-danger'>*</span>";
        }
        $html .= "</label>";

        switch ($type) {
            case 'DATE':
                $html .= "<input type='date' name='task_meta[{$name}]' class='form-control' value='{$value}' required>";
                break;

            case 'NUMBER':
                $html .= "<input type='number' name='task_meta[{$name}]' class='form-control' value='{$value}' required>";
                break;

            case 'SELECT':
                $html .= "<select name='task_meta[{$name}]' class='form-control' required>";
                $html .= "<option value=''>Select {$label}</option>";
                // TODO: Load options from master/config
                $html .= "</select>";
                break;

            case 'TEXTAREA':
                $html .= "<textarea name='task_meta[{$name}]' class='form-control' rows='3' required>{$value}</textarea>";
                break;

            case 'TEXT':
            default:
                $html .= "<input type='text' name='task_meta[{$name}]' class='form-control' value='{$value}' required>";
                break;
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

// Legacy function for backward compatibility
function build_client_form($client_id)
{
    return build_client_meta_form($client_id);
}

/**
 * Build HTML for all attachments with thumbnails and document icons
 * 
 * @param array $images_list Array of image attachment records
 * @param array $documents_list Array of document attachment records
 * @param string $base_url Base URL for file paths
 * @return string HTML string for attachments display
 */
function build_all_attachments_html($images_list, $documents_list, $base_url)
{
    $html = '';

    // Function to get file icon based on file type/extension
    $get_file_icon = function ($file_type, $file_name) {
        $file_type_lower = strtolower($file_type ?? '');
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Determine icon based on file type or extension
        if (strpos($file_type_lower, 'pdf') !== false || $file_ext === 'pdf') {
            return '📄'; // PDF icon
        } elseif (strpos($file_type_lower, 'word') !== false || in_array($file_ext, ['doc', 'docx'])) {
            return '📝'; // Word document icon
        } elseif (strpos($file_type_lower, 'excel') !== false || in_array($file_ext, ['xls', 'xlsx'])) {
            return '📊'; // Excel icon
        } elseif (strpos($file_type_lower, 'text') !== false || $file_ext === 'txt') {
            return '📃'; // Text file icon
        } else {
            return '📎'; // Generic document icon
        }
    };

    // Display Images (2 per row as thumbnails)
    if (!empty($images_list)) {
        $html .= '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="margin-bottom: 10px; font-size: 16px; font-weight: bold;">Images</h4>';
        $html .= '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';

        foreach ($images_list as $img) {
            $file_url = $base_url . 'upload/' . $img['file_url'];
            $file_name = htmlspecialchars($img['file_name'] ?? 'Image');

            $html .= '<div style="flex: 0 0 calc(50% - 5px); max-width: calc(50% - 5px); margin-bottom: 10px;">';
            $html .= '<div style="border: 1px solid #ddd; padding: 5px; text-align: center; background: #f9f9f9;">';
            $html .= '<img src="' . htmlspecialchars($file_url) . '" alt="' . $file_name . '" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">';
            $html .= '<div style="margin-top: 5px; font-size: 12px; color: #666;">' . $file_name . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }

    // Display Documents with icons and download links
    if (!empty($documents_list)) {
        $html .= '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="margin-bottom: 10px; font-size: 16px; font-weight: bold;">Documents</h4>';
        $html .= '<div style="display: flex; flex-direction: column; gap: 8px;">';

        foreach ($documents_list as $doc) {
            $file_url = $base_url . 'upload/' . $doc['file_url'];
            $file_name = htmlspecialchars($doc['file_name'] ?? 'Document');
            $file_type = htmlspecialchars($doc['file_type'] ?? 'Unknown');
            $icon = $get_file_icon($doc['file_type'] ?? '', $doc['file_name'] ?? '');

            $html .= '<div style="display: flex; align-items: center; padding: 8px; border: 1px solid #ddd; background: #fff;">';
            $html .= '<span style="font-size: 24px; margin-right: 10px;">' . $icon . '</span>';
            $html .= '<div style="flex: 1;">';
            $html .= '<div style="font-weight: bold; margin-bottom: 2px;">' . $file_name . '</div>';
            $html .= '<div style="font-size: 12px; color: #666;">' . $file_type . '</div>';
            $html .= '</div>';
            $html .= '<a href="' . htmlspecialchars($file_url) . '" download style="padding: 6px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">📥 Download</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }

    return $html;
}

/**
 * Generate Report from HTML Template
 * Simple function that takes HTML and replaces placeholders with data
 * 
 * @param string $html_template HTML template with {{placeholder}} format
 * @param int $case_id Case ID (optional, for case-specific data)
 * @param int $client_id Client ID (optional, for client-specific data)
 * @param array $custom_data Custom data array to override defaults
 * @return array ['success' => bool, 'html' => string, 'message' => string]
 */
function generate_report_from_html($html_template, $case_id = null, $client_id = null, $custom_data = [])
{
    global $con;
    global $base_url;
    global $CONFIG;

    // Ensure base_url is set
    if (!isset($base_url) || empty($base_url)) {
        if (isset($CONFIG['base_url'])) {
            $base_url = $CONFIG['base_url'];
        } else {
            $base_url = 'http://localhost/kprm/';
        }
    }

    if (empty($html_template)) {
        return ['success' => false, 'html' => '', 'message' => 'HTML template is required'];
    }

    $html = $html_template;

    // Collect all data
    $data = [];

    // 1. Case Information
    if ($case_id) {
        $case_query = "SELECT * FROM cases WHERE id = '$case_id'";
        $case_result = mysqli_query($con, $case_query);
        if ($case_result && mysqli_num_rows($case_result) > 0) {
            $case_data = mysqli_fetch_assoc($case_result);
            // Use application_no from cases table
            $data['application_number'] = $case_data['application_no'] ?? '';
            $data['application_no'] = $case_data['application_no'] ?? '';
            $data['case_status'] = $case_data['case_status'] ?? '';
            // Get client_id from case if not provided
            if (!$client_id && isset($case_data['client_id'])) {
                $client_id = $case_data['client_id'];
            }
            // Note: product, region, state, branch, location, loan_amount are in case_info JSON
        }
    }

    // 2. Client Meta Data (from case_info JSON in cases table)
    if ($case_id) {
        // Get case_info JSON from cases table
        $case_info_query = "SELECT case_info FROM cases WHERE id = '$case_id'";
        $case_info_result = mysqli_query($con, $case_info_query);
        if ($case_info_result && mysqli_num_rows($case_info_result) > 0) {
            $case_info_row = mysqli_fetch_assoc($case_info_result);
            if (!empty($case_info_row['case_info'])) {
                $case_info_data = json_decode($case_info_row['case_info'], true);
                if (is_array($case_info_data)) {
                    // Add all client meta values from case_info
                    foreach ($case_info_data as $key => $value) {
                        if (!isset($data[$key])) {
                            $data[$key] = $value ?? '';
                        }
                    }
                }
            }
        }
    }

    // 3. Client Information
    $client_status_words = [
        'POSITIVE' => 'Positive',
        'NEGATIVE' => 'Negative',
        'CNV' => 'CNV'
    ];
    $agency_id = null;

    if ($client_id) {
        $client_query = "SELECT * FROM clients WHERE id = '$client_id'";
        $client_result = mysqli_query($con, $client_query);
        if ($client_result && mysqli_num_rows($client_result) > 0) {
            $client = mysqli_fetch_assoc($client_result);
            $data['client_name'] = $client['name'] ?? $client['client_name'] ?? '';
            // Get client status words for review status mapping
            $client_status_words['POSITIVE'] = $client['positve_status'] ?? 'Positive';
            $client_status_words['NEGATIVE'] = $client['negative_status'] ?? 'Negative';
            $client_status_words['CNV'] = $client['cnv_status'] ?? 'CNV';
            // Get agency_id from client
            $agency_id = $client['agency_id'] ?? null;
            // Also add individual client fields to data
            foreach ($client as $key => $value) {
                if (!isset($data[$key])) {
                    $data[$key] = $value;
                }
            }
        }

        // Note: Client meta field values are stored in case_info JSON (not retrieved separately)
        // If you need client meta field definitions without a case, they are available from clients_meta table
        // but their actual values come from case_info JSON when a case_id is provided
    }

    // 4. Task/Document Data (if case_id provided)
    $documents = [];
    $task_data = [];
    $all_task_names = [];
    $all_task_types = [];
    $all_task_remarks = [];

    // Make client_status_words available in this scope
    // (Already defined in client section above, but ensure it's available)
    if (!isset($client_status_words)) {
        $client_status_words = [
            'POSITIVE' => 'Positive',
            'NEGATIVE' => 'Negative',
            'CNV' => 'CNV'
        ];
    }

    if ($case_id) {
        // Get tasks from case_tasks table
        $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
        $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);

        if ($has_case_tasks) {
            // Use case_tasks table
            $tasks_query = "SELECT ct.*, t.task_name as template_task_name, t.task_type as template_task_type 
                           FROM case_tasks ct 
                           LEFT JOIN tasks t ON ct.task_template_id = t.id 
                           WHERE ct.case_id = '$case_id' AND ct.status = 'ACTIVE' 
                           ORDER BY ct.id ASC";
            $tasks_result = mysqli_query($con, $tasks_query);

            if ($tasks_result) {
                $task_count = mysqli_num_rows($tasks_result);
                $task_data['no_of_task'] = $task_count;

                while ($row = mysqli_fetch_assoc($tasks_result)) {
                    $task_name = $row['task_name'] ?? $row['template_task_name'] ?? '';
                    $task_type = $row['task_type'] ?? $row['template_task_type'] ?? '';
                    $task_status = $row['task_status'] ?? '';

                    // Collect unique task names and types
                    if (!empty($task_name) && !in_array($task_name, $all_task_names)) {
                        $all_task_names[] = $task_name;
                    }
                    if (!empty($task_type) && !in_array($task_type, $all_task_types)) {
                        $all_task_types[] = $task_type;
                    }

                    // Parse task_data JSON if exists
                    $task_meta = [];
                    $task_remarks = '';
                    $review_status = '';
                    if (!empty($row['task_data'])) {
                        $task_meta = json_decode($row['task_data'], true);
                        if (!is_array($task_meta)) {
                            $task_meta = [];
                        } else {
                            // Extract remarks from task_data JSON (review_remarks or verifier_remarks)
                            $task_remarks = $task_meta['review_remarks'] ?? $task_meta['verifier_remarks'] ?? '';
                            if (!empty($task_remarks) && !in_array($task_remarks, $all_task_remarks)) {
                                $all_task_remarks[] = $task_remarks;
                            }
                            // Extract review_status from task_data JSON
                            $review_status = $task_meta['review_status'] ?? '';
                        }
                    }

                    // Map review_status to client status words
                    // task_status placeholder should show only reviewer's review_status with client status words
                    $review_status_display = '';
                    if (!empty($review_status)) {
                        $review_status_display = $client_status_words[$review_status] ?? $review_status;
                    }

                    // Use review_status with client status words as the task status (not the database task_status)
                    $combined_status = $review_status_display; // Show only review_status with client words

                    $documents[] = [
                        'id' => $row['id'],
                        'task_type' => $task_type,
                        'task_name' => $task_name,
                        'status' => $combined_status, // This will be review_status with client words
                        'review_status' => $review_status, // Keep raw review_status for overall_status calculation
                        'remarks' => $task_remarks,
                        'particulars' => 'Document ' . (count($documents) + 1),
                        'meta' => $task_meta
                    ];
                }

                // Set aggregated task data (first task or combined)
                if (count($documents) > 0) {
                    $first_task = $documents[0];
                    // Task name with line breaks (join multiple task names with line breaks)
                    $task_data['task_name'] = !empty($all_task_names) ? implode('<br>', $all_task_names) : nl2br($first_task['task_name'] ?? '');
                    $task_data['task_type'] = !empty($all_task_types) ? implode(', ', $all_task_types) : ($first_task['task_type'] ?? '');
                    // Task remarks with line breaks (use <br> for HTML) - join multiple remarks with line breaks
                    $task_data['task_remarks'] = !empty($all_task_remarks) ? implode('<br>', array_map(function ($r) {
                        return nl2br($r);
                    }, $all_task_remarks)) : nl2br($first_task['remarks'] ?? '');
                    // Task status (line break separated if multiple) - shows review_status with client status words only
                    $all_task_statuses = [];
                    foreach ($documents as $doc) {
                        // Use review_status from the document, mapped to client status words
                        $doc_review_status = $doc['review_status'] ?? '';
                        if (!empty($doc_review_status)) {
                            $review_status_word = $client_status_words[$doc_review_status] ?? $doc_review_status;
                            if (!empty($review_status_word) && !in_array($review_status_word, $all_task_statuses)) {
                                $all_task_statuses[] = $review_status_word;
                            }
                        }
                    }
                    // Use review_status words, or fallback to status if no review_status
                    $task_data['task_status'] = !empty($all_task_statuses) ? implode('<br>', $all_task_statuses) : ($first_task['status'] ?? '');
                }
            }
        } else {
            // Fallback to old tasks table structure
            $tasks_query = "SELECT t.*, tm.field_name, tm.field_value 
                            FROM tasks t 
                            LEFT JOIN tasks_meta tm ON t.id = tm.task_id 
                            WHERE t.case_id = '$case_id' AND t.status = 'ACTIVE' 
                            ORDER BY t.id ASC";
            $tasks_result = mysqli_query($con, $tasks_query);

            $current_task = null;

            if ($tasks_result) {
                while ($row = mysqli_fetch_assoc($tasks_result)) {
                    if ($current_task === null || $current_task['id'] != $row['id']) {
                        if ($current_task !== null) {
                            $documents[] = $current_task;
                        }
                        $current_task = [
                            'id' => $row['id'],
                            'task_type' => $row['task_type'] ?? '',
                            'task_name' => $row['task_name'] ?? '',
                            'status' => $row['status'] ?? '',
                            'remarks' => $row['remarks'] ?? '',
                            'particulars' => 'Document ' . (count($documents) + 1),
                            'meta' => []
                        ];
                    }

                    if ($row['field_name']) {
                        $current_task['meta'][$row['field_name']] = $row['field_value'];
                    }
                }

                if ($current_task !== null) {
                    $documents[] = $current_task;
                }

                // Set task data
                if (count($documents) > 0) {
                    $first_task = $documents[0];
                    // Task name with line breaks
                    $task_data['task_name'] = nl2br($first_task['task_name'] ?? '');
                    $task_data['task_type'] = $first_task['task_type'] ?? '';
                    $task_data['no_of_task'] = count($documents);
                    // Task remarks with line breaks
                    $task_data['task_remarks'] = nl2br($first_task['remarks'] ?? '');
                    // Task status - shows review_status with client status words only
                    $first_review_status = $first_task['review_status'] ?? '';
                    if (!empty($first_review_status)) {
                        $task_data['task_status'] = $client_status_words[$first_review_status] ?? $first_review_status;
                    } else {
                        $task_data['task_status'] = ''; // Empty if no review_status
                    }
                }
            }
        }
    }

    // Add task data to main data array
    foreach ($task_data as $key => $value) {
        if (!isset($data[$key])) {
            $data[$key] = $value;
        }
    }

    // 5. Get Attachments (if case_id provided) - BEFORE placeholder replacement
    $attachments_html = '';
    $verification_pics_html = '';
    $all_attachments_html = '';
    if ($case_id) {
        // For report generation: Only get selected images (display_in_report='YES' AND file_type like 'image%')
        // This ensures only selected images are shown in reports, not documents
        $attachments_query = "SELECT a.* FROM attachments a 
                             INNER JOIN case_tasks ct ON a.task_id = ct.id 
                             WHERE ct.case_id = '$case_id' AND a.status = 'ACTIVE' AND ct.status = 'ACTIVE'
                             AND a.display_in_report = 'YES' AND a.file_type LIKE 'image%'
                             ORDER BY a.id ASC";
        $attachments_result = mysqli_query($con, $attachments_query);

        if ($attachments_result && mysqli_num_rows($attachments_result) > 0) {
            $attachments_list = [];
            $pics_list = [];
            $images_list = [];
            $documents_list = [];

            while ($att = mysqli_fetch_assoc($attachments_result)) {
                $file_url = $base_url . 'upload/' . $att['file_url'];
                $file_name = $att['file_name'] ?? 'Attachment';
                $file_type = $att['file_type'] ?? '';

                // Check if it's an image
                $is_image = strpos($file_type, 'image/') === 0;

                if ($is_image) {
                    // For all_attachments: images as thumbnails (2 per row)
                    $images_list[] = $att;
                    // Legacy support
                    $pics_list[] = '<img src="' . htmlspecialchars($file_url) . '" alt="' . htmlspecialchars($file_name) . '" style="max-width: 200px; margin: 5px;">';
                } else {
                    // For all_attachments: documents with icons
                    $documents_list[] = $att;
                    // Legacy support
                    $attachments_list[] = '<a href="' . htmlspecialchars($file_url) . '" target="_blank">' . htmlspecialchars($file_name) . '</a>';
                }
            }

            // Legacy support
            $attachments_html = !empty($attachments_list) ? '<div>' . implode('<br>', $attachments_list) . '</div>' : '';
            $verification_pics_html = !empty($pics_list) ? '<div>' . implode('', $pics_list) . '</div>' : '';

            // Build all_attachments HTML with proper layout
            $all_attachments_html = build_all_attachments_html($images_list, $documents_list, $base_url);
        }
    }

    // 6. Get Agency Information (if agency_id available from client)
    $agency_logo_html = '';
    $agency_stamp_html = '';
    $agency_name = '';

    if ($agency_id) {
        $agency_query = "SELECT * FROM agency WHERE id = '$agency_id' AND status = 'ACTIVE' LIMIT 1";
        $agency_result = mysqli_query($con, $agency_query);
        if ($agency_result && mysqli_num_rows($agency_result) > 0) {
            $agency = mysqli_fetch_assoc($agency_result);
            $agency_name = $agency['agency_name'] ?? '';

            // Get agency logo
            if (!empty($agency['logo'])) {
                $agency_logo_path = trim($agency['logo']);
                // If path doesn't start with http:// or https:// or /, assume it's relative to upload folder
                if (strpos($agency_logo_path, 'http://') !== 0 && strpos($agency_logo_path, 'https://') !== 0 && strpos($agency_logo_path, '/') !== 0) {
                    $agency_logo_path = 'upload/' . $agency_logo_path;
                }
                $agency_logo_html = '<img src="' . $base_url . htmlspecialchars($agency_logo_path) . '" style="max-width: 200px;">';
            }

            // Get agency stamp
            if (!empty($agency['agency_stamp'])) {
                $agency_stamp_path = trim($agency['agency_stamp']);
                // If path doesn't start with http:// or https:// or /, assume it's relative to upload folder
                if (strpos($agency_stamp_path, 'http://') !== 0 && strpos($agency_stamp_path, 'https://') !== 0 && strpos($agency_stamp_path, '/') !== 0) {
                    $agency_stamp_path = 'upload/' . $agency_stamp_path;
                }
                $agency_stamp_html = '<img src="' . $base_url . htmlspecialchars($agency_stamp_path) . '" style="max-width: 150px;">';
            }
        }
    }

    // 7. Get Logo (from op_config or op_settings) - BEFORE placeholder replacement
    $logo_html = '';
    // Try op_config first (uses option_name and option_value)
    $logo_query = "SELECT option_value FROM op_config WHERE option_name = 'inst_logo' AND status = 'ACTIVE' LIMIT 1";
    $logo_result = mysqli_query($con, $logo_query);
    if ($logo_result && mysqli_num_rows($logo_result) > 0) {
        $logo_row = mysqli_fetch_assoc($logo_result);
        $logo_path = $logo_row['option_value'] ?? '';
        if (!empty($logo_path)) {
            // Remove any newline characters
            $logo_path = trim($logo_path);
            $logo_html = '<img src="' . $base_url . htmlspecialchars($logo_path) . '" style="max-width: 200px;">';
        }
    }

    // If not found in op_config, try op_settings
    if (empty($logo_html)) {
        $logo_query2 = "SELECT logo FROM op_settings WHERE status = 'ACTIVE' LIMIT 1";
        $logo_result2 = mysqli_query($con, $logo_query2);
        if ($logo_result2 && mysqli_num_rows($logo_result2) > 0) {
            $logo_row2 = mysqli_fetch_assoc($logo_result2);
            $logo_file = $logo_row2['logo'] ?? '';
            if (!empty($logo_file)) {
                $logo_html = '<img src="' . $base_url . 'upload/' . htmlspecialchars($logo_file) . '" style="max-width: 200px;">';
            }
        }
    }

    // Use agency logo if available, otherwise use system logo
    if (empty($agency_logo_html) && !empty($logo_html)) {
        $agency_logo_html = $logo_html;
    }

    // 7. Calculate Overall Status based on all tasks (after all tasks are collected)
    $overall_status = '';
    $overall_status_word = '';

    if ($case_id && !empty($documents)) {
        $has_cnv = false;
        $has_negative = false;
        $all_positive = true;
        $tasks_with_review_status = 0;

        foreach ($documents as $doc) {
            $review_status = $doc['review_status'] ?? '';

            if (!empty($review_status)) {
                $tasks_with_review_status++;

                // Check for NEGATIVE (medium priority)
                if ($review_status === 'NEGATIVE') {
                    $has_negative = true;
                    $all_positive = false;
                }
                // Check for CNV (highest priority)
                else if ($review_status === 'CNV') {
                    $has_cnv = true;
                    $all_positive = false;
                }

                // If review_status is not POSITIVE, mark as not all positive
                elseif ($review_status !== 'POSITIVE') {
                    $all_positive = false;
                }
            }
            // If review_status is empty, we cannot say all are positive
            // But still check other tasks for CNV/NEGATIVE
        }

        // Determine overall status (priority: CNV > NEGATIVE > POSITIVE)
        // Only if we have at least one task with review_status
        if ($tasks_with_review_status > 0) {
            if ($has_cnv) {
                // If ANY task has CNV → CNV
                $overall_status = 'CNV';
                $overall_status_word = $client_status_words['CNV'] ?? 'CNV';
            } elseif ($has_negative) {
                // If ANY task has NEGATIVE → NEGATIVE
                $overall_status = 'NEGATIVE';
                $overall_status_word = $client_status_words['NEGATIVE'] ?? 'Negative';
            } elseif ($all_positive && $tasks_with_review_status === count($documents)) {
                // If ALL tasks have review_status AND all are POSITIVE → POSITIVE
                $overall_status = 'POSITIVE';
                $overall_status_word = $client_status_words['POSITIVE'] ?? 'Positive';
            }
        }
    }

    // Add overall status to data array
    $data['over_all_status'] = $overall_status_word;
    $data['overall_status'] = $overall_status_word; // Also support without underscore
    $data['over_all_status_raw'] = $overall_status; // Raw status code
    $data['overall_status_raw'] = $overall_status; // Raw status code without underscore

    // 8. Merge custom data (overrides)
    $data = array_merge($data, $custom_data);

    // 9. Process Task Loop - Single placeholder {{TaskLoop}} that generates complete table
    // Get tasks from case_tasks table for task loop
    $task_loop_items = [];
    if ($case_id) {
        $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
        $has_case_tasks_loop = ($table_check && mysqli_num_rows($table_check) > 0);

        if ($has_case_tasks_loop) {
            $tasks_query = "SELECT ct.*, t.task_name as template_task_name, t.task_type as template_task_type 
                       FROM case_tasks ct 
                       LEFT JOIN tasks t ON ct.task_template_id = t.id 
                       WHERE ct.case_id = '$case_id' AND ct.status = 'ACTIVE' 
                       ORDER BY ct.id ASC";
            $tasks_result = mysqli_query($con, $tasks_query);

            if ($tasks_result) {
                $serial_no = 1;
                while ($row = mysqli_fetch_assoc($tasks_result)) {
                    $task_name = $row['task_name'] ?? $row['template_task_name'] ?? '';
                    $task_status_db = $row['task_status'] ?? 'PENDING';

                    // Get task data JSON
                    $task_data_json = [];
                    if (!empty($row['task_data'])) {
                        $task_data_json = json_decode($row['task_data'], true);
                        if (!is_array($task_data_json)) {
                            $task_data_json = [];
                        }
                    }

                    // Get review_status and map to client status words
                    // In reports, task_status should show reviewer's review_status with client words
                    $review_status = $task_data_json['review_status'] ?? '';
                    $task_status_display = '';

                    if (!empty($review_status)) {
                        // Map review_status to client status words
                        $task_status_display = $client_status_words[$review_status] ?? $review_status;
                    } else {
                        // If no review_status, show empty or fallback to workflow status
                        // But for reports, we prefer to show empty if not reviewed
                        $task_status_display = ''; // Show empty if not reviewed
                    }

                    // Get task remarks
                    $task_remarks = $task_data_json['review_remarks'] ?? $task_data_json['verifier_remarks'] ?? '';

                    $task_loop_items[] = [
                        'serial_no' => $serial_no++,
                        'task_name' => $task_name,
                        'task_status' => $task_status_display, // This will be review_status with client words
                        'task_remarks' => htmlspecialchars($task_remarks)
                    ];
                }
            }
        }
    }

    // Replace {{TaskLoop}} placeholder with complete table
    // Support: {{TaskLoop}}, {{TaskLoop|columns=serial,name}}, {{TaskLoop|columns=serial,name|serial_prefix=Doc |serial_start=1|header_serial=S.No|width_serial=10%}} etc.
    // Process all occurrences - use while loop to handle multiple instances
    while (preg_match('/\{\{TaskLoop([^}]*)\}\}/', $html, $matches)) {
        $params_str = $matches[1] ?? '';
        $selected_columns = ['serial', 'name', 'status', 'remarks']; // Default: show all

        // Default values
        $serial_prefix = '';
        $serial_start = 1;
        $headers = [
            'serial' => 'S.No',
            'name' => 'Task Name',
            'status' => 'Status',
            'remarks' => 'Remarks'
        ];
        $widths = [
            'serial' => '10%',
            'name' => '30%',
            'status' => '20%',
            'remarks' => '40%'
        ];

        // Parse parameters
        if (!empty($params_str)) {
            // Parse columns= parameter
            if (preg_match('/\|columns=([^|]+)/', $params_str, $col_match)) {
                $selected_columns = array_map('trim', explode(',', $col_match[1]));
            }

            // Parse serial_prefix= parameter
            if (preg_match('/\|serial_prefix=([^|]+)/', $params_str, $prefix_match)) {
                $serial_prefix = urldecode($prefix_match[1]);
            }

            // Parse serial_start= parameter
            if (preg_match('/\|serial_start=(\d+)/', $params_str, $start_match)) {
                $serial_start = intval($start_match[1]);
            }

            // Parse header_serial=, header_name=, header_status=, header_remarks= parameters
            if (preg_match('/\|header_serial=([^|]+)/', $params_str, $header_match)) {
                $headers['serial'] = urldecode($header_match[1]);
            }
            if (preg_match('/\|header_name=([^|]+)/', $params_str, $header_match)) {
                $headers['name'] = urldecode($header_match[1]);
            }
            if (preg_match('/\|header_status=([^|]+)/', $params_str, $header_match)) {
                $headers['status'] = urldecode($header_match[1]);
            }
            if (preg_match('/\|header_remarks=([^|]+)/', $params_str, $header_match)) {
                $headers['remarks'] = urldecode($header_match[1]);
            }

            // Parse width_serial=, width_name=, width_status=, width_remarks= parameters
            if (preg_match('/\|width_serial=([^|]+)/', $params_str, $width_match)) {
                $widths['serial'] = urldecode($width_match[1]);
            }
            if (preg_match('/\|width_name=([^|]+)/', $params_str, $width_match)) {
                $widths['name'] = urldecode($width_match[1]);
            }
            if (preg_match('/\|width_status=([^|]+)/', $params_str, $width_match)) {
                $widths['status'] = urldecode($width_match[1]);
            }
            if (preg_match('/\|width_remarks=([^|]+)/', $params_str, $width_match)) {
                $widths['remarks'] = urldecode($width_match[1]);
            }

            // Parse TH styling options
            $th_bg_color = '#f8f9fa'; // Default
            $th_text_color = ''; // Default: inherit
            $inherit_css = false;

            if (preg_match('/\|th_bg_color=([^|]+)/', $params_str, $th_match)) {
                $th_bg_color = urldecode($th_match[1]);
            }
            if (preg_match('/\|th_text_color=([^|]+)/', $params_str, $th_match)) {
                $th_text_color = urldecode($th_match[1]);
            }
            if (preg_match('/\|inherit_css=yes/i', $params_str)) {
                $inherit_css = true;
            }
        } else {
            // Default values when no params
            $th_bg_color = '#f8f9fa';
            $th_text_color = '';
            $inherit_css = false;
        }

        // Build TH style
        $th_style = '';
        if (!$inherit_css) {
            $th_styles = [];
            if (!empty($th_bg_color) && $th_bg_color !== 'transparent') {
                $th_styles[] = 'background-color: ' . htmlspecialchars($th_bg_color);
            }
            if (!empty($th_text_color)) {
                $th_styles[] = 'color: ' . htmlspecialchars($th_text_color);
            }
            if (!empty($th_styles)) {
                $th_style = ' style="' . implode('; ', $th_styles) . '"';
            }
        }

        // Build complete table with header (once) and rows
        $table_style = $inherit_css ? '' : ' style="width: 100%; border-collapse: collapse;"';
        $table_html = '<table class="table table-bordered table-sm"' . $table_style . '>' . "\n";
        $table_html .= '<thead>' . "\n";
        $table_html .= '<tr>' . "\n";

        $col_count = 0;

        if (in_array('serial', $selected_columns)) {
            $width_style = (!$inherit_css && !empty($widths['serial'])) ? ' style="width: ' . htmlspecialchars($widths['serial']) . ';"' : '';
            $table_html .= '<th' . $width_style . $th_style . '>' . htmlspecialchars($headers['serial']) . '</th>' . "\n";
            $col_count++;
        }
        if (in_array('name', $selected_columns)) {
            $width_style = (!$inherit_css && !empty($widths['name'])) ? ' style="width: ' . htmlspecialchars($widths['name']) . ';"' : '';
            $table_html .= '<th' . $width_style . $th_style . '>' . htmlspecialchars($headers['name']) . '</th>' . "\n";
            $col_count++;
        }
        if (in_array('status', $selected_columns)) {
            $width_style = (!$inherit_css && !empty($widths['status'])) ? ' style="width: ' . htmlspecialchars($widths['status']) . ';"' : '';
            $table_html .= '<th' . $width_style . $th_style . '>' . htmlspecialchars($headers['status']) . '</th>' . "\n";
            $col_count++;
        }
        if (in_array('remarks', $selected_columns)) {
            $width_style = (!$inherit_css && !empty($widths['remarks'])) ? ' style="width: ' . htmlspecialchars($widths['remarks']) . ';"' : '';
            $table_html .= '<th' . $width_style . $th_style . '>' . htmlspecialchars($headers['remarks']) . '</th>' . "\n";
            $col_count++;
        }

        $table_html .= '</tr>' . "\n";
        $table_html .= '</thead>' . "\n";
        $table_html .= '<tbody>' . "\n";

        // Calculate serial numbers with prefix and start
        $current_serial = $serial_start;

        // Add rows for each task
        $td_style = $inherit_css ? '' : ' style="padding:8px; text-align:left; border: 1px solid #ddd;"';
        foreach ($task_loop_items as $task_item) {
            $table_html .= '<tr>' . "\n";

            if (in_array('serial', $selected_columns)) {
                // Apply serial prefix and current number
                $serial_display = $serial_prefix . $current_serial;
                $table_html .= '<td' . $td_style . '>' . htmlspecialchars($serial_display) . '</td>' . "\n";
                $current_serial++;
            }
            if (in_array('name', $selected_columns)) {
                $table_html .= '<td' . $td_style . '>' . htmlspecialchars($task_item['task_name']) . '</td>' . "\n";
            }
            if (in_array('status', $selected_columns)) {
                $table_html .= '<td' . $td_style . '>' . htmlspecialchars($task_item['task_status']) . '</td>' . "\n";
            }
            if (in_array('remarks', $selected_columns)) {
                $table_html .= '<td' . $td_style . '>' . nl2br($task_item['task_remarks']) . '</td>' . "\n";
            }

            $table_html .= '</tr>' . "\n";
        }

        // If no tasks, show empty row
        if (empty($task_loop_items)) {
            $empty_style = $inherit_css ? '' : ' style="padding:8px; text-align:center; color:#999; border: 1px solid #ddd;"';
            $table_html .= '<tr>' . "\n";
            $table_html .= '<td colspan="' . $col_count . '"' . $empty_style . '>No tasks found</td>' . "\n";
            $table_html .= '</tr>' . "\n";
        }

        $table_html .= '</tbody>' . "\n";
        $table_html .= '</table>';

        // Replace the placeholder - use exact string replacement to ensure correct match
        $full_placeholder = '{{TaskLoop' . $params_str . '}}';
        $html = str_replace($full_placeholder, $table_html, $html);
    }

    // Also support legacy format #TaskLoop#
    if (strpos($html, '#TaskLoop#') !== false) {
        // Default: show all columns
        $selected_columns = ['serial', 'name', 'status', 'remarks'];

        $table_html = '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; max-width:100%; border-collapse:collapse; table-layout:auto;">' . "\n";
        $table_html .= '<thead>' . "\n";
        $table_html .= '<tr>' . "\n";
        $table_html .= '<th style="">Sr. No.</th>' . "\n";
        $table_html .= '<th style="">Task Name</th>' . "\n";
        $table_html .= '<th style="">Status</th>' . "\n";
        $table_html .= '<th style="">Remarks</th>' . "\n";
        $table_html .= '</tr>' . "\n";
        $table_html .= '</thead>' . "\n";
        $table_html .= '<tbody>' . "\n";

        foreach ($task_loop_items as $task_item) {
            $table_html .= '<tr>' . "\n";
            $table_html .= '<td style="padding:8px; text-align:left;">' . $task_item['serial_no'] . '</td>' . "\n";
            $table_html .= '<td style="padding:8px; text-align:left;">' . htmlspecialchars($task_item['task_name']) . '</td>' . "\n";
            $table_html .= '<td style="padding:8px; text-align:left;">' . htmlspecialchars($task_item['task_status']) . '</td>' . "\n";
            $table_html .= '<td style="padding:8px; text-align:left;">' . nl2br($task_item['task_remarks']) . '</td>' . "\n";
            $table_html .= '</tr>' . "\n";
        }

        if (empty($task_loop_items)) {
            $table_html .= '<tr>' . "\n";
            $table_html .= '<td colspan="4" style="padding:8px; text-align:center; color:#999;">No tasks found</td>' . "\n";
            $table_html .= '</tr>' . "\n";
        }

        $table_html .= '</tbody>' . "\n";
        $table_html .= '</table>';

        $html = str_replace('#TaskLoop#', $table_html, $html);
    }

    // Process TaskCountLoop - Horizontal task count summary (Task names as columns, rows for count/status)
    // Format: {{TaskCountLoop|show=name,count,status|show_labels=yes|labels=Task Name,Count,Status|width_label=20%|width_task=auto}}
    // Process all occurrences - use while loop to handle multiple instances
    while (preg_match('/\{\{TaskCountLoop([^}]*)\}\}/', $html, $matches)) {
        $params_str = $matches[1] ?? '';
        $show_options = ['name', 'count', 'status']; // Default: show all
        $show_labels = true; // Default: show labels
        $labels = ['Task Name', 'Count', 'Status']; // Default labels
        $width_label = '20%'; // Default label column width
        $width_task = 'auto'; // Default task column width

        // Parse parameters
        if (!empty($params_str)) {
            // Parse show= parameter
            if (preg_match('/\|show=([^|]+)/', $params_str, $show_match)) {
                $show_options = array_map('trim', explode(',', $show_match[1]));
            }

            // Parse show_labels= parameter
            if (preg_match('/\|show_labels=no/i', $params_str)) {
                $show_labels = false;
            }

            // Parse labels= parameter
            if (preg_match('/\|labels=([^|}]+)/', $params_str, $labels_match)) {
                // Decode URL encoding and split by comma
                $labels_str = urldecode($labels_match[1]);
                // Handle both comma-separated and URL-encoded comma (%2C)
                $labels_str = str_replace('%2C', ',', $labels_str);
                $labels_parsed = array_map('trim', explode(',', $labels_str));

                // Always use provided labels in order: [0]=Task Name, [1]=Count, [2]=Status
                if (count($labels_parsed) >= 3) {
                    // Direct assignment: labels are always in order Task Name, Count, Status
                    $labels[0] = $labels_parsed[0]; // Task Name label
                    $labels[1] = $labels_parsed[1]; // Count label
                    $labels[2] = $labels_parsed[2]; // Status label
                } elseif (count($labels_parsed) == 2) {
                    // If only 2 labels provided, assume they're for name and count
                    $labels[0] = $labels_parsed[0];
                    $labels[1] = $labels_parsed[1];
                } elseif (count($labels_parsed) == 1) {
                    // If only 1 label provided, assume it's for task name
                    $labels[0] = $labels_parsed[0];
                }
            }

            // Parse width_label= parameter
            if (preg_match('/\|width_label=([^|]+)/', $params_str, $width_match)) {
                $width_label = urldecode($width_match[1]);
            }

            // Parse width_task= parameter
            if (preg_match('/\|width_task=([^|]+)/', $params_str, $width_match)) {
                $width_task = urldecode($width_match[1]);
            }

            // Parse TH styling options
            $th_bg_color = '#f8f9fa'; // Default
            $th_text_color = ''; // Default: inherit
            $inherit_css = false;

            if (preg_match('/\|th_bg_color=([^|]+)/', $params_str, $th_match)) {
                $th_bg_color = urldecode($th_match[1]);
            }
            if (preg_match('/\|th_text_color=([^|]+)/', $params_str, $th_match)) {
                $th_text_color = urldecode($th_match[1]);
            }
            if (preg_match('/\|inherit_css=yes/i', $params_str)) {
                $inherit_css = true;
            }
        } else {
            // Default values when no params
            $th_bg_color = '#f8f9fa';
            $th_text_color = '';
            $inherit_css = false;
        }

        // Group tasks by name and count them, also get statuses
        $task_summary = [];
        foreach ($task_loop_items as $task_item) {
            $task_name = $task_item['task_name'];
            if (!isset($task_summary[$task_name])) {
                $task_summary[$task_name] = [
                    'name' => $task_name,
                    'count' => 0,
                    'statuses' => []
                ];
            }
            $task_summary[$task_name]['count']++;

            // Collect unique statuses
            $status = $task_item['task_status'];
            if (!in_array($status, $task_summary[$task_name]['statuses'])) {
                $task_summary[$task_name]['statuses'][] = $status;
            }
        }

        // Build TH style
        $th_style = '';
        if (!$inherit_css) {
            $th_styles = [];
            if (!empty($th_bg_color) && $th_bg_color !== 'transparent') {
                $th_styles[] = 'background-color: ' . htmlspecialchars($th_bg_color);
            }
            if (!empty($th_text_color)) {
                $th_styles[] = 'color: ' . htmlspecialchars($th_text_color);
            }
            if (!empty($th_styles)) {
                $th_style = ' style="' . implode('; ', $th_styles) . '"';
            }
        }

        // Build horizontal table (transposed: task names as columns)
        $table_style = $inherit_css ? '' : ' style="width: 100%; border-collapse: collapse;"';
        $table_html = '<table class="table table-bordered table-sm"' . $table_style . '>' . "\n";
        $table_html .= '<tbody>' . "\n";

        // Get unique task names for columns
        $task_names = array_keys($task_summary);
        $col_count = count($task_names);

        // Build width styles
        $label_width_style = (!$inherit_css && $width_label && $width_label !== 'auto') ? ' style="width: ' . htmlspecialchars($width_label) . ';"' : '';
        $task_width_style = (!$inherit_css && $width_task && $width_task !== 'auto') ? ' style="width: ' . htmlspecialchars($width_task) . ';"' : '';
        $td_style = $inherit_css ? '' : ' style="padding:8px; border: 1px solid #ddd;"';

        // Row 1: Task Names (as header row)
        if (in_array('name', $show_options) || empty($show_options) || $col_count > 0) {
            $table_html .= '<tr>' . "\n";
            // First cell is label (if show_labels is true)
            if ($show_labels) {
                $table_html .= '<th' . $label_width_style . $th_style . '>' . htmlspecialchars($labels[0]) . '</th>' . "\n";
            }
            // Then task names as columns
            foreach ($task_names as $task_name) {
                $table_html .= '<th' . $task_width_style . $th_style . '>' . htmlspecialchars($task_name) . '</th>' . "\n";
            }
            $table_html .= '</tr>' . "\n";
        }

        // Row 2: Count values
        if (in_array('count', $show_options)) {
            $table_html .= '<tr>' . "\n";
            if ($show_labels) {
                $table_html .= '<td' . $label_width_style . $td_style . '><strong>' . htmlspecialchars($labels[1]) . '</strong></td>' . "\n";
            }
            foreach ($task_names as $task_name) {
                $count = isset($task_summary[$task_name]) ? $task_summary[$task_name]['count'] : 0;
                $table_html .= '<td' . $task_width_style . $td_style . '><strong>' . $count . '</strong></td>' . "\n";
            }
            $table_html .= '</tr>' . "\n";
        }

        // Row 3: Status values
        if (in_array('status', $show_options)) {
            $table_html .= '<tr>' . "\n";
            if ($show_labels) {
                $table_html .= '<td' . $label_width_style . $td_style . '><strong>' . htmlspecialchars($labels[2]) . '</strong></td>' . "\n";
            }
            foreach ($task_names as $task_name) {
                $status_display = 'N/A';
                if (isset($task_summary[$task_name]) && !empty($task_summary[$task_name]['statuses'])) {
                    $status_display = implode(', ', $task_summary[$task_name]['statuses']);
                }
                $table_html .= '<td' . $task_width_style . $td_style . '>' . htmlspecialchars($status_display) . '</td>' . "\n";
            }
            $table_html .= '</tr>' . "\n";
        }

        // If no tasks, show empty message
        if (empty($task_names)) {
            $table_html .= '<tr>' . "\n";
            $colspan = $show_labels ? $col_count + 1 : $col_count;
            $table_html .= '<td colspan="' . max(2, $colspan) . '" style="padding:8px; text-align:center; color:#999;">No tasks found</td>' . "\n";
            $table_html .= '</tr>' . "\n";
        }

        $table_html .= '</tbody>' . "\n";
        $table_html .= '</table>';

        // Replace the placeholder - use exact string replacement to ensure correct match
        $full_placeholder = '{{TaskCountLoop' . $params_str . '}}';
        $html = str_replace($full_placeholder, $table_html, $html);
    }

    // Process VerificationParticularTable placeholders - generates header and data cells (no table structure)
    // Headers: {{VerificationParticularHeader1}}, {{VerificationParticularHeader2}}, etc.
    // Data: {{VerificationParticularData1}}, {{VerificationParticularData2}}, etc.
    if ($case_id) {
        for ($i = 1; $i <= 6; $i++) {
            // Process header placeholder
            $header_placeholder = '{{VerificationParticularHeader' . $i . '}}';
            if (strpos($html, $header_placeholder) !== false) {
                $header_html = generate_verification_particular_header_cell($case_id, $i, [
                    'include_pending' => false,
                ]);
                $html = str_replace($header_placeholder, $header_html, $html);
            }

            // Process data placeholder
            $data_placeholder = '{{VerificationParticularData' . $i . '}}';
            if (strpos($html, $data_placeholder) !== false) {
                $data_html = generate_verification_particular_data_cell($case_id, $i, [
                    'include_pending' => false,
                ]);
                $html = str_replace($data_placeholder, $data_html, $html);
            }
        }

        // Also support legacy {{VerificationParticularTable}} for backward compatibility
        if (strpos($html, '{{VerificationParticularTable}}') !== false) {
            $table_html = generate_verification_particular_tables_horizontal($case_id, [
                'include_pending' => false,
            ]);
            $html = str_replace('{{VerificationParticularTable}}', $table_html, $html);
        }
    }

    // Also support legacy format #TaskCountLoop#
    if (strpos($html, '#TaskCountLoop#') !== false) {
        // Default: show all
        $show_options = ['name', 'count', 'status'];

        // Group tasks by name and count them
        $task_summary = [];
        foreach ($task_loop_items as $task_item) {
            $task_name = $task_item['task_name'];
            if (!isset($task_summary[$task_name])) {
                $task_summary[$task_name] = [
                    'name' => $task_name,
                    'count' => 0,
                    'statuses' => []
                ];
            }
            $task_summary[$task_name]['count']++;
            $status = $task_item['task_status'];
            if (!in_array($status, $task_summary[$task_name]['statuses'])) {
                $task_summary[$task_name]['statuses'][] = $status;
            }
        }

        // Build horizontal table (transposed: task names as columns)
        $table_html = '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; max-width:100%; border-collapse:collapse; table-layout:auto;">' . "\n";
        $table_html .= '<tbody>' . "\n";

        $task_names = array_keys($task_summary);

        // Row 1: Task Names
        $table_html .= '<tr>' . "\n";
        $table_html .= '<th style="text-align:left; padding:8px; background-color:#f8f9fa; font-weight:bold;">Task Name</th>' . "\n";
        foreach ($task_names as $task_name) {
            $table_html .= '<th style="text-align:center; padding:8px; background-color:#f8f9fa; font-weight:bold;">' . htmlspecialchars($task_name) . '</th>' . "\n";
        }
        $table_html .= '</tr>' . "\n";

        // Row 2: Count
        $table_html .= '<tr>' . "\n";
        $table_html .= '<td style="text-align:left; padding:8px; background-color:#f0f0f0; font-weight:bold;">Count</td>' . "\n";
        foreach ($task_names as $task_name) {
            $count = isset($task_summary[$task_name]) ? $task_summary[$task_name]['count'] : 0;
            $table_html .= '<td style="text-align:center; padding:8px; font-weight:bold;">' . $count . '</td>' . "\n";
        }
        $table_html .= '</tr>' . "\n";

        // Row 3: Status
        $table_html .= '<tr>' . "\n";
        $table_html .= '<td style="text-align:left; padding:8px; background-color:#f0f0f0; font-weight:bold;">Status</td>' . "\n";
        foreach ($task_names as $task_name) {
            $status_display = 'N/A';
            if (isset($task_summary[$task_name]) && !empty($task_summary[$task_name]['statuses'])) {
                $status_display = implode(', ', $task_summary[$task_name]['statuses']);
            }
            $table_html .= '<td style="text-align:center; padding:8px;">' . htmlspecialchars($status_display) . '</td>' . "\n";
        }
        $table_html .= '</tr>' . "\n";

        if (empty($task_names)) {
            $table_html .= '<tr>' . "\n";
            $table_html .= '<td colspan="2" style="padding:8px; text-align:center; color:#999;">No tasks found</td>' . "\n";
            $table_html .= '</tr>' . "\n";
        }

        $table_html .= '</tbody>' . "\n";
        $table_html .= '</table>';

        $html = str_replace('#TaskCountLoop#', $table_html, $html);
    }

    // 9. Process Document Loop (support both {{loop_start}} and #loop_start# formats)
    $loop_patterns = [
        ['start' => '{{document_loop_start}}', 'end' => '{{document_loop_end}}'],
        ['start' => '#document_loop_start#', 'end' => '#document_loop_end#']
    ];

    foreach ($loop_patterns as $pattern) {
        $start_pos = strpos($html, $pattern['start']);
        $end_pos = strpos($html, $pattern['end']);

        if ($start_pos !== false && $end_pos !== false) {
            $loop_template = substr($html, $start_pos + strlen($pattern['start']), $end_pos - $start_pos - strlen($pattern['start']));

            $loop_content = '';
            foreach ($documents as $index => $doc) {
                $doc_html = $loop_template;

                // Replace document-specific variables (both formats)
                $replacements = [
                    '{{document_particulars}}' => $doc['particulars'] ?? 'Document ' . ($index + 1),
                    '{{document_type}}' => $doc['task_type'] ?? $doc['task_name'] ?? '',
                    '{{document_status}}' => $doc['status'] ?? '',
                    '{{document_remarks}}' => $doc['remarks'] ?? '',
                    '#document_particulars#' => $doc['particulars'] ?? 'Document ' . ($index + 1),
                    '#document_type#' => $doc['task_type'] ?? $doc['task_name'] ?? '',
                    '#document_status#' => $doc['status'] ?? '',
                    '#document_remarks#' => $doc['remarks'] ?? '',
                ];

                foreach ($replacements as $placeholder => $value) {
                    $doc_html = str_replace($placeholder, htmlspecialchars($value), $doc_html);
                }

                // Replace document meta variables
                foreach ($doc['meta'] as $key => $value) {
                    $doc_html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $doc_html);
                    $doc_html = str_replace('#' . $key . '#', htmlspecialchars($value), $doc_html);
                }

                $loop_content .= $doc_html;
            }

            $html = substr($html, 0, $start_pos) . $loop_content . substr($html, $end_pos + strlen($pattern['end']));
            break; // Process only first match
        }
    }

    // 10. Replace all placeholders (support both {{variable}} and #variable# formats)
    // Fields that may contain HTML and should not be escaped
    $html_fields = ['task_remarks', 'task_name', 'task_status'];

    foreach ($data as $key => $value) {
        // Don't escape HTML fields (like task_remarks which contains <br> tags)
        $safe_value = in_array($key, $html_fields) ? $value : htmlspecialchars($value);

        // Primary format: {{variable}}
        $html = str_replace('{{' . $key . '}}', $safe_value, $html);
        // Legacy format: #variable#
        $html = str_replace('#' . $key . '#', $safe_value, $html);
    }

    // Calculate date-related placeholders
    $sample_date = $data['sample_date'] ?? '';
    $pickup_date = $data['pickup_date'] ?? $sample_date; // Use pickup_date if available, else use sample_date
    $current_date = date('d-m-Y');
    $report_date = date('d-m-Y');

    // Get date of review (latest reviewed_at from case_tasks)
    $date_of_review = '';
    if ($case_id) {
        $review_date_query = "SELECT MAX(reviewed_at) as latest_review_date FROM case_tasks WHERE case_id = '$case_id' AND reviewed_at IS NOT NULL";
        $review_date_result = mysqli_query($con, $review_date_query);
        if ($review_date_result && mysqli_num_rows($review_date_result) > 0) {
            $review_date_row = mysqli_fetch_assoc($review_date_result);
            if (!empty($review_date_row['latest_review_date'])) {
                $date_of_review = date('d-m-Y', strtotime($review_date_row['latest_review_date']));
            }
        }
    }
    if (empty($date_of_review)) {
        $date_of_review = $current_date; // Fallback to current date
    }

    // Calculate TAT (Turn Around Time) in days
    $tat_calculation = '';
    if (!empty($sample_date)) {
        // Try to parse sample_date (could be in various formats)
        $sample_timestamp = false;
        $date_formats = ['d-m-Y', 'Y-m-d', 'd/m/Y', 'Y/m/d'];
        foreach ($date_formats as $format) {
            $parsed = date_create_from_format($format, $sample_date);
            if ($parsed !== false) {
                $sample_timestamp = $parsed->getTimestamp();
                break;
            }
        }

        if ($sample_timestamp !== false) {
            $report_timestamp = strtotime($date_of_review);
            if ($report_timestamp !== false) {
                $days_diff = round(($report_timestamp - $sample_timestamp) / (60 * 60 * 24));
                $tat_calculation = $days_diff . ' Days';
            }
        }
    }
    if (empty($tat_calculation)) {
        $tat_calculation = 'N/A';
    }

    // 11. Replace system variables
    $system_vars = [
        'current_date' => $current_date,
        'report_date' => $report_date,
        'sample_date' => $sample_date ? (is_numeric($sample_date) ? date('d-m-Y', strtotime($sample_date)) : $sample_date) : $current_date,
        'pickup_date' => $pickup_date ? (is_numeric($pickup_date) ? date('d-m-Y', strtotime($pickup_date)) : $pickup_date) : ($sample_date ? (is_numeric($sample_date) ? date('d-m-Y', strtotime($sample_date)) : $sample_date) : $current_date),
        'date_of_review' => $date_of_review,
        'receive_date' => $current_date, // Current date when report is printed
        'tat_calculation' => $tat_calculation,
        'serial_no' => $case_id ? str_pad($case_id, 6, '0', STR_PAD_LEFT) : '',
        'total_no_of_docs_sampled' => count($documents),
        'agency_dedupe_status' => 'No',
        'over_all_status' => $data['case_status'] ?? 'Referred',
        'stamp' => !empty($agency_stamp_html) ? $agency_stamp_html : '<img src="' . $base_url . 'system/img/stamp.png" style="max-width: 150px;">',
        'agency_stamp' => $agency_stamp_html,
        'agency_logo' => $agency_logo_html,
        'agency_name' => $agency_name,
        'verification_pics' => $verification_pics_html,
        'attachments' => $attachments_html,
        'all_attachments' => $all_attachments_html,
        'logo' => !empty($agency_logo_html) ? $agency_logo_html : $logo_html,
    ];

    foreach ($system_vars as $key => $value) {
        $html = str_replace('{{' . $key . '}}', $value, $html);
        $html = str_replace('#' . $key . '#', $value, $html);
    }

    // 11. Replace nested placeholders (e.g., {{borrower.name}}, {{case.result}})
    // Support dot notation for nested data
    $html = preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function ($matches) use ($data) {
        $key = $matches[1];
        // Handle dot notation
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $value = $data;
            foreach ($parts as $part) {
                if (isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    return ''; // Not found
                }
            }
            return htmlspecialchars($value);
        } else {
            return isset($data[$key]) ? htmlspecialchars($data[$key]) : '';
        }
    }, $html);

    // 12. Replace any remaining placeholders with empty string
    $html = preg_replace('/\{\{([a-zA-Z0-9_.]+)\}\}/', '', $html);
    $html = preg_replace('/#([a-zA-Z0-9_]+)#/', '', $html);

    return [
        'success' => true,
        'html' => $html,
        'message' => 'Report generated successfully'
    ];
}

/**
 * Get Report Template for Case
 * 
 * @param int $case_id Case ID
 * @param string $template_type Template type (REPORT, CUSTOM) - defaults to REPORT
 * @return int|null Template ID or null
 */
function get_report_template_for_case($case_id, $template_type = 'REPORT')
{
    global $con;

    // Get case to find client_id
    $case_query = "SELECT client_id FROM cases WHERE id = '$case_id'";
    $case_result = mysqli_query($con, $case_query);

    if (!$case_result || mysqli_num_rows($case_result) == 0) {
        return null;
    }

    $case = mysqli_fetch_assoc($case_result);
    $client_id = $case['client_id'];

    // Get default REPORT template for client
    $template_query = "SELECT id FROM report_templates 
                       WHERE client_id = '$client_id' 
                       AND template_type = '$template_type' 
                       AND status = 'ACTIVE' 
                       AND is_default = 'YES' 
                       LIMIT 1";
    $template_result = mysqli_query($con, $template_query);

    if ($template_result && mysqli_num_rows($template_result) > 0) {
        $template = mysqli_fetch_assoc($template_result);
        return $template['id'];
    }

    // If no default, get first active REPORT template
    $template_query = "SELECT id FROM report_templates 
                       WHERE client_id = '$client_id' 
                       AND template_type = '$template_type' 
                       AND status = 'ACTIVE' 
                       LIMIT 1";
    $template_result = mysqli_query($con, $template_query);

    if ($template_result && mysqli_num_rows($template_result) > 0) {
        $template = mysqli_fetch_assoc($template_result);
        return $template['id'];
    }

    return null;
}

/**
 * Get Task Status Display Name
 * Maps database status to display status
 * Flow: Fresh Case -> Assigned -> Verified -> Reviewed
 * 
 * @param string $db_status Database task status
 * @param array $task_data Task data array (optional, for checking review_status)
 * @return string Display status name
 */
function get_task_status_display($db_status, $task_data = [])
{
    $status = strtoupper($db_status ?? 'PENDING');

    // Map database status to display status
    switch ($status) {
        case 'PENDING':
            return 'Fresh Case';
        case 'IN_PROGRESS':
            return 'Assigned';
        case 'VERIFICATION_COMPLETED':
            return 'Verified';
        case 'COMPLETED':
            // Check if task has review_status to distinguish Reviewed
            if (isset($task_data['review_status']) && !empty($task_data['review_status'])) {
                return 'Reviewed';
            }
            // If COMPLETED without review_status, treat as Reviewed (backward compatibility)
            return 'Reviewed';
        default:
            return 'Fresh Case';
    }
}

/**
 * Calculate Case Status Based on Tasks
 * Case Status Logic:
 * - PENDING: If all tasks are in Fresh Case status (PENDING)
 * - IN_PROGRESS: If any task status has changed from PENDING
 * - COMPLETED: If all tasks are Reviewed (COMPLETED with review_status)
 * 
 * @param array $tasks Array of tasks with 'db_status' or 'task_status' key and optional 'task_data'
 * @return string Case status (PENDING, IN_PROGRESS, or COMPLETED)
 */
function calculate_case_status($tasks)
{
    if (empty($tasks)) {
        return 'PENDING'; // No tasks = PENDING
    }

    $all_fresh_case = true;
    $all_reviewed = true;

    foreach ($tasks as $task) {
        $task_status = strtoupper($task['db_status'] ?? $task['task_status'] ?? 'PENDING');

        // Check if task is Fresh Case (PENDING)
        if ($task_status != 'PENDING') {
            $all_fresh_case = false;
        }

        // Check if task is Reviewed (COMPLETED with review_status)
        $is_reviewed = false;
        if ($task_status == 'COMPLETED') {
            // Check if task_data has review_status
            $task_data = [];
            if (isset($task['task_data'])) {
                if (is_string($task['task_data'])) {
                    $task_data = json_decode($task['task_data'], true);
                } elseif (is_array($task['task_data'])) {
                    $task_data = $task['task_data'];
                }
            }
            // Task is reviewed if it has review_status
            if (isset($task_data['review_status']) && !empty($task_data['review_status'])) {
                $is_reviewed = true;
            } else {
                // If COMPLETED but no review_status, still consider it reviewed for backward compatibility
                $is_reviewed = true;
            }
        }

        if (!$is_reviewed) {
            $all_reviewed = false;
        }
    }

    // If all tasks are Fresh Case (PENDING), case is PENDING
    if ($all_fresh_case) {
        return 'PENDING';
    }

    // If all tasks are Reviewed, case is COMPLETED
    if ($all_reviewed) {
        return 'COMPLETED';
    }

    // If any task has changed from PENDING but not all are Reviewed, case is IN_PROGRESS
    return 'IN_PROGRESS';
}

/**
 * Check if user can view task based on role
 * 
 * @param array $task Task data with assigned_to, task_status
 * @param int $user_id Current user ID
 * @param string $user_type Current user type (ADMIN, DEV, VERIFIER, REVIEWER, etc.)
 * @return bool True if user can view the task
 */
function can_user_view_task($task, $user_id, $user_type)
{
    // Admin and DEV can view all tasks
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return true;
    }

    // Verifier can view tasks assigned to them or pending tasks
    if ($user_type == 'VERIFIER') {
        $task_status = strtoupper($task['task_status'] ?? 'PENDING');
        $assigned_to = $task['assigned_to'] ?? null;

        // Can view if assigned to them or if pending (not assigned yet)
        if ($task_status == 'PENDING' || ($assigned_to && $assigned_to == $user_id)) {
            return true;
        }
    }

    // Reviewer can view verified tasks and completed tasks
    if ($user_type == 'REVIEWER') {
        $task_status = strtoupper($task['task_status'] ?? 'PENDING');
        if (in_array($task_status, ['VERIFICATION_COMPLETED', 'COMPLETED'])) {
            return true;
        }
    }

    return false;
}

/**
 * Check if user can perform action on task based on role and status
 * 
 * @param array $task Task data
 * @param string $action Action to perform (assign, verify, review, view)
 * @param int $user_id Current user ID
 * @param string $user_type Current user type
 * @return bool True if user can perform the action
 */
/**
 * Generate Financial Matching Remark
 * @param mixed $provided Amount as per provided copy
 * @param mixed $ito Amount as per ITO record
 * @return array [text, class, style]
 */
function get_financial_remark($provided, $ito)
{
    if ($provided === '' || $ito === '' || is_null($provided) || is_null($ito)) {
        return ['text' => 'Pending', 'class' => 'bg-light text-muted', 'style' => 'color:#666;'];
    }

    // Canonical number check
    $p = floatval(str_replace(',', '', $provided));
    $i = floatval(str_replace(',', '', $ito));

    if (abs($p - $i) < 0.01) { // Allow for tiny floats
        return ['text' => 'Figure Matching', 'class' => 'matching', 'style' => 'color:#28a745;'];
    } else {
        $diff = abs($p - $i);
        return ['text' => 'Figure Difference ' . number_format($diff, 2), 'class' => 'not-matching', 'style' => 'color:#dc3545;font-weight:bold;'];
    }
}

function can_user_action_task($task, $action, $user_id, $user_type)
{
    $task_status = strtoupper($task['task_status'] ?? 'PENDING');
    $assigned_to = $task['assigned_to'] ?? null;

    // Admin and DEV can do all actions
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return true;
    }

    switch (strtolower($action)) {
        case 'assign':
            // Only admin/dev can assign, or verifier can self-assign pending tasks
            if ($user_type == 'VERIFIER' && $task_status == 'PENDING') {
                return true;
            }
            return false;

        case 'verify':
            // Verifier can verify tasks assigned to them
            if (
                $user_type == 'VERIFIER' && $assigned_to == $user_id &&
                in_array($task_status, ['PENDING', 'IN_PROGRESS'])
            ) {
                return true;
            }
            return false;

        case 'review':
            // Reviewer can review verified tasks
            if ($user_type == 'REVIEWER' && $task_status == 'VERIFICATION_COMPLETED') {
                return true;
            }
            return false;

        case 'view':
            return can_user_view_task($task, $user_id, $user_type);

        default:
            return false;
    }
}

/**
 * Check if user can view case based on role
 * 
 * @param array $case Case data
 * @param int $user_id Current user ID
 * @param string $user_type Current user type
 * @return bool True if user can view the case
 */
function can_user_view_case($case, $user_id, $user_type)
{
    // Admin and DEV can view all cases
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return true;
    }

    // Other roles: check if they have any visible tasks in the case
    // This will be checked at the task level
    return true; // Allow viewing case, but tasks will be filtered
}

/**
 * Get task action URL based on status and user role
 * 
 * @param array $task Task data
 * @param int $case_id Case ID
 * @param string $user_type Current user type
 * @return string URL for the action
 */
/**
 * Log activity to activity_log table
 * Helper function to easily log system activities
 * 
 * @param string $task_name Task/action name
 * @param array $request_data Request data to log (will be JSON encoded)
 * @param int|string $user_id User ID (optional, defaults to session user_id)
 * @return array Result array with status and message
 */
function log_activity($task_name, $request_data = [], $user_id = null)
{
    global $con, $current_date_time;

    if (!isset($con) || !$con) {
        return ['status' => 'error', 'message' => 'Database connection not available'];
    }

    // Get user ID from session if not provided
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }

    // Get IP address
    $ip_address = '';
    if (function_exists('get_ip')) {
        $ip_address = get_ip();
    } else {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    $log_data = [
        'user_id' => $user_id,
        'date_time' => $current_date_time ?? date('Y-m-d H:i:s'),
        'task_name' => $task_name,
        'request_data' => json_encode($request_data),
        'ip_address' => $ip_address,
        'status' => 'ACTIVE'
    ];

    try {
        $result = insert_data('activity_log', $log_data);
        return $result;
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function get_task_action_url($task, $case_id, $user_type = '')
{
    $task_id = $task['id'] ?? 0;
    $task_status = strtoupper($task['db_status'] ?? $task['task_status'] ?? 'PENDING');

    switch ($task_status) {
        case 'PENDING':
        case 'IN_PROGRESS':
            return 'task_verifier_submit.php?case_task_id=' . $task_id;

        case 'VERIFICATION_COMPLETED':
            return 'task_review.php?case_task_id=' . $task_id;

        case 'COMPLETED':
            // Check if reviewed (has review_status)
            $task_data = is_array($task['task_data'] ?? null) ? $task['task_data'] :
                (is_string($task['task_data'] ?? null) ? json_decode($task['task_data'], true) : []);

            if (isset($task_data['review_status']) && !empty($task_data['review_status'])) {
                return 'task_review.php?case_task_id=' . $task_id; // View review
            }
            return 'view_case.php?case_id=' . $case_id; // View case

        default:
            return 'view_case.php?case_id=' . $case_id;
    }
}

/**
 * =====================================================
 * ROLE MANAGEMENT FUNCTIONS
 * =====================================================
 */

/**
 * Check if user has access to a client
 * 
 * @param int $user_id User ID
 * @param int $client_id Client ID
 * @param string $user_type User type (BEO, TL, MANAGER, CLIENT, ADMIN, DEV)
 * @return bool True if user has access
 */
function can_user_access_client($user_id, $client_id, $user_type)
{
    global $con;

    // Admin and DEV have access to all clients
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return true;
    }

    // CLIENT users can only access their own client
    if ($user_type == 'CLIENT') {
        $user_data = get_data('op_user', $user_id)['data'] ?? [];
        // Assuming client users have a client_id field or relationship
        // This needs to be adjusted based on your schema
        return true; // Placeholder - adjust based on your schema
    }

    // Check user_clients table for BEO, TL, MANAGER
    if ($user_type == 'BEO' || $user_type == 'TL' || $user_type == 'MANAGER') {
        $check_query = "SELECT id FROM user_clients 
                       WHERE user_id = '$user_id' AND client_id = '$client_id' AND status = 'ACTIVE'";
        $check_result = mysqli_query($con, $check_query);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            return true;
        }

        // Also check hierarchy: BEO can access clients assigned to them via beo_id
        if ($user_type == 'BEO') {
            $client_check = "SELECT id FROM clients WHERE id = '$client_id' AND status = 'ACTIVE'";
            // Additional logic based on your schema
        }
    }

    return false;
}

/**
 * Check if user has access to a task
 * 
 * @param int $user_id User ID
 * @param int $task_id Task ID
 * @param string $user_type User type
 * @return bool True if user has access
 */
function can_user_access_task($user_id, $task_id, $user_type)
{
    global $con;

    // Admin and DEV have access to all tasks
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return true;
    }

    // CLIENT users have view-only access to their tasks
    if ($user_type == 'CLIENT') {
        return true; // View only - check case access
    }

    // Check user_tasks table for TL, MANAGER
    if ($user_type == 'TL' || $user_type == 'MANAGER') {
        $check_query = "SELECT id FROM user_tasks 
                       WHERE user_id = '$user_id' AND task_id = '$task_id' AND status = 'ACTIVE'";
        $check_result = mysqli_query($con, $check_query);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            return true;
        }
    }

    // BEO has access to all tasks for their allowed clients
    if ($user_type == 'BEO') {
        // Check if task belongs to a client the BEO has access to
        $task_query = "SELECT ct.case_id, c.client_id 
                      FROM case_tasks ct
                      JOIN cases c ON ct.case_id = c.id
                      WHERE ct.id = '$task_id' AND ct.status = 'ACTIVE'";
        $task_result = mysqli_query($con, $task_query);
        if ($task_result && $task_row = mysqli_fetch_assoc($task_result)) {
            return can_user_access_client($user_id, $task_row['client_id'], $user_type);
        }
    }

    return false;
}

/**
 * Get allowed clients for a user
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @return array Array of client IDs
 */
function get_user_allowed_clients($user_id, $user_type)
{
    global $con;
    $client_ids = [];

    // Admin and DEV have access to all clients
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        $all_clients = get_all('clients', 'id', ['status' => 'ACTIVE']);
        if ($all_clients['count'] > 0) {
            foreach ($all_clients['data'] as $client) {
                $client_ids[] = $client['id'];
            }
        }
        return $client_ids;
    }

    // CLIENT users - get their own client
    if ($user_type == 'CLIENT') {
        // Adjust based on your schema
        return $client_ids;
    }

    // Get from user_clients table
    $query = "SELECT client_id FROM user_clients 
              WHERE user_id = '$user_id' AND status = 'ACTIVE'";
    $result = mysqli_query($con, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $client_ids[] = $row['client_id'];
        }
    }

    return $client_ids;
}

/**
 * Get allowed tasks for a user
 * 
 * @param int $user_id User ID
 * @param string $user_type User type
 * @return array Array of task IDs
 */
function get_user_allowed_tasks($user_id, $user_type)
{
    global $con;
    $task_ids = [];

    // Admin and DEV have access to all tasks
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        $all_tasks = get_all('tasks', 'id', ['status' => 'ACTIVE']);
        if ($all_tasks['count'] > 0) {
            foreach ($all_tasks['data'] as $task) {
                $task_ids[] = $task['id'];
            }
        }
        return $task_ids;
    }

    // BEO has access to all tasks for their allowed clients
    if ($user_type == 'BEO') {
        $allowed_clients = get_user_allowed_clients($user_id, $user_type);
        if (!empty($allowed_clients)) {
            $client_ids_str = implode(',', array_map('intval', $allowed_clients));
            $query = "SELECT DISTINCT ct.task_template_id 
                     FROM case_tasks ct
                     JOIN cases c ON ct.case_id = c.id
                     WHERE c.client_id IN ($client_ids_str) AND ct.status = 'ACTIVE'";
            $result = mysqli_query($con, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $task_ids[] = $row['task_template_id'];
                }
            }
        }
        return array_unique($task_ids);
    }

    // Get from user_tasks table for TL, MANAGER
    $query = "SELECT task_id FROM user_tasks 
              WHERE user_id = '$user_id' AND status = 'ACTIVE'";
    $result = mysqli_query($con, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $task_ids[] = $row['task_id'];
        }
    }

    return $task_ids;
}

/**
 * Check if user can perform action based on role
 * 
 * @param string $user_type User type
 * @param string $action Action name (create_case, assign_task, view_case, etc.)
 * @return bool True if user can perform the action
 */
function can_user_perform_action($user_type, $action)
{
    $role_permissions = [
        'ADMIN' => [
            'create_case' => true,
            'assign_task' => true,
            'view_case' => true,
            'edit_case' => true,
            'delete_case' => true,
            'view_all_clients' => true,
            'view_all_tasks' => true,
            'manage_users' => true,
            'manage_roles' => true,
            'view_activity_log' => true,
            'export_data' => true,
        ],
        'BEO' => [
            'create_case' => true,
            'assign_task' => true,
            'view_case' => true,
            'edit_case' => false,
            'delete_case' => false,
            'view_all_clients' => false,
            'view_all_tasks' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'view_activity_log' => false,
            'export_data' => false,
        ],
        'TL' => [
            'create_case' => false,
            'assign_task' => false,
            'view_case' => true,
            'edit_case' => false,
            'delete_case' => false,
            'view_all_clients' => false,
            'view_all_tasks' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'view_activity_log' => true,
            'export_data' => false,
        ],
        'MANAGER' => [
            'create_case' => false,
            'assign_task' => false,
            'view_case' => true,
            'edit_case' => false,
            'delete_case' => false,
            'view_all_clients' => false,
            'view_all_tasks' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'view_activity_log' => true,
            'export_data' => true,
        ],
        'CLIENT' => [
            'create_case' => false,
            'assign_task' => false,
            'view_case' => true,
            'edit_case' => false,
            'delete_case' => false,
            'view_all_clients' => false,
            'view_all_tasks' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'view_activity_log' => false,
            'export_data' => false,
        ],
    ];

    // DEV has all permissions
    if ($user_type == 'DEV') {
        return true;
    }

    return $role_permissions[$user_type][$action] ?? false;
}

/**
 * Filter cases query based on user role and permissions
 * 
 * @param string $user_type User type
 * @param int $user_id User ID
 * @param string $base_query Base SQL query
 * @return string Modified SQL query with WHERE conditions
 */
function filter_cases_by_role($user_type, $user_id, $base_query = '')
{
    global $con;

    // Admin and DEV can see all cases
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return $base_query;
    }

    // Get allowed clients for the user
    $allowed_clients = get_user_allowed_clients($user_id, $user_type);

    if (empty($allowed_clients)) {
        // No access - return query that returns no results
        return $base_query . " AND 1=0";
    }

    $client_ids_str = implode(',', array_map('intval', $allowed_clients));

    // Add client filter
    if (stripos($base_query, 'WHERE') !== false) {
        return $base_query . " AND c.client_id IN ($client_ids_str)";
    } else {
        return $base_query . " WHERE c.client_id IN ($client_ids_str)";
    }
}

/**
 * Filter tasks query based on user role and permissions
 * 
 * @param string $user_type User type
 * @param int $user_id User ID
 * @param string $base_query Base SQL query
 * @return string Modified SQL query with WHERE conditions
 */
function filter_tasks_by_role($user_type, $user_id, $base_query = '')
{
    global $con;

    // Admin and DEV can see all tasks
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return $base_query;
    }

    // BEO can see all tasks for their allowed clients
    if ($user_type == 'BEO') {
        $allowed_clients = get_user_allowed_clients($user_id, $user_type);
        if (empty($allowed_clients)) {
            return $base_query . " AND 1=0";
        }
        $client_ids_str = implode(',', array_map('intval', $allowed_clients));
        if (stripos($base_query, 'WHERE') !== false) {
            return $base_query . " AND c.client_id IN ($client_ids_str)";
        } else {
            return $base_query . " WHERE c.client_id IN ($client_ids_str)";
        }
    }

    // TL and MANAGER can see only allowed tasks
    if ($user_type == 'TL' || $user_type == 'MANAGER') {
        $allowed_tasks = get_user_allowed_tasks($user_id, $user_type);
        if (empty($allowed_tasks)) {
            return $base_query . " AND 1=0";
        }
        $task_ids_str = implode(',', array_map('intval', $allowed_tasks));
        if (stripos($base_query, 'WHERE') !== false) {
            return $base_query . " AND ct.task_template_id IN ($task_ids_str)";
        } else {
            return $base_query . " WHERE ct.task_template_id IN ($task_ids_str)";
        }
    }

    // CLIENT can see only their own cases' tasks
    if ($user_type == 'CLIENT') {
        // Adjust based on your schema
        return $base_query;
    }

    return $base_query;
}


function gen_ai_remarks($format, $data, $status = 'Postive')
{
    global $ai_apikey;

    $url = "https://api.openai.com/v1/chat/completions";

    $prompt = "
        You are a professional bank verification report assistant.
        
        Rules:
        - Do NOT assume missing data
        - Do NOT change facts
        - Use professional legal language
        - Fix grammar automatically
        - Output clean final report only
        - Use similar sentence flow and pattern as per format given
        - Report in one paragraph 50-150 words
        - Make full report sentiment on basis of status given
        
        Format:
        $format
        
        Data:
        $data
        
        Nature of Report:
        $status
        ";

    $data = [
        "model" => "gpt-4.1-mini",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are a professional bank verification report assistant."
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "temperature" => 0.2
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $ai_apikey"
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch);
        exit;
    }

    curl_close($ch);

    $result = json_decode($response, true);

    // ✅ Output only final report
    return trim($result['choices'][0]['message']['content'] ?? '');
}

/**
 * Generate Verification Particular Report for a case
 * Groups documents by type and combines multiple documents of the same type
 * 
 * @param int $case_id Case ID
 * @param array $options Optional parameters for customization
 * @return array Array of verification particular rows
 */
function generate_verification_particular_report($case_id, $options = [])
{
    global $con;

    if (!$con || !$case_id) {
        return [];
    }

    // Default options
    $default_options = [
        'include_pending' => true,
        'status_filter' => null, // null = all, or specific status
        'task_type_filter' => null, // null = all, or specific task type
    ];
    $options = array_merge($default_options, $options);

    // Check if case_tasks table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);

    if (!$has_case_tasks) {
        return [];
    }

    // Get client status words for status mapping
    $case_query = "SELECT c.client_id, cl.positve_status, cl.negative_status, cl.cnv_status 
                   FROM cases c 
                   LEFT JOIN clients cl ON c.client_id = cl.id 
                   WHERE c.id = '$case_id' AND c.status != 'DELETED'";
    $case_res = mysqli_query($con, $case_query);
    $client_status_words = [
        'POSITIVE' => 'Positive',
        'NEGATIVE' => 'Negative',
        'CNV' => 'CNV'
    ];
    if ($case_res && $case_row = mysqli_fetch_assoc($case_res)) {
        if (!empty($case_row['positve_status'])) {
            $client_status_words['POSITIVE'] = $case_row['positve_status'];
        }
        if (!empty($case_row['negative_status'])) {
            $client_status_words['NEGATIVE'] = $case_row['negative_status'];
        }
        if (!empty($case_row['cnv_status'])) {
            $client_status_words['CNV'] = $case_row['cnv_status'];
        }
    }

    // Get all tasks for this case
    $tasks_query = "SELECT ct.*, t.task_name as template_task_name, t.task_type as template_task_type 
                    FROM case_tasks ct 
                    LEFT JOIN tasks t ON ct.task_template_id = t.id 
                    WHERE ct.case_id = '$case_id' AND ct.status = 'ACTIVE'";

    // Apply filters
    if (!$options['include_pending']) {
        $tasks_query .= " AND ct.task_status != 'PENDING' AND ct.task_status IS NOT NULL";
    }

    if ($options['task_type_filter']) {
        $task_type_filter = mysqli_real_escape_string($con, $options['task_type_filter']);
        $tasks_query .= " AND (ct.task_type = '$task_type_filter' OR t.task_type = '$task_type_filter')";
    }

    $tasks_query .= " ORDER BY ct.id ASC";

    $tasks_result = mysqli_query($con, $tasks_query);

    if (!$tasks_result) {
        return [];
    }

    // Group documents by task_type
    $grouped_documents = [];

    while ($row = mysqli_fetch_assoc($tasks_result)) {
        $task_type = strtoupper(trim($row['task_type'] ?? $row['template_task_type'] ?? 'UNKNOWN'));
        $task_name = $row['task_name'] ?? $row['template_task_name'] ?? '';

        // Parse task_data JSON
        $task_data = [];
        if (!empty($row['task_data'])) {
            $task_data = json_decode($row['task_data'], true);
            if (!is_array($task_data)) {
                $task_data = [];
            }
        }

        // Extract fields from task_data - try multiple possible field names
        $document_holder_name = $task_data['document_holder_name'] ??
            $task_data['holder_name'] ??
            $task_data['applicant_name'] ??
            $task_data['name'] ?? '';
        $document_category = $task_data['document_category'] ??
            $task_data['category'] ??
            $task_data['doc_category'] ?? 'INCOME';
        $verification_point = $task_data['verification_point'] ??
            $task_data['verification_type'] ??
            $task_data['verification'] ?? 'CP';
        $local_ogl = $task_data['local_ogl'] ??
            $task_data['local'] ??
            $task_data['location'] ?? 'Local';
        $review_status = $task_data['review_status'] ?? '';
        $verifier_remarks = $task_data['verifier_remarks'] ?? '';
        $review_remarks = $task_data['review_remarks'] ?? '';
        $remarks = !empty($review_remarks) ? $review_remarks : $verifier_remarks;

        // Map review_status to client status words
        $status_display = '';
        if (!empty($review_status)) {
            $status_display = $client_status_words[$review_status] ?? $review_status;
        } else {
            // Fallback to task_status if no review_status
            $task_status = $row['task_status'] ?? '';
            if ($task_status == 'COMPLETED') {
                $status_display = $client_status_words['POSITIVE'] ?? 'Positive';
            } elseif ($task_status == 'VERIFICATION_COMPLETED') {
                $status_display = 'Pending Review';
            } else {
                $status_display = 'Pending';
            }
        }

        // Initialize group if not exists
        if (!isset($grouped_documents[$task_type])) {
            $grouped_documents[$task_type] = [
                'document_picked' => $task_type,
                'count' => 0,
                'holder_names' => [],
                'task_names' => [],
                'document_category' => $document_category,
                'verification_point' => $verification_point,
                'local_ogl' => $local_ogl,
                'status' => $status_display,
                'remarks' => []
            ];
        }

        // Add to group
        $grouped_documents[$task_type]['count']++;

        if (!empty($document_holder_name) && !in_array($document_holder_name, $grouped_documents[$task_type]['holder_names'])) {
            $grouped_documents[$task_type]['holder_names'][] = $document_holder_name;
        }

        if (!empty($task_name) && !in_array($task_name, $grouped_documents[$task_type]['task_names'])) {
            $grouped_documents[$task_type]['task_names'][] = $task_name;
        }

        if (!empty($remarks) && !in_array($remarks, $grouped_documents[$task_type]['remarks'])) {
            $grouped_documents[$task_type]['remarks'][] = $remarks;
        }

        // Update category, verification_point, local_ogl if they differ (use first non-empty value)
        if (empty($grouped_documents[$task_type]['document_category']) && !empty($document_category)) {
            $grouped_documents[$task_type]['document_category'] = $document_category;
        }
        if (empty($grouped_documents[$task_type]['verification_point']) && !empty($verification_point)) {
            $grouped_documents[$task_type]['verification_point'] = $verification_point;
        }
        if (empty($grouped_documents[$task_type]['local_ogl']) && !empty($local_ogl)) {
            $grouped_documents[$task_type]['local_ogl'] = $local_ogl;
        }

        // Update status - if any document has a different status, use the most important one
        if ($status_display != $grouped_documents[$task_type]['status'] && !empty($status_display)) {
            // If statuses differ, show the most important one (Positive > Referred > Pending)
            $status_priority = ['Positive' => 3, 'Referred' => 2, 'Pending' => 1, 'Negative' => 0];
            $current_priority = $status_priority[$grouped_documents[$task_type]['status']] ?? 0;
            $new_priority = $status_priority[$status_display] ?? 0;
            if ($new_priority > $current_priority) {
                $grouped_documents[$task_type]['status'] = $status_display;
            }
        }
    }

    // Convert grouped data to report format
    $report_data = [];
    foreach ($grouped_documents as $group) {
        $report_data[] = [
            'document_picked' => $group['document_picked'],
            'count_of_documents' => $group['count'],
            'document_holder_name' => implode('/', $group['holder_names']),
            'document_category' => $group['document_category'] ?: 'INCOME',
            'verification_point' => $group['verification_point'] ?: 'CP',
            'local_ogl' => $group['local_ogl'] ?: 'Local',
            'status' => $group['status'] ?: 'Pending',
            'remarks' => implode(' | ', $group['remarks']),
            'task_names' => implode('/', $group['task_names'])
        ];
    }

    return $report_data;
}

/**
 * Generate Verification Particular Report HTML Table
 * 
 * @param int $case_id Case ID
 * @param array $options Optional parameters
 * @return string HTML table
 */
function generate_verification_particular_table($case_id, $options = [])
{
    $report_data = generate_verification_particular_report($case_id, $options);

    if (empty($report_data)) {
        return '<p class="text-muted">No verification data available for this case.</p>';
    }

    $table_html = '<table class="table table-bordered table-sm" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">';
    $table_html .= '<thead style="background-color: #f8f9fa;">';
    $table_html .= '<tr>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Picked</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Count of Documents</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Holder Name</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Category</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Verification Point</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Local / OGL</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Status</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Remark</th>';
    $table_html .= '</tr>';
    $table_html .= '</thead>';
    $table_html .= '<tbody>';

    foreach ($report_data as $row) {
        $table_html .= '<tr>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row['document_picked']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row['count_of_documents']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars($row['document_holder_name'] ?: 'N/A') . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row['document_category']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row['verification_point']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row['local_ogl']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row['status']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars($row['remarks'] ?? '') . '</td>';
        $table_html .= '</tr>';
    }

    $table_html .= '</tbody>';
    $table_html .= '</table>';

    return $table_html;
}

/**
 * Generate Verification Particular Header Cell (Single Header Row)
 * Returns just the header cells (th elements) for a specific table index
 * 
 * @param int $case_id Case ID
 * @param int $table_index Table index (1-6)
 * @param array $options Optional parameters
 * @return string HTML header cells (th elements only, no table structure)
 */
function generate_verification_particular_header_cell($case_id, $table_index, $options = [])
{
    // Standard header cells for verification particular table
    $header_html = '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Picked</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Count of Documents</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Holder Name</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Category</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Verification Point</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Local / OGL</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Status</th>';
    $header_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Remark</th>';

    return $header_html;
}

/**
 * Generate Verification Particular Data Cell (Single Data Row)
 * Returns just the data cells (td elements) for a specific table index
 * 
 * @param int $case_id Case ID
 * @param int $table_index Table index (1-6)
 * @param array $options Optional parameters
 * @return string HTML data cells (td elements only, no table structure)
 */
function generate_verification_particular_data_cell($case_id, $table_index, $options = [])
{
    global $con;

    if (!$con || !$case_id) {
        // Return empty cells if no data
        return '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
    }

    // Get client status words for status mapping
    $case_query = "SELECT c.client_id, cl.positve_status, cl.negative_status, cl.cnv_status 
                   FROM cases c 
                   LEFT JOIN clients cl ON c.client_id = cl.id 
                   WHERE c.id = '$case_id' AND c.status != 'DELETED'";
    $case_res = mysqli_query($con, $case_query);
    $client_status_words = [
        'POSITIVE' => 'Positive',
        'NEGATIVE' => 'Negative',
        'CNV' => 'CNV'
    ];
    if ($case_res && $case_row = mysqli_fetch_assoc($case_res)) {
        if (!empty($case_row['positve_status'])) {
            $client_status_words['POSITIVE'] = $case_row['positve_status'];
        }
        if (!empty($case_row['negative_status'])) {
            $client_status_words['NEGATIVE'] = $case_row['negative_status'];
        }
        if (!empty($case_row['cnv_status'])) {
            $client_status_words['CNV'] = $case_row['cnv_status'];
        }
    }

    // Get all tasks for this case
    $tasks_query = "SELECT ct.*, t.task_name as template_task_name, t.task_type as template_task_type 
                    FROM case_tasks ct 
                    LEFT JOIN tasks t ON ct.task_template_id = t.id 
                    WHERE ct.case_id = '$case_id' AND ct.status = 'ACTIVE'";

    // Apply filters
    if (!isset($options['include_pending']) || !$options['include_pending']) {
        $tasks_query .= " AND ct.task_status != 'PENDING' AND ct.task_status IS NOT NULL";
    }

    $tasks_query .= " ORDER BY ct.id ASC";
    $tasks_result = mysqli_query($con, $tasks_query);

    if (!$tasks_result) {
        return '<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>';
    }

    // Group documents by task_type
    $grouped_documents = [];

    while ($row = mysqli_fetch_assoc($tasks_result)) {
        $task_type = strtoupper(trim($row['task_type'] ?? $row['template_task_type'] ?? 'UNKNOWN'));
        $task_name = $row['task_name'] ?? $row['template_task_name'] ?? '';

        // Parse task_data JSON
        $task_data = [];
        if (!empty($row['task_data'])) {
            $task_data = json_decode($row['task_data'], true);
            if (!is_array($task_data)) {
                $task_data = [];
            }
        }

        // Extract fields from task_data
        $document_holder_name = $task_data['document_holder_name'] ??
            $task_data['holder_name'] ??
            $task_data['applicant_name'] ??
            $task_data['name'] ?? '';
        $document_category = $task_data['document_category'] ??
            $task_data['category'] ??
            $task_data['doc_category'] ?? 'INCOME';
        $verification_point = $task_data['verification_point'] ??
            $task_data['verification_type'] ??
            $task_data['verification'] ?? 'CP';
        $local_ogl = $task_data['local_ogl'] ??
            $task_data['local'] ??
            $task_data['location'] ?? 'Local';
        $review_status = $task_data['review_status'] ?? '';
        $verifier_remarks = $task_data['verifier_remarks'] ?? '';
        $review_remarks = $task_data['review_remarks'] ?? '';
        $remarks = !empty($review_remarks) ? $review_remarks : $verifier_remarks;

        // Map review_status to client status words
        $status_display = '';
        if (!empty($review_status)) {
            $status_display = $client_status_words[$review_status] ?? $review_status;
        } else {
            $task_status = $row['task_status'] ?? '';
            if ($task_status == 'COMPLETED') {
                $status_display = $client_status_words['POSITIVE'] ?? 'Positive';
            } elseif ($task_status == 'VERIFICATION_COMPLETED') {
                $status_display = 'Pending Review';
            } else {
                $status_display = 'Pending';
            }
        }

        // Initialize group if not exists
        if (!isset($grouped_documents[$task_type])) {
            $grouped_documents[$task_type] = [
                'document_picked' => $task_type,
                'count' => 0,
                'holder_names' => [],
                'task_names' => [],
                'document_category' => $document_category,
                'verification_point' => $verification_point,
                'local_ogl' => $local_ogl,
                'status' => $status_display,
                'remarks' => []
            ];
        }

        // Add to group
        $grouped_documents[$task_type]['count']++;

        if (!empty($document_holder_name) && !in_array($document_holder_name, $grouped_documents[$task_type]['holder_names'])) {
            $grouped_documents[$task_type]['holder_names'][] = $document_holder_name;
        }

        if (!empty($task_name) && !in_array($task_name, $grouped_documents[$task_type]['task_names'])) {
            $grouped_documents[$task_type]['task_names'][] = $task_name;
        }

        if (!empty($remarks) && !in_array($remarks, $grouped_documents[$task_type]['remarks'])) {
            $grouped_documents[$task_type]['remarks'][] = $remarks;
        }

        // Update category, verification_point, local_ogl if they differ
        if (empty($grouped_documents[$task_type]['document_category']) && !empty($document_category)) {
            $grouped_documents[$task_type]['document_category'] = $document_category;
        }
        if (empty($grouped_documents[$task_type]['verification_point']) && !empty($verification_point)) {
            $grouped_documents[$task_type]['verification_point'] = $verification_point;
        }
        if (empty($grouped_documents[$task_type]['local_ogl']) && !empty($local_ogl)) {
            $grouped_documents[$task_type]['local_ogl'] = $local_ogl;
        }

        // Update status - if any document has a different status, use the most important one
        if ($status_display != $grouped_documents[$task_type]['status'] && !empty($status_display)) {
            $status_priority = ['Positive' => 3, 'Referred' => 2, 'Pending' => 1, 'Negative' => 0];
            $current_priority = $status_priority[$grouped_documents[$task_type]['status']] ?? 0;
            $new_priority = $status_priority[$status_display] ?? 0;
            if ($new_priority > $current_priority) {
                $grouped_documents[$task_type]['status'] = $status_display;
            }
        }
    }

    // Convert to indexed array
    $grouped_array = array_values($grouped_documents);
    $data_index = $table_index - 1; // Zero-based index

    // Get data for this table index, or return empty cells
    if (isset($grouped_array[$data_index])) {
        $group = $grouped_array[$data_index];
        $row_data = [
            'document_picked' => $group['document_picked'],
            'count_of_documents' => $group['count'],
            'document_holder_name' => implode('/', $group['holder_names']),
            'document_category' => $group['document_category'] ?: 'INCOME',
            'verification_point' => $group['verification_point'] ?: 'CP',
            'local_ogl' => $group['local_ogl'] ?: 'Local',
            'status' => $group['status'] ?: 'Pending',
            'remarks' => implode(' | ', $group['remarks'])
        ];

        // Generate data cells
        $data_html = '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['document_picked']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['count_of_documents']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars($row_data['document_holder_name']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['document_category']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['verification_point']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['local_ogl']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['status']) . '</td>';
        $data_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars($row_data['remarks']) . '</td>';

        return $data_html;
    } else {
        // Return empty cells if no data for this index
        return '<td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td><td style="border: 1px solid #ddd; padding: 8px;"></td>';
    }
}

/**
 * Generate Verification Particular Tables in Horizontal Layout (Left to Right)
 * Creates separate table for each task group, arranged horizontally
 * 
 * @param int $case_id Case ID
 * @param array $options Optional parameters
 * @return string HTML with multiple tables arranged horizontally
 */
function generate_verification_particular_tables_horizontal($case_id, $options = [])
{
    global $con;

    if (!$con || !$case_id) {
        return '<p class="text-muted">No verification data available.</p>';
    }

    // Get client status words for status mapping
    $case_query = "SELECT c.client_id, cl.positve_status, cl.negative_status, cl.cnv_status 
                   FROM cases c 
                   LEFT JOIN clients cl ON c.client_id = cl.id 
                   WHERE c.id = '$case_id' AND c.status != 'DELETED'";
    $case_res = mysqli_query($con, $case_query);
    $client_status_words = [
        'POSITIVE' => 'Positive',
        'NEGATIVE' => 'Negative',
        'CNV' => 'CNV'
    ];
    if ($case_res && $case_row = mysqli_fetch_assoc($case_res)) {
        if (!empty($case_row['positve_status'])) {
            $client_status_words['POSITIVE'] = $case_row['positve_status'];
        }
        if (!empty($case_row['negative_status'])) {
            $client_status_words['NEGATIVE'] = $case_row['negative_status'];
        }
        if (!empty($case_row['cnv_status'])) {
            $client_status_words['CNV'] = $case_row['cnv_status'];
        }
    }

    // Get all tasks for this case
    $tasks_query = "SELECT ct.*, t.task_name as template_task_name, t.task_type as template_task_type 
                    FROM case_tasks ct 
                    LEFT JOIN tasks t ON ct.task_template_id = t.id 
                    WHERE ct.case_id = '$case_id' AND ct.status = 'ACTIVE'";

    // Apply filters
    if (!isset($options['include_pending']) || !$options['include_pending']) {
        $tasks_query .= " AND ct.task_status != 'PENDING' AND ct.task_status IS NOT NULL";
    }

    $tasks_query .= " ORDER BY ct.id ASC";
    $tasks_result = mysqli_query($con, $tasks_query);

    if (!$tasks_result) {
        return '<p class="text-muted">No verification data available.</p>';
    }

    // Group documents by task_type (same logic as generate_verification_particular_report)
    $grouped_documents = [];

    while ($row = mysqli_fetch_assoc($tasks_result)) {
        $task_type = strtoupper(trim($row['task_type'] ?? $row['template_task_type'] ?? 'UNKNOWN'));
        $task_name = $row['task_name'] ?? $row['template_task_name'] ?? '';

        // Parse task_data JSON
        $task_data = [];
        if (!empty($row['task_data'])) {
            $task_data = json_decode($row['task_data'], true);
            if (!is_array($task_data)) {
                $task_data = [];
            }
        }

        // Extract fields from task_data
        $document_holder_name = $task_data['document_holder_name'] ??
            $task_data['holder_name'] ??
            $task_data['applicant_name'] ??
            $task_data['name'] ?? '';
        $document_category = $task_data['document_category'] ??
            $task_data['category'] ??
            $task_data['doc_category'] ?? 'INCOME';
        $verification_point = $task_data['verification_point'] ??
            $task_data['verification_type'] ??
            $task_data['verification'] ?? 'CP';
        $local_ogl = $task_data['local_ogl'] ??
            $task_data['local'] ??
            $task_data['location'] ?? 'Local';
        $review_status = $task_data['review_status'] ?? '';
        $verifier_remarks = $task_data['verifier_remarks'] ?? '';
        $review_remarks = $task_data['review_remarks'] ?? '';
        $remarks = !empty($review_remarks) ? $review_remarks : $verifier_remarks;

        // Map review_status to client status words
        $status_display = '';
        if (!empty($review_status)) {
            $status_display = $client_status_words[$review_status] ?? $review_status;
        } else {
            $task_status = $row['task_status'] ?? '';
            if ($task_status == 'COMPLETED') {
                $status_display = $client_status_words['POSITIVE'] ?? 'Positive';
            } elseif ($task_status == 'VERIFICATION_COMPLETED') {
                $status_display = 'Pending Review';
            } else {
                $status_display = 'Pending';
            }
        }

        // Initialize group if not exists
        if (!isset($grouped_documents[$task_type])) {
            $grouped_documents[$task_type] = [
                'document_picked' => $task_type,
                'count' => 0,
                'holder_names' => [],
                'task_names' => [],
                'document_category' => $document_category,
                'verification_point' => $verification_point,
                'local_ogl' => $local_ogl,
                'status' => $status_display,
                'remarks' => []
            ];
        }

        // Add to group
        $grouped_documents[$task_type]['count']++;

        if (!empty($document_holder_name) && !in_array($document_holder_name, $grouped_documents[$task_type]['holder_names'])) {
            $grouped_documents[$task_type]['holder_names'][] = $document_holder_name;
        }

        if (!empty($task_name) && !in_array($task_name, $grouped_documents[$task_type]['task_names'])) {
            $grouped_documents[$task_type]['task_names'][] = $task_name;
        }

        if (!empty($remarks) && !in_array($remarks, $grouped_documents[$task_type]['remarks'])) {
            $grouped_documents[$task_type]['remarks'][] = $remarks;
        }

        // Update category, verification_point, local_ogl if they differ
        if (empty($grouped_documents[$task_type]['document_category']) && !empty($document_category)) {
            $grouped_documents[$task_type]['document_category'] = $document_category;
        }
        if (empty($grouped_documents[$task_type]['verification_point']) && !empty($verification_point)) {
            $grouped_documents[$task_type]['verification_point'] = $verification_point;
        }
        if (empty($grouped_documents[$task_type]['local_ogl']) && !empty($local_ogl)) {
            $grouped_documents[$task_type]['local_ogl'] = $local_ogl;
        }

        // Update status - if any document has a different status, use the most important one
        if ($status_display != $grouped_documents[$task_type]['status'] && !empty($status_display)) {
            $status_priority = ['Positive' => 3, 'Referred' => 2, 'Pending' => 1, 'Negative' => 0];
            $current_priority = $status_priority[$grouped_documents[$task_type]['status']] ?? 0;
            $new_priority = $status_priority[$status_display] ?? 0;
            if ($new_priority > $current_priority) {
                $grouped_documents[$task_type]['status'] = $status_display;
            }
        }
    }

    if (empty($grouped_documents)) {
        return '<p class="text-muted">No verification data available for this case.</p>';
    }

    // Define color palette for different task types (cycled if more task types than colors)
    $color_palette = [
        '#e3f2fd', // Light blue
        '#f3e5f5', // Light purple
        '#e8f5e9', // Light green
        '#fff3e0', // Light orange
        '#fce4ec', // Light pink
        '#e0f2f1', // Light teal
        '#f1f8e9', // Light lime
        '#ede7f6', // Light deep purple
        '#e8eaf6', // Light indigo
        '#fff9c4', // Light yellow
    ];

    // Create HTML container with horizontal layout - Generate tables for ALL task types
    //$html = '<div style="display: flex; flex-wrap: wrap; gap: 10px; width: 100%;">';

    $table_index = 0;
    foreach ($grouped_documents as $task_type => $group) {
        // Get color for this table (cycle through palette)
        $header_color = $color_palette[$table_index % count($color_palette)];
        $table_number = $table_index + 1;

        // Convert group to row data
        $row_data = [
            'document_picked' => $group['document_picked'],
            'count_of_documents' => $group['count'],
            'document_holder_name' => implode('/', $group['holder_names']),
            'document_category' => $group['document_category'] ?: 'INCOME',
            'verification_point' => $group['verification_point'] ?: 'CP',
            'local_ogl' => $group['local_ogl'] ?: 'Local',
            'status' => $group['status'] ?: 'Pending',
            'remarks' => implode(' | ', $group['remarks'])
        ];

        // Create table container (each table takes up to 50% width, so 2 per row)
        $html .= '<div style="flex: 1; min-width: 400px; max-width: 50%;">';

        // Table title header with different color for each task type
        // $html .= '<div style="font-weight: bold; text-align: center; padding: 8px; background-color: ' . $header_color . '; border: 1px solid #ddd; border-bottom: none;">';
        // $html .= 'Verification Particular (Doc / Profile etc.) - ' . $table_number;
        // $html .= '</div>';

        // Table structure
        // $table_html = '<table class="table table-bordered table-sm" style="width: 100%; border-collapse: collapse; font-size: 0.9rem; margin: 0;">';
        // $table_html .= '<thead style="background-color: ' . $header_color . ';">';
        // $table_html .= '<tr>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Picked</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Count of Documents</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Holder Name</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Document Category</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Verification Point</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Local / OGL</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Status</th>';
        // $table_html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">Remark</th>';
        // $table_html .= '</tr>';
        // $table_html .= '</thead>';
        // $table_html .= '<tbody>';

        // Add data row
        //$table_html .= '<tr>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['document_picked']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['count_of_documents']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars($row_data['document_holder_name']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['document_category']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['verification_point']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['local_ogl']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">' . htmlspecialchars($row_data['status']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . htmlspecialchars($row_data['remarks']) . '</td>';
        // $table_html .= '</tr>';

        // $table_html .= '</tbody>';
        // $table_html .= '</table>';

        $html .= $table_html;
        $html .= '</div>';

        $table_index++;
    }

    //$html .= '</div>';

    return $html;
}

/**
 * Generate Complete MIS Report for a case
 * Shows case details + multiple Verification Particular sections (one per task type)
 * Each section groups documents by task_type and combines data
 * 
 * @param int $case_id Case ID
 * @param array $options Optional parameters
 * @return array Complete MIS data with case details and verification particulars
 */
function generate_case_mis_report($case_id, $options = [])
{
    global $con;

    if (!$con || !$case_id) {
        return null;
    }

    // Default options
    $default_options = [
        'include_pending' => false, // Only completed/verified by default
        'max_sections' => 10, // Maximum number of verification particular sections
    ];
    $options = array_merge($default_options, $options);

    // Get case details
    $case_query = "SELECT c.*, cl.name as client_name, cl.positve_status, cl.negative_status, cl.cnv_status
                   FROM cases c 
                   LEFT JOIN clients cl ON c.client_id = cl.id 
                   WHERE c.id = '$case_id' AND c.status != 'DELETED'";
    $case_res = mysqli_query($con, $case_query);

    if (!$case_res || mysqli_num_rows($case_res) == 0) {
        return null;
    }

    $case = mysqli_fetch_assoc($case_res);

    // Parse case_info JSON if exists
    $case_info = [];
    if (!empty($case['case_info'])) {
        $case_info = json_decode($case['case_info'], true);
        if (!is_array($case_info)) {
            $case_info = [];
        }
    }

    // Get client status words
    $client_status_words = [
        'POSITIVE' => $case['positve_status'] ?? 'Positive',
        'NEGATIVE' => $case['negative_status'] ?? 'Negative',
        'CNV' => $case['cnv_status'] ?? 'CNV'
    ];

    // Get verification particulars grouped by task_type
    $verification_particulars = generate_verification_particular_report($case_id, [
        'include_pending' => $options['include_pending']
    ]);

    // Organize into sections (one per task type)
    $sections = [];
    $section_number = 1;

    foreach ($verification_particulars as $particular) {
        if ($section_number > $options['max_sections']) {
            break;
        }

        $sections["section_{$section_number}"] = [
            'section_number' => $section_number,
            'document_picked' => $particular['document_picked'],
            'count_of_documents' => $particular['count_of_documents'],
            'document_holder_name' => $particular['document_holder_name'],
            'document_category' => $particular['document_category'],
            'verification_point' => $particular['verification_point'],
            'local_ogl' => $particular['local_ogl'],
            'status' => $particular['status'],
            'remark' => $particular['remarks']
        ];

        $section_number++;
    }

    // Build complete MIS data
    $mis_data = [
        // Case Details
        'case_id' => $case['id'],
        'application_no' => $case['application_no'] ?? 'N/A',
        'client_name' => $case['client_name'] ?? 'N/A',
        'case_status' => $case['case_status'] ?? 'ACTIVE',
        'case_created_at' => $case['created_at'] ?? '',

        // Case Info fields (from JSON)
        'case_info' => $case_info,

        // CMV Details (for Section 1) - extract from case_info or calculate
        'cmv_details' => $case_info['cmv_details'] ?? $case_info['cmv'] ?? '',
        'cmv_count' => $case_info['cmv_count'] ?? count($verification_particulars),
        'agency_dedupe_status' => $case_info['agency_dedupe_status'] ?? $case_info['dedupe_status'] ?? '',

        // Verification Particular Sections
        'sections' => $sections,
        'total_sections' => count($sections),

        // Additional case fields
        'case_fields' => $case
    ];

    return $mis_data;
}

/**
 * Generate Complete MIS Report HTML Table
 * Creates a single-row table with case details + multiple verification particular sections
 * 
 * @param int $case_id Case ID
 * @param array $options Optional parameters
 * @return string HTML table
 */
function generate_case_mis_table($case_id, $options = [])
{
    $mis_data = generate_case_mis_report($case_id, $options);

    if (!$mis_data) {
        return '<p class="text-muted">No data available for this case.</p>';
    }

    $table_html = '<table class="table table-bordered" style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">';
    $table_html .= '<thead style="background-color: #f8f9fa;">';
    $table_html .= '<tr>';

    // Case Details Columns
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Application No</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Client Name</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Case Status</th>';

    // Add case_info fields as columns if they exist
    if (!empty($mis_data['case_info'])) {
        foreach ($mis_data['case_info'] as $key => $value) {
            if (!in_array($key, ['cmv_details', 'cmv_count', 'agency_dedupe_status'])) {
                $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</th>';
            }
        }
    }

    // Verification Particular Section 1 (with CMV columns)
    $table_html .= '<th colspan="11" style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Verification Particular (Doc/Profile etc.)-1</th>';

    // Additional sections (2, 3, etc.)
    for ($i = 2; $i <= $mis_data['total_sections']; $i++) {
        $table_html .= '<th colspan="8" style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . ($i == 2 ? '#d1ecf1' : '#f8d7da') . ';">Verification Particular (Doc/Profile etc.)-' . $i . '</th>';
    }

    $table_html .= '</tr>';

    // Sub-header row for Section 1
    $table_html .= '<tr>';
    $case_info_cols = 0;
    if (!empty($mis_data['case_info'])) {
        foreach ($mis_data['case_info'] as $key => $value) {
            if (!in_array($key, ['cmv_details', 'cmv_count', 'agency_dedupe_status'])) {
                $case_info_cols++;
            }
        }
    }
    $table_html .= '<th colspan="' . (3 + $case_info_cols) . '" style="border: 1px solid #ddd; padding: 6px; background-color: #fff3cd;"></th>';

    // Section 1 sub-headers
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Details of CMV</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">CMV Count</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Agency Dedupe Status</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Document Picked</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Count of Documents</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Document Holder Name</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Document Category</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Verification Point</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Local / OGL</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Status</th>';
    $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: #fff3cd;">Remark</th>';

    // Sub-headers for additional sections
    for ($i = 2; $i <= $mis_data['total_sections']; $i++) {
        $bg_color = $i == 2 ? '#d1ecf1' : '#f8d7da';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Document Picked</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Count of Documents</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Document Holder Name</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Document Category</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Verification Point</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Local / OGL</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Status</th>';
        $table_html .= '<th style="border: 1px solid #ddd; padding: 6px; font-weight: 600; text-align: center; background-color: ' . $bg_color . ';">Remark</th>';
    }

    $table_html .= '</tr>';
    $table_html .= '</thead>';
    $table_html .= '<tbody>';
    $table_html .= '<tr>';

    // Case Details Data
    $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($mis_data['application_no']) . '</td>';
    $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($mis_data['client_name']) . '</td>';
    $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($mis_data['case_status']) . '</td>';

    // Case Info fields data
    if (!empty($mis_data['case_info'])) {
        foreach ($mis_data['case_info'] as $key => $value) {
            if (!in_array($key, ['cmv_details', 'cmv_count', 'agency_dedupe_status'])) {
                $display_value = is_array($value) ? json_encode($value) : $value;
                $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($display_value) . '</td>';
            }
        }
    }

    // Section 1 Data (with CMV columns)
    $section_1 = $mis_data['sections']['section_1'] ?? null;
    if ($section_1) {
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($mis_data['cmv_details']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($mis_data['cmv_count']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($mis_data['agency_dedupe_status']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section_1['document_picked']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section_1['count_of_documents']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: left;">' . htmlspecialchars($section_1['document_holder_name'] ?: 'N/A') . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section_1['document_category']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section_1['verification_point']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section_1['local_ogl']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section_1['status']) . '</td>';
        $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: left;">' . htmlspecialchars($section_1['remark']) . '</td>';
    } else {
        // Empty cells for section 1
        $table_html .= str_repeat('<td style="border: 1px solid #ddd; padding: 6px;"></td>', 11);
    }

    // Additional sections data (2, 3, etc.)
    for ($i = 2; $i <= $mis_data['total_sections']; $i++) {
        $section_key = "section_{$i}";
        $section = $mis_data['sections'][$section_key] ?? null;

        if ($section) {
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section['document_picked']) . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section['count_of_documents']) . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: left;">' . htmlspecialchars($section['document_holder_name'] ?: 'N/A') . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section['document_category']) . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section['verification_point']) . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section['local_ogl']) . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: center;">' . htmlspecialchars($section['status']) . '</td>';
            $table_html .= '<td style="border: 1px solid #ddd; padding: 6px; text-align: left;">' . htmlspecialchars($section['remark']) . '</td>';
        } else {
            // Empty cells
            $table_html .= str_repeat('<td style="border: 1px solid #ddd; padding: 6px;"></td>', 8);
        }
    }

    $table_html .= '</tr>';
    $table_html .= '</tbody>';
    $table_html .= '</table>';

    return $table_html;
}

/**
 * Generate MIS Excel Report from Template Configuration
 * 
 * @param array $template_config Template configuration with columns
 * @param array $data Optional pre-fetched data
 * @return string Path to generated file or false on error
 */
function generate_mis_excel($template_config, $data = null)
{
    global $con;

    if (!isset($template_config['columns']) || empty($template_config['columns'])) {
        return false;
    }

    require_once('system/vendor/autoload.php');

    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($template_config['name'] ?? 'MIS Report');

        $currentRow = 1;
        $currentCol = 1;

        // Check for groups
        $hasGroups = false;
        foreach ($template_config['columns'] as $column) {
            if (isset($column['type']) && $column['type'] === 'group' && isset($column['columns']) && count($column['columns']) > 0) {
                $hasGroups = true;
                break;
            }
        }

        // Create headers
        if ($hasGroups) {
            $groupHeaderRow = $currentRow;
            $columnHeaderRow = $currentRow + 1;
            $dataStartRow = $currentRow + 2;

            foreach ($template_config['columns'] as $column) {
                if (isset($column['type']) && $column['type'] === 'group') {
                    $groupLabel = $column['label'] ?? 'Group';
                    $groupColCount = isset($column['columns']) ? count($column['columns']) : 1;

                    if ($groupColCount > 0) {
                        $startCol = $currentCol;
                        $endCol = $currentCol + $groupColCount - 1;

                        $startCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol) . $groupHeaderRow;
                        $endCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($endCol) . $groupHeaderRow;
                        $sheet->mergeCells($startCell . ':' . $endCell);
                        $sheet->setCellValue($startCell, $groupLabel);

                        if (isset($column['columns'])) {
                            foreach ($column['columns'] as $subCol) {
                                $colLabel = $subCol['label'] ?? 'Column';
                                $colCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol) . $columnHeaderRow;
                                $sheet->setCellValue($colCell, $colLabel);
                                $currentCol++;
                            }
                        }
                    }
                } else {
                    $colLabel = $column['label'] ?? 'Column';
                    $colCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol) . $groupHeaderRow;
                    $sheet->mergeCells($colCell . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol) . $columnHeaderRow);
                    $sheet->setCellValue($colCell, $colLabel);
                    $currentCol++;
                }
            }

            // Style group header
            $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $groupHeaderRow . ':' .
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol - 1) . $groupHeaderRow)
                ->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ]);

            $currentRow = $columnHeaderRow;
        } else {
            foreach ($template_config['columns'] as $column) {
                $colLabel = $column['label'] ?? 'Column';
                $colCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol) . $currentRow;
                $sheet->setCellValue($colCell, $colLabel);
                $currentCol++;
            }
            $dataStartRow = $currentRow + 1;
        }

        // Style column headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];

        $headerRowToStyle = $hasGroups ? $columnHeaderRow : $currentRow;
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $headerRowToStyle . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol - 1) . $headerRowToStyle)
            ->applyFromArray($headerStyle);

        // Get data
        if ($data === null) {
            $data = get_mis_data_from_config($template_config['columns']);
        }

        // Generate data rows
        $currentRow = $dataStartRow;
        foreach ($data as $dataRow) {
            $currentCol = 1;

            foreach ($template_config['columns'] as $column) {
                if (isset($column['type']) && $column['type'] === 'group' && isset($column['columns'])) {
                    foreach ($column['columns'] as $subCol) {
                        $value = get_mis_column_value($subCol, $dataRow);
                        $colCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol) . $currentRow;
                        $sheet->setCellValue($colCell, $value);
                        $currentCol++;
                    }
                } else {
                    $value = get_mis_column_value($column, $dataRow);
                    $colCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol) . $currentRow;
                    $sheet->setCellValue($colCell, $value);
                    $currentCol++;
                }
            }

            $currentRow++;
        }

        // Auto-size columns
        for ($col = 1; $col < $currentCol; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header
        $freezeRow = $hasGroups ? $columnHeaderRow + 1 : $dataStartRow;
        $sheet->freezePane('A' . $freezeRow);

        return $spreadsheet;

    } catch (Exception $e) {
        error_log('MIS Excel Generation Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get data for MIS based on column configuration
 */
function get_mis_data_from_config($columns)
{
    global $con;

    $dataRows = [];
    $hasLoop = false;
    $loopQuery = '';

    foreach ($columns as $column) {
        $checkCols = [];
        if (isset($column['type']) && $column['type'] === 'group' && isset($column['columns'])) {
            $checkCols = $column['columns'];
        } else {
            $checkCols = [$column];
        }

        foreach ($checkCols as $col) {
            if (isset($col['placeholderType']) && $col['placeholderType'] === 'loop' && !empty($col['dataSource'])) {
                $hasLoop = true;
                $loopQuery = $col['dataSource'];
                break 2;
            }
        }
    }

    if ($hasLoop && !empty($loopQuery)) {
        $result = mysqli_query($con, $loopQuery);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dataRows[] = $row;
            }
        }
    } else {
        // Default: return empty row for static content
        $dataRows[] = [];
    }

    return $dataRows;
}

/**
 * Get value for a MIS column based on configuration
 */
function get_mis_column_value($column, $dataRow = [])
{
    $placeholderType = $column['placeholderType'] ?? 'static';

    switch ($placeholderType) {
        case 'static':
            return $column['placeholderValue'] ?? '';

        case 'loop':
            if (!empty($column['dataField']) && isset($dataRow[$column['dataField']])) {
                return $dataRow[$column['dataField']];
            }
            return '';

        case 'dynamic':
            if (!empty($column['dataField'])) {
                $field = $column['dataField'];

                if (isset($dataRow[$field])) {
                    return $dataRow[$field];
                }

                // Try nested access
                $parts = explode('.', $field);
                $value = $dataRow;
                foreach ($parts as $part) {
                    if (isset($value[$part])) {
                        $value = $value[$part];
                    } else {
                        return '';
                    }
                }
                return $value;
            }
            return '';

        default:
            return '';
    }
}

/**
 * Generate MIS Editor Data Structure for Single Row Table
 * Returns data in format: ['client'=>[], 'task_type1'=>[], ..., 'task_type6'=>[]]
 * Used for MIS Editor to generate single row tables with client meta and task type data
 * 
 * @param int $case_id Case ID
 * @param int $client_id Client ID (optional, will be fetched from case if not provided)
 * @param array $options Optional parameters:
 *   - 'max_task_types' => 6 (maximum number of task types to include)
 *   - 'include_pending' => true (include pending tasks)
 *   - 'column_labels' => [] (custom column labels mapping)
 * @return array Structured data array with client meta and task types
 */
function generate_mis_editor_data($case_id, $client_id = null, $options = [])
{
    global $con;

    if (!$con || !$case_id) {
        return [
            'client' => [],
            'task_type1' => [],
            'task_type2' => [],
            'task_type3' => [],
            'task_type4' => [],
            'task_type5' => [],
            'task_type6' => []
        ];
    }

    // Default options
    $default_options = [
        'max_task_types' => 6,
        'include_pending' => true,
        'column_labels' => []
    ];
    $options = array_merge($default_options, $options);

    // Initialize result structure
    $result = [
        'client' => []
    ];

    // Initialize task type slots
    for ($i = 1; $i <= $options['max_task_types']; $i++) {
        $result["task_type{$i}"] = [];
    }

    // Get case information
    $case_query = "SELECT c.*, cl.id as client_id_from_case, cl.name as client_name, 
                          cl.positve_status, cl.negative_status, cl.cnv_status
                   FROM cases c 
                   LEFT JOIN clients cl ON c.client_id = cl.id 
                   WHERE c.id = '$case_id' AND c.status != 'DELETED'";
    $case_res = mysqli_query($con, $case_query);

    if (!$case_res || mysqli_num_rows($case_res) == 0) {
        return $result;
    }

    $case = mysqli_fetch_assoc($case_res);

    // Use provided client_id or get from case
    if (!$client_id) {
        $client_id = $case['client_id_from_case'] ?? $case['client_id'] ?? 0;
    }

    // Get client status words
    $client_status_words = [
        'POSITIVE' => $case['positve_status'] ?? 'Positive',
        'NEGATIVE' => $case['negative_status'] ?? 'Negative',
        'CNV' => $case['cnv_status'] ?? 'CNV'
    ];

    // 1. Get Client Meta Information
    $client_meta_data = [];

    // Get client meta field definitions
    if ($client_id > 0) {
        $client_meta_query = "SELECT field_name, display_name FROM clients_meta 
                             WHERE client_id = '$client_id' AND status = 'ACTIVE' 
                             ORDER BY id ASC";
        $client_meta_res = mysqli_query($con, $client_meta_query);

        if ($client_meta_res) {
            while ($meta_row = mysqli_fetch_assoc($client_meta_res)) {
                $field_name = $meta_row['field_name'];
                $display_name = $meta_row['display_name'];

                // Get value from case_info JSON
                $value = '';
                if (!empty($case['case_info'])) {
                    $case_info = json_decode($case['case_info'], true);
                    if (is_array($case_info) && isset($case_info[$field_name])) {
                        $value = $case_info[$field_name] ?? '';
                    }
                }

                // Use custom label if provided, otherwise use display_name
                $label = $options['column_labels']["client_{$field_name}"] ??
                    $options['column_labels'][$field_name] ??
                    $display_name;

                $client_meta_data[$field_name] = [
                    'value' => $value,
                    'label' => $label,
                    'display_name' => $display_name
                ];
            }
        }
    }

    // Add basic client information
    $client_meta_data['client_name'] = [
        'value' => $case['client_name'] ?? '',
        'label' => $options['column_labels']['client_name'] ?? 'Client Name',
        'display_name' => 'Client Name'
    ];

    // Add case basic info to client meta
    if (!empty($case['case_info'])) {
        $case_info = json_decode($case['case_info'], true);
        if (is_array($case_info)) {
            foreach ($case_info as $key => $value) {
                if (!isset($client_meta_data[$key])) {
                    $label = $options['column_labels']["client_{$key}"] ??
                        $options['column_labels'][$key] ??
                        ucwords(str_replace('_', ' ', $key));
                    $client_meta_data[$key] = [
                        'value' => $value ?? '',
                        'label' => $label,
                        'display_name' => ucwords(str_replace('_', ' ', $key))
                    ];
                }
            }
        }
    }

    $result['client'] = $client_meta_data;

    // 2. Get Task Types Data
    // Check if case_tasks table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);

    if (!$has_case_tasks) {
        return $result;
    }

    // Get all tasks for this case
    $tasks_query = "SELECT ct.*, t.task_name as template_task_name, t.task_type as template_task_type 
                    FROM case_tasks ct 
                    LEFT JOIN tasks t ON ct.task_template_id = t.id 
                    WHERE ct.case_id = '$case_id' AND ct.status = 'ACTIVE'";

    if (!$options['include_pending']) {
        $tasks_query .= " AND ct.task_status != 'PENDING' AND ct.task_status IS NOT NULL";
    }

    $tasks_query .= " ORDER BY ct.id ASC";

    $tasks_result = mysqli_query($con, $tasks_query);

    if (!$tasks_result) {
        return $result;
    }

    // Group documents by task_type
    $grouped_documents = [];

    while ($row = mysqli_fetch_assoc($tasks_result)) {
        $task_type = strtoupper(trim($row['task_type'] ?? $row['template_task_type'] ?? 'UNKNOWN'));
        $task_name = $row['task_name'] ?? $row['template_task_name'] ?? '';

        // Parse task_data JSON
        $task_data = [];
        if (!empty($row['task_data'])) {
            $task_data = json_decode($row['task_data'], true);
            if (!is_array($task_data)) {
                $task_data = [];
            }
        }

        // Extract fields from task_data
        $document_holder_name = $task_data['document_holder_name'] ??
            $task_data['holder_name'] ??
            $task_data['applicant_name'] ??
            $task_data['name'] ?? '';
        $document_category = $task_data['document_category'] ??
            $task_data['category'] ??
            $task_data['doc_category'] ?? 'INCOME';
        $verification_point = $task_data['verification_point'] ??
            $task_data['verification_type'] ??
            $task_data['verification'] ?? 'CP';
        $local_ogl = $task_data['local_ogl'] ??
            $task_data['local'] ??
            $task_data['location'] ?? 'Local';
        $review_status = $task_data['review_status'] ?? '';
        $verifier_remarks = $task_data['verifier_remarks'] ?? '';
        $review_remarks = $task_data['review_remarks'] ?? '';
        $remarks = !empty($review_remarks) ? $review_remarks : $verifier_remarks;

        // Map review_status to client status words
        $status_display = '';
        if (!empty($review_status)) {
            $status_display = $client_status_words[$review_status] ?? $review_status;
        } else {
            $task_status = $row['task_status'] ?? '';
            if ($task_status == 'COMPLETED') {
                $status_display = $client_status_words['POSITIVE'] ?? 'Positive';
            } elseif ($task_status == 'VERIFICATION_COMPLETED') {
                $status_display = 'Pending Review';
            } else {
                $status_display = 'Pending';
            }
        }

        // Initialize group if not exists
        if (!isset($grouped_documents[$task_type])) {
            $grouped_documents[$task_type] = [
                'document_picked' => $task_type,
                'count' => 0,
                'holder_names' => [],
                'task_names' => [],
                'document_category' => $document_category,
                'verification_point' => $verification_point,
                'local_ogl' => $local_ogl,
                'status' => $status_display,
                'remarks' => []
            ];
        }

        // Add to group
        $grouped_documents[$task_type]['count']++;

        if (!empty($document_holder_name) && !in_array($document_holder_name, $grouped_documents[$task_type]['holder_names'])) {
            $grouped_documents[$task_type]['holder_names'][] = $document_holder_name;
        }

        if (!empty($task_name) && !in_array($task_name, $grouped_documents[$task_type]['task_names'])) {
            $grouped_documents[$task_type]['task_names'][] = $task_name;
        }

        if (!empty($remarks) && !in_array($remarks, $grouped_documents[$task_type]['remarks'])) {
            $grouped_documents[$task_type]['remarks'][] = $remarks;
        }

        // Update category, verification_point, local_ogl if they differ
        if (empty($grouped_documents[$task_type]['document_category']) && !empty($document_category)) {
            $grouped_documents[$task_type]['document_category'] = $document_category;
        }
        if (empty($grouped_documents[$task_type]['verification_point']) && !empty($verification_point)) {
            $grouped_documents[$task_type]['verification_point'] = $verification_point;
        }
        if (empty($grouped_documents[$task_type]['local_ogl']) && !empty($local_ogl)) {
            $grouped_documents[$task_type]['local_ogl'] = $local_ogl;
        }

        // Update status - use the most important one
        if ($status_display != $grouped_documents[$task_type]['status'] && !empty($status_display)) {
            $status_priority = ['Positive' => 3, 'Referred' => 2, 'Pending' => 1, 'Negative' => 0];
            $current_priority = $status_priority[$grouped_documents[$task_type]['status']] ?? 0;
            $new_priority = $status_priority[$status_display] ?? 0;
            if ($new_priority > $current_priority) {
                $grouped_documents[$task_type]['status'] = $status_display;
            }
        }
    }

    // Convert grouped data to task type format and assign to slots
    $task_type_index = 1;
    foreach ($grouped_documents as $task_type => $group) {
        if ($task_type_index > $options['max_task_types']) {
            break;
        }

        $slot_key = "task_type{$task_type_index}";

        // Get custom labels or use defaults
        $labels = [
            'document_picked' => $options['column_labels']["{$slot_key}_document_picked"] ??
                $options['column_labels']['document_picked'] ?? 'Document Picked',
            'count_of_documents' => $options['column_labels']["{$slot_key}_count_of_documents"] ??
                $options['column_labels']['count_of_documents'] ?? 'Count of Documents',
            'document_holder_name' => $options['column_labels']["{$slot_key}_document_holder_name"] ??
                $options['column_labels']['document_holder_name'] ?? 'Document Holder Name',
            'document_category' => $options['column_labels']["{$slot_key}_document_category"] ??
                $options['column_labels']['document_category'] ?? 'Document Category',
            'verification_point' => $options['column_labels']["{$slot_key}_verification_point"] ??
                $options['column_labels']['verification_point'] ?? 'Verification Point',
            'local_ogl' => $options['column_labels']["{$slot_key}_local_ogl"] ??
                $options['column_labels']['local_ogl'] ?? 'Local / OGL',
            'status' => $options['column_labels']["{$slot_key}_status"] ??
                $options['column_labels']['status'] ?? 'Status',
            'remarks' => $options['column_labels']["{$slot_key}_remarks"] ??
                $options['column_labels']['remarks'] ?? 'Remark'
        ];

        $result[$slot_key] = [
            'document_picked' => [
                'value' => $group['document_picked'],
                'label' => $labels['document_picked']
            ],
            'count_of_documents' => [
                'value' => $group['count'],
                'label' => $labels['count_of_documents']
            ],
            'document_holder_name' => [
                'value' => implode('/', $group['holder_names']),
                'label' => $labels['document_holder_name']
            ],
            'document_category' => [
                'value' => $group['document_category'] ?: 'INCOME',
                'label' => $labels['document_category']
            ],
            'verification_point' => [
                'value' => $group['verification_point'] ?: 'CP',
                'label' => $labels['verification_point']
            ],
            'local_ogl' => [
                'value' => $group['local_ogl'] ?: 'Local',
                'label' => $labels['local_ogl']
            ],
            'status' => [
                'value' => $group['status'] ?: 'Pending',
                'label' => $labels['status']
            ],
            'remarks' => [
                'value' => implode(' | ', $group['remarks']),
                'label' => $labels['remarks']
            ],
            'task_names' => [
                'value' => implode('/', $group['task_names']),
                'label' => $options['column_labels']["{$slot_key}_task_names"] ??
                    $options['column_labels']['task_names'] ?? 'Task Names'
            ]
        ];

        $task_type_index++;
    }

    return $result;
}

/**
 * Generate Single Row HTML Table from MIS Editor Data
 * Creates a single row table with client meta and task type columns
 * 
 * @param array $mis_data Data from generate_mis_editor_data()
 * @param array $options Optional parameters:
 *   - 'include_headers' => true (include table headers)
 *   - 'table_class' => 'table table-bordered' (CSS classes for table)
 * @return string HTML table
 */
function generate_mis_editor_table($mis_data, $options = [])
{
    $default_options = [
        'include_headers' => true,
        'table_class' => 'table table-bordered table-sm'
    ];
    $options = array_merge($default_options, $options);

    $html = '<table class="' . htmlspecialchars($options['table_class']) . '" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">';

    // Generate headers
    if ($options['include_headers']) {
        $html .= '<thead style="background-color: #f8f9fa;">';
        $html .= '<tr>';

        // Client meta headers
        if (isset($mis_data['client']) && is_array($mis_data['client'])) {
            foreach ($mis_data['client'] as $field_name => $field_data) {
                $label = is_array($field_data) ? ($field_data['label'] ?? $field_data['display_name'] ?? $field_name) : $field_name;
                $html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">' .
                    htmlspecialchars($label) . '</th>';
            }
        }

        // Task type headers
        for ($i = 1; $i <= 6; $i++) {
            $slot_key = "task_type{$i}";
            if (isset($mis_data[$slot_key]) && is_array($mis_data[$slot_key]) && !empty($mis_data[$slot_key])) {
                foreach ($mis_data[$slot_key] as $field_key => $field_data) {
                    $label = is_array($field_data) ? ($field_data['label'] ?? $field_key) : $field_key;
                    $html .= '<th style="border: 1px solid #ddd; padding: 8px; font-weight: 600; text-align: center;">' .
                        htmlspecialchars($label) . '</th>';
                }
            }
        }

        $html .= '</tr>';
        $html .= '</thead>';
    }

    // Generate data row
    $html .= '<tbody>';
    $html .= '<tr>';

    // Client meta data
    if (isset($mis_data['client']) && is_array($mis_data['client'])) {
        foreach ($mis_data['client'] as $field_name => $field_data) {
            $value = is_array($field_data) ? ($field_data['value'] ?? '') : $field_data;
            $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' .
                htmlspecialchars($value ?: '-') . '</td>';
        }
    }

    // Task type data
    for ($i = 1; $i <= 6; $i++) {
        $slot_key = "task_type{$i}";
        if (isset($mis_data[$slot_key]) && is_array($mis_data[$slot_key]) && !empty($mis_data[$slot_key])) {
            foreach ($mis_data[$slot_key] as $field_key => $field_data) {
                $value = is_array($field_data) ? ($field_data['value'] ?? '') : $field_data;
                $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: left;">' .
                    htmlspecialchars($value ?: '-') . '</td>';
            }
        }
    }

    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}



function send_banking($data)
{
    $url = "https://rmoutsourcingandconsulting.com/system/api.php?task=update_banking";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ],
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return ["status" => "error", "msg" => curl_error($ch)];
    }

    curl_close($ch);
    return json_decode($response, true);
}



function send_itr($data)
{
    $url = "https://rmoutsourcingandconsulting.com/system/api.php?task=update_itr";

    // Same fields as your UI
    // $data = [
    //     "name_of_document" => "ITR",
    //     "pan"              => "DTIDPA2323A",
    //     "ay"               => "1000",
    //     "requirement"      => "Testing"
    // ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // POST request
    curl_setopt($ch, CURLOPT_POST, true);

    // Send as form-data
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    // Headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return [
            "status" => "error",
            "msg" => curl_error($ch)
        ];
    }

    curl_close($ch);

    return json_decode($response, true);
}