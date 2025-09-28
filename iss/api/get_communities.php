<?php
// api/get_communities.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$electoral_area_id = isset($_GET['electoral_area_id']) ? (int)$_GET['electoral_area_id'] : 0;

$communities = [];

if ($electoral_area_id > 0) {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, name FROM communities WHERE electoral_area_id = ? ORDER BY name ASC");
    $stmt->execute([$electoral_area_id]);

    $communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($communities);
