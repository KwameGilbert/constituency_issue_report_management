<?php
// admin/officers/toggle_officer_status.php - Toggle Officer Status
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Check if officer ID and status are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status']) || empty($_GET['status'])) {
    header('Location: ./');
    exit;
}

$officer_id = $_GET['id'];
$new_status = $_GET['status'];

$message = '';
$message_type = '';

// Validate status
if (!in_array($new_status, ['active', 'inactive'])) {
    $message = "Invalid status";
    $message_type = 'error';
} else {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Check if officer exists and is actually an officer
        $check_sql = "SELECT name, status FROM users WHERE id = ? AND role = 'officer'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute([$officer_id]);
        $officer = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$officer) {
            throw new Exception("Officer not found");
        }

        // Update officer status
        $update_sql = "UPDATE users SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$new_status, $officer_id]);

        if ($update_stmt->rowCount() > 0) {
            $action = $new_status === 'active' ? 'activated' : 'deactivated';
            $message = "Officer '" . htmlspecialchars($officer['name']) . "' has been {$action} successfully";
            $message_type = 'success';
        } else {
            $message = "No changes were made";
            $message_type = 'error';
        }

    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Redirect back to officers page with message
$redirect_url = './?message=' . urlencode($message) . '&type=' . $message_type;
header("Location: $redirect_url");
exit;
?>
