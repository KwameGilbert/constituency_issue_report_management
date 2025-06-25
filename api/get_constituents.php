<?php
// api/get_constituents.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();

$constituents = [];

$stmt = $conn->query("SELECT id, name, phone AS email FROM constituents ORDER BY name ASC");
$constituents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($constituents);
