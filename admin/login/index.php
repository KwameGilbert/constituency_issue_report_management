<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Constituency System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        // SweetAlert Toast Mixin
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Page load animation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';

            setTimeout(() => {
                form.style.transition = 'all 0.6s ease-out';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);

            // Login form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Get form input values
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();

                // Basic validation
                if (!email || !password) {
                    Toast.fire({
                        icon: 'warning',
                        title: 'Please fill in all fields'
                    });
                    return;
                }

                // Button elements
                const loginBtn = document.getElementById('loginBtn');
                const loginIcon = document.getElementById('loginIcon');
                const loadingIcon = document.getElementById('loadingIcon');
                const btnText = document.getElementById('btnText');

                // Show loading state
                loginBtn.disabled = true;
                loginIcon.classList.add('hidden');
                loadingIcon.classList.remove('hidden');
                btnText.textContent = 'Verifying credentials...';

                // Create form data
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);

                // Send login request
                fetch('admin_login_ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button state
                        loginBtn.disabled = false;
                        loginIcon.classList.remove('hidden');
                        loadingIcon.classList.add('hidden');
                        btnText.textContent = 'Access Admin Console';

                        if (data.success) {
                            // Success case
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Admin authentication successful'
                            }).then(() => {
                                // Redirect to admin dashboard
                                window.location.href = '../dashboard/';
                            });
                        } else {
                            // Error case
                            Toast.fire({
                                icon: 'error',
                                title: data.message || 'Authentication failed'
                            });
                        }
                    })
                    .catch(error => {
                        // Network or other error
                        console.error('Login error:', error);

                        // Reset button state
                        loginBtn.disabled = false;
                        loginIcon.classList.remove('hidden');
                        loadingIcon.classList.add('hidden');
                        btnText.textContent = 'Access Admin Console';

                        // Show error message
                        Toast.fire({
                            icon: 'error',
                            title: 'Connection error'
                        });
                    });
            });
        });
    </script>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black font-inter antialiased">
    <div class="min-h-screen flex">
        <!-- Left Side - Admin Branding -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 bg-gradient-to-br from-purple-900 via-red-900 to-gray-900 relative overflow-hidden">
            <!-- Enhanced Background Pattern -->
            <div class="absolute inset-0 opacity-20">
                <svg class="absolute inset-0 h-full w-full" fill="currentColor" viewBox="0 0 100 100">
                    <defs>
                        <pattern id="adminGrid" width="15" height="15" patternUnits="userSpaceOnUse">
                            <path d="M 15 0 L 0 0 0 15" fill="none" stroke="currentColor" stroke-width="1" />
                            <circle cx="7.5" cy="7.5" r="1.5" fill="currentColor" opacity="0.4" />
                            <rect x="6" y="6" width="3" height="3" fill="none" stroke="currentColor" stroke-width="0.5" opacity="0.3" />
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#adminGrid)" />
                </svg>
            </div>

            <!-- Premium Content -->
            <div class="relative z-10 flex flex-col justify-center px-12 py-12 text-white">
                <div class="max-w-md">
                    <!-- Enhanced Admin Icon with Multiple Badges -->
                    <div class="mb-8 relative">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500/20 to-purple-500/20 backdrop-blur-sm rounded-3xl border border-red-300/30 shadow-2xl">
                            <svg class="w-10 h-10 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        
                        <!-- Admin Crown Badge -->
                        <div class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-4 h-4 text-yellow-900" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM17 4a1 1 0 10-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4z"></path>
                            </svg>
                        </div>
                        
                        <!-- Security Shield Badge -->
                        <div class="absolute -bottom-2 -left-2 w-6 h-6 bg-gradient-to-br from-emerald-400 to-cyan-500 rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-3 h-3 text-emerald-900" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Enhanced Admin Text Content -->
                    <h1 class="text-5xl font-bold mb-6 leading-tight">
                        <span class="bg-gradient-to-r from-red-300 via-yellow-300 to-purple-300 bg-clip-text text-transparent">
                            System Command Center
                        </span>
                        <div class="text-lg font-medium text-red-200 mt-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            Administrator Portal
                        </div>
                    </h1>
                    <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                        Complete system control and oversight. Manage all constituents, officers, agents, projects, and strategic initiatives from this centralized administrative hub.
                    </p>

                    <!-- Enhanced Admin-Specific Features -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3 group">
                            <div class="w-2 h-2 bg-red-400 rounded-full group-hover:scale-125 transition-transform"></div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Full system administration & control</span>
                        </div>
                        <div class="flex items-center space-x-3 group">
                            <div class="w-2 h-2 bg-red-400 rounded-full group-hover:scale-125 transition-transform"></div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Officer & agent management</span>
                        </div>
                        <div class="flex items-center space-x-3 group">
                            <div class="w-2 h-2 bg-red-400 rounded-full group-hover:scale-125 transition-transform"></div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Project & employment oversight</span>
                        </div>
                        <div class="flex items-center space-x-3 group">
                            <div class="w-2 h-2 bg-red-400 rounded-full group-hover:scale-125 transition-transform"></div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">Strategic planning & analytics</span>
                        </div>
                        <div class="flex items-center space-x-3 group">
                            <div class="w-2 h-2 bg-red-400 rounded-full group-hover:scale-125 transition-transform"></div>
                            <span class="text-gray-300 group-hover:text-white transition-colors">System security & audit controls</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Premium Decorative Elements -->
            <div class="absolute top-1/4 right-0 w-72 h-72 bg-red-500/20 rounded-full blur-3xl transform translate-x-36"></div>
            <div class="absolute bottom-1/4 left-0 w-56 h-56 bg-purple-500/20 rounded-full blur-3xl transform -translate-x-28"></div>
            <div class="absolute top-1/2 left-1/3 w-40 h-40 bg-yellow-400/10 rounded-full blur-2xl"></div>
            <div class="absolute top-3/4 right-1/4 w-24 h-24 bg-pink-500/15 rounded-full blur-xl"></div>
        </div>

        <!-- Right Side - Enhanced Login Form -->
        <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-8 xl:px-12 bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="mx-auto w-full max-w-md">
                <!-- Mobile Header -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-red-100 to-purple-100 rounded-xl mb-4 shadow-lg">
                        <svg class="w-7 h-7 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 flex justify-center items-center gap-2">
                        Administrator
                        <span class="text-xs px-2 py-0.5 bg-gradient-to-r from-red-100 to-purple-100 text-red-700 rounded-full font-medium border border-red-200">ADMIN</span>
                    </h2>
                    <p class="text-gray-600 mt-2">System Control Center</p>
                </div>

                <!-- Desktop Header -->
                <div class="hidden lg:block mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="bg-gradient-to-r from-red-600 to-purple-600 bg-clip-text text-transparent">
                            Administrator Access
                        </span>
                        <span class="text-xs px-3 py-1 bg-gradient-to-r from-red-100 to-purple-100 text-red-700 rounded-full font-medium border border-red-200 shadow-sm">
                            FULL ACCESS
                        </span>
                    </h2>
                    <p class="mt-2 text-gray-600">Secure login to system administration console</p>
                </div>

                <!-- Enhanced Login Form -->
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 p-8">
                    <form id="loginForm" class="space-y-6">
                        <!-- Admin Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Administrator Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" required
                                    class="block w-full pl-10 pr-4 py-3 border border-red-200 rounded-xl
                                           focus:ring-2 focus:ring-red-500 focus:border-red-500
                                           transition duration-200 ease-in-out sm:text-sm
                                           placeholder-gray-400 bg-red-50/30 focus:bg-white shadow-sm"
                                    placeholder="Enter administrator email">
                            </div>
                        </div>

                        <!-- Admin Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Master Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 12H9v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.586l4.707-4.707A6 6 0 0115 7z"></path>
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required
                                    class="block w-full pl-10 pr-4 py-3 border border-red-200 rounded-xl
                                           focus:ring-2 focus:ring-red-500 focus:border-red-500
                                           transition duration-200 ease-in-out sm:text-sm
                                           placeholder-gray-400 bg-red-50/30 focus:bg-white shadow-sm"
                                    placeholder="Enter master password">
                            </div>
                        </div>

                        <!-- Enhanced Admin Login Button -->
                        <button type="submit" id="loginBtn"
                            class="w-full flex justify-center items-center py-4 px-4 border border-transparent
                                   rounded-xl shadow-xl text-base font-semibold text-white bg-gradient-to-r 
                                   from-red-600 via-purple-600 to-red-600 hover:from-red-700 hover:via-purple-700 hover:to-red-700 
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 
                                   transition duration-200 ease-in-out transform hover:scale-[1.02] hover:shadow-2xl
                                   bg-size-200 bg-pos-0 hover:bg-pos-100 animate-gradient">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loginIcon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <svg class="w-5 h-5 mr-3 animate-spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loadingIcon">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="btnText">Access Admin Console</span>
                        </button>

                        <!-- Security Notice -->
                        <div class="mt-6 p-4 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-amber-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-amber-800">High Security Zone</p>
                                    <p class="text-xs text-amber-700 mt-1">All administrative activities are logged and monitored for security purposes.</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Enhanced Premium Footer -->
                <div class="mt-8 text-center">
                    <div class="flex items-center justify-center space-x-2 mb-3">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Protected Administrator Zone</span>
                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">
                        Authorized personnel only • All access attempts are monitored
                    </p>
                    <p class="text-xs text-gray-500">
                        For security issues or access problems, contact the system security team
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient 3s ease infinite;
        }
        
        .bg-size-200 { background-size: 200% 200%; }
        .bg-pos-0 { background-position: 0% 50%; }
        .hover\:bg-pos-100:hover { background-position: 100% 50%; }
    </style>
</body>

</html>