<?php
// agents/index.php - Officer Agents Management Page
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$officerId = $_SESSION['user_id'] ?? null;
$current_page = 'agents';

// Initialize message variables
$message = '';
$message_type = '';

// Check for messages from URL parameters
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';
}

// Fetch agents data with statistics
try {
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.department,
            u.status,
            u.created_at,
            u.last_login,
            mc.name as main_community_name,
            sc.name as smaller_community_name,
            s.name as suburb_name,
            c.name as cottage_name,
            COUNT(i.id) as total_issues,
            SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
        FROM users u
        LEFT JOIN communities mc ON u.main_community_id = mc.id
        LEFT JOIN smaller_communities sc ON u.smaller_community_id = sc.id
        LEFT JOIN suburbs s ON u.suburb_id = s.id
        LEFT JOIN cottages c ON u.cottage_id = c.id
        LEFT JOIN issues i ON u.id = i.agent_id
        WHERE u.role = 'agent'
        GROUP BY u.id, u.name, u.email, u.phone, u.department, u.status, u.created_at, u.last_login, mc.name, sc.name, s.name, c.name
        ORDER BY u.created_at DESC
    ");

    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get filter options
    $statusOptions = ['active', 'inactive'];

    // Get main communities for filtering
    $stmt = $conn->prepare("SELECT id, name FROM communities ORDER BY name");
    $stmt->execute();
    $mainCommunities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get smaller communities for filtering
    $stmt = $conn->prepare("SELECT id, name FROM smaller_communities ORDER BY name");
    $stmt->execute();
    $smallerCommunities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get suburbs for filtering
    $stmt = $conn->prepare("SELECT id, name FROM suburbs ORDER BY name");
    $stmt->execute();
    $suburbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get cottages for filtering
    $stmt = $conn->prepare("SELECT id, name FROM cottages ORDER BY name");
    $stmt->execute();
    $cottages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get departments
    $stmt = $conn->prepare("SELECT DISTINCT department FROM users WHERE role = 'agent' AND department IS NOT NULL ORDER BY department");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $message = "Error loading agents data: " . $e->getMessage();
    $message_type = "error";
    $agents = [];
    $statusOptions = [];
    $mainCommunities = [];
    $smallerCommunities = [];
    $suburbs = [];
    $cottages = [];
    $departments = [];
}

// Calculate summary statistics
$totalAgents = count($agents);
$activeAgents = count(array_filter($agents, fn($agent) => $agent['status'] === 'active'));
$inactiveAgents = $totalAgents - $activeAgents;
$totalIssuesHandled = array_sum(array_column($agents, 'total_issues'));

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-user-plus',
        'label' => 'Add Agent',
        'href' => 'add_agent.php',
        'class' => 'bg-indigo-900 text-white hover:bg-indigo-800'
    ]
];

// Get current user data for display
$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agents Management - Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
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
                        info: '#3b82f6',
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
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

    <style>
        /* Custom styles for status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Focus styles */
        input:focus,
        select:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderOfficerSidebar($current_page, getPendingIssuesCount($conn)); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderOfficerHeader('Agents Management', 'Manage field agents and their activities', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message) : ?>
                <div class="mb-4 p-3 rounded-xl text-xs <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-900/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-users text-blue-900"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Agents</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $totalAgents; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-success/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-user-check text-success"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Active Agents</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $activeAgents; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-error/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-user-times text-error"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Inactive Agents</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $inactiveAgents; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-alt text-primary"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Issues Handled</p>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo $totalIssuesHandled; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div id="filterSection" class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center justify-between">
                    <span>Filter Agents</span>
                    <button id="toggleFiltersBtn" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary rounded-full p-1 transition-transform duration-200">
                        <i class="fas fa-chevron-up" id="toggleIcon"></i>
                    </button>
                </h2>
                <div id="filterInputsContainer" class="transition-all duration-300 ease-in-out overflow-hidden max-h-screen">
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <!-- Search Input (full width) -->
                        <div>
                            <label for="searchInput" class="block text-xs font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="searchInput" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary" placeholder="Search by name, email, or department...">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                        <!-- Status Filter -->
                        <div>
                            <label for="statusFilter" class="block text-xs font-medium text-gray-700 mb-2">Status</label>
                            <select id="statusFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">
                                <option value="">All Statuses</option>
                                <?php foreach ($statusOptions as $status) : ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>"><?php echo ucfirst($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Main Community Filter -->
                        <div>
                            <label for="mainCommunityFilter" class="block text-xs font-medium text-gray-700 mb-2">Main Community</label>
                            <select id="mainCommunityFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">
                                <option value="">All Communities</option>
                                <?php foreach ($mainCommunities as $mc) : ?>
                                    <option value="<?php echo htmlspecialchars($mc['name']); ?>"><?php echo htmlspecialchars($mc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Smaller Community Filter -->
                        <div>
                            <label for="smallerCommunityFilter" class="block text-xs font-medium text-gray-700 mb-2">Smaller Community</label>
                            <select id="smallerCommunityFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">
                                <option value="">All Smaller Communities</option>
                                <?php foreach ($smallerCommunities as $sc) : ?>
                                    <option value="<?php echo htmlspecialchars($sc['name']); ?>"><?php echo htmlspecialchars($sc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Suburb Filter -->
                        <div>
                            <label for="suburbFilter" class="block text-xs font-medium text-gray-700 mb-2">Suburb</label>
                            <select id="suburbFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">
                                <option value="">All Suburbs</option>
                                <?php foreach ($suburbs as $suburb) : ?>
                                    <option value="<?php echo htmlspecialchars($suburb['name']); ?>"><?php echo htmlspecialchars($suburb['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Cottage Filter -->
                        <div>
                            <label for="cottageFilter" class="block text-xs font-medium text-gray-700 mb-2">Cottage</label>
                            <select id="cottageFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">
                                <option value="">All Cottages</option>
                                <?php foreach ($cottages as $cottage) : ?>
                                    <option value="<?php echo htmlspecialchars($cottage['name']); ?>"><?php echo htmlspecialchars($cottage['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Department Filter -->
                        <div>
                            <label for="departmentFilter" class="block text-xs font-medium text-gray-700 mb-2">Department</label>
                            <select id="departmentFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring focus:ring-primary focus:border-primary">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept) : ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button id="resetFiltersBtn" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-undo mr-2"></i> Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Agents Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Main Community</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Smaller Community</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suburb</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cottage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues Stats</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="agentsTable">
                            <?php if (empty($agents)) : ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No agents found. Add your first agent to get started.
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($agents as $agent) : ?>
                                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                        data-name="<?php echo strtolower($agent['name']); ?>"
                                        data-email="<?php echo strtolower($agent['email']); ?>"
                                        data-department="<?php echo strtolower($agent['department'] ?? ''); ?>"
                                        data-status="<?php echo strtolower($agent['status']); ?>"
                                        data-main-community="<?php echo strtolower($agent['main_community_name'] ?? ''); ?>"
                                        data-smaller-community="<?php echo strtolower($agent['smaller_community_name'] ?? ''); ?>"
                                        data-suburb="<?php echo strtolower($agent['suburb_name'] ?? ''); ?>"
                                        data-cottage="<?php echo strtolower($agent['cottage_name'] ?? ''); ?>">

                                        <!-- Agent Name & Department -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-indigo-700 text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($agent['name']); ?></div>
                                                    <?php if ($agent['department']) : ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($agent['department']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Contact Info -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($agent['email']); ?></div>
                                            <?php if ($agent['phone']) : ?>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($agent['phone']); ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Main Community -->
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($agent['main_community_name'] ?? 'Not Assigned'); ?>
                                        </td>
                                        
                                        <!-- Smaller Community -->
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($agent['smaller_community_name'] ?? 'Not Assigned'); ?>
                                        </td>
                                        
                                        <!-- Suburb -->
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($agent['suburb_name'] ?? 'Not Assigned'); ?>
                                        </td>
                                        
                                        <!-- Cottage -->
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($agent['cottage_name'] ?? 'Not Assigned'); ?>
                                        </td>

                                        <!-- Issues Statistics -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <span class="text-gray-900 font-medium"><?php echo $agent['total_issues']; ?></span>
                                                <span class="text-gray-500">total</span>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <span class="text-yellow-600"><?php echo $agent['pending_issues']; ?> pending</span> •
                                                <span class="text-green-600"><?php echo $agent['resolved_issues']; ?> resolved</span>
                                            </div>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4">
                                            <span class="status-badge status-badge-<?php echo $agent['status']; ?>">
                                                <?php echo ucfirst($agent['status']); ?>
                                            </span>
                                        </td>

                                        <!-- Last Login -->
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php
                                            if ($agent['last_login']) {
                                                echo date('M d, Y', strtotime($agent['last_login']));
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4 text-center text-sm font-medium">
                                            <div class="flex items-center justify-center space-x-2">
                                                <a href="view_agent.php?id=<?php echo $agent['id']; ?>" class="text-primary hover:text-primary-dark" title="View Agent">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_agent.php?id=<?php echo $agent['id']; ?>" class="text-secondary hover:text-secondary-dark" title="Edit Agent">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($agent['status'] === 'active') : ?>
                                                    <button onclick="toggleAgentStatus(<?php echo $agent['id']; ?>, 'inactive')" class="text-error hover:text-error-dark" title="Deactivate Agent">
                                                        <i class="fas fa-user-times"></i>
                                                    </button>
                                                <?php else : ?>
                                                    <button onclick="toggleAgentStatus(<?php echo $agent['id']; ?>, 'active')" class="text-success hover:text-success-dark" title="Activate Agent">
                                                        <i class="fas fa-user-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="noAgentsMessage" class="hidden px-6 py-10 text-center text-sm text-gray-500">
                    No agents found matching your filters.
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = {
                search: document.getElementById('searchInput'),
                status: document.getElementById('statusFilter'),
                mainCommunity: document.getElementById('mainCommunityFilter'),
                smallerCommunity: document.getElementById('smallerCommunityFilter'),
                suburb: document.getElementById('suburbFilter'),
                cottage: document.getElementById('cottageFilter'),
                department: document.getElementById('departmentFilter')
            };

            const tableBody = document.getElementById('agentsTable');
            const tableRows = Array.from(tableBody.querySelectorAll('tr'));
            const noAgentsMessage = document.getElementById('noAgentsMessage');

            // Filter section toggle
            const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
            const toggleIcon = document.getElementById('toggleIcon');
            const filterInputsContainer = document.getElementById('filterInputsContainer');

            let isFiltersExpanded = true;

            toggleFiltersBtn.addEventListener('click', () => {
                isFiltersExpanded = !isFiltersExpanded;
                if (isFiltersExpanded) {
                    filterInputsContainer.style.maxHeight = filterInputsContainer.scrollHeight + 'px';
                    toggleIcon.classList.remove('fa-chevron-down');
                    toggleIcon.classList.add('fa-chevron-up');
                } else {
                    filterInputsContainer.style.maxHeight = '0';
                    toggleIcon.classList.remove('fa-chevron-up');
                    toggleIcon.classList.add('fa-chevron-down');
                }
            });

            // Set initial filter container height
            filterInputsContainer.style.maxHeight = filterInputsContainer.scrollHeight + 'px';

            function normalize(text) {
                return (text || '').toLowerCase().trim();
            }

            function filterRows() {
                const values = Object.fromEntries(Object.entries(filters).map(([key, el]) => [key, normalize(el.value)]));
                let visibleRowCount = 0;

                tableRows.forEach(row => {
                    // Skip the "no agents" row if it exists
                    if (row.cells.length === 1) return;

                    const matches =
                        (!values.search ||
                            row.dataset.name.includes(values.search) ||
                            row.dataset.email.includes(values.search) ||
                            row.dataset.department.includes(values.search)
                        ) &&
                        (!values.status || row.dataset.status === values.status) &&
                        (!values.mainCommunity || row.dataset.mainCommunity === values.mainCommunity) &&
                        (!values.smallerCommunity || row.dataset.smallerCommunity === values.smallerCommunity) &&
                        (!values.suburb || row.dataset.suburb === values.suburb) &&
                        (!values.cottage || row.dataset.cottage === values.cottage) &&
                        (!values.department || row.dataset.department === values.department);

                    row.style.display = matches ? '' : 'none';
                    if (matches) {
                        visibleRowCount++;
                    }
                });

                // Show/hide "No agents found" message
                if (visibleRowCount === 0) {
                    noAgentsMessage.classList.remove('hidden');
                    tableBody.classList.add('hidden');
                } else {
                    noAgentsMessage.classList.add('hidden');
                    tableBody.classList.remove('hidden');
                }
            }

            // Attach event listeners to all filter inputs
            Object.values(filters).forEach(input => {
                if (input) {
                    input.addEventListener('input', filterRows);
                    input.addEventListener('change', filterRows);
                }
            });

            // Reset filters button
            document.getElementById('resetFiltersBtn')?.addEventListener('click', () => {
                Object.values(filters).forEach(input => {
                    if (input) {
                        input.value = '';
                    }
                });
                filterRows();
            });

            // Initial filter application
            filterRows();
        });

        // Function to toggle agent status
        function toggleAgentStatus(agentId, newStatus) {
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this agent?`)) {
                // Create form data
                const formData = new FormData();
                formData.append('agent_id', agentId);
                formData.append('status', newStatus);
                formData.append('action', 'toggle_status');

                // Send request to toggle status
                fetch('toggle_agent_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to reflect changes
                            window.location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Failed to update agent status'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the agent status');
                    });
            }
        }
    </script>
</body>

</html>