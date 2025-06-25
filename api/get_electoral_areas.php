<?php
// api/get_electoral_areas.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();

$electoralAreas = [];

$stmt = $conn->query("SELECT id, name FROM electoral_areas ORDER BY name ASC");
$electoralAreas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($electoralAreas);
