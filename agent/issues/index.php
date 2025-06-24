<?php
// issues.php - Agent Issues Management Page
require_once __DIR__ . '/../components/sidebar.php';
require_once_once __DIR__ . '/../components/header.php';
_once __DIR__ . '/../login/session_check.php';

$current_page = 'issues';

// Dummy data for demonstration
$issues = [
    [
        'id' => 1,
        'title' => 'Potholes on High Street',
        'category' => 'Roads',
        'status' => 'pending',
        'location' => 'Central Business District',
        'submitted_at' => '2023-06-15',
        'description' => 'Large potholes making driving difficult and dangerous on High Street near the market.'
    ],
    [
        'id' => 2,
        'title' => 'Water Shortage in North Hills',
        'category' => 'Water',
        'status' => 'approved',
        'location' => 'North Hills Residential',
        'submitted_at' => '2023-06-10',
        'description' => 'Intermittent water supply for the past week, affecting daily chores.'
    ],
    [
        'id' => 3,
        'title' => 'Broken Streetlight at Park Entrance',
        'category' => 'Electricity',
        'status' => 'resolved',
        'location' => 'Community Park',
        'submitted_at' => '2023-06-01',
        'description' => 'Streetlight at the main entrance of Community Park has been out for several nights.'
    ],
    [
        'id' => 4,
        'title' => 'Illegal Dumping in Riverfront Area',
        'category' => 'Environment',
        'status' => 'pending',
        'location' => 'Riverfront Pathway',
        'submitted_at' => '2023-06-18',
        'description' => 'Construction waste being dumped near the riverfront walking path.'
    ],
    [
        'id' => 5,
        'title' => 'School Playground Equipment Damaged',
        'category' => 'Education',
        'status' => 'rejected',
        'location' => 'Central Elementary School',
        'submitted_at' => '2023-06-08',
        'description' => 'Swings and slides are damaged and pose safety risks to children.'
    ],
    [
        'id' => 6,
        'title' => 'Sewage Overflow on Main Street',
        'category' => 'Sanitation',
        'status' => 'approved',
        'location' => 'Main Street Shopping District',
        'submitted_at' => '2023-06-14',
        'description' => 'Sewage backing up onto street near restaurant row, causing health concerns.'
    ],
];

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
    ],
    [
        'icon' => 'fas fa-filter',
        'label' => 'Filter',
        'href' => '#',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300',
        'id' => 'filterToggleBtn'
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
            <!-- Filters Section - Initially Hidden -->
            <div id="filterSection" class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6 hidden">
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
            // Filter toggle functionality
            const filterToggleBtn = document.getElementById('filterToggleBtn');
            const filterSection = document.getElementById('filterSection');
            
            filterToggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                filterSection.classList.toggle('hidden');
            });
            
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