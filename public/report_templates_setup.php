<?php
/**
 * KPRM - Report Templates Setup
 * Quick setup and diagnostic page
 */

require_once('../system/all_header.php');

global $con;

// Check if tables exist
$tables_check = [];
$tables_to_check = ['report_templates', 'report_template_variables'];

foreach ($tables_to_check as $table) {
    $check = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    $tables_check[$table] = ($check && mysqli_num_rows($check) > 0);
}

// Try to create tables if they don't exist
$create_attempted = false;
if (isset($_POST['create_tables'])) {
    $create_attempted = true;
    $sql_file = '../db/create_report_templates_table.sql';
    
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        // Remove comments and split by semicolon
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (mysqli_query($con, $statement)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = mysqli_error($con);
                }
            }
        }
        
        // Re-check tables
        foreach ($tables_to_check as $table) {
            $check = mysqli_query($con, "SHOW TABLES LIKE '$table'");
            $tables_check[$table] = ($check && mysqli_num_rows($check) > 0);
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-tools"></i> Report Templates Setup & Diagnostics
                    </h4>
                </div>
                <div class="card-body">
                    <h5>Database Tables Status</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables_to_check as $table): ?>
                                <tr>
                                    <td><code><?php echo $table; ?></code></td>
                                    <td>
                                        <?php if ($tables_check[$table]): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Exists
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> Not Found
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (!$tables_check['report_templates']): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Tables Not Found</h5>
                            <p>The required database tables are missing. You can:</p>
                            <ol>
                                <li><strong>Manual Method:</strong> Run this SQL in your database:
                                    <pre class="bg-light p-3 mt-2"><code>SOURCE db/create_report_templates_table.sql;</code></pre>
                                </li>
                                <li><strong>Auto Create:</strong> Click the button below to attempt automatic creation:
                                    <form method="POST" class="mt-3">
                                        <button type="submit" name="create_tables" class="btn btn-primary">
                                            <i class="fas fa-magic"></i> Create Tables Automatically
                                        </button>
                                    </form>
                                </li>
                            </ol>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> All required tables exist!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($create_attempted)): ?>
                        <div class="alert alert-<?php echo ($error_count == 0) ? 'success' : 'warning'; ?> mt-3">
                            <h5>Creation Results</h5>
                            <p>Success: <?php echo $success_count; ?> statements</p>
                            <?php if ($error_count > 0): ?>
                                <p>Errors: <?php echo $error_count; ?></p>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h5>Quick Links</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="report_templates_manage.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> Manage Templates
                        </a>
                        <a href="report_templates_add.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add New Template
                        </a>
                        <a href="case_manage.php" class="btn btn-info">
                            <i class="fas fa-folder"></i> View Cases
                        </a>
                    </div>
                    
                    <hr>
                    
                    <h5>Test Database Connection</h5>
                    <?php
                    $db_test = mysqli_query($con, "SELECT 1");
                    if ($db_test) {
                        echo '<div class="alert alert-success"><i class="fas fa-check"></i> Database connection is working.</div>';
                    } else {
                        echo '<div class="alert alert-danger"><i class="fas fa-times"></i> Database connection error: ' . mysqli_error($con) . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../system/footer.php'); ?>

