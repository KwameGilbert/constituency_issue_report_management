<?php
// users/toggle_user_status.php - Toggle user active/inactive status
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();

// Get user ID and desired status from query parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) && $_GET['status'] === 'active' ? 'active' : 'inactive';

if ($user_id <= 0) {
    header('Location: ./index.php?message=Invalid+user+ID&type=error');
    exit;
}

// Prevent self-deactivation
if ($user_id == $_SESSION['user_id'] && $status === 'inactive') {
    header('Location: ./index.php?message=You+cannot+deactivate+your+own+account&type=error');
    exit;
}

try {
    // First get the user details for the activity log
    $stmt = $conn->prepare("SELECT name, role, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: ./index.php?message=User+not+found&type=error');
        exit;
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $user_id]);
    
    // Log the activity
    $admin_id = $_SESSION['user_id'];
    $action = $status === 'active' ? 'user_activated' : 'user_deactivated';
    $details = "User '{$user['name']}' ({$user['role']}) was " . ($status === 'active' ? 'activated' : 'deactivated') . " by administrator";
    
    $stmt = $conn->prepare("
        INSERT INTO activity_logs 
            (user_id, action, details, created_at)
        VALUES 
            (?, ?, ?, NOW())
    ");
    $stmt->execute([$admin_id, $action, $details]);
    
    // Redirect back with success message
    $message = "User status has been updated to " . ucfirst($status);
    header("Location: ./view_user.php?id={$user_id}&message=" . urlencode($message) . "&type=success");
    
} catch (Exception $e) {
    // Redirect back with error message
    header('Location: ./index.php?message=' . urlencode('Error updating user status: ' . $e->getMessage()) . '&type=error');
}
