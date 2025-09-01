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
        $_POST['constituent_name'] ?? null,
        $_POST['constituent_phone'] ?? null,
        $_POST['constituent_address'] ?? null,
        $_POST['constituent_email'] ?? null,
        $_POST['constituent_gender'] ?? null,
        $constituent_id
    ]);

    // Step 2: Update issue details using the new location columns
    $issueStmt = $conn->prepare(
        "UPDATE issues SET
            title = ?, description = ?, location_description = ?,
            main_community_id = ?, smaller_community_id = ?, suburb_id = ?, cottage_id = ?,
            category_id = ?, sector_id = ?, subsector_id = ?,
            type = ?, severity = ?, people_affected = ?,
            additional_notes = ?
        WHERE id = ? AND officer_id = ?"
    );

    $issueStmt->execute([
        $_POST['title'] ?? null,
        $_POST['description'] ?? null,
        $_POST['location_description'] ?? null,
        $_POST['main_community_id'] ?? null,
        $_POST['smaller_community_id'] ?? null,
        $_POST['suburb_id'] ?? null,
        $_POST['cottage_id'] ?? null,
        $_POST['category_id'] ?? null,
        $_POST['sector_id'] ?? null,
        $_POST['subsector_id'] ?? null,
        $_POST['type'] ?? null,
        $_POST['severity'] ?? null,
        $_POST['people_affected'] ?? null,
        $_POST['additional_notes'] ?? null,
        $issue_id,
        $officerId
    ]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Issue updated successfully.']);
} catch (Exception $e) {
    $conn->rollBack(); // Rollback the transaction if any error occurred
    echo json_encode(['success' => false, 'message' => 'Update failed.', 'error' => $e->getMessage()]);
}
