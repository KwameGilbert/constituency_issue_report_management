<?php
session_start();
header("Content-Type: application/json");
require_once '../../../config/db.php';

// Check if user is logged in as PA
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$pa_id = $_SESSION['pa_id'];

// Handle AJAX comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $response = ['success' => false];
    
    // Validate project ID
    if (!isset($_POST['project_id']) || !is_numeric($_POST['project_id'])) {
        $response['message'] = 'Invalid project ID';
        echo json_encode($response);
        exit;
    }
    
    $project_id = intval($_POST['project_id']);
    
    // Verify project exists
    $check_query = "SELECT id FROM projects WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $project_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $response['message'] = 'Project not found';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (empty($_POST['comment'])) {
        $response['message'] = 'Comment is required';
        echo json_encode($response);
        exit;
    }
    
    // Get input values
    $comment = trim($_POST['comment']);
    
    // Insert comment
    $insert_query = "INSERT INTO project_comments (project_id, pa_id, comment, created_at) 
                   VALUES (?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iis", $project_id, $pa_id, $comment);
    
    if ($insert_stmt->execute()) {
        // Get PA name for the response
        $pa_query = "SELECT name FROM personal_assistants WHERE id = ?";
        $pa_stmt = $conn->prepare($pa_query);
        $pa_stmt->bind_param("i", $pa_id);
        $pa_stmt->execute();
        $pa_result = $pa_stmt->get_result();
        $pa_data = $pa_result->fetch_assoc();
        
        $author_name = $pa_data['name'] ?? 'User';
        $author_initial = strtoupper(substr($author_name, 0, 1));
        
        $response['success'] = true;
        $response['message'] = 'Comment added successfully';
        $response['author_name'] = $author_name;
        $response['author_initial'] = $author_initial;
        $response['comment_id'] = $insert_stmt->insert_id;
    } else {
        $response['message'] = 'Failed to add comment: ' . $conn->error;
    }
    
    echo json_encode($response);
    exit;
}

// If not an AJAX request, redirect to projects page
header('Location: index.php');
exit;
?>