<?php require_once('all_header.php');

if($user_type=='DEV' or $user_type=='ADMIN' or $user_type=='CLIENT')
{

if($user_type=='CLIENT'){
    $full_name = get_data('op_user',$user_id,'full_name','id')['data'];
    $client_name = "client_" . $user_name;
}elseif($user_type=='DEV' or $user_type=='ADMIN'){
    $cid = $_REQUEST['cid'];
    $cinfo = get_data('op_user',$cid)['data'];
    $full_name = $cinfo['full_name'];
    $user_name = $cinfo['user_name'];
    $client_name = "client_" . $user_name;
}
?>
			<main class="content">
				<div class="container-fluid p-0">

					<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<h3><strong><?= add_space($client_name) ?> </strong>Dashboard </h3>
						
						</div>

						<div class="col-auto ms-auto text-end mt-n1">
							<?php if($user_type =='DEV') { ?>
						
							<!--<a href="op_menu_manage" class="btn btn-border border-warning text-dark me-2">Manage Menu </a>-->
							<a href="op_play.php" class="btn btn-warning">Playground</a>
						
							<?php  } ?> 
						</div>
						
					</div>
					<div class="row">
                       <?php
                            
                            $start_date = date('Y-m-01'); 
                            $end_date = date('Y-m-d');
                        
                            function getDailyCaseData($client_name, $date) {
                                $tables = ['task_ito', 'task_banking', 'task_physical'];
                                $total_received = 0;
                                $closed_same_day = 0;
                                $pending = 0;
                        
                                $client_name_esc = addslashes($client_name);
                                $date = addslashes($date);
                        
                                foreach ($tables as $table) {
                                    $t_res = direct_sql("SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date'");
                                    $total_received += (int)$t_res['data']['0']['count'];
                                    $csd_res = direct_sql("SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date' AND status='CLOSED' AND DATE(close_at) = '$date'");
                                    $closed_same_day += (int)$csd_res['data']['0']['count'];
                                    
                                    $res = direct_sql("SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) <= '$date' AND status != 'CLOSED'");
                                    $pending += (int)$res['data']['0']['count'];
                                }
                        
                                return [
                                    'received' => $total_received,
                                    'closed'   => $closed_same_day,
                                    'pending'  => $pending
                                ];
                            }
                        ?>

                        <div class="col-md-5">
                            <div class="card shadow-sm border-primary">
                                <div class="card-header bg-primary text-white">
                                    <strong>Client Case Summary - <?= date('F Y', strtotime($start_date)) ?></strong>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="mb-2">Name of Client: <span class="text-muted"><?= htmlspecialchars($full_name) ?></span></h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-bordered table-sm table-hover mb-0 text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style='width:80px'>Date</th>
                                                    <th>Total Cases Received</th>
                                                    <th>Closed on Same Day</th>
                                                    <th>Total Pending Cases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $current = strtotime($start_date);
                                                $end = strtotime($end_date);
                                                while ($current <= $end) {
                                                    $date_str = date("Y-m-d", $current);
                                                    $show_date = date("d-m-Y", $current);
                                                    $data = getDailyCaseData('client_indusind', $date_str);
                                                    echo "<tr>
                                                            <td>$show_date</td>
                                                            <td>{$data['received']}</td>
                                                            <td>{$data['closed']}</td>
                                                            <td>{$data['pending']}</td>
                                                        </tr>";
                                                    $current = strtotime("+1 day", $current);
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                              
                            function getSafeCount($res) {
                                return isset($res['data'][0]['count']) ? (int)$res['data'][0]['count'] : 0;
                            }
                            function getCaseCounts($client_name, $date) {
                                $tables = ['task_ito', 'task_banking', 'task_physical'];
                            
                                $received = 0;
                                $closed_same_day = 0;
                                $pending = 0;
                            
                                $client_name_esc = addslashes($client_name);
                                $date_esc = addslashes($date);
                            
                                foreach ($tables as $table) {
                                    $sql_received = "SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc'";
                                    $res = direct_sql($sql_received);
                                    $received += getSafeCount($res);
                            
                                    $sql_closed = "SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc' AND status='CLOSED' AND DATE(close_at) = '$date_esc'";
                                    $res = direct_sql($sql_closed);
                                    $closed_same_day += getSafeCount($res);
                            
                                    $sql_pending = "SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) <= '$date_esc' AND (close_at IS NULL OR DATE(close_at) > '$date_esc')";
                                    $res = direct_sql($sql_pending);
                                    $pending += getSafeCount($res);
                                }
                            
                                return [
                                    'received' => $received,
                                    'closed_same_day' => $closed_same_day,
                                    'pending' => $pending,
                                ];
                            }
                            
                            $current = strtotime($start_date);
                            $end = strtotime($end_date);
                            
                            ?>
                            
                            <div class="col-md-7">
                                <div class="card shadow-sm border-success">
                                    <div class="card-header bg-success text-white">
                                        <strong>Daily Case Report - <?= date('F Y', strtotime($start_date)) ?></strong>
                                    </div>
                                    <div class="card-body p-2">
                                        <h6 class="mb-2">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                            <table class="table table-bordered table-sm table-hover mb-0 text-center">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style='width: 85px;'>Date</th>
                                                        <th>Total Cases Received</th>
                                                        <th>Closed on Same Day</th>
                                                        <th>Pendency of the Day</th>
                                                        <th>Previous Day Pendency</th>
                                                        <th>Total Pendency</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $prev_pending = 0; // store pending from previous day to calculate pendency of the day
                            
                                                    while ($current <= $end) {
                                                        $date_str = date("Y-m-d", $current);
                                                        $display_date = date("d-m-Y", $current);
                            
                                                        $counts = getCaseCounts($client_name, $date_str);
                            
                                                        $pendency_of_day = max(0, $counts['pending'] - $prev_pending);
                                                        $previous_day_pendency = $prev_pending;
                                                        $total_pendency = $counts['pending'];
                            
                                                        echo "<tr>
                                                                <td>$display_date</td>
                                                                <td>{$counts['received']}</td>
                                                                <td>{$counts['closed_same_day']}</td>
                                                                <td>$pendency_of_day</td>
                                                                <td>$previous_day_pendency</td>
                                                                <td>$total_pendency</td>
                                                              </tr>";
                            
                                                        $prev_pending = $counts['pending'];
                                                        $current = strtotime("+1 day", $current);
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php
                         // or adjust as needed
                        $report_date = date('Y-m-d'); // today, or set manually e.g. '2025-08-06'
                        
                        
                        // Run COUNT queries across your tables for a given date range (start and end dates inclusive)
                        function getCaseCountForDateRange($client_name, $start_date, $end_date) {
                            $tables = ['task_ito', 'task_banking', 'task_physical'];
                            $total = 0;
                        
                            $client_name_esc = addslashes($client_name);
                            $start_date_esc = addslashes($start_date);
                            $end_date_esc = addslashes($end_date);
                        
                            foreach ($tables as $table) {
                                $sql = "SELECT COUNT(id) AS count 
                                        FROM $table 
                                        WHERE client_name='$client_name_esc' 
                                          AND DATE(date_of_entry) BETWEEN '$start_date_esc' AND '$end_date_esc'";
                        
                                $res = direct_sql($sql);
                                $total += getSafeCount($res);
                            }
                        
                            return $total;
                        }
                        
                        // Get total cases for client (open at any time)
                        function getTotalCases($client_name) {
                            $tables = ['task_ito', 'task_banking', 'task_physical'];
                            $total = 0;
                            $client_name_esc = addslashes($client_name);
                        
                            foreach ($tables as $table) {
                                $sql = "SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc'";
                                $res = direct_sql($sql);
                                $total += getSafeCount($res);
                            }
                            return $total;
                        }
                        
                        // Calculate aging categories counts
                        $today = new DateTime($report_date);
                        $today_str = $today->format('Y-m-d');
                        
                        $aging_counts = [];
                        $total_cases = getTotalCases($client_name);
                        
                        // 0 Day (today)
                        $aging_counts['0 Day'] = getCaseCountForDateRange($client_name, $today_str, $today_str);
                        
                        // 0-1 day (today and yesterday)
                        $day_minus_1 = clone $today;
                        $day_minus_1->modify('-1 day');
                        $aging_counts['0-1 day'] = getCaseCountForDateRange($client_name, $day_minus_1->format('Y-m-d'), $today_str);
                        
                        // 0-2 days
                        $day_minus_2 = clone $today;
                        $day_minus_2->modify('-2 days');
                        $aging_counts['0-2 days'] = getCaseCountForDateRange($client_name, $day_minus_2->format('Y-m-d'), $today_str);
                        
                        // 0-3 days
                        $day_minus_3 = clone $today;
                        $day_minus_3->modify('-3 days');
                        $aging_counts['0-3 days'] = getCaseCountForDateRange($client_name, $day_minus_3->format('Y-m-d'), $today_str);
                        
                        // More than 3 days (before day minus 3)
                        $day_minus_3_prev = $day_minus_3->format('Y-m-d');
                        $aging_counts['More than 3 days'] = 0;
                        foreach (['task_ito', 'task_banking', 'task_physical'] as $table) {
                            $sql = "SELECT COUNT(id) AS count FROM $table WHERE client_name='" . addslashes($client_name) . "' AND DATE(date_of_entry) < '$day_minus_3_prev'";
                            $res = direct_sql($sql);
                            $aging_counts['More than 3 days'] += getSafeCount($res);
                        }
                        
                        // Calculate percentages
                        $aging_percentages = [];
                        foreach ($aging_counts as $category => $count) {
                            $aging_percentages[$category] = ($total_cases > 0) ? round(($count / $total_cases) * 100, 2) : 0;
                        }
                        
                        ?>
                        
                        <div class="col-md-4">
                            <div class="card shadow-sm border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <strong>Case Aging Report</strong>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="mb-2">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-bordered table-sm table-hover mb-0 text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Aging Category</th>
                                                    <th>Count of Cases</th>
                                                    <th>%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($aging_counts as $category => $count): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($category) ?></td>
                                                        <td><?= $count ?></td>
                                                        <td><?= $aging_percentages[$category] ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        
                        function getVerificationCounts($client_name, $date) {
                            $tables = ['task_ito', 'task_banking', 'task_physical'];
                        
                            $pv_count = 0;
                            $dv_count = 0;
                        
                            $client_name_esc = addslashes($client_name);
                            $date_esc = addslashes($date);
                        
                            foreach ($tables as $table) {
                                $sql = "SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc'";
                        
                                $res = direct_sql($sql);
                                $count = getSafeCount($res);
                        
                                if ($table === 'task_physical') {
                                    $pv_count += $count;
                                } else { // task_ito or task_banking
                                    $dv_count += $count;
                                }
                            }
                        
                            return [
                                'pv' => $pv_count,
                                'dv' => $dv_count,
                                'total' => $pv_count + $dv_count,
                            ];
                        }
                        
                        $current = strtotime($start_date);
                        $end = strtotime($end_date);
                        ?>
                        
                        <div class="col-md-4">
                            <div class="card shadow-sm border-info">
                                <div class="card-header bg-info text-white">
                                    <strong>Type of Verification Summary</strong>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="mb-2">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                                    <div class="table-responsive" style="max-height: 145px; overflow-y: auto;">
                                        <table class="table table-bordered table-sm table-hover mb-0 text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>PV *</th>
                                                    <th>DV **</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($current <= $end) {
                                                    $date_str = date("Y-m-d", $current);
                                                    $display_date = date("d-m-Y", $current);
                        
                                                    $counts = getVerificationCounts($client_name, $date_str);
                        
                                                    echo "<tr>
                                                            <td>{$display_date}</td>
                                                            <td>{$counts['pv']}</td>
                                                            <td>{$counts['dv']}</td>
                                                            <td>{$counts['total']}</td>
                                                          </tr>";
                        
                                                    $current = strtotime("+1 day", $current);
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-muted mt-2" style="font-size: 0.8rem;">
                                        * PV: Physical Verification ** DV: Banking + ITO
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <?php
                         
                        function getPendencyCountByTable($client_name, $table) {
                            $client_name_esc = addslashes($client_name);
                        
                            $sql = "SELECT COUNT(id) AS count FROM $table WHERE client_name='$client_name_esc' AND status != 'CLOSED'";
                            $res = direct_sql($sql);
                        
                            return getSafeCount($res);
                        }
                        
                        $pendency_counts = [
                            'ITO' => getPendencyCountByTable($client_name, 'task_ito'),
                            'Banking' => getPendencyCountByTable($client_name, 'task_banking'),
                            'Filed' => getPendencyCountByTable($client_name, 'task_physical'),
                        ];
                        ?>
                        
                        <div class="col-md-4">
                          <div class="card shadow-sm border-secondary">
                            <div class="card-header bg-secondary text-white">
                              <strong>Dashboard - Pendency Count by Verification Type</strong>
                            </div>
                            <div class="card-body p-3">
                              <h6 class="mb-3">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                              <div class="table-responsive" style="height: 145px; overflow-y: auto;">
                                <table class="table table-bordered table-sm text-center mb-0">
                                  <thead class="table-light">
                                    <tr>
                                      <th>Verification Type</th>
                                      <th>Pendency Count</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr><td>ITO</td><td><?= $pendency_counts['ITO'] ?></td></tr>
                                    <tr><td>Banking</td><td><?= $pendency_counts['Banking'] ?></td></tr>
                                    <tr><td>Filed</td><td><?= $pendency_counts['Filed'] ?></td></tr>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
                        </div>

                        <?php
                         
                        function getItoPendencyData($client_name, $date) {
                            $client_name_esc = addslashes($client_name);
                            $date_esc = addslashes($date);
                        
                            // Total received on given date
                            $sql_received = "SELECT COUNT(id) AS count FROM task_ito WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc'";
                            $res_received = direct_sql($sql_received);
                            $received = getSafeCount($res_received);
                        
                            // Closed same day
                            $sql_closed = "SELECT COUNT(id) AS count FROM task_ito WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc' AND status='CLOSED' AND DATE(close_at) = '$date_esc'";
                            $res_closed = direct_sql($sql_closed);
                            $closed = getSafeCount($res_closed);
                        
                            // Total pending (created on or before date but not closed yet)
                            $sql_pending = "SELECT COUNT(id) AS count FROM task_ito WHERE client_name='$client_name_esc' AND DATE(date_of_entry) <= '$date_esc' AND status != 'CLOSED'";
                            $res_pending = direct_sql($sql_pending);
                            $pending = getSafeCount($res_pending);
                        
                            return [
                                'received' => $received,
                                'closed' => $closed,
                                'pending' => $pending,
                            ];
                        }
                        
                        $current = strtotime($start_date);
                        $end = strtotime($end_date);
                        ?>
                        
                        <div class="col-md-4">
                            <div class="card shadow-sm border-success">
                                <div class="card-header bg-success text-white">
                                    <strong>Date Wise Pendency - ITO</strong>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="mb-2">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-bordered table-hover table-sm text-center mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style='width:80px'>Date</th>
                                                    <th>Total Cases Received</th>
                                                    <th>Closed on Same Day</th>
                                                    <th>Total Pending Cases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($current <= $end) {
                                                    $date_str = date("Y-m-d", $current);
                                                    $display_date = date("d-m-Y", $current);
                        
                                                    $data = getItoPendencyData($client_name, $date_str);
                        
                                                    echo "<tr>
                                                            <td>$display_date</td>
                                                            <td>{$data['received']}</td>
                                                            <td>{$data['closed']}</td>
                                                            <td>{$data['pending']}</td>
                                                          </tr>";
                        
                                                    $current = strtotime("+1 day", $current);
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        
                        function getBankingPendencyData($client_name, $date) {
                            $client_name_esc = addslashes($client_name);
                            $date_esc = addslashes($date);
                        
                            // Total received on given date
                            $sql_received = "SELECT COUNT(id) AS count FROM task_banking WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc'";
                            $res_received = direct_sql($sql_received);
                            $received = getSafeCount($res_received);
                        
                            // Closed same day
                            $sql_closed = "SELECT COUNT(id) AS count FROM task_banking WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc' AND status='CLOSED' AND DATE(close_at) = '$date_esc'";
                            $res_closed = direct_sql($sql_closed);
                            $closed = getSafeCount($res_closed);
                        
                            // Total pending (created on or before date but not closed yet)
                            $sql_pending = "SELECT COUNT(id) AS count FROM task_banking WHERE client_name='$client_name_esc' AND DATE(date_of_entry) <= '$date_esc' AND status != 'CLOSED'";
                            $res_pending = direct_sql($sql_pending);
                            $pending = getSafeCount($res_pending);
                        
                            return [
                                'received' => $received,
                                'closed' => $closed,
                                'pending' => $pending,
                            ];
                        }
                        
                        
                        $current = strtotime($start_date);
                        $end = strtotime($end_date);
                        ?>
                        
                        <div class="col-md-4">
                            <div class="card shadow-sm border-danger">
                                <div class="card-header bg-danger text-white">
                                    <strong>Date Wise Pendency - Banking</strong>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="mb-2">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-bordered table-hover table-sm text-center mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style='width:80px'>Date</th>
                                                    <th>Total Cases Received</th>
                                                    <th>Closed on Same Day</th>
                                                    <th>Total Pending Cases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($current <= $end) {
                                                    $date_str = date("Y-m-d", $current);
                                                    $display_date = date("d-m-Y", $current);
                        
                                                    $data = getBankingPendencyData($client_name, $date_str);
                        
                                                    echo "<tr>
                                                            <td>$display_date</td>
                                                            <td>{$data['received']}</td>
                                                            <td>{$data['closed']}</td>
                                                            <td>{$data['pending']}</td>
                                                          </tr>";
                        
                                                    $current = strtotime("+1 day", $current);
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                         
                        function getFiledPendencyData($client_name, $date) {
                            $client_name_esc = addslashes($client_name);
                            $date_esc = addslashes($date);
                        
                            // Total cases received on the date
                            $sql_received = "SELECT COUNT(id) AS count FROM task_physical WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc'";
                            $res_received = direct_sql($sql_received);
                            $received = getSafeCount($res_received);
                        
                            // Cases closed on the same date
                            $sql_closed = "SELECT COUNT(id) AS count FROM task_physical WHERE client_name='$client_name_esc' AND DATE(date_of_entry) = '$date_esc' AND status='CLOSED' AND DATE(close_at) = '$date_esc'";
                            $res_closed = direct_sql($sql_closed);
                            $closed = getSafeCount($res_closed);
                        
                            // Total pending as of that date (created on or before date but not closed yet)
                            $sql_pending = "SELECT COUNT(id) AS count FROM task_physical WHERE client_name='$client_name_esc' AND DATE(date_of_entry) <= '$date_esc' AND status != 'CLOSED'";
                            $res_pending = direct_sql($sql_pending);
                            $pending = getSafeCount($res_pending);
                        
                            return [
                                'received' => $received,
                                'closed' => $closed,
                                'pending' => $pending,
                            ];
                        }
                        
                        $current = strtotime($start_date);
                        $end = strtotime($end_date);
                        ?>
                        
                        <div class="col-md-4">
                            <div class="card shadow-sm border-primary">
                                <div class="card-header bg-primary text-white">
                                    <strong>Date Wise Pendency - Filed</strong>
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="mb-2">Name of Client: <span class="text-muted"><strong><?= htmlspecialchars($full_name) ?></strong></span></h6>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-bordered table-hover table-sm text-center mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style='width:80px'>Date</th>
                                                    <th>Total Cases Received</th>
                                                    <th>Closed on Same Day</th>
                                                    <th>Total Pending Cases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                while ($current <= $end) {
                                                    $date_str = date("Y-m-d", $current);
                                                    $display_date = date("d-m-Y", $current);
                        
                                                    $data = getFiledPendencyData($client_name, $date_str);
                        
                                                    echo "<tr>
                                                            <td>{$display_date}</td>
                                                            <td>{$data['received']}</td>
                                                            <td>{$data['closed']}</td>
                                                            <td>{$data['pending']}</td>
                                                          </tr>";
                        
                                                    $current = strtotime("+1 day", $current);
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--<div class="col-md-6">-->
                        <!--  <div class="card shadow-sm border-dark">-->
                        <!--    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">-->
                        <!--      <strong>FE Dashboard</strong>-->
                        <!--      <div class="input-group input-group-sm" style="width: 160px;">-->
                        <!--        <span class="input-group-text">Date</span>-->
                        <!--        <input type="date" class="form-control form-control-sm" value="2025-08-06">-->
                        <!--      </div>-->
                        <!--    </div>-->
                        <!--    <div class="card-body p-2">-->
                        <!--      <div class="table-responsive">-->
                        <!--        <table class="table table-bordered table-sm table-hover text-center mb-0">-->
                        <!--          <thead class="table-light">-->
                        <!--            <tr>-->
                        <!--              <th>Name</th>-->
                        <!--              <th>Case Received</th>-->
                        <!--              <th>Case Closed</th>-->
                        <!--              <th>Pending Case</th>-->
                        <!--            </tr>-->
                        <!--          </thead>-->
                        <!--          <tbody>-->
                        <!--            <tr><td></td><td></td><td></td><td></td></tr>-->
                        <!--            <tr><td></td><td></td><td></td><td></td></tr>-->
                        <!--            <tr><td></td><td></td><td></td><td></td></tr>-->
                        <!--          </tbody>-->
                        <!--        </table>-->
                        <!--      </div>-->
                        <!--    </div>-->
                        <!--  </div>-->
                        <!--</div>-->
					</div>
				</div>
			</main>
<?php }
else{
     echo "<script>window.location.href='op_dashboard'</script>";
}

require_once('footer.php'); ?>