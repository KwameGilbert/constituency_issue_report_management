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
    // Find user by email
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status, password_reset_required FROM users WHERE email = ? LIMIT 1");
    $stmt->bindValue(1, $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists
        if ($user) {

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['status'] === 'inactive') {
                    $response['message'] = 'Your account has been deactivated';
                    echo json_encode($response);
                    exit;
                }

                // Check if user is an agent
                if ($user['role'] !== 'officer') {
                    $response['message'] = 'Access denied. This portal is only for agents';
                    echo json_encode($response);
                    exit;
                }

                // Authentication successful - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['password_reset_required'] = $user['password_reset_required'] ?? 0;
                // electoral_area/main_community_id not present in users table
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();

                // Update last login time
                $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->bindValue(1, $user['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                // Set success response
                $response['success'] = true;
                $response['message'] = 'Login successful';
                $response['redirect'] = '../dashboard/';
            } else {
                // Password is incorrect
                $response['message'] = 'Incorrect password';
            }
        } else {
            // User not found
            $response['message'] = 'User not found';
        }
    } catch (Exception $e) {
        // System error
        error_log("Login error: " . $e->getMessage());
        $response['message'] = 'System error. Please try again later';
    }
} else {
    // Not a POST request
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
