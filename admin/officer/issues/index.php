<?php
// issues.php - Issue management page
session_start();

// Check if user is logged in and is a field officer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';
$officer_id = $_SESSION['user_id'];

// Handle filtering
$status_filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$severity_filter = isset($_GET['severity']) ? $_GET['severity'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM issues WHERE officer_id = ?";
$params = [$officer_id];
$types = "i";

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($severity_filter)) {
    $query .= " AND severity = ?";
    $params[] = $severity_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR description LIKE ? OR electoral_area LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*)", $query);
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_row()[0];
$total_pages = ceil($total_records / $limit);

// Add pagination to query
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$issues = [];
while($issue = $result->fetch_assoc()) {
    $issues[] = $issue;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Management - Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 flex-shrink-0">
            <div class="p-4">
                <h2 class="text-2xl font-bold">Field Officer Portal</h2>
                <p class="text-sm opacity-70">Constituency Issue Management</p>
            </div>
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="issues.php" class="flex items-center py-3 px-4 bg-blue-900">
                    <i class="fas fa-exclamation-circle w-6"></i>
                    <span>Issue Management</span>
                </a>
                <a href="profile.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-user w-6"></i>
                    <span>Profile</span>
                </a>
                <a href="reports.php" class="flex items-center py-3 px-4 hover:bg-blue-700">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span>Reports & Analytics</span>
                </a>
                <a href="logout.php" class="flex items-center py-3 px-4 hover:bg-blue-700 mt-12">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Issue Management</h1>
                    <a href="create-issue.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                        <i class="fas fa-plus mr-2"></i> Report New Issue
                    </a>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4">
                <div class="max-w-7xl mx-auto">
                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <form action="issues.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="filter" id="filter"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All Statuses</option>
                                    <option value="pending"
                                        <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress"
                                        <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress
                                    </option>
                                    <option value="resolved"
                                        <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            </div>
                            <div>
                                <label for="severity"
                                    class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                                <select name="severity" id="severity"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                                    value="<?php echo htmlspecialchars($search); ?>" placeholder="Search issues..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Issues List -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Issue ID
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Title
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Electoral Area
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
                                            Date Reported
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($issues)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            No issues found matching your criteria.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($issues as $issue): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            #<?php echo $issue['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="issue-detail.php?id=<?php echo $issue['id']; ?>"
                                                class="text-blue-600 hover:underline font-medium">
                                                <?php echo htmlspecialchars($issue['title']); ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($issue['electoral_area']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                                $severity_color = 'gray';
                                                if ($issue['severity'] == 'critical') $severity_color = 'red';
                                                if ($issue['severity'] == 'high') $severity_color = 'orange';
                                                if ($issue['severity'] == 'medium') $severity_color = 'yellow';
                                                if ($issue['severity'] == 'low') $severity_color = 'green';
                                                ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $severity_color; ?>-100 text-<?php echo $severity_color; ?>-800">
                                                <?php echo ucfirst($issue['severity']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                                $status_color = 'gray';
                                                if ($issue['status'] == 'pending') $status_color = 'yellow';
                                                if ($issue['status'] == 'in_progress') $status_color = 'blue';
                                                if ($issue['status'] == 'resolved') $status_color = 'green';
                                                ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                                <?php echo ucfirst(str_replace('_', ' ', $issue['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="issue-detail.php?id=<?php echo $issue['id']; ?>"
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-issue.php?id=<?php echo $issue['id']; ?>"
                                                class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="text-red-600 hover:text-red-900"
                                                onclick="confirmDelete(<?php echo $issue['id']; ?>)">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 bg-white border-t border-gray-200">
                            <nav class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between items-center">
                                    <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo ($page - 1); ?>&filter=<?php echo $status_filter; ?>&severity=<?php echo $severity_filter; ?>&search=<?php echo urlencode($search); ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                    <?php else: ?>
                                    <span
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-100 cursor-not-allowed">
                                        Previous
                                    </span>
                                    <?php endif; ?>

                                    <div class="hidden md:flex">
                                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php if($i == $page): ?>
                                        <span
                                            class="relative inline-flex items-center px-4 py-2 mx-1 border border-blue-500 text-sm font-medium rounded-md text-white bg-blue-500">
                                            <?php echo $i; ?>
                                        </span>
                                        <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>&filter=<?php echo $status_filter; ?>&severity=<?php echo $severity_filter; ?>&search=<?php echo urlencode($search); ?>"
                                            class="relative inline-flex items-center px-4 py-2 mx-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            <?php echo $i; ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>

                                    <div class="md:hidden text-sm text-gray-700">
                                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                    </div>

                                    <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo ($page + 1); ?>&filter=<?php echo $status_filter; ?>&severity=<?php echo $severity_filter; ?>&search=<?php echo urlencode($search); ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                    <?php else: ?>
                                    <span
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-gray-100 cursor-not-allowed">
                                        Next
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
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
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete this issue? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST" action="delete-issue.php">
                        <input type="hidden" id="deleteIssueId" name="issue_id" value="">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                    </form>
                    <button type="button" onclick="closeDeleteModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmDelete(issueId) {
        document.getElementById('deleteIssueId').value = issueId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    </script>
</body>

</html>