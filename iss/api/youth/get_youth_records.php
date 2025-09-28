<?php
/**
 * API: Get Youth Records
 * 
 * Endpoint for retrieving youth records with filtering options
 * Method: GET
 * 
 * Query parameters:
 * - status: Filter by status (pending, approved, rejected, archived)
 * - employment: Filter by employment status (unemployed, employed, self_employed, student)
 * - qualification: Filter by qualification level (jhs, shs, certificate, diploma, degree, postgrad, professional)
 * - search: Search in name, phone, ID or residential community
 * - page: Page number for pagination
 * - limit: Results per page (default 10, max 50)
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

// Get query parameters with defaults
$status = isset($_GET['status']) ? $_GET['status'] : '';
$employment = isset($_GET['employment']) ? $_GET['employment'] : '';
$qualification = isset($_GET['qualification']) ? $_GET['qualification'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
$offset = ($page - 1) * $limit;

try {
    // Build WHERE clause based on filters
    $where_clauses = [];
    $params = [];
    
    if (!empty($status)) {
        $where_clauses[] = "status = ?";
        $params[] = $status;
    }
    
    if (!empty($employment)) {
        $where_clauses[] = "employment_status = ?";
        $params[] = $employment;
    }
    
    if (!empty($qualification)) {
        // Check for non-empty value in the selected qualification column
        switch($qualification) {
            case 'jhs':
                $where_clauses[] = "jhs_completed = 1";
                break;
            case 'shs':
                $where_clauses[] = "shs_qualification IS NOT NULL AND shs_qualification != ''";
                break;
            case 'certificate':
                $where_clauses[] = "certificate_qualification IS NOT NULL AND certificate_qualification != ''";
                break;
            case 'diploma':
                $where_clauses[] = "diploma_qualification IS NOT NULL AND diploma_qualification != ''";
                break;
            case 'degree':
                $where_clauses[] = "first_degree IS NOT NULL AND first_degree != ''";
                break;
            case 'postgrad':
                $where_clauses[] = "postgraduate_qualification IS NOT NULL AND postgraduate_qualification != ''";
                break;
            case 'professional':
                $where_clauses[] = "professional_qualification IS NOT NULL AND professional_qualification != ''";
                break;
        }
    }
    
    if (!empty($search)) {
        $where_clauses[] = "(name LIKE ? OR phone_number LIKE ? OR national_id LIKE ? OR residential_community LIKE ?)";
        $search_term = "%{$search}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Count total matching records for pagination metadata
    $count_sql = "
        SELECT COUNT(*) 
        FROM youth_records
        " . (!empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "") . "
    ";
    
    $stmt = $conn->prepare($count_sql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);
    
    // Query to fetch youth records with pagination
    $sql = "
        SELECT 
            id, 
            name, 
            date_of_birth, 
            national_id, 
            residential_community, 
            phone_number,
            jhs_completed,
            shs_qualification,
            certificate_qualification,
            diploma_qualification,
            first_degree,
            postgraduate_qualification,
            professional_qualification,
            employment_status,
            current_employment,
            availability_status,
            preferred_work_location,
            status,
            created_at,
            updated_at
        FROM 
            youth_records
        " . (!empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "") . "
        ORDER BY 
            created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters for main query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process records to calculate age and format dates
    foreach ($records as &$record) {
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
        
        // Convert boolean field to boolean type
        $record['jhs_completed'] = (bool)$record['jhs_completed'];
    }
    
    // Return success response with data and pagination metadata
    echo json_encode([
        'status' => 'success',
        'data' => $records,
        'meta' => [
            'total_records' => $total_records,
            'records_per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $total_pages
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching youth records: ' . $e->getMessage(),
        'error_code' => 'database_error'
    ]);
}