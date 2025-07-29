<?php
// filepath: c:\xampp\htdocs\swma\admin\components\header.php
// Admin Dashboard Header

/**
 * Renders the header for the Admin dashboard.
 * @param string $pageTitle The title of the current page.
 * @param string $pageDescription Optional description text for the page.
 * @param array $actionButtons Optional array of action buttons to display in header.
 */
function renderAdminHeader($pageTitle, $pageDescription = '', $actionButtons = [])
{
    // Get user role for display
    $userRole = $_SESSION['user_role'] ?? 'admin';
    $roleNames = [
        'mp' => 'MP',
        'mce' => 'MCE',
        'pa' => 'PA',
        'admin' => 'ADMIN'
    ];
    $roleAbbr = $roleNames[$userRole] ?? 'ADMIN';
?>
    <!-- Enhanced Admin Header Section -->
    <header class="bg-gradient-to-r from-white to-gray-50 border-b border-gray-200 shadow-sm">
        <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center">
                <!-- Mobile menu hamburger button - only visible on mobile -->
                <button id="sidebarToggle" class="lg:hidden mr-3 w-8 h-8 flex items-center justify-center text-gray-700 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Mobile sidebar overlay -->
                <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 invisible lg:hidden transition-all duration-300 ease-in-out z-40"></div>

                <div class="flex items-center space-x-4">
                    <!-- Page Title and Description -->
                    <div>
                        <div class="flex items-center space-x-3">
                            <h1 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h1>
                            <!-- Admin Role Badge -->
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-red-100 to-purple-100 text-red-800 border border-red-200">
                                <i class="fas fa-crown mr-1 text-xs"></i>
                                <?php echo $roleAbbr; ?>
                            </span>
                        </div>
                        <?php if (!empty($pageDescription)) : ?>
                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($pageDescription); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons and Admin Info -->
            <div class="flex items-center space-x-4">
                <!-- System Status Indicator -->
                <div class="hidden sm:flex items-center space-x-2 px-3 py-1.5 bg-green-50 rounded-lg border border-green-200">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span class="text-xs font-medium text-green-800">System Online</span>
                </div>

                <!-- Action Buttons -->
                <?php if (!empty($actionButtons)) : ?>
                    <div class="flex items-center space-x-3">
                        <?php foreach ($actionButtons as $button) :
                            $icon = $button['icon'] ?? '';
                            $label = $button['label'] ?? '';
                            $href = $button['href'] ?? '#';
                            $btnClass = $button['class'] ?? 'bg-gradient-to-r from-red-600 to-purple-600 text-white hover:from-red-700 hover:to-purple-700 shadow-lg';
                        ?>
                            <a href="<?php echo $href; ?>" class="px-4 py-2 <?php echo $btnClass; ?> text-sm rounded-xl transition-all duration-200 flex items-center space-x-2 font-medium">
                                <?php if ($icon) : ?>
                                    <i class="<?php echo $icon; ?> text-xs"></i>
                                <?php endif; ?>
                                <span><?php echo $label; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Admin Quick Actions Dropdown -->
                <div class="relative">
                    <button id="adminMenuToggle" class="flex items-center space-x-2 px-3 py-2 text-sm text-gray-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                        <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-crown text-red-700 text-sm"></i>
                        </div>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="adminDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50 opacity-0 invisible transform scale-95 transition-all duration-200 ease-out">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrator'; ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo $roleNames[$userRole] ?? 'Administrator'; ?>
                            </p>
                        </div>
                        <a href="../profile/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                            <i class="fas fa-user-circle w-4 h-4 mr-3"></i>
                            Profile Settings
                        </a>
                        <a href="../audit/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                            <i class="fas fa-shield-alt w-4 h-4 mr-3"></i>
                            Audit Logs
                        </a>
                        <a href="../settings/" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors">
                            <i class="fas fa-cogs w-4 h-4 mr-3"></i>
                            System Settings
                        </a>
                        <div class="border-t border-gray-100 mt-1"></div>
                        <a href="../login/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt w-4 h-4 mr-3"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleBtn = document.getElementById('sidebarToggle');

            function toggleSidebar() {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('opacity-0');
                overlay.classList.toggle('invisible');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', toggleSidebar);
            }

            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }

            // Admin dropdown menu functionality
            const adminMenuToggle = document.getElementById('adminMenuToggle');
            const adminDropdown = document.getElementById('adminDropdown');

            function toggleAdminDropdown() {
                const isVisible = !adminDropdown.classList.contains('invisible');
                
                if (isVisible) {
                    adminDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
                    adminDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
                } else {
                    adminDropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
                    adminDropdown.classList.add('opacity-100', 'visible', 'scale-100');
                }
            }

            if (adminMenuToggle) {
                adminMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleAdminDropdown();
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (adminDropdown && !adminDropdown.contains(e.target) && !adminMenuToggle.contains(e.target)) {
                    adminDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
                    adminDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
                }
            });
        });
    </script>
<?php
}
?>