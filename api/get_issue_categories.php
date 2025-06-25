<?php
// api/get_issue_categories.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();

$categories = [];

$stmt = $conn->query("SELECT id, name FROM issue_categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categories);
