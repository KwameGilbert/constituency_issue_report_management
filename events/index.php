<?php
require_once '../config/db.php';

// Handle filtering
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
// Remove type filter since there's no event_type column
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    // Change title to name, as per database schema
    $where[] = "(name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($month > 0 && $year > 0) {
    $where[] = "MONTH(start_date) = ? AND YEAR(start_date) = ?";
    $params[] = $month;
    $params[] = $year;
    $param_types .= 'ii';
} elseif ($month > 0) {
    $where[] = "MONTH(start_date) = ?";
    $params[] = $month;
    $param_types .= 'i';
} elseif ($year > 0) {
    $where[] = "YEAR(start_date) = ?";
    $params[] = $year;
    $param_types .= 'i';
}

// Remove the event_type filter

// Get current date
$current_date = date('Y-m-d');

// Pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$events_per_page = 10;
$offset = ($current_page - 1) * $events_per_page;

// Prepare the WHERE clause
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total events for pagination
$count_sql = "SELECT COUNT(*) as total FROM events $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$total_events = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_events / $events_per_page);

// Fetch events with pagination
$sql = "SELECT * FROM events $where_clause ORDER BY start_date DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $offset;
$params[] = $events_per_page;
$param_types .= 'ii';

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch upcoming events for sidebar
$upcoming_events_query = "SELECT * FROM events WHERE start_date >= ? ORDER BY start_date ASC LIMIT 5";
$upcoming_stmt = $conn->prepare($upcoming_events_query);
$upcoming_stmt->bind_param('s', $current_date);
$upcoming_stmt->execute();
$upcoming_events = $upcoming_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Remove event_types query since there's no such column
// Instead, get unique slugs to use as "types" (if needed)
$event_years = $conn->query("SELECT DISTINCT YEAR(start_date) as year FROM events ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);

// Get counts for stats
$upcoming_count = $conn->query("SELECT COUNT(*) as count FROM events WHERE start_date >= '$current_date'")->fetch_assoc()['count'];
$past_count = $conn->query("SELECT COUNT(*) as count FROM events WHERE start_date < '$current_date'")->fetch_assoc()['count'];
$total_count = $upcoming_count + $past_count;

// Helper function to format date
function formatEventDate($date, $include_year = true) {
    $timestamp = strtotime($date);
    return $include_year ? date('F j, Y', $timestamp) : date('F j', $timestamp);
}

// Helper function to get month name
function getMonthName($month) {
    return date('F', mktime(0, 0, 0, $month, 1));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events | Sefwi Wiawso Constituency</title>
    <meta name="description" content="Events, gatherings, and activities in the Sefwi Wiawso Constituency">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php include_once '../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="bg-red-700 text-white py-12 md:py-20">
            <div class="max-w-6xl mx-auto px-4">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Constituency Events</h1>
                <p class="text-lg md:text-xl max-w-3xl">Stay informed about upcoming gatherings, celebrations, town
                    halls, and other activities across Sefwi Wiawso Constituency</p>
            </div>
        </section>

        <!-- Event Stats -->
        <section class="bg-white shadow-md py-6 border-b">
            <div class="max-w-6xl mx-auto px-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-red-700"><?= $upcoming_count ?></span>
                        <span class="text-sm text-gray-500">Upcoming Events</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-red-700"><?= $past_count ?></span>
                        <span class="text-sm text-gray-500">Past Events</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-red-700"><?= $total_count ?></span>
                        <span class="text-sm text-gray-500">Total Events</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="max-w-6xl mx-auto px-4 py-12">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Main Content -->
                <div class="lg:w-2/3">
                    <!-- Search and Filters -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                        <h2 class="text-xl font-semibold mb-4">Find Events</h2>
                        <form action="" method="GET" class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:gap-4">
                            <!-- Search -->
                            <div class="md:w-full lg:w-full xl:w-1/3">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Search events"
                                    class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            </div>

                            <!-- Month filter -->
                            <div class="md:w-1/2 lg:w-1/3">
                                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                <select name="month" id="month"
                                    class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                                    <option value="0">All Months</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $month === $i ? 'selected' : '' ?>>
                                        <?= getMonthName($i) ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- Year filter -->
                            <div class="md:w-1/2 lg:w-1/3">
                                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                                <select name="year" id="year"
                                    class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                                    <option value="0">All Years</option>
                                    <?php foreach ($event_years as $yr): ?>
                                    <option value="<?= $yr['year'] ?>" <?= $year == $yr['year'] ? 'selected' : '' ?>>
                                        <?= $yr['year'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Filter buttons -->
                            <div class="md:w-full flex items-end mt-4">
                                <button type="submit"
                                    class="px-4 py-2 bg-red-700 text-white rounded hover:bg-red-800 transition-colors">
                                    <i class="fas fa-search mr-2"></i> Apply Filters
                                </button>
                                <?php if (!empty($search) || $month > 0 || $year > 0): ?>
                                <a href="index.php"
                                    class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
                                    <i class="fas fa-times mr-2"></i> Clear Filters
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Event List -->
                    <h2 class="text-2xl font-semibold mb-6">
                        <?= !empty($search) || $month > 0 || $year > 0 ? 'Filtered Events' : 'All Events' ?>
                        <span class="text-gray-500 text-lg">(<?= $total_events ?>)</span>
                    </h2>

                    <?php if (count($events) > 0): ?>
                    <div class="space-y-6">
                        <?php foreach ($events as $event): ?>
                        <?php 
                                $is_upcoming = strtotime($event['start_date']) >= strtotime($current_date);
                                $status_class = $is_upcoming ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                $status_text = $is_upcoming ? 'Upcoming' : 'Past';
                            ?>
                        <div
                            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="md:flex">
                                <?php if (!empty($event['image_url'])): ?>
                                <div class="md:w-1/3">
                                    <div class="h-48 md:h-full bg-cover bg-center"
                                        style="background-image: url('<?= htmlspecialchars($event['image_url']) ?>')">
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="p-6 md:w-2/3">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="text-xl font-bold">
                                                <?= htmlspecialchars($event['name']) ?>
                                            </h3>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center text-sm text-gray-500 mb-3">
                                        <i class="far fa-calendar-alt mr-2"></i>
                                        <span><?= formatEventDate($event['start_date']) ?></span>
                                        <?php if (!empty($event['event_time'])): ?>
                                        <span class="mx-2">•</span>
                                        <i class="far fa-clock mr-2"></i>
                                        <span><?= htmlspecialchars($event['event_time']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($event['location'])): ?>
                                    <div class="flex items-center text-sm text-gray-500 mb-3">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        <span><?= htmlspecialchars($event['location']) ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <p class="text-gray-600 mb-4">
                                        <?= htmlspecialchars(substr($event['description'], 0, 150)) ?>...
                                    </p>

                                    <a href="event-detail.php?id=<?= $event['id'] ?>"
                                        class="inline-block text-red-700 font-medium hover:text-red-900 hover:underline">
                                        View Event Details <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-8">
                        <nav class="flex justify-center">
                            <ul class="flex">
                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $month > 0 ? '&month=' . $month : '' ?><?= $year > 0 ? '&year=' . $year : '' ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </a>
                                <?php else: ?>
                                <span
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </span>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                if ($start_page > 1) {
                                    echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . ($month > 0 ? '&month=' . $month : '') . ($year > 0 ? '&year=' . $year : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                    
                                    if ($start_page > 2) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    if ($i == $current_page) {
                                        echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-red-500 bg-red-50 text-sm font-medium text-red-600">' . $i . '</span>';
                                    } else {
                                        echo '<a href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . ($month > 0 ? '&month=' . $month : '') . ($year > 0 ? '&year=' . $year : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $i . '</a>';
                                    }
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                    echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . ($month > 0 ? '&month=' . $month : '') . ($year > 0 ? '&year=' . $year : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                }
                                ?>

                                <!-- Next page -->
                                <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $month > 0 ? '&month=' . $month : '' ?><?= $year > 0 ? '&year=' . $year : '' ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </a>
                                <?php else: ?>
                                <span
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </span>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 text-red-600 mb-4">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No events found</h3>
                        <p class="text-gray-500 mb-6">
                            <?php if (!empty($search) || $month > 0 || $year > 0): ?>
                            No events match your search criteria. Try adjusting your filters.
                            <?php else: ?>
                            There are no events scheduled at the moment.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search) || $month > 0 || $year > 0): ?>
                        <a href="index.php"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i> Clear Filters
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="lg:w-1/3 space-y-8">
                    <!-- Upcoming Events -->
                    <?php if (count($upcoming_events) > 0): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Upcoming Events</h3>
                        <div class="space-y-4">
                            <?php foreach ($upcoming_events as $upcoming): ?>
                            <div class="flex gap-4">
                                <div
                                    class="flex-shrink-0 w-16 h-16 bg-red-100 rounded-lg flex flex-col items-center justify-center text-center">
                                    <span
                                        class="font-bold text-red-800 text-lg"><?= date('d', strtotime($upcoming['start_date'])) ?></span>
                                    <span
                                        class="text-xs text-red-700"><?= date('M', strtotime($upcoming['start_date'])) ?></span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-sm">
                                        <a href="event-detail.php?id=<?= $upcoming['id'] ?>" class="hover:text-red-700">
                                            <?= htmlspecialchars($upcoming['name']) ?>
                                        </a>
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= htmlspecialchars($upcoming['location']) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <?php if ($upcoming_count > count($upcoming_events)): ?>
                            <div class="mt-2 text-center">
                                <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>"
                                    class="text-red-700 text-sm hover:underline">
                                    View all upcoming events <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Calendar / Quick Filter -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Quick Filters</h3>
                        <div class="space-y-2">
                            <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>"
                                class="flex items-center justify-between p-2 rounded hover:bg-red-50 transition-colors">
                                <div class="flex items-center">
                                    <div
                                        class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar-alt text-green-600"></i>
                                    </div>
                                    <span>Upcoming Events</span>
                                </div>
                                <span class="text-gray-500"><?= $upcoming_count ?></span>
                            </a>
                            <a href="?year=<?= date('Y', strtotime('-1 year')) ?>"
                                class="flex items-center justify-between p-2 rounded hover:bg-red-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-history text-gray-600"></i>
                                    </div>
                                    <span>Past Events</span>
                                </div>
                                <span class="text-gray-500"><?= $past_count ?></span>
                            </a>
                            <?php 
                                $current_year = date('Y');
                                $current_year_count = $conn->query("SELECT COUNT(*) as count FROM events WHERE YEAR(start_date) = $current_year")->fetch_assoc()['count'];
                            ?>
                            <a href="?year=<?= $current_year ?>"
                                class="flex items-center justify-between p-2 rounded hover:bg-red-50 transition-colors">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="far fa-calendar-check text-blue-600"></i>
                                    </div>
                                    <span>Events in <?= $current_year ?></span>
                                </div>
                                <span class="text-gray-500"><?= $current_year_count ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Newsletter Subscription -->
                    <div class="bg-red-50 rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-2">Get Event Updates</h3>
                        <p class="text-sm text-gray-600 mb-4">Subscribe to receive notifications about upcoming events
                        </p>
                        <form action="#" method="post" class="space-y-3">
                            <div>
                                <input type="email" name="email" placeholder="Your email address" required
                                    class="w-full px-3 py-2 border border-red-300 rounded-md focus:outline-none focus:ring-1 focus:ring-red-500">
                            </div>
                            <button type="submit"
                                class="w-full px-4 py-2 bg-red-700 text-white rounded hover:bg-red-800 transition-colors">
                                Subscribe <i class="fas fa-paper-plane ml-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when specific filters change
        const autoSubmitFilters = document.querySelectorAll('#month, #year');

        autoSubmitFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    });
    </script>
</body>

</html>