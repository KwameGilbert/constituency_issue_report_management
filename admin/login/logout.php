<?php
// filepath: c:\xampp\htdocs\swma\admin\login\logout.php
// Start session
session_start();

// Get user ID and role before clearing session (for audit log)
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// If we have user ID, log the logout
if ($user_id) {
    require_once __DIR__ . '/../../config/db_connection.php';
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Get IP and user agent
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Create role-specific logout action
        $roleNames = [
            'mp' => 'Member of Parliament',
            'mce' => 'Municipal Chief Executive',
            'pa' => 'Personal Assistant',
            'admin' => 'System Administrator'
        ];
        $roleName = $roleNames[$user_role] ?? 'Administrator';
        $action = "admin_logout";
        $details = "Administrator ({$roleName}) logged out from admin dashboard";

        // Insert logout record into activity log
        $stmt = $conn->prepare("
            INSERT INTO activity_logs 
                (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES 
                (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $details, $ip, $userAgent]);
    } catch (Exception $e) {
        // Just log the error, but continue with logout process
        error_log("Admin logout audit log error: " . $e->getMessage());
    }
}

// Start a new session for logout message
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out from the administrative system.';
$_SESSION['logout_success'] = true;

// Redirect to admin login page
header("Location: ./index.php");
exit();
