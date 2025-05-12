<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in as PA
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$pa_id = $_SESSION['pa_id'];

// Handle AJAX update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_update'])) {
    $response = ['success' => false];
    
    // Validate project ID
    if (!isset($_POST['project_id']) || !is_numeric($_POST['project_id'])) {
        $response['message'] = 'Invalid project ID';
        echo json_encode($response);
        exit;
    }
    
    $project_id = intval($_POST['project_id']);
    
    // Verify project belongs to the PA
    $check_query = "SELECT id FROM projects WHERE id = ? AND pa_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $project_id, $pa_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $response['message'] = 'Project not found or you don\'t have permission to update it';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['content'])) {
        $response['message'] = 'Title and content are required';
        echo json_encode($response);
        exit;
    }
    
    // Get input values
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    // Insert update
    $insert_query = "INSERT INTO project_updates (project_id, title, content, created_at, updated_at) 
                    VALUES (?, ?, ?, NOW(), NOW())";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iss", $project_id, $title, $content);
    
    if ($insert_stmt->execute()) {
        $update_id = $insert_stmt->insert_id;
        
        // Format the date for display
        $created_at = date('M j, Y');
        
        $response['success'] = true;
        $response['message'] = 'Update added successfully';
        $response['update'] = [
            'id' => $update_id,
            'title' => $title,
            'content' => $content,
            'created_at' => $created_at
        ];
    } else {
        $response['message'] = 'Failed to add update: ' . $conn->error;
    }
    
    echo json_encode($response);
    exit;
}

// If not an AJAX request, redirect to projects page
header('Location: index.php');
exit;
?>