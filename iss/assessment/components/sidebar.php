<?php
// Officer Dashboard Sidebar
function getPendingIssuesCount(PDO $conn): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM issues WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->fetch();
    return ($result !== false) ? $result['count'] : 0;
};

$totalPendingIssues = getPendingIssuesCount($conn);
function renderOfficerSidebar($current_page, $totalPendingIssues = 0)
{
    // Helper function to render the pending issues badge
    function renderPendingIssuesBadge($page, $totalPendingIssues)
    {
        if ($page === 'issues' && $totalPendingIssues > 0) {
            return ' <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">' . $totalPendingIssues . '</span>';
        }
        return '';
    }
?>
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 -translate-x-full lg:translate-x-0 z-50 transition-transform duration-300 ease-in-out">
        <div class="h-full flex flex-col bg-white border-r border-gray-200 shadow-sm font-inter">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-700 rounded-xl flex items-center justify-center">
                        <i class="fas fa-tasks text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-gray-800 font-semibold">Assessment Portal</h3>
                        <p class="text-gray-500 text-xs">Stakeholder Team</p>
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
                    $menuItems = [
                        'dashboard' => ['icon' => 'fas fa-chart-line', 'label' => 'Dashboard'],
                        'issues' => ['icon' => 'fas fa-file-alt', 'label' => 'Issues for Assessment'],
                        'reports' => ['icon' => 'fas fa-file-invoice', 'label' => 'Assessment Reports'],
                        'profile' => ['icon' => 'fas fa-user', 'label' => 'Profile'],
                        'help' => ['icon' => 'fas fa-question-circle', 'label' => 'Help'],
                    ];

                    // Helper function to determine active menu item class
                    function getActiveMenuClass($current_page, $page)
                    {
                        return ($current_page === $page)
                            ? 'bg-purple-700 text-white font-medium'
                            : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700';
                    }

                    foreach ($menuItems as $page => $item) {
                        $isActive = getActiveMenuClass($current_page, $page);
                    
                        echo '<li class="transition-colors duration-200 ease-in-out">';
                        echo '<a href="./../' . $page . '/" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-colors ' . $isActive . '">';
                        echo '<i class="' . $item['icon'] . ' fa-lg mr-3 w-5"></i>';
                        echo '<span>' . $item['label'];
                        // Only show pending issues badge for Issues menu item
                        if ($page === 'issues') {
                            echo renderPendingIssuesBadge($page, $totalPendingIssues);
                        }
                        echo '</span>';
                        echo '</a>';
                        echo '</li>';
                    }
                    ?>
                </ul>

                <!-- <div class="mt-6 mb-2 px-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Account</h5>
                </div> -->
                <!-- <ul class="space-y-1">
                    <?php
                    // $accountItems = [
                    //     'profile_settings' => ['icon' => 'fas fa-user-cog', 'label' => 'Settings'],
                    // ];

                    // foreach ($accountItems as $page => $item) {
                    //     $isActive = getActiveMenuClass($current_page, $page);

                    //     echo '<li>';
                    //     echo '<a href="./../' . $page . '/" class="flex items-center px-4 py-2.5 text-sm rounded-xl transition-colors ' . $isActive . '">';
                    //     echo '<i class="' . $item['icon'] . ' fa-lg mr-3"></i>';
                    //     echo '<span>' . $item['label'] . '</span>';
                    //     echo '</a>';
                    //     echo '</li>';
                    // }
                    ?>
                </ul> -->
            </nav>

            <!-- Bottom Section with User Info -->
            <div class="p-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center text-purple-700">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') : 'Assessor'; ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8') : 'assessor@example.com'; ?>
                            </p>
                        </div>
                    </div>
                    <a href="./../login/logout.php" id="logout-link" class="p-2 rounded-lg text-gray-500 hover:text-purple-700 hover:bg-purple-50 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Logout Modal -->
    <div id="logout-modal" class="fixed inset-0 flex items-center justify-center z-[1000] bg-black bg-opacity-60
                opacity-0 invisible transition-opacity duration-300 ease-in-out pointer-events-none font-sans antialiased text-gray-900">
        <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-sm text-center transform -translate-y-5 transition-transform duration-300 ease-in-out modal-content">
            <h2 class="text-xl font-semibold mb-3 text-gray-900">Are you sure?</h2>
            <p class="text-sm text-gray-600 mb-6">You will be logged out from your account.</p>
            <div class="flex justify-end space-x-4">
                <button id="cancel-logout" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
                    Cancel
                </button>
                <button id="confirm-logout" class="px-4 py-2 bg-purple-700 text-white rounded-lg hover:bg-purple-800 transition-colors font-medium text-sm">
                    Yes, log me out!
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