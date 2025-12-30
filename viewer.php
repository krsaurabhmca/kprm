<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>File Viewer</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
      font-family: Arial, sans-serif;
    }
    iframe {
      width: 100%;
      height: 100%;
      border: none;
    }
    #excelData {
      overflow: auto;
      padding: 10px;
      max-height: 90vh;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 6px;
    }
    th {
      background-color: #f4f4f4;
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>

<?php
error_reporting(0);
ini_set('display_errors', 0);

$file = $_GET['file'] ?? '';
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$basename = basename($file);
$safeFile = htmlspecialchars($file, ENT_QUOTES);

//echo "<h3 style='padding:10px;'>📄 Viewing: " . htmlspecialchars($basename) . "</h3>";

switch ($ext) {
  case 'pdf':
    echo "<iframe src=\"" . $safeFile . "\"></iframe>";
    break;

  case 'jpg':
  case 'jpeg':
  case 'png':
    echo "
    <style>
      .zoom-wrapper {
        width: 100%;
        height: 80vh;
        overflow: auto;
        border: 1px solid #ccc;
        cursor: grab;
      }
      .zoom-wrapper img {
        transform-origin: 0 0;
        transition: transform 0.2s ease;
      }
    </style>
  
    <div class='zoom-wrapper' id='zoomWrapper'>
      <img src=\"" . $safeFile . "\" id='zoomImage' style='max-width: none;'>
    </div>
  
    <script>
     
      
       const wrapper = document.getElementById('zoomWrapper');
          const img = document.getElementById('zoomImage');
          let scale = 1;
        
          wrapper.addEventListener('wheel', function(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            scale = Math.min(Math.max(0.1, scale + delta), 5);
            img.style.transform = `scale(\${scale})`; // FIXED
          });
          
      let isDragging = false, startX, startY, scrollLeft, scrollTop;
  
      wrapper.addEventListener('mousedown', e => {
        isDragging = true;
        wrapper.style.cursor = 'grabbing';
        startX = e.pageX - wrapper.offsetLeft;
        startY = e.pageY - wrapper.offsetTop;
        scrollLeft = wrapper.scrollLeft;
        scrollTop = wrapper.scrollTop;
      });
  
      wrapper.addEventListener('mouseleave', () => {
        isDragging = false;
        wrapper.style.cursor = 'grab';
      });
  
      wrapper.addEventListener('mouseup', () => {
        isDragging = false;
        wrapper.style.cursor = 'grab';
      });
  
      wrapper.addEventListener('mousemove', e => {
        if (!isDragging) return;
        e.preventDefault();
        const x = e.pageX - wrapper.offsetLeft;
        const y = e.pageY - wrapper.offsetTop;
        const walkX = x - startX;
        const walkY = y - startY;
        wrapper.scrollLeft = scrollLeft - walkX;
        wrapper.scrollTop = scrollTop - walkY;
      });
    </script>
    ";
    break;

  case 'txt':
  case 'json':
  case 'csv':
  case 'sql':
    echo "<pre style='padding:10px;'>" . htmlspecialchars(@file_get_contents($file)) . "</pre>";
    break;

  case 'doc':
  case 'docx':
  case 'ppt':
  case 'pptx':
    $url = "https://docs.google.com/gview?url=" . urlencode($file) . "&embedded=true";
    echo "<iframe src=\"" . htmlspecialchars($url, ENT_QUOTES) . "\"></iframe>";
    break;

  case 'xls':
  case 'xlsx':
    echo "<div id='excelData'>⏳ Loading Excel Preview...</div>";
    echo "<script>
      async function loadExcelFromUrl(url) {
        try {
          const res = await fetch(url);
          const data = await res.arrayBuffer();
          const wb = XLSX.read(data, { type: 'array' });
          const sheet = wb.SheetNames[0];
          const html = XLSX.utils.sheet_to_html(wb.Sheets[sheet]);
          document.getElementById('excelData').innerHTML = html;
        } catch (err) {
          document.getElementById('excelData').innerHTML = '<b style=\"color:red\">❌ Failed to load Excel file.</b>';
          console.error(err);
        }
      }
      loadExcelFromUrl(" . json_encode($file) . ");
    </script>";
    break;

  default:
    echo "<p style='padding:10px;'>❌ Unsupported file type.</p>";
}
?>

</body>
</html>
