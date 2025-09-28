<?php
// api/get_suburbs.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$community_id = isset($_GET['community_id']) ? (int)$_GET['community_id'] : 0;

$suburbs = [];

if ($community_id > 0) {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, name FROM suburbs WHERE community_id = ?");
    $stmt->execute([$community_id]);

    $suburbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($suburbs);
