<?php
// api/get_issue_subsectors.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$sector_id = isset($_GET['sector_id']) ? (int)$_GET['sector_id'] : 0;

$subsectors = [];

if ($sector_id > 0) {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, name FROM issue_subsectors WHERE sector_id = ?");
    $stmt->execute([$sector_id]);

    $subsectors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($subsectors);
