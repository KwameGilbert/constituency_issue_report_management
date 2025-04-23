<?php
// Get the current URL path
$current_url = $_SERVER['REQUEST_URI'];
?>

<!-- Sidebar -->
<aside class="hidden md:flex md:flex-col w-64 bg-gray-800 text-white">
    <div class="p-4 border-b border-gray-700">
        <div class="flex items-center space-x-2">
            <img src="/assets/images/coat-of-arms.png" alt="Logo" class="h-8 w-8">
            <h1 class="text-xl font-bold">Admin Panel</h1>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-2 px-2">
            <li>
                <a href="/web-admin/dashboard/"
                    class="flex items-center space-x-2 p-2 rounded-md <?php echo strpos($current_url, '/web-admin/dashboard/') !== false ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/web-admin/modules/blog/"
                    class="flex items-center space-x-2 p-2 rounded-md <?php echo strpos($current_url, '/web-admin/modules/blog/') !== false ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-newspaper w-6"></i>
                    <span>Blog Posts</span>
                </a>
            </li>
            <li>
                <a href="/web-admin/modules/events/"
                    class="flex items-center space-x-2 p-2 rounded-md <?php echo strpos($current_url, '/web-admin/modules/events/') !== false ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-calendar-alt w-6"></i>
                    <span>Events</span>
                </a>
            </li>
            <li>
                <a href="/web-admin/modules/carousel/"
                    class="flex items-center space-x-2 p-2 rounded-md <?php echo strpos($current_url, '/web-admin/modules/carousel/') !== false ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-images w-6"></i>
                    <span>Carousel</span>
                </a>
            </li>
        </ul>
        <div class="mt-8 px-4">
            <h3 class="text-xs uppercase text-gray-400 font-semibold mb-2">Settings</h3>
            <ul class="space-y-2">
                <li>
                    <a href="/web-admin/modules/profile/"
                        class="flex items-center space-x-2 p-2 rounded-md <?php echo strpos($current_url, '/web-admin/modules/profile/') !== false ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                        <i class="fas fa-user-cog w-6"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="/web-admin/logout.php"
                        class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-700 text-red-400">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>