<?php
// filepath: c:\xampp\htdocs\swma\403.php
// Set 403 header to ensure proper status code
header("HTTP/1.0 403 Forbidden");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - Constituency Issue Management System</title>

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
    .error-container {
        min-height: calc(100vh - 200px);
    }

    .error-code {
        font-size: 120px;
        line-height: 1;
    }

    @media (max-width: 640px) {
        .error-code {
            font-size: 80px;
        }
    }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/" class="flex items-center">
                <img src="/assets/images/coat-of-arms.png" alt="Logo" class="h-12 mr-3">
                <div>
                    <div class="text-xl font-bold text-amber-700">Constituency</div>
                    <div class="text-sm text-gray-600">Issue Management System</div>
                </div>
            </a>
            <a href="/" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-md transition duration-300">
                <i class="fas fa-home mr-2"></i>Return Home
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div
            class="bg-white rounded-lg shadow-lg p-6 md:p-10 text-center error-container flex flex-col items-center justify-center">
            <div class="text-red-500 error-code font-bold mb-4">403</div>

            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">Access Forbidden</h1>

            <div class="w-24 h-1 bg-red-500 mb-6"></div>

            <p class="text-gray-600 text-lg mb-8 max-w-xl">
                You don't have permission to access this page or resource.
                This might be due to insufficient privileges or restricted access to this section.
            </p>

            <div class="flex flex-col md:flex-row gap-4">
                <a href="/"
                    class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-md transition duration-300">
                    <i class="fas fa-home mr-2"></i>Return to Homepage
                </a>
                <a href="/contact/"
                    class="inline-flex items-center justify-center border-2 border-gray-300 hover:border-gray-400 text-gray-700 px-6 py-3 rounded-md transition duration-300">
                    <i class="fas fa-envelope mr-2"></i>Contact Support
                </a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="/login/"
                    class="inline-flex items-center justify-center bg-gray-700 hover:bg-gray-800 text-white px-6 py-3 rounded-md transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>Log In
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security information section -->
        <div class="mt-10 bg-white rounded-lg shadow-lg p-6 md:p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Access Information</h2>
            <div class="p-4 mb-6 bg-red-50 border-l-4 border-red-500 text-gray-700">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm">
                            If you believe you should have access to this page, please ensure you are properly logged in
                            with the correct account. For assistance, please contact your system administrator or
                            support team.
                        </p>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 mb-3">Common Reasons for Access Restriction:</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-600 mb-6">
                <li>Your account lacks the necessary permissions</li>
                <li>Session has expired and requires re-authentication</li>
                <li>You're trying to access a resource restricted to specific user roles</li>
                <li>The system is currently under maintenance</li>
            </ul>

            <h3 class="text-lg font-semibold text-gray-800 mb-3">What You Can Do:</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="/login/"
                    class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-sign-in-alt text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Log In Again</span>
                </a>
                <a href="/"
                    class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-home text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Return to Home</span>
                </a>
                <a href="/contact/"
                    class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-headset text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Contact Support</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <img src="/assets/images/coat-of-arms.png" alt="Logo" class="h-12 mx-auto mb-4">
                <p class="mb-4">Constituency Issue Management System</p>
                <p class="text-gray-400 text-sm">&copy; <?php echo date('Y'); ?> All Rights Reserved</p>
            </div>
        </div>
    </footer>

</body>

</html>