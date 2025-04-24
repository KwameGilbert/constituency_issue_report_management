<?php
require_once '../../includes/auth.php';
require_once '../../../config/db.php';

// Handle deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $event_id = (int) $_GET['delete'];
    
    // Get image path before deletion to remove the file
    $image_result = $conn->query("SELECT image_url FROM events WHERE id = $event_id");
    if ($image_result && $image_result->num_rows > 0) {
        $image_path = $image_result->fetch_assoc()['image_url'];
        // Remove the image file if it exists
        if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
        }
    }
    
    // Delete the event
    $conn->query("DELETE FROM events WHERE id = $event_id");
    
    // Set notification message
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Event deleted successfully!'
    ];
    
    // Redirect to refresh the page
    header("Location: index.php");
    exit;
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total events count
$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$total_pages = ceil($total_events / $limit);

// Search functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE name LIKE '%$search%' OR location LIKE '%$search%' OR description LIKE '%$search%'";
}

// Filter by date
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$date_condition = '';
if ($filter === 'upcoming') {
    $date_condition = $search_condition ? " AND start_date >= CURDATE()" : "WHERE start_date >= CURDATE()";
} elseif ($filter === 'past') {
    $date_condition = $search_condition ? " AND start_date < CURDATE()" : "WHERE start_date < CURDATE()";
}

// Fetch events with pagination, search and filter
$query = "SELECT id, name, slug, location, image_url, start_date, end_date, event_time 
          FROM events 
          $search_condition $date_condition
          ORDER BY start_date DESC 
          LIMIT $offset, $limit";
$events = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Layout structure with sidebar -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar - same as dashboard -->
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
                        <h2 class="text-xl font-semibold text-gray-800">Events</h2>
                    </div>

                    <!-- User profile -->
                    <div class="flex items-center">
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none">
                                <span
                                    class="hidden md:block text-sm"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                                <div
                                    class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile sidebar (similar to dashboard) -->
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

                <!-- Header with search and create button -->
                <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h3 class="text-lg font-semibold">Manage Events</h3>
                            <p class="text-gray-500 text-sm">Create, edit and manage your events calendar</p>
                        </div>
                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                            <form action="" method="get" class="flex flex-wrap gap-2">
                                <div class="flex flex-1">
                                    <input type="text" name="search" placeholder="Search events..."
                                        value="<?= htmlspecialchars($search) ?>"
                                        class="border rounded-l px-4 py-2 focus:outline-none focus:ring-1 focus:ring-green-500 min-w-0 flex-1">
                                    <button type="submit"
                                        class="bg-green-50 text-green-600 px-4 rounded-r border border-l-0 hover:bg-green-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <select name="filter" class="border rounded px-4 py-2 text-gray-700"
                                    onchange="this.form.submit()">
                                    <option value="" <?= $filter === '' ? 'selected' : '' ?>>All Events</option>
                                    <option value="upcoming" <?= $filter === 'upcoming' ? 'selected' : '' ?>>Upcoming
                                    </option>
                                    <option value="past" <?= $filter === 'past' ? 'selected' : '' ?>>Past</option>
                                </select>
                            </form>
                            <a href="create.php"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 inline-flex items-center justify-center whitespace-nowrap">
                                <i class="fas fa-plus mr-2"></i> New Event
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Events table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (empty($events) && empty($search) && empty($filter)): ?>
                    <div class="p-8 text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Events Yet</h3>
                        <p class="text-gray-500 mb-6">Get started by creating your first event.</p>
                        <a href="create.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i> Create Event
                        </a>
                    </div>
                    <?php elseif (empty($events)): ?>
                    <div class="p-8 text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-600 mb-4">
                            <i class="fas fa-search text-xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Results Found</h3>
                        <p class="text-gray-500 mb-6">No events match your search criteria.</p>
                        <a href="index.php" class="text-green-600 hover:underline">
                            <i class="fas fa-arrow-left mr-2"></i> Back to All Events
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Event</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date & Time</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Location</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($events as $event): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if (!empty($event['image_url'])): ?>
                                                <img class="h-10 w-10 object-cover rounded"
                                                    src="<?= htmlspecialchars($event['image_url']) ?>" alt="">
                                                <?php else: ?>
                                                <div
                                                    class="h-10 w-10 rounded bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($event['name']) ?></div>
                                                <div class="text-sm text-gray-500 truncate max-w-xs">
                                                    <?= htmlspecialchars(substr($event['location'], 0, 50)) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>
                                            <span
                                                class="font-medium"><?= date('M d, Y', strtotime($event['start_date'])) ?></span>
                                            <?php if (!empty($event['end_date']) && $event['end_date'] != $event['start_date']): ?>
                                            - <?= date('M d, Y', strtotime($event['end_date'])) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?= !empty($event['event_time']) ? date('h:i A', strtotime($event['event_time'])) : 'All day' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($event['location']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (strtotime($event['start_date']) >= strtotime('today')): ?>
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Upcoming
                                        </span>
                                        <?php else: ?>
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Past
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="/events/<?= $event['slug'] ?>"
                                            class="text-gray-600 hover:text-gray-900 mr-3" target="_blank">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $event['id'] ?>"
                                            class="text-green-600 hover:text-green-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="text-red-600 hover:text-red-900"
                                            onclick="confirmDelete(<?= $event['id'] ?>, '<?= addslashes($event['name']) ?>')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span
                                        class="font-medium"><?= min(($page - 1) * $limit + 1, $total_events) ?></span>
                                    to
                                    <span class="font-medium"><?= min($page * $limit, $total_events) ?></span> of
                                    <span class="font-medium"><?= $total_events ?></span> events
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                    aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter) ? '&filter=' . $filter : '' ?>"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter) ? '&filter=' . $filter : '' ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 
                                              <?= $i === $page ? 'bg-green-50 text-green-600' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
                                        <?= $i ?>
                                    </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter) ? '&filter=' . $filter : '' ?>"
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
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete the event "<span
                        id="eventName"></span>"? This action cannot be undone.</p>
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
    // Delete confirmation functionality
    function confirmDelete(id, name) {
        document.getElementById('eventName').textContent = name;
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
        }
    });
    </script>
</body>

</html>