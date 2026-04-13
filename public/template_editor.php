<?php
/**
 * KPRM - Template Editor with Preview
 * Simple editor to paste HTML and bind placeholders
 */
require_once('../system/op_lib.php');
require_once('../function.php');

$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Get template if editing
$template = null;
if ($template_id > 0) {
    global $con;
    $template_query = "SELECT * FROM report_templates WHERE id = '$template_id' AND status = 'ACTIVE'";
    $template_result = mysqli_query($con, $template_query);
    if ($template_result && mysqli_num_rows($template_result) > 0) {
        $template = mysqli_fetch_assoc($template_result);
        $client_id = $template['client_id'];
    }
}

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

// Get available placeholders
$placeholders = [
    'case_info' => [
        'application_number' => 'Application Number',
        'product' => 'Product',
        'region' => 'Region',
        'state' => 'State',
        'branch' => 'Branch',
        'location' => 'Location',
        'loan_amount' => 'Loan Amount',
        'sample_date' => 'Sample Date',
        'pickup_date' => 'Pickup Date (Same as Sample Date)',
        'report_date' => 'Report Date',
        'date_of_review' => 'Date of Review',
        'receive_date' => 'Receive Date (Current Date)',
        'tat_calculation' => 'TAT Calculation (Days between Sample Date and Report Date)',
    ],
    'client_info' => [
        'client_name' => 'Client Name',
    ],
    'system' => [
        'current_date' => 'Current Date',
        'serial_no' => 'Serial Number',
        'total_no_of_docs_sampled' => 'Total Documents',
        'all_attachments' => 'All Attachments (Images + Documents)',
        'attachments' => 'Attachments (Legacy - Documents only)',
        'verification_pics' => 'Verification Pictures (Legacy - Images only)',
      //  'logo' => 'Logo (Agency or System)',
        'agency_logo' => 'Agency Logo',
        'agency_stamp' => 'Agency Stamp',
        'agency_name' => 'Agency Name',
     //   'stamp' => 'Stamp (Agency or System)',
    ],
    'task_info' => [
        'task_name' => 'Task Name',
        'task_type' => 'Task Type',
        'task_status' => 'Task Status',
        'task_remarks' => 'Task Remarks (with line breaks)',
        'no_of_task' => 'Number of Tasks',
        'over_all_status' => 'Overall Status (Based on All Tasks - CNV/Negative/Positive)',
    ],
    'task_loop' => [
        'TaskLoop' => 'Task Loop Table (Complete Table with All Tasks)',
        'TaskCountLoop' => 'Task Count Summary (Horizontal - Task Name, Count, Status)',
    ],
    'document_loop' => [
        'document_loop_start' => 'Start Document Loop',
        'document_loop_end' => 'End Document Loop',
        'document_particulars' => 'Document Particulars',
        'document_type' => 'Document Type',
        'document_status' => 'Document Status',
        'document_remarks' => 'Document Remarks',
    ]
];

// Get client meta fields if client selected
$client_meta_placeholders = [];
if ($client_id) {
    $client_meta_query = "SELECT field_name, display_name FROM clients_meta WHERE client_id = '$client_id' AND status = 'ACTIVE'";
    $client_meta_result = mysqli_query($con, $client_meta_query);
    if ($client_meta_result) {
        while ($row = mysqli_fetch_assoc($client_meta_result)) {
            $client_meta_placeholders[$row['field_name']] = $row['display_name'];
        }
    }
}

$page_title = "Template Editor";
require_once('../system/header.php');
?>

<main class="content">
    <div class="container-fluid ">
        <h1 class="h3 mb-3 p-0 mb-3 d-flex justify-content-between align-items-center">
        <a href="../system/op_dashboard" class="btn btn-sm btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
       <?php echo $page_title; ?></h1>
        <div class="alert alert-warning" role="alert">
    <strong>Instructions : </strong>
    Paste the Report Image in chatGPT and Add Command 
    "Generate Similar Design in HTML Use Custom ClassName and use only html and css"
</div>

        <div class="row">
            <!-- Left Sidebar - Placeholders -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-code"></i> Placeholders
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Search Box -->
                        <div class="p-2 border-bottom">
                            <input type="text" id="placeholderSearch" class="form-control form-control-sm" 
                                   placeholder="Search placeholders..." onkeyup="filterPlaceholders(this.value)">
                        </div>
                        <div class="list-group list-group-flush" id="placeholderList" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($placeholders as $category => $items): ?>
                                <div class="list-group-item bg-light">
                                    <strong><?php echo ucwords(str_replace('_', ' ', $category)); ?></strong>
                                </div>
                                <?php foreach ($items as $key => $label): ?>
                                    <a href="javascript:void(0)" class="list-group-item list-group-item-action placeholder-item" 
                                       data-placeholder="{{<?php echo $key; ?>}}"
                                       data-search="<?php echo strtolower($key . ' ' . $label); ?>"
                                       title="Click to insert">
                                        <code>{{<?php echo $key; ?>}}</code>
                                        <small class="text-muted d-block"><?php echo $label; ?></small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($client_meta_placeholders)): ?>
                                <div class="list-group-item bg-light">
                                    <strong>Client Meta</strong>
                                </div>
                                <?php foreach ($client_meta_placeholders as $key => $label): ?>
                                    <a href="javascript:void(0)" class="list-group-item list-group-item-action placeholder-item" 
                                       data-placeholder="{{<?php echo $key; ?>}}"
                                       data-search="<?php echo strtolower($key . ' ' . $label); ?>"
                                       title="Click to insert">
                                        <code>{{<?php echo $key; ?>}}</code>
                                        <small class="text-muted d-block"><?php echo $label; ?></small>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="list-group-item">
                                    <small class="text-muted">Select a client to see client meta fields</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Editor Area -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit"></i> HTML Template
                                </h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-sm btn-warning" onclick="testPlaceholderBinding()" title="View Placeholder Data Mapping">
                                    <i class="fas fa-link"></i> Bind Data
                                </button>
                                <?php if ($template_id > 0): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="generateReportFromTemplate(<?php echo $template_id; ?>)" title="Generate Report (requires Case ID)">
                                        <i class="fas fa-file-pdf"></i> Generate Report
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-info" onclick="previewTemplate()">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="saveTemplate()">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="templateForm">
                            <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Client</label>
                                <select name="client_id" id="client_id" class="form-select" onchange="loadClientPlaceholders(this.value)">
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id']; ?>" <?php echo ($client_id == $client['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($client['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="template_name" id="template_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($template['template_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col mb-3">
                                <label class="form-label">Template Type</label>
                                <select name="template_type" id="template_type" class="form-select">
                                    <option value="REPORT" <?php echo (($template['template_type'] ?? 'REPORT') == 'REPORT') ? 'selected' : ''; ?>>Report</option>
                                    <option value="MIS" <?php echo (($template['template_type'] ?? '') == 'MIS') ? 'selected' : ''; ?>>MIS</option>
                                    <option value="OTHER" <?php echo (($template['template_type'] ?? '') == 'OTHER') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <small class="text-muted">Select template type: Report (for case reports), MIS (for management reports), or Other</small>
                            </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">HTML Template</label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" id="codeModeBtn" onclick="switchMode('code')">
                                            <i class="fas fa-code"></i> Code
                                        </button>
                                        <button type="button" class="btn btn-outline-primary active" id="visualModeBtn" onclick="switchMode('visual')">
                                            <i class="fas fa-eye"></i> Visual
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Code Mode -->
                                <div id="codeMode" class="editor-mode" style="display: none;">
                                    <div class="position-relative" style="min-height: 400px;">
                                        <textarea name="template_html" id="template_html" class="form-control" rows="20" 
                                                  placeholder="Paste your HTML template here. Click placeholders on the left to insert them." 
                                                  style="font-family: 'Courier New', monospace; font-size: 13px; background: transparent; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 2; color: transparent; caret-color: #333; resize: none; overflow: auto; border: none; padding: 12px; margin: 0;"><?php echo htmlspecialchars($template['template_html'] ?? ''); ?></textarea>
                                        <pre id="codeHighlight" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; margin: 0; padding: 12px; border: 1px solid #ced4da; border-radius: 0.375rem; background: #0d1117 !important; overflow: auto; z-index: 1; pointer-events: none; white-space: pre-wrap; word-wrap: break-word; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; min-height: 400px; color: #c9d1d9 !important;"><code class="language-html" style="color: #c9d1d9 !important; background: transparent !important;"></code></pre>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="fas fa-info-circle me-1"></i>Syntax highlighting enabled. Click placeholders on the left to insert them.
                                    </small>
                                </div>
                                
                                <!-- Visual Mode -->
                                <div id="visualMode" class="editor-mode">
                                    <div class="visual-toolbar mb-2 p-2 bg-light border rounded">
                                        <div class="btn-group btn-group-sm me-2">
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="formatText('bold')" title="Bold">
                                                <i class="fas fa-bold"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="formatText('italic')" title="Italic">
                                                <i class="fas fa-italic"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="formatText('underline')" title="Underline">
                                                <i class="fas fa-underline"></i>
                                            </button>
                                        </div>
                                        <div class="btn-group btn-group-sm me-2">
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="setAlignment('left')" title="Align Left">
                                                <i class="fas fa-align-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="setAlignment('center')" title="Align Center">
                                                <i class="fas fa-align-center"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="setAlignment('right')" title="Align Right">
                                                <i class="fas fa-align-right"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-info" onclick="insertTable()" title="Insert Table">
                                            <i class="fas fa-table"></i> Table
                                        </button>
                                    </div>
                                    <div id="visual_editor" contenteditable="true" class="form-control" 
                                         style="min-height: 400px; border: 1px solid #ced4da; padding: 15px; background: #fff; overflow-y: auto;">
                                        <?php echo $template['template_html'] ?? '<p>Start editing your template here. Click placeholders on the left to insert them.</p>'; ?>
                                    </div>
                                    <small class="text-muted">Click placeholders on the left sidebar to insert them into your template.</small>
                                </div>
                            </div>
                            <input type="hidden" name="template_id" id="template_id" value="<?php echo $template_id; ?>">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent" style="border: 1px solid #ddd; padding: 20px; background: #fff;">
                    <p class="text-muted">Preview will appear here...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.placeholder-item {
    cursor: pointer;
    transition: background-color 0.2s;
}
.placeholder-item:hover {
    background-color: #e9ecef !important;
}
.placeholder-item code {
    color: #0d6efd;
    font-weight: bold;
}
#template_html {
    font-family: 'Courier New', monospace;
    font-size: 13px;
}
#codeMode {
    position: relative;
}
#codeHighlight {
    max-height: 500px;
    overflow-y: auto;
    background: #0d1117 !important;
    color: #c9d1d9 !important;
}
#codeHighlight code {
    background: transparent !important;
    padding: 0 !important;
    font-size: 13px;
    line-height: 1.5;
    color: #c9d1d9 !important;
}
/* Highlight.js will handle syntax colors - just ensure visibility */
#codeHighlight * {
    color: inherit !important;
}
.editor-mode {
    position: relative;
}
#visual_editor {
    font-family: Arial, sans-serif;
    line-height: 1.6;
}
#visual_editor:focus {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
}
.placeholder-tag {
    background-color: #e7f3ff;
    color: #0066cc;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
    border: 1px dashed #0066cc;
    cursor: pointer;
}
.placeholder-tag:hover {
    background-color: #cfe2ff;
}
.visual-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}
#placeholderSearch {
    border-radius: 0;
}
#placeholderSearch:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
</style>

<script>
// Current editor mode (default to visual)
let currentMode = 'visual';

// Switch between code and visual mode
function switchMode(mode) {
    if (mode === currentMode) {
        return; // Already in this mode
    }
    
    const codeMode = document.getElementById('codeMode');
    const visualMode = document.getElementById('visualMode');
    const codeBtn = document.getElementById('codeModeBtn');
    const visualBtn = document.getElementById('visualModeBtn');
    
    if (mode === 'code') {
        // Sync visual to code before switching
        try {
            syncVisualToCode();
        } catch (e) {
            console.error('Error syncing visual to code:', e);
        }
        codeMode.style.display = 'block';
        visualMode.style.display = 'none';
        codeBtn.classList.add('active');
        visualBtn.classList.remove('active');
        currentMode = 'code';
        
        // Focus on code editor and sync highlight
        setTimeout(() => {
            document.getElementById('template_html').focus();
            syncCodeHighlight();
            // Force re-highlight after Highlight.js processes
            setTimeout(() => {
                syncCodeHighlight();
            }, 200);
        }, 100);
    } else {
        // Sync code to visual before switching
        try {
            syncCodeToVisual();
        } catch (e) {
            console.error('Error syncing code to visual:', e);
        }
        codeMode.style.display = 'none';
        visualMode.style.display = 'block';
        codeBtn.classList.remove('active');
        visualBtn.classList.add('active');
        currentMode = 'visual';
        
        // Focus on visual editor
        setTimeout(() => {
            document.getElementById('visual_editor').focus();
        }, 100);
    }
}

// Sync code editor to visual editor
function syncCodeToVisual() {
    const codeEditor = document.getElementById('template_html');
    const visualEditor = document.getElementById('visual_editor');
    let html = codeEditor.value;
    
    if (!html || !html.trim()) {
        visualEditor.innerHTML = '<p>Start editing your template here. Click placeholders on the left to insert them.</p>';
        return;
    }
    
    // Convert placeholders to visual tags
    // Use a more robust replacement that handles placeholders in HTML attributes too
    html = html.replace(/\{\{([^}]+)\}\}/g, function(match, placeholder) {
        // Escape the match for use in HTML attribute
        const escapedMatch = match.replace(/"/g, '&quot;');
        return '<span class="placeholder-tag" data-placeholder="' + escapedMatch + '">' + match + '</span>';
    });
    
    visualEditor.innerHTML = html;
    
    // Make sure placeholder tags are non-editable
    const placeholderTags = visualEditor.querySelectorAll('.placeholder-tag');
    placeholderTags.forEach(tag => {
        tag.contentEditable = 'false';
    });
}

// Sync visual editor to code editor
function syncVisualToCode() {
    const codeEditor = document.getElementById('template_html');
    const visualEditor = document.getElementById('visual_editor');
    
    // Clone the visual editor to work with
    const clone = visualEditor.cloneNode(true);
    
    // Replace all placeholder tags with their placeholder text
    const placeholderTags = clone.querySelectorAll('.placeholder-tag');
    placeholderTags.forEach(tag => {
        const placeholder = tag.getAttribute('data-placeholder');
        if (placeholder) {
            // Replace the tag with just the placeholder text
            const textNode = document.createTextNode(placeholder);
            if (tag.parentNode) {
                tag.parentNode.replaceChild(textNode, tag);
            }
        }
    });
    
    // Get the HTML content
    let html = clone.innerHTML;
    
    // Clean up any extra whitespace but preserve structure
    html = html.trim();
    
    // If empty, set to empty string
    if (!html || html === '<br>' || html === '<p></p>' || html === '<div></div>') {
        html = '';
    }
    
    codeEditor.value = html;
}

// Insert placeholder on click
document.querySelectorAll('.placeholder-item').forEach(item => {
    item.addEventListener('click', function() {
        const placeholder = this.getAttribute('data-placeholder');
        insertPlaceholder(placeholder);
    });
});

// Insert placeholder based on current mode
function insertPlaceholder(placeholder) {
    // Special handling for TaskLoop - show column selection modal
    if (placeholder === '{{TaskLoop}}') {
        showTaskLoopColumnModal();
        return;
    }
    
    // Special handling for TaskCountLoop - show options modal
    if (placeholder === '{{TaskCountLoop}}') {
        showTaskCountLoopModal();
        return;
    }
    
    if (currentMode === 'code') {
        const textarea = document.getElementById('template_html');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        
        textarea.value = text.substring(0, start) + placeholder + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
        // Sync highlight after insertion
        if (typeof syncCodeHighlight === 'function') {
            syncCodeHighlight();
        }
    } else {
        // Visual mode
        const visualEditor = document.getElementById('visual_editor');
        const selection = window.getSelection();
        
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            
            range.insertNode(placeholderTag);
            
            // Move cursor after placeholder
            range.setStartAfter(placeholderTag);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            // Append at end
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            visualEditor.appendChild(placeholderTag);
        }
        
        visualEditor.focus();
    }
}

// Show TaskLoop column selection modal
function showTaskLoopColumnModal() {
    // Save cursor position BEFORE modal opens (before textarea loses focus)
    let savedCursorPosition = { start: 0, end: 0 };
    let savedSelection = null;
    
    if (currentMode === 'code') {
        const textarea = document.getElementById('template_html');
        if (textarea) {
            savedCursorPosition.start = textarea.selectionStart;
            savedCursorPosition.end = textarea.selectionEnd;
        }
    } else {
        // Visual mode - save selection
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            savedSelection = selection.getRangeAt(0).cloneRange();
        }
    }
    
    // Show modal to select columns
    const modalHTML = `
        <div class="modal fade" id="taskLoopColumnModal" tabindex="-1" aria-labelledby="taskLoopColumnModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taskLoopColumnModalLabel">
                            <i class="fas fa-table me-2"></i>Task Loop Table - Design & Configure
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-cog me-2"></i>Configuration</h6>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Select Columns</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="col_serial" checked onchange="updateTaskLoopPreview()">
                                            <label class="form-check-label" for="col_serial">
                                                <strong>Serial Number</strong>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="col_name" checked onchange="updateTaskLoopPreview()">
                                            <label class="form-check-label" for="col_name">
                                                <strong>Task Name</strong>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="col_status" checked onchange="updateTaskLoopPreview()">
                                            <label class="form-check-label" for="col_status">
                                                <strong>Task Status</strong>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="col_remarks" checked onchange="updateTaskLoopPreview()">
                                            <label class="form-check-label" for="col_remarks">
                                                <strong>Task Remarks</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Column Headers (Labels)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2" id="header_inputs_serial">
                                            <label class="form-label small">Serial Number Header:</label>
                                            <input type="text" class="form-control form-control-sm" id="header_serial" value="S.No" onchange="updateTaskLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="header_inputs_name">
                                            <label class="form-label small">Task Name Header:</label>
                                            <input type="text" class="form-control form-control-sm" id="header_name" value="Task Name" onchange="updateTaskLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="header_inputs_status">
                                            <label class="form-label small">Task Status Header:</label>
                                            <input type="text" class="form-control form-control-sm" id="header_status" value="Status" onchange="updateTaskLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="header_inputs_remarks">
                                            <label class="form-label small">Task Remarks Header:</label>
                                            <input type="text" class="form-control form-control-sm" id="header_remarks" value="Remarks" onchange="updateTaskLoopPreview()">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Serial Number Options</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2" id="serial_prefix_input">
                                            <label class="form-label small">Serial Prefix (e.g., "Doc ", "Item ", leave empty for numbers only):</label>
                                            <input type="text" class="form-control form-control-sm" id="serial_prefix" value="" placeholder="e.g., Doc " onchange="updateTaskLoopPreview()">
                                            <small class="text-muted">Example: "Doc " will show as "Doc 1", "Doc 2", etc.</small>
                                        </div>
                                        <div class="mb-2" id="serial_start_input">
                                            <label class="form-label small">Start Number:</label>
                                            <input type="number" class="form-control form-control-sm" id="serial_start" value="1" min="1" onchange="updateTaskLoopPreview()">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Column Widths (in % or px)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2" id="width_inputs_serial">
                                            <label class="form-label small">Serial Number Width:</label>
                                            <input type="text" class="form-control form-control-sm" id="width_serial" value="10%" placeholder="e.g., 10% or 100px" onchange="updateTaskLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="width_inputs_name">
                                            <label class="form-label small">Task Name Width:</label>
                                            <input type="text" class="form-control form-control-sm" id="width_name" value="30%" placeholder="e.g., 30% or 300px" onchange="updateTaskLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="width_inputs_status">
                                            <label class="form-label small">Task Status Width:</label>
                                            <input type="text" class="form-control form-control-sm" id="width_status" value="20%" placeholder="e.g., 20% or 200px" onchange="updateTaskLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="width_inputs_remarks">
                                            <label class="form-label small">Task Remarks Width:</label>
                                            <input type="text" class="form-control form-control-sm" id="width_remarks" value="40%" placeholder="e.g., 40% or 400px" onchange="updateTaskLoopPreview()">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Table Header (TH) Styling</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label class="form-label small">TH Background Color:</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" id="th_bg_color" value="#f8f9fa" placeholder="#f8f9fa or transparent" onchange="updateTaskLoopPreview()">
                                                <input type="color" class="form-control form-control-color" id="th_bg_color_picker" value="#f8f9fa" onchange="document.getElementById('th_bg_color').value=this.value; updateTaskLoopPreview();" title="Pick color">
                                            </div>
                                            <small class="text-muted">Leave empty or use "transparent" to inherit from parent</small>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">TH Text Color:</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" id="th_text_color" value="" placeholder="#000000 or inherit" onchange="updateTaskLoopPreview()">
                                                <input type="color" class="form-control form-control-color" id="th_text_color_picker" value="#000000" onchange="document.getElementById('th_text_color').value=this.value; updateTaskLoopPreview();" title="Pick color">
                                            </div>
                                            <small class="text-muted">Leave empty to inherit from parent</small>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="inherit_parent_css" checked onchange="updateTaskLoopPreview()">
                                            <label class="form-check-label" for="inherit_parent_css">
                                                <strong>Inherit Parent CSS</strong> (Use minimal inline styles, let parent CSS control appearance)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-eye me-2"></i>Preview</h6>
                                
                                <!-- Tabs for Visual and Code Preview -->
                                <ul class="nav nav-tabs mb-2" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="taskloop-visual-tab" data-bs-toggle="tab" data-bs-target="#taskloop-visual-preview" type="button" role="tab">
                                            <i class="fas fa-eye me-1"></i>Visual
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="taskloop-code-tab" data-bs-toggle="tab" data-bs-target="#taskloop-code-preview" type="button" role="tab">
                                            <i class="fas fa-code me-1"></i>HTML Code
                                        </button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content">
                                    <!-- Visual Preview -->
                                    <div class="tab-pane fade show active" id="taskloop-visual-preview" role="tabpanel">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div id="taskLoopPreview" style="overflow-x: auto;">
                                                    <table class="table table-bordered table-sm" style="width: 100%;">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 10%;">S.No</th>
                                                                <th style="width: 30%;">Task Name</th>
                                                                <th style="width: 20%;">Status</th>
                                                                <th style="width: 40%;">Remarks</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>1</td>
                                                                <td>Sample Task 1</td>
                                                                <td>Completed</td>
                                                                <td>Sample remarks text</td>
                                                            </tr>
                                                            <tr>
                                                                <td>2</td>
                                                                <td>Sample Task 2</td>
                                                                <td>Pending</td>
                                                                <td>Another sample remark</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Code Preview -->
                                    <div class="tab-pane fade" id="taskloop-code-preview" role="tabpanel">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <pre id="taskLoopCodePreview" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; white-space: pre-wrap; word-wrap: break-word;"><code class="language-html">HTML code will appear here...</code></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Generated Placeholder Code</strong>
                                    </div>
                                    <div class="card-body">
                                        <textarea id="taskLoopGeneratedCode" class="form-control form-control-sm" rows="3" readonly style="font-family: monospace; font-size: 12px;"></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="copyTaskLoopCode()">
                                            <i class="fas fa-copy me-1"></i>Copy Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="insertTaskLoopWithColumns()">
                            <i class="fas fa-check me-1"></i>Insert Task Loop
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('taskLoopColumnModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal using Bootstrap
    const modalElement = new bootstrap.Modal(document.getElementById('taskLoopColumnModal'));
    const modal = document.getElementById('taskLoopColumnModal');
    
    // Store cursor position in modal data attribute
    if (modal) {
        modal.setAttribute('data-cursor-start', savedCursorPosition.start);
        modal.setAttribute('data-cursor-end', savedCursorPosition.end);
        if (savedSelection) {
            modal.setAttribute('data-has-selection', 'true');
        }
    }
    
    // Initialize preview
    setTimeout(() => {
        updateTaskLoopPreview();
        updateHeaderVisibility();
    }, 100);
    
    modalElement.show();
    
    // Clean up modal after hidden
    modal.addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Update header and width input visibility based on column selection
function updateHeaderVisibility() {
    const showSerial = document.getElementById('col_serial').checked;
    const showName = document.getElementById('col_name').checked;
    const showStatus = document.getElementById('col_status').checked;
    const showRemarks = document.getElementById('col_remarks').checked;
    
    document.getElementById('header_inputs_serial').style.display = showSerial ? 'block' : 'none';
    document.getElementById('header_inputs_name').style.display = showName ? 'block' : 'none';
    document.getElementById('header_inputs_status').style.display = showStatus ? 'block' : 'none';
    document.getElementById('header_inputs_remarks').style.display = showRemarks ? 'block' : 'none';
    
    document.getElementById('width_inputs_serial').style.display = showSerial ? 'block' : 'none';
    document.getElementById('width_inputs_name').style.display = showName ? 'block' : 'none';
    document.getElementById('width_inputs_status').style.display = showStatus ? 'block' : 'none';
    document.getElementById('width_inputs_remarks').style.display = showRemarks ? 'block' : 'none';
    
    document.getElementById('serial_prefix_input').style.display = showSerial ? 'block' : 'none';
    document.getElementById('serial_start_input').style.display = showSerial ? 'block' : 'none';
}

// Update TaskLoop preview
function updateTaskLoopPreview() {
    updateHeaderVisibility();
    
    const showSerial = document.getElementById('col_serial').checked;
    const showName = document.getElementById('col_name').checked;
    const showStatus = document.getElementById('col_status').checked;
    const showRemarks = document.getElementById('col_remarks').checked;
    
    const headerSerial = document.getElementById('header_serial').value || 'S.No';
    const headerName = document.getElementById('header_name').value || 'Task Name';
    const headerStatus = document.getElementById('header_status').value || 'Status';
    const headerRemarks = document.getElementById('header_remarks').value || 'Remarks';
    
    const widthSerial = document.getElementById('width_serial').value || '10%';
    const widthName = document.getElementById('width_name').value || '30%';
    const widthStatus = document.getElementById('width_status').value || '20%';
    const widthRemarks = document.getElementById('width_remarks').value || '40%';
    
    const serialPrefix = document.getElementById('serial_prefix').value || '';
    const serialStart = parseInt(document.getElementById('serial_start').value) || 1;
    
    // Get TH styling options
    const thBgColor = document.getElementById('th_bg_color').value.trim() || '';
    const thTextColor = document.getElementById('th_text_color').value.trim() || '';
    const inheritParentCSS = document.getElementById('inherit_parent_css').checked;
    
    // Build TH style
    let thStyle = '';
    if (!inheritParentCSS) {
        const styles = [];
        if (thBgColor && thBgColor !== 'transparent') {
            styles.push('background-color: ' + thBgColor);
        }
        if (thTextColor) {
            styles.push('color: ' + thTextColor);
        }
        if (styles.length > 0) {
            thStyle = ' style="' + styles.join('; ') + ';"';
        }
    }
    
    // Build preview table HTML
    let tableStyle = inheritParentCSS ? '' : 'style="width: 100%; border-collapse: collapse;"';
    let previewHTML = `<table class="table table-bordered table-sm" ${tableStyle}>`;
    previewHTML += '<thead><tr>';
    
    if (showSerial) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthSerial};"`;
        previewHTML += `<th${widthStyle}${thStyle}>${headerSerial}</th>`;
    }
    if (showName) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthName};"`;
        previewHTML += `<th${widthStyle}${thStyle}>${headerName}</th>`;
    }
    if (showStatus) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthStatus};"`;
        previewHTML += `<th${widthStyle}${thStyle}>${headerStatus}</th>`;
    }
    if (showRemarks) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthRemarks};"`;
        previewHTML += `<th${widthStyle}${thStyle}>${headerRemarks}</th>`;
    }
    
    previewHTML += '</tr></thead><tbody>';
    
    // Generate serial numbers with prefix
    const serial1 = serialPrefix ? serialPrefix + serialStart : serialStart;
    const serial2 = serialPrefix ? serialPrefix + (serialStart + 1) : (serialStart + 1);
    
    previewHTML += '<tr>';
    if (showSerial) previewHTML += `<td>${serial1}</td>`;
    if (showName) previewHTML += '<td>Sample Task 1</td>';
    if (showStatus) previewHTML += '<td>Completed</td>';
    if (showRemarks) previewHTML += '<td>Sample remarks text</td>';
    previewHTML += '</tr>';
    previewHTML += '<tr>';
    if (showSerial) previewHTML += `<td>${serial2}</td>`;
    if (showName) previewHTML += '<td>Sample Task 2</td>';
    if (showStatus) previewHTML += '<td>Pending</td>';
    if (showRemarks) previewHTML += '<td>Another sample remark</td>';
    previewHTML += '</tr>';
    previewHTML += '</tbody></table>';
    
    document.getElementById('taskLoopPreview').innerHTML = previewHTML;
    
    // Generate and display the actual HTML code
    let tableStyleAttr = inheritParentCSS ? '' : ' style="width: 100%; border-collapse: collapse;"';
    let codeHTML = `<table class="table table-bordered table-sm"${tableStyleAttr}>\n`;
    codeHTML += '    <thead>\n';
    codeHTML += '        <tr>\n';
    
    // Build TH style for code
    let thStyleCode = '';
    if (!inheritParentCSS) {
        const styles = [];
        if (thBgColor && thBgColor !== 'transparent') {
            styles.push('background-color: ' + thBgColor);
        }
        if (thTextColor) {
            styles.push('color: ' + thTextColor);
        }
        if (styles.length > 0) {
            thStyleCode = ' style="' + styles.join('; ') + '"';
        }
    }
    
    if (showSerial) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthSerial};"`;
        codeHTML += `            <th${widthStyle}${thStyleCode}>${escapeHtml(headerSerial)}</th>\n`;
    }
    if (showName) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthName};"`;
        codeHTML += `            <th${widthStyle}${thStyleCode}>${escapeHtml(headerName)}</th>\n`;
    }
    if (showStatus) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthStatus};"`;
        codeHTML += `            <th${widthStyle}${thStyleCode}>${escapeHtml(headerStatus)}</th>\n`;
    }
    if (showRemarks) {
        const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthRemarks};"`;
        codeHTML += `            <th${widthStyle}${thStyleCode}>${escapeHtml(headerRemarks)}</th>\n`;
    }
    
    codeHTML += '        </tr>\n';
    codeHTML += '    </thead>\n';
    codeHTML += '    <tbody>\n';
    codeHTML += '        {{TaskLoop';
    
    // Build options for code display
    const codeOptions = [];
    const columns = [];
    if (showSerial) columns.push('serial');
    if (showName) columns.push('name');
    if (showStatus) columns.push('status');
    if (showRemarks) columns.push('remarks');
    
    if (columns.length > 0 && columns.length < 4) {
        codeOptions.push('columns=' + columns.join(','));
    }
    
    if (serialPrefix) {
        codeOptions.push('serial_prefix=' + encodeURIComponent(serialPrefix));
    }
    if (serialStart !== 1) {
        codeOptions.push('serial_start=' + serialStart);
    }
    
    if (headerSerial !== 'S.No') {
        codeOptions.push('header_serial=' + encodeURIComponent(headerSerial));
    }
    if (headerName !== 'Task Name') {
        codeOptions.push('header_name=' + encodeURIComponent(headerName));
    }
    if (headerStatus !== 'Status') {
        codeOptions.push('header_status=' + encodeURIComponent(headerStatus));
    }
    if (headerRemarks !== 'Remarks') {
        codeOptions.push('header_remarks=' + encodeURIComponent(headerRemarks));
    }
    
    if (widthSerial !== '10%') {
        codeOptions.push('width_serial=' + encodeURIComponent(widthSerial));
    }
    if (widthName !== '30%') {
        codeOptions.push('width_name=' + encodeURIComponent(widthName));
    }
    if (widthStatus !== '20%') {
        codeOptions.push('width_status=' + encodeURIComponent(widthStatus));
    }
    if (widthRemarks !== '40%') {
        codeOptions.push('width_remarks=' + encodeURIComponent(widthRemarks));
    }
    
    // Add TH styling options
    if (thBgColor && thBgColor !== '#f8f9fa' && thBgColor !== 'transparent') {
        codeOptions.push('th_bg_color=' + encodeURIComponent(thBgColor));
    }
    if (thTextColor) {
        codeOptions.push('th_text_color=' + encodeURIComponent(thTextColor));
    }
    if (inheritParentCSS) {
        codeOptions.push('inherit_css=yes');
    }
    
    if (codeOptions.length > 0) {
        codeHTML += '|' + codeOptions.join('|');
    }
    codeHTML += '}}\n';
    codeHTML += '    </tbody>\n';
    codeHTML += '</table>';
    
    document.getElementById('taskLoopCodePreview').innerHTML = '<code class="language-html">' + escapeHtml(codeHTML) + '</code>';
    
    // Update generated code
    updateTaskLoopGeneratedCode();
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Update generated code for TaskLoop
function updateTaskLoopGeneratedCode() {
    const showSerial = document.getElementById('col_serial').checked;
    const showName = document.getElementById('col_name').checked;
    const showStatus = document.getElementById('col_status').checked;
    const showRemarks = document.getElementById('col_remarks').checked;
    
    const headerSerial = document.getElementById('header_serial').value || 'S.No';
    const headerName = document.getElementById('header_name').value || 'Task Name';
    const headerStatus = document.getElementById('header_status').value || 'Status';
    const headerRemarks = document.getElementById('header_remarks').value || 'Remarks';
    
    const widthSerial = document.getElementById('width_serial').value || '10%';
    const widthName = document.getElementById('width_name').value || '30%';
    const widthStatus = document.getElementById('width_status').value || '20%';
    const widthRemarks = document.getElementById('width_remarks').value || '40%';
    
    const serialPrefix = document.getElementById('serial_prefix').value || '';
    const serialStart = parseInt(document.getElementById('serial_start').value) || 1;
    
    // Get TH styling options
    const thBgColor = document.getElementById('th_bg_color').value.trim() || '';
    const thTextColor = document.getElementById('th_text_color').value.trim() || '';
    const inheritParentCSS = document.getElementById('inherit_parent_css').checked;
    
    // Build placeholder with options
    let placeholder = '{{TaskLoop';
    const options = [];
    
    const columns = [];
    if (showSerial) columns.push('serial');
    if (showName) columns.push('name');
    if (showStatus) columns.push('status');
    if (showRemarks) columns.push('remarks');
    
    if (columns.length > 0 && columns.length < 4) {
        options.push('columns=' + columns.join(','));
    }
    
    // Add serial prefix and start
    if (serialPrefix) {
        options.push('serial_prefix=' + encodeURIComponent(serialPrefix));
    }
    if (serialStart !== 1) {
        options.push('serial_start=' + serialStart);
    }
    
    // Add headers
    if (showSerial && headerSerial !== 'S.No') {
        options.push('header_serial=' + encodeURIComponent(headerSerial));
    }
    if (showName && headerName !== 'Task Name') {
        options.push('header_name=' + encodeURIComponent(headerName));
    }
    if (showStatus && headerStatus !== 'Status') {
        options.push('header_status=' + encodeURIComponent(headerStatus));
    }
    if (showRemarks && headerRemarks !== 'Remarks') {
        options.push('header_remarks=' + encodeURIComponent(headerRemarks));
    }
    
    // Add widths
    if (showSerial && widthSerial !== '10%') {
        options.push('width_serial=' + encodeURIComponent(widthSerial));
    }
    if (showName && widthName !== '30%') {
        options.push('width_name=' + encodeURIComponent(widthName));
    }
    if (showStatus && widthStatus !== '20%') {
        options.push('width_status=' + encodeURIComponent(widthStatus));
    }
    if (showRemarks && widthRemarks !== '40%') {
        options.push('width_remarks=' + encodeURIComponent(widthRemarks));
    }
    
    // Add TH styling options
    if (thBgColor && thBgColor !== '#f8f9fa' && thBgColor !== 'transparent') {
        options.push('th_bg_color=' + encodeURIComponent(thBgColor));
    }
    if (thTextColor) {
        options.push('th_text_color=' + encodeURIComponent(thTextColor));
    }
    if (inheritParentCSS) {
        options.push('inherit_css=yes');
    }
    
    if (options.length > 0) {
        placeholder += '|' + options.join('|');
    }
    placeholder += '}}';
    
    document.getElementById('taskLoopGeneratedCode').value = placeholder;
}

// Copy TaskLoop code to clipboard
function copyTaskLoopCode() {
    const codeTextarea = document.getElementById('taskLoopGeneratedCode');
    codeTextarea.select();
    document.execCommand('copy');
    
    // Show feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

// Insert TaskLoop placeholder with column selection
function insertTaskLoopWithColumns() {
    // Get the generated code from the textarea
    const placeholder = document.getElementById('taskLoopGeneratedCode').value;
    
    if (!placeholder) {
        alert('Please configure the table first');
        return;
    }
    
    // Insert placeholder using saved cursor position
    if (currentMode === 'code') {
        const textarea = document.getElementById('template_html');
        const modal = document.getElementById('taskLoopColumnModal');
        
        // Get saved cursor position from modal
        let start = 0;
        let end = 0;
        
        if (modal) {
            const savedStart = modal.getAttribute('data-cursor-start');
            const savedEnd = modal.getAttribute('data-cursor-end');
            if (savedStart !== null && savedEnd !== null) {
                start = parseInt(savedStart, 10);
                end = parseInt(savedEnd, 10);
            } else {
                // Fallback: use current selection
                start = textarea.selectionStart;
                end = textarea.selectionEnd;
            }
        } else {
            // Fallback: use current selection
            start = textarea.selectionStart;
            end = textarea.selectionEnd;
        }
        
        const text = textarea.value;
        textarea.value = text.substring(0, start) + placeholder + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
        // Sync highlight after insertion
        if (typeof syncCodeHighlight === 'function') {
            syncCodeHighlight();
        }
    } else {
        // Visual mode
        const visualEditor = document.getElementById('visual_editor');
        const selection = window.getSelection();
        
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            
            range.insertNode(placeholderTag);
            
            // Move cursor after placeholder
            range.setStartAfter(placeholderTag);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            // Append at end
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            visualEditor.appendChild(placeholderTag);
        }
        
        visualEditor.focus();
    }
    
    // Close modal
    const modalElement = bootstrap.Modal.getInstance(document.getElementById('taskLoopColumnModal'));
    if (modalElement) {
        modalElement.hide();
    }
}

// Show TaskCountLoop options modal (horizontal task count summary)
function showTaskCountLoopModal() {
    // Save cursor position BEFORE modal opens (before textarea loses focus)
    let savedCursorPosition = { start: 0, end: 0 };
    let savedSelection = null;
    
    if (currentMode === 'code') {
        const textarea = document.getElementById('template_html');
        if (textarea) {
            savedCursorPosition.start = textarea.selectionStart;
            savedCursorPosition.end = textarea.selectionEnd;
        }
    } else {
        // Visual mode - save selection
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            savedSelection = selection.getRangeAt(0).cloneRange();
        }
    }
    
    // Show modal to select display options
    const modalHTML = `
        <div class="modal fade" id="taskCountLoopModal" tabindex="-1" aria-labelledby="taskCountLoopModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taskCountLoopModalLabel">
                            <i class="fas fa-list-ul me-2"></i>Task Count Summary - Design & Configure
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-cog me-2"></i>Configuration</h6>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Display Options</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="count_show_name" checked onchange="updateTaskCountLoopPreview()">
                                            <label class="form-check-label" for="count_show_name">
                                                <strong>Task Name Row</strong>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="count_show_count" checked onchange="updateTaskCountLoopPreview()">
                                            <label class="form-check-label" for="count_show_count">
                                                <strong>Count Row</strong>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="count_show_status" checked onchange="updateTaskCountLoopPreview()">
                                            <label class="form-check-label" for="count_show_status">
                                                <strong>Status Row</strong>
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="count_show_labels" checked onchange="updateTaskCountLoopPreview()">
                                            <label class="form-check-label" for="count_show_labels">
                                                <strong>Show Row Labels</strong> (First column with labels)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Row Labels (Headers)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2" id="count_label_inputs_name">
                                            <label class="form-label small">Task Name Row Label:</label>
                                            <input type="text" class="form-control form-control-sm" id="label_task_name" value="Task Name" onchange="updateTaskCountLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="count_label_inputs_count">
                                            <label class="form-label small">Count Row Label:</label>
                                            <input type="text" class="form-control form-control-sm" id="label_count" value="Count" onchange="updateTaskCountLoopPreview()">
                                        </div>
                                        <div class="mb-2" id="count_label_inputs_status">
                                            <label class="form-label small">Status Row Label:</label>
                                            <input type="text" class="form-control form-control-sm" id="label_status" value="Status" onchange="updateTaskCountLoopPreview()">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Column Widths (in % or px)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label class="form-label small">Label Column Width (if shown):</label>
                                            <input type="text" class="form-control form-control-sm" id="count_width_label" value="20%" placeholder="e.g., 20% or 200px" onchange="updateTaskCountLoopPreview()">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Task Column Width (each):</label>
                                            <input type="text" class="form-control form-control-sm" id="count_width_task" value="auto" placeholder="e.g., auto, 15%, or 150px" onchange="updateTaskCountLoopPreview()">
                                        </div>
                                        <small class="text-muted">Leave as "auto" for equal distribution</small>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Table Header (TH) Styling</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <label class="form-label small">TH Background Color:</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" id="count_th_bg_color" value="#f8f9fa" placeholder="#f8f9fa or transparent" onchange="updateTaskCountLoopPreview()">
                                                <input type="color" class="form-control form-control-color" id="count_th_bg_color_picker" value="#f8f9fa" onchange="document.getElementById('count_th_bg_color').value=this.value; updateTaskCountLoopPreview();" title="Pick color">
                                            </div>
                                            <small class="text-muted">Leave empty or use "transparent" to inherit from parent</small>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">TH Text Color:</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" id="count_th_text_color" value="" placeholder="#000000 or inherit" onchange="updateTaskCountLoopPreview()">
                                                <input type="color" class="form-control form-control-color" id="count_th_text_color_picker" value="#000000" onchange="document.getElementById('count_th_text_color').value=this.value; updateTaskCountLoopPreview();" title="Pick color">
                                            </div>
                                            <small class="text-muted">Leave empty to inherit from parent</small>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="count_inherit_parent_css" checked onchange="updateTaskCountLoopPreview()">
                                            <label class="form-check-label" for="count_inherit_parent_css">
                                                <strong>Inherit Parent CSS</strong> (Use minimal inline styles, let parent CSS control appearance)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-eye me-2"></i>Preview</h6>
                                
                                <!-- Tabs for Visual and Code Preview -->
                                <ul class="nav nav-tabs mb-2" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="taskcountloop-visual-tab" data-bs-toggle="tab" data-bs-target="#taskcountloop-visual-preview" type="button" role="tab">
                                            <i class="fas fa-eye me-1"></i>Visual
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="taskcountloop-code-tab" data-bs-toggle="tab" data-bs-target="#taskcountloop-code-preview" type="button" role="tab">
                                            <i class="fas fa-code me-1"></i>HTML Code
                                        </button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content">
                                    <!-- Visual Preview -->
                                    <div class="tab-pane fade show active" id="taskcountloop-visual-preview" role="tabpanel">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div id="taskCountLoopPreview" style="overflow-x: auto;">
                                                    <table class="table table-bordered table-sm" style="width: 100%;">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 20%;">Task Name</th>
                                                                <th>Task 1</th>
                                                                <th>Task 2</th>
                                                                <th>Task 3</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td style="width: 20%;"><strong>Task Name</strong></td>
                                                                <td>Residence Verification</td>
                                                                <td>Banking Verification</td>
                                                                <td>ITO Verification</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width: 20%;"><strong>Count</strong></td>
                                                                <td>5</td>
                                                                <td>3</td>
                                                                <td>2</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width: 20%;"><strong>Status</strong></td>
                                                                <td>Completed</td>
                                                                <td>Pending</td>
                                                                <td>Verified</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Code Preview -->
                                    <div class="tab-pane fade" id="taskcountloop-code-preview" role="tabpanel">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <pre id="taskCountLoopCodePreview" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; white-space: pre-wrap; word-wrap: break-word;"><code class="language-html">HTML code will appear here...</code></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Generated Placeholder Code</strong>
                                    </div>
                                    <div class="card-body">
                                        <textarea id="taskCountLoopGeneratedCode" class="form-control form-control-sm" rows="3" readonly style="font-family: monospace; font-size: 12px;"></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="copyTaskCountLoopCode()">
                                            <i class="fas fa-copy me-1"></i>Copy Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="insertTaskCountLoopWithOptions()">
                            <i class="fas fa-check me-1"></i>Insert Task Count Summary
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('taskCountLoopModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal using Bootstrap
    const modalElement = new bootstrap.Modal(document.getElementById('taskCountLoopModal'));
    const modal = document.getElementById('taskCountLoopModal');
    
    // Store cursor position in modal data attribute
    if (modal) {
        modal.setAttribute('data-cursor-start', savedCursorPosition.start);
        modal.setAttribute('data-cursor-end', savedCursorPosition.end);
        if (savedSelection) {
            modal.setAttribute('data-has-selection', 'true');
        }
    }
    
    // Initialize preview
    setTimeout(() => {
        updateTaskCountLoopPreview();
        updateCountLabelVisibility();
    }, 100);
    
    modalElement.show();
    
    // Clean up modal after hidden
    modal.addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Update count label visibility
function updateCountLabelVisibility() {
    const showLabels = document.getElementById('count_show_labels').checked;
    const showName = document.getElementById('count_show_name').checked;
    const showCount = document.getElementById('count_show_count').checked;
    const showStatus = document.getElementById('count_show_status').checked;
    
    document.getElementById('count_label_inputs_name').style.display = (showLabels && showName) ? 'block' : 'none';
    document.getElementById('count_label_inputs_count').style.display = (showLabels && showCount) ? 'block' : 'none';
    document.getElementById('count_label_inputs_status').style.display = (showLabels && showStatus) ? 'block' : 'none';
}

// Update TaskCountLoop preview
function updateTaskCountLoopPreview() {
    updateCountLabelVisibility();
    
    const showName = document.getElementById('count_show_name').checked;
    const showCount = document.getElementById('count_show_count').checked;
    const showStatus = document.getElementById('count_show_status').checked;
    const showLabels = document.getElementById('count_show_labels').checked;
    
    const labelTaskName = document.getElementById('label_task_name').value || 'Task Name';
    const labelCount = document.getElementById('label_count').value || 'Count';
    const labelStatus = document.getElementById('label_status').value || 'Status';
    
    const widthLabel = document.getElementById('count_width_label').value || '20%';
    const widthTask = document.getElementById('count_width_task').value || 'auto';
    
    // Get TH styling options
    const thBgColor = document.getElementById('count_th_bg_color').value.trim() || '';
    const thTextColor = document.getElementById('count_th_text_color').value.trim() || '';
    const inheritParentCSS = document.getElementById('count_inherit_parent_css').checked;
    
    // Build TH style
    let thStyle = '';
    if (!inheritParentCSS) {
        const styles = [];
        if (thBgColor && thBgColor !== 'transparent') {
            styles.push('background-color: ' + thBgColor);
        }
        if (thTextColor) {
            styles.push('color: ' + thTextColor);
        }
        if (styles.length > 0) {
            thStyle = ' style="' + styles.join('; ') + ';"';
        }
    }
    
    // Build preview table
    let tableStyle = inheritParentCSS ? '' : 'style="width: 100%; border-collapse: collapse;"';
    let previewHTML = `<table class="table table-bordered table-sm" ${tableStyle}>`;
    
    // Header row (task names as columns)
    if (showName || showCount || showStatus) {
        previewHTML += '<thead><tr>';
        if (showLabels) {
            const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthLabel};"`;
            previewHTML += `<th${widthStyle}${thStyle}>Task Name</th>`;
        }
        const taskWidthStyle = inheritParentCSS ? '' : (widthTask !== 'auto' ? ` style="width: ${widthTask};"` : '');
        previewHTML += `<th${taskWidthStyle}${thStyle}>Task 1</th>`;
        previewHTML += `<th${taskWidthStyle}${thStyle}>Task 2</th>`;
        previewHTML += `<th${taskWidthStyle}${thStyle}>Task 3</th>`;
        previewHTML += '</tr></thead>';
    }
    
    previewHTML += '<tbody>';
    
    const tdStyle = inheritParentCSS ? '' : ' style="padding:8px; border: 1px solid #ddd;"';
    const labelWidthStyle = inheritParentCSS ? '' : ` style="width: ${widthLabel};"`;
    const taskWidthStyle = inheritParentCSS ? '' : (widthTask !== 'auto' ? ` style="width: ${widthTask};"` : '');
    
    // Task Name Row
    if (showName) {
        previewHTML += '<tr>';
        if (showLabels) {
            previewHTML += `<td${labelWidthStyle}${tdStyle}><strong>${labelTaskName}</strong></td>`;
        }
        previewHTML += `<td${taskWidthStyle}${tdStyle}>Residence Verification</td>`;
        previewHTML += `<td${taskWidthStyle}${tdStyle}>Banking Verification</td>`;
        previewHTML += `<td${taskWidthStyle}${tdStyle}>ITO Verification</td>`;
        previewHTML += '</tr>';
    }
    
    // Count Row
    if (showCount) {
        previewHTML += '<tr>';
        if (showLabels) {
            previewHTML += `<td${labelWidthStyle}${tdStyle}><strong>${labelCount}</strong></td>`;
        }
        previewHTML += `<td${taskWidthStyle}${tdStyle}><strong>5</strong></td>`;
        previewHTML += `<td${taskWidthStyle}${tdStyle}><strong>3</strong></td>`;
        previewHTML += `<td${taskWidthStyle}${tdStyle}><strong>2</strong></td>`;
        previewHTML += '</tr>';
    }
    
    // Status Row
    if (showStatus) {
        previewHTML += '<tr>';
        if (showLabels) {
            previewHTML += `<td${labelWidthStyle}${tdStyle}><strong>${labelStatus}</strong></td>`;
        }
        previewHTML += `<td${taskWidthStyle}${tdStyle}>Completed</td>`;
        previewHTML += `<td${taskWidthStyle}${tdStyle}>Pending</td>`;
        previewHTML += `<td${taskWidthStyle}${tdStyle}>Verified</td>`;
        previewHTML += '</tr>';
    }
    
    previewHTML += '</tbody></table>';
    
    document.getElementById('taskCountLoopPreview').innerHTML = previewHTML;
    
    // Generate and display the actual HTML code
    let tableStyleAttr = inheritParentCSS ? '' : ' style="width: 100%; border-collapse: collapse;"';
    let codeHTML = `<table class="table table-bordered table-sm"${tableStyleAttr}>\n`;
    
    // Header row (task names as columns)
    if (showName || showCount || showStatus) {
        codeHTML += '    <thead>\n';
        codeHTML += '        <tr>\n';
        if (showLabels) {
            const widthStyle = inheritParentCSS ? '' : ` style="width: ${widthLabel};"`;
            codeHTML += `            <th${widthStyle}${thStyle}>Task Name</th>\n`;
        }
        const taskWidthStyle = inheritParentCSS ? '' : (widthTask !== 'auto' ? ` style="width: ${widthTask};"` : '');
        codeHTML += `            <th${taskWidthStyle}${thStyle}>Task 1</th>\n`;
        codeHTML += `            <th${taskWidthStyle}${thStyle}>Task 2</th>\n`;
        codeHTML += `            <th${taskWidthStyle}${thStyle}>Task 3</th>\n`;
        codeHTML += '        </tr>\n';
        codeHTML += '    </thead>\n';
    }
    
    codeHTML += '    <tbody>\n';
    codeHTML += '        {{TaskCountLoop';
    
    // Build options for code display
    const codeOptions = [];
    const showOptions = [];
    if (showName) showOptions.push('name');
    if (showCount) showOptions.push('count');
    if (showStatus) showOptions.push('status');
    
    if (showOptions.length > 0) {
        codeOptions.push('show=' + showOptions.join(','));
    }
    
    if (!showLabels) {
        codeOptions.push('show_labels=no');
    }
    
    if (labelTaskName !== 'Task Name' || labelCount !== 'Count' || labelStatus !== 'Status') {
        codeOptions.push('labels=' + encodeURIComponent(labelTaskName) + ',' + encodeURIComponent(labelCount) + ',' + encodeURIComponent(labelStatus));
    }
    
    if (widthLabel !== '20%') {
        codeOptions.push('width_label=' + encodeURIComponent(widthLabel));
    }
    if (widthTask !== 'auto') {
        codeOptions.push('width_task=' + encodeURIComponent(widthTask));
    }
    
    // Add TH styling options
    if (thBgColor && thBgColor !== '#f8f9fa' && thBgColor !== 'transparent') {
        codeOptions.push('th_bg_color=' + encodeURIComponent(thBgColor));
    }
    if (thTextColor) {
        codeOptions.push('th_text_color=' + encodeURIComponent(thTextColor));
    }
    if (inheritParentCSS) {
        codeOptions.push('inherit_css=yes');
    }
    
    if (codeOptions.length > 0) {
        codeHTML += '|' + codeOptions.join('|');
    }
    codeHTML += '}}\n';
    codeHTML += '    </tbody>\n';
    codeHTML += '</table>';
    
    document.getElementById('taskCountLoopCodePreview').innerHTML = '<code class="language-html">' + escapeHtml(codeHTML) + '</code>';
    
    // Update generated code
    updateTaskCountLoopGeneratedCode();
}

// Update generated code for TaskCountLoop
function updateTaskCountLoopGeneratedCode() {
    const showName = document.getElementById('count_show_name').checked;
    const showCount = document.getElementById('count_show_count').checked;
    const showStatus = document.getElementById('count_show_status').checked;
    const showLabels = document.getElementById('count_show_labels').checked;
    
    const labelTaskName = document.getElementById('label_task_name').value || 'Task Name';
    const labelCount = document.getElementById('label_count').value || 'Count';
    const labelStatus = document.getElementById('label_status').value || 'Status';
    
    const widthLabel = document.getElementById('count_width_label').value || '20%';
    const widthTask = document.getElementById('count_width_task').value || 'auto';
    
    // Get TH styling options
    const thBgColor = document.getElementById('count_th_bg_color').value.trim() || '';
    const thTextColor = document.getElementById('count_th_text_color').value.trim() || '';
    const inheritParentCSS = document.getElementById('count_inherit_parent_css').checked;
    
    // Build placeholder with options
    let placeholder = '{{TaskCountLoop';
    const options = [];
    
    const showOptions = [];
    if (showName) showOptions.push('name');
    if (showCount) showOptions.push('count');
    if (showStatus) showOptions.push('status');
    
    if (showOptions.length > 0) {
        options.push('show=' + showOptions.join(','));
    }
    
    // Add label visibility
    if (!showLabels) {
        options.push('show_labels=no');
    }
    
    // Add custom labels if changed from default
    if (labelTaskName !== 'Task Name' || labelCount !== 'Count' || labelStatus !== 'Status') {
        options.push('labels=' + encodeURIComponent(labelTaskName) + ',' + encodeURIComponent(labelCount) + ',' + encodeURIComponent(labelStatus));
    }
    
    // Add widths
    if (widthLabel !== '20%') {
        options.push('width_label=' + encodeURIComponent(widthLabel));
    }
    if (widthTask !== 'auto') {
        options.push('width_task=' + encodeURIComponent(widthTask));
    }
    
    // Add TH styling options
    if (thBgColor && thBgColor !== '#f8f9fa' && thBgColor !== 'transparent') {
        options.push('th_bg_color=' + encodeURIComponent(thBgColor));
    }
    if (thTextColor) {
        options.push('th_text_color=' + encodeURIComponent(thTextColor));
    }
    if (inheritParentCSS) {
        options.push('inherit_css=yes');
    }
    
    if (options.length > 0) {
        placeholder += '|' + options.join('|');
    }
    placeholder += '}}';
    
    document.getElementById('taskCountLoopGeneratedCode').value = placeholder;
}

// Copy TaskCountLoop code to clipboard
function copyTaskCountLoopCode() {
    const codeTextarea = document.getElementById('taskCountLoopGeneratedCode');
    codeTextarea.select();
    document.execCommand('copy');
    
    // Show feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

// Insert TaskCountLoop placeholder with options
function insertTaskCountLoopWithOptions() {
    // Get the generated code from the textarea
    const placeholder = document.getElementById('taskCountLoopGeneratedCode').value;
    
    if (!placeholder) {
        alert('Please configure the table first');
        return;
    }
    
    // Insert placeholder - use saved cursor position
    if (currentMode === 'code') {
        const textarea = document.getElementById('template_html');
        const modal = document.getElementById('taskCountLoopModal');
        
        // Get saved cursor position from modal
        let start = 0;
        let end = 0;
        
        if (modal) {
            const savedStart = modal.getAttribute('data-cursor-start');
            const savedEnd = modal.getAttribute('data-cursor-end');
            if (savedStart !== null && savedEnd !== null) {
                start = parseInt(savedStart, 10);
                end = parseInt(savedEnd, 10);
            } else {
                // Fallback: use current selection
                start = textarea.selectionStart;
                end = textarea.selectionEnd;
            }
        } else {
            // Fallback: use current selection
            start = textarea.selectionStart;
            end = textarea.selectionEnd;
        }
        
        const text = textarea.value;
        textarea.value = text.substring(0, start) + placeholder + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
        // Sync highlight after insertion
        if (typeof syncCodeHighlight === 'function') {
            syncCodeHighlight();
        }
    } else {
        // Visual mode
        const visualEditor = document.getElementById('visual_editor');
        const modal = document.getElementById('taskCountLoopModal');
        const selection = window.getSelection();
        
        // Try to restore saved selection if available
        let range = null;
        if (modal && modal.getAttribute('data-has-selection') === 'true') {
            // Try to restore from saved selection (if we saved it)
            // For now, use current selection or create new range
            if (selection.rangeCount > 0) {
                range = selection.getRangeAt(0);
            }
        }
        
        if (!range && selection.rangeCount > 0) {
            range = selection.getRangeAt(0);
        }
        
        if (range) {
            range.deleteContents();
            
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            
            range.insertNode(placeholderTag);
            
            // Move cursor after placeholder
            range.setStartAfter(placeholderTag);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            // Append at end if no selection
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            visualEditor.appendChild(placeholderTag);
            
            // Move cursor after placeholder
            const newRange = document.createRange();
            newRange.setStartAfter(placeholderTag);
            newRange.collapse(true);
            selection.removeAllRanges();
            selection.addRange(newRange);
        }
        
        visualEditor.focus();
    }
    
    // Close modal
    const modalElement = bootstrap.Modal.getInstance(document.getElementById('taskCountLoopModal'));
    if (modalElement) {
        modalElement.hide();
    }
}

// Text formatting functions for visual editor
function formatText(command) {
    if (currentMode === 'visual') {
        document.execCommand(command, false, null);
        document.getElementById('visual_editor').focus();
    }
}

function setAlignment(align) {
    if (currentMode === 'visual') {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            let element = range.commonAncestorContainer;
            
            if (element.nodeType !== 1) {
                element = element.parentElement;
            }
            
            // Find or create block element
            while (element && !['DIV', 'P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6'].includes(element.tagName)) {
                element = element.parentElement;
            }
            
            if (element) {
                element.style.textAlign = align;
            } else {
                const div = document.createElement('div');
                div.style.textAlign = align;
                div.appendChild(range.extractContents());
                range.insertNode(div);
            }
        }
    }
}

// Insert placeholder directly
function insertPlaceholderDirect(placeholder) {
    if (currentMode === 'code') {
        const textarea = document.getElementById('template_html');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        textarea.value = text.substring(0, start) + placeholder + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + placeholder.length, start + placeholder.length);
    } else {
        // Visual mode
        const visualEditor = document.getElementById('visual_editor');
        const selection = window.getSelection();
        
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            
            range.insertNode(placeholderTag);
            
            // Move cursor after placeholder
            range.setStartAfter(placeholderTag);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            // Append at end
            const placeholderTag = document.createElement('span');
            placeholderTag.className = 'placeholder-tag';
            placeholderTag.setAttribute('data-placeholder', placeholder);
            placeholderTag.textContent = placeholder;
            placeholderTag.contentEditable = 'false';
            visualEditor.appendChild(placeholderTag);
        }
        
        visualEditor.focus();
    }
}

function insertTable() {
    if (currentMode === 'visual') {
        const rows = prompt('Number of rows:', '3');
        const cols = prompt('Number of columns:', '3');
        
        if (rows && cols) {
            const table = document.createElement('table');
            table.style.borderCollapse = 'collapse';
            table.style.width = '100%';
            table.style.margin = '10px 0';
            table.style.border = '1px solid #ddd';
            
            for (let i = 0; i < parseInt(rows); i++) {
                const tr = document.createElement('tr');
                for (let j = 0; j < parseInt(cols); j++) {
                    const td = document.createElement('td');
                    td.style.border = '1px solid #ddd';
                    td.style.padding = '8px';
                    td.textContent = '';
                    tr.appendChild(td);
                }
                table.appendChild(tr);
            }
            
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                range.insertNode(table);
            } else {
                document.getElementById('visual_editor').appendChild(table);
            }
        }
    }
}

// Preview template
function previewTemplate() {
    // Sync current mode to code first
    if (currentMode === 'visual') {
        syncVisualToCode();
    }
    
    const html = document.getElementById('template_html').value;
    if (!html.trim()) {
        alert('Please enter HTML template first');
        return;
    }
    
    // Replace placeholders with sample data for preview
    let previewHtml = html;
    const sampleData = {
        '{{application_number}}': 'APP-12345',
        '{{client_name}}': 'Sample Client',
        '{{product}}': 'Home Loan',
        '{{region}}': 'North',
        '{{state}}': 'Delhi',
        '{{branch}}': 'Main Branch',
        '{{location}}': 'New Delhi',
        '{{loan_amount}}': '50,00,000',
        '{{sample_date}}': '<?php echo date('d-m-Y', strtotime('-5 days')); ?>',
        '{{pickup_date}}': '<?php echo date('d-m-Y', strtotime('-5 days')); ?>',
        '{{report_date}}': '<?php echo date('d-m-Y'); ?>',
        '{{date_of_review}}': '<?php echo date('d-m-Y'); ?>',
        '{{receive_date}}': '<?php echo date('d-m-Y'); ?>',
        '{{tat_calculation}}': '5 Days',
        '{{current_date}}': '<?php echo date('d-m-Y'); ?>',
        '{{serial_no}}': '000123',
        '{{task_name}}': 'Residence Verification',
        '{{task_type}}': 'PHYSICAL',
        '{{task_remarks}}': 'Verified',
        '{{no_of_task}}': '3',
        '{{total_no_of_docs_sampled}}': '5',
        '{{document_particulars}}': 'Document 1',
        '{{document_type}}': 'Aadhar Card',
        '{{document_status}}': 'Verified',
        '{{document_remarks}}': 'Valid',
    };
    
    // Replace all placeholders
    Object.keys(sampleData).forEach(key => {
        previewHtml = previewHtml.replace(new RegExp(key.replace(/[{}]/g, '\\$&'), 'g'), sampleData[key]);
    });
    
    // Remove document loop markers for preview
    previewHtml = previewHtml.replace(/{{document_loop_start}}/g, '');
    previewHtml = previewHtml.replace(/{{document_loop_end}}/g, '');
    
    // Remove any remaining placeholders
    previewHtml = previewHtml.replace(/\{\{[^}]+\}\}/g, '[PLACEHOLDER]');
    
    document.getElementById('previewContent').innerHTML = previewHtml;
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

// Save template
function saveTemplate() {
    // Sync current mode to code first
    if (currentMode === 'visual') {
        syncVisualToCode();
    }
    
    const form = document.getElementById('templateForm');
    const formData = new FormData(form);
    formData.append('action', document.getElementById('template_id').value > 0 ? 'update' : 'create');
    formData.append('description', '');
    
    fetch('template_save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Template saved successfully!');
            if (data.id && document.getElementById('template_id').value == 0) {
                window.location.href = 'template_editor.php?id=' + data.id;
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Initialize: Load visual editor content if editing (default mode is visual)
document.addEventListener('DOMContentLoaded', function() {
    const templateHtml = document.getElementById('template_html').value;
    const visualEditor = document.getElementById('visual_editor');
    
    // Set visual mode as default
    if (currentMode === 'visual') {
        const codeMode = document.getElementById('codeMode');
        const visualMode = document.getElementById('visualMode');
        const codeBtn = document.getElementById('codeModeBtn');
        const visualBtn = document.getElementById('visualModeBtn');
        
        if (codeMode && visualMode && codeBtn && visualBtn) {
            codeMode.style.display = 'none';
            visualMode.style.display = 'block';
            codeBtn.classList.remove('active');
            visualBtn.classList.add('active');
        }
    }
    
    if (templateHtml && templateHtml.trim()) {
        // Pre-convert placeholders for visual mode
        let visualHtml = templateHtml;
        visualHtml = visualHtml.replace(/\{\{([^}]+)\}\}/g, function(match, placeholder) {
            return '<span class="placeholder-tag" data-placeholder="' + match + '">' + match + '</span>';
        });
        visualEditor.innerHTML = visualHtml;
    } else {
        visualEditor.innerHTML = '<p>Start editing your template here. Click placeholders on the left to insert them.</p>';
    }
    
    // Prevent placeholder tags from being edited directly
    visualEditor.addEventListener('keydown', function(e) {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            let node = range.startContainer;
            
            // Check if we're inside a placeholder tag
            while (node && node !== visualEditor) {
                if (node.nodeType === 1 && node.classList && node.classList.contains('placeholder-tag')) {
                    // If trying to type inside placeholder, prevent it
                    if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                        e.preventDefault();
                        // Move cursor after placeholder
                        const range = document.createRange();
                        range.setStartAfter(node);
                        range.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                    break;
                }
                node = node.parentNode;
            }
        }
    });
    
    // Make placeholder tags non-editable
    visualEditor.addEventListener('click', function(e) {
        if (e.target.classList.contains('placeholder-tag')) {
            // Select the placeholder
            const range = document.createRange();
            range.selectNode(e.target);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    });
    
    // Update placeholder tags to be non-editable
    function makePlaceholdersNonEditable() {
        const placeholderTags = visualEditor.querySelectorAll('.placeholder-tag');
        placeholderTags.forEach(tag => {
            tag.contentEditable = 'false';
        });
    }
    
    // Observer to handle dynamically added placeholders
    const observer = new MutationObserver(function(mutations) {
        makePlaceholdersNonEditable();
    });
    
    observer.observe(visualEditor, {
        childList: true,
        subtree: true
    });
    
    makePlaceholdersNonEditable();
});

// Load client placeholders
function loadClientPlaceholders(clientId) {
    if (!clientId) {
        return;
    }
    
    // Reload page with client_id to get client meta placeholders
    window.location.href = 'template_editor.php?client_id=' + clientId + (document.getElementById('template_id').value > 0 ? '&id=' + document.getElementById('template_id').value : '');
}

// Filter placeholders by search term
function filterPlaceholders(searchTerm) {
    const searchLower = searchTerm.toLowerCase().trim();
    const placeholderItems = document.querySelectorAll('.placeholder-item');
    const categoryHeaders = document.querySelectorAll('.list-group-item.bg-light');
    
    if (!searchLower) {
        // Show all
        placeholderItems.forEach(item => {
            item.style.display = '';
        });
        categoryHeaders.forEach(header => {
            header.style.display = '';
        });
        return;
    }
    
    let visibleCount = 0;
    let currentCategory = null;
    let categoryHasVisible = false;
    
    placeholderItems.forEach(item => {
        const searchText = item.getAttribute('data-search') || '';
        if (searchText.includes(searchLower)) {
            item.style.display = '';
            visibleCount++;
            
            // Show parent category
            let parent = item.previousElementSibling;
            while (parent && !parent.classList.contains('bg-light')) {
                parent = parent.previousElementSibling;
            }
            if (parent) {
                parent.style.display = '';
            }
        } else {
            item.style.display = 'none';
        }
    });
    
    // Hide categories with no visible items
    categoryHeaders.forEach(header => {
        let hasVisible = false;
        let next = header.nextElementSibling;
        while (next && !next.classList.contains('bg-light')) {
            if (next.classList.contains('placeholder-item') && next.style.display !== 'none') {
                hasVisible = true;
                break;
            }
            next = next.nextElementSibling;
        }
        if (!hasVisible) {
            header.style.display = 'none';
        }
    });
}

// Test/Bind data for placeholders
function testPlaceholderBinding() {
    // Sync current mode to code first
    if (currentMode === 'visual') {
        syncVisualToCode();
    }
    
    const html = document.getElementById('template_html').value;
    if (!html.trim()) {
        alert('Please enter HTML template first');
        return;
    }
    
    // Show modal with placeholder mapping
    showPlaceholderMappingModal(html);
}

// Show placeholder mapping modal
function showPlaceholderMappingModal(html) {
    // Extract all placeholders from HTML
    const placeholderRegex = /\{\{([^}]+)\}\}/g;
    const placeholders = [];
    let match;
    
    while ((match = placeholderRegex.exec(html)) !== null) {
        const placeholder = match[1];
        if (!placeholders.includes(placeholder)) {
            placeholders.push(placeholder);
        }
    }
    
    if (placeholders.length === 0) {
        alert('No placeholders found in template');
        return;
    }
    
    // Create modal content
    let modalContent = '<div class="table-responsive"><table class="table table-sm table-bordered">';
    modalContent += '<thead><tr><th>Placeholder</th><th>Data Source</th><th>Sample Value</th></tr></thead><tbody>';
    
    placeholders.forEach(placeholder => {
        const dataSource = getPlaceholderDataSource(placeholder);
        const sampleValue = getPlaceholderSampleValue(placeholder);
        
        modalContent += '<tr>';
        modalContent += '<td><code>{{' + placeholder + '}}</code></td>';
        modalContent += '<td>' + dataSource + '</td>';
        modalContent += '<td>' + (sampleValue || '<span class="text-muted">N/A</span>') + '</td>';
        modalContent += '</tr>';
    });
    
    modalContent += '</tbody></table></div>';
    
    // Update preview modal to show mapping
    document.getElementById('previewContent').innerHTML = modalContent;
    document.querySelector('#previewModal .modal-title').textContent = 'Placeholder Data Mapping';
    
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

// Get data source for placeholder
function getPlaceholderDataSource(placeholder) {
    const placeholderLower = placeholder.toLowerCase();
    
    // Case information
    if (['application_number', 'product', 'region', 'state', 'branch', 'location', 'loan_amount', 'sample_date', 'pickup_date'].includes(placeholderLower)) {
        return '<span class="badge bg-primary">Case Data</span>';
    }
    
    // Client information
    if (placeholderLower === 'client_name' || placeholderLower.startsWith('client_')) {
        return '<span class="badge bg-info">Client Data</span>';
    }
    
    // System generated (dates and calculations)
    if (['current_date', 'report_date', 'date_of_review', 'receive_date', 'tat_calculation', 'serial_no', 'total_no_of_docs_sampled'].includes(placeholderLower)) {
        return '<span class="badge bg-success">System Generated</span>';
    }
    
    // Task information
    if (['task_name', 'task_type', 'task_remarks', 'no_of_task'].includes(placeholderLower)) {
        return '<span class="badge bg-warning">Task Data</span>';
    }
    
    // Document loop
    if (placeholderLower.startsWith('document_')) {
        return '<span class="badge bg-secondary">Document Loop</span>';
    }
    
    // Client meta (default assumption)
    return '<span class="badge bg-secondary">Client Meta</span>';
}

// Get sample value for placeholder
function getPlaceholderSampleValue(placeholder) {
    const placeholderLower = placeholder.toLowerCase();
    const sampleData = {
        'application_number': 'APP-12345',
        'client_name': 'Sample Client Name',
        'product': 'Home Loan',
        'region': 'North Region',
        'state': 'Delhi',
        'branch': 'Main Branch',
        'location': 'New Delhi',
        'loan_amount': '50,00,000',
        'sample_date': '<?php echo date('d-m-Y', strtotime('-5 days')); ?>',
        'pickup_date': '<?php echo date('d-m-Y', strtotime('-5 days')); ?>',
        'report_date': '<?php echo date('d-m-Y'); ?>',
        'date_of_review': '<?php echo date('d-m-Y'); ?>',
        'receive_date': '<?php echo date('d-m-Y'); ?>',
        'tat_calculation': '5 Days',
        'current_date': '<?php echo date('d-m-Y'); ?>',
        'serial_no': '000123',
        'task_name': 'Residence Verification',
        'task_type': 'PHYSICAL',
        'task_remarks': 'Verified Successfully',
        'no_of_task': '3',
        'total_no_of_docs_sampled': '5',
        'document_particulars': 'Document 1',
        'document_type': 'Aadhar Card',
        'document_status': 'Verified',
        'document_remarks': 'Valid Document',
    };
    
    return sampleData[placeholderLower] || 'Sample Data';
}

// Generate report from template editor
function generateReportFromTemplate(templateId) {
    // Prompt for case_id (required to generate actual report data)
    const caseId = prompt('To generate a report with actual data, please enter the Case ID:\n\n(Leave empty to generate with sample data only)');
    
    if (caseId === null) {
        // User cancelled
        return;
    }
    
    let url = 'generate_report.php?template_id=' + templateId;
    if (caseId && caseId.trim() !== '') {
        url += '&case_id=' + encodeURIComponent(caseId.trim());
    }
    
    // Open in new window
    window.open(url, '_blank');
}

// Sync textarea with code highlight
function syncCodeHighlight() {
    const textarea = document.getElementById('template_html');
    const highlight = document.getElementById('codeHighlight');
    const code = highlight ? highlight.querySelector('code') : null;
    
    if (textarea && code) {
        const text = textarea.value;
        // Set the text content
        code.textContent = text;
        
        // Highlight the code if Highlight.js is available
        if (typeof hljs !== 'undefined') {
            try {
                // Use manual highlighting for better control
                const highlighted = hljs.highlight(text, { language: 'html' });
                code.innerHTML = highlighted.value;
                code.className = 'language-html hljs';
            } catch(e) {
                // If manual highlighting fails, try highlightElement
                try {
                    code.className = 'language-html';
                    hljs.highlightElement(code);
                } catch(e2) {
                    console.error('Highlight.js error:', e2);
                    // Fallback: just show the text
                    code.textContent = text;
                    code.className = 'language-html';
                }
            }
        } else {
            // Fallback if Highlight.js not loaded
            code.textContent = text;
            code.className = 'language-html';
        }
        
        // Sync scroll
        if (highlight) {
            highlight.scrollTop = textarea.scrollTop;
            highlight.scrollLeft = textarea.scrollLeft;
        }
    }
}
</script>

<!-- Prism.js for syntax highlighting -->
<!-- Highlight.js for syntax highlighting - Better Bootstrap compatibility -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
<style>
/* Highlight.js code editor styling */
#codeHighlight {
    background: #0d1117 !important;
    color: #c9d1d9 !important;
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
    padding: 12px !important;
    margin: 0 !important;
    font-family: 'Courier New', monospace !important;
    font-size: 13px !important;
    line-height: 1.5 !important;
    min-height: 400px !important;
    overflow: auto !important;
}
#codeHighlight code {
    background: transparent !important;
    padding: 0 !important;
    font-size: 13px !important;
    line-height: 1.5 !important;
    color: #c9d1d9 !important;
    font-family: 'Courier New', monospace !important;
}
#codeHighlight pre {
    background: #0d1117 !important;
    color: #c9d1d9 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
}
/* Ensure all text is visible */
#codeHighlight * {
    color: inherit !important;
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/html.min.js"></script>
<script>
// Initialize Highlight.js
if (typeof hljs !== 'undefined') {
    hljs.configure({ 
        languages: ['html', 'xml'],
        ignoreUnescapedHTML: true
    });
}

// Update highlight on textarea input
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('template_html');
    if (textarea) {
        // Initial sync
        setTimeout(() => {
            syncCodeHighlight();
        }, 100);
        
        // Sync on input
        textarea.addEventListener('input', function() {
            syncCodeHighlight();
        });
        
        // Sync on scroll
        textarea.addEventListener('scroll', function() {
            const highlight = document.getElementById('codeHighlight');
            if (highlight) {
                highlight.scrollTop = textarea.scrollTop;
                highlight.scrollLeft = textarea.scrollLeft;
            }
        });
        
        // Sync textarea height with highlight
        function syncHeight() {
            const highlight = document.getElementById('codeHighlight');
            if (highlight && currentMode === 'code') {
                const newHeight = Math.max(highlight.scrollHeight, 400);
                textarea.style.height = newHeight + 'px';
                highlight.style.height = newHeight + 'px';
            }
        }
        
        textarea.addEventListener('input', syncHeight);
        setTimeout(syncHeight, 200);
    }
});
</script>

<?php require_once('../system/footer.php'); ?>

