<?php
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$message = '';
$message_type = '';

// Handle password change submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "All fields are required";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match";
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters long";
        $message_type = 'error';
    } else {
        $database = new Database();
        $conn = $database->getConnection();

        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            // Update password and reset the password_reset_required flag
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = ?, password_reset_required = 0 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->execute([$new_password_hash, $_SESSION['user_id']]);

            if ($update_stmt->rowCount() > 0) {
                // Update session variable
                $_SESSION['password_reset_required'] = 0;
                
                $message = "Password changed successfully";
                $message_type = 'success';

                // Redirect to dashboard after 2 seconds
                header("Refresh: 2; URL=../dashboard/");
            } else {
                throw new Exception("Failed to update password");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen font-sans flex items-center justify-center">
    <div class="w-full max-w-md p-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-indigo-600 text-xl"></i>
                </div>
                <h1 class="text-xl font-semibold text-gray-900">Change Your Password</h1>
                <?php if ($_SESSION['password_reset_required']): ?>
                    <p class="text-gray-600 mt-2">Your password has been reset by an administrator. Please create a new password to continue.</p>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="current_password" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        <button type="button" onclick="togglePassword('current_password', 'current_eye')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="current_eye" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if ($_SESSION['password_reset_required']): ?>
                        <p class="text-sm text-gray-500 mt-1">Use your phone number as your current password</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="new_password" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required minlength="8">
                        <button type="button" onclick="togglePassword('new_password', 'new_eye')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="new_eye" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required minlength="8">
                        <button type="button" onclick="togglePassword('confirm_password', 'confirm_eye')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="confirm_eye" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-2 px-4 text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition duration-200">
                    Change Password
                </button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
