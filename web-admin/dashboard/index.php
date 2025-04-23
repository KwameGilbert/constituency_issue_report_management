<?php
require_once '../includes/auth.php';
require_once '../../config/db.php';

// Fetch stats for dashboard
$stats = [
    'posts' => $conn->query("SELECT COUNT(*) as count FROM blog_posts")->fetch_assoc()['count'],
    'events' => $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'],
    'carousel' => $conn->query("SELECT COUNT(*) as count FROM carousel_items")->fetch_assoc()['count'],
    'upcoming_events' => $conn->query("SELECT COUNT(*) as count FROM events WHERE start_date >= CURDATE()")->fetch_assoc()['count'],
];

// Fetch recent content
$recent_posts = $conn->query("SELECT id, title, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recent_events = $conn->query("SELECT id, name, start_date FROM events ORDER BY start_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Constituency Issue Report Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Sidebar for larger screens -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php require_once '../includes/desktop_sidebar.php';?>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top navbar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between p-4">
                    <!-- Mobile menu button -->
                    <button id="mobile-menu-button" class="md:hidden text-gray-700 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="flex items-center ml-4 md:ml-0">
                        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                    </div>

                    <!-- User menu -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span
                                    class="hidden md:block text-sm"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                                <div
                                    class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <?php require_once '../includes/mobile_sidebar.php';?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto p-4">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <!-- Blog Posts -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Blog Posts</h3>
                                <p class="text-2xl font-bold"><?= $stats['posts'] ?></p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3 text-blue-600">
                                <i class="fas fa-newspaper"></i>
                            </div>
                        </div>
                        <a href="../modules/blog/"
                            class="text-blue-600 text-sm mt-4 inline-block hover:underline">Manage Posts</a>
                    </div>

                    <!-- Events -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Events</h3>
                                <p class="text-2xl font-bold"><?= $stats['events'] ?></p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3 text-green-600">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <a href="../modules/events/"
                            class="text-green-600 text-sm mt-4 inline-block hover:underline">Manage Events</a>
                    </div>

                    <!-- Carousel Items -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Carousel Items</h3>
                                <p class="text-2xl font-bold"><?= $stats['carousel'] ?></p>
                            </div>
                            <div class="rounded-full bg-purple-100 p-3 text-purple-600">
                                <i class="fas fa-images"></i>
                            </div>
                        </div>
                        <a href="../modules/carousel/"
                            class="text-purple-600 text-sm mt-4 inline-block hover:underline">Manage Carousel</a>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Upcoming Events</h3>
                                <p class="text-2xl font-bold"><?= $stats['upcoming_events'] ?></p>
                            </div>
                            <div class="rounded-full bg-amber-100 p-3 text-amber-600">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <a href="../modules/events/"
                            class="text-amber-600 text-sm mt-4 inline-block hover:underline">View Calendar</a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="p-4 border-b">
                        <h2 class="text-lg font-semibold">Quick Actions</h2>
                    </div>
                    <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="../modules/blog/create.php"
                            class="flex items-center justify-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                            <span>New Blog Post</span>
                        </a>
                        <a href="../modules/events/create.php"
                            class="flex items-center justify-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition">
                            <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                            <span>New Event</span>
                        </a>
                        <a href="../modules/carousel/create.php"
                            class="flex items-center justify-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition">
                            <i class="fas fa-plus-circle text-purple-600 mr-2"></i>
                            <span>New Carousel Item</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Content -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Blog Posts -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold">Recent Blog Posts</h2>
                            <a href="../modules/blog/" class="text-sm text-blue-600 hover:underline">View All</a>
                        </div>
                        <div class="p-4">
                            <?php if (empty($recent_posts)): ?>
                            <p class="text-gray-500 text-center py-4">No blog posts found.</p>
                            <?php else: ?>
                            <ul class="divide-y">
                                <?php foreach ($recent_posts as $post): ?>
                                <li class="py-3">
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="font-medium"><?= htmlspecialchars($post['title']) ?></p>
                                            <p class="text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($post['created_at'])) ?></p>
                                        </div>
                                        <div>
                                            <a href="../modules/blog/edit.php?id=<?= $post['id'] ?>"
                                                class="text-blue-600 hover:underline text-sm">Edit</a>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Events -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-4 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold">Recent Events</h2>
                            <a href="../modules/events/" class="text-sm text-green-600 hover:underline">View All</a>
                        </div>
                        <div class="p-4">
                            <?php if (empty($recent_events)): ?>
                            <p class="text-gray-500 text-center py-4">No events found.</p>
                            <?php else: ?>
                            <ul class="divide-y">
                                <?php foreach ($recent_events as $event): ?>
                                <li class="py-3">
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="font-medium"><?= htmlspecialchars($event['name']) ?></p>
                                            <p class="text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($event['start_date'])) ?></p>
                                        </div>
                                        <div>
                                            <a href="../modules/events/edit.php?id=<?= $event['id'] ?>"
                                                class="text-green-600 hover:underline text-sm">Edit</a>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white p-4 border-t text-center text-sm text-gray-600">
                &copy; <?= date('Y') ?> Constituency Issue Report Management. All rights reserved.
            </footer>
        </div>
    </div>


</body>

</html>