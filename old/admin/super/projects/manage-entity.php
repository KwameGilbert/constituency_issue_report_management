<?php
// filepath: c:\xampp\htdocs\swma\admin\super\projects\manage-entity.php
session_start();
require_once '../../../config/db.php';

// Authentication check
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Check if POST data exists
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get action type
$action = $_POST['action'] ?? '';

// Process different actions
switch ($action) {
    case 'create':
        createEntity();
        break;
    case 'update':
        updateEntity();
        break;
    case 'delete':
        deleteEntity();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
}

// Function to create new entity
function createEntity() {
    global $conn;
    
    // Validate required fields
    if (empty($_POST['name']) || empty($_POST['type']) || empty($_POST['project_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Name, type, and project ID are required']);
        exit();
    }
    
    // Get form data
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $project_id = (int)$_POST['project_id'];
    $contact_person = !empty($_POST['contact_person']) ? trim($_POST['contact_person']) : null;
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }
    
    // Insert entity
    $query = "INSERT INTO entities (name, type, project_id, contact_person, phone, email, address, notes) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssisssss", $name, $type, $project_id, $contact_person, $phone, $email, $address, $notes);
    
    if ($stmt->execute()) {
        $entity_id = $conn->insert_id;
        
        // Get the newly created entity
        $query = "SELECT * FROM entities WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $entity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entity = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Entity created successfully',
            'operation' => 'create',
            'entity' => $entity
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to create entity: ' . $conn->error
        ]);
    }
}

// Function to update existing entity
function updateEntity() {
    global $conn;
    
    // Validate required fields
    if (empty($_POST['name']) || empty($_POST['type']) || empty($_POST['entity_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Name, type, and entity ID are required']);
        exit();
    }
    
    // Get form data
    $entity_id = (int)$_POST['entity_id'];
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $contact_person = !empty($_POST['contact_person']) ? trim($_POST['contact_person']) : null;
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }
    
    // Update entity
    $query = "UPDATE entities SET 
              name = ?, 
              type = ?, 
              contact_person = ?, 
              phone = ?, 
              email = ?, 
              address = ?, 
              notes = ?,
              updated_at = CURRENT_TIMESTAMP
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssi", $name, $type, $contact_person, $phone, $email, $address, $notes, $entity_id);
    
    if ($stmt->execute()) {
        // Get the updated entity
        $query = "SELECT * FROM entities WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $entity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entity = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Entity updated successfully',
            'operation' => 'update',
            'entity' => $entity
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update entity: ' . $conn->error
        ]);
    }
}

// Function to delete entity
function deleteEntity() {
    global $conn;
    
    // Validate entity ID
    if (empty($_POST['entity_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Entity ID is required']);
        exit();
    }
    
    $entity_id = (int)$_POST['entity_id'];
    
    // Delete entity
    $query = "DELETE FROM entities WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $entity_id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Entity deleted successfully',
            'operation' => 'delete',
            'entity_id' => $entity_id
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete entity: ' . $conn->error
        ]);
    }
}
?>