<?php
require_once '../includes/auth.php';
require_once '../includes/header.php';
?>
<main class="p-8">
    <h2 class="text-2xl font-bold mb-6">Admin Dashboard</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="modules/blog/index.php" class="p-4 bg-white rounded shadow hover:bg-gray-50">
            Manage Blog Posts
        </a>
        <a href="modules/events/index.php" class="p-4 bg-white rounded shadow hover:bg-gray-50">
            Manage Events
        </a>
        <a href="modules/carousel/index.php" class="p-4 bg-white rounded shadow hover:bg-gray-50">
            Manage Carousel
        </a>
    </div>
</main>
<?php require_once 'includes/footer.php'; ?>