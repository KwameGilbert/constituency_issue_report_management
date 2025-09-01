<?php
// header.php - Officer Dashboard Header

/**
 * Renders the header for the Officer dashboard.
 * @param string $pageTitle The title of the current page.
 * @param string $pageDescription Optional description text for the page.
 * @param array $actionButtons Optional array of action buttons to display in header.
 */
function renderOfficerHeader($pageTitle, $pageDescription = '', $actionButtons = [])
{
?>
    <!-- Header Section -->
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center">
                <!-- Mobile menu hamburger button - only visible on mobile -->
                <button id="sidebarToggle" class="lg:hidden mr-3 w-8 h-8 flex items-center justify-center text-gray-700 hover:text-slate-900">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Mobile sidebar overlay -->
                <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 invisible lg:hidden transition-all duration-300 ease-in-out z-40"></div>

                <div>
                    <h1 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php if (!empty($pageDescription)) : ?>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($pageDescription); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($actionButtons)) : ?>
                <div class="flex items-center space-x-3">
                    <?php foreach ($actionButtons as $button) :
                        $icon = $button['icon'] ?? '';
                        $label = $button['label'] ?? '';
                        $href = $button['href'] ?? '#';
                        $btnClass = $button['class'] ?? 'bg-indigo-900 text-white hover:bg-indigo-800';
                    ?>
                        <a href="<?php echo $href; ?>" class="px-4 py-2 <?php echo $btnClass; ?> text-sm rounded-xl shadow-sm transition-colors duration-200 flex items-center space-x-2">
                            <?php if ($icon) : ?>
                                <i class="<?php echo $icon; ?> text-xs"></i>
                            <?php endif; ?>
                            <span><?php echo $label; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

            toggleBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        });
    </script>
<?php
}
?>