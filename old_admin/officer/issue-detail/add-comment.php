<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $officer_id = $_SESSION['officer_id'];
    
    // Validate input
    if ($issue_id <= 0) {
        $_SESSION['error'] = "Invalid issue ID.";
        header("Location: ../issues/");
        exit();
    }
    
    if (empty($comment)) {
        $_SESSION['error'] = "Comment cannot be empty.";
        header("Location: index.php?id=" . $issue_id);
        exit();
    }
    
    // Verify the issue exists and belongs to this officer
    $check_query = "SELECT id FROM issues WHERE id = ? AND officer_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $issue_id, $officer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $_SESSION['error'] = "You don't have permission to comment on this issue.";
        header("Location: ../issues/");
        exit();
    }
    
    // Insert the comment
    $insert_query = "INSERT INTO issue_comments (issue_id, officer_id, comment, created_at) VALUES (?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iis", $issue_id, $officer_id, $comment);
    
    if ($insert_stmt->execute()) {
        $_SESSION['success'] = "Comment added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add comment: " . $conn->error;
    }
    
    // Redirect back to the issue details page
    header("Location: index.php?id=" . $issue_id);
    exit();
} else {
    // If someone tries to access this file directly without posting data
    header("Location: ../issues/");
    exit();
}
?>