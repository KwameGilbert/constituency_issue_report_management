<?php
// reports/index.php - Officer Reports Dashboard
require_once __DIR__ . '/../login/session_check.php';
require_once __DIR__ . '/../../config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/header.php';

$current_page = 'reports';
$officerId = $_SESSION['user_id'] ?? null;

// Initialize date filters
$date_from = $_GET['date_from'] ?? date('2000-01-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$selected_agent = $_GET['agent_id'] ?? '';
$selected_status = $_GET['status'] ?? '';
$selected_category = $_GET['category'] ?? '';

// Fetch summary statistics
try {
    // Issues overview
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_issues,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
            SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_issues,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_issues,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
            AVG(CASE WHEN resolved_at IS NOT NULL 
                THEN DATEDIFF(resolved_at, created_at) END) as avg_resolution_days
        FROM issues 
        WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    ");
    $stmt->execute([$date_from, $date_to]);
    $issues_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agent performance
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            COUNT(i.id) as issues_submitted,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
            AVG(CASE WHEN i.resolved_at IS NOT NULL 
                THEN DATEDIFF(i.resolved_at, i.created_at) END) as avg_resolution_days
        FROM users u
        LEFT JOIN issues i ON u.id = i.agent_id 
            AND i.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        WHERE u.role = 'agent' AND u.status = 'active'
        GROUP BY u.id, u.name
        ORDER BY issues_submitted DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $agent_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Issues by category
    $stmt = $conn->prepare("
        SELECT 
            ic.name as category_name,
            COUNT(i.id) as issue_count,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
        FROM issues i
        LEFT JOIN issue_categories ic ON i.category_id = ic.id
        WHERE i.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY ic.id, ic.name
        ORDER BY issue_count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Issues by electoral area
    $stmt = $conn->prepare("
        SELECT 
            ea.name as area_name,
            COUNT(i.id) as issue_count,
            SUM(CASE WHEN i.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
        FROM issues i
        LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
        WHERE i.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        GROUP BY ea.id, ea.name
        ORDER BY issue_count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $area_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent activity
    $stmt = $conn->prepare("
        SELECT 
            i.id,
            i.title,
            i.status,
            i.severity,
            i.created_at,
            i.updated_at,
            u.name as agent_name,
            ic.name as category_name,
            ea.name as area_name
        FROM issues i
        LEFT JOIN users u ON i.agent_id = u.id
        LEFT JOIN issue_categories ic ON i.category_id = ic.id
        LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
        WHERE i.updated_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        ORDER BY i.updated_at DESC
        LIMIT 20
    ");
    $stmt->execute([$date_from, $date_to]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly trends (last 12 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_issues,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
        FROM issues 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get filter options
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'agent' ORDER BY name");
    $stmt->execute();
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT DISTINCT status FROM issues ORDER BY status");
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $conn->prepare("SELECT id, name FROM issue_categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error loading report data: " . $e->getMessage();
    $issues_stats = [];
    $agent_performance = [];
    $category_stats = [];
    $area_stats = [];
    $recent_activity = [];
    $monthly_trends = [];
    $agents = [];
    $statuses = [];
    $categories = [];
}

// Define header action buttons
$headerActionButtons = [
    [
        'icon' => 'fas fa-download',
        'label' => 'Export Report',
        'href' => '#',
        'class' => 'bg-indigo-900 text-white hover:bg-indigo-800',
        'onclick' => 'exportReport()'
    ],
    [
        'icon' => 'fas fa-print',
        'label' => 'Print Report',
        'href' => '#',
        'class' => 'bg-gray-200 text-gray-700 hover:bg-gray-300',
        'onclick' => 'printReport()'
    ]
];

$userName = $_SESSION['user_name'] ?? 'Officer';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reports & Analytics - Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

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
                        info: '#3b82f6',
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
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

    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge-pending {
            background-color: #fef3c7;
            color: #b45309;
        }

        .status-badge-reviewed {
            background-color: #bfdbfe;
            color: #1e40af;
        }

        .status-badge-approved {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-badge-resolved {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-badge-in_progress{
            background-color: #ec7f0abd;
            color: #341802ff;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-break {
                page-break-after: always;
            }
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen font-sans">
    <?php renderOfficerSidebar($current_page, getPendingIssuesCount($conn)); ?>

    <main class="lg:ml-64 min-h-screen transition-all duration-300">
        <?php renderOfficerHeader('Reports & Analytics', 'Comprehensive system insights and performance metrics', $headerActionButtons); ?>

        <div class="p-4 sm:p-6" id="reportContent">

            <!-- Filter Section -->
            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 mb-6 no-print">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Report Filters</h2>

                <form method="GET" action="" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-xs font-medium text-gray-700 mb-2">Date From</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-xs font-medium text-gray-700 mb-2">Date To</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                        </div>

                        <!-- Agent Filter -->
                        <div>
                            <label for="agent_id" class="block text-xs font-medium text-gray-700 mb-2">Agent</label>
                            <select id="agent_id" name="agent_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                                <option value="">All Agents</option>
                                <?php foreach ($agents as $agent) : ?>
                                    <option value="<?php echo $agent['id']; ?>" <?php echo $selected_agent == $agent['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($agent['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-xs font-medium text-gray-700 mb-2">Status</label>
                            <select id="status" name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $status) : ?>
                                    <option value="<?php echo $status; ?>" <?php echo $selected_status == $status ? 'selected' : ''; ?>>
                                        <?php echo str_replace('_',' ',ucfirst($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label for="category" class="block text-xs font-medium text-gray-700 mb-2">Category</label>
                            <select id="category" name="category" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-900 focus:border-indigo-900">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $selected_category == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-900 rounded-lg hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-900 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                        <a href="?" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors">
                            <i class="fas fa-undo mr-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Report Header -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6">
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">System Performance Report</h1>
                    <p class="text-gray-600">Report Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?></p>
                    <p class="text-sm text-gray-500 mt-1">Generated on <?php echo date('M d, Y H:i'); ?> by <?php echo htmlspecialchars($userName); ?></p>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total Issues</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($issues_stats['total_issues'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Pending Issues</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($issues_stats['pending_issues'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Resolved Issues</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($issues_stats['resolved_issues'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Avg Resolution Time</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo round($issues_stats['avg_resolution_days'] ?? 0, 1); ?> days</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Monthly Trends Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Monthly Trends (Last 12 Months)</h3>
                    </div>
                    <div class="p-5">
                        <div class="h-64">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Issue Status Distribution</h3>
                    </div>
                    <div class="p-5">
                        <div class="h-64">
                            <canvas id="statusDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agent Performance -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Top Agent Performance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolved</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolution Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Resolution Time</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($agent_performance)) : ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No agent data available for the selected period.
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($agent_performance as $agent) : ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($agent['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo number_format($agent['issues_submitted']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo number_format($agent['resolved_count']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php
                                            $rate = $agent['issues_submitted'] > 0 ? ($agent['resolved_count'] / $agent['issues_submitted']) * 100 : 0;
                                            echo round($rate, 1) . '%';
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo $agent['avg_resolution_days'] ? round($agent['avg_resolution_days'], 1) . ' days' : 'N/A'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Issues by Category -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Issues by Category</h3>
                    </div>
                    <div class="p-5">
                        <?php if (empty($category_stats)) : ?>
                            <p class="text-center text-sm text-gray-500 py-8">No category data available.</p>
                        <?php else : ?>
                            <div class="space-y-3">
                                <?php foreach (array_slice($category_stats, 0, 8) as $category) : ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($category['category_name'] ?? 'Uncategorized'); ?>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <?php
                                                $total = array_sum(array_column($category_stats, 'issue_count'));
                                                $percentage = $total > 0 ? ($category['issue_count'] / $total) * 100 : 0;
                                                ?>
                                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="ml-4 text-sm font-medium text-gray-900">
                                            <?php echo number_format($category['issue_count']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Issues by Electoral Area -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Issues by Electoral Area</h3>
                    </div>
                    <div class="p-5">
                        <?php if (empty($area_stats)) : ?>
                            <p class="text-center text-sm text-gray-500 py-8">No area data available.</p>
                        <?php else : ?>
                            <div class="space-y-3">
                                <?php foreach (array_slice($area_stats, 0, 8) as $area) : ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($area['area_name'] ?? 'Unassigned'); ?>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <?php
                                                $total = array_sum(array_column($area_stats, 'issue_count'));
                                                $percentage = $total > 0 ? ($area['issue_count'] / $total) * 100 : 0;
                                                ?>
                                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="ml-4 text-sm font-medium text-gray-900">
                                            <?php echo number_format($area['issue_count']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden print-break">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Recent Activity</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($recent_activity)) : ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No recent activity found for the selected period.
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($recent_activity as $activity) : ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($activity['title']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?php echo $activity['id']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="status-badge status-badge-<?php echo strtolower($activity['status']); ?>">
                                                <?php echo str_replace('_',' ', ucfirst($activity['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($activity['agent_name'] ?? 'Unassigned'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($activity['category_name'] ?? 'Uncategorized'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo date('M d, Y H:i', strtotime($activity['updated_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Monthly Trends Chart
        const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_trends, 'month')); ?>,
                datasets: [{
                    label: 'Total Issues',
                    data: <?php echo json_encode(array_column($monthly_trends, 'total_issues')); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Resolved Issues',
                    data: <?php echo json_encode(array_column($monthly_trends, 'resolved_issues')); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Reviewed', 'Approved', 'Rejected', 'Resolved', 'In Progress'],
                datasets: [{
                    data: [
                        <?php echo $issues_stats['pending_issues'] ?? 0; ?>,
                        <?php echo $issues_stats['reviewed_issues'] ?? 0; ?>,
                        <?php echo $issues_stats['approved_issues'] ?? 0; ?>,
                        <?php echo $issues_stats['rejected_issues'] ?? 0; ?>,
                        <?php echo $issues_stats['resolved_issues'] ?? 0; ?>,
                        <?php echo $issues_stats['in_progress_issues'] ?? 0; ?>,
                    ],
                    backgroundColor: [
                        '#fbbf24', // yellow for pending
                        '#3b82f6', // blue for reviewed
                        '#10b981', // green for approved
                        '#ef4444', // red for rejected
                        '#26fa01ff', // gray for resolved,
                        '#6b7280' // pending
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });

        // Export Report Function
        function exportReport() {
            // You can implement CSV/PDF export here
            alert('Export functionality would be implemented here');
        }

        // Print Report Function
        function printReport() {
            window.print();
        }
    </script>
</body>

</html>