<?php
// api/get_issue_sectors.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();

$sectors = [];

$stmt = $conn->query("SELECT id, name FROM issue_sectors ORDER BY name ASC");
$sectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($sectors);
