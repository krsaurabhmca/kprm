<?php
/**
 * KPRM - Dashboard Router
 * Routes users to their role-specific dashboard
 */
require_once('all_header.php');

// Route based on user type
switch($user_type) {
    case 'BEO':
        include 'dashboard_beo.php';
        break;
    case 'TL':
        include 'dashboard_tl.php';
        break;
    case 'MANAGER':
        include 'dashboard_manager.php';
        break;
    case 'CLIENT':
        include 'dashboard_client.php';
        break;
    case 'ADMIN':
    case 'DEV':
        // Use existing admin dashboard
        include 'op_dashboard.php';
        break;
    default:
        // Fallback to default dashboard
        include 'op_dashboard.php';
        break;
}
?>

