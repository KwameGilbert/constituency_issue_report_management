<?php
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once __DIR__ . '/../../config/db_connection.php';
$database = new Database();
$conn = $database->getConnection();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Check if form was submitted with POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Check for empty fields
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please fill in all required fields';
        echo json_encode($response);
        exit;
    }

    try {
        // Find admin user by email in users table
        $stmt = $conn->prepare("
            SELECT id, name, email, password, role, phone, main_community_id,
                   smaller_community_id, suburb_id, cottage_id, 
                   department, status, last_login 
            FROM users 
            WHERE email = ? 
            LIMIT 1
        ");
        $stmt->bindValue(1, $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['status'] === 'inactive') {
                    $response['message'] = 'Your administrator account has been deactivated. Please contact system security.';
                    echo json_encode($response);
                    exit;
                }

                // Check if user has admin privileges (mp, mce, pa, or admin)
                $adminRoles = ['mp', 'mce', 'pa', 'admin'];
                if (!in_array($user['role'], $adminRoles)) {
                    $response['message'] = 'Access denied. Administrative privileges required for this portal.';
                    echo json_encode($response);
                    exit;
                }

                // Authentication successful - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['main_community_id'] = $user['main_community_id'];
                $_SESSION['smaller_community_id'] = $user['smaller_community_id'];
                $_SESSION['suburb_id'] = $user['suburb_id'];
                $_SESSION['cottage_id'] = $user['cottage_id'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['logged_in'] = true;
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['last_activity'] = time();
                $_SESSION['is_admin'] = true;

                // Get client information for activity logging
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                // Update last login time
                $updateStmt = $conn->prepare("
                    UPDATE users 
                    SET last_login = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $updateStmt->bindValue(1, $user['id'], PDO::PARAM_INT);
                $updateStmt->execute();

                // Log the admin login activity in activity_logs table
                try {
                    $logStmt = $conn->prepare("
                        INSERT INTO activity_logs 
                            (user_id, action, details, ip_address, user_agent, created_at)
                        VALUES 
                            (?, 'admin_login', ?, ?, ?, NOW())
                    ");
                    $loginDetails = "Administrator ({$user['role']}) logged into admin dashboard";
                    $logStmt->execute([
                        $user['id'],
                        $loginDetails,
                        $ip_address,
                        $user_agent
                    ]);
                } catch (Exception $logError) {
                    // Log the error but don't fail the login
                    error_log("Admin activity logging failed: " . $logError->getMessage());
                }

                // Set success response with role-specific message
                $roleNames = [
                    'mp' => 'Member of Parliament',
                    'mce' => 'Municipal Chief Executive',
                    'pa' => 'Personal Assistant',
                    'admin' => 'System Administrator'
                ];
                $roleName = $roleNames[$user['role']] ?? 'Administrator';

                $response['success'] = true;
                $response['message'] = "Welcome, {$roleName}. Access granted to administrative dashboard.";
                $response['redirect'] = '../dashboard/';
            } else {
                // Password is incorrect
                $response['message'] = 'Invalid administrator credentials. Access denied.';

                // Log failed login attempt
                try {
                    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                    $logStmt = $conn->prepare("
                        INSERT INTO activity_logs 
                            (user_id, action, details, ip_address, user_agent, created_at)
                        VALUES 
                            (?, 'failed_admin_login', ?, ?, ?, NOW())
                    ");
                    $failDetails = "Failed admin login attempt for email: {$email}";
                    $logStmt->execute([
                        $user['id'],
                        $failDetails,
                        $ip_address,
                        $user_agent
                    ]);
                } catch (Exception $logError) {
                    error_log("Failed login logging error: " . $logError->getMessage());
                }
            }
        } else {
            // User not found
            $response['message'] = 'Administrator account not found. Access restricted.';

            // Log unrecognized login attempt (without user_id since user doesn't exist)
            try {
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                $logStmt = $conn->prepare("
                    INSERT INTO activity_logs 
                        (user_id, action, details, ip_address, user_agent, created_at)
                    VALUES 
                        (NULL, 'unrecognized_admin_login', ?, ?, ?, NOW())
                ");
                $unrecognizedDetails = "Admin login attempt with unrecognized email: {$email}";
                $logStmt->execute([
                    $unrecognizedDetails,
                    $ip_address,
                    $user_agent
                ]);
            } catch (Exception $logError) {
                error_log("Unrecognized login logging error: " . $logError->getMessage());
            }
        }
    } catch (Exception $e) {
        // System error
        error_log("Admin login system error: " . $e->getMessage());
        $response['message'] = 'System authentication error. Please contact technical support immediately.' . $e->getMessage();

        // Log system error
        try {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $logStmt = $conn->prepare("
                INSERT INTO activity_logs 
                    (user_id, action, details, ip_address, user_agent, created_at)
                VALUES 
                    (NULL, 'admin_system_error', ?, ?, ?, NOW())
            ");
            $errorDetails = "Admin login system error: {$e->getMessage()}";
            $logStmt->execute([
                $errorDetails,
                $ip_address,
                $user_agent
            ]);
        } catch (Exception $logError) {
            error_log("System error logging failed: " . $logError->getMessage());
        }
    }
} else {
    // Not a POST request
    $response['message'] = 'Invalid request method. Security violation detected.';
}

// Return JSON response
echo json_encode($response);
