<?php
// Get the current user's name from session
$officer_name = isset($_SESSION['officer_name']) ? $_SESSION['officer_name'] : 'Officer';
$first_letter = strtoupper(substr($officer_name, 0, 1));
?>

<header class="bg-white shadow-sm z-10 sticky top-0">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Mobile Menu Toggle Button -->
        <button id="mobile-menu-button"
            class="lg:hidden text-amber-800 focus:outline-none transition-transform duration-300 ease-in-out"
            aria-label="Toggle Menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Page Title -->
        <div class="flex items-center">
            <h2 class="text-xl font-semibold text-gray-800"><?= isset($pageTitle) ? $pageTitle : 'Dashboard' ?></h2>
        </div>

        <!-- User Profile -->
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <button class="text-gray-500 hover:text-amber-600 transition-colors duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                </button>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                    <span class="hidden md:block text-sm"><?= htmlspecialchars($officer_name) ?></span>
                    <div
                        class="h-8 w-8 rounded-full bg-amber-600 flex items-center justify-center text-white shadow-md hover:bg-amber-700 transition-colors duration-300">
                        <?= $first_letter ?>
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open" @click.away="open = false"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95" style="display: none;">
                    <a href="<?= isset($basePath) ? $basePath : '' ?>profile/"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50">
                        <i class="fas fa-user-circle mr-2"></i> Your Profile
                    </a>
                    <a href="<?= isset($basePath) ? $basePath : '' ?>profile/#settings"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-amber-50">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="<?= isset($basePath) ? $basePath : '' ?>../logout.php"
                        class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>