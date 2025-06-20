<?php
// Start session
session_start();

// Get user ID before clearing session (for audit log)
$user_id = $_SESSION['user_id'] ?? null;

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// If we have user ID, log the logout
if ($user_id) {
    // Include database connection
    require_once '../config/db_config.php';
    
    try {
        // Get IP and user agent
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $action = "User logout";
        
        // Insert logout record into audit log
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $action, $ip, $userAgent);
        $stmt->execute();
    } catch (Exception $e) {
        // Just log the error, but continue with logout process
        error_log("Logout audit log error: " . $e->getMessage());
    }
}

// Redirect to login page with logout success message
header("Location: login/index.php?success=logged_out");
exit();
?>