<?php
/**
 * Assessment Team Session Check
 * Checks if user is already logged in and validates session
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests - return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // Check if user is logged in as assessment team member
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'assessment') {
        echo json_encode(['loggedIn' => true, 'user_id' => $_SESSION['user_id']]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
    exit;
}

// For page requests - redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'assessment') {
    // User is not logged in as assessment team member, redirect to login page
    header("Location: ../login/index.php?error=auth_required");
    exit();
}

// Check if session has been inactive for too long (e.g., 7200 minutes)
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
