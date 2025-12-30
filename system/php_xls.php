<?php
// Include the PhpSpreadsheet library
require './vendor/autoload.php';
require './op_lib.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// Your MySQL query
$query = 'SELECT state,district, block from op_sdb order by rand() limit 10 ';
$res = direct_sql($query);


// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers for the XLSX file
$sheet->setCellValue('A1', 'state');
$sheet->setCellValue('B1', 'district');
$sheet->setCellValue('C1', 'block');

// Loop through the MySQL query results and populate the XLSX file
$rowIndex = 2;
foreach ($res['data'] as $row) {
    $sheet->setCellValue('A' . $rowIndex, $row['state']);
    $sheet->setCellValue('B' . $rowIndex, $row['district']);
    $sheet->setCellValue('C' . $rowIndex, $row['block']);
    $rowIndex++;
}

// Save the XLSX file
$writer = new Xlsx($spreadsheet);
$filename = 'output.xlsx';
$writer->save($filename);

//$res2 = send_wa('854450365', 'XLS File Check and Reply'); //, $base_url.'system/output.xlsx');

print_r($res2);

echo "XLSX file created successfully. File name: <a href='{$base_url}system/output.xlsx'> $filename</a>";
?>
