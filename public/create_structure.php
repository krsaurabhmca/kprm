<?php
require_once('../function.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Clean column name to valid meta field
 */
function normalizeFieldName($name)
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[\r\n]+/', ' ', $name);
    $name = preg_replace('/[^a-z0-9 ]/', '', $name);
    $name = preg_replace('/\s+/', '_', $name);
    $name = trim($name, '_');
    return substr($name, 0, 55);
}

/**
 * Guess input type based on field name
 */
function guessInputType($field)
{
    if (strpos($field, 'date') !== false) {
        return 'DATE';
    }
    if (
        strpos($field, 'amount') !== false ||
        strpos($field, 'count') !== false ||
        strpos($field, 'tat') !== false ||
        strpos($field, 'days') !== false
    ) {
        return 'NUMBER';
    }
    if (
        strpos($field, 'status') !== false ||
        strpos($field, 'type') !== false
    ) {
        return 'SELECT';
    }
    return 'TEXT';
}

/**
 * MAIN FUNCTION: Sync Client Meta from Payload
 */
function syncClientMetaFromPayload($con, $client_id, $columns, $user_id)
{
    $usedFields = [];
    $messages   = [];

    foreach ($columns as $col) {

        if (!isset($col['name'])) {
            continue;
        }

        $rawName = trim($col['name']);

        // Skip empty / blank columns
        if ($rawName === '') {
            continue;
        }

        $field = normalizeFieldName($rawName);

        if ($field === '') {
            continue;
        }

        // Ensure uniqueness in same request
        $base = $field;
        $i = 1;
        while (in_array($field, $usedFields)) {
            $field = substr($base, 0, 50) . '_' . $i;
            $i++;
        }
        $usedFields[] = $field;

        $display_name = ucwords(str_replace('_', ' ', $field));
        $input_type   = guessInputType($field);

        $field        = mysqli_real_escape_string($con, $field);
        $display_name = mysqli_real_escape_string($con, $display_name);

        // Check if field exists
        $checkSql = "
            SELECT id FROM clients_meta
            WHERE client_id = '$client_id'
              AND field_name = '$field'
            LIMIT 1
        ";

        $checkRes = mysqli_query($con, $checkSql);

        if (!$checkRes) {
            $messages[] = "❌ DB Error: " . mysqli_error($con);
            continue;
        }

        if (mysqli_num_rows($checkRes) > 0) {

            // UPDATE
            mysqli_query($con, "
                UPDATE clients_meta SET
                    display_name = '$display_name',
                    input_type   = '$input_type',
                    updated_at   = NOW(),
                    updated_by   = '$user_id'
                WHERE client_id = '$client_id'
                  AND field_name = '$field'
            ");

            $messages[] = "🔄 Updated field: $field";

        } else {

            // INSERT
            mysqli_query($con, "
                INSERT INTO clients_meta
                (
                    client_id,
                    field_name,
                    display_name,
                    input_type,
                    is_unique,
                    default_value,
                    by_client,
                    by_verifier,
                    by_findings,
                    status,
                    created_at,
                    created_by
                )
                VALUES
                (
                    '$client_id',
                    '$field',
                    '$display_name',
                    '$input_type',
                    'NO',
                    NULL,
                    'YES',
                    'NO',
                    'NO',
                    'ACTIVE',
                    NOW(),
                    '$user_id'
                )
            ");

            $messages[] = "✅ Inserted field: $field";
        }
    }

    return $messages;
}

/**
 * ===============================
 * REQUEST HANDLER
 * ===============================
 */
if (
    isset($_POST['table_name']) &&
    isset($_POST['columns'])
) {
    $client_id = (int)$_POST['table_name'];
    $columns   = json_decode($_POST['columns'], true);
    $user_id   = $_SESSION['user_id'] ?? 1;

    if (!is_array($columns)) {
        die('❌ Invalid columns payload');
    }

    $result = syncClientMetaFromPayload(
        $con,
        $client_id,
        $columns,
        $user_id
    );

    // echo "<pre>";
    // echo implode("\n", $result);
    // echo "</pre>";

    echo "<strong>✅ Client Meta Structure Synced Successfully</strong>";
}
?>
