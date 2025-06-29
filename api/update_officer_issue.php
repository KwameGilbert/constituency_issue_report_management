<?php
// api/update_issue.php
header('Content-Type: application/json'); 
require_once __DIR__ . '/../config/db_connection.php';

// Start session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is logged in and their agent_id is available
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}
$officerId = $_SESSION['user_id'];

// Get the issue ID from the URL query parameter
$issue_id = $_GET['id'] ?? null;

if (!$issue_id) {
    echo json_encode(['success' => false, 'message' => 'Issue ID is missing.']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection(); 
    $conn->beginTransaction(); 

    // Step 1: Update constituent details
    $constituent_id = $_POST['constituent_id'] ?? null;
    if (!$constituent_id) {
        throw new Exception('Constituent ID is missing for update.');
    }

    $stmt = $conn->prepare("UPDATE constituents SET name = ?, phone = ?, location = ?, email = ?, gender = ? WHERE id = ?");
    $stmt->execute([
        $_POST['constituent_name'],
        $_POST['constituent_phone'],
        $_POST['constituent_address'],
        $_POST['constituent_email'],
        $_POST['constituent_gender'],
        $constituent_id
    ]);

    // Step 2: Update issue details
    $issueStmt = $conn->prepare("
        UPDATE issues SET
            title = ?, description = ?, location = ?,
            electoral_area_id = ?, community_id = ?, suburb_id = ?,
            category_id = ?, sector_id = ?, subsector_id = ?,
            type = ?, severity = ?, people_affected = ?,
            additional_notes = ?
        WHERE id = ? AND officer_id = ?
    ");
    $issueStmt->execute([
        $_POST['title'],
        $_POST['description'],
        $_POST['location'],
        $_POST['electoral_area_id'],
        $_POST['community_id'],
        $_POST['suburb_id'],
        $_POST['category_id'],
        $_POST['sector_id'],
        $_POST['subsector_id'],
        $_POST['type'],
        $_POST['severity'],
        $_POST['people_affected'],
        $_POST['additional_notes'],
        $issue_id, 
        $officerId
    ]);

    $conn->commit(); 
    echo json_encode(['success' => true, 'message' => 'Issue updated successfully.']);
} catch (Exception $e) {
    $conn->rollBack(); // Rollback the transaction if any error occurred
    echo json_encode(['success' => false, 'message' => 'Update failed.', 'error' => $e->getMessage()]);
}
