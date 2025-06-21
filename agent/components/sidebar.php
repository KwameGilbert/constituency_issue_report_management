<?php
// Agent Dashboard Sidebar

/**
 * 
 * Renders the sidebar for the Agent dashboard.
 * @param string $current_page The identifier for the current active page to highlight in the sidebar.
 */
function renderAgentSidebar($current_page)
{
?>
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 -translate-x-full lg:translate-x-0 z-50 transition-transform duration-300 ease-in-out">
        <div class="h-full flex flex-col bg-white border-r border-gray-200 shadow-sm">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-tie text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-800 font-semibold">Agent Portal</h3>
                        <p class="text-gray-500 text-xs">Welcome back</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="p-3 flex-grow overflow-y-auto">
                <div class="mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Main Navigation</h5>
                </div>
                <ul class="space-y-1">
                    <?php
                    // Define menu items
                    $menuItems = [
                        'dashboard' => ['icon' => 'fas fa-chart-line', 'label' => 'Dashboard'],
                        'issues' => ['icon' => 'fas fa-file-alt', 'label' => 'Issues'],
                        'idea_submissions' => ['icon' => 'fas fa-lightbulb', 'label' => 'Ideas'],
                        'notifications' => ['icon' => 'fas fa-bell', 'label' => 'Notifications'],
                    ];

                    // Generate menu items
                    foreach ($menuItems as $page => $item) {
                        $isActive = ($current_page === $page)
                            ? 'bg-slate-900 text-white font-medium'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-slate-900';

                        $pageUrl = "./../" . $page . "/";
                        $pageIcon = $item['icon'];
                        $pageLabel = $item['label'];
                        echo '<li>';
                        echo '<a href="' . $pageUrl . '" class="flex items-center align-center px-4 py-2.5 text-sm rounded-xl transition-colors ' . $isActive . '">';
                        echo '<i class="' . $pageIcon . ' w-5 h-5 mr-3"></i>';
                        echo '<span>' . $pageLabel . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>

                <div class="mt-6 mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Account</h5>
                </div>
                <ul class="space-y-1">
                    <?php
                    // Define account menu items
                    $accountItems = [
                        'profile_settings' => ['icon' => 'fas fa-user-cog', 'label' => 'Settings'],
                        'export_history' => ['icon' => 'fas fa-history', 'label' => 'Exports'],
                    ];

                    // Generate account menu items
                    foreach ($accountItems as $page => $item) {
                        $isActive = ($current_page === $page)
                            ? 'bg-slate-900 text-white font-medium'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-slate-900';

                        $pageUrl = "./../" . $page . "/";
                        $pageIcon = $item['icon'];
                        $pageLabel = $item['label'];
                        echo '<li>';
                        echo '<a href="' . $pageUrl . '" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-colors ' . $isActive . '">';
                        echo '<i class="' . $pageIcon . ' w-5 h-5 mr-3"></i>';
                        echo '<span>' . $pageLabel . '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </nav>

            <!-- Bottom Section with User Info -->
            <div class="p-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-slate-700">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Agent User'; ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'agent@example.com'; ?>
                            </p>
                        </div>
                    </div>
                    <a href="./../login/logout.php" class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>
<?php
}
?>