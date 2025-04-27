<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to issues page if not a POST request
    header("Location: ./");
    exit;
}

// Check if issue_id is set
if (!isset($_POST['issue_id']) || empty($_POST['issue_id'])) {
    $_SESSION['error'] = "Invalid issue ID.";
    header("Location: ./");
    exit;
}

// Include database connection
require_once '../../../config/db.php';

// Get the issue ID
$issue_id = (int)$_POST['issue_id'];
$officer_id = $_SESSION['officer_id'];

// First verify that the issue belongs to the logged in officer
$check_query = "SELECT id FROM issues WHERE id = ? AND officer_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $issue_id, $officer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    // Issue doesn't exist or doesn't belong to this officer
    $_SESSION['error'] = "You don't have permission to delete this issue or the issue doesn't exist.";
    header("Location: ./");
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete issue photos first (to prevent orphaned records and handle foreign key constraints)
    $delete_photos_query = "DELETE FROM issue_photos WHERE issue_id = ?";
    $delete_photos_stmt = $conn->prepare($delete_photos_query);
    $delete_photos_stmt->bind_param("i", $issue_id);
    $delete_photos_stmt->execute();
    
    // Delete issue comments if they exist
    $delete_comments_query = "DELETE FROM issue_comments WHERE issue_id = ?";
    $delete_comments_stmt = $conn->prepare($delete_comments_query);
    $delete_comments_stmt->bind_param("i", $issue_id);
    $delete_comments_stmt->execute();
    
    // Finally, delete the issue
    $delete_issue_query = "DELETE FROM issues WHERE id = ? AND officer_id = ?";
    $delete_issue_stmt = $conn->prepare($delete_issue_query);
    $delete_issue_stmt->bind_param("ii", $issue_id, $officer_id);
    $delete_issue_stmt->execute();
    
    // Check if any rows were affected
    if ($delete_issue_stmt->affected_rows > 0) {
        $conn->commit();
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Issue deleted successfully.'
        ];
    } else {
        throw new Exception("Failed to delete the issue.");
    }
} catch (Exception $e) {
    // Rollback the transaction if any query fails
    $conn->rollback();
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Error deleting issue: ' . $e->getMessage()
    ];
}

// Close statements
if (isset($check_stmt)) $check_stmt->close();
if (isset($delete_photos_stmt)) $delete_photos_stmt->close();
if (isset($delete_comments_stmt)) $delete_comments_stmt->close();
if (isset($delete_issue_stmt)) $delete_issue_stmt->close();

// Redirect back to issues page
header("Location: ./");
exit;
?>