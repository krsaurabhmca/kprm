<?php
/**
 * KPRM - Report Template Visual Designer
 * Enhanced Excel-like visual designer for report templates
 */

require_once('../system/header.php');

// Check if table exists
global $con;
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'report_templates'");
$table_exists = ($table_check && mysqli_num_rows($table_check) > 0);

if (!$table_exists) {
    echo '<div class="alert alert-warning">';
    echo '<i class="fas fa-exclamation-triangle"></i> ';
    echo '<strong>Database tables not found!</strong> Please run: <code>SOURCE db/create_report_templates_table.sql;</code> in your database.';
    echo '</div>';
    require_once('../system/footer.php');
    exit;
}

$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

// Get template data if exists
$template_data = null;
$client_id = 0;
if ($template_id > 0) {
    $template_result = get_data('report_templates', $template_id);
    if ($template_result['count'] > 0) {
        $template_data = $template_result['data'];
        $client_id = isset($template_data['client_id']) ? intval($template_data['client_id']) : 0;
    }
}

// Get client meta fields for this client
$client_meta_variables = [];
if ($client_id > 0) {
    $client_meta_sql = "
        SELECT field_name, display_name, input_type 
        FROM clients_meta 
        WHERE client_id = '$client_id' 
          AND status = 'ACTIVE'
        ORDER BY id ASC
    ";
    $client_meta_res = mysqli_query($con, $client_meta_sql);
    if ($client_meta_res) {
        while ($row = mysqli_fetch_assoc($client_meta_res)) {
            $client_meta_variables[] = $row;
        }
    }
}

// Get available variables from tasks_meta
global $con;
$available_variables = [];
$variables_sql = "
    SELECT DISTINCT field_name, display_name, input_type 
    FROM tasks_meta 
    WHERE status = 'ACTIVE' 
    ORDER BY field_name
";
$variables_res = mysqli_query($con, $variables_sql);
if ($variables_res) {
    while ($row = mysqli_fetch_assoc($variables_res)) {
        $available_variables[] = $row;
    }
}

// Task loop variables (simplified - only essential fields)
$task_loop_variables = [
    ['field_name' => 'task_loop_start', 'display_name' => 'Task Loop Start', 'input_type' => 'LOOP_START', 'description' => 'Start loop for all tasks'],
    ['field_name' => 'task_loop_end', 'display_name' => 'Task Loop End', 'input_type' => 'LOOP_END', 'description' => 'End loop for all tasks'],
    ['field_name' => 'task_serial', 'display_name' => 'Serial Number (Optional)', 'input_type' => 'NUMBER', 'description' => 'Auto-incrementing serial number - optional'],
    ['field_name' => 'task_type', 'display_name' => 'Task Type', 'input_type' => 'TEXT', 'description' => 'Task type from task template'],
    ['field_name' => 'task_name_loop', 'display_name' => 'Task Name', 'input_type' => 'TEXT', 'description' => 'Task name'],
    ['field_name' => 'task_remarks_loop', 'display_name' => 'Task Remarks', 'input_type' => 'TEXTAREA', 'description' => 'Task remarks/findings'],
    ['field_name' => 'task_status_loop', 'display_name' => 'Task Status', 'input_type' => 'TEXT', 'description' => 'Task status (PENDING, IN_PROGRESS, etc.)'],
];

// Summary variables (for use anywhere in template)
$summary_variables = [
    ['field_name' => 'all_task_remarks', 'display_name' => 'All Task Remarks', 'input_type' => 'TEXTAREA', 'description' => 'All task remarks combined'],
    ['field_name' => 'all_attachments_display', 'display_name' => 'All Attachments (for Report)', 'input_type' => 'HTML', 'description' => 'All attachments marked for report display'],
    ['field_name' => 'all_tasks_list', 'display_name' => 'All Tasks List', 'input_type' => 'TEXT', 'description' => 'List of all task names'],
    ['field_name' => 'task_count', 'display_name' => 'Total Task Count', 'input_type' => 'NUMBER', 'description' => 'Total number of tasks'],
    ['field_name' => 'document_count', 'display_name' => 'Document Count', 'input_type' => 'NUMBER', 'description' => 'Number of documents/attachments'],
    ['field_name' => 'image_count', 'display_name' => 'Image Count', 'input_type' => 'NUMBER', 'description' => 'Number of images'],
];
?>

<style>
.template-designer-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 200px);
}

.excel-toolbar {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: 2px solid #007bff;
    padding: 8px 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 0;
}

.toolbar-group {
    display: flex;
    gap: 4px;
    padding: 0 8px;
    border-right: 1px solid #dee2e6;
    align-items: center;
}

.toolbar-group:last-child {
    border-right: none;
}

.toolbar-btn {
    padding: 6px 10px;
    border: 1px solid #ced4da;
    background: white;
    cursor: pointer;
    border-radius: 3px;
    font-size: 13px;
    min-width: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.toolbar-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.toolbar-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.toolbar-input {
    width: 70px;
    padding: 4px 6px;
    border: 1px solid #ced4da;
    border-radius: 3px;
    font-size: 12px;
}

.toolbar-label {
    font-size: 12px;
    margin-right: 4px;
    color: #495057;
    white-space: nowrap;
}

#designCanvas {
    flex: 1;
    overflow: auto;
    border: 1px solid #dee2e6;
    background: white;
    padding: 20px;
    min-height: 600px;
}

.selected-cell {
    outline: 3px solid #007bff !important;
    outline-offset: -3px;
    background-color: #e7f3ff !important;
}

.multi-selected-cell {
    outline: 2px solid #28a745 !important;
    outline-offset: -2px;
    background-color: #d4edda !important;
}

.editable-table {
    border-collapse: collapse;
    width: 100%;
    margin: 15px 0;
}

.editable-table td,
.editable-table th {
    border: 1px solid #000;
    padding: 8px;
    min-width: 80px;
    min-height: 30px;
    position: relative;
}

.editable-table td:focus,
.editable-table th:focus {
    outline: 3px solid #007bff;
    outline-offset: -3px;
}

.variable-palette {
    max-height: 250px;
    overflow-y: auto;
}
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Left Sidebar: Components & Variables -->
        <div class="col-md-3">
            <div class="card sticky-top" style="top: 20px; max-height: calc(100vh - 40px); overflow-y: auto;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-puzzle-piece"></i> Components</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Layout Elements</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTable()">
                                <i class="fas fa-table"></i> Insert Table
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComponent('header')">
                                <i class="fas fa-heading"></i> Header
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComponent('text')">
                                <i class="fas fa-paragraph"></i> Text Block
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComponent('image')">
                                <i class="fas fa-image"></i> Image
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComponent('signature')">
                                <i class="fas fa-signature"></i> Signature
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComponent('divider')">
                                <i class="fas fa-minus"></i> Divider
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Placeholder Search -->
                    <div class="mb-3">
                        <h6>Search Placeholders</h6>
                        <input type="text" id="placeholderSearch" class="form-control form-control-sm" 
                            placeholder="Type to search placeholders..." 
                            onkeyup="filterPlaceholders(this.value)">
                    </div>
                    
                    <!-- Client Meta Variables -->
                    <?php if (!empty($client_meta_variables)): ?>
                    <div class="mb-3">
                        <h6>Client Meta Variables</h6>
                        <div class="variable-palette list-group" id="clientMetaVariables">
                            <?php foreach ($client_meta_variables as $var): ?>
                                <button type="button" class="list-group-item list-group-item-action btn-sm placeholder-item" 
                                    data-search="<?php echo strtolower($var['field_name'] . ' ' . $var['display_name']); ?>"
                                    onclick="insertVariable('<?php echo $var['field_name']; ?>')">
                                    <strong>#<?php echo $var['field_name']; ?>#</strong><br>
                                    <small><?php echo $var['display_name']; ?></small>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Task Loop Variables -->
                    <div class="mb-3">
                        <h6>Task Variables <span class="badge bg-info">Loop</span></h6>
                        <div class="variable-palette list-group" id="taskLoopVariables">
                            <?php foreach ($task_loop_variables as $var): ?>
                                <button type="button" class="list-group-item list-group-item-action btn-sm placeholder-item" 
                                    data-search="<?php echo strtolower($var['field_name'] . ' ' . $var['display_name']); ?>"
                                    onclick="insertVariable('<?php echo $var['field_name']; ?>')">
                                    <strong>#<?php echo $var['field_name']; ?>#</strong><br>
                                    <small><?php echo $var['display_name']; ?></small>
                                    <?php if (isset($var['description'])): ?>
                                        <br><small class="text-muted" style="font-size: 10px;"><?php echo $var['description']; ?></small>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Summary Variables (Use Anywhere) -->
                    <div class="mb-3">
                        <h6>Summary Variables <span class="badge bg-success">Use Anywhere</span></h6>
                        <div class="variable-palette list-group" id="summaryVariables">
                            <?php foreach ($summary_variables as $var): ?>
                                <button type="button" class="list-group-item list-group-item-action btn-sm placeholder-item" 
                                    data-search="<?php echo strtolower($var['field_name'] . ' ' . $var['display_name']); ?>"
                                    onclick="insertVariable('<?php echo $var['field_name']; ?>')">
                                    <strong>#<?php echo $var['field_name']; ?>#</strong><br>
                                    <small><?php echo $var['display_name']; ?></small>
                                    <?php if (isset($var['description'])): ?>
                                        <br><small class="text-muted" style="font-size: 10px;"><?php echo $var['description']; ?></small>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Center: Design Canvas with Excel-like Editor -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-light p-0">
                    <div class="d-flex justify-content-between align-items-center p-2">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Template Designer</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-success" onclick="previewTemplate()">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="saveTemplate()">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                    
                    <!-- Excel-like Toolbar -->
                    <div class="excel-toolbar">
                        <!-- Text Formatting -->
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="btnBold" onclick="formatText('bold')" title="Bold (Ctrl+B)">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button class="toolbar-btn" id="btnItalic" onclick="formatText('italic')" title="Italic (Ctrl+I)">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button class="toolbar-btn" id="btnUnderline" onclick="formatText('underline')" title="Underline (Ctrl+U)">
                                <i class="fas fa-underline"></i>
                            </button>
                        </div>
                        
                        <!-- Alignment -->
                        <div class="toolbar-group">
                            <button class="toolbar-btn" onclick="setAlignment('left')" title="Align Left">
                                <i class="fas fa-align-left"></i>
                            </button>
                            <button class="toolbar-btn" onclick="setAlignment('center')" title="Align Center">
                                <i class="fas fa-align-center"></i>
                            </button>
                            <button class="toolbar-btn" onclick="setAlignment('right')" title="Align Right">
                                <i class="fas fa-align-right"></i>
                            </button>
                            <button class="toolbar-btn" onclick="setAlignment('justify')" title="Justify">
                                <i class="fas fa-align-justify"></i>
                            </button>
                        </div>
                        
                        <!-- Font Size -->
                        <div class="toolbar-group">
                            <span class="toolbar-label">Font:</span>
                            <select class="toolbar-input" id="fontSize" onchange="setFontSize(this.value)">
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12" selected>12</option>
                                <option value="14">14</option>
                                <option value="16">16</option>
                                <option value="18">18</option>
                                <option value="20">20</option>
                                <option value="24">24</option>
                                <option value="28">28</option>
                                <option value="32">32</option>
                            </select>
                        </div>
                        
                        <!-- Text Color -->
                        <div class="toolbar-group">
                            <span class="toolbar-label">Text:</span>
                            <input type="color" class="toolbar-input" id="textColor" value="#000000" onchange="setTextColor(this.value)" title="Text Color" style="width: 50px; height: 28px; padding: 2px;">
                        </div>
                        
                        <!-- Background Color -->
                        <div class="toolbar-group">
                            <span class="toolbar-label">Fill:</span>
                            <input type="color" class="toolbar-input" id="bgColor" value="#ffffff" onchange="setBgColor(this.value)" title="Background Color" style="width: 50px; height: 28px; padding: 2px;">
                        </div>
                        
                        <!-- Cell Management -->
                        <div class="toolbar-group">
                            <button class="toolbar-btn" onclick="mergeCells()" title="Merge Cells">
                                <i class="fas fa-compress"></i> Merge
                            </button>
                            <button class="toolbar-btn" onclick="splitCell()" title="Split Cell">
                                <i class="fas fa-expand"></i> Split
                            </button>
                        </div>
                        
                        <!-- Borders -->
                        <div class="toolbar-group">
                            <span class="toolbar-label">Border:</span>
                            <select class="toolbar-input" id="borderStyle" onchange="setBorder(this.value)">
                                <option value="none">None</option>
                                <option value="1px solid #000" selected>Thin</option>
                                <option value="2px solid #000">Medium</option>
                                <option value="3px solid #000">Thick</option>
                                <option value="1px dashed #000">Dashed</option>
                                <option value="1px dotted #000">Dotted</option>
                            </select>
                        </div>
                        
                        <!-- Cell Size -->
                        <div class="toolbar-group">
                            <span class="toolbar-label">W:</span>
                            <input type="number" class="toolbar-input" id="cellWidth" value="100" min="20" max="500" onchange="setCellWidth(this.value)" placeholder="px" style="width: 60px;">
                            <span class="toolbar-label">H:</span>
                            <input type="number" class="toolbar-input" id="cellHeight" value="30" min="20" max="200" onchange="setCellHeight(this.value)" placeholder="px" style="width: 60px;">
                        </div>
                        
                        <!-- Text Wrap -->
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="wrapBtn" onclick="toggleTextWrap()" title="Text Wrap">
                                <i class="fas fa-text-width"></i> Wrap
                            </button>
                        </div>
                        
                        <!-- Excel Paste -->
                        <div class="toolbar-group">
                            <button class="toolbar-btn" onclick="pasteExcel()" title="Paste Excel Data">
                                <i class="fas fa-paste"></i> Paste Excel
                            </button>
                        </div>
                        
                        <!-- Apply to Selected -->
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="applyToSelectedBtn" onclick="applyToSelected()" title="Apply Current Properties to All Selected Cells" disabled>
                                <i class="fas fa-check-double"></i> Apply to Selected
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <!-- Design Canvas -->
                    <div class="template-designer-wrapper">
                        <div id="designCanvas" contenteditable="true">
                            <?php if ($template_data): ?>
                                <?php echo $template_data['template_html']; ?>
                            <?php else: ?>
                                <div class="text-center text-muted p-5">
                                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                                    <p>Start designing your template by inserting a table or adding components</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../system/footer.php'); ?>

<script>
var templateId = <?php echo $template_id; ?>;
var selectedCell = null;
var selectedCells = []; // Array for multi-selection
var textWrapEnabled = false;
var isMultiSelectMode = false;

// Initialize table editing when table is created or loaded
function initTableEditor(table) {
    if (!table) return;
    
    var cells = table.querySelectorAll('td, th');
    cells.forEach(function(cell) {
        cell.contentEditable = true;
        cell.addEventListener('click', function(e) {
            e.stopPropagation();
            var isMulti = e.ctrlKey || e.metaKey || e.shiftKey;
            selectCell(this, isMulti);
        });
        cell.addEventListener('focus', function(e) {
            e.stopPropagation();
            if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
                selectCell(this, false);
            }
        });
        cell.addEventListener('blur', function() {
            // Keep selection on blur for toolbar updates
        });
    });
}

function selectCell(cell, isMultiSelect = false) {
    if (!isMultiSelect) {
        // Clear all selections
        clearAllSelections();
        selectedCells = [];
    }
    
    // Remove previous single selection
    if (selectedCell && !isMultiSelect) {
        selectedCell.classList.remove('selected-cell');
    }
    
    // Add to selection
    if (isMultiSelect && selectedCells.indexOf(cell) === -1) {
        selectedCells.push(cell);
        cell.classList.add('multi-selected-cell');
    } else if (!isMultiSelect) {
        selectedCell = cell;
        selectedCells = [cell];
        cell.classList.add('selected-cell');
    }
    
    // Update toolbar with cell properties
    updateToolbar(cell);
    
    // Update apply button state
    updateApplyButton();
    
    // Focus the cell
    if (!isMultiSelect) {
        cell.focus();
    }
}

function clearAllSelections() {
    if (selectedCell) {
        selectedCell.classList.remove('selected-cell');
    }
    selectedCells.forEach(function(cell) {
        cell.classList.remove('multi-selected-cell');
        cell.classList.remove('selected-cell');
    });
    selectedCell = null;
    selectedCells = [];
}

function updateApplyButton() {
    var applyBtn = document.getElementById('applyToSelectedBtn');
    if (applyBtn) {
        applyBtn.disabled = (selectedCells.length <= 1);
    }
}

function updateToolbar(cell) {
    if (!cell) return;
    
    var style = window.getComputedStyle(cell);
    
    // Update font size
    var fontSize = parseInt(style.fontSize);
    var fontSizeSelect = document.getElementById('fontSize');
    if (fontSizeSelect) {
        fontSizeSelect.value = fontSize || 12;
    }
    
    // Update text color
    var textColor = style.color;
    var textColorInput = document.getElementById('textColor');
    if (textColorInput) {
        textColorInput.value = rgbToHex(textColor);
    }
    
    // Update background color
    var bgColor = style.backgroundColor;
    var bgColorInput = document.getElementById('bgColor');
    if (bgColorInput) {
        bgColorInput.value = rgbToHex(bgColor);
    }
    
    // Update alignment buttons
    var textAlign = style.textAlign || 'left';
    document.querySelectorAll('.toolbar-btn').forEach(btn => {
        if (btn.onclick && btn.onclick.toString().includes("setAlignment")) {
            btn.classList.remove('active');
        }
    });
    var alignBtn = document.querySelector(`[onclick*="setAlignment('${textAlign}')"]`);
    if (alignBtn) alignBtn.classList.add('active');
    
    // Update text wrap
    var whiteSpace = style.whiteSpace;
    textWrapEnabled = (whiteSpace !== 'nowrap');
    var wrapBtn = document.getElementById('wrapBtn');
    if (wrapBtn) {
        wrapBtn.classList.toggle('active', textWrapEnabled);
    }
    
    // Update cell dimensions
    var cellWidth = document.getElementById('cellWidth');
    var cellHeight = document.getElementById('cellHeight');
    if (cellWidth) {
        cellWidth.value = parseInt(style.width) || parseInt(cell.offsetWidth) || 100;
    }
    if (cellHeight) {
        cellHeight.value = parseInt(style.height) || parseInt(cell.offsetHeight) || 30;
    }
    
    // Update format buttons (bold, italic, underline)
    updateFormatButtons(cell);
}

function updateFormatButtons(cell) {
    // Check if cell has bold
    var isBold = window.getComputedStyle(cell).fontWeight >= 600 || 
                 cell.querySelector('strong, b') !== null ||
                 cell.style.fontWeight === 'bold';
    document.getElementById('btnBold').classList.toggle('active', isBold);
    
    // Check if cell has italic
    var isItalic = window.getComputedStyle(cell).fontStyle === 'italic' ||
                   cell.querySelector('em, i') !== null ||
                   cell.style.fontStyle === 'italic';
    document.getElementById('btnItalic').classList.toggle('active', isItalic);
    
    // Check if cell has underline
    var isUnderline = window.getComputedStyle(cell).textDecoration.includes('underline') ||
                      cell.style.textDecoration.includes('underline');
    document.getElementById('btnUnderline').classList.toggle('active', isUnderline);
}

function rgbToHex(rgb) {
    if (!rgb || rgb === 'transparent' || rgb === 'rgba(0, 0, 0, 0)') return '#ffffff';
    var match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    if (!match) {
        // Try rgba
        match = rgb.match(/^rgba\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) return '#000000';
    }
    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return "#" + hex(match[1]) + hex(match[2]) + hex(match[3]);
}

// Text Formatting Functions
function formatText(command) {
    if (selectedCells.length === 0) {
        // Try to format selected text in canvas
        document.execCommand(command, false, null);
        return;
    }
    
    // Apply to all selected cells
    applyToSelectedCells(function(cell) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(cell);
        selection.removeAllRanges();
        selection.addRange(range);
        document.execCommand(command, false, null);
        selection.removeAllRanges();
    });
    
    if (selectedCell) {
        updateFormatButtons(selectedCell);
    }
}

function setAlignment(align) {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    applyToSelectedCells(function(cell) {
        cell.style.textAlign = align;
    });
    if (selectedCell) {
        updateToolbar(selectedCell);
    }
}

function setFontSize(size) {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    applyToSelectedCells(function(cell) {
        cell.style.fontSize = size + 'px';
    });
}

function setTextColor(color) {
    if (selectedCells.length === 0) {
        // Apply to selected text
        document.execCommand('foreColor', false, color);
        return;
    }
    applyToSelectedCells(function(cell) {
        cell.style.color = color;
    });
}

function setBgColor(color) {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    applyToSelectedCells(function(cell) {
        cell.style.backgroundColor = color;
    });
}

function setBorder(style) {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    applyToSelectedCells(function(cell) {
        if (style === 'none') {
            cell.style.border = 'none';
        } else {
            cell.style.border = style;
        }
    });
}

function setCellWidth(width) {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    applyToSelectedCells(function(cell) {
        cell.style.width = width + 'px';
        cell.style.minWidth = width + 'px';
    });
}

function setCellHeight(height) {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    applyToSelectedCells(function(cell) {
        cell.style.height = height + 'px';
        cell.style.minHeight = height + 'px';
    });
}

function toggleTextWrap() {
    if (selectedCells.length === 0) {
        alert('Please select a table cell first');
        return;
    }
    textWrapEnabled = !textWrapEnabled;
    applyToSelectedCells(function(cell) {
        if (textWrapEnabled) {
            cell.style.whiteSpace = 'normal';
            cell.style.wordWrap = 'break-word';
        } else {
            cell.style.whiteSpace = 'nowrap';
        }
    });
    document.getElementById('wrapBtn').classList.toggle('active', textWrapEnabled);
}

// Apply function to all selected cells
function applyToSelectedCells(func) {
    selectedCells.forEach(function(cell) {
        func(cell);
    });
}

// Apply current toolbar properties to all selected cells
function applyToSelected() {
    if (selectedCells.length <= 1) {
        alert('Please select multiple cells first (Ctrl+Click or Shift+Click)');
        return;
    }
    
    if (!selectedCell) {
        alert('Please select a source cell first to copy properties from');
        return;
    }
    
    var sourceStyle = window.getComputedStyle(selectedCell);
    
    // Get current toolbar values
    var fontSize = document.getElementById('fontSize').value;
    var textColor = document.getElementById('textColor').value;
    var bgColor = document.getElementById('bgColor').value;
    var borderStyle = document.getElementById('borderStyle').value;
    var cellWidth = document.getElementById('cellWidth').value;
    var cellHeight = document.getElementById('cellHeight').value;
    var textAlign = sourceStyle.textAlign;
    
    // Apply to all selected cells
    selectedCells.forEach(function(cell) {
        if (cell !== selectedCell) {
            cell.style.fontSize = fontSize + 'px';
            cell.style.color = textColor;
            cell.style.backgroundColor = bgColor;
            if (borderStyle === 'none') {
                cell.style.border = 'none';
            } else {
                cell.style.border = borderStyle;
            }
            cell.style.width = cellWidth + 'px';
            cell.style.minWidth = cellWidth + 'px';
            cell.style.height = cellHeight + 'px';
            cell.style.minHeight = cellHeight + 'px';
            cell.style.textAlign = textAlign;
        }
    });
    
    alert('Properties applied to ' + (selectedCells.length - 1) + ' cells');
}

// Cell Management Functions
function mergeCells() {
    if (!selectedCell) {
        alert('Please select a cell to merge');
        return;
    }
    
    var row = selectedCell.parentElement;
    var cells = Array.from(row.cells);
    var cellIndex = cells.indexOf(selectedCell);
    
    // Merge with next cell if available
    if (cellIndex < cells.length - 1) {
        var nextCell = cells[cellIndex + 1];
        var colspan = parseInt(selectedCell.colSpan || 1) + parseInt(nextCell.colSpan || 1);
        selectedCell.colSpan = colspan;
        
        // Preserve content from both cells
        var nextContent = nextCell.innerHTML.trim();
        if (nextContent && selectedCell.innerHTML.trim() !== nextContent) {
            selectedCell.innerHTML += (selectedCell.innerHTML.trim() ? ' ' : '') + nextContent;
        }
        
        nextCell.remove();
        
        // Re-initialize table editor
        initTableEditor(selectedCell.closest('table'));
    } else {
        alert('No adjacent cell to merge with. Select a cell that has a neighbor to the right.');
    }
}

function splitCell() {
    if (!selectedCell) {
        alert('Please select a cell to split');
        return;
    }
    
    var colspan = parseInt(selectedCell.colSpan || 1);
    if (colspan > 1) {
        var row = selectedCell.parentElement;
        var cellIndex = Array.from(row.cells).indexOf(selectedCell);
        
        // Split the merged cell
        selectedCell.colSpan = 1;
        
        // Create new cells
        for (var i = 1; i < colspan; i++) {
            var newCell = document.createElement(selectedCell.tagName);
            newCell.contentEditable = true;
            newCell.style.border = '1px solid #000';
            newCell.style.padding = '8px';
            newCell.style.minWidth = '80px';
            newCell.style.minHeight = '30px';
            newCell.addEventListener('click', function() { selectCell(this); });
            row.insertBefore(newCell, selectedCell.nextSibling);
        }
        
        // Re-initialize table editor
        initTableEditor(row.closest('table'));
    } else {
        alert('Cell is not merged. Cannot split.');
    }
}

// Component Insertion Functions
// Excel Paste Functionality
function pasteExcel() {
    var canvas = document.getElementById('designCanvas');
    
    // Check if clipboard contains Excel data
    navigator.clipboard.readText().then(function(text) {
        if (!text) {
            alert('No data in clipboard. Please copy data from Excel first.');
            return;
        }
        
        // Parse tab-separated or newline-separated data
        var rows = text.split(/\r?\n/).filter(function(row) {
            return row.trim().length > 0;
        });
        
        if (rows.length === 0) {
            alert('No valid data found in clipboard.');
            return;
        }
        
        // Create table
        var table = document.createElement('table');
        table.className = 'editable-table';
        table.style.borderCollapse = 'collapse';
        table.style.width = '100%';
        table.style.margin = '20px 0';
        
        rows.forEach(function(rowText) {
            var cells = rowText.split('\t');
            if (cells.length === 0) {
                cells = [rowText]; // Single cell if no tabs
            }
            
            var row = document.createElement('tr');
            cells.forEach(function(cellText) {
                var cell = document.createElement('td');
                cell.contentEditable = true;
                cell.style.border = '1px solid #000';
                cell.style.padding = '8px';
                cell.style.minWidth = '80px';
                cell.style.minHeight = '30px';
                cell.textContent = cellText.trim();
                cell.addEventListener('click', function() { selectCell(this); });
                row.appendChild(cell);
            });
            table.appendChild(row);
        });
        
        canvas.appendChild(table);
        initTableEditor(table);
        
        alert('Excel data pasted successfully! ' + rows.length + ' rows created.');
    }).catch(function(err) {
        // Fallback: prompt for paste
        var pasteData = prompt('Paste your Excel data here (tab-separated):');
        if (pasteData) {
            var rows = pasteData.split(/\r?\n/).filter(function(row) {
                return row.trim().length > 0;
            });
            
            if (rows.length > 0) {
                var table = document.createElement('table');
                table.className = 'editable-table';
                table.style.borderCollapse = 'collapse';
                table.style.width = '100%';
                table.style.margin = '20px 0';
                
                rows.forEach(function(rowText) {
                    var cells = rowText.split('\t');
                    if (cells.length === 0) {
                        cells = [rowText];
                    }
                    
                    var row = document.createElement('tr');
                    cells.forEach(function(cellText) {
                        var cell = document.createElement('td');
                        cell.contentEditable = true;
                        cell.style.border = '1px solid #000';
                        cell.style.padding = '8px';
                        cell.style.minWidth = '80px';
                        cell.style.minHeight = '30px';
                        cell.textContent = cellText.trim();
                        cell.addEventListener('click', function() { selectCell(this); });
                        row.appendChild(cell);
                    });
                    table.appendChild(row);
                });
                
                canvas.appendChild(table);
                initTableEditor(table);
                alert('Excel data pasted successfully!');
            }
        }
    });
}

function addTable() {
    var rows = prompt('Number of rows:', '5');
    var cols = prompt('Number of columns:', '4');
    
    if (rows && cols && !isNaN(rows) && !isNaN(cols)) {
        var canvas = document.getElementById('designCanvas');
        var table = document.createElement('table');
        table.className = 'editable-table';
        table.style.borderCollapse = 'collapse';
        table.style.width = '100%';
        table.style.margin = '20px 0';
        
        for (var i = 0; i < parseInt(rows); i++) {
            var row = document.createElement('tr');
            for (var j = 0; j < parseInt(cols); j++) {
                var cell = document.createElement('td');
                cell.contentEditable = true;
                cell.style.border = '1px solid #000';
                cell.style.padding = '8px';
                cell.style.minWidth = '80px';
                cell.style.minHeight = '30px';
                cell.addEventListener('click', function() { selectCell(this); });
                row.appendChild(cell);
            }
            table.appendChild(row);
        }
        
        canvas.appendChild(table);
        initTableEditor(table);
    }
}

function addComponent(type) {
    var html = '';
    var canvas = document.getElementById('designCanvas');
    
    switch(type) {
        case 'header':
            html = '<h2 class="text-center mb-4" style="border-bottom: 2px solid #333; padding-bottom: 10px;">Document Verification Remarks</h2>';
            break;
        case 'text':
            html = '<p style="margin: 15px 0; line-height: 1.6;">#verifier_remarks#</p>';
            break;
        case 'image':
            html = '<div style="text-align: center; margin: 20px 0;"><img src="#image_url#" alt="Visit Photo" style="max-width: 100%; height: auto; border: 1px solid #ddd; padding: 5px;" /></div>';
            break;
        case 'signature':
            html = '<div style="margin-top: 50px;"><p><strong>Verifier Signature and Stamp of Agency</strong></p><div style="border: 1px solid #333; width: 200px; height: 100px; display: inline-block; text-align: center; line-height: 100px;">Signature Area</div></div>';
            break;
        case 'divider':
            html = '<hr style="border-top: 2px solid #333; margin: 20px 0;">';
            break;
        case 'logo':
            html = '<div style="text-align: center; margin: 20px 0;"><img src="#logo_url#" alt="Logo" style="max-width: 150px; height: auto;" /></div>';
            break;
        case 'stamp':
            html = '<div style="text-align: right; margin: 20px 0;"><img src="#stamp_url#" alt="Stamp" style="max-width: 120px; height: auto;" /></div>';
            break;
    }
    
    // Insert at cursor position or append
    if (window.getSelection && window.getSelection().rangeCount > 0) {
        var selection = window.getSelection();
        var range = selection.getRangeAt(0);
        range.deleteContents();
        var div = document.createElement('div');
        div.innerHTML = html;
        var frag = document.createDocumentFragment();
        while (div.firstChild) {
            frag.appendChild(div.firstChild);
        }
        range.insertNode(frag);
    } else {
        canvas.innerHTML += html;
    }
    
    // If table was added, initialize it
    var newTable = canvas.querySelector('table:last-of-type');
    if (newTable) {
        initTableEditor(newTable);
    }
}

function insertVariable(varName) {
    var variable = '#' + varName + '#';
    
    if (selectedCell) {
        // Insert into selected cell
        var selection = window.getSelection();
        if (selection.rangeCount > 0) {
            var range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(document.createTextNode(variable));
        } else {
            selectedCell.textContent += variable;
        }
    } else {
        // Insert into canvas
        var canvas = document.getElementById('designCanvas');
        if (window.getSelection && window.getSelection().rangeCount > 0) {
            var selection = window.getSelection();
            var range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(document.createTextNode(variable));
        } else {
            canvas.innerHTML += variable;
        }
    }
}

// Filter placeholders based on search
function filterPlaceholders(searchTerm) {
    var search = searchTerm.toLowerCase().trim();
    var allItems = document.querySelectorAll('.placeholder-item');
    
    if (search === '') {
        // Show all items and their parent sections
        allItems.forEach(function(item) {
            item.style.display = '';
            var section = item.closest('.variable-palette');
            if (section) {
                section.style.display = '';
                var sectionHeader = section.previousElementSibling;
                if (sectionHeader && sectionHeader.tagName === 'H6') {
                    sectionHeader.style.display = '';
                }
            }
        });
        return;
    }
    
    // Hide all sections first
    document.querySelectorAll('.variable-palette').forEach(function(section) {
        var sectionHeader = section.previousElementSibling;
        if (sectionHeader && sectionHeader.tagName === 'H6') {
            sectionHeader.style.display = 'none';
        }
        section.style.display = 'none';
    });
    
    // Show matching items
    var hasMatches = false;
    allItems.forEach(function(item) {
        var searchData = item.getAttribute('data-search') || '';
        if (searchData.indexOf(search) !== -1) {
            item.style.display = '';
            hasMatches = true;
            
            // Show parent section
            var section = item.closest('.variable-palette');
            if (section) {
                section.style.display = '';
                var sectionHeader = section.previousElementSibling;
                if (sectionHeader && sectionHeader.tagName === 'H6') {
                    sectionHeader.style.display = '';
                }
            }
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show message if no matches
    if (!hasMatches) {
        // Could show a "No results" message here
    }
}

function previewTemplate() {
    var html = document.getElementById('designCanvas').innerHTML;
    document.getElementById('previewContent').innerHTML = html;
    $('#previewModal').modal('show');
}

function saveTemplate() {
    var html = document.getElementById('designCanvas').innerHTML;
    
    if (!templateId) {
        alert('Please save the template first from the main form.');
        return;
    }
    
    $.ajax({
        url: 'save_report_template.php',
        type: 'POST',
        data: {
            action: 'update_html',
            template_id: templateId,
            template_html: html
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Template saved successfully!');
            } else {
                alert('Error: ' + (response.message || 'Failed to save template'));
            }
        },
        error: function() {
            alert('Error saving template. Please try again.');
        }
    });
}

// Initialize on page load
$(document).ready(function() {
    // Initialize table editors for existing tables
    var tables = document.querySelectorAll('#designCanvas table');
    tables.forEach(function(table) {
        initTableEditor(table);
    });
    
    // Make canvas clickable for cell selection
    var canvas = document.getElementById('designCanvas');
    canvas.addEventListener('click', function(e) {
        if (e.target.tagName === 'TD' || e.target.tagName === 'TH') {
            var isMulti = e.ctrlKey || e.metaKey || e.shiftKey;
            selectCell(e.target, isMulti);
        } else if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
            // Deselect if clicking outside table
            clearAllSelections();
            updateApplyButton();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && selectedCell) {
            switch(e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    formatText('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    formatText('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    formatText('underline');
                    break;
            }
        }
    });
});
</script>

<style>
#designCanvas:focus {
    outline: none;
    border-color: #007bff;
}
</style>
