<?php
// admin/location/index.php - Location Management Dashboard
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

// Count statistics for all location types
try {
    // Communities count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM communities");
    $stmt->execute();
    $communities_count = $stmt->fetchColumn();
    
    // Smaller Communities count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM smaller_communities");
    $stmt->execute();
    $smaller_communities_count = $stmt->fetchColumn();
    
    // Suburbs count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM suburbs");
    $stmt->execute();
    $suburbs_count = $stmt->fetchColumn();
    
    // Cottages count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cottages");
    $stmt->execute();
    $cottages_count = $stmt->fetchColumn();
    
    // Total locations count
    $total_locations = $communities_count + $smaller_communities_count + $suburbs_count + $cottages_count;
    
    // Recent locations (combined from all tables)
    $recent_locations_query = "
        (SELECT 'community' as type, name, created_at FROM communities ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'smaller_community' as type, name, created_at FROM smaller_communities ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'suburb' as type, name, created_at FROM suburbs ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'cottage' as type, name, created_at FROM cottages ORDER BY created_at DESC LIMIT 5)
        ORDER BY created_at DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($recent_locations_query);
    $stmt->execute();
    $recent_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Error fetching location statistics: " . $e->getMessage();
    $message_type = 'error';
    $communities_count = $smaller_communities_count = $suburbs_count = $cottages_count = $total_locations = 0;
    $recent_locations = [];
}

// Get counts for sidebar
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-building',
        'label' => 'Communities',
        'href' => 'communities.php',
        'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'
    ],
    [
        'icon' => 'fas fa-home',
        'label' => 'Smaller Communities',
        'href' => 'smaller_communities.php',
        'class' => 'bg-blue-600 text-white hover:bg-blue-700'
    ],
    [
        'icon' => 'fas fa-map-marker-alt',
        'label' => 'Suburbs',
        'href' => 'suburbs.php',
        'class' => 'bg-green-600 text-white hover:bg-green-700'
    ],
    [
        'icon' => 'fas fa-house-user',
        'label' => 'Cottages',
        'href' => 'cottages.php',
        'class' => 'bg-amber-600 text-white hover:bg-amber-700'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Location Management - Admin Dashboard</title>
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
        <?php renderAdminHeader('Location Management', 'Manage all geographical locations in the system', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                <!-- Total Locations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Total Locations</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_locations); ?></h3>
                        </div>
                        <div class="bg-indigo-100 text-indigo-600 rounded-lg p-3">
                            <i class="fas fa-map-marked-alt fa-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Communities -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Communities</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($communities_count); ?></h3>
                        </div>
                        <div class="bg-indigo-100 text-indigo-600 rounded-lg p-3">
                            <i class="fas fa-building fa-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Smaller Communities -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Smaller Communities</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($smaller_communities_count); ?></h3>
                        </div>
                        <div class="bg-blue-100 text-blue-600 rounded-lg p-3">
                            <i class="fas fa-home fa-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Suburbs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Suburbs</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($suburbs_count); ?></h3>
                        </div>
                        <div class="bg-green-100 text-green-600 rounded-lg p-3">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Cottages -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Cottages</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($cottages_count); ?></h3>
                        </div>
                        <div class="bg-amber-100 text-amber-600 rounded-lg p-3">
                            <i class="fas fa-house-user fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Location Management Cards -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Manage Locations</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a href="communities.php" class="block p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 rounded-xl border border-indigo-200 transition-all">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-building fa-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-medium text-gray-800">Communities</h3>
                                    <p class="text-xs text-gray-500 mt-1">Manage main communities</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="smaller_communities.php" class="block p-4 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl border border-blue-200 transition-all">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-home fa-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-medium text-gray-800">Smaller Communities</h3>
                                    <p class="text-xs text-gray-500 mt-1">Manage smaller communities</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="suburbs.php" class="block p-4 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-xl border border-green-200 transition-all">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-map-marker-alt fa-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-medium text-gray-800">Suburbs</h3>
                                    <p class="text-xs text-gray-500 mt-1">Manage suburb locations</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="cottages.php" class="block p-4 bg-gradient-to-br from-amber-50 to-amber-100 hover:from-amber-100 hover:to-amber-200 rounded-xl border border-amber-200 transition-all">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-house-user fa-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-medium text-gray-800">Cottages</h3>
                                    <p class="text-xs text-gray-500 mt-1">Manage cottage locations</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Locations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Recently Added Locations</h2>
                    <?php if (empty($recent_locations)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-map text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No locations have been added yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="text-xs font-medium text-gray-500 border-b border-gray-100">
                                    <tr>
                                        <th class="px-3 py-3 text-left">Location</th>
                                        <th class="px-3 py-3 text-left">Type</th>
                                        <th class="px-3 py-3 text-left">Added</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($recent_locations as $location): ?>
                                        <tr class="text-sm">
                                            <td class="px-3 py-3 font-medium"><?php echo htmlspecialchars($location['name']); ?></td>
                                            <td class="px-3 py-3">
                                                <?php 
                                                    $type_badges = [
                                                        'community' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">Community</span>',
                                                        'smaller_community' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Smaller Community</span>',
                                                        'suburb' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Suburb</span>',
                                                        'cottage' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">Cottage</span>'
                                                    ];
                                                    echo $type_badges[$location['type']] ?? $location['type'];
                                                ?>
                                            </td>
                                            <td class="px-3 py-3 text-gray-500">
                                                <?php echo date('M d, Y', strtotime($location['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Location Relationships -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Location Hierarchy</h2>
                <div class="max-w-xl mx-auto">
                    <div class="relative py-6">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="h-full w-1 bg-indigo-200"></div>
                        </div>
                        
                        <div class="relative flex items-center justify-between mb-12">
                            <div class="w-5/12 text-right pr-8">
                                <h3 class="text-lg font-medium text-indigo-600">Communities</h3>
                                <p class="text-sm text-gray-600 mt-1">Main geographical areas in the constituency</p>
                                <a href="communities.php" class="inline-block mt-2 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                    Manage Communities →
                                </a>
                            </div>
                            <div class="z-10 flex items-center justify-center w-10 h-10 bg-indigo-500 rounded-full text-white text-lg font-bold">
                                1
                            </div>
                            <div class="w-5/12 pl-8">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i> 
                                    <?php echo number_format($communities_count); ?> communities
                                </div>
                            </div>
                        </div>
                        
                        <div class="relative flex items-center justify-between mb-12">
                            <div class="w-5/12 text-right pr-8">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i> 
                                    <?php echo number_format($smaller_communities_count); ?> smaller communities
                                </div>
                            </div>
                            <div class="z-10 flex items-center justify-center w-10 h-10 bg-blue-500 rounded-full text-white text-lg font-bold">
                                2
                            </div>
                            <div class="w-5/12 pl-8">
                                <h3 class="text-lg font-medium text-blue-600">Smaller Communities</h3>
                                <p class="text-sm text-gray-600 mt-1">Subdivisions within the constituency</p>
                                <a href="smaller_communities.php" class="inline-block mt-2 text-xs font-medium text-blue-600 hover:text-blue-800">
                                    Manage Smaller Communities →
                                </a>
                            </div>
                        </div>
                        
                        <div class="relative flex items-center justify-between mb-12">
                            <div class="w-5/12 text-right pr-8">
                                <h3 class="text-lg font-medium text-green-600">Suburbs</h3>
                                <p class="text-sm text-gray-600 mt-1">Residential areas within communities</p>
                                <a href="suburbs.php" class="inline-block mt-2 text-xs font-medium text-green-600 hover:text-green-800">
                                    Manage Suburbs →
                                </a>
                            </div>
                            <div class="z-10 flex items-center justify-center w-10 h-10 bg-green-500 rounded-full text-white text-lg font-bold">
                                3
                            </div>
                            <div class="w-5/12 pl-8">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i> 
                                    <?php echo number_format($suburbs_count); ?> suburbs
                                </div>
                            </div>
                        </div>
                        
                        <div class="relative flex items-center justify-between">
                            <div class="w-5/12 text-right pr-8">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-check-circle text-green-500 mr-1"></i> 
                                    <?php echo number_format($cottages_count); ?> cottages
                                </div>
                            </div>
                            <div class="z-10 flex items-center justify-center w-10 h-10 bg-amber-500 rounded-full text-white text-lg font-bold">
                                4
                            </div>
                            <div class="w-5/12 pl-8">
                                <h3 class="text-lg font-medium text-amber-600">Cottages</h3>
                                <p class="text-sm text-gray-600 mt-1">Individual housing units within smaller communities</p>
                                <a href="cottages.php" class="inline-block mt-2 text-xs font-medium text-amber-600 hover:text-amber-800">
                                    Manage Cottages →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
