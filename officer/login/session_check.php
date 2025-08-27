<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an officer
if (!isset($_SESSION['user_id']) && ($_SESSION['user_role'] !== 'officer')) {
    // User is not logged in or not an officer, redirect to login page
    header("Location: ../login/index.php?error=auth_required");
    exit();
}

// Optional: Check if session has been inactive for too long (e.g., 7200 minutes)
$max_idle_time = 7200 * 60; // 7200 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_idle_time)) {
    // Session has expired, destroy it and redirect to login
    session_unset();
    session_destroy();
    
    header("Location: ../login/index.php?error=session_expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>