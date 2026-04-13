<?php
/**
 * KPRM - Attachment Management
 * Manage all attachments with type, size, date, case, task info
 * Supports bulk delete and download, date range filter, and search
 */

require_once('../system/all_header.php');

// Get filters
$filter_case = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
$filter_task = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$filter_type = isset($_GET['file_type']) ? trim($_GET['file_type']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$page_title = "Attachment Management";
?>
<main class="content">
    <div class="container-fluid py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="fas fa-folder-open text-primary me-2"></i>
                <strong><?php echo $page_title; ?></strong>
            </h4>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filters & Search
                </h6>
            </div>
            <div class="card-body p-3">
                <form method="GET" action="" id="filterForm">
                    <!-- Search Bar -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" 
                                       name="search" 
                                       class="form-control" 
                                       placeholder="Search by file name, case number, task name..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Row 1 -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Case</label>
                            <select name="case_id" class="form-select form-select-sm">
                                <option value="0">All Cases</option>
                                <?php
                                $cases_query = "SELECT id, application_no FROM cases WHERE status = 'ACTIVE' ORDER BY id DESC LIMIT 100";
                                $cases_result = mysqli_query($con, $cases_query);
                                if ($cases_result) {
                                    while ($case_row = mysqli_fetch_assoc($cases_result)) {
                                        $selected = ($filter_case == $case_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $case_row['id'] . '" ' . $selected . '>' . htmlspecialchars($case_row['application_no']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">File Type</label>
                            <select name="file_type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <option value="image" <?php echo $filter_type == 'image' ? 'selected' : ''; ?>>Images</option>
                                <option value="pdf" <?php echo $filter_type == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                                <option value="document" <?php echo $filter_type == 'document' ? 'selected' : ''; ?>>Documents</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Date From</label>
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control form-control-sm" 
                                   value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Date To</label>
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control form-control-sm" 
                                   value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row g-3">
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                            <a href="document_manage.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php
                            // Show active filter count
                            $filter_count = 0;
                            if ($filter_case > 0) $filter_count++;
                            if ($filter_type != '') $filter_count++;
                            if ($filter_date_from != '') $filter_count++;
                            if ($filter_date_to != '') $filter_count++;
                            if ($search_query != '') $filter_count++;
                            
                            if ($filter_count > 0) {
                                echo '<small class="text-muted"><i class="fas fa-info-circle me-1"></i>' . $filter_count . ' filter(s) active</small>';
                            }
                            ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="card shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" id="selectAllDocs" class="form-check-input me-2">
                        <label for="selectAllDocs" class="form-check-label">Select All</label>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-success" id="bulkDownloadBtn" disabled>
                            <i class="fas fa-download me-1"></i> Download Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled>
                            <i class="fas fa-trash me-1"></i> Delete Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAllHeader" class="form-check-input">
                                </th>
                                <th>File Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Case</th>
                                <th>Task</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build query
                            $where_conditions = ["a.status = 'ACTIVE'"];
                            
                            if ($filter_case > 0) {
                                $where_conditions[] = "ct.case_id = '$filter_case'";
                            }
                            
                            if ($filter_task > 0) {
                                $where_conditions[] = "a.task_id = '$filter_task'";
                            }
                            
                            if ($filter_type == 'image') {
                                $where_conditions[] = "a.file_type LIKE 'image%'";
                            } elseif ($filter_type == 'pdf') {
                                $where_conditions[] = "a.file_type LIKE '%pdf%'";
                            } elseif ($filter_type == 'document') {
                                $where_conditions[] = "a.file_type NOT LIKE 'image%' AND a.file_type NOT LIKE '%pdf%'";
                            }
                            
                            // Date range filter
                            if (!empty($filter_date_from)) {
                                $date_from_escaped = mysqli_real_escape_string($con, $filter_date_from);
                                $where_conditions[] = "DATE(a.created_at) >= '$date_from_escaped'";
                            }
                            
                            if (!empty($filter_date_to)) {
                                $date_to_escaped = mysqli_real_escape_string($con, $filter_date_to);
                                $where_conditions[] = "DATE(a.created_at) <= '$date_to_escaped'";
                            }
                            
                            // Search filter
                            if (!empty($search_query)) {
                                $search_escaped = mysqli_real_escape_string($con, $search_query);
                                $where_conditions[] = "(
                                    a.file_name LIKE '%$search_escaped%' OR
                                    c.application_no LIKE '%$search_escaped%' OR
                                    ct.task_name LIKE '%$search_escaped%' OR
                                    t.task_name LIKE '%$search_escaped%' OR
                                    a.file_type LIKE '%$search_escaped%'
                                )";
                            }
                            
                            $where_clause = implode(' AND ', $where_conditions);
                            
                            $query = "SELECT a.*, 
                                     ct.case_id, 
                                     c.application_no,
                                     ct.task_name,
                                     t.task_name as template_task_name
                                     FROM attachments a
                                     LEFT JOIN case_tasks ct ON a.task_id = ct.id
                                     LEFT JOIN cases c ON ct.case_id = c.id
                                     LEFT JOIN tasks t ON ct.task_template_id = t.id
                                     WHERE $where_clause
                                     ORDER BY a.created_at DESC
                                     LIMIT 500";
                            
                            $result = mysqli_query($con, $query);
                            
                            // Count active filters for display
                            $active_filters = 0;
                            if ($filter_case > 0) $active_filters++;
                            if ($filter_type != '') $active_filters++;
                            if ($filter_date_from != '') $active_filters++;
                            if ($filter_date_to != '') $active_filters++;
                            if ($search_query != '') $active_filters++;
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $file_ext = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
                                    $file_type = $row['file_type'] ?? 'Unknown';
                                    
                                    // Get file size from file if not in database
                                    $file_size = $row['file_size'] ?? 0;
                                    if ($file_size == 0) {
                                        $file_path = '../upload/' . $row['file_url'];
                                        if (file_exists($file_path)) {
                                            $file_size = filesize($file_path);
                                        }
                                    }
                                    
                                    // Format file size
                                    $size_display = formatFileSize($file_size);
                                    
                                    // Get icon
                                    $icon_class = 'fa-file';
                                    $icon_color = 'text-primary';
                                    
                                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']) || strpos($file_type, 'image/') === 0) {
                                        $icon_class = 'fa-image';
                                        $icon_color = 'text-success';
                                    } elseif ($file_ext == 'pdf' || strpos($file_type, 'pdf') !== false) {
                                        $icon_class = 'fa-file-pdf';
                                        $icon_color = 'text-danger';
                                    }
                                    
                                    $task_name = $row['task_name'] ?? $row['template_task_name'] ?? 'N/A';
                                    $application_no = $row['application_no'] ?? 'N/A';
                                    $created_at = date('d M Y H:i', strtotime($row['created_at']));
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input doc-checkbox" value="<?php echo $row['id']; ?>">
                                        </td>
                                        <td>
                                            <i class="fas <?php echo $icon_class; ?> <?php echo $icon_color; ?> me-2"></i>
                                            <span title="<?php echo htmlspecialchars($row['file_name']); ?>">
                                                <?php echo htmlspecialchars(mb_substr($row['file_name'], 0, 40)) . (mb_strlen($row['file_name']) > 40 ? '...' : ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($file_type); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo $size_display; ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($application_no); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($task_name); ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $created_at; ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../upload/<?php echo htmlspecialchars($row['file_url']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../upload/<?php echo htmlspecialchars($row['file_url']); ?>" 
                                                   download 
                                                   class="btn btn-outline-success btn-sm" 
                                                   title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteDocument(<?php echo $row['id']; ?>)" 
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-2 d-block"></i>
                                        <p class="mb-1">No attachments found</p>
                                        <?php if ($active_filters > 0 || !empty($search_query)): ?>
                                            <small>Try adjusting your filters or search criteria</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<?php require_once('../system/footer.php'); ?>

<script>
// Set max date for date inputs (today)
$(document).ready(function() {
    var today = new Date().toISOString().split('T')[0];
    $('input[name="date_from"], input[name="date_to"]').attr('max', today);
    
    // Validate date range
    $('input[name="date_from"]').on('change', function() {
        var dateFrom = $(this).val();
        var dateTo = $('input[name="date_to"]').val();
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('Date From cannot be greater than Date To');
            $(this).val('');
        }
    });
    
    $('input[name="date_to"]').on('change', function() {
        var dateFrom = $('input[name="date_from"]').val();
        var dateTo = $(this).val();
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('Date To cannot be less than Date From');
            $(this).val('');
        }
    });
    
    // Enter key on search input submits form
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });
});

// Select All functionality
$('#selectAllDocs, #selectAllHeader').on('change', function() {
    var isChecked = $(this).prop('checked');
    $('.doc-checkbox').prop('checked', isChecked);
    updateBulkButtons();
});

$('.doc-checkbox').on('change', function() {
    updateBulkButtons();
    
    // Update select all checkbox
    var total = $('.doc-checkbox').length;
    var checked = $('.doc-checkbox:checked').length;
    $('#selectAllDocs, #selectAllHeader').prop('checked', total === checked);
});

function updateBulkButtons() {
    var checked = $('.doc-checkbox:checked').length;
    $('#bulkDownloadBtn, #bulkDeleteBtn').prop('disabled', checked === 0);
}

// Bulk Download
$('#bulkDownloadBtn').on('click', function() {
    var selected = [];
    $('.doc-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    
    if (selected.length === 0) {
        alert('Please select at least one document');
        return;
    }
    
    // Create download links for each file
    selected.forEach(function(id) {
        // Get file URL from row
        var row = $('.doc-checkbox[value="' + id + '"]').closest('tr');
        var downloadLink = row.find('a[download]').attr('href');
        if (downloadLink) {
            var link = document.createElement('a');
            link.href = downloadLink;
            link.download = '';
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
    
    // Small delay between downloads
    setTimeout(function() {
        alert('Download initiated for ' + selected.length + ' file(s)');
    }, 500);
});

// Bulk Delete
$('#bulkDeleteBtn').on('click', function() {
    var selected = [];
    $('.doc-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    
    if (selected.length === 0) {
        alert('Please select at least one document');
        return;
    }
    
    if (!confirm('Are you sure you want to delete ' + selected.length + ' document(s)? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: 'save_task_review.php',
        type: 'POST',
        data: {
            action: 'bulk_delete_attachments',
            attachment_ids: selected.join(',')
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Deleted ' + response.deleted_count + ' document(s) successfully');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete documents'));
            }
        },
        error: function() {
            alert('Error deleting documents. Please try again.');
        }
    });
});

// Single Delete
function deleteDocument(attachmentId) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }
    
    $.ajax({
        url: 'save_task_review.php',
        type: 'POST',
        data: {
            action: 'delete_attachment',
            attachment_id: attachmentId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Document deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to delete document'));
            }
        },
        error: function() {
            alert('Error deleting document. Please try again.');
        }
    });
}
</script>

