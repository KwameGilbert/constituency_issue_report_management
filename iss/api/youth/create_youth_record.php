<?php
/**
 * API: Create Youth Record
 * 
 * Endpoint for creating a new youth record
 * Method: POST
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

// Check if the user has permission to create records
if (!$auth->hasPermission('create_youth_records')) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'You do not have permission to create youth records',
        'error_code' => 'insufficient_permissions'
    ]);
    exit;
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON data',
        'error_code' => 'invalid_input'
    ]);
    exit;
}

// Validate required fields
$required_fields = ['name', 'date_of_birth', 'national_id', 'home_town', 'residential_community', 'phone_number'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
        'error_code' => 'missing_fields'
    ]);
    exit;
}

// Validate national ID uniqueness
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM youth_records WHERE national_id = ?");
    $stmt->execute([$input['national_id']]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode([
            'status' => 'error',
            'message' => 'A record with this National ID already exists in the system',
            'error_code' => 'duplicate_national_id'
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error validating National ID: ' . $e->getMessage(),
        'error_code' => 'database_error'
    ]);
    exit;
}

// Prepare data array for insertion
try {
    $youth_data = [
        'name' => $input['name'],
        'date_of_birth' => $input['date_of_birth'],
        'national_id' => $input['national_id'],
        'home_town' => $input['home_town'],
        'residential_community' => $input['residential_community'],
        'phone_number' => $input['phone_number'],
        'jhs_completed' => isset($input['jhs_completed']) ? (int)$input['jhs_completed'] : 0,
        'shs_qualification' => $input['shs_qualification'] ?? '',
        'certificate_qualification' => $input['certificate_qualification'] ?? '',
        'diploma_qualification' => $input['diploma_qualification'] ?? '',
        'first_degree' => $input['first_degree'] ?? '',
        'postgraduate_qualification' => $input['postgraduate_qualification'] ?? '',
        'professional_qualification' => $input['professional_qualification'] ?? '',
        'work_experience_1' => $input['work_experience_1'] ?? '',
        'work_experience_2' => $input['work_experience_2'] ?? '',
        'work_experience_3' => $input['work_experience_3'] ?? '',
        'work_experience_4' => $input['work_experience_4'] ?? '',
        'work_experience_5' => $input['work_experience_5'] ?? '',
        'work_experience_6' => $input['work_experience_6'] ?? '',
        'employment_status' => in_array($input['employment_status'] ?? '', ['unemployed', 'employed', 'self_employed', 'student']) ? $input['employment_status'] : 'unemployed',
        'current_employment' => $input['current_employment'] ?? '',
        'employment_notes' => $input['employment_notes'] ?? '',
        'skills' => $input['skills'] ?? '',
        'interests' => $input['interests'] ?? '',
        'availability_status' => in_array($input['availability_status'] ?? '', ['available', 'unavailable', 'part_time']) ? $input['availability_status'] : 'available',
        'preferred_work_location' => $input['preferred_work_location'] ?? '',
        'salary_expectation' => isset($input['salary_expectation']) ? floatval($input['salary_expectation']) : null,
        'status' => in_array($input['status'] ?? '', ['pending', 'approved', 'rejected', 'archived']) ? $input['status'] : 'pending',
        'admin_notes' => $input['admin_notes'] ?? ''
    ];
    
    // If status is not pending, set reviewed_by and reviewed_at
    if ($youth_data['status'] !== 'pending') {
        $youth_data['reviewed_by'] = $auth->getUserId();
        $youth_data['reviewed_at'] = date('Y-m-d H:i:s');
    }
    
    // Insert new record
    $sql_fields = implode(', ', array_keys($youth_data));
    $sql_placeholders = implode(', ', array_fill(0, count($youth_data), '?'));
    
    $stmt = $conn->prepare("INSERT INTO youth_records ($sql_fields) VALUES ($sql_placeholders)");
    $stmt->execute(array_values($youth_data));
    
    $new_id = $conn->lastInsertId();
    
    // Log the action
    $user_id = $auth->getUserId();
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'create_youth_record', ?, ?, ?)
    ");
    $details = "Created new youth record ID: $new_id for: " . $youth_data['name'];
    $stmt->execute([$user_id, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    
    // Return success response
    http_response_code(201); // Created
    echo json_encode([
        'status' => 'success',
        'message' => 'Youth record created successfully',
        'data' => [
            'id' => $new_id
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error creating youth record: ' . $e->getMessage(),
        'error_code' => 'database_error'
    ]);
}