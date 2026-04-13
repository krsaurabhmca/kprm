<?php
require_once('system/op_lib.php');
global $con;
$res = mysqli_query($con, "SELECT id, task_name FROM tasks WHERE task_name LIKE '%Financial%'");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | Name: " . $row['task_name'] . "\n";
}
?>
