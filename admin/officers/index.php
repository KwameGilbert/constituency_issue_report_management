<?php
// admin/officers/index.php - Admin Officer Management
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'officers';

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
$where_clauses = ["role = 'officer'"]; // Only officers
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

// Count total officers for pagination
try {
    $count_sql = "SELECT COUNT(*) FROM users WHERE {$where_clause}";
    $stmt = $conn->prepare($count_sql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total_officers = $stmt->fetchColumn();
    $total_pages = ceil($total_officers / $per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // Query to fetch officers with pagination
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
    $officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error fetching officers: " . $e->getMessage();
    $message_type = 'error';
    $officers = [];
    $total_pages = 0;
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'Add New Officer',
        'href' => './add_officer.php',
        'class' => 'bg-indigo-900 text-white hover:bg-indigo-800'
    ],
    [
        'icon' => 'fas fa-download',
        'label' => 'Export Officers',
        'href' => '../reports/export.php?type=officers',
        'class' => 'bg-green-600 text-white hover:bg-green-700'
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Dashboard',
        'href' => '../dashboard/',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Get status counts for filter
try {
    $status_count_sql = "SELECT status, COUNT(*) as count FROM users WHERE role = 'officer' GROUP BY status";
    $stmt = $conn->prepare($status_count_sql);
    $stmt->execute();
    $status_counts = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    // Get total count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'officer'");
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
    <title>Officer Management - Admin Dashboard</title>
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
                        slate: {}
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
        <?php renderAdminHeader('Officer Management', 'Manage all system officers', $headerActionButtons); ?>

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
                        <div>
                            <h2 class="text-lg font-medium text-gray-800">Officers</h2>
                            <p class="text-sm text-gray-500 mt-1">Total: <?php echo $total_officers; ?> officers</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <a href="?status=" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo $status_filter === '' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                All (<?php echo $status_counts['all'] ?? 0; ?>)
                            </a>
                            <a href="?status=active" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo $status_filter === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                Active (<?php echo $status_counts['active'] ?? 0; ?>)
                            </a>
                            <a href="?status=inactive" class="px-4 py-2 text-sm font-medium rounded-lg <?php echo $status_filter === 'inactive' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                Inactive (<?php echo $status_counts['inactive'] ?? 0; ?>)
                            </a>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="mt-4">
                        <form method="GET" class="flex flex-wrap gap-3">
                            <?php if (!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <div class="flex-grow">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" class="block w-full p-2.5 pl-10 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search officers by name, email or phone...">
                                    <?php if (!empty($search_query)): ?>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <a href="?<?php echo !empty($status_filter) ? 'status=' . htmlspecialchars($status_filter) : ''; ?>" class="text-gray-400 hover:text-gray-500">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Officers Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <?php if (empty($officers)): ?>
                    <div class="p-6 text-center">
                        <div class="py-8">
                            <div class="mb-4">
                                <i class="fas fa-user-shield text-4xl text-gray-300"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">No officers found</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                <?php if (!empty($search_query)): ?>
                                    No officers match your search criteria. Try a different search term.
                                <?php elseif (!empty($status_filter)): ?>
                                    No officers with status: <?php echo ucfirst($status_filter); ?>
                                <?php else: ?>
                                    There are no officers in the system yet.
                                <?php endif; ?>
                            </p>
                            <div class="mt-6">
                                <a href="./add_officer.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-plus mr-2"></i> Add New Officer
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($officers as $officer): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600">
                                                    <?php echo substr($officer['name'], 0, 2); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($officer['name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($officer['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($officer['phone']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php
                                                $locations = [];
                                                if (!empty($officer['main_community_name'])) $locations[] = $officer['main_community_name'];
                                                if (!empty($officer['smaller_community_name'])) $locations[] = $officer['smaller_community_name'];
                                                if (!empty($officer['suburb_name'])) $locations[] = $officer['suburb_name'];
                                                if (!empty($officer['cottage_name'])) $locations[] = $officer['cottage_name'];
                                                echo !empty($locations) ? htmlspecialchars(implode(' / ', $locations)) : '<span class="text-gray-400">No location assigned</span>';
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo getStatusBadge($officer['status'], $status_colors); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($officer['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="./view_officer.php?id=<?php echo $officer['id']; ?>" class="text-indigo-600 hover:text-indigo-900 p-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="./edit_officer.php?id=<?php echo $officer['id']; ?>" class="text-blue-600 hover:text-blue-900 p-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="toggleStatus(<?php echo $officer['id']; ?>, '<?php echo $officer['status']; ?>')" class="<?php echo $officer['status'] === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?> p-1">
                                                    <i class="fas <?php echo $officer['status'] === 'active' ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 bg-white border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing <span class="font-medium"><?php echo ($page - 1) * $per_page + 1; ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_officers); ?></span> of <span class="font-medium"><?php echo $total_officers; ?></span> results
                                    </p>
                                </div>
                                <div>
                                    <nav class="flex items-center space-x-1">
                                        <?php if ($page > 1): ?>
                                            <a href="<?php echo getPaginationUrl(1); ?>" class="px-3 py-1 text-gray-500 hover:text-indigo-600 hover:bg-gray-50 rounded">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                            <a href="<?php echo getPaginationUrl($page - 1); ?>" class="px-3 py-1 text-gray-500 hover:text-indigo-600 hover:bg-gray-50 rounded">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);

                                        for ($i = $start_page; $i <= $end_page; $i++):
                                        ?>
                                            <a href="<?php echo getPaginationUrl($i); ?>" class="px-3 py-1 <?php echo $i === $page ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-500 hover:text-indigo-600 hover:bg-gray-50'; ?> rounded">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <a href="<?php echo getPaginationUrl($page + 1); ?>" class="px-3 py-1 text-gray-500 hover:text-indigo-600 hover:bg-gray-50 rounded">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                            <a href="<?php echo getPaginationUrl($total_pages); ?>" class="px-3 py-1 text-gray-500 hover:text-indigo-600 hover:bg-gray-50 rounded">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Toggle Status Modal -->
    <div id="toggleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Confirm Action</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-600" id="modalMessage">Are you sure you want to perform this action?</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button onclick="confirmToggle()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentOfficerId = null;
        let currentStatus = null;

        function toggleStatus(officerId, status) {
            currentOfficerId = officerId;
            currentStatus = status;
            
            const modal = document.getElementById('toggleModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            
            if (status === 'active') {
                modalTitle.textContent = 'Deactivate Officer';
                modalMessage.textContent = 'Are you sure you want to deactivate this officer? They will no longer be able to access the system.';
            } else {
                modalTitle.textContent = 'Activate Officer';
                modalMessage.textContent = 'Are you sure you want to activate this officer? They will regain access to the system.';
            }
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('toggleModal').classList.add('hidden');
            currentOfficerId = null;
            currentStatus = null;
        }

        function confirmToggle() {
            if (currentOfficerId && currentStatus) {
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                window.location.href = `toggle_officer_status.php?id=${currentOfficerId}&status=${newStatus}`;
            }
        }

        // Close modal when clicking outside
        document.getElementById('toggleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Handle sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>
