<?php
// api/create_agent_issue.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db_connection.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$agentId = $_SESSION['user_id'] ?? null;

try {
    $database = new Database();
    $conn = $database->getConnection();
    $conn->beginTransaction();

    // Defensive helpers for POST data
    $p = function($key, $default = null) {
        return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
    };

    // Step 1: Insert or find constituent
    $constituentId = null;
    $constituentName = trim($p('constituent_name', ''));
    $constituentPhone = trim($p('constituent_phone', ''));
    $constituentLocation = trim($p('constituent_address', ''));
    $constituentEmail = trim($p('constituent_email', null));
    $constituentGender = trim($p('constituent_gender', null));

    if ($constituentName !== '' || $constituentPhone !== '') {
        $stmt = $conn->prepare("INSERT INTO constituents (name, phone, location) VALUES (?, ?, ?)");
        $stmt->execute([$constituentName ?: null, $constituentPhone ?: null, $constituentLocation ?: null]);
        $constituentId = $conn->lastInsertId();
    }

    // Step 2: Prepare issue insert using new schema columns
    $title = $p('title', '');
    $description = $p('description', '');
    // new schema uses location_description
    $locationDescription = $p('location', $p('location_description', null));

    // Location fields per new schema
    $mainCommunityId = $p('main_community_id', null);
    $smallerCommunityId = $p('smaller_community_id', null);
    $suburbId = $p('suburb_id', null);
    $cottageId = $p('cottage_id', null);

    $categoryId = $p('category_id', null);
    $sectorId = $p('sector_id', null);
    $subsectorId = $p('subsector_id', null);
    $type = $p('type', 'personal');
    $severity = $p('severity', 'low');
    $peopleAffected = $p('people_affected', null);
    $additionalNotes = $p('additional_notes', null);

    $issueStmt = $conn->prepare("INSERT INTO issues (
        title, description, location_description,
        main_community_id, smaller_community_id, suburb_id, cottage_id,
        category_id, sector_id, subsector_id,
        type, severity, status,
        agent_id, constituent_id, people_affected,
        additional_notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");

    $issueStmt->execute([
        $title,
        $description,
        $locationDescription,
        $mainCommunityId,
        $smallerCommunityId,
        $suburbId,
        $cottageId,
        $categoryId,
        $sectorId,
        $subsectorId,
        $type,
        $severity,
        $agentId,
        $constituentId,
        $peopleAffected,
        $additionalNotes
    ]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Issue submitted successfully.']);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Submission failed.', 'error' => $e->getMessage()]);
}