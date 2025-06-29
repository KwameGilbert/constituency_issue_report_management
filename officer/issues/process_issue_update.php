<?php
// process_issue_update.php
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id = isset($_POST['issue_id']) ? intval($_POST['issue_id']) : 0;
    $update_title = isset($_POST['update_title']) ? $_POST['update_title'] : '';
    $update_message = isset($_POST['update_message']) ? $_POST['update_message'] : '';
    $status = isset($_POST['status']) && !empty($_POST['status']) ? $_POST['status'] : null;
    $notify_agent = isset($_POST['notify_agent']) ? true : false;
    $officer_id = $_SESSION['user_id'] ?? 0;

    // Validate required data
    if ($issue_id <= 0 || empty($update_title) || empty($update_message) || $officer_id <= 0) {
        // Redirect with error
        header('Location: view_issue.php?id=' . $issue_id . '&error=missing_data');
        exit;
    }

    try {
        // Begin transaction
        $conn->beginTransaction();

        // 1. Add entry to issue_history_logs
        $stmt = $conn->prepare("
            INSERT INTO issue_history_logs 
                (issue_id, user_id, action, comment, created_at)
            VALUES 
                (:issue_id, :user_id, :action, :comment, NOW())
        ");

        $action = $update_title;
        if ($status) {
            $action = "Status updated to: " . ucfirst($status) . " - " . $update_title;
        }

        $stmt->bindParam(':issue_id', $issue_id);
        $stmt->bindParam(':user_id', $officer_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':comment', $update_message);
        $stmt->execute();

        $log_id = $conn->lastInsertId();

        // 2. Update issue status if specified
        if ($status) {
            $stmt = $conn->prepare("
                UPDATE issues 
                SET status = :status,
                    status_description = CONCAT('Status updated to ', :status_label, ' on ', DATE_FORMAT(NOW(), '%M %d, %Y'), '.'),
                    updated_at = NOW()
                WHERE id = :issue_id
            ");

            $status_label = ucfirst($status);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':status_label', $status_label);
            $stmt->bindParam(':issue_id', $issue_id);
            $stmt->execute();

            // If status is resolved, update resolved_at timestamp
            if ($status === 'resolved') {
                $stmt = $conn->prepare("
                    UPDATE issues 
                    SET resolved_at = NOW(),
                        resolved_by = :officer_id
                    WHERE id = :issue_id
                ");
                $stmt->bindParam(':officer_id', $officer_id);
                $stmt->bindParam(':issue_id', $issue_id);
                $stmt->execute();
            }
        }

        // 3. Handle file uploads
        // For this implementation, we'll need to create a new table for attachments
        // Here's how the table structure might look:
        /*
        CREATE TABLE issue_attachments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            issue_id INT,
            log_id INT,
            file_name VARCHAR(255),
            file_path VARCHAR(255),
            file_type VARCHAR(50),
            file_size INT,
            uploaded_by INT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
            FOREIGN KEY (log_id) REFERENCES issue_history_logs(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        );
        */

        // Process image uploads
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $uploadDir = '../../uploads/issues/' . $issue_id . '/images/';

            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    $name = basename($_FILES['images']['name'][$i]);
                    $fileType = $_FILES['images']['type'][$i];
                    $fileSize = $_FILES['images']['size'][$i];

                    // Generate a unique filename
                    $fileName = uniqid() . '_' . $name;
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmp_name, $filePath)) {
                        // Save attachment record
                        $relativePath = 'uploads/issues/' . $issue_id . '/images/' . $fileName;

                        $stmt = $conn->prepare("
                            INSERT INTO issue_attachments 
                                (issue_id, log_id, file_name, file_path, file_type, file_size, uploaded_by)
                            VALUES 
                                (:issue_id, :log_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by)
                        ");

                        $stmt->bindParam(':issue_id', $issue_id);
                        $stmt->bindParam(':log_id', $log_id);
                        $stmt->bindParam(':file_name', $name);
                        $stmt->bindParam(':file_path', $relativePath);
                        $stmt->bindParam(':file_type', $fileType);
                        $stmt->bindParam(':file_size', $fileSize);
                        $stmt->bindParam(':uploaded_by', $officer_id);
                        $stmt->execute();
                    }
                }
            }
        }

        // Process document uploads
        if (!empty($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
            $uploadDir = '../../uploads/issues/' . $issue_id . '/documents/';

            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            for ($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['documents']['tmp_name'][$i];
                    $name = basename($_FILES['documents']['name'][$i]);
                    $fileType = $_FILES['documents']['type'][$i];
                    $fileSize = $_FILES['documents']['size'][$i];

                    // Generate a unique filename
                    $fileName = uniqid() . '_' . $name;
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmp_name, $filePath)) {
                        // Save attachment record
                        $relativePath = 'uploads/issues/' . $issue_id . '/documents/' . $fileName;

                        $stmt = $conn->prepare("
                            INSERT INTO issue_attachments 
                                (issue_id, log_id, file_name, file_path, file_type, file_size, uploaded_by)
                            VALUES 
                                (:issue_id, :log_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by)
                        ");

                        $stmt->bindParam(':issue_id', $issue_id);
                        $stmt->bindParam(':log_id', $log_id);
                        $stmt->bindParam(':file_name', $name);
                        $stmt->bindParam(':file_path', $relativePath);
                        $stmt->bindParam(':file_type', $fileType);
                        $stmt->bindParam(':file_size', $fileSize);
                        $stmt->bindParam(':uploaded_by', $officer_id);
                        $stmt->execute();
                    }
                }
            }
        }

        // 4. Send notification to agent if requested
        if ($notify_agent) {
            // First get the agent ID for this issue
            $stmt = $conn->prepare("SELECT agent_id FROM issues WHERE id = :issue_id");
            $stmt->bindParam(':issue_id', $issue_id);
            $stmt->execute();
            $agent_id = $stmt->fetchColumn();

            // Add notification if agent_id exists
            if ($agent_id) {
                $message = "Issue #{$issue_id} has been updated: {$update_title}";
                $type = "issue_update";

                $stmt = $conn->prepare("
                    INSERT INTO notifications
                        (user_id, message, type, is_read, created_at)
                    VALUES
                        (:user_id, :message, :type, false, NOW())
                ");

                $stmt->bindParam(':user_id', $agent_id);
                $stmt->bindParam(':message', $message);
                $stmt->bindParam(':type', $type);
                $stmt->execute();
            }
        }

        // Commit transaction
        $conn->commit();

        // Redirect with success
        header('Location: view_issue.php?id=' . $issue_id . '&success=update_added');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();

        // Redirect with error
        header('Location: view_issue.php?id=' . $issue_id . '&error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    // Not a POST request, redirect to issues listing
    header('Location: ./');
    exit;
}
