<?php
/**
 * KPRM - File Preview Page
 * Opens files in a resizable, movable window popup
 */

// Get file URL from query parameter
$file_url = isset($_GET['file']) ? $_GET['file'] : '';
$file_name = isset($_GET['name']) ? urldecode($_GET['name']) : 'File Preview';

if (empty($file_url)) {
    die('No file specified');
}

$file_path = '../upload/' . $file_url;
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Check if file exists
if (!file_exists($file_path)) {
    die('File not found: ' . htmlspecialchars($file_name));
}

// Get full URL for Google Docs Viewer (needs to be publicly accessible)
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
// Remove /public from path if present
$script_path = str_replace('/public', '', $script_path);
$full_url = $base_url . $script_path . '/upload/' . $file_url;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($file_name); ?> - Preview</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            overflow: hidden;
        }
        .header {
            background: #007bff;
            color: white;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header h4 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .preview-container {
            width: 100%;
            height: calc(100vh - 50px);
            overflow: auto;
            background: white;
        }
        .preview-content {
            width: 100%;
            height: 100%;
        }
        .preview-content img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .preview-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .info-message {
            padding: 40px;
            text-align: center;
            background: white;
        }
        .info-message i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .info-message h5 {
            margin-bottom: 15px;
            color: #333;
        }
        .info-message p {
            color: #666;
            margin-bottom: 20px;
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            flex-direction: column;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <h4>
            <i class="fas fa-file"></i>
            <?php echo htmlspecialchars($file_name); ?>
        </h4>
        <div class="header-actions">
            <a href="<?php echo htmlspecialchars($file_path); ?>" class="btn btn-success" download>
                <i class="fas fa-download"></i> Download
            </a>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
    <div class="preview-container">
        <div class="preview-content" id="previewContent">
            <div class="loading">
                <div class="spinner"></div>
                <p style="margin-top: 10px;">Loading preview...</p>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var fileExtension = '<?php echo $file_extension; ?>';
        var filePath = '<?php echo htmlspecialchars($file_path); ?>';
        var fullUrl = '<?php echo htmlspecialchars($full_url); ?>';
        var previewContent = document.getElementById('previewContent');
        
        var previewHtml = '';
        
        // Image files
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(fileExtension)) {
            previewHtml = '<img src="' + filePath + '" alt="Preview" onerror="showError(\'Unable to load image. Please download the file.\')">';
        }
        // PDF files
        else if (fileExtension === 'pdf') {
            previewHtml = '<iframe src="' + filePath + '#toolbar=1" onerror="showError(\'Unable to load PDF.\')"></iframe>';
        }
        // Office Documents - Use Google Docs Viewer
        else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(fileExtension)) {
            var googleViewerUrl = 'https://docs.google.com/viewer?url=' + encodeURIComponent(fullUrl) + '&embedded=true';
            previewHtml = '<iframe src="' + googleViewerUrl + '" onerror="showError(\'Unable to preview document. The file may need to be publicly accessible.\')"></iframe>';
        }
        // ZIP files
        else if (['zip', 'rar', '7z'].includes(fileExtension)) {
            previewHtml = '<div class="info-message"><i class="fas fa-file-archive"></i><h5>Archive File</h5><p>This is a compressed archive file. Please download it to extract and view its contents.</p><a href="' + filePath + '" class="btn btn-success" download><i class="fas fa-download"></i> Download Archive</a></div>';
        }
        // Text files
        else if (['txt', 'csv', 'log', 'json', 'xml', 'html', 'css', 'js'].includes(fileExtension)) {
            previewHtml = '<iframe src="' + filePath + '" style="font-family: monospace;"></iframe>';
        }
        // Other files
        else {
            previewHtml = '<div class="info-message"><i class="fas fa-file"></i><h5>File Preview Not Available</h5><p>Preview is not available for this file type (' + fileExtension + '). Please download the file to view it.</p><a href="' + filePath + '" class="btn btn-success" download><i class="fas fa-download"></i> Download File</a></div>';
        }
        
        previewContent.innerHTML = previewHtml;
        
        function showError(message) {
            previewContent.innerHTML = '<div class="info-message"><i class="fas fa-exclamation-triangle"></i><h5>Error</h5><p>' + message + '</p><a href="' + filePath + '" class="btn btn-success" download><i class="fas fa-download"></i> Download File</a></div>';
        }
    })();
    </script>
</body>
</html>

