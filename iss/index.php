<?php
// // filepath: c:\xampp\htdocs\swma\admin\index.php
// session_start();

// // Check if already logged in and redirect to appropriate dashboard
// if (isset($_SESSION['admin_id'])) {
//     if ($_SESSION['role'] === 'agent') {
//         // New role: Agent (replaces Field Officer)
//         header("Location: agent/dashboard/");
//     } elseif ($_SESSION['role'] === 'officer') {
//         // New role: Officer (replaces Personal Assistant)
//         header("Location: officer/dashboard/");
//     } elseif ($_SESSION['role'] === 'admin') {
//         // New role: Admin (replaces super_admin/Supervisor)
//         header("Location: admin/dashboard/");
//     }
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Sefwi Wiawso Constituency</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body {
            background-color: #f9fafb;
            background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23f1f5f9" fill-opacity="0.6" fill-rule="evenodd"/%3E%3C/svg%3E');
        }
        .role-card {
            transition: all 0.3s ease;
        }
        .role-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .role-icon {
            transition: all 0.3s ease;
        }
        .role-card:hover .role-icon {
            transform: scale(1.1);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <img src="../assets/images/coat-of-arms.png" alt="Ghana Coat of Arms" class="h-10 w-auto">
                <div class="ml-3">
                    <h1 class="text-xl font-bold text-gray-900">Sefwi Wiawso</h1>
                    <p class="text-sm text-gray-600">Municipal Assembly</p>
                </div>
            </div>
            <a href="/" class="text-amber-600 hover:text-amber-700 flex items-center">
                <span class="mr-1">Return to Website</span>
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl w-full space-y-8">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Admin Portal</h2>
                <p class="mt-2 text-sm text-gray-600">Select your role to continue to the login page</p>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <a href="agent/login/" class="role-card bg-white rounded-xl shadow-md overflow-hidden hover:border-slate-700 border-2 border-transparent">
                    <div class="p-6 text-center">
                        <div class="mx-auto h-20 w-20 rounded-full bg-slate-300 flex items-center justify-center mb-4">
                            <i class="fas fa-user-tag text-slate-900 text-3xl role-icon"></i>
                        </div>
                        <h3 class="text-xl font-medium text-slate-900 mb-2">Agent</h3>
                        <p class="text-gray-500 text-sm mb-4">Manage field activities and report issues from the community</p>
                        <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-slate-900 hover:bg-slate-800">
                            Login as Agent
                        </div>
                    </div>
                </a>

                <a href="officer/login/" class="role-card bg-white rounded-xl shadow-md overflow-hidden hover:border-blue-500 border-2 border-transparent">
                    <div class="p-6 text-center">
                        <div class="mx-auto h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                            <i class="fas fa-user-cog text-blue-600 text-3xl role-icon"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">Officer</h3>
                        <p class="text-gray-500 text-sm mb-4">Coordinate resources, manage communications, and support staff</p>
                        <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Login as Officer
                        </div>
                    </div>
                </a>

                <a href="admin/login/" class="role-card bg-white rounded-xl shadow-md overflow-hidden hover:border-green-500 border-2 border-transparent">
                    <div class="p-6 text-center">
                        <div class="mx-auto h-20 w-20 rounded-full bg-green-100 flex items-center justify-center mb-4">
                            <i class="fas fa-user-shield text-red-900 text-3xl role-icon"></i>
                        </div>
                        <h3 class="text-xl font-medium text-red-900 mb-2">Admin</h3>
                        <p class="text-red-500 text-sm mb-4">Oversee all operations, manage users, and handle system settings</p>
                        <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            Login as Admin
                        </div>
                    </div>
                </a>
            </div>

            <div class="mt-10 text-center">
                <p class="text-xs text-gray-500">Need help? Contact the system administrator at <a href="mailto:admin@sefwiwiawso.gov.gh" class="text-amber-600 hover:text-amber-700">admin@sefwiwiawso.gov.gh</a></p>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    &copy; <?= date('Y') ?> Hon. Kofi Afful Benteh. All rights reserved.
                </div>
                <div class="text-sm text-gray-500">
                    <span class="mr-2">Version 1.0</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Add any additional JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            const roleCards = document.querySelectorAll('.role-card');
            
            roleCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    // Additional hover effects could be added here
                });
            });
        });
    </script>
</body>
</html>