<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - Constituency System</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
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
                btnText.textContent = 'Logging in...';

                // Create form data
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);

                // Send login request
                fetch('login_ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button state
                        loginBtn.disabled = false;
                        loginIcon.classList.remove('hidden');
                        loadingIcon.classList.add('hidden');
                        btnText.textContent = 'Sign in to your account';

                        if (data.success) {
                            // Success case
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Login successful'
                            }).then(() => {
                                // Redirect to dashboard
                                window.location.href = '../dashboard/';
                            });
                        } else {
                            // Error case
                            Toast.fire({
                                icon: 'error',
                                title: data.message || 'Login failed'
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
                        btnText.textContent = 'Sign in to your account';

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

<body class="min-h-screen bg-gray-50 font-inter antialiased">
    <div class="min-h-screen flex">
        <!-- Left Side - Image/Branding -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <svg class="absolute inset-0 h-full w-full" fill="currentColor" viewBox="0 0 100 100">
                    <defs>
                        <pattern id="smallGrid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#smallGrid)" />
                </svg>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center px-12 py-12 text-white">
                <div class="max-w-md">
                    <!-- Icon -->
                    <div class="mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Text Content -->
                    <h1 class="text-4xl font-bold mb-6 leading-tight">
                        Constituency Issue Management System
                    </h1>
                    <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                        Empowering agents to efficiently track, manage, and resolve community issues for better constituent services.
                    </p>

                    <!-- Features -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                            <span class="text-gray-300">Real-time issue tracking</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                            <span class="text-gray-300">Comprehensive reporting tools</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
                            <span class="text-gray-300">Streamlined workflow management</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decorative Elements -->
            <div class="absolute top-1/4 right-0 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl transform translate-x-32"></div>
            <div class="absolute bottom-1/4 left-0 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl transform -translate-x-24"></div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-8 xl:px-12">
            <div class="mx-auto w-full max-w-md">
                <!-- Mobile Header (visible on mobile only) -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-100 rounded-xl mb-4">
                        <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
                    <p class="text-gray-600 mt-2">Constituency Management System</p>
                </div>

                <!-- Desktop Header -->
                <div class="hidden lg:block mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Welcome back</h2>
                    <p class="mt-2 text-gray-600">Please sign in to your agent account</p>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="email" id="email" name="email" required
                                class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-slate-500 focus:border-slate-500
                                       transition duration-200 ease-in-out sm:text-sm
                                       placeholder-gray-400 bg-gray-50 focus:bg-white"
                                placeholder="Enter your email">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-slate-500 focus:border-slate-500
                                       transition duration-200 ease-in-out sm:text-sm
                                       placeholder-gray-400 bg-gray-50 focus:bg-white"
                                placeholder="Enter your password">
                        </div>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" id="loginBtn"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent
                               rounded-xl shadow-sm text-base font-medium text-white bg-slate-900
                               hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2
                               focus:ring-slate-500 transition duration-200 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loginIcon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <svg class="w-5 h-5 mr-2 animate-spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="loadingIcon">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btnText">Sign in to your account</span>
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">
                        Need help? Contact your system administrator
                    </p>
                </div>
            </div>
        </div>
    </div>


</body>

</html>