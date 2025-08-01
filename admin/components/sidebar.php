<?php
// filepath: c:\xampp\htdocs\swma\admin\components\sidebar.php
// Admin Dashboard Sidebar

/**
 * Get total count of pending issues across the system
 * @param PDO $conn Database connection
 * @return int Total pending issues count
 */
function getSystemPendingIssuesCount(PDO $conn): int
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM issues WHERE status = 'pending'");
        $stmt->execute();
        $result = $stmt->fetch();
        return ($result !== false) ? $result['count'] : 0;
    } catch (Exception $e) {
        error_log("Error getting pending issues count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total count of active users (officers and agents)
 * @param PDO $conn Database connection
 * @return int Total active users count
 */
function getActiveUsersCount(PDO $conn): int
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE status = 'active' AND role IN ('officer', 'agent')");
        $stmt->execute();
        $result = $stmt->fetch();
        return ($result !== false) ? $result['count'] : 0;
    } catch (Exception $e) {
        error_log("Error getting active users count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Renders the sidebar for the Admin dashboard.
 * @param string $current_page The identifier for the current active page to highlight in the sidebar.
 * @param int $pendingIssuesCount Total pending issues count
 * @param int $activeUsersCount Total active users count
 */
function renderAdminSidebar($current_page, $pendingIssuesCount = 0, $activeUsersCount = 0)
{
    // Helper function to render notification badges
    function renderNotificationBadge($page, $count)
    {
        if ($count > 0) {
            $badgeClass = 'ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold';
            switch ($page) {
                case 'issues':
                    return ' <span class="' . $badgeClass . ' bg-red-100 text-red-800">' . $count . '</span>';
                case 'users':
                    return ' <span class="' . $badgeClass . ' bg-green-100 text-green-800">' . $count . '</span>';
                default:
                    return '';
            }
        }
        return '';
    }

    // Get user role for display
    $userRole = $_SESSION['user_role'] ?? 'admin';
    $roleNames = [
        'mp' => 'Member of Parliament',
        'mce' => 'Municipal Chief Executive',
        'pa' => 'Personal Assistant',
        'admin' => 'System Administrator'
    ];
    $displayRole = $roleNames[$userRole] ?? 'Administrator';
?>
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 -translate-x-full lg:translate-x-0 z-50 transition-transform duration-300 ease-in-out">
        <div class="h-full flex flex-col bg-white border-r border-gray-200 shadow-sm font-inter">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-100 bg-red-900">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-crown text-red-900 text-lg"></i>
                        </div>
                        <!-- Admin badge -->
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-star text-white text-xs"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">Admin Portal</h3>
                        <p class="text-red-100 text-xs"><?php echo htmlspecialchars($displayRole); ?></p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-3 flex-grow overflow-y-auto">
                <!-- Main Navigation -->
                <div class="mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">System Overview</h5>
                </div>
                <ul class="space-y-1 mb-6">
                    <?php
                    $mainMenuItems = [
                        'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard'],
                        'analytics' => ['icon' => 'fas fa-chart-bar', 'label' => 'Analytics'],
                        'reports' => ['icon' => 'fas fa-file-invoice', 'label' => 'Reports'],
                    ];

                    // Helper function to determine active menu item class
                    function getActiveMenuClass($current_page, $page)
                    {
                        return ($current_page === $page)
                            ? 'bg-red-900 text-white font-medium shadow-lg'
                            : 'text-gray-600 hover:bg-red-100 hover:text-red-900';
                    }

                    foreach ($mainMenuItems as $page => $item) {
                        $isActive = getActiveMenuClass($current_page, $page);

                        echo '<li class="transition-all duration-200 ease-in-out">';
                        echo '<a href="./../' . $page . '/" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-all ' . $isActive . '">';
                        echo '<i class="' . $item['icon'] . ' fa-lg mr-3 w-5"></i>';
                        echo '<span>' . $item['label'] . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>

                <!-- Management Section -->
                <div class="mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Management</h5>
                </div>
                <ul class="space-y-1 mb-6">
                    <?php
                    $managementItems = [
                        'issues' => ['icon' => 'fas fa-clipboard-list', 'label' => 'Issues', 'badge' => $pendingIssuesCount],
                        'users' => ['icon' => 'fas fa-users-cog', 'label' => 'Users', 'badge' => $activeUsersCount],
                        'agents' => ['icon' => 'fas fa-user-tie', 'label' => 'Field Agents'],
                        'officers' => ['icon' => 'fas fa-user-shield', 'label' => 'Officers'],
                        'locations' => ['icon' => 'fas fa-map-marked-alt', 'label' => 'Locations'],
                    ];

                    foreach ($managementItems as $page => $item) {
                        $isActive = getActiveMenuClass($current_page, $page);

                        echo '<li class="transition-all duration-200 ease-in-out">';
                        echo '<a href="./../' . $page . '/" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-all ' . $isActive . '">';
                        echo '<i class="' . $item['icon'] . ' fa-lg mr-3 w-5"></i>';
                        echo '<span>' . $item['label'];

                        // Show badge if available
                        if (isset($item['badge'])) {
                            echo renderNotificationBadge($page, $item['badge']);
                        }
                        echo '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>

                <!-- Content Management -->
                <div class="mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Content & Projects</h5>
                </div>
                <ul class="space-y-1 mb-6">
                    <?php
                    $contentItems = [
                        'projects' => ['icon' => 'fas fa-project-diagram', 'label' => 'Projects'],
                        'employment' => ['icon' => 'fas fa-briefcase', 'label' => 'Employment'],
                        'ideas' => ['icon' => 'fas fa-lightbulb', 'label' => 'Ideas & Suggestions'],
                        'announcements' => ['icon' => 'fas fa-bullhorn', 'label' => 'Announcements'],
                    ];

                    foreach ($contentItems as $page => $item) {
                        $isActive = getActiveMenuClass($current_page, $page);

                        echo '<li class="transition-all duration-200 ease-in-out">';
                        echo '<a href="./../' . $page . '/" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-all ' . $isActive . '">';
                        echo '<i class="' . $item['icon'] . ' fa-lg mr-3 w-5"></i>';
                        echo '<span>' . $item['label'] . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>

                <!-- System Administration -->
                <div class="mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">System</h5>
                </div>
                <ul class="space-y-1">
                    <?php
                    $systemItems = [
                        'settings' => ['icon' => 'fas fa-cogs', 'label' => 'System Settings'],
                        'audit' => ['icon' => 'fas fa-shield-alt', 'label' => 'Audit Logs'],
                        'profile' => ['icon' => 'fas fa-user-circle', 'label' => 'Profile'],
                        'help' => ['icon' => 'fas fa-question-circle', 'label' => 'Help & Support'],
                    ];

                    foreach ($systemItems as $page => $item) {
                        $isActive = getActiveMenuClass($current_page, $page);

                        echo '<li class="transition-all duration-200 ease-in-out">';
                        echo '<a href="./../' . $page . '/" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-all ' . $isActive . '">';
                        echo '<i class="' . $item['icon'] . ' fa-lg mr-3 w-5"></i>';
                        echo '<span>' . $item['label'] . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </nav>

            <!-- Bottom Section with Admin Info -->
            <div class="p-4 border-t border-gray-100 bg-red-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-red-900">
                                <i class="fas fa-user text-lg"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">
                                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') : 'Administrator'; ?>
                            </p>
                            <p class="text-xs text-red-100">
                                <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8') : 'admin@example.com'; ?>
                            </p>
                        </div>
                    </div>
                    <a href="./../login/logout.php" id="logout-link" class="p-2 rounded-lg text-white hover:text-red-900 hover:bg-white transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Enhanced Logout Modal -->
    <div id="logout-modal" class="fixed inset-0 flex items-center justify-center z-[1000] bg-black bg-opacity-60
                opacity-0 invisible transition-opacity duration-300 ease-in-out pointer-events-none font-sans antialiased text-gray-900">
        <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm text-center transform -translate-y-5 transition-transform duration-300 ease-in-out modal-content border border-red-100">
            <div class="w-16 h-16 bg-gradient-to-br from-red-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-sign-out-alt text-red-600 text-xl"></i>
            </div>
            <h2 class="text-xl font-semibold mb-3 text-gray-900">Confirm Admin Logout</h2>
            <p class="text-sm text-gray-600 mb-6">You will be logged out from the administrative system. All unsaved changes will be lost.</p>
            <div class="flex justify-end space-x-4">
                <button id="cancel-logout" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                    Cancel
                </button>
                <button id="confirm-logout" class="px-4 py-2 bg-gradient-to-r from-red-600 to-purple-600 text-white rounded-lg hover:from-red-700 hover:to-purple-700 transition-all font-medium text-sm shadow-lg">
                    Yes, logout
                </button>
            </div>
        </div>
    </div>

    <script>
        const logoutLink = document.getElementById('logout-link');
        const logoutModal = document.getElementById('logout-modal');
        const confirmLogoutBtn = document.getElementById('confirm-logout');
        const cancelLogoutBtn = document.getElementById('cancel-logout');

        function showModal() {
            logoutModal.classList.remove('opacity-0', 'invisible', 'pointer-events-none');
            logoutModal.classList.add('opacity-100', 'visible');
        }

        function hideModal() {
            logoutModal.classList.remove('opacity-100', 'visible');
            logoutModal.classList.add('opacity-0', 'invisible', 'pointer-events-none');
        }

        if (logoutLink) {
            logoutLink.addEventListener('click', function(event) {
                event.preventDefault();
                showModal();
            });
        }

        if (confirmLogoutBtn) {
            confirmLogoutBtn.addEventListener('click', function() {
                setTimeout(() => {
                    if (logoutLink && logoutLink.href) {
                        window.location.href = logoutLink.href;
                    }
                }, 300);
            });
        }

        if (cancelLogoutBtn) {
            cancelLogoutBtn.addEventListener('click', function() {
                hideModal();
            });
        }

        if (logoutModal) {
            logoutModal.addEventListener('click', function(event) {
                if (event.target === logoutModal) {
                    hideModal();
                }
            });
        }
    </script>
<?php
}
?>