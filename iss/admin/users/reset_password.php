<?php
// reset_password.php - Reset user password to their phone number
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Check if the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../dashboard/');
    exit;
}

// Initialize variables
$message = '';
$message_type = '';

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ./');
    exit;
}

$user_id = $_GET['id'];
$database = new Database();
$conn = $database->getConnection();

try {
    // Get user details
    $sql = "SELECT name, email, phone FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Check if user has a phone number
    if (empty($user['phone'])) {
        throw new Exception("User does not have a phone number set");
    }

    // Hash the phone number to use as new password
    $new_password = password_hash($user['phone'], PASSWORD_DEFAULT);

    // Update user's password
    $update_sql = "UPDATE users SET password = ?, password_reset_required = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$new_password, $user_id]);

    if ($update_stmt->rowCount() > 0) {
        $message = "Password has been reset successfully. The user will need to change their password upon next login.";
        $message_type = 'success';
    } else {
        throw new Exception("Failed to reset password");
    }

} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    $message_type = 'error';
}

// Redirect back to users page with message
$redirect_url = './?message=' . urlencode($message) . '&type=' . $message_type;
header("Location: $redirect_url");
exit;
?>
