<?php
/**
 * Assessment Team Logout Handler
 * Logs out the assessment team member and destroys the session
 */

session_start();

// Get user ID for logging before destroying session
$user_id = $_SESSION['user_id'] ?? null;

try {
    if ($user_id) {
        // Include database connection
        require_once __DIR__ . '/../../config/db_connection.php';

        $database = new Database();
        $conn = $database->getConnection();

        // Log logout activity
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, 'logout', 'Assessment team logout', ?, ?)
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $_SERVER['REMOTE_ADDR']);
        $stmt->bindValue(3, $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
    }
} catch (Exception $e) {
    // Continue with logout even if logging fails
    error_log("Assessment logout error: " . $e->getMessage());
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit;
?>
