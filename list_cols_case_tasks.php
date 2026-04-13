<?php
require 'system/op_lib.php';
$cols = column_list('case_tasks');
foreach($cols['data'] as $c) echo $c['COLUMN_NAME']."\n";
?>
