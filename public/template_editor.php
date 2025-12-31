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
        'report_date' => 'Report Date',
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
                                    <option value="STANDARD" <?php echo (($template['template_type'] ?? 'STANDARD') == 'STANDARD') ? 'selected' : ''; ?>>Standard</option>
                                    <option value="CUSTOM" <?php echo (($template['template_type'] ?? '') == 'CUSTOM') ? 'selected' : ''; ?>>Custom</option>
                                    <option value="TASK_SPECIFIC" <?php echo (($template['template_type'] ?? '') == 'TASK_SPECIFIC') ? 'selected' : ''; ?>>Task Specific</option>
                                </select>
                            </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">HTML Template</label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary active" id="codeModeBtn" onclick="switchMode('code')">
                                            <i class="fas fa-code"></i> Code
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" id="visualModeBtn" onclick="switchMode('visual')">
                                            <i class="fas fa-eye"></i> Visual
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Code Mode -->
                                <div id="codeMode" class="editor-mode">
                                    <textarea name="template_html" id="template_html" class="form-control" rows="20" 
                                              placeholder="Paste your HTML template here. Click placeholders on the left to insert them."><?php echo htmlspecialchars($template['template_html'] ?? ''); ?></textarea>
                                </div>
                                
                                <!-- Visual Mode -->
                                <div id="visualMode" class="editor-mode" style="display: none;">
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
// Current editor mode
let currentMode = 'code';

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
        
        // Focus on code editor
        setTimeout(() => {
            document.getElementById('template_html').focus();
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
        '{{sample_date}}': '<?php echo date('d-m-Y'); ?>',
        '{{report_date}}': '<?php echo date('d-m-Y'); ?>',
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

// Initialize: Load visual editor content if editing
document.addEventListener('DOMContentLoaded', function() {
    const templateHtml = document.getElementById('template_html').value;
    const visualEditor = document.getElementById('visual_editor');
    
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
    if (['application_number', 'product', 'region', 'state', 'branch', 'location', 'loan_amount', 'sample_date', 'report_date'].includes(placeholderLower)) {
        return '<span class="badge bg-primary">Case Data</span>';
    }
    
    // Client information
    if (placeholderLower === 'client_name' || placeholderLower.startsWith('client_')) {
        return '<span class="badge bg-info">Client Data</span>';
    }
    
    // System generated
    if (['current_date', 'report_date', 'serial_no', 'total_no_of_docs_sampled'].includes(placeholderLower)) {
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
        'sample_date': '<?php echo date('d-m-Y'); ?>',
        'report_date': '<?php echo date('d-m-Y'); ?>',
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
</script>

<?php require_once('../system/footer.php'); ?>

