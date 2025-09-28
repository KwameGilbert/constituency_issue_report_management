<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an agent
if (!isset($_SESSION['user_id'])) {
    // User is not logged in or not an agent, redirect to login page
    header("Location: ../login/index.php?error=auth_required");
    exit();
}

// Check if password reset is required
$current_script = $_SERVER['SCRIPT_NAME'];
if (isset($_SESSION['password_reset_required']) && 
    $_SESSION['password_reset_required'] == 1 && 
    !strpos($current_script, 'change_password.php')) {
    // Redirect to change password page
    header("Location: ../profile_settings/change_password.php");
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