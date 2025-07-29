<?php
// filepath: c:\xampp\htdocs\swma\admin\login\session_check.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // User is not logged in, redirect to admin login page
    header("Location: ../login/index.php?error=auth_required");
    exit();
}

// Check if user has admin privileges (mp, mce, pa, or admin roles)
$adminRoles = ['mp', 'mce', 'pa', 'admin'];
if (!in_array($_SESSION['user_role'], $adminRoles)) {
    // User doesn't have admin privileges, redirect to login with error
    header("Location: ../login/index.php?error=insufficient_privileges");
    exit();
}

// Additional security check for admin status
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // Admin flag not set properly, redirect to login
    header("Location: ../login/index.php?error=invalid_session");
    exit();
}

// Optional: Check if session has been inactive for too long (e.g., 7200 minutes)
$max_idle_time = 7200 * 60; // 7200 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_idle_time)) {
    // Session has expired, log the expiration for audit purposes
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../../config/db_connection.php';
            $database = new Database();
            $conn = $database->getConnection();

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt = $conn->prepare("
                INSERT INTO activity_logs 
                    (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES 
                    (?, 'admin_session_expired', 'Administrator session expired due to inactivity', ?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $ip, $userAgent]);
        } catch (Exception $e) {
            error_log("Admin session expiry logging error: " . $e->getMessage());
        }
    }

    // Destroy expired session and redirect to login
    session_unset();
    session_destroy();

    header("Location: ../login/index.php?error=session_expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Regenerate session ID periodically for security (every 30 minutes)
if (!isset($_SESSION['session_regenerated']) || (time() - $_SESSION['session_regenerated']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = time();

    // Log session regeneration for admin audit
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../../config/db_connection.php';
            $database = new Database();
            $conn = $database->getConnection();

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt = $conn->prepare("
                INSERT INTO activity_logs 
                    (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES 
                    (?, 'admin_session_regenerated', 'Administrator session ID regenerated for security', ?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $ip, $userAgent]);
        } catch (Exception $e) {
            error_log("Admin session regeneration logging error: " . $e->getMessage());
        }
    }
}
