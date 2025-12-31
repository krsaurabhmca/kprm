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
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-check text-primary me-2"></i>
                    <strong>Task Verification: <?php echo htmlspecialchars($task_name); ?></strong>
                </h4>
                <small class="text-muted">Case: <?php echo htmlspecialchars($case_data['application_no'] ?? 'N/A'); ?></small>
            </div>
            <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Left Column: Verification Form -->
            <div class="col-lg-7 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-clipboard-list me-2"></i>Add Verification Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="verifierSubmitForm" method="POST" action="save_verifier_submission.php" enctype="multipart/form-data">
                            <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
                            <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Verifier Remarks / Findings</label>
                                <textarea name="verifier_remarks" id="verifier_remarks" class="form-control" rows="5" placeholder="Enter your verification remarks, findings, observations..."><?php echo htmlspecialchars($existing_remarks); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Upload Documents</label>
                                <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept="*/*">
                                <small class="text-muted">Multiple files allowed</small>
                            </div>

                            <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary btn-md" id="submitBtn">
        <i class="fas fa-save me-1"></i>Upload
    </button>

    <?php if ($current_status == 'IN_PROGRESS' && $is_assigned): ?>
        <button type="button" class="btn btn-success" id="completeVerificationBtn">
            <i class="fas fa-check-circle me-1"></i>Complete Verification
        </button>
    <?php elseif ($current_status == 'VERIFICATION_COMPLETED'): ?>
        <div class="alert alert-success mb-0 py-2 px-3 d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>Verification Completed
        </div>
    <?php endif; ?>
</div>

                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Attachments -->
            <div class="col-lg-5 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-paperclip text-info me-2"></i>Uploaded Files
                            <?php if (!empty($attachments)): ?>
                                <span class="badge bg-info ms-2"><?php echo count($attachments); ?></span>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attachments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0 small">No files uploaded</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($attachments as $attachment): ?>
                                    <?php
                                    $file_ext = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                                    $icon_class = 'fa-file';
                                    $icon_color = 'text-primary';
                                    
                                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                                        $icon_class = 'fa-image';
                                        $icon_color = 'text-success';
                                    } elseif (in_array($file_ext, ['pdf'])) {
                                        $icon_class = 'fa-file-pdf';
                                        $icon_color = 'text-danger';
                                    }
                                    ?>
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas <?php echo $icon_class; ?> <?php echo $icon_color; ?> me-2"></i>
                                            <span class="flex-grow-1 text-truncate" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                                <?php echo htmlspecialchars($attachment['file_name']); ?>
                                            </span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download me-1"></i>Download
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteAttachment(<?php echo $attachment['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once('../system/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#verifierSubmitForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#submitBtn');
        var originalBtnHtml = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
        
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
                    alert(response.message || 'Saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to save'));
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalBtnHtml);
                console.error('Error:', error, xhr.responseText);
                alert('Error saving. Please try again.');
            }
        });
    });
    
    // Mark verification as complete
    $('#completeVerificationBtn').click(function() {
        if (confirm('Mark verification as complete? This will move the task to review stage.')) {
            var btn = $(this);
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Completing...');
            
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
                        alert(response.message || 'Verification completed!');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to complete'));
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
    if (confirm('Delete this attachment?')) {
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
                    alert('Deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to delete'));
                }
            },
            error: function() {
                alert('Error deleting. Please try again.');
            }
        });
    }
}
</script>
