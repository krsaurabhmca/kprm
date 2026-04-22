<?php
/**
 * KPRM - MIS Export
 * Generates Excel (.xlsx) file for MIS Report
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide in production, log instead

ob_start();

try {
    require_once('../system/op_lib.php');
    require_once('../function.php');
    require_once('../system/vendor/autoload.php');

    // Get filter parameters
    $f_case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
    $f_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
    
    // Default date range
    $date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($con, $_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
    $date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($con, $_GET['date_to']) : date('Y-m-d');

    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        ob_end_clean();
        die("PhpSpreadsheet library not found. Please run 'composer install'.");
    }

    // Build SQL query for cases
    $where = "c.status != 'DELETED'";
    if ($f_case_id > 0) {
        $where .= " AND c.id = $f_case_id";
    } else {
        $where .= " AND DATE(c.created_at) BETWEEN '$date_from' AND '$date_to'";
        if ($f_client_id > 0) {
            $where .= " AND c.client_id = $f_client_id";
        }
    }

    $sql_cases = "
        SELECT 
            c.id as case_id,
            c.application_no,
            c.case_info,
            c.created_at as case_received_date,
            c.case_status,
            cl.name as client_name,
            cl.positve_status,
            cl.negative_status,
            cl.cnv_status,
            u.user_name as creator_name
        FROM cases c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN op_user u ON c.created_by = u.id
        WHERE $where
        ORDER BY c.created_at DESC
    ";

    $res_cases = mysqli_query($con, $sql_cases);
    $cases_data = [];
    $case_ids = [];
    while ($row = mysqli_fetch_assoc($res_cases)) {
        $cases_data[] = $row;
        $case_ids[] = $row['case_id'];
    }

    // Fetch tasks for these cases
    $tasks_by_case = [];
    if (!empty($case_ids)) {
        $ids_str = implode(',', $case_ids);
        $sql_tasks = "
            SELECT 
                ct.case_id,
                ct.task_name,
                ct.task_type,
                ct.task_status,
                ct.created_at as task_created_at,
                ct.reviewed_at,
                ct.task_data,
                v.verifier_name as agency_name
            FROM case_tasks ct
            LEFT JOIN verifier v ON ct.assigned_to = v.id
            WHERE ct.case_id IN ($ids_str)
            AND ct.status = 'ACTIVE'
            ORDER BY ct.id ASC
        ";
        $res_tasks = mysqli_query($con, $sql_tasks);
        while ($t_row = mysqli_fetch_assoc($res_tasks)) {
            $tasks_by_case[$t_row['case_id']][] = $t_row;
        }
    }

    // Create Spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('MIS Report');

    // Header Style
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ];

    // Columns requested by user
    $headers = [
        'Sr No.', 'User Name', 'App ID', 'Customer Name', 'Location', 'Product', 
        'Initiation Document (Sampled Documents)', 'RCU Agency Name', 'Login Month', 
        'Received Date', 'RCU Send', 'TAT', 'Report Status', 'Report Comments', 
        'Loan Amount applied', 'Extra checks'
    ];

    // Add Headers
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        if ($col !== 'N') { // Auto-size everything except Comments
            $sheet->getColumnDimension($col)->setAutoSize(true);
        } else {
            $sheet->getColumnDimension($col)->setWidth(50); // Fixed width for Comments
        }
        $col++;
    }
    $sheet->getStyle('A1:' . (--$col) . '1')->applyFromArray($headerStyle);

    // Add Data
    $row_index = 2;
    $sr = 1;
    foreach ($cases_data as $row) {
        $case_id = $row['case_id'];
        $case_info = json_decode($row['case_info'] ?? '{}', true);
        $case_tasks = $tasks_by_case[$case_id] ?? [];
        
        $task_counts = [];
        $remarks_list = [];
        $agencies = [];
        $rcu_send_dates = [];
        $is_fully_completed = true;
        $latest_reviewed_at = null;
        $extra_checks_list = [];
        
        $has_negative = false;
        $all_positive = true;
        $has_any_reviewed = false;
        $has_any_pending = (empty($case_tasks));
        
        $remark_sr = 1;
        foreach ($case_tasks as $task) {
            $t_name = strtoupper(trim($task['task_name']));
            $task_counts[$t_name] = ($task_counts[$t_name] ?? 0) + 1;
            
            $t_data = json_decode($task['task_data'] ?? '{}', true);
            $t_review_status = strtoupper($t_data['review_status'] ?? '');

            if (!empty($t_review_status)) {
                $has_any_reviewed = true;
                if ($t_review_status == 'NEGATIVE') {
                    $has_negative = true;
                }
                if ($t_review_status != 'POSITIVE') {
                    $all_positive = false;
                }
            } else {
                $has_any_pending = true;
                $all_positive = false;
            }

            if (!empty($t_data['review_remarks'])) {
                $remarks_list[] = $remark_sr . ". " . $t_name . " - " . $t_data['review_remarks'];
                $remark_sr++;
            } else {
                // Show ONLY initial task data entered during initiation (e.g. Name, PAN for ITR)
                $task_summary = [];
                $ignore_keys = [
                    'review_status', 'review_remarks', 'extra_checks', 'action', 'task_id', 
                    'case_id', 'client_id', 'status', 'id', 'created_at', 'updated_at',
                    'step', 'ajax', 'assigned_to', 'verified_at', 'reviewed_at', 'process_status',
                    'verifier_remarks', 'verification_status', 'verified_by'
                ];
                foreach ($t_data as $k => $v) {
                    if (!in_array($k, $ignore_keys) && !empty($v) && !is_array($v)) {
                        $label = ucwords(str_replace(['_', '-'], ' ', $k));
                        $task_summary[] = "$label: $v";
                    }
                }
                if (!empty($task_summary)) {
                    $remarks_list[] = $remark_sr . ". " . $t_name . " - " . implode(' | ', $task_summary);
                    $remark_sr++;
                }
            }
            
            if ($task['agency_name']) $agencies[] = $task['agency_name'];
            if ($task['task_created_at']) $rcu_send_dates[] = strtotime($task['task_created_at']);
            
            if ($task['task_status'] != 'REVIEWED' && $task['task_status'] != 'COMPLETED') {
                $is_fully_completed = false;
            }
            
            if ($task['reviewed_at']) {
                $t_rev_time = strtotime($task['reviewed_at']);
                if (!$latest_reviewed_at || $t_rev_time > $latest_reviewed_at) {
                    $latest_reviewed_at = $t_rev_time;
                }
            }

            if (!empty($t_data['extra_checks'])) {
                $extra_checks_list[] = $t_data['extra_checks'];
            }
        }
        
        $initiation_doc_str = "";
        foreach ($task_counts as $name => $count) {
            $initiation_doc_str .= $count . ". " . $name . " ";
        }
        $initiation_doc_str = trim($initiation_doc_str);

        // Map row data using fallbacks that prefer manually entered form data (as seen in screenshots)
        $user_name = $case_info['user_name'] ?? $row['creator_name'] ?: 'N/A';
        $app_id = $case_info['app_id'] ?? $case_info['application_id'] ?? $row['application_no'] ?: 'N/A';
        
        // Customer Name fallbacks
        $customer_name = $case_info['customer_name'] 
            ?? $case_info['applicant_name'] 
            ?? $case_info['name_of_applicant'] 
            ?? $row['client_name'] 
            ?? 'N/A';
            
        $location = $case_info['location'] ?? $case_info['city'] ?? 'N/A';
        $product = $case_info['product'] ?? $case_info['product_smebledi'] ?? 'N/A';

        // Agency Name priority: Form > Assigned Tasks > Client Unit
        $agency_name = $case_info['rcu_agency_name'] 
            ?? $case_info['rcu_agency'] 
            ?? (!empty($agencies) ? implode(', ', array_unique($agencies)) : ($case_info['unit_name'] ?? $row['client_name']));
        
        $login_month = $case_info['login_month'] ?? date('F', strtotime($row['case_received_date']));
        $received_date = $case_info['received_date'] ?? date('d-m-Y', strtotime($row['case_received_date']));

        $rcu_send = $case_info['rcu_send'] ?? (!empty($rcu_send_dates) ? date('d-m-Y', min($rcu_send_dates)) : 'N/A');

        // Improved TAT: Show hours/days even if pending
        $d1 = new DateTime($row['case_received_date']);
        if ($is_fully_completed && $latest_reviewed_at) {
            $d2 = new DateTime(date('Y-m-d H:i:s', $latest_reviewed_at));
            $is_pending_tat = false;
        } else {
            $d2 = new DateTime(); // Current time
            $is_pending_tat = true;
        }
        
        $diff = $d1->diff($d2);
        $tat_days = $diff->days;
        $tat_hours = $diff->h;
        
        if ($tat_days > 0) {
            $tat = $tat_days . 'd ' . $tat_hours . 'h';
        } else {
            $tat = $tat_hours . 'h ' . $diff->i . 'm';
        }
        
        if ($is_pending_tat) {
            $tat = "(Pending) " . $tat;
        }
        
        // Calculate Overall Report Status
        $pos_word = $row['positve_status'] ?: 'Positive';
        $neg_word = $row['negative_status'] ?: 'Negative';
        $cnv_word = $row['cnv_status'] ?: 'CNV';

        if ($has_any_pending) {
            $report_status = 'Pending';
        } elseif ($has_negative) {
            $report_status = $neg_word;
        } elseif ($all_positive) {
            $report_status = $pos_word;
        } else {
            $report_status = $cnv_word;
        }

        $report_comments = implode("\n\n", $remarks_list);
        
        // Loan Amount fallbacks
        $loan_amount = $case_info['loan_amount_applied'] 
            ?? $case_info['loan_amount'] 
            ?? $case_info['loan_amt'] 
            ?? $case_info['amount'] 
            ?? $case_info['applied_amount'] 
            ?? 'N/A';
            
        $extra_checks = $case_info['extra_checks'] ?? implode(", ", array_unique($extra_checks_list));
        if (empty($extra_checks)) $extra_checks = 'N/A';

        $sheet->setCellValue('A' . $row_index, $sr++);
        $sheet->setCellValue('B' . $row_index, $user_name);
        $sheet->setCellValue('C' . $row_index, $app_id);
        $sheet->setCellValue('D' . $row_index, $customer_name);
        $sheet->setCellValue('E' . $row_index, $location);
        $sheet->setCellValue('F' . $row_index, $product);
        $sheet->setCellValue('G' . $row_index, $initiation_doc_str);
        $sheet->setCellValue('H' . $row_index, $agency_name);
        $sheet->setCellValue('I' . $row_index, $login_month);
        $sheet->setCellValue('J' . $row_index, $received_date);
        $sheet->setCellValue('K' . $row_index, $rcu_send);
        $sheet->setCellValue('L' . $row_index, $tat);
        $sheet->setCellValue('M' . $row_index, $report_status);
        $sheet->setCellValue('N' . $row_index, $report_comments);
        $sheet->setCellValue('O' . $row_index, $loan_amount);
        $sheet->setCellValue('P' . $row_index, $extra_checks);

        // Enable text wrapping for comments
        $sheet->getStyle('N' . $row_index)->getAlignment()->setWrapText(true);

        $row_index++;
    }

    // Output
    if (ob_get_length()) ob_end_clean();    // Generate filename - use App ID if it's a single case
    $file_prefix = 'MIS_Report';
    if ($f_case_id > 0 && !empty($cases_data)) {
        $first_case = $cases_data[0];
        $case_info_obj = json_decode($first_case['case_info'] ?? '{}', true);
        $found_app_id = $case_info_obj['app_id'] ?? $case_info_obj['application_id'] ?? $first_case['application_no'] ?? 'Case_'.$f_case_id;
        $file_prefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $found_app_id) . '_MIS';
    }
    
    $file_name = $file_prefix . '_' . date('d-m-Y') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    echo "Error generating excel: " . $e->getMessage();
}
?>
