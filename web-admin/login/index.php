<?php
// Start session and include necessary files
session_start();
require_once '../../config/db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard/');
    exit;
}

$error = '';

// Process login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (!$email || !$password) {
        $error = "All fields are required";
    } else {
        try {
            // Updated to use admins table
            $stmt = $conn->prepare("SELECT id, password_hash, username, first_name, role FROM admins WHERE email=? AND status='active' AND id > 0");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Successful login
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['first_name'];
                $_SESSION['admin_last_activity'] = time();
                
                // Update last login timestamp
                $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param('i', $admin['id']);
                $updateStmt->execute();
                
                // Log activity if table exists
                try {
                    $logStmt = $conn->prepare("INSERT INTO admins_activity_log (admin_id, action, details, ip_address, user_agent) VALUES (?, 'login', 'Admin logged in successfully', ?, ?)");
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    $logStmt->bind_param('iss', $admin['id'], $ip, $userAgent);
                    $logStmt->execute();
                } catch (Exception $logError) {
                    // Silently fail if logging fails
                    error_log("Login log error: " . $logError->getMessage());
                }
                
                // Redirect to dashboard
                header('Location: ../dashboard/');
                exit;
            } else {
                // Failed login
                $error = "Invalid email or password";
                
                // Log failed attempt if table exists - use system ID (0) for failed attempts
                try {
                    $systemId = 0; // Reference to system account
                    $details = "Failed login attempt with email: " . $email;
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    
                    $failedLogStmt = $conn->prepare("INSERT INTO admins_activity_log (admin_id, action, details, ip_address, user_agent) VALUES (?, 'login_failed', ?, ?, ?)");
                    $failedLogStmt->bind_param('isss', $systemId, $details, $ip, $userAgent);
                    $failedLogStmt->execute();
                } catch (Exception $logError) {
                    // Silently fail if logging fails
                    error_log("Failed login log error: " . $logError->getMessage());
                }
            }
        } catch (Exception $e) {
            // Log error
            error_log("Login error: " . $e->getMessage());
            $error = "System error. Please try again later.";
        }
    }
}

// Get any messages from session
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin login page for content management">
    <title>Constituency Issue Management | Admin Login</title>
    <link rel="icon" href="../../assets/images/favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
    <div class="max-w-md w-full px-6 py-8">
        <div class="mb-8 text-center">
            <img src="../../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4"
                onerror="this.style.display='none'">
            <h1 class="text-2xl font-bold text-gray-800">Constituency Issue Report Management</h1>
            <p class="text-gray-600">Manage blog posts, events, carousels, and more</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-xl font-semibold mb-6 text-center">Admin Login</h2>

            <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 relative"
                role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($successMessage) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="mb-6">
                    <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                    <input id="email" name="email" type="email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="admin@example.com" required
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                        autocomplete="email">
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-gray-700 text-sm font-medium">Password</label>
                        <a href="reset-password.php" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                    </div>
                    <input id="password" name="password" type="password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div class="flex items-center mb-6">
                    <input id="remember" name="remember" type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Sign In
                </button>
            </form>
        </div>

        <div class="mt-8 text-center text-sm text-gray-600">
            <p>Having trouble? <a href="../../contact.php" class="text-blue-600 hover:underline">Contact support</a></p>
            <p class="mt-1">Return to <a href="../../index.php" class="text-blue-600 hover:underline">website</a></p>
        </div>
    </div>

    <footer class="w-full text-center p-4 text-sm text-gray-600">
        &copy; <?= date('Y') ?> Constituency Issue Management. All rights reserved.
    </footer>

    <script>
    // Focus on email field on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
    });
    </script>
</body>

</html>