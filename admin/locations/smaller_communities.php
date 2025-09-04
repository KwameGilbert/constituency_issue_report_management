<?php
// admin/location/smaller_communities.php - Smaller Communities Management
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
    if (isset($_POST['add_smaller_community'])) {
        // Add new smaller community
        $name = trim($_POST['name']);
        $suburb_id = $_POST['suburb_id'];
        
        if (!empty($name) && !empty($suburb_id)) {
            try {
                $stmt = $conn->prepare("INSERT INTO smaller_communities (name, suburb_id) VALUES (?, ?)");
                $stmt->execute([$name, $suburb_id]);
                $message = "Smaller community '{$name}' has been added successfully.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error adding smaller community: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Smaller community name and suburb are required.";
            $message_type = 'error';
        }
    } elseif (isset($_POST['edit_smaller_community'])) {
        // Edit smaller community
        $id = $_POST['smaller_community_id'];
        $name = trim($_POST['name']);
        $suburb_id = $_POST['suburb_id'];
        
        if (!empty($name) && !empty($suburb_id)) {
            try {
                $stmt = $conn->prepare("UPDATE smaller_communities SET name = ?, suburb_id = ? WHERE id = ?");
                $stmt->execute([$name, $suburb_id, $id]);
                $message = "Smaller community has been updated successfully.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error updating smaller community: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Smaller community name and suburb are required.";
            $message_type = 'error';
        }
    }
}

// Delete smaller community (via GET request with confirmation)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Check if this smaller community is used in cottages or other dependencies
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cottages WHERE smaller_community_id = ?");
        $stmt->execute([$id]);
        $dependent_count = $stmt->fetchColumn();
        
        if ($dependent_count > 0) {
            $message = "Cannot delete smaller community because it has {$dependent_count} cottages associated with it.";
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM smaller_communities WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Smaller community has been deleted successfully.";
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = "Error deleting smaller community: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Get all suburbs for dropdown
try {
    $stmt = $conn->prepare("
        SELECT 
            s.id, 
            s.name AS suburb_name,
            c.name AS community_name
        FROM 
            suburbs s
        JOIN 
            communities c ON s.community_id = c.id
        ORDER BY 
            c.name ASC, s.name ASC
    ");
    $stmt->execute();
    $suburbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $suburbs = [];
}

// Get filter parameters
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$suburb_filter = isset($_GET['suburb']) ? $_GET['suburb'] : '';
$community_filter = isset($_GET['community']) ? $_GET['community'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Get all communities for filter dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM communities ORDER BY name ASC");
    $stmt->execute();
    $communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $communities = [];
}

// Prepare WHERE clause based on filters
$where_clauses = [];
$params = [];

if (!empty($search_query)) {
    $where_clauses[] = "sc.name LIKE ?";
    $search_term = "%{$search_query}%";
    $params[] = $search_term;
}

if (!empty($suburb_filter)) {
    $where_clauses[] = "sc.suburb_id = ?";
    $params[] = $suburb_filter;
}

if (!empty($community_filter)) {
    $where_clauses[] = "c.id = ?";
    $params[] = $community_filter;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total smaller communities for pagination
try {
    $count_sql = "
        SELECT COUNT(*) 
        FROM smaller_communities sc
        JOIN suburbs s ON sc.suburb_id = s.id
        JOIN communities c ON s.community_id = c.id
        {$where_clause}
    ";
    $stmt = $conn->prepare($count_sql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $total_smaller_communities = $stmt->fetchColumn();
    $total_pages = ceil($total_smaller_communities / $per_page);
    
    // Ensure page is within valid range
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    // Query to fetch smaller communities with pagination and related data
    $sql = "
        SELECT 
            sc.id, 
            sc.name, 
            sc.created_at,
            sc.suburb_id,
            s.name AS suburb_name,
            c.id AS community_id,
            c.name AS community_name,
            (SELECT COUNT(*) FROM cottages WHERE smaller_community_id = sc.id) AS cottage_count
        FROM 
            smaller_communities sc
        JOIN 
            suburbs s ON sc.suburb_id = s.id
        JOIN 
            communities c ON s.community_id = c.id
        {$where_clause}
        ORDER BY 
            c.name ASC, s.name ASC, sc.name ASC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters for main query
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $smaller_communities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error fetching smaller communities: " . $e->getMessage();
    $message_type = 'error';
    $smaller_communities = [];
    $total_pages = 0;
    $total_smaller_communities = 0;
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-plus',
        'label' => 'Add Smaller Community',
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
    <title>Smaller Communities Management - Admin Dashboard</title>
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
        <?php renderAdminHeader('Smaller Communities', 'Manage smaller communities within suburbs', $headerActionButtons); ?>

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
                            <h2 class="text-lg font-medium text-gray-800">Smaller Communities</h2>
                            <p class="text-sm text-gray-500 mt-1">Showing <?php echo number_format($total_smaller_communities); ?> smaller communities</p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <div class="mt-4">
                        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" class="block w-full p-2.5 pl-10 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search by name...">
                            </div>
                            <div>
                                <select name="community" class="block w-full p-2.5 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Communities</option>
                                    <?php foreach ($communities as $community): ?>
                                        <option value="<?php echo $community['id']; ?>" <?php echo ($community_filter == $community['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($community['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <select name="suburb" class="block w-full p-2.5 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">All Suburbs</option>
                                    <?php foreach ($suburbs as $suburb): ?>
                                        <option value="<?php echo $suburb['id']; ?>" <?php echo ($suburb_filter == $suburb['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($suburb['suburb_name'] . ' (' . $suburb['community_name'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex space-x-2">
                                <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                                    Apply Filters
                                </button>
                                <?php if (!empty($search_query) || !empty($community_filter) || !empty($suburb_filter)): ?>
                                    <a href="?" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 focus:ring-2 focus:ring-gray-300 focus:ring-opacity-50">
                                        Clear Filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                     
                    <!-- Add Button -->
                    <div class="mt-4 flex justify-end">
                        <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Add New Smaller Community
                        </button>
                    </div>
                </div>
            </div>

            <!-- Smaller Communities Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <?php if (empty($smaller_communities)): ?>
                    <div class="p-6 text-center">
                        <div class="py-8">
                            <div class="mb-4">
                                <i class="fas fa-map-marker-alt text-4xl text-gray-300"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">No smaller communities found</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                <?php if (!empty($search_query) || !empty($community_filter) || !empty($suburb_filter)): ?>
                                    No smaller communities match your search criteria. Try different filters.
                                <?php else: ?>
                                    There are no smaller communities in the system yet.
                                <?php endif; ?>
                            </p>
                            <div class="mt-6">
                                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-plus mr-2"></i> Add New Smaller Community
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
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Suburb</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Community</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Cottages</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-4 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($smaller_communities as $sc): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                                    <i class="fas fa-map-pin"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sc['name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <a href="?suburb=<?php echo $sc['suburb_id']; ?>" class="text-indigo-600 hover:underline">
                                                    <?php echo htmlspecialchars($sc['suburb_name']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <a href="?community=<?php echo $sc['community_id']; ?>" class="text-indigo-600 hover:underline">
                                                    <?php echo htmlspecialchars($sc['community_name']); ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?php if ($sc['cottage_count'] > 0): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                        <?php echo $sc['cottage_count']; ?> cottage<?php echo $sc['cottage_count'] === 1 ? '' : 's'; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">No cottages</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($sc['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex space-x-3">
                                                <button onclick="openEditModal(
                                                    <?php echo $sc['id']; ?>, 
                                                    '<?php echo htmlspecialchars(addslashes($sc['name'])); ?>', 
                                                    <?php echo $sc['suburb_id']; ?>
                                                )" class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($sc['cottage_count'] == 0): ?>
                                                    <button onclick="confirmDelete(<?php echo $sc['id']; ?>, '<?php echo htmlspecialchars(addslashes($sc['name'])); ?>')" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-300 cursor-not-allowed" title="Cannot delete - has cottages">
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
                                        Showing <span class="font-medium"><?php echo ($page - 1) * $per_page + 1; ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_smaller_communities); ?></span> of <span class="font-medium"><?php echo $total_smaller_communities; ?></span> smaller communities
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

    <!-- Add Smaller Community Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add New Smaller Community</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="add_suburb_id" class="block text-sm font-medium text-gray-700 mb-2">Suburb</label>
                        <select id="add_suburb_id" name="suburb_id" class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Suburb</option>
                            <?php foreach ($suburbs as $suburb): ?>
                                <option value="<?php echo $suburb['id']; ?>">
                                    <?php echo htmlspecialchars($suburb['suburb_name'] . ' (' . $suburb['community_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="add_name" class="block text-sm font-medium text-gray-700 mb-2">Smaller Community Name</label>
                        <input type="text" id="add_name" name="name" class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="add_smaller_community" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Add Smaller Community
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Smaller Community Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Smaller Community</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" id="edit_smaller_community_id" name="smaller_community_id" value="">
                    <div class="mb-4">
                        <label for="edit_suburb_id" class="block text-sm font-medium text-gray-700 mb-2">Suburb</label>
                        <select id="edit_suburb_id" name="suburb_id" class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">Select Suburb</option>
                            <?php foreach ($suburbs as $suburb): ?>
                                <option value="<?php echo $suburb['id']; ?>">
                                    <?php echo htmlspecialchars($suburb['suburb_name'] . ' (' . $suburb['community_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-2">Smaller Community Name</label>
                        <input type="text" id="edit_name" name="name" class="w-full p-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="edit_smaller_community" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
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
                    <p class="text-sm text-gray-600" id="deleteMessage">Are you sure you want to delete this smaller community?</p>
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
        function openEditModal(id, name, suburbId) {
            document.getElementById('edit_smaller_community_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_suburb_id').value = suburbId;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Delete Modal Functions
        function confirmDelete(id, name) {
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the smaller community "${name}"?`;
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
