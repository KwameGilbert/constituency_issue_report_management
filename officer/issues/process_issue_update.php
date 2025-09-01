<?php
// Include necessary files for session management and database connection.
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

// Instantiate the Database class to get a connection.
$database = new Database();
$conn = $database->getConnection();

header('Content-Type: application/json'); // Set header to indicate JSON response

$response = [
    'success' => false,
    'message' => '',
    'issue_id' => null // Include issue_id in response for client-side use
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data.
    $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);
    $officerId = $_SESSION['user_id'] ?? null; // Get user_id from session, default to null if not set

    // Determine if it's a status update or a general update based on presence of 'new_status' or 'is_general_update'.
    $is_status_update = isset($_POST['new_status']);
    $is_general_update = isset($_POST['is_general_update']);

    $response['issue_id'] = $issue_id; // Always set issue_id in response

    if (!$issue_id || !$officerId) {
        $response['message'] = 'Invalid issue ID or user not authenticated.';
        echo json_encode($response);
        exit();
    }

    try {
        $conn->beginTransaction(); // Start a transaction for atomicity

        if ($is_status_update) {
            // --- Handle Status Update ---
            $new_status = trim(filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING));

            // Validate new status against allowed values to prevent invalid updates.
            $allowed_statuses = ['pending', 'reviewed', 'approved', 'in_progress', 'rejected', 'resolved'];
            if (!in_array($new_status, $allowed_statuses)) {
                throw new Exception('Invalid status provided.');
            }

            // Fetch current status to ensure procedural flow.
            $stmt_current_status = $conn->prepare("SELECT status FROM issues WHERE id = ?");
            $stmt_current_status->execute([$issue_id]);
            $current_issue_status = $stmt_current_status->fetchColumn();

            // Procedural checks for status transitions.
            $proceed_with_status_update = false;
            switch ($current_issue_status) {
                case 'pending':
                    if ($new_status === 'reviewed' || $new_status === 'rejected') $proceed_with_status_update = true;
                    break;
                case 'reviewed':
                    if ($new_status === 'approved' || $new_status === 'rejected') $proceed_with_status_update = true;
                    break;
                case 'approved':
                    if ($new_status === 'in_progress' || $new_status === 'resolved' || $new_status === 'rejected') $proceed_with_status_update = true;
                    break;
                case 'in_progress':
                    if ($new_status === 'resolved' || $new_status === 'rejected') $proceed_with_status_update = true;
                    break;
                case 'rejected':
                case 'resolved':
                    // If already rejected or resolved, do not allow further status changes via these buttons.
                    $response['message'] = 'Issue is already ' . $current_issue_status . ' and cannot be updated via status actions.';
                    $conn->commit(); // Commit any prior changes
                    echo json_encode($response);
                    exit();
            }

            if (!$proceed_with_status_update) {
                throw new Exception("Status change from '{$current_issue_status}' to '{$new_status}' is not allowed.");
            }

            // Update the issue status in the issues table.
            $stmt_update_issue = $conn->prepare("UPDATE issues SET status = ?, updated_at = NOW(), resolved_at = CASE WHEN ? = 'resolved' THEN NOW() ELSE resolved_at END WHERE id = ?");
            $stmt_update_issue->execute([$new_status, $new_status, $issue_id]);

            // Add a log entry for the status change in the `issue_updates` table.
            $status_action_comment = "Status updated to: " . ucfirst($new_status);
            $stmt_log = $conn->prepare("
                INSERT INTO issue_updates (issue_id, user_id, action, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt_log->execute([$issue_id, $officerId, $status_action_comment, $status_action_comment]);

            $response['success'] = true;
            $response['message'] = 'Issue status updated to ' . ucfirst($new_status) . ' successfully.';
        } elseif ($is_general_update) {
            // --- Handle General Update (Comment/Attachments) ---
            $update_title = trim(filter_input(INPUT_POST, 'update_title', FILTER_UNSAFE_RAW));
            $update_message = trim(filter_input(INPUT_POST, 'update_message', FILTER_UNSAFE_RAW));
            $notify_agent = isset($_POST['notify_agent']) ? 1 : 0; // Checkbox value

            if (empty($update_title) || empty($update_message)) {
                throw new Exception('Update title and message are required for a general update.');
            }

            // Insert into `issue_updates` for the general update.
            $stmt_log = $conn->prepare("
                INSERT INTO issue_updates (issue_id, user_id, action, message, created_at)
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
            $handleFileUpload = function ($file_array, $issue_id, $log_id, $officerId, $conn, $upload_dir, &$errors) {
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
                                INSERT INTO issue_attachments (issue_id, update_id, file_name, file_path, file_type, file_size, uploaded_by, uploaded_at)
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

            $response['success'] = true;
            $response['message'] = 'Issue update added successfully.';
            if (!empty($upload_errors)) {
                $response['message'] .= ' Some files could not be uploaded: ' . implode(', ', $upload_errors);
                // Note: success remains true, but message becomes a warning. Client-side JS can handle this.
            }

            // You might want to add logic here to actually notify the agent if $notify_agent is 1
            // e.g., send an email or push notification.

        } else {
            $response['message'] = 'No valid action specified for the update.';
        }

        $conn->commit(); // Commit the transaction if all operations were successful

    } catch (Exception $e) {
        $conn->rollBack(); // Rollback on error
        $response['message'] = 'Error processing issue update: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response); // Always output JSON
