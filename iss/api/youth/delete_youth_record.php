<?php
/**
 * API: Delete Youth Record
 * 
 * Endpoint for deleting a youth record
 * Method: DELETE or POST with _method=DELETE
 * 
 * URL parameter:
 * - id: Youth record ID
 * 
 * Requires authentication token with sufficient permissions
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

// Check if the user has permission to delete records
if (!$auth->hasPermission('delete_youth_records')) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to delete youth records',
        'error_code' => 'insufficient_permissions'
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
    // Get the record details first for logging
    $stmt = $conn->prepare("SELECT name FROM youth_records WHERE id = ?");
    $stmt->execute([$id]);
    $youth_name = $stmt->fetchColumn();
    
    if (!$youth_name) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Youth record not found',
            'error_code' => 'record_not_found'
        ]);
        exit;
    }
    
    // Delete the record
    $stmt = $conn->prepare("DELETE FROM youth_records WHERE id = ?");
    $stmt->execute([$id]);
    
    // Log the action
    $user_id = $auth->getUserId();
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'delete_youth_record', ?, ?, ?)
    ");
    $details = "Deleted youth record ID: $id for: " . $youth_name;
    $stmt->execute([$user_id, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Youth record deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error deleting youth record: ' . $e->getMessage(),
        'error_code' => 'database_error'
    ]);
}