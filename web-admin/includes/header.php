<!-- Navbar -->
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between">
        <a href="<?php __DIR__ . "./../"?>" class="text-2xl font-bold text-gray-800">Admin Dashboard</a>
        <nav class="space-x-4 mt-2 md:mt-0">
            <a href="<?php __DIR__ . "./../"?>" class="text-gray-600 hover:text-gray-900">Home</a>
            <a href="<?php __DIR__ . "./../../"?>modules/blog/index.php" class="text-gray-600 hover:text-gray-900">Blog
                Posts</a>
            <a href="<?php __DIR__ . "./../../"?>modules/events/index.php"
                class="text-gray-600 hover:text-gray-900">Events</a>
            <a href="<?php __DIR__ . "./../../"?>modules/carousel/index.php"
                class="text-gray-600 hover:text-gray-900">Carousel</a>
            <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
        </nav>
    </div>
</header>