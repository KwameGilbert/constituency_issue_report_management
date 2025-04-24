<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Handle deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $carousel_id = (int) $_GET['delete'];
    
    // Get image path before deletion to remove the file
    $image_result = $conn->query("SELECT image FROM homepage_carousel WHERE id = $carousel_id");
    if ($image_result && $image_result->num_rows > 0) {
        $image_path = $image_result->fetch_assoc()['image'];
        // Remove the image file if it exists
        if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
        }
    }
    
    // Delete the carousel item
    $conn->query("DELETE FROM homepage_carousel WHERE id = $carousel_id");
    
    // Set notification message
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Carousel item deleted successfully!'
    ];
    
    // Redirect to refresh the page
    header("Location: index.php");
    exit;
}

// Handle order updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $items = $_POST['items'];
    $success = true;
    
    foreach ($items as $position => $id) {
        $id = (int) $id;
        $position = (int) $position + 1; // Make position 1-based
        if (!$conn->query("UPDATE homepage_carousel SET position = $position WHERE id = $id")) {
            $success = false;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total carousel items count
$total_items = $conn->query("SELECT COUNT(*) as count FROM homepage_carousel")->fetch_assoc()['count'];
$total_pages = ceil($total_items / $limit);

// Fetch carousel items with pagination
$carousel_items = $conn->query("SELECT id, title, image, link, created_at FROM homepage_carousel ORDER BY position, created_at DESC LIMIT $offset, $limit")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Management | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Layout structure with sidebar -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (same as other modules) -->
        <?php require_once '../../includes/desktop_sidebar.php';?>

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
                        <h2 class="text-xl font-semibold text-gray-800">Carousel Management</h2>
                    </div>

                    <!-- User profile -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span
                                    class="hidden md:block text-sm"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                                <div
                                    class="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile sidebar (hidden) -->
            <?php require_once '../../includes/mobile_sidebar.php';?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto p-4">
                <!-- Notification message -->
                <?php if (isset($_SESSION['notification'])): ?>
                <div
                    class="mb-4 p-4 rounded-md <?= $_SESSION['notification']['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= $_SESSION['notification']['message'] ?>
                    <button class="float-right focus:outline-none" onclick="this.parentElement.style.display='none';">
                        &times;
                    </button>
                </div>
                <?php unset($_SESSION['notification']); endif; ?>

                <!-- Header with create button -->
                <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h3 class="text-lg font-semibold">Homepage Carousel</h3>
                            <p class="text-gray-500 text-sm">Manage sliding images that appear on the homepage</p>
                        </div>
                        <a href="create.php"
                            class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 inline-flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i> Add Carousel Item
                        </a>
                    </div>
                </div>

                <!-- Carousel items -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($carousel_items)): ?>
                    <div class="p-8 text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-100 text-purple-600 mb-4">
                            <i class="fas fa-images text-xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Carousel Items Yet</h3>
                        <p class="text-gray-500 mb-6">Add slideshow images to your homepage by creating your first
                            carousel item.</p>
                        <a href="create.php" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                            <i class="fas fa-plus mr-2"></i> Add Carousel Item
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="p-4 border-b">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Drag and drop items to change their display order on the homepage.
                        </p>
                    </div>
                    <ul id="sortable-carousel" class="divide-y divide-gray-200">
                        <?php foreach ($carousel_items as $item): ?>
                        <li class="carousel-item flex items-center px-4 py-3 hover:bg-gray-50"
                            data-id="<?= $item['id'] ?>">
                            <div class="flex-shrink-0 cursor-move handle mr-3 text-gray-400">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <div class="h-16 w-28 flex-shrink-0 bg-gray-100 rounded overflow-hidden mr-4">
                                <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['title']) ?>" class="h-full w-full object-cover">
                                <?php else: ?>
                                <div class="h-full w-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 truncate">
                                    <?= htmlspecialchars($item['title']) ?></h4>
                                <?php if (!empty($item['link'])): ?>
                                <p class="text-xs text-gray-500 truncate">
                                    <i class="fas fa-link mr-1"></i>
                                    <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank"
                                        class="hover:underline"><?= htmlspecialchars($item['link']) ?></a>
                                </p>
                                <?php else: ?>
                                <p class="text-xs text-gray-500">No link</p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    Added <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                </p>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <a href="edit.php?id=<?= $item['id'] ?>"
                                    class="text-purple-600 hover:text-purple-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" class="text-red-600 hover:text-red-900"
                                    onclick="confirmDelete(<?= $item['id'] ?>, '<?= addslashes($item['title']) ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span
                                        class="font-medium"><?= min(($page - 1) * $limit + 1, $total_items) ?></span> to
                                    <span class="font-medium"><?= min($page * $limit, $total_items) ?></span> of
                                    <span class="font-medium"><?= $total_items ?></span> items
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                    aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?= $i ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 
                                              <?= $i === $page ? 'bg-purple-50 text-purple-600' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                                        <?= $i ?>
                                    </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Deletion confirmation modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete the carousel item "<span
                        id="carouselItemTitle"></span>"? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="hideDeleteModal()"
                        class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">Cancel</button>
                    <a id="deleteLink" href="#"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Mobile menu functionality
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileSidebar = document.getElementById('mobile-sidebar');

    if (mobileMenuButton && mobileSidebar) {
        mobileMenuButton.addEventListener('click', function() {
            mobileSidebar.classList.toggle('hidden');
        });
    }

    // Delete confirmation functionality
    function confirmDelete(id, title) {
        document.getElementById('carouselItemTitle').textContent = title;
        document.getElementById('deleteLink').href = 'index.php?delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideDeleteModal();
        });
    });

    // Sortable functionality for drag-and-drop reordering
    const sortableList = document.getElementById('sortable-carousel');
    if (sortableList) {
        const sortable = new Sortable(sortableList, {
            animation: 150,
            handle: '.handle',
            ghostClass: 'bg-purple-100',
            onEnd: function() {
                // Get the new order of items
                const items = Array.from(sortableList.children).map(item => item.dataset.id);

                // Send the new order to the server via AJAX
                fetch('index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'update_order',
                            'items': items
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show a brief success message
                            const notification = document.createElement('div');
                            notification.className =
                                'fixed top-4 right-4 bg-green-100 text-green-800 p-4 rounded-md shadow-md z-50';
                            notification.textContent = 'Order updated successfully';
                            document.body.appendChild(notification);

                            setTimeout(() => {
                                notification.remove();
                            }, 3000);
                        }
                    });
            }
        });
    }
    </script>
</body>

</html>