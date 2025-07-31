<?php
// filepath: c:\xampp\htdocs\swma\admin\dashboard\index.php
require_once __DIR__ . '/../../config/db_connection.php';
$database = new Database();
$conn = $database->getConnection();
// Include the sidebar and header function definitions
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../login/session_check.php';

$current_page = "dashboard";
$adminId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Administrator';
$userRole = $_SESSION['user_role'] ?? 'admin';

// Colors (random shades for each)
function randomColor($base)
{
    $shades = [
        'slate' => ['#64748b', '#475569', '#334155', '#1e293b'],
        'red' => ['#ef4444', '#dc2626', '#b91c1c', '#991b1b'],
        'purple' => ['#a855f7', '#9333ea', '#7c3aed', '#6d28d9'],
        'indigo' => ['#6366f1', '#4f46e5', '#4338ca', '#3730a3'],
        'blue' => ['#3b82f6', '#2563eb', '#1d4ed8', '#1e40af'],
        'green' => ['#10b981', '#059669', '#047857', '#065f46'],
        'yellow' => ['#f59e0b', '#d97706', '#b45309', '#92400e'],
        'pink' => ['#ec4899', '#db2777', '#be185d', '#9d174d'],
    ];
    return $shades[$base][array_rand($shades[$base])];
}

// Function to get status badge classes
function getStatusBadgeClasses(string $status): string
{
    return match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'reviewed' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'resolved' => 'bg-emerald-100 text-emerald-800',
        'in_progress' => 'bg-purple-100 text-purple-800',
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        default => 'bg-gray-100 text-gray-800',
    };
}

// --- System-wide Statistics ---
try {
    // Issues overview
    $issuesStmt = $conn->prepare("SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'in_progress') AS in_progress,
        SUM(status = 'resolved') AS resolved,
        SUM(status = 'rejected') AS rejected
        FROM issues");
    $issuesStmt->execute();
    $issuesRow = $issuesStmt->fetch(PDO::FETCH_ASSOC);

    $totalIssues = (int)$issuesRow['total'];
    $pendingIssues = (int)$issuesRow['pending'];
    $approvedIssues = (int)$issuesRow['approved'];
    $inProgressIssues = (int)$issuesRow['in_progress'];
    $resolvedIssues = (int)$issuesRow['resolved'];
    $rejectedIssues = (int)$issuesRow['rejected'];

    // Users overview
    $usersStmt = $conn->prepare("SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(role = 'agent') AS agents,
        SUM(role = 'officer') AS officers,
        SUM(role IN ('mp', 'mce', 'pa', 'admin')) AS admins
        FROM users");
    $usersStmt->execute();
    $usersRow = $usersStmt->fetch(PDO::FETCH_ASSOC);

    $totalUsers = (int)$usersRow['total'];
    $activeUsers = (int)$usersRow['active'];
    $totalAgents = (int)$usersRow['agents'];
    $totalOfficers = (int)$usersRow['officers'];
    $totalAdmins = (int)$usersRow['admins'];

    // Projects overview
    $projectsStmt = $conn->prepare("SELECT
        COUNT(*) AS total,
        SUM(status = 'ongoing') AS ongoing,
        SUM(status = 'completed') AS completed,
        COALESCE(SUM(budget), 0) AS total_budget
        FROM projects");
    $projectsStmt->execute();
    $projectsRow = $projectsStmt->fetch(PDO::FETCH_ASSOC);

    $totalProjects = (int)$projectsRow['total'];
    $ongoingProjects = (int)$projectsRow['ongoing'];
    $completedProjects = (int)$projectsRow['completed'];
    $totalBudget = (float)$projectsRow['total_budget'];

    // Employment opportunities
    $employmentStmt = $conn->prepare("SELECT COUNT(*) AS total FROM employment_opportunities");
    $employmentStmt->execute();
    $employmentRow = $employmentStmt->fetch(PDO::FETCH_ASSOC);
    $totalJobs = (int)$employmentRow['total'];
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    // Set default values
    $totalIssues = $pendingIssues = $approvedIssues = $inProgressIssues = $resolvedIssues = $rejectedIssues = 0;
    $totalUsers = $activeUsers = $totalAgents = $totalOfficers = $totalAdmins = 0;
    $totalProjects = $ongoingProjects = $completedProjects = 0;
    $totalBudget = 0;
    $totalJobs = 0;
}

// --- Chart Data ---
// Issues by Status
$statusLabels = ['Pending', 'Reviewed', 'Approved', 'Rejected', 'Resolved', 'In Progress'];
$statusData = [$pendingIssues, 0, $approvedIssues, $rejectedIssues, $resolvedIssues, $inProgressIssues];
$statusColors = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#059669', '#8b5cf6'];

// Monthly trends (last 12 months)
try {
    $trendsStmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_issues,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
        FROM issues 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $trendsStmt->execute();
    $monthlyTrends = $trendsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $monthlyTrends = [];
}

// Recent activity
try {
    $activityStmt = $conn->prepare("
        SELECT 
            al.action,
            al.details,
            al.created_at,
            u.name as user_name,
            u.role as user_role
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $activityStmt->execute();
    $recentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentActivity = [];
}

// Recent issues for admin overview
try {
    $recentIssuesStmt = $conn->prepare("
        SELECT
            i.id,
            i.title,
            i.status,
            i.severity,
            i.created_at,
            u.name AS agent_name,
            ea.name AS electoral_area
        FROM issues i
        LEFT JOIN users u ON i.agent_id = u.id
        LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
        ORDER BY i.created_at DESC
        LIMIT 8
    ");
    $recentIssuesStmt->execute();
    $recentIssues = $recentIssuesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentIssues = [];
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-users',
        'label' => 'Manage Users',
        'href' => '../users/'
    ],
    [
        'icon' => 'fas fa-plus',
        'label' => 'New Project',
        'href' => '../projects/add_project.php'
    ],
    [
        'icon' => 'fas fa-chart-line',
        'label' => 'Analytics',
        'href' => '../analytics/'
    ]
];

// Get counts for sidebar
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Constituency System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
    <?php renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount); ?>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('System Dashboard', 'Complete overview of the constituency management system', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <!-- System Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Issues -->
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500/10 to-blue-600/10 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Issues</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($totalIssues); ?></h3>
                            <p class="text-xs text-gray-400 mt-1"><?php echo $pendingIssues; ?> pending review</p>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500/10 to-green-600/10 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Active Users</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($activeUsers); ?></h3>
                            <p class="text-xs text-gray-400 mt-1"><?php echo $totalUsers; ?> total registered</p>
                        </div>
                    </div>
                </div>

                <!-- Projects -->
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500/10 to-purple-600/10 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-project-diagram text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Projects</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($totalProjects); ?></h3>
                            <p class="text-xs text-gray-400 mt-1"><?php echo $ongoingProjects; ?> ongoing</p>
                        </div>
                    </div>
                </div>

                <!-- Budget -->
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500/10 to-yellow-600/10 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Budget</p>
                            <h3 class="text-2xl font-bold text-gray-800">₵<?php echo number_format($totalBudget, 0); ?></h3>
                            <p class="text-xs text-gray-400 mt-1">Project allocations</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Agents -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800">Field Agents</p>
                            <p class="text-2xl font-bold text-blue-900"><?php echo $totalAgents; ?></p>
                        </div>
                        <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
                    </div>
                </div>

                <!-- Officers -->
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-4 border border-indigo-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-indigo-800">Officers</p>
                            <p class="text-2xl font-bold text-indigo-900"><?php echo $totalOfficers; ?></p>
                        </div>
                        <i class="fas fa-user-shield text-indigo-600 text-2xl"></i>
                    </div>
                </div>

                <!-- Admins -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border border-red-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-800">Administrators</p>
                            <p class="text-2xl font-bold text-red-900"><?php echo $totalAdmins; ?></p>
                        </div>
                        <i class="fas fa-user-crown text-red-600 text-2xl"></i>
                    </div>
                </div>

                <!-- Jobs -->
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-emerald-800">Job Opportunities</p>
                            <p class="text-2xl font-bold text-emerald-900"><?php echo $totalJobs; ?></p>
                        </div>
                        <i class="fas fa-briefcase text-emerald-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Issues Status Distribution -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Issues Status Distribution</h2>
                        <a href="../issues/" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="h-64">
                        <canvas id="issuesStatusChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Monthly Trends</h2>
                        <a href="../analytics/" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Detailed Analytics <i class="fas fa-chart-line ml-1"></i>
                        </a>
                    </div>
                    <div class="h-64">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Issues -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-800">Recent Issues</h2>
                                <a href="../issues/" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    View All Issues <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($recentIssues)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                                <i class="fas fa-inbox text-gray-300 text-3xl mb-2"></i>
                                                <p>No recent issues found.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentIssues as $issue): ?>
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            #ISU<?php echo str_pad($issue['id'], 5, '0', STR_PAD_LEFT); ?>
                                                        </p>
                                                        <p class="text-sm text-gray-500 truncate max-w-xs">
                                                            <?php echo htmlspecialchars($issue['title']); ?>
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($issue['agent_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClasses($issue['status']); ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $issue['status'])); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    <?php echo match ($issue['severity']) {
                                                        'high' => 'bg-red-100 text-red-800',
                                                        'medium' => 'bg-yellow-100 text-yellow-800',
                                                        'low' => 'bg-green-100 text-green-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                    ?>">
                                                        <?php echo ucfirst($issue['severity']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?php echo date('M d, Y', strtotime($issue['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Activity</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php if (empty($recentActivity)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-history text-gray-300 text-3xl mb-2"></i>
                                    <p class="text-sm text-gray-500">No recent activity</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="flex items-start space-x-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-<?php
                                                echo match (true) {
                                                    str_contains($activity['action'], 'login') => 'sign-in-alt',
                                                    str_contains($activity['action'], 'logout') => 'sign-out-alt',
                                                    str_contains($activity['action'], 'issue') => 'clipboard-list',
                                                    str_contains($activity['action'], 'user') => 'user',
                                                    str_contains($activity['action'], 'project') => 'project-diagram',
                                                    default => 'bell'
                                                };
                                                ?> text-red-600 text-xs"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
                                                <span class="font-medium">
                                                    <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?>
                                                </span>
                                                <?php if ($activity['user_role']): ?>
                                                    <span class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded uppercase">
                                                        <?php echo htmlspecialchars($activity['user_role']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-sm text-gray-600 truncate">
                                                <?php echo htmlspecialchars($activity['details']); ?>
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
                        <a href="../audit/" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            View Audit Logs <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Issues Status Chart
            const statusCtx = document.getElementById('issuesStatusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($statusLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($statusData); ?>,
                        backgroundColor: <?php echo json_encode($statusColors); ?>,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    family: "'Inter', sans-serif"
                                }
                            }
                        }
                    }
                }
            });

            // Monthly Trends Chart
            const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthlyTrends, 'month')); ?>,
                    datasets: [{
                        label: 'Total Issues',
                        data: <?php echo json_encode(array_column($monthlyTrends, 'total_issues')); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Resolved Issues',
                        data: <?php echo json_encode(array_column($monthlyTrends, 'resolved_issues')); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12,
                                    family: "'Inter', sans-serif"
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>