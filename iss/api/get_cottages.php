<?php
// api/get_cottages.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$smaller_community_id = isset($_GET['smaller_community_id']) ? (int)$_GET['smaller_community_id'] : 0;

$cottages = [];

if ($smaller_community_id > 0) {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, name FROM cottages WHERE smaller_community_id = ?");
    $stmt->execute([$smaller_community_id]);

    $cottages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($cottages);
