<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - Constituency System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                }
            }
        }
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
                <form id="loginForm" action="login_process.php" method="POST" class="space-y-6">
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

                    <!-- Error Message -->
                    <div id="errorMessage" class="text-red-600 text-sm text-center p-3 bg-red-50 rounded-lg border border-red-200
                        <?php echo isset($_GET['error']) && $_GET['error'] == 'invalid_credentials' ? '' : 'hidden'; ?>">
                        <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Invalid email or password. Please try again.
                    </div>

                    <!-- Login Button -->
                    <button type="submit"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent
                               rounded-xl shadow-sm text-base font-medium text-white bg-slate-900
                               hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2
                               focus:ring-slate-500 transition duration-200 ease-in-out
                               transform hover:translate-y-[-1px] active:translate-y-0">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Sign in to your account
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

    <script>
        // Custom alert modal function
        function alert(message) {
            const existingModal = document.getElementById('customAlertModal');
            if (existingModal) {
                existingModal.remove();
            }

            const modalHtml = `
                <div id="customAlertModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm transform transition-all duration-200 scale-100">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Notification</h3>
                        </div>
                        <p class="text-gray-600 mb-6 leading-relaxed">${message}</p>
                        <div class="flex justify-end">
                            <button id="closeAlertButton" 
                                class="px-6 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 
                                       focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2
                                       transition duration-200 font-medium">
                                Got it
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Add click handler for close button
            document.getElementById('closeAlertButton').addEventListener('click', function() {
                document.getElementById('customAlertModal').remove();
            });

            // Close on backdrop click
            document.getElementById('customAlertModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.remove();
                }
            });
        }

        // Check for success message from PHP
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === 'registered') {
            alert('Registration successful! Please log in with your credentials.');
        }

        // Add subtle animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';

            setTimeout(() => {
                form.style.transition = 'all 0.6s ease-out';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>