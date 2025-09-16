<?php
/**
 * API: Get Single Youth Record
 * 
 * Endpoint for retrieving a single youth record by ID
 * Method: GET
 * 
 * URL parameter:
 * - id: Youth record ID
 * 
 * Requires authentication token
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db_connection.php';

// Check if the request is authenticated
$auth = new Auth();
if (!$auth->validateApiRequest()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access',
        'error_code' => 'unauthorized'
    ]);
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get the youth record ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or missing youth record ID',
        'error_code' => 'invalid_parameter'
    ]);
    exit;
}

$id = (int)$_GET['id'];

try {
    // Query to fetch the youth record
    $stmt = $conn->prepare("SELECT * FROM youth_records WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Youth record not found',
            'error_code' => 'record_not_found'
        ]);
        exit;
    }
    
    // Get reviewer information if the record has been reviewed
    if ($record['reviewed_by']) {
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmt->execute([$record['reviewed_by']]);
        $reviewer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reviewer) {
            $record['reviewer'] = $reviewer;
        }
    }
    
    // Calculate age from date_of_birth
    $dob = new DateTime($record['date_of_birth']);
    $now = new DateTime();
    $record['age'] = $now->diff($dob)->y;
    
    // Format dates for consistency
    $record['date_of_birth'] = date('Y-m-d', strtotime($record['date_of_birth']));
    $record['created_at'] = date('Y-m-d H:i:s', strtotime($record['created_at']));
    if ($record['updated_at']) {
        $record['updated_at'] = date('Y-m-d H:i:s', strtotime($record['updated_at']));
    }
    if ($record['reviewed_at']) {
        $record['reviewed_at'] = date('Y-m-d H:i:s', strtotime($record['reviewed_at']));
    }
    
    // Convert boolean field to boolean type
    $record['jhs_completed'] = (bool)$record['jhs_completed'];
    
    // Return success response with data
    echo json_encode([
        'status' => 'success',
        'data' => $record
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching youth record: ' . $e->getMessage(),
        'error_code' => 'database_error'
    ]);
}