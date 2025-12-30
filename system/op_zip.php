<?php
if(isset($_GET['table_name']))
{                                                                                                                                                                                        
    $table_name = $_GET['table_name'];
    $zipFile =$table_name.".zip";
    //$zipFile = "images.zip";
    
    // Initializing PHP class
    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    //$files = scandir('../rd_details');
    $files = scandir("../$table_name");
    
    foreach($files as $file) {
        if($file == '.' || $file == '..') continue;
        // $zip->addFile('../rd_details/'.$file, $file);
        $zip->addFile("../$table_name/".$file, $file);
    }
    
    $zip->close();
    
    //Force download a file in php
    if (file_exists($zipFile)) {
    	header('Content-Description: File Transfer');
    	header('Content-Type: application/octet-stream');
    	header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
    	header('Expires: 0');
    	header('Cache-Control: must-revalidate');
    	header('Pragma: public');
    	header('Content-Length: ' . filesize($zipFile));
    	readfile($zipFile);
    	exit;
    }

}
?>