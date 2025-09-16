<?php
/**
 * API: Update Youth Record Status
 * 
 * Endpoint for updating just the status of a youth record
 * Method: PATCH or POST with _method=PATCH
 * 
 * URL parameters:
 * - id: Youth record ID
 * - status: New status (pending, approved, rejected, archived)
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

// Check if the user has permission to update records
if (!$auth->hasPermission('update_youth_records')) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to update youth records',
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

// Get the new status
$valid_statuses = ['pending', 'approved', 'rejected', 'archived'];
$input = json_decode(file_get_contents('php://input'), true);
$new_status = isset($input['status']) ? $input['status'] : '';

if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid status value. Must be one of: ' . implode(', ', $valid_statuses),
        'error_code' => 'invalid_status'
    ]);
    exit;
}

try {
    // Get current record to check for status change
    $stmt = $conn->prepare("SELECT name, status FROM youth_records WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Youth record not found',
            'error_code' => 'record_not_found'
        ]);
        exit;
    }
    
    $youth_name = $result['name'];
    $current_status = $result['status'];
    
    // Update status
    $user_id = $auth->getUserId();
    
    if ($current_status === 'pending' && $new_status !== 'pending') {
        // First review, update review details
        $stmt = $conn->prepare("
            UPDATE youth_records 
            SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $user_id, $id]);
    } else {
        // Just update status
        $stmt = $conn->prepare("UPDATE youth_records SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
    }
    
    // Add admin notes if provided
    if (isset($input['admin_notes']) && !empty($input['admin_notes'])) {
        $stmt = $conn->prepare("UPDATE youth_records SET admin_notes = ? WHERE id = ?");
        $stmt->execute([$input['admin_notes'], $id]);
    }
    
    // Log the action
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'update_youth_status', ?, ?, ?)
    ");
    $details = "Updated youth record ID: $id status from $current_status to $new_status for: " . $youth_name;
    $stmt->execute([$user_id, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Youth record status updated successfully',
        'data' => [
            'id' => $id,
            'new_status' => $new_status
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error updating youth record status: ' . $e->getMessage(),
        'error_code' => 'database_error'
    ]);
}