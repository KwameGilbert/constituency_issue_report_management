<?php
// filepath: c:\xampp\htdocs\swma\admin\super\projects\get-entity.php
session_start();
require_once '../../../config/db.php';

// Authentication check
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Check if entity ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid entity ID']);
    exit();
}

$entity_id = (int)$_GET['id'];

// Get entity details
$query = "SELECT * FROM entities WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $entity_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Entity not found']);
    exit();
}

$entity = $result->fetch_assoc();

// Return entity data
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'entity' => $entity
]);
?>