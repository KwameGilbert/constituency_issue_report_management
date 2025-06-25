<?php
// issues.php - Agent Issues Management Page
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
$agentId = $_SESSION['user_id'] ?? null;
if (!$agentId) {
    die("Unauthorized");
}                                                                                                                                      
$current_page = 'issues';

$issuesStmt = $conn->prepare("
    SELECT 
        i.id,
        i.title,
        ic.name AS category,
        i.status,
        i.location,
        DATE(i.created_at) AS submitted_at
    FROM issues i
    LEFT JOIN issue_categories ic ON i.category_id = ic.id
    WHERE i.agent_id = ?
    ORDER BY i.created_at DESC
");

$issuesStmt->execute([$agentId]);
$issues = $issuesStmt->fetchAll(PDO::FETCH_ASSOC);

// Extract unique categories and statuses for filters
$categories = array_unique(array_column($issues, 'category'));
sort($categories);
$statuses = array_unique(array_column($issues, 'status'));
sort($statuses);

// Define action buttons for the header
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'New Issue',
        'href' => 'add_issue.php'
    ]
];

// Get current user data for display
$userName = $_SESSION['user_name'] ?? 'Agent';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Issues - Agent Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        success: '#10b981',
                        warning: '#f59e0b',
                        error: '#ef4444',
                        slate: {
                            50: '#f8fafc',
                            900: '#0f172a',
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderAgentSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAgentHeader('Issues', 'Manage and track constituent issues', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <!-- Filters Section -->
            <div id="filterSection" class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Filter Issues</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <!-- Search Filter -->
                    <div>
                        <label for="searchInput" class="block text-xs font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="searchInput" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900" placeholder="Search by title or location...">
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label for="categoryFilter" class="block text-xs font-medium text-gray-700 mb-2">Category</label>
                        <select id="categoryFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="statusFilter" class="block text-xs font-medium text-gray-700 mb-2">Status</label>
                        <select id="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-1 focus:ring-slate-900 focus:border-slate-900">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status) : ?>
                                <option value="<?php echo htmlspecialchars($status); ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex justify-end space-x-2">
                    <button id="resetFiltersBtn" class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                        Reset
                    </button>
                    <button id="applyFiltersBtn" class="px-4 py-2 text-xs font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                        Apply Filters
                    </button>
                </div>
            </div>

            <!-- Issues Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-base font-semibold text-gray-800">All Issues</h2>
                    <div class="text-sm text-gray-500"><?php echo count($issues); ?> issues found</div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="issuesTable">
                            <?php foreach ($issues as $issue) : ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $issue['id']; ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($issue['title']); ?></div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($issue['description'] ?? 'No description'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($issue['category']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($issue['location']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusClass = '';
                                        switch ($issue['status']) {
                                            case 'pending':
                                                $statusClass = 'bg-warning/10 text-warning';
                                                break;
                                            case 'approved':
                                                $statusClass = 'bg-primary/10 text-primary';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'bg-error/10 text-error';
                                                break;
                                            case 'resolved':
                                                $statusClass = 'bg-success/10 text-success';
                                                break;
                                            default:
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                break;
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($issue['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($issue['submitted_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="view_issue.php?id=<?php echo $issue['id']; ?>" class="text-primary hover:text-primary/80 font-medium mr-3">View</a>
                                        <a href="edit_issue.php?id=<?php echo $issue['id']; ?>" class="text-gray-600 hover:text-gray-900 font-medium">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($issues); ?></span> of <span class="font-medium"><?php echo count($issues); ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </a>
                                <a href="#" aria-current="page" class="z-10 bg-slate-900 border-slate-900 text-white relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Filter functionality
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');
            const resetFiltersBtn = document.getElementById('resetFiltersBtn');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const issuesTable = document.getElementById('issuesTable');
            const rows = issuesTable.querySelectorAll('tr');

            // Apply filters function
            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase();
                const category = categoryFilter.value.toLowerCase();
                const status = statusFilter.value.toLowerCase();

                rows.forEach(row => {
                    const title = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const location = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                    const rowCategory = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const rowStatus = row.querySelector('td:nth-child(5)').textContent.toLowerCase();

                    const matchesSearch = title.includes(searchTerm) || location.includes(searchTerm);
                    const matchesCategory = !category || rowCategory === category;
                    const matchesStatus = !status || rowStatus.includes(status);

                    if (matchesSearch && matchesCategory && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Reset filters function
            function resetFilters() {
                searchInput.value = '';
                categoryFilter.selectedIndex = 0;
                statusFilter.selectedIndex = 0;
                rows.forEach(row => row.style.display = '');
            }

            // Event listeners
            applyFiltersBtn.addEventListener('click', applyFilters);
            resetFiltersBtn.addEventListener('click', resetFilters);
        });
    </script>
</body>

</html>