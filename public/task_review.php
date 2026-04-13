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

// Replace status words in review remarks with client-defined status words
if (!empty($review_remarks)) {
    // Replace database status words with client status words in remarks
    // Replace "Positive" with client's positive status word
    $review_remarks = str_replace('Positive', $positive_status, $review_remarks);
    $review_remarks = str_replace('positive', strtolower($positive_status), $review_remarks);
    
    // Replace "Negative" with client's negative status word
    $review_remarks = str_replace('Negative', $negative_status, $review_remarks);
    $review_remarks = str_replace('negative', strtolower($negative_status), $review_remarks);
    
    // Replace "CNV" with client's CNV status word
    $review_remarks = str_replace('CNV', $cnv_status, $review_remarks);
    $review_remarks = str_replace('cnv', strtolower($cnv_status), $review_remarks);
    
    // Also use case-insensitive replacement to catch all variations
    $db_status_words = ['Positive', 'Negative', 'CNV'];
    $client_status_words = [$positive_status, $negative_status, $cnv_status];
    for ($i = 0; $i < count($db_status_words); $i++) {
        $review_remarks = str_ireplace($db_status_words[$i], $client_status_words[$i], $review_remarks);
    }
}
?>
<link rel="stylesheet" href="../system/css/json_table.css">
<script src="../system/js/json_table.js"></script>

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
            <!-- Left Column: Review Information (3/4 Screen) -->
            <div class="col-lg-9 mb-3">
                <!-- Task Details -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2"></i>Task Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-0">Task Name</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($task_name); ?></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-0">Task Type</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($task_template_data['task_type'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small mb-0">Current Status</label>
                                <div>
                                    <?php
                                    $status_display = get_task_status_display($current_status, $task_data_json);
                                    $status_badges = [
                                        'Pending' => 'warning',
                                        'Assigned' => 'info',
                                        'Verified' => 'primary',
                                        'Reviewed' => 'warning',
                                        'Closed' => 'success'
                                    ];
                                    $badge_color = $status_badges[$status_display] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_color; ?>"><?php echo htmlspecialchars($status_display); ?></span>
                                    <?php 
                                    $task_type = $task_template_data['task_type'] ?? '';
                                    if (strtoupper($task_type) == 'ITO' && stripos($task_name, 'Financial') !== false): 
                                    ?>
                                        <a href="financial_add.php?case_task_id=<?php echo $case_task_id; ?>" class="btn btn-sm btn-outline-info ms-2 py-0" style="font-size: 0.75rem;">
                                            <i class="fas fa-file-invoice me-1"></i> Financial Entry
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Meta Fields -->
                        <?php
                        // Get task meta fields
                        $task_meta_fields = get_all('tasks_meta', '*', ['task_id' => $task_template_id, 'status' => 'ACTIVE'], 'id ASC');
                        if ($task_meta_fields['count'] > 0):
                        ?>
                            <hr class="my-3">
                            <h6 class="mb-2 text-muted small">
                                <i class="fas fa-list-ul me-1"></i> Task Information
                            </h6>
                            <div class="row">
                                <?php
                                foreach ($task_meta_fields['data'] as $field) {
                                    $field_value = isset($task_data_json[$field['field_name']]) ? $task_data_json[$field['field_name']] : '';
                                    $is_table = (is_array($field_value) || (is_string($field_value) && (strpos($field_value, '[{"section"') === 0 || strpos($field_value, '{"') === 0)));
                                    ?>
                                    <div class="<?php echo $is_table ? 'col-12' : 'col-md-6'; ?> mb-3">
                                        <label class="form-label text-muted small mb-0">
                                            <?php echo htmlspecialchars($field['display_name'] ?? $field['field_name']); ?>
                                        </label>
                                        <div class="field-value p-2 bg-light border rounded">
                                            <?php if($is_table): ?>
                                                <?php 
                                                    $field_id = str_replace(' ', '_', $field['field_name']);
                                                    // Ensure we have a clean array for JS
                                                    $clean_data = is_array($field_value) ? $field_value : json_decode($field_value, true);
                                                    if (!$clean_data) $clean_data = null;
                                                ?>
                                                <div id="json_table_container_<?php echo $field_id; ?>"></div>
                                                <input type="hidden" name="task_meta[<?php echo $field['field_name']; ?>]" id="json_table_input_<?php echo $field_id; ?>" value="<?php echo htmlspecialchars(is_array($field_value) ? json_encode($field_value) : $field_value); ?>">
                                                <script>
                                                    (function() {
                                                        const fieldId = <?php echo json_encode($field_id); ?>;
                                                        const config = <?php echo !empty($field['default_value']) ? $field['default_value'] : '{}'; ?>;
                                                        const existingData = <?php echo json_encode($clean_data); ?>;
                                                        
                                                        const initFunc = function() {
                                                            if (typeof initJsonTable === 'function') {
                                                                initJsonTable(fieldId, config, existingData);
                                                            } else {
                                                                setTimeout(initFunc, 100);
                                                            }
                                                        };
                                                        
                                                        if (document.readyState === 'complete' || document.readyState === 'interactive') {
                                                            initFunc();
                                                        } else {
                                                            document.addEventListener('DOMContentLoaded', initFunc);
                                                        }
                                                    })();
                                                </script>
                                            <?php else: ?>
                                                <?php echo render_financial_table_readonly($field_value); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-2">
                                <small class="text-muted">No additional task fields configured</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Verifier Remarks -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-comment-alt text-success me-2"></i>Verifier Remarks
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="pasteImageBtn" title="Paste Image from Clipboard">
                            <i class="fas fa-paste me-1"></i> Paste Image
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($verifier_remarks)): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($verifier_remarks)); ?></p>
                        <?php else: ?>
                            <p class="text-muted mb-0"><em>No verifier remarks</em></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Review Form -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark py-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-clipboard-check me-2"></i>Review & Complete
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="reviewForm" method="POST" action=".php">
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

            <!-- Right Column: Attachments (1/4 Screen) -->
            <div class="col-lg-3 mb-3">
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
                                            <?php 
                                            // Handle absolute URLs correctly
                                            $is_external_att = (strpos($attachment['file_url'], 'http://') === 0 || strpos($attachment['file_url'], 'https://') === 0);
                                            $full_att_url = $is_external_att ? $attachment['file_url'] : '../upload/' . $attachment['file_url'];
                                            ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="previewFile('<?php echo htmlspecialchars($attachment['file_url']); ?>', '<?php echo htmlspecialchars($attachment['file_name']); ?>', '<?php echo htmlspecialchars($attachment['file_type'] ?? ''); ?>')">
                                                <i class="fas fa-eye me-1"></i>View
                                             </button>
                                             <a href="<?php echo htmlspecialchars($full_att_url); ?>" target="_blank" class="btn btn-sm btn-outline-success" download>
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
var taskType = <?php echo json_encode(strtoupper($task_template_data['task_type'] ?? '')); ?>;
var caseTaskId = <?php echo $case_task_id; ?>;
// Client-defined status words for #status# replacement
var clientStatusWords = {
    'POSITIVE': <?php echo json_encode($positive_status); ?>,
    'NEGATIVE': <?php echo json_encode($negative_status); ?>,
    'CNV': <?php echo json_encode($cnv_status); ?>
};

function generateRemarks() {
    var status = $('#review_status').val();
    if (!status) {
        return;
    }
    
    // For PHYSICAL task types, use AI generation
    if (taskType === 'PHYSICAL') {
        generateAIRemarks(status);
    } else {
        // For other task types, use template replacement
        if (!taskTemplates[status]) {
            return;
        }
        
        var template = taskTemplates[status];
        var remarks = template;
        
        // Replace #status# FIRST with client-defined status word based on selected review status
        // This must be done before the loop to prevent taskData.status from overriding it
        var statusWord = clientStatusWords[status] || taskData.status || '';
        remarks = remarks.replace(/#status#/gi, statusWord);
        
        // Replace variables in template with actual values (skip 'status' as it's already handled)
        for (var key in taskData) {
            if (taskData.hasOwnProperty(key) && typeof taskData[key] === 'string' && key !== 'status') {
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
        
        // Replace any hardcoded status words (Positive, Negative, CNV) with client status words
        // This handles cases where the template has "Status - Positive" instead of "Status - #status#"
        // Only replace the status word that matches the selected review status
        if (statusWord) {
            var dbStatusMap = {
                'POSITIVE': 'Positive',
                'NEGATIVE': 'Negative',
                'CNV': 'CNV'
            };
            var dbStatusWord = dbStatusMap[status];
            if (dbStatusWord && dbStatusWord !== statusWord) {
                // Use word boundary regex to avoid partial replacements (e.g., "Positive" in "Positively")
                var regex = new RegExp('\\b' + dbStatusWord + '\\b', 'gi');
                remarks = remarks.replace(regex, statusWord);
            }
        }
        
        // Also replace all status words if they appear in the template (for comprehensive coverage)
        // This ensures any hardcoded status words are replaced with client words
        remarks = remarks.replace(/\bPositive\b/gi, clientStatusWords['POSITIVE'] || 'Positive');
        remarks = remarks.replace(/\bNegative\b/gi, clientStatusWords['NEGATIVE'] || 'Negative');
        remarks = remarks.replace(/\bCNV\b/gi, clientStatusWords['CNV'] || 'CNV');
        
        $('#review_remarks').val(remarks);
    }
}

function generateAIRemarks(status) {
    // Show loading state
    var remarksTextarea = $('#review_remarks');
    var originalValue = remarksTextarea.val();
    remarksTextarea.prop('disabled', true).val('Generating AI remarks... Please wait...');
    
    $.ajax({
        url: 'save_task_review.php',
        type: 'POST',
        data: {
            action: 'generate_ai_remarks',
            case_task_id: caseTaskId,
            review_status: status
        },
        dataType: 'json',
        success: function(response) {
            remarksTextarea.prop('disabled', false);
            
            if (response.success && response.remarks) {
                var aiRemarks = response.remarks;
                
                // Replace any hardcoded status words (Positive, Negative, CNV) with client status words
                // This handles cases where AI-generated remarks contain database status words
                var statusWord = clientStatusWords[status] || '';
                if (statusWord) {
                    // Replace database status words with client status words
                    aiRemarks = aiRemarks.replace(/\bPositive\b/gi, clientStatusWords['POSITIVE'] || 'Positive');
                    aiRemarks = aiRemarks.replace(/\bNegative\b/gi, clientStatusWords['NEGATIVE'] || 'Negative');
                    aiRemarks = aiRemarks.replace(/\bCNV\b/gi, clientStatusWords['CNV'] || 'CNV');
                    
                    // Also replace the specific status word for the selected status
                    var dbStatusMap = {
                        'POSITIVE': 'Positive',
                        'NEGATIVE': 'Negative',
                        'CNV': 'CNV'
                    };
                    var dbStatusWord = dbStatusMap[status];
                    if (dbStatusWord && dbStatusWord !== statusWord) {
                        var regex = new RegExp('\\b' + dbStatusWord + '\\b', 'gi');
                        aiRemarks = aiRemarks.replace(regex, statusWord);
                    }
                }
                
                remarksTextarea.val(aiRemarks);
            } else {
                remarksTextarea.val(originalValue);
                alert('Error: ' + (response.message || 'Failed to generate AI remarks. Please try again.'));
            }
        },
        error: function(xhr, status, error) {
            remarksTextarea.prop('disabled', false).val(originalValue);
            console.error('Error:', error, xhr.responseText);
            alert('Error generating AI remarks. Please try again.');
        }
    });
}

// Select all attachments checkbox
$('#selectAllAttachments').change(function() {
    $('.attachment-checkbox').prop('checked', $(this).prop('checked'));
});

// Paste Image from Clipboard
$('#pasteImageBtn').on('click', function() {
    // Create a temporary input element to capture paste
    var pasteArea = $('<textarea>').css({
        position: 'fixed',
        left: '-9999px',
        top: '0px'
    }).appendTo('body').focus();
    
    // Listen for paste event
    $(document).one('paste', function(e) {
        e.preventDefault();
        var clipboardData = e.originalEvent.clipboardData || window.clipboardData;
        var items = clipboardData.items;
        
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                var blob = items[i].getAsFile();
                var reader = new FileReader();
                
                reader.onload = function(event) {
                    var imageData = event.target.result;
                    uploadPastedImage(imageData);
                };
                
                reader.readAsDataURL(blob);
                break;
            }
        }
        
        pasteArea.remove();
    });
    
    // Show instruction
    alert('Press Ctrl+V (or Cmd+V on Mac) to paste an image from your clipboard.');
});

function uploadPastedImage(imageData) {
    // Convert data URL to blob
    var blob = dataURLtoBlob(imageData);
    var formData = new FormData();
    formData.append('action', 'paste_image');
    formData.append('case_task_id', <?php echo $case_task_id; ?>);
    formData.append('case_id', <?php echo $case_id; ?>);
    formData.append('image', blob, 'pasted_image_' + Date.now() + '.png');
    
    // Show loading
    $('#pasteImageBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Uploading...');
    
    $.ajax({
        url: 'save_task_review.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            $('#pasteImageBtn').prop('disabled', false).html('<i class="fas fa-paste me-1"></i> Paste Image');
            
            if (response.success) {
                alert('Image pasted and attached successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to upload image'));
            }
        },
        error: function(xhr, status, error) {
            $('#pasteImageBtn').prop('disabled', false).html('<i class="fas fa-paste me-1"></i> Paste Image');
            console.error('Error:', error, xhr.responseText);
            alert('Error uploading image. Please try again.');
        }
    });
}

function dataURLtoBlob(dataurl) {
    var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
    while(n--){
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new Blob([u8arr], {type:mime});
}

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
                    // Auto-redirect after successful save
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.href = 'view_case.php?case_id=<?php echo $case_id; ?>';
                    }
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
