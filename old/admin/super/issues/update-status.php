<?php
// filepath: c:\xampp\htdocs\swma\admin\pa\issues\update-status.php
session_start();

// Authentication check
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Check request method
$is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

// Get issue ID and status
if ($is_post) {
    // POST request handling (from forms with additional data)
    if (!isset($_POST['issue_id']) || !isset($_POST['status'])) {
        header("Location: ./");
        exit();
    }
    
    $issue_id = (int)$_POST['issue_id'];
    $status = $_POST['status'];
    
    // Additional form data
    $update_text = '';
    $resolution_notes = null;
    
    if ($status === 'rejected') {
        if (!isset($_POST['rejection_reason']) || empty($_POST['rejection_reason'])) {
            header("Location: view.php?id={$issue_id}&error=missing_rejection_reason");
            exit();
        }
        $update_text = trim($_POST['rejection_reason']);
    } elseif ($status === 'resolved') {
        if (!isset($_POST['resolution_notes']) || empty($_POST['resolution_notes'])) {
            header("Location: view.php?id={$issue_id}&error=missing_resolution_notes");
            exit();
        }
        $resolution_notes = trim($_POST['resolution_notes']);
        $update_text = "Issue resolved: " . $resolution_notes;
    }
} else {
    // GET request handling (simple status changes)
    if (!isset($_GET['id']) || !isset($_GET['status'])) {
        header("Location: ./");
        exit();
    }
    
    $issue_id = (int)$_GET['id'];
    $status = $_GET['status'];
    
    // Default text for status changes without additional notes
    $status_messages = [
        'under_review' => 'Issue is now under review.',
        'in_progress' => 'Issue is now in progress.',
        'resolved' => 'Issue has been resolved.',
        'rejected' => 'Issue has been rejected.'
    ];
    
    $update_text = $status_messages[$status] ?? "Status updated to {$status}.";
    $resolution_notes = null;
}

// Validate status value
$valid_statuses = ['pending', 'under_review', 'in_progress', 'resolved', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    header("Location: view.php?id={$issue_id}&error=invalid_status");
    exit();
}

// Get current issue status to check for valid transitions
$check_query = "SELECT status FROM issues WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $issue_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ./");
    exit();
}

$current_status = $result->fetch_assoc()['status'];

// Define valid status transitions
$valid_transitions = [
    'pending' => ['under_review', 'rejected'],
    'under_review' => ['in_progress', 'rejected'],
    'in_progress' => ['resolved', 'rejected'],
    'resolved' => [], // No further transitions allowed
    'rejected' => []  // No further transitions allowed
];

// Check if transition is valid
if (!in_array($status, $valid_transitions[$current_status])) {
    header("Location: view.php?id={$issue_id}&error=invalid_transition");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert update record
    $update_stmt = $conn->prepare("INSERT INTO issue_updates (issue_id, officer_id, update_text, status_change, created_at) VALUES (?, ?, ?, ?, NOW())");
    $update_stmt->bind_param("iiss", $issue_id, $_SESSION['pa_id'], $update_text, $status);
    $update_stmt->execute();
    
    // Update issue status
    $update_query = "UPDATE issues SET status = ?, updated_at = NOW()";
    $params = [$status];
    $types = "s";
    
    // If resolved, set resolved_at and resolution_notes
    if ($status === 'resolved' && $resolution_notes) {
        $update_query .= ", resolved_at = NOW(), resolution_notes = ?";
        $params[] = $resolution_notes;
        $types .= "s";
    }
    
    $update_query .= " WHERE id = ?";
    $params[] = $issue_id;
    $types .= "i";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to issue view
    header("Location: view.php?id={$issue_id}&status_updated=1");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Redirect with error message
    header("Location: view.php?id={$issue_id}&error=update_failed");
    exit();
}