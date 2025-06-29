<?php
// api/create_issue.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db_connection.php';
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$agentId = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->beginTransaction();

    // Step 1: Insert constituent
    $stmt = $conn->prepare("INSERT INTO constituents (name, phone, location, email, gender) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['constituent_name'],
        $_POST['constituent_phone'],
        $_POST['constituent_address'],
        $_POST['constituent_email'], 
        $_POST['constituent_gender']
    ]);
    $constituent_id = $conn->lastInsertId();

    // Step 2: Insert issue
    $issueStmt = $conn->prepare("
        INSERT INTO issues (
            title, description, location,
            electoral_area_id, community_id, suburb_id,
            category_id, sector_id, subsector_id,
            type, severity, status,
            agent_id, constituent_id, people_affected,
            additional_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
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
        $agentId,
        $constituent_id,
        $_POST['people_affected'],
        $_POST['additional_notes']
    ]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Issue submitted successfully.']);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Submission failed.', 'error' => $e->getMessage()]);
}