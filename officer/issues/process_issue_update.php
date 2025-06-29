<?php
// Include necessary files for session management and database connection.
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Instantiate the Database class to get a connection.
$database = new Database();
$conn = $database->getConnection();

// Initialize message variables for URL redirection
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data.
    $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);
    $officerId = $_SESSION['user_id'] ?? null; // Get user_id from session, default to null if not set

    // Determine if it's a status update or a general update based on presence of 'new_status' or 'is_general_update'.
    $is_status_update = isset($_POST['new_status']);
    $is_general_update = isset($_POST['is_general_update']);

    if (!$issue_id || !$officerId) {
        $message = 'Invalid issue ID or user not authenticated.';
        $message_type = 'error';
        header("Location: view_issue.php?id=" . $issue_id . "&message=" . urlencode($message) . "&type=" . $message_type);
        exit();
    }

    try {
        $conn->beginTransaction(); // Start a transaction for atomicity

        if ($is_status_update) {
            // --- Handle Status Update ---
            $new_status = trim(filter_input(INPUT_POST, 'new_status', FILTER_DEFAULT));

            // Validate new status against allowed values to prevent invalid updates.
            $allowed_statuses = ['pending', 'reviewed', 'approved', 'rejected', 'resolved'];
            if (!in_array($new_status, $allowed_statuses)) {
                throw new Exception('Invalid status provided.');
            }

            // Fetch current status to ensure procedural flow if needed (e.g., cannot approve if not reviewed)
            $stmt_current_status = $conn->prepare("SELECT status FROM issues WHERE id = ?");
            $stmt_current_status->execute([$issue_id]);
            $current_issue_status = $stmt_current_status->fetchColumn();

            // Example of procedural checks (add more complex logic as needed)
            $proceed_with_status_update = false;
            switch ($current_issue_status) {
                case 'pending':
                    if ($new_status === 'reviewed' || $new_status === 'rejected') $proceed_with_status_update = true;
                    break;
                case 'reviewed':
                    if ($new_status === 'approved' || $new_status === 'rejected') $proceed_with_status_update = true;
                    break;
                case 'approved':
                    if ($new_status === 'resolved') $proceed_with_status_update = true;
                    break;
                // If already rejected or resolved, typically no further status changes are allowed via these buttons
                case 'rejected':
                case 'resolved':
                    $message = 'Issue is already ' . $current_issue_status . ' and cannot be updated via status actions.';
                    $message_type = 'error';
                    // We commit here to allow the previous transaction to complete for any prior actions if any, although for status only it won't be an issue
                    $conn->commit();
                    header("Location: view_issue.php?id=" . $issue_id . "&message=" . urlencode($message) . "&type=" . $message_type);
                    exit();
            }

            if (!$proceed_with_status_update) {
                throw new Exception("Status change from '{$current_issue_status}' to '{$new_status}' is not allowed.");
            }

            // Update the issue status in the issues table.
            $stmt_update_issue = $conn->prepare("UPDATE issues SET status = ?, updated_at = NOW(), resolved_at = CASE WHEN ? = 'resolved' THEN NOW() ELSE resolved_at END WHERE id = ?");
            $stmt_update_issue->execute([$new_status, $new_status, $issue_id]);

            // Add a log entry for the status change.
            $status_action_comment = "Status updated to: " . ucfirst($new_status);
            $stmt_log = $conn->prepare("
                INSERT INTO issue_history_logs (issue_id, user_id, action, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt_log->execute([$issue_id, $officerId, $status_action_comment, $status_action_comment]);

            $message = 'Issue status updated to ' . ucfirst($new_status) . ' successfully.';
            $message_type = 'success';
        } elseif ($is_general_update) {
            // --- Handle General Update (Comment/Attachments) ---
            $update_title = trim(filter_input(INPUT_POST, 'update_title', FILTER_SANITIZE_STRING));
            $update_message = trim(filter_input(INPUT_POST, 'update_message', FILTER_SANITIZE_STRING));
            $notify_agent = isset($_POST['notify_agent']) ? 1 : 0; // Checkbox value

            if (empty($update_title) || empty($update_message)) {
                throw new Exception('Update title and message are required for a general update.');
            }

            // Insert into issue_history_logs for the general update.
            $stmt_log = $conn->prepare("
                INSERT INTO issue_history_logs (issue_id, user_id, action, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $log_action = htmlspecialchars($update_title);
            $stmt_log->execute([$issue_id, $officerId, $log_action, $update_message]);
            $log_id = $conn->lastInsertId();

            $upload_dir = __DIR__ . '/../../uploads/issue_attachments/'; // Adjust path as needed
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
            }

            $upload_errors = [];

            // Helper function for file uploads
            $handleFileUpload = function ($file_array, $issue_id, $log_id, $officerId, $conn, $upload_dir, &$errors) use (&$uploaded_files_count) {
                if (!isset($file_array['name']) || !is_array($file_array['name'])) {
                    return; // No files to process or invalid array structure
                }
                foreach ($file_array['name'] as $key => $name) {
                    if ($file_array['error'][$key] === UPLOAD_ERR_OK) {
                        $file_tmp_name = $file_array['tmp_name'][$key];
                        $file_name = basename($name);
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $file_size = $file_array['size'][$key];
                        $new_file_name = uniqid('attachment_') . '.' . $file_ext;
                        $target_file_path = $upload_dir . $new_file_name;

                        // Basic validation for allowed file types and size
                        $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];
                        $allowed_document_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
                        $max_file_size = 5 * 1024 * 1024; // 5 MB

                        if (!in_array($file_ext, array_merge($allowed_image_types, $allowed_document_types))) {
                            $errors[] = "File type not allowed for {$file_name}.";
                            continue;
                        }
                        if ($file_size > $max_file_size) {
                            $errors[] = "File size too large for {$file_name}. Max 5MB.";
                            continue;
                        }

                        if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                            $file_type = in_array($file_ext, $allowed_image_types) ? 'image' : 'document';
                            $stmt_attach = $conn->prepare("
                                INSERT INTO issue_attachments (issue_id, log_id, file_name, file_path, file_type, file_size, uploaded_by, uploaded_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt_attach->execute([$issue_id, $log_id, $file_name, 'uploads/issue_attachments/' . $new_file_name, $file_type, $file_size, $officerId]);
                        } else {
                            $errors[] = "Failed to upload {$file_name}.";
                        }
                    } else if ($file_array['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = "Upload error for {$name}: " . $file_array['error'][$key];
                    }
                }
            };

            // Handle image uploads
            if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $handleFileUpload($_FILES['images'], $issue_id, $log_id, $officerId, $conn, $upload_dir, $upload_errors);
            }

            // Handle document uploads
            if (isset($_FILES['documents']) && $_FILES['documents']['error'][0] !== UPLOAD_ERR_NO_FILE) {
                $handleFileUpload($_FILES['documents'], $issue_id, $log_id, $officerId, $conn, $upload_dir, $upload_errors);
            }

            $message = 'Issue update added successfully.';
            $message_type = 'success';
            if (!empty($upload_errors)) {
                $message .= ' Some files could not be uploaded: ' . implode(', ', $upload_errors);
                $message_type = 'warning'; // Change type to warning if there are upload issues
            }

            // You might want to add logic here to actually notify the agent if $notify_agent is 1
            // e.g., send an email or push notification.

        } else {
            // This case should ideally not be reached if forms are structured correctly
            $message = 'No valid action specified for the update.';
            $message_type = 'error';
        }

        $conn->commit(); // Commit the transaction if all operations were successful

    } catch (Exception $e) {
        $conn->rollBack(); // Rollback on error
        $message = 'Error processing issue update: ' . $e->getMessage();
        $message_type = 'error';
    }
} else {
    $message = 'Invalid request method.';
    $message_type = 'error';
}

// Redirect back to the view_issue page with a message
header("Location: view_issue.php?id=" . $issue_id . "&message=" . urlencode($message) . "&type=" . $message_type);
exit();
