<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../issues/");
    exit();
}

// Validate required fields
if (!isset($_POST['issue_id']) || empty($_POST['issue_id']) || !isset($_POST['update_text']) || empty($_POST['update_text'])) {
    header("Location: ../issues/?error=missing_fields");
    exit();
}

$issue_id = (int)$_POST['issue_id'];
$update_text = trim($_POST['update_text']);
$pa_id = $_SESSION['pa_id'];

// Verify that the issue exists
$check_query = "SELECT id, status FROM issues WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $issue_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    // Issue not found
    header("Location: ../issues/?error=issue_not_found");
    exit();
}

$issue = $result->fetch_assoc();

// Don't allow updates on rejected issues
if ($issue['status'] === 'rejected') {
    header("Location: ../issues/view.php?id=" . $issue_id . "&error=rejected_issue");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert the status update
    $insert_query = "INSERT INTO issue_updates (issue_id, officer_id, update_text, created_at) 
                    VALUES (?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iis", $issue_id, $pa_id, $update_text);
    $insert_stmt->execute();
    
    // Update the issue's updated_at timestamp
    $update_issue_query = "UPDATE issues SET updated_at = NOW() WHERE id = ?";
    $update_issue_stmt = $conn->prepare($update_issue_query);
    $update_issue_stmt->bind_param("i", $issue_id);
    $update_issue_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to issue view with success message
    header("Location: ../issues/view.php?id=" . $issue_id . "&update_added=1");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Redirect with error message
    header("Location: ../issues/view.php?id=" . $issue_id . "&error=update_failed");
    exit();
}