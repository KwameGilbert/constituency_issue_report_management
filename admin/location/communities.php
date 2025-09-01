<?php
// admin/location/communities.php - Communities Management
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'locations';

// Initialize message variables
$message = '';
$message_type = '';

// Process GET parameters for messages
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = isset($_GET['type']) && $_GET['type'] === 'success' ? 'success' : 'error';
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_community'])) {
        // Add new community
        $name = trim($_POST['name']);
        
        if (!empty($name)) {
            try {
                $stmt = $conn->prepare("INSERT INTO communities (name) VALUES (?)");
                $stmt->execute([$name]);
                $message = "Community '{$name}' has been added successfully.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error adding community: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Community name cannot be empty.";
            $message_type = 'error';
        }
    } elseif (isset($_POST['edit_community'])) {
        // Edit community
        $id = $_POST['community_id'];
        $name = trim($_POST['name']);
        
        if (!empty($name)) {
            try {
                $stmt = $conn->prepare("UPDATE communities SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $message = "Community has been updated successfully.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error updating community: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Community name cannot be empty.";
            $message_type = 'error';
        }
    }
}

// Delete community (via GET request with confirmation)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Check if this community is used in suburbs
        $stmt = $conn->prepare("SELECT COUNT(*) FROM suburbs WHERE community_id = ?");
        $stmt->execute([$id]);
        $suburb_count = $stmt->fetchColumn();
        
        if ($suburb_count > 0) {
            $message = "Cannot delete community because it has {$suburb_count} suburbs associated with it.";
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM communities WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Community has been deleted successfully.";
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = "Error deleting community: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get filter parameters
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Prepare WHERE clause based on filters
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "name LIKE ?";
    $search_term = "%{$search_query}%";
    $params[] = $search_term;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total communities for pagination
try {
    $count_sql = "SELECT COUNT(*) FROM communities {$where_clause}";
    $stmt = $conn->prepare($count_sql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total_communities = $stmt->fetchColumn();
    $total_pages = ceil($total_communities / $per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // Query to fetch communities with pagination
    $sql = "
        SELECT c.id, c.name, c.created_at,
               (SELECT COUNT(*) FROM suburbs WHERE community_id = c.id) AS suburb_count
        FROM communities c
        {$where_clause}
        ORDER BY c.name ASC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters for main query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error fetching communities: " . $e->getMessage();
    $message_type = 'error';
    $communities = [];
    $total_pages = 0;
    $total_communities = 0;
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'Add Community',
        'href' => '#',
        'onclick' => 'openAddModal()',
        'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Locations',
        'href' => './index.php',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Get counts for sidebar
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

// Define the pagination URL function
function getPaginationUrl($page, $current_params = []) {
    // Start with the base URL
    $url = '?';
    
    // Add all current GET parameters except 'page'
    foreach ($_GET as $key => $value) {
        if ($key !== 'page' && $key !== 'message' && $key !== 'type' && $key !== 'delete' && !array_key_exists($key, $current_params)) {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Communities Management - Admin Dashboard</title>
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
    <?php renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('Communities', 'Manage main communities in the constituency', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-4 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-medium text-gray-800">Communities</h2>
                            <p class="text-sm text-gray-500 mt-1">Showing <?php echo number_format($total_communities); ?> communities</p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="mt-4">
                        <form method="GET" class="flex flex-wrap gap-3">
                            <div class="flex-grow">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" class="block w-full p-2.5 pl-10 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search communities by name...">
                                    <?php if (!empty($search_query)): ?>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <a href="?" class="text-gray-400 hover:text-gray-500">
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

            <!-- Communities Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <?php if (empty($communities)): ?>
                    <div class="p-6 text-center">
                        <div class="py-8">
                            <div class="mb-4">
                                <i class="fas fa-building text-4xl text-gray-300"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">No communities found</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                <?php if (!empty($search_query)): ?>
                                    No communities match your search criteria. Try a different search term.
                                <?php else: ?>
                                    There are no communities in the system yet.
                                <?php endif; ?>
                            </p>
                            <div class="mt-6">
                                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-plus mr-2"></i> Add New Community
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Suburbs</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($communities as $community): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($community['name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?php if ($community['suburb_count'] > 0): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo $community['suburb_count']; ?> suburb<?php echo $community['suburb_count'] === 1 ? '' : 's'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">No suburbs</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($community['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-3">
                                                <button onclick="openEditModal(<?php echo $community['id']; ?>, '<?php echo htmlspecialchars(addslashes($community['name'])); ?>')" class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($community['suburb_count'] == 0): ?>
                                                    <button onclick="confirmDelete(<?php echo $community['id']; ?>, '<?php echo htmlspecialchars(addslashes($community['name'])); ?>')" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-300 cursor-not-allowed" title="Cannot delete - has suburbs">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </span>
                                                <?php endif; ?>
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
                                        Showing <span class="font-medium"><?php echo ($page - 1) * $per_page + 1; ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_communities); ?></span> of <span class="font-medium"><?php echo $total_communities; ?></span> communities
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

    <!-- Add Community Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add New Community</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Community Name</label>
                        <input type="text" id="name" name="name" class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="add_community" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Add Community
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Community Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Community</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" id="edit_community_id" name="community_id" value="">
                    <div class="mb-4">
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">Community Name</label>
                        <input type="text" id="edit_name" name="name" class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="edit_community" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
                    <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-600" id="deleteMessage">Are you sure you want to delete this community?</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <a href="#" id="confirmDeleteButton" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 inline-block text-center">
                        Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add Modal Functions
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        
        // Edit Modal Functions
        function openEditModal(id, name) {
            document.getElementById('edit_community_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Delete Modal Functions
        function confirmDelete(id, name) {
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the community "${name}"?`;
            document.getElementById('confirmDeleteButton').href = `?delete=${id}`;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (e.target === addModal) {
                closeAddModal();
            } else if (e.target === editModal) {
                closeEditModal();
            } else if (e.target === deleteModal) {
                closeDeleteModal();
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
