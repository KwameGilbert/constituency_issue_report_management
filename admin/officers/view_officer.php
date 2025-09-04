<?php
// admin/officers/view_officer.php - View Officer Details
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'officers';

// Check if officer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ./');
    exit;
}

$officer_id = $_GET['id'];
$message = '';
$message_type = '';

// Fetch officer details
try {
    $sql = "
        SELECT u.*,
               mc.name as main_community_name,
               sc.name as smaller_community_name,
               sb.name as suburb_name,
               ct.name as cottage_name
        FROM users u
        LEFT JOIN communities mc ON u.main_community_id = mc.id
        LEFT JOIN smaller_communities sc ON u.smaller_community_id = sc.id
        LEFT JOIN suburbs sb ON u.suburb_id = sb.id
        LEFT JOIN cottages ct ON u.cottage_id = ct.id
        WHERE u.id = ? AND u.role = 'officer'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$officer_id]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        header('Location: ./');
        exit;
    }

    // Fetch officer's activity statistics
    $stats_sql = "
        SELECT
            COUNT(*) as total_issues,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_issues,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_issues,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_issues
        FROM issues
        WHERE officer_id = ?
    ";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute([$officer_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Count agents managed by this officer
    $agents_count_sql = "SELECT COUNT(*) as total_agents FROM users WHERE role = 'agent'";
    $agents_count = $conn->query($agents_count_sql)->fetch(PDO::FETCH_ASSOC)['total_agents'];

    // Fetch recent issues managed by this officer
    $recent_issues_sql = "
        SELECT i.id, i.title, i.status, i.created_at, i.updated_at,
               c.name as category_name, sec.name as sector_name,
               u.name as agent_name
        FROM issues i
        LEFT JOIN issue_categories c ON i.category_id = c.id
        LEFT JOIN issue_sectors sec ON i.sector_id = sec.id
        LEFT JOIN users u ON i.agent_id = u.id
        WHERE i.officer_id = ?
        ORDER BY i.created_at DESC
        LIMIT 10
    ";
    $recent_issues_stmt = $conn->prepare($recent_issues_sql);
    $recent_issues_stmt->execute([$officer_id]);
    $recent_issues = $recent_issues_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Error fetching officer details: " . $e->getMessage();
    $message_type = 'error';
    $officer = null;
    $stats = null;
    $recent_issues = [];
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-edit',
        'label' => 'Edit Officer',
        'href' => './edit_officer.php?id=' . $officer_id,
        'class' => 'bg-blue-600 text-white hover:bg-blue-700'
    ],
    [
        'icon' => 'fas fa-key',
        'label' => 'Reset Password',
        'href' => '../users/reset_password.php?id=' . $officer_id,
        'class' => 'bg-yellow-600 text-white hover:bg-yellow-700',
        'onclick' => "return confirm('Are you sure you want to reset password for this officer?')"
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Officers',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Get the logged-in user's name
$userName = $_SESSION['user_name'] ?? 'Administrator';

// Status colors for badges
$status_colors = [
    'active' => 'green',
    'inactive' => 'red'
];

// Issue status colors
$issue_status_colors = [
    'pending' => 'yellow',
    'reviewed' => 'blue',
    'approved' => 'green',
    'rejected' => 'red',
    'resolved' => 'green'
];

// Function to output status badge
function getStatusBadge($status, $status_colors) {
    $color = isset($status_colors[$status]) ? $status_colors[$status] : 'gray';
    $colors = [
        'green' => 'bg-green-100 text-green-800',
        'red' => 'bg-red-100 text-red-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'gray' => 'bg-gray-100 text-gray-800'
    ];

    $color_class = $colors[$color];

    return "<span class=\"px-2 py-1 text-xs font-medium rounded-full {$color_class}\">" . ucfirst(str_replace('_', ' ', $status)) . "</span>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Officer - Admin Dashboard</title>
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
        <?php renderAdminHeader('Officer Details', 'View officer information and activity', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($officer): ?>
                <!-- Officer Profile Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="p-6">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                                <?php if (!empty($officer['profile_image'])): ?>
                                    <img src="../../../<?php echo htmlspecialchars($officer['profile_image']); ?>" alt="Profile Image" class="w-full h-full rounded-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user-tie text-indigo-700 text-2xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($officer['name']); ?></h1>
                                <p class="text-gray-600"><?php echo htmlspecialchars($officer['email']); ?></p>
                                <div class="flex items-center space-x-4 mt-2">
                                    <?php echo getStatusBadge($officer['status'], $status_colors); ?>
                                    <span class="text-sm text-gray-500">Officer</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Contact Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Contact Information</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-envelope text-gray-400 w-5 mr-3"></i>
                                        <span><?php echo htmlspecialchars($officer['email']); ?></span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-phone text-gray-400 w-5 mr-3"></i>
                                        <span><?php echo htmlspecialchars($officer['phone']); ?></span>
                                    </div>
                                    <div class="flex items-center text-sm">
                                        <i class="fas fa-calendar text-gray-400 w-5 mr-3"></i>
                                        <span>Joined <?php echo date('M d, Y', strtotime($officer['created_at'])); ?></span>
                                    </div>
                                    <?php if ($officer['last_login']): ?>
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-clock text-gray-400 w-5 mr-3"></i>
                                            <span>Last login <?php echo date('M d, Y H:i', strtotime($officer['last_login'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Location Assignment</h3>
                                <div class="space-y-2">
                                    <?php if (!empty($officer['main_community_name'])): ?>
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-map-marker-alt text-gray-400 w-5 mr-3"></i>
                                            <span><?php echo htmlspecialchars($officer['main_community_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($officer['smaller_community_name'])): ?>
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-map-pin text-gray-400 w-5 mr-3"></i>
                                            <span><?php echo htmlspecialchars($officer['smaller_community_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($officer['suburb_name'])): ?>
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-home text-gray-400 w-5 mr-3"></i>
                                            <span><?php echo htmlspecialchars($officer['suburb_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($officer['cottage_name'])): ?>
                                        <div class="flex items-center text-sm">
                                            <i class="fas fa-building text-gray-400 w-5 mr-3"></i>
                                            <span><?php echo htmlspecialchars($officer['cottage_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (empty($officer['main_community_name']) && empty($officer['smaller_community_name']) && empty($officer['suburb_name']) && empty($officer['cottage_name'])): ?>
                                        <span class="text-gray-400 text-sm">No location assigned</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Activity Statistics</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-indigo-600"><?php echo $stats['total_issues'] ?? 0; ?></div>
                                        <div class="text-xs text-gray-500">Total Issues</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600"><?php echo $stats['resolved_issues'] ?? 0; ?></div>
                                        <div class="text-xs text-gray-500">Resolved</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_issues'] ?? 0; ?></div>
                                        <div class="text-xs text-gray-500">Pending</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600"><?php echo $stats['recent_issues'] ?? 0; ?></div>
                                        <div class="text-xs text-gray-500">Last 30 Days</div>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-purple-600"><?php echo $agents_count ?? 0; ?></div>
                                        <div class="text-xs text-gray-500">Agents Managed</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Issues -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Issues Managed</h3>

                        <?php if (empty($recent_issues)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-inbox text-gray-400 text-3xl mb-4"></i>
                                <p class="text-gray-500">No issues found for this officer</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-6 py-3 text-left">Issue</th>
                                            <th class="px-6 py-3 text-left">Category</th>
                                            <th class="px-6 py-3 text-left">Agent</th>
                                            <th class="px-6 py-3 text-left">Status</th>
                                            <th class="px-6 py-3 text-left">Created</th>
                                            <th class="px-6 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($recent_issues as $issue): ?>
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-6 py-4">
                                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($issue['title']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($issue['category_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($issue['agent_name'] ?? 'Unassigned'); ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <?php echo getStatusBadge($issue['status'], $issue_status_colors); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <a href="../issues/view_issue.php?id=<?php echo $issue['id']; ?>" class="p-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition" title="View Issue">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-times text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">Officer Not Found</h3>
                    <p class="text-gray-600 mb-4">The officer you're looking for doesn't exist or has been removed.</p>
                    <a href="./" class="inline-block px-4 py-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Officers
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>
