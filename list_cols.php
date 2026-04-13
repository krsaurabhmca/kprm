<?php
require 'system/op_lib.php';
$cols = column_list('attachments');
foreach($cols['data'] as $c) echo $c['COLUMN_NAME']."\n";
?>
