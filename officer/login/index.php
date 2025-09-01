<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Login - Constituency System</title>
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
                btnText.textContent = 'Authenticating...';

                // Create form data
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);

                // Send login request
                fetch('officer_login_ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button state
                        loginBtn.disabled = false;
                        loginIcon.classList.remove('hidden');
                        loadingIcon.classList.add('hidden');
                        btnText.textContent = 'Access Officer Dashboard';

                        if (data.success) {
                            // Success case
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Authentication successful'
                            }).then(() => {
                                // Redirect to officer dashboard
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
                        btnText.textContent = 'Access Officer Dashboard';

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

<body class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 font-inter antialiased">
    <div class="min-h-screen flex">
        <!-- Left Side - Image/Branding -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-900 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-15">
                <svg class="absolute inset-0 h-full w-full" fill="currentColor" viewBox="0 0 100 100">
                    <defs>
                        <pattern id="officerGrid" width="12" height="12" patternUnits="userSpaceOnUse">
                            <path d="M 12 0 L 0 0 0 12" fill="none" stroke="currentColor" stroke-width="0.8" />
                            <circle cx="6" cy="6" r="1" fill="currentColor" opacity="0.3" />
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#officerGrid)" />
                </svg>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center px-12 py-12 text-white">
                <div class="max-w-md">
                    <!-- Icon with Badge -->
                    <div class="mb-8 relative">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <!-- Officer Badge -->
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-amber-400 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-amber-900" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Text Content -->
                    <h1 class="text-4xl font-bold mb-6 leading-tight">
                        Officer Access Portal
                        <div class="text-lg font-medium text-blue-200 mt-2">Constituency Management System</div>
                    </h1>
                    <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                        Comprehensive oversight and management tools for reviewing, approving, and coordinating constituency issues across all levels.
                    </p>

                    <!-- Enhanced Features for Officers -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-amber-400 rounded-full"></div>
                            <span class="text-gray-300">Issue review & approval workflow</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-amber-400 rounded-full"></div>
                            <span class="text-gray-300">Advanced analytics & reporting</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-amber-400 rounded-full"></div>
                            <span class="text-gray-300">Multi-level escalation management</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-amber-400 rounded-full"></div>
                            <span class="text-gray-300">Agent oversight & coordination</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Decorative Elements -->
            <div class="absolute top-1/4 right-0 w-64 h-64 bg-amber-400/20 rounded-full blur-3xl transform translate-x-32"></div>
            <div class="absolute bottom-1/4 left-0 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl transform -translate-x-24"></div>
            <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-blue-400/10 rounded-full blur-2xl"></div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-8 xl:px-12">
            <div class="mx-auto w-full max-w-md">
                <!-- Mobile Header -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-100 rounded-xl mb-4">
                        <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11c1.105 0 2-.895 2-2s-.895-2-2-2a2 2 0 00-2 2c0 1.105.895 2 2 2zm0 2c-1.657 0-3 .895-3 2v1h6v-1c0-1.105-1.343-2-3-2z">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 flex justify-center items-center gap-2">
                        Officer Login
                        <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full font-medium">Officer</span>
                    </h2>
                    <p class="text-gray-600 mt-2">Constituency Management System</p>
                </div>

                <!-- Desktop Header -->
                <div class="hidden lg:block mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                        Welcome Officer
                        <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full font-medium">Officer Access</span>
                    </h2>
                    <p class="mt-2 text-gray-600">Please sign in to your officer account</p>
                </div>


                <!-- Login Form -->
                <form id="loginForm" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Officer Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="email" id="email" name="email" required
                                class="block w-full pl-10 pr-4 py-3 border border-blue-200 rounded-xl
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                       transition duration-200 ease-in-out sm:text-sm
                                       placeholder-gray-400 bg-blue-50/30 focus:bg-white"
                                placeholder="Enter your officer email">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Secure Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="block w-full pl-10 pr-4 py-3 border border-blue-200 rounded-xl
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                       transition duration-200 ease-in-out sm:text-sm
                                       placeholder-gray-400 bg-blue-50/30 focus:bg-white"
                                placeholder="Enter your secure password">
                        </div>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" id="loginBtn"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent
                               rounded-xl shadow-lg text-base font-medium text-white bg-gradient-to-r 
                               from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 
                               transition duration-200 ease-in-out transform hover:scale-[1.02]">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loginIcon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <svg class="w-5 h-5 mr-2 animate-spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loadingIcon">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btnText">Access Officer Dashboard</span>
                    </button>

                </form>

                <!-- Enhanced Footer -->
                <div class="mt-8 text-center">
                    <div class="flex items-center justify-center space-x-2 mb-2">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Secure Officer Access</span>
                    </div>
                    <p class="text-sm text-gray-500">
                        For technical support or access issues, contact your system administrator
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>