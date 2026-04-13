<?php
require_once('system/op_lib.php');
global $con;
$res = mysqli_query($con, "SELECT field_name, input_type FROM tasks_meta WHERE task_id = 10");
while($row = mysqli_fetch_assoc($res)) {
    echo "Field: " . $row['field_name'] . " | Type: " . $row['input_type'] . "\n";
}
?>
