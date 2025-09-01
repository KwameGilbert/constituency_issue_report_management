<?php
// Start session
session_start();

// Check if user is logged in and is a personal assistant
if (isset($_SESSION['pa_id']) && $_SESSION['role'] === 'pa') {
    header("Location: ./dashboard/");
    exit();
} else {
    // Redirect to login page
    header("Location: ./login/");
    exit();
}
?>