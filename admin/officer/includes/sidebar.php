<?php
// Get current page to highlight active menu item
$active_page = $active_page ?? 'dashboard';
?>

<!-- Sidebar for desktop -->
<div class="hidden lg:flex lg:flex-shrink-0">
    <div class="flex flex-col w-64 border-r border-gray-200 bg-white">
        <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
            <!-- Logo -->
            <div class="flex items-center flex-shrink-0 px-4 mb-5">
                <a href="<?= isset($basePath) ? $basePath : '' ?>dashboard/" class="flex items-center">
                    <img src="<?= isset($basePath) ? $basePath : '' ?>../../assets/images/coat-of-arms.png"
                        class="h-10 w-auto mr-3" alt="Logo">
                    <div>
                        <div class="text-lg font-semibold text-amber-700">Constituency</div>
                        <div class="text-xs text-gray-600">Issue Management System</div>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 space-y-1 bg-white" aria-label="Sidebar">
                <a href="<?= isset($basePath) ? $basePath : '' ?>dashboard/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'dashboard' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-tachometer-alt mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'dashboard' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    Dashboard
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>create-issue/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'create-issue' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-plus-circle mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'create-issue' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    Report New Issue
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>issues/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'issues' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-clipboard-list mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'issues' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    My Issues
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>reports/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'reports' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-chart-bar mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'reports' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    Reports
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>profile/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'profile' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-user-circle mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'profile' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    My Profile
                </a>

                <!-- Help and support with dropdown -->
                <div x-data="{ open: false }" class="space-y-1">
                    <button @click="open = !open" type="button"
                        class="group w-full flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-amber-50 hover:text-amber-700 transition-colors duration-300">
                        <i
                            class="fas fa-question-circle mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-amber-500 transition-colors duration-300"></i>
                        <span class="flex-1 text-left">Help & Support</span>
                        <i class="fas fa-chevron-down text-gray-400 group-hover:text-amber-500 transition-colors duration-300"
                            :class="{'transform rotate-180': open}"></i>
                    </button>
                    <div x-show="open" class="space-y-1 pl-12" style="display: none;">
                        <a href="<?= isset($basePath) ? $basePath : '' ?>help/user-guide.php"
                            class="group flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-amber-700 transition-colors duration-300">
                            <i
                                class="fas fa-book mr-3 text-gray-400 group-hover:text-amber-500 transition-colors duration-300"></i>
                            User Guide
                        </a>
                        <a href="<?= isset($basePath) ? $basePath : '' ?>help/contact-support.php"
                            class="group flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-amber-700 transition-colors duration-300">
                            <i
                                class="fas fa-life-ring mr-3 text-gray-400 group-hover:text-amber-500 transition-colors duration-300"></i>
                            Contact Support
                        </a>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-gray-200">
                    <a href="<?= isset($basePath) ? $basePath : '' ?>../logout.php"
                        class="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-red-700 hover:bg-red-50 transition-colors duration-300">
                        <i
                            class="fas fa-sign-out-alt mr-3 flex-shrink-0 h-6 w-6 text-red-400 group-hover:text-red-500 transition-colors duration-300"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>
    </div>
</div>

<!-- Mobile sidebar (off-canvas) -->
<div id="mobile-sidebar" class="lg:hidden fixed inset-0 z-40 hidden">
    <!-- Overlay background with blur effect -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 backdrop-filter backdrop-blur-sm"></div>

    <div class="fixed inset-y-0 left-0 flex flex-col max-w-xs w-full bg-white shadow-xl">
        <!-- Close button -->
        <div class="absolute top-0 right-0 -mr-12 pt-2">
            <button id="close-sidebar-button"
                class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500">
                <span class="sr-only">Close sidebar</span>
                <i class="fas fa-times text-white text-xl"></i>
            </button>
        </div>

        <!-- Logo -->
        <div class="flex-shrink-0 flex items-center px-4 py-5 border-b border-gray-200">
            <a href="<?= isset($basePath) ? $basePath : '' ?>dashboard/" class="flex items-center">
                <img src="<?= isset($basePath) ? $basePath : '' ?>../../assets/images/coat-of-arms.png"
                    class="h-8 w-auto mr-3" alt="Logo">
                <div>
                    <div class="text-lg font-semibold text-amber-700">Constituency</div>
                    <div class="text-xs text-gray-600">Issue Management System</div>
                </div>
            </a>
        </div>

        <!-- Mobile Navigation -->
        <div class="flex-1 h-0 overflow-y-auto">
            <nav class="px-2 py-4 space-y-1">
                <a href="<?= isset($basePath) ? $basePath : '' ?>dashboard/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'dashboard' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-tachometer-alt mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'dashboard' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    Dashboard
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>create-issue/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'create-issue' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-plus-circle mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'create-issue' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    Report New Issue
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>issues/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'issues' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-clipboard-list mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'issues' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    My Issues
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>reports/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'reports' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-chart-bar mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'reports' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    Reports
                </a>

                <a href="<?= isset($basePath) ? $basePath : '' ?>profile/"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= $active_page === 'profile' ? 'bg-amber-50 text-amber-700' : 'text-gray-600 hover:bg-amber-50 hover:text-amber-700' ?> transition-colors duration-300">
                    <i
                        class="fas fa-user-circle mr-3 flex-shrink-0 h-6 w-6 <?= $active_page === 'profile' ? 'text-amber-500' : 'text-gray-400 group-hover:text-amber-500' ?> transition-colors duration-300"></i>
                    My Profile
                </a>

                <!-- Help and support with accordion -->
                <div x-data="{ open: false }" class="space-y-1">
                    <button @click="open = !open" type="button"
                        class="group w-full flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-amber-50 hover:text-amber-700 transition-colors duration-300">
                        <i
                            class="fas fa-question-circle mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-amber-500 transition-colors duration-300"></i>
                        <span class="flex-1 text-left">Help & Support</span>
                        <i class="fas fa-chevron-down text-gray-400 group-hover:text-amber-500 transition-colors duration-300"
                            :class="{'transform rotate-180': open}"></i>
                    </button>
                    <div x-show="open" class="space-y-1 pl-12" style="display: none;">
                        <a href="<?= isset($basePath) ? $basePath : '' ?>help/user-guide.php"
                            class="group flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-amber-700 transition-colors duration-300">
                            <i
                                class="fas fa-book mr-3 text-gray-400 group-hover:text-amber-500 transition-colors duration-300"></i>
                            User Guide
                        </a>
                        <a href="<?= isset($basePath) ? $basePath : '' ?>help/contact-support.php"
                            class="group flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-amber-700 transition-colors duration-300">
                            <i
                                class="fas fa-life-ring mr-3 text-gray-400 group-hover:text-amber-500 transition-colors duration-300"></i>
                            Contact Support
                        </a>
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-gray-200">
                    <a href="<?= isset($basePath) ? $basePath : '' ?>../logout.php"
                        class="group flex items-center px-3 py-2 text-sm font-medium rounded-md text-red-700 hover:bg-red-50 transition-colors duration-300">
                        <i
                            class="fas fa-sign-out-alt mr-3 flex-shrink-0 h-6 w-6 text-red-400 group-hover:text-red-500 transition-colors duration-300"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </div>
    </div>
</div>

<!-- JavaScript for mobile sidebar toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const closeSidebarButton = document.getElementById('close-sidebar-button');

    // Function to toggle sidebar
    window.toggleMobileSidebar = function() {
        mobileSidebar.classList.toggle('hidden');
        document.body.classList.toggle('overflow-hidden');
    };

    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', toggleMobileSidebar);

    // Close sidebar when clicking close button
    closeSidebarButton.addEventListener('click', toggleMobileSidebar);
});
</script>