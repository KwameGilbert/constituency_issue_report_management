<?php
// Set 404 header to ensure proper status code
header("HTTP/1.0 404 Not Found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Constituency Issue Management System</title>

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
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-10 text-center error-container flex flex-col items-center justify-center">
            <div class="text-amber-500 error-code font-bold mb-4">404</div>
            
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">Page Not Found</h1>
            
            <div class="w-24 h-1 bg-amber-500 mb-6"></div>
            
            <p class="text-gray-600 text-lg mb-8 max-w-xl">
                We're sorry, but the page you were looking for couldn't be found. 
                It might have been moved, deleted, or perhaps the URL was mistyped.
            </p>
            
            <div class="flex flex-col md:flex-row gap-4">
                <a href="/" class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-md transition duration-300">
                    <i class="fas fa-home mr-2"></i>Return to Homepage
                </a>
                <a href="/contact/" class="inline-flex items-center justify-center border-2 border-gray-300 hover:border-gray-400 text-gray-700 px-6 py-3 rounded-md transition duration-300">
                    <i class="fas fa-envelope mr-2"></i>Contact Support
                </a>
            </div>
        </div>
        
        <!-- Helpful links section -->
        <div class="mt-10 bg-white rounded-lg shadow-lg p-6 md:p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Popular Pages</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="/" class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-home text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Homepage</span>
                </a>
                <a href="/admin/officer/login/" class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-user-shield text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Officer Login</span>
                </a>
                <a href="/about/" class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-info-circle text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">About Us</span>
                </a>
                <a href="/blog/" class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-newspaper text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">News & Blog</span>
                </a>
                <a href="/events/" class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-calendar-alt text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Events</span>
                </a>
                <a href="/contact/" class="flex items-center p-4 rounded-lg border border-gray-200 hover:border-amber-500 transition duration-300">
                    <i class="fas fa-phone-alt text-amber-500 text-2xl mr-3"></i>
                    <span class="font-medium">Contact Us</span>
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