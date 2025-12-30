<?php
/**
 * KPRM - Task Review
 * Review task, verifier remarks, attachments, and generate final report
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
$task_template_id = $case_task_data['task_template_id'];
$current_status = $case_task_data['task_status'] ?? 'PENDING';

// Only allow review if verification is completed
if ($current_status != 'VERIFICATION_COMPLETED' && $current_status != 'COMPLETED') {
    $_SESSION['error_message'] = 'Task verification must be completed before review. Current status: ' . $current_status;
    header('Location: view_case.php?case_id=' . $case_id);
    exit;
}

// Get case and client info
$case_info = get_data('cases', $case_id);
$case_data = $case_info['count'] > 0 ? $case_info['data'] : null;
$client_id = $case_data['client_id'] ?? 0;

// Get client info for status options
$client_info = get_data('clients', $client_id);
$client_data = $client_info['count'] > 0 ? $client_info['data'] : null;
$positive_status = $client_data['positve_status'] ?? 'Positive';
$negative_status = $client_data['negative_status'] ?? 'Negative';
$cnv_status = $client_data['cnv_status'] ?? 'CNV';

// Get task template
$task_template = get_data('tasks', $task_template_id);
$task_name = $task_template['count'] > 0 ? $task_template['data']['task_name'] : 'Unknown Task';
$task_template_data = $task_template['count'] > 0 ? $task_template['data'] : [];

// Get task data JSON
$task_data_json = json_decode($case_task_data['task_data'] ?? '{}', true);
if (!is_array($task_data_json)) {
    $task_data_json = [];
}

// Get verifier remarks
$verifier_remarks = $task_data_json['verifier_remarks'] ?? '';

// Get existing attachments
$attachments = [];
global $con;
$attachments_sql = "
    SELECT id, file_type, file_name, file_url, display_in_report, created_at
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

// Get current review status and remarks
$review_status = $task_data_json['review_status'] ?? '';
$review_remarks = $task_data_json['review_remarks'] ?? '';

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
                        <i class="fas fa-tasks text-primary"></i> Task Review Workflow
                    </h5>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded bg-light">
                                <i class="fas fa-clock fa-2x mb-2 text-muted"></i>
                                <p class="mb-0"><strong>1. Pending</strong></p>
                                <small class="text-muted">Task Created</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded bg-light">
                                <i class="fas fa-user-check fa-2x mb-2 text-muted"></i>
                                <p class="mb-0"><strong>2. In Progress</strong></p>
                                <small class="text-muted">Verification</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded bg-primary text-white">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0"><strong>3. Verified</strong></p>
                                <small>Ready for Review</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 rounded <?php echo $current_status == 'COMPLETED' ? 'bg-success text-white' : 'bg-light'; ?>">
                                <i class="fas fa-clipboard-check fa-2x mb-2 <?php echo $current_status == 'COMPLETED' ? '' : 'text-muted'; ?>"></i>
                                <p class="mb-0"><strong>4. Completed</strong></p>
                                <small <?php echo $current_status == 'COMPLETED' ? '' : 'class="text-muted"'; ?>>Review Done</small>
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
                <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0 text-dark">
                        <i class="fas fa-clipboard-check "></i> Task Review: <?php echo htmlspecialchars($task_name); ?>
                    </h4>
                    <div class="card-tools">
                        <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-light btn-sm">
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

                    <!-- Task Fields Card -->
                    <div class="card border-info mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-list text-info"></i> Task Data Fields
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $task_meta_fields = get_all('tasks_meta', '*', ['task_id' => $task_template_id, 'status' => 'ACTIVE'], 'id ASC');
                            if ($task_meta_fields['count'] > 0):
                            ?>
                                <div class="row">
                                    <?php foreach ($task_meta_fields['data'] as $field): ?>
                                        <?php
                                        $field_value = isset($task_data_json[$field['field_name']]) ? $task_data_json[$field['field_name']] : '';
                                        if (!empty($field_value)):
                                        ?>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label text-muted">
                                                    <small><?php echo htmlspecialchars($field['display_name']); ?></small>
                                                </label>
                                                <div class="form-control-plaintext bg-light p-3 rounded border">
                                                    <strong><?php echo htmlspecialchars($field_value); ?></strong>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No task fields configured.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Verifier Remarks Card -->
                    <?php if (!empty($verifier_remarks)): ?>
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-comment-alt"></i> Verifier Remarks & Findings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light border">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($verifier_remarks)); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Attachments Card -->
                    <?php if (!empty($attachments)): ?>
                        <div class="card border-info mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-paperclip text-info"></i> Verifier Attachments
                                    <span class="badge bg-info"><?php echo count($attachments); ?> file(s)</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Select attachments</strong> that should be included in the final report by checking the boxes below.
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="selectAllAttachments" title="Select All" class="form-check-input">
                                                </th>
                                                <th><i class="fas fa-file"></i> File Name</th>
                                                <th><i class="fas fa-tag"></i> Type</th>
                                                <th><i class="fas fa-calendar"></i> Uploaded On</th>
                                                <th><i class="fas fa-check-circle"></i> In Report</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attachments as $attachment): ?>
                                                <tr class="<?php echo ($attachment['display_in_report'] ?? 'NO') == 'YES' ? 'table-success' : ''; ?>">
                                                    <td>
                                                        <input type="checkbox" class="attachment-checkbox form-check-input" name="attachment_ids[]" value="<?php echo $attachment['id']; ?>" 
                                                            <?php echo ($attachment['display_in_report'] ?? 'NO') == 'YES' ? 'checked' : ''; ?>>
                                                    </td>
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
                                                        <?php if (($attachment['display_in_report'] ?? 'NO') == 'YES'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check"></i> YES
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">NO</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" onclick="previewFile('<?php echo htmlspecialchars($attachment['file_url']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', '<?php echo htmlspecialchars($attachment['file_type'] ?? ''); ?>')" title="Preview File">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-sm btn-success" download title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Review Form Card -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-check"></i> Final Review & Report Generation
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Review Process:</strong> Select the review status to auto-generate the report. 
                                You can edit the generated remarks before saving. Once saved, the task will be marked as completed.
                            </div>
                            
                            <form id="reviewForm" method="POST" action="save_task_review.php">
                                <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
                                <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                                <input type="hidden" name="task_template_id" value="<?php echo $task_template_id; ?>">
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <i class="fas fa-flag text-primary"></i> 
                                            <strong>Review Status</strong> 
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select name="review_status" id="review_status" class="form-select form-select-lg" required onchange="generateRemarks()">
                                            <option value="">-- Select Review Status --</option>
                                            <option value="POSITIVE" <?php echo $review_status == 'POSITIVE' ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($positive_status); ?>
                                            </option>
                                            <option value="NEGATIVE" <?php echo $review_status == 'NEGATIVE' ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($negative_status); ?>
                                            </option>
                                            <option value="CNV" <?php echo $review_status == 'CNV' ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cnv_status); ?>
                                            </option>
                                        </select>
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb"></i> Select status to auto-generate report remarks from template.
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-file-alt text-primary"></i> 
                                        <strong>Final Report / Review Remarks</strong>
                                    </label>
                                    <textarea name="review_remarks" id="review_remarks" class="form-control" rows="6" placeholder="Review remarks will be generated automatically based on selected status. You can edit as needed..."><?php echo htmlspecialchars($review_remarks); ?></textarea>
                                    <small class="text-muted">
                                        <i class="fas fa-edit"></i> The report will be auto-generated when you select a status. You can edit it before saving.
                                    </small>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="fas fa-save"></i> Save Review & Complete Task
                                    </button>
                                    <a href="view_case.php?case_id=<?php echo $case_id; ?>" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../system/footer.php'); ?>

<script>
// File Preview Function - Opens in resizable, movable window popup
function previewFile(fileUrl, fileName, fileType) {
    var previewUrl = 'file_preview.php?file=' + encodeURIComponent(fileUrl) + '&name=' + encodeURIComponent(fileName);
    
    // Open in a resizable, movable popup window
    var popup = window.open(
        previewUrl,
        'filePreview',
        'width=1200,height=800,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no'
    );
    
    // Focus the popup window
    if (popup) {
        popup.focus();
    } else {
        alert('Please allow popups for this site to view files.');
    }
}

// Store task data for template replacement
var taskData = <?php echo json_encode($task_data_json); ?>;
var taskTemplates = {
    'POSITIVE': <?php echo json_encode($task_template_data['positive_format'] ?? ''); ?>,
    'NEGATIVE': <?php echo json_encode($task_template_data['negative_format'] ?? ''); ?>,
    'CNV': <?php echo json_encode($task_template_data['cnv_format'] ?? ''); ?>
};

function generateRemarks() {
    var status = $('#review_status').val();
    if (!status || !taskTemplates[status]) {
        return;
    }
    
    var template = taskTemplates[status];
    var remarks = template;
    
    // Replace variables in template with actual values
    for (var key in taskData) {
        if (taskData.hasOwnProperty(key) && typeof taskData[key] === 'string') {
            var regex = new RegExp('#' + key + '#', 'gi');
            remarks = remarks.replace(regex, taskData[key]);
        }
    }
    
    // Replace common variables
    remarks = remarks.replace(/#applicant_name#/gi, taskData.applicant_name || '');
    remarks = remarks.replace(/#address#/gi, taskData.address || '');
    remarks = remarks.replace(/#met_with#/gi, taskData.met_with || '');
    remarks = remarks.replace(/#time_period#/gi, taskData.time_period || '');
    remarks = remarks.replace(/#ownership#/gi, taskData.ownership || '');
    remarks = remarks.replace(/#family#/gi, taskData.family || '');
    remarks = remarks.replace(/#area#/gi, taskData.area || '');
    remarks = remarks.replace(/#locality#/gi, taskData.locality || '');
    remarks = remarks.replace(/#tpc#/gi, taskData.tpc || '');
    remarks = remarks.replace(/#data#/gi, taskData.data || '');
    
    $('#review_remarks').val(remarks);
}

// Select all attachments checkbox
$('#selectAllAttachments').change(function() {
    $('.attachment-checkbox').prop('checked', $(this).prop('checked'));
});

$(document).ready(function() {
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#submitBtn');
        var originalBtnHtml = submitBtn.html();
        
        // Collect selected attachment IDs
        var selectedAttachments = [];
        $('.attachment-checkbox:checked').each(function() {
            selectedAttachments.push($(this).val());
        });
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        var formData = $(this).serialize();
        formData += '&attachment_ids=' + selectedAttachments.join(',');
        formData += '&action=save_review';
        
        $.ajax({
            url: 'save_task_review.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalBtnHtml);
                
                if (response.success) {
                    alert(response.message || 'Review saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to save review'));
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalBtnHtml);
                console.error('Error:', error, xhr.responseText);
                alert('Error saving review. Please try again.');
            }
        });
    });
    
    // Generate remarks on page load if status is already selected
    if ($('#review_status').val()) {
        generateRemarks();
    }
});
</script>

