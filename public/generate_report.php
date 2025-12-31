<?php
/**
 * KPRM - Generate Report
 * Simple report generator from HTML templates with export options
 */
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Include vendor autoload for export libraries
require_once('../system/vendor/autoload.php');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo '<div style="padding: 20px; color: red; font-family: monospace;">';
        echo '<h3>Fatal Error:</h3>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($error['file']) . '</p>';
        echo '<p><strong>Line:</strong> ' . htmlspecialchars($error['line']) . '</p>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
        echo '</div>';
    }
});

try {
    require_once('../system/op_lib.php');
    require_once('../function.php');
    
    // Ensure base_url is set
    if (!isset($base_url)) {
        global $CONFIG;
        if (isset($CONFIG['base_url'])) {
            $base_url = $CONFIG['base_url'];
        } else {
            $base_url = 'http://localhost/kprm/';
        }
    }
    
    $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
    $case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
    
    if (!$template_id) {
        die('<div style="padding: 20px; color: red;">Error: Template ID is required</div>');
    }
    
    if (!isset($con) || !$con) {
        die('<div style="padding: 20px; color: red;">Error: Database connection not available</div>');
    }
    
    // Get template (use prepared statement for security)
    $template_id_escaped = mysqli_real_escape_string($con, $template_id);
    $template_query = "SELECT * FROM report_templates WHERE id = '$template_id_escaped' AND status = 'ACTIVE'";
    $template_result = mysqli_query($con, $template_query);
    
    if (!$template_result) {
        die('<div style="padding: 20px; color: red;">Error: Database query failed - ' . htmlspecialchars(mysqli_error($con)) . '</div>');
    }
    
    if (mysqli_num_rows($template_result) == 0) {
        die('<div style="padding: 20px; color: red;">Error: Template not found (ID: ' . htmlspecialchars($template_id) . ')</div>');
    }
    
    $template = mysqli_fetch_assoc($template_result);
    $html_template = $template['template_html'] ?? '';
    $template_name = $template['template_name'] ?? 'Report';
    $client_id = $template['client_id'] ?? null;
    
    if (empty($html_template)) {
        die('<div style="padding: 20px; color: red;">Error: Template HTML is empty</div>');
    }
    
    // If case_id is provided, get client_id from case (this ensures we have the correct client for the case)
    if ($case_id && (!$client_id || empty($client_id))) {
        $case_query = "SELECT client_id FROM cases WHERE id = '$case_id' LIMIT 1";
        $case_result = mysqli_query($con, $case_query);
        if ($case_result && mysqli_num_rows($case_result) > 0) {
            $case_row = mysqli_fetch_assoc($case_result);
            $client_id = $case_row['client_id'] ?? null;
        }
    }
    
    // Check for export format
    $export_format = isset($_GET['export']) ? strtolower($_GET['export']) : '';
    $custom_filename = isset($_GET['filename']) ? trim($_GET['filename']) : '';
    
    // Generate report
    $result = generate_report_from_html($html_template, $case_id, $client_id);
    
    if (!$result['success']) {
        die('<div style="padding: 20px; color: red;">Error: ' . htmlspecialchars($result['message']) . '</div>');
    }
    
    $report_html = $result['html'];
    
    // Handle export formats
    if ($export_format == 'word') {
        export_to_word($report_html, $custom_filename ?: $template_name, $case_id);
        exit;
    } elseif ($export_format == 'excel') {
        export_to_excel($report_html, $custom_filename ?: $template_name, $case_id);
        exit;
    } elseif ($export_format == 'pdf') {
        export_to_pdf($report_html, $custom_filename ?: $template_name, $case_id);
        exit;
    }
    
    // Default: Display HTML with export toolbar
    display_report_with_toolbar($report_html, $template_id, $case_id, $template_name);
    
} catch (Exception $e) {
    echo '<div style="padding: 20px; color: red; font-family: monospace;">';
    echo '<h3>Exception:</h3>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . htmlspecialchars($e->getLine()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
} catch (Error $e) {
    echo '<div style="padding: 20px; color: red; font-family: monospace;">';
    echo '<h3>Fatal Error:</h3>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . htmlspecialchars($e->getLine()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

/**
 * Display report with export toolbar
 */
function display_report_with_toolbar($html, $template_id, $case_id, $filename = 'Report') {
    $current_url = 'generate_report.php?template_id=' . $template_id;
    if ($case_id) {
        $current_url .= '&case_id=' . $case_id;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Report</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }
            .export-toolbar {
                position: sticky;
                top: 0;
                background: #fff;
                border-bottom: 2px solid #007bff;
                padding: 10px 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .export-buttons {
                display: flex;
                gap: 10px;
            }
            .btn-export {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                font-size: 14px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: background 0.3s;
            }
            .btn-export:hover {
                opacity: 0.9;
            }
            .btn-word {
                background: #185abd;
                color: white;
            }
            .btn-excel {
                background: #1d6f42;
                color: white;
            }
            .btn-pdf {
                background: #dc3545;
                color: white;
            }
            .btn-print {
                background: #6c757d;
                color: white;
            }
            .report-content {
                padding: 20px;
                max-width: 100%;
            }
            .report-content img {
                max-width: 100% !important;
                height: auto !important;
                display: block;
                margin: 5px auto;
            }
            .report-content table {
                width: 100%;
                border-collapse: collapse;
            }
            .report-content table img {
                max-width: 200px !important;
                max-height: 150px !important;
            }
            @media print {
                .export-toolbar {
                    display: none;
                }
                .report-content {
                    padding: 0;
                }
                .report-content img {
                    max-width: 100% !important;
                    height: auto !important;
                    page-break-inside: avoid;
                }
            }
        </style>
    </head>
    <body>
        <div class="export-toolbar">
            <div>
                <strong>Export Options:</strong>
            </div>
            <div class="export-buttons">
                <button onclick="exportReport('word', '<?php echo $current_url; ?>')" class="btn-export btn-word" title="Export to Word">
                    📄 Word
                </button>
                <button onclick="exportReport('pdf', '<?php echo $current_url; ?>')" class="btn-export btn-pdf" title="Export to PDF">
                    📑 PDF
                </button>
                <button onclick="window.print()" class="btn-export btn-print" title="Print">
                    🖨️ Print
                </button>
            </div>
        </div>
        <div class="report-content">
            <?php echo $html; ?>
        </div>
        <script>
        function exportReport(format, baseUrl) {
            // Ask for filename
            var defaultName = '<?php echo htmlspecialchars($filename, ENT_QUOTES); ?>';
            if (!defaultName || defaultName === 'Report') {
                defaultName = 'Report';
            }
            // Clean default name
            defaultName = defaultName.replace(/[^a-zA-Z0-9_-]/g, '_');
            <?php if ($case_id): ?>
            defaultName = defaultName + '_Case_<?php echo $case_id; ?>_' + new Date().toISOString().split('T')[0];
            <?php else: ?>
            defaultName = defaultName + '_' + new Date().toISOString().split('T')[0];
            <?php endif; ?>
            
            var fileName = prompt('Enter filename (without extension):', defaultName);
            
            if (fileName === null) {
                return; // User cancelled
            }
            
            // Clean filename
            fileName = fileName.trim();
            if (!fileName) {
                alert('Filename cannot be empty');
                return;
            }
            
            // Remove extension if user added one
            fileName = fileName.replace(/\.(doc|docx|pdf|xlsx)$/i, '');
            
            // Build URL with filename
            var url = baseUrl + '&export=' + format;
            if (fileName) {
                url += '&filename=' + encodeURIComponent(fileName);
            }
            
            // Open export URL
            window.location.href = url;
        }
        </script>
    </body>
    </html>
    <?php
}

/**
 * Export report to Word format
 */
function export_to_word($html, $filename, $case_id) {
    try {
        // Generate filename - use provided filename or generate default
        $base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $file_name = $base_name;
        
        // Only add case_id and date if filename wasn't provided by user (contains template name pattern)
        // User-provided filenames will be used as-is (they already have date/case in the prompt default)
        
        // Ensure .doc extension
        if (substr(strtolower($file_name), -4) !== '.doc') {
            $file_name .= '.doc';
        }
        
        // Word can open HTML files, so we'll output as HTML with Word MIME type
        // This creates a Word-compatible HTML file
        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Cache-Control: max-age=0');
        
        // Output HTML with Word-specific meta tags and image constraints
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="ProgId" content="Word.Document">';
        echo '<meta name="Generator" content="KPRM Report Generator">';
        echo '<meta name="Originator" content="KPRM">';
        echo '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View></w:WordDocument></xml><![endif]-->';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }';
        echo '@page { size: 8.5in 11in; margin: 1in; }';
        echo 'img { max-width: 100%; height: auto; display: block; margin: 5px 0; }';
        echo 'table { width: 100%; border-collapse: collapse; }';
        echo 'table img { max-width: 200px; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo $html;
        echo '</body>';
        echo '</html>';
        exit;
    } catch (Exception $e) {
        die('Error exporting to Word: ' . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Export report to Excel format
 * Improved version that handles HTML tables better
 */
function export_to_excel($html, $filename, $case_id) {
    try {
        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Use DOMDocument to parse HTML and extract table data
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Try to extract tables first
        $tables = $xpath->query('//table');
        $row = 1;
        
        if ($tables->length > 0) {
            // Process tables
            foreach ($tables as $table) {
                $rows = $xpath->query('.//tr', $table);
                foreach ($rows as $tr) {
                    $cols = $xpath->query('.//td | .//th', $tr);
                    $col = 1;
                    foreach ($cols as $cell) {
                        $cellValue = trim($cell->textContent);
                        $cellValue = preg_replace('/\s+/', ' ', $cellValue); // Clean whitespace
                        if (!empty($cellValue)) {
                            $sheet->setCellValueByColumnAndRow($col, $row, $cellValue);
                        }
                        $col++;
                    }
                    $row++;
                }
                $row++; // Add spacing between tables
            }
        } else {
            // No tables found, extract text content
            $text = strip_tags($html);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            
            // Split by lines and add to cells
            $lines = preg_split('/\r\n|\r|\n/', $text);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $sheet->setCellValue('A' . $row, $line);
                    $row++;
                }
            }
        }
        
        // Auto-size columns (limit to first 10 columns)
        for ($col = 1; $col <= 10; $col++) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        
        // Generate filename - use provided filename (already includes case/date from prompt)
        $base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $file_name = $base_name;
        
        // Ensure .xlsx extension
        if (substr(strtolower($file_name), -5) !== '.xlsx') {
            $file_name .= '.xlsx';
        }
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) {
        die('Error exporting to Excel: ' . htmlspecialchars($e->getMessage()));
    }
}

/**
 * Export report to PDF format
 */
function export_to_pdf($html, $filename, $case_id) {
    try {
        global $base_url;
        
        // Create mPDF instance
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);
        
        // Add CSS to prevent images from breaking layout
        $css = '<style>
            img {
                max-width: 100% !important;
                height: auto !important;
                page-break-inside: avoid;
                display: block;
                margin: 5px auto;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                page-break-inside: avoid;
            }
            table img {
                max-width: 200px !important;
                max-height: 150px !important;
            }
            .report-content {
                width: 100%;
                overflow: hidden;
            }
        </style>';
        
        // Convert relative URLs to absolute URLs for images
        $html = preg_replace_callback(
            '/src=["\']([^"\']+)["\']/',
            function($matches) use ($base_url) {
                $url = $matches[1];
                if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
                    $url = rtrim($base_url, '/') . '/' . ltrim($url, '/');
                }
                return 'src="' . $url . '"';
            },
            $html
        );
        
        // Prepend CSS to HTML
        $html = $css . $html;
        
        // Write HTML content
        $mpdf->WriteHTML($html);
        
        // Generate filename - use provided filename or generate default
        $base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $file_name = $base_name;
        
        // Only add case_id and date if filename wasn't provided by user
        if (strpos($base_name, '_Case_') === false && strpos($base_name, date('Y-m-d')) === false) {
            $file_name = $base_name . ($case_id ? '_Case_' . $case_id : '') . '_' . date('Y-m-d');
        }
        
        // Ensure .pdf extension
        if (substr(strtolower($file_name), -4) !== '.pdf') {
            $file_name .= '.pdf';
        }
        
        // Output
        $mpdf->Output($file_name, 'D');
        exit;
    } catch (Exception $e) {
        die('Error exporting to PDF: ' . htmlspecialchars($e->getMessage()));
    }
}
