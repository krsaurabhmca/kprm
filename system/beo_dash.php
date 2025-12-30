
<style>
    .client-widget {
        background: #fff;
        border-radius: 0.375rem;
        padding: 0;
        box-shadow: 0 0.125rem 0.25rem rgb(0 0 0 / 0.075);
        margin-bottom: 1.5rem;
    }

    .client-widget .card-header {
        background-color: #0d6efd;
        color: #fff;
        font-weight: 600;
        padding: 0.75rem 1rem;
        font-size: .9rem;
        border-bottom: 2px solid #0b5ed7;
    }

    .client-widget .card-body {
        padding: 0.5rem 1rem 1rem 1rem;
    }

    .client-table thead {
        background-color: #e9ecef;
    }

    .client-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .client-table {
        font-size: 0.875rem;
    }

    .table-responsive-scroll {
        max-height: 300px;
        overflow-y: auto;
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="client-widget card shadow-sm">
                <div class="card-header">
                    All Clients
                </div>
                <div class="card-body">
                    <div class="table-responsive-scroll">
                        <table class="table client-table table-bordered table-hover table-sm align-middle mb-0 text-center">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client Name</th>
                                    <th>User Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>View Statics</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $clients = direct_sql("SELECT id,full_name,user_email,user_mobile,user_name FROM op_user WHERE beo_id = '{$_SESSION['user_id']}'");
                                    if (($clients['count']>0)):
                                    $i=1; 
                                    foreach ($clients['data'] as $client): 
                                    $cid = $client['id']; 
                                    ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($client['full_name']) ?></td>
                                            <td><?= htmlspecialchars($client['user_name']) ?></td>
                                            <td><?= htmlspecialchars($client['user_email']) ?></td>
                                            <td><?= htmlspecialchars($client['user_mobile']) ?></td>
                                            <td class="text-center">
                                                <a href='../public/add_new_case.php?client_id=<?=$cid?>' target='_blank' class="btn btn-sm btn-success view-statics-btn" data-client-id="<?= $client['id'] ?>">
                                                    <i class="fas fa-plus me-1"></i> Add Case
                                                </a>
                                                <a href='../public/case_manage?cstatus=PENDING' target='_blank' class="btn btn-sm btn-info view-statics-btn" data-client-id="<?= $client['id'] ?>">
                                                    <i class="fas fa-hourglass-half me-1"></i> Pending Case
                                                </a>
                                                <a href='../public/case_manage?cstatus=CLOSED' target='_blank' class="btn btn-sm btn-dark view-statics-btn" data-client-id="<?= $client['id'] ?>">
                                                    <i class="fas fa-check-circle me-1"></i> Closed Case
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; 
                                else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No clients found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</div> 