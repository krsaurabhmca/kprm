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
        
        // Use existing value if available, otherwise use default
        $value = '';
        if (isset($existing_values[$row['field_name']]) && !empty($existing_values[$row['field_name']])) {
            $value = htmlspecialchars($existing_values[$row['field_name']]);
        } elseif (!empty($row['default_value'])) {
            $value = htmlspecialchars($row['default_value']);
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
                $html .= "<select name='client_meta[{$name}]' class='form-control' {$required}>";
                $html .= "<option value=''>Select {$label}</option>";
                if (!empty($value)) {
                    $html .= "<option value='{$value}' selected>{$value}</option>";
                }
                // TODO: Load options from master/config
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
