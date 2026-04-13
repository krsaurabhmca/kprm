<?php
/**
 * KPRM - Get Client Meta Fields
 * Returns client meta fields as JSON for MIS table generation
 */
require_once('../system/op_lib.php');
require_once('../function.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if (!$client_id) {
    echo json_encode(['success' => false, 'message' => 'Client ID is required']);
    exit;
}

global $con;

// Get client meta fields
$client_meta_query = "SELECT field_name, display_name FROM clients_meta WHERE client_id = '$client_id' AND status = 'ACTIVE' ORDER BY id ASC";
$client_meta_result = mysqli_query($con, $client_meta_query);

$fields = [];

if ($client_meta_result) {
    while ($row = mysqli_fetch_assoc($client_meta_result)) {
        $fields[] = [
            'field_name' => $row['field_name'],
            'display_name' => $row['display_name']
        ];
    }
}

echo json_encode([
    'success' => true,
    'fields' => $fields,
    'count' => count($fields)
]);

