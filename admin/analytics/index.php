<?php
// filepath: c:\xampp\htdocs\swma\admin\analytics\index.php
require_once __DIR__ . '/../../config/db_connection.php';
$database = new Database();
$conn = $database->getConnection();

// Include the sidebar and header function definitions
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../login/session_check.php';

$current_page = "analytics";
$adminId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Administrator';
$userRole = $_SESSION['user_role'] ?? 'admin';

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

// --- Analytics Data Collection ---
try {
    // 1. Overall System Statistics
    $overallStatsStmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM issues) as total_issues,
            (SELECT COUNT(*) FROM users WHERE role IN ('officer', 'agent')) as total_staff,
            (SELECT COUNT(*) FROM projects) as total_projects,
            (SELECT COUNT(*) FROM constituents) as total_constituents,
            (SELECT COUNT(*) FROM employment_opportunities) as total_jobs,
            (SELECT COUNT(*) FROM idea_bank) as total_ideas,
            (SELECT COALESCE(SUM(budget), 0) FROM projects WHERE status = 'ongoing') as active_budget
    ");
    $overallStatsStmt->execute();
    $overallStats = $overallStatsStmt->fetch(PDO::FETCH_ASSOC);

    // 2. Issues Analytics
    $issuesAnalyticsStmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count,
            AVG(DATEDIFF(COALESCE(resolved_at, NOW()), created_at)) as avg_resolution_days
        FROM issues 
        GROUP BY status
    ");
    $issuesAnalyticsStmt->execute();
    $issuesAnalytics = $issuesAnalyticsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Issues by Severity
    $issuesBySeverityStmt = $conn->prepare("
        SELECT severity, COUNT(*) as count 
        FROM issues 
        GROUP BY severity
    ");
    $issuesBySeverityStmt->execute();
    $issuesBySeverity = $issuesBySeverityStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Issues by Category
    $issuesByCategoryStmt = $conn->prepare("
        SELECT ic.name, COUNT(i.id) as count 
        FROM issue_categories ic
        LEFT JOIN issues i ON ic.id = i.category_id
        GROUP BY ic.id, ic.name
        ORDER BY count DESC
        LIMIT 10
    ");
    $issuesByCategoryStmt->execute();
    $issuesByCategory = $issuesByCategoryStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Monthly Trends (Last 12 months)
    $monthlyTrendsStmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as issues_created,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as issues_resolved
        FROM issues 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyTrendsStmt->execute();
    $monthlyTrends = $monthlyTrendsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Top Performing Agents/Officers
    $topPerformersStmt = $conn->prepare("
        SELECT 
            u.name,
            u.role,
            COUNT(i.id) as issues_handled,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as issues_resolved,
            ROUND((SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) / COUNT(i.id)) * 100, 1) as resolution_rate
        FROM users u
        LEFT JOIN issues i ON (u.role = 'agent' AND i.agent_id = u.id) OR (u.role = 'officer' AND i.officer_id = u.id)
        WHERE u.role IN ('agent', 'officer') AND u.status = 'active'
        GROUP BY u.id, u.name, u.role
        HAVING issues_handled > 0
        ORDER BY resolution_rate DESC, issues_resolved DESC
        LIMIT 10
    ");
    $topPerformersStmt->execute();
    $topPerformers = $topPerformersStmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Community Analytics
    $communityAnalyticsStmt = $conn->prepare("
        SELECT 
            COALESCE(c.name, sc.name, 'Other') as location_name,
            COUNT(i.id) as issues_count,
            AVG(CASE WHEN i.status = 'resolved' AND i.resolved_at IS NOT NULL 
                THEN DATEDIFF(i.resolved_at, i.created_at) END) as avg_resolution_days
        FROM issues i
        LEFT JOIN communities c ON i.main_community_id = c.id
        LEFT JOIN smaller_communities sc ON i.smaller_community_id = sc.id
        GROUP BY location_name
        HAVING issues_count > 0
        ORDER BY issues_count DESC
        LIMIT 10
    ");
    $communityAnalyticsStmt->execute();
    $communityAnalytics = $communityAnalyticsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Budget Analytics
    $budgetAnalyticsStmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as project_count,
            COALESCE(SUM(budget), 0) as total_budget,
            COALESCE(AVG(budget), 0) as avg_budget
        FROM projects 
        GROUP BY status
    ");
    $budgetAnalyticsStmt->execute();
    $budgetAnalytics = $budgetAnalyticsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 9. Recent Activity Summary
    $recentActivityStmt = $conn->prepare("
        SELECT 
            DATE(created_at) as activity_date,
            COUNT(*) as activity_count
        FROM activity_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY activity_date DESC
        LIMIT 7
    ");
    $recentActivityStmt->execute();
    $recentActivity = $recentActivityStmt->fetchAll(PDO::FETCH_ASSOC);

    // 10. System Health Metrics
    $systemHealthStmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM issues WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as pending_this_week,
            (SELECT COUNT(*) FROM issues WHERE status = 'resolved' AND resolved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as resolved_this_week,
            (SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as active_users_week,
            (SELECT COUNT(*) FROM projects WHERE status = 'ongoing') as active_projects
    ");
    $systemHealthStmt->execute();
    $systemHealth = $systemHealthStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Analytics data error: " . $e->getMessage());
    echo "Error fetching analytics data.". "\n" . $e->getMessage();
    // Set default values
    $overallStats = ['total_issues' => 0, 'total_staff' => 0, 'total_projects' => 0, 'total_constituents' => 0, 'total_jobs' => 0, 'total_ideas' => 0, 'active_budget' => 0];
    $issuesAnalytics = [];
    $issuesBySeverity = [];
    $issuesByCategory = [];
    $monthlyTrends = [];
    $topPerformers = [];
    $communityAnalytics = [];
    $budgetAnalytics = [];
    $recentActivity = [];
    $systemHealth = ['pending_this_week' => 0, 'resolved_this_week' => 0, 'active_users_week' => 0, 'active_projects' => 0];
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-download',
        'label' => 'Export Report',
        'href' => '#',
        'onclick' => 'exportAnalyticsReport()'
    ],
    [
        'icon' => 'fas fa-refresh',
        'label' => 'Refresh Data',
        'href' => '#',
        'onclick' => 'window.location.reload()'
    ],
    [
        'icon' => 'fas fa-cog',
        'label' => 'Settings',
        'href' => '../settings/'
    ]
];

// Get counts for sidebar
$pendingIssuesCount = getSystemPendingIssuesCount($conn);
$activeUsersCount = getActiveUsersCount($conn);

// Prepare chart data
$statusLabels = array_column($issuesAnalytics, 'status');
$statusCounts = array_column($issuesAnalytics, 'count');
$severityLabels = array_column($issuesBySeverity, 'severity');
$severityCounts = array_column($issuesBySeverity, 'count');
$monthLabels = array_column($monthlyTrends, 'month');
$monthlyIssues = array_column($monthlyTrends, 'issues_created');
$monthlyResolved = array_column($monthlyTrends, 'issues_resolved');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link href="/styles/output.css"  rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#dc2626',
                        'secondary': '#64748b'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 min-h-screen font-inter">
    <?php renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount); ?>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderAdminHeader('System Analytics', 'Comprehensive insights and performance metrics', $headerActionButtons); ?>

        <div class="p-4 sm:p-6">
            <!-- Key Performance Indicators -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Issues -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Issues</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overallStats['total_issues']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Active Staff -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Staff</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overallStats['total_staff']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Projects -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-project-diagram text-green-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Projects</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($overallStats['total_projects']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Active Budget -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Budget</p>
                            <p class="text-2xl font-bold text-gray-900">₵<?php echo number_format($overallStats['active_budget'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Pending This Week -->
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-800">New Issues This Week</p>
                            <p class="text-2xl font-bold text-yellow-900"><?php echo $systemHealth['pending_this_week']; ?></p>
                        </div>
                        <div class="w-10 h-10 bg-yellow-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-700"></i>
                        </div>
                    </div>
                </div>

                <!-- Resolved This Week -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-800">Resolved This Week</p>
                            <p class="text-2xl font-bold text-green-900"><?php echo $systemHealth['resolved_this_week']; ?></p>
                        </div>
                        <div class="w-10 h-10 bg-green-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-700"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800">Active Users (7 days)</p>
                            <p class="text-2xl font-bold text-blue-900"><?php echo $systemHealth['active_users_week']; ?></p>
                        </div>
                        <div class="w-10 h-10 bg-blue-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-check text-blue-700"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Projects -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-800">Ongoing Projects</p>
                            <p class="text-2xl font-bold text-purple-900"><?php echo $systemHealth['active_projects']; ?></p>
                        </div>
                        <div class="w-10 h-10 bg-purple-200 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tasks text-purple-700"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Issues Status Chart -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Issues by Status</h3>
                        <div class="flex space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Total: <?php echo array_sum($statusCounts); ?>
                            </span>
                        </div>
                    </div>
                    <div class="relative h-80">
                        <canvas id="issuesStatusChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trends Chart -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Monthly Trends</h3>
                        <div class="flex space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Last 12 Months
                            </span>
                        </div>
                    </div>
                    <div class="relative h-80">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Issues by Severity -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Issues by Severity</h3>
                        <div class="flex space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Priority Analysis
                            </span>
                        </div>
                    </div>
                    <div class="relative h-80">
                        <canvas id="severityChart"></canvas>
                    </div>
                </div>

                <!-- Top Categories -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Top Issue Categories</h3>
                        <div class="flex space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Top 10
                            </span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <?php foreach (array_slice($issuesByCategory, 0, 8) as $index => $category): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white text-xs font-bold"><?php echo $index + 1; ?></span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($category['name'] ?: 'Uncategorized'); ?></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full"
                                            style="width: <?php echo ($category['count'] / max(array_column($issuesByCategory, 'count'))) * 100; ?>%"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900"><?php echo $category['count']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top Performers -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Top Performers</h3>
                        <p class="text-sm text-gray-600">Staff with highest resolution rates</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach (array_slice($topPerformers, 0, 8) as $index => $performer): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-blue-600 rounded-full flex items-center justify-center">
                                            <span class="text-white text-sm font-bold"><?php echo $index + 1; ?></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($performer['name']); ?></p>
                                            <p class="text-xs text-gray-500 capitalize"><?php echo ucfirst($performer['role']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-green-600"><?php echo $performer['resolution_rate']; ?>%</p>
                                        <p class="text-xs text-gray-500"><?php echo $performer['issues_resolved']; ?>/<?php echo $performer['issues_handled']; ?> resolved</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Community Analytics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Community Insights</h3>
                        <p class="text-sm text-gray-600">Issues by location and resolution time</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach (array_slice($communityAnalytics, 0, 8) as $community): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-full flex items-center justify-center">
                                            <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($community['location_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo $community['issues_count']; ?> issues reported</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-blue-600"><?php echo $community['avg_resolution_days'] ? round($community['avg_resolution_days'], 1) . ' days' : 'N/A'; ?></p>
                                        <p class="text-xs text-gray-500">Avg. resolution</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Analytics -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Budget Analytics</h3>
                    <div class="flex space-x-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Project Finances
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php foreach ($budgetAnalytics as $budget): ?>
                        <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 capitalize"><?php echo str_replace('_', ' ', $budget['status']); ?></p>
                                    <p class="text-lg font-bold text-gray-900">₵<?php echo number_format($budget['total_budget'], 2); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $budget['project_count']; ?> projects</p>
                                </div>
                                <div class="w-12 h-12 bg-white rounded-lg shadow-sm flex items-center justify-center">
                                    <i class="fas fa-wallet text-gray-600"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Chart.js Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartColors = {
                primary: '#dc2626',
                secondary: '#64748b',
                success: '#10b981',
                warning: '#f59e0b',
                danger: '#ef4444',
                info: '#3b82f6',
                light: '#f8fafc',
                dark: '#1e293b'
            };

            // Issues Status Chart
            if (document.getElementById('issuesStatusChart')) {
                const statusCtx = document.getElementById('issuesStatusChart').getContext('2d');
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($statusLabels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($statusCounts); ?>,
                            backgroundColor: [
                                '#f59e0b', // pending - yellow
                                '#3b82f6', // reviewed - blue  
                                '#10b981', // approved - green
                                '#ef4444', // rejected - red
                                '#059669', // resolved - emerald
                                '#8b5cf6' // in_progress - purple
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Monthly Trends Chart
            if (document.getElementById('monthlyTrendsChart')) {
                const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($monthLabels); ?>,
                        datasets: [{
                            label: 'Issues Created',
                            data: <?php echo json_encode($monthlyIssues); ?>,
                            borderColor: chartColors.danger,
                            backgroundColor: chartColors.danger + '20',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Issues Resolved',
                            data: <?php echo json_encode($monthlyResolved); ?>,
                            borderColor: chartColors.success,
                            backgroundColor: chartColors.success + '20',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
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
                                    padding: 20
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f1f5f9'
                                }
                            },
                            x: {
                                grid: {
                                    color: '#f1f5f9'
                                }
                            }
                        }
                    }
                });
            }

            // Severity Chart
            if (document.getElementById('severityChart')) {
                const severityCtx = document.getElementById('severityChart').getContext('2d');
                new Chart(severityCtx, {
                    type: 'polarArea',
                    data: {
                        labels: <?php echo json_encode($severityLabels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($severityCounts); ?>,
                            backgroundColor: [
                                '#10b981', // low - green
                                '#f59e0b', // medium - yellow
                                '#ef4444' // high - red
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        },
                        scales: {
                            r: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f1f5f9'
                                }
                            }
                        }
                    }
                });
            }
        });

        // Export functionality
        function exportAnalyticsReport() {
            // This would typically trigger a server-side export
            alert('Export functionality would be implemented here');
        }
    </script>
</body>

</html>