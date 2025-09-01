<?php
// admin/agents/index.php - Admin Agent Management
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'agents';

// Initialize message variables
$message = '';
$message_type = '';

// Process GET parameters for messages
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = isset($_GET['type']) && $_GET['type'] === 'success' ? 'success' : 'error';
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Prepare WHERE clause based on filters
$where_clauses = ["role = 'agent'"]; // Only agents
$params = [];

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%{$search_query}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(" AND ", $where_clauses);

// Count total agents for pagination
try {
    $count_sql = "SELECT COUNT(*) FROM users WHERE {$where_clause}";
    $stmt = $conn->prepare($count_sql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total_agents = $stmt->fetchColumn();
    $total_pages = ceil($total_agents / $per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // Query to fetch agents with pagination
    $sql = "
        SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at, u.last_login,
               mc.name as main_community_name, 
               sc.name as smaller_community_name,
               sb.name as suburb_name,
               ct.name as cottage_name
        FROM users u
        LEFT JOIN communities mc ON u.main_community_id = mc.id
        LEFT JOIN smaller_communities sc ON u.smaller_community_id = sc.id
        LEFT JOIN suburbs sb ON u.suburb_id = sb.id
        LEFT JOIN cottages ct ON u.cottage_id = ct.id
        WHERE {$where_clause}
        ORDER BY u.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters for main query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error fetching agents: " . $e->getMessage();
    $message_type = 'error';
    $agents = [];
    $total_pages = 0;
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'Add New Agent',
        'href' => './add_agent.php',
        'class' => 'bg-indigo-900 text-white hover:bg-indigo-800'
    ],
    [
        'icon' => 'fas fa-download',
        'label' => 'Export Agents',
        'href' => '../reports/export.php?type=agents',
        'class' => 'bg-green-600 text-white hover:bg-green-700'
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Dashboard',
        'href' => '../dashboard/',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Get the logged-in user's name
$userName = $_SESSION['user_name'] ?? 'Administrator';

// Get status counts for filter
try {
    $status_count_sql = "SELECT status, COUNT(*) as count FROM users WHERE role = 'agent' GROUP BY status";
    $stmt = $conn->prepare($status_count_sql);
    $stmt->execute();
    $status_counts = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // Get total count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'agent'");
    $stmt->execute();
    $status_counts['all'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $status_counts = ['all' => 0];
}

// Status colors for badges
$status_colors = [
    'active' => 'green',
    'inactive' => 'red'
];

// Define the pagination URL function
function getPaginationUrl($page, $current_params = []) {
    // Start with the base URL
    $url = '?';
    
    // Add all current GET parameters except 'page'
    foreach ($_GET as $key => $value) {
        if ($key !== 'page' && !array_key_exists($key, $current_params)) {
            $url .= urlencode($key) . '=' . urlencode($value) . '&';
        }
    }
    
    // Add any additional parameters
    foreach ($current_params as $key => $value) {
        $url .= urlencode($key) . '=' . urlencode($value) . '&';
    }
    
    // Add the page parameter
    $url .= 'page=' . $page;
    
    return $url;
}

// Function to output status badge
function getStatusBadge($status, $status_colors) {
    $color = isset($status_colors[$status]) ? $status_colors[$status] : 'gray';
    $colors = [
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'gray' => 'bg-gray-100 text-gray-800'
    ];
    
    $color_class = $colors[$color];
    
    return "<span class=\"px-2 py-1 text-xs font-medium rounded-full {$color_class}\">" . ucfirst($status) . "</span>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agent Management - Admin Dashboard</title>
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
    <?php renderAdminSidebar($current_page); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('Agent Management', 'Manage all system agents', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-4 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <!-- Status Filter Tabs -->
                        <div class="flex flex-wrap gap-2">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => ''])); ?>" class="<?php echo empty($status_filter) ? 'bg-indigo-900 text-white' : 'bg-gray-100 text-gray-700'; ?> px-4 py-2 text-sm font-medium rounded-lg hover:bg-indigo-800 hover:text-white transition">
                                All Agents (<?php echo $status_counts['all'] ?? 0; ?>)
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'active'])); ?>" class="<?php echo $status_filter === 'active' ? 'bg-indigo-900 text-white' : 'bg-gray-100 text-gray-700'; ?> px-4 py-2 text-sm font-medium rounded-lg hover:bg-indigo-800 hover:text-white transition">
                                Active (<?php echo $status_counts['active'] ?? 0; ?>)
                            </a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'inactive'])); ?>" class="<?php echo $status_filter === 'inactive' ? 'bg-indigo-900 text-white' : 'bg-gray-100 text-gray-700'; ?> px-4 py-2 text-sm font-medium rounded-lg hover:bg-indigo-800 hover:text-white transition">
                                Inactive (<?php echo $status_counts['inactive'] ?? 0; ?>)
                            </a>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="mt-4">
                        <form action="" method="GET" class="flex gap-2">
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <div class="flex-grow">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name, email or phone..." class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900 transition-colors">
                            </div>
                            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-900 rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-900 focus:ring-offset-2 transition-colors">
                                <i class="fas fa-search mr-1"></i> Search
                            </button>
                            <?php if (!empty($search_query)): ?>
                                <a href="?<?php echo http_build_query(array_diff_key($_GET, ['search' => ''])); ?>" class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                    <i class="fas fa-times mr-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Agents Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <?php if (empty($agents)): ?>
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No agents found</h3>
                        <p class="text-gray-600">
                            <?php if (!empty($search_query)): ?>
                                No agents match your search criteria.
                            <?php elseif (!empty($status_filter)): ?>
                                No agents match the selected status.
                            <?php else: ?>
                                There are no agents in the system yet.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search_query) || !empty($status_filter)): ?>
                            <a href="./" class="inline-block mt-4 px-4 py-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                <i class="fas fa-times mr-1"></i> Clear all filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 text-left">Agent</th>
                                    <th class="px-6 py-3 text-left">Location</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($agents as $agent): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                    <?php if (!empty($agent['profile_image'])): ?>
                                                        <img src="../../<?php echo htmlspecialchars($agent['profile_image']); ?>" alt="Profile Image" class="w-full h-full rounded-full object-cover">
                                                    <?php else: ?>
                                                        <i class="fas fa-user text-indigo-700"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($agent['name']); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($agent['email']); ?></div>
                                                    <?php if (!empty($agent['phone'])): ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($agent['phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?php if (!empty($agent['main_community_name'])): ?>
                                                <div><span class="text-gray-500">Community:</span> <?php echo htmlspecialchars($agent['main_community_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($agent['smaller_community_name'])): ?>
                                                <div><span class="text-gray-500">Smaller Comm.:</span> <?php echo htmlspecialchars($agent['smaller_community_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($agent['suburb_name'])): ?>
                                                <div><span class="text-gray-500">Suburb:</span> <?php echo htmlspecialchars($agent['suburb_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($agent['cottage_name'])): ?>
                                                <div><span class="text-gray-500">Cottage:</span> <?php echo htmlspecialchars($agent['cottage_name']); ?></div>
                                            <?php endif; ?>
                                            <?php if (empty($agent['main_community_name']) && empty($agent['smaller_community_name']) && empty($agent['suburb_name']) && empty($agent['cottage_name'])): ?>
                                                <span class="text-gray-400">No location assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <?php echo getStatusBadge($agent['status'], $status_colors); ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php if ($agent['last_login']): ?>
                                                        Last login: <?php echo date('M d, Y', strtotime($agent['last_login'])); ?>
                                                    <?php else: ?>
                                                        Never logged in
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Added: <?php echo date('M d, Y', strtotime($agent['created_at'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <a href="./view_agent.php?id=<?php echo $agent['id']; ?>" class="p-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition" title="View Agent">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="./edit_agent.php?id=<?php echo $agent['id']; ?>" class="p-2 text-sm font-medium text-blue-900 bg-blue-50 rounded-lg hover:bg-blue-100 transition" title="Edit Agent">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="./toggle_agent_status.php?id=<?php echo $agent['id']; ?>&status=<?php echo $agent['status'] === 'active' ? 'inactive' : 'active'; ?>" onclick="return confirm('Are you sure you want to <?php echo $agent['status'] === 'active' ? 'deactivate' : 'activate'; ?> this agent?')" class="p-2 text-sm font-medium <?php echo $agent['status'] === 'active' ? 'text-red-900 bg-red-50 hover:bg-red-100' : 'text-green-900 bg-green-50 hover:bg-green-100'; ?> rounded-lg transition" title="<?php echo $agent['status'] === 'active' ? 'Deactivate Agent' : 'Activate Agent'; ?>">
                                                    <i class="fas <?php echo $agent['status'] === 'active' ? 'fa-user-times' : 'fa-user-check'; ?>"></i>
                                                </a>
                                                <a href="../users/reset_password.php?id=<?php echo $agent['id']; ?>" onclick="return confirm('Are you sure you want to reset password for this agent?')" class="p-2 text-sm font-medium text-yellow-900 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition" title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Showing <?php echo ($page - 1) * $per_page + 1; ?> to <?php echo min($page * $per_page, $total_agents); ?> of <?php echo $total_agents; ?> agents
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="<?php echo getPaginationUrl($page - 1); ?>" class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition">
                                            <i class="fas fa-chevron-left mr-1"></i> Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $range = 2;
                                    $show_dots = false;
                                    for ($i = 1; $i <= $total_pages; $i++) {
                                        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
                                            echo '<a href="' . getPaginationUrl($i) . '" class="px-3 py-1 text-sm font-medium ' . ($i == $page ? 'bg-indigo-900 text-white' : 'bg-gray-100 text-gray-600') . ' rounded-lg hover:bg-indigo-800 hover:text-white transition">' . $i . '</a>';
                                            $show_dots = true;
                                        } elseif ($show_dots) {
                                            echo '<span class="px-3 py-1 text-sm font-medium text-gray-600">...</span>';
                                            $show_dots = false;
                                        }
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="<?php echo getPaginationUrl($page + 1); ?>" class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition">
                                            Next <i class="fas fa-chevron-right ml-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle filter changes
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    const searchParams = new URLSearchParams(window.location.search);
                    searchParams.set('status', this.value);
                    window.location.search = searchParams.toString();
                });
            }
        });
    </script>
</body>

</html>
