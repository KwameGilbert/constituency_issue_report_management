<?php
// toggle_agent_status.php - Handle agent status toggle requests
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Check if user is logged in and has officer role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'officer') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Get form data
$agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
$new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Validate input data
if ($agent_id <= 0) {
    $response['message'] = 'Invalid agent ID provided';
    echo json_encode($response);
    exit;
}

if (!in_array($new_status, ['active', 'inactive'])) {
    $response['message'] = 'Invalid status provided';
    echo json_encode($response);
    exit;
}

if ($action !== 'toggle_status') {
    $response['message'] = 'Invalid action provided';
    echo json_encode($response);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // First, verify that the agent exists and is actually an agent
    $stmt = $conn->prepare("
        SELECT id, name, email, status, role 
        FROM users 
        WHERE id = :agent_id AND role = 'agent'
    ");
    $stmt->bindParam(':agent_id', $agent_id, PDO::PARAM_INT);
    $stmt->execute();

    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        $response['message'] = 'Agent not found or invalid agent ID';
        echo json_encode($response);
        exit;
    }

    // Check if the status is already what we're trying to set
    if ($agent['status'] === $new_status) {
        $response['message'] = 'Agent is already ' . $new_status;
        echo json_encode($response);
        exit;
    }

    // Begin transaction for data consistency
    $conn->beginTransaction();

    // Update the agent's status
    $stmt = $conn->prepare("
        UPDATE users 
        SET status = :status, 
            updated_at = NOW() 
        WHERE id = :agent_id AND role = 'agent'
    ");

    $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
    $stmt->bindParam(':agent_id', $agent_id, PDO::PARAM_INT);

    $updateResult = $stmt->execute();

    if (!$updateResult) {
        throw new Exception('Failed to update agent status');
    }

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        throw new Exception('No changes made to agent status');
    }

    // Log the status change activity
    $officer_id = $_SESSION['user_id'];
    $activity_description = "Agent status changed from '{$agent['status']}' to '{$new_status}' by officer";

    $stmt = $conn->prepare("
        INSERT INTO activity_logs 
            (user_id, target_user_id, action, description, created_at)
        VALUES 
            (:officer_id, :agent_id, :action, :description, NOW())
    ");

    $action_log = 'agent_status_toggle';

    $stmt->bindParam(':officer_id', $officer_id, PDO::PARAM_INT);
    $stmt->bindParam(':agent_id', $agent_id, PDO::PARAM_INT);
    $stmt->bindParam(':action', $action_log, PDO::PARAM_STR);
    $stmt->bindParam(':description', $activity_description, PDO::PARAM_STR);

    try {
        $stmt->execute();
    } catch (Exception $e) {
        // If activity logging fails, we'll continue but note it
        // This prevents the main operation from failing due to logging issues
        error_log("Activity logging failed: " . $e->getMessage());
    }

    // If the agent is being deactivated, we might want to notify them
    if ($new_status === 'inactive') {
        // Optional: Add notification to the agent about status change
        try {
            $stmt = $conn->prepare("
                INSERT INTO notifications 
                    (user_id, message, type, is_read, created_at)
                VALUES 
                    (:user_id, :message, :type, FALSE, NOW())
            ");

            $notification_message = "Your agent account has been deactivated. Please contact your supervisor for more information.";
            $notification_type = "account_status";

            $stmt->bindParam(':user_id', $agent_id, PDO::PARAM_INT);
            $stmt->bindParam(':message', $notification_message, PDO::PARAM_STR);
            $stmt->bindParam(':type', $notification_type, PDO::PARAM_STR);

            $stmt->execute();
        } catch (Exception $e) {
            // If notification fails, log it but don't fail the main operation
            error_log("Agent notification failed: " . $e->getMessage());
        }
    } elseif ($new_status === 'active') {
        // Optional: Add notification to the agent about reactivation
        try {
            $stmt = $conn->prepare("
                INSERT INTO notifications 
                    (user_id, message, type, is_read, created_at)
                VALUES 
                    (:user_id, :message, :type, FALSE, NOW())
            ");

            $notification_message = "Your agent account has been reactivated. You can now access the system normally.";
            $notification_type = "account_status";

            $stmt->bindParam(':user_id', $agent_id, PDO::PARAM_INT);
            $stmt->bindParam(':message', $notification_message, PDO::PARAM_STR);
            $stmt->bindParam(':type', $notification_type, PDO::PARAM_STR);

            $stmt->execute();
        } catch (Exception $e) {
            // If notification fails, log it but don't fail the main operation
            error_log("Agent notification failed: " . $e->getMessage());
        }
    }

    // Commit the transaction
    $conn->commit();

    // Prepare success response
    $response['success'] = true;
    $response['message'] = "Agent '{$agent['name']}' has been successfully " . ($new_status === 'active' ? 'activated' : 'deactivated');
    $response['data'] = [
        'agent_id' => $agent_id,
        'agent_name' => $agent['name'],
        'old_status' => $agent['status'],
        'new_status' => $new_status,
        'updated_at' => date('Y-m-d H:i:s')
    ];
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Log the error for debugging
    error_log("Agent status toggle error: " . $e->getMessage());

    // Return error response
    $response['message'] = 'An error occurred while updating agent status: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit;
