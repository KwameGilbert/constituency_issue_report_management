<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['pa_id']) && $_SESSION['role'] === 'pa') {
    header("Location: ../dashboard/");
    exit;
}

// Include database connection
require_once '../../../config/db.php';

$message = '';
$message_type = '';
$email_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = 'error';
    } else {
        // Check if PA exists with this email
        $query = "SELECT id, name FROM personal_assistants WHERE email = ? AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal if the account exists for security reasons
            $message = "If an account with that email exists, we've sent password reset instructions.";
            $message_type = 'info';
        } else {
            $pa = $result->fetch_assoc();
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $token_query = "INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())";
            $token_stmt = $conn->prepare($token_query);
            $token_stmt->bind_param("sss", $email, $token, $expires);
            $token_stmt->execute();
            
            if ($token_stmt->affected_rows > 0) {
                // Prepare reset link
                $reset_link = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                
                // Send email (in a production environment, you would use a proper email service)
                $to = $email;
                $subject = "Password Reset Request - Constituency Management System";
                $headers = "From: noreply@localgov.gh\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                $message_body = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <img src='https://localgov.gh/assets/images/coat-of-arms.png' alt='Ghana Coat of Arms' style='max-width: 100px;'>
                        </div>
                        <div style='background-color: #f8f9fa; border-left: 4px solid #006b3f; padding: 15px; margin-bottom: 20px;'>
                            <h2 style='color: #333; margin-top: 0;'>Password Reset Request</h2>
                            <p>Dear {$pa['name']},</p>
                            <p>We received a request to reset your password for the Constituency Management System. If you didn't make this request, you can ignore this email.</p>
                            <p>To reset your password, click the button below:</p>
                            <div style='text-align: center; margin: 25px 0;'>
                                <a href='{$reset_link}' style='background-color: #006b3f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Reset Password</a>
                            </div>
                            <p>Or copy and paste this link into your browser:</p>
                            <p style='background-color: #eeeeee; padding: 10px; font-size: 12px; word-break: break-all;'>{$reset_link}</p>
                            <p>This link will expire in 1 hour for security reasons.</p>
                        </div>
                        <div style='font-size: 12px; color: #666; text-align: center; margin-top: 30px;'>
                            <p>This is an automated message, please do not reply.</p>
                            <p>© " . date('Y') . " Republic of Ghana. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // In development, we'll simulate email sending
                // mail($to, $subject, $message_body, $headers);
                
                $email_sent = true;
                $message = "Password reset instructions have been sent to your email.";
                $message_type = 'success';
            } else {
                $message = "An error occurred. Please try again later.";
                $message_type = 'error';
            }
            
            $token_stmt->close();
        }
        
        $stmt->close();
    }
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
    <title>Forgot Password - Personal Assistant Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <p class="max-w-md text-center px-8">Easily recover your password and regain access to the Constituency Management System</p>

                <div class="mt-12 text-white text-center">
                    <h3 class="text-xl font-semibold mb-2">Password Recovery</h3>
                    <p class="max-w-md px-8">
                        Enter your email address and we'll send you instructions on how to reset your password.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Password Recovery Form -->
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
                    <h1 class="text-2xl font-bold text-gray-800">Password Recovery</h1>
                    <p class="text-gray-600 mt-2">Enter your email to reset your password</p>
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

                <?php if ($email_sent): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                            <i class="fas fa-envelope-open-text text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Check Your Email</h3>
                        <p class="text-gray-600 mb-6">
                            We've sent recovery instructions to your email address. Please follow the link in the email to reset your password.
                        </p>
                        <p class="text-sm text-gray-500 mb-4">
                            The recovery link will expire in 1 hour.
                        </p>
                        <a href="index.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded transition-colors duration-200">
                            Return to Login
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="hidden md:block mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Forgot Your Password?</h2>
                        <p class="text-gray-600 mt-2">Enter your email and we'll send you instructions to reset your password.</p>
                    </div>

                    <form action="<?= $_SERVER['PHP_SELF']; ?>" method="POST" class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" name="email" id="email" required
                                    class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="pa@localgov.gh">
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <i class="fas fa-paper-plane mr-2"></i> Send Reset Instructions
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="index.php" class="text-sm font-medium text-green-600 hover:text-green-800">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Login
                            </a>
                        </div>
                    </form>
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
</body>

</html>