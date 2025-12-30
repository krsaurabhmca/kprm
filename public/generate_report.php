<?php
/**
 * KPRM - Generate Report
 * Generates a comprehensive report from template by replacing placeholders with actual data
 * Supports: Case-level reports, Task-level reports, Export to DOC/XLS/PDF
 */

require_once('../system/op_lib.php');
require_once('../function.php');

// Get base_url
global $base_url;
if (!isset($base_url)) {
    require_once('../system/op_config.php');
}

// Check if table exists
global $con;
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
$table_exists = ($table_check && mysqli_num_rows($table_check) > 0);

if (!$table_exists) {
    die('Database tables not found. Please run: SOURCE db/create_report_templates_table.sql; in your database.');
}

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
$case_task_id = isset($_GET['case_task_id']) ? intval($_GET['case_task_id']) : 0;
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$export_format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html'; // html, doc, xls, pdf

if (!$case_id && !$case_task_id) {
    die('Invalid case ID or case task ID');
}

// If case_task_id provided, get case_id from it
if ($case_task_id > 0 && $case_id == 0) {
    $case_task_result = get_data('case_tasks', $case_task_id);
    if ($case_task_result['count'] > 0) {
        $case_id = $case_task_result['data']['case_id'];
    }
}

if (!$case_id) {
    die('Invalid case ID');
}

// Get case data
$case_result = get_data('cases', $case_id);
if ($case_result['count'] == 0) {
    die('Case not found');
}

$case_data = $case_result['data'];
$client_id = $case_data['client_id'] ?? 0;

// Get client data
$client_result = get_data('clients', $client_id);
$client_data = $client_result['count'] > 0 ? $client_result['data'] : [];

// Get all client meta fields
$client_meta_fields = [];
if ($client_id > 0) {
    $client_meta_sql = "
        SELECT field_name, display_name 
        FROM clients_meta 
        WHERE client_id = '$client_id' 
        AND status = 'ACTIVE'
        ORDER BY id ASC
    ";
    $client_meta_res = mysqli_query($con, $client_meta_sql);
    if ($client_meta_res) {
        while ($row = mysqli_fetch_assoc($client_meta_res)) {
            $client_meta_fields[$row['field_name']] = $row['display_name'];
        }
    }
}

// Get case info (JSON) - contains client meta values
$case_info_json = json_decode($case_data['case_info'] ?? '{}', true);
if (!is_array($case_info_json)) {
    $case_info_json = [];
}

// Get all tasks for this case
$all_tasks = [];
$tasks_sql = "
    SELECT ct.*, t.task_name, t.task_type
    FROM case_tasks ct
    LEFT JOIN tasks t ON ct.task_template_id = t.id
    WHERE ct.case_id = '$case_id' 
    AND ct.status = 'ACTIVE'
    ORDER BY ct.id ASC
";
$tasks_res = mysqli_query($con, $tasks_sql);
if ($tasks_res) {
    while ($row = mysqli_fetch_assoc($tasks_res)) {
        $all_tasks[] = $row;
    }
} else {
    // Log error if query fails
    error_log("Task query error: " . mysqli_error($con));
}

// Debug: Log task count
error_log("Total tasks found: " . count($all_tasks));

// Get specific task if case_task_id provided
$current_task = null;
$current_task_data = [];
if ($case_task_id > 0) {
    foreach ($all_tasks as $task) {
        if ($task['id'] == $case_task_id) {
            $current_task = $task;
            $current_task_data = json_decode($task['task_data'] ?? '{}', true);
            if (!is_array($current_task_data)) {
                $current_task_data = [];
            }
            break;
        }
    }
}

// Get report template
if ($template_id > 0) {
    $template_result = get_data('report_templates', $template_id);
} else {
    // Get default template for client
    $template_sql = "
        SELECT * FROM report_templates 
        WHERE client_id = '$client_id' 
        AND status = 'ACTIVE' 
        ORDER BY is_default DESC, id DESC 
        LIMIT 1
    ";
    $template_res = mysqli_query($con, $template_sql);
    if ($template_res && mysqli_num_rows($template_res) > 0) {
        $template_result = ['count' => 1, 'data' => mysqli_fetch_assoc($template_res)];
    } else {
        $template_result = ['count' => 0];
    }
}

if ($template_result['count'] == 0) {
    die('No report template found. Please create a template first.');
}

$template = $template_result['data'];
$template_html = $template['template_html'];
$template_css = $template['template_css'] ?? '';

// Build replacement data array
$replacements = [];

// System variables
$replacements['case_id'] = $case_id;
$replacements['application_no'] = $case_data['application_no'] ?? 'N/A';
$replacements['client_name'] = $client_data['name'] ?? 'Unknown';
$replacements['current_date'] = date('d-M-Y');
$replacements['current_time'] = date('h:i A');
$replacements['current_datetime'] = date('d-M-Y h:i A');
$replacements['report_date'] = date('d-M-Y');

// Get agency name from config or client data
$replacements['agency_name'] = $client_data['name'] ?? 'Verification Agency';
$replacements['signature'] = 'Signature Area';
$replacements['tat'] = 'TAT: ' . date('d-M-Y'); // Can be calculated based on case dates

// Generate serial number (can be based on case_id or auto-increment)
$replacements['serial_number'] = str_pad($case_id, 6, '0', STR_PAD_LEFT);

// Task-related variables
$task_names = [];
$task_remarks = [];
$all_task_remarks = [];
$total_attachments = 0;
$all_attachments = [];

foreach ($all_tasks as $task) {
    $task_data = json_decode($task['task_data'] ?? '{}', true);
    if (!is_array($task_data)) {
        $task_data = [];
    }
    
    // Get task name (from JOIN or fetch separately)
    $task_name = $task['task_name'] ?? 'Unknown Task';
    if ($task_name == 'Unknown Task' && !empty($task['task_template_id'])) {
        $task_template_result = get_data('tasks', $task['task_template_id']);
        if ($task_template_result['count'] > 0) {
            $task_name = $task_template_result['data']['task_name'] ?? 'Unknown Task';
        }
    }
    
    $task_names[] = $task_name;
    
    if (!empty($task_data['verifier_remarks'])) {
        $task_remarks[] = $task_data['verifier_remarks'];
        $all_task_remarks[] = $task_name . ': ' . $task_data['verifier_remarks'];
    }
    
    // Get attachments for this task (include display_in_report field)
    $task_attachments_sql = "
        SELECT file_url, file_name, file_type, display_in_report
        FROM attachments 
        WHERE task_id = '{$task['id']}' 
        AND status = 'ACTIVE'
        ORDER BY created_at ASC
    ";
    $task_attachments_res = mysqli_query($con, $task_attachments_sql);
    if ($task_attachments_res) {
        while ($attach_row = mysqli_fetch_assoc($task_attachments_res)) {
            $total_attachments++;
            // Ensure display_in_report has a default value
            if (!isset($attach_row['display_in_report'])) {
                $attach_row['display_in_report'] = 'NO';
            }
            $all_attachments[] = $attach_row;
        }
    }
}

$replacements['task_count'] = count($all_tasks);
$replacements['task_names'] = implode(', ', $task_names);
$replacements['all_tasks_list'] = implode(', ', $task_names);

// Get current task name properly
$current_task_name = 'No Task';
if ($current_task) {
    $current_task_name = $current_task['task_name'] ?? 'Unknown Task';
    if ($current_task_name == 'Unknown Task' && !empty($current_task['task_template_id'])) {
        $task_template_result = get_data('tasks', $current_task['task_template_id']);
        if ($task_template_result['count'] > 0) {
            $current_task_name = $task_template_result['data']['task_name'] ?? 'Unknown Task';
        }
    }
} elseif (count($task_names) > 0) {
    $current_task_name = $task_names[0];
}

$replacements['task_name'] = $current_task_name;
$replacements['task_remarks'] = $current_task ? ($current_task_data['verifier_remarks'] ?? '') : '';
$replacements['all_task_remarks'] = !empty($all_task_remarks) ? implode("\n\n", $all_task_remarks) : 'No task remarks available.';
$replacements['task_attachments_count'] = $total_attachments;

// Client meta variables from case_info
foreach ($client_meta_fields as $field_name => $display_name) {
    $replacements[$field_name] = $case_info_json[$field_name] ?? '';
}

// Current task data (if specific task selected)
if ($current_task) {
    foreach ($current_task_data as $key => $value) {
        if (!is_array($value) && !is_object($value)) {
            $replacements[$key] = $value;
        }
    }
    
    $replacements['verifier_remarks'] = $current_task_data['verifier_remarks'] ?? '';
    $replacements['review_status'] = $current_task_data['review_status'] ?? '';
    $replacements['review_remarks'] = $current_task_data['review_remarks'] ?? '';
    
    // Get verifier info if assigned
    if (!empty($current_task['assigned_to'])) {
        $verifier_result = get_data('verifier', $current_task['assigned_to']);
        if ($verifier_result['count'] > 0) {
            $verifier = $verifier_result['data'];
            $replacements['verifier_name'] = $verifier['verifier_name'] ?? '';
            $replacements['verifier_mobile'] = $verifier['verifier_mobile'] ?? '';
            $replacements['verifier_type'] = $verifier['verifier_type'] ?? '';
        }
    }
    
    // Get attachments for current task
    $current_task_attachments = [];
    $current_attachments_sql = "
        SELECT file_url, file_name, file_type
        FROM attachments 
        WHERE task_id = '$case_task_id' 
        AND status = 'ACTIVE'
        AND display_in_report = 'YES'
        ORDER BY created_at ASC
    ";
    $current_attachments_res = mysqli_query($con, $current_attachments_sql);
    if ($current_attachments_res) {
        while ($attach_row = mysqli_fetch_assoc($current_attachments_res)) {
            $current_task_attachments[] = $attach_row;
        }
    }
    
    // Build attachments list HTML
    $attachments_list_html = '';
    $attachments_list_text = '';
    // Get base_url
    global $base_url;
    if (!isset($base_url)) {
        require_once('../system/op_config.php');
    }
    foreach ($current_task_attachments as $attach) {
        $file_path = '../upload/' . $attach['file_url'];
        $file_url = $base_url . 'upload/' . $attach['file_url'];
        $attachments_list_html .= '<div style="margin: 10px 0;"><a href="' . htmlspecialchars($file_url) . '" target="_blank">' . htmlspecialchars($attach['file_name']) . '</a></div>';
        $attachments_list_text .= $attach['file_name'] . "\n";
    }
    $replacements['task_attachments'] = $attachments_list_html;
    $replacements['task_attachments_list'] = $attachments_list_text;
    
    // Build images HTML
    $images_html = '';
    foreach ($current_task_attachments as $attach) {
        $ext = strtolower(pathinfo($attach['file_url'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $file_url = $base_url . 'upload/' . $attach['file_url'];
            $images_html .= '<img src="' . htmlspecialchars($file_url) . '" alt="' . htmlspecialchars($attach['file_name']) . '" style="max-width: 100%; width: auto; height: auto; margin: 5px auto; display: block; border: 1px solid #ddd; padding: 5px;" />';
        }
    }
    $replacements['visit_photos'] = $images_html;
    $replacements['attachments'] = $images_html;
} else {
    // For case-level report, show all attachments
    // Get base_url
    global $base_url;
    if (!isset($base_url)) {
        require_once('../system/op_config.php');
    }
    $all_images_html = '';
    $all_attachments_list = '';
    foreach ($all_attachments as $attach) {
        $ext = strtolower(pathinfo($attach['file_url'], PATHINFO_EXTENSION));
        $file_url = $base_url . 'upload/' . $attach['file_url'];
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $all_images_html .= '<img src="' . htmlspecialchars($file_url) . '" alt="' . htmlspecialchars($attach['file_name']) . '" style="max-width: 100%; width: auto; height: auto; margin: 5px auto; display: block; border: 1px solid #ddd; padding: 5px;" />';
        }
        $all_attachments_list .= $attach['file_name'] . "\n";
    }
    $replacements['task_attachments'] = $all_attachments_list;
    $replacements['attachments'] = $all_images_html;
    $replacements['visit_photos'] = $all_images_html;
}

// Handle task loop: #task_loop_start# ... #task_loop_end#
$task_loop_start = strpos($template_html, '#task_loop_start#');
$task_loop_end = strpos($template_html, '#task_loop_end#');

if ($task_loop_start !== false && $task_loop_end !== false) {
    // Extract loop template
    $loop_template = substr($template_html, $task_loop_start + strlen('#task_loop_start#'), $task_loop_end - $task_loop_start - strlen('#task_loop_start#'));
    
    // Generate loop content for each task
    $loop_content = '';
    $serial = 1;
    
    foreach ($all_tasks as $task) {
        $task_data = json_decode($task['task_data'] ?? '{}', true);
        if (!is_array($task_data)) {
            $task_data = [];
        }
        
        // Get task template to get task_type (use already joined data if available)
        $task_type = $task['task_type'] ?? 'N/A';
        $task_name = $task['task_name'] ?? 'Unknown Task';
        
        // If not available from JOIN, fetch separately
        if ($task_type == 'N/A' || $task_name == 'Unknown Task') {
            $task_template_result = get_data('tasks', $task['task_template_id']);
            $task_template_data = $task_template_result['count'] > 0 ? $task_template_result['data'] : [];
            $task_type = $task_template_data['task_type'] ?? $task_type;
            $task_name = $task_template_data['task_name'] ?? $task_name;
        }
        
        $task_loop_html = $loop_template;
        
        // Replace task loop variables
        $task_loop_replacements = [
            'task_serial' => $serial++,
            'task_type' => $task_type,
            'task_name_loop' => $task_name,
            'task_remarks_loop' => $task_data['verifier_remarks'] ?? '',
            'task_findings' => $task_data['verifier_remarks'] ?? '',
            'task_status_loop' => $task['task_status'] ?? 'PENDING',
        ];
        
        foreach ($task_loop_replacements as $key => $val) {
            $task_loop_html = str_replace('#' . $key . '#', htmlspecialchars($val), $task_loop_html);
        }
        
        $loop_content .= $task_loop_html;
    }
    
    // Replace the entire loop section with generated content
    $template_html = substr_replace($template_html, $loop_content, $task_loop_start, $task_loop_end + strlen('#task_loop_end#') - $task_loop_start);
}

// Count documents and images separately (only those marked for report)
$image_count = 0;
$document_count = 0;
$images = [];
$documents = [];
foreach ($all_attachments as $attach) {
    if (($attach['display_in_report'] ?? 'NO') == 'YES') {
        $ext = strtolower(pathinfo($attach['file_url'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $image_count++;
            $images[] = $attach;
        } else {
            $document_count++;
            $documents[] = $attach;
        }
    }
}

$replacements['image_count'] = $image_count;
$replacements['document_count'] = $document_count;

// Build attachment displays
// Images: 2 per row
$images_html = '';

if (!empty($images)) {
    $images_html = '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0;">';
    foreach ($images as $img) {
        $file_url = $base_url . 'upload/' . $img['file_url'];
        $images_html .= '<div style="flex: 0 0 calc(50% - 5px); max-width: calc(50% - 5px);">';
        $images_html .= '<img src="' . htmlspecialchars($file_url) . '" alt="' . htmlspecialchars($img['file_name']) . '" style="max-width: 100%; height: auto; border: 1px solid #ddd; padding: 5px;" />';
        $images_html .= '<p style="text-align: center; font-size: 10px; margin-top: 5px;">' . htmlspecialchars($img['file_name']) . '</p>';
        $images_html .= '</div>';
    }
    $images_html .= '</div>';
}

// Documents: Links with thumbnails
$documents_html = '';

if (!empty($documents)) {
    $documents_html = '<div style="margin: 20px 0;">';
    foreach ($documents as $doc) {
        $file_url = $base_url . 'upload/' . $doc['file_url'];
        $doc_icon = '📄';
        if (in_array(strtolower(pathinfo($doc['file_url'], PATHINFO_EXTENSION)), ['pdf'])) {
            $doc_icon = '📕';
        } elseif (in_array(strtolower(pathinfo($doc['file_url'], PATHINFO_EXTENSION)), ['doc', 'docx'])) {
            $doc_icon = '📘';
        } elseif (in_array(strtolower(pathinfo($doc['file_url'], PATHINFO_EXTENSION)), ['xls', 'xlsx'])) {
            $doc_icon = '📗';
        }
        $documents_html .= '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">';
        $documents_html .= '<span style="font-size: 24px; margin-right: 10px;">' . $doc_icon . '</span>';
        $documents_html .= '<a href="' . htmlspecialchars($file_url) . '" target="_blank" style="text-decoration: none; color: #007bff;">' . htmlspecialchars($doc['file_name']) . '</a>';
        $documents_html .= '</div>';
    }
    $documents_html .= '</div>';
}

$replacements['attachments_images'] = $images_html;
$replacements['attachments_documents'] = $documents_html;
$replacements['attachments_all'] = $images_html . $documents_html;
$replacements['all_attachments_display'] = $images_html . $documents_html;

// Replace placeholders in template
foreach ($replacements as $key => $value) {
    $placeholder = '#' . $key . '#';
    // Use htmlspecialchars for text, but preserve HTML for HTML variables
    if (in_array($key, ['task_attachments', 'attachments', 'visit_photos', 'attachments_images', 'attachments_documents', 'attachments_all', 'all_attachments_display'])) {
        $template_html = str_replace($placeholder, $value, $template_html);
    } else {
        $template_html = str_replace($placeholder, htmlspecialchars($value), $template_html);
    }
}

// Replace any remaining placeholders with empty string
$template_html = preg_replace('/#([a-zA-Z0-9_]+)#/', '', $template_html);

// Handle export formats
if ($export_format == 'pdf') {
    // PDF export using mPDF
    try {
        require_once('../system/vendor/autoload.php');
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 20,
            'margin_right' => 20,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'autoScriptToLang' => true,
            'autoLangToFont' => true
        ]);
        
        $mpdf->SetTitle('Report - ' . ($case_data['application_no'] ?? 'N/A'));
        $mpdf->SetAuthor('KPRM System');
        $mpdf->SetCreator('KPRM Case Management System');
        
        // Combine CSS and HTML with A4 width constraints
        $a4_css = '
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    font-size: 11pt;
                    line-height: 1.4;
                }
                * {
                    max-width: 100%;
                    box-sizing: border-box;
                }
                img {
                    max-width: 100% !important;
                    height: auto !important;
                }
                table {
                    width: 100% !important;
                    max-width: 100% !important;
                    table-layout: fixed;
                    word-wrap: break-word;
                    border-collapse: collapse;
                }
                td, th {
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    padding: 4px;
                }
                .report-container {
                    width: 100%;
                    max-width: 100%;
                }
                ' . $template_css . '
            </style>';
        $full_html = $a4_css . $template_html;
        
        $mpdf->WriteHTML($full_html);
        
        $filename = 'Report_' . ($case_data['application_no'] ?? $case_id) . '_' . date('YmdHis') . '.pdf';
        $mpdf->Output($filename, 'D');
        exit;
        
    } catch (Exception $e) {
        // Fallback: redirect to HTML view with print
        header('Location: ?case_id=' . $case_id . ($case_task_id ? '&case_task_id=' . $case_task_id : '') . ($template_id ? '&template_id=' . $template_id : '') . '&format=html');
        exit;
    }
    
} elseif ($export_format == 'doc') {
    // DOC export
    header('Content-Type: application/vnd.ms-word');
    header('Content-Disposition: attachment; filename="Report_' . ($case_data['application_no'] ?? $case_id) . '_' . date('YmdHis') . '.doc"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; font-size: 11pt; margin: 20mm; }';
    echo '* { max-width: 100%; box-sizing: border-box; }';
    echo 'img { max-width: 100% !important; height: auto !important; }';
    echo 'table { width: 100% !important; max-width: 100% !important; table-layout: fixed; word-wrap: break-word; }';
    echo 'td, th { word-wrap: break-word; overflow-wrap: break-word; }';
    echo $template_css;
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo $template_html;
    echo '</body>';
    echo '</html>';
    exit;
    
} elseif ($export_format == 'xls') {
    // XLS export (HTML table format)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Report_' . ($case_data['application_no'] ?? $case_id) . '_' . date('YmdHis') . '.xls"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; font-size: 11pt; }';
    echo '* { max-width: 100%; box-sizing: border-box; }';
    echo 'img { max-width: 100% !important; height: auto !important; }';
    echo 'table { width: 100% !important; max-width: 100% !important; table-layout: fixed; word-wrap: break-word; }';
    echo 'td, th { word-wrap: break-word; overflow-wrap: break-word; }';
    echo $template_css;
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo $template_html;
    echo '</body>';
    echo '</html>';
    exit;
}

// HTML view (default)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - <?php echo htmlspecialchars($case_data['application_no'] ?? 'N/A'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .report-container {
            background: white;
            padding: 20mm;
            max-width: 210mm;
            width: 210mm;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .report-container * {
            max-width: 100%;
        }
        .report-container img {
            max-width: 100% !important;
            height: auto !important;
        }
        .report-container table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
            word-wrap: break-word;
        }
        .report-container td, .report-container th {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .export-buttons {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .export-buttons button, .export-buttons a {
            margin: 5px;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-print { background: #007bff; color: white; }
        .btn-pdf { background: #dc3545; color: white; }
        .btn-doc { background: #28a745; color: white; }
        .btn-xls { background: #17a2b8; color: white; }
        .btn-back { background: #6c757d; color: white; }
        .btn-print:hover { background: #0056b3; }
        .btn-pdf:hover { background: #c82333; }
        .btn-doc:hover { background: #218838; }
        .btn-xls:hover { background: #138496; }
        .btn-back:hover { background: #5a6268; }
        @media print {
            @page {
                size: A4;
                margin: 20mm;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .report-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                width: 100%;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        <?php echo $template_css; ?>
    </style>
</head>
<body>
    <div class="no-print export-buttons">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="?case_id=<?php echo $case_id; ?><?php echo $case_task_id ? '&case_task_id=' . $case_task_id : ''; ?><?php echo $template_id ? '&template_id=' . $template_id : ''; ?>&format=pdf" class="btn-pdf">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>
        <a href="?case_id=<?php echo $case_id; ?><?php echo $case_task_id ? '&case_task_id=' . $case_task_id : ''; ?><?php echo $template_id ? '&template_id=' . $template_id : ''; ?>&format=doc" class="btn-doc">
            <i class="fas fa-file-word"></i> Download DOC
        </a>
        <a href="?case_id=<?php echo $case_id; ?><?php echo $case_task_id ? '&case_task_id=' . $case_task_id : ''; ?><?php echo $template_id ? '&template_id=' . $template_id : ''; ?>&format=xls" class="btn-xls">
            <i class="fas fa-file-excel"></i> Download XLS
        </a>
        <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Case
        </a>
    </div>
    
    <div class="report-container">
        <?php echo $template_html; ?>
    </div>
</body>
</html>
