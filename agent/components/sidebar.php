<?php
// sidebar.php - Agent Dashboard Sidebar

/**
 * Renders the sidebar for the Agent dashboard.
 * @param string $current_page The identifier for the current active page to highlight in the sidebar.
 */
function renderAgentSidebar($current_page)
{
?>
    <!-- Mobile menu button -->
    <button id="sidebarToggle" class="fixed lg:hidden top-4 left-4 w-10 h-10 rounded-lg bg-white flex items-center justify-center text-gray-700 hover:text-primary z-50 shadow-md transition-all">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile sidebar overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 opacity-0 invisible lg:hidden transition-all duration-300 ease-in-out z-40"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-60 -translate-x-full lg:translate-x-0 bg-dark z-50 transition-transform duration-300 ease-in-out">
        <div class="h-full flex flex-col bg-white border-r border-gray-100">
            <!-- Sidebar Header -->
            <div class="p-3 border-b border-gray-100">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">
                        <i class="fas fa-user-tie text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-800 font-medium text-sm">Agent Portal</h3>
                        <p class="text-gray-500 text-xs">Dashboard</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-2 flex-grow overflow-y-auto">
                <ul class="space-y-1">
                    <?php
                    // Define menu items
                    $menuItems = [
                        'dashboard' => ['icon' => 'fas fa-chart-line', 'label' => 'Dashboard'],
                        'issues' => ['icon' => 'fas fa-file-alt', 'label' => 'Issues'],
                        'idea_submissions' => ['icon' => 'fas fa-lightbulb', 'label' => 'Ideas'],
                        'notifications' => ['icon' => 'fas fa-bell', 'label' => 'Notifications'],
                        'profile_settings' => ['icon' => 'fas fa-user-cog', 'label' => 'Settings'],
                        'export_history' => ['icon' => 'fas fa-history', 'label' => 'Exports'],
                    ];

                    // Generate menu items
                    foreach ($menuItems as $page => $item) {
                        $isActive = ($current_page === $page)
                            ? 'bg-gray-100 text-primary font-medium'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';

                        $pageUrl = "./../" . $page . "/";
                        $pageIcon = $item['icon'];
                        $pageLabel = $item['label'];
                        echo '<li>';
                        echo '<a href="' . $pageUrl . '" class="flex items-center px-3 py-2 text-xs rounded-md transition-colors ' . $isActive . '">';
                        echo '<i class="' . $pageIcon . ' w-4 h-4 mr-2"></i>';
                        echo '<span>' . $pageLabel . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </nav>

            <!-- Bottom Section -->
            <div class="p-2 border-t border-gray-100">
                <a href="?page=logout" class="flex items-center px-3 py-2 text-xs rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                    <i class="fas fa-sign-out-alt w-4 h-4 mr-2"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </aside>

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

                    toggleBtn.addEventListener('click', toggleSidebar);
                    overlay.addEventListener('click', toggleSidebar);
                });
    </script>
<?php
}
?>