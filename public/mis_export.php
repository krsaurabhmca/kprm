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

    // Default date range if not provided
    $date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($con, $_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
    $date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($con, $_GET['date_to']) : date('Y-m-d');

    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        ob_end_clean();
        die("PhpSpreadsheet library not found. Please run 'composer install'.");
    }

    // Build SQL query for cases
    $sql_cases = "
        SELECT 
            c.id as case_id,
            c.application_no,
            c.created_at as case_received_date,
            c.case_info,
            c.case_status,
            cl.name as client_name,
            u.user_name as creator_name
        FROM cases c
        LEFT JOIN clients cl ON c.client_id = cl.id
        LEFT JOIN op_user u ON c.created_by = u.id
        WHERE c.status != 'DELETED'
        AND DATE(c.created_at) BETWEEN '$date_from' AND '$date_to'
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
        $sheet->getColumnDimension($col)->setAutoSize(true);
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
        
        $remark_sr = 1;
        foreach ($case_tasks as $task) {
            $t_name = strtoupper(trim($task['task_name']));
            $task_counts[$t_name] = ($task_counts[$t_name] ?? 0) + 1;
            
            $t_data = json_decode($task['task_data'] ?? '{}', true);
            if (!empty($t_data['review_remarks'])) {
                $remarks_list[] = $remark_sr . ". " . $t_name . " - " . $t_data['review_remarks'];
                $remark_sr++;
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

        // Map data
        $user_name = $row['creator_name'] ?: 'N/A';
        $app_id = $row['application_no'] ?: 'N/A';
        $customer_name = $case_info['applicant_name'] ?? $row['client_name'] ?? 'N/A';
        $location = $case_info['location'] ?? $case_info['city'] ?? 'N/A';
        $product = $case_info['product_smebledi'] ?? $case_info['product'] ?? 'N/A';
        $agency_name = !empty($agencies) ? implode(', ', array_unique($agencies)) : 'Not Assigned';
        $login_month = date('F', strtotime($row['case_received_date']));
        $received_date = date('d-m-Y', strtotime($row['case_received_date']));
        $rcu_send = !empty($rcu_send_dates) ? date('d-m-Y', min($rcu_send_dates)) : 'N/A';
        
        // TAT
        $tat = 'Pending';
        if ($is_fully_completed && $latest_reviewed_at) {
            $d1 = new DateTime($row['case_received_date']);
            $d2 = new DateTime(date('Y-m-d H:i:s', $latest_reviewed_at));
            $diff = $d1->diff($d2);
            $tat = $diff->days . " Days";
        }
        
        $report_status = $row['case_status'];
        $report_comments = implode("\n\n", $remarks_list);
        $loan_amount = $case_info['loan_amount'] ?? 'N/A';
        $extra_checks = implode(", ", array_unique($extra_checks_list));

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
    $filename = "KPRM_MIS_Report_" . date('Y-m-d_His') . ".xlsx";
    
    if (ob_get_length()) ob_end_clean(); // Clear any existing output
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    echo "Error generating excel: " . $e->getMessage();
}
?>
