<?php
session_start();

// Authentication check
if (!isset($_SESSION['pa_id']) || $_SESSION['role'] !== 'pa') {
    header("Location: ../login/");
    exit();
}

// Include database connection
require_once '../../../config/db.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$severity = isset($_GET['severity']) ? $_GET['severity'] : '';
$electoral_area = isset($_GET['electoral_area']) ? (int)$_GET['electoral_area'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query conditions based on filters
$conditions = [];
$params = [];
$param_types = '';

if (!empty($status)) {
    $conditions[] = "i.status = ?";
    $params[] = $status;
    $param_types .= 's';
}

if (!empty($severity)) {
    $conditions[] = "i.severity = ?";
    $params[] = $severity;
    $param_types .= 's';
}

if (!empty($electoral_area)) {
    $conditions[] = "i.electoral_area_id = ?";
    $params[] = $electoral_area;
    $param_types .= 'i';
}

if (!empty($search)) {
    $search = "%$search%";
    $conditions[] = "(i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $param_types .= 'sss';
}

if (!empty($start_date)) {
    $conditions[] = "i.created_at >= ?";
    $params[] = $start_date . ' 00:00:00';
    $param_types .= 's';
}

if (!empty($end_date)) {
    $conditions[] = "i.created_at <= ?";
    $params[] = $end_date . ' 23:59:59';
    $param_types .= 's';
}

// Build the WHERE clause
$where_clause = '';
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Build the ORDER BY clause
$order_clause = match($sort) {
    'oldest' => "ORDER BY i.created_at ASC",
    'severity_high' => "ORDER BY FIELD(i.severity, 'critical', 'high', 'medium', 'low')",
    'severity_low' => "ORDER BY FIELD(i.severity, 'low', 'medium', 'high', 'critical')",
    'title_asc' => "ORDER BY i.title ASC",
    'title_desc' => "ORDER BY i.title DESC",
    default => "ORDER BY i.created_at DESC"
};

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM issues i $where_clause";
$count_stmt = $conn->prepare($count_query);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get issues with pagination
$query = "SELECT 
            i.id, i.title, i.description, i.location, i.status, i.severity, 
            i.created_at, i.updated_at, i.resolved_at, i.people_affected,
            ea.name as electoral_area_name,
            fo.name as officer_name
          FROM issues i
          LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
          LEFT JOIN field_officers fo ON i.officer_id = fo.id
          $where_clause
          $order_clause
          LIMIT $offset, $records_per_page";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get all electoral areas for filter dropdown
$areas_query = "SELECT id, name FROM electoral_areas ORDER BY name";
$areas_result = $conn->query($areas_query);
$electoral_areas = [];
while ($area = $areas_result->fetch_assoc()) {
    $electoral_areas[] = $area;
}

$page_title = "Issues Management - PA Portal";
include_once '../includes/header.php';
?>

<div class="p-4 sm:ml-64 mt-14">

    <!-- Page Title and Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Issues Management</h1>
            <p class="mt-1 text-gray-600">Review, update, and manage all reported issues</p>
        </div>

        <div class="mt-4 md:mt-0">
            <button type="button" id="toggleFilters"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <i class="fas fa-filter mr-2"></i> Filters
            </button>
        </div>
    </div>

    <!-- Filters Section (toggleable) -->
    <div id="filtersContainer"
        class="bg-white rounded-lg shadow-md p-4 mb-6 <?= !empty($status) || !empty($severity) || !empty($electoral_area) || !empty($search) || !empty($start_date) || !empty($end_date) ? '' : 'hidden' ?>">
        <h2 class="text-lg font-medium text-gray-800 mb-4">Filter Issues</h2>

        <form action="" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="under_review" <?= $status === 'under_review' ? 'selected' : '' ?>>Under
                            Review</option>
                        <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress
                        </option>
                        <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <!-- Severity Filter -->
                <div>
                    <label for="severity" class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                    <select name="severity" id="severity"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        <option value="">All Severities</option>
                        <option value="critical" <?= $severity === 'critical' ? 'selected' : '' ?>>Critical</option>
                        <option value="high" <?= $severity === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="medium" <?= $severity === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="low" <?= $severity === 'low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>

                <!-- Electoral Area Filter -->
                <div>
                    <label for="electoral_area" class="block text-sm font-medium text-gray-700 mb-1">Electoral
                        Area</label>
                    <select name="electoral_area" id="electoral_area"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        <option value="">All Areas</option>
                        <?php foreach ($electoral_areas as $area): ?>
                        <option value="<?= $area['id'] ?>" <?= $electoral_area == $area['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($area['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search titles, descriptions, locations..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Date Range -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort" id="sort"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-500 focus:ring-opacity-50">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="severity_high" <?= $sort === 'severity_high' ? 'selected' : '' ?>>Severity
                            (High to Low)</option>
                        <option value="severity_low" <?= $sort === 'severity_low' ? 'selected' : '' ?>>Severity (Low
                            to High)</option>
                        <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Title (A-Z)
                        </option>
                        <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Title (Z-A)
                        </option>
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        <i class="fas fa-search mr-1"></i> Apply Filters
                    </button>
                    <a href="index.php"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Applied Filters Display -->
    <?php if (!empty($status) || !empty($severity) || !empty($electoral_area) || !empty($search) || !empty($start_date) || !empty($end_date)): ?>
    <div class="bg-gray-50 rounded-lg p-3 mb-6 border border-gray-200">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-gray-700">Active Filters:</span>

            <?php if (!empty($status)): ?>
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Status: <?= ucfirst(str_replace('_', ' ', $status)) ?>
            </span>
            <?php endif; ?>

            <?php if (!empty($severity)): ?>
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                Severity: <?= ucfirst($severity) ?>
            </span>
            <?php endif; ?>

            <?php if (!empty($electoral_area)): 
                    $area_name = '';
                    foreach ($electoral_areas as $area) {
                        if ($area['id'] == $electoral_area) {
                            $area_name = $area['name'];
                            break;
                        }
                    }
                ?>
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                Area: <?= htmlspecialchars($area_name) ?>
            </span>
            <?php endif; ?>

            <?php if (!empty($search)): ?>
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Search:
                <?= htmlspecialchars(substr(trim($search, '%'), 0, 20)) ?><?= strlen(trim($search, '%')) > 20 ? '...' : '' ?>
            </span>
            <?php endif; ?>

            <?php if (!empty($start_date) || !empty($end_date)): ?>
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                Date: <?= !empty($start_date) ? $start_date : 'Any' ?> to
                <?= !empty($end_date) ? $end_date : 'Any' ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Issues Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if ($result->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Issue
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Location
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Severity
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reported By
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($issue = $result->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div>
                                <a href="view.php?id=<?= $issue['id'] ?>"
                                    class="text-sm font-medium text-gray-900 hover:text-green-600">
                                    <?= htmlspecialchars($issue['title']) ?>
                                </a>
                                <div class="text-xs text-gray-500 mt-1">
                                    ID: <?= $issue['id'] ?>
                                    <span class="ml-2">
                                        <i class="fas fa-users text-gray-400"></i>
                                        <?= number_format($issue['people_affected'] ?? 0) ?> people affected
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                <?= htmlspecialchars($issue['location']) ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= htmlspecialchars($issue['electoral_area_name'] ?? 'N/A') ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                                        $status_class = match($issue['status']) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'under_review' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-purple-100 text-purple-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        $status_text = match($issue['status']) {
                                            'pending' => 'Pending',
                                            'under_review' => 'Under Review',
                                            'in_progress' => 'In Progress',
                                            'resolved' => 'Resolved',
                                            'rejected' => 'Rejected',
                                            default => 'Unknown'
                                        };
                                        ?>
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                        $severity_class = match($issue['severity']) {
                                            'critical' => 'bg-red-100 text-red-800',
                                            'high' => 'bg-orange-100 text-orange-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'low' => 'bg-green-100 text-green-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        $severity_text = ucfirst($issue['severity']);
                                        ?>
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $severity_class ?>">
                                <?= $severity_text ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?= htmlspecialchars($issue['officer_name'] ?? 'Unknown') ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?= date('M d, Y', strtotime($issue['created_at'])) ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= date('h:i A', strtotime($issue['created_at'])) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="view.php?id=<?= $issue['id'] ?>" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($issue['status'] == 'pending'): ?>
                            <a href="update-status.php?id=<?= $issue['id'] ?>&status=under_review"
                                class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
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
                            class="font-medium"><?= min(($page - 1) * $records_per_page + 1, $total_records) ?></span>
                        to
                        <span class="font-medium"><?= min($page * $records_per_page, $total_records) ?></span> of
                        <span class="font-medium"><?= $total_records ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <!-- Previous Page Link -->
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&severity=<?= urlencode($severity) ?>&electoral_area=<?= urlencode($electoral_area) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&sort=<?= urlencode($sort) ?>"
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

                        <!-- Page Numbers -->
                        <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    if ($start_page > 1) {
                                        echo '<a href="?page=1&status='.urlencode($status).'&severity='.urlencode($severity).'&electoral_area='.urlencode($electoral_area).'&search='.urlencode($search).'&start_date='.urlencode($start_date).'&end_date='.urlencode($end_date).'&sort='.urlencode($sort).'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }

                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        if ($i == $page) {
                                            echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-green-500 bg-green-50 text-sm font-medium text-green-600">'.$i.'</span>';
                                        } else {
                                            echo '<a href="?page='.$i.'&status='.urlencode($status).'&severity='.urlencode($severity).'&electoral_area='.urlencode($electoral_area).'&search='.urlencode($search).'&start_date='.urlencode($start_date).'&end_date='.urlencode($end_date).'&sort='.urlencode($sort).'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$i.'</a>';
                                        }
                                    }

                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?page='.$total_pages.'&status='.urlencode($status).'&severity='.urlencode($severity).'&electoral_area='.urlencode($electoral_area).'&search='.urlencode($search).'&start_date='.urlencode($start_date).'&end_date='.urlencode($end_date).'&sort='.urlencode($sort).'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
                                    }
                                    ?>

                        <!-- Next Page Link -->
                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&severity=<?= urlencode($severity) ?>&electoral_area=<?= urlencode($electoral_area) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&sort=<?= urlencode($sort) ?>"
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
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="p-6 text-center">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No issues found</h3>
            <p class="mt-1 text-sm text-gray-500">
                <?php if (!empty($status) || !empty($severity) || !empty($electoral_area) || !empty($search) || !empty($start_date) || !empty($end_date)): ?>
                No issues match your filters. Try adjusting your search criteria.
                <?php else: ?>
                There are no issues to display at this time.
                <?php endif; ?>
            </p>
            <?php if (!empty($status) || !empty($severity) || !empty($electoral_area) || !empty($search) || !empty($start_date) || !empty($end_date)): ?>
            <div class="mt-4">
                <a href="index.php"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-times mr-2"></i> Clear All Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filters container
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const filtersContainer = document.getElementById('filtersContainer');

    toggleFiltersBtn.addEventListener('click', function() {
        filtersContainer.classList.toggle('hidden');
    });
});
</script>

<?php 
// include_once '../includes/footer.php'; 
?>