<?php
// users/view_user.php - View User Details
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'users';

// Initialize message variables
$message = '';
$message_type = '';

// Process GET parameters for messages
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = $_GET['message'];
    $message_type = isset($_GET['type']) && $_GET['type'] === 'success' ? 'success' : 'error';
}

// Get user ID from query parameter
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: ./index.php?message=Invalid+user+ID&type=error');
    exit;
}

// Fetch user data
try {
    $stmt = $conn->prepare("
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
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ./index.php?message=User+not+found&type=error');
        exit;
    }
} catch (Exception $e) {
    header('Location: ./index.php?message=' . urlencode('Error fetching user data: ' . $e->getMessage()) . '&type=error');
    exit;
}

// Get user statistics based on role
$stats = [];

try {
    switch ($user['role']) {
        case 'agent':
            // Get total issues reported by this agent
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_issues,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
                FROM issues 
                WHERE agent_id = ?
            ");
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            break;

        case 'officer':
            // Get issues managed by this officer
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_issues,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
                FROM issues 
                WHERE officer_id = ?
            ");
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            break;

        case 'admin':
        case 'mp':
        case 'mce':
        case 'pa':
            // Get activity count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_activities
                FROM activity_logs 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $stats['total_activities'] = $stmt->fetchColumn();
            break;
    }
} catch (Exception $e) {
    $stats = [];
}

// Get recent activity logs
try {
    $stmt = $conn->prepare("
        SELECT action, details, created_at
        FROM activity_logs 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $activities = [];
}

// Role labels and colors for badges
$role_labels = [
    'mp' => ['label' => 'Member of Parliament', 'color' => 'blue'],
    'mce' => ['label' => 'Municipal Chief Executive', 'color' => 'purple'],
    'pa' => ['label' => 'Personal Assistant', 'color' => 'indigo'],
    'officer' => ['label' => 'Officer', 'color' => 'green'],
    'agent' => ['label' => 'Agent', 'color' => 'yellow'],
    'admin' => ['label' => 'Admin', 'color' => 'red']
];

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-edit',
        'label' => 'Edit User',
        'href' => './edit_user.php?id=' . $user_id,
        'class' => 'bg-blue-600 text-white hover:bg-blue-700'
    ],
    [
        'icon' => $user['status'] === 'active' ? 'fas fa-user-times' : 'fas fa-user-check',
        'label' => $user['status'] === 'active' ? 'Deactivate User' : 'Activate User',
        'href' => './toggle_user_status.php?id=' . $user_id . '&status=' . ($user['status'] === 'active' ? 'inactive' : 'active'),
        'class' => $user['status'] === 'active' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700'
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Back to Users',
        'href' => './',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300'
    ]
];

// Get the logged-in user's name
$userName = $_SESSION['user_name'] ?? 'Administrator';

// Function to output role badge
function getRoleBadge($role, $role_labels) {
    $info = isset($role_labels[$role]) ? $role_labels[$role] : ['label' => ucfirst($role), 'color' => 'gray'];
    $colors = [
        'blue' => 'bg-blue-100 text-blue-800',
        'purple' => 'bg-purple-100 text-purple-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'green' => 'bg-green-100 text-green-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'red' => 'bg-red-100 text-red-800',
        'gray' => 'bg-gray-100 text-gray-800'
    ];
    
    $color_class = $colors[$info['color']];
    
    return "<span class=\"px-2 py-1 text-xs font-medium rounded-full {$color_class}\">{$info['label']}</span>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View User - Admin Dashboard</title>
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
        <?php renderAdminHeader('User Profile', htmlspecialchars($user['name']), $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl text-sm <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Profile -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <?php if (!empty($user['profile_image'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="w-full h-full rounded-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-indigo-700 text-2xl"></i>
                                    <?php endif; ?>
                                </div>

                                <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></h2>
                                <div class="mt-2">
                                    <?php echo getRoleBadge($user['role'], $role_labels); ?>
                                </div>
                                <div class="mt-2">
                                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Email:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>

                                <?php if (!empty($user['phone'])): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Phone:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($user['department'])): ?>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Department:</span>
                                        <span class="text-gray-900"><?php echo htmlspecialchars($user['department']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex justify-between">
                                    <span class="text-gray-500">Member Since:</span>
                                    <span class="text-gray-900"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-gray-500">Last Login:</span>
                                    <span class="text-gray-900">
                                        <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <?php if (!empty($user['main_community_name']) || !empty($user['smaller_community_name']) || !empty($user['suburb_name']) || !empty($user['cottage_name'])): ?>
                                <div class="mt-6 pt-6 border-t border-gray-100">
                                    <h3 class="text-base font-semibold text-gray-800 mb-3">Location Assignment</h3>
                                    <div class="space-y-2 text-sm">
                                        <?php if (!empty($user['main_community_name'])): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Main Community:</span>
                                                <span class="text-gray-900"><?php echo htmlspecialchars($user['main_community_name']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($user['smaller_community_name'])): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Smaller Community:</span>
                                                <span class="text-gray-900"><?php echo htmlspecialchars($user['smaller_community_name']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($user['suburb_name'])): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Suburb:</span>
                                                <span class="text-gray-900"><?php echo htmlspecialchars($user['suburb_name']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($user['cottage_name'])): ?>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Cottage:</span>
                                                <span class="text-gray-900"><?php echo htmlspecialchars($user['cottage_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-base font-semibold text-gray-800 mb-4">User Activity</h3>

                            <div class="space-y-4">
                                <?php if ($user['role'] === 'agent' || $user['role'] === 'officer'): ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-file-alt text-blue-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">Total Issues</span>
                                        </div>
                                        <span class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['total_issues'] ?? 0); ?></span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-clock text-yellow-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">Pending Issues</span>
                                        </div>
                                        <span class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['pending_issues'] ?? 0); ?></span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-check-circle text-green-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">Resolved Issues</span>
                                        </div>
                                        <span class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['resolved_issues'] ?? 0); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-chart-line text-indigo-600 text-sm"></i>
                                            </div>
                                            <span class="text-sm text-gray-600">Total Activities</span>
                                        </div>
                                        <span class="text-lg font-semibold text-gray-800"><?php echo number_format($stats['total_activities'] ?? 0); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-clock-rotate-left text-purple-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Last Activity</span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">
                                        <?php 
                                            if (!empty($activities)) {
                                                echo date('M d, Y H:i', strtotime($activities[0]['created_at']));
                                            } else {
                                                echo 'No activities';
                                            }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-xl font-semibold text-gray-800">Activity History</h2>
                            <p class="text-sm text-gray-600 mt-1">Recent activities performed by this user</p>
                        </div>

                        <?php if (empty($activities)): ?>
                            <div class="p-6 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-history text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-800 mb-2">No activities found</h3>
                                <p class="text-gray-600">This user has not performed any tracked activities yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="p-6">
                                <div class="space-y-6">
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="flex">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-history text-indigo-700 text-sm"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-800">
                                                    <?php 
                                                        echo ucfirst(str_replace('_', ' ', $activity['action']));
                                                    ?>
                                                </div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    <?php echo htmlspecialchars($activity['details']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (isset($stats['total_activities']) && $stats['total_activities'] > 5): ?>
                                    <div class="mt-6 text-center">
                                        <a href="./user_activities.php?id=<?php echo $user_id; ?>" class="px-4 py-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                            View All Activities
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Related Issues Section (for Agents and Officers) -->
                    <?php if ($user['role'] === 'agent' || $user['role'] === 'officer'): ?>
                        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-6 border-b border-gray-100">
                                <h2 class="text-xl font-semibold text-gray-800">
                                    <?php echo $user['role'] === 'agent' ? 'Reported Issues' : 'Managed Issues'; ?>
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php echo $user['role'] === 'agent' ? 'Issues reported by this agent' : 'Issues being managed by this officer'; ?>
                                </p>
                            </div>

                            <div class="p-6">
                                <?php if (empty($stats['total_issues']) || $stats['total_issues'] == 0): ?>
                                    <div class="text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-clipboard-list text-gray-400 text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-800 mb-2">No issues found</h3>
                                        <p class="text-gray-600">
                                            <?php echo $user['role'] === 'agent' ? 'This agent has not reported any issues yet.' : 'This officer is not managing any issues yet.'; ?>
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <a href="../issues/index.php?<?php echo $user['role'] === 'agent' ? 'agent_id=' : 'officer_id='; ?><?php echo $user_id; ?>" class="px-4 py-2 text-sm font-medium text-indigo-900 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                            View All <?php echo $stats['total_issues']; ?> Issues
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
