<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Check if required parameters are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status']) || empty($_GET['status'])) {
    // Redirect to issues list if parameters missing
    header("Location: ./");
    exit();
}

$issue_id = (int)$_GET['id'];
$new_status = $_GET['status'];

// Validate status value
$allowed_statuses = ['pending', 'under_review', 'in_progress', 'resolved', 'rejected'];
if (!in_array($new_status, $allowed_statuses)) {
    // Redirect if invalid status
    header("Location: view.php?id=" . $issue_id . "&error=invalid_status");
    exit();
}

// Get current issue status
$current_status_query = "SELECT status FROM issues WHERE id = ?";
$status_stmt = $conn->prepare($current_status_query);
$status_stmt->bind_param("i", $issue_id);
$status_stmt->execute();
$result = $status_stmt->get_result();

if ($result->num_rows === 0) {
    // Issue not found
    header("Location: ./");
    exit();
}

$current_status = $result->fetch_assoc()['status'];

// Don't update if status is the same
if ($current_status === $new_status) {
    header("Location: view.php?id=" . $issue_id);
    exit();
}

// Check for valid status transitions
$valid_transition = false;

switch ($current_status) {
    case 'pending':
        $valid_transition = ($new_status === 'under_review' || $new_status === 'rejected');
        break;
    case 'under_review':
        $valid_transition = ($new_status === 'in_progress' || $new_status === 'rejected');
        break;
    case 'in_progress':
        $valid_transition = ($new_status === 'resolved' || $new_status === 'rejected');
        break;
    // Resolved and rejected statuses typically don't transition to other statuses,
    // but you could allow it for administrative purposes
    case 'resolved':
    case 'rejected':
        // Allow administrators to reopen issues if needed
        $valid_transition = in_array($new_status, ['under_review', 'in_progress']);
        break;
}

if (!$valid_transition) {
    // Invalid status transition
    header("Location: view.php?id=" . $issue_id . "&error=invalid_transition");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Update issue status
    $update_query = "UPDATE issues SET status = ?, updated_at = NOW()";
    
    // If the new status is 'resolved', also set the resolved_at timestamp
    if ($new_status === 'resolved') {
        $update_query .= ", resolved_at = NOW()";
    }
    
    $update_query .= " WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_status, $issue_id);
    $update_stmt->execute();
    
    // Create an update record in the issue_updates table
    $update_text = "Issue status changed from " . formatStatus($current_status) . " to " . formatStatus($new_status) . ".";
    $log_query = "INSERT INTO issue_updates (issue_id, officer_id, update_text, status_change, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $pa_id = $_SESSION['pa_id'];
    $log_stmt->bind_param("iiss", $issue_id, $pa_id, $update_text, $new_status);
    $log_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to issue view with success message
    header("Location: view.php?id=" . $issue_id . "&status_updated=1");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Redirect with error message
    header("Location: view.php?id=" . $issue_id . "&error=update_failed");
    exit();
}

// Helper function to format status for display
function formatStatus($status) {
    $text = str_replace('_', ' ', $status);
    return ucwords($text);
}