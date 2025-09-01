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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Management - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php renderSidebar($current_page); ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php renderHeader("Officer Management"); ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Officer Management</h1>
                                <p class="mt-2 text-gray-600">Manage and monitor all officers in the system</p>
                            </div>
                            <a href="add_officer.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition duration-200">
                                <i class="fas fa-plus"></i>
                                Add New Officer
                            </a>
                        </div>
                    </div>

                    <!-- Message Display -->
                    <?php if (!empty($message)): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                            <div class="flex items-center">
                                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Filters and Search -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <form method="GET" class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Officers</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                                       placeholder="Search by name, email, or phone..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div class="md:w-48">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition duration-200">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Officers Table -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Officers (<?php echo $total_officers; ?>)
                            </h2>
                        </div>

                        <?php if (empty($officers)): ?>
                            <div class="p-8 text-center">
                                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No officers found</h3>
                                <p class="text-gray-600 mb-4">
                                    <?php if (!empty($search_query) || !empty($status_filter)): ?>
                                        No officers match your search criteria.
                                    <?php else: ?>
                                        Get started by adding your first officer.
                                    <?php endif; ?>
                                </p>
                                <a href="add_officer.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2 transition duration-200">
                                    <i class="fas fa-plus"></i>
                                    Add Officer
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($officers as $officer): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10">
                                                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                                                <span class="text-white font-medium text-sm">
                                                                    <?php echo strtoupper(substr($officer['name'], 0, 1)); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <?php echo htmlspecialchars($officer['name']); ?>
                                                            </div>
                                                            <div class="text-sm text-gray-500">
                                                                ID: <?php echo $officer['id']; ?>
                                                            </div>
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
                                                        $location_parts = array_filter([
                                                            $officer['main_community_name'],
                                                            $officer['smaller_community_name'],
                                                            $officer['suburb_name'],
                                                            $officer['cottage_name']
                                                        ]);
                                                        echo htmlspecialchars(implode(', ', $location_parts));
                                                        ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        <?php echo $officer['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                        <?php echo ucfirst($officer['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $officer['last_login'] ? date('M d, Y H:i', strtotime($officer['last_login'])) : 'Never'; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex items-center space-x-2">
                                                        <a href="view_officer.php?id=<?php echo $officer['id']; ?>" 
                                                           class="text-blue-600 hover:text-blue-900 p-1" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_officer.php?id=<?php echo $officer['id']; ?>" 
                                                           class="text-indigo-600 hover:text-indigo-900 p-1" title="Edit Officer">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button onclick="toggleStatus(<?php echo $officer['id']; ?>, '<?php echo $officer['status']; ?>')" 
                                                                class="text-yellow-600 hover:text-yellow-900 p-1" title="Toggle Status">
                                                            <i class="fas fa-<?php echo $officer['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
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
                                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-700">
                                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total_officers); ?> of <?php echo $total_officers; ?> officers
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <?php if ($page > 1): ?>
                                                <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search_query); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                    Previous
                                                </a>
                                            <?php endif; ?>

                                            <?php
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium <?php echo $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 bg-white hover:bg-gray-50'; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search_query); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                                    Next
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
        </div>
    </div>

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
                window.location.href = `toggle_officer_status.php?id=${currentOfficerId}&action=toggle`;
            }
        }

        // Close modal when clicking outside
        document.getElementById('toggleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
