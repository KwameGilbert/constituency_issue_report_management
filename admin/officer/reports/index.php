<?php
// Start session
session_start();

// Check if user is logged in and is a field officer
if (!isset($_SESSION['officer_id']) || $_SESSION['role'] !== 'field_officer') {
    header("Location: ../login/");
    exit;
}

// Include database connection
require_once '../../../config/db.php';

// Set active page and title for sidebar
$active_page = 'reports';
$pageTitle = 'Reports & Analytics';
$basePath = '../';

// Get officer details
$officer_id = $_SESSION['officer_id'];
$query = "SELECT * FROM field_officers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();
$stmt->close();

// Get time period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$period_clause = '';

switch ($period) {
    case 'week':
        $period_clause = "AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'month':
        $period_clause = "AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'quarter':
        $period_clause = "AND i.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        break;
    case 'year':
        $period_clause = "AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    default:
        $period_clause = "";
}

// Get summary statistics
$stats_query = "SELECT 
                COUNT(*) as total_issues,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_issues,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_issues,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_issues,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_issues,
                SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_issues,
                SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) as medium_issues,
                SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) as low_issues
                FROM issues i WHERE officer_id = ? $period_clause";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $officer_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Monthly trend data for line chart
$trend_query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as issue_count 
                FROM issues 
                WHERE officer_id = ? 
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
                LIMIT 12";

$trend_stmt = $conn->prepare($trend_query);
$trend_stmt->bind_param("i", $officer_id);
$trend_stmt->execute();
$trend_result = $trend_stmt->get_result();
$trend_data = [];
$trend_labels = [];

while ($row = $trend_result->fetch_assoc()) {
    $month_year = date('M Y', strtotime($row['month'] . '-01'));
    $trend_labels[] = $month_year;
    $trend_data[] = (int)$row['issue_count'];
}

// Electoral area breakdown data for pie chart
$area_query = "SELECT 
               ea.name as area_name,
               COUNT(i.id) as issue_count
               FROM issues i
               LEFT JOIN electoral_areas ea ON i.electoral_area_id = ea.id
               WHERE i.officer_id = ? $period_clause
               GROUP BY i.electoral_area_id
               ORDER BY issue_count DESC";

$area_stmt = $conn->prepare($area_query);
$area_stmt->bind_param("i", $officer_id);
$area_stmt->execute();
$area_result = $area_stmt->get_result();
$area_data = [];
$area_labels = [];

while ($row = $area_result->fetch_assoc()) {
    $area_name = $row['area_name'] ? $row['area_name'] : 'Unassigned';
    $area_labels[] = $area_name;
    $area_data[] = (int)$row['issue_count'];
}

// Resolution rate calculation
$resolution_rate = 0;
if ($stats['total_issues'] > 0) {
    $resolution_rate = round(($stats['resolved_issues'] / $stats['total_issues']) * 100);
}

// Average resolution time
$avg_time_query = "SELECT 
                  AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days
                  FROM issues 
                  WHERE officer_id = ? AND status = 'resolved' $period_clause";

$avg_time_stmt = $conn->prepare($avg_time_query);
$avg_time_stmt->bind_param("i", $officer_id);
$avg_time_stmt->execute();
$avg_time_result = $avg_time_stmt->get_result();
$avg_time_row = $avg_time_result->fetch_assoc();
$avg_resolution_days = round($avg_time_row['avg_days'] ?? 0);

// Severity distribution data for horizontal bar chart
$severity_data = [
    $stats['critical_issues'],
    $stats['high_issues'],
    $stats['medium_issues'],
    $stats['low_issues']
];

// Top issues by affected people for bar chart
$top_issues_query = "SELECT 
                    id, title, people_affected 
                    FROM issues 
                    WHERE officer_id = ? AND people_affected > 0 $period_clause
                    ORDER BY people_affected DESC 
                    LIMIT 5";

$top_issues_stmt = $conn->prepare($top_issues_query);
$top_issues_stmt->bind_param("i", $officer_id);
$top_issues_stmt->execute();
$top_issues_result = $top_issues_stmt->get_result();
$top_issues = [];
$top_issues_labels = [];
$top_issues_data = [];

while ($row = $top_issues_result->fetch_assoc()) {
    $top_issues[] = $row;
    $top_issues_labels[] = substr($row['title'], 0, 20) . (strlen($row['title']) > 20 ? '...' : '');
    $top_issues_data[] = (int)$row['people_affected'];
}

// Recent status changes
$recent_updates_query = "SELECT 
                        i.id, i.title, iu.status_change, iu.created_at
                        FROM issue_updates iu
                        JOIN issues i ON iu.issue_id = i.id
                        WHERE i.officer_id = ? $period_clause
                        ORDER BY iu.created_at DESC
                        LIMIT 5";

$recent_updates_stmt = $conn->prepare($recent_updates_query);
$recent_updates_stmt->bind_param("i", $officer_id);
$recent_updates_stmt->execute();
$recent_updates_result = $recent_updates_stmt->get_result();
$recent_updates = [];

while ($row = $recent_updates_result->fetch_assoc()) {
    $recent_updates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | Field Officer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out forwards;
    }

    .staggered-item {
        opacity: 0;
        animation: fadeIn 0.5s ease-out forwards;
    }

    @keyframes slideInFromBottom {
        0% {
            transform: translateY(20px);
            opacity: 0;
        }

        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Custom form input styling for better visibility */
    input[type="text"],
    input[type="number"],
    textarea,
    select {
        border: 1px solid #d1d5db !important;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        width: 100%;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
    }

    input:hover,
    textarea:hover,
    select:hover {
        border-color: #f59e0b !important;
    }

    .chart-container {
        position: relative;
        width: 100%;
        height: 300px;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar component -->
        <?php include_once '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header component -->
            <?php include_once '../includes/header.php'; ?>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Action Bar -->
                    <div
                        class="bg-gradient-to-r from-amber-600 to-amber-800 rounded-xl shadow-lg mb-6 p-6 text-white fade-in">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <h1 class="text-2xl font-bold">Reports & Analytics</h1>
                                <p class="mt-1 opacity-90">Track and analyze your issue reporting performance</p>
                            </div>

                            <!-- Time period filter -->
                            <div class="flex-shrink-0">
                                <form action="" method="GET" class="flex items-center">
                                    <label for="period" class="mr-2 text-white">Time Period:</label>
                                    <select name="period" id="period" onchange="this.form.submit()"
                                        class="bg-white text-amber-800 rounded-lg font-medium text-sm px-4 py-2 shadow hover:bg-amber-50 transition-colors duration-300 border-none focus:ring-2 focus:ring-white">
                                        <option value="all" <?php echo $period == 'all' ? 'selected' : ''; ?>>All Time
                                        </option>
                                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Past
                                            Week</option>
                                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Past
                                            Month</option>
                                        <option value="quarter" <?php echo $period == 'quarter' ? 'selected' : ''; ?>>
                                            Past Quarter</option>
                                        <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Past
                                            Year</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Key Metrics Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.1s;">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                    <i class="fas fa-clipboard-list text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Issues</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?php echo $stats['total_issues']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.2s;">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Resolution Rate</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $resolution_rate; ?>%</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.3s;">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Critical Issues</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        <?php echo $stats['critical_issues']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.4s;">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Avg. Resolution Time</p>
                                    <p class="text-2xl font-semibold text-gray-900"><?php echo $avg_resolution_days; ?>
                                        days</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Breakdown & Monthly Trend -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Status Breakdown -->
                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.5s;">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Issue Status Breakdown</h2>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>

                        <!-- Monthly Trend -->
                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.6s;">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Monthly Reporting Trend</h2>
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Electoral Area Breakdown & Severity Distribution -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Electoral Area -->
                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.7s;">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Electoral Area Breakdown</h2>
                            <?php if (empty($area_data)): ?>
                            <div class="flex flex-col items-center justify-center h-64">
                                <div class="text-amber-500 mb-2"><i class="fas fa-map-marker-alt text-3xl"></i></div>
                                <p class="text-gray-500">No area data available for the selected period</p>
                            </div>
                            <?php else: ?>
                            <div class="chart-container">
                                <canvas id="areaChart"></canvas>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Severity Distribution -->
                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 0.8s;">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Severity Distribution</h2>
                            <div class="chart-container">
                                <canvas id="severityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Issues by People Affected -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 staggered-item hover:shadow-md transition-shadow duration-300"
                        style="animation-delay: 0.9s;">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Top Issues by People Affected</h2>
                        <?php if (empty($top_issues)): ?>
                        <div class="flex flex-col items-center justify-center h-40">
                            <div class="text-amber-500 mb-2"><i class="fas fa-users text-3xl"></i></div>
                            <p class="text-gray-500">No people affected data available for the selected period</p>
                        </div>
                        <?php else: ?>
                        <div class="chart-container">
                            <canvas id="peopleAffectedChart"></canvas>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Status Updates & Printable Report -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        <!-- Recent Status Updates -->
                        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 1s;">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Recent Status Updates</h2>
                                <a href="../issues/"
                                    class="text-amber-600 text-sm hover:underline transition-colors duration-300">
                                    View All Issues
                                </a>
                            </div>

                            <?php if (empty($recent_updates)): ?>
                            <div class="flex flex-col items-center justify-center h-40">
                                <div class="text-amber-500 mb-2"><i class="fas fa-history text-3xl"></i></div>
                                <p class="text-gray-500">No recent updates for the selected period</p>
                            </div>
                            <?php else: ?>
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <?php foreach ($recent_updates as $index => $update): ?>
                                    <li>
                                        <div class="relative pb-8">
                                            <?php if ($index !== count($recent_updates) - 1): ?>
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <?php 
                                                                $icon_color = 'bg-gray-400';
                                                                $icon = 'fas fa-spinner';
                                                                if (strpos($update['status_change'], 'pending') !== false) {
                                                                    $icon_color = 'bg-yellow-500';
                                                                    $icon = 'fas fa-hourglass-half';
                                                                }
                                                                if (strpos($update['status_change'], 'under_review') !== false) {
                                                                    $icon_color = 'bg-purple-500';
                                                                    $icon = 'fas fa-search';
                                                                }
                                                                if (strpos($update['status_change'], 'in_progress') !== false) {
                                                                    $icon_color = 'bg-blue-500';
                                                                    $icon = 'fas fa-cogs';
                                                                }
                                                                if (strpos($update['status_change'], 'resolved') !== false) {
                                                                    $icon_color = 'bg-green-500';
                                                                    $icon = 'fas fa-check';
                                                                }
                                                                if (strpos($update['status_change'], 'rejected') !== false) {
                                                                    $icon_color = 'bg-red-500';
                                                                    $icon = 'fas fa-times';
                                                                }
                                                            ?>
                                                    <span
                                                        class="h-8 w-8 rounded-full <?php echo $icon_color; ?> flex items-center justify-center ring-8 ring-white">
                                                        <i class="<?php echo $icon; ?> text-white text-sm"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <a href="../issue-detail/?id=<?php echo $update['id']; ?>"
                                                            class="text-sm text-amber-700 hover:underline font-medium">
                                                            <?php echo htmlspecialchars($update['title']); ?>
                                                        </a>
                                                        <p class="text-sm text-gray-500">Status changed to <span
                                                                class="font-medium text-gray-900">
                                                                <?php echo ucfirst(str_replace('_', ' ', $update['status_change'])); ?>
                                                            </span></p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <?php echo date('M d, Y', strtotime($update['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Printable Report -->
                        <div class="bg-white rounded-lg shadow-sm p-6 staggered-item hover:shadow-md transition-shadow duration-300"
                            style="animation-delay: 1.1s;">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Generate Report</h2>
                            <p class="text-gray-600 mb-6">Download or print detailed reports for your records and
                                presentations.</p>

                            <form action="generate-report.php" method="post" class="space-y-4">
                                <div>
                                    <label for="report_period" class="block text-sm font-medium text-gray-700 mb-1">Time
                                        Period</label>
                                    <select name="report_period" id="report_period"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        <option value="all">All Time</option>
                                        <option value="week">Past Week</option>
                                        <option value="month">Past Month</option>
                                        <option value="quarter">Past Quarter</option>
                                        <option value="year">Past Year</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>

                                <div id="custom_dates" class="hidden space-y-4">
                                    <div>
                                        <label for="start_date"
                                            class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                        <input type="date" name="start_date" id="start_date"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    </div>
                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End
                                            Date</label>
                                        <input type="date" name="end_date" id="end_date"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    </div>
                                </div>

                                <div>
                                    <label for="report_format"
                                        class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                                    <select name="report_format" id="report_format"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        <option value="pdf">PDF Document</option>
                                        <option value="excel">Excel Spreadsheet</option>
                                        <option value="print">Printable HTML</option>
                                    </select>
                                </div>

                                <div class="pt-2">
                                    <button type="submit"
                                        class="w-full bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-md font-medium transition-colors duration-300 flex items-center justify-center">
                                        <i class="fas fa-file-download mr-2"></i> Generate Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle custom date fields based on period selection
        const reportPeriod = document.getElementById('report_period');
        const customDates = document.getElementById('custom_dates');

        if (reportPeriod && customDates) {
            reportPeriod.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDates.classList.remove('hidden');
                } else {
                    customDates.classList.add('hidden');
                }
            });
        }

        // Status Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Resolved', 'Rejected'],
                    datasets: [{
                        data: [
                            <?= $stats['pending_issues'] ?>,
                            <?= $stats['in_progress_issues'] ?>,
                            <?= $stats['resolved_issues'] ?>,
                            <?= $stats['rejected_issues'] ?>
                        ],
                        backgroundColor: [
                            '#FCD34D', // Amber/Yellow
                            '#60A5FA', // Blue
                            '#34D399', // Green
                            '#F87171' // Red
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        // Monthly Trend Chart
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trend_labels) ?>,
                    datasets: [{
                        label: 'Issues Reported',
                        data: <?= json_encode($trend_data) ?>,
                        borderColor: '#D97706',
                        backgroundColor: 'rgba(217, 119, 6, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Electoral Area Chart
        const areaCtx = document.getElementById('areaChart');
        if (areaCtx && <?= !empty($area_data) ? 'true' : 'false' ?>) {
            new Chart(areaCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($area_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($area_data) ?>,
                        backgroundColor: [
                            '#F59E0B', // Amber
                            '#10B981', // Green
                            '#3B82F6', // Blue
                            '#8B5CF6', // Purple
                            '#EC4899', // Pink
                            '#EF4444', // Red
                            '#F97316', // Orange
                            '#14B8A6', // Teal
                            '#6366F1', // Indigo
                            '#A855F7' // Purple
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        // Severity Distribution Chart
        const severityCtx = document.getElementById('severityChart');
        if (severityCtx) {
            new Chart(severityCtx, {
                type: 'bar',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low'],
                    datasets: [{
                        label: 'Issues',
                        data: <?= json_encode($severity_data) ?>,
                        backgroundColor: [
                            '#EF4444', // Red (Critical)
                            '#F97316', // Orange (High)
                            '#F59E0B', // Amber (Medium)
                            '#10B981' // Green (Low)
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // People Affected Chart
        const peopleAffectedCtx = document.getElementById('peopleAffectedChart');
        if (peopleAffectedCtx && <?= !empty($top_issues) ? 'true' : 'false' ?>) {
            new Chart(peopleAffectedCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($top_issues_labels) ?>,
                    datasets: [{
                        label: 'People Affected',
                        data: <?= json_encode($top_issues_data) ?>,
                        backgroundColor: 'rgba(217, 119, 6, 0.7)',
                        borderColor: '#D97706',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Animation for staggered items
        document.querySelectorAll('.staggered-item').forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = "1";
            }, 100 * index);
        });
    });
    </script>
</body>

</html>