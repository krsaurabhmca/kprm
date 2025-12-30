<?php
/**
 * KPRM - Verifier Task Submission
 * Allows verifiers to add remarks and attachments to assigned tasks
 */

require_once('../system/all_header.php');

// Get task ID
$case_task_id = isset($_GET['case_task_id']) ? intval($_GET['case_task_id']) : 0;

if (!$case_task_id) {
    $_SESSION['error_message'] = 'Invalid task ID.';
    header('Location: case_manage.php');
    exit;
}

// Get case task data
$case_task = get_data('case_tasks', $case_task_id);
if ($case_task['count'] == 0) {
    $_SESSION['error_message'] = 'Task not found.';
    header('Location: case_manage.php');
    exit;
}

$case_task_data = $case_task['data'];
$case_id = $case_task_data['case_id'];
$assigned_to = $case_task_data['assigned_to'] ?? 0;
$current_status = $case_task_data['task_status'] ?? 'PENDING';

// Check if current user is assigned to this task (or is ADMIN/DEV)
$is_assigned = ($assigned_to == $_SESSION['user_id']) || ($_SESSION['user_type'] == 'ADMIN' || $_SESSION['user_type'] == 'DEV');

if (!$is_assigned && $assigned_to > 0) {
    $_SESSION['error_message'] = 'You are not assigned to this task.';
    header('Location: case_manage.php');
    exit;
}

// Only allow verification if task is IN_PROGRESS (assigned)
if ($current_status != 'IN_PROGRESS' && $current_status != 'VERIFICATION_COMPLETED' && ($_SESSION['user_type'] != 'ADMIN' && $_SESSION['user_type'] != 'DEV')) {
    $_SESSION['error_message'] = 'Task must be assigned (IN_PROGRESS) before verification. Current status: ' . $current_status;
    header('Location: view_case.php?case_id=' . $case_id);
    exit;
}

// Get case and task template info
$case_info = get_data('cases', $case_id);
$case_data = $case_info['count'] > 0 ? $case_info['data'] : null;
$task_template = get_data('tasks', $case_task_data['task_template_id']);
$task_name = $task_template['count'] > 0 ? $task_template['data']['task_name'] : 'Unknown Task';

// Get existing attachments
$attachments = [];
global $con;
$attachments_sql = "
    SELECT id, file_type, file_name, file_url, created_at, created_by
    FROM attachments
    WHERE task_id = '$case_task_id' AND status = 'ACTIVE'
    ORDER BY created_at DESC
";
$attachments_res = mysqli_query($con, $attachments_sql);
if ($attachments_res && mysqli_num_rows($attachments_res) > 0) {
    while ($row = mysqli_fetch_assoc($attachments_res)) {
        $attachments[] = $row;
    }
}

// Get existing remarks (stored in task_data JSON or separate field)
$existing_remarks = '';
$task_data_json = json_decode($case_task_data['task_data'] ?? '{}', true);
if (isset($task_data_json['verifier_remarks'])) {
    $existing_remarks = $task_data_json['verifier_remarks'];
}

// Display messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-check-circle"></i> ' . $_SESSION['success_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid">
    <!-- Progress Indicator -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-tasks text-primary"></i> Task Verification Workflow
                    </h5>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded <?php echo $current_status == 'PENDING' ? 'bg-warning text-white' : 'bg-light'; ?>">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <p class="mb-0"><strong>1. Pending</strong></p>
                                <small>Task Created</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded <?php echo $current_status == 'IN_PROGRESS' ? 'bg-info text-white' : ($current_status == 'VERIFICATION_COMPLETED' || $current_status == 'COMPLETED' ? 'bg-light' : 'bg-secondary text-white'); ?>">
                                <i class="fas fa-user-check fa-2x mb-2"></i>
                                <p class="mb-0"><strong>2. In Progress</strong></p>
                                <small>Verification</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded <?php echo $current_status == 'VERIFICATION_COMPLETED' ? 'bg-primary text-white' : ($current_status == 'COMPLETED' ? 'bg-light' : 'bg-secondary text-white'); ?>">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0"><strong>3. Verified</strong></p>
                                <small>Ready for Review</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded <?php echo $current_status == 'COMPLETED' ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                                <p class="mb-0"><strong>4. Completed</strong></p>
                                <small>Review Done</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0 text-light">
                        <i class="fas fa-file-upload"></i> Task Verification: <?php echo htmlspecialchars($task_name); ?>
                    </h4>
                    <div class="card-tools">
                        <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-light btn-sm float-end">
                            <i class="fas fa-arrow-left"></i> Back to Case
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Task Info Card -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle text-primary"></i> Task Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <i class="fas fa-folder text-muted"></i> 
                                        <strong>Case ID:</strong> 
                                        <span class="badge bg-secondary"><?php echo $case_id; ?></span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <i class="fas fa-file-alt text-muted"></i> 
                                        <strong>Application No:</strong> 
                                        <span><?php echo htmlspecialchars($case_data['application_no'] ?? 'N/A'); ?></span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <i class="fas fa-tasks text-muted"></i> 
                                        <strong>Task Name:</strong> 
                                        <span><?php echo htmlspecialchars($task_name); ?></span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2">
                                        <i class="fas fa-flag text-muted"></i> 
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            $status = $case_task_data['task_status'] ?? 'PENDING';
                                            echo $status == 'COMPLETED' ? 'success' : ($status == 'VERIFICATION_COMPLETED' ? 'primary' : ($status == 'IN_PROGRESS' ? 'info' : 'warning'));
                                        ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Form Card -->
                    <div class="card border-success mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list"></i> Step 1: Add Verification Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Instructions:</strong> Add your verification remarks and upload supporting documents. 
                                You can save multiple times before marking verification as complete.
                            </div>
                            
                            <form id="verifierSubmitForm" method="POST" action="save_verifier_submission.php" enctype="multipart/form-data">
                                <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
                                <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-comment-alt text-primary"></i> 
                                        <strong>Verifier Remarks / Findings</strong>
                                    </label>
                                    <textarea name="verifier_remarks" id="verifier_remarks" class="form-control" rows="6" placeholder="Enter your verification remarks, findings, observations, or any notes about this task..."><?php echo htmlspecialchars($existing_remarks); ?></textarea>
                                    <small class="text-muted">
                                        <i class="fas fa-lightbulb"></i> You can add or update remarks at any time before completing verification.
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-paperclip text-primary"></i> 
                                        <strong>Upload Supporting Documents</strong>
                                        <span class="badge bg-info">Multiple files allowed</span>
                                    </label>
                                    <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept="*/*">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> You can upload multiple files of any type (PDF, images, documents, etc.)
                                    </small>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="fas fa-save"></i> Save Remarks & Files
                                    </button>
                                    <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Complete Verification Card -->
                    <?php 
                    // Show complete button if status is IN_PROGRESS and user has access
                    // $is_assigned already includes check for assigned user OR ADMIN/DEV
                    if ($current_status == 'IN_PROGRESS' && $is_assigned):
                    ?>
                        <div class="card border-warning mb-4">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">
                                    <i class="fas fa-check-circle"></i> Step 2: Complete Verification
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Important:</strong> Once you mark verification as complete, the task will move to review stage. 
                                    Make sure you have added all necessary remarks and attachments.
                                </div>
                                <button type="button" class="btn btn-success btn-lg" id="completeVerificationBtn">
                                    <i class="fas fa-check-circle"></i> Mark Verification Complete
                                </button>
                                <small class="d-block text-muted mt-2">
                                    <i class="fas fa-info-circle"></i> After completing, the task will be available for review.
                                </small>
                            </div>
                        </div>
                    <?php elseif ($current_status == 'VERIFICATION_COMPLETED'): ?>
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-check-circle"></i> Verification Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <strong>Verification Completed!</strong> 
                                    <p class="mb-0 mt-2">This task has been verified and is now ready for review.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Existing Attachments Card -->
                    <div class="card border-info">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-paperclip text-info"></i> Uploaded Attachments
                                <?php if (!empty($attachments)): ?>
                                    <span class="badge bg-info"><?php echo count($attachments); ?> file(s)</span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attachments)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p class="mb-0">No attachments uploaded yet. Use the form above to upload files.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">#</th>
                                                <th><i class="fas fa-file"></i> File Name</th>
                                                <th><i class="fas fa-tag"></i> Type</th>
                                                <th><i class="fas fa-calendar"></i> Uploaded On</th>
                                                <th width="150"><i class="fas fa-cog"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attachments as $index => $attachment): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <i class="fas fa-file text-primary"></i> 
                                                        <strong><?php echo htmlspecialchars($attachment['file_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($attachment['file_type'] ?? 'Unknown'); ?></span>
                                                    </td>
                                                    <td>
                                                        <small><?php echo $attachment['created_at'] ? date('d M Y, h:i A', strtotime($attachment['created_at'])) : 'N/A'; ?></small>
                                                    </td>
                                                    <td>
                                                        <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-sm btn-info" download title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteAttachment(<?php echo $attachment['id']; ?>)" title="Delete">
                                                            <i class="fas fa-trash"></i>
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
    </div>
</div>

<?php require_once('../system/footer.php'); ?>


<script>
$(document).ready(function() {
    $('#verifierSubmitForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#submitBtn');
        var originalBtnHtml = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        var formData = new FormData(this);
        formData.append('action', 'save_verifier_submission');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalBtnHtml);
                
                if (response.success) {
                    alert(response.message || 'Remarks and files saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to save submission'));
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalBtnHtml);
                console.error('Error:', error, xhr.responseText);
                alert('Error saving submission. Please try again.');
            }
        });
    });
    
    // Mark verification as complete
    $('#completeVerificationBtn').click(function() {
        if (confirm('Are you sure you want to mark this verification as complete? This will move the task to review stage.')) {
            var btn = $(this);
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Completing...');
            
            $.ajax({
                url: 'save_verifier_submission.php',
                type: 'POST',
                data: {
                    action: 'complete_verification',
                    case_task_id: <?php echo $case_task_id; ?>
                },
                dataType: 'json',
                success: function(response) {
                    btn.prop('disabled', false).html(originalHtml);
                    
                    if (response.success) {
                        alert(response.message || 'Verification marked as complete!');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to complete verification'));
                    }
                },
                error: function(xhr, status, error) {
                    btn.prop('disabled', false).html(originalHtml);
                    console.error('Error:', error, xhr.responseText);
                    alert('Error completing verification. Please try again.');
                }
            });
        }
    });
});

function deleteAttachment(attachmentId) {
    if (confirm('Are you sure you want to delete this attachment?')) {
        $.ajax({
            url: 'save_verifier_submission.php',
            type: 'POST',
            data: {
                action: 'delete_attachment',
                attachment_id: attachmentId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Attachment deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to delete attachment'));
                }
            },
            error: function() {
                alert('Error deleting attachment. Please try again.');
            }
        });
    }
}
</script>
