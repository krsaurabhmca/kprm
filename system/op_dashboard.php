<?php require_once('all_header.php');

// Get statistics for dashboard
global $con;

// Cases Statistics
$cases_stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'closed' => 0
];

$cases_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN case_status = 'PENDING' OR case_status IS NULL THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN case_status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN case_status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN case_status = 'CLOSED' THEN 1 ELSE 0 END) as closed
    FROM cases WHERE status = 'ACTIVE'";
$cases_res = mysqli_query($con, $cases_sql);
if ($cases_res) {
    $cases_row = mysqli_fetch_assoc($cases_res);
    $cases_stats = [
        'total' => (int)$cases_row['total'],
        'pending' => (int)$cases_row['pending'],
        'in_progress' => (int)$cases_row['in_progress'],
        'completed' => (int)$cases_row['completed'],
        'closed' => (int)$cases_row['closed']
    ];
}

// Tasks Statistics
$tasks_stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'verification_completed' => 0,
    'completed' => 0
];

$tasks_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN task_status = 'PENDING' OR task_status IS NULL THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN task_status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN task_status = 'VERIFICATION_COMPLETED' THEN 1 ELSE 0 END) as verification_completed,
    SUM(CASE WHEN task_status = 'COMPLETED' THEN 1 ELSE 0 END) as completed
    FROM case_tasks WHERE status = 'ACTIVE'";
$tasks_res = mysqli_query($con, $tasks_sql);
if ($tasks_res) {
    $tasks_row = mysqli_fetch_assoc($tasks_res);
    $tasks_stats = [
        'total' => (int)$tasks_row['total'],
        'pending' => (int)$tasks_row['pending'],
        'in_progress' => (int)$tasks_row['in_progress'],
        'verification_completed' => (int)$tasks_row['verification_completed'],
        'completed' => (int)$tasks_row['completed']
    ];
}

// Clients Statistics
$clients_sql = "SELECT COUNT(*) as total FROM clients WHERE status = 'ACTIVE'";
$clients_res = mysqli_query($con, $clients_sql);
$clients_total = 0;
if ($clients_res) {
    $clients_row = mysqli_fetch_assoc($clients_res);
    $clients_total = (int)$clients_row['total'];
}

// Verifiers Statistics
$verifiers_sql = "SELECT COUNT(*) as total FROM verifier WHERE status = 'ACTIVE'";
$verifiers_res = mysqli_query($con, $verifiers_sql);
$verifiers_total = 0;
if ($verifiers_res) {
    $verifiers_row = mysqli_fetch_assoc($verifiers_res);
    $verifiers_total = (int)$verifiers_row['total'];
}

// Recent Cases (Last 5)
$recent_cases = [];
$recent_sql = "SELECT c.*, cl.name as client_name 
    FROM cases c 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    WHERE c.status = 'ACTIVE' 
    ORDER BY c.created_at DESC 
    LIMIT 5";
$recent_res = mysqli_query($con, $recent_sql);
if ($recent_res) {
    while ($row = mysqli_fetch_assoc($recent_res)) {
        $recent_cases[] = $row;
    }
}

// Pending Tasks (Last 5)
$pending_tasks = [];
$pending_tasks_sql = "SELECT ct.*, c.application_no, cl.name as client_name 
    FROM case_tasks ct 
    LEFT JOIN cases c ON ct.case_id = c.id 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    WHERE ct.status = 'ACTIVE' AND (ct.task_status = 'PENDING' OR ct.task_status IS NULL)
    ORDER BY ct.created_at DESC 
    LIMIT 5";
$pending_tasks_res = mysqli_query($con, $pending_tasks_sql);
if ($pending_tasks_res) {
    while ($row = mysqli_fetch_assoc($pending_tasks_res)) {
        $pending_tasks[] = $row;
    }
}

// Tasks requiring review
$review_tasks = [];
$review_sql = "SELECT ct.*, c.application_no, cl.name as client_name 
    FROM case_tasks ct 
    LEFT JOIN cases c ON ct.case_id = c.id 
    LEFT JOIN clients cl ON c.client_id = cl.id 
    WHERE ct.status = 'ACTIVE' AND ct.task_status = 'VERIFICATION_COMPLETED'
    ORDER BY ct.verified_at DESC 
    LIMIT 5";
$review_res = mysqli_query($con, $review_sql);
if ($review_res) {
    while ($row = mysqli_fetch_assoc($review_res)) {
        $review_tasks[] = $row;
    }
}
?>
		<main class="content">
			<div class="container-fluid p-0">
				<div class="row mb-2 mb-xl-3">
						<div class="col-auto d-none d-sm-block">
							<h3><strong><?= $user_type ?> </strong>Dashboard </h3>
						</div>

						<div class="col-auto ms-auto text-end mb-1 mx-4">
						    <span class="btn btn-md btn-dark"><i class="fas fa-user me-1"></i><?= $user_name ?></span>
							<?php if($user_type =='DEV') { ?>
						
							<a href="op_play.php" class="btn btn-warning">Playground</a>
						
							<?php  } else if($user_type=='MANAGER'){ ?>
                            <a href="../public/case_manage.php?cstatus=PENDING" class="btn btn-warning mr-2">Pending Cases</a>
                            <a href="../public/case_manage.php?cstatus=CLOSED" class="btn btn-success pr-2">Closed Cases</a>
                            <?php }  ?>
                            
						<?php if($user_type =='ADMIN' || $user_type =='DEV') { ?>
						
						<a href="<?= $base_url?>public/case_manage.php" class="btn btn-primary">Cases</a>
						<a href="<?= $base_url?>public/report_templates_manage.php" class="btn btn-info">Templates</a>
						
						<?php } ?> 
						</div>
						
		
				</div>

				<!-- Statistics Cards -->
				<div class="row">
					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-primary shadow h-100 py-2" style="border-left: 4px solid #4e73df !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
											Total Cases
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $cases_stats['total'] ?></div>
									</div>
									<div class="col-auto">
										<i class="fas fa-folder fa-2x text-gray-300"></i>
									</div>
								</div>
							</div>
						</div>
                            </div>

					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-warning shadow h-100 py-2" style="border-left: 4px solid #f6c23e !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
											Pending Cases
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $cases_stats['pending'] ?></div>
									</div>
									<div class="col-auto">
										<i class="fas fa-clock fa-2x text-gray-300"></i>
									</div>
                              </div>
                            </div>
                          </div>
                        </div>

					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-info shadow h-100 py-2" style="border-left: 4px solid #36b9cc !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-info text-uppercase mb-1">
											Total Tasks
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tasks_stats['total'] ?></div>
									</div>
									<div class="col-auto">
										<i class="fas fa-tasks fa-2x text-gray-300"></i>
									</div>
								</div>
							</div>
                                        </div>
					</div>

					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-success shadow h-100 py-2" style="border-left: 4px solid #1cc88a !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-success text-uppercase mb-1">
											Completed Tasks
            </div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tasks_stats['completed'] ?></div>
            </div>
									<div class="col-auto">
										<i class="fas fa-check-circle fa-2x text-gray-300"></i>
            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

				<!-- Additional Stats Row -->
				<div class="row">
					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-danger shadow h-100 py-2" style="border-left: 4px solid #e74a3b !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
											Tasks In Progress
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tasks_stats['in_progress'] ?></div>
									</div>
									<div class="col-auto">
										<i class="fas fa-spinner fa-2x text-gray-300"></i>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-secondary shadow h-100 py-2" style="border-left: 4px solid #858796 !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
											Awaiting Review
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tasks_stats['verification_completed'] ?></div>
                        </div>
									<div class="col-auto">
										<i class="fas fa-eye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-primary shadow h-100 py-2" style="border-left: 4px solid #4e73df !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
											Active Clients
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $clients_total ?></div>
									</div>
									<div class="col-auto">
										<i class="fas fa-users fa-2x text-gray-300"></i>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-xl-3 col-md-6 mb-4">
						<div class="card border-left-info shadow h-100 py-2" style="border-left: 4px solid #36b9cc !important;">
							<div class="card-body">
								<div class="row no-gutters align-items-center">
									<div class="col mr-2">
										<div class="text-xs font-weight-bold text-info text-uppercase mb-1">
											Active Verifiers
										</div>
										<div class="h5 mb-0 font-weight-bold text-gray-800"><?= $verifiers_total ?></div>
                                                </div>
									<div class="col-auto">
										<i class="fas fa-user-check fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        
				<!-- Quick Actions & Recent Activity -->
				<div class="row">
					<!-- Quick Actions -->
					<div class="col-lg-4 mb-4">
						<div class="card shadow">
							<div class="card-header bg-primary text-white">
								<h6 class="m-0 font-weight-bold"><i class="fas fa-bolt"></i> Quick Actions</h6>
                                        </div>
							<div class="card-body">
								<div class="d-grid gap-2">
									<a href="<?= $base_url?>public/add_new_case.php" class="btn btn-primary btn-block">
										<i class="fas fa-plus-circle"></i> Add New Case
									</a>
									<a href="<?= $base_url?>public/case_manage.php" class="btn btn-info btn-block">
										<i class="fas fa-list"></i> View All Cases
									</a>
									<a href="<?= $base_url?>public/report_templates_manage.php" class="btn btn-warning btn-block">
										<i class="fas fa-file-alt"></i> Report Templates
									</a>
									<?php if($user_type =='ADMIN' || $user_type =='DEV') { ?>
									<a href="<?= $base_url?>public/report_templates_add.php" class="btn btn-success btn-block">
										<i class="fas fa-plus"></i> Create Template
									</a>
									<?php } ?>
									<a href="<?= $base_url?>public/case_manage.php?cstatus=PENDING" class="btn btn-danger btn-block">
										<i class="fas fa-exclamation-triangle"></i> Pending Cases
									</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        
					<!-- Recent Cases -->
					<div class="col-lg-4 mb-4">
						<div class="card shadow">
							<div class="card-header bg-info text-white">
								<h6 class="m-0 font-weight-bold"><i class="fas fa-folder-open"></i> Recent Cases</h6>
                        </div>
							<div class="card-body" style="max-height: 400px; overflow-y: auto;">
								<?php if (count($recent_cases) > 0): ?>
									<?php foreach ($recent_cases as $case): ?>
										<div class="mb-3 pb-3 border-bottom">
											<div class="d-flex justify-content-between align-items-start">
												<div class="flex-grow-1">
													<h6 class="mb-1">
														<a href="<?= $base_url?>public/view_case.php?case_id=<?= $case['id'] ?>" class="text-decoration-none">
															<?= htmlspecialchars($case['application_no'] ?? 'N/A') ?>
														</a>
													</h6>
													<small class="text-muted">
														<i class="fas fa-building"></i> <?= htmlspecialchars($case['client_name'] ?? 'Unknown') ?>
													</small>
													<br>
													<small class="text-muted">
														<i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($case['created_at'])) ?>
													</small>
                                        </div>
												<div>
                                                        <?php
													$status = $case['case_status'] ?? 'PENDING';
													$badge_class = 'secondary';
													if ($status == 'COMPLETED') $badge_class = 'success';
													elseif ($status == 'IN_PROGRESS') $badge_class = 'info';
													elseif ($status == 'CLOSED') $badge_class = 'dark';
                                                        ?>
													<span class="badge bg-<?= $badge_class ?>"><?= $status ?></span>
                                            </div>
                                        </div>
                                    </div>
									<?php endforeach; ?>
								<?php else: ?>
									<p class="text-muted text-center">No recent cases</p>
								<?php endif; ?>
							</div>
							<div class="card-footer text-center">
								<a href="<?= $base_url?>public/case_manage.php" class="btn btn-sm btn-info">View All</a>
							</div>
						</div>
                            </div>
                    
					<!-- Pending Tasks & Review -->
					<div class="col-lg-4 mb-4">
						<div class="card shadow">
							<div class="card-header bg-warning text-dark">
								<h6 class="m-0 font-weight-bold"><i class="fas fa-tasks"></i> Pending Tasks</h6>
							</div>
							<div class="card-body" style="max-height: 200px; overflow-y: auto;">
								<?php if (count($pending_tasks) > 0): ?>
									<?php foreach ($pending_tasks as $task): ?>
										<div class="mb-2 pb-2 border-bottom">
											<small>
												<a href="<?= $base_url?>public/view_case.php?case_id=<?= $task['case_id'] ?>" class="text-decoration-none">
													<?= htmlspecialchars($task['application_no'] ?? 'N/A') ?>
												</a>
												<br>
												<span class="text-muted"><?= htmlspecialchars($task['client_name'] ?? 'Unknown') ?></span>
											</small>
										</div>
									<?php endforeach; ?>
								<?php else: ?>
									<p class="text-muted text-center">No pending tasks</p>
								<?php endif; ?>
							</div>
						</div>

						<div class="card shadow mt-3">
							<div class="card-header bg-success text-white">
								<h6 class="m-0 font-weight-bold"><i class="fas fa-check-double"></i> Awaiting Review</h6>
							</div>
							<div class="card-body" style="max-height: 200px; overflow-y: auto;">
								<?php if (count($review_tasks) > 0): ?>
									<?php foreach ($review_tasks as $task): ?>
										<div class="mb-2 pb-2 border-bottom">
											<small>
												<a href="<?= $base_url?>public/task_review.php?task_id=<?= $task['id'] ?>" class="text-decoration-none">
													<?= htmlspecialchars($task['application_no'] ?? 'N/A') ?>
												</a>
												<br>
												<span class="text-muted"><?= htmlspecialchars($task['client_name'] ?? 'Unknown') ?></span>
											</small>
                                        </div>
									<?php endforeach; ?>
								<?php else: ?>
									<p class="text-muted text-center">No tasks awaiting review</p>
								<?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                    
				<!-- Task Status Overview Chart -->
				<div class="row">
					<div class="col-lg-12 mb-4">
						<div class="card shadow">
                                        <div class="card-header bg-primary text-white">
								<h6 class="m-0 font-weight-bold"><i class="fas fa-chart-bar"></i> Task Status Overview</h6>
							</div>
							<div class="card-body">
								<div class="row text-center">
									<div class="col-md-3">
										<div class="p-3 border rounded">
											<h4 class="text-warning"><?= $tasks_stats['pending'] ?></h4>
											<p class="mb-0 text-muted">Pending</p>
										</div>
									</div>
									<div class="col-md-3">
										<div class="p-3 border rounded">
											<h4 class="text-info"><?= $tasks_stats['in_progress'] ?></h4>
											<p class="mb-0 text-muted">In Progress</p>
										</div>
									</div>
									<div class="col-md-3">
										<div class="p-3 border rounded">
											<h4 class="text-primary"><?= $tasks_stats['verification_completed'] ?></h4>
											<p class="mb-0 text-muted">Verification Completed</p>
										</div>
									</div>
									<div class="col-md-3">
										<div class="p-3 border rounded">
											<h4 class="text-success"><?= $tasks_stats['completed'] ?></h4>
											<p class="mb-0 text-muted">Completed</p>
                                        </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>
				</div>

			</div>
		</main>
<?php require_once('footer.php'); ?>


<script src='./js/kprm.js'></script>
</body>
</html>
