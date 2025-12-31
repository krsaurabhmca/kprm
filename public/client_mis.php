<?php
/**
 * KPRM - Client MIS Report
 * Management Information System - Cases with Task Details
 * Export to Excel functionality
 */

require_once('../system/all_header.php');

global $con;

// Get selected client
$selected_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

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

// Get MIS data (cases with their tasks)
$mis_data = [];
$client_name = '';

if ($selected_client_id > 0) {
    // Get client name
    $client_sql = "SELECT name FROM clients WHERE id = '$selected_client_id' AND status = 'ACTIVE'";
    $client_res = mysqli_query($con, $client_sql);
    if ($client_res && $client_row = mysqli_fetch_assoc($client_res)) {
        $client_name = $client_row['name'];
    }
    
    // Check if case_tasks table exists
    $table_check = mysqli_query($con, "SHOW TABLES LIKE 'case_tasks'");
    $has_case_tasks = ($table_check && mysqli_num_rows($table_check) > 0);
    
    if ($has_case_tasks) {
        // Get all cases for this client with their tasks
        // One row per case+task combination (denormalized for MIS)
        $mis_sql = "
            SELECT 
                c.id as case_id,
                c.application_no,
                c.created_at as case_created_at,
                c.case_status,
                cl.name as client_name,
                ct.id as task_id,
                ct.task_name,
                ct.task_status,
                ct.assigned_to,
                ct.created_at as task_created_at,
                ct.verified_at,
                ct.reviewed_at,
                t.task_name as template_task_name,
                v.verifier_name,
                ct.task_data
            FROM cases c
            INNER JOIN clients cl ON c.client_id = cl.id
            LEFT JOIN case_tasks ct ON c.id = ct.case_id AND ct.status = 'ACTIVE'
            LEFT JOIN tasks t ON ct.task_template_id = t.id
            LEFT JOIN verifier v ON ct.assigned_to = v.id
            WHERE c.client_id = '$selected_client_id' 
            AND c.status != 'DELETED'
            ORDER BY c.id ASC, ct.id ASC
        ";
        $mis_res = mysqli_query($con, $mis_sql);
        if ($mis_res) {
            while ($row = mysqli_fetch_assoc($mis_res)) {
                // Parse task_data JSON if exists
                $task_data_json = [];
                if (!empty($row['task_data'])) {
                    $task_data_json = json_decode($row['task_data'], true);
                    if (!is_array($task_data_json)) {
                        $task_data_json = [];
                    }
                }
                
                $mis_data[] = [
                    'case_id' => $row['case_id'],
                    'application_no' => $row['application_no'] ?? 'N/A',
                    'case_created_at' => $row['case_created_at'] ?? '',
                    'case_status' => $row['case_status'] ?? 'ACTIVE',
                    'client_name' => $row['client_name'] ?? 'N/A',
                    'task_id' => $row['task_id'] ?? null,
                    'task_name' => $row['template_task_name'] ?? $row['task_name'] ?? 'No Task',
                    'task_status' => $row['task_status'] ?? 'PENDING',
                    'assigned_to' => $row['verifier_name'] ?? '',
                    'task_created_at' => $row['task_created_at'] ?? '',
                    'verified_at' => $row['verified_at'] ?? '',
                    'reviewed_at' => $row['reviewed_at'] ?? '',
                    'review_status' => $task_data_json['review_status'] ?? '',
                    'review_remarks' => isset($task_data_json['review_remarks']) ? substr(strip_tags($task_data_json['review_remarks']), 0, 100) : '',
                    'verifier_remarks' => isset($task_data_json['verifier_remarks']) ? substr(strip_tags($task_data_json['verifier_remarks']), 0, 100) : ''
                ];
            }
        }
    } else {
        // If case_tasks table doesn't exist, show only cases
        $cases_sql = "
            SELECT 
                c.id as case_id,
                c.application_no,
                c.created_at as case_created_at,
                c.case_status,
                cl.name as client_name
            FROM cases c
            INNER JOIN clients cl ON c.client_id = cl.id
            WHERE c.client_id = '$selected_client_id' 
            AND c.status != 'DELETED'
            ORDER BY c.id ASC
        ";
        $cases_res = mysqli_query($con, $cases_sql);
        if ($cases_res) {
            while ($row = mysqli_fetch_assoc($cases_res)) {
                $mis_data[] = [
                    'case_id' => $row['case_id'],
                    'application_no' => $row['application_no'] ?? 'N/A',
                    'case_created_at' => $row['case_created_at'] ?? '',
                    'case_status' => $row['case_status'] ?? 'ACTIVE',
                    'client_name' => $row['client_name'] ?? 'N/A',
                    'task_id' => null,
                    'task_name' => 'N/A',
                    'task_status' => 'N/A',
                    'assigned_to' => '',
                    'task_created_at' => '',
                    'verified_at' => '',
                    'reviewed_at' => '',
                    'review_status' => '',
                    'review_remarks' => '',
                    'verifier_remarks' => ''
                ];
            }
        }
    }
}

// Handle Excel export
if ($export_excel && $selected_client_id > 0 && !empty($mis_data)) {
    require_once('../system/vendor/autoload.php');
    
    try {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet title
        $sheet->setTitle('Client MIS');
        
        // Define headers
        $headers = [
            'A1' => 'Case ID',
            'B1' => 'Application No',
            'C1' => 'Client Name',
            'D1' => 'Case Status',
            'E1' => 'Case Created Date',
            'F1' => 'Task ID',
            'G1' => 'Task Name',
            'H1' => 'Task Status',
            'I1' => 'Assigned To',
            'J1' => 'Task Created Date',
            'K1' => 'Verified Date',
            'L1' => 'Reviewed Date',
            'M1' => 'Review Status',
            'N1' => 'Review Remarks',
            'O1' => 'Verifier Remarks'
        ];
        
        // Set headers
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);
        
        // Add data rows
        $row = 2;
        foreach ($mis_data as $data) {
            $sheet->setCellValue('A' . $row, $data['case_id']);
            $sheet->setCellValue('B' . $row, $data['application_no']);
            $sheet->setCellValue('C' . $row, $data['client_name']);
            $sheet->setCellValue('D' . $row, $data['case_status']);
            $sheet->setCellValue('E' . $row, $data['case_created_at'] ? date('d/m/Y', strtotime($data['case_created_at'])) : '');
            $sheet->setCellValue('F' . $row, $data['task_id'] ?? '');
            $sheet->setCellValue('G' . $row, $data['task_name']);
            $sheet->setCellValue('H' . $row, $data['task_status']);
            $sheet->setCellValue('I' . $row, $data['assigned_to']);
            $sheet->setCellValue('J' . $row, $data['task_created_at'] ? date('d/m/Y', strtotime($data['task_created_at'])) : '');
            $sheet->setCellValue('K' . $row, $data['verified_at'] ? date('d/m/Y', strtotime($data['verified_at'])) : '');
            $sheet->setCellValue('L' . $row, $data['reviewed_at'] ? date('d/m/Y', strtotime($data['reviewed_at'])) : '');
            $sheet->setCellValue('M' . $row, $data['review_status']);
            $sheet->setCellValue('N' . $row, $data['review_remarks']);
            $sheet->setCellValue('O' . $row, $data['verifier_remarks']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set freeze pane on first row
        $sheet->freezePane('A2');
        
        // Generate filename
        $filename = 'MIS_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $client_name) . '_' . date('Y-m-d') . '.xlsx';
        
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
                <h4 class="mb-0">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    <strong>Client MIS Report</strong>
                </h4>
                <small class="text-muted">Management Information System - Cases with Task Details</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <select name="client_id" id="clientSelect" class="form-select form-select-sm" style="min-width: 200px;" onchange="this.form.submit()">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo $selected_client_id == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                   
                </form>
            </div>
        </div>

        <?php if ($selected_client_id > 0): ?>
            <!-- Info Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0">
                                <i class="fas fa-building text-primary me-2"></i>
                                <strong>Client:</strong> <?php echo htmlspecialchars($client_name); ?>
                            </h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info">
                                <i class="fas fa-list me-1"></i>
                                Total Records: <?php echo count($mis_data); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MIS Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-light py-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-table text-primary me-2"></i>MIS Data
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mis_data)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No data found for this client</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm mb-0" id="misTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Case ID</th>
                                        <th>Application No</th>
                                        <th>Client Name</th>
                                        <th>Case Status</th>
                                        <th>Case Created</th>
                                        <th>Task ID</th>
                                        <th>Task Name</th>
                                        <th>Task Status</th>
                                        <th>Assigned To</th>
                                        <th>Task Created</th>
                                        <th>Verified Date</th>
                                        <th>Reviewed Date</th>
                                        <th>Review Status</th>
                                        <th>Review Remarks</th>
                                        <th>Verifier Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mis_data as $data): ?>
                                        <tr>
                                            <td><?php echo $data['case_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($data['application_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($data['client_name']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                if ($data['case_status'] == 'COMPLETED') $status_class = 'success';
                                                elseif ($data['case_status'] == 'IN_PROGRESS') $status_class = 'info';
                                                elseif ($data['case_status'] == 'PENDING') $status_class = 'warning';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo htmlspecialchars($data['case_status']); ?></span>
                                            </td>
                                            <td><small><?php echo $data['case_created_at'] ? date('d M Y', strtotime($data['case_created_at'])) : ''; ?></small></td>
                                            <td><?php echo $data['task_id'] ?? '-'; ?></td>
                                            <td><?php echo htmlspecialchars($data['task_name']); ?></td>
                                            <td>
                                                <?php if ($data['task_status'] != 'N/A'): ?>
                                                    <?php
                                                    $task_status_class = 'warning';
                                                    if ($data['task_status'] == 'COMPLETED') $task_status_class = 'success';
                                                    elseif ($data['task_status'] == 'VERIFICATION_COMPLETED') $task_status_class = 'primary';
                                                    elseif ($data['task_status'] == 'IN_PROGRESS') $task_status_class = 'info';
                                                    ?>
                                                    <span class="badge bg-<?php echo $task_status_class; ?>"><?php echo htmlspecialchars($data['task_status']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?php echo htmlspecialchars($data['assigned_to'] ?: '-'); ?></small></td>
                                            <td><small><?php echo $data['task_created_at'] ? date('d M Y', strtotime($data['task_created_at'])) : '-'; ?></small></td>
                                            <td><small><?php echo $data['verified_at'] ? date('d M Y', strtotime($data['verified_at'])) : '-'; ?></small></td>
                                            <td><small><?php echo $data['reviewed_at'] ? date('d M Y', strtotime($data['reviewed_at'])) : '-'; ?></small></td>
                                            <td><small><?php echo htmlspecialchars($data['review_status'] ?: '-'); ?></small></td>
                                            <td><small title="<?php echo htmlspecialchars($data['review_remarks']); ?>"><?php echo htmlspecialchars($data['review_remarks'] ?: '-'); ?></small></td>
                                            <td><small title="<?php echo htmlspecialchars($data['verifier_remarks']); ?>"><?php echo htmlspecialchars($data['verifier_remarks'] ?: '-'); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- No Client Selected -->
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Please select a client to view MIS report</h5>
                    <p class="text-muted mb-0">Use the dropdown above to select a client</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once('../system/footer.php'); ?>

<script>
// Initialize DataTable if available
$(document).ready(function() {
    if ($.fn.DataTable && $('#misTable').length) {
        $('#misTable').DataTable({
            pageLength: 50,
            order: [[0, 'asc']],
            scrollX: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Export Excel',
                    className: 'btn btn-sm btn-success'
                }
            ]
        });
    }
});
</script>

