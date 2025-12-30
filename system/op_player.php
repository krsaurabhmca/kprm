<?php
// Directory containing the files
$directory = '../public/';

// List files in the directory
if (isset($_GET['list'])) {
    $files = scandir($directory);
    $files = array_diff($files, array('.', '..')); // Remove '.' and '..' entries
    echo json_encode(array_values($files));
}

// Load a file
if (isset($_GET['load'])) {
    $filename = $_GET['load'];
    $filepath = $directory . $filename;
    echo file_get_contents($filepath);
}

// Save a file
if (isset($_GET['save'])) {
    $filename = $_GET['save'];
    $filepath = $directory . $filename;
    $content = file_get_contents('php://input');
    file_put_contents($filepath, $content);
}
?>
