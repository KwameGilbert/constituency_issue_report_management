<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit;
}

// Include database connection
require_once '../../../config/db.php';

// Set active page and title for sidebar
$active_page = 'issues';
$pageTitle = 'Issue Management';
$basePath = '../';

// Get officer details
$officer_id = $_SESSION['officer_id'];
$query = "SELECT * FROM field_officers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();
$stmt->close();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter setup
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$severity_filter = isset($_GET['severity']) ? $_GET['severity'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build WHERE clause
$where_clause = "i.officer_id = $officer_id";
if (!empty($status_filter)) {
    $where_clause .= " AND i.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($severity_filter)) {
    $where_clause .= " AND i.severity = '" . $conn->real_escape_string($severity_filter) . "'";
}
if (!empty($search_term)) {
    $search_term = $conn->real_escape_string($search_term);
    $where_clause .= " AND (i.title LIKE '%$search_term%' OR i.description LIKE '%$search_term%' OR i.location LIKE '%$search_term%')";
}

// Count total issues with filters
$count_query = "SELECT COUNT(*) as total FROM issues i WHERE $where_clause";
$count_result = $conn->query($count_query);
$total_issues = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_issues / $limit);

// Fetch issues with pagination and filtering
$issues_query = "SELECT i.*, ea.name as area_name, 
                 (SELECT photo_url FROM issue_photos WHERE issue_id = i.id LIMIT 1) as thumbnail 
                 FROM issues i 
                 LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
                 WHERE $where_clause 
                 ORDER BY CASE 
                     WHEN i.status = 'pending' THEN 1
                     WHEN i.status = 'under_review' THEN 2
                     WHEN i.status = 'in_progress' THEN 3
                     WHEN i.status = 'resolved' THEN 4
                     WHEN i.status = 'rejected' THEN 5
                 END, 
                 CASE 
                     WHEN i.severity = 'critical' THEN 1
                     WHEN i.severity = 'high' THEN 2
                     WHEN i.severity = 'medium' THEN 3
                     WHEN i.severity = 'low' THEN 4
                 END,
                 i.created_at DESC
                 LIMIT $offset, $limit";

$issues_result = $conn->query($issues_query);
$issues = [];
if ($issues_result) {
    while ($row = $issues_result->fetch_assoc()) {
        $issues[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Management | Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out forwards;
    }

    .hover-scale {
        transition: transform 0.2s ease-in-out;
    }

    .hover-scale:hover {
        transform: scale(1.01);
    }

    @keyframes slideInFromRight {
        from {
            transform: translateX(20px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .slide-in-right {
        animation: slideInFromRight 0.3s ease-out forwards;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar component -->
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header component -->
            <?php include_once '../includes/header.php'; ?>

            <!-- Overlay for mobile sidebar -->
            <div x-show="sidebarOpen" class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
                @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"></div>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Action Bar -->
                    <div
                        class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl shadow-lg mb-6 p-6 text-white fade-in">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <h1 class="text-2xl font-bold">Issue Management</h1>
                                <p class="mt-1 opacity-90">Manage and track constituency issues</p>
                            </div>
                            <a href="../create-issue/"
                                class="inline-flex items-center px-4 py-2 bg-white text-amber-800 rounded-lg font-medium shadow hover:bg-amber-50 transition-colors duration-300">
                                <i class="fas fa-plus mr-2"></i> Report New Issue
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 fade-in" style="animation-delay: 0.1s;">
                        <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 bg-white border border-gray-200 px-4 py-2">
                                    <option value="">All Statuses</option>
                                    <option value="pending"
                                        <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="under_review"
                                        <?php echo $status_filter == 'under_review' ? 'selected' : ''; ?>>Under Review
                                    </option>
                                    <option value="in_progress"
                                        <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress
                                    </option>
                                    <option value="resolved"
                                        <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="rejected"
                                        <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div>
                                <label for="severity"
                                    class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                                <select name="severity" id="severity"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 bg-white border border-gray-200 px-4 py-2">
                                    <option value="">All Severities</option>
                                    <option value="critical"
                                        <?php echo $severity_filter == 'critical' ? 'selected' : ''; ?>>Critical
                                    </option>
                                    <option value="high" <?php echo $severity_filter == 'high' ? 'selected' : ''; ?>>
                                        High</option>
                                    <option value="medium"
                                        <?php echo $severity_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="low" <?php echo $severity_filter == 'low' ? 'selected' : ''; ?>>Low
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" name="search" id="search"
                                    value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search issues..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 bg-white border border-gray-200 px-4 py-2">
                            </div>
                            <div class="flex items-end">
                                <button type="submit"
                                    class="w-full bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-300">
                                    <i class="fas fa-filter mr-2"></i> Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Issues List -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden fade-in" style="animation-delay: 0.2s;">
                        <?php if (empty($issues) && (empty($status_filter) && empty($severity_filter) && empty($search_term))): ?>
                        <div class="p-8 text-center">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                                <i class="fas fa-clipboard-list text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Issues Reported Yet</h3>
                            <p class="text-gray-500 mb-6">Start by reporting your first constituency issue</p>
                            <a href="../create-issue/"
                                class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg font-medium shadow hover:bg-amber-700 transition-colors duration-300">
                                <i class="fas fa-plus mr-2"></i> Report New Issue
                            </a>
                        </div>
                        <?php elseif (empty($issues)): ?>
                        <div class="p-8 text-center">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-500 mb-4">
                                <i class="fas fa-search text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Results Found</h3>
                            <p class="text-gray-500 mb-6">Try adjusting your filters for different results</p>
                            <a href="index.php" class="text-amber-600 hover:underline transition-colors duration-300">
                                <i class="fas fa-sync-alt mr-2"></i> Reset Filters
                            </a>
                        </div>
                        <?php else: ?>
                        <!-- Issues Table/Cards -->
                        <div class="hidden md:block">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID/Title
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Location
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Severity
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($issues as $issue): ?>
                                        <tr class="hover-scale hover:bg-amber-50 transition-all duration-300">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div
                                                        class="flex-shrink-0 h-10 w-10 bg-amber-100 rounded-md flex items-center justify-center">
                                                        <?php if (!empty($issue['thumbnail'])): ?>
                                                        <img src="<?php echo htmlspecialchars($issue['thumbnail']); ?>"
                                                            alt="Issue Thumbnail"
                                                            class="h-10 w-10 rounded-md object-cover">
                                                        <?php else: ?>
                                                        <i class="fas fa-clipboard-list text-amber-600"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <a href="../issue-detail/?id=<?php echo $issue['id']; ?>"
                                                                class="hover:text-amber-700 hover:underline transition-colors duration-300">
                                                                <?php echo htmlspecialchars($issue['title']); ?>
                                                            </a>
                                                        </div>
                                                        <div class="text-xs text-gray-500">#<?php echo $issue['id']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="text-gray-900">
                                                    <?php echo htmlspecialchars($issue['location']); ?></div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($issue['area_name'] ?: 'Unassigned Area'); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php 
                                                    $severity_color = 'gray';
                                                    $severity_bg = 'bg-gray-100';
                                                    $severity_text = 'text-gray-800';
                                                    
                                                    if ($issue['severity'] == 'critical') {
                                                        $severity_color = 'red';
                                                        $severity_bg = 'bg-red-100';
                                                        $severity_text = 'text-red-800';
                                                    } else if ($issue['severity'] == 'high') {
                                                        $severity_color = 'orange';
                                                        $severity_bg = 'bg-orange-100';
                                                        $severity_text = 'text-orange-800';
                                                    } else if ($issue['severity'] == 'medium') {
                                                        $severity_color = 'yellow';
                                                        $severity_bg = 'bg-yellow-100';
                                                        $severity_text = 'text-yellow-800';
                                                    } else if ($issue['severity'] == 'low') {
                                                        $severity_color = 'green';
                                                        $severity_bg = 'bg-green-100';
                                                        $severity_text = 'text-green-800';
                                                    }
                                                ?>
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $severity_bg ?> <?= $severity_text ?>">
                                                    <?php echo ucfirst($issue['severity']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php 
                                                    $status_color = 'gray';
                                                    $status_bg = 'bg-gray-100';
                                                    $status_text = 'text-gray-800';
                                                    
                                                    if ($issue['status'] == 'pending') {
                                                        $status_color = 'yellow';
                                                        $status_bg = 'bg-yellow-100';
                                                        $status_text = 'text-yellow-800';
                                                    } else if ($issue['status'] == 'under_review') {
                                                        $status_color = 'purple';
                                                        $status_bg = 'bg-purple-100';
                                                        $status_text = 'text-purple-800';
                                                    } else if ($issue['status'] == 'in_progress') {
                                                        $status_color = 'blue';
                                                        $status_bg = 'bg-blue-100';
                                                        $status_text = 'text-blue-800';
                                                    } else if ($issue['status'] == 'resolved') {
                                                        $status_color = 'green';
                                                        $status_bg = 'bg-green-100';
                                                        $status_text = 'text-green-800';
                                                    } else if ($issue['status'] == 'rejected') {
                                                        $status_color = 'red';
                                                        $status_bg = 'bg-red-100';
                                                        $status_text = 'text-red-800';
                                                    }
                                                ?>
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $status_bg ?> <?= $status_text ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="../issue-detail/?id=<?php echo $issue['id']; ?>"
                                                    class="text-amber-600 hover:text-amber-900 transition-colors duration-300 px-2">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../edit-issue/?id=<?php echo $issue['id']; ?>"
                                                    class="text-amber-600 hover:text-amber-900 transition-colors duration-300 px-2">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button
                                                    onclick="confirmDelete(<?php echo $issue['id']; ?>, '<?php echo addslashes($issue['title']); ?>')"
                                                    class="text-red-600 hover:text-red-900 transition-colors duration-300 px-2">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile Cards View -->
                        <div class="md:hidden">
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($issues as $index => $issue): ?>
                                <div class="p-4 hover:bg-amber-50 transition-colors duration-300"
                                    style="animation: fadeIn 0.3s ease-out forwards; animation-delay: <?= ($index * 0.05) ?>s; opacity: 0;">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <a href="../issue-detail/?id=<?php echo $issue['id']; ?>"
                                                class="text-lg font-medium text-amber-800 hover:text-amber-900 hover:underline transition-colors duration-300">
                                                <?php echo htmlspecialchars($issue['title']); ?>
                                            </a>
                                            <div class="flex flex-wrap gap-2 mt-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php echo ($issue['status'] == 'pending') ? 'bg-yellow-100 text-yellow-800' : 
                                                    (($issue['status'] == 'in_progress') ? 'bg-blue-100 text-blue-800' : 
                                                    (($issue['status'] == 'resolved') ? 'bg-green-100 text-green-800' : 
                                                    (($issue['status'] == 'rejected') ? 'bg-red-100 text-red-800' : 
                                                    'bg-gray-100 text-gray-800'))); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    <?php echo ($issue['severity'] == 'critical') ? 'bg-red-100 text-red-800' : 
                                                    (($issue['severity'] == 'high') ? 'bg-orange-100 text-orange-800' : 
                                                    (($issue['severity'] == 'medium') ? 'bg-yellow-100 text-yellow-800' : 
                                                    (($issue['severity'] == 'low') ? 'bg-green-100 text-green-800' : 
                                                    'bg-gray-100 text-gray-800'))); ?>">
                                                    <?php echo ucfirst($issue['severity']); ?>
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-2">
                                                <div><i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                                                    <?php echo htmlspecialchars($issue['location']); ?></div>
                                                <div><i class="fas fa-calendar text-gray-400 mr-1"></i>
                                                    <?php echo date('M d, Y', strtotime($issue['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ml-4">
                                            <?php if (!empty($issue['thumbnail'])): ?>
                                            <img src="<?php echo htmlspecialchars($issue['thumbnail']); ?>"
                                                alt="Issue Thumbnail"
                                                class="h-16 w-16 rounded-md object-cover border border-gray-200">
                                            <?php else: ?>
                                            <div
                                                class="h-16 w-16 bg-amber-100 rounded-md flex items-center justify-center">
                                                <i class="fas fa-clipboard-list text-amber-600 text-xl"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex justify-end mt-3 space-x-2">
                                        <a href="../issue-detail/?id=<?php echo $issue['id']; ?>"
                                            class="px-3 py-1 bg-amber-100 text-amber-700 rounded-md text-sm hover:bg-amber-200 transition-colors duration-300">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../edit-issue/?id=<?php echo $issue['id']; ?>"
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded-md text-sm hover:bg-blue-200 transition-colors duration-300">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button
                                            onclick="confirmDelete(<?php echo $issue['id']; ?>, '<?php echo addslashes($issue['title']); ?>')"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded-md text-sm hover:bg-red-200 transition-colors duration-300">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="px-4 py-4 border-t border-gray-200 sm:px-6">
                            <nav class="flex items-center justify-between">
                                <div class="hidden sm:block">
                                    <p class="text-sm text-gray-700">
                                        Showing <span
                                            class="font-medium"><?= min(($page - 1) * $limit + 1, $total_issues) ?></span>
                                        to
                                        <span class="font-medium"><?= min($page * $limit, $total_issues) ?></span> of
                                        <span class="font-medium"><?= $total_issues ?></span> issues
                                    </p>
                                </div>
                                <div class="flex-1 flex justify-between sm:justify-end">
                                    <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&severity=<?php echo $severity_filter; ?>&search=<?php echo urlencode($search_term); ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-300">
                                        <i class="fas fa-chevron-left mr-2"></i> Previous
                                    </a>
                                    <?php else: ?>
                                    <span
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-100 cursor-not-allowed">
                                        <i class="fas fa-chevron-left mr-2"></i> Previous
                                    </span>
                                    <?php endif; ?>

                                    <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&severity=<?php echo $severity_filter; ?>&search=<?php echo urlencode($search_term); ?>"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-300">
                                        Next <i class="fas fa-chevron-right ml-2"></i>
                                    </a>
                                    <?php else: ?>
                                    <span
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-100 cursor-not-allowed">
                                        Next <i class="fas fa-chevron-right ml-2"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full slide-in-right">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Delete Issue
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500" id="delete-message">
                                    Are you sure you want to delete this issue? All data related to this issue will be
                                    permanently removed.
                                    This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST" action="delete.php">
                        <input type="hidden" id="delete_issue_id" name="issue_id" value="">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-300">
                            Delete
                        </button>
                        <button type="button" onclick="closeDeleteModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-300">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // AlpineJS sidebar control
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            open: false,
            toggle() {
                this.open = !this.open;
            }
        });
    });

    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        Alpine.store('sidebar').toggle();
    });

    // Delete modal functions
    function confirmDelete(id, title) {
        document.getElementById('delete_issue_id').value = id;
        document.getElementById('delete-message').innerHTML =
            `Are you sure you want to delete the issue "<strong>${title}</strong>"? All data related to this issue will be permanently removed. This action cannot be undone.`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
    </script>
</body>

</html>