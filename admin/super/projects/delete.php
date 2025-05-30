<?php
session_start();
require_once '../../../config/db.php';

// Check if user is logged in as PA
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../login/");
    exit;
}

// Check if project ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: index.php");
    exit;
}

$project_id = intval($_GET['id']);
$pa_id = $_SESSION['pa_id'];

// Verify that this PA owns this project (security check)
$check_query = "SELECT id, title, images FROM projects WHERE id = ? AND pa_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $project_id, $pa_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to delete this project or the project doesn't exist.";
    header("Location: index.php");
    exit;
}

$project = $result->fetch_assoc();
$project_title = $project['title'];

// Begin transaction to ensure data integrity
$conn->begin_transaction();

try {
    // Delete related entities if the project_entities table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'project_entities'");
    if ($table_check->num_rows > 0) {
        $entity_delete_query = "DELETE FROM project_entities WHERE project_id = ?";
        $entity_delete_stmt = $conn->prepare($entity_delete_query);
        $entity_delete_stmt->bind_param("i", $project_id);
        $entity_delete_stmt->execute();
    }

    // Delete related updates if the project_updates table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'project_updates'");
    if ($table_check->num_rows > 0) {
        $updates_delete_query = "DELETE FROM project_updates WHERE project_id = ?";
        $updates_delete_stmt = $conn->prepare($updates_delete_query);
        $updates_delete_stmt->bind_param("i", $project_id);
        $updates_delete_stmt->execute();
    }

    // Delete related comments if the project_comments table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'project_comments'");
    if ($table_check->num_rows > 0) {
        $comments_delete_query = "DELETE FROM project_comments WHERE project_id = ?";
        $comments_delete_stmt = $conn->prepare($comments_delete_query);
        $comments_delete_stmt->bind_param("i", $project_id);
        $comments_delete_stmt->execute();
    }

    // Delete the project itself
    $project_delete_query = "DELETE FROM projects WHERE id = ?";
    $project_delete_stmt = $conn->prepare($project_delete_query);
    $project_delete_stmt->bind_param("i", $project_id);
    $project_delete_stmt->execute();

    // Commit transaction
    $conn->commit();

    // Delete associated image files
    if (!empty($project['images'])) {
        $images = json_decode($project['images'], true);
        if (is_array($images)) {
            foreach ($images as $image_path) {
                $file_path = "../../../" . ltrim($image_path, '/');
                if (file_exists($file_path) && is_file($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }

    // // Log the deletion
    // $log_query = "INSERT INTO activity_log (user_id, user_type, action, entity_type, entity_id, entity_name, details, created_at) 
    //               VALUES (?, 'pa', 'delete', 'project', ?, ?, 'Project deleted', NOW())";
    // $log_stmt = $conn->prepare($log_query);
    // $log_stmt->bind_param("iis", $pa_id, $project_id, $project_title);
    // $log_stmt->execute();

    $_SESSION['success'] = "Project '{$project_title}' has been successfully deleted.";
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Error deleting project: " . $e->getMessage();
}

// Redirect back to project listing
header("Location: index.php");
exit;
?>