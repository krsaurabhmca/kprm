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
?>

<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-check text-warning me-2"></i>
                    <strong>Task Review: <?php echo htmlspecialchars($task_name); ?></strong>
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
            <!-- Left Column: Review Information -->
            <div class="col-lg-7 mb-3">
                <!-- Verifier Remarks -->
                <?php if (!empty($verifier_remarks)): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-comment-alt text-success me-2"></i>Verifier Remarks
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($verifier_remarks)); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Review Form -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-clipboard-check me-2"></i>Review & Complete
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="reviewForm" method="POST" action="save_task_review.php">
                            <input type="hidden" name="case_task_id" value="<?php echo $case_task_id; ?>">
                            <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                            <input type="hidden" name="task_template_id" value="<?php echo $task_template_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Review Status <span class="text-danger">*</span></label>
                                <select name="review_status" id="review_status" class="form-select" required onchange="generateRemarks()">
                                    <option value="">-- Select Status --</option>
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
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Review Remarks</label>
                                <textarea name="review_remarks" id="review_remarks" class="form-control" rows="6" placeholder="Review remarks will be generated automatically based on selected status. You can edit as needed..."><?php echo htmlspecialchars($review_remarks); ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-1"></i>Save Review & Complete Task
                                </button>
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
                            <i class="fas fa-paperclip text-info me-2"></i>Attachments
                            <?php if (!empty($attachments)): ?>
                                <span class="badge bg-info ms-2"><?php echo count($attachments); ?></span>
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attachments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0 small">No attachments</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-2">
                                <label class="form-check-label">
                                    <input type="checkbox" id="selectAllAttachments" class="form-check-input me-2">
                                    <small>Select All</small>
                                </label>
                            </div>
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
                                    $is_selected = ($attachment['display_in_report'] ?? 'NO') == 'YES';
                                    ?>
                                    <div class="list-group-item <?php echo $is_selected ? 'border-success' : ''; ?>">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="attachment-checkbox form-check-input" name="attachment_ids[]" value="<?php echo $attachment['id']; ?>" 
                                                id="att_<?php echo $attachment['id']; ?>" <?php echo $is_selected ? 'checked' : ''; ?>>
                                            <label class="form-check-label d-flex align-items-center w-100" for="att_<?php echo $attachment['id']; ?>">
                                                <i class="fas <?php echo $icon_class; ?> <?php echo $icon_color; ?> me-2"></i>
                                                <span class="flex-grow-1 text-truncate" title="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                </span>
                                                <?php if ($is_selected): ?>
                                                    <span class="badge bg-success ms-2"><i class="fas fa-check"></i></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewFile('<?php echo htmlspecialchars($attachment['file_url']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', '<?php echo htmlspecialchars($attachment['file_type'] ?? ''); ?>')">
                                                <i class="fas fa-eye me-1"></i>View
                                            </button>
                                            <a href="../upload/<?php echo htmlspecialchars($attachment['file_url']); ?>" target="_blank" class="btn btn-sm btn-outline-success" download>
                                                <i class="fas fa-download me-1"></i>Download
                                            </a>
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
// File Preview Function
function previewFile(fileUrl, fileName, fileType) {
    var previewUrl = 'file_preview.php?file=' + encodeURIComponent(fileUrl) + '&name=' + encodeURIComponent(fileName);
    var popup = window.open(previewUrl, 'filePreview', 'width=1200,height=800,resizable=yes,scrollbars=yes,toolbar=no,location=no,menubar=no,status=no');
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
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
        
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
