<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['pa_id']) && $_SESSION['role'] === 'pa') {
    header("Location: ../dashboard/");
    exit;
}

// Include database connection
require_once '../../../config/db.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$token_valid = false;
$reset_success = false;

// Verify token is provided
if (empty($token)) {
    $message = "Invalid or missing password reset token.";
    $message_type = 'error';
} else {
    // Check if token exists and is not expired
    $query = "SELECT email, expires_at FROM password_resets 
              WHERE token = ? AND used = 0 AND expires_at > NOW() 
              ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "This password reset link is invalid or has expired.";
        $message_type = 'error';
    } else {
        $reset_data = $result->fetch_assoc();
        $email = $reset_data['email'];
        $token_valid = true;
        
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate passwords
            if (empty($password) || empty($confirm_password)) {
                $message = "Please enter and confirm your new password.";
                $message_type = 'error';
            } elseif (strlen($password) < 8) {
                $message = "Password must be at least 8 characters long.";
                $message_type = 'error';
            } elseif ($password !== $confirm_password) {
                $message = "Passwords do not match.";
                $message_type = 'error';
            } else {
                // Hash the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update the user's password
                $update_query = "UPDATE personal_assistants SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ss", $hashed_password, $email);
                $update_result = $update_stmt->execute();
                
                if ($update_result) {
                    // Mark token as used
                    $mark_used_query = "UPDATE password_resets SET used = 1 WHERE token = ?";
                    $mark_used_stmt = $conn->prepare($mark_used_query);
                    $mark_used_stmt->bind_param("s", $token);
                    $mark_used_stmt->execute();
                    
                    $reset_success = true;
                    $message = "Your password has been reset successfully. You can now log in with your new password.";
                    $message_type = 'success';
                    
                    $mark_used_stmt->close();
                } else {
                    $message = "An error occurred while resetting your password. Please try again.";
                    $message_type = 'error';
                }
                
                $update_stmt->close();
            }
        }
    }
    
    $stmt->close();
}

// Set message styling
if (!empty($message)) {
    $message_bg_color = match($message_type) {
        'success' => 'bg-green-100 border-green-500 text-green-700',
        'error' => 'bg-red-100 border-red-500 text-red-700',
        'info' => 'bg-blue-100 border-blue-500 text-blue-700',
        default => 'bg-gray-100 border-gray-500 text-gray-700'
    };
    $message_icon = match($message_type) {
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'info' => 'fa-info-circle',
        default => 'fa-bell'
    };
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Personal Assistant Portal</title>
     <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .ghana-colors {
        background: linear-gradient(to bottom, #ce1126 33%, #fcd116 33%, #fcd116 66%, #006b3f 66%);
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Left Side - Ghana Colors and Coat of Arms (hidden on mobile) -->
        <div class="hidden md:flex md:w-1/2 flex-col ghana-colors justify-center items-center relative">
            <div class="absolute inset-0 bg-black opacity-20"></div>

            <div class="relative z-10 flex flex-col items-center text-white">
                <!-- Ghana Coat of Arms -->
                <div class="mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="120" height="120">
                        <!-- Simplified Ghana Coat of Arms -->
                        <circle cx="100" cy="100" r="95" fill="#fcd116" stroke="#000" stroke-width="2" />
                        <path d="M70,50 L130,50 L140,150 L100,170 L60,150 Z" fill="#fff" stroke="#000"
                            stroke-width="2" />
                        <path d="M100,50 L100,170 M70,110 L130,110" stroke="#000" stroke-width="3" />
                        <path d="M70,50 L100,50 L100,110 L70,110 Z" fill="#006b3f" />
                        <path d="M100,50 L130,50 L130,110 L100,110 Z" fill="#ce1126" />
                        <path d="M70,110 L100,110 L100,170 L70,110 Z" fill="#000" />
                        <path d="M100,110 L130,110 L130,150 L100,170 Z" fill="#fcd116" />
                        <path d="M100,85 L105,100 L120,100 L110,110 L115,125 L100,115 L85,125 L90,110 L80,100 L95,100 Z"
                            fill="#000" />
                    </svg>
                </div>

                <h1 class="text-3xl font-bold mb-4 text-center">Republic of Ghana</h1>
                <h2 class="text-xl font-semibold mb-2 text-center">Personal Assistant Portal</h2>
                <p class="max-w-md text-center px-8">Create a new secure password for your account</p>

                <div class="mt-12 text-white text-center">
                    <h3 class="text-xl font-semibold mb-2">Password Reset</h3>
                    <p class="max-w-md px-8">
                        Enter your new password in the form to complete the password reset process.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Password Reset Form -->
        <div class="w-full md:w-1/2 flex items-center justify-center p-6">
            <div class="max-w-md w-full">
                <!-- Mobile Only Header -->
                <div class="md:hidden text-center mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="80" height="80" class="mx-auto mb-4">
                        <!-- Simplified Ghana Coat of Arms -->
                        <circle cx="100" cy="100" r="95" fill="#fcd116" stroke="#000" stroke-width="2" />
                        <path d="M70,50 L130,50 L140,150 L100,170 L60,150 Z" fill="#fff" stroke="#000"
                            stroke-width="2" />
                        <path d="M100,50 L100,170 M70,110 L130,110" stroke="#000" stroke-width="3" />
                        <path d="M70,50 L100,50 L100,110 L70,110 Z" fill="#006b3f" />
                        <path d="M100,50 L130,50 L130,110 L100,110 Z" fill="#ce1126" />
                        <path d="M70,110 L100,110 L100,170 L70,110 Z" fill="#000" />
                        <path d="M100,110 L130,110 L130,150 L100,170 Z" fill="#fcd116" />
                        <path d="M100,85 L105,100 L120,100 L110,110 L115,125 L100,115 L85,125 L90,110 L80,100 L95,100 Z"
                            fill="#000" />
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-800">Reset Your Password</h1>
                    <p class="text-gray-600 mt-2">Enter a new secure password for your account</p>
                </div>

                <?php if (!empty($message)): ?>
                <div class="mb-6 border-l-4 <?= $message_bg_color ?> p-4 rounded-md" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas <?= $message_icon ?> text-2xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><?= $message ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($reset_success): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                            <i class="fas fa-check-circle text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Password Reset Complete</h3>
                        <p class="text-gray-600 mb-6">
                            Your password has been reset successfully. You can now log in with your new password.
                        </p>
                        <a href="index.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition-colors duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Go to Login
                        </a>
                    </div>
                </div>
                <?php elseif ($token_valid): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="hidden md:block mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Reset Your Password</h2>
                        <p class="text-gray-600 mt-2">Enter a new secure password for your account below.</p>
                    </div>

                    <form action="<?= $_SERVER['PHP_SELF']; ?>?token=<?= htmlspecialchars($token) ?>" method="POST" class="space-y-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" name="password" id="password" required
                                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Enter new password" minlength="8">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters long</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" name="confirm_password" id="confirm_password" required
                                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Confirm new password" minlength="8">
                            </div>
                        </div>

                        <div>
                            <button type="submit" name="reset_password" value="1"
                                class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <i class="fas fa-key mr-2"></i> Reset Password
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 text-red-500 mb-4">
                            <i class="fas fa-exclamation-triangle text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Invalid Reset Link</h3>
                        <p class="text-gray-600 mb-6">
                            This password reset link is invalid or has expired. Please request a new password reset link.
                        </p>
                        <a href="forgot-password.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition-colors duration-200">
                            <i class="fas fa-redo mr-2"></i> Request New Reset Link
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-8 text-center text-gray-600 text-sm">
                    <p>Need assistance? Contact support:</p>
                    <p class="font-medium text-green-600">support@localgov.gh | 030 222 3344</p>
                    <p class="mt-6">&copy; <?php echo date('Y'); ?> Republic of Ghana. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Password strength and matching check (client-side validation)
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (passwordInput && confirmPasswordInput) {
            // Check password match when confirm field changes
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity("Passwords don't match");
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            });
            
            // Clear custom validity when password field changes
            passwordInput.addEventListener('input', function() {
                if (confirmPasswordInput.value) {
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
            });
        }
    });
    </script>
</body>

</html>