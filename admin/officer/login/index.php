<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['officer_id']) && $_SESSION['role'] === 'field_officer') {
    header("Location: ../dashboard/");
    exit;
}

// Include database connection and email templates
require_once '../../../config/db.php';
require_once '../../../email-services/mail.php';
require_once '../../../email-services/email-templates.php';

$error_message = '';
$error_type = ''; // For styling different types of errors

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Validation
    if (empty($email) && empty($password)) {
        $error_message = "Please enter your email and password.";
        $error_type = 'validation';
    } elseif (empty($email)) {
        $error_message = "Please enter your email address.";
        $error_type = 'validation';
    } elseif (empty($password)) {
        $error_message = "Please enter your password.";
        $error_type = 'validation';
    } else {
        // Check if officer exists
        $query = "SELECT id, name, email, password, status FROM field_officers 
                 WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = "No account found with this email address.";
            $error_type = 'not_found';
        } else {
            $officer = $result->fetch_assoc();
            
            // Check account status first
            if ($officer['status'] === 'inactive') {
                $error_message = "Your account is currently inactive. Please contact the administrator.";
                $error_type = 'inactive';
            } elseif ($officer['status'] === 'suspended') {
                $error_message = "Your account has been suspended. Please contact the administrator.";
                $error_type = 'suspended';
            } elseif (!password_verify($password, $officer['password'])) {
                $error_message = "Incorrect password. Please try again.";
                $error_type = 'invalid_password';
                
                // Send failed login email notification
                sendSecurityNotificationEmail(
                    $officer['email'],
                    $officer['name'],
                    'failed_login',
                    ['reason' => 'Incorrect password entered']
                );
                
            } else {
                // Set session variables
                $_SESSION['officer_id'] = $officer['id'];
                $_SESSION['officer_name'] = $officer['name'];
                $_SESSION['email'] = $officer['email'];
                $_SESSION['role'] = 'field_officer';
                
                // Update last login timestamp
                $update_query = "UPDATE field_officers SET last_login = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $officer['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Store the last login time for display
                $_SESSION['last_login'] = date('Y-m-d H:i:s');
                
                // Send successful login notification email
                // We do this in the background to avoid delaying the user's login
                sendSecurityNotificationEmail(
                    $officer['email'],
                    $officer['name'],
                    'success_login'
                );
                
                // Redirect to officer dashboard
                header("Location: ../dashboard/");
                exit();
            }
        }
        $stmt->close();
    }
    $conn->close();
}

// Set error styling based on error type
if (!empty($error_message)) {
    $error_bg_color = match($error_type) {
        'validation' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
        'not_found' => 'bg-red-100 border-red-500 text-red-700',
        'inactive', 'suspended' => 'bg-orange-100 border-orange-500 text-orange-700',
        'invalid_password' => 'bg-red-100 border-red-500 text-red-700',
        default => 'bg-red-100 border-red-500 text-red-700'
    };
    $error_icon = match($error_type) {
        'validation' => 'fa-exclamation-triangle',
        'not_found' => 'fa-user-times',
        'inactive', 'suspended' => 'fa-lock',
        'invalid_password' => 'fa-times-circle',
        default => 'fa-exclamation-circle'
    };
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Officer Login - Constituency Issue Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .ghana-colors {
        background: linear-gradient(to bottom, #ce1126 33%, #fcd116 33%, #fcd116 66%, #006b3f 66%);
    }

    .ghana-flag-border {
        border-top: 4px solid #ce1126;
        border-right: 4px solid #fcd116;
        border-bottom: 4px solid #006b3f;
        border-left: 4px solid #000;
    }

    @keyframes pulse-subtle {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(0, 107, 63, 0.4);
        }

        50% {
            box-shadow: 0 0 15px 0 rgba(0, 107, 63, 0.7);
        }
    }

    .pulse-animation {
        animation: pulse-subtle 3s infinite;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen">
        <!-- Left Side - Ghana Colors and Coat of Arms -->
        <div class="hidden md:flex md:w-1/2 flex-col ghana-colors justify-center items-center relative">
            <div class="absolute inset-0 bg-black opacity-20"></div>

            <div class="relative z-10 flex flex-col items-center text-white">
                <!-- Ghana Coat of Arms -->
                <div class="mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="150" height="150">
                        <!-- Simplified Ghana Coat of Arms -->
                        <circle cx="100" cy="100" r="95" fill="#fcd116" stroke="#000" stroke-width="2" />

                        <!-- Shield -->
                        <path d="M70,50 L130,50 L140,150 L100,170 L60,150 Z" fill="#fff" stroke="#000"
                            stroke-width="2" />

                        <!-- Cross on Shield -->
                        <path d="M100,50 L100,170 M70,110 L130,110" stroke="#000" stroke-width="3" />

                        <!-- Quarters -->
                        <path d="M70,50 L100,50 L100,110 L70,110 Z" fill="#006b3f" />
                        <path d="M100,50 L130,50 L130,110 L100,110 Z" fill="#ce1126" />
                        <path d="M70,110 L100,110 L100,170 L70,110 Z" fill="#000" />
                        <path d="M100,110 L130,110 L130,150 L100,170 Z" fill="#fcd116" />

                        <!-- Black Star -->
                        <path d="M100,85 L105,100 L120,100 L110,110 L115,125 L100,115 L85,125 L90,110 L80,100 L95,100 Z"
                            fill="#000" />
                    </svg>
                </div>

                <h1 class="text-4xl font-bold mb-4 text-center">Republic of Ghana</h1>
                <h2 class="text-2xl font-semibold mb-6 text-center">Constituency Field Officers Portal</h2>
                <p class="max-w-md text-center px-8 text-lg">Documenting and resolving community issues for better
                    governance</p>
            </div>

            <!-- Ghana Flag Strip at Bottom -->
            <div class="absolute bottom-0 left-0 right-0 h-6 flex">
                <div class="flex-1 bg-red-600"></div>
                <div class="flex-1 bg-yellow-400"></div>
                <div class="flex-1 bg-green-700"></div>
            </div>

            <!-- Black Star -->
            <div class="absolute bottom-12 right-12">
                <svg width="60" height="60" viewBox="0 0 50 50">
                    <path d="M25,2 L31,18 L48,18 L34,28 L40,45 L25,35 L10,45 L16,28 L2,18 L19,18 Z" fill="#000" />
                </svg>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full md:w-1/2 flex items-center justify-center">
            <div class="max-w-md w-full p-8">
                <div class="text-center mb-10">
                    <!-- Mobile Only Coat of Arms -->
                    <div class="md:hidden mb-6 flex justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="80" height="80">
                            <!-- Simplified Coat of Arms (same as above but smaller) -->
                            <circle cx="100" cy="100" r="95" fill="#fcd116" stroke="#000" stroke-width="2" />
                            <path d="M70,50 L130,50 L140,150 L100,170 L60,150 Z" fill="#fff" stroke="#000"
                                stroke-width="2" />
                            <path d="M100,50 L100,170 M70,110 L130,110" stroke="#000" stroke-width="3" />
                            <path d="M70,50 L100,50 L100,110 L70,110 Z" fill="#006b3f" />
                            <path d="M100,50 L130,50 L130,110 L100,110 Z" fill="#ce1126" />
                            <path d="M70,110 L100,110 L100,170 L70,110 Z" fill="#000" />
                            <path d="M100,110 L130,110 L130,150 L100,170 Z" fill="#fcd116" />
                            <path
                                d="M100,85 L105,100 L120,100 L110,110 L115,125 L100,115 L85,125 L90,110 L80,100 L95,100 Z"
                                fill="#000" />
                        </svg>
                    </div>

                    <h2 class="text-3xl font-bold text-gray-800">Field Officer Login</h2>
                    <p class="text-gray-600 mt-2">Access your constituency issue management dashboard</p>
                </div>

                <?php if (!empty($error_message)): ?>
                <div class="mb-6 border-l-4 <?= $error_bg_color ?> p-4 rounded-md" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas <?= $error_icon ?> text-2xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium"><?= match($error_type) {
                                'validation' => 'Form Validation Error',
                                'not_found' => 'Account Not Found',
                                'inactive' => 'Account Inactive',
                                'suspended' => 'Account Suspended',
                                'invalid_password' => 'Authentication Failed',
                                default => 'Error'
                            } ?></h3>
                            <p class="mt-1 text-sm"><?= $error_message ?></p>
                            <?php if (in_array($error_type, ['inactive', 'suspended'])): ?>
                            <p class="mt-2 text-sm">
                                <a href="mailto:support@localgov.gh" class="underline hover:text-gray-900">Contact
                                    support for assistance</a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form action="<?= $_SERVER['PHP_SELF']; ?>" method="POST" class="space-y-6">
                    <div class="ghana-flag-border rounded-lg pulse-animation">
                        <div class="bg-white p-6 rounded-lg">
                            <!-- Email Field -->
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Officer
                                    Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="email" id="email" required
                                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder="officer@localgov.gh">
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-2">
                                <label for="password"
                                    class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="password" id="password" required
                                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder="••••••••">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <button type="button" id="togglePassword"
                                            class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="remember" id="remember"
                                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <label for="remember" class="ml-2 block text-sm text-gray-700">
                                        Remember me
                                    </label>
                                </div>
                                <a href="forgot-password.php"
                                    class="text-sm font-medium text-green-600 hover:text-green-500">
                                    Forgot your password?
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-sign-in-alt mr-2"></i> Sign in to Officer Portal
                        </button>
                    </div>
                </form>

                <!-- Support Contact -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">Having trouble logging in? Contact IT support:</p>
                    <p class="text-sm font-medium text-green-600">support@localgov.gh | 030 222 3344</p>
                </div>

                <!-- Footer with Government Info -->
                <div class="mt-8 text-center text-gray-600 text-sm">
                    <p>Government of Ghana</p>
                    <p class="mt-1">Ministry of Local Government and Rural Development</p>
                    <p class="mt-4 text-xs">&copy; <?php echo date('Y'); ?> Republic of Ghana. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    </script>
</body>

</html>