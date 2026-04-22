<?php
/**
 * KPRM - MIS Report
 * Comprehensive Management Information System Report with Excel Export
 */

require_once("../system/all_header.php");

// Filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$f_client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Build SQL query for cases
$where = "c.status != 'DELETED' AND DATE(c.created_at) BETWEEN '$date_from' AND '$date_to'";
if ($f_client_id > 0) {
    $where .= " AND c.client_id = $f_client_id";
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
if ($res_cases) {
    while ($row = mysqli_fetch_assoc($res_cases)) {
        $cases_data[] = $row;
        $case_ids[] = $row['case_id'];
    }
}

// Fetch all active tasks for these cases
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
    if ($res_tasks) {
        while ($t_row = mysqli_fetch_assoc($res_tasks)) {
            $tasks_by_case[$t_row['case_id']][] = $t_row;
        }
    }
}

$total_records = count($cases_data);
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1 fw-bold">
                    <i class="fas fa-chart-line text-primary me-2"></i> MIS Report
                </h4>
                <p class="text-muted small mb-0">Single row per case with aggregated task details</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" id="btnExport">
                    <i class="fas fa-file-excel me-2"></i> Export to Excel
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-4 bg-glass-dark">
            <div class="card-body p-3">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Client</label>
                        <select name="client_id" class="form-select">
                            <option value="0">All Clients</option>
                            <?php 
                            $cl_res = get_all('clients', 'id, name', ['status' => 'ACTIVE']);
                            if ($cl_res['count'] > 0) {
                                foreach ($cl_res['data'] as $cl) {
                                    $selected = ($f_client_id == $cl['id']) ? 'selected' : '';
                                    echo "<option value='{$cl['id']}' {$selected}>{$cl['name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100 p-2">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a href="mis_report.php" class="btn btn-outline-secondary w-100 p-2">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-table me-2 text-primary"></i> Data Overview
                    <span class="badge bg-primary ms-2"><?php echo $total_records; ?> Cases</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="misTable">
                        <thead class="table-light">
                            <tr>
                                <th>Sr No.</th>
                                <th>User Name</th>
                                <th>App ID</th>
                                <th>Customer Name</th>
                                <th>Location</th>
                                <th>Product</th>
                                <th>Initiation Document (Sampled Documents)</th>
                                <th>RCU Agency Name</th>
                                <th>Login Month</th>
                                <th>Received Date</th>
                                <th>RCU Send</th>
                                <th>TAT</th>
                                <th>Report Status</th>
                                <th style="width: 300px; min-width: 300px;">Report Comments</th>
                                <th>Loan Amount</th>
                                <th>Extra Checks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($total_records > 0):
                                $sr = 1;
                                foreach ($cases_data as $row):
                                    $case_id = $row['case_id'];
                                    $case_info = json_decode($row['case_info'] ?? '{}', true);
                                    $case_tasks = $tasks_by_case[$case_id] ?? [];

                                    // Aggregate Tasks Info
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
                                    $has_any_pending = (empty($case_tasks)); // True if no tasks or any task is not reviewed

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

                                        if ($task['agency_name'])
                                            $agencies[] = $task['agency_name'];
                                        if ($task['task_created_at'])
                                            $rcu_send_dates[] = strtotime($task['task_created_at']);

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

                                    // Formatting initiation documents string
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
                                    
                                    // Calculate Overall Report Status
                                    $pos_word = $row['positve_status'] ?: 'Positive';
                                    $neg_word = $row['negative_status'] ?: 'Negative';
                                    $cnv_word = $row['cnv_status'] ?: 'CNV';

                                    if ($has_any_pending) {
                                        $report_status = 'Pending';
                                        $status_color = 'warning';
                                    } elseif ($has_negative) {
                                        $report_status = $neg_word;
                                        $status_color = 'danger';
                                    } elseif ($all_positive) {
                                        $report_status = $pos_word;
                                        $status_color = 'success';
                                    } else {
                                        $report_status = $cnv_word;
                                        $status_color = 'info';
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
                                    ?>
                                    <tr>
                                        <td><?php echo $sr++; ?></td>
                                        <td><span class="fw-bold"><?php echo htmlspecialchars($user_name); ?></span></td>
                                        <td><span class="text-primary fw-bold"><?php echo htmlspecialchars($app_id); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($customer_name); ?></td>
                                        <td><?php echo htmlspecialchars($location); ?></td>
                                        <td><span
                                                class="badge bg-light text-dark"><?php echo htmlspecialchars($product); ?></span>
                                        </td>
                                        <td><small class="fw-bold"><?php echo htmlspecialchars($initiation_doc_str); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($agency_name); ?></td>
                                        <td><?php echo $login_month; ?></td>
                                        <td><?php echo $received_date; ?></td>
                                        <td><?php echo $rcu_send; ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo $is_pending_tat ? 'bg-warning text-dark' : 'bg-success'; ?>">
                                                <?php echo $tat; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                <?php echo htmlspecialchars($report_status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div
                                                style="max-height: 100px; overflow-y: auto; white-space: pre-wrap; font-size: 0.8rem; width: 300px;">
                                                <?php echo htmlspecialchars($report_comments); ?>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-success"><?php echo $loan_amount; ?></td>
                                        <td><small><?php echo htmlspecialchars($extra_checks ?: 'N/A'); ?></small></td>
                                    </tr>
                                    <?php
                                endforeach;
                            else:
                                ?>
                                <tr>
                                    <td colspan="16" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 opacity-20"></i>
                                        <p>No records found for the selected period.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    .bg-glass-dark {
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    #misTable thead th {
        background-color: #d8dee3;
        /* Soft gray background like in user image */
        color: #334155;
        white-space: nowrap;
        font-size: 0.82rem;
        text-transform: none;
        /* User image shows normal case */
        letter-spacing: normal;
        font-weight: 600;
        border: 1px solid #cbd5e1;
    }

    #misTable td {
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
    }

    .opacity-20 {
        opacity: 0.2;
    }
</style>

<?php require_once("../system/footer.php"); ?>

<script>
    $(document).ready(function () {
        $('#btnExport').click(function () {
            const date_from = $('input[name="date_from"]').val();
            const date_to = $('input[name="date_to"]').val();
            const client_id = $('select[name="client_id"]').val();
            window.location.href = `mis_export.php?date_from=${date_from}&date_to=${date_to}&client_id=${client_id}`;
        });

        // Initialize DataTable for search/sort
        if ($.fn.DataTable) {
            $('#misTable').DataTable({
                pageLength: 50,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search report..."
                }
            });
        }
    });
</script>