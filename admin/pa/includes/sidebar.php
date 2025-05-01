<?php
$current_page = basename(dirname($_SERVER['PHP_SELF']));

// Get pending issues count
$pending_count_query = "SELECT COUNT(*) as count FROM issues WHERE status = 'pending'";
$pending_count_result = $conn->query($pending_count_query);
$pending_count = $pending_count_result->fetch_assoc()['count'];
?>

<!-- Sidebar -->
<aside id="default-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
        <ul class="space-y-2 font-medium">
            <li>
                <a href="../dashboard/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'dashboard' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-tachometer-alt w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'dashboard' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../issues/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'issues' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-clipboard-list w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'issues' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Issues Management</span>
                    <?php if ($pending_count > 0): ?>
                    <span
                        class="inline-flex items-center justify-center w-5 h-5 ml-3 text-xs font-medium text-white bg-red-500 rounded-full">
                        <?= $pending_count > 9 ? '9+' : $pending_count ?>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../projects/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'projects' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-project-diagram w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'projects' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Projects</span>
                </a>
            </li>
            <li>
                <a href="../entities/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'entities' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-building w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'entities' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Entities & Companies</span>
                </a>
            </li>
            <li>
                <a href="../employment/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'employment' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-user-tie w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'employment' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Employment</span>
                </a>
            </li>
            <li>
                <a href="../reports/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'reports' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-chart-bar w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'reports' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Reports & Analytics</span>
                </a>
            </li>
            <li>
                <a href="../profile/"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'profile' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-user-cog w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'profile' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Profile Settings</span>
                </a>
            </li>
            <li>
                <a href="../help/user-guide.php"
                    class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group <?= $current_page === 'help' ? 'active-nav-link' : '' ?>">
                    <i
                        class="fas fa-question-circle w-5 h-5 text-gray-500 transition duration-75 group-hover:text-gray-900 <?= $current_page === 'help' ? 'text-green-600' : '' ?>"></i>
                    <span class="ml-3">Help & Support</span>
                </a>
            </li>
        </ul>

        <!-- Ghana Flag at bottom -->
        <div class="mt-10 px-4">
            <div class="flex items-center mb-2">
                <div class="w-full h-1 bg-red-600"></div>
            </div>
            <div class="flex items-center mb-2">
                <div class="w-full h-1 bg-yellow-400"></div>
            </div>
            <div class="flex items-center">
                <div class="w-full h-1 bg-green-700"></div>
            </div>
            <div class="mt-4 text-center text-xs text-gray-500">
                <p>&copy; <?= date('Y') ?> Republic of Ghana</p>
                <p class="mt-1">Constituency Management System</p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile sidebar toggle -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggleButton = document.getElementById('sidebar-toggle-button');
    const sidebar = document.getElementById('default-sidebar');

    if (sidebarToggleButton && sidebar) {
        sidebarToggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
});
</script>