<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['daterange'])) {
    $range = explode(" - ", $_GET['daterange']);
    $startDate = DateTime::createFromFormat('m/d/Y', trim($range[0]));
    $endDate   = DateTime::createFromFormat('m/d/Y', trim($range[1]));
    $from_date = $startDate->format("Y-m-d");
    $to_date   = $endDate->format("Y-m-d");
} else {
    // Default to last 30 days if no range selected
    $from_date = date('Y-m-d', strtotime('-30 days'));
    $to_date = date('Y-m-d');
}

$task_ito = direct_sql("
    SELECT * 
    FROM task_ito 
    WHERE status = 'VERIFIED'
    AND DATE(allocation_date) BETWEEN '$from_date' AND '$to_date'
");

$task_banking = direct_sql("
    SELECT * 
    FROM task_banking 
    WHERE status = 'VERIFIED'
    AND DATE(allocation_date) BETWEEN '$from_date' AND '$to_date'
");

$task_physical = direct_sql("
    SELECT * 
    FROM task_physical 
    WHERE status = 'VERIFIED'
    AND DATE(allocation_date) BETWEEN '$from_date' AND '$to_date'
");

// Calculate totals
$total_tasks = ($task_ito['count'] ?? 0) + ($task_banking['count'] ?? 0) + ($task_physical['count'] ?? 0);

// Calculate percentage changes (you can modify this logic based on your needs)
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

// You can fetch previous period data for comparison
$previous_ito = 15; // Replace with actual query for previous period
$previous_banking = 12; // Replace with actual query for previous period  
$previous_physical = 28; // Replace with actual query for previous period

$ito_change = calculatePercentageChange($task_ito['count'] ?? 0, $previous_ito);
$banking_change = calculatePercentageChange($task_banking['count'] ?? 0, $previous_banking);
$physical_change = calculatePercentageChange($task_physical['count'] ?? 0, $previous_physical);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --shadow-soft: 0 8px 32px rgba(31, 38, 135, 0.15);
            --shadow-hover: 0 12px 40px rgba(31, 38, 135, 0.25);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dashboard-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0 !important;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: var(--shadow-soft);
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dashboard-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
            font-weight: 300;
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-soft);
            padding: 1.5rem !important;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .filter-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .daterange-input {
            border: 2px solid #e3e8ef;
            border-radius: 12px;
            padding: 12px 16px !important;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .daterange-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-modern {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-soft);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .stat-card.ito::before {
            background: var(--success-gradient);
        }

        .stat-card.banking::before {
            background: var(--warning-gradient);
        }

        .stat-card.physical::before {
            background: var(--danger-gradient);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-card.ito .stat-icon {
            background: var(--success-gradient);
        }

        .stat-card.banking .stat-icon {
            background: var(--warning-gradient);
        }

        .stat-card.physical .stat-icon {
            background: var(--danger-gradient);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0.5rem 0;
            line-height: 1;
        }

        .stat-label {
            color: #718096;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.9rem;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .stat-change.positive {
            color: #059669;
            background: rgba(5, 150, 105, 0.1);
        }

        .stat-change.negative {
            color: #dc2626;
            background: rgba(220, 38, 38, 0.1);
        }

        .stat-change.neutral {
            color: #6b7280;
            background: rgba(107, 114, 128, 0.1);
        }

        .nav-tabs-modern {
            border: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 8px;
            box-shadow: var(--shadow-soft);
        }

        .nav-tabs-modern .nav-link {
            border: none;
            border-radius: 12px;
            color: #718096;
            font-weight: 600;
            padding: 12px 24px;
            transition: var(--transition);
            margin: 0 4px;
        }

        .nav-tabs-modern .nav-link:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .nav-tabs-modern .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .table-container {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            margin-top: 1.5rem;
        }

        .table-modern {
            margin: 0;
            font-size: 0.95rem;
        }

        .table-modern thead th {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            /*letter-spacing: 0.5px;*/
            padding: 1.2rem 1rem !important;
            border: none;
            /*font-size: 0.85rem;*/
        }

        .table-modern tbody td {
            padding: 0.5rem !important;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .table-modern tbody tr {
            /*transition: var(--transition);*/
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            /*transform: scale(1.01);*/
        }

        .btn-action {
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 16px;
            border: 2px solid;
            transition: var(--transition);
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .tab-content {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge-task-type {
            background: var(--primary-gradient);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .date-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
        }

        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                border-radius: var(--border-radius);
            }
        }
    </style>
</head>
<body>

    <div class="container pb-5">
        <div class="filter-card">
            <form method="GET" action="">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-1">
                            <i class="fas fa-filter me-2 text-primary"></i>Filter by Date Range
                        </h5>
                        <p class="text-muted mb-0">Select a date range to view tasks (Currently showing: <?php echo $total_tasks; ?> tasks)</p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 mt-3 mt-md-0 float-end">
                            <input type="text" name="daterange" class="form-control daterange-input flex-grow-1" 
                                   placeholder="Select date range" 
                                   style='max-width: 220px;'
                                   value="<?php echo isset($_GET['daterange']) ? htmlspecialchars($_GET['daterange']) : ''; ?>" />
                            <button type="submit" class="btn btn-modern" style='min-width: 120px;'>
                                <i class="fas fa-search me-2"></i>Apply
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-tabs-modern mb-0" id="taskTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ito-tab" data-bs-toggle="tab" data-bs-target="#ito" type="button" role="tab">
                    <i class="fas fa-desktop me-2"></i>Task ITO 
                    <span class="badge bg-light text-dark ms-2"><?php echo $task_ito['count'] ?? 0; ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="banking-tab" data-bs-toggle="tab" data-bs-target="#banking" type="button" role="tab">
                    <i class="fas fa-university me-2"></i>Task Banking 
                    <span class="badge bg-light text-dark ms-2"><?php echo $task_banking['count'] ?? 0; ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="physical-tab" data-bs-toggle="tab" data-bs-target="#physical" type="button" role="tab">
                    <i class="fas fa-map-marker-alt me-2"></i>Task Physical 
                    <span class="badge bg-light text-dark ms-2"><?php echo $task_physical['count'] ?? 0; ?></span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="taskTabsContent">
            <!-- Task ITO -->
            <div class="tab-pane fade show active" id="ito" role="tabpanel">
                <div class="table-container">
                    <?php if (isset($task_ito['data']) && !empty($task_ito['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tag me-2"></i>Task Type</th>
                                        <th><i class="fas fa-file-alt me-2"></i>Document No</th>
                                        <th><i class="fas fa-clipboard-list me-2"></i>Requirement</th>
                                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                                        <th><i class="fas fa-cog me-2"></i>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach((array)$task_ito['data'] as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="badge-task-type">
                                                    <?php echo htmlspecialchars(get_data('task_list', $row['task_type_id'], 'task_name')['data'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($row['document_no'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['requirement'] ?? 'N/A'); ?></td>
                                            <td><small class="text-muted"><?php echo date('Y-m-d', strtotime($row['allocation_date'] ?? 'now')); ?></small></td>
                                            <td>
                                                <button class="btn btn-outline-primary btn-action btn_review" data-table="task_ito" data-id="<?=$row['id']?>">
                                                    <i class="fas fa-eye me-1"></i>Review
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h5>No ITO Tasks Found</h5>
                            <p>No verified ITO tasks found for the selected date range.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Task Banking -->
            <div class="tab-pane fade" id="banking" role="tabpanel">
                <div class="table-container">
                    <?php if (isset($task_banking['data']) && !empty($task_banking['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tag me-2"></i>Task Type</th>
                                        <th><i class="fas fa-university me-2"></i>Bank Name</th>
                                        <th><i class="fas fa-credit-card me-2"></i>A/c No.</th>
                                        <th><i class="fas fa-clipboard-list me-2"></i>Requirement</th>
                                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                                        <th><i class="fas fa-cog me-2"></i>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach((array)$task_banking['data'] as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="badge-task-type">
                                                    <?php echo htmlspecialchars(get_data('task_list', $row['task_type_id'], 'task_name')['data'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($row['bank_name'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['account_no'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['requirement'] ?? 'N/A'); ?></td>
                                            <td><small class="text-muted"><?php echo date('Y-m-d', strtotime($row['allocation_date'] ?? 'now')); ?></small></td>
                                            <td>
                                                <button class="btn btn-outline-warning btn-action btn_review" data-table="task_banking" data-id="<?=$row['id']?>">
                                                    <i class="fas fa-eye me-1"></i>Review
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-university"></i>
                            <h5>No Banking Tasks Found</h5>
                            <p>No verified banking tasks found for the selected date range.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Task Physical -->
            <div class="tab-pane fade" id="physical" role="tabpanel">
                <div class="table-container">
                    <?php if (isset($task_physical['data']) && !empty($task_physical['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tag me-2"></i>Task Type</th>
                                        <th><i class="fas fa-user me-2"></i>Applicant</th>
                                        <th><i class="fas fa-map-marker-alt me-2"></i>Address</th>
                                        <th><i class="fas fa-clipboard-list me-2"></i>Requirement</th>
                                        <th><i class="fas fa-calendar me-2"></i>Date</th>
                                        <th><i class="fas fa-cog me-2"></i>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach((array)$task_physical['data'] as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="badge-task-type">
                                                    <?php echo htmlspecialchars(get_data('task_list', $row['task_type_id'], 'task_name')['data'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($row['applicant_name'] ?? 'N/A'); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($row['requirement'] ?? 'N/A'); ?></td>
                                            <td><small class="text-muted"><?php echo date('Y-m-d', strtotime($row['allocation_date'] ?? 'now')); ?></small></td>
                                            <td>
                                                <button class="btn btn-outline-info btn-action btn_review" data-table="task_physical" data-id="<?=$row['id']?>">
                                                    <i class="fas fa-eye me-1"></i>Review
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-map-marker-alt"></i>
                            <h5>No Physical Tasks Found</h5>
                            <p>No verified physical tasks found for the selected date range.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php

    
    // $client_id = $_SESSION["client_id"] ?? "";
    // $status_list = get_data('op_user',$client_id,'status_list')['data']??[];
    // print_r($_SESSION);
?>
    
    
<!-- Review Modal -->
<div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" id="review_model">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalCenterTitle">Review Case</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Added 'card-body', 'overflow-auto', and 'max-height' -->
            <div class="modal-body card-body overflow-auto">
                <input type="hidden" name="table_name" class="form-control" id="review_table_name">
                <input type="hidden" name="cases" class="form-control" id="review_case_id">

                <div class="mb-2" id="attachment_area">
                    <label for="attachment" class="form-label"><strong>Attachment : </strong></label>
                    <span id="prev_attachment" target="_blank" class="text-primary">View Attachment</span>
                </div>

                <div class="fields"></div>
                
                <div class='row'>
                    <div class='col-md-6'>
                 
                        <div class="mb-2">
                            <label for="remarks" class="form-label"><strong>Review:</strong></label>
                            <textarea name="remarks" id="Verifier_remarks" class="form-control" rows="3"
                                placeholder="Verifier remark..."></textarea>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        
                    <div class="mb-2">
                        <label for="admin_remark" class="form-label">
                            <strong>Admin Remark:</strong>
                            <badge class='badge bg-dark text-light' class='open_chat btn_ai_remark'
                                data-status='Positive'> Generate Remarks With AI  </badge>
                            <badge class="btn_positive badge bg-success" id='btn_positive' data-status='Positive'>Positive
                            </badge>
                            <badge class="btn_negetive badge bg-danger" data-status='Negative'>Negative</badge>
                            <badge class="btn_cnv badge bg-secondary" data-status='CNV'>Can Not Verify</badge>
                         </label>
                        <textarea name="admin_remark" id="admin_remark" class="form-control" rows="3"
                            placeholder="Enter your remark..."></textarea>
                        <!--<input type="hidden" name="case_status" class="form-control" id="case_status">-->
                        
                        <!--<div class='p-2'>-->
                        <!--  Task Status : -->
                        <!--<select name='case_status' class='from-control' id="case_status" required>-->
                            <?//= dropdown(explode(",",$status_list)); ?>
                        <!--</select>-->
                        <!--</div>-->
                        
                        <input type="hidden" name="cancle_reason" class="form-control" id="cancle_reason">
                    </div>
                </div>
                </div>
                <div class="mb-2">
                  
                    <label for="hidden_attachment_input" class="form-label">Attachment</label>
                    <input type="text" class='form-control' name="attachment" id="hidden_attachment_input" value="">
                </div>
                <!--<input class="custom-file-uploader form-control" type="file" id="custom_file_input" name="uploadimg[]" multiple accept="image/png, image/gif, image/jpeg, application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-table="itr" data-field="attachment">-->
                <div id="custom_file_preview"></div>
                <div class="mb-2" id="attachment_area">
                    <div class="form-check">
                        <input type="hidden" name="docs_in_report" value="NO">
                        <input class="form-check-input" type="checkbox" id="docs_in_report" name="docs_in_report"
                            value="YES">
                        <label class="form-check-label" for="docs_in_report">
                            <strong>Show in Report</strong>
                        </label>
                    </div>

                </div>

            </div>
            <div class="modal-footer">
                <button class="save_btn btn btn-success m-1">Save & Close</button>
            </div>
        </div>
    </div>
</div>





    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   <?php  require_once("ai_chat.php");  ?>
 <script>
    $(function() {
      $('input[name="daterange"]').daterangepicker({
        opens: 'left',
        autoUpdateInput: false,
        locale: {
          format: 'MM/DD/YYYY',
          cancelLabel: 'Clear'
        },
        ranges: {
          'Today': [moment(), moment()],
          'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
      });

      $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
      });

      $('input[name="daterange"]').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
      });
    });
  </script>
  
  