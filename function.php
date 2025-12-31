<?php
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
require_once('system/op_lib.php');

function btn_meta($table, $id ,$link, $icon='link')
{
	$view_link = $link.'?link='.encode('table='.$table.'&id='.$id);
	$str ="<a class='btn btn-dark btn-sm' href='$view_link' ><i class='fa fa-$icon'></i></a>";
	return $str ;										
}	

function btn_stucture($table, $id ,$link, $icon='cubes')
{
	$view_link = $link.'?link='.encode('table='.$table.'&id='.$id);
	$str =" <a class='btn btn-danger btn-sm' href='$view_link' ><i class='fa fa-$icon'></i></a>";
	return $str ;										
}	

/**
 * Extract variables from report format text
 */
function extract_variables_from_text($text) {
    if (empty($text)) return [];
    preg_match_all('/#([a-zA-Z0-9_]+)#/', $text, $matches);
    return array_unique($matches[1]);
}

/**
 * Get field properties based on variable name
 */
function get_field_properties_from_variable($var_name) {
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
    if (strpos($var_lower, 'date') !== false || strpos($var_lower, 'dob') !== false || 
        strpos($var_lower, 'joining') !== false || strpos($var_lower, 'registration') !== false ||
        strpos($var_lower, 'closing') !== false || strpos($var_lower, 'dorf') !== false) {
        $properties['input_type'] = 'DATE';
    }
    
    // Number fields
    if (strpos($var_lower, 'amount') !== false || strpos($var_lower, 'income') !== false ||
        strpos($var_lower, 'rent') !== false || strpos($var_lower, 'fee') !== false ||
        strpos($var_lower, 'area') !== false || strpos($var_lower, 'rate') !== false ||
        strpos($var_lower, 'tax') !== false || strpos($var_lower, 'turnover') !== false ||
        strpos($var_lower, 'outstanding') !== false || strpos($var_lower, 'payment') !== false ||
        strpos($var_lower, 'students') !== false || strpos($var_lower, 'teacher') !== false ||
        strpos($var_lower, 'staff') !== false || strpos($var_lower, 'family') !== false) {
        $properties['input_type'] = 'NUMBER';
    }
    
    // Textarea fields
    if (strpos($var_lower, 'remark') !== false || strpos($var_lower, 'address') !== false ||
        strpos($var_lower, 'transaction') !== false || strpos($var_lower, 'tpc') !== false) {
        $properties['input_type'] = 'TEXTAREA';
    }
    
    // Client provided fields
    if (strpos($var_lower, 'applicant') !== false || strpos($var_lower, 'document_no') !== false ||
        strpos($var_lower, 'pan') !== false || strpos($var_lower, 'aadhar') !== false ||
        strpos($var_lower, 'bank') !== false || strpos($var_lower, 'account') !== false ||
        strpos($var_lower, 'financial_year') !== false || strpos($var_lower, 'ay') !== false ||
        strpos($var_lower, 'tenant') !== false || strpos($var_lower, 'seller') !== false ||
        strpos($var_lower, 'dealer') !== false) {
        $properties['by_client'] = 'YES';
        $properties['is_required'] = 'YES';
    }
    
    // Verifier fields
    if (strpos($var_lower, 'met_with') !== false || strpos($var_lower, 'verification') !== false ||
        strpos($var_lower, 'locality') !== false || strpos($var_lower, 'ownership') !== false ||
        strpos($var_lower, 'nob') !== false || strpos($var_lower, 'time_period') !== false ||
        strpos($var_lower, 'business_period') !== false) {
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
function get_display_name_for_variable($var_name) {
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
        $name  = htmlspecialchars($row['field_name']);
        $label = htmlspecialchars($row['display_name']);
        $type  = strtoupper($row['input_type']);
        
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
        $name  = htmlspecialchars($row['field_name']);
        $label = htmlspecialchars($row['display_name']);
        $type  = strtoupper($row['input_type']);
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
    $get_file_icon = function($file_type, $file_name) {
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
                    $review_status_display = '';
                    if (!empty($review_status)) {
                        $review_status_display = $client_status_words[$review_status] ?? $review_status;
                    }
                    
                    // Combine task_status with review_status if available (using client status words)
                    $combined_status = $task_status;
                    if (!empty($review_status_display)) {
                      //  $combined_status = $task_status . ' (Review: ' . $review_status_display . ')';
                        $combined_status =  $review_status_display ;

                    }
                    
                    $documents[] = [
                        'id' => $row['id'],
                        'task_type' => $task_type,
                        'task_name' => $task_name,
                        'status' => $combined_status,
                        'review_status' => $review_status,
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
                    $task_data['task_remarks'] = !empty($all_task_remarks) ? implode('<br>', array_map(function($r) { return nl2br($r); }, $all_task_remarks)) : nl2br($first_task['remarks'] ?? '');
                    // Task status (line break separated if multiple) - includes review_status
                    $all_task_statuses = [];
                    foreach ($documents as $doc) {
                        if (!empty($doc['status']) && !in_array($doc['status'], $all_task_statuses)) {
                            $all_task_statuses[] = $doc['status'];
                        }
                    }
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
                    // Task status (may include review_status from meta)
                    $task_data['task_status'] = $first_task['status'] ?? '';
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
        // Get all attachments for tasks in this case (all active attachments)
        $attachments_query = "SELECT a.* FROM attachments a 
                             INNER JOIN case_tasks ct ON a.task_id = ct.id 
                             WHERE ct.case_id = '$case_id' AND a.status = 'ACTIVE' AND ct.status = 'ACTIVE'
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
    
    // 7. Merge custom data (overrides)
    $data = array_merge($data, $custom_data);
    
    // 8. Process Document Loop (support both {{loop_start}} and #loop_start# formats)
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
    
    // 9. Replace all placeholders (support both {{variable}} and #variable# formats)
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
    
    // 10. Replace system variables
    $system_vars = [
        'current_date' => date('d-m-Y'),
        'report_date' => date('d-m-Y'),
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
    $html = preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function($matches) use ($data) {
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
 * @param string $template_type Template type (STANDARD, CUSTOM, TASK_SPECIFIC)
 * @return int|null Template ID or null
 */
function get_report_template_for_case($case_id, $template_type = 'STANDARD')
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
    
    // Get default template for client
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
    
    // If no default, get first active template
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
