<?php
/**
 * KPRM - Template Management
 * Simple page to upload/manage HTML templates
 */
require_once('../system/op_lib.php');
require_once('../function.php');

$page_title = "Template Management";
require_once('../system/all_header.php');

// Get client list
global $con;
$clients = [];
if (isset($con) && $con) {
    $clients_query = "SELECT id, name FROM clients WHERE status = 'ACTIVE' ORDER BY name ASC";
    $clients_result = mysqli_query($con, $clients_query);
    if ($clients_result) {
        while ($row = mysqli_fetch_assoc($clients_result)) {
            $clients[] = $row;
        }
    }
}

// Get templates
$templates = [];
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    $templates_query = "SELECT * FROM report_templates WHERE status = 'ACTIVE' ORDER BY id DESC";
    $templates_result = mysqli_query($con, $templates_query);
    if ($templates_result) {
        while ($row = mysqli_fetch_assoc($templates_result)) {
            $templates[] = $row;
        }
    }
}
?>

<main class="content">
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3"><?php echo $page_title; ?></h1>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-code"></i> HTML Templates
                            <a href="template_editor.php" class="btn btn-primary btn-sm float-end">
                                <i class="fas fa-plus"></i> New Template
                            </a>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($templates)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No templates found. Upload your first HTML template to get started.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Template Name</th>
                                            <th>Client</th>
                                            <th>Type</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($templates as $template): ?>
                                            <tr>
                                                <td><?php echo $template['id']; ?></td>
                                                <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $client_name = 'Unknown';
                                                    foreach ($clients as $client) {
                                                        if ($client['id'] == $template['client_id']) {
                                                            $client_name = $client['name'];
                                                            break;
                                                        }
                                                    }
                                                    echo htmlspecialchars($client_name);
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($template['template_type']); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($template['created_at'])); ?></td>
                                                <td>
                                                    <a href="template_editor.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteTemplate(<?php echo $template['id']; ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<script>
function deleteTemplate(id) {
    if (confirm('Are you sure you want to delete this template?')) {
        fetch('template_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete&template_id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>

<?php require_once('../system/footer.php'); ?>

