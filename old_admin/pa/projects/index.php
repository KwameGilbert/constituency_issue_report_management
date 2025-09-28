<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit;
}

require_once '../../../config/db.php';

// Get the current page for pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Default filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sector_filter = isset($_GET['sector']) ? $_GET['sector'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query based on filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($sector_filter)) {
    $where_conditions[] = "sector = ?";
    $params[] = $sector_filter;
    $param_types .= 's';
}

if (!empty($location_filter)) {
    $where_conditions[] = "location LIKE ?";
    $params[] = "%$location_filter%";
    $param_types .= 's';
}

if (!empty($search_term)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $param_types .= 'sss';
}

if (!empty($date_from)) {
    $where_conditions[] = "start_date >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "end_date <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

// Construct the WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM projects $where_clause";
$stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get projects with filters and pagination
$query = "SELECT * FROM projects $where_clause ORDER BY created_at DESC LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $records_per_page;
$param_types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get distinct sectors and locations for filter dropdowns
$sectors_query = "SELECT DISTINCT sector FROM projects ORDER BY sector";
$sectors_result = $conn->query($sectors_query);

$locations_query = "SELECT DISTINCT location FROM projects ORDER BY location";
$locations_result = $conn->query($locations_query);

$page_title = "Projects Management - PA Portal";
include '../includes/header.php';
?>

<div class="p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Page Title and Actions -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Constituency Projects</h1>
                <p class="mt-1 text-gray-600">Manage and track development projects in your constituency</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="create.php"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Project
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-800">
                    <i class="fas fa-filter mr-2 text-gray-500"></i>Filter Projects
                </h2>
                <?php if (isset($_GET['filter'])): ?>
                <a href="index.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times mr-1"></i> Clear Filters
                </a>
                <?php endif; ?>
            </div>
            <div class="p-4">
                <form action="" method="GET">
                    <input type="hidden" name="filter" value="true">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <!-- Search box -->
                        <div class="col-span-1 md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                id="search" name="search" placeholder="Search by title, description or location"
                                value="<?= htmlspecialchars($search_term) ?>">
                        </div>

                        <!-- Status filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="planned" <?= $status_filter === 'planned' ? 'selected' : '' ?>>Planned
                                </option>
                                <option value="ongoing" <?= $status_filter === 'ongoing' ? 'selected' : '' ?>>Ongoing
                                </option>
                                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>
                                    Completed</option>
                            </select>
                        </div>

                        <!-- Sector filter -->
                        <div>
                            <label for="sector" class="block text-sm font-medium text-gray-700 mb-1">Sector</label>
                            <select
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                id="sector" name="sector">
                                <option value="">All Sectors</option>
                                <?php while ($sector = $sectors_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($sector['sector']) ?>"
                                    <?= $sector_filter === $sector['sector'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sector['sector']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- Location filter -->
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <select
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                id="location" name="location">
                                <option value="">All Locations</option>
                                <?php while ($location = $locations_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($location['location']) ?>"
                                    <?= $location_filter === $location['location'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($location['location']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Date range -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From
                                Date</label>
                            <input type="date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>

                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                    </div>

                    <!-- Filter action buttons -->
                    <div class="flex flex-wrap gap-2">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-search mr-2"></i> Apply Filters
                        </button>
                        <a href="index.php"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-sync-alt mr-2"></i> Reset
                        </a>
                        <button type="button" id="exportBtn"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-file-export mr-2"></i> Export Results
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Projects Grid -->
        <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($project = $result->fetch_assoc()): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <!-- Project Status Badge -->
                <div class="relative">
                    <?php 
                    $status_class = match($project['status']) {
                        'planned' => 'bg-blue-100 text-blue-800',
                        'ongoing' => 'bg-yellow-100 text-yellow-800',
                        'completed' => 'bg-green-100 text-green-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                    ?>
                    <span
                        class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                        <?= ucfirst($project['status']) ?>
                    </span>

                    <!-- Project Image -->
                    <?php 
                    $image_url = '../../../assets/images/projects/default-project.jpg';
                    if (!empty($project['images'])) {
                        $images = json_decode($project['images'], true);
                        if (!empty($images) && isset($images[0])) {
                            $image_url = $images[0];
                        }
                    }
                    ?>
                    <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($project['title']) ?>"
                        class="w-full h-48 object-cover">
                </div>

                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 truncate"
                        title="<?= htmlspecialchars($project['title']) ?>">
                        <?= htmlspecialchars($project['title']) ?>
                    </h3>

                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span class="truncate"><?= htmlspecialchars($project['location']) ?></span>
                    </div>

                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span>
                            <?= date('M d, Y', strtotime($project['start_date'])) ?>
                            <?php if ($project['end_date']): ?>
                            - <?= date('M d, Y', strtotime($project['end_date'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                        <?= htmlspecialchars(substr($project['description'], 0, 150)) ?>...
                    </p>

                    <div class="mt-4 border-t pt-4 flex justify-between items-center">
                        <div class="text-xs text-gray-500">
                            Sector: <span class="font-medium"><?= htmlspecialchars($project['sector']) ?></span>
                        </div>

                        <div class="flex gap-2">
                            <a href="view.php?id=<?= $project['id'] ?>"
                                class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                            <a href="edit.php?id=<?= $project['id'] ?>"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="upload-photos.php?id=<?= $project['id'] ?>"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200">
                                <i class="fas fa-images"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div
                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
                <i class="fas fa-project-diagram text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No projects found</h3>
            <p class="text-gray-500 mb-6">
                <?php if (isset($_GET['filter'])): ?>
                No projects match your filter criteria. Try adjusting your filters.
                <?php else: ?>
                You haven't added any projects yet.
                <?php endif; ?>
            </p>
            <?php if (isset($_GET['filter'])): ?>
            <a href="index.php"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-times mr-2"></i> Clear Filters
            </a>
            <?php else: ?>
            <a href="create.php"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                <i class="fas fa-plus-circle mr-2"></i> Create Your First Project
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-6">
            <nav class="flex justify-center">
                <ul class="flex">
                    <!-- Previous page -->
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left h-5 w-5"></i>
                    </a>
                    <?php else: ?>
                    <span
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left h-5 w-5"></i>
                    </span>
                    <?php endif; ?>

                    <!-- Page numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo '<a href="?page=1' . (isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                        if ($start_page > 2) {
                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-green-500 bg-green-50 text-sm font-medium text-green-600">' . $i . '</span>';
                        } else {
                            echo '<a href="?page=' . $i . (isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $i . '</a>';
                        }
                    }

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                        echo '<a href="?page=' . $total_pages . (isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                    }
                    ?>

                    <!-- Next page -->
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= isset($_GET['filter']) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right h-5 w-5"></i>
                    </a>
                    <?php else: ?>
                    <span
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right h-5 w-5"></i>
                    </span>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        // Get current filter parameters
        const urlParams = new URLSearchParams(window.location.search);
        let exportUrl = 'export-projects.php';

        // Add filters to export URL
        if (urlParams.has('filter')) {
            exportUrl += '?' + window.location.search.substr(1);
        }

        window.location.href = exportUrl;
    });
});
</script>

<?php include '../includes/footer.php'; ?>