<?php
/**
 * KPRM - Client Case Dashboard
 * Professional Bank/NBFC Verification MIS Dashboard
 * Shows comprehensive case analytics for selected client
 */

require_once('../system/all_header.php');

global $con;

// Get selected client and date filter
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Check if export is requested
$export_excel = isset($_GET['export']) && $_GET['export'] == 'excel';

// Get all active clients
$clients = [];
$clients_sql = "SELECT id, name FROM clients WHERE status = 'ACTIVE' ORDER BY name ASC";
$clients_res = mysqli_query($con, $clients_sql);
if ($clients_res) {
    while ($row = mysqli_fetch_assoc($clients_res)) {
        $clients[] = $row;
    }
}

// Get client name
$client_name = '';
if ($selected_client_id > 0) {
    $client_sql = "SELECT name FROM clients WHERE id = '$selected_client_id' AND status = 'ACTIVE'";
    $client_res = mysqli_query($con, $client_sql);
    if ($client_res && $client_row = mysqli_fetch_assoc($client_res)) {
        $client_name = $client_row['name'];
    }
}

// Check if case_tasks table exists
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
$has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);

// Initialize data arrays
$monthly_summary = [];
$daily_report = [];
$aging_report = [];
$verification_summary = [];
$pendency_by_type = [];

if ($selected_client_id > 0) {
    $month_start = date('Y-m-01', mktime(0, 0, 0, $selected_month, 1, $selected_year));
    $month_end = date('Y-m-t', mktime(0, 0, 0, $selected_month, 1, $selected_year));
    
    // ============================================
    // 1. CLIENT CASE SUMMARY (Monthly View)
    // ============================================
    $summary_sql = "
        SELECT 
            DATE(c.created_at) as case_date,
            COUNT(DISTINCT c.id) as total_received,
            SUM(CASE WHEN c.case_status IN ('COMPLETED', 'CLOSED') 
                AND DATE(COALESCE(c.updated_at, c.created_at)) = DATE(c.created_at) THEN 1 ELSE 0 END) as closed_same_day,
            SUM(CASE WHEN c.case_status NOT IN ('COMPLETED', 'CLOSED') THEN 1 ELSE 0 END) as total_pending
        FROM cases c
        WHERE c.client_id = '$selected_client_id'
        AND c.status != 'DELETED'
        AND DATE(c.created_at) BETWEEN '$month_start' AND '$month_end'
        GROUP BY DATE(c.created_at)
        ORDER BY case_date ASC
    ";
    $summary_res = mysqli_query($con, $summary_sql);
    if ($summary_res) {
        while ($row = mysqli_fetch_assoc($summary_res)) {
            $monthly_summary[] = $row;
        }
    }
    
    // ============================================
    // 2. DAILY CASE REPORT
    // ============================================
    // Get all dates in the month
    $all_dates = [];
    $current_date = $month_start;
    while ($current_date <= $month_end) {
        $all_dates[] = $current_date;
        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
    }
    
    // Get initial pendency (cases created before month start that are still pending)
    $initial_pendency_sql = "
        SELECT COUNT(*) as count
        FROM cases c
        WHERE c.client_id = '$selected_client_id'
        AND c.status != 'DELETED'
        AND c.case_status NOT IN ('COMPLETED', 'CLOSED')
        AND DATE(c.created_at) < '$month_start'
    ";
    $initial_pendency_res = mysqli_query($con, $initial_pendency_sql);
    $prev_pendency = 0;
    if ($initial_pendency_res && $initial_row = mysqli_fetch_assoc($initial_pendency_res)) {
        $prev_pendency = intval($initial_row['count']);
    }
    
    // Process each date
    foreach ($all_dates as $report_date) {
        // Get cases received on this date
        $received_sql = "
            SELECT COUNT(DISTINCT c.id) as total_received
            FROM cases c
            WHERE c.client_id = '$selected_client_id'
            AND c.status != 'DELETED'
            AND DATE(c.created_at) = '$report_date'
        ";
        $received_res = mysqli_query($con, $received_sql);
        $total_received = 0;
        if ($received_res && $received_row = mysqli_fetch_assoc($received_res)) {
            $total_received = intval($received_row['total_received']);
        }
        
        // Get cases closed on this date (created on this date and closed on same date)
        $closed_sql = "
            SELECT COUNT(DISTINCT c.id) as closed_same_day
            FROM cases c
            WHERE c.client_id = '$selected_client_id'
            AND c.status != 'DELETED'
            AND DATE(c.created_at) = '$report_date'
            AND c.case_status IN ('COMPLETED', 'CLOSED')
            AND DATE(COALESCE(c.updated_at, c.created_at)) = '$report_date'
        ";
        $closed_res = mysqli_query($con, $closed_sql);
        $closed_same_day = 0;
        if ($closed_res && $closed_row = mysqli_fetch_assoc($closed_res)) {
            $closed_same_day = intval($closed_row['closed_same_day']);
        }
        
        // Pendency of the day = cases created today that are still pending
        $pendency_of_day = $total_received - $closed_same_day;
        
        // Total pendency = Previous day pendency + New received - Closed
        $total_pendency = $prev_pendency + $total_received - $closed_same_day;
        
        $daily_report[] = [
            'date' => $report_date,
            'total_received' => $total_received,
            'closed_same_day' => $closed_same_day,
            'pendency_of_day' => $pendency_of_day,
            'previous_day_pendency' => $prev_pendency,
            'total_pendency' => $total_pendency
        ];
        
        // Update previous day pendency for next iteration
        $prev_pendency = $total_pendency;
    }
    
    // ============================================
    // 3. CASE AGING REPORT
    // ============================================
    $aging_sql = "
        SELECT 
            c.id,
            c.created_at,
            c.case_status,
            DATEDIFF(CURDATE(), DATE(c.created_at)) as days_old
        FROM cases c
        WHERE c.client_id = '$selected_client_id'
        AND c.status != 'DELETED'
        AND c.case_status NOT IN ('COMPLETED', 'CLOSED')
    ";
    $aging_res = mysqli_query($con, $aging_sql);
    $aging_data = [];
    if ($aging_res) {
        while ($row = mysqli_fetch_assoc($aging_res)) {
            $days = intval($row['days_old']);
            if ($days == 0) {
                $aging_data['0_day'][] = $row['id'];
            } elseif ($days <= 1) {
                $aging_data['0_1_day'][] = $row['id'];
            } elseif ($days <= 2) {
                $aging_data['0_2_days'][] = $row['id'];
            } elseif ($days <= 3) {
                $aging_data['0_3_days'][] = $row['id'];
            } else {
                $aging_data['more_3_days'][] = $row['id'];
            }
        }
    }
    
    $total_pending = count($aging_data['0_day'] ?? []) + 
                     count($aging_data['0_1_day'] ?? []) + 
                     count($aging_data['0_2_days'] ?? []) + 
                     count($aging_data['0_3_days'] ?? []) + 
                     count($aging_data['more_3_days'] ?? []);
    
    $aging_report = [
        ['category' => '0 Day', 'count' => count($aging_data['0_day'] ?? []), 'percentage' => $total_pending > 0 ? round((count($aging_data['0_day'] ?? []) / $total_pending) * 100, 2) : 0],
        ['category' => '0-1 Day', 'count' => count($aging_data['0_1_day'] ?? []), 'percentage' => $total_pending > 0 ? round((count($aging_data['0_1_day'] ?? []) / $total_pending) * 100, 2) : 0],
        ['category' => '0-2 Days', 'count' => count($aging_data['0_2_days'] ?? []), 'percentage' => $total_pending > 0 ? round((count($aging_data['0_2_days'] ?? []) / $total_pending) * 100, 2) : 0],
        ['category' => '0-3 Days', 'count' => count($aging_data['0_3_days'] ?? []), 'percentage' => $total_pending > 0 ? round((count($aging_data['0_3_days'] ?? []) / $total_pending) * 100, 2) : 0],
        ['category' => 'More than 3 Days', 'count' => count($aging_data['more_3_days'] ?? []), 'percentage' => $total_pending > 0 ? round((count($aging_data['more_3_days'] ?? []) / $total_pending) * 100, 2) : 0]
    ];
    
    // ============================================
    // 4. TYPE OF VERIFICATION SUMMARY
    // ============================================
    if ($has_case_tasks) {
        $verification_sql = "
            SELECT 
                DATE(c.created_at) as report_date,
                SUM(CASE WHEN t.task_type = 'PHYSICAL' OR ct.task_type = 'PHYSICAL' THEN 1 ELSE 0 END) as pv_count,
                SUM(CASE WHEN t.task_type IN ('ITO', 'BANKING', 'DOCUMENT') 
                    OR ct.task_type IN ('ITO', 'BANKING', 'DOCUMENT') THEN 1 ELSE 0 END) as dv_count
            FROM cases c
            LEFT JOIN case_tasks ct ON c.id = ct.case_id AND ct.status = 'ACTIVE'
            LEFT JOIN tasks t ON ct.task_template_id = t.id
            WHERE c.client_id = '$selected_client_id'
            AND c.status != 'DELETED'
            AND DATE(c.created_at) BETWEEN '$month_start' AND '$month_end'
            GROUP BY DATE(c.created_at)
            ORDER BY report_date ASC
        ";
        $verification_res = mysqli_query($con, $verification_sql);
        if ($verification_res) {
            while ($row = mysqli_fetch_assoc($verification_res)) {
                $verification_summary[] = [
                    'date' => $row['report_date'],
                    'pv' => intval($row['pv_count']),
                    'dv' => intval($row['dv_count']),
                    'total' => intval($row['pv_count']) + intval($row['dv_count'])
                ];
            }
        }
    }
    
    // ============================================
    // 5. PENDENCY COUNT BY VERIFICATION TYPE
    // ============================================
    if ($has_case_tasks) {
        $pendency_sql = "
            SELECT 
                COALESCE(t.task_type, ct.task_type, 'UNKNOWN') as verification_type,
                COUNT(DISTINCT c.id) as pendency_count
            FROM cases c
            LEFT JOIN case_tasks ct ON c.id = ct.case_id AND ct.status = 'ACTIVE'
            LEFT JOIN tasks t ON ct.task_template_id = t.id
            WHERE c.client_id = '$selected_client_id'
            AND c.status != 'DELETED'
            AND c.case_status NOT IN ('COMPLETED', 'CLOSED')
            AND (ct.task_status IS NULL OR ct.task_status NOT IN ('COMPLETED'))
            GROUP BY COALESCE(t.task_type, ct.task_type, 'UNKNOWN')
            ORDER BY pendency_count DESC
        ";
        $pendency_res = mysqli_query($con, $pendency_sql);
        if ($pendency_res) {
            while ($row = mysqli_fetch_assoc($pendency_res)) {
                $pendency_by_type[] = [
                    'type' => $row['verification_type'] ?: 'UNKNOWN',
                    'count' => intval($row['pendency_count'])
                ];
            }
        }
    }
}

// Handle Excel export
if ($export_excel && $selected_client_id > 0) {
    require_once('../system/vendor/autoload.php');
    
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        
        // ========== Sheet 1: Monthly Summary ==========
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Monthly Summary');
        
        // Header
        $sheet1->setCellValue('A1', 'Client Case Dashboard - ' . $client_name);
        $sheet1->setCellValue('A2', 'Reporting Period: ' . date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)));
        $sheet1->mergeCells('A1:D1');
        $sheet1->mergeCells('A2:D2');
        
        // Monthly Summary Table
        $sheet1->setCellValue('A4', 'Date');
        $sheet1->setCellValue('B4', 'Total Cases Received');
        $sheet1->setCellValue('C4', 'Closed on Same Day');
        $sheet1->setCellValue('D4', 'Total Pending Cases');
        
        $row = 5;
        foreach ($monthly_summary as $data) {
            $sheet1->setCellValue('A' . $row, date('d/m/Y', strtotime($data['case_date'])));
            $sheet1->setCellValue('B' . $row, $data['total_received']);
            $sheet1->setCellValue('C' . $row, $data['closed_same_day']);
            $sheet1->setCellValue('D' . $row, $data['total_pending']);
            $row++;
        }
        
        // ========== Sheet 2: Daily Report ==========
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Daily Report');
        
        $sheet2->setCellValue('A1', 'Date');
        $sheet2->setCellValue('B1', 'Total Cases Received');
        $sheet2->setCellValue('C1', 'Closed on Same Day');
        $sheet2->setCellValue('D1', 'Pendency of the Day');
        $sheet2->setCellValue('E1', 'Previous Day Pendency');
        $sheet2->setCellValue('F1', 'Total Pendency');
        
        $row = 2;
        foreach ($daily_report as $data) {
            $sheet2->setCellValue('A' . $row, date('d/m/Y', strtotime($data['date'])));
            $sheet2->setCellValue('B' . $row, $data['total_received']);
            $sheet2->setCellValue('C' . $row, $data['closed_same_day']);
            $sheet2->setCellValue('D' . $row, $data['pendency_of_day']);
            $sheet2->setCellValue('E' . $row, $data['previous_day_pendency']);
            $sheet2->setCellValue('F' . $row, $data['total_pendency']);
            $row++;
        }
        
        // ========== Sheet 3: Aging Report ==========
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Aging Report');
        
        $sheet3->setCellValue('A1', 'Aging Category');
        $sheet3->setCellValue('B1', 'Count of Cases');
        $sheet3->setCellValue('C1', 'Percentage (%)');
        
        $row = 2;
        foreach ($aging_report as $data) {
            $sheet3->setCellValue('A' . $row, $data['category']);
            $sheet3->setCellValue('B' . $row, $data['count']);
            $sheet3->setCellValue('C' . $row, $data['percentage']);
            $row++;
        }
        
        // ========== Sheet 4: Verification Summary ==========
        if (!empty($verification_summary)) {
            $sheet4 = $spreadsheet->createSheet();
            $sheet4->setTitle('Verification Summary');
            
            $sheet4->setCellValue('A1', 'Date');
            $sheet4->setCellValue('B1', 'PV (Physical Verification)');
            $sheet4->setCellValue('C1', 'DV (Document/Banking/ITO)');
            $sheet4->setCellValue('D1', 'Total');
            
            $row = 2;
            foreach ($verification_summary as $data) {
                $sheet4->setCellValue('A' . $row, date('d/m/Y', strtotime($data['date'])));
                $sheet4->setCellValue('B' . $row, $data['pv']);
                $sheet4->setCellValue('C' . $row, $data['dv']);
                $sheet4->setCellValue('D' . $row, $data['total']);
                $row++;
            }
        }
        
        // ========== Sheet 5: Pendency by Type ==========
        if (!empty($pendency_by_type)) {
            $sheet5 = $spreadsheet->createSheet();
            $sheet5->setTitle('Pendency by Type');
            
            $sheet5->setCellValue('A1', 'Verification Type');
            $sheet5->setCellValue('B1', 'Pendency Count');
            
            $row = 2;
            foreach ($pendency_by_type as $data) {
                $sheet5->setCellValue('A' . $row, $data['type']);
                $sheet5->setCellValue('B' . $row, $data['count']);
                $row++;
            }
        }
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->getStyle('A1:Z1')->applyFromArray($headerStyle);
            foreach (range('A', 'Z') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }
        
        // Set active sheet
        $spreadsheet->setActiveSheetIndex(0);
        
        // Generate filename
        $filename = 'Client_Dashboard_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $client_name) . '_' . date('F_Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)) . '.xlsx';
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        die('Error exporting to Excel: ' . htmlspecialchars($e->getMessage()));
    }
}
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">
                    <strong><?php echo $selected_client_id > 0 ? 'Client ' . htmlspecialchars($client_name) . ' Dashboard' : 'Client Case Dashboard'; ?></strong>
                </h2>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Client Selection -->
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                    <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                    <select name="client_id" class="form-select form-select-sm" style="min-width: 250px;" onchange="this.form.submit()">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <!-- Month/Year Selection -->
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="client_id" value="<?php echo $selected_client_id; ?>">
                    <select name="month" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $selected_month == $m ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>
                <?php if ($selected_client_id > 0): ?>
                    <a href="?client_id=<?php echo $selected_client_id; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>&export=excel" class="btn btn-sm btn-success">
                        <i class="fas fa-file-excel me-1"></i>Export to Excel
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selected_client_id > 0): ?>
            <?php 
            $reporting_period = date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year));
            ?>

            <!-- Dashboard Grid Layout -->
            <div class="row g-3">
                <!-- ============================================ -->
                <!-- 1. CLIENT CASE SUMMARY (Monthly View) - Top Left -->
                <!-- ============================================ -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header text-white" style="background-color: #4472C4;">
                            <h6 class="mb-0 fw-bold">Client Case Summary - <?php echo $reporting_period; ?></h6>
                            <small class="opacity-75">Client Name: <?php echo htmlspecialchars($client_name); ?></small>
                        </div>
                        <div class="card-body p-2">
                            <p class="small text-muted mb-2 px-2">
                                <strong>What this shows:</strong> Overall case inflow and pending status at a glance. Track how many cases were received each day, how many were closed the same day, and current pending cases.
                            </p>
                            <?php if (empty($monthly_summary)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No data available for the selected period</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                                        <thead style="background-color: #D9E1F2;">
                                            <tr>
                                                <th class="text-center" style="font-weight: 600;">Date</th>
                                                <th class="text-center" style="font-weight: 600;">Total Cases Received</th>
                                                <th class="text-center" style="font-weight: 600;">Closed on Same Day</th>
                                                <th class="text-center" style="font-weight: 600;">Total Pending Cases</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($monthly_summary as $data): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo date('d-m-Y', strtotime($data['case_date'])); ?></td>
                                                    <td class="text-center"><?php echo $data['total_received']; ?></td>
                                                    <td class="text-center"><?php echo $data['closed_same_day']; ?></td>
                                                    <td class="text-center"><?php echo $data['total_pending']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- 2. DAILY CASE REPORT - Top Right -->
                <!-- ============================================ -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header text-white" style="background-color: #70AD47;">
                            <h6 class="mb-0 fw-bold">Daily Case Report - <?php echo $reporting_period; ?></h6>
                            <small class="opacity-75">Client Name: <?php echo htmlspecialchars($client_name); ?></small>
                        </div>
                        <div class="card-body p-2">
                            <p class="small text-muted mb-2 px-2">
                                <strong>What this shows:</strong> Daily operational performance tracking. Shows case flow, closures, and cumulative pendency. 
                                <strong>Formula:</strong> Previous Day Pendency + New Received – Closed = Total Pendency
                            </p>
                            <?php if (empty($daily_report)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No data available for the selected period</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                                        <thead style="background-color: #E2EFDA; position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th class="text-center" style="font-weight: 600; white-space: normal; word-wrap: break-word; max-width: 80px;">Date</th>
                                                <th class="text-center" style="font-weight: 600; white-space: normal; word-wrap: break-word; max-width: 100px;">Total Cases Received</th>
                                                <th class="text-center" style="font-weight: 600; white-space: normal; word-wrap: break-word; max-width: 100px;">Closed on Same Day</th>
                                                <th class="text-center" style="font-weight: 600; white-space: normal; word-wrap: break-word; max-width: 100px;">Pendency of the Day</th>
                                                <th class="text-center" style="font-weight: 600; white-space: normal; word-wrap: break-word; max-width: 120px;">Previous Day Pendency</th>
                                                <th class="text-center" style="font-weight: 600; white-space: normal; word-wrap: break-word; max-width: 100px;">Total Pendency</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($daily_report as $data): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo date('d-m-Y', strtotime($data['date'])); ?></td>
                                                    <td class="text-center"><?php echo $data['total_received']; ?></td>
                                                    <td class="text-center"><?php echo $data['closed_same_day']; ?></td>
                                                    <td class="text-center"><?php echo $data['pendency_of_day']; ?></td>
                                                    <td class="text-center"><?php echo $data['previous_day_pendency']; ?></td>
                                                    <td class="text-center"><?php echo $data['total_pendency']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- 3. CASE AGING REPORT - Bottom Left -->
                <!-- ============================================ -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header text-white" style="background-color: #FFC000;">
                            <h6 class="mb-0 fw-bold">Case Aging Report</h6>
                            <small class="opacity-75">Client Name: <?php echo htmlspecialchars($client_name); ?></small>
                        </div>
                        <div class="card-body p-2">
                            <p class="small text-muted mb-2 px-2">
                                <strong>What this shows:</strong> Identifies SLA risks and delayed cases. Shows how old pending cases are, helping management prioritize urgent cases and identify bottlenecks.
                            </p>
                            <?php if (empty($aging_report) || array_sum(array_column($aging_report, 'count')) == 0): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No pending cases - All cases are completed!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                                        <thead style="background-color: #FFF2CC;">
                                            <tr>
                                                <th class="text-center" style="font-weight: 600;">Aging Category</th>
                                                <th class="text-center" style="font-weight: 600;">Count of Cases</th>
                                                <th class="text-center" style="font-weight: 600;">Percentage (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($aging_report as $data): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo htmlspecialchars($data['category']); ?></td>
                                                    <td class="text-center"><?php echo $data['count']; ?></td>
                                                    <td class="text-center"><?php echo $data['percentage']; ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot style="background-color: #FFF2CC;">
                                            <tr>
                                                <td class="text-center" style="font-weight: 600;">Total</td>
                                                <td class="text-center" style="font-weight: 600;"><?php echo array_sum(array_column($aging_report, 'count')); ?></td>
                                                <td class="text-center" style="font-weight: 600;">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- 4. TYPE OF VERIFICATION SUMMARY - Bottom Middle -->
                <!-- ============================================ -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header text-white" style="background-color: #5B9BD5;">
                            <h6 class="mb-0 fw-bold">Type of Verification Summary</h6>
                            <small class="opacity-75">Client Name: <?php echo htmlspecialchars($client_name); ?></small>
                        </div>
                        <div class="card-body p-2">
                            <p class="small text-muted mb-2 px-2">
                                <strong>What this shows:</strong> Verification workload breakdown by type. 
                                <strong>PV</strong> = Physical Verification (Field visits), 
                                <strong>DV</strong> = Document/Banking/ITO Verification (Office-based verification).
                            </p>
                            <?php if (empty($verification_summary)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No verification data available for the selected period</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                                        <thead style="background-color: #DEEBF7; position: sticky; top: 0;">
                                            <tr>
                                                <th class="text-center" style="font-weight: 600;">Date</th>
                                                <th class="text-center" style="font-weight: 600;">PV *</th>
                                                <th class="text-center" style="font-weight: 600;">DV **</th>
                                                <th class="text-center" style="font-weight: 600;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($verification_summary as $data): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo date('d-m-Y', strtotime($data['date'])); ?></td>
                                                    <td class="text-center"><?php echo $data['pv']; ?></td>
                                                    <td class="text-center"><?php echo $data['dv']; ?></td>
                                                    <td class="text-center"><?php echo $data['total']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 px-2">
                                    <small class="text-muted">
                                        <strong>* PV:</strong> Physical Verification | <strong>** DV:</strong> Banking + ITO
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- 5. PENDENCY COUNT BY VERIFICATION TYPE - Bottom Right -->
                <!-- ============================================ -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header text-white" style="background-color: #808080;">
                            <h6 class="mb-0 fw-bold">Dashboard - Pendency Count by Verification Type</h6>
                            <small class="opacity-75">Client Name: <?php echo htmlspecialchars($client_name); ?></small>
                        </div>
                        <div class="card-body p-2">
                            <p class="small text-muted mb-2 px-2">
                                <strong>What this shows:</strong> Helps management identify bottlenecks. Shows where pending cases are stuck by verification type, enabling targeted resource allocation.
                            </p>
                            <?php if (empty($pendency_by_type)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No pending cases by verification type</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0" style="font-size: 0.85rem;">
                                        <thead style="background-color: #D9D9D9;">
                                            <tr>
                                                <th class="text-center" style="font-weight: 600;">Verification Type</th>
                                                <th class="text-center" style="font-weight: 600;">Pendency Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendency_by_type as $data): ?>
                                                <tr>
                                                    <td class="text-center"><?php echo htmlspecialchars($data['type']); ?></td>
                                                    <td class="text-center"><?php echo $data['count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot style="background-color: #D9D9D9;">
                                            <tr>
                                                <td class="text-center" style="font-weight: 600;">Total Pendency</td>
                                                <td class="text-center" style="font-weight: 600;"><?php echo array_sum(array_column($pendency_by_type, 'count')); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- No Client Selected -->
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Please select a client to view the dashboard</h5>
                    <p class="text-muted mb-0">Use the dropdown above to select a client and view comprehensive case analytics</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('../system/footer.php'); ?>

<style>
.card-header {
    font-weight: 600;
    padding: 0.75rem 1rem;
}
.card-header h6 {
    margin-bottom: 0.25rem;
}
.card-header small {
    display: block;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}
.card-body {
    padding: 0.5rem;
}
.table {
    margin-bottom: 0;
}
.table th {
    font-weight: 600;
    padding: 0.5rem;
    font-size: 0.85rem;
}
.table th.word-wrap {
    white-space: normal;
    word-wrap: break-word;
}
.table td {
    padding: 0.5rem;
    font-size: 0.85rem;
}
.table tfoot {
    font-weight: 600;
}
.table-responsive {
    border: none;
}
.card {
    border: 1px solid #dee2e6;
}
.card-header {
    border-bottom: 2px solid rgba(255,255,255,0.2);
}
</style>

