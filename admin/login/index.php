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

<body class="h-screen overflow-hidden bg-gray-100 font-inter antialiased">
    <div class="h-screen flex">
        <!-- Left Side - Clean Admin Branding -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 bg-red-900 relative overflow-hidden">
            <!-- Simple Admin Pattern -->
            <div class="absolute inset-0 opacity-5">
                <svg class="absolute inset-0 h-full w-full" fill="currentColor" viewBox="0 0 100 100">
                    <defs>
                        <pattern id="adminGrid" width="24" height="24" patternUnits="userSpaceOnUse">
                            <path d="M 24 0 L 0 0 0 24" fill="none" stroke="currentColor" stroke-width="0.3" />
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#adminGrid)" />
                </svg>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center px-8 py-8 text-white h-full">
                <div class="max-w-md">
                    <!-- Clean Admin Icon -->
                    <div class="mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg">
                            <svg class="w-8 h-8 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Clean Admin Text Content -->
                    <h1 class="text-4xl font-bold mb-4 leading-tight text-white">
                        Administrator
                        <div class="text-base font-medium text-red-200 mt-2">
                            System Control Portal
                        </div>
                    </h1>
                    <p class="text-lg text-red-100 mb-8 leading-relaxed">
                        Comprehensive system administration with full control over constituency operations and user management.
                    </p>

                    <!-- Clean Admin Features -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            <span class="text-red-100">Complete system administration</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            <span class="text-red-100">User & role management</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            <span class="text-red-100">Project oversight & control</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            <span class="text-red-100">Security & audit management</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Clean Login Form -->
        <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-8 xl:px-12 bg-gray-50 h-full overflow-y-auto">
            <div class="mx-auto w-full max-w-md py-4">
                <!-- Mobile Header -->
                <div class="lg:hidden text-center mb-4">
                    <div class="inline-flex items-center justify-center w-10 h-10 bg-red-900 rounded-xl mb-2 shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 flex justify-center items-center gap-2">
                        Administrator
                        <span class="text-xs px-1.5 py-0.5 bg-red-900 text-white rounded-full font-medium">ADMIN</span>
                    </h2>
                    <p class="text-gray-600 mt-1 text-xs">System Control Portal</p>
                </div>

                <!-- Desktop Header -->
                <div class="hidden lg:block mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        Administrator Access
                        <span class="text-xs px-2 py-0.5 bg-red-900 text-white rounded-full font-medium">
                            FULL ACCESS
                        </span>
                    </h2>
                    <p class="mt-1 text-gray-600 text-sm">Secure access to system administration console</p>
                </div>

                <!-- Clean Login Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <form id="loginForm" class="space-y-4">
                        <!-- Admin Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-800 mb-1.5">
                                Administrator Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" required
                                    class="block w-full pl-9 pr-4 py-2 border border-red-200 rounded-lg
                                           focus:ring-2 focus:ring-red-900 focus:border-red-900
                                           transition duration-200 ease-in-out text-sm
                                           placeholder-gray-400 bg-white"
                                    placeholder="Enter administrator email">
                            </div>
                        </div>

                        <!-- Admin Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-800 mb-1.5">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-red-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required
                                    class="block w-full pl-9 pr-4 py-2 border border-red-200 rounded-lg
                                           focus:ring-2 focus:ring-red-900 focus:border-red-900
                                           transition duration-200 ease-in-out text-sm
                                           placeholder-gray-400 bg-white"
                                    placeholder="Enter password">
                            </div>
                        </div>

                        <!-- Clean Admin Login Button -->
                        <button type="submit" id="loginBtn"
                            class="w-full flex justify-center items-center py-2 px-4 border border-transparent
                                   rounded-lg shadow-sm text-sm font-semibold text-white bg-red-900 
                                   hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-900 
                                   transition duration-200 ease-in-out">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loginIcon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <svg class="w-4 h-4 mr-2 animate-spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loadingIcon">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="btnText">Access Admin Console</span>
                        </button>

                        <!-- Security Notice -->
                        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-4 h-4 text-red-900 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <p class="text-xs font-semibold text-red-900">High Security Zone</p>
                                    <p class="text-xs text-red-800 mt-0.5">All activities are logged and monitored.</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Clean Footer -->
                <div class="mt-4 text-center">
                    <div class="flex items-center justify-center space-x-1.5 mb-2">
                        <svg class="w-4 h-4 text-red-900" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-xs font-semibold text-gray-800">Protected Administrator Zone</span>
                    </div>
                    <p class="text-xs text-gray-600">
                        Authorized personnel only • All access monitored
                    </p>
                </div>
            </div>
        </div>
    </div>


</body>

</html>