<?php
/**
 * KPRM - Dashboard Helper Functions
 * Common functions for role-specific dashboards
 */

/**
 * Get role-filtered SQL WHERE clause for cases
 */
function get_cases_filter_sql($user_type, $user_id, $date_where = '') {
    global $con;
    require_once('../function.php');
    
    // Admin and DEV see all
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return "WHERE status != 'DELETED' $date_where";
    }
    
    // Get allowed clients
    $allowed_clients = get_user_allowed_clients($user_id, $user_type);
    
    if (empty($allowed_clients)) {
        return "WHERE 1=0"; // No access
    }
    
    $client_ids_str = implode(',', array_map('intval', $allowed_clients));
    return "WHERE status != 'DELETED' AND client_id IN ($client_ids_str) $date_where";
}

/**
 * Get role-filtered SQL WHERE clause for tasks
 */
function get_tasks_filter_sql($user_type, $user_id, $date_where = '') {
    global $con;
    require_once('../function.php');
    
    // Admin and DEV see all
    if ($user_type == 'ADMIN' || $user_type == 'DEV') {
        return "WHERE status = 'ACTIVE' $date_where";
    }
    
    // BEO: Filter by allowed clients
    if ($user_type == 'BEO') {
        $allowed_clients = get_user_allowed_clients($user_id, $user_type);
        if (empty($allowed_clients)) {
            return "WHERE 1=0";
        }
        $client_ids_str = implode(',', array_map('intval', $allowed_clients));
        return "WHERE ct.status = 'ACTIVE' AND c.client_id IN ($client_ids_str) $date_where";
    }
    
    // TL and MANAGER: Filter by allowed tasks
    if ($user_type == 'TL' || $user_type == 'MANAGER') {
        $allowed_tasks = get_user_allowed_tasks($user_id, $user_type);
        if (empty($allowed_tasks)) {
            return "WHERE 1=0";
        }
        $task_ids_str = implode(',', array_map('intval', $allowed_tasks));
        return "WHERE ct.status = 'ACTIVE' AND ct.task_template_id IN ($task_ids_str) $date_where";
    }
    
    // CLIENT: Filter by their own cases
    if ($user_type == 'CLIENT') {
        // This will need to be adjusted based on how clients are linked to cases
        return "WHERE ct.status = 'ACTIVE' $date_where";
    }
    
    return "WHERE status = 'ACTIVE' $date_where";
}

?>

